<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    Sanctum::actingAs($this->admin);
});

it('creates a user with a responder type', function () {
    $this->postJson('/api/v1/users', [
        'name' => 'Dr. Andi',
        'email' => 'dr.andi@morowali.go.id',
        'password' => 'SangatRahasia1!',
        'password_confirmation' => 'SangatRahasia1!',
        'role' => User::ROLE_OPERATOR,
        'responder_type' => User::RESPONDER_MEDICAL,
    ])->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Dr. Andi')
        ->assertJsonPath('data.responder_type', 'medical')
        ->assertJsonPath('data.responder_type_label', 'Medis');

    expect(User::where('email', 'dr.andi@morowali.go.id')->first())
        ->responder_type->toBe(User::RESPONDER_MEDICAL);
});

it('creates a user without a responder type', function () {
    $this->postJson('/api/v1/users', [
        'name' => 'Operator Biasa',
        'email' => 'op@morowali.go.id',
        'password' => 'SangatRahasia1!',
        'password_confirmation' => 'SangatRahasia1!',
        'role' => User::ROLE_VIEWER,
        'responder_type' => null,
    ])->assertCreated()
        ->assertJsonPath('data.responder_type', null);

    expect(User::where('email', 'op@morowali.go.id')->first())
        ->responder_type->toBeNull();
});

it('updates a user responder type', function () {
    $user = User::factory()->operator()->create();

    $this->putJson("/api/v1/users/{$user->id}", [
        'name' => $user->name,
        'email' => $user->email,
        'role' => User::ROLE_OPERATOR,
        'responder_type' => User::RESPONDER_FIRE,
    ])->assertOk()
        ->assertJsonPath('data.responder_type', 'fire')
        ->assertJsonPath('data.responder_type_label', 'Pemadam Kebakaran');

    expect($user->fresh()->responder_type)->toBe(User::RESPONDER_FIRE);
});

it('clears a user responder type', function () {
    $user = User::factory()->operator()->medicalResponder()->create();

    $this->putJson("/api/v1/users/{$user->id}", [
        'name' => $user->name,
        'email' => $user->email,
        'role' => User::ROLE_OPERATOR,
        'responder_type' => null,
    ])->assertOk()
        ->assertJsonPath('data.responder_type', null);

    expect($user->fresh()->responder_type)->toBeNull();
});

it('rejects an invalid responder type', function () {
    $this->postJson('/api/v1/users', [
        'name' => 'Salah',
        'email' => 'salah@morowali.go.id',
        'password' => 'SangatRahasia1!',
        'password_confirmation' => 'SangatRahasia1!',
        'role' => User::ROLE_VIEWER,
        'responder_type' => 'ambulance',
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['errors' => ['responder_type']]);
});

it('verifies an unverified user email as admin', function () {
    $user = User::factory()->viewer()->unverified()->create();

    $this->postJson("/api/v1/users/{$user->id}/verify-email")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email_verified', true);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('is idempotent when the email is already verified', function () {
    $user = User::factory()->operator()->create();

    $this->postJson("/api/v1/users/{$user->id}/verify-email")
        ->assertOk()
        ->assertJsonPath('data.email_verified', true);
});

it('marks a verified email as unverified when verified is false', function () {
    $user = User::factory()->operator()->create();

    $this->postJson("/api/v1/users/{$user->id}/verify-email", [
        'verified' => false,
    ])->assertOk()
        ->assertJsonPath('data.email_verified', false);

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
