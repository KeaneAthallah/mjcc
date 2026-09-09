<?php

use App\Models\ActivityLog;
use App\Models\ResponderLocation;
use App\Models\SosAlert;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

const SOS_VALID_COORDS = ['latitude' => -2.0584032, 'longitude' => 121.9005688];

it('creates SOS with each category', function () {
    foreach (['general', 'medical', 'fire', 'police'] as $category) {
        $user = User::factory()->viewer()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/sos', [...SOS_VALID_COORDS, 'category' => $category])
            ->assertStatus(201)
            ->assertJsonPath('data.category', $category);

        $this->assertDatabaseHas('sos_alerts', [
            'category' => $category,
            'status' => SosAlert::STATUS_ACTIVE,
        ]);
    }
});

it('accepts SOS with matching responder type', function () {
    $responder = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create(['category' => 'medical']);

    Sanctum::actingAs($responder);

    $this->postJson("/api/v1/sos/{$sos->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_ACCEPTED);

    $this->assertDatabaseHas('sos_alerts', [
        'id' => $sos->id,
        'status' => SosAlert::STATUS_ACCEPTED,
        'accepted_by' => $responder->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => ActivityLog::ACTION_SOS_ACCEPTED,
        'resource_id' => $sos->id,
    ]);
});

it('rejects SOS acceptance with non-matching responder type', function () {
    $responder = User::factory()->create(['responder_type' => 'fire']);
    $sos = SosAlert::factory()->active()->create(['category' => 'medical']);

    Sanctum::actingAs($responder);

    $this->postJson("/api/v1/sos/{$sos->id}/accept")
        ->assertForbidden();
});

it('allows admin to accept any SOS category', function () {
    $admin = User::factory()->admin()->create();
    $sos = SosAlert::factory()->active()->create(['category' => 'medical']);

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/sos/{$sos->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_ACCEPTED);
});

it('prevents two responders from accepting the same SOS simultaneously', function () {
    $responder1 = User::factory()->create(['responder_type' => 'medical']);
    $responder2 = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create(['category' => 'medical']);

    Sanctum::actingAs($responder1);
    $this->postJson("/api/v1/sos/{$sos->id}/accept")->assertOk();

    Sanctum::actingAs($responder2);
    $this->postJson("/api/v1/sos/{$sos->id}/accept")
        ->assertStatus(422);
});

it('transitions through the full responder lifecycle', function () {
    $responder = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create(['category' => 'medical']);

    Sanctum::actingAs($responder);

    $this->postJson("/api/v1/sos/{$sos->id}/accept")
        ->assertOk()->assertJsonPath('data.status', SosAlert::STATUS_ACCEPTED);

    $this->postJson("/api/v1/sos/{$sos->id}/on-the-way")
        ->assertOk()->assertJsonPath('data.status', SosAlert::STATUS_ON_THE_WAY);

    $this->postJson("/api/v1/sos/{$sos->id}/arrived")
        ->assertOk()->assertJsonPath('data.status', SosAlert::STATUS_ARRIVED);
});

it('allows responder to update location', function () {
    $responder = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create([
        'category' => 'medical',
        'accepted_by' => $responder->id,
        'accepted_at' => now(),
        'status' => SosAlert::STATUS_ACCEPTED,
    ]);

    Sanctum::actingAs($responder);

    $this->postJson("/api/v1/sos/{$sos->id}/location", [
        'latitude' => -2.5,
        'longitude' => 121.5,
    ])->assertOk();

    $this->assertDatabaseHas('sos_responder_locations', [
        'sos_alert_id' => $sos->id,
        'user_id' => $responder->id,
        'latitude' => -2.5,
        'longitude' => 121.5,
    ]);
});

it('prevents non-accepted responder from updating location', function () {
    $responder1 = User::factory()->create(['responder_type' => 'medical']);
    $responder2 = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create([
        'category' => 'medical',
        'accepted_by' => $responder1->id,
        'accepted_at' => now(),
        'status' => SosAlert::STATUS_ACCEPTED,
    ]);

    Sanctum::actingAs($responder2);

    $this->postJson("/api/v1/sos/{$sos->id}/location", [
        'latitude' => -2.5,
        'longitude' => 121.5,
    ])->assertForbidden();
});

it('returns active incidents matching responder type', function () {
    $responder = User::factory()->create(['responder_type' => 'medical']);
    $medicalSos = SosAlert::factory()->active()->create(['category' => 'medical']);
    $fireSos = SosAlert::factory()->active()->create(['category' => 'fire']);

    Sanctum::actingAs($responder);

    $this->getJson('/api/v1/sos/active-incidents')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $medicalSos->id);
});

