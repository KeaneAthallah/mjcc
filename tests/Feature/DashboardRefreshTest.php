<?php

use App\Models\CommandAlert;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\School;
use App\Models\User;
use App\Services\PublicData\SourceRegistry;
use Illuminate\Support\Facades\Cache;

it('redirects guests to login when trying to refresh the dashboard', function () {
    $this->post(route('dashboard.refresh'))->assertRedirect(route('login'));
});

it('clears dashboard caches and re-syncs alerts on refresh', function () {
    $user = User::factory()->operator()->create();

    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'rusak berat',
    ]);

    Cache::put('command-center.status.overall', ['forced' => true]);
    Cache::put('command-center.data-freshness', 'stale');
    Cache::put('dashboard.overview', ['forced' => true]);

    $this->actingAs($user)
        ->post(route('dashboard.refresh'))
        ->assertRedirect();

    expect(Cache::has('command-center.status.overall'))->toBeFalse()
        ->and(Cache::has('command-center.data-freshness'))->toBeFalse()
        ->and(Cache::has('dashboard.overview'))->toBeFalse()
        ->and(CommandAlert::query()->whereIn('status', CommandAlert::openStatuses())->count())->toBe(1);
});

it('renders the auto-refresh control on the dashboard', function () {
    $user = User::factory()->operator()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('dashboardAutoRefresh', false)
        ->assertSee(config('command-center.dashboard.live_refresh_seconds'), false)
        ->assertSee(route('dashboard.refresh'));
});

it('lets a viewer refresh the dashboard', function () {
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->post(route('dashboard.refresh'))
        ->assertRedirect();
});

it('renders the public data overview section on the dashboard', function () {
    $user = User::factory()->operator()->create();

    SourceRegistry::seed();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pusat Data Publik')
        ->assertSee('Pendidikan')
        ->assertSee(route('public-data.dashboard'), false);
});
