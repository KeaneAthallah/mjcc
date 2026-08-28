<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Services\AlertService;
use App\Services\SecurityDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityDashboardController extends Controller
{
    public function __construct(
        private readonly SecurityDashboardService $service,
        private readonly AlertService $alerts,
    ) {}

    public function index(Request $request): View
    {
        $kecamatanId = $request->integer('kecamatan', 0) ?: null;

        $stats = $this->service->statistics($kecamatanId);
        $compare = $this->service->compareChart($kecamatanId);
        $distribution = $this->service->poskamlingDistribution($kecamatanId);
        $kelurahanChart = $this->service->kelurahanPerKecamatan($kecamatanId);
        $polsekTable = $this->service->polsekTable($kecamatanId);
        $map = $this->service->map($kecamatanId);
        $kecamatans = Kecamatan::orderBy('name')->get(['id', 'name']);

        $dashboardData = [
            'compare' => $compare,
            'distribution' => $distribution,
            'kelurahanChart' => $kelurahanChart,
        ];

        $alerts = $this->alerts->forSector('ketertiban');

        return view('security.dashboard', compact(
            'stats',
            'dashboardData',
            'polsekTable',
            'map',
            'kecamatans',
            'kecamatanId',
            'alerts'
        ));
    }
}
