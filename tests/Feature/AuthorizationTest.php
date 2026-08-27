<?php

use App\Models\Kecamatan;
use App\Models\School;
use App\Models\User;

it('allows an admin to manage users and kecamatan', function () {
    $admin = User::factory()->admin()->create();
    $kecamatan = Kecamatan::factory()->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('master.kecamatans.edit', $kecamatan))
        ->assertOk();
});

it('blocks an operator from the users section', function () {
    $operator = User::factory()->operator()->create();

    $this->actingAs($operator)->get(route('users.index'))->assertForbidden();
});

it('blocks an operator from writing kecamatan (admin-only master data)', function () {
    $operator = User::factory()->operator()->create();
    $kecamatan = Kecamatan::factory()->create();

    $this->actingAs($operator)
        ->get(route('master.kecamatans.create'))
        ->assertForbidden();

    $this->actingAs($operator)
        ->put(route('master.kecamatans.update', $kecamatan), ['name' => 'Ubah'])
        ->assertForbidden();
});

it('allows an operator to write operational data like schools', function () {
    $operator = User::factory()->operator()->create();
    $kecamatan = Kecamatan::factory()->create();

    $this->actingAs($operator)
        ->get(route('education.schools.create'))
        ->assertOk();

    $schoolData = [
        'name' => 'SDN Contoh Baru',
        'school_type' => School::TYPE_SD,
        'kecamatan_id' => $kecamatan->id,
        'npsn' => '1234567890',
        'students_male' => 50,
        'students_female' => 50,
        'teachers' => 10,
        'classes' => 6,
        'capacity' => 220,
    ];

    $this->actingAs($operator)
        ->post(route('education.schools.store'), $schoolData)
        ->assertRedirect(route('education.schools.index'));

    $this->assertDatabaseHas('schools', ['name' => 'SDN Contoh Baru']);
});

it('blocks a viewer from writing operational data (read-only)', function () {
    $viewer = User::factory()->viewer()->create();
    $kecamatan = Kecamatan::factory()->create();

    $this->actingAs($viewer)
        ->get(route('education.schools.create'))
        ->assertForbidden();

    $this->actingAs($viewer)
        ->post(route('education.schools.store'), [
            'name' => 'SDN Terlarang',
            'school_type' => School::TYPE_SD,
            'kecamatan_id' => $kecamatan->id,
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('schools', 0);
});
