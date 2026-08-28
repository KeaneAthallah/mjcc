<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('logs in and returns a usable bearer token', function () {
    $user = User::factory()->admin()->create(['password' => 'secret123']);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login berhasil')
        ->assertJsonStructure([
            'data' => ['token', 'user' => ['id', 'name', 'email', 'role']],
        ]);

    $token = $response->json('data.token');
    expect($token)->toBeString()->not->toBeEmpty();

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('rejects invalid credentials on login', function () {
    $user = User::factory()->create(['password' => 'secret123']);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Validasi gagal')
        ->assertJsonStructure(['errors' => ['email']]);
});

it('rate limits the login endpoint after repeated attempts', function () {
    User::factory()->create(['email' => 'ratelimit@morowali.go.id', 'password' => 'secret123']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => 'ratelimit@morowali.go.id',
            'password' => 'secret123',
        ])->assertOk();
    }

    $this->postJson('/api/v1/login', [
        'email' => 'ratelimit@morowali.go.id',
        'password' => 'secret123',
    ])->assertStatus(429);
});

it('returns the authenticated user via the me endpoint', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.id', $admin->id)
        ->assertJsonPath('data.role', 'admin');
});

it('does not leak password data in the user resource', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonMissingPath('data.password')
        ->assertJsonMissingPath('data.remember_token');
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $this->postJson('/api/v1/logout')->assertOk()->assertJsonPath('success', true);

    expect($user->tokens()->count())->toBe(0);
});

it('returns 401 for unauthenticated requests', function () {
    $this->getJson('/api/v1/me')
        ->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Anda belum terautentikasi.');
});
