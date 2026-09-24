<?php

namespace App\Services;

use App\Models\ApbdRecord;
use App\Models\BpsObservation;
use App\Models\CommodityPrice;
use App\Models\DisasterEvent;
use App\Models\DisasterRiskIndex;
use App\Models\ExternalData;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use App\Services\PublicData\LocationResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Provides data for the combined map ("Peta Gabungan") so all sector records
 * can be rendered together and filtered by sector and kecamatan.
 */
class MapService
{
    /**
     * External (Satu Data) sectors mapped onto the map's sector labels.
     */
    private const EXTERNAL_SECTOR_MAP = [
        'pendidikan' => 'pendidikan',
        'kesehatan' => 'kesehatan',
        'keamanan' => 'ketertiban',
    ];

    private const EXTERNAL_CATEGORY_MAP = [
        'pendidikan' => 'data-publik-pendidikan',
        'kesehatan' => 'data-publik-kesehatan',
        'keamanan' => 'data-publik-keamanan',
    ];

    /**
     * All map-enabled records across all sectors, optionally filtered by
     * kecamatan. Results are cached briefly; pass `$force = true` (or the
     * `refresh=1` query parameter on the data endpoint) to bypass.
     *
     * @return array{markers: Collection<int, array<string, mixed>>, kecamatans: array<int, array<string, mixed>>, riskMap: array<string, array<string, mixed>>}
     */
    public function combined(?int $kecamatanId = null, bool $force = false): array
    {
        $cacheKey = 'command-center.maps.'.($kecamatanId ?: 'all');

        if ($force) {
            Cache::forget($cacheKey);
        }

        return Cache::remember(
            $cacheKey,
            (int) config('command-center.cache.map_ttl', 60),
            fn () => $this->buildPayload($kecamatanId),
        );
    }

    /**
     * Forget every cached combined-map payload (all kecamatan + global map).
     * Called by `DataCacheFlusher` whenever any master or public dataset row
     * changes, so the map reflects new data without waiting for the TTL.
     */
    public function flushCache(): void
    {
        Cache::forget('command-center.maps.all');

        foreach (Kecamatan::query()->pluck('id') as $id) {
            Cache::forget('command-center.maps.'.$id);
        }
    }

    /**
     * @return array{markers: Collection<int, array<string, mixed>>, kecamatans: array<int, array<string, mixed>>, riskMap: array<string, array<string, mixed>>}
     */
    private function buildPayload(?int $kecamatanId = null): array
    {
        $resolver = app(LocationResolver::class);
        $commoditySummaries = $this->commoditySummariesByMarket();

        $markers = collect();

        // Pendidikan
        School::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (School $s) use (&$markers) {
                $markers->push([
                    'name' => $s->name,
                    'id' => $s->id,
                    'slug' => 'school',
                    'sector' => 'pendidikan',
                    'category' => $s->school_type,
                    'latitude' => (float) $s->latitude,
                    'longitude' => (float) $s->longitude,
                    'kecamatan' => $s->kecamatan?->name,
                    'details' => [
                        'Jenis' => $s->school_type,
                        'Siswa' => (int) $s->students_male + (int) $s->students_female,
                        'Guru' => (int) $s->teachers,
                        'Kondisi' => $s->condition,
                    ],
                ]);
            });

