<?php

namespace App\Services\PublicData;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\Tipkamtikmas;
use InvalidArgumentException;

/**
 * Imports the real master-data snapshots (Kemkes puskesmas, SP2KP markets)
 * into the master tables and retires the seeded placeholder rows.
 *
 * Seeder rows are frozen with source 'seed' (by the migration, or tagged at
 * the first run). Rows created later through the CRUD keep source null and are
 * never touched. Polsek is a special case: its rows are canonical places kept
 * active and simply re-tagged as 'manual'.
 */
class MasterDataImport
{
    public const SOURCE_HEALTH = 'kemkes';

    public const SOURCE_MARKET = 'sp2kp';

    public const SOURCE_SEED = 'seed';

    public const SOURCE_MANUAL = 'manual';

    /**
     * Curated puskesmas attributes the Kemkes SISDMK snapshot does not carry.
     * The kecamatan and address for each puskesmas were verified from official
     * sources (morowalikab.go.id, BPJS Faskes) and cross-checked references.
     *
     * @var array<string, array{0: string, 1: string|null}>
     */
    private const PUSKESMAS_LOCATIONS = [
        'Puskesmas Bahodopi' => ['Bahodopi', 'Ds. Keurea, Kec. Bahodopi'],
        'Puskesmas Bahonsuai' => ['Bumi Raya', 'Ds. Parilangke, Kec. Bumi Raya'],
        'Puskesmas Bahomotefe' => ['Bungku Tengah', 'Ds. Bahomatefe, Kec. Bungku Tengah'],
        'Puskesmas Bungku' => ['Bungku Tengah', 'Kel. Matano, Kec. Bungku Tengah'],
        'Puskesmas Fonuasingko' => ['Bungku Tengah', 'Ds. Bahomohoni, Kec. Bungku Tengah'],
        'Puskesmas Kaleroang' => ['Bungku Selatan', 'Ds. Kaleroang, Kec. Bungku Selatan'],
        'Puskesmas Lafeu' => ['Bungku Pesisir', 'Desa Lafeu, Kec. Bungku Pesisir'],
        "Puskesmas La'antula Jaya" => ['Wita Ponda', 'Ds. Lantula Jaya, Kec. Wita Ponda'],
        'Puskesmas Tanjung Harapan' => ['Menui Kepulauan', null],
        'Puskesmas Ulunambo' => ['Menui Kepulauan', 'Kel. Ulunambo, Kec. Menui Kepulauan'],
        'Puskesmas Wosu' => ['Bungku Barat', 'Ds. Wosu, Kec. Bungku Barat'],
    ];

