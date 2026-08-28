<?php

use App\Models\School;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('lists trashed schools for an admin', function () {
    $admin = User::factory()->admin()->create();
    $trashed = School::factory()->count(3)->create();
    School::factory()->count(2)->create();

    $trashed->each->delete();

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/schools/trash')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('allows an operator to restore a trashed school', function () {
    $operator = User::factory()->operator()->create();
    $school = School::factory()->create();
    $school->delete();

    Sanctum::actingAs($operator);

    $this->putJson("/api/v1/schools/{$school->id}/restore")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($school->fresh()->trashed())->toBeFalse();
});

it('returns 404 when restoring a non-trashed or missing record', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin);

    $this->putJson('/api/v1/schools/999999/restore')
        ->assertStatus(404)
        ->assertJsonPath('message', 'Data tidak ditemukan.');
});

it('soft-delete via DELETE does not permanently remove the record', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create();

    Sanctum::actingAs($admin);

    $this->deleteJson("/api/v1/schools/{$school->id}")->assertOk();

    $this->assertDatabaseHas('schools', ['id' => $school->id, 'deleted_at' => $school->fresh()->deleted_at]);
});
