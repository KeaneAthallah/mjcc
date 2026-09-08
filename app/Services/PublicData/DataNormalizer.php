<?php

namespace App\Services\PublicData;

use App\Models\ExternalData;

/**
 * Converts a parsed Satu Data detail table into normalized long-format
 * datapoints. Only cells that parse as real numbers produce records: text-only
 * columns (year, location names, dimension ids) are used as row context, never
 * as values.
 */
class DataNormalizer
{
    public function __construct(private readonly LocationResolver $resolver) {}

    /**
     * @param  array{title?: string, headers?: array<int, string>, rows?: array<int, array<int, string>>, metadata?: array<string, string>}  $parsed
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $parsed, string $sector, string $source, string $sourceUrl, string $topic): array
    {
        $headers = array_map('strval', $parsed['headers'] ?? []);
        $rows = $parsed['rows'] ?? [];
        $metadata = $parsed['metadata'] ?? [];

        $dataset = trim((string) ($parsed['title'] ?? ''));

        if ($dataset === '') {
            return [];
        }

        $yearIdx = $this->yearColumn($headers);
        $locationIdx = $this->locationColumn($headers);

        $fallbackYear = $this->extractYear($dataset.' '.(string) ($metadata['period'] ?? ''));

        $records = [];

        foreach ($rows as $row) {
            $year = $yearIdx !== null ? $this->extractYear((string) ($row[$yearIdx] ?? '')) : $fallbackYear;
            $location = $locationIdx !== null ? trim((string) ($row[$locationIdx] ?? '')) : '';

            if ($location === '' || $location === '-' || strtolower($location) === 'null') {
                $location = 'Kabupaten Morowali';
            }

            $resolved = $this->resolver->resolve($location);

            foreach ($headers as $i => $header) {
                if ($i === $yearIdx || $i === $locationIdx || ! $this->isIndicatorColumn($header)) {
                    continue;
                }

                $cell = isset($row[$i]) ? trim((string) $row[$i]) : '';
                $value = $this->parseNumber($cell);

                if ($value === null) {
                    continue;
                }

                $indicator = $this->indicatorName($header);

                $records[] = [
                    'sector' => $sector,
                    'source' => $source,
                    'source_url' => $sourceUrl,
                    'dataset' => $dataset,
                    'topic' => $topic,
                    'year' => $year,
                    'location' => $location,
                    'latitude' => $resolved['latitude'] ?? null,
                    'longitude' => $resolved['longitude'] ?? null,
                    'indicator' => $indicator,
                    'value' => $value,
                    'unit' => $this->detectUnit($header),
                    'dedupe_key' => ExternalData::dedupeKey($sector, $source, $dataset, $year, $location, $indicator),
                    'raw_data' => [
                        'source_url' => $sourceUrl,
                        'topic' => $topic,
                        'headers' => $headers,
                        'row' => $row,
                        'metadata' => $metadata,
                    ],
                    'scraped_at' => now(),
                ];
            }
        }

        return $records;
    }

    private function yearColumn(array $headers): ?int
    {
        foreach ($headers as $i => $header) {
            if (preg_match('/tahun/i', $header) === 1 || preg_match('/^\d{4}$/', trim($header)) === 1) {
                return $i;
            }
        }

        return null;
    }

    private function locationColumn(array $headers): ?int
    {
        $priority = [
            '/kecamatan/i',
            '/^nama\s+kelurahan|^nama\s+desa/i',
            '/^nama\s+(sekolah|smp|smk|sd|madrasah|faskes|puskesmas|fasyankes|fasilitas|satuan|posyandu|ormas|bangunan)/i',
            '/^nama\s+kabupaten/i',
            '/^nama\s+provinsi/i',
            '/\b(kecamatan|kelurahan|desa)\b/i',
            '/\b(sekolah|faskes|puskesmas|fasyankes|posyandu|satuan|bangunan)\b/i',
        ];

        foreach ($priority as $pattern) {
            foreach ($headers as $i => $header) {
                if (preg_match('/\b(kode|id|no)\b/i', $header) === 1) {
                    continue;
                }

                if (preg_match($pattern, $header) === 1) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function isIndicatorColumn(string $header): bool
    {
        if (trim($header) === '') {
            return false;
        }

        return preg_match('/\b(kode|id|no)\b/i', $header) !== 1;
    }

    private function indicatorName(string $header): string
    {
        $name = trim($header);

        $name = preg_replace('/\s*\(.*?\)\s*$/', '', $name);

        return $name === '' ? 'Nilai' : $name;
    }

    private function detectUnit(string $header): ?string
    {
        if (preg_match('/persen|%/i', $header) === 1) {
            return '%';
        }

        if (preg_match('/jiwa/i', $header) === 1) {
            return 'Jiwa';
        }

        if (preg_match('/orang/i', $header) === 1) {
            return 'Orang';
        }

        if (preg_match('/sekolah|satuan|unit/i', $header) === 1) {
            return 'Unit';
        }

        if (preg_match('/ruang|kelas/i', $header) === 1) {
            return 'Ruang';
        }

        return null;
    }

    private function parseNumber(string $value): ?float
    {
        $value = trim($value);

        if ($value === '' || in_array($value, ['-', '—', '.', ','], true)) {
            return null;
        }

        $negative = str_starts_with($value, '-');
        $body = ltrim($value, '-+');

        // Indonesian numbers use a comma as decimal separator
        // ("1.234,5" = one thousand, two hundred thirty-four and a half).
        if (str_contains($body, ',')) {
            [$int, $dec] = explode(',', $body, 2);

            $int = preg_replace('/[^0-9]/', '', (string) $int);
            $dec = preg_replace('/[^0-9]/', '', (string) $dec);

            if ($int === '' && $dec === '') {
                return null;
            }

            $digits = $int.($dec !== '' ? '.'.$dec : '');
        } else {
            $dot = preg_replace('/[^0-9.]/', '', $body);

            if ($dot === '' || $dot === '.') {
                return null;
            }

            // Dot as thousands separator ("1.234" = 1234) beats dot-as-decimal.
            $digits = preg_match('/^\d{1,3}(\.\d{3})+$/', $dot) === 1
                ? str_replace('.', '', $dot)
                : $dot;
        }

        if (! is_numeric($digits)) {
            return null;
        }

        return $negative ? -(float) $digits : (float) $digits;
    }

    private function extractYear(string $value): ?int
    {
        if (preg_match_all('/(19|20)\d{2}/', $value, $m) === 1 && count($m[0]) === 1) {
            return (int) $m[0][0];
        }

        return null;
    }
}
