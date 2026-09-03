<?php

namespace App\Support;

/**
 * Centralizes the two target regions that the crawler is allowed to persist:
 * Kabupaten Morowali (Kemendagri 72.06 / BPS 7206) and Kabupaten Morowali
 * Utara (Kemendagri 72.12 / BPS 7212), both in Sulawesi Tengah (72).
 *
 * Different sources expose different region code formats, so this class also
 * stores source-to-canonical mappings.
 */
class TargetRegionService
{
    public const PROVINCE_CODE = '72';

    public const MOROWALI_CODE = '7206';

    public const MOROWALI_UTARA_CODE = '7212';

    /**
     * Canonical codes (BPS-style, 4-digit) currently persisted.
     *
     * @return string[]
     */
    public function getCodes(): array
    {
        return [
            self::MOROWALI_CODE,
            self::MOROWALI_UTARA_CODE,
        ];
    }

    /**
     * Canonical codes with the province prefix removed (2-digit raw values).
     *
     * @return string[]
     */
    public function getShortCodes(): array
    {
        return [
            '06',
            '12',
        ];
    }

    /**
     * The dotted Kemendagri-style codes (72.06 / 72.12).
     *
     * @return string[]
     */
    public function getDottedCodes(): array
    {
        return [
            '72.06',
            '72.12',
        ];
    }

    /**
     * Names used to match records coming from text-based sources. The
     * collection keeps official and colloquial variants.
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return [
            'morowali',
            'kab. morowali',
            'kabupaten morowali',
            'morowali utara',
            'kab. morowali utara',
            'kabupaten morowali utara',
        ];
    }

    public function isMorowali(string $code): bool
    {
        return $this->normalizeCode($code) === self::MOROWALI_CODE;
    }

    public function isMorowaliUtara(string $code): bool
    {
        return $this->normalizeCode($code) === self::MOROWALI_UTARA_CODE;
    }

    /**
     * Whether a code identifies one of the two target regions. Accepts a
     * canonical (7206), dotted (72.06), or raw-short-canonical code.
     */
    public function isTargetRegion(?string $code): bool
    {
        if ($code === null || $code === '') {
            return false;
        }

        return in_array($this->normalizeCode($code), $this->getCodes(), true);
    }

    /**
     * Whether a region name string refers to one of the two target regions.
     * This is a fallback used only for sources that do not expose codes.
     */
    public function isTargetRegionName(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }

        $normalized = strtolower((string) preg_replace('/\s+/', ' ', trim($name)));

        return in_array($normalized, $this->getNames(), true);
    }

    /**
     * Normalize any supported code representation to the canonical 4-digit
     * BPS style (e.g. "72.06" -> "7206", "6" -> "7206", "07206" -> "7206").
     */
    public function normalizeCode(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        // Strip dots/spaces and leading province prefix patterns.
        $digits = preg_replace('/[^0-9]/', '', $code);

        if ($digits === null || $digits === '') {
            return null;
        }

        // If it is a full 6-digit code (province + kabupaten), drop the prefix.
        if (strlen($digits) >= 6) {
            return substr($digits, -4);
        }

        // 4-digit canonical BPS code.
        if (strlen($digits) >= 4) {
            return substr($digits, -4);
        }

        // 2-digit (or shorter) kabupaten code: build from the province prefix.
        return self::PROVINCE_CODE.str_pad($digits, 2, '0', STR_PAD_LEFT);
    }
}
