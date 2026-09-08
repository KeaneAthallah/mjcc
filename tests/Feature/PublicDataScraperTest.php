<?php

use App\Models\ExternalData;
use App\Models\PublicDataSync;
use App\Services\PublicData\PublicDataScraper;
use App\Services\PublicData\SatuDataScraper;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function satudataFixture(string $name): string
{
    $path = base_path('tests/fixtures/satudata/'.$name);

    if (! file_exists($path)) {
        throw new RuntimeException("Fixture tidak ditemukan: {$path}");
    }

    return (string) file_get_contents($path);
}

/**
 * The catalog walk keeps paging until it finds an empty page, so only page 1
 * serves real items and every later page serves an empty listing.
 */
function fakeSatudataPortal(): void
{
    Http::fake([
        'https://data.morowalikab.go.id/dataset/detail/pendidikan-jumlah-siswa' => Http::response(satudataFixture('detail-pendidikan.html')),
        'https://data.morowalikab.go.id/dataset/detail/kesehatan-akses-puskesmas' => Http::response(satudataFixture('detail-kesehatan.html')),
        'https://data.morowalikab.go.id/dataset/detail/kesbangpol' => Http::response(satudataFixture('detail-nodata.html')),
        'https://data.morowalikab.go.id/dataset/detail/pertanian' => Http::response(satudataFixture('detail-malformed.html')),
        'https://data.morowalikab.go.id/dataset*' => function (Request $request) {
            return Http::response(str_contains($request->url(), 'page=1')
                ? satudataFixture('catalog-page1.html')
                : '<html><body></body></html>');
        },
    ]);
}

beforeEach(function () {
    config()->set('public_data.http.delay_ms', 0);
    Http::preventStrayRequests();
});

it('discovers datasets for a sector without persisting anything', function () {
    fakeSatudataPortal();

    $result = app(SatuDataScraper::class)->discover('pendidikan');

    expect($result['discovered'])->toBe(1)
        ->and($result['matched'][0]['url'])->toContain('pendidikan-jumlah-siswa')
        ->and($result['matched'][0]['topic'])->toBe('Bidang Pendidikan')
        ->and(ExternalData::count())->toBe(0)
        ->and(PublicDataSync::count())->toBe(0);
});

it('scrapes and persists normalized long-format records per sector', function () {
    fakeSatudataPortal();

    $result = app(PublicDataScraper::class)->scrape('pendidikan')[0];

    expect($result['discovered'])->toBe(1)
        ->and($result['datasets'])->toBe(1)
        ->and($result['created'])->toBe(4)
        ->and($result['records'])->toBe(4)
        ->and($result['failures'])->toBe([]);

    expect(ExternalData::count())->toBe(4);

    $row = ExternalData::where('location', 'SDN 1 Bungintende')->where('indicator', 'JUMLAH SISWA')->first();

    expect($row)->not->toBeNull()
        ->and($row->year)->toBe(2024)
        ->and((float) $row->value)->toBe(320.0)
        ->and($row->sector)->toBe('pendidikan')
        ->and($row->source)->toBe('satudata');

    $thousands = ExternalData::where('location', 'SDN 2 Bahomohoni')->where('indicator', 'JUMLAH SISWA')->first();

    expect((float) $thousands->value)->toBe(1234.0);

    $percent = ExternalData::where('location', 'SDN 2 Bahomohoni')->where('indicator', 'PERSENTASE')->first();

    expect($percent)->not->toBeNull()
        ->and((float) $percent->value)->toBe(36.25)
        ->and($percent->unit)->toBe('%');

    expect(ExternalData::where('location', 'SDN 3 Tanah Rata')->count())->toBe(0);

    $sync = PublicDataSync::where('sector', 'pendidikan')->first();

    expect($sync)->not->toBeNull()
        ->and($sync->status)->toBe(PublicDataSync::STATUS_SUCCESS)
        ->and($sync->record_count)->toBe(4)
        ->and($sync->last_success_at)->not->toBeNull()
        ->and($sync->last_attempt_at)->not->toBeNull();
});

