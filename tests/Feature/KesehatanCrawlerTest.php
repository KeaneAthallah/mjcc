<?php

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\User;
use App\Services\Crawlers\CrawlerManager;
use App\Services\Crawlers\CrawlerSyncService;
use App\Services\Crawlers\HealthFacilityImportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['crawler.enabled' => true]);
    app(CrawlerManager::class)->seedSources();
});

describe('Kesehatan Crawl Lifecycle', function () {
    it('registers kesehatan as a crawl source', function () {
        $source = CrawlSource::where('slug', 'kesehatan')->first();

        expect($source)->not->toBeNull()
            ->and($source->name)->toBe('Fasilitas Kesehatan')
            ->and($source->is_active)->toBeTrue();
    });

    it('creates a crawl run when syncing kesehatan with mocked API', function () {
        Http::preventStrayRequests();
        Http::fake([
            'api-kfakes.kemkes.go.id/*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        $result = app(CrawlerManager::class)->syncSource('kesehatan');

        expect($result->source)->toBe('kesehatan')
            ->and($result->found)->toBe(0);

        $run = CrawlRun::latest('id')->first();
        expect($run)->not->toBeNull()
            ->and($run->status)->toBe(CrawlRun::STATUS_SUCCESS)
            ->and($run->started_at)->not->toBeNull()
            ->and($run->finished_at)->not->toBeNull();
    });

    it('parses health facilities from API response and creates crawl records', function () {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $kabkot = (string) $request->data()['kabkot'] ?? '7206';

            return Http::response([
                'data' => [
                    [
                        'id' => 'HF001',
                        'nama' => 'Puskesmas Bungku Tengah',
                        'jenis_fasyankes' => 'Puskesmas',
                        'alamat' => 'Jl. Trans Sulawesi',
                        'kecamatan' => 'Bungku Tengah',
                        'latitude' => -3.35,
                        'longitude' => 121.85,
                        'telp' => '0123456789',
                        'jumlah_dokter' => 5,
                        'jumlah_perawat' => 12,
                        'jumlah_bidan' => 8,
                        'jumlah_tempat_tidur' => 20,
                        'status' => 'Aktif',
                        'kondisi' => 'Baik',
                    ],
                ],
            ], 200);
        });

        $result = app(CrawlerManager::class)->syncSource('kesehatan');

        $source = CrawlSource::where('slug', 'kesehatan')->first();

        expect($result->found)->toBe(2)
            ->and(CrawlRecord::where('crawl_source_id', $source->id)->count())->toBe(1);

        $record = CrawlRecord::where('external_id', 'kesehatan-HF001')->first();
        expect($record)->not->toBeNull()
            ->and($record->name)->toBe('Puskesmas Bungku Tengah')
            ->and($record->kabupaten_code)->toBeIn(['7206', '7212'])
            ->and($record->data['facility_type'])->toBe('Puskesmas')
            ->and($record->data['doctors'])->toBe(5);
    });

    it('handles API failure gracefully and records error', function () {
        Http::preventStrayRequests();
        Http::fake([
            'api-kfakes.kemkes.go.id/*' => Http::response(null, 500),
        ]);

        $result = app(CrawlerManager::class)->syncSource('kesehatan');

        expect($result->failed)->toBeGreaterThanOrEqual(1);

        $run = CrawlRun::latest('id')->first();
        expect($run->status)->toBe(CrawlRun::STATUS_PARTIAL);
    });

    it('records crawl duration and timestamps', function () {
        Http::preventStrayRequests();
        Http::fake([
            'api-kfakes.kemkes.go.id/*' => Http::response(['data' => []], 200),
        ]);

        app(CrawlerManager::class)->syncSource('kesehatan');

        $run = CrawlRun::latest('id')->first();
        expect($run->started_at)->not->toBeNull()
            ->and($run->finished_at)->not->toBeNull()
            ->and($run->duration)->toBeGreaterThanOrEqual(0);
    });
});

