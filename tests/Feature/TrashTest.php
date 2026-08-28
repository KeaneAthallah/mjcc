<?php

use App\Models\School;
use App\Models\User;

it('shows soft deleted resources in the trash', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create();
    $school->delete();

    $this->actingAs($admin)
        ->get(route('education.schools.trash'))
        ->assertOk()
        ->assertSee($school->name);
});

it('does not show active resources in the trash', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create();

    $this->actingAs($admin)
        ->get(route('education.schools.trash'))
        ->assertOk()
        ->assertDontSee($school->name);
});

it('restores a soft deleted resource', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create();
    $school->delete();

    $this->actingAs($admin)->put(route('education.schools.restore', $school->id));

    expect(School::find($school->id))->not->toBeNull();
});

it('allows an operator to restore a resource', function () {
    $operator = User::factory()->operator()->create();
    $school = School::factory()->create();
    $school->delete();

    $this->actingAs($operator)
        ->put(route('education.schools.restore', $school->id))
        ->assertRedirect();

    expect(School::find($school->id))->not->toBeNull();
});

it('permanently deletes a resource as admin', function () {
    $admin = User::factory()->admin()->create();
    $school = School::factory()->create();
    $school->delete();

    $this->actingAs($admin)
        ->delete(route('education.schools.force-destroy', $school->id))
        ->assertRedirect();

    expect(School::withTrashed()->find($school->id))->toBeNull();
});

it('forbids an operator from permanently deleting a resource', function () {
    $operator = User::factory()->operator()->create();
    $school = School::factory()->create();
    $school->delete();

    $this->actingAs($operator)
        ->delete(route('education.schools.force-destroy', $school->id))
        ->assertForbidden();

    expect(School::withTrashed()->find($school->id))->not->toBeNull();
});
