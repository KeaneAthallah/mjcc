<?php

namespace App\Services;

use App\Models\HealthFacility;
use App\Models\Market;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Mengagregasi item "perlu perhatian" lintas sektor untuk pusat kendali.
 *
 * Alert dikelompokkan berdasarkan tingkat keparahan (critical/warning/info)
 * dan setiap alert membawa metadata operasional: sektor, resource, kecamatan,
 * koordinat, serta tautan detail dan peta — sehingga dapat dipetakan dan
 * ditindaklanjuti, bukan sekadar teks.
 */
class AlertService
{
    /**
     * Kondisi gedung yang dianggap layak/dalam kondisi baik.
     */
    private const GOOD_CONDITIONS = ['baik', 'bagus', 'layak', 'rusak ringan'];

    /**
     * Pemetaan tipe resource ke rute detail.
     */
    private const ROUTES = [
        'school' => 'education.schools.show',
        'health_facility' => 'health.facilities.show',
        'poskamling' => 'security.poskamlings.show',
        'tipkamtikmas' => 'security.tipkamtikmas.show',
        'market' => 'security.markets.show',
        'polsek' => 'security.polseks.show',
    ];

    /**
     * @return array<string, array<int, array<string, string>>>
     */
    public function alerts(): array
    {
        $all = $this->all();

        return [
            'critical' => $all->where('severity', 'critical')->values()->all(),
            'warning' => $all->where('severity', 'warning')->values()->all(),
            'info' => $all->where('severity', 'info')->values()->all(),
        ];
    }

    /**
     * Alert untuk satu sektor ('pendidikan' | 'kesehatan' | 'ketertiban').
     *
     * @return array<int, array<string, string>>
     */
    public function forSector(string $sector): array
    {
        return $this->all()
            ->where('sector_key', $sector)
            ->whereIn('severity', ['critical', 'warning'])
            ->values()
            ->all();
    }

