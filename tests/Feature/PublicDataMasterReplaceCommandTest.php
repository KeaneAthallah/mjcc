<?php

use App\Console\Commands\PublicDataMasterReplaceCommand;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\Tipkamtikmas;

beforeEach(function () {
    $this->fixtures = base_path('tests/Fixtures/PublicData');

    config()->set('public_data.master.snapshots.puskesmas', $this->fixtures.'/kemkes-puskesmas.json');
    config()->set('public_data.master.snapshots.markets', $this->fixtures.'/sp2kp-markets.json');

    $this->bungkuTengah = Kecamatan::factory()->create(['name' => 'Bungku Tengah', 'code' => '72.06.05']);

    // Placeholder rows as they exist right after seeder (source null).
    $this->seedHealth = HealthFacility::factory()->create([
        'name' => 'Puskesmas Lembo Indah',
        'facility_type' => HealthFacility::TYPE_PUSKESMAS,
        'status' => 'aktif',
    ]);
    HealthFacility::factory()->create([
        'name' => 'Posyandu Bungku Jaya',
        'facility_type' => HealthFacility::TYPE_POSYANDU,
        'status' => 'aktif',
    ]);
    $this->seedPolsek = Polsek::factory()->create(['name' => 'Polsek Bungku Tengah', 'status' => 'aktif']);
    $this->seedPoskamling = Poskamling::factory()->create(['name' => 'Poskamling Matano 1', 'is_active' => true, 'status' => 'aktif']);
    $this->seedTipkamtikmas = Tipkamtikmas::factory()->create(['title' => 'Tipkamtikmas Matano 1', 'status' => 'aktif']);
    $this->seedMarket = Market::factory()->create(['name' => 'Pasar Lembo 1', 'status' => 'aktif']);
});

it('imports the real snapshots and retires the seeded placeholders', function () {
    $this->artisan(PublicDataMasterReplaceCommand::class)
        ->assertExitCode(0);

    // Puskesmas nyata.
    $bahodopi = HealthFacility::where('name', 'Puskesmas Bahodopi')->first();
    expect($bahodopi)->not->toBeNull()
        ->and($bahodopi->facility_type)->toBe(HealthFacility::TYPE_PUSKESMAS)
        ->and($bahodopi->doctors)->toBe(4)
        ->and($bahodopi->nurses)->toBe(35)
        ->and($bahodopi->midwives)->toBe(51)
        ->and($bahodopi->status)->toBe('aktif')
        ->and($bahodopi->source)->toBe('kemkes')
        ->and($bahodopi->description)->toBe('Tidak Terpencil Rawat Inap');

    expect(HealthFacility::where('name', 'Puskesmas La\'antula Jaya')->exists())->toBeTrue();

    // Yang dummy dinonaktifkan.
    expect($this->seedHealth->fresh()->status)->toBe('tidak aktif')
        ->and(HealthFacility::where('name', 'Posyandu Bungku Jaya')->value('status'))->toBe('tidak aktif');

    // Pasar nyata + dummy pasar nonaktif.
    $market = Market::where('name', 'Pasar Rakyat Bungku Tengah')->first();
    expect($market)->not->toBeNull()
        ->and($market->kecamatan_id)->toBe($this->bungkuTengah->id)
        ->and((float) $market->latitude)->toBe(-2.5674091)
        ->and((float) $market->longitude)->toBe(121.8520499)
        ->and($market->source)->toBe('sp2kp')
        ->and($market->status)->toBe('aktif');

    expect($this->seedMarket->fresh()->status)->toBe('tidak aktif');

    // Polsek tetap aktif, ditandai manual.
    expect($this->seedPolsek->fresh()->status)->toBe('aktif')
        ->and($this->seedPolsek->fresh()->source)->toBe('manual');

    // Poskamling & tipkamtikmas dummy nonaktif.
    expect($this->seedPoskamling->fresh()->is_active)->toBeFalse()
        ->and($this->seedPoskamling->fresh()->status)->toBe('tidak aktif')
        ->and($this->seedTipkamtikmas->fresh()->status)->toBe('tidak aktif');
});

it('is idempotent on a repeated run', function () {
    $this->artisan(PublicDataMasterReplaceCommand::class)->assertExitCode(0);
    $this->artisan(PublicDataMasterReplaceCommand::class)->assertExitCode(0);

    expect(HealthFacility::where('source', 'kemkes')->count())->toBe(3)
        ->and(Market::where('source', 'sp2kp')->count())->toBe(1)
        ->and($this->seedHealth->fresh()->status)->toBe('tidak aktif');
});

it('never retires CRUD rows created after the first import', function () {
    $this->artisan(PublicDataMasterReplaceCommand::class)->assertExitCode(0);

    $manual = HealthFacility::factory()->create([
        'name' => 'Posyandu Desa Matano',
        'facility_type' => HealthFacility::TYPE_POSYANDU,
        'status' => 'aktif',
    ]);

    $this->artisan(PublicDataMasterReplaceCommand::class)->assertExitCode(0);

    expect($manual->fresh()->status)->toBe('aktif')
        ->and($manual->fresh()->source)->toBeNull();
});

it('fails when a snapshot file is missing', function () {
    config()->set('public_data.master.snapshots.markets', storage_path('app/data/tidak-ada.json'));

    $this->artisan(PublicDataMasterReplaceCommand::class)
        ->expectsOutputToContain('Snapshot tidak ditemukan')
        ->assertExitCode(1);
});

it('assigns no kecamatan to puskesmas without region data', function () {
    $this->artisan(PublicDataMasterReplaceCommand::class)->assertExitCode(0);

    expect(HealthFacility::where('source', 'kemkes')->whereNull('kecamatan_id')->count())->toBe(3);
});
