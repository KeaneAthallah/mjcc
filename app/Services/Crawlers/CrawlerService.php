<?php

namespace App\Services\Crawlers;

use App\Models\CrawlError;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Support\TargetRegionService;
use Illuminate\Support\Collection;

/**
 * Read-side service used by the crawler admin UI. These methods never call
 * external sources; they only summarize already-persisted crawl data.
 */
class CrawlerService
{
    public function __construct(private readonly TargetRegionService $targetRegions) {}

    public function sources(): Collection
    {
        $counts = CrawlRecord::query()
            ->selectRaw('crawl_source_id, count(*) as total')
            ->whereNotNull('kabupaten_code')
            ->groupBy('crawl_source_id')
            ->pluck('total', 'crawl_source_id');

        return CrawlSource::query()
            ->orderBy('slug')
            ->get()
            ->map(function (CrawlSource $source) use ($counts) {
                $lastRun = $source->runs()->latest('started_at')->first();

                return [
                    'model' => $source,
                    'records' => $counts->get($source->id, 0),
                    'last_run' => $lastRun,
                    'is_active' => $source->is_active,
                    'enabled_config' => (bool) data_get(config("crawler.sources.{$source->slug}"), 'enabled', true),
                ];
            });
    }

    public function summary(): array
    {
        $totalRecords = CrawlRecord::query()->count();
        $withCoordinates = CrawlRecord::query()->hasCoordinates()->count();

        $latestRun = CrawlRun::query()->latest('started_at')->first();

        return [
            'sources' => CrawlSource::count(),
            'records' => $totalRecords,
            'with_coordinates' => $withCoordinates,
            'runs' => CrawlRun::count(),
            'last_run_at' => $latestRun?->started_at,
            'errors' => CrawlError::count(),
            'last_run_status' => $latestRun?->status,
        ];
    }

    /**
     * @param  string[]  $statuses
     */
    public function runsForSource(CrawlSource $source, int $limit = 20, array $statuses = []): Collection
    {
        return $source->runs()
            ->when($statuses !== [], fn ($q) => $q->whereIn('status', $statuses))
            ->latest('started_at')
            ->limit($limit)
            ->get();
    }

    public function recentErrors(int $limit = 10): Collection
    {
        return CrawlError::query()
            ->with('source')
            ->latest('occurred_at')
            ->limit($limit)
            ->get();
    }

    public function sourceMeta(string $slug): array
    {
        $meta = (array) config("crawler.sources.{$slug}", []);

        return [
            'name' => $meta['name'] ?? ucfirst($slug),
            'source_label' => $meta['source_label'] ?? strtoupper($slug),
            'description' => $meta['description'] ?? '',
            'icon' => $meta['icon'] ?? '📡',
            'base_url' => $meta['base_url'] ?? '',
            'enabled' => (bool) ($meta['enabled'] ?? true),
        ];
    }

    /**
     * Query records for one source, filtered to the target regions.
     */
    public function recordsFor(string $slug, array $filters = [], int $perPage = 15)
    {
        $query = CrawlRecord::query()
            ->with('source')
            ->whereHas('source', fn ($q) => $q->where('slug', $slug));

        // The price source stores province-scope records (no kabupaten), so the
        // target-region constraint is intentionally not applied to it.
        if ($slug !== 'sp2kp') {
            $query->targetRegion();
        }

        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            match ($key) {
                'kabupaten' => $query->where('kabupaten_code', (string) $value),
                'kecamatan' => $query->where('kecamatan_code', (string) $value),
                'search' => $query->where(function ($q) use ($value) {
                    $q->where('name', 'like', '%'.$value.'%')
                        ->orWhere('external_id', 'like', '%'.$value.'%');
                }),
                'jenjang' => $query->where('data->jenjang', (string) $value),
                'category' => $query->where('data->category', (string) $value),
                default => null,
            };
        }

