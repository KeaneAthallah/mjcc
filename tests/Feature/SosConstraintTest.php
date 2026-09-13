<?php

use App\Models\ActivityLog;
use App\Models\SosAlert;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lets the accepted petugas report a constraint while on the way', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ON_THE_WAY,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(5),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/constraint", [
        'constraint_type' => SosAlert::CONSTRAINT_CANNOT_REACH,
        'constraint_reason' => 'Jalan tertutup longsor.',
    ])->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_CONSTRAINED)
        ->assertJsonPath('data.constraint_type', SosAlert::CONSTRAINT_CANNOT_REACH)
        ->assertJsonPath('data.constraint_type_label', 'Tidak dapat menjangkau lokasi')
        ->assertJsonPath('data.constraint_reason', 'Jalan tertutup longsor.');

    $this->assertDatabaseHas('sos_alerts', [
        'id' => $sos->id,
        'status' => SosAlert::STATUS_CONSTRAINED,
        'constraint_type' => SosAlert::CONSTRAINT_CANNOT_REACH,
        'constraint_reason' => 'Jalan tertutup longsor.',
        'constrained_by' => $petugas->id,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'action' => ActivityLog::ACTION_SOS_CONSTRAINED,
        'resource_id' => $sos->id,
    ]);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $sos->user_id,
        'type' => 'sos',
    ]);
});

it('allows a constraint from the accepted status', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ACCEPTED,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(2),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/constraint", [
        'constraint_type' => SosAlert::CONSTRAINT_DELAYED,
        'constraint_reason' => 'Kemacetan parah di jalan utama.',
    ])->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_CONSTRAINED);
});

it('rejects a constraint without a reason or type', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ON_THE_WAY,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(5),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/constraint", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['constraint_type', 'constraint_reason']);
});

it('rejects an invalid constraint type', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ON_THE_WAY,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(5),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/constraint", [
        'constraint_type' => 'lost',
        'constraint_reason' => 'Tersesat.',
    ])->assertStatus(422)
        ->assertJsonValidationErrors('constraint_type');
});

it('rejects a constraint from a petugas who did not accept the SOS', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $other = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ON_THE_WAY,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(5),
    ]);

    Sanctum::actingAs($other);

    $this->postJson("/api/v1/sos/{$sos->id}/constraint", [
        'constraint_type' => SosAlert::CONSTRAINT_CANNOT_REACH,
        'constraint_reason' => 'Kendaraan rusak.',
    ])->assertForbidden();
});

it('rejects a constraint after the petugas has arrived', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ARRIVED,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(10),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/constraint", [
        'constraint_type' => SosAlert::CONSTRAINT_CANNOT_REACH,
        'constraint_reason' => 'Terjebak di lokasi lain.',
    ])->assertForbidden();
});

it('prevents the petugas from resolving before arriving', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ACCEPTED,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(2),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/resolve")
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('lets the petugas resolve after arriving', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_ARRIVED,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(10),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/resolve")
        ->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_RESOLVED);
});

it('lets the petugas continue and arrive after reporting a constraint', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_CONSTRAINED,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(10),
        'constraint_type' => SosAlert::CONSTRAINT_DELAYED,
        'constraint_reason' => 'Kemacetan.',
        'constrained_by' => $petugas->id,
        'constrained_at' => now()->subMinutes(8),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/on-the-way")
        ->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_ON_THE_WAY);

    $this->postJson("/api/v1/sos/{$sos->id}/arrived")
        ->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_ARRIVED);

    $this->postJson("/api/v1/sos/{$sos->id}/resolve")
        ->assertOk()
        ->assertJsonPath('data.status', SosAlert::STATUS_RESOLVED);
});

it('lets the petugas update the constraint reason while still constrained', function () {
    $petugas = User::factory()->operator()->create(['responder_type' => 'medical']);
    $sos = SosAlert::factory()->create([
        'status' => SosAlert::STATUS_CONSTRAINED,
        'category' => 'medical',
        'accepted_by' => $petugas->id,
        'accepted_at' => now()->subMinutes(10),
        'constraint_type' => SosAlert::CONSTRAINT_DELAYED,
        'constraint_reason' => 'Kemacetan.',
        'constrained_by' => $petugas->id,
        'constrained_at' => now()->subMinutes(8),
    ]);

    Sanctum::actingAs($petugas);

    $this->postJson("/api/v1/sos/{$sos->id}/constraint", [
        'constraint_type' => SosAlert::CONSTRAINT_CANNOT_REACH,
        'constraint_reason' => 'Jalan ditutup total.',
    ])->assertOk();

    $this->assertDatabaseHas('sos_alerts', [
        'id' => $sos->id,
        'constraint_type' => SosAlert::CONSTRAINT_CANNOT_REACH,
        'constraint_reason' => 'Jalan ditutup total.',
    ]);
});
