<?php

use App\Console\Commands\PublicDataMasterPurgeCommand;
use App\Models\HealthFacility;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;

beforeEach(function () {
    $this->seedHealth = HealthFacility::factory()->create([
        'name' => 'Posyandu Bungku Jaya',
        'facility_type' => HealthFacility::TYPE_POSYANDU,
        'status' => 'tidak aktif',
        'source' => 'seed',
    ]);
    $this->seedMarket = Market::factory()->create([
        'name' => 'Pasar Lembo 1',
        'status' => 'tidak aktif',
        'source' => 'seed',
    ]);
    $this->seedPoskamling = Poskamling::factory()->create([
        'name' => 'Poskamling Matano 1',
        'is_active' => false,
        'status' => 'tidak aktif',
        'source' => 'seed',
    ]);
    $this->seedTipkamtikmas = Tipkamtikmas::factory()->create([
        'title' => 'Tipkamtikmas Matano 1',
        'status' => 'tidak aktif',
        'source' => 'seed',
    ]);
    $this->seedSchool = School::factory()->create(['is_active' => false]);

    $this->realHealth = HealthFacility::factory()->create([
        'name' => 'Puskesmas Bahodopi',
        'facility_type' => HealthFacility::TYPE_PUSKESMAS,
        'status' => 'aktif',
        'source' => 'kemkes',
    ]);
    $this->crudHealth = HealthFacility::factory()->create([
        'name' => 'Posyandu Desa Karatungan',
        'facility_type' => HealthFacility::TYPE_POSYANDU,
        'status' => 'aktif',
    ]);
    $this->realMarket = Market::factory()->create([
        'name' => 'Pasar Rakyat Bungku Tengah',
        'status' => 'aktif',
        'source' => 'sp2kp',
    ]);
    $this->realSchool = School::factory()->create(['source' => 'dapodik']);
    $this->crudSchool = School::factory()->create();
    $this->manualPolsek = Polsek::factory()->create(['status' => 'aktif', 'source' => 'manual']);
});

it('force deletes every seed row and nothing else', function () {
    $this->artisan(PublicDataMasterPurgeCommand::class)->assertExitCode(0);

    expect(HealthFacility::where('source', 'seed')->exists())->toBeFalse()
        ->and(HealthFacility::withTrashed()->where('id', $this->seedHealth->id)->exists())->toBeFalse()
        ->and(Market::withTrashed()->where('id', $this->seedMarket->id)->exists())->toBeFalse()
        ->and(Poskamling::withTrashed()->where('id', $this->seedPoskamling->id)->exists())->toBeFalse()
        ->and(Tipkamtikmas::withTrashed()->where('id', $this->seedTipkamtikmas->id)->exists())->toBeFalse()
        ->and(School::withTrashed()->where('id', $this->seedSchool->id)->exists())->toBeFalse();

    expect($this->realHealth->fresh())->not->toBeNull()
        ->and($this->crudHealth->fresh())->not->toBeNull()
        ->and($this->realMarket->fresh())->not->toBeNull()
        ->and($this->realSchool->fresh())->not->toBeNull()
        ->and($this->crudSchool->fresh())->not->toBeNull()
        ->and($this->manualPolsek->fresh())->not->toBeNull();
});

it('is idempotent on a repeated run', function () {
    $this->artisan(PublicDataMasterPurgeCommand::class)->assertExitCode(0);
    $this->artisan(PublicDataMasterPurgeCommand::class)->assertExitCode(0);

    $this->artisan(PublicDataMasterPurgeCommand::class)
        ->expectsOutputToContain('0 baris data dummy dihapus permanen.');

    expect(HealthFacility::where('source', 'seed')->count())->toBe(0)
        ->and(School::whereNull('source')->where('is_active', false)->count())->toBe(0);
});
