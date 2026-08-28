<?php

use App\Models\ActivityLog;
use App\Models\Kecamatan;
use App\Models\School;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows an admin to read the audit log', function () {
    $admin = User::factory()->admin()->create();
    ActivityLog::create([
        'user_id' => $admin->id,
        'action' => ActivityLog::ACTION_LOGIN,
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/audit')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => [['id', 'action', 'description']]]);
});

it('blocks a non-admin from the audit log', function () {
    $operator = User::factory()->operator()->create();

    Sanctum::actingAs($operator);

    $this->getJson('/api/v1/audit')->assertStatus(403);
});

it('records a create audit entry when creating via the API', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $kecamatan = Kecamatan::factory()->create();

    $this->postJson('/api/v1/schools', [
        'name' => 'SDN Ter-Audit',
        'school_type' => 'SD',
        'kecamatan_id' => $kecamatan->id,
        'students_male' => 10,
        'students_female' => 10,
        'teachers' => 2,
        'classes' => 6,
        'capacity' => 120,
    ])->assertStatus(201);

    $school = School::where('name', 'SDN Ter-Audit')->first();

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => ActivityLog::ACTION_CREATE,
        'resource_type' => 'School',
        'resource_id' => $school->id,
    ]);
});

it('never stores passwords in audit logs', function () {
    $admin = User::factory()->admin()->create();

    // login
    $this->postJson('/api/v1/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertOk();

    Sanctum::actingAs($admin);

    // create a user with a distinctive password
    $this->postJson('/api/v1/users', [
        'name' => 'Rahasia',
        'email' => 'rahasia@morowali.go.id',
        'password' => 'SuperRahasia99',
        'role' => 'viewer',
    ])->assertStatus(201);

    ActivityLog::query()->get()->each(function (ActivityLog $log) {
        if (is_array($log->new_values)) {
            expect($log->new_values)->not->toContain('SuperRahasia99')
                ->and(isset($log->new_values['password']))->toBeFalse()
                ->and(isset($log->new_values['remember_token']))->toBeFalse();
        }

        if (is_array($log->old_values)) {
            expect($log->old_values)->not->toContain('SuperRahasia99');
        }
    });
});
