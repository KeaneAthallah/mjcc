<?php

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\School;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    Sanctum::actingAs($this->user);
});

it('returns the overview dashboard with stats, charts and alerts', function () {
    $kecamatan = Kecamatan::factory()->create();
    School::factory()->count(3)->create(['kecamatan_id' => $kecamatan->id]);

    $this->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'stats' => [
                    'total_sekolah', 'total_siswa', 'total_guru', 'kecamatan', 'kelurahan',
                ],
                'comparison' => ['labels', 'datasets'],
                'infra_composition' => ['labels', 'data'],
                'student_chart' => ['labels', 'datasets'],
                'health_workforce_chart' => ['labels', 'data'],
                'top_schools',
                'top_poskamling',
                'top_health',
                'alerts' => ['critical', 'warning', 'info'],
            ],
        ])
        ->assertJsonPath('data.stats.total_sekolah', 3);
});

it('returns the education dashboard statistics', function () {
    School::factory()->sd()->create(['students_male' => 20, 'students_female' => 10]);

    $this->getJson('/api/v1/dashboard/education')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'statistics' => ['total_sd', 'total_smp', 'siswa_laki', 'siswa_perempuan', 'guru'],
                'student_per_kecamatan' => ['labels', 'datasets'],
                'teacher_ratio' => ['labels', 'data'],
                'facility_progress',
                'table',
            ],
        ])
        ->assertJsonPath('data.statistics.total_sd', 1)
        ->assertJsonPath('data.statistics.siswa_laki', 20);
});

it('returns the security dashboard statistics', function () {
    $this->getJson('/api/v1/dashboard/security')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'statistics' => ['kelurahan', 'polsek', 'tipkamtikmas', 'poskamling', 'pasar'],
                'compare_chart' => ['labels', 'datasets'],
                'poskamling_distribution' => ['labels', 'data'],
                'kelurahan_per_kecamatan' => ['labels', 'data'],
                'polseks',
            ],
        ]);
});

it('returns the health dashboard statistics', function () {
    HealthFacility::factory()->create(['facility_type' => 'Puskesmas', 'doctors' => 5]);

    $this->getJson('/api/v1/dashboard/health')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'statistics' => ['puskesmas', 'pustu', 'rs', 'posyandu', 'dokter', 'perawat', 'bidan'],
                'workforce_per_kecamatan' => ['labels', 'datasets'],
                'facility_proportion' => ['labels', 'data'],
                'capacity_per_kecamatan' => ['labels', 'data'],
                'table',
            ],
        ])
        ->assertJsonPath('data.statistics.puskesmas', 1)
        ->assertJsonPath('data.statistics.dokter', 5);
});

it('scopes dashboard endpoints by kecamatan_id', function () {
    $kecamatanA = Kecamatan::factory()->create();
    $kecamatanB = Kecamatan::factory()->create();

    School::factory()->sd()->create(['kecamatan_id' => $kecamatanA->id]);
    School::factory()->create(['kecamatan_id' => $kecamatanB->id]);

    $this->getJson("/api/v1/dashboard/education?kecamatan_id={$kecamatanA->id}")
        ->assertOk()
        ->assertJsonPath('data.statistics.total_sd', 1);
});
