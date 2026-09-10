<?php

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;

it('serves the public overview without authentication', function () {
    Kecamatan::factory()->create();
    School::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/public/overview');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['pendidikan', 'kesehatan', 'ketertiban', 'fasilitas'],
        ])
        ->assertJsonPath('data.pendidikan.sekolah', 3);
});

it('lists public schools with pagination, search and filters', function () {
    $kecamatan = Kecamatan::factory()->create(['name' => 'Bungku']);
    School::factory()->count(25)->sd()->create(['kecamatan_id' => $kecamatan->id]);
    School::factory()->smp()->create(['name' => 'SMPN Pelita', 'kecamatan_id' => $kecamatan->id]);
    $this->getJson('/api/v1/public/schools?per_page=10')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [['id', 'name', 'school_type']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.total', 26);

    $searchResponse = $this->getJson('/api/v1/public/schools?search=Pelita');

    $searchResponse->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'SMPN Pelita');

    $this->getJson('/api/v1/public/schools?school_type=SMP')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.school_type', 'SMP');

    $this->getJson('/api/v1/public/schools?sort=name&sort_direction=desc')
        ->assertOk();
});

it('shows a public school detail', function () {
    $kecamatan = Kecamatan::factory()->create(['name' => 'Bungku']);
    $school = School::factory()->create(['name' => 'SDN Detail', 'kecamatan_id' => $kecamatan->id]);

    $this->getJson('/api/v1/public/schools/'.$school->id)
        ->assertOk()
        ->assertJsonPath('data.id', $school->id)
        ->assertJsonPath('data.name', 'SDN Detail')
        ->assertJsonStructure(['data' => ['kecamatan', 'address']]);
});

it('lists public health facilities and supports filters', function () {
    HealthFacility::factory()->count(3)->create(['facility_type' => 'Puskesmas']);
    HealthFacility::factory()->create(['facility_type' => 'Posyandu']);

    $this->getJson('/api/v1/public/health-facilities')
        ->assertOk()
        ->assertJsonPath('meta.total', 4);

    $this->getJson('/api/v1/public/health-facilities?facility_type=Posyandu')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.facility_type', 'Posyandu');
});

it('lists public security entities', function () {
    Polsek::factory()->count(2)->create();
    Poskamling::factory()->count(3)->create();
    Tipkamtikmas::factory()->count(4)->create();

    $this->getJson('/api/v1/public/polseks')->assertOk()->assertJsonPath('meta.total', 2);
    $this->getJson('/api/v1/public/poskamlings')->assertOk()->assertJsonPath('meta.total', 3);
    $this->getJson('/api/v1/public/tipkamtikmas')->assertOk()->assertJsonPath('meta.total', 4);
});

it('lists public facilities and kelurahans', function () {
    $kecamatan = Kecamatan::factory()->create();
    Market::factory()->count(2)->create();
    Kelurahan::factory()->count(5)->create(['kecamatan_id' => $kecamatan->id]);

    $this->getJson('/api/v1/public/markets')->assertOk()->assertJsonPath('meta.total', 2);
    $this->getJson('/api/v1/public/kelurahans')
        ->assertOk()
        ->assertJsonPath('meta.total', 5)
        ->assertJsonStructure(['data' => [['id', 'name', 'population']]]);
});

it('returns 404 for a missing public record', function () {
    $this->getJson('/api/v1/public/schools/999999')->assertNotFound();
});

it('exposes only read endpoints publicly (no write access)', function () {
    $this->postJson('/api/v1/public/schools', ['name' => 'X'])->assertStatus(405);
    $this->putJson('/api/v1/public/schools/1', ['name' => 'X'])->assertStatus(405);
    $this->deleteJson('/api/v1/public/schools/1')->assertStatus(405);
});
