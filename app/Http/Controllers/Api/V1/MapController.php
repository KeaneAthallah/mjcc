<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kecamatanId = $request->anyFilled('kecamatan_id') ? $request->integer('kecamatan_id') : null;
        $sector = $request->string('sector')->toString();
        $type = $request->string('type')->toString();

        $markers = collect();

        $scopeKec = fn ($query) => $kecamatanId ? $query->where('kecamatan_id', $kecamatanId) : $query;
        $inSector = fn (string $key) => in_array($sector, ['', $key], true);

        if ($inSector('pendidikan') && in_array($type, ['', 'school'], true)) {
            School::with('kecamatan:id,name')
                ->with('kelurahan:id,name')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->tap($scopeKec)
                ->get()
                ->each(function (School $s) use ($markers) {
                    $markers->push([
                        'id' => $s->id,
                        'type' => 'school',
                        'sector' => 'pendidikan',
                        'name' => $s->name,
                        'latitude' => (float) $s->latitude,
                        'longitude' => (float) $s->longitude,
                        'status' => $s->is_active ? 'aktif' : 'tidak aktif',
                        'kecamatan' => $s->kecamatan?->name,
                        'kelurahan' => $s->kelurahan?->name,
                    ]);
                });
        }

        if ($inSector('ketertiban')) {
            $this->pushPolsek($markers, $scopeKec, $type);
            $this->pushTipkamtikmas($markers, $scopeKec, $type);
            $this->pushPoskamling($markers, $scopeKec, $type);
            $this->pushMarket($markers, $scopeKec, $type);
        }

        if ($inSector('kesehatan') && in_array($type, ['', 'health'], true)) {
            HealthFacility::with('kecamatan:id,name')
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->tap($scopeKec)
                ->get()
                ->each(function (HealthFacility $f) use ($markers) {
                    $markers->push([
                        'id' => $f->id,
                        'type' => 'health_facility',
                        'sector' => 'kesehatan',
                        'name' => $f->name,
                        'latitude' => (float) $f->latitude,
                        'longitude' => (float) $f->longitude,
                        'status' => $f->status,
                        'kecamatan' => $f->kecamatan?->name,
                        'kelurahan' => null,
                    ]);
                });
        }

        return ApiResponse::success([
            'markers' => $markers->values(),
            'kecamatans' => Kecamatan::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $markers
     */
    private function pushPolsek($markers, $scopeKec, string $type): void
    {
        if (! in_array($type, ['', 'polsek'], true)) {
            return;
        }

        Polsek::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->tap($scopeKec)
            ->get()
            ->each(function (Polsek $p) use ($markers) {
                $markers->push([
                    'id' => $p->id,
                    'type' => 'polsek',
                    'sector' => 'ketertiban',
                    'name' => $p->name,
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'status' => $p->status,
                    'kecamatan' => $p->kecamatan?->name,
                    'kelurahan' => null,
                ]);
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $markers
     */
    private function pushTipkamtikmas($markers, $scopeKec, string $type): void
    {
        if (! in_array($type, ['', 'tipkamtikmas'], true)) {
            return;
        }

        Tipkamtikmas::with('kecamatan:id,name')
            ->with('kelurahan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->tap($scopeKec)
            ->get()
            ->each(function (Tipkamtikmas $t) use ($markers) {
                $markers->push([
                    'id' => $t->id,
                    'type' => 'tipkamtikmas',
                    'sector' => 'ketertiban',
                    'name' => $t->title,
                    'latitude' => (float) $t->latitude,
                    'longitude' => (float) $t->longitude,
                    'status' => $t->status,
                    'kecamatan' => $t->kecamatan?->name,
                    'kelurahan' => $t->kelurahan?->name,
                ]);
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $markers
     */
    private function pushPoskamling($markers, $scopeKec, string $type): void
    {
        if (! in_array($type, ['', 'poskamling'], true)) {
            return;
        }

        Poskamling::with('kecamatan:id,name')
            ->with('kelurahan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->tap($scopeKec)
            ->get()
            ->each(function (Poskamling $p) use ($markers) {
                $markers->push([
                    'id' => $p->id,
                    'type' => 'poskamling',
                    'sector' => 'ketertiban',
                    'name' => $p->name,
                    'latitude' => (float) $p->latitude,
                    'longitude' => (float) $p->longitude,
                    'status' => $p->is_active ? 'aktif' : 'tidak aktif',
                    'kecamatan' => $p->kecamatan?->name,
                    'kelurahan' => $p->kelurahan?->name,
                ]);
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $markers
     */
    private function pushMarket($markers, $scopeKec, string $type): void
    {
        if (! in_array($type, ['', 'market'], true)) {
            return;
        }

        Market::with('kecamatan:id,name')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->tap($scopeKec)
            ->get()
            ->each(function (Market $m) use ($markers) {
                $markers->push([
                    'id' => $m->id,
                    'type' => 'market',
                    'sector' => 'ketertiban',
                    'name' => $m->name,
                    'latitude' => (float) $m->latitude,
                    'longitude' => (float) $m->longitude,
                    'status' => $m->status,
                    'kecamatan' => $m->kecamatan?->name,
                    'kelurahan' => null,
                ]);
            });
    }
}
