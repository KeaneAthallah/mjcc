<?php

namespace App\Http\Controllers;

use App\Models\ApbdRecord;
use App\Models\BpsDataset;
use App\Models\BpsObservation;
use App\Models\CommodityPrice;
use App\Models\DisasterEvent;
use App\Models\DisasterRiskIndex;
use App\Models\ExternalData;
use App\Models\Kecamatan;
use App\Models\PublicDataSource;
use App\Services\AtsDashboardService;
use App\Services\PublicData\SourceRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'ats' => $this->atsData($request, $source),
            'dapodik' => $this->dapodikData($request, $source),
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
        $markets = CommodityPrice::distinct()->pluck('market');
        $totalCommodities = CommodityPrice::distinct()->count('commodity');
        $averageChange = CommodityPrice::where('record_date', $latestDate)->whereNotNull('percentage_change')->avg('percentage_change');

        $insights = [];

        if ($latestDate) {
            $insights[] = [
                'icon' => '🛒',
                'title' => 'Komoditas termonitor',
                'detail' => number_format($totalCommodities).' komoditas dipantau di '.number_format($markets->count()).' pasar per '.Carbon::parse($latestDate)->translatedFormat('d M Y').'.',
            ];

            if (($top = $topIncreases->first()) !== null) {
                $insights[] = [
                    'icon' => '📈',
                    'title' => 'Kenaikan terbesar',
                    'detail' => $top->commodity.' naik '.$top->formattedPercentageChange().' menjadi Rp '.number_format((float) $top->current_price, 0, ',', '.').'.',
                ];
            }

            if (($drop = $topDecreases->first()) !== null) {
                $insights[] = [
                    'icon' => '📉',
                    'title' => 'Penurunan terbesar',
                    'detail' => $drop->commodity.' turun '.$drop->formattedPercentageChange().' menjadi Rp '.number_format((float) $drop->current_price, 0, ',', '.').'.',
                ];
            }

            $insights[] = [
                'icon' => '📊',
                'title' => 'Perubahan rata-rata',
                'detail' => 'Rata-rata perubahan harga '.($averageChange !== null ? number_format($averageChange, 1, ',', '.').'%' : 'belum tersedia').' pada periode terakhir.',
            ];
        } else {
            $insights[] = [
                'icon' => '🕘',
                'title' => 'Belum ada data',
                'detail' => 'Jalankan sinkronisasi untuk mengambil harga komoditas terbaru dari sumber SP2KP.',
            ];
        }

        return [
            'records' => $records,
            'latestDate' => $latestDate,
            'latestPrices' => $latestPrices,
            'topIncreases' => $topIncreases,
            'topDecreases' => $topDecreases,
            'commodities' => CommodityPrice::distinct()->pluck('commodity'),
            'markets' => $markets,
            'totalCommodities' => $totalCommodities,
            'averageChange' => $averageChange,
            'insights' => $insights,
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

        $byYear = BpsObservation::selectRaw('year, COUNT(*) as total')
            ->whereNotNull('year')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $datasetBreakdown = BpsObservation::with('dataset')
            ->whereNotNull('value')
            ->get()
            ->groupBy(fn ($o) => $o->dataset?->name ?? 'Lainnya')
            ->map->count()
            ->sortDesc()
            ->take(6);

        $totalObservations = BpsObservation::count();
        $regionTotal = BpsObservation::whereNotNull('region_name')->distinct()->count('region_name');
        $latestYear = (int) BpsObservation::max('year');
        $earliestYear = (int) BpsObservation::min('year');
        $topDataset = $datasetBreakdown->keys()->first();

        $insights = [];

        if ($totalObservations > 0) {
            $insights[] = [
                'icon' => '🧮',
                'title' => 'Observasi tersimpan',
                'detail' => number_format($totalObservations).' observasi dari '.number_format($regionTotal).' wilayah dan '.number_format($datasetBreakdown->count()).' dataset BPS.',
            ];

            if ($topDataset !== null) {
                $insights[] = [
                    'icon' => '📚',
                    'title' => 'Dataset terbanyak',
                    'detail' => $topDataset.' menjadi dataset dengan jumlah observasi terbanyak ('.number_format((int) $datasetBreakdown->first()).' baris).',
                ];
            }

            if ($earliestYear > 0 && $latestYear >= $earliestYear) {
                $range = $earliestYear === $latestYear ? (string) $latestYear : $earliestYear.'–'.$latestYear;
                $insights[] = [
                    'icon' => '📅',
                    'title' => 'Rentang tahun',
                    'detail' => 'Data mencakup seri tahun '.$range.' — cocok untuk analisis tren indikator statistik.',
                ];
            }
        } else {
            $insights[] = [
                'icon' => '🕘',
                'title' => 'Belum ada data',
                'detail' => 'Jalankan sinkronisasi untuk mengambil observasi BPS dari sumber resmi.',
            ];
        }

        return [
            'observations' => $observations,
            'datasets' => BpsDataset::orderBy('name')->get(),
            'years' => BpsObservation::distinct()->orderByDesc('year')->pluck('year'),
            'byYear' => [
                'labels' => $byYear->pluck('year')->map(fn ($y) => (string) $y)->values()->all(),
                'data' => $byYear->pluck('total')->map(fn ($v) => (int) $v)->values()->all(),
            ],
            'datasetBreakdown' => [
                'labels' => $datasetBreakdown->keys()->values()->all(),
                'data' => $datasetBreakdown->values()->map(fn ($v) => (int) $v)->all(),
            ],
            'insights' => $insights,
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

        $averageIndex = $records->avg('risk_index');
        $highestRisk = $records->sortByDesc('risk_index')->first();
        $hazardTypeCount = DisasterRiskIndex::distinct()->count('hazard_type');
        $riskCounts = [
            'Tinggi' => $records->where('risk_level', 'Tinggi')->count(),
            'Sedang' => $records->where('risk_level', 'Sedang')->count(),
            'Rendah' => $records->where('risk_level', 'Rendah')->count(),
        ];

        $insights = [];

        if ($records->isNotEmpty()) {
            $insights[] = [
                'icon' => '🌋',
                'title' => 'Sebaran level risiko',
                'detail' => 'Dari '.number_format($records->count()).' baris indeks, '.number_format($riskCounts['Tinggi']).' berisiko tinggi, '.number_format($riskCounts['Sedang']).' sedang, '.number_format($riskCounts['Rendah']).' rendah.',
            ];

            if ($highestRisk !== null) {
                $insights[] = [
                    'icon' => '⚠️',
                    'title' => 'Indeks tertinggi',
                    'detail' => $highestRisk->region_name.' mencatatkan indeks risiko '.$highestRisk->hazard_type.' sebesar '.number_format((float) $highestRisk->risk_index, 2, ',', '.').' ('.$highestRisk->risk_level.').',
                ];
            }

            $insights[] = [
                'icon' => '📐',
                'title' => 'Rata-rata indeks',
                'detail' => 'Rata-rata indeks risiko keseluruhan '.($averageIndex !== null ? number_format((float) $averageIndex, 2, ',', '.') : '—').' untuk '.number_format($hazardTypeCount).' jenis bahaya yang dipetakan.',
            ];
        } else {
            $insights[] = [
                'icon' => '🕘',
                'title' => 'Belum ada data',
                'detail' => 'Jalankan sinkronisasi untuk mengambil indeks risiko bencana terbaru.',
            ];
        }

        return [
            'records' => $records,
            'mapData' => $mapData,
            'hazardTypes' => DisasterRiskIndex::distinct()->pluck('hazard_type'),
            'riskCounts' => [
                'Tinggi' => $riskCounts['Tinggi'],
                'Sedang' => $riskCounts['Sedang'],
                'Rendah' => $riskCounts['Rendah'],
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
            'averageIndex' => $averageIndex,
            'insights' => $insights,
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

        $byType = DisasterEvent::whereNotNull('disaster_type')
            ->selectRaw('disaster_type, COUNT(*) as total')
            ->groupBy('disaster_type')
            ->orderByDesc('total')
            ->get();

        $byDistrict = DisasterEvent::whereNotNull('district')
            ->selectRaw('district, COUNT(*) as total')
            ->groupBy('district')
            ->orderByDesc('total')
            ->get();

        $totalCount = DisasterEvent::count();
        $recentHubCount = DisasterEvent::where('event_date', '>=', now()->subDays(30))->count();
        $impactTotal = (int) DisasterEvent::sum('affected_population');
        $topType = $byType->first()?->disaster_type;
        $topDistrict = $byDistrict->first()?->district;

        $insights = [];

        if ($totalCount > 0) {
            $insights[] = [
                'icon' => '🚨',
                'title' => 'Bencana terpantau',
                'detail' => number_format($totalCount).' kejadian tercatat, '.number_format($recentHubCount).' di antaranya terjadi dalam 30 hari terakhir.',
            ];

            if ($recentHubCount > 0) {
                $insights[] = [
                    'icon' => '📌',
                    'title' => 'Lokasi terbanyak',
                    'detail' => ($topDistrict ?? '—').' mencatatkan kejadian terbanyak ('.number_format((int) ($byDistrict->first()?->total ?? 0)).' kejadian) — perkuat kesiapsiagaan di wilayah ini.',
                ];
            }

            $insights[] = [
                'icon' => '🏷️',
                'title' => 'Jenis terbanyak',
                'detail' => ($topType ?? '—').' menjadi jenis bencana yang paling sering terjadi selama periode pantauan.',
            ];

            if ($impactTotal > 0) {
                $insights[] = [
                    'icon' => '👥',
                    'title' => 'Dampak penduduk',
                    'detail' => 'Total penduduk terdampak tercatat '.number_format($impactTotal).' jiwa dari seluruh kejadian.',
                ];
            }
        } else {
            $insights[] = [
                'icon' => '🕘',
                'title' => 'Belum ada data',
                'detail' => 'Jalankan sinkronisasi untuk mengambil kejadian bencana terbaru dari SITABA.',
            ];
        }

        return [
            'records' => $records,
            'mapData' => $mapData,
            'types' => DisasterEvent::distinct()->pluck('disaster_type'),
            'districts' => DisasterEvent::whereNotNull('district')->distinct()->pluck('district'),
            'recentCount' => $recentHubCount,
            'byType' => [
                'labels' => $byType->pluck('disaster_type')->values()->all(),
                'data' => $byType->pluck('total')->map(fn ($v) => (int) $v)->values()->all(),
            ],
            'byDistrict' => [
                'labels' => $byDistrict->pluck('district')->values()->all(),
                'data' => $byDistrict->pluck('total')->map(fn ($v) => (int) $v)->values()->all(),
            ],
            'impactTotal' => $impactTotal,
            'insights' => $insights,
        ];
    }

    private function apbdData(Request $request): array
    {
        $year = $request->integer('year', (int) date('Y'));

        $records = ApbdRecord::where('year', $year)->orderBy('category')->orderBy('indicator')->get();

        $years = ApbdRecord::distinct()->orderByDesc('year')->pluck('year');

        $totalPendapatan = $records->where('category', 'Pendapatan')->sum('realization_value');
        $totalBelanja = $records->where('category', 'Belanja')->sum('realization_value');
        $totalTarget = $records->sum('target_value');
        $totalRealisasi = $records->sum('realization_value');
        $realizationRate = $totalTarget > 0 ? (int) round($totalRealisasi / $totalTarget * 100) : 0;
        $fiskal = $totalPendapatan - $totalBelanja;
        $topItem = $records->sortByDesc('realization_value')->first();

        $insights = [];

        if ($records->isNotEmpty()) {
            $insights[] = [
                'icon' => '🧾',
                'title' => 'Realisasi anggaran',
                'detail' => 'Tingkat realisasi anggaran tahun '.$year.' mencapai '.$realizationRate.'% dari total target Rp '.number_format($totalTarget, 0, ',', '.').'.',
            ];

            $insights[] = [
                'icon' => '⚖️',
                'title' => 'Posisi fiskal',
                'detail' => 'Selisih pendapatan vs belanja '.($fiskal >= 0 ? 'surplus' : 'defisit').' sebesar Rp '.number_format(abs($fiskal), 0, ',', '.').'.',
            ];

            if ($topItem !== null) {
                $insights[] = [
                    'icon' => '💎',
                    'title' => 'Item terbesar',
                    'detail' => $topItem->indicator.' menjadi pos realisasi terbesar tahun '.$year.' (Rp '.number_format((float) $topItem->realization_value, 0, ',', '.').').',
                ];
            }
        } else {
            $insights[] = [
                'icon' => '🕘',
                'title' => 'Belum ada data',
                'detail' => 'Jalankan sinkronisasi untuk mengambil data anggaran dari sumber DJPK Kemenkeu.',
            ];
        }

        return [
            'records' => $records,
            'year' => $year,
            'years' => $years,
            'totalPendapatan' => $totalPendapatan,
            'totalBelanja' => $totalBelanja,
            'categories' => $records->groupBy('category'),
            'realizationRate' => $realizationRate,
            'fiskal' => $fiskal,
            'insights' => $insights,
        ];
    }

    private function atsData(Request $request, PublicDataSource $source): array
    {
        $kecamatanId = $request->integer('kecamatan') ?: null;

        return array_merge($this->genericData($request, $source), [
            'ats' => (new AtsDashboardService)->dashboard($kecamatanId),
        ]);
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

        $datasets = ExternalData::where('source_key', $source->key)->whereNotNull('dataset')->distinct()->orderBy('dataset')->pluck('dataset');
        $locationTotal = ExternalData::where('source_key', $source->key)->whereNotNull('location')->distinct()->count('location');
        $totalRows = ExternalData::where('source_key', $source->key)->count();
        $latestYear = ExternalData::where('source_key', $source->key)->max('year');
        $datasetDistribution = ExternalData::where('source_key', $source->key)
            ->whereNotNull('dataset')
            ->selectRaw('dataset, COUNT(*) as total')
            ->groupBy('dataset')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $topIndicator = ExternalData::where('source_key', $source->key)
            ->whereNotNull('value')
            ->selectRaw('indicator, SUM(value) as total')
            ->groupBy('indicator')
            ->orderByDesc('total')
            ->first();

        $insights = [];

        if ($totalRows > 0) {
            $insights[] = [
                'icon' => '📦',
                'title' => 'Record tersimpan',
                'detail' => number_format($totalRows).' baris data tersimpan untuk sumber ini di database publik.',
            ];

            if ($datasets->isNotEmpty()) {
                $insights[] = [
                    'icon' => '📚',
                    'title' => 'Dataset aktif',
                    'detail' => number_format($datasets->count()).' dataset terpetakan — '.$chartData['labels'][0] ?? 'perhatikan distribusi'.' sebagai indikator dominan pada grafik.',
                ];
            }

            $insights[] = [
                'icon' => '📍',
                'title' => 'Wilayah terpantau',
                'detail' => 'Data mencakup '.number_format($locationTotal).' wilayah/lokasi berbeda.',
            ];

            if ($latestYear !== null) {
                $insights[] = [
                    'icon' => '🗓️',
                    'title' => 'Data terbaru tahun',
                    'detail' => 'Data terbaru tercatat pada tahun '.$latestYear.' — pastikan rutin sinkronisasi.'.(($topIndicator !== null) ? ' Indikator puncak: '.$topIndicator->indicator.'.' : ''),
                ];
            }
        } else {
            $insights[] = [
                'icon' => '🕘',
                'title' => 'Belum ada data',
                'detail' => 'Jalankan sinkronisasi untuk menarik data terbaru dari sumber resmi.',
            ];
        }

        return [
            'records' => $records,
            'chart' => $chartData,
            'indicators' => ExternalData::where('source_key', $source->key)->distinct()->pluck('indicator'),
            'years' => ExternalData::where('source_key', $source->key)->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year'),
            'datasets' => $datasets,
            'locations' => $locationTotal,
            'datasetDistribution' => [
                'labels' => $datasetDistribution->pluck('dataset')->values()->all(),
                'data' => $datasetDistribution->pluck('total')->map(fn ($v) => (int) $v)->values()->all(),
            ],
            'insights' => $insights,
        ];
    }

    private function dapodikData(Request $request, PublicDataSource $source): array
    {
        $data = $this->genericData($request, $source);

        $rowIndex = [];
        foreach (ExternalData::where('source_key', 'dapodik')->get(['location', 'indicator', 'value']) as $row) {
            $rowIndex[$row->location][$row->indicator] = (float) $row->value;
        }

        $regionName = config('public_data.dapodik.region_name', 'Kabupaten Morowali');
        $kabupaten = $rowIndex[$regionName] ?? [];

        $byKecamatan = Kecamatan::orderBy('name')
            ->get(['name'])
            ->map(fn (Kecamatan $k) => [
                'name' => $k->name,
                'schools' => (int) ($rowIndex[$k->name]['Jumlah Sekolah'] ?? 0),
                'students' => (int) ($rowIndex[$k->name]['Jumlah Siswa'] ?? 0),
                'teachers' => (int) ($rowIndex[$k->name]['Jumlah Guru'] ?? 0),
            ])
            ->filter(fn ($r) => $r['students'] > 0 || $r['schools'] > 0)
            ->values();

        $rankings = $byKecamatan->sortByDesc('students');
        $topKecamatan = $rankings->take(5)->values();
        $bottomKecamatan = $rankings->slice(-5)->values();

        $insights = [];

        if ($kabupaten !== []) {
            $insights[] = [
                'icon' => '🏫',
                'title' => 'Sekolah terdaftar',
                'detail' => 'Kabupaten '.$regionName.' mencatat '.number_format((int) ($kabupaten['Jumlah Sekolah'] ?? 0)).' satuan pendidikan di Dapodik.',
            ];

            $insights[] = [
                'icon' => '👩‍🎓',
                'title' => 'Siswa terdaftar',
                'detail' => 'Total '.number_format((int) ($kabupaten['Jumlah Siswa'] ?? 0)).' siswa dan '.number_format((int) ($kabupaten['Jumlah Guru'] ?? 0)).' guru tercatat aktif.',
            ];

            if ($byKecamatan->isNotEmpty()) {
                $insights[] = [
                    'icon' => '🏆',
                    'title' => 'Kecamatan terpadat',
                    'detail' => $topKecamatan->get(0)['name'].' memiliki jumlah siswa terbanyak ('.number_format((int) $topKecamatan->get(0)['students']).' siswa).',
                ];
            }
        } else {
            $insights[] = [
                'icon' => '🕘',
                'title' => 'Belum ada data',
                'detail' => 'Jalankan sinkronisasi Dapodik untuk menarik data pendidikan terbaru.',
            ];
        }

        return array_merge($data, [
            'byKecamatan' => $byKecamatan,
            'topKecamatan' => $topKecamatan,
            'bottomKecamatan' => $bottomKecamatan,
            'dapodikRegion' => $regionName,
            'kabupatenSchools' => (int) ($kabupaten['Jumlah Sekolah'] ?? 0),
            'kabupatenStudents' => (int) ($kabupaten['Jumlah Siswa'] ?? 0),
            'kabupatenTeachers' => (int) ($kabupaten['Jumlah Guru'] ?? 0),
            'insights' => $insights,
        ]);
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
