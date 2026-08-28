<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('returns the authenticated user profile', function () {
    $this->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.id', $this->user->id)
        ->assertJsonPath('data.email', $this->user->email);
});

it('updates the profile name and email', function () {
    $this->putJson('/api/v1/profile', [
        'name' => 'Nama Baru',
        'email' => 'baru@morowali.go.id',
    ])->assertOk()
        ->assertJsonPath('data.name', 'Nama Baru')
        ->assertJsonPath('data.email', 'baru@morowali.go.id');

    expect($this->user->fresh()->name)->toBe('Nama Baru');
});

it('rejects a duplicate email when updating the profile', function () {
    User::factory()->create(['email' => 'dipakai@morowali.go.id']);

    $this->putJson('/api/v1/profile', [
        'name' => 'Nama',
        'email' => 'dipakai@morowali.go.id',
    ])->assertStatus(422)
        ->assertJsonStructure(['errors' => ['email']]);
});

it('changes the password with the correct current password', function () {
    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'password',
        'password' => 'newsecret123',
        'password_confirmation' => 'newsecret123',
    ])->assertOk()
        ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

    expect(Hash::check('newsecret123', $this->user->fresh()->password))->toBeTrue();
});

it('rejects a password change with the wrong current password', function () {
    $this->putJson('/api/v1/profile/password', [
        'current_password' => 'salah',
        'password' => 'newsecret123',
        'password_confirmation' => 'newsecret123',
    ])->assertStatus(422)
        ->assertJsonStructure(['errors' => ['current_password']]);
});
