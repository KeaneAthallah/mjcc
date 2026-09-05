<?php

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\School;
use App\Services\DataFreshnessService;

beforeEach(function () {
    $this->freshness = app(DataFreshnessService::class);
});

it('reports per-table freshness snapshots', function () {
    $snapshot = $this->freshness->snapshot(fresh: true);

    expect($snapshot)->toHaveKeys(['last_update', 'status', 'label', 'tables'])
        ->and($snapshot['tables'])->toBeArray()
        ->and(array_keys($snapshot['tables']))->toContain('schools');
});

it('considers fresh data as terbaru', function () {
    $kecamatan = Kecamatan::factory()->create();
    Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'updated_at' => now()]);

    $snapshot = $this->freshness->snapshot(fresh: true);

    expect($snapshot['status'])->toBe('terbaru')
        ->and($snapshot['label'])->toBe('TERBARU');
});

it('flags stale data as perlu diperbarui', function () {
    $kecamatan = Kecamatan::factory()->create(['updated_at' => now()->subDays(20)]);
    Kelurahan::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'updated_at' => now()->subDays(20),
    ]);

    $snapshot = $this->freshness->snapshot(fresh: true);

    expect($snapshot['status'])->toBe('perlu_diperbarui');
});

it('marks very old data as data lama', function () {
    $kecamatan = Kecamatan::factory()->create(['updated_at' => now()->subDays(60)]);
    Kelurahan::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'updated_at' => now()->subDays(60),
    ]);

    $snapshot = $this->freshness->snapshot(fresh: true);

    expect($snapshot['status'])->toBe('data_lama');
});

it('reports belum ada data when everything is empty', function () {
    $snapshot = $this->freshness->snapshot(fresh: true);

    expect($snapshot['status'])->toBe('tidak_ada_data')
        ->and($snapshot['last_update'])->toBeNull();
});

it('uses the newest updated_at across all tracked models', function () {
    $kecamatan = Kecamatan::factory()->create();
    Kelurahan::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'updated_at' => now()->subDays(3),
    ]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'updated_at' => now()->addMinutes(1),
    ]);

    $snapshot = $this->freshness->snapshot(fresh: true);

    expect($snapshot['status'])->toBe('terbaru')
        ->and($snapshot['tables']['schools'])->not->toBeNull();
});
