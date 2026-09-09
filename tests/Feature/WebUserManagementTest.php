<?php

use App\Models\User;

it('allows admin to verify an unverified user email', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->viewer()->unverified()->create();

    $this->actingAs($admin)
        ->post(route('users.verify-email', $user))
        ->assertRedirect()
        ->assertSessionHas('success', 'Email "'.$user->email.'" berhasil diverifikasi.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('returns info when email is already verified', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->operator()->create();

    $this->actingAs($admin)
        ->post(route('users.verify-email', $user))
        ->assertRedirect()
        ->assertSessionHas('info');
});

it('creates a user with a responder type via web', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Dr. Andi',
            'email' => 'dr.andi.web@morowali.go.id',
            'password' => 'SangatRahasia1!',
            'password_confirmation' => 'SangatRahasia1!',
            'role' => User::ROLE_OPERATOR,
            'responder_type' => User::RESPONDER_MEDICAL,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $user = User::where('email', 'dr.andi.web@morowali.go.id')->first();
    expect($user->responder_type)->toBe(User::RESPONDER_MEDICAL);
});

it('updates a user responder type via web', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->operator()->create();

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => User::ROLE_OPERATOR,
            'responder_type' => User::RESPONDER_FIRE,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($user->fresh()->responder_type)->toBe(User::RESPONDER_FIRE);
});
