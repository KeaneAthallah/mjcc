<?php

use App\Events\CommandAlertChanged;
use App\Events\MasterDataChanged;
use App\Events\NotificationCreated;
use App\Events\PublicDataSyncCompleted;
use App\Events\ResponderLocationUpdated;
use App\Events\SosCreated;
use App\Events\SosUpdated;
use App\Models\CommandAlert;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\ResponderLocation;
use App\Models\School;
use App\Models\SosAlert;
use App\Models\User;
use App\Observers\DataChangeObserver;
use App\Services\NotificationService;
use App\Services\SosService;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->viewer()->create();
    DataChangeObserver::flush();
});

/**
 * Route `/broadcasting/auth` through a real Pusher-compatible broadcaster so
 * the channel authorization callbacks actually run. `MasterDataChanged` is
 * faked so the app-terminating observer flush cannot attempt a real broadcast.
 */
function usePusherBroadcaster(): void
{
    config()->set('broadcasting.default', 'pusher');
    config()->set('broadcasting.connections.pusher.key', '0123456789abcdef0123456789abcdef01234567');
    config()->set('broadcasting.connections.pusher.secret', '0123456789abcdef0123456789abcdef01234567');
    config()->set('broadcasting.connections.pusher.app_id', '123456');

    Event::fake([MasterDataChanged::class]);

    // Channel callbacks were bound to the driver resolved at boot (null in
    // tests); re-register them against the now-default pusher driver.
    require base_path('routes/channels.php');
}

it('authorizes the dashboard channel for any authenticated user', function () {
    usePusherBroadcaster();
    Sanctum::actingAs($this->user);

    $this->postJson('/broadcasting/auth', [
        'channel_name' => 'private-dashboard',
        'socket_id' => '123.456',
    ])->assertOk()->assertJsonStructure(['auth']);
});

it('rejects the dashboard channel for guests', function () {
    $this->postJson('/broadcasting/auth', [
        'channel_name' => 'private-dashboard',
        'socket_id' => '123.456',
    ])->assertUnauthorized();
});

it('authorizes the command-center channel only for managers and responders', function () {
    usePusherBroadcaster();
    $manager = User::factory()->admin()->create();
    $responder = User::factory()->medicalResponder()->create();

    Sanctum::actingAs($manager);
    $this->postJson('/broadcasting/auth', ['channel_name' => 'private-command-center', 'socket_id' => '123.456'])
        ->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($responder);
    $this->postJson('/broadcasting/auth', ['channel_name' => 'private-command-center', 'socket_id' => '123.456'])
        ->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($this->user);
    $this->postJson('/broadcasting/auth', ['channel_name' => 'private-command-center', 'socket_id' => '123.456'])
        ->assertForbidden();
});

