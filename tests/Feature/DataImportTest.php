<?php

use App\Models\DataImportLog;
use App\Models\ExternalData;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\School;
use App\Models\User;
use App\Services\DataImport\DataImportService;
use App\Services\EducationDashboardService;
use App\Services\PublicData\PublicDataScraper;

/**
 * Build one normalized long-format external-data record the way DataNormalizer
 * would: the raw row keeps the full entity row so entity importers can rebuild
 * it, and $location is whatever the normalizer promoted to the location column.
 */
function entityExternalData(
    string $sector,
    string $dataset,
    array $headers,
    array $cells,
    string $location,
    ?int $year,
    string $indicator,
    float $value,
): ExternalData {
    return ExternalData::create([
        'sector' => $sector,
        'source' => 'satudata',
        'source_url' => 'https://data.morowalikab.go.id/dataset/detail/'.fake()->md5(),
        'dataset' => $dataset,
        'topic' => 'Bidang '.ucfirst($sector),
        'year' => $year,
        'location' => $location,
        'indicator' => $indicator,
        'value' => $value,
        'dedupe_key' => ExternalData::dedupeKey($sector, 'satudata', $dataset, $year, $location, $indicator),
        'raw_data' => ['headers' => $headers, 'row' => $cells],
        'scraped_at' => now(),
    ]);
}

it('imports a per-entity school dataset into the schools master table', function () {
    $dataset = 'Data Jumlah Siswa di Sekolah Menurut Sekolah Kabupaten Morowali';
    $headers = ['KODE SEKOLAH', 'NAMA SEKOLAH', 'TAHUN', 'JUMLAH SISWA'];

    entityExternalData('pendidikan', $dataset, $headers, ['101', 'SDN 1 Bahodopi', '2024', '320'], 'SDN 1 Bahodopi', 2024, 'JUMLAH SISWA', 320);
    entityExternalData('pendidikan', $dataset, $headers, ['102', 'SMPN 1 Wita Ponda', '2024', '150'], 'SMPN 1 Wita Ponda', 2024, 'JUMLAH SISWA', 150);

    $result = app(DataImportService::class)->run('pendidikan')[0];

    expect($result['status'])->toBe(DataImportLog::STATUS_SUCCESS)
        ->and($result['entity_datasets'])->toBe(1)
        ->and($result['entities_created'])->toBe(2)
        ->and($result['entities_skipped'])->toBe(0);

    $sd = School::where('name', 'SDN 1 Bahodopi')->first();
    $smp = School::where('name', 'SMPN 1 Wita Ponda')->first();

    expect($sd)->not->toBeNull()
        ->and($sd->school_type)->toBe(School::TYPE_SD)
        ->and($sd->kecamatan->name)->toBe('Bahodopi')
        ->and(round((float) $sd->latitude, 3))->toBe(-2.796)
        ->and($smp)->not->toBeNull()
        ->and($smp->school_type)->toBe(School::TYPE_SMP)
        ->and($smp->kecamatan->name)->toBe('Wita Ponda');

    expect(Kecamatan::where('name', 'Bahodopi')->exists())->toBeTrue()
        ->and(Kecamatan::where('name', 'Wita Ponda')->exists())->toBeTrue();

    $log = DataImportLog::latest('id')->first();

    expect($log->status)->toBe(DataImportLog::STATUS_SUCCESS)
        ->and($log->sector)->toBe('pendidikan')
        ->and($log->entities_created)->toBe(2)
        ->and($log->kecamatan_created)->toBeGreaterThanOrEqual(2)
        ->and($log->finished_at)->not->toBeNull();
});

it('re-importing the same data updates instead of duplicating', function () {
    $dataset = 'Data Jumlah Siswa di Sekolah';
    $headers = ['KODE', 'NAMA SEKOLAH', 'JUMLAH SISWA'];

    entityExternalData('pendidikan', $dataset, $headers, ['1', 'SDN 1 Bahodopi', '320'], 'SDN 1 Bahodopi', 2024, 'JUMLAH SISWA', 320);

    $first = app(DataImportService::class)->run('pendidikan')[0];
    $second = app(DataImportService::class)->run('pendidikan')[0];

    expect(School::count())->toBe(1)
        ->and($first['entities_created'])->toBe(1)
        ->and($second['entities_created'])->toBe(0)
        ->and($second['entities_updated'])->toBe(1);

    expect(DataImportLog::count())->toBe(2);
});

it('feeds the existing education dashboard from imported schools', function () {
    entityExternalData('pendidikan', 'Jumlah Siswa', ['KODE', 'NAMA SEKOLAH', 'JUMLAH SISWA'], ['1', 'SDN 1 Bahodopi', '320'], 'SDN 1 Bahodopi', 2024, 'JUMLAH SISWA', 320);

    app(DataImportService::class)->run('pendidikan');

    $stats = (new EducationDashboardService)->statistics();

    expect($stats['total_sd'])->toBe(1)
        ->and($stats['total_smp'])->toBe(0);
});

