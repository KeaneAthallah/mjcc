<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Models\HealthFacility;
use App\Services\Crawlers\CrawlerManager;
use App\Services\Crawlers\CrawlerService;
use App\Services\Crawlers\HealthFacilityImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CrawlerController extends Controller
{
    public function __construct(
        private readonly CrawlerService $service,
        private readonly CrawlerManager $manager,
        private readonly HealthFacilityImportService $healthImport,
    ) {}

    public function index(): View
    {
        $sources = $this->service->sources();
        $summary = $this->service->summary();
        $recentErrors = $this->service->recentErrors(10);

        return view('crawler.index', compact('sources', 'summary', 'recentErrors'));
    }

    public function source(CrawlSource $source): View
    {
        $this->authorize('view', $source);

        return view('crawler.source', [
            'source' => $source,
            'records' => $source->records()
                ->latest('last_seen_at')
                ->limit(15)
                ->get(),
            'runs' => $this->service->runsForSource($source),
            'errors' => $source->errors()
                ->latest('occurred_at')
                ->limit(10)
                ->get(),
        ]);
    }

    public function records(Request $request): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $records = CrawlRecord::query()
            ->with('source')
            ->targetRegion()
            ->when($request->filled('source'), fn ($q) => $q->where('crawl_source_id', $request->integer('source')))
            ->when($request->filled('regional'), fn ($q) => $q->where('kabupaten_code', (string) $request->string('regional')))
            ->when($request->filled('type'), fn ($q) => $q->where('record_type', $request->string('type')))
            ->when($request->filled('has_coords'), fn ($q) => $q->hasCoordinates())
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->latest('last_seen_at')
            ->paginate(15)
            ->withQueryString();

        return view('crawler.records', [
            'records' => $records,
            'sources' => CrawlSource::orderBy('slug')->get(),
            'regions' => $this->regions(),
            'types' => CrawlRecord::query()
                ->distinct()
                ->orderBy('record_type')
                ->pluck('record_type'),
        ]);
    }

    public function show(CrawlRecord $record): View
    {
        $this->authorize('view', $record);

        return view('crawler.show', ['record' => $record->load('source', 'run')]);
    }

    /**
     * Admin/operator: trigger an on-demand crawl for a single source.
     */
    public function runCrawl(CrawlSource $source): RedirectResponse
    {
        $this->authorize('run', $source);

        ActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => ActivityLog::ACTION_CREATE,
            'resource_type' => 'CrawlRun',
            'new_values' => ['source' => $source->slug, 'trigger' => 'manual'],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        try {
            $this->manager->seedSources();
            $result = $this->manager->syncSource($source->slug);

            $extraMessage = '';
            if ($source->slug === 'kesehatan') {
                $run = CrawlRun::latest('started_at')->first();
                if ($run !== null && $run->status !== CrawlRun::STATUS_FAILED) {
                    $importStats = $this->healthImport->importFromCrawl($run);
                    $extraMessage = " (impor: {$importStats['created']} baru, {$importStats['updated']} diperbarui)";
                }
            }

            Log::channel('crawler')->info('crawler:manual-run', [
                'source' => $source->slug,
                'found' => $result->found,
            ]);

            return back()->with('success', "Sinkronisasi {$source->name} selesai (ditemukan {$result->found}, dibuat {$result->created}, diperbarui {$result->updated}).{$extraMessage}");
        } catch (\Throwable $e) {
            return back()->with('error', "Sinkronisasi gagal: {$e->getMessage()}");
        }
    }

    /**
     * Kesehatan crawl overview page.
     */
    public function kesehatan(Request $request): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $source = CrawlSource::where('slug', 'kesehatan')->first();
        $meta = $this->service->sourceMeta('kesehatan');

        $runs = $source
            ? $source->runs()->with('source')->latest('started_at')->paginate(15)->withQueryString()
            : collect();

        $lastRun = $source?->runs()->latest('started_at')->first();

        $recentErrors = $source
            ? $source->errors()->latest('occurred_at')->limit(10)->get()
            : collect();

        $facilityStats = [
            'total' => HealthFacility::count(),
            'crawled' => HealthFacility::whereNotNull('source_id')->count(),
            'puskesmas' => HealthFacility::where('facility_type', 'Puskesmas')->count(),
            'pustu' => HealthFacility::where('facility_type', 'Pustu')->count(),
            'rs' => HealthFacility::where('facility_type', 'Rumah Sakit')->count(),
            'posyandu' => HealthFacility::where('facility_type', 'Posyandu')->count(),
        ];

        $recentImports = HealthFacility::whereNotNull('last_crawled_at')
            ->latest('last_crawled_at')
            ->limit(10)
            ->get();

        return view('crawler.kesehatan.index', compact(
            'source', 'meta', 'runs', 'lastRun', 'recentErrors',
            'facilityStats', 'recentImports',
        ));
    }

    /**
     * Show a single health facility imported via crawl.
     */
    public function kesehatanShow(HealthFacility $facility): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $crawlRecord = null;
        if ($facility->source_id) {
            $crawlRecord = CrawlRecord::where('external_id', $facility->source_id)->first();
        }

        return view('crawler.kesehatan.show', [
            'facility' => $facility,
            'crawlRecord' => $crawlRecord,
        ]);
    }

    /**
     * Trigger a Kesehatan crawl and import.
     */
    public function runKesehatan(): RedirectResponse
    {
        $source = CrawlSource::where('slug', 'kesehatan')->firstOrFail();
        $this->authorize('run', $source);

        try {
            $this->manager->seedSources();
            $result = $this->manager->syncSource('kesehatan');

            $run = CrawlRun::latest('started_at')->first();

            $importStats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'warnings' => []];
            if ($run !== null && $run->status !== CrawlRun::STATUS_FAILED) {
                $importStats = $this->healthImport->importFromCrawl($run);
            }

            Log::channel('crawler')->info('crawler:manual-run:kesehatan', [
                'found' => $result->found,
                'import' => $importStats,
            ]);

            $message = 'Sinkronisasi Fasilitas Kesehatan selesai. '
                ."Ditemukan {$result->found}, "
                ."dibuat {$importStats['created']}, "
                ."diperbarui {$importStats['updated']}, "
                ."dilewati {$importStats['skipped']}, "
                ."gagal {$importStats['failed']}.";

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', "Sinkronisasi kesehatan gagal: {$e->getMessage()}");
        }
    }

    /**
     * @return array<string, string>
     */
    private function regions(): array
    {
        return (array) config('crawler.target_region.regions', []);
    }

    public function ats(Request $request): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $records = $this->service->recordsFor('ats', [
            'kabupaten' => $request->string('kabupaten')->toString() ?: null,
            'kecamatan' => $request->string('kecamatan')->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: $request->string('q')->toString() ?: null,
        ]);

        $source = CrawlSource::where('slug', 'ats')->first();

        return view('crawler.ats.index', [
            'meta' => $this->service->sourceMeta('ats'),
            'source' => $source,
            'records' => $records,
            'kecamatans' => $this->service->kecamatanOptions('ats'),
            'distribution' => $this->service->distributionByKecamatan('ats'),
            'byKabupaten' => $this->countsByKabupaten('ats'),
            'kategori' => $this->distinctDataValue('ats', 'category'),
            'lastRun' => $source?->runs()->latest('started_at')->first(),
            'regions' => $this->regions(),
        ]);
    }

    public function atsShow(CrawlRecord $record): View
    {
        $this->authorize('view', $record);
        $this->ensureSource($record, 'ats');

        return view('crawler.ats.show', [
            'record' => $record->load('source', 'run'),
            'meta' => $this->service->sourceMeta('ats'),
        ]);
    }

    public function dapo(Request $request): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $records = $this->service->recordsFor('dapo', [
            'kabupaten' => $request->string('kabupaten')->toString() ?: null,
            'kecamatan' => $request->string('kecamatan')->toString() ?: null,
            'jenjang' => $request->string('jenjang')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: $request->string('q')->toString() ?: null,
        ]);

        $source = CrawlSource::where('slug', 'dapo')->first();

        return view('crawler.dapo.index', [
            'meta' => $this->service->sourceMeta('dapo'),
            'source' => $source,
            'records' => $records,
            'kecamatans' => $this->service->kecamatanOptions('dapo'),
            'jenjangs' => $this->distinctDataValue('dapo', 'jenjang'),
            'distribution' => $this->service->distributionByKecamatan('dapo'),
            'byJenjang' => $this->countsByDataField('dapo', 'jenjang'),
            'lastRun' => $source?->runs()->latest('started_at')->first(),
            'regions' => $this->regions(),
        ]);
    }

    public function dapoShow(CrawlRecord $record): View
    {
        $this->authorize('view', $record);
        $this->ensureSource($record, 'dapo');

        return view('crawler.dapo.show', [
            'record' => $record->load('source', 'run'),
            'meta' => $this->service->sourceMeta('dapo'),
        ]);
    }

    public function sp2kp(Request $request): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $records = $this->service->recordsFor('sp2kp', [
            'kabupaten' => $request->string('kabupaten')->toString() ?: null,
            'kecamatan' => $request->string('kecamatan')->toString() ?: null,
            'category' => $request->string('komoditas')->toString() ?: null,
            'search' => $request->string('search')->toString() ?: $request->string('q')->toString() ?: null,
        ]);

        $source = CrawlSource::where('slug', 'sp2kp')->first();

        return view('crawler.sp2kp.index', [
            'meta' => $this->service->sourceMeta('sp2kp'),
            'source' => $source,
            'records' => $records,
            'kecamatans' => $this->service->kecamatanOptions('sp2kp'),
            'komoditas' => $this->distinctDataValue('sp2kp', 'komoditas'),
            'commodityCounts' => $this->countsByDataField('sp2kp', 'komoditas'),
            'lastRun' => $source?->runs()->latest('started_at')->first(),
            'regions' => $this->regions(),
        ]);
    }

    public function sp2kpShow(CrawlRecord $record): View
    {
        $this->authorize('view', $record);
        $this->ensureSource($record, 'sp2kp');

        return view('crawler.sp2kp.show', [
            'record' => $record->load('source', 'run'),
            'meta' => $this->service->sourceMeta('sp2kp'),
        ]);
    }

    public function bps(Request $request): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $indicators = $this->service->bpsIndicators();

        $category = $request->string('category')->toString() ?: null;
        if ($category) {
            $indicators = array_values(array_filter($indicators, fn ($i) => $i['category'] === $category));
        }

        $source = CrawlSource::where('slug', 'bps')->first();

        $categories = collect($this->service->bpsIndicators())
            ->groupBy('category')
            ->map(fn ($g) => [
                'name' => (string) $g->first()['category'],
                'count' => $g->count(),
                'latest_year' => $g->pluck('period')->filter()->max(),
                'regions' => $g->map(fn ($i) => $i['external_id'])->count(),
            ])
            ->values()
            ->all();

        return view('crawler.bps.index', [
            'meta' => $this->service->sourceMeta('bps'),
            'source' => $source,
            'indicators' => $indicators,
            'categories' => $categories,
            'activeCategory' => $category,
            'lastRun' => $source?->runs()->latest('started_at')->first(),
        ]);
    }

    public function bpsShow(CrawlRecord $record): View
    {
        $this->authorize('view', $record);
        $this->ensureSource($record, 'bps');

        $pairs = CrawlRecord::query()
            ->targetRegion()
            ->where('external_id', $record->external_id)
            ->get();

        return view('crawler.bps.show', [
            'record' => $record->load('source', 'run'),
            'pairs' => $pairs,
            'meta' => $this->service->sourceMeta('bps'),
        ]);
    }

    public function runs(Request $request): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        return view('crawler.runs.index', [
            'runs' => $this->service->runs($request->string('source')->toString() ?: null),
            'sources' => CrawlSource::orderBy('slug')->get(),
        ]);
    }

    public function runShow(CrawlRun $run): View
    {
        $this->authorize('viewAny', CrawlRecord::class);

        return view('crawler.runs.show', [
            'run' => $run->load('source', 'records', 'errors'),
            'records' => $run->records()->latest('last_seen_at')->limit(50)->get(),
            'meta' => $run->source ? $this->service->sourceMeta($run->source->slug) : null,
        ]);
    }

    private function ensureSource(CrawlRecord $record, string $slug): void
    {
        if ($record->source?->slug !== $slug) {
            abort(404);
        }
    }

    /**
     * @return array<string, int>
     */
    private function countsByKabupaten(string $slug): array
    {
        return CrawlRecord::query()
            ->whereHas('source', fn ($q) => $q->where('slug', $slug))
            ->get()
            ->groupBy('kabupaten_code')
            ->map->count()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function countsByDataField(string $slug, string $field): array
    {
        return CrawlRecord::query()
            ->whereHas('source', fn ($q) => $q->where('slug', $slug))
            ->get()
            ->flatMap(fn ($r) => [$r->data[$field] ?? 'Tidak diketahui'])
            ->countBy()
            ->sortDesc()
            ->all();
    }

    private function distinctDataValue(string $slug, string $field): array
    {
        return CrawlRecord::query()
            ->whereHas('source', fn ($q) => $q->where('slug', $slug))
            ->get()
            ->pluck("data.{$field}")
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
