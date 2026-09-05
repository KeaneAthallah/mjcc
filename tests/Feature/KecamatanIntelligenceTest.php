<?php

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\User;
use App\Services\KecamatanIntelligenceService;

beforeEach(function () {
    $this->intelligence = app(KecamatanIntelligenceService::class);
});

it('aggregates operational counts per kecamatan', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'population' => 15000]);
    Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'population' => 5000]);

    School::factory()->count(2)->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'baik',
        'teachers' => 10,
        'students_male' => 50,
        'students_female' => 50,
    ]);
    HealthFacility::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'aktif', 'doctors' => 2, 'nurses' => 3, 'midwives' => 1]);
    Poskamling::factory()->count(3)->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'is_active' => true]);

    $row = $this->intelligence->forKecamatan($kecamatan, force: true);

    expect($row['counts']['sekolah'])->toBe(2)
        ->and($row['counts']['siswa'])->toBe(200)
        ->and($row['counts']['guru'])->toBe(20)
        ->and($row['counts']['faskes'])->toBe(1)
        ->and($row['counts']['dokter'])->toBe(2)
        ->and($row['counts']['poskamling'])->toBe(3)
        ->and($row['counts']['poskamling_aktif'])->toBe(3)
        ->and($row['kecamatan']['kelurahan'])->toBe(2)
        ->and($row['kecamatan']['population'])->toBe(20000);
});

it('scores kecamatans separately from the kabupaten-wide status', function () {
    $good = Kecamatan::factory()->create();
    $bad = Kecamatan::factory()->create();

    School::factory()->create(['kecamatan_id' => $good->id, 'condition' => 'baik', 'teachers' => 15]);
    Poskamling::factory()->create(['kecamatan_id' => $good->id, 'is_active' => true]);

    School::factory()->create(['kecamatan_id' => $bad->id, 'condition' => 'rusak berat', 'teachers' => 0]);
    Poskamling::factory()->create(['kecamatan_id' => $bad->id, 'is_active' => false]);

    $goodRow = $this->intelligence->forKecamatan($good, force: true);
    $badRow = $this->intelligence->forKecamatan($bad, force: true);

    expect($goodRow['status']['score'])->toBeGreaterThan($badRow['status']['score']);
});

it('ranks kecamatans by descending score', function () {
    $good = Kecamatan::factory()->create(['name' => 'Good']);
    $bad = Kecamatan::factory()->create(['name' => 'Bad']);
    $goodKelurahan = Kelurahan::factory()->create(['kecamatan_id' => $good->id]);
    $badKelurahan = Kelurahan::factory()->create(['kecamatan_id' => $bad->id]);

    School::factory()->create(['kecamatan_id' => $good->id, 'kelurahan_id' => $goodKelurahan->id, 'condition' => 'baik', 'teachers' => 15]);
    School::factory()->create(['kecamatan_id' => $bad->id, 'kelurahan_id' => $badKelurahan->id, 'condition' => 'rusak berat', 'teachers' => 0]);

    $rows = $this->intelligence->all(force: true);

    expect($rows->first()['kecamatan']['name'])->toBe('Good')
        ->and($rows->last()['kecamatan']['name'])->toBe('Bad')
        ->and($rows)->toHaveCount(2);
});

it('renders the intelligence ranking page', function () {
    $user = User::factory()->admin()->create();
    $kecamatan = Kecamatan::factory()->create();

    $this->actingAs($user)
        ->get(route('kecamatan.overview'))
        ->assertOk()
        ->assertSee($kecamatan->name);
});

it('renders a kecamatan profile with operational status', function () {
    $user = User::factory()->operator()->create();
    $kecamatan = Kecamatan::factory()->create();
    School::factory()->create(['kecamatan_id' => $kecamatan->id, 'condition' => 'baik', 'teachers' => 12]);

    $this->actingAs($user)
        ->get(route('kecamatan.show', $kecamatan))
        ->assertOk()
        ->assertSee($kecamatan->name)
        ->assertSee('Status Sektor');
});
