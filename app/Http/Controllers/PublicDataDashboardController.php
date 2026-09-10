<?php

namespace App\Http\Controllers;

use App\Models\ApbdRecord;
use App\Models\BpsDataset;
use App\Models\BpsObservation;
use App\Models\CommodityPrice;
use App\Models\DisasterEvent;
use App\Models\DisasterRiskIndex;
use App\Models\ExternalData;
use App\Models\PublicDataSource;
use App\Services\PublicData\SourceRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicDataDashboardController extends Controller
{
    public function index(): View
    {
        $categories = config('public_data.categories', []);
        $sources = PublicDataSource::orderBy('category')->orderBy('name')->get();

        $categoryData = [];

        foreach ($categories as $key => $cat) {
            $catSources = $sources->where('category', $key);
            $totalRecords = 0;
            $latestSync = null;

            foreach ($catSources as $src) {
                $totalRecords += $src->record_count;
                if ($src->last_success_at && ($latestSync === null || $src->last_success_at->gt($latestSync))) {
                    $latestSync = $src->last_success_at;
                }
            }

            $categoryData[$key] = [
                'key' => $key,
                'label' => $cat['label'],
                'icon' => $cat['icon'],
                'description' => $cat['description'],
                'source_count' => $catSources->count(),
                'total_records' => $totalRecords,
                'latest_sync' => $latestSync,
                'sources' => $catSources->map(fn (PublicDataSource $s) => [
                    'key' => $s->key,
                    'name' => $s->name,
                    'status' => $s->status,
                    'status_label' => $s->statusLabel(),
                    'last_success_at' => $s->last_success_at,
                    'record_count' => $s->record_count,
                    'url' => route('public-data.source', $s->key),
                    'freshness' => $s->freshnessLabel(),
                    'supports_map' => $s->supports_map,
                ])->all(),
            ];
        }

        return view('public-data.dashboard', [
            'categories' => $categoryData,
            'totalSources' => $sources->count(),
            'activeSources' => $sources->where('status', PublicDataSource::STATUS_SUCCESS)->count(),
            'totalRecords' => $sources->sum('record_count'),
            'latestSync' => $sources->max('last_success_at'),
        ]);
    }

    public function category(string $category): View
    {
        $categories = config('public_data.categories', []);

        if (! array_key_exists($category, $categories)) {
            abort(404, 'Kategori tidak dikenal.');
        }

        $sources = PublicDataSource::where('category', $category)->orderBy('name')->get();

        return view('public-data.category', [
            'category' => $category,
            'categoryLabel' => $categories[$category]['label'],
            'categoryIcon' => $categories[$category]['icon'],
            'categoryDescription' => $categories[$category]['description'],
            'sources' => $sources,
        ]);
    }

    public function source(Request $request, string $sourceKey): View
    {
        $source = PublicDataSource::where('key', $sourceKey)->firstOrFail();

        $data = match ($sourceKey) {
            'sp2kp' => $this->commodityData($request),
            'bps' => $this->bpsData($request, $source),
            'irbi' => $this->riskData($request),
            'sitaba' => $this->disasterData($request),
            'apbd' => $this->apbdData($request),
            default => $this->genericData($request, $source),
        };

        return view('public-data.source', array_merge([
            'source' => $source,
        ], $data));
    }

    public function syncSource(string $sourceKey): RedirectResponse
    {
        $this->authorizeSync();

        $source = PublicDataSource::where('key', $sourceKey)->firstOrFail();

        $result = SourceRegistry::sync($source->key);

        $status = $result['status'] === 'success' ? 'success' : 'error';
        $message = $result['status'] === 'success'
            ? "Sinkronisasi {$source->name} berhasil."
            : "Sinkronisasi {$source->name} gagal: {$result['message']}";

        return redirect()->back()->with($status, $message);
    }

    public function syncAll(): RedirectResponse
    {
        $this->authorizeSync();

        $sources = PublicDataSource::enabled()->get();
        $success = 0;
        $failed = 0;

        foreach ($sources as $source) {
            $result = SourceRegistry::sync($source->key);
            if ($result['status'] === 'success') {
                $success++;
            } else {
                $failed++;
            }
        }

        $message = "Sinkronisasi selesai: {$success} berhasil, {$failed} gagal.";

        return redirect()->back()->with($failed > 0 ? 'error' : 'success', $message);
    }

    private function authorizeSync(): void
    {
        $user = Auth::user();

        if ($user === null || (! $user->isAdmin() && ! $user->isOperator())) {
            abort(403, 'Tidak diizinkan.');
        }
    }

    private function commodityData(Request $request): array
    {
        $query = CommodityPrice::query()->latest('record_date');

        $commodity = $request->string('commodity');
        $market = $request->string('market');

        if ($commodity->isNotEmpty()) {
            $query->where('commodity', $commodity);
        }

        if ($market->isNotEmpty()) {
            $query->where('market', $market);
        }

        $records = $query->paginate(50)->withQueryString();

        $latestDate = CommodityPrice::max('record_date');
        $latestPrices = $latestDate
            ? CommodityPrice::where('record_date', $latestDate)->get()
            : collect();

        $topIncreases = $latestPrices->where('percentage_change', '>', 0)->sortByDesc('percentage_change')->take(5)->values();
        $topDecreases = $latestPrices->where('percentage_change', '<', 0)->sortBy('percentage_change')->take(5)->values();

        return [
            'records' => $records,
            'latestDate' => $latestDate,
            'latestPrices' => $latestPrices,
            'topIncreases' => $topIncreases,
            'topDecreases' => $topDecreases,
            'commodities' => CommodityPrice::distinct()->pluck('commodity'),
            'markets' => CommodityPrice::distinct()->pluck('market'),
            'totalCommodities' => CommodityPrice::distinct()->count('commodity'),
            'averageChange' => CommodityPrice::where('record_date', $latestDate)->whereNotNull('percentage_change')->avg('percentage_change'),
        ];
    }

    private function bpsData(Request $request, PublicDataSource $source): array
    {
        $query = BpsObservation::with('dataset');

        $datasetId = $request->integer('dataset_id');
        $year = $request->integer('year');

        if ($datasetId > 0) {
            $query->where('bps_dataset_id', $datasetId);
        }

        if ($year > 0) {
            $query->where('year', $year);
        }

        $observations = $query->orderByDesc('year')->paginate(50)->withQueryString();

        return [
            'observations' => $observations,
            'datasets' => BpsDataset::orderBy('name')->get(),
            'years' => BpsObservation::distinct()->orderByDesc('year')->pluck('year'),
        ];
    }

    private function riskData(Request $request): array
    {
        $query = DisasterRiskIndex::query();

        $hazard = $request->string('hazard');
        if ($hazard->isNotEmpty()) {
            $query->where('hazard_type', $hazard);
        }

        $records = $query->orderBy('region_name')->get();
        $mapData = $records->filter(fn ($r) => $r->latitude && $r->longitude)->values();

        $selectedHazard = $hazard->isNotEmpty() ? $hazard->toString() : 'Multi Bahaya';
        $latestYear = DisasterRiskIndex::max('year');

        $mapRows = DisasterRiskIndex::query()
            ->where('year', $latestYear)
            ->where('hazard_type', $selectedHazard)
            ->get();

        return [
            'records' => $records,
            'mapData' => $mapData,
            'hazardTypes' => DisasterRiskIndex::distinct()->pluck('hazard_type'),
            'riskCounts' => [
                'Tinggi' => $records->where('risk_level', 'Tinggi')->count(),
                'Sedang' => $records->where('risk_level', 'Sedang')->count(),
                'Rendah' => $records->where('risk_level', 'Rendah')->count(),
            ],
            'riskChoropleth' => [
                'geojson_url' => url('geo/sulteng-kabupaten.geojson'),
                'year' => $latestYear,
                'hazard' => $selectedHazard,
                'regions' => $mapRows->mapWithKeys(fn ($r) => [$r->region_code => [
                    'name' => $r->region_name,
                    'index' => (float) $r->risk_index,
                    'level' => $r->risk_level,
                ]])->all(),
                'regions_count' => $mapRows->count(),
            ],
        ];
    }

    private function disasterData(Request $request): array
    {
        $query = DisasterEvent::query();

        $type = $request->string('type');
        $district = $request->string('district');

        if ($type->isNotEmpty()) {
            $query->where('disaster_type', $type);
        }

        if ($district->isNotEmpty()) {
            $query->where('district', $district);
        }

        $records = $query->orderByDesc('event_date')->paginate(30)->withQueryString();
        $mapData = DisasterEvent::whereNotNull('latitude')->whereNotNull('longitude')->get();

        return [
            'records' => $records,
            'mapData' => $mapData,
            'types' => DisasterEvent::distinct()->pluck('disaster_type'),
            'districts' => DisasterEvent::whereNotNull('district')->distinct()->pluck('district'),
            'recentCount' => DisasterEvent::where('event_date', '>=', now()->subDays(30))->count(),
        ];
    }

    private function apbdData(Request $request): array
    {
        $year = $request->integer('year', (int) date('Y'));

        $records = ApbdRecord::where('year', $year)->orderBy('category')->orderBy('indicator')->get();

        $years = ApbdRecord::distinct()->orderByDesc('year')->pluck('year');

        $totalPendapatan = $records->where('category', 'Pendapatan')->sum('realization_value');
        $totalBelanja = $records->where('category', 'Belanja')->sum('realization_value');

        return [
            'records' => $records,
            'year' => $year,
            'years' => $years,
            'totalPendapatan' => $totalPendapatan,
            'totalBelanja' => $totalBelanja,
            'categories' => $records->groupBy('category'),
        ];
    }

    private function genericData(Request $request, PublicDataSource $source): array
    {
        $query = ExternalData::query()
            ->where('source_key', $source->key)
            ->orderByDesc('year')
            ->orderBy('indicator');

        $indicator = $request->string('indicator');
        if ($indicator->isNotEmpty()) {
            $query->where('indicator', $indicator);
        }

        $year = $request->integer('year');
        if ($year > 0) {
            $query->where('year', $year);
        }

        $records = $query->paginate(50)->withQueryString();

        $chartData = $this->genericChartData($source->key);

        return [
            'records' => $records,
            'chart' => $chartData,
            'indicators' => ExternalData::where('source_key', $source->key)->distinct()->pluck('indicator'),
            'years' => ExternalData::where('source_key', $source->key)->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year'),
        ];
    }

    private function genericChartData(string $sourceKey): array
    {
        $byYear = ExternalData::where('source_key', $sourceKey)
            ->whereNotNull('value')
            ->whereNotNull('year')
            ->selectRaw('year, SUM(value) as total')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        if ($byYear->isNotEmpty()) {
            return [
                'labels' => $byYear->pluck('year')->map(fn ($y) => (string) $y)->values()->all(),
                'data' => $byYear->pluck('total')->map(fn ($v) => (float) $v)->values()->all(),
            ];
        }

        $byIndicator = ExternalData::where('source_key', $sourceKey)
            ->whereNotNull('value')
            ->selectRaw('indicator, SUM(value) as total')
            ->groupBy('indicator')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return [
            'labels' => $byIndicator->pluck('indicator')->values()->all(),
            'data' => $byIndicator->pluck('total')->map(fn ($v) => (float) $v)->values()->all(),
        ];
    }
}
