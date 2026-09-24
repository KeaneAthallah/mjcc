<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    /**
     * Keystone search across the master datasets, mirroring the web search
     * behaviour but returning JSON for the SPA.
     */
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if ($query === '') {
            return ApiResponse::success([
                'query' => '',
                'results' => [],
                'suggestions' => [],
            ], 'Pencarian intelijen');
        }

        $results = collect()
            ->merge($this->schools($query))
            ->merge($this->healthFacilities($query))
            ->merge($this->poskamlings($query))
            ->merge($this->tipkamtikmas($query))
            ->merge($this->polseks($query))
            ->merge($this->markets($query))
            ->merge($this->kecamatans($query))
            ->merge($this->kelurahans($query));

        return ApiResponse::success([
            'query' => $query,
            'results' => $results->sortBy('type')->values()->all(),
            'suggestions' => $results->pluck('title')->unique()->take(6)->values()->all(),
        ], 'Pencarian intelijen');
    }

    private function schools(string $query): Collection
    {
        return School::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orWhere('npsn', 'like', '%'.$query.'%')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'npsn', 'condition', 'school_type', 'kecamatan_id'])
            ->map(fn (School $s) => [
                'id' => $s->id,
                'type' => 'Sekolah',
                'slug' => 'school',
                'title' => $s->name,
                'subtitle' => trim(($s->school_type ?? '').' · '.($s->kecamatan?->name ?? '')).' · Kondisi '.($s->condition ?: 'belum diisi'),
                'kecamatan_name' => $s->kecamatan?->name,
                'icon' => '🏫',
            ]);
    }

    private function healthFacilities(string $query): Collection
    {
        return HealthFacility::query()
            ->where('name', 'like', '%'.$query.'%')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'facility_type', 'status', 'kecamatan_id'])
            ->map(fn (HealthFacility $f) => [
                'id' => $f->id,
                'type' => 'Faskes',
                'slug' => 'health_facility',
                'title' => $f->name,
                'subtitle' => trim(($f->facility_type ?? '').' · '.($f->kecamatan?->name ?? '')).' · Status '.($f->status ?: 'belum diisi'),
                'kecamatan_name' => $f->kecamatan?->name,
                'icon' => '🏥',
            ]);
    }

    private function poskamlings(string $query): Collection
    {
        return Poskamling::query()
            ->where('name', 'like', '%'.$query.'%')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'is_active', 'kecamatan_id'])
            ->map(fn (Poskamling $p) => [
                'id' => $p->id,
                'type' => 'Poskamling',
                'slug' => 'poskamling',
                'title' => $p->name,
                'subtitle' => ($p->kecamatan?->name ?? '').' · '.($p->is_active ? 'Aktif' : 'Tidak aktif'),
                'kecamatan_name' => $p->kecamatan?->name,
                'icon' => '🛡️',
            ]);
    }

    private function tipkamtikmas(string $query): Collection
    {
        return Tipkamtikmas::query()
            ->where('title', 'like', '%'.$query.'%')
            ->with('kecamatan:id,name')
            ->get(['id', 'title', 'status', 'kecamatan_id'])
            ->map(fn (Tipkamtikmas $t) => [
                'id' => $t->id,
                'type' => 'Tipkamtikmas',
                'slug' => 'tipkamtikmas',
                'title' => $t->title,
                'subtitle' => ($t->kecamatan?->name ?? '').' · Status '.($t->status ?: 'belum diisi'),
                'kecamatan_name' => $t->kecamatan?->name,
                'icon' => '🪖',
            ]);
    }

    private function polseks(string $query): Collection
    {
        return Polsek::query()
            ->where('name', 'like', '%'.$query.'%')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'status', 'kecamatan_id'])
            ->map(fn (Polsek $p) => [
                'id' => $p->id,
                'type' => 'Polsek',
                'slug' => 'polsek',
                'title' => $p->name,
                'subtitle' => ($p->kecamatan?->name ?? '').' · Status '.($p->status ?: 'belum diisi'),
                'kecamatan_name' => $p->kecamatan?->name,
                'icon' => '🚓',
            ]);
    }

    private function markets(string $query): Collection
    {
        return Market::query()
            ->where('name', 'like', '%'.$query.'%')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'status', 'kecamatan_id'])
            ->map(fn (Market $m) => [
                'id' => $m->id,
                'type' => 'Pasar',
                'slug' => 'market',
                'title' => $m->name,
                'subtitle' => ($m->kecamatan?->name ?? '').' · Status '.($m->status ?: 'belum diisi'),
                'kecamatan_name' => $m->kecamatan?->name,
                'icon' => '🏪',
            ]);
    }

    private function kecamatans(string $query): Collection
    {
        return Kecamatan::query()
            ->where('name', 'like', '%'.$query.'%')
            ->get(['id', 'name'])
            ->map(fn (Kecamatan $k) => [
                'id' => $k->id,
                'type' => 'Kecamatan',
                'slug' => 'kecamatan',
                'title' => $k->name,
                'subtitle' => 'Profil intelijen kecamatan',
                'kecamatan_name' => $k->name,
                'icon' => '🧭',
            ]);
    }

    private function kelurahans(string $query): Collection
    {
        return Kelurahan::query()
            ->where('name', 'like', '%'.$query.'%')
            ->with('kecamatan:id,name')
            ->get(['id', 'name', 'kecamatan_id'])
            ->map(fn (Kelurahan $l) => [
                'id' => $l->id,
                'type' => 'Kelurahan/Desa',
                'slug' => 'kelurahan',
                'title' => $l->name,
                'subtitle' => $l->kecamatan?->name ?? '',
                'kecamatan_name' => $l->kecamatan?->name,
                'icon' => '🏘️',
            ]);
    }
}
