<?php

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\School;
use App\Services\PublicData\DapodikSchoolService;

/**
 * Writes a Dapodik snapshot fixture (the shape written by
 * scripts/dapodik/dapodik-capture.cjs) into a temporary file.
 */
function dapodikSnapshot(array $schools): string
{
    $path = tempnam(sys_get_temp_dir(), 'dapodik_').'.json';
    file_put_contents($path, (string) json_encode([
        'captured_at' => '2026-09-10 19:23:42',
        'source' => 'dapodik',
        'region_code' => '180700',
        'region_name' => 'Kabupaten Morowali',
        'semester' => '20261',
        'schools' => $schools,
    ], JSON_THROW_ON_ERROR));

    return $path;
}

function dapodikRow(array $overrides = []): array
{
    return array_replace([
        'npsn' => '40202491',
        'nama' => 'SD NEGERI SAMARENGGA',
        'bentuk_pendidikan' => 'SD',
        'status_sekolah' => 'Negeri',
        'kecamatan' => 'Kec. Menui Kepulauan',
        'desa_kelurahan' => 'Samarengga',
        'alamat_jalan' => 'Desa Samarengga',
        'lintang' => -3.3857,
        'bujur' => 122.8177,
        'pd_l' => 41,
        'pd_p' => 64,
        'jum_guru' => 9,
        'rombel' => 6,
        'ruang_kelas' => 7,
        'kelas_baik' => 4,
        'kelas_sedang' => 0,
        'kelas_berat' => 3,
        'perpus' => 1,
        'lab_ipa' => 0,
        'lab_kom' => 1,
        'r_guru' => 1,
        'wc_guru' => 0,
        'wc_siswa' => 2,
    ], $overrides);
}

function dapodikKecamatan(string $name = 'Menui Kepulauan'): Kecamatan
{
    return Kecamatan::create(['name' => $name, 'is_active' => true]);
}

it('imports every Dapodik jenjang into the schools master table', function () {
    $kecamatan = dapodikKecamatan('Menui Kepulauan');
    Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'name' => 'Samarengga']);

    $path = dapodikSnapshot([
        dapodikRow(),
        dapodikRow(['npsn' => '40202492', 'nama' => 'SMP NEGERI 1 BUNGKU', 'bentuk_pendidikan' => 'SMP']),
        dapodikRow(['npsn' => '40202494', 'nama' => 'SMA NEGERI 1 BUNGKU', 'bentuk_pendidikan' => 'SMA']),
        dapodikRow(['npsn' => '40202495', 'nama' => 'SMK NEGERI 1 BUNGKU', 'bentuk_pendidikan' => 'SMK']),
        dapodikRow(['npsn' => '40202496', 'nama' => 'SLB NEGERI BUNGKU', 'bentuk_pendidikan' => 'SLB']),
        dapodikRow(['npsn' => '40202493', 'nama' => 'TK KENCANA', 'bentuk_pendidikan' => 'TK']),
    ]);

    $result = app(DapodikSchoolService::class)->import($path);

    expect($result['created'])->toBe(5)
        ->and($result['skipped'])->toBe(1)
        ->and($result['no_kecamatan'])->toBe(0)
        ->and($result['deactivated'])->toBe(0);

    $sd = School::where('npsn', '40202491')->first();
    $sma = School::where('npsn', '40202494')->first();
    $smk = School::where('npsn', '40202495')->first();
    $slb = School::where('npsn', '40202496')->first();

    expect($sd)->not->toBeNull()
        ->and($sd->name)->toBe('SD NEGERI SAMARENGGA')
        ->and($sd->school_type)->toBe(School::TYPE_SD)
        ->and($sd->kecamatan_id)->toBe($kecamatan->id)
        ->and($sd->kelurahan)->not->toBeNull()
        ->and($sd->kelurahan->name)->toBe('Samarengga')
        ->and((float) $sd->latitude)->toBe(-3.3857)
        ->and((float) $sd->longitude)->toBe(122.8177)
        ->and($sd->students_male)->toBe(41)
        ->and($sd->students_female)->toBe(64)
        ->and($sd->teachers)->toBe(9)
        ->and($sd->classes)->toBe(6)
        ->and($sd->capacity)->toBe(7)
        ->and($sd->condition)->toBe('rusak_berat')
        ->and((float) $sd->library_percentage)->toBe(100.0)
        ->and((float) $sd->science_lab_percentage)->toBe(0.0)
        ->and((float) $sd->computer_lab_percentage)->toBe(100.0)
        ->and((float) $sd->toilet_percentage)->toBe(100.0)
        ->and($sd->source)->toBe(DapodikSchoolService::SOURCE)
        ->and($sd->is_active)->toBeTrue();

    expect($sma->school_type)->toBe(School::TYPE_SMA)
        ->and($sma->name)->toBe('SMA NEGERI 1 BUNGKU')
        ->and($smk->school_type)->toBe(School::TYPE_SMK)
        ->and($slb->school_type)->toBe(School::TYPE_SLB);

    expect(School::where('npsn', '40202493')->exists())->toBeFalse();
});

