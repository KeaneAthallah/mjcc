<?php

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\User;
use App\Services\AlertService;

it('flags a school building in poor condition as a pendidikan alert', function () {
    $service = app(AlertService::class);
    School::factory()->create(['condition' => 'rusak berat']);

    $alerts = $service->forSector('pendidikan');

    expect($alerts)->not->toBeEmpty()
        ->and($alerts[0]['sector_key'])->toBe('pendidikan')
        ->and($alerts[0]['title'])->toBe('Kondisi gedung perlu perhatian');
});

it('flags a health facility without any medical staff as critical', function () {
    $service = app(AlertService::class);
    HealthFacility::factory()->create(['doctors' => 0, 'nurses' => 0, 'midwives' => 0, 'status' => 'aktif']);

    $alerts = $service->forSector('kesehatan');

    expect($alerts)->not->toBeEmpty()
        ->and(collect($alerts)->contains(fn ($a) => $a['severity'] === 'critical' && $a['title'] === 'Faskes tanpa tenaga medis'))->toBeTrue();
});

it('flags an inactive poskamling as a ketertiban critical alert', function () {
    $service = app(AlertService::class);
    Poskamling::factory()->create(['is_active' => false]);

    $alerts = $service->forSector('ketertiban');

    expect(collect($alerts)->contains(fn ($a) => $a['severity'] === 'critical' && $a['title'] === 'Pos Kamling nonaktif'))->toBeTrue();
});

it('returns no alerts for a healthy dataset', function () {
    $service = app(AlertService::class);
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);

    School::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'condition' => 'baik']);
    HealthFacility::factory()->create(['kecamatan_id' => $kecamatan->id, 'doctors' => 3, 'nurses' => 5, 'midwives' => 2, 'status' => 'aktif']);
    Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'is_active' => true]);

    expect($service->forSector('pendidikan'))->toBeEmpty()
        ->and($service->forSector('kesehatan'))->toBeEmpty()
        ->and($service->forSector('ketertiban'))->toBeEmpty();
});

it('renders the per-sector dashboard with the alerts section', function () {
    $admin = User::factory()->admin()->create();
    School::factory()->create(['condition' => 'rusak berat']);

    $this->actingAs($admin)
        ->get(route('education.dashboard'))
        ->assertOk()
        ->assertSee('Perlu Perhatian');
});