it('allows admin to see all active incidents regardless of category', function () {
    $admin = User::factory()->admin()->create();
    SosAlert::factory()->active()->create(['category' => 'medical']);
    SosAlert::factory()->active()->create(['category' => 'fire']);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/sos/active-incidents')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('includes category in SOS resource output', function () {
    $admin = User::factory()->admin()->create();
    $sos = SosAlert::factory()->active()->create(['category' => 'medical']);

    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/sos/{$sos->id}")
        ->assertOk()
        ->assertJsonPath('data.category', 'medical')
        ->assertJsonPath('data.category_label', 'Medis');
});

it('rejects non-responder from viewing active incidents', function () {
    $viewer = User::factory()->viewer()->create();

    Sanctum::actingAs($viewer);

    $this->getJson('/api/v1/sos/active-incidents')
        ->assertForbidden();
});

it('returns latest responder location to the SOS owner', function () {
    $owner = User::factory()->viewer()->create();
    $responder = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create([
        'category' => 'medical',
        'user_id' => $owner->id,
        'accepted_by' => $responder->id,
        'status' => SosAlert::STATUS_ACCEPTED,
    ]);

    ResponderLocation::create([
        'sos_alert_id' => $sos->id,
        'user_id' => $responder->id,
        'latitude' => -2.5,
        'longitude' => 121.5,
    ]);
    ResponderLocation::create([
        'sos_alert_id' => $sos->id,
        'user_id' => $responder->id,
        'latitude' => -2.6,
        'longitude' => 121.6,
    ]);

    Sanctum::actingAs($owner);

    $this->getJson("/api/v1/sos/{$sos->id}/responder-locations")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user_id', $responder->id)
        ->assertJsonPath('data.0.latitude', -2.6)
        ->assertJsonPath('data.0.longitude', 121.6)
        ->assertJsonPath('data.0.user.responder_type', 'medical');
});

it('allows operator to view responder locations', function () {
    $operator = User::factory()->operator()->create();
    $responder = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create([
        'category' => 'medical',
        'accepted_by' => $responder->id,
        'status' => SosAlert::STATUS_ACCEPTED,
    ]);

    ResponderLocation::create([
        'sos_alert_id' => $sos->id,
        'user_id' => $responder->id,
        'latitude' => -2.5,
        'longitude' => 121.5,
    ]);

    Sanctum::actingAs($operator);

    $this->getJson("/api/v1/sos/{$sos->id}/responder-locations")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids unrelated viewer from viewing responder locations', function () {
    $owner = User::factory()->viewer()->create();
    $other = User::factory()->viewer()->create();
    $responder = User::factory()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->active()->create([
        'category' => 'medical',
        'user_id' => $owner->id,
        'accepted_by' => $responder->id,
        'status' => SosAlert::STATUS_ACCEPTED,
    ]);

    ResponderLocation::create([
        'sos_alert_id' => $sos->id,
        'user_id' => $responder->id,
        'latitude' => -2.5,
        'longitude' => 121.5,
    ]);

    Sanctum::actingAs($other);

    $this->getJson("/api/v1/sos/{$sos->id}/responder-locations")
        ->assertForbidden();
});
