<?php

use App\Models\Kecamatan;
use App\Models\School;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows an admin to manage users and kecamatan through the API', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/users')->assertOk();

    $this->postJson('/api/v1/users', [
        'name' => 'Operator Baru',
        'email' => 'operator.baru@morowali.go.id',
        'password' => 'password',
        'role' => 'operator',
    ])->assertStatus(201);

    $this->postJson('/api/v1/kecamatans', [
        'name' => 'Bungku Tengah',
        'is_active' => true,
    ])->assertStatus(201);
});

it('blocks an operator from the users and kecamatan write sections', function () {
    $operator = User::factory()->operator()->create();

    Sanctum::actingAs($operator);

    $this->getJson('/api/v1/users')->assertStatus(403);
    $this->postJson('/api/v1/users', [
        'name' => 'X',
        'email' => 'x@morowali.go.id',
        'password' => 'password',
        'role' => 'viewer',
    ])->assertStatus(403);

    $this->postJson('/api/v1/kecamatans', ['name' => 'Bungku'])->assertStatus(403);
});

it('allows an operator to write operational data such as schools', function () {
    $operator = User::factory()->operator()->create();
    $kecamatan = Kecamatan::factory()->create();

    Sanctum::actingAs($operator);

    $this->getJson('/api/v1/schools')->assertOk();

    $this->postJson('/api/v1/schools', [
        'name' => 'SDN API Contoh',
        'school_type' => 'SD',
        'kecamatan_id' => $kecamatan->id,
        'students_male' => 50,
        'students_female' => 50,
        'teachers' => 10,
        'classes' => 6,
        'capacity' => 220,
    ])->assertStatus(201)
        ->assertJsonPath('data.name', 'SDN API Contoh');

    $this->assertDatabaseHas('schools', ['name' => 'SDN API Contoh']);
});

it('blocks a viewer from any write operation (read-only)', function () {
    $viewer = User::factory()->viewer()->create();
    $kecamatan = Kecamatan::factory()->create();
    $school = School::factory()->create();

    Sanctum::actingAs($viewer);

    $this->getJson('/api/v1/schools')->assertOk();

    $this->postJson('/api/v1/schools', [
        'name' => 'SDN Terlarang',
        'school_type' => 'SD',
        'kecamatan_id' => $kecamatan->id,
    ])->assertStatus(403);

    $this->putJson("/api/v1/schools/{$school->id}", [
        'name' => 'Ubah',
        'school_type' => 'SD',
        'kecamatan_id' => $kecamatan->id,
        'students_male' => 0,
        'students_female' => 0,
        'teachers' => 0,
        'classes' => 0,
        'capacity' => 0,
    ])->assertStatus(403);

    $this->deleteJson("/api/v1/schools/{$school->id}")->assertStatus(403);
});

it('returns 403 for an operator trying to force-delete data (admin only)', function () {
    $operator = User::factory()->operator()->create();
    $school = School::factory()->create();
    $school->delete();

    Sanctum::actingAs($operator);

    $this->deleteJson("/api/v1/schools/{$school->id}/force")->assertStatus(403);
});

it('allows an admin to force-delete a soft-deleted record', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create();
    $school->delete();

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/schools/{$school->id}/force")->assertOk();

    $this->assertDatabaseMissing('schools', ['id' => $school->id]);
});
