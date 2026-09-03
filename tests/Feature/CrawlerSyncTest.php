<?php

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Services\Crawlers\CrawlerManager;
use App\Services\Crawlers\CrawlerSyncService;
use App\Support\Crawl\CrawlResult;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function syncSource(string $slug): CrawlResult
{
    return app(CrawlerManager::class)->syncSource($slug);
}

beforeEach(function () {
    config(['crawler.enabled' => true]);
    app(CrawlerManager::class)->seedSources();
});

it('creates only records for the two target regions', function () {
    $service = app(CrawlerSyncService::class);
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    $run = CrawlRun::create(['crawl_source_id' => $source->id, 'started_at' => now(), 'status' => CrawlRun::STATUS_RUNNING]);

    $service->sync($source, $run, [
        new RawRecord(
            externalId: 'sekolah-1',
            recordType: 'sekolah',
            name: 'SDN Morowali',
            kabupatenCode: '7206',
            kabupatenName: 'Kabupaten Morowali',
        ),
        new RawRecord(
            externalId: 'sekolah-2',
            recordType: 'sekolah',
            name: 'SMPN Morowali Utara',
            kabupatenCode: '7212',
            kabupatenName: 'Kabupaten Morowali Utara',
        ),
        new RawRecord(
            externalId: 'sekolah-luar',
            recordType: 'sekolah',
            name: 'SDN Poso',
            kabupatenCode: '7207',
            kabupatenName: 'Kabupaten Poso',
        ),
    ]);

    expect(CrawlRecord::where('external_id', 'sekolah-luar')->exists())->toBeFalse()
        ->and(CrawlRecord::count())->toBe(2)
        ->and(CrawlRecord::firstWhere('external_id', 'sekolah-1')->kabupaten_code)->toBe('7206');
});

it('marks a payload as unchanged when content hash matches', function () {
    $service = app(CrawlerSyncService::class);
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    $run = CrawlRun::create(['crawl_source_id' => $source->id, 'started_at' => now(), 'status' => CrawlRun::STATUS_RUNNING]);

    $raw = new RawRecord(
        externalId: 'sekolah-1',
        recordType: 'sekolah',
        name: 'SDN Morowali',
        kabupatenCode: '7206',
        kabupatenName: 'Kabupaten Morowali',
    );

    $first = $service->sync($source, $run, [$raw]);
    $second = $service->sync($source, $run, [$raw]);

    expect($first['created'])->toBe(1)
        ->and($second['created'])->toBe(0)
        ->and($second['unchanged'])->toBe(1)
        ->and(CrawlRecord::where('external_id', 'sekolah-1')->count())->toBe(1);
});

it('updates an existing record when content hash differs', function () {
    $service = app(CrawlerSyncService::class);
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    $run = CrawlRun::create(['crawl_source_id' => $source->id, 'started_at' => now(), 'status' => CrawlRun::STATUS_RUNNING]);

    $v1 = new RawRecord(externalId: 'sekolah-1', recordType: 'sekolah', name: 'SDN Lama', kabupatenCode: '7206', kabupatenName: 'Kabupaten Morowali');
    $v2 = new RawRecord(externalId: 'sekolah-1', recordType: 'sekolah', name: 'SDN Baru', kabupatenCode: '7206', kabupatenName: 'Kabupaten Morowali');

    $service->sync($source, $run, [$v1]);
    $second = $service->sync($source, $run, [$v2]);

    expect($second['updated'])->toBe(1)
        ->and(CrawlRecord::firstWhere('external_id', 'sekolah-1')->name)->toBe('SDN Baru')
        ->and(CrawlRecord::where('external_id', 'sekolah-1')->count())->toBe(1);
});

it('records an error and does not call the BPS API when the key is missing', function () {
    config(['services.bps.key' => '']);

    Http::preventStrayRequests();

    $result = syncSource('bps');

    expect($result->failed)->toBe(1)
        ->and(CrawlRun::latest('id')->first()->status)->toBe(CrawlRun::STATUS_PARTIAL)
        ->and(CrawlRecord::where('crawl_source_id', CrawlSource::where('slug', 'bps')->firstOrFail()->id)->count())->toBe(0);
});

it('fetches one indicator per target region when the BPS key is present', function () {
    config(['services.bps.key' => 'secret-key']);
    config(['crawler.sources.bps.indicators' => [
        '102' => ['label' => 'Tingkat Partisipasi Angkatan Kerja (TPAK)', 'unit' => 'Persen', 'latest_period' => true, 'period' => 125],
    ]]);

    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        $query = $request->data();
        $model = $query['model'] ?? null;

        if ($model === 'th') {
            return Http::response([
                'status' => 'OK',
                'data' => [
                    ['total' => 1],
                    [['th_id' => 125, 'th' => 2025]],
                ],
            ]);
        }

        return Http::response([
            'status' => 'OK',
            'data-availability' => 'available',
            'datacontent' => ['value' => 70.39],
            'var' => [['val' => 102, 'label' => 'TPAK']],
        ]);
    });

    $source = CrawlSource::where('slug', 'bps')->firstOrFail();

    $result = syncSource('bps');

    expect(CrawlRecord::where('crawl_source_id', $source->id)->count())->toBe(2)
        ->and(CrawlRecord::where('crawl_source_id', $source->id)->where('kabupaten_code', '7206')->exists())->toBeTrue()
        ->and(CrawlRecord::where('crawl_source_id', $source->id)->where('kabupaten_code', '7212')->exists())->toBeTrue();
});

