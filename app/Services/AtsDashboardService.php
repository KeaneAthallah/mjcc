<?php

namespace App\Services;

use App\Models\ExternalData;
use App\Models\Kecamatan;
use App\Models\PublicDataSource;
use Illuminate\Support\Collection;

/**
 * Aggregates the "Anak Tidak Sekolah" (ATS) executive widgets for the
 * Pendidikan dashboard from the normalized Verval ATS dataset stored in
 * `external_data` (source_key = ats). Optionally scoped to one kecamatan.
 */
class AtsDashboardService
{
    /**
     * @var array<string, array<string, float>>|null indicator index keyed
     *                                               by location then indicator name.
     */
    private ?array $index = null;

    public function dashboard(?int $kecamatanId = null): array
    {
        $rows = ExternalData::where('source_key', 'ats')->get(['location', 'indicator', 'value']);

        $this->index = [];

        foreach ($rows as $row) {
            $this->index[$row->location][$row->indicator] = (float) $row->value;
        }

        $kecamatans = Kecamatan::orderBy('name')->get(['id', 'name', 'latitude', 'longitude']);
        $scope = $kecamatanId ? $kecamatans->firstWhere('id', $kecamatanId) : null;
        $scopeName = $scope?->name ?? (string) config('public_data.ats.region_name', 'Kabupaten Morowali');

        $indicator = fn (string $name): float => (float) ($this->index[$scopeName][$name] ?? 0);

        $total = $indicator('Anak Tidak Sekolah');
        $do = $indicator('Anak Tidak Sekolah - DO');
        $ltm = $indicator('Anak Tidak Sekolah - LTM');
        $bpb = $indicator('Anak Tidak Sekolah - BPB');
        $recovery = $indicator('Anak Tidak Sekolah - Kembali Sekolah');
        $verified = $indicator('Anak Tidak Sekolah - Verifikasi Sudah');
        $unverified = $indicator('Anak Tidak Sekolah - Verifikasi Belum');
        $verificationBase = $verified + $unverified;

        $reasons = $this->collectReasons($scopeName);
        $kecamatanData = $this->kecamatanData($kecamatans, $scope?->id);
        $rankings = $this->rankings($kecamatanData);

        return [
            'scope' => $scopeName,
            'is_region' => $scope === null,
            'source_label' => 'ATS Kemendikdasmen',
            'as_of' => $this->lastSyncedAt(),
            'kpi' => [
                'total' => $total,
                'do' => $do,
                'ltm' => $ltm,
                'bpb' => $bpb,
                'verified' => $verified,
                'unverified' => $unverified,
                'verified_pct' => $this->percent($verified, $verificationBase),
                'recovery' => $recovery,
                'recovery_pct' => $this->percent($recovery, $total),
            ],
            'composition' => [
                'labels' => ['DO', 'LTM', 'BPB'],
                'data' => [$do, $ltm, $bpb],
            ],
            'verification' => [
                'labels' => ['Terverifikasi', 'Belum Verifikasi'],
                'data' => [$verified, $unverified],
                'pct' => $this->percent($verified, $verificationBase),
                'total' => $verificationBase,
            ],
            'recovery' => [
                'labels' => ['Kembali Sekolah', 'Belum Kembali'],
                'data' => [$recovery, max(0, $total - $recovery)],
                'pct' => $this->percent($recovery, $total),
                'total' => $recovery,
            ],
            'doJenjang' => [
                'labels' => ['PAUD', 'SD', 'SMP', 'SMA/SMK'],
                'data' => [
                    $indicator('Anak Tidak Sekolah - Kembali Sekolah DO Jenjang PAUD'),
                    $indicator('Anak Tidak Sekolah - Kembali Sekolah DO Jenjang Dasar (SD)'),
                    $indicator('Anak Tidak Sekolah - Kembali Sekolah DO Jenjang Pertama (SMP)'),
                    $indicator('Anak Tidak Sekolah - Kembali Sekolah DO Jenjang Atas (SMA/SMK)'),
                ],
            ],
            'doTingkat' => [
                'labels' => range(1, 13),
                'data' => array_map(fn (int $tingkat): int => (int) $indicator('Anak Tidak Sekolah - Kembali Sekolah DO Tingkat '.$tingkat), range(1, 13)),
            ],
            'reasons' => $reasons,
            'map' => $kecamatanData,
            'rankings' => $rankings,
            'insights' => $this->insights($kecamatanData, ['do' => $do, 'ltm' => $ltm, 'bpb' => $bpb, 'recovery' => $recovery]),
        ];
    }

