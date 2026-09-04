<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CrawlRecordResource;
use App\Http\Resources\CrawlRunResource;
use App\Http\Resources\CrawlSourceResource;
use App\Http\Responses\ApiResponse;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrawlerController extends Controller
{
    /**
     * List all crawl sources with aggregate statistics.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', CrawlSource::class);

        $sources = CrawlSource::withCount(['runs', 'records'])
            ->withCount([
                'runs as last_run_at' => fn ($q) => $q->select(\DB::raw('MAX(started_at)')),
            ])
            ->orderBy('name')
            ->get();

        $items = CrawlSourceResource::collection($sources)->resolve();

        return ApiResponse::success($items, 'Data sumber crawling berhasil diambil.');
    }

    /**
     * Show a single crawl source with aggregate statistics.
     */
    public function show(string $slug): JsonResponse
    {
        $source = CrawlSource::where('slug', $slug)->firstOrFail();
        $this->authorize('view', $source);

        $source->loadCount(['runs', 'records']);
        $source->loadCount([
            'runs as last_run_at' => fn ($q) => $q->select(\DB::raw('MAX(started_at)')),
        ]);

        return ApiResponse::success(new CrawlSourceResource($source));
    }

    /**
     * List crawl runs, optionally filtered by source.
     */
    public function runs(Request $request, ?string $sourceSlug = null): JsonResponse
    {
        $this->authorize('viewAny', CrawlSource::class);

        $query = CrawlRun::query()
            ->with(['source:id,name,slug', 'errors'])
            ->when($sourceSlug !== null, function ($q) use ($sourceSlug) {
                $source = CrawlSource::where('slug', $sourceSlug)->firstOrFail();
                $q->where('crawl_source_id', $source->id);
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('started_at');

        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $paginator = $query->paginate($perPage)->withQueryString();

        $items = CrawlRunResource::collection($paginator->items())->resolve();

        return ApiResponse::paginate($items, $paginator, 'Data riwayat crawling berhasil diambil.');
    }

    /**
     * Show a single crawl run with records and errors.
     */
    public function runShow(int $runId): JsonResponse
    {
        $this->authorize('viewAny', CrawlSource::class);

        $run = CrawlRun::with(['source:id,name,slug', 'errors', 'records' => fn ($q) => $q->latest('last_seen_at')->limit(50)])
            ->findOrFail($runId);

        return ApiResponse::success(new CrawlRunResource($run));
    }

    /**
     * List crawl records with filtering and search.
     *
     * Supports: source, record_type, kabupaten_code, kecamatan_code,
     *           search (name, external_id), sort, per_page.
     */
    public function records(Request $request): JsonResponse
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $query = CrawlRecord::query()
            ->with(['source:id,name,slug']);

        if ($request->filled('source')) {
            $query->whereHas('source', fn ($q) => $q->where('slug', $request->string('source')));
        }

        if ($request->filled('record_type')) {
            $query->where('record_type', $request->string('record_type'));
        }

        if ($request->filled('kabupaten_code')) {
            $query->where('kabupaten_code', $request->string('kabupaten_code'));
        }

        if ($request->filled('kecamatan_code')) {
            $query->where('kecamatan_code', $request->string('kecamatan_code'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search')->toString();
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('external_id', 'like', '%'.$term.'%');
            });
        }

        $sort = in_array($request->string('sort')->toString(), ['name', 'record_type', 'kabupaten_code', 'last_seen_at', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'last_seen_at';

        $direction = strtolower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $perPage = max(1, min(100, $request->integer('per_page', 20)));
        $paginator = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        $items = CrawlRecordResource::collection($paginator->items())->resolve();

        return ApiResponse::paginate($items, $paginator, 'Data rekaman crawling berhasil diambil.');
    }

    /**
     * Show a single crawl record.
     */
    public function recordShow(int $recordId): JsonResponse
    {
        $this->authorize('viewAny', CrawlRecord::class);

        $record = CrawlRecord::with(['source:id,name,slug'])->findOrFail($recordId);

        return ApiResponse::success(new CrawlRecordResource($record));
    }
}
