<?php

namespace App\Http\Controllers;

use App\Models\HealthFacility;
use App\Models\Polsek;
use App\Models\School;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard) {}

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

        return view('dashboard.index', [
            'stats' => $stats,
            'dashboardData' => $dashboardData,
            'topSekolah' => $this->dashboard->topSekolah(),
            'topPoskamling' => $this->dashboard->topPoskamling(),
            'topKesehatan' => $this->dashboard->topKesehatan(),
            'schoolMap' => $schoolMap,
            'securityMap' => $securityMap,
            'healthMap' => $healthMap,
        ]);
    }
}