    /**
     * Import both snapshots and retire seeded placeholders.
     *
     * @return array<string, mixed>
     */
    public function import(string $puskesmasPath, string $marketsPath): array
    {
        $puskesmas = $this->readSnapshot($puskesmasPath, 'puskesmas');
        $markets = $this->readSnapshot($marketsPath, 'markets');

        $this->tagSeedsOnce();
        $polsekTagged = $this->markPolsekManual();

        [$created, $updated, $touchedHealth] = $this->importPuskesmas($puskesmas['puskesmas']);
        [$marketCreated, $marketUpdated, $touchedMarkets] = $this->importMarkets($markets['markets']);

        $deactivated = [
            'health' => $this->deactivateHealth($touchedHealth),
            'markets' => $this->deactivateMarkets($touchedMarkets),
            'poskamlings' => $this->deactivatePoskamlings(),
            'tipkamtikmas' => $this->deactivateTipkamtikmas(),
        ];

        return [
            'puskesmas' => [
                'created' => $created,
                'updated' => $updated,
                'total' => count($puskesmas['puskesmas'] ?? []),
            ],
            'markets' => [
                'created' => $marketCreated,
                'updated' => $marketUpdated,
                'total' => count($markets['markets'] ?? []),
            ],
            'polsek_tagged' => $polsekTagged,
            'deactivated' => $deactivated,
            'captured_at' => $puskesmas['captured_at'] ?? $markets['captured_at'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function readSnapshot(string $path, string $key): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException(sprintf('Snapshot tidak ditemukan: %s', $path));
        }

        $snapshot = json_decode((string) file_get_contents($path), true);

        if (! is_array($snapshot) || ! isset($snapshot[$key]) || ! is_array($snapshot[$key])) {
            throw new InvalidArgumentException(sprintf('Snapshot tidak valid (butuh key "%s"): %s', $key, $path));
        }

        return $snapshot;
    }

    /**
     * On the first run only, existing placeholder rows that predate the source
     * column (source null) are frozen as 'seed' so later imports never confuse
     * them with CRUD-created rows.
     */
    private function tagSeedsOnce(): void
    {
        foreach ([HealthFacility::class, Poskamling::class, Tipkamtikmas::class, Market::class] as $model) {
            if ($model::where('source', self::SOURCE_SEED)->exists()) {
                continue;
            }

            $model::whereNull('source')->update(['source' => self::SOURCE_SEED]);
        }
    }

    /**
     * Polsek rows are canonical places; keep them active and tag them so a
     * future cleanup never retires them as placeholders.
     */
    private function markPolsekManual(): int
    {
        $polseks = Polsek::query()
            ->where(fn ($query) => $query->whereNull('source')->orWhere('source', self::SOURCE_SEED))
            ->get();

        foreach ($polseks as $polsek) {
            $polsek->update(['source' => self::SOURCE_MANUAL]);
        }

        return $polseks->count();
    }

    /**
     * @return array{0: int, 1: int, 2: array<int, int>}
     */
    private function importPuskesmas(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $touched = [];

        foreach ($rows as $row) {
            $name = $this->displayName((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $attributes = $this->puskesmasAttributes($row, $name);

            $existing = HealthFacility::where('facility_type', HealthFacility::TYPE_PUSKESMAS)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if ($existing instanceof HealthFacility) {
                $existing->update($attributes);
                $updated++;
                $touched[] = $existing->id;
            } else {
                $touched[] = HealthFacility::create($attributes)->id;
                $created++;
            }
        }

        return [$created, $updated, $touched];
    }

    /**
     * @return array{0: int, 1: int, 2: array<int, int>}
     */
    private function importMarkets(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $touched = [];

        foreach ($rows as $row) {
            $name = trim((string) ($row['nama'] ?? ''));

            if ($name === '') {
                continue;
            }

            $attributes = $this->marketAttributes($row, $name);

            $existing = Market::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

            if ($existing instanceof Market) {
                $existing->update($attributes);
                $updated++;
                $touched[] = $existing->id;
            } else {
                $touched[] = Market::create($attributes)->id;
                $created++;
            }
        }

        return [$created, $updated, $touched];
    }

    /**
     * @return array<string, mixed>
     */
    private function puskesmasAttributes(array $row, string $name): array
    {
        [$kecamatanName, $address] = $this->puskesmasLocation($name);

        return [
            'name' => $name,
            'facility_type' => HealthFacility::TYPE_PUSKESMAS,
            'kecamatan_id' => $this->resolveKecamatanByName($kecamatanName),
            'address' => $address,
            'latitude' => null,
            'longitude' => null,
            'condition' => 'baik',
            'beds' => 0,
            'doctors' => (int) ($row['dokter'] ?? 0),
            'nurses' => (int) ($row['perawat'] ?? 0),
            'midwives' => (int) ($row['bidan'] ?? 0),
            'status' => 'aktif',
            'phone' => null,
            'description' => trim((string) ($row['jenis'] ?? '')) ?: null,
            'source' => self::SOURCE_HEALTH,
        ];
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function puskesmasLocation(string $name): array
    {
        return self::PUSKESMAS_LOCATIONS[$name] ?? ['', null];
    }

    private function resolveKecamatanByName(string $name): ?int
    {
        if ($name === '') {
            return null;
        }

        return Kecamatan::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function marketAttributes(array $row, string $name): array
    {
        return [
            'name' => $name,
            'kecamatan_id' => $this->resolveKecamatan((string) ($row['kode_kecamatan'] ?? '')),
            'address' => trim((string) ($row['alamat'] ?? '')) ?: null,
            'latitude' => $this->nullableDecimal($row['latitude'] ?? null),
            'longitude' => $this->nullableDecimal($row['longitude'] ?? null),
            'status' => 'aktif',
            'source' => self::SOURCE_MARKET,
        ];
    }

    /**
     * Maps a SP2KP "7206051" BPS-style code onto a kecamatan row.
     */
    private function resolveKecamatan(string $code): ?int
    {
        if (! preg_match('/^\d{4}(\d{2})/', $code, $match)) {
            return null;
        }

        return Kecamatan::whereRaw('REPLACE(code, "72.06.", "") = ?', [$match[1]])->value('id');
    }

    /**
     * @param  array<int, int>  $touchedIds
     */
    private function deactivateHealth(array $touchedIds): int
    {
        return HealthFacility::where('status', 'aktif')
            ->where('source', self::SOURCE_SEED)
            ->whereNotIn('id', $touchedIds)
            ->update(['status' => 'tidak aktif']);
    }

    /**
     * @param  array<int, int>  $touchedIds
     */
    private function deactivateMarkets(array $touchedIds): int
    {
        return Market::where('status', 'aktif')
            ->where('source', self::SOURCE_SEED)
            ->whereNotIn('id', $touchedIds)
            ->update(['status' => 'tidak aktif']);
    }

    private function deactivatePoskamlings(): int
    {
        return Poskamling::where('is_active', true)
            ->where('source', self::SOURCE_SEED)
            ->update(['is_active' => false, 'status' => 'tidak aktif']);
    }

    private function deactivateTipkamtikmas(): int
    {
        return Tipkamtikmas::where('status', 'aktif')
            ->where('source', self::SOURCE_SEED)
            ->update(['status' => 'tidak aktif']);
    }

    private function displayName(string $raw): string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return '';
        }

        $withoutPrefix = (string) preg_replace('/^(UPTD\s+)?PUSKESMAS\s+/i', '', $raw);

        return 'Puskesmas '.mb_convert_case($withoutPrefix, MB_CASE_TITLE, 'UTF-8');
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
