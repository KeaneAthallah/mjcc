<?php

namespace App\Services;

use App\Models\HealthFacility;
use App\Models\Market;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;

/**
 * Aggregates operational "needs attention" items across all sectors for the
 * command-center dashboard. Alerts are grouped by severity so leadership can
 * see at a glance where intervention is needed.
 */
class AlertService
{
    /**
     * Conditions considered acceptable for a building in good shape.
     */
    private const GOOD_CONDITIONS = ['baik', 'bagus', 'layak', 'rusak ringan'];

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public function alerts(): array
    {
        return [
            'critical' => array_merge($this->criticalHealth(), $this->criticalSecurity()),
            'warning' => array_merge($this->warningSchools(), $this->warningHealth(), $this->warningSecurity()),
            'info' => $this->infoAlerts(),
        ];
    }

    /**
     * Alerts scoped to a single sector key ('pendidikan' | 'kesehatan' | 'ketertiban').
     *
     * @return array<int, array<string, string>>
     */
    public function forSector(string $sector): array
    {
        return collect(array_merge($this->alerts()['critical'], $this->alerts()['warning']))
            ->where('sector_key', $sector)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{severity: string, sector: string, sector_key: string, title: string, detail: string}>
     */
    private function criticalHealth(): array
    {
        $facilities = HealthFacility::query()
            ->where('doctors', 0)
            ->where('nurses', 0)
            ->where('midwives', 0)
            ->limit(5)
            ->get(['id', 'name']);

        return $facilities->map(fn ($f) => [
            'severity' => 'critical',
            'sector' => 'Kesehatan',
            'sector_key' => 'kesehatan',
            'title' => 'Faskes tanpa tenaga medis',
            'detail' => $f->name.' tidak memiliki dokter, perawat, maupun bidan.',
        ])->values()->all();
    }

    /**
     * @return array<int, array{severity: string, sector: string, sector_key: string, title: string, detail: string}>
     */
    private function criticalSecurity(): array
    {
        return Poskamling::query()
            ->where('is_active', false)
            ->limit(5)
            ->get(['id', 'name'])
            ->map(fn ($p) => [
                'severity' => 'critical',
                'sector' => 'Ketertiban',
                'sector_key' => 'ketertiban',
                'title' => 'Pos Kamling nonaktif',
                'detail' => 'Pos kamling '.$p->name.' dilaporkan tidak aktif.',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{severity: string, sector: string, sector_key: string, title: string, detail: string}>
     */
    private function warningSchools(): array
    {
        $alerts = collect();

        School::query()
            ->select('id', 'name', 'condition')
            ->limit(100)
            ->get()
            ->each(function ($school) use ($alerts) {
                $normalized = strtolower(trim((string) $school->condition));

                if ($normalized === '' || ! in_array($normalized, self::GOOD_CONDITIONS, true)) {
                    $alerts->push([
                        'severity' => 'warning',
                        'sector' => 'Pendidikan',
                        'sector_key' => 'pendidikan',
                        'title' => 'Kondisi gedung perlu perhatian',
                        'detail' => $school->name.' memiliki kondisi "'.($school->condition ?: 'belum diisi').'".',
                    ]);
                }
            });

        return $alerts->take(5)->values()->all();
    }

    /**
     * @return array<int, array{severity: string, sector: string, sector_key: string, title: string, detail: string}>
     */
    private function warningHealth(): array
    {
        $facilities = HealthFacility::query()
            ->where('status', '!=', 'aktif')
            ->limit(5)
            ->get(['id', 'name', 'status']);

        return $facilities->map(fn ($f) => [
            'severity' => 'warning',
            'sector' => 'Kesehatan',
            'sector_key' => 'kesehatan',
            'title' => 'Status faskes tidak aktif',
            'detail' => $f->name.' berstatus "'.($f->status ?: 'belum diisi').'".',
        ])->values()->all();
    }

    /**
     * @return array<int, array{severity: string, sector: string, sector_key: string, title: string, detail: string}>
     */
    private function warningSecurity(): array
    {
        $items = collect();

        Tipkamtikmas::query()
            ->where('status', '!=', 'aktif')
            ->limit(5)
            ->get(['id', 'title', 'status'])
            ->each(function ($t) use ($items) {
                $items->push([
                    'severity' => 'warning',
                    'sector' => 'Ketertiban',
                    'sector_key' => 'ketertiban',
                    'title' => 'Tipkamtikmas perlu tindak lanjut',
                    'detail' => $t->title.' berstatus "'.($t->status ?: 'belum diisi').'".',
                ]);
            });

        Market::query()
            ->where('status', '!=', 'aktif')
            ->limit(5)
            ->get(['id', 'name', 'status'])
            ->each(function ($m) use ($items) {
                $items->push([
                    'severity' => 'warning',
                    'sector' => 'Ketertiban',
                    'sector_key' => 'ketertiban',
                    'title' => 'Pasar tidak aktif',
                    'detail' => 'Pasar '.$m->name.' berstatus "'.($m->status ?: 'belum diisi').'".',
                ]);
            });

        return $items->take(5)->values()->all();
    }

    /**
     * @return array<int, array{severity: string, sector: string, title: string, detail: string}>
     */
    private function infoAlerts(): array
    {
        return [
            [
                'severity' => 'info',
                'sector' => 'Sistem',
                'title' => 'Pembaruan data',
                'detail' => 'Pastikan data lapangan diperbarui secara berkala agar peta dan statistik tetap akurat.',
            ],
        ];
    }
}
