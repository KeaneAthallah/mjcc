<?php

namespace App\Services\DataImport;

use App\Models\Kecamatan;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;

/**
 * Imports per-entity school datasets (a row per school, e.g. "Data Jumlah
 * Siswa Menurut Sekolah") into the schools master table. Names that carry a
 * known kecamatan suffix are matched against the gazetteer; rows whose location
 * cannot be verified are skipped rather than guessed.
 */
class SchoolImporter extends EntityImporter
{
    public function sector(): string
    {
        return 'pendidikan';
    }

    /**
     * @return class-string<Model>
     */
    protected function modelClass(): string
    {
        return School::class;
    }

    /**
     * @return array<int, string>
     */
    protected function nameColumnPatterns(): array
    {
        return [
            '/^nama\s+(sekolah|satuan|satuan\s+pendidikan|sd|smp|smpn|smk|sma|madrasah|mts|ma|mi)\b/i',
            '/^(sekolah|satuan\s+pendidikan|nama)\b/i',
        ];
    }

    protected function requiresKecamatan(): bool
    {
        return true;
    }

    protected function inferType(array $row, string $name): ?string
    {
        foreach ($row as $header => $value) {
            if (preg_match('/^(jenjang|jenis|tingkat|level)\b/i', (string) $header) === 1) {
                $type = $this->typeFromLabel((string) $value);

                if ($type !== null) {
                    return $type;
                }

                break;
            }
        }

        return $this->typeFromLabel($name);
    }

    protected function findExisting(string $name, string $type): ?Model
    {
        return School::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    protected function toFillable(array $row, string $name, string $type, ?Kecamatan $kecamatan): array
    {
        $data = [
            'name' => $name,
            'school_type' => $type,
            'kecamatan_id' => $kecamatan?->id,
        ];

        $npsn = $this->cell($row, ['npsn', 'nomor pokok sekolah', 'nomor induk sekolah']);

        if ($npsn !== null) {
            $data['npsn'] = $npsn;
        }

        $address = $this->cell($row, ['alamat', 'alamat sekolah', 'desa', 'kelurahan']);

        if ($address !== null) {
            $data['address'] = $address;
        }

        $coordinates = $this->explicitCoordinates($row) ?? $this->kecamatanCentroid($kecamatan);

        $data['latitude'] = $coordinates['latitude'] ?? null;
        $data['longitude'] = $coordinates['longitude'] ?? null;

        return $data;
    }

    private function typeFromLabel(string $label): ?string
    {
        if (preg_match('/\b(sd|sdn|sdit|sdlb|mi|mis|min)\b/i', $label) === 1) {
            return School::TYPE_SD;
        }

        if (preg_match('/\b(smp|smpn|smpit|mts|mtsn)\b/i', $label) === 1) {
            return School::TYPE_SMP;
        }

        if (preg_match('/\b(smk|smkn)\b/i', $label) === 1) {
            return 'SMK';
        }

        if (preg_match('/\b(sma|sman|smait|ma|man)\b/i', $label) === 1) {
            return 'SMA';
        }

        return null;
    }
}
