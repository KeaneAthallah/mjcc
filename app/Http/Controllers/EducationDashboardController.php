<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Services\AlertService;
use App\Services\AtsDashboardService;
use App\Services\EducationDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EducationDashboardController extends Controller
{
    public function __construct(
        private readonly EducationDashboardService $service,
        private readonly AtsDashboardService $ats,
        private readonly AlertService $alerts,
    ) {}

    public function index(Request $request): View
    {
        $kecamatanId = $request->integer('kecamatan', 0) ?: null;

        $stats = $this->service->statistics($kecamatanId);
        $students = $this->service->studentPerKecamatan($kecamatanId);
        $teacherRatio = $this->service->teacherRatio($kecamatanId);
        $table = $this->service->table($kecamatanId);
        $facilities = $this->service->facilityProgress($kecamatanId);
        $map = $this->service->map($kecamatanId);
        $kecamatans = Kecamatan::orderBy('name')->get(['id', 'name']);
        $ats = $this->ats->dashboard($kecamatanId);

        $dashboardData = [
            'students' => $students,
            'teacherRatio' => $teacherRatio,
        ];

        $alerts = $this->alerts->forSector('pendidikan');

        return view('education.dashboard', compact(
            'stats',
            'dashboardData',
            'table',
            'facilities',
            'map',
            'kecamatans',
            'kecamatanId',
            'alerts',
            'ats'
        ));
    }
}
