<?php

use App\Models\ActivityLog;
use App\Models\School;
use App\Models\User;

it('records a create log entry when a resource is created', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    School::factory()->create(['name' => 'SDN Coba 01']);

    $log = ActivityLog::where('resource_type', 'School')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->action)->toBe(ActivityLog::ACTION_CREATE)
        ->and($log->user_id)->toBe($admin->id);
});

it('strips sensitive attributes from log values', function () {
    User::factory()->create(['password' => 'secret', 'remember_token' => 'token']);

    $log = ActivityLog::where('resource_type', 'User')->latest()->first();

    expect(isset($log->new_values['password']))->toBeFalse()
        ->and(isset($log->new_values['remember_token']))->toBeFalse();
});

it('records a login audit entry', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect(ActivityLog::where('action', ActivityLog::ACTION_LOGIN)->exists())->toBeTrue();
});

it('allows an admin to view the audit page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('audit.index'))
        ->assertOk()
        ->assertSee('Log Aktivitas');
});

it('denies non-admin access to the audit page', function () {
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('audit.index'))
        ->assertForbidden();
});
