<?php

namespace App\Http\Controllers;

use App\Models\CommandAlert;
use App\Models\ExternalData;
use App\Models\HealthFacility;
use App\Models\Polsek;
use App\Models\PublicDataSync;
use App\Models\School;
use App\Services\CommandAlertSyncService;
use App\Services\CommandCenterStatusService;
use App\Services\DashboardService;
use App\Services\DataFreshnessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly CommandAlertSyncService $alerts,
        private readonly CommandCenterStatusService $statusService,
        private readonly DataFreshnessService $freshness,
    ) {}

    public function index(): View
    {
        $stats = $this->dashboard->overviewStats();

        $schoolMap = School::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('kecamatan:id,name')
            ->get()
            ->map(fn ($s) => [
                'name' => $s->name,
                'category' => $s->school_type,
                'latitude' => (float) $s->latitude,
                'longitude' => (float) $s->longitude,
                'kecamatan' => $s->kecamatan?->name,
                'details' => [
                    'Jenis' => $s->school_type,
                    'Siswa' => (int) $s->students_male + (int) $s->students_female,
                    'Guru' => (int) $s->teachers,
                ],
            ]);

        $securityMap = Polsek::query()
            ->whereNotNull('latitude')->whereNotNull('longitude')->with('kecamatan:id,name')->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'category' => 'polsek',
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'kecamatan' => $p->kecamatan?->name,
                'details' => ['Personel' => (int) $p->personnel_count],
            ]);

        $healthMap = HealthFacility::query()
            ->whereNotNull('latitude')->whereNotNull('longitude')->with('kecamatan:id,name')->get()
            ->map(fn ($f) => [
                'name' => $f->name,
                'category' => $f->facility_type,
                'latitude' => (float) $f->latitude,
                'longitude' => (float) $f->longitude,
                'kecamatan' => $f->kecamatan?->name,
                'details' => ['Dokter' => (int) $f->doctors, 'Perawat' => (int) $f->nurses],
            ]);

        $dashboardData = [
            'comparison' => $this->dashboard->comparisonChart(),
            'infra' => $this->dashboard->infraComposition(),
            'students' => $this->dashboard->studentChart(),
            'workforce' => $this->dashboard->healthWorkforceChart(),
            'palette' => ['#10b981', '#059669', '#047857', '#3b82f6', '#2563eb', '#1d4ed8', '#6ee7b7', '#34d399', '#a7f3d0', '#60a5fa'],
        ];

        $this->alerts->sync();

        $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];

        $openAlerts = CommandAlert::query()
            ->with('kecamatan:id,name')
            ->whereIn('status', CommandAlert::openStatuses())
            ->latest('opened_at')
            ->limit(30)
            ->get()
            ->sortBy(fn (CommandAlert $a) => [$severityOrder[$a->severity] ?? 3, $a->opened_at?->timestamp ?? 0])
            ->take(8)
            ->values();

        $status = $this->statusService->overall();
        $freshness = $this->freshness->snapshot();

        $publicData = [
            'records' => ExternalData::count(),
            'datasets' => ExternalData::distinct()->count('dataset'),
            'sectors' => [],
        ];

        foreach (config('public_data.sectors', []) as $key => $config) {
            $publicData['sectors'][$key] = [
                'label' => (string) $config['label'],
                'records' => ExternalData::where('sector', $key)->count(),
                'datasets' => ExternalData::where('sector', $key)->distinct()->count('dataset'),
                'last_success_at' => PublicDataSync::where('sector', $key)->value('last_success_at'),
            ];
        }

        return view('dashboard.index', [
            'stats' => $stats,
            'openAlerts' => $openAlerts,
            'alertCounts' => $this->alertCounts(),
            'status' => $status,
            'freshness' => $freshness,
            'publicData' => $publicData,
            'dashboardData' => $dashboardData,
            'topSekolah' => $this->dashboard->topSekolah(),
            'topPoskamling' => $this->dashboard->topPoskamling(),
            'topKesehatan' => $this->dashboard->topKesehatan(),
            'schoolMap' => $schoolMap,
            'securityMap' => $securityMap,
            'healthMap' => $healthMap,
        ]);
    }

    public function refresh(): RedirectResponse
    {
        $this->dashboard->clearCache();
        $this->alerts->sync(force: true);

        Cache::forget('command-center.status.overall');
        Cache::forget('command-center.data-freshness');

        return redirect()->back()
            ->with('success', 'Data dashboard berhasil dimuat ulang dalam keadaan terbaru.');
    }

    /**
     * @return array<string, int>
     */
    private function alertCounts(): array
    {
        $base = CommandAlert::query()->whereIn('status', CommandAlert::openStatuses());

        return [
            'critical' => (clone $base)->where('severity', CommandAlert::SEVERITY_CRITICAL)->count(),
            'warning' => (clone $base)->where('severity', CommandAlert::SEVERITY_WARNING)->count(),
            'info' => (clone $base)->where('severity', CommandAlert::SEVERITY_INFO)->count(),
            'total' => $base->count(),
        ];
    }
}