it('re-importing the same snapshot updates instead of duplicating', function () {
    dapodikKecamatan('Menui Kepulauan');
    $path = dapodikSnapshot([dapodikRow()]);

    app(DapodikSchoolService::class)->import($path);
    $second = app(DapodikSchoolService::class)->import($path);

    expect($second['created'])->toBe(0)
        ->and($second['updated'])->toBe(1)
        ->and(School::count())->toBe(1);
});

it('resolves the Sambori Kepulauan alias to the canonical kecamatan', function () {
    $kecamatan = dapodikKecamatan('Sombori Kepulauan');

    $path = dapodikSnapshot([
        dapodikRow(['kecamatan' => 'Kec. Sambori Kepulauan', 'nama' => 'SDN 1 SAMBORI']),
    ]);

    app(DapodikSchoolService::class)->import($path);

    expect(School::first()->kecamatan_id)->toBe($kecamatan->id);
});

it('deactivates placeholder schools of any jenjang absent from the snapshot with replace', function () {
    dapodikKecamatan('Menui Kepulauan');
    dapodikKecamatan('Bungku Tengah');

    $placeholder = School::create([
        'name' => 'SDN 1 Bungku Tengah',
        'school_type' => School::TYPE_SD,
        'kecamatan_id' => Kecamatan::where('name', 'Bungku Tengah')->first()->id,
        'is_active' => true,
    ]);
    $placeholderSma = School::create([
        'name' => 'SMAN 1 Bungku Tengah',
        'school_type' => School::TYPE_SMA,
        'kecamatan_id' => Kecamatan::where('name', 'Bungku Tengah')->first()->id,
        'is_active' => true,
    ]);
    $otherSource = School::create([
        'name' => 'SDN 2 Bungku Tengah',
        'school_type' => School::TYPE_SD,
        'kecamatan_id' => Kecamatan::where('name', 'Bungku Tengah')->first()->id,
        'source' => 'inggrid',
        'is_active' => true,
    ]);

    $path = dapodikSnapshot([dapodikRow()]);

    $result = app(DapodikSchoolService::class)->import($path, true);

    expect($result['deactivated'])->toBe(2)
        ->and($placeholder->fresh()->is_active)->toBeFalse()
        ->and($placeholderSma->fresh()->is_active)->toBeFalse()
        ->and($otherSource->fresh()->source)->toBe('inggrid')
        ->and($otherSource->fresh()->is_active)->toBeTrue();
});

it('fails when the snapshot file is missing or invalid', function () {
    $service = app(DapodikSchoolService::class);

    $service->import(storage_path('app/data/dapodik/does-not-exist.json'));

    expect(true)->toBeTrue();
})->throws(InvalidArgumentException::class);

it('fails when the snapshot JSON misses the schools key', function () {
    $path = tempnam(sys_get_temp_dir(), 'dapodik_').'.json';
    file_put_contents($path, (string) json_encode(['region_name' => 'Kabupaten Morowali']));

    app(DapodikSchoolService::class)->import($path);
})->throws(InvalidArgumentException::class);

it('reports the import result through the artisan command', function () {
    dapodikKecamatan('Menui Kepulauan');
    $path = dapodikSnapshot([dapodikRow()]);

    $this->artisan('public-data:dapodik-import', ['file' => $path])
        ->expectsOutputToContain('Berhasil: 1 baru, 0 diperbarui')
        ->assertExitCode(0);

    expect(School::where('source', DapodikSchoolService::SOURCE)->count())->toBe(1);
});

it('keeps two real schools apart when they share a name but have different npsn', function () {
    dapodikKecamatan('Menui Kepulauan');

    $path = dapodikSnapshot([
        dapodikRow(['npsn' => '40204551', 'nama' => 'SD NEGERI UMBELE']),
        dapodikRow(['npsn' => '40204545', 'nama' => 'SD NEGERI UMBELE']),
    ]);

    $first = app(DapodikSchoolService::class)->import($path);

    expect($first['created'])->toBe(2)
        ->and(School::where('npsn', '40204551')->exists())->toBeTrue()
        ->and(School::where('npsn', '40204545')->exists())->toBeTrue()
        ->and(School::whereRaw('LOWER(name) = ?', ['sd negeri umbele'])->count())->toBe(2);
});

it('reports a missing snapshot through the artisan command', function () {
    $this->artisan('public-data:dapodik-import', ['file' => storage_path('app/data/dapodik/none.json')])
        ->expectsOutputToContain('Snapshot Dapodik tidak ditemukan')
        ->assertExitCode(1);
});