it('only fills kecamatan master rows from aggregate statistics', function () {
    $headers = ['KODE', 'KECAMATAN', 'JUMLAH SARANA PENDIDIKAN'];

    entityExternalData('pendidikan', 'Jumlah Sarana Pendidikan Menurut Kecamatan', $headers, ['1', 'Bahodopi', '20'], 'Bahodopi', 2024, 'JUMLAH SARANA PENDIDIKAN', 20);
    entityExternalData('pendidikan', 'Jumlah Sarana Pendidikan Menurut Kecamatan', $headers, ['2', 'Wita Ponda', '14'], 'Wita Ponda', 2024, 'JUMLAH SARANA PENDIDIKAN', 14);

    $result = app(DataImportService::class)->run('pendidikan')[0];

    expect(School::count())->toBe(0)
        ->and($result['entity_datasets'])->toBe(0)
        ->and(Kecamatan::where('name', 'Bahodopi')->exists())->toBeTrue()
        ->and(Kecamatan::where('name', 'Wita Ponda')->exists())->toBeTrue()
        ->and($result['kecamatan_created'])->toBe(2);
});

it('never creates a kecamatan for regency-level aggregate rows', function () {
    entityExternalData('pendidikan', 'Rekapitulasi Kabupaten', ['TAHUN', 'JUMLAH SISWA'], ['2024', '1000'], 'Kabupaten Morowali', 2024, 'JUMLAH SISWA', 1000);

    app(DataImportService::class)->run('pendidikan');

    expect(Kecamatan::count())->toBe(0);
});

it('skips entity rows whose location cannot be verified', function () {
    $dataset = 'Data Jumlah Siswa di Sekolah Menurut Sekolah';
    $headers = ['KODE', 'NAMA SEKOLAH', 'JUMLAH SISWA'];

    entityExternalData('pendidikan', $dataset, $headers, ['1', 'SDN 1 Bungintende', '320'], 'SDN 1 Bungintende', 2023, 'JUMLAH SISWA', 320);
    entityExternalData('pendidikan', $dataset, $headers, ['2', 'SDN 2 Bahomohoni', '150'], 'SDN 2 Bahomohoni', 2023, 'JUMLAH SISWA', 150);

    $result = app(DataImportService::class)->run('pendidikan')[0];

    expect($result['status'])->toBe(DataImportLog::STATUS_SUCCESS)
        ->and($result['entities_skipped'])->toBe(2)
        ->and(School::count())->toBe(0)
        ->and(Kecamatan::where('name', 'Bungintende')->exists())->toBeFalse();
});

it('does not treat aggregate total rows as real entities', function () {
    $headers = ['NAMA SEKOLAH', 'JUMLAH SISWA'];

    entityExternalData('pendidikan', 'Jumlah Siswa', $headers, ['SDN 1 Bahodopi', '320'], 'SDN 1 Bahodopi', 2024, 'JUMLAH SISWA', 320);
    entityExternalData('pendidikan', 'Jumlah Siswa', $headers, ['JUMLAH', '470'], 'JUMLAH', 2024, 'JUMLAH SISWA', 470);

    $result = app(DataImportService::class)->run('pendidikan')[0];

    expect($result['entities_created'])->toBe(1)
        ->and(School::where('name', 'JUMLAH')->exists())->toBeFalse();
});

it('creates health facilities even without a verifiable kecamatan', function () {
    $headers = ['KODE', 'NAMA PUSKESMAS', 'TAHUN', 'JANGKAUAN (PERSEN)'];

    entityExternalData('kesehatan', 'Akses Penduduk terhadap Puskesmas', $headers, ['11', 'Puskesmas Bungintende', '2023', '45,5'], 'Puskesmas Bungintende', 2023, 'JANGKAUAN (PERSEN)', 45.5);

    $result = app(DataImportService::class)->run('kesehatan')[0];

    $facility = HealthFacility::where('name', 'Puskesmas Bungintende')->first();

    expect($result['entities_created'])->toBe(1)
        ->and($facility)->not->toBeNull()
        ->and($facility->facility_type)->toBe(HealthFacility::TYPE_PUSKESMAS)
        ->and($facility->kecamatan_id)->toBeNull()
        ->and($facility->latitude)->toBeNull();
});

it('rejects placeholder coordinates and falls back to the kecamatan centroid', function () {
    $headers = ['NAMA PUSKESMAS', 'LATITUDE', 'LONGITUDE', 'JANGKAUAN (PERSEN)'];

    entityExternalData('kesehatan', 'Akses Puskesmas', $headers, ['Puskesmas Bahodopi', '0', '0', '62'], 'Puskesmas Bahodopi', 2023, 'JANGKAUAN (PERSEN)', 62);

    $result = app(DataImportService::class)->run('kesehatan')[0];

    $facility = HealthFacility::where('name', 'Puskesmas Bahodopi')->first();

    expect($result['entities_created'])->toBe(1)
        ->and($facility->kecamatan->name)->toBe('Bahodopi')
        ->and(round((float) $facility->latitude, 3))->toBe(-2.796)
        ->and(round((float) $facility->longitude, 3))->toBe(122.127);
});

