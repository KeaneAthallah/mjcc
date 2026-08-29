<?php

use App\Models\ActivityLog;
use App\Models\SosAlert;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

const SOS_VALID_COORDS = ['latitude' => -2.0584032, 'longitude' => 121.9005688];

it('allows any authenticated user to create an SOS alert', function () {
    Sanctum::actingAs(User::factory()->viewer()->create());

    $this->postJson('/api/v1/sos', SOS_VALID_COORDS)->assertStatus(201)
        ->assertJsonPath('data.status', SosAlert::STATUS_ACTIVE);

    $this->assertDatabaseHas('sos_alerts', [
        'status' => SosAlert::STATUS_ACTIVE,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => ActivityLog::ACTION_SOS_CREATED,
        'resource_type' => 'SosAlert',
    ]);
});

it('rejects SOS creation for guests', function () {
    $this->postJson('/api/v1/sos', SOS_VALID_COORDS)->assertUnauthorized();
});

it('validates coordinates and optional fields', function () {
    Sanctum::actingAs(User::factory()->viewer()->create());

    $this->postJson('/api/v1/sos', ['latitude' => 999, 'longitude' => 121.9])
        ->assertStatus(422);

    $this->postJson('/api/v1/sos', [...SOS_VALID_COORDS, 'accuracy' => -5])
        ->assertStatus(422);

    $this->postJson('/api/v1/sos', [...SOS_VALID_COORDS, 'message' => str_repeat('a', 501)])
        ->assertStatus(422);
});

it('blocks a second open SOS while one is still active (anti-spam)', function () {
    $user = User::factory()->viewer()->create();
    SosAlert::factory()->active()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/sos', SOS_VALID_COORDS)
        ->assertStatus(422)
        ->assertJsonValidationErrors('sos');
});

it('scopes the inbox per role: operators/admins see everything, viewers only own', function () {
    $operator = User::factory()->operator()->create();
    $viewer = User::factory()->viewer()->create();
    $other = User::factory()->viewer()->create();

    SosAlert::factory()->count(3)->create(['user_id' => $viewer->id]);
    SosAlert::factory()->create(['user_id' => $other->id]);

    Sanctum::actingAs($operator);
    $this->getJson('/api/v1/sos')->assertOk()
        ->assertJsonPath('meta.total', 4);

    Sanctum::actingAs($viewer);
    $this->getJson('/api/v1/sos')->assertOk()
        ->assertJsonPath('meta.total', 3);

    $foreign = SosAlert::factory()->create(['user_id' => $other->id]);
    $this->getJson("/api/v1/sos/{$foreign->id}")->assertForbidden();
});

it('allows an operator to acknowledge, respond to, and resolve an SOS', function () {
    $operator = User::factory()->operator()->create();
    $sos = SosAlert::factory()->active()->create();

    Sanctum::actingAs($operator);

    $this->postJson("/api/v1/sos/{$sos->id}/acknowledge", ['response_message' => 'Segera diproses.'])
        ->assertOk()->assertJsonPath('data.status', SosAlert::STATUS_ACKNOWLEDGED);

    $this->postJson("/api/v1/sos/{$sos->id}/respond")
        ->assertOk()->assertJsonPath('data.status', SosAlert::STATUS_RESPONDING);

    $this->postJson("/api/v1/sos/{$sos->id}/resolve")
        ->assertOk()->assertJsonPath('data.status', SosAlert::STATUS_RESOLVED);

    $this->assertDatabaseHas('activity_logs', [
        'action' => ActivityLog::ACTION_SOS_ACKNOWLEDGED,
        'resource_id' => $sos->id,
    ]);
});

it('rejects managing an SOS for viewers and unauthenticated attempts', function () {
    $viewer = User::factory()->viewer()->create();
    $sos = SosAlert::factory()->active()->create();

    Sanctum::actingAs($viewer);
    $this->postJson("/api/v1/sos/{$sos->id}/acknowledge")->assertForbidden();

    Sanctum::actingAs(User::factory()->viewer()->create());
    $this->postJson("/api/v1/sos/{$sos->id}/respond")->assertForbidden();
});

it('rejects invalid status transitions with 422', function () {
    Sanctum::actingAs(User::factory()->operator()->create());

    $resolved = SosAlert::factory()->resolved()->create();
    $this->postJson("/api/v1/sos/{$resolved->id}/respond")
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');

    $cancelled = SosAlert::factory()->cancelled()->create();
    $this->postJson("/api/v1/sos/{$cancelled->id}/resolve")
        ->assertStatus(422);
});

it('allows the owner or an operator to cancel an active SOS', function () {
    $owner = User::factory()->viewer()->create();
    $sos = SosAlert::factory()->active()->create(['user_id' => $owner->id]);

    Sanctum::actingAs($owner);
    $this->postJson("/api/v1/sos/{$sos->id}/cancel")
        ->assertOk()->assertJsonPath('data.status', SosAlert::STATUS_CANCELLED);
});

it('blocks cancelling an alert the user does not own', function () {
    $viewer = User::factory()->viewer()->create();
    $sos = SosAlert::factory()->active()->create(['user_id' => User::factory()->viewer()->create()->id]);

    Sanctum::actingAs($viewer);
    $this->postJson("/api/v1/sos/{$sos->id}/cancel")->assertForbidden();
});