it('upserts on the dedupe key instead of duplicating records', function () {
    fakeSatudataPortal();

    $first = app(PublicDataScraper::class)->scrape('pendidikan');

    expect(ExternalData::count())->toBe(4)
        ->and($first[0]['created'])->toBe(4)
        ->and($first[0]['updated'])->toBe(0);

    $second = app(PublicDataScraper::class)->scrape('pendidikan');

    expect(ExternalData::count())->toBe(4)
        ->and($second[0]['created'])->toBe(0)
        ->and($second[0]['updated'])->toBe(4);
});

it('retains last successful data when a re-scrape fails', function () {
    fakeSatudataPortal();
    app(PublicDataScraper::class)->scrape('pendidikan');

    $syncBefore = PublicDataSync::where('sector', 'pendidikan')->first();
    $lastSuccess = $syncBefore->last_success_at;

    config()->set('public_data.satudata.base_url', 'https://broken.invalid');
    Http::fake(['https://broken.invalid/*' => Http::response('', 500)]);

    $result = app(PublicDataScraper::class)->scrape('pendidikan')[0];

    expect($result['error'])->not->toBeNull();

    $sync = PublicDataSync::where('sector', 'pendidikan')->first();

    expect($sync->status)->toBe(PublicDataSync::STATUS_FAILED)
        ->and($sync->last_error)->not->toBeNull()
        ->and($sync->last_success_at->equalTo($lastSuccess))->toBeTrue()
        ->and($sync->record_count)->toBe(4);

    expect(ExternalData::count())->toBe(4);
});

it('keeps previously fetched data when the transport completely fails', function () {
    fakeSatudataPortal();
    app(PublicDataScraper::class)->scrape('kesehatan');

    $before = ExternalData::count();
    $lastSuccess = PublicDataSync::where('sector', 'kesehatan')->value('last_success_at');

    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    $result = app(PublicDataScraper::class)->scrape('kesehatan')[0];

    $sync = PublicDataSync::where('sector', 'kesehatan')->first();

    expect($result['error'])->toContain('Connection timed out')
        ->and($sync->status)->toBe(PublicDataSync::STATUS_FAILED)
        ->and($sync->last_error)->toContain('Connection timed out')
        ->and($sync->last_success_at->equalTo($lastSuccess))->toBeTrue()
        ->and($sync->record_count)->toBe(2);

    expect(ExternalData::count())->toBe($before);
});

it('collects per-dataset failures but still completes the sector run', function () {
    Http::fake([
        'https://data.morowalikab.go.id/dataset/detail/kesehatan-akses-puskesmas' => Http::response('', 500),
        'https://data.morowalikab.go.id/dataset*' => function (Request $request) {
            return Http::response(str_contains($request->url(), 'page=1')
                ? satudataFixture('catalog-page1.html')
                : '<html><body></body></html>');
        },
    ]);

    $result = app(PublicDataScraper::class)->scrape('kesehatan')[0];

    expect($result['discovered'])->toBe(1)
        ->and($result['datasets'])->toBe(0)
        ->and($result['failures'])->toHaveCount(1);

    $sync = PublicDataSync::where('sector', 'kesehatan')->first();

    expect($sync->status)->toBe(PublicDataSync::STATUS_SUCCESS)
        ->and($sync->last_error)->toContain('1 dari 1 dataset');

    expect(ExternalData::count())->toBe(0);
});

it('keeps discover-only mode off the sync log', function () {
    fakeSatudataPortal();

    $result = app(PublicDataScraper::class)->scrape('kesehatan', discoverOnly: true)[0];

    expect($result['discovered'])->toBe(1)
        ->and(PublicDataSync::count())->toBe(0)
        ->and(ExternalData::count())->toBe(0);
});

it('rejects unknown sectors', function () {
    expect(fn () => app(PublicDataScraper::class)->scrape('nuklir'))
        ->toThrow(RuntimeException::class);
});
