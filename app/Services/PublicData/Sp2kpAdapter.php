<?php

namespace App\Services\PublicData;

use App\Models\CommodityPrice;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;

class Sp2kpAdapter implements PublicDataSourceAdapter
{
    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $apiBase = config('public_data.sp2kp.api_base', 'https://api-sp2kp.kemendag.go.id');
        $provinceCode = config('public_data.sp2kp.province_code', '72');
        $regionCode = config('public_data.sp2kp.region_code', '7206');

        try {
            $markets = $this->http->get($apiBase.'/master/api/pasar', [
                'take' => 9999,
                'kode_provinsi' => $provinceCode,
                'kode_kab_kota' => $regionCode,
                'is_nasional' => 'true',
            ])->json()['data'] ?? [];

            $market = $markets[0] ?? null;
            $marketId = (int) ($market['id'] ?? config('public_data.sp2kp.market_id', 606));
            $marketName = (string) ($market['nama'] ?? config('public_data.sp2kp.market_name', 'Pasar Rakyat Bungku Tengah'));

            $variants = $this->http->get($apiBase.'/master/api/variant', [
                'take' => 9999,
                'is_public' => 'true',
            ])->json()['data'] ?? [];

            if ($variants === []) {
                return ['data' => null, 'metadata' => ['error' => 'No variants available'], 'available' => false];
            }

            $startDate = date('Y-m-01', strtotime('-'.((int) config('public_data.sp2kp.months_back', 12) - 1).' months'));
            $endDate = date('Y-m-d');

            $prices = $this->http->postForm($apiBase.'/report/api/average-price/export-area-monthly-json', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'level' => '2',
                'variant_ids' => implode(',', array_column($variants, 'id')),
                'kode_provinsi' => $provinceCode,
                'kode_kab_kota' => $regionCode,
                'pasar_id' => (string) $marketId,
                'skip_sat_sun' => 'true',
                'tipe_komoditas' => '',
            ])->json()['data'] ?? [];

            return [
                'data' => [
                    'market' => ['id' => $marketId, 'name' => $marketName],
                    'variants' => $variants,
                    'prices' => $prices,
                ],
                'metadata' => [
                    'source_url' => $apiBase,
                    'region_code' => $regionCode,
                    'market_id' => $marketId,
                ],
                'available' => count($prices) > 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('SP2KP fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false) || empty($raw['data']['prices'])) {
            return [];
        }

        $records = [];
        $market = $raw['data']['market']['name'] ?? 'Pasar Utama';

        foreach ($raw['data']['prices'] as $serie) {
            $commodity = (string) ($serie['variant'] ?? '');
            $unit = (string) ($serie['satuan'] ?? 'kg');

            if ($commodity === '') {
                continue;
            }

            $prices = $serie['daftarHarga'] ?? [];
            usort($prices, fn (array $a, array $b) => strcmp((string) $a['date'], (string) $b['date']));

            $previousPrice = null;

            foreach ($prices as $price) {
                $currentPrice = (float) ($price['harga'] ?? 0);
                $period = (string) ($price['date'] ?? '');
                $recordDate = $period !== '' ? $period.'-01' : date('Y-m-d');

                if ($currentPrice <= 0) {
                    continue;
                }

                $priceChange = $previousPrice !== null ? $currentPrice - $previousPrice : null;
                $pctChange = ($previousPrice !== null && $previousPrice > 0)
                    ? round(($priceChange / $previousPrice) * 100, 2)
                    : null;

                $records[] = [
                    'commodity' => $commodity,
                    'category' => null,
                    'current_price' => $currentPrice,
                    'previous_price' => $previousPrice,
                    'price_change' => $priceChange != 0 ? $priceChange : null,
                    'percentage_change' => $pctChange != 0 ? $pctChange : null,
                    'market' => $market,
                    'region' => 'Kabupaten Morowali',
                    'record_date' => $recordDate,
                    'availability' => 'tersedia',
                    'unit' => 'Rp/'.$unit,
                ];

                $previousPrice = $currentPrice;
            }
        }

        return $records;
    }

    public function store(array $records, PublicDataSource $source): array
    {
        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $dedupeKey = CommodityPrice::dedupeKey(
                $record['commodity'],
                $record['market'],
                $record['record_date']
            );

            $existing = CommodityPrice::where('dedupe_key', $dedupeKey)->first();

            if ($existing) {
                $existing->update($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $updated++;
            } else {
                CommodityPrice::create($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        $total = CommodityPrice::count();
        $latest = CommodityPrice::latest('record_date')->first();
        $commodities = CommodityPrice::distinct()->count('commodity');
        $avgChange = CommodityPrice::whereNotNull('percentage_change')->avg('percentage_change');

        return [
            'total_records' => $total,
            'total_commodities' => $commodities,
            'latest_date' => $latest?->record_date?->format('d M Y'),
            'average_change' => $avgChange ? round($avgChange, 1) : null,
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Pasar & Kebutuhan Pokok';
    }
}
