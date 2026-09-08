<?php

namespace App\Services;

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
     * @return array{markers: Collection<int, array<string, mixed>>, kecamatans: array<int, array<string, mixed>>}
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
     * @return array{markers: Collection<int, array<string, mixed>>, kecamatans: array<int, array<string, mixed>>}
     */
    private function buildPayload(?int $kecamatanId = null): array
    {
        $filter = fn ($q) => $kecamatanId ? $q->where('kecamatan_id', $kecamatanId) : $q;

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
            ->each(function (Market $m) use (&$markers) {
                $markers->push([
                    'name' => $m->name,
                    'id' => $m->id,
                    'slug' => 'market',
                    'sector' => 'ketertiban',
                    'category' => 'pasar',
                    'latitude' => (float) $m->latitude,
                    'longitude' => (float) $m->longitude,
                    'kecamatan' => $m->kecamatan?->name,
                    'details' => [],
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

        // Data Publik (Satu Data Morowali): locations aggregated per sector.
        $externalKecamatans = $this->pushExternalData($markers, $kecamatanId, app(LocationResolver::class));

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
        ];
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
