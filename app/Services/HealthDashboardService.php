<?php

namespace App\Services;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aggregates the Kesehatan dashboard data, optionally scoped to a kecamatan.
 */
class HealthDashboardService
{
    /**
     * Kesehatan statistics for the given kecamatan (or all kecamatan).
     *
     * @return array<string, int>
     */
    public function statistics(?int $kecamatanId = null): array
    {
        $query = HealthFacility::query();
        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        return [
            'puskesmas' => (clone $query)->where('facility_type', HealthFacility::TYPE_PUSKESMAS)->count(),
            'pustu' => (clone $query)->where('facility_type', HealthFacility::TYPE_PUSTU)->count(),
            'rs' => (clone $query)->where('facility_type', HealthFacility::TYPE_RS)->count(),
            'posyandu' => (clone $query)->where('facility_type', HealthFacility::TYPE_POSYANDU)->count(),
            'dokter' => (clone $query)->sum('doctors'),
            'perawat' => (clone $query)->sum('nurses'),
            'bidan' => (clone $query)->sum('midwives'),
            'bed' => (clone $query)->sum('beds'),
        ];
    }

    /**
     * Tenaga kesehatan per kecamatan (bar chart).
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    public function workforcePerKecamatan(?int $kecamatanId = null): array
    {
        $kecamatans = Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withSum('healthFacilities as dok', 'doctors')
            ->withSum('healthFacilities as per', 'nurses')
            ->withSum('healthFacilities as bid', 'midwives')
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Dokter',
                    'data' => $kecamatans->pluck('dok')->toArray(),
                    'backgroundColor' => 'rgba(16,185,129,0.8)',
                    'borderColor' => 'rgba(16,185,129,1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Perawat',
                    'data' => $kecamatans->pluck('per')->toArray(),
                    'backgroundColor' => 'rgba(59,130,246,0.8)',
                    'borderColor' => 'rgba(59,130,246,1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Bidan',
                    'data' => $kecamatans->pluck('bid')->toArray(),
                    'backgroundColor' => 'rgba(167,243,208,0.8)',
                    'borderColor' => 'rgba(167,243,208,1)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * Facility type proportions (doughnut).
     *
     * @return array{labels: string[], data: int[]}
     */
    public function facilityProportion(?int $kecamatanId = null): array
    {
        $query = HealthFacility::query();
        if ($kecamatanId) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        return [
            'labels' => ['Puskesmas', 'Pustu', 'Rumah Sakit', 'Posyandu'],
            'data' => [
                (clone $query)->where('facility_type', HealthFacility::TYPE_PUSKESMAS)->count(),
                (clone $query)->where('facility_type', HealthFacility::TYPE_PUSTU)->count(),
                (clone $query)->where('facility_type', HealthFacility::TYPE_RS)->count(),
                (clone $query)->where('facility_type', HealthFacility::TYPE_POSYANDU)->count(),
            ],
        ];
    }

    /**
     * Bed capacity per kecamatan (bar chart).
     *
     * @return array{labels: string[], data: int[]}
     */
    public function capacityPerKecamatan(?int $kecamatanId = null): array
    {
        $kecamatans = Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withSum('healthFacilities as bed', 'beds')
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'data' => $kecamatans->pluck('bed')->toArray(),
        ];
    }

    /**
     * Per-kecamatan health summary table.
     *
     * @return Collection<int, Kecamatan>
     */
    public function table(?int $kecamatanId = null): Collection
    {
        return Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withCount([
                'healthFacilities as puskesmas_count' => fn ($q) => $q->where('facility_type', HealthFacility::TYPE_PUSKESMAS),
                'healthFacilities as pustu_count' => fn ($q) => $q->where('facility_type', HealthFacility::TYPE_PUSTU),
                'healthFacilities as rs_count' => fn ($q) => $q->where('facility_type', HealthFacility::TYPE_RS),
                'healthFacilities as posyandu_count' => fn ($q) => $q->where('facility_type', HealthFacility::TYPE_POSYANDU),
            ])
            ->withSum('healthFacilities as dok', 'doctors')
            ->withSum('healthFacilities as per', 'nurses')
            ->withSum('healthFacilities as bid', 'midwives')
            ->withSum('healthFacilities as bed', 'beds')
            ->orderBy('name')
            ->get();
    }

    /**
     * Health facilities for the map.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function map(?int $kecamatanId = null): \Illuminate\Support\Collection
    {
        return HealthFacility::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->map(fn (HealthFacility $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'category' => $f->facility_type,
                'latitude' => (float) $f->latitude,
                'longitude' => (float) $f->longitude,
                'kecamatan' => $f->kecamatan?->name,
                'details' => [
                    'Dokter' => (int) $f->doctors,
                    'Perawat' => (int) $f->nurses,
                    'Bidan' => (int) $f->midwives,
                    'Bed' => (int) $f->beds,
                    'Kondisi' => $f->condition,
                ],
            ])
            ->values();
    }
}