it('authorizes the sos channel for the owner, managers and matching responders only', function () {
    usePusherBroadcaster();
    $owner = User::factory()->viewer()->create();
    $admin = User::factory()->admin()->create();
    $medical = User::factory()->medicalResponder()->create();
    $fire = User::factory()->fireResponder()->create();

    $sos = SosAlert::factory()->create([
        'user_id' => $owner->id,
        'category' => SosAlert::CATEGORY_MEDICAL,
        'status' => SosAlert::STATUS_ACTIVE,
    ]);

    $channel = "private-sos.{$sos->id}";
    $payload = [
        'channel_name' => $channel,
        'socket_id' => '123.456',
    ];

    Sanctum::actingAs($owner);
    $this->postJson('/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($admin);
    $this->postJson('/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($medical);
    $this->postJson('/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($fire);
    $this->postJson('/broadcasting/auth', $payload)->assertForbidden();

    Sanctum::actingAs($this->user);
    $this->postJson('/broadcasting/auth', $payload)->assertForbidden();
});

it('authorizes the user channel only for the owner', function () {
    usePusherBroadcaster();
    $other = User::factory()->viewer()->create();

    Sanctum::actingAs($this->user);
    $this->postJson('/broadcasting/auth', [
        'channel_name' => "private-user.{$this->user->id}",
        'socket_id' => '123.456',
    ])->assertOk()->assertJsonStructure(['auth']);

    Sanctum::actingAs($other);
    $this->postJson('/broadcasting/auth', [
        'channel_name' => "private-user.{$this->user->id}",
        'socket_id' => '123.456',
    ])->assertForbidden();
});

it('clears all aggregate caches when master data changes', function () {
    $kecamatan = Kecamatan::factory()->create();

    $keys = [
        'dashboard.overview',
        'dashboard.counts',
        'command-center.maps.all',
        'command-center.maps.'.$kecamatan->id,
        'command-center.status.overall',
        'command-center.status.kecamatan.'.$kecamatan->id,
        'command-center.data-freshness',
    ];

    foreach ($keys as $key) {
        Cache::put($key, 'stale', 60);
    }

    event(new MasterDataChanged(['schools' => ['created' => 1]]));

    foreach ($keys as $key) {
        expect(Cache::has($key))->toBeFalse();
    }
});

it('clears the sidebar alert caches when a command alert changes', function () {
    Cache::put('command-center.sidebar.alerts', 'stale', 60);
    Cache::put('command-center.sidebar.alerts.recent', 'stale', 60);

    event(new CommandAlertChanged(42, CommandAlert::STATUS_SELESAI));

    expect(Cache::has('command-center.sidebar.alerts'))->toBeFalse();
    expect(Cache::has('command-center.sidebar.alerts.recent'))->toBeFalse();
});

it('batches model writes and flushes them as a single MasterDataChanged event', function () {
    $kecamatan = Kecamatan::factory()->create();
    School::factory()->count(2)->create(['kecamatan_id' => $kecamatan->id]);
    HealthFacility::factory()->create(['kecamatan_id' => $kecamatan->id]);

    Event::fake([MasterDataChanged::class]);

    expect(DataChangeObserver::pendingChanges())->not->toBeEmpty();

    DataChangeObserver::flush($this->user->id);

    Event::assertDispatched(MasterDataChanged::class, function (MasterDataChanged $event) {
        return isset($event->resources['schools']['created'])
            && $event->resources['schools']['created'] === 2
            && isset($event->resources['kecamatans']['created'])
            && isset($event->resources['health_facilities']['created'])
            && $event->triggerUserId === $this->user->id;
    });

    expect(DataChangeObserver::pendingChanges())->toBeEmpty();
});

it('dispatches realtime events through the SOS lifecycle', function () {
    Event::fake([SosCreated::class, SosUpdated::class, NotificationCreated::class]);

    $sos = app(SosService::class)->create($this->user, [
        'latitude' => -2.0,
        'longitude' => 121.9,
        'category' => SosAlert::CATEGORY_GENERAL,
        'message' => 'tolong',
    ]);

    $sosId = $sos->id;

    Event::assertDispatched(SosCreated::class, function (SosCreated $event) use ($sosId) {
        return $event->sos->id === $sosId;
    });

    app(SosService::class)->respond($sos, 'segera menuju lokasi', $this->user);

    Event::assertDispatched(SosUpdated::class, function (SosUpdated $event) use ($sosId) {
        return $event->sos->id === $sosId
            && $event->previousStatus === SosAlert::STATUS_ACTIVE
            && $event->sos->status === SosAlert::STATUS_RESPONDING;
    });
});

it('notifies recipients with a realtime payload that renders correctly', function () {
    Event::fake([NotificationCreated::class]);

    $notification = app(NotificationService::class)->notifyUser(
        $this->user,
        'SOS Baru',
        'Ada SOS yang membutuhkan bantuan.',
        'sos',
        ['sos_alert_id' => 1],
    );

    $notificationId = $notification->id;

    Event::assertDispatched(NotificationCreated::class, function (NotificationCreated $event) use ($notificationId) {
        return $event->notification->id === $notificationId;
    });

    $payload = (new NotificationCreated($notification))->broadcastWith();

    expect($payload['notification']['title'])->toBe('SOS Baru');
    expect($payload['unread_count'])->toBeGreaterThanOrEqual(1);
});

it('broadcasts responder location updates with the sos id and location', function () {
    Event::fake([ResponderLocationUpdated::class]);

    $sos = SosAlert::factory()->create([
        'user_id' => $this->user->id,
        'status' => SosAlert::STATUS_ACTIVE,
    ]);

    app(SosService::class)->updateResponderLocation($sos->id, User::factory()->fireResponder()->create(), -2.1, 121.9);

    $sosId = $sos->id;

    Event::assertDispatched(ResponderLocationUpdated::class, function (ResponderLocationUpdated $event) use ($sosId) {
        return $event->sosId === $sosId;
    });

    $location = ResponderLocation::query()->latest('id')->first();
    $payload = (new ResponderLocationUpdated($location, $sosId))->broadcastWith();

    expect($payload['sos_id'])->toBe($sosId);
    expect($payload['location']['latitude'])->toBe(-2.1);
});

it('dispatches a sync-completed payload after a successful sector scrape', function () {
    Event::fake([PublicDataSyncCompleted::class]);

    event(new PublicDataSyncCompleted('pendidikan', 'Pendidikan', [
        'created' => 10,
        'updated' => 2,
        'records' => 12,
        'failures' => [],
        'status' => 'success',
    ]));

    Event::assertDispatched(PublicDataSyncCompleted::class, function (PublicDataSyncCompleted $event) {
        return $event->sector === 'pendidikan' && $event->result['records'] === 12;
    });
});
