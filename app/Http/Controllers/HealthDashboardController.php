<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Services\HealthDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthDashboardController extends Controller
{
    public function __construct(private readonly HealthDashboardService $service) {}

    public function index(Request $request): View
    {
        $kecamatanId = $request->integer('kecamatan', 0) ?: null;

        $stats = $this->service->statistics($kecamatanId);
        $workforce = $this->service->workforcePerKecamatan($kecamatanId);
        $proportion = $this->service->facilityProportion($kecamatanId);
        $capacity = $this->service->capacityPerKecamatan($kecamatanId);
        $table = $this->service->table($kecamatanId);
        $map = $this->service->map($kecamatanId);
        $kecamatans = Kecamatan::orderBy('name')->get(['id', 'name']);

        $dashboardData = [
            'workforce' => $workforce,
            'proportion' => $proportion,
            'capacity' => $capacity,
        ];

        return view('health.dashboard', compact(
            'stats',
            'dashboardData',
            'table',
            'map',
            'kecamatans',
            'kecamatanId'
        ));
    }
}
