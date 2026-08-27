<?php

namespace App\Services;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\Tipkamtikmas;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aggregates the Ketertiban dashboard data, optionally scoped to a kecamatan.
 */
class SecurityDashboardService
{
    /**
     * Ketertiban statistics for the given kecamatan (or all kecamatan).
     *
     * @return array<string, int>
     */
    public function statistics(?int $kecamatanId = null): array
    {
        $kelurahan = Kelurahan::query();
        $polsek = Polsek::query();
        $tipkamtikmas = Tipkamtikmas::query();
        $poskamling = Poskamling::query();
        $pasar = Market::query();

        if ($kecamatanId) {
            $kelurahan->where('kecamatan_id', $kecamatanId);
            $polsek->where('kecamatan_id', $kecamatanId);
            $tipkamtikmas->where('kecamatan_id', $kecamatanId);
            $poskamling->where('kecamatan_id', $kecamatanId);
            $pasar->where('kecamatan_id', $kecamatanId);
        }

        return [
            'kelurahan' => $kelurahan->count(),
            'polsek' => $polsek->count(),
            'tipkamtikmas' => $tipkamtikmas->count(),
            'poskamling' => $poskamling->where('is_active', true)->count(),
            'pasar' => $pasar->count(),
        ];
    }

    /**
     * Tipkamtikmas & Poskamling bar chart per kecamatan.
     *
     * @return array{labels: string[], datasets: array<int, array<string, mixed>>}
     */
    public function compareChart(?int $kecamatanId = null): array
    {
        $kecamatans = Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withCount('tipkamtikmas')
            ->withCount(['poskamlings as poskamling_count' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Tipkamtikmas',
                    'data' => $kecamatans->pluck('tipkamtikmas_count')->toArray(),
                    'backgroundColor' => 'rgba(16,185,129,0.8)',
                    'borderColor' => 'rgba(16,185,129,1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Poskamling Aktif',
                    'data' => $kecamatans->pluck('poskamling_count')->toArray(),
                    'backgroundColor' => 'rgba(59,130,246,0.8)',
                    'borderColor' => 'rgba(59,130,246,1)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    /**
     * Distribution pie chart for active poskamling.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function poskamlingDistribution(?int $kecamatanId = null): array
    {
        $kecamatans = Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withCount(['poskamlings as n' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'data' => $kecamatans->pluck('n')->toArray(),
        ];
    }

    /**
     * Kelurahan per kecamatan (horizontal bar).
     *
     * @return array{labels: string[], data: int[]}
     */
    public function kelurahanPerKecamatan(?int $kecamatanId = null): array
    {
        $kecamatans = Kecamatan::query()
            ->when($kecamatanId, fn ($q) => $q->whereKey($kecamatanId))
            ->withCount('kelurahans')
            ->orderBy('name')
            ->get();

        return [
            'labels' => $kecamatans->pluck('name')->toArray(),
            'data' => $kecamatans->pluck('kelurahans_count')->toArray(),
        ];
    }

    /**
     * Polsek table.
     *
     * @return Collection<int, Polsek>
     */
    public function polsekTable(?int $kecamatanId = null): Collection
    {
        return Polsek::query()
            ->with('kecamatan:id,name')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->orderBy('name')
            ->get();
    }

    /**
     * Data for the security map (polsek, kelurahan, pasar with coordinates).
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    public function map(?int $kecamatanId = null): array
    {
        $kecQuery = fn ($q) => $kecamatanId ? $q->where('kecamatan_id', $kecamatanId) : $q;

        $polsek = Polsek::with('kecamatan:id,name')->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->map(fn (Polsek $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => 'polsek',
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'kecamatan' => $p->kecamatan?->name,
                'personnel' => (int) $p->personnel_count,
                'poskamling' => (int) $p->poskamling_count,
            ])
            ->values();

        $kelurahan = Kelurahan::with('kecamatan:id,name')->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->map(fn (Kelurahan $k) => [
                'id' => $k->id,
                'name' => $k->name,
                'type' => 'kelurahan',
                'latitude' => (float) $k->latitude,
                'longitude' => (float) $k->longitude,
                'kecamatan' => $k->kecamatan?->name,
                'population' => (int) $k->population,
            ])
            ->values();

        $pasar = Market::with('kecamatan:id,name')->whereNotNull('latitude')->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->map(fn (Market $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'type' => 'pasar',
                'latitude' => (float) $m->latitude,
                'longitude' => (float) $m->longitude,
                'kecamatan' => $m->kecamatan?->name,
            ])
            ->values();

        return compact('polsek', 'kelurahan', 'pasar');
    }
}
