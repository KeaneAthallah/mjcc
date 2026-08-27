<?php

namespace App\Services;

use App\Models\Kecamatan;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aggregates the Pendidikan dashboard data from the database, optionally
 * scoped to a single kecamatan.
 */
class EducationDashboardService
{
    /**
     * Pendidikan statistics for the given kecamatan (or all kecamatan).
     *
     * @return array<string, int>
     */
    public function statistics(?int $kecamatanId = null): array
    {
        $query = School::query();

        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        $stats = (clone $query)->with('subjects');

        return [
            'total_sd' => (clone $query)->where('school_type', School::TYPE_SD)->count(),
            'total_smp' => (clone $query)->where('school_type', School::TYPE_SMP)->count(),
            'siswa_laki' => (clone $query)->sum('students_male'),
            'siswa_perempuan' => (clone $query)->sum('students_female'),
            'guru' => (clone $query)->sum('teachers'),
            'kelas' => (clone $query)->sum('classes'),
            'mapel' => $this->subjectCount($query),
        ];
    }

    /**
     * Siswa per kecamatan (bar chart: male/female).
     *
     * @param int|null $kecamatanId
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    public function studentPerKecamatan(?int $kecamatanId = null): array
    {
        $kecamatans = Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withSum('schools as male', 'students_male')
            ->withSum('schools as female', 'students_female')
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Siswa Laki-laki',
                    'data' => $kecamatans->pluck('male')->toArray(),
                    'backgroundColor' => 'rgba(16,185,129,0.8)',
                    'borderColor' => 'rgba(16,185,129,1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Siswa Perempuan',
                    'data' => $kecamatans->pluck('female')->toArray(),
                    'backgroundColor' => 'rgba(59,130,246,0.8)',
                    'borderColor' => 'rgba(59,130,246,1)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * Rasio siswa per guru (doughnut by guru count per kecamatan).
     *
     * @return array{labels: string[], data: int[]}
     */
    public function teacherRatio(?int $kecamatanId = null): array
    {
        $kecamatans = Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withSum('schools as guru', 'teachers')
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'data' => $kecamatans->pluck('guru')->toArray(),
        ];
    }

    /**
     * Per-kecamatan pendidikan summary table.
     *
     * @return Collection<int, Kecamatan>
     */
    public function table(?int $kecamatanId = null): Collection
    {
        return Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withCount([
                'schools as sd_count' => fn ($q) => $q->where('school_type', School::TYPE_SD),
                'schools as smp_count' => fn ($q) => $q->where('school_type', School::TYPE_SMP),
            ])
            ->withSum('schools as siswa_l', 'students_male')
            ->withSum('schools as siswa_p', 'students_female')
            ->withSum('schools as guru', 'teachers')
            ->withSum('schools as kelas', 'classes')
            ->withSum('schools as kapasitas', 'capacity')
            ->orderBy('name')
            ->get();
    }

    /**
     * Facility progress percentages.
     *
     * @return array<int, array{name: string, pct: int, meta: string}>
     */
    public function facilityProgress(?int $kecamatanId = null, int $limit = 6): array
    {
        $query = School::query();
        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        $base = [
            'Perpustakaan' => 'library_percentage',
            'Laboratorium IPA' => 'science_lab_percentage',
            'Laboratorium Komputer' => 'computer_lab_percentage',
            'Ruang Guru' => 'teacher_room_percentage',
            'WC / Toilet' => 'toilet_percentage',
            'Ruang Ibadah' => 'worship_room_percentage',
        ];

        $result = [];
        foreach ($base as $label => $column) {
            $avg = (clone $query)->avg($column) ?? 0;
            $result[] = [
                'name' => $label,
                'pct' => (int) round($avg),
                'meta' => $column,
            ];
        }

        return array_slice($result, 0, $limit);
    }

    /**
     * Schools for the pendidikan map (points with coordinates).
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function map(?int $kecamatanId = null): \Illuminate\Support\Collection
    {
        return School::query()
            ->with('kecamatan:id,name')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function (School $school) {
                return [
                    'id' => $school->id,
                    'name' => $school->name,
                    'type' => $school->school_type,
                    'latitude' => (float) $school->latitude,
                    'longitude' => (float) $school->longitude,
                    'kecamatan' => $school->kecamatan?->name,
                    'condition' => $school->condition,
                    'students' => (int) $school->students_male + (int) $school->students_female,
                    'teachers' => (int) $school->teachers,
                    'classes' => (int) $school->classes,
                    'capacity' => (int) $school->capacity,
                ];
            })
            ->values();
    }

    private function subjectCount($schoolQuery): int
    {
        $subjectQuery = Subject::query()->where('is_active', true);
        $count = $subjectQuery->count();

        if (! $count) {
            return $count;
        }

        return $subjectQuery->count();
    }
}
