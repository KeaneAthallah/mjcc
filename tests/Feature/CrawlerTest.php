<?php

use App\Jobs\CrawlSourceJob;
use App\Models\CrawlRecord;
use App\Models\CrawlSource;
use App\Models\User;
use App\Services\Crawlers\CrawlerManager;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    app(CrawlerManager::class)->seedSources();
});

it('renders the crawler dashboard for an authenticated viewer', function () {
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('crawler.dashboard'))
        ->assertOk()
        ->assertSee('Data Eksternal');
});

it('renders the source detail page', function () {
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('crawler.sources.show', $source))
        ->assertOk()
        ->assertSee($source->name);
});

it('renders the PIHPS price index and detail pages', function () {
    $source = CrawlSource::where('slug', 'sp2kp')->firstOrFail();
    $record = CrawlRecord::factory()->create([
        'crawl_source_id' => $source->id,
        'external_id' => 'pihps-1',
        'record_type' => 'harga-pangan',
        'name' => 'Beras',
        'province_code' => '72',
        'kabupaten_code' => null,
        'kabupaten_name' => 'Provinsi Sulawesi Tengah',
        'data' => [
            'komoditas' => 'Beras',
            'harga' => 16300,
            'satuan' => 'Rp/kg',
            'tanggal' => '03 Sep 26',
            'skala' => 'Provinsi Sulawesi Tengah',
        ],
    ]);
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('crawler.sp2kp'))
        ->assertOk()
        ->assertSee('Harga Pangan PIHPS')
        ->assertSee('Beras');

    $this->actingAs($viewer)
        ->get(route('crawler.sp2kp.markets.show', $record))
        ->assertOk()
        ->assertSee('Beras')
        ->assertSee('Provinsi Sulawesi Tengah');
});

it('renders a record detail page', function () {
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    $record = CrawlRecord::factory()->create(['crawl_source_id' => $source->id]);
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('crawler.records.show', $record))
        ->assertOk()
        ->assertSee($record->name);
});

it('filters records by source and region', function () {
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'kabupaten_code' => '7206']);
    CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'kabupaten_code' => '7212']);
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('crawler.records.index', ['source' => $source->id, 'regional' => '7212']))
        ->assertOk()
        ->assertSee('7212');
});

it('allows admin and operator but blocks viewer from triggering an on-demand crawl run', function () {
    $source = CrawlSource::where('slug', 'bps')->firstOrFail();
    $viewer = User::factory()->viewer()->create();
    $operator = User::factory()->operator()->create();
    $admin = User::factory()->admin()->create();

    config(['services.bps.key' => '']);

    $this->actingAs($viewer)
        ->post(route('crawler.sources.run', $source))
        ->assertForbidden();

    $this->actingAs($operator)
        ->post(route('crawler.sources.run', $source))
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('crawler.sources.run', $source))
        ->assertRedirect();
});

it('integrates crawler records into the combined map', function () {
    $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
    CrawlRecord::factory()->create([
        'crawl_source_id' => $source->id,
        'kabupaten_code' => '7206',
        'latitude' => -3.3,
        'longitude' => 121.8,
    ]);
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer)
        ->get(route('maps.index'))
        ->assertOk()
        ->assertSee('eksternal');
});

it('dispatches a unique crawl job to the queue with --queue', function () {
    Queue::fake();

    $this->artisan('crawler:sync', ['--source' => 'dapo', '--queue' => true])
        ->expectsOutputToContain('[dapo]')
        ->assertExitCode(0);

    Queue::assertPushed(CrawlSourceJob::class, fn ($job) => $job instanceof CrawlSourceJob);
});
