<?php

namespace App\Services\DataImport;

use App\Models\Kecamatan;

/**
 * Upserts kecamatan master rows from verified, resolved locations. Only
 * locations the LocationResolver gazetteer confirms become master records, and
 * regency-level summaries ("Kabupaten Morowali") are deliberately excluded so
 * the master table stays a genuine kecamatan list.
 */
class KecamatanImporter
{
    /**
     * @param  array<int, array{name: string, latitude: float, longitude: float}>  $locations
     * @return array{created: int, updated: int}
     */
    public function import(array $locations): array
    {
        $counts = ['created' => 0, 'updated' => 0];

        $seen = [];

        foreach ($locations as $location) {
            $name = trim((string) ($location['name'] ?? ''));

            if ($name === '' || ! self::isDistrict($name)) {
                continue;
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $counts[$this->upsert(
                $name,
                isset($location['latitude']) ? (float) $location['latitude'] : null,
                isset($location['longitude']) ? (float) $location['longitude'] : null,
            )]++;
        }

        return $counts;
    }

    /**
     * Create the kecamatan when missing, otherwise refresh geography only.
     * Existing rows are never deactivated by the import.
     *
     * @return string one of 'created' | 'updated'
     */
    public function upsert(string $name, ?float $latitude = null, ?float $longitude = null): string
    {
        $existing = Kecamatan::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($existing === null) {
            Kecamatan::create([
                'name' => $name,
                'latitude' => Coordinates::valid($latitude, $longitude) ? $latitude : null,
                'longitude' => Coordinates::valid($latitude, $longitude) ? $longitude : null,
                'is_active' => true,
            ]);

            return 'created';
        }

        $existing->is_active = true;

        if (Coordinates::valid($latitude, $longitude)) {
            $existing->latitude = $latitude;
            $existing->longitude = $longitude;
        }

        if ($existing->isDirty()) {
            $existing->save();
        }

        return 'updated';
    }

    /**
     * Regina-level summaries must never become kecamatan master rows.
     */
    public static function isDistrict(string $name): bool
    {
        return mb_strtolower(trim($name)) !== 'kabupaten morowali';
    }
}