    /**
     * @param  Collection<int, Kecamatan>  $kecamatans
     * @return Collection<int, array<string, mixed>>
     */
    private function kecamatanData(Collection $kecamatans, ?int $selectedId = null): Collection
    {
        return $kecamatans->map(fn (Kecamatan $kecamatan): array => [
            'name' => $kecamatan->name,
            'latitude' => (float) $kecamatan->latitude,
            'longitude' => (float) $kecamatan->longitude,
            'selected' => $selectedId !== null && (int) $kecamatan->id === $selectedId,
            'total' => $this->value($kecamatan->name, 'Anak Tidak Sekolah'),
            'do' => $this->value($kecamatan->name, 'Anak Tidak Sekolah - DO'),
            'ltm' => $this->value($kecamatan->name, 'Anak Tidak Sekolah - LTM'),
            'bpb' => $this->value($kecamatan->name, 'Anak Tidak Sekolah - BPB'),
            'recovery' => $this->value($kecamatan->name, 'Anak Tidak Sekolah - Kembali Sekolah'),
            'verified' => $this->value($kecamatan->name, 'Anak Tidak Sekolah - Verifikasi Sudah'),
            'unverified' => $this->value($kecamatan->name, 'Anak Tidak Sekolah - Verifikasi Belum'),
            'verified_pct' => $this->percent(
                $this->value($kecamatan->name, 'Anak Tidak Sekolah - Verifikasi Sudah'),
                $this->value($kecamatan->name, 'Anak Tidak Sekolah - Verifikasi Sudah') + $this->value($kecamatan->name, 'Anak Tidak Sekolah - Verifikasi Belum')
            ),
            'recovery_pct' => $this->percent(
                $this->value($kecamatan->name, 'Anak Tidak Sekolah - Kembali Sekolah'),
                $this->value($kecamatan->name, 'Anak Tidak Sekolah')
            ),
        ])->values();
    }

    /**
     * @return array{labels: array<int, string>, data: array<int, int>, total: int}
     */
    private function collectReasons(string $location): array
    {
        $columns = ['k_1', 'k_2', 'k_3', 'k_4', 'k_5', 'k_6', 'k_7', 'k_8', 'k_9', 'k_10', 'k_11', 'k_12', 'k_13', 'k_15', 'k_16', 'k_17', 'k_18', 'k_19', 'k_21', 'k_22', 'k_23', 'k_24', 'k_25'];

        $pairs = array_map(
            fn (string $code): array => [
                'label' => $code,
                'value' => (int) $this->value($location, 'Anak Tidak Sekolah - Verifikasi Alasan '.$code),
            ],
            $columns
        );

        usort($pairs, fn (array $a, array $b): int => $b['value'] <=> $a['value']);

        return [
            'labels' => array_map(fn (array $p): string => $p['label'], $pairs),
            'data' => array_map(fn (array $p): int => $p['value'], $pairs),
            'total' => array_sum(array_map(fn (array $p): int => $p['value'], $pairs)),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $kecamatanData
     * @return array{top: array<int, array{name: string, total: int, verified_pct: int, recovery_pct: int}>, bottom: array<int, array{name: string, total: int, verified_pct: int, recovery_pct: int}>}
     */
    private function rankings(Collection $kecamatanData): array
    {
        $sorted = $kecamatanData
            ->sortByDesc('total')
            ->values()
            ->map(fn (array $row): array => [
                'name' => $row['name'],
                'total' => (int) $row['total'],
                'verified_pct' => $row['verified_pct'],
                'recovery_pct' => $row['recovery_pct'],
            ])
            ->values();

        return [
            'top' => $sorted->take(5)->values()->all(),
            'bottom' => $sorted->take(-5)->values()->reject(fn (array $row): bool => $row['total'] === 0)->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $kecamatanData
     * @return array<int, array{icon: string, title: string, detail: string}>
     */
    private function insights(Collection $kecamatanData, array $region): array
    {
        $sum = $kecamatanData->sum('total');
        $insights = [];

        if ($sum > 0) {
            $top = $kecamatanData->sortByDesc('total')->first();

            if ($top) {
                $insights[] = [
                    'icon' => '📌',
                    'title' => 'Beban tertinggi di '.$top['name'],
                    'detail' => ($top['total']).' anak ('.$this->percent($top['total'], $sum).'% dari total ATS Kabupaten) memerlukan perhatian paling besar.',
                ];
            }

            if ($region['bpb'] > 0) {
                $insights[] = [
                    'icon' => '👶',
                    'title' => 'Belum pernah bersekolah',
                    'detail' => (int) $region['bpb'].' anak ('.$this->percent($region['bpb'], $sum).'%) adalah BPB — prioritas penuntasan pendidikan dasar.',
                ];
            }

            if ($region['do'] > $region['ltm']) {
                $insights[] = [
                    'icon' => '🚻',
                    'title' => 'Putus sekolah lebih dominan',
                    'detail' => 'DO berjumlah '.$this->percent($region['do'], $region['do'] + $region['ltm']).'% dari anak eks-sekolah, diikuti LTM ('.$region['ltm'].' anak).',
                ];
            }

            $lowestVerified = $kecamatanData->sortBy('verified_pct')->first();

            if ($lowestVerified && (float) $lowestVerified['total'] > 0) {
                $insights[] = [
                    'icon' => '🔍',
                    'title' => 'Verifikasi lapangan perlu dikejar',
                    'detail' => 'Tingkat verifikasi terendah di '.$lowestVerified['name'].' ('.$lowestVerified['verified_pct'].'%) — '.($lowestVerified['unverified']).' anak belum diverifikasi.',
                ];
            }
        }

        $insights[] = [
            'icon' => '🔄',
            'title' => 'Progres pemulihan',
            'detail' => (int) $region['recovery'].' anak telah kembali bersekolah, '.$this->percent($region['recovery'], $sum).'% dari total ATS.',
        ];

        return $insights;
    }

    private function value(string $location, string $indicator): float
    {
        return (float) ($this->index[$location][$indicator] ?? 0);
    }

    private function percent(float $part, float $whole): int
    {
        return $whole > 0 ? (int) round(($part / $whole) * 100) : 0;
    }

    private function lastSyncedAt(): ?string
    {
        return PublicDataSource::where('key', 'ats')->value('last_success_at');
    }
}