it('crawls DAPO schools from the official referensi portal for the two target regions only', function () {
    Http::preventStrayRequests();

    $portal = 'https://referensi.data.kemendikdasmen.go.id';

    $provinceHtml = '
        <a href="'.$portal.'/pendidikan/dikdas/180700/2">Kab. Morowali</a>
        <a href="'.$portal.'/pendidikan/dikdas/181200/2">Kab. Morowali Utara</a>
        <a href="'.$portal.'/pendidikan/dikdas/180200/2">Kab. Donggala</a>
    ';

    $morowaliHtml = '<a href="'.$portal.'/pendidikan/dikdas/180703/3">Bungku Tengah</a>';
    $morowaliUtaraHtml = '<a href="'.$portal.'/pendidikan/dikdas/181201/3">Bungku Utara</a>';

    $schoolsHtml = function (string $npsn, string $name, string $kecamatan, string $status) {
        return '<tr>
            <td align="right">1</td>
            <td class="link1"><a target="_blank" href="https://referensi.data.kemendikdasmen.go.id/pendidikan/npsn/'.$npsn.'">'.$npsn.'</a></td>
            <td>'.$name.'</td><td>Jln Jend. Sudirman</td><td>'.$kecamatan.'</td><td>'.$status.'</td>
        </tr>';
    };

    Http::fake([
        $portal.'/pendidikan/dikdas/180000/1' => Http::response($provinceHtml),
        $portal.'/pendidikan/dikdas/180700/2' => Http::response($morowaliHtml),
        $portal.'/pendidikan/dikdas/181200/2' => Http::response($morowaliUtaraHtml),
        $portal.'/pendidikan/dikdas/180703/3' => Http::response($schoolsHtml('40202730', 'SD NEGERI BUNGKU', 'Bungku Tengah', 'NEGERI')),
        $portal.'/pendidikan/dikdas/181201/3' => Http::response($schoolsHtml('40202731', 'MTSN MOROWALI UTARA', 'Bungku Utara', 'NEGERI')),
    ]);

    $result = syncSource('dapo');

    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

    expect($result->found)
        ->toBe(2)
        ->and($result->failed)
        ->toBe(0)
        ->and(CrawlRecord::where('crawl_source_id', $source->id)->count())
        ->toBe(2)
        ->and(CrawlRecord::where('kabupaten_code', '7206')->first()->name)
        ->toBe('SD NEGERI BUNGKU')
        ->and(CrawlRecord::where('kabupaten_code', '7206')->first()->data['jenjang'])
        ->toBe('SD')
        ->and(CrawlRecord::where('kabupaten_code', '7212')->first()->data['jenjang'])
        ->toBe('MTs');
});

it('target-region scope only returns the two target regions', function () {
    CrawlRecord::factory()->create(['kabupaten_code' => '7206']);
    CrawlRecord::factory()->morowaliUtara()->create();
    CrawlRecord::factory()->create(['kabupaten_code' => '7207', 'kabupaten_name' => 'Kabupaten Poso']);

    $regions = CrawlRecord::query()->targetRegion()->pluck('kabupaten_code')->sort()->values();

    expect($regions)->toEqual(collect(['7206', '7212']));
});

it('crawls PIHPS province-level prices and persists them as province scope records', function () {
    config(['crawler.sources.sp2kp.base_url' => 'https://www.bi.go.id/hargapangan']);
    config(['crawler.sources.sp2kp.commodities' => [
        1 => ['name' => 'Beras', 'unit' => '/kg'],
        3 => ['name' => 'Daging Sapi', 'unit' => '/kg'],
    ]]);

    Http::preventStrayRequests();
    Http::fake(function (Request $request) {
        $commodity = (int) ($request->data()['commodity'] ?? 0);

        return Http::response([
            'data' => [
                [
                    'Komoditas' => $commodity === 1 ? 'Beras' : 'Daging Sapi',
                    'Nilai' => $commodity === 1 ? 16300.0 : 149400.0,
                    'Tanggal' => '03 Sep 26',
                ],
            ],
        ]);
    });

    $result = syncSource('sp2kp');

    $source = CrawlSource::where('slug', 'sp2kp')->firstOrFail();

    $beras = CrawlRecord::where('crawl_source_id', $source->id)->where('external_id', 'pihps-1')->first();

    expect($result->found)->toBe(2)
        ->and($result->failed)->toBe(0)
        ->and(CrawlRecord::where('crawl_source_id', $source->id)->count())->toBe(2)
        ->and($beras)->not->toBeNull()
        ->and($beras->province_code)->toBe('72')
        ->and($beras->kabupaten_code)->toBeNull()
        ->and($beras->kabupaten_name)->toBe('Provinsi Sulawesi Tengah')
        ->and($beras->data['komoditas'])->toBe('Beras')
        ->and((float) $beras->data['harga'])->toBe(16300.0)
        ->and($beras->data['skala'])->toBe('Provinsi Sulawesi Tengah');
});

it('rejects PIHPS province-scope records for non-price sources', function () {
    $service = app(CrawlerSyncService::class);
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    $run = CrawlRun::create(['crawl_source_id' => $source->id, 'started_at' => now(), 'status' => CrawlRun::STATUS_RUNNING]);

    $counts = $service->sync($source, $run, [
        new RawRecord(
            externalId: 'provinsi-1',
            recordType: 'sekolah',
            name: 'SDN Contoh',
            provinceCode: '72',
            kabupatenName: 'Provinsi Sulawesi Tengah',
        ),
    ]);

    expect($counts['failed'])->toBe(1)
        ->and(CrawlRecord::where('external_id', 'provinsi-1')->exists())->toBeFalse();
});
