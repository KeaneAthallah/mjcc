<?php

namespace Database\Seeders;

/**
 * Generates deterministic, stored coordinates for individual records within a
 * kecamatan boundary. The offsets spread records around the kecamatan centre
 * without using randomness, so every map-enabled record has a stable,
 * real (stored) latitude/longitude in the database.
 */
final class CoordinateSeeder
{
    /**
     * Generate a coordinate offset around a kecamatan centre based on an index.
     *
     * @return array{latitude: float, longitude: float}
     */
    public static function around(float $lat, float $lng, int $index): array
    {
        $ring = $index % 5;
        $slot = intdiv($index, 5);

        $radius = 0.015 + $ring * 0.008;
        $angle = ($slot * 0.9) + ($ring * 0.6);

        $dLat = $radius * cos($angle);
        $dLng = $radius * sin($angle);

        return [
            'latitude' => round($lat + $dLat, 7),
            'longitude' => round($lng + $dLng, 7),
        ];
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    public static function near(float $lat, float $lng, int $slot): array
    {
        return self::around($lat, $lng, $slot);
    }
}