it('returns a scoped active-count for the notification badge', function () {
    $operator = User::factory()->operator()->create();
    $viewer = User::factory()->viewer()->create();

    SosAlert::factory()->active()->create(['user_id' => $viewer->id]);
    SosAlert::factory()->acknowledged()->create(['user_id' => $viewer->id]);

    Sanctum::actingAs($operator);
    $this->getJson('/api/v1/sos/active-count')->assertOk()->assertJsonPath('data.open', 2);

    Sanctum::actingAs($viewer);
    $this->getJson('/api/v1/sos/active-count')->assertOk()->assertJsonPath('data.open', 2);

    $other = User::factory()->viewer()->create();
    SosAlert::factory()->responding()->create(['user_id' => $other->id]);

    Sanctum::actingAs($viewer);
    $this->getJson('/api/v1/sos/active-count')->assertOk()->assertJsonPath('data.open', 2);

    Sanctum::actingAs($operator);
    $this->getJson('/api/v1/sos/active-count')->assertOk()->assertJsonPath('data.open', 3);
});

it('exposes the SOS inbox and detail pages on the web for admins only', function () {
    $admin = User::factory()->admin()->create();
    $sos = SosAlert::factory()->acknowledged()->create();

    Sanctum::actingAs($admin);
    $this->actingAs($admin)->get('/sos')->assertOk();
    $this->actingAs($admin)->get("/sos/{$sos->id}")->assertOk();

    $this->actingAs($admin)->post("/sos/{$sos->id}/resolve", ['response_message' => 'Selesai'])
        ->assertRedirect();

    $this->assertDatabaseHas('sos_alerts', ['id' => $sos->id, 'status' => SosAlert::STATUS_RESOLVED]);
});

it('lets the owner cancel their own SOS on the web', function () {
    $owner = User::factory()->viewer()->create();
    $sos = SosAlert::factory()->active()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)->get('/sos')->assertForbidden();
    $this->actingAs($owner)->get("/sos/{$sos->id}")->assertOk();

    $this->actingAs($owner)->post("/sos/{$sos->id}/cancel")->assertRedirect();

    $this->assertDatabaseHas('sos_alerts', ['id' => $sos->id, 'status' => SosAlert::STATUS_CANCELLED]);
});

it('returns the owners open alert via my-open and null otherwise', function () {
    $viewer = User::factory()->viewer()->create();

    Sanctum::actingAs($viewer);
    $this->getJson('/api/v1/sos/my-open')->assertOk()->assertJsonPath('data', null);

    $sos = SosAlert::factory()->active()->create(['user_id' => $viewer->id]);
    $this->getJson('/api/v1/sos/my-open')->assertOk()->assertJsonPath('data.id', $sos->id);

    $operator = User::factory()->operator()->create();
    Sanctum::actingAs($operator);
    $this->getJson('/api/v1/sos/my-open')->assertOk()->assertJsonPath('data', null);
});

it('serves live hub data: counts, open-alert markers, and the list fragment', function () {
    $admin = User::factory()->admin()->create();
    SosAlert::factory()->count(2)->active()->create();
    SosAlert::factory()->count(3)->create(['status' => SosAlert::STATUS_ACKNOWLEDGED]);
    SosAlert::factory()->resolved()->create();

    $this->actingAs($admin)->get('/sos/live')->assertOk()
        ->assertJsonPath('counts.active', 2)
        ->assertJsonPath('counts.open', 5)
        ->assertJsonCount(5, 'markers')
        ->assertJsonStructure([
            'counts',
            'markers' => ['*' => ['id', 'category', 'latitude', 'longitude']],
            'listHtml',
        ]);
});

it('marks only open alerts on the live hub map, ignoring resolved and cancelled', function () {
    $admin = User::factory()->admin()->create();
    SosAlert::factory()->active()->create();
    SosAlert::factory()->cancelled()->create();

    $this->actingAs($admin)->get('/sos/live')->assertOk()
        ->assertJsonPath('counts.open', 1)
        ->assertJsonCount(1, 'markers');
});

it('reflects the current filters in the live list fragment', function () {
    $admin = User::factory()->admin()->create();
    $cancelled = SosAlert::factory()->cancelled()->create(['message' => 'pesan-special']);

    $this->actingAs($admin)->get('/sos/live?status=cancelled&page=1')->assertOk()
        ->assertJsonPath('counts.open', 0)
        ->assertJsonPath('listHtml', fn (string $html) => str_contains($html, 'pesan-special'));
});

it('serves live detail fragments for the web show page', function () {
    $admin = User::factory()->admin()->create();
    $sos = SosAlert::factory()->acknowledged()->create(['response_message' => 'Segera diproses.']);

    $this->actingAs($admin)->get("/sos/live/{$sos->id}")->assertOk()
        ->assertJsonPath('status', SosAlert::STATUS_ACKNOWLEDGED)
        ->assertJsonPath('statusCardHtml', fn (string $html) => str_contains($html, 'Diterima'))
        ->assertJsonPath('timelineHtml', fn (string $html) => str_contains($html, 'Petugas Menuju Lokasi') && str_contains($html, 'Segera diproses.'))
        ->assertJsonPath('actionsHtml', fn (string $html) => str_contains($html, 'Menuju Lokasi'));
});

it('hides live management fragments from a regular owner', function () {
    $owner = User::factory()->viewer()->create();
    $sos = SosAlert::factory()->active()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)->get("/sos/live/{$sos->id}")->assertOk()
        ->assertJsonPath('status', SosAlert::STATUS_ACTIVE)
        ->assertJsonPath('actionsHtml', fn (string $html) => ! str_contains($html, 'Terima'))
        ->assertJsonPath('timelineHtml', fn (string $html) => str_contains($html, 'SOS Dikirim'));

    $foreign = SosAlert::factory()->active()->create(['user_id' => User::factory()->viewer()->create()->id]);
    $this->get("/sos/live/{$foreign->id}")->assertForbidden();
});
