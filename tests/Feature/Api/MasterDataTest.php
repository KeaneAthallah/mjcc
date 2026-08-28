<?php

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Subject;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    Sanctum::actingAs($this->user);
});

it('lists and shows kecamatan', function () {
    $kecamatan = Kecamatan::factory()->create(['name' => 'Bungku']);

    $this->getJson('/api/v1/kecamatans')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/kecamatans/{$kecamatan->id}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Bungku');
});

it('returns kelurahan for a given kecamatan', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'name' => 'Bungi']);

    $this->getJson("/api/v1/kecamatans/{$kecamatan->id}/kelurahans")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Bungi');
});

it('lists kelurahan and filters by kecamatan', function () {
    $kecamatan = Kecamatan::factory()->create();
    Kelurahan::factory()->count(2)->create(['kecamatan_id' => $kecamatan->id]);
    Kelurahan::factory()->create();

    $this->getJson('/api/v1/kelurahans')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    $this->getJson("/api/v1/kelurahans?kecamatan_id={$kecamatan->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('lists subjects', function () {
    Subject::factory()->count(3)->create();

    $this->getJson('/api/v1/subjects')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name', 'code']]]);
});
