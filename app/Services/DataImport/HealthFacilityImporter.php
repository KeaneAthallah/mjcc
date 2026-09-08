<?php

namespace App\Services\DataImport;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Model;

/**
 * Imports per-entity health datasets (a row per facility, e.g. "Akses
 * Penduduk terhadap Puskesmas") into the health_facilities master table.
 * Health rows may keep a null kecamatan because that column is nullable on the
 * master and the portal rarely pinpoints a facility's district.
 */
class HealthFacilityImporter extends EntityImporter
{
    public function sector(): string
    {
        return 'kesehatan';
    }

    /**
     * @return class-string<Model>
     */
    protected function modelClass(): string
    {
        return HealthFacility::class;
    }

    /**
     * @return array<int, string>
     */
    protected function nameColumnPatterns(): array
    {
        return [
            '/^nama\s+(puskesmas|pustu|fasyankes|faskes|fasilitas|klinik|rumah\s+sakit|rs|posyandu|poskesdes|polindes|puskesmas\s+pembantu)\b/i',
        ];
    }

    protected function requiresKecamatan(): bool
    {
        return false;
    }

    protected function inferType(array $row, string $name): ?string
    {
        foreach ($row as $header => $value) {
            if (preg_match('/^(jenis|tipe|type)\b/i', (string) $header) === 1) {
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
        return HealthFacility::where('facility_type', $type)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    protected function toFillable(array $row, string $name, string $type, ?Kecamatan $kecamatan): array
    {
        $data = [
            'name' => $name,
            'facility_type' => $type,
            'kecamatan_id' => $kecamatan?->id,
        ];

        $address = $this->cell($row, ['alamat', 'lokasi', 'desa', 'kelurahan']);

        if ($address !== null) {
            $data['address'] = $address;
        }

        $description = $this->cell($row, ['keterangan', 'catatan']);

        if ($description !== null) {
            $data['description'] = $description;
        }

        $coordinates = $this->explicitCoordinates($row) ?? $this->kecamatanCentroid($kecamatan);

        $data['latitude'] = $coordinates['latitude'] ?? null;
        $data['longitude'] = $coordinates['longitude'] ?? null;

        return $data;
    }

    private function typeFromLabel(string $label): ?string
    {
        $label = mb_strtolower(trim($label));

        if (str_contains($label, 'puskesmas') || str_contains($label, 'pkm')) {
            return HealthFacility::TYPE_PUSKESMAS;
        }

        if (str_contains($label, 'pustu') || str_contains($label, 'pembantu')) {
            return HealthFacility::TYPE_PUSTU;
        }

        if (str_contains($label, 'posyandu') || str_contains($label, 'poskesdes')) {
            return HealthFacility::TYPE_POSYANDU;
        }

        if (preg_match('/\b(rs|rsk|rumah\s+sakit)\b/i', $label) === 1) {
            return HealthFacility::TYPE_RS;
        }

        return null;
    }
}
