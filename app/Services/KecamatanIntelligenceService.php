<?php

namespace App\Services;

use App\Models\CommandAlert;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Intelijen per kecamatan: menggabungkan skor status (CommandCenterStatusService)
 * dengan agregasi operasional setempat menjadi satu baris "peta kecamatan".
 *
 * Digunakan untuk halaman Rangking Kecamatan (bird's-eye) dan profil
 * kecamatan (worm's-eye). Hanya menghitung dari data nyata — tidak ada
 * nilai yang dikarang.
 */
class KecamatanIntelligenceService
{
    public function __construct(
        private readonly CommandCenterStatusService $status,
        private readonly CommandAlertSyncService $alerts,
    ) {}

    /**
     * Baris intelijen untuk seluruh kecamatan, diurutkan skor tertinggi.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function all(bool $force = false): Collection
    {
        return Kecamatan::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Kecamatan $kecamatan) => $this->forKecamatan($kecamatan, $force))
            ->sortByDesc(fn (array $row) => $row['status']['score'] ?? -1)
            ->values();
    }

    /**
     * Baris intelijen untuk satu kecamatan.
     *
     * @return array<string, mixed>
     */
    public function forKecamatan(Kecamatan|int $kecamatan, bool $force = false): array
    {
        $kecamatan = $kecamatan instanceof Kecamatan
            ? $kecamatan
            : Kecamatan::query()->findOrFail($kecamatan);

        $this->alerts->sync();

        $counts = $this->countsFor($kecamatan->id);

        $openAlerts = CommandAlert::query()
            ->with('kecamatan:id,name')
            ->where('kecamatan_id', $kecamatan->id)
            ->whereIn('status', CommandAlert::openStatuses())
            ->latest('opened_at')
            ->get();

        return [
            'kecamatan' => [
                'id' => $kecamatan->id,
                'name' => $kecamatan->name,
                'kelurahan' => Kelurahan::where('kecamatan_id', $kecamatan->id)->count(),
                'population' => Kelurahan::where('kecamatan_id', $kecamatan->id)->sum('population'),
            ],
            'status' => $this->status->forKecamatan($kecamatan->id, $force),
            'counts' => $counts,
            'open_alerts' => $openAlerts,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function countsFor(int $kecamatanId): array
    {
        return [
            'sekolah' => School::where('kecamatan_id', $kecamatanId)->count(),
            'sekolah_baik' => School::where('kecamatan_id', $kecamatanId)
                ->whereIn('condition', config('command-center.good_conditions', ['baik']))
                ->count(),
            'siswa' => (int) School::where('kecamatan_id', $kecamatanId)
                ->sum(DB::raw('students_male + students_female')),
            'guru' => (int) School::where('kecamatan_id', $kecamatanId)->sum('teachers'),
            'faskes' => HealthFacility::where('kecamatan_id', $kecamatanId)->count(),
            'faskes_aktif' => HealthFacility::where('kecamatan_id', $kecamatanId)->where('status', 'aktif')->count(),
            'dokter' => (int) HealthFacility::where('kecamatan_id', $kecamatanId)->sum('doctors'),
            'perawat' => (int) HealthFacility::where('kecamatan_id', $kecamatanId)->sum('nurses'),
            'bidan' => (int) HealthFacility::where('kecamatan_id', $kecamatanId)->sum('midwives'),
            'polsek' => Polsek::where('kecamatan_id', $kecamatanId)->count(),
            'poskamling' => Poskamling::where('kecamatan_id', $kecamatanId)->count(),
            'poskamling_aktif' => Poskamling::where('kecamatan_id', $kecamatanId)->where('is_active', true)->count(),
            'tipkamtikmas' => Tipkamtikmas::where('kecamatan_id', $kecamatanId)->count(),
            'tipkamtikmas_aktif' => Tipkamtikmas::where('kecamatan_id', $kecamatanId)->where('status', 'aktif')->count(),
            'pasar' => Market::where('kecamatan_id', $kecamatanId)->count(),
            'pasar_aktif' => Market::where('kecamatan_id', $kecamatanId)->where('status', 'aktif')->count(),
        ];
    }
}
