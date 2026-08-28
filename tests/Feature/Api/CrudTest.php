<?php

use App\Models\Kecamatan;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    Sanctum::actingAs($this->user);
});

it('lists schools with a paginated envelope', function () {
    School::factory()->count(25)->create();

    $response = $this->getJson('/api/v1/schools?per_page=10');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Data berhasil diambil')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'school_type']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('meta.total'))->toBe(25)
        ->and($response->json('meta.per_page'))->toBe(10)
        ->and(count($response->json('data')))->toBe(10);
});

it('searches, filters and sorts the school list', function () {
    $kecamatanA = Kecamatan::factory()->create(['name' => 'Bungku']);
    $kecamatanB = Kecamatan::factory()->create(['name' => 'Menui']);

    School::factory()->sd()->create(['name' => 'SDN Pelita', 'kecamatan_id' => $kecamatanA->id]);
    School::factory()->smp()->create(['name' => 'SMPN Menui', 'kecamatan_id' => $kecamatanB->id]);

    // search
    $this->getJson('/api/v1/schools?search=Pelita')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    // filter by school_type
    $this->getJson('/api/v1/schools?school_type=SMP')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.school_type', 'SMP');

    // filter by kecamatan
    $this->getJson("/api/v1/schools?kecamatan_id={$kecamatanA->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'SDN Pelita');

    // sort descending by name
    $this->getJson('/api/v1/schools?sort=name&sort_direction=desc')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'SMPN Menui');
});

it('creates a school and syncs subjects', function () {
    $kecamatan = Kecamatan::factory()->create();
    $subjectA = Subject::factory()->create();
    $subjectB = Subject::factory()->create();

    $response = $this->postJson('/api/v1/schools', [
        'name' => 'SDN Kreasi',
        'school_type' => 'SD',
        'kecamatan_id' => $kecamatan->id,
        'students_male' => 40,
        'students_female' => 35,
        'teachers' => 12,
        'classes' => 6,
        'capacity' => 250,
        'subjects' => [$subjectA->id, $subjectB->id],
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'SDN Kreasi');

    $school = School::where('name', 'SDN Kreasi')->first();
    expect($school->subjects()->pluck('subjects.id'))->toContain($subjectA->id, $subjectB->id);
});

it('shows a school with its relations', function () {
    $school = School::factory()->create();

    $this->getJson("/api/v1/schools/{$school->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $school->id)
        ->assertJsonPath('data.name', $school->name)
        ->assertJsonStructure(['data' => ['kecamatan', 'kelurahan']]);
});

it('updates a school', function () {
    $school = School::factory()->create();

    $this->putJson("/api/v1/schools/{$school->id}", [
        'name' => 'SDN Diperbarui',
        'school_type' => 'SD',
        'kecamatan_id' => $school->kecamatan_id,
        'students_male' => 30,
        'students_female' => 30,
        'teachers' => 10,
        'classes' => 6,
        'capacity' => 200,
    ])->assertOk()
        ->assertJsonPath('data.name', 'SDN Diperbarui');

    expect($school->fresh()->name)->toBe('SDN Diperbarui');
});

it('soft-deletes a school via DELETE', function () {
    $school = School::factory()->create();

    $this->deleteJson("/api/v1/schools/{$school->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($school->fresh()->trashed())->toBeTrue();
});

it('rejects invalid coordinate values', function () {
    $kecamatan = Kecamatan::factory()->create();

    $this->postJson('/api/v1/schools', [
        'name' => 'SDN Koordinat Buruk',
        'school_type' => 'SD',
        'kecamatan_id' => $kecamatan->id,
        'latitude' => 95.5,
        'longitude' => 200,
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Validasi gagal')
        ->assertJsonStructure(['errors' => ['latitude', 'longitude']]);
});

it('rejects an invalid foreign key (kecamatan)', function () {
    $this->postJson('/api/v1/schools', [
        'name' => 'SDN Fk Salah',
        'school_type' => 'SD',
        'kecamatan_id' => 999999,
    ])->assertStatus(422)
        ->assertJsonStructure(['errors' => ['kecamatan_id']]);
});

it('rejects an invalid school type', function () {
    $kecamatan = Kecamatan::factory()->create();

    $this->postJson('/api/v1/schools', [
        'name' => 'SDN Tipe Salah',
        'school_type' => 'Perguruan Tinggi',
        'kecamatan_id' => $kecamatan->id,
    ])->assertStatus(422)
        ->assertJsonStructure(['errors' => ['school_type']]);
});