    /**
     * Seluruh alert terdeteksi, diurutkan critical -> warning -> info.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];

        return collect()
            ->merge($this->criticalHealth())
            ->merge($this->criticalSecurity())
            ->merge($this->warningSchools())
            ->merge($this->warningHealth())
            ->merge($this->warningSecurity())
            ->merge($this->infoAlerts())
            ->sortBy(fn ($a) => $order[$a['severity']] ?? 3)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function criticalHealth(): Collection
    {
        return HealthFacility::query()
            ->where('doctors', 0)
            ->where('nurses', 0)
            ->where('midwives', 0)
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'kecamatan_id', 'latitude', 'longitude', 'created_at'])
            ->map(fn (HealthFacility $f) => $this->build(
                rule: 'faskes_tanpa_tenaga_medis',
                severity: 'critical',
                sector: 'Kesehatan',
                sectorKey: 'kesehatan',
                title: 'Faskes tanpa tenaga medis',
                detail: $f->name.' tidak memiliki dokter, perawat, maupun bidan.',
                resource: $f,
                resourceType: 'health_facility',
            ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function criticalSecurity(): Collection
    {
        return Poskamling::query()
            ->where('is_active', false)
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'kecamatan_id', 'latitude', 'longitude', 'created_at'])
            ->map(fn (Poskamling $p) => $this->build(
                rule: 'poskamling_nonaktif',
                severity: 'critical',
                sector: 'Ketertiban',
                sectorKey: 'ketertiban',
                title: 'Pos Kamling nonaktif',
                detail: 'Pos kamling '.$p->name.' dilaporkan tidak aktif.',
                resource: $p,
                resourceType: 'poskamling',
            ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function warningSchools(): Collection
    {
        return School::query()
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'condition', 'kecamatan_id', 'latitude', 'longitude', 'created_at'])
            ->filter(fn (School $school) => ! in_array(
                strtolower(trim((string) $school->condition)),
                self::GOOD_CONDITIONS,
                true,
            ))
            ->map(fn (School $school) => $this->build(
                rule: 'kondisi_gedung_sekolah',
                severity: 'warning',
                sector: 'Pendidikan',
                sectorKey: 'pendidikan',
                title: 'Kondisi gedung perlu perhatian',
                detail: $school->name.' memiliki kondisi "'.($school->condition ?: 'belum diisi').'".',
                resource: $school,
                resourceType: 'school',
            ))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function warningHealth(): Collection
    {
        return HealthFacility::query()
            ->where('status', '!=', 'aktif')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'status', 'kecamatan_id', 'latitude', 'longitude', 'created_at'])
            ->map(fn (HealthFacility $f) => $this->build(
                rule: 'faskes_nonaktif',
                severity: 'warning',
                sector: 'Kesehatan',
                sectorKey: 'kesehatan',
                title: 'Status faskes tidak aktif',
                detail: $f->name.' berstatus "'.($f->status ?: 'belum diisi').'".',
                resource: $f,
                resourceType: 'health_facility',
            ));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function warningSecurity(): Collection
    {
        $items = collect();

        Tipkamtikmas::query()
            ->where('status', '!=', 'aktif')
            ->with('kecamatan:id,name')
            ->get(['id', 'title', 'status', 'kecamatan_id', 'latitude', 'longitude', 'created_at'])
            ->each(function (Tipkamtikmas $t) use ($items) {
                $items->push($this->build(
                    rule: 'tipkamtikmas_nonaktif',
                    severity: 'warning',
                    sector: 'Ketertiban',
                    sectorKey: 'ketertiban',
                    title: 'Tipkamtikmas perlu tindak lanjut',
                    detail: $t->title.' berstatus "'.($t->status ?: 'belum diisi').'".',
                    resource: $t,
                    resourceType: 'tipkamtikmas',
                ));
            });

        Market::query()
            ->where('status', '!=', 'aktif')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'status', 'kecamatan_id', 'latitude', 'longitude', 'created_at'])
            ->each(function (Market $m) use ($items) {
                $items->push($this->build(
                    rule: 'pasar_nonaktif',
                    severity: 'warning',
                    sector: 'Ketertiban',
                    sectorKey: 'ketertiban',
                    title: 'Pasar tidak aktif',
                    detail: 'Pasar '.$m->name.' berstatus "'.($m->status ?: 'belum diisi').'".',
                    resource: $m,
                    resourceType: 'market',
                ));
            });

        return $items;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function infoAlerts(): Collection
    {
        return collect([
            $this->build(
                rule: 'pembaruan_data',
                severity: 'info',
                sector: 'Sistem',
                sectorKey: 'sistem',
                title: 'Pembaruan data',
                detail: 'Pastikan data lapangan diperbarui secara berkala agar peta dan statistik tetap akurat.',
            ),
        ]);
    }

    /**
     * Membentuk array alert yang seragam.
     *
     * @param  array<string, mixed>|null  $detailParams
     * @return array<string, mixed>
     */
    private function build(
        string $rule,
        string $severity,
        string $sector,
        string $sectorKey,
        string $title,
        string $detail,
        ?Model $resource = null,
        ?string $resourceType = null,
        ?array $detailParams = null,
    ): array {
        $route = $resource !== null && $resourceType !== null
            ? self::ROUTES[$resourceType] ?? null
            : null;

        return [
            'rule' => $rule,
            'severity' => $severity,
            'sector' => $sector,
            'sector_key' => $sectorKey,
            'title' => $title,
            'detail' => $detail,
            'resource_type' => $resourceType,
            'resource_class' => $resource ? get_class($resource) : null,
            'resource_id' => $resource?->getKey(),
            'kecamatan_id' => $resource?->kecamatan_id,
            'kecamatan_name' => $resource?->kecamatan?->name,
            'latitude' => $resource?->latitude !== null ? (float) $resource->latitude : null,
            'longitude' => $resource?->longitude !== null ? (float) $resource->longitude : null,
            'detail_route' => $route,
            'detail_params' => $route ? ($detailParams ?? ['id' => $resource->getKey()]) : null,
            'created_at' => $resource?->created_at?->toDateTimeString(),
        ];
    }
}
