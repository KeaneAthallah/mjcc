<?php

namespace App\Services;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates the overview ("Beranda") dashboard data from the database.
 *
 * The heavy aggregate queries are cached briefly so repeated dashboard loads
 * do not re-run every count/sum against the database.
 */
class DashboardService
{
    /**
     * How long overview aggregates remain cached (seconds).
     */
    private const CACHE_TTL = 60;

    /**
     * @return array<string, mixed>
     */
    private function cached(string $key, callable $callback): array
    {
        return $this->remember($key, $callback);
    }

    private function cachedCollection(string $key, callable $callback): Collection
    {
        return $this->remember($key, $callback);
    }

    /**
     * Cached lookup that is resilient to corrupt serialized values.
     *
     * The database (and file) cache stores Eloquent instances verbatim. Those
     * can unserialise as `__PHP_Incomplete_Class` when the serialized class
     * definition is not loaded yet (e.g. a `Collection`) or after model/code
     * changes. Such values are treated as a miss and rebuilt, and the relevant
     * collection classes are force-loaded before reading so unserialize can
     * complete.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function remember(string $key, callable $callback): mixed
    {
        // Loading these before `Cache::get` lets the cached `Collection`
        // instances unserialise instead of degrading to `__PHP_Incomplete_Class`.
        \Illuminate\Database\Eloquent\Collection::class;
        Collection::class;
        LengthAwarePaginator::class;

        $cacheKey = 'dashboard.'.$key;
        $value = Cache::get($cacheKey);

        if (! $this->isUsable($value)) {
            $value = $callback();
            Cache::put($cacheKey, $value, self::CACHE_TTL);
        }

        return $value;
    }

    private function isUsable(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_a($value, '__PHP_Incomplete_Class')) {
            return false;
        }

        return true;
    }

    /**
     * Kabupaten-level overview statistics.
     *
     * @return array<string, int>
     */
    public function overviewStats(): array
    {
        return $this->cached('overview', function () {
            return [
                'kecamatan' => Kecamatan::count(),
                'kelurahan' => Kelurahan::count(),
                'population' => Kelurahan::sum('population'),
                'total_sekolah' => School::count(),
                'total_sd' => School::where('school_type', School::TYPE_SD)->count(),
                'total_smp' => School::where('school_type', School::TYPE_SMP)->count(),
                'total_siswa' => School::sum(DB::raw('students_male + students_female')),
                'total_guru' => School::sum('teachers'),
                'total_polsek' => Polsek::count(),
                'total_tipkamtikmas' => Tipkamtikmas::count(),
                'total_poskamling' => Poskamling::count(),
                'total_pasar' => Market::count(),
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
        });
    }

    /**
     * Per-sektor comparison per kecamatan for the overview bar chart.
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    public function comparisonChart(): array
    {
        $kecamatans = $this->kecamatansWithCounts();

        return $this->cached('comparison', function () use ($kecamatans) {
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
        });
    }

    /**
     * Infrastructure composition for the polar chart.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function infraComposition(): array
    {
        return $this->cached('infra', function () {
            return [
                'labels' => ['Pendidikan', 'Ketertiban', 'Kesehatan'],
                'data' => [
                    School::count(),
                    Polsek::count() + Poskamling::count() + Market::count(),
                    HealthFacility::count(),
                ],
            ];
        });
    }

    /**
     * Student trend per kecamatan (male/female) for the stacked bar chart.
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    public function studentChart(): array
    {
        return $this->cached('students', function () {
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
        });
    }

    /**
     * Health workforce proportions for the doughnut chart.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function healthWorkforceChart(): array
    {
        return $this->cached('workforce', function () {
            return [
                'labels' => ['Dokter', 'Perawat', 'Bidan'],
                'data' => [
                    HealthFacility::sum('doctors'),
                    HealthFacility::sum('nurses'),
                    HealthFacility::sum('midwives'),
                ],
            ];
        });
    }

    /**
     * Top kecamatan by number of schools (rankings list).
     */
    public function topSekolah(): Collection
    {
        return $this->cachedCollection('top.sekolah', function () {
            return Kecamatan::withCount('schools')
                ->orderByDesc('schools_count')
                ->limit(5)
                ->get(['id', 'name', 'schools_count']);
        });
    }

    /**
     * Top kecamatan by active poskamling (rankings list).
     */
    public function topPoskamling(): Collection
    {
        return $this->cachedCollection('top.poskamling', function () {
            return Kecamatan::withCount(['poskamlings as active_poskamling' => function ($query) {
                $query->where('is_active', true);
            }])
                ->orderByDesc('active_poskamling')
                ->limit(5)
                ->get(['id', 'name', 'active_poskamling']);
        });
    }

    /**
     * Top kecamatan by health workforce (rankings list).
     */
    public function topKesehatan(): Collection
    {
        return $this->cachedCollection('top.kesehatan', function () {
            return Kecamatan::withSum('healthFacilities as workers', DB::raw('doctors + nurses + midwives'))
                ->orderByDesc('workers')
                ->limit(5)
                ->get(['id', 'name', 'workers']);
        });
    }

    /**
     * All kecamatan with entity counts, used for charts and tables.
     */
    public function kecamatansWithCounts(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->cachedCollection('counts', function () {
            return Kecamatan::withCount([
                'schools',
                'tipkamtikmas',
                'healthFacilities',
                'kelurahans',
                'poskamlings',
                'markets',
            ])->orderBy('name')->get();
        });
    }
}