describe('Kesehatan Health Facility Import', function () {
    it('creates a health facility from a crawl record', function () {
        $kecamatan = Kecamatan::factory()->create(['name' => 'Bungku Tengah']);
        $source = CrawlSource::where('slug', 'kesehatan')->first();
        $run = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);

        CrawlRecord::create([
            'crawl_source_id' => $source->id,
            'crawl_run_id' => $run->id,
            'external_id' => 'kesehatan-HF001',
            'record_type' => 'fasyankes',
            'name' => 'Puskesmas Bungku Tengah',
            'province_code' => '72',
            'kabupaten_code' => '7206',
            'kabupaten_name' => 'Kabupaten Morowali',
            'kecamatan_name' => 'Bungku Tengah',
            'latitude' => -3.35,
            'longitude' => 121.85,
            'data' => [
                'facility_type' => 'Puskesmas',
                'address' => 'Jl. Trans Sulawesi',
                'phone' => '0123456789',
                'doctors' => 5,
                'nurses' => 12,
                'midwives' => 8,
                'beds' => 20,
                'status' => 'aktif',
                'condition' => 'baik',
            ],
            'source_url' => 'https://api-kfakes.kemkes.go.id/v1/api/fasyankes/HF001',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'content_hash' => sha1('test'),
        ]);

        $importService = app(HealthFacilityImportService::class);
        $stats = $importService->importFromCrawl($run);

        expect($stats['created'])->toBe(1)
            ->and($stats['updated'])->toBe(0)
            ->and(HealthFacility::count())->toBe(1);

        $facility = HealthFacility::first();
        expect($facility->name)->toBe('Puskesmas Bungku Tengah')
            ->and($facility->kecamatan_id)->toBe($kecamatan->id)
            ->and($facility->source_name)->toBe('kemenkes_fasyankes')
            ->and($facility->source_id)->toBe('kesehatan-HF001')
            ->and($facility->doctors)->toBe(5)
            ->and($facility->beds)->toBe(20);
    });

    it('updates an existing health facility on re-import', function () {
        $kecamatan = Kecamatan::factory()->create(['name' => 'Bungku Tengah']);
        $source = CrawlSource::where('slug', 'kesehatan')->first();
        $run = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);

        CrawlRecord::create([
            'crawl_source_id' => $source->id,
            'crawl_run_id' => $run->id,
            'external_id' => 'kesehatan-HF001',
            'record_type' => 'fasyankes',
            'name' => 'Puskesmas Bungku Tengah',
            'province_code' => '72',
            'kabupaten_code' => '7206',
            'kabupaten_name' => 'Kabupaten Morowali',
            'kecamatan_name' => 'Bungku Tengah',
            'latitude' => -3.35,
            'longitude' => 121.85,
            'data' => [
                'facility_type' => 'Puskesmas',
                'address' => 'Jl. Trans Sulawesi',
                'doctors' => 8,
                'nurses' => 15,
                'midwives' => 10,
                'beds' => 25,
                'status' => 'aktif',
                'condition' => 'baik',
            ],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'content_hash' => sha1('test'),
        ]);

        HealthFacility::create([
            'kecamatan_id' => $kecamatan->id,
            'name' => 'Puskesmas Bungku Tengah',
            'facility_type' => 'Puskesmas',
            'address' => 'Jl. Trans Sulawesi',
            'doctors' => 5,
            'nurses' => 12,
            'midwives' => 8,
            'beds' => 20,
            'source_name' => 'kemenkes_fasyankes',
            'source_id' => 'kesehatan-HF001',
        ]);

        $importService = app(HealthFacilityImportService::class);
        $stats = $importService->importFromCrawl($run);

        expect($stats['created'])->toBe(0)
            ->and($stats['updated'])->toBe(1)
            ->and(HealthFacility::count())->toBe(1);

        $facility = HealthFacility::first();
        expect($facility->doctors)->toBe(8)
            ->and($facility->beds)->toBe(25);
    });

    it('skips records with empty names', function () {
        $source = CrawlSource::where('slug', 'kesehatan')->first();
        $run = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);

        CrawlRecord::create([
            'crawl_source_id' => $source->id,
            'crawl_run_id' => $run->id,
            'external_id' => 'kesehatan-empty',
            'record_type' => 'fasyankes',
            'name' => '',
            'data' => ['facility_type' => 'Puskesmas'],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'content_hash' => sha1('empty'),
        ]);

        $importService = app(HealthFacilityImportService::class);
        $stats = $importService->importFromCrawl($run);

        expect($stats['skipped'])->toBe(1)
            ->and(HealthFacility::count())->toBe(0);
    });

    it('does not create duplicate facilities on repeated imports', function () {
        $kecamatan = Kecamatan::factory()->create(['name' => 'Bungku Tengah']);
        $source = CrawlSource::where('slug', 'kesehatan')->first();

        $record = CrawlRecord::create([
            'crawl_source_id' => $source->id,
            'external_id' => 'kesehatan-HF001',
            'record_type' => 'fasyankes',
            'name' => 'Puskesmas Bungku Tengah',
            'province_code' => '72',
            'kabupaten_code' => '7206',
            'kecamatan_name' => 'Bungku Tengah',
            'data' => [
                'facility_type' => 'Puskesmas',
                'doctors' => 5,
            ],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'content_hash' => sha1('test-0'),
        ]);

        $run1 = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);
        $record->crawl_run_id = $run1->id;
        $record->save();
        app(HealthFacilityImportService::class)->importFromCrawl($run1);

        $record->data = ['facility_type' => 'Puskesmas', 'doctors' => 6];
        $record->content_hash = sha1('test-1');
        $record->save();

        $run2 = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);
        $record->crawl_run_id = $run2->id;
        $record->save();
        app(HealthFacilityImportService::class)->importFromCrawl($run2);

        expect(HealthFacility::count())->toBe(1);

        $facility = HealthFacility::first();
        expect($facility->doctors)->toBe(6);
    });

    it('handles null coordinates gracefully during import', function () {
        $source = CrawlSource::where('slug', 'kesehatan')->first();
        $run = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);

        CrawlRecord::create([
            'crawl_source_id' => $source->id,
            'crawl_run_id' => $run->id,
            'external_id' => 'kesehatan-HF002',
            'record_type' => 'fasyankes',
            'name' => 'Puskesmas Test',
            'province_code' => '72',
            'kabupaten_code' => '7206',
            'latitude' => null,
            'longitude' => null,
            'data' => ['facility_type' => 'Puskesmas'],
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'content_hash' => sha1('nocoords'),
        ]);

        app(HealthFacilityImportService::class)->importFromCrawl($run);

        $facility = HealthFacility::first();
        expect($facility)->not->toBeNull()
            ->and($facility->latitude)->toBeNull()
            ->and($facility->longitude)->toBeNull();
    });

    it('stores source attribution on imported facilities', function () {
        $source = CrawlSource::where('slug', 'kesehatan')->first();
        $run = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);

        CrawlRecord::create([
            'crawl_source_id' => $source->id,
            'crawl_run_id' => $run->id,
            'external_id' => 'kesehatan-HF003',
            'record_type' => 'fasyankes',
            'name' => 'RS Morowali',
            'province_code' => '72',
            'kabupaten_code' => '7206',
            'data' => [
                'facility_type' => 'Rumah Sakit',
                'doctors' => 20,
            ],
            'source_url' => 'https://example.com/hf/HF003',
            'source_updated_at' => now()->subDay(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'content_hash' => sha1('rs'),
        ]);

        app(HealthFacilityImportService::class)->importFromCrawl($run);

        $facility = HealthFacility::first();
        expect($facility->source_name)->toBe('kemenkes_fasyankes')
            ->and($facility->source_id)->toBe('kesehatan-HF003')
            ->and($facility->source_url)->toBe('https://example.com/hf/HF003')
            ->and($facility->last_crawled_at)->not->toBeNull()
            ->and($facility->isCrawled())->toBeTrue();
    });
});

