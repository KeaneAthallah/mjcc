<?php

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use App\Models\User;

it('requires authentication', function () {
    $this->get(route('search.index'))->assertRedirect(route('login'));
});

it('shows the search page for an empty query', function () {
    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('search.index'))
        ->assertOk();
});

it('finds a school by name', function () {
    $kecamatan = Kecamatan::factory()->create();
    $school = School::factory()->create(['kecamatan_id' => $kecamatan->id, 'name' => 'SDN 1 Bungintende']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('search.index', ['q' => 'Bungintende']))
        ->assertOk()
        ->assertSee('SDN 1 Bungintende')
        ->assertSee(route('education.schools.show', $school));
});

it('finds a health facility by name', function () {
    $kecamatan = Kecamatan::factory()->create();
    $faskes = HealthFacility::factory()->create(['kecamatan_id' => $kecamatan->id, 'name' => 'Puskesmas Bahodopi']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('search.index', ['q' => 'Bahodopi']))
        ->assertOk()
        ->assertSee('Puskesmas Bahodopi')
        ->assertSee(route('health.facilities.show', $faskes));
});

it('finds security assets across multiple types', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'name' => 'Poskamling Terminal Bungi']);
    Tipkamtikmas::factory()->create(['kecamatan_id' => $kecamatan->id, 'title' => 'Tipkamtikmas Bungi']);
    Polsek::factory()->create(['kecamatan_id' => $kecamatan->id, 'name' => 'Polsek Bungi']);
    Market::factory()->create(['kecamatan_id' => $kecamatan->id, 'name' => 'Pasar Bungi']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('search.index', ['q' => 'Bungi']))
        ->assertOk()
        ->assertSee('Poskamling Terminal Bungi')
        ->assertSee('Tipkamtikmas Bungi')
        ->assertSee('Polsek Bungi')
        ->assertSee('Pasar Bungi');
});

it('shows an untouched profile page when no results match', function () {
    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('search.index', ['q' => 'tidak-ada-entitas-xyz']))
        ->assertOk()
        ->assertSee('Tidak ada hasil');
});