it('a single malformed record never aborts the whole run', function () {
    $good = entityExternalData('pendidikan', 'Jumlah Siswa', ['NAMA SEKOLAH', 'JUMLAH SISWA'], ['SDN 1 Bahodopi', '320'], 'SDN 1 Bahodopi', 2024, 'JUMLAH SISWA', 320);

    ExternalData::create([
        'sector' => 'pendidikan',
        'source' => 'satudata',
        'source_url' => $good->source_url,
        'dataset' => 'Jumlah Siswa',
        'topic' => 'Bidang Pendidikan',
        'year' => 2024,
        'location' => 'SDN 9 Hilang Kolom',
        'indicator' => 'JUMLAH SISWA',
        'value' => 999,
        'dedupe_key' => ExternalData::dedupeKey('pendidikan', 'satudata', 'Jumlah Siswa', 2024, 'SDN 9 Hilang Kolom', 'JUMLAH SISWA'),
        'raw_data' => ['headers' => ['NAMA SEKOLAH', 'JUMLAH SISWA'], 'row' => []],
        'scraped_at' => now(),
    ]);

    $result = app(DataImportService::class)->run('pendidikan')[0];

    expect($result['status'])->toBe(DataImportLog::STATUS_SUCCESS)
        ->and($result['entities_created'])->toBe(1)
        ->and(School::where('name', 'SDN 1 Bahodopi')->exists())->toBeTrue();
});

it('imports scraped fixture data end-to-end without crashing', function () {
    Http::fake([
        'https://data.morowalikab.go.id/dataset/detail/pendidikan-jumlah-siswa' => Http::response(file_get_contents(base_path('tests/fixtures/satudata/detail-pendidikan.html'))),
        'https://data.morowalikab.go.id/dataset/detail/kesehatan-akses-puskesmas' => Http::response(file_get_contents(base_path('tests/fixtures/satudata/detail-kesehatan.html'))),
        'https://data.morowalikab.go.id/dataset/detail/kesbangpol' => Http::response(file_get_contents(base_path('tests/fixtures/satudata/detail-nodata.html'))),
        'https://data.morowalikab.go.id/dataset/detail/pertanian' => Http::response(file_get_contents(base_path('tests/fixtures/satudata/detail-malformed.html'))),
        'https://data.morowalikab.go.id/dataset*' => function ($request) {
            return Http::response(str_contains($request->url(), 'page=1')
                ? file_get_contents(base_path('tests/fixtures/satudata/catalog-page1.html'))
                : '<html><body></body></html>');
        },
    ]);

    app(PublicDataScraper::class)->scrape('pendidikan');

    $result = app(DataImportService::class)->run('pendidikan')[0];

    expect($result['status'])->toBe(DataImportLog::STATUS_SUCCESS)
        ->and($result['entities_skipped'])->toBe(2)
        ->and(School::count())->toBe(0);
});

it('rejects unknown sectors', function () {
    expect(fn () => app(DataImportService::class)->run('nuklir'))
        ->toThrow(RuntimeException::class);
});

it('the data:sync command imports from already-scraped data', function () {
    entityExternalData('pendidikan', 'Jumlah Siswa', ['NAMA SEKOLAH', 'JUMLAH SISWA'], ['SDN 1 Bahodopi', '320'], 'SDN 1 Bahodopi', 2024, 'JUMLAH SISWA', 320);

    $this->artisan('data:sync', ['--import-only' => true, '--sector' => 'pendidikan'])
        ->assertExitCode(0);

    expect(School::where('name', 'SDN 1 Bahodopi')->exists())->toBeTrue()
        ->and(DataImportLog::where('status', DataImportLog::STATUS_SUCCESS)->exists())->toBeTrue();
});

it('sync page requires authentication', function () {
    $this->get(route('data-import.index'))->assertRedirect(route('login'));
});

it('denies the sync page to viewers', function () {
    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('data-import.index'))
        ->assertForbidden();
});

it('lets operators open the sync page and trigger an import', function () {
    entityExternalData('pendidikan', 'Jumlah Siswa', ['NAMA SEKOLAH', 'JUMLAH SISWA'], ['SDN 1 Bahodopi', '320'], 'SDN 1 Bahodopi', 2024, 'JUMLAH SISWA', 320);

    $operator = User::factory()->operator()->create();

    $this->actingAs($operator)
        ->get(route('data-import.index'))
        ->assertOk()
        ->assertSee('Sinkronisasi Data');

    $this->actingAs($operator)
        ->post(route('data-import.run'))
        ->assertRedirect(route('data-import.index'));

    expect(DataImportLog::count())->toBe(3)
        ->and(School::where('name', 'SDN 1 Bahodopi')->exists())->toBeTrue();
});
