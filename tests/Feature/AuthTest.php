<?php

use App\Models\User;

it('allows a registered user to log in and access the dashboard', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('rejects invalid credentials', function () {
    $this->post(route('login'), [
        'email' => 'tidak-ada@morowali.go.id',
        'password' => 'salah',
    ])->assertSessionHasErrors('email');
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
});
