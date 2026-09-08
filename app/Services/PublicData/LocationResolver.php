<?php

namespace App\Services\PublicData;

/**
 * Resolves Satu Data Morowali location strings to map coordinates.
 *
 * The portal records locations loosely: the same place appears as "Witaponda"
 * and "Wita Ponda", "Bumiraya" and "Bumi Raya", "Kep Sambori" and "SOMBORI
 * KEPUALAUAN". This resolver normalizes those aliases to the canonical
 * kecamatan name and returns its coordinates, keeping geographic sense in one
 * place that both scraping (write coordinates) and the map (render) can reuse.
 *
 * Locations that are not real places ("Jumlah", "Jumlah kab/kota",
 * "Kabupaten", "Angka Kematian dilaporkan") resolve to null and never produce
 * map markers.
 */
class LocationResolver
{
    /**
     * Canonical kecamatan name => [latitude, longitude]. Coordinates are the
     * Wikipedia geohack values for the current (post-2020) kecamatan division
     * of Kabupaten Morowali. Regency-level records use the centroid of those
     * kecamatan.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    private const GAZETTEER = [
        'Bahodopi' => [-2.79611745, 122.1265571],
        'Bumi Raya' => [-2.21626438, 121.72478897],
        'Bungku Barat' => [-2.35654379, 121.85178321],
        'Bungku Pesisir' => [-2.99124204, 122.27414597],
        'Bungku Selatan' => [-2.8936, 122.105],
        'Bungku Tengah' => [-2.5131, 121.7864],
        'Bungku Timur' => [-2.68568262, 121.99162626],
        'Menui Kepulauan' => [-3.2078, 122.3789],
        'Sombori Kepulauan' => [-3.03, 122.5],
        'Wita Ponda' => [-2.21591805, 121.62234363],
        'Kabupaten Morowali' => [-2.69062683, 122.036154514],
    ];

    /**
     * Lower-normalized portal spellings mapped to the canonical name.
     *
     * @var array<string, string>
     */
    private const ALIASES = [
        'witaponda' => 'Wita Ponda',
        'bumiraya' => 'Bumi Raya',
        'sombori kepualauan' => 'Sombori Kepulauan',
        'kep sambori' => 'Sombori Kepulauan',
        'kep.. sambori' => 'Sombori Kepulauan',
        'morowali' => 'Kabupaten Morowali',
        'kabupaten morowali' => 'Kabupaten Morowali',
    ];

    /**
     * Resolve a location string to its canonical name and coordinates.
     *
     * @return array{name: string, latitude: float, longitude: float}|null
     */
    public function resolve(string $location): ?array
    {
        $name = $this->canonicalName($location);

        if ($name === null) {
            return null;
        }

        $coordinates = self::GAZETTEER[$name];

        return [
            'name' => $name,
            'latitude' => $coordinates[0],
            'longitude' => $coordinates[1],
        ];
    }

    /**
     * Normalize a portal location string to the canonical kecamatan name, or
     * null when the string does not describe one of the known places.
     */
    public function canonicalName(?string $location): ?string
    {
        $key = $this->normalizeKey($location ?? '');

        if ($key === '') {
            return null;
        }

        $name = self::ALIASES[$key] ?? $this->title($key);

        return isset(self::GAZETTEER[$name]) ? $name : null;
    }

    private function normalizeKey(string $location): string
    {
        $key = mb_strtolower(trim($location));

        $key = preg_replace('/\s+/', ' ', $key) ?? $key;

        return trim($key, " \t\n\r\0\x0B.");
    }

    private function title(string $key): string
    {
        return mb_convert_case($key, MB_CASE_TITLE, 'UTF-8');
    }
}
