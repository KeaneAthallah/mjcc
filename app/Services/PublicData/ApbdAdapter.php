<?php

namespace App\Services\PublicData;

use App\Models\ApbdRecord;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;

class ApbdAdapter implements PublicDataSourceAdapter
{
    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $baseUrl = config('public_data.apbd.base_url', 'https://djpk.kemenkeu.go.id');
        $pagePath = config('public_data.apbd.url', '/portal/data/tkdd');
        $year = (int) ($config['year'] ?? date('Y'));

        try {
            $region = $this->resolveRegionCodes($baseUrl, $year);
            $response = $this->http->get($baseUrl.$pagePath, [
                'type' => 'tkdd',
                'provinsi' => $region['province_code'],
                'pemda' => $region['pemda_code'],
                'tahun' => (string) $year,
            ]);

            if (! $response->successful()) {
                return ['data' => null, 'metadata' => ['error' => 'HTTP '.$response->status()], 'available' => false];
            }

            $html = $response->body();
            $data = $this->parseApbdPage($html);

            return [
                'data' => $data,
                'metadata' => [
                    'source_url' => $baseUrl.$pagePath,
                    'region_code' => $region['region_code'],
                    'region_name' => $region['region_name'],
                    'province_code' => $region['province_code'],
                    'pemda_code' => $region['pemda_code'],
                    'year' => $year,
                ],
                'available' => $data !== null && count($data) > 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('APBD fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    /**
     * Resolve the DJPK portal internal province/pemda codes for Kabupaten Morowali.
     *
     * @return array{province_code: string, pemda_code: string, region_code: string, region_name: string}
     */
    private function resolveRegionCodes(string $baseUrl, int $year): array
    {
        $regionName = config('public_data.apbd.region_name', 'Kabupaten Morowali');
        $defaultProvince = (string) config('public_data.apbd.province_code', '19');
        $defaultPemda = (string) config('public_data.apbd.pemda_code', '06');

        $provinceCode = $defaultProvince;
        $pemdaCode = $defaultPemda;

        try {
            $provinces = $this->http->get($baseUrl.'/portal/provinsi/'.$year)->json();

            if (is_array($provinces)) {
                foreach ($provinces as $code => $name) {
                    if (str_contains((string) $name, 'Sulawesi Tengah')) {
                        $provinceCode = (string) $code;
                        break;
                    }
                }
            }

            $pemdas = $this->http->get($baseUrl.'/portal/pemda/'.$provinceCode.'/'.$year)->json();

            if (is_array($pemdas)) {
                foreach ($pemdas as $code => $name) {
                    if ($this->isKabupatenMorowali((string) $name)) {
                        $pemdaCode = (string) $code;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::debug('APBD region discovery fallback to defaults', ['error' => $e->getMessage()]);
        }

        return [
            'province_code' => $provinceCode,
            'pemda_code' => $pemdaCode,
            'region_code' => config('public_data.apbd.region_code', '72.03'),
            'region_name' => $regionName,
        ];
    }

    private function isKabupatenMorowali(string $name): bool
    {
        return preg_match('/morowali\b/i', $name) === 1
            && preg_match('/utara/i', $name) !== 1;
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false) || empty($raw['data'])) {
            return [];
        }

        $records = [];
        $year = $raw['metadata']['year'] ?? (int) date('Y');
        $region = $raw['metadata']['region_name'] ?? 'Kabupaten Morowali';

        foreach ($raw['data'] as $item) {
            $indicator = $item['indicator'] ?? '';

            if ($indicator === '') {
                continue;
            }

            $target = (float) ($item['target'] ?? '0');
            $realization = (float) ($item['realization'] ?? '0');
            $percentage = $target > 0 ? round(($realization / $target) * 100, 2) : 0;

            // Guard against COLUMNS overflow on extremely skewed rows.
            $percentage = $percentage > 1000 ? null : $percentage;

            $category = 'Lainnya';
            $lower = strtolower($indicator);
            if (
                str_contains($lower, 'pendapatan')
                || str_contains($lower, 'transfer ke daerah')
                || str_contains($lower, 'dana desa')
                || str_contains($lower, 'dana alokasi')
                || str_contains($lower, 'dana bagi hasil')
                || str_contains($lower, 'dbh')
            ) {
                $category = 'Pendapatan';
            } elseif (str_contains($lower, 'belanja')) {
                $category = 'Belanja';
            } elseif (str_contains($lower, 'pembiayaan')) {
                $category = 'Pembiayaan';
            }

            $records[] = [
                'year' => $year,
                'indicator' => $indicator,
                'category' => $category,
                'target_value' => $target ?: null,
                'realization_value' => $realization ?: null,
                'percentage' => $percentage ?: null,
                'previous_value' => null,
                'unit' => $item['unit'] ?? 'Rp',
                'region' => $region,
                'source_indicator_code' => $item['code'] ?? null,
            ];
        }

        return $records;
    }

    public function store(array $records, PublicDataSource $source): array
    {
        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $dedupeKey = ApbdRecord::dedupeKey(
                $record['year'],
                $record['indicator'],
                $record['region']
            );

            $existing = ApbdRecord::where('dedupe_key', $dedupeKey)->first();

            if ($existing) {
                $existing->update($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $updated++;
            } else {
                ApbdRecord::create($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        $total = ApbdRecord::count();
        $latestYear = ApbdRecord::max('year');
        $totalApbd = ApbdRecord::where('year', $latestYear)
            ->where('category', 'Pendapatan')
            ->sum('realization_value');

        return [
            'total_records' => $total,
            'latest_year' => $latestYear,
            'total_apbd' => $totalApbd,
            'indicators' => ApbdRecord::where('year', $latestYear)->distinct()->count('indicator'),
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Monitoring APBD';
    }

    private function parseApbdPage(?string $html): ?array
    {
        if (! $html) {
            return null;
        }

        // The DJPK portal renders rows as self-closing <tr/> tags, so normalize
        // them to closing tags before matching row boundaries.
        $html = preg_replace('/<tr[^>]*\/>/i', '</tr>', $html);

        $data = [];

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $rowMatches);

        foreach ($rowMatches[1] as $row) {
            if (preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cellMatches)) {
                $cells = array_map(fn ($c) => trim(preg_replace('/\s+/', ' ', strip_tags($c))), $cellMatches[1]);

                if (count($cells) < 4) {
                    continue;
                }

                $label = $cells[1] ?? '';

                if ($label === '' || $label === '&nbsp;') {
                    continue;
                }

                $data[] = [
                    'indicator' => $label,
                    'target' => $this->parseAmount($cells[2] ?? '0'),
                    'realization' => $this->parseAmount($cells[3] ?? '0'),
                    'percentage' => $this->parseAmount($cells[4] ?? '0'),
                    'unit' => 'Rp',
                    'code' => $cells[0] ?? null,
                ];
            }
        }

        return $data ?: null;
    }

    /**
     * Convert "661.665,87 M" style amounts into a float-safe string.
     */
    private function parseAmount(?string $value): string
    {
        $clean = preg_replace('/[^0-9.,-]/', '', (string) $value);

        return str_replace(['.', ','], ['', '.'], $clean);
    }
}
