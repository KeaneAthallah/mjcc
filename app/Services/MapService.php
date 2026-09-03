<?php

namespace App\Services;

use App\Models\CrawlRecord;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use App\Support\TargetRegionService;
use Illuminate\Support\Collection;

/**
 * Provides data for the combined map ("Peta Gabungan") so all sector records
 * can be rendered together and filtered by sector and kecamatan.
 */
class MapService
{
    /**
     * All map-enabled records across all sectors, optionally filtered by
     * kecamatan.
     *
     * @return array{markers: Collection<int, array<string, mixed>>, kecamatans: array<int, array<string, mixed>>}
     */
    public function combined(?int $kecamatanId = null): array
    {
        $filter = fn ($q) => $kecamatanId ? $q->where('kecamatan_id', $kecamatanId) : $q;

        $markers = collect();

        // Pendidikan
        School::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (School $s) use (&$markers) {
                $markers->push([
                    'name' => $s->name,
                    'sector' => 'pendidikan',
                    'category' => $s->school_type,
                    'latitude' => (float) $s->latitude,
                    'longitude' => (float) $s->longitude,
                    'kecamatan' => $s->kecamatan?->name,
                    'details' => [
                        'Jenis' => $s->school_type,
                        'Siswa' => (int) $s->students_male + (int) $s->students_female,
                        'Guru' => (int) $s->teachers,
                        'Kondisi' => $s->condition,
                    ],
                ]);
            });

        // Ketertiban
        Polsek::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Polsek $p) use (&$markers) {
                $markers->push([
                    'name' => $p->name,
                    'sector' => 'ketertiban',
                    'category' => 'polsek',
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'kecamatan' => $p->kecamatan?->name,
                    'details' => [
                        'Personel' => (int) $p->personnel_count,
                        'Poskamling' => (int) $p->poskamling_count,
                    ],
                ]);
            });

        Market::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Market $m) use (&$markers) {
                $markers->push([
                    'name' => $m->name,
                    'sector' => 'ketertiban',
                    'category' => 'pasar',
                    'latitude' => (float) $m->latitude,
                    'longitude' => (float) $m->longitude,
                    'kecamatan' => $m->kecamatan?->name,
                    'details' => [],
                ]);
            });

        Poskamling::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Poskamling $p) use (&$markers) {
                $markers->push([
                    'name' => $p->name,
                    'sector' => 'ketertiban',
                    'category' => 'poskamling',
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'kecamatan' => $p->kecamatan?->name,
                    'details' => ['Status' => $p->is_active ? 'Aktif' : 'Tidak Aktif'],
                ]);
            });

        Tipkamtikmas::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Tipkamtikmas $t) use (&$markers) {
                $markers->push([
                    'name' => $t->title,
                    'sector' => 'ketertiban',
                    'category' => 'tipkamtikmas',
                    'latitude' => (float) $t->latitude,
                    'longitude' => (float) $t->longitude,
                    'kecamatan' => $t->kecamatan?->name,
                    'details' => ['Status' => ucfirst($t->status)],
                ]);
            });

        Kelurahan::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (Kelurahan $k) use (&$markers) {
                $markers->push([
                    'name' => $k->name,
                    'sector' => 'ketertiban',
                    'category' => 'kelurahan',
                    'latitude' => (float) $k->latitude,
                    'longitude' => (float) $k->longitude,
                    'kecamatan' => $k->kecamatan?->name,
                    'details' => ['Populasi' => (int) $k->population],
                ]);
            });

        // Kesehatan
        HealthFacility::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($kecamatanId, fn ($q) => $q->where('kecamatan_id', $kecamatanId))
            ->get()
            ->each(function (HealthFacility $f) use (&$markers) {
                $markers->push([
                    'name' => $f->name,
                    'sector' => 'kesehatan',
                    'category' => $f->facility_type,
                    'latitude' => (float) $f->latitude,
                    'longitude' => (float) $f->longitude,
                    'kecamatan' => $f->kecamatan?->name,
                    'details' => [
                        'Dokter' => (int) $f->doctors,
                        'Perawat' => (int) $f->nurses,
                        'Bidan' => (int) $f->midwives,
                        'Bed' => (int) $f->beds,
                    ],
                ]);
            });

        // Data Eksternal (crawler: ATS / DAPO / PIHPS BI / BPS)
        CrawlRecord::with('source')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->each(function (CrawlRecord $cr) use (&$markers) {
                if (! $this->isTargetRegionRecord($cr)) {
                    return;
                }

                $slug = $cr->source?->slug;
                $detailRoute = match ($slug) {
                    'ats' => 'crawler.ats.show',
                    'dapo' => 'crawler.dapo.schools.show',
                    'sp2kp' => 'crawler.sp2kp.markets.show',
                    'bps' => 'crawler.bps.show',
                    default => null,
                };

                $markers->push([
                    'name' => $cr->name ?? $cr->external_id,
                    'sector' => 'eksternal',
                    'category' => 'ext-'.($slug ?? 'external'),
                    'latitude' => (float) $cr->latitude,
                    'longitude' => (float) $cr->longitude,
                    'kecamatan' => $cr->kecamatan_name ?? $cr->kabupaten_name,
                    'lastSeen' => $cr->last_seen_at?->diffForHumans(),
                    'detailUrl' => $detailRoute ? route($detailRoute, $cr) : null,
                    'sourceUrl' => $cr->source_url,
                    'details' => array_merge(
                        ['Sumber' => strtoupper((string) ($slug ?? ''))],
                        $this->extractableDetails($cr),
                    ),
                ]);
            });

        return [
            'markers' => $markers,
            'kecamatans' => Kecamatan::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($k) => ['id' => $k->id, 'name' => $k->name])
                ->all(),
        ];
    }

    private function isTargetRegionRecord(CrawlRecord $record): bool
    {
        $service = new TargetRegionService;

        return $service->isTargetRegion($record->kabupaten_code)
            || $service->isTargetRegionName($record->kabupaten_name);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractableDetails(CrawlRecord $record): array
    {
        $data = $record->data ?? [];

        if (! is_array($data)) {
            return [];
        }

        $pick = ['komoditas', 'harga', 'indicator', 'label', 'status', 'jenjang'];

        $details = [];

        foreach ($pick as $key) {
            if (isset($data[$key]) && is_scalar($data[$key])) {
                $details[ucfirst($key)] = $data[$key];
            }
        }

        return $details;
    }
}
