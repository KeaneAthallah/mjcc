<?php

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;
use App\Services\CommandCenterStatusService;

beforeEach(function () {
    $this->service = app(CommandCenterStatusService::class);
});

it('reports no data when there are no records to score', function () {
    $result = $this->service->overall(force: true);

    expect($result['status'])->toBe('tidak_ada_data')
        ->and($result['score'])->toBeNull()
        ->and($result['no_data'])->toBeTrue()
        ->and($result['sectors'])->toBe([
            'pendidikan' => 'tidak_ada_data',
            'ketertiban' => 'tidak_ada_data',
            'kesehatan' => 'tidak_ada_data',
        ]);
});

it('scores the pendidikan sector from real school data', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);

    School::factory()->count(4)->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'baik',
        'teachers' => 12,
        'library_percentage' => 100,
        'science_lab_percentage' => 100,
        'computer_lab_percentage' => 100,
        'teacher_room_percentage' => 100,
        'toilet_percentage' => 100,
        'worship_room_percentage' => 100,
    ]);

    $sector = $this->service->sector('pendidikan', config('command-center.sectors.pendidikan'));

    expect($sector['score'])->toBe(100.0)
        ->and($sector['status'])->toBe('baik');
});

it('drops benefits from rules whose data is absent', function () {
    // Hanya 1 sekolah dengan guru; aturan lain tidak memiliki data yang
    // berbeda, sehingga skor mengikuti ketersediaan guru.
    $kecamatan = Kecamatan::factory()->create();
    School::factory()->create(['kecamatan_id' => $kecamatan->id, 'condition' => 'baik', 'teachers' => 5]);

    $sector = $this->service->sector('pendidikan', config('command-center.sectors.pendidikan'));

    expect($sector['score'])->toBeGreaterThan(0)
        ->and($sector['score'])->toBeLessThanOrEqual(100);
});

it('subscores aggregate the weight of only available rules', function () {
    // Tanpa data apa pun untuk ketertiban/kesehatan, keseluruhan hanya
    // dihitung dari sektor pendidikan (bobot dinormalisasi ke 100).
    $kecamatan = Kecamatan::factory()->create();
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'condition' => 'baik',
        'teachers' => 10,
    ]);

    $result = $this->service->overall(force: true);

    expect($result['no_data'])->toBeFalse()
        ->and($result['score'])->toBeGreaterThan(0)
        ->and($result['sectors'])->toHaveCount(3)
        ->and(collect($result['sectors'])->filter(fn ($s) => $s === 'baik')->count())->toBeGreaterThanOrEqual(1);
});

it('marks the kabupaten as kritis in the worst case', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);

    // Semua data keamanan/kesehatan buruk.
    Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'is_active' => false]);
    Tipkamtikmas::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'nonaktif']);
    Polsek::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'nonaktif']);
    Market::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'nonaktif']);
    HealthFacility::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'nonaktif', 'doctors' => 0, 'nurses' => 0, 'midwives' => 0, 'condition' => 'rusak berat']);

    $result = $this->service->overall(force: true);

    expect($result['score'])->toBeLessThan(50)
        ->and($result['status'])->toBe('kritis');
});

it('answers for a healthy dataset with baik status', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);

    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'condition' => 'baik',
        'teachers' => 20,
        'library_percentage' => 90,
        'science_lab_percentage' => 90,
        'computer_lab_percentage' => 90,
        'teacher_room_percentage' => 90,
        'toilet_percentage' => 90,
        'worship_room_percentage' => 90,
    ]);
    Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id, 'is_active' => true]);
    Tipkamtikmas::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'aktif']);
    Polsek::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'aktif']);
    Market::factory()->create(['kecamatan_id' => $kecamatan->id, 'status' => 'aktif']);
    HealthFacility::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'status' => 'aktif',
        'doctors' => 3,
        'nurses' => 4,
        'midwives' => 2,
        'condition' => 'baik',
    ]);

    $result = $this->service->overall(force: true);

    expect($result['status'])->toBe('baik')
        ->and($result['score'])->toBeGreaterThanOrEqual(85);
});

it('exposes label and per-sector detail for the UI', function () {
    $result = $this->service->overall(force: true);

    expect($result)->toHaveKeys(['status', 'label', 'score', 'sectors', 'sector_details', 'no_data'])
        ->and($result['sector_details'])->toHaveCount(3)
        ->and($result['sector_details']['pendidikan'])->toHaveKeys(['key', 'label', 'score', 'status', 'rules']);
});
