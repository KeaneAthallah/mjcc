<?php

use App\Console\Commands\PublicDataMasterRegionCleanupCommand;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Polsek;
use App\Models\School;

it('removes kecamatan, kelurahan, polsek and schools outside Morowali', function () {
    $morowali = Kecamatan::factory()->create(['name' => 'Bungku Tengah']);
    $kelurahanMorowali = Kelurahan::factory()->create(['kecamatan_id' => $morowali->id, 'name' => 'Matano']);
    $polsekMorowali = Polsek::factory()->create(['kecamatan_id' => $morowali->id, 'name' => 'Polsek Bungku Tengah']);
    $schoolMorowali = School::factory()->create(['kecamatan_id' => $morowali->id, 'kelurahan_id' => $kelurahanMorowali->id]);

    $morut = Kecamatan::factory()->create(['name' => 'Petasia']);
    $kelurahanMorut = Kelurahan::factory()->create(['kecamatan_id' => $morut->id, 'name' => 'Beteleme']);
    $polsekMorut = Polsek::factory()->create(['kecamatan_id' => $morut->id, 'name' => 'Polsek Petasia']);
    $schoolMorut = School::factory()->create(['kecamatan_id' => $morut->id, 'kelurahan_id' => $kelurahanMorut->id]);

    $this->artisan(PublicDataMasterRegionCleanupCommand::class)
        ->expectsOutputToContain('kecamatan dihapus: 1')
        ->expectsOutputToContain('kelurahan dihapus: 1')
        ->expectsOutputToContain('polsek dihapus: 1')
        ->expectsOutputToContain('sekolah dihapus: 1')
        ->assertExitCode(0);

    expect(Kecamatan::where('name', 'Petasia')->exists())->toBeFalse()
        ->and(Kelurahan::where('id', $kelurahanMorut->id)->exists())->toBeFalse()
        ->and(Polsek::where('id', $polsekMorut->id)->exists())->toBeFalse()
        ->and(School::where('id', $schoolMorut->id)->exists())->toBeFalse();

    expect(Kecamatan::where('name', 'Bungku Tengah')->exists())->toBeTrue()
        ->and(Kelurahan::where('id', $kelurahanMorowali->id)->exists())->toBeTrue()
        ->and(Polsek::where('id', $polsekMorowali->id)->exists())->toBeTrue()
        ->and(School::where('id', $schoolMorowali->id)->exists())->toBeTrue();
});

it('is idempotent when nothing is out of scope', function () {
    Kecamatan::factory()->create(['name' => 'Bahodopi']);

    $this->artisan(PublicDataMasterRegionCleanupCommand::class)->assertExitCode(0);
    $this->artisan(PublicDataMasterRegionCleanupCommand::class)->assertExitCode(0);

    expect(Kecamatan::where('name', 'Bahodopi')->exists())->toBeTrue();
});