describe('Kesehatan Crawl Authorization', function () {
    it('allows admin to view kesehatan crawl page', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('crawler.kesehatan'))
            ->assertOk();
    });

    it('allows operator to view kesehatan crawl page', function () {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get(route('crawler.kesehatan'))
            ->assertOk();
    });

    it('allows viewer to view kesehatan crawl page', function () {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('crawler.kesehatan'))
            ->assertOk();
    });

    it('allows admin to trigger kesehatan crawl', function () {
        $admin = User::factory()->admin()->create();

        Http::preventStrayRequests();
        Http::fake([
            'api-kfakes.kemkes.go.id/*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('crawler.kesehatan.run'))
            ->assertRedirect();
    });

    it('allows operator to trigger kesehatan crawl', function () {
        $operator = User::factory()->operator()->create();

        Http::preventStrayRequests();
        Http::fake([
            'api-kfakes.kemkes.go.id/*' => Http::response(['data' => []], 200),
        ]);

        $this->actingAs($operator)
            ->post(route('crawler.kesehatan.run'))
            ->assertRedirect();
    });

    it('blocks viewer from triggering kesehatan crawl', function () {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->post(route('crawler.kesehatan.run'))
            ->assertForbidden();
    });

    it('allows admin to view kesehatan facility detail', function () {
        $admin = User::factory()->admin()->create();
        $facility = HealthFacility::factory()->create();

        $this->actingAs($admin)
            ->get(route('crawler.kesehatan.show', $facility))
            ->assertOk();
    });
});

