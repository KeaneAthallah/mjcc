<?php

namespace App\Services\PublicData;

use App\Models\ExternalData;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;

class DapodikAdapter implements PublicDataSourceAdapter
{
    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $baseUrl = config('public_data.dapodik.url', 'https://dapo.kemendikdasmen.go.id');
        $regionCode = config('public_data.dapodik.region_code', '180700');
        $headers = $this->browserHeaders($baseUrl);

        try {
            $token = $this->fetchToken($baseUrl, $headers);

            if ($token === '') {
                return ['data' => null, 'metadata' => ['error' => 'VITE_API_TOKEN tidak ditemukan di env.js'], 'available' => false];
            }

            $endpoint = '/api/progress-pengiriman/kecamatan';
            $response = $this->http->get($baseUrl.$endpoint, ['kode_kabupaten' => $regionCode], $headers + ['Authorization' => 'Bearer '.$token], false);

            if (! $response->successful()) {
                return ['data' => null, 'metadata' => ['error' => 'HTTP '.$response->status()], 'available' => false];
            }

            $data = (array) ($response->json()['data'] ?? []);

            return [
                'data' => $data,
                'metadata' => ['source_url' => $baseUrl, 'region_code' => $regionCode, 'endpoint' => $endpoint],
                'available' => count($data) > 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('Dapodik fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false) || empty($raw['data'])) {
            return [];
        }

        $records = [];
        $year = (int) date('Y');
        $sector = 'pendidikan';
        $sourceKey = 'dapodik';
        $dataset = 'Dapodik Rekapitulasi';
        $sourceUrl = $raw['metadata']['source_url'] ?? '';
        $regionName = config('public_data.dapodik.region_name', 'Kabupaten Morowali');

        $totals = ['Jumlah Sekolah' => 0.0, 'Jumlah Siswa' => 0.0, 'Jumlah Guru' => 0.0, 'Jumlah Tenaga Kependidikan' => 0.0];

        foreach ($raw['data'] as $kecamatan) {
            $location = trim(preg_replace('/^Kec\.\s*/i', '', (string) ($kecamatan['kecamatan'] ?? 'Unknown')));

            $dataPoints = [
                'Jumlah Sekolah' => (float) ($kecamatan['jml_sekolah'] ?? 0),
                'Jumlah Siswa' => (float) ($kecamatan['jml_siswa'] ?? 0),
                'Jumlah Guru' => (float) ($kecamatan['jml_guru'] ?? 0),
                'Jumlah Tenaga Kependidikan' => (float) ($kecamatan['jml_tendik'] ?? 0),
            ];

            foreach ($dataPoints as $indicator => $value) {
                $totals[$indicator] += $value;

                $records[] = [
                    'sector' => $sector,
                    'source' => $sourceKey,
                    'source_key' => $sourceKey,
                    'source_url' => $sourceUrl,
                    'dataset' => $dataset,
                    'topic' => 'Pendidikan',
                    'year' => $year,
                    'location' => $location,
                    'indicator' => $indicator,
                    'value' => $value,
                    'unit' => $this->unitFor($indicator),
                ];
            }
        }

        foreach ($totals as $indicator => $value) {
            $records[] = [
                'sector' => $sector,
                'source' => $sourceKey,
                'source_key' => $sourceKey,
                'source_url' => $sourceUrl,
                'dataset' => $dataset,
                'topic' => 'Pendidikan',
                'year' => $year,
                'location' => $regionName,
                'indicator' => $indicator,
                'value' => $value,
                'unit' => $this->unitFor($indicator),
            ];
        }

        return $records;
    }

    public function store(array $records, PublicDataSource $source): array
    {
        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $dedupeKey = ExternalData::dedupeKey(
                $record['sector'],
                $record['source_key'],
                $record['dataset'],
                $record['year'] ?? null,
                $record['location'],
                $record['indicator']
            );

            $existing = ExternalData::where('dedupe_key', $dedupeKey)->first();

            if ($existing) {
                $existing->update($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $updated++;
            } else {
                ExternalData::create($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        $total = ExternalData::where('source_key', 'dapodik')->count();

        return [
            'total_records' => $total,
            'districts' => ExternalData::where('source_key', 'dapodik')->whereNotNull('location')->distinct()->count('location'),
            'indicators' => ExternalData::where('source_key', 'dapodik')->distinct()->count('indicator'),
            'latest_year' => ExternalData::where('source_key', 'dapodik')->max('year'),
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Dapodik';
    }

    private function fetchToken(string $baseUrl, array $headers): string
    {
        $response = $this->http->get($baseUrl.'/env.js', [], $headers, false);

        if (! $response->successful()) {
            return '';
        }

        if (preg_match('/API_TOKEN["\']?\s*[:=]\s*["\']([a-f0-9]{60,})/i', $response->body(), $match)) {
            return $match[1];
        }

        return '';
    }

    private function browserHeaders(string $baseUrl): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept' => 'application/json, */*',
            'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
            'Accept-Encoding' => 'gzip, deflate',
            'Referer' => $baseUrl.'/',
            'Origin' => $baseUrl,
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Dest' => 'script',
        ];
    }

    private function unitFor(string $indicator): string
    {
        return match ($indicator) {
            'Jumlah Sekolah' => 'Sekolah',
            'Jumlah Siswa' => 'Siswa',
            'Jumlah Guru' => 'Guru',
            default => 'Orang',
        };
    }
}