        // Ketertiban
        Polsek::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Polsek $p) use (&$markers) {
                $markers->push([
                    'name' => $p->name,
                    'id' => $p->id,
                    'slug' => 'polsek',
                    'sector' => 'ketertiban',
                    'category' => 'polsek',
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'kecamatan' => $p->kecamatan?->name,
                    'details' => [
                        'Personel' => (int) $p->personnel_count,
                        'Poskamling' => (int) $p->poskamling_count,
                    ],
                ]);
            });

        Market::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Market $m) use (&$markers, $commoditySummaries) {
                $summary = $commoditySummaries->get($m->name);
                $details = [];

                if ($summary !== null) {
                    $details['Komoditas SP2KP'] = $summary['count'];
                    $details['Harga Update'] = $summary['date'];
                    $details['Naik Tertinggi'] = $summary['riser'];
                    $details['Turun Terendah'] = $summary['faller'];
                }

                $markers->push([
                    'name' => $m->name,
                    'id' => $m->id,
                    'slug' => 'market',
                    'sector' => 'ketertiban',
                    'category' => 'pasar',
                    'latitude' => (float) $m->latitude,
                    'longitude' => (float) $m->longitude,
                    'kecamatan' => $m->kecamatan?->name,
                    'details' => array_filter($details, fn ($v) => $v !== null),
                    'detailUrl' => $summary !== null ? route('public-data.show', 'sp2kp') : null,
                    'lastSeen' => $summary['date'] ?? null,
                ]);
            });

        Poskamling::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Poskamling $p) use (&$markers) {
                $markers->push([
                    'name' => $p->name,
                    'id' => $p->id,
                    'slug' => 'poskamling',
                    'sector' => 'ketertiban',
                    'category' => 'poskamling',
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'kecamatan' => $p->kecamatan?->name,
                    'details' => ['Status' => $p->is_active ? 'Aktif' : 'Tidak Aktif'],
                ]);
            });

        Tipkamtikmas::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Tipkamtikmas $t) use (&$markers) {
                $markers->push([
                    'name' => $t->title,
                    'id' => $t->id,
                    'slug' => 'tipkamtikmas',
                    'sector' => 'ketertiban',
                    'category' => 'tipkamtikmas',
                    'latitude' => (float) $t->latitude,
                    'longitude' => (float) $t->longitude,
                    'kecamatan' => $t->kecamatan?->name,
                    'details' => ['Status' => ucfirst($t->status)],
                ]);
            });

        Kelurahan::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Kelurahan $k) use (&$markers) {
                $markers->push([
                    'name' => $k->name,
                    'id' => $k->id,
                    'slug' => 'kelurahan',
                    'sector' => 'ketertiban',
                    'category' => 'kelurahan',
                    'latitude' => (float) $k->latitude,
                    'longitude' => (float) $k->longitude,
                    'kecamatan' => $k->kecamatan?->name,
                    'details' => ['Populasi' => (int) $k->population],
                ]);
            });

        // Kesehatan
        HealthFacility::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (HealthFacility $f) use (&$markers) {
                $markers->push([
                    'name' => $f->name,
                    'id' => $f->id,
                    'slug' => 'health_facility',
                    'sector' => 'kesehatan',
                    'category' => $f->facility_type,
                    'latitude' => (float) $f->latitude,
                    'longitude' => (float) $f->longitude,
                    'kecamatan' => $f->kecamatan?->name,
                    'details' => [
                        'Dokter' => (int) $f->doctors,
                        'Perawat' => (int) $f->nurses,
                        'Bidan' => (int) $f->midwives,
                        'Bed' => (int) $f->beds,
                    ],
                ]);
            });

        // Data Publik (bencana terkini SITABA + indeks risiko IRBI).
        $this->pushDisasterEvents($markers, $kecamatanId, $resolver);

        // Data Publik region-level (BPS & APBD) di centroid kabupaten.
        $this->pushRegionPublicData($markers, $kecamatanId, $resolver);

        // Data Publik (Satu Data Morowali): locations aggregated per sector.
        $externalKecamatans = $this->pushExternalData($markers, $kecamatanId, $resolver);

        $riskMap = $this->riskMap();

        $kecamatanNames = collect();

        foreach (Kecamatan::orderBy('name')->get(['id', 'name']) as $kecamatan) {
            $kecamatanNames->push(['id' => $kecamatan->id, 'name' => $kecamatan->name]);
        }

        foreach ($externalKecamatans as $external) {
            $kecamatanNames->push($external);
        }

        $kecamatanNames = $kecamatanNames->unique('name')->values()->all();

        return [
            'markers' => $markers,
            'kecamatans' => $kecamatanNames,
            'riskMap' => $riskMap,
        ];
    }

    /**
     * Latest SP2KP commodity snapshot per market, summarised for the market
     * (pasar) marker popup.
     *
     * @return Collection<string, array{count: int, date: string|null, riser: string|null, faller: string|null}>
     */
    private function commoditySummariesByMarket(): Collection
    {
        return CommodityPrice::query()
            ->orderByDesc('record_date')
            ->get(['market', 'commodity', 'percentage_change', 'record_date'])
            ->filter(fn (CommodityPrice $price) => filled($price->market))
            ->groupBy('market')
            ->map(function (Collection $rows): array {
                $rows = $rows->sortByDesc('record_date')->values();
                $latestDate = $rows->first()->record_date;
                $latest = $rows->filter(fn (CommodityPrice $price) => $price->record_date?->equalTo($latestDate));

                $riser = $latest->filter(fn (CommodityPrice $price) => $price->percentage_change > 0)
                    ->sortByDesc('percentage_change')->first();
                $faller = $latest->filter(fn (CommodityPrice $price) => $price->percentage_change < 0)
                    ->sortBy('percentage_change')->first();

                return [
                    'count' => $latest->count(),
                    'date' => $latestDate?->format('Y-m-d'),
                    'riser' => $riser ? $riser->commodity.' ('.$riser->formattedPercentageChange().')' : null,
                    'faller' => $faller ? $faller->commodity.' ('.$faller->formattedPercentageChange().')' : null,
                ];
            });
    }

    /**
     * SITABA disaster events as point markers under the "kebencanaan" sector.
     *
     * @param  Collection<int, array<string, mixed>>  $markers
     */
    private function pushDisasterEvents(Collection $markers, ?int $kecamatanId, LocationResolver $resolver): void
    {
        $target = $kecamatanId !== null ? Kecamatan::whereKey($kecamatanId)->value('name') : null;

        DisasterEvent::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('event_date')
            ->get()
            ->each(function (DisasterEvent $event) use ($markers, $target, $resolver): void {
                $kecamatan = $resolver->canonicalName($event->village ?? $event->sub_district ?? $event->district);

                if ($target !== null && $kecamatan !== $target) {
                    return;
                }

                $markers->push([
                    'name' => $event->disaster_name ?: $event->disaster_type,
                    'id' => $event->id,
                    'slug' => 'disaster_event',
                    'sector' => 'kebencanaan',
                    'category' => 'bencana',
                    'latitude' => (float) $event->latitude,
                    'longitude' => (float) $event->longitude,
                    'kecamatan' => $kecamatan,
                    'details' => array_filter([
                        'Jenis' => $event->disaster_type,
                        'Tanggal' => $event->event_date?->format('d M Y'),
                        'Wilayah' => $event->district,
                        'Terdampak' => $event->affected_population,
                        'Status' => $event->status ? ucfirst($event->status) : null,
                    ], fn ($v) => $v !== null),
                    'sourceUrl' => $event->source_url,
                    'detailUrl' => route('public-data.show', 'sitaba'),
                    'lastSeen' => $event->scraped_at?->format('Y-m-d H:i'),
                ]);
            });
    }

    /**
     * Region-level public data (BPS statistics & APBD) anchored to the
     * kabupaten centroid, since these datasets carry no point geometry.
     *
     * @param  Collection<int, array<string, mixed>>  $markers
     */
    private function pushRegionPublicData(Collection $markers, ?int $kecamatanId, LocationResolver $resolver): void
    {
        if ($kecamatanId !== null) {
            return;
        }

        $centroid = $resolver->resolve('Kabupaten Morowali');

        if ($centroid === null) {
            return;
        }

        $observations = BpsObservation::query()->get(['bps_dataset_id', 'indicator', 'year', 'fetched_at']);

        if ($observations->isNotEmpty()) {
            $markers->push([
                'name' => 'Statistik BPS Morowali',
                'id' => null,
                'slug' => 'bps_observation',
                'sector' => 'statistik',
                'category' => 'data-publik-bps',
                'latitude' => $centroid['latitude'],
                'longitude' => $centroid['longitude'],
                'kecamatan' => 'Kabupaten Morowali',
                'details' => [
                    'Indikator' => $observations->pluck('indicator')->unique()->count(),
                    'Dataset' => $observations->pluck('bps_dataset_id')->unique()->count(),
                    'Tahun Terbaru' => $observations->max('year'),
                ],
                'detailUrl' => route('public-data.show', 'bps'),
                'lastSeen' => $observations->max('fetched_at')?->format('Y-m-d H:i'),
            ]);
        }

        $apbd = ApbdRecord::query()->get(['indicator', 'year', 'percentage', 'scraped_at']);

        if ($apbd->isNotEmpty()) {
            $markers->push([
                'name' => 'Anggaran APBD Morowali',
                'id' => null,
                'slug' => 'apbd_record',
                'sector' => 'statistik',
                'category' => 'data-publik-apbd',
                'latitude' => $centroid['latitude'],
                'longitude' => $centroid['longitude'],
                'kecamatan' => 'Kabupaten Morowali',
                'details' => [
                    'Indikator' => $apbd->pluck('indicator')->unique()->count(),
                    'Tahun Terbaru' => $apbd->max('year'),
                    'Rata Realisasi' => number_format((float) $apbd->avg('percentage'), 1, ',', '.').'%',
                ],
                'detailUrl' => route('public-data.show', 'apbd'),
                'lastSeen' => $apbd->max('scraped_at')?->format('Y-m-d H:i'),
            ]);
        }
    }

    /**
     * IRBI risk index per kabupaten for the latest year, picking the hazard
     * with the highest index; feeds the choropleth overlay on the map.
     *
     * @return array<string, array{name: string, index: float, level: string|null, hazard: string|null}>
     */
    private function riskMap(): array
    {
        $rows = DisasterRiskIndex::query()->get(['region_code', 'region_name', 'hazard_type', 'risk_index', 'risk_level', 'year']);

        if ($rows->isEmpty()) {
            return [];
        }

        $latestYear = $rows->max('year');

        return $rows
            ->where('year', $latestYear)
            ->filter(fn (DisasterRiskIndex $row) => filled($row->region_code))
            ->groupBy('region_code')
            ->map(function (Collection $regionRows): array {
                $top = $regionRows->sortByDesc('risk_index')->first();

                return [
                    'name' => $top->region_name,
                    'index' => (float) $top->risk_index,
                    'level' => $top->risk_level,
                    'hazard' => $top->hazard_type,
                ];
            })
            ->all();
    }

    /**
     * Aggregates scraped external datapoints into one marker per kecamatan per
     * sector. Rejects unresolved locations (aggregate rows like "Jumlah").
     *
     * @param  Collection<int, array<string, mixed>>  $markers
     * @return array<int, array{id: null, name: string}>
     */
    private function pushExternalData(Collection $markers, ?int $kecamatanId, LocationResolver $resolver): array
    {
        $kecamatanName = $kecamatanId !== null ? Kecamatan::whereKey($kecamatanId)->value('name') : null;

        $rows = ExternalData::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['sector', 'source_url', 'dataset', 'location', 'latitude', 'longitude', 'scraped_at']);

        $grouped = collect();

        foreach ($rows as $row) {
            $resolved = $resolver->resolve($row->location);

            if ($resolved === null) {
                continue;
            }

            if ($kecamatanName !== null && $resolved['name'] !== $kecamatanName) {
                continue;
            }

            $grouped->push([
                'sector' => $row->sector,
                'name' => $resolved['name'],
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'source_url' => $row->source_url,
                'dataset' => $row->dataset,
                'scraped_at' => $row->scraped_at,
            ]);
        }

        $grouped
            ->groupBy(fn ($row) => $row['sector'].'|'.$row['name'])
            ->each(function (Collection $rows) use ($markers) {
                $first = $rows->first();

                $sector = (string) $first['sector'];

                $markers->push([
                    'name' => $first['name'],
                    'id' => null,
                    'slug' => 'external_data',
                    'sector' => self::EXTERNAL_SECTOR_MAP[$sector] ?? $sector,
                    'category' => self::EXTERNAL_CATEGORY_MAP[$sector] ?? 'data-publik-pendidikan',
                    'latitude' => $first['latitude'],
                    'longitude' => $first['longitude'],
                    'kecamatan' => $first['name'],
                    'details' => [
                        'Dataset' => $rows->pluck('dataset')->unique()->count(),
                        'Rekor' => $rows->count(),
                    ],
                    'sourceUrl' => $first['source_url'],
                    'detailUrl' => route('public-data.show', $sector),
                    'lastSeen' => $rows->pluck('scraped_at')->max()?->format('Y-m-d H:i'),
                ]);
            });

        return $grouped
            ->pluck('name')
            ->filter(fn ($name) => $name !== 'Kabupaten Morowali')
            ->unique()
            ->map(fn ($name) => ['id' => null, 'name' => $name])
            ->values()
            ->all();
    }
}
