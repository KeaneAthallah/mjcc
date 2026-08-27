<?php

namespace App\Services;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;

/**
 * Aggregates the overview ("Beranda") dashboard data from the database.
 */
class DashboardService
{
    /**
     * Kabupaten-level overview statistics.
     *
     * @return array<string, int>
     */
    public function overviewStats(): array
    {
        return [
            'kecamatan' => Kecamatan::count(),
            'kelurahan' => Kelurahan::count(),
            'population' => Kelurahan::sum('population'),
            'total_sekolah' => School::count(),
            'total_sd' => School::where('school_type', School::TYPE_SD)->count(),
            'total_smp' => School::where('school_type', School::TYPE_SMP)->count(),
            'total_siswa' => School::sum(\Illuminate\Support\Facades\DB::raw('students_male + students_female')),
            'total_guru' => School::sum('teachers'),
            'total_polsek' => Polsek::count(),
            'total_tipkamtikmas' => Tipkamtikmas::count(),
            'total_poskamling' => Poskamling::count(),
            'total_pasar' => \App\Models\Market::count(),
            'total_faskes' => HealthFacility::count(),
            'total_puskesmas' => HealthFacility::where('facility_type', HealthFacility::TYPE_PUSKESMAS)->count(),
            'total_rs' => HealthFacility::where('facility_type', HealthFacility::TYPE_RS)->count(),
            'total_pustu' => HealthFacility::where('facility_type', HealthFacility::TYPE_PUSTU)->count(),
            'total_posyandu' => HealthFacility::where('facility_type', HealthFacility::TYPE_POSYANDU)->count(),
            'total_dokter' => HealthFacility::sum('doctors'),
            'total_perawat' => HealthFacility::sum('nurses'),
            'total_bidan' => HealthFacility::sum('midwives'),
            'total_bed' => HealthFacility::sum('beds'),
        ];
    }

    /**
     * Per-sektor comparison per kecamatan for the overview bar chart.
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    public function comparisonChart(): array
    {
        $kecamatans = $this->kecamatansWithCounts();

        return [
            'labels' => $kecamatans->pluck('name')->values()->toArray(),
            'datasets' => [
                [
                    'label' => 'Sekolah',
                    'data' => $kecamatans
                        ->map(fn ($k) => $k->schools_count)
                        ->values()
                        ->toArray(),
                    'backgroundColor' => 'rgba(16,185,129,0.8)',
                    'borderColor' => 'rgba(16,185,129,1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Tipkamtikmas',
                    'data' => $kecamatans
                        ->map(fn ($k) => $k->tipkamtikmas_count)
                        ->values()
                        ->toArray(),
                    'backgroundColor' => 'rgba(59,130,246,0.8)',
                    'borderColor' => 'rgba(59,130,246,1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Fasilitas Kesehatan',
                    'data' => $kecamatans
                        ->map(fn ($k) => $k->health_facilities_count)
                        ->values()
                        ->toArray(),
                    'backgroundColor' => 'rgba(110,231,183,0.7)',
                    'borderColor' => 'rgba(110,231,183,1)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * Infrastructure composition for the polar chart.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function infraComposition(): array
    {
        return [
            'labels' => ['Pendidikan', 'Ketertiban', 'Kesehatan'],
            'data' => [
                School::count(),
                Polsek::count() + Poskamling::count() + \App\Models\Market::count(),
                HealthFacility::count(),
            ],
        ];
    }

    /**
     * Student trend per kecamatan (male/female) for the stacked bar chart.
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    public function studentChart(): array
    {
        $kecamatans = Kecamatan::withSum('schools as male', 'students_male')
            ->withSum('schools as female', 'students_female')
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Laki-laki',
                    'data' => $kecamatans->pluck('male')->toArray(),
                    'backgroundColor' => 'rgba(16,185,129,0.8)',
                ],
                [
                    'label' => 'Perempuan',
                    'data' => $kecamatans->pluck('female')->toArray(),
                    'backgroundColor' => 'rgba(59,130,246,0.8)',
                ],
            ],
        ];
    }

    /**
     * Health workforce proportions for the doughnut chart.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function healthWorkforceChart(): array
    {
        return [
            'labels' => ['Dokter', 'Perawat', 'Bidan'],
            'data' => [
                HealthFacility::sum('doctors'),
                HealthFacility::sum('nurses'),
                HealthFacility::sum('midwives'),
            ],
        ];
    }

    /**
     * Top kecamatan by number of schools (rankings list).
     *
     * @return \Illuminate\Support\Collection
     */
    public function topSekolah(): \Illuminate\Support\Collection
    {
        return Kecamatan::withCount('schools')
            ->orderByDesc('schools_count')
            ->limit(5)
            ->get(['id', 'name', 'schools_count']);
    }

    /**
     * Top kecamatan by active poskamling (rankings list).
     *
     * @return \Illuminate\Support\Collection
     */
    public function topPoskamling(): \Illuminate\Support\Collection
    {
        return Kecamatan::withCount(['poskamlings as active_poskamling' => function ($query) {
            $query->where('is_active', true);
        }])
            ->orderByDesc('active_poskamling')
            ->limit(5)
            ->get(['id', 'name', 'active_poskamling']);
    }

    /**
     * Top kecamatan by health workforce (rankings list).
     *
     * @return \Illuminate\Support\Collection
     */
    public function topKesehatan(): \Illuminate\Support\Collection
    {
        return Kecamatan::withSum('healthFacilities as workers', \Illuminate\Support\Facades\DB::raw('doctors + nurses + midwives'))
            ->orderByDesc('workers')
            ->limit(5)
            ->get(['id', 'name', 'workers']);
    }

    /**
     * All kecamatan with entity counts, used for charts and tables.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function kecamatansWithCounts(): \Illuminate\Database\Eloquent\Collection
    {
        return Kecamatan::withCount([
            'schools',
            'tipkamtikmas',
            'healthFacilities',
            'kelurahans',
            'poskamlings',
            'markets',
        ])->orderBy('name')->get();
    }
}