describe('Kesehatan Crawl UI', function () {
    it('renders the kesehatan crawl index page', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('crawler.kesehatan'))
            ->assertOk()
            ->assertSee('Crawling Data Fasyankes')
            ->assertSee('Fasilitas Kesehatan');
    });

    it('renders the kesehatan facility detail page', function () {
        $admin = User::factory()->admin()->create();
        $facility = HealthFacility::factory()->create([
            'name' => 'Puskesmas Test UI',
        ]);

        $this->actingAs($admin)
            ->get(route('crawler.kesehatan.show', $facility))
            ->assertOk()
            ->assertSee('Puskesmas Test UI');
    });

    it('shows crawl history on kesehatan page', function () {
        $admin = User::factory()->admin()->create();
        $source = CrawlSource::where('slug', 'kesehatan')->first();
        CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
            'records_found' => 10,
            'records_created' => 5,
            'records_updated' => 3,
        ]);

        $this->actingAs($admin)
            ->get(route('crawler.kesehatan'))
            ->assertOk()
            ->assertSee('Riwayat Sinkronisasi')
            ->assertSee('10');
    });

    it('shows source attribution on crawled facility detail', function () {
        $admin = User::factory()->admin()->create();
        $facility = HealthFacility::factory()->create([
            'source_name' => 'kemenkes_fasyankes',
            'source_id' => 'kesehatan-HF001',
            'last_crawled_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('crawler.kesehatan.show', $facility))
            ->assertOk()
            ->assertSee('kemenkes_fasyankes')
            ->assertSee('Data ini berasal dari crawling');
    });
});

describe('Kesehatan Crawl Sync Integration', function () {
    it('crawls kesehatan with multiple kabupaten', function () {
        Http::preventStrayRequests();

        $callCount = 0;
        Http::fake(function (Request $request) use (&$callCount) {
            $kabkot = (string) $request->data()['kabkot'] ?? '7206';
            $callCount++;

            return Http::response([
                'data' => [
                    [
                        'id' => "HF-{$kabkot}",
                        'nama' => "Faskes {$kabkot}",
                        'jenis_fasyankes' => 'Puskesmas',
                        'kecamatan' => 'Bungku Tengah',
                        'latitude' => -3.3,
                        'longitude' => 121.8,
                    ],
                ],
            ], 200);
        });

        $result = app(CrawlerManager::class)->syncSource('kesehatan');

        expect($result->found)->toBe(2)
            ->and($result->created)->toBe(2);
    });

    it('uses content hash to detect unchanged records', function () {
        $source = CrawlSource::where('slug', 'kesehatan')->first();
        $run = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'status' => CrawlRun::STATUS_RUNNING,
        ]);

        $raw = new RawRecord(
            externalId: 'kesehatan-HF001',
            recordType: 'fasyankes',
            name: 'Puskesmas Test',
            provinceCode: '72',
            kabupatenCode: '7206',
            kabupatenName: 'Kabupaten Morowali',
        );

        $syncService = app(CrawlerSyncService::class);

        $first = $syncService->sync($source, $run, [$raw]);
        $second = $syncService->sync($source, $run, [$raw]);

        expect($first['created'])->toBe(1)
            ->and($second['unchanged'])->toBe(1)
            ->and($second['created'])->toBe(0);
    });
});
