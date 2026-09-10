<?php

namespace App\Services\PublicData;

use App\Models\DisasterEvent;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SitabaAdapter implements PublicDataSourceAdapter
{
    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $baseUrl = config('public_data.sitaba.api_base', 'https://sitaba.pu.go.id');
        $province = strtoupper((string) config('public_data.sitaba.province', 'Sulawesi Tengah'));
        $search = config('public_data.sitaba.search', 'MOROWALI');

        try {
            $response = $this->http->get($baseUrl.'/api/public/list-new-disaster/', [
                'page' => 1,
                'size' => 200,
                'sort' => 'DESC',
                'province' => $province,
                'search' => $search,
            ]);

            if (! $response->successful()) {
                return ['data' => null, 'metadata' => ['error' => 'HTTP '.$response->status()], 'available' => false];
            }

            $items = (array) ($response->json()['data'] ?? []);

            $items = array_values(array_filter($items, function (array $item) {
                $city = strtoupper((string) ($item['city'] ?? ''));

                return str_contains($city, 'MOROWALI') && ! str_contains($city, 'UTARA');
            }));

            return [
                'data' => $items,
                'metadata' => ['source_url' => $baseUrl, 'province' => $province],
                'available' => count($items) > 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('Sitaba fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false) || empty($raw['data'])) {
            return [];
        }

        $records = [];

        foreach ($raw['data'] as $item) {
            $category = $item['category'] ?? [];
            $point = $item['point_of_occurrence'] ?? [];
            $damage = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($item['damage'] ?? ''))));
            $cause = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($item['cause'] ?? ''))));
            $status = $item['is_rehab_rekon'] ? 'rehab-rekon' : ($item['is_pin'] ? 'penanganan' : 'terkini');

            $records[] = [
                'event_id' => $item['uid'] ?? null,
                'disaster_type' => $category['child_disaster_category'] ?? $category['disaster_category'] ?? 'Bencana',
                'disaster_name' => $item['name'] ?? null,
                'event_date' => Str::substr((string) ($item['date_time_of_occurrence'] ?? ''), 0, 10) ?: null,
                'province' => $item['province'] ?? 'SULAWESI TENGAH',
                'district' => $item['city'] ?? 'KABUPATEN MOROWALI',
                'sub_district' => null,
                'village' => null,
                'affected_area' => $item['place_of_occurrence'] ?? null,
                'impact' => $damage !== '' ? $damage : null,
                'affected_population' => (int) ($item['affected_population'] ?? 0),
                'infrastructure_impact' => $cause !== '' ? $cause : null,
                'status' => $status,
                'latitude' => $point[0] ?? null,
                'longitude' => $point[1] ?? null,
                'source_name' => 'Sitaba PUPR',
                'source_url' => ($item['uid'] ?? null)
                    ? ($raw['metadata']['source_url'] ?? '').'/api/public/detail-new-disaster/?uid='.$item['uid']
                    : ($raw['metadata']['source_url'] ?? ''),
            ];
        }

        return $records;
    }

    public function store(array $records, PublicDataSource $source): array
    {
        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $dedupeKey = hash('sha256', implode("\n", [
                $record['event_id'] ?? '',
                $record['disaster_type'],
                $record['event_date'] ?? '',
                $record['district'] ?? '',
            ]));

            $existing = DisasterEvent::where('dedupe_key', $dedupeKey)->first();

            if ($existing) {
                $existing->update($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $updated++;
            } else {
                DisasterEvent::create($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        $total = DisasterEvent::count();
        $recent = DisasterEvent::where('event_date', '>=', now()->subDays(30))->count();

        return [
            'total_records' => $total,
            'recent_events' => $recent,
            'types' => DisasterEvent::distinct()->count('disaster_type'),
            'districts_affected' => DisasterEvent::whereNotNull('district')->distinct()->count('district'),
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Bencana Terkini';
    }
}
