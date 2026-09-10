<?php

namespace App\Services\PublicData;

use App\Models\BpsDataset;
use App\Models\BpsObservation;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;

class BpsApiAdapter implements PublicDataSourceAdapter
{
    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $appId = config('public_data.bps.app_id') ?? config('services.bps.app_id', '');
        $baseUrl = config('public_data.bps.base_url', 'https://webapi.bps.go.id');

        if ($appId === '') {
            return ['data' => null, 'metadata' => ['error' => 'BPS_APP_ID tidak dikonfigurasi'], 'available' => false];
        }

        try {
            $regionCode = $config['region_code'] ?? config('public_data.bps.region_code', '7203');
            $regionName = $config['region_name'] ?? config('public_data.bps.region_name', 'Kabupaten Morowali');
            $maxVars = (int) ($config['max_vars'] ?? config('public_data.bps.max_vars', 30));

            $entries = [];
            foreach (array_slice($this->listVariables($baseUrl, $appId, $regionCode), 0, $maxVars) as $variable) {
                $period = $this->selectYearPeriod($this->listPeriods($baseUrl, $appId, $regionCode, $variable['var_id']));

                if ($period === null) {
                    continue;
                }

                $data = $this->fetchDataset($baseUrl, $appId, $regionCode, $variable['var_id'], $period['id']);

                if ($data === null || empty($data['datacontent'])) {
                    continue;
                }

                $entries[] = $data;
            }

            return [
                'data' => $entries,
                'metadata' => [
                    'source_url' => $baseUrl,
                    'region_code' => $regionCode,
                    'region_name' => $regionName,
                    'var_count' => count($entries),
                ],
                'available' => true,
            ];
        } catch (\Throwable $e) {
            Log::warning('BPS API fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false) || empty($raw['data'])) {
            return [];
        }

        $records = [];
        $metadata = $raw['metadata'];

        foreach ($raw['data'] as $entry) {
            $variable = $entry['var'][0] ?? [];
            $varId = (string) ($variable['val'] ?? 'unknown');
            $datasetId = $this->ensureDataset($varId, (string) ($variable['label'] ?? 'BPS Data'), $entry);

            $verticalVars = $entry['vervar'] ?: [['val' => '', 'label' => '']];
            $turVars = $entry['turvar'] ?: [['val' => '', 'label' => '']];

            foreach ($verticalVars as $vertical) {
                foreach ($turVars as $turVar) {
                    foreach ($entry['tahun'] ?? [] as $year) {
                        foreach ($entry['turtahun'] ?? [] as $turYear) {
                            $key = strval($vertical['val']).$varId.strval($turVar['val']).strval($year['val']).strval($turYear['val']);

                            if (! array_key_exists($key, $entry['datacontent'])) {
                                continue;
                            }

                            $indicator = $turVar['label'] !== '' ? (string) $turVar['label'] : (string) $variable['label'];
                            $period = ($turYear['label'] ?? '') !== 'Tahun' ? (string) ($turYear['label'] ?? '') : null;

                            $records[] = [
                                'bps_dataset_id' => $datasetId,
                                'indicator' => $indicator,
                                'region_name' => $metadata['region_name'],
                                'region_code' => $metadata['region_code'],
                                'year' => (int) $year['label'],
                                'period' => $period,
                                'value' => (float) $entry['datacontent'][$key],
                                'unit' => $variable['unit'] ?? null,
                            ];
                        }
                    }
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
            $dedupeKey = BpsObservation::dedupeKey(
                $record['bps_dataset_id'],
                $record['indicator'],
                $record['region_name'],
                $record['year'],
                $record['period']
            );

            $existing = BpsObservation::where('dedupe_key', $dedupeKey)->first();

            if ($existing) {
                $existing->update($record + ['dedupe_key' => $dedupeKey, 'fetched_at' => now()]);
                $updated++;
            } else {
                BpsObservation::create($record + ['dedupe_key' => $dedupeKey, 'fetched_at' => now()]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        $total = BpsObservation::count();
        $datasets = BpsDataset::count();
        $latest = BpsObservation::latest('year')->first();

        return [
            'total_records' => $total,
            'total_datasets' => $datasets,
            'latest_year' => $latest?->year,
            'indicators' => BpsObservation::distinct()->count('indicator'),
        ];
    }

    public function validateConfig(array $config): bool
    {
        $appId = config('public_data.bps.app_id') ?? config('services.bps.app_id', '');

        return $appId !== '';
    }

    public function name(): string
    {
        return 'BPS';
    }

    private function listVariables(string $baseUrl, string $appId, string $regionCode): array
    {
        $variables = [];
        $page = 1;

        do {
            $json = $this->http->get($baseUrl.'/v1/api/list', [
                'model' => 'var',
                'domain' => $regionCode,
                'area' => '1',
                'page' => (string) $page,
                'key' => $appId,
            ])->json();

            $meta = $json['data'][0] ?? [];
            $variables = array_merge($variables, (array) ($json['data'][1] ?? []));
            $page++;
        } while (($meta['pages'] ?? 1) >= $page);

        return $variables;
    }

    private function listPeriods(string $baseUrl, string $appId, string $regionCode, int $varId): array
    {
        $json = $this->http->get($baseUrl.'/v1/api/list', [
            'model' => 'th',
            'domain' => $regionCode,
            'var' => (string) $varId,
            'key' => $appId,
        ])->json();

        $periods = [];
        foreach ((array) ($json['data'][1] ?? []) as $item) {
            $periods[] = ['id' => (string) $item['th_id'], 'label' => (string) $item['th']];
        }

        return $periods;
    }

    /**
     * Prefer a whole-year period (e.g. "2023") over monthly/quarterly ones.
     *
     * @param  array<int, array{id: string, label: string}>  $periods
     * @return array{id: string, label: string}|null
     */
    private function selectYearPeriod(array $periods): ?array
    {
        foreach ($periods as $period) {
            if (preg_match('/^\d{4}$/', $period['label'])) {
                return $period;
            }
        }

        return $periods[0] ?? null;
    }

    private function fetchDataset(string $baseUrl, string $appId, string $regionCode, int $varId, string $thId): ?array
    {
        $json = $this->http->get($baseUrl.'/v1/api/list', [
            'model' => 'data',
            'domain' => $regionCode,
            'var' => (string) $varId,
            'th' => $thId,
            'key' => $appId,
        ])->json();

        if (($json['status'] ?? '') !== 'OK' || ! isset($json['datacontent'])) {
            return null;
        }

        return $json;
    }

    private function ensureDataset(string $datasetId, string $name, array $raw): int
    {
        $dataset = BpsDataset::where('dataset_id', $datasetId)->first();

        if ($dataset) {
            return $dataset->id;
        }

        $subject = $raw['subject'][0]['label'] ?? $raw['var'][0]['subj'] ?? null;
        $turYears = $raw['turtahun'] ?? [];
        $tahunIndicatingMonth = collect($turYears)->contains(fn (array $y) => ($y['label'] ?? '') !== 'Tahun');
        $periodType = $tahunIndicatingMonth ? 'Monthly' : 'Yearly';

        return BpsDataset::create([
            'dataset_id' => $datasetId,
            'name' => $name,
            'description' => $raw['var'][0]['def'] ?? null,
            'subject' => $subject,
            'period_type' => $periodType,
            'metadata' => $raw,
        ])->id;
    }
}
