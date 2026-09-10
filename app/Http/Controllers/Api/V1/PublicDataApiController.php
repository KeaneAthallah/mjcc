<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ApbdRecord;
use App\Models\BpsObservation;
use App\Models\CommodityPrice;
use App\Models\DisasterEvent;
use App\Models\DisasterRiskIndex;
use App\Models\ExternalData;
use App\Models\PublicDataSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicDataApiController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = config('public_data.categories', []);

        $data = array_map(fn (array $cat, string $key) => [
            'key' => $key,
            'label' => $cat['label'],
            'icon' => $cat['icon'],
            'description' => $cat['description'],
            'source_count' => PublicDataSource::where('category', $key)->count(),
        ], $categories, array_keys($categories));

        return ApiResponse::success(array_values($data), 'Kategori data publik');
    }

    public function sources(Request $request): JsonResponse
    {
        $query = PublicDataSource::query();

        $category = $request->string('category');
        if ($category->isNotEmpty()) {
            $query->where('category', $category);
        }

        $sources = $query->orderBy('category')->orderBy('name')->get()->map(fn (PublicDataSource $s) => [
            'key' => $s->key,
            'name' => $s->name,
            'category' => $s->category,
            'description' => $s->description,
            'status' => $s->status,
            'status_label' => $s->statusLabel(),
            'last_success_at' => $s->last_success_at?->toIso8601String(),
            'record_count' => $s->record_count,
            'freshness' => $s->freshnessLabel(),
            'source_url' => $s->source_url,
            'supports_map' => $s->supports_map,
        ]);

        return ApiResponse::success($sources, 'Sumber data publik');
    }

    public function show(Request $request, string $source): JsonResponse
    {
        $src = PublicDataSource::where('key', $source)->first();

        if (! $src) {
            return ApiResponse::error('Sumber data tidak ditemukan.', 404);
        }

        $data = match ($source) {
            'sp2kp' => $this->commodityApiData($request),
            'bps' => $this->bpsApiData($request),
            'irbi' => $this->riskApiData($request),
            'sitaba' => $this->disasterApiData($request),
            'apbd' => $this->apbdApiData($request),
            default => $this->genericApiData($request, $source),
        };

        return ApiResponse::success(array_merge([
            'source' => [
                'key' => $src->key,
                'name' => $src->name,
                'status' => $src->status,
                'last_success_at' => $src->last_success_at?->toIso8601String(),
                'record_count' => $src->record_count,
            ],
        ], $data), $src->name);
    }

    private function commodityApiData(Request $request): array
    {
        $query = CommodityPrice::query()->latest('record_date');

        $commodity = $request->string('commodity');
        if (! $commodity->isEmpty()) {
            $query->where('commodity', $commodity);
        }

        return [
            'total' => $query->count(),
            'latest_date' => CommodityPrice::max('record_date'),
            'records' => $query->paginate(50)->items(),
        ];
    }

    private function bpsApiData(Request $request): array
    {
        $query = BpsObservation::with('dataset');

        $datasetId = $request->integer('dataset_id');
        if ($datasetId > 0) {
            $query->where('bps_dataset_id', $datasetId);
        }

        $year = $request->integer('year');
        if ($year > 0) {
            $query->where('year', $year);
        }

        return [
            'total' => $query->count(),
            'records' => $query->paginate(50)->items(),
        ];
    }

    private function riskApiData(Request $request): array
    {
        $hazard = $request->string('hazard');

        $records = DisasterRiskIndex::query()
            ->when($hazard->isNotEmpty(), fn ($q) => $q->where('hazard_type', $hazard))
            ->get();

        return [
            'total' => $records->count(),
            'risk_counts' => [
                'Tinggi' => $records->where('risk_level', 'Tinggi')->count(),
                'Sedang' => $records->where('risk_level', 'Sedang')->count(),
                'Rendah' => $records->where('risk_level', 'Rendah')->count(),
            ],
            'records' => $records->all(),
        ];
    }

    private function disasterApiData(Request $request): array
    {
        $type = $request->string('type');
        $district = $request->string('district');

        $query = DisasterEvent::query();

        if ($type->isNotEmpty()) {
            $query->where('disaster_type', $type);
        }

        if ($district->isNotEmpty()) {
            $query->where('district', $district);
        }

        $query->orderByDesc('event_date');

        return [
            'total' => $query->count(),
            'records' => $query->paginate(30)->items(),
        ];
    }

    private function apbdApiData(Request $request): array
    {
        $year = $request->integer('year', (int) date('Y'));

        $records = ApbdRecord::where('year', $year)->get();

        return [
            'year' => $year,
            'total' => $records->count(),
            'total_pendapatan' => $records->where('category', 'Pendapatan')->sum('realization_value'),
            'total_belanja' => $records->where('category', 'Belanja')->sum('realization_value'),
            'records' => $records->all(),
        ];
    }

    private function genericApiData(Request $request, string $source): array
    {
        $query = ExternalData::where('source_key', $source);

        $year = $request->integer('year');
        if ($year > 0) {
            $query->where('year', $year);
        }

        return [
            'total' => $query->count(),
            'records' => $query->paginate(50)->items(),
        ];
    }
}
