<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\AlertService;
use App\Services\DashboardService;
use App\Services\EducationDashboardService;
use App\Services\HealthDashboardService;
use App\Services\SecurityDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly AlertService $alerts,
        private readonly EducationDashboardService $education,
        private readonly SecurityDashboardService $security,
        private readonly HealthDashboardService $health,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $kecamatanId = $request->integer('kecamatan_id') ?: null;

        return ApiResponse::success([
            'stats' => $this->dashboard->overviewStats(),
            'comparison' => $this->dashboard->comparisonChart(),
            'infra_composition' => $this->dashboard->infraComposition(),
            'student_chart' => $this->dashboard->studentChart(),
            'health_workforce_chart' => $this->dashboard->healthWorkforceChart(),
            'top_schools' => $this->dashboard->topSekolah()->map(
                fn ($k) => ['kecamatan_id' => $k->id, 'name' => $k->name, 'count' => (int) $k->schools_count]
            )->values(),
            'top_poskamling' => $this->dashboard->topPoskamling()->map(
                fn ($k) => ['kecamatan_id' => $k->id, 'name' => $k->name, 'count' => (int) $k->active_poskamling]
            )->values(),
            'top_health' => $this->dashboard->topKesehatan()->map(
                fn ($k) => ['kecamatan_id' => $k->id, 'name' => $k->name, 'count' => (int) $k->workers]
            )->values(),
            'per_kecamatan' => $this->dashboard->kecamatansWithCounts()->map(fn ($k) => [
                'kecamatan_id' => $k->id,
                'name' => $k->name,
                'schools' => (int) $k->schools_count,
                'tipkamtikmas' => (int) $k->tipkamtikmas_count,
                'health_facilities' => (int) $k->health_facilities_count,
                'kelurahans' => (int) $k->kelurahans_count,
                'poskamlings' => (int) $k->poskamlings_count,
                'markets' => (int) $k->markets_count,
            ])->values(),
            'alerts' => $this->alerts->alerts(),
        ]);
    }

    public function education(Request $request): JsonResponse
    {
        $kecamatanId = $request->integer('kecamatan_id') ?: null;

        return ApiResponse::success([
            'statistics' => $this->education->statistics($kecamatanId),
            'student_per_kecamatan' => $this->education->studentPerKecamatan($kecamatanId),
            'teacher_ratio' => $this->education->teacherRatio($kecamatanId),
            'facility_progress' => $this->education->facilityProgress($kecamatanId),
            'table' => $this->education->table($kecamatanId)->toArray(),
        ]);
    }

    public function security(Request $request): JsonResponse
    {
        $kecamatanId = $request->integer('kecamatan_id') ?: null;

        return ApiResponse::success([
            'statistics' => $this->security->statistics($kecamatanId),
            'compare_chart' => $this->security->compareChart($kecamatanId),
            'poskamling_distribution' => $this->security->poskamlingDistribution($kecamatanId),
            'kelurahan_per_kecamatan' => $this->security->kelurahanPerKecamatan($kecamatanId),
            'polseks' => $this->security->polsekTable($kecamatanId)->toArray(),
        ]);
    }

    public function health(Request $request): JsonResponse
    {
        $kecamatanId = $request->integer('kecamatan_id') ?: null;

        return ApiResponse::success([
            'statistics' => $this->health->statistics($kecamatanId),
            'workforce_per_kecamatan' => $this->health->workforcePerKecamatan($kecamatanId),
            'facility_proportion' => $this->health->facilityProportion($kecamatanId),
            'capacity_per_kecamatan' => $this->health->capacityPerKecamatan($kecamatanId),
            'table' => $this->health->table($kecamatanId)->toArray(),
        ]);
    }
}
