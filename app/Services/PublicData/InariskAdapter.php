<?php

namespace App\Services\PublicData;

use App\Models\DisasterRiskIndex;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;

class InariskAdapter implements PublicDataSourceAdapter
{
    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $baseUrl = config('public_data.irbi.url', 'https://inarisk.bnpb.go.id');
        $provinceCode = config('public_data.irbi.province_code', '72');
        $historyCode = config('public_data.irbi.region_code', '72.06');

        try {
            $response = $this->http->get($baseUrl.'/irbi');

            if (! $response->successful()) {
                return ['data' => null, 'metadata' => ['error' => 'HTTP '.$response->status()], 'available' => false];
            }

            $regions = $this->parseRiskJson($response->body());
            $hazards = $this->fetchHazardNames($baseUrl);

            if ($regions === null) {
                return ['data' => null, 'metadata' => ['error' => 'irbimapdata tidak ditemukan'], 'available' => false];
            }

            return [
                'data' => $regions,
                'metadata' => [
                    'source_url' => $baseUrl,
                    'province_code' => $provinceCode,
                    'history_code' => $historyCode,
                    'hazards' => $hazards,
                ],
                'available' => count($regions) > 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('IRBI fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false) || empty($raw['data'])) {
            return [];
        }

        $metadata = $raw['metadata'];
        $provinceCode = $metadata['province_code'] ?? '72';
        $historyCode = $metadata['history_code'] ?? '72.06';
        $hazards = $metadata['hazards'] ?? [];
        $records = [];

        foreach ($raw['data'] as $region) {
            $regionCode = (string) ($region['KODE_KAB'] ?? '');

            if ($regionCode === '' || ! str_starts_with($regionCode, $provinceCode)) {
                continue;
            }

            $regionName = $region['name'] ?? $regionCode;
            $years = $this->yearKeys($region);
            $latestYear = $years !== [] ? max($years) : null;

            if ($latestYear === null) {
                continue;
            }

            // Riwayat lengkap hanya untuk wilayah target (Morowali),
            // provinsi lain hanya diambil tahun terbaru untuk peta.
            $isTarget = $regionCode === $historyCode;
            $selectedYears = $isTarget ? $years : [$latestYear];
            $classes = $region['kelas_ikd'] ?? [];

            foreach ($selectedYears as $year) {
                $scores = $region[$year] ?? [];

                if (! is_array($scores)) {
                    continue;
                }

                foreach ($scores as $hazardId => $index) {
                    if ($index === null) {
                        continue;
                    }

                    $hazardType = (string) ($hazards[$hazardId] ?? "Bahaya {$hazardId}");

                    $records[] = [
                        'region_name' => $regionName,
                        'region_code' => $regionCode,
                        'hazard_type' => $hazardType,
                        'risk_index' => (float) $index,
                        'risk_level' => $classes[$year][$hazardId] ?? null,
                        'vulnerability_index' => null,
                        'exposure_index' => null,
                        'capacity_index' => null,
                        'year' => (int) $year,
                        'latitude' => null,
                        'longitude' => null,
                    ];
                }
            }
        }

        return $records;
    }

    public function store(array $records, PublicDataSource $source): array
    {
        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $dedupeKey = hash('sha256', implode("\n", [
                $record['region_name'],
                $record['hazard_type'] ?? '',
                (string) ($record['year'] ?? ''),
            ]));

            $existing = DisasterRiskIndex::where('dedupe_key', $dedupeKey)->first();

            if ($existing) {
                $existing->update($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $updated++;
            } else {
                DisasterRiskIndex::create($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        $total = DisasterRiskIndex::count();
        $highRisk = DisasterRiskIndex::where('risk_level', 'Tinggi')->count();

        return [
            'total_records' => $total,
            'high_risk_areas' => $highRisk,
            'hazard_types' => DisasterRiskIndex::distinct()->count('hazard_type'),
            'regions' => DisasterRiskIndex::distinct()->count('region_name'),
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Indeks Risiko Bencana';
    }

    /**
     * @return array<int, mixed>|null
     */
    private function parseRiskJson(string $html): ?array
    {
        if (! preg_match('/var irbimapdata = (\[.*?\]);/s', $html, $match)) {
            return null;
        }

        $regions = json_decode($match[1], true);

        if (! is_array($regions)) {
            return null;
        }

        $names = [];

        if (preg_match('/var labelmap = (\{.*?\});/s', $html, $labelMatch)) {
            $names = json_decode($labelMatch[1], true) ?? [];
        }

        $named = [];

        foreach ($regions as $region) {
            if (! is_array($region) || ! isset($region['KODE_KAB'])) {
                continue;
            }

            $code = (string) $region['KODE_KAB'];
            $region['name'] = $names[$code]['name'] ?? $code;

            $named[] = $region;
        }

        return $named;
    }

    /**
     * @return array<int, string>
     */
    private function fetchHazardNames(string $baseUrl): array
    {
        $response = $this->http->get($baseUrl.'/api/bencana-irbi');

        if (! $response->successful()) {
            return [];
        }

        $result = [];

        foreach ((array) $response->json() as $hazard) {
            if (isset($hazard['id'], $hazard['nama'])) {
                $result[(int) $hazard['id']] = (string) $hazard['nama'];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $region
     * @return array<int, int>
     */
    private function yearKeys(array $region): array
    {
        $years = [];

        foreach ($region as $key => $value) {
            if (preg_match('/^(20\d{2})$/', (string) $key, $match) && is_array($value)) {
                $years[] = (int) $match[1];
            }
        }

        return $years;
    }
}
