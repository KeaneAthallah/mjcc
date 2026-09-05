<?php

use App\Models\ActivityLog;
use App\Models\CommandAlert;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\User;
use App\Services\CommandAlertSyncService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('command-alerts.synced_at');
    $this->sync = app(CommandAlertSyncService::class);
});

it('persists detected problems as actionable command alerts', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'rusak berat',
    ]);
    Poskamling::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'is_active' => false,
    ]);

    $this->sync->sync(force: true);

    expect(CommandAlert::count())->toBe(2)
        ->and(CommandAlert::where('severity', CommandAlert::SEVERITY_CRITICAL)->count())->toBe(1)
        ->and(CommandAlert::where('severity', CommandAlert::SEVERITY_WARNING)->count())->toBe(1)
        ->and(CommandAlert::whereIn('status', CommandAlert::openStatuses())->count())->toBe(2);
});

it('does not duplicate alerts on repeated syncs', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'rusak berat',
    ]);

    $this->sync->sync(force: true);
    $this->sync->sync(force: true);

    expect(CommandAlert::count())->toBe(1)
        ->and(CommandAlert::first()->last_seen_at)->not->toBeNull();
});

it('auto-resolves an open alert once the condition disappears', function () {
    Cache::forget('command-alerts.synced_at');

    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    $school = School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'rusak berat',
    ]);

    $this->sync->sync(force: true);

    expect(CommandAlert::where('status', CommandAlert::STATUS_BARU)->count())->toBe(1);

    $school->update(['condition' => 'baik']);

    $this->sync->sync(force: true);

    expect(CommandAlert::where('status', CommandAlert::STATUS_SELESAI)->count())->toBe(1);
});

it('keeps healthy data free of open alerts', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'condition' => 'baik']);
    Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'is_active' => true]);
    HealthFacility::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'status' => 'aktif',
        'doctors' => 2,
        'nurses' => 3,
        'midwives' => 1,
    ]);

    $this->sync->sync(force: true);

    expect(CommandAlert::whereIn('status', CommandAlert::openStatuses())->count())->toBe(0);
});

it('lists persisted alerts for an operator', function () {
    $user = User::factory()->operator()->create();
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'rusak berat',
    ]);

    $this->actingAs($user)
        ->get(route('alerts.index'))
        ->assertOk()
        ->assertSee('Kondisi gedung perlu perhatian');
});

it('lets an operator advance an alert status and logs the activity', function () {
    $user = User::factory()->operator()->create();
    $alert = CommandAlert::factory()->create(['status' => CommandAlert::STATUS_BARU]);

    $this->actingAs($user)
        ->post(route('alerts.status', $alert), ['status' => CommandAlert::STATUS_DITINJAU])
        ->assertRedirect();

    expect($alert->fresh()->status)->toBe(CommandAlert::STATUS_DITINJAU)
        ->and(ActivityLog::query()->where('action', ActivityLog::ACTION_ALERT_STATUS)->count())->toBe(1);
});

it('blocks viewers from updating alert status', function () {
    $viewer = User::factory()->viewer()->create();
    $alert = CommandAlert::factory()->create();

    $this->actingAs($viewer)
        ->post(route('alerts.status', $alert), ['status' => CommandAlert::STATUS_SELESAI])
        ->assertForbidden();

    expect($alert->fresh()->status)->toBe(CommandAlert::STATUS_BARU);
});

it('filters the alert list by severity', function () {
    $user = User::factory()->admin()->create();
    CommandAlert::factory()->critical()->create(['status' => CommandAlert::STATUS_BARU]);
    CommandAlert::factory()->create(['severity' => CommandAlert::SEVERITY_INFO, 'status' => CommandAlert::STATUS_BARU]);

    $this->actingAs($user)
        ->get(route('alerts.index', ['severity' => 'critical']))
        ->assertOk()
        ->assertSee('Faskes tanpa tenaga medis')
        ->assertDontSee('Kondisi gedung perlu perhatian');
});

it('exposes an operational summary for the command center', function () {
    $user = User::factory()->admin()->create();
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'condition' => 'rusak berat']);
    Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'is_active' => false]);

    $this->actingAs($user)
        ->getJson(route('alerts.summary'))
        ->assertOk()
        ->assertJsonPath('counts.total_open', 2)
        ->assertJsonPath('counts.critical', 1)
        ->assertJsonPath('counts.warning', 1)
        ->assertJsonStructure(['counts' => ['unread', 'critical', 'warning', 'info', 'total_open'], 'recent']);
});

it('shows alert detail for an operator', function () {
    $user = User::factory()->operator()->create();
    $alert = CommandAlert::factory()->critical()->create();

    $this->actingAs($user)
        ->get(route('alerts.show', $alert))
        ->assertOk()
        ->assertSee($alert->title);
});

it('renders the header alert bell with the open-alert count and recent alerts', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    $school = School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'rusak berat',
    ]);

    $user = User::factory()->operator()->create();

    Cache::forget('command-center.sidebar.alerts');
    Cache::forget('command-center.sidebar.alerts.recent');

    $response = $this->actingAs($user)->get(route('dashboard'));

    $alert = CommandAlert::firstWhere('resource_id', $school->id);

    $response
        ->assertOk()
        ->assertSee('🔔')
        ->assertSee('alert-dropdown-list')
        ->assertSee('Kondisi gedung perlu perhatian')
        ->assertSee(route('alerts.show', $alert));
});

it('renders an empty state in the alert bell when nothing is open', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'baik',
    ]);
    Poskamling::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'is_active' => true,
    ]);

    $user = User::factory()->operator()->create();

    Cache::forget('command-center.sidebar.alerts');
    Cache::forget('command-center.sidebar.alerts.recent');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tidak ada alert terbuka');
});