        return $query->latest('last_seen_at')->paginate($perPage)->withQueryString();
    }

    /**
     * Distinct kecamatan values for one source.
     *
     * @return array<string, string>
     */
    public function kecamatanOptions(string $slug): array
    {
        return CrawlRecord::query()
            ->whereHas('source', fn ($q) => $q->where('slug', $slug))
            ->whereNotNull('kecamatan_code')
            ->distinct()
            ->orderBy('kecamatan_name')
            ->pluck('kecamatan_name', 'kecamatan_code')
            ->filter()
            ->all();
    }

    /**
     * Distribution of one source's records by kecamatan (for charts).
     *
     * @return array{labels: string[], values: int[]}
     */
    public function distributionByKecamatan(string $slug): array
    {
        $rows = CrawlRecord::query()
            ->whereHas('source', fn ($q) => $q->where('slug', $slug))
            ->whereNotNull('kabupaten_code')
            ->get()
            ->groupBy(fn ($r) => $r->kecamatan_name ?: ($r->kabupaten_name ?: 'Lainnya'));

        return [
            'labels' => $rows->keys()->map(fn ($k) => (string) $k)->values()->all(),
            'values' => $rows->map->count()->values()->all(),
        ];
    }

    /**
     * BPS indicator records grouped by category derived from the label.
     * Categories are only ever produced from real stored data — nothing is
     * fabricated. Records without a recognized keyword fall under 'Lainnya'.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bpsIndicators(): array
    {
        $records = CrawlRecord::query()
            ->with('source')
            ->targetRegion()
            ->whereHas('source', fn ($q) => $q->where('slug', 'bps'))
            ->get()
            ->groupBy('external_id');

        $categories = [
            'Kependudukan' => ['penduduk', 'keluarga', 'kelahiran', 'kematian', 'seks', 'gender', 'rasio jenis'],
            'Pendidikan' => ['pendidikan', 'sekolah', 'melek', 'partisipasi sekolah'],
            'Kesehatan' => ['kesehatan', 'balita', 'gizi', 'imunisasi', 'puskesmas', 'stunting', 'rumah sakit'],
            'Ekonomi' => ['ekonomi', 'pdb', 'ekonomi', 'inflasi', 'ekspor', 'impor', 'kemiskinan', 'pdrb', 'investasi'],
            'Ketenagakerjaan' => ['kerja', 'tenaga kerja', 'tpak', 'tpt', 'penganggur', 'upah', 'angkatan kerja'],
            'Pertanian' => ['pertanian', 'tanaman', 'pangan', 'perkebunan', 'ternak', 'sawah', 'produksi'],
            'Infrastruktur' => ['infrastruktur', 'jalan', 'listrik', 'air', 'sanitasi', 'perumahan', 'sumber air'],
        ];

        $groups = [];

        foreach ($records as $externalId => $group) {
            $latest = $group->firstWhere('kabupaten_code', '7206')
                ?? $group->firstWhere('kabupaten_code', '7212')
                ?? $group->first();

            $label = (string) ($latest['data']['label'] ?? $latest->name ?? $externalId);
            $category = 'Lainnya';

            foreach ($categories as $cat => $keywords) {
                foreach ($keywords as $kw) {
                    if (str_contains(strtolower($label), $kw)) {
                        $category = $cat;
                        break 2;
                    }
                }
            }

            $morowali = $group->firstWhere('kabupaten_code', '7206');
            $morowaliUtara = $group->firstWhere('kabupaten_code', '7212');

            $groups[] = [
                'id' => $latest->id,
                'external_id' => (string) $externalId,
                'label' => $label,
                'unit' => $latest['data']['unit'] ?? null,
                'period' => $latest['data']['period'] ?? null,
                'category' => $category,
                'morowali_value' => $this->firstValue($morowali),
                'morowali_utara_value' => $this->firstValue($morowaliUtara),
                'records' => $group->map(fn ($r) => $r->id)->all(),
            ];
        }

        usort($groups, fn ($a, $b) => $a['label'] <=> $b['label']);

        return $groups;
    }

    private function firstValue($record): mixed
    {
        if (! $record) {
            return null;
        }

        $values = $record['data']['values'] ?? [];

        if (! is_array($values) || $values === []) {
            return null;
        }

        return reset($values);
    }

    /**
     * Crawl run history with a searchable source filter.
     */
    public function runs(?string $sourceSlug = null, int $perPage = 20)
    {
        return CrawlRun::query()
            ->with('source')
            ->when($sourceSlug, fn ($q) => $q->whereHas('source', fn ($q) => $q->where('slug', $sourceSlug)))
            ->latest('started_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
