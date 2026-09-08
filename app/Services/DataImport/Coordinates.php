<?php

namespace App\Services\DataImport;

/**
 * Lightweight coordinate sanity checks shared by the importers. Coordinates
 * that are missing, out of range, or the classic (0,0) placeholder are never
 * trusted into the master tables.
 */
final class Coordinates
{
    public static function valid(?float $latitude, ?float $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        if (abs($latitude) < 0.0000001 && abs($longitude) < 0.0000001) {
            return false;
        }

        return $latitude >= -90 && $latitude <= 90
            && $longitude >= -180 && $longitude <= 180;
    }
}
