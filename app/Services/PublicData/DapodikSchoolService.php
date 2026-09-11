<?php

namespace App\Services\PublicData;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\School;
use InvalidArgumentException;

/**
 * Imports the per-school Dapodik snapshot into the schools master table.
 *
 * The snapshot is produced by scripts/dapodik/dapodik-capture.cjs: the Dapodik
 * portal rejects plain HTTP clients on its school endpoints (SafeLine WAF), so
 * the rows are captured through a real browser once and imported from JSON.
 * Each snapshot row is a /api/detail-sekolah record (latest semester) and maps
 * onto the School columns the education dashboards read.
 */
class DapodikSchoolService
{
    public const SOURCE = 'dapodik';

    /**
     * Import every Dapodik jenjang row (SD/SMP/SMA/SMK/SLB) in a snapshot,
     * creating or updating schools.
     *
     * @param  string  $path  Absolute path to the snapshot JSON file.
     * @return array{created: int, updated: int, skipped: int, no_kecamatan: int, deactivated: int, schools_total: int, semester: string|null, captured_at: string|null}
     */
    public function import(string $path, bool $replace = false): array
    {
        $snapshot = $this->readSnapshot($path);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $noKecamatan = 0;
        $touchedIds = [];

        foreach ($snapshot['schools'] as $row) {
            $type = $this->mapType((string) ($row['bentuk_pendidikan'] ?? ''));

            if ($type === null) {
                $skipped++;

                continue;
            }

            $name = trim((string) ($row['nama'] ?? ''));

            if ($name === '') {
                $skipped++;

                continue;
            }

            $kecamatan = $this->resolveKecamatan($row);

            if ($kecamatan === null) {
                $noKecamatan++;
                $skipped++;

                continue;
            }

            $kelurahan = $this->resolveKelurahan($row, $kecamatan);

            $existing = $this->findExisting($name, (string) ($row['npsn'] ?? ''));

            $attributes = $this->toFillable($row, $name, $type, $kecamatan, $kelurahan);

            if ($existing instanceof School) {
                $existing->update($attributes);
                $updated++;
                $touchedIds[] = $existing->id;
            } else {
                $touchedIds[] = School::create($attributes)->id;
                $created++;
            }
        }

        $deactivated = $replace ? $this->deactivateMissing($touchedIds) : 0;

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'no_kecamatan' => $noKecamatan,
            'deactivated' => $deactivated,
            'schools_total' => count($snapshot['schools']),
            'semester' => $snapshot['semester'] ?? null,
            'captured_at' => $snapshot['captured_at'] ?? null,
        ];
    }

    /**
     * @return array{schools: array<int, array<string, mixed>>, semester?: string, captured_at?: string}
     */
    private function readSnapshot(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException(sprintf('Snapshot Dapodik tidak ditemukan: %s', $path));
        }

        $snapshot = json_decode((string) file_get_contents($path), true);

        if (! is_array($snapshot) || ! isset($snapshot['schools']) || ! is_array($snapshot['schools'])) {
            throw new InvalidArgumentException('Snapshot Dapodik tidak valid (butuh key "schools").');
        }

        return $snapshot;
    }

    private function mapType(string $bentuk): ?string
    {
        return match (mb_strtoupper($bentuk)) {
            'SD' => School::TYPE_SD,
            'SMP' => School::TYPE_SMP,
            'SMA' => School::TYPE_SMA,
            'SMK' => School::TYPE_SMK,
            'SLB' => School::TYPE_SLB,
            default => null,
        };
    }

    private function resolveKecamatan(array $row): ?Kecamatan
    {
        $name = trim((string) ($row['kecamatan'] ?? ''));
        $name = trim((string) preg_replace('/^Kec\.\s*/i', '', $name));

        $canonical = $name === '' ? null : app(LocationResolver::class)->canonicalName($name);

        $query = Kecamatan::query();

        if ($canonical !== null) {
            $query->whereRaw('LOWER(name) = ?', [mb_strtolower($canonical)]);
        } elseif ($name !== '') {
            $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);
        } else {
            return null;
        }

        return $query->first();
    }

    private function resolveKelurahan(array $row, Kecamatan $kecamatan): ?Kelurahan
    {
        $name = trim((string) ($row['desa_kelurahan'] ?? ''));

        if ($name === '') {
            return null;
        }

        return Kelurahan::where('kecamatan_id', $kecamatan->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    private function findExisting(string $name, string $npsn): ?School
    {
        if ($npsn !== '') {
            return School::where('npsn', $npsn)->first();
        }

        return School::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function toFillable(array $row, string $name, string $type, Kecamatan $kecamatan, ?Kelurahan $kelurahan): array
    {
        $desa = trim((string) ($row['desa_kelurahan'] ?? ''));
        $jalan = trim((string) ($row['alamat_jalan'] ?? ''));
        $address = trim(implode(', ', array_filter([$jalan, $desa])));

        return [
            'name' => $name,
            'school_type' => $type,
            'kecamatan_id' => $kecamatan->id,
            'kelurahan_id' => $kelurahan?->id,
            'npsn' => (string) ($row['npsn'] ?? '') ?: null,
            'address' => $address ?: null,
            'latitude' => $this->nullableDecimal($row['lintang'] ?? null),
            'longitude' => $this->nullableDecimal($row['bujur'] ?? null),
            'condition' => $this->inferCondition($row),
            'students_male' => (int) ($row['pd_l'] ?? 0),
            'students_female' => (int) ($row['pd_p'] ?? 0),
            'teachers' => (int) ($row['jum_guru'] ?? 0),
            'classes' => (int) ($row['rombel'] ?? 0),
            'capacity' => (int) ($row['ruang_kelas'] ?? 0),
            'library_percentage' => $this->hasFacility($row, 'perpus'),
            'science_lab_percentage' => $this->hasFacility($row, 'lab_ipa'),
            'computer_lab_percentage' => $this->hasFacility($row, 'lab_kom'),
            'teacher_room_percentage' => $this->hasFacility($row, 'r_guru'),
            'toilet_percentage' => $this->hasAny($row, ['wc_guru', 'wc_siswa']),
            'worship_room_percentage' => 0,
            'is_active' => true,
            'source' => self::SOURCE,
        ];
    }

    private function inferCondition(array $row): string
    {
        $berat = (int) ($row['kelas_berat'] ?? 0);
        $sedang = (int) ($row['kelas_sedang'] ?? 0);

        if ($berat > 0) {
            return 'rusak_berat';
        }

        if ($sedang > 0) {
            return 'rusak_sedang';
        }

        return 'baik';
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function hasFacility(array $row, string $key): float
    {
        return ((int) ($row[$key] ?? 0)) > 0 ? 100 : 0;
    }

    private function hasAny(array $row, array $keys): float
    {
        foreach ($keys as $key) {
            if (((int) ($row[$key] ?? 0)) > 0) {
                return 100;
            }
        }

        return 0;
    }

    /**
     * Deactivate placeholder rows of any jenjang that the snapshot does not
     * contain. Only rows owned by this pipeline (source null or 'dapodik') are
     * touched, so rows produced by other sources are never retired.
     *
     * @param  array<int, int>  $touchedIds
     */
    private function deactivateMissing(array $touchedIds): int
    {
        return School::where('is_active', true)
            ->whereIn('school_type', School::SCHOOL_TYPES)
            ->where(function ($query): void {
                $query->whereNull('source')->orWhere('source', self::SOURCE);
            })
            ->whereNotIn('id', $touchedIds)
            ->update(['is_active' => false]);
    }
}
