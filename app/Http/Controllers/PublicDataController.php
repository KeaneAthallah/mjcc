<?php

namespace App\Http\Controllers;

use App\Models\ExternalData;
use App\Models\PublicDataSync;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PublicDataController extends Controller
{
    /**
     * Overview of the Data Publik module across all sectors.
     */
    public function index(): View
    {
        $sectors = [];

        foreach (config('public_data.sectors', []) as $key => $config) {
            $sectors[$key] = [
                'key' => $key,
                'label' => (string) $config['label'],
                'records' => ExternalData::where('sector', $key)->count(),
                'datasets' => ExternalData::where('sector', $key)->distinct()->count('dataset'),
                'sync' => $this->syncStatus($key),
                'url' => route('public-data.show', $key),
            ];
        }

        $latest = ExternalData::max('scraped_at');

        return view('public-data.index', [
            'sectors' => $sectors,
            'totalRecords' => array_sum(array_column($sectors, 'records')),
            'totalDatasets' => array_sum(array_column($sectors, 'datasets')),
            'latestScrapedAt' => $latest !== null ? Carbon::parse($latest) : null,
        ]);
    }

    public function show(Request $request, string $sector): View
    {
        $sectors = config('public_data.sectors', []);

        if (! array_key_exists($sector, $sectors)) {
            abort(404, 'Sektor tidak dikenal.');
        }

        $query = ExternalData::query()->where('sector', $sector);

        $dataset = (string) $request->string('dataset');
        $year = $request->integer('year');
        $location = (string) $request->string('location');
        $indicator = (string) $request->string('indicator');

        if ($dataset !== '') {
            $query->where('dataset', $dataset);
        }

        if ($year > 0) {
            $query->where('year', $year);
        }

        if ($location !== '') {
            $query->where('location', $location);
        }

        if ($indicator !== '') {
            $query->where('indicator', $indicator);
        }

        $records = (clone $query)
            ->orderByDesc('year')
            ->orderBy('dataset')
            ->orderBy('location')
            ->orderBy('indicator')
            ->paginate(50)
            ->withQueryString();

        $filters = [
            'datasets' => ExternalData::where('sector', $sector)->distinct()->orderBy('dataset')->pluck('dataset'),
            'years' => ExternalData::where('sector', $sector)->whereNotNull('year')->distinct()->orderByDesc('year')->pluck('year'),
            'locations' => ExternalData::where('sector', $sector)->distinct()->orderBy('location')->pluck('location'),
            'indicators' => ExternalData::where('sector', $sector)->distinct()->orderBy('indicator')->pluck('indicator'),
        ];

        return view('public-data.show', [
            'sector' => $sector,
            'sectorLabel' => (string) $sectors[$sector]['label'],
            'sync' => $this->syncStatus($sector),
            'summary' => [
                'records' => ExternalData::where('sector', $sector)->count(),
                'datasets' => ExternalData::where('sector', $sector)->distinct()->count('dataset'),
                'locations' => ExternalData::where('sector', $sector)->distinct()->count('location'),
                'years' => ExternalData::where('sector', $sector)->whereNotNull('year')->distinct()->count('year'),
            ],
            'records' => $records,
            'filters' => $filters,
            'active' => [
                'dataset' => $dataset,
                'year' => $year,
                'location' => $location,
                'indicator' => $indicator,
            ],
            'chart' => $this->chartData($sector, $dataset, $location, $indicator),
        ]);
    }

    public function dataset(string $sector, string $dataset): View
    {
        $sectors = config('public_data.sectors', []);

        if (! array_key_exists($sector, $sectors)) {
            abort(404, 'Sektor tidak dikenal.');
        }

        $records = ExternalData::query()
            ->where('sector', $sector)
            ->where('dataset', $dataset)
            ->orderByDesc('year')
            ->orderBy('location')
            ->orderBy('indicator')
            ->get();

        if ($records->isEmpty()) {
            abort(404, 'Dataset tidak ditemukan.');
        }

        $sample = $records->first();
        $raw = $sample->raw_data ?? [];
        $metadata = is_array($raw['metadata'] ?? null) ? $raw['metadata'] : [];
        $headers = is_array($raw['headers'] ?? null) ? $raw['headers'] : [];
        $row = is_array($raw['row'] ?? null) ? $raw['row'] : [];

        return view('public-data.dataset', [
            'sector' => $sector,
            'sectorLabel' => (string) $sectors[$sector]['label'],
            'sync' => $this->syncStatus($sector),
            'dataset' => $dataset,
            'topic' => $records->first()->topic,
            'sourceUrl' => $records->first()->source_url,
            'metadata' => $metadata,
            'headers' => $headers,
            'sampleRow' => $row,
            'summary' => [
                'records' => $records->count(),
                'locations' => $records->pluck('location')->unique()->values(),
                'years' => $records->whereNotNull('year')->pluck('year')->unique()->sortDesc()->values(),
                'indicators' => $records->pluck('indicator')->unique()->values(),
            ],
            'records' => $records,
            'chart' => $this->chartData($sector, $dataset, '', ''),
        ]);
    }

    private function syncStatus(string $sector): array
    {
        $sync = PublicDataSync::query()->where('sector', $sector)->first();

        $default = [
            'status' => PublicDataSync::STATUS_PENDING,
            'status_label' => PublicDataSync::statusLabels()[PublicDataSync::STATUS_PENDING],
            'last_attempt_at' => null,
            'last_success_at' => null,
            'last_error' => null,
            'record_count' => 0,
        ];

        if ($sync === null) {
            return $default;
        }

        $default['status'] = $sync->status;
        $default['status_label'] = $sync->statusLabel();
        $default['last_attempt_at'] = $sync->last_attempt_at;
        $default['last_success_at'] = $sync->last_success_at;
        $default['last_error'] = $sync->last_error;
        $default['record_count'] = $sync->record_count;

        return $default;
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, float>}
     */
    private function chartData(string $sector, string $dataset, string $location, string $indicator): array
    {
        $query = ExternalData::query()
            ->where('sector', $sector)
            ->whereNotNull('value')
            ->when($dataset !== '', fn ($q) => $q->where('dataset', $dataset))
            ->when($location !== '', fn ($q) => $q->where('location', $location))
            ->when($indicator !== '', fn ($q) => $q->where('indicator', $indicator));

        $byYear = (clone $query)
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

        $byLocation = (clone $query)
            ->selectRaw('location, SUM(value) as total')
            ->groupBy('location')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        return [
            'labels' => $byLocation->pluck('location')->values()->all(),
            'data' => $byLocation->pluck('total')->map(fn ($v) => (float) $v)->values()->all(),
        ];
    }
}
