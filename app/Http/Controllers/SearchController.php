<?php

namespace App\Http\Controllers;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Pencarian lintas data master (keystone search).
     */
    public function index(Request $request): View
    {
        $query = trim((string) $request->string('q'));

        if ($query === '') {
            return view('search.index', [
                'query' => '',
                'results' => collect(),
                'suggestions' => collect(),
            ]);
        }

        $results = collect();

        $results = $results->merge(
            School::query()
                ->where('name', 'like', '%'.$query.'%')
                ->orWhere('npsn', 'like', '%'.$query.'%')
                ->with('kecamatan:id,name')
                ->get(['id', 'name', 'npsn', 'condition', 'school_type', 'kecamatan_id'])
                ->map(fn (School $s) => [
                    'type' => 'Sekolah',
                    'slug' => 'school',
                    'title' => $s->name,
                    'subtitle' => trim(($s->school_type ?? '').' · '.($s->kecamatan?->name ?? '')).' · Kondisi '.($s->condition ?: 'belum diisi'),
                    'url' => route('education.schools.show', $s),
                    'icon' => '🏫',
                ]),
        );

        $results = $results->merge(
            HealthFacility::query()
                ->where('name', 'like', '%'.$query.'%')
                ->with('kecamatan:id,name')
                ->get(['id', 'name', 'facility_type', 'status', 'kecamatan_id'])
                ->map(fn (HealthFacility $f) => [
                    'type' => 'Faskes',
                    'slug' => 'health_facility',
                    'title' => $f->name,
                    'subtitle' => trim(($f->facility_type ?? '').' · '.($f->kecamatan?->name ?? '')).' · Status '.($f->status ?: 'belum diisi'),
                    'url' => route('health.facilities.show', $f),
                    'icon' => '🏥',
                ]),
        );

        $results = $results->merge(
            Poskamling::query()
                ->where('name', 'like', '%'.$query.'%')
                ->with('kecamatan:id,name')
                ->get(['id', 'name', 'is_active', 'kecamatan_id'])
                ->map(fn (Poskamling $p) => [
                    'type' => 'Poskamling',
                    'slug' => 'poskamling',
                    'title' => $p->name,
                    'subtitle' => ($p->kecamatan?->name ?? '').' · '.($p->is_active ? 'Aktif' : 'Tidak aktif'),
                    'url' => route('security.poskamlings.show', $p),
                    'icon' => '🛡️',
                ]),
        );

        $results = $results->merge(
            Tipkamtikmas::query()
                ->where('title', 'like', '%'.$query.'%')
                ->with('kecamatan:id,name')
                ->get(['id', 'title', 'status', 'kecamatan_id'])
                ->map(fn (Tipkamtikmas $t) => [
                    'type' => 'Tipkamtikmas',
                    'slug' => 'tipkamtikmas',
                    'title' => $t->title,
                    'subtitle' => ($t->kecamatan?->name ?? '').' · Status '.($t->status ?: 'belum diisi'),
                    'url' => route('security.tipkamtikmas.show', $t),
                    'icon' => '🪖',
                ]),
        );

        $results = $results->merge(
            Polsek::query()
                ->where('name', 'like', '%'.$query.'%')
                ->with('kecamatan:id,name')
                ->get(['id', 'name', 'status', 'kecamatan_id'])
                ->map(fn (Polsek $p) => [
                    'type' => 'Polsek',
                    'slug' => 'polsek',
                    'title' => $p->name,
                    'subtitle' => ($p->kecamatan?->name ?? '').' · Status '.($p->status ?: 'belum diisi'),
                    'url' => route('security.polseks.show', $p),
                    'icon' => '🚓',
                ]),
        );

        $results = $results->merge(
            Market::query()
                ->where('name', 'like', '%'.$query.'%')
                ->with('kecamatan:id,name')
                ->get(['id', 'name', 'status', 'kecamatan_id'])
                ->map(fn (Market $m) => [
                    'type' => 'Pasar',
                    'slug' => 'market',
                    'title' => $m->name,
                    'subtitle' => ($m->kecamatan?->name ?? '').' · Status '.($m->status ?: 'belum diisi'),
                    'url' => route('security.markets.show', $m),
                    'icon' => '🏪',
                ]),
        );

        $results = $results->merge(
            Kecamatan::query()
                ->where('name', 'like', '%'.$query.'%')
                ->get(['id', 'name'])
                ->map(fn (Kecamatan $k) => [
                    'type' => 'Kecamatan',
                    'slug' => 'kecamatan',
                    'title' => $k->name,
                    'subtitle' => 'Profil intelijen kecamatan',
                    'url' => route('kecamatan.show', $k),
                    'icon' => '🧭',
                ]),
        );

        $results = $results->merge(
            Kelurahan::query()
                ->where('name', 'like', '%'.$query.'%')
                ->with('kecamatan:id,name')
                ->get(['id', 'name', 'kecamatan_id'])
                ->map(fn (Kelurahan $l) => [
                    'type' => 'Kelurahan/Desa',
                    'slug' => 'kelurahan',
                    'title' => $l->name,
                    'subtitle' => $l->kecamatan?->name ?? '',
                    'url' => route('master.kelurahans.show', $l),
                    'icon' => '🏘️',
                ]),
        );

        $suggestions = $results
            ->pluck('title')
            ->unique()
            ->take(6);

        return view('search.index', [
            'query' => $query,
            'results' => $results->sortBy('type')->values(),
            'suggestions' => $suggestions,
        ]);
    }
}
