<?php

use App\Models\CrawlError;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Models\User;
use App\Services\Crawlers\CrawlerManager;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    app(CrawlerManager::class)->seedSources();

    $this->user = User::factory()->viewer()->create();
    Sanctum::actingAs($this->user);
});

describe('Crawl Sources', function () {
    it('lists crawl sources with aggregate stats', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRecord::factory()->count(3)->create(['crawl_source_id' => $source->id]);
        CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
            'records_found' => 5,
        ]);

        $response = $this->getJson('/api/v1/crawler');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'is_active', 'runs_count', 'records_count']],
            ]);

        expect($response->json('data'))->toHaveCount(CrawlSource::count());
    });

    it('shows a single crawl source with stats', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        $response = $this->getJson("/api/v1/crawler/sources/{$source->slug}");

        $response->assertOk()
            ->assertJsonPath('data.slug', 'dapo')
            ->assertJsonPath('data.name', $source->name);
    });

    it('returns 404 for non-existent source', function () {
        $this->getJson('/api/v1/crawler/sources/nonexistent')
            ->assertStatus(404);
    });
});

describe('Crawl Runs', function () {
    it('lists crawl runs with pagination', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now()->subHours(2),
            'finished_at' => now()->subHours(2)->addMinutes(1),
            'status' => CrawlRun::STATUS_SUCCESS,
            'records_found' => 10,
        ]);
        CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_FAILED,
            'records_found' => 0,
        ]);

        $response = $this->getJson('/api/v1/crawler/runs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [['id', 'status', 'started_at', 'records_found', 'source']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        expect($response->json('meta.total'))->toBeGreaterThanOrEqual(2);
    });

    it('filters runs by status', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
        ]);
        CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'status' => CrawlRun::STATUS_FAILED,
        ]);

        $response = $this->getJson('/api/v1/crawler/runs?status=failed');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'failed');
    });

    it('filters runs by source slug', function () {
        $dapo = CrawlSource::where('slug', 'dapo')->firstOrFail();
        $sp2kp = CrawlSource::where('slug', 'sp2kp')->firstOrFail();

        CrawlRun::create(['crawl_source_id' => $dapo->id, 'started_at' => now(), 'status' => CrawlRun::STATUS_SUCCESS]);
        CrawlRun::create(['crawl_source_id' => $sp2kp->id, 'started_at' => now(), 'status' => CrawlRun::STATUS_SUCCESS]);

        $response = $this->getJson("/api/v1/crawler/sources/{$dapo->slug}/runs");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('shows a single run with errors and records', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
        $run = CrawlRun::create([
            'crawl_source_id' => $source->id,
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_PARTIAL,
            'error_count' => 1,
        ]);

        CrawlError::create([
            'crawl_source_id' => $source->id,
            'crawl_run_id' => $run->id,
            'http_status' => 500,
            'message' => 'Server error',
        ]);

        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'crawl_run_id' => $run->id]);

        $response = $this->getJson("/api/v1/crawler/runs/{$run->id}");

        $response->assertOk()
            ->assertJsonPath('data.status', 'partial')
            ->assertJsonPath('data.error_count', 1)
            ->assertJsonCount(1, 'data.errors')
            ->assertJsonCount(1, 'data.records');
    });
});

describe('Crawl Records', function () {
    it('lists crawl records with pagination', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
        CrawlRecord::factory()->count(25)->create(['crawl_source_id' => $source->id]);

        $response = $this->getJson('/api/v1/crawler/records?per_page=10');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [['id', 'external_id', 'name', 'record_type', 'source']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        expect($response->json('meta.total'))->toBe(25)
            ->and(count($response->json('data')))->toBe(10);
    });

    it('filters records by source slug', function () {
        $dapo = CrawlSource::where('slug', 'dapo')->firstOrFail();
        $sp2kp = CrawlSource::where('slug', 'sp2kp')->firstOrFail();

        CrawlRecord::factory()->create(['crawl_source_id' => $dapo->id, 'name' => 'SDN Test']);
        CrawlRecord::factory()->create(['crawl_source_id' => $sp2kp->id, 'name' => 'Beras']);

        $response = $this->getJson('/api/v1/crawler/records?source=dapo');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'SDN Test');
    });

    it('filters records by record_type', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'record_type' => 'sekolah', 'name' => 'SDN']);
        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'record_type' => 'pasar', 'name' => 'Pasar']);

        $response = $this->getJson('/api/v1/crawler/records?record_type=sekolah');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.record_type', 'sekolah');
    });

    it('filters records by kabupaten_code', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'kabupaten_code' => '7206']);
        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'kabupaten_code' => '7212']);

        $response = $this->getJson('/api/v1/crawler/records?kabupaten_code=7206');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kabupaten_code', '7206');
    });

    it('searches records by name', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'name' => 'SDN Bungku']);
        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'name' => 'SMPN Lembo']);

        $response = $this->getJson('/api/v1/crawler/records?search=Bungku');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'SDN Bungku');
    });

    it('searches records by external_id', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'external_id' => 'sekolah-123']);
        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'external_id' => 'sekolah-456']);

        $response = $this->getJson('/api/v1/crawler/records?search=123');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.external_id', 'sekolah-123');
    });

    it('shows a single crawl record', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();
        $record = CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'name' => 'SDN Detail']);

        $response = $this->getJson("/api/v1/crawler/records/{$record->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'SDN Detail')
            ->assertJsonPath('data.external_id', $record->external_id)
            ->assertJsonStructure(['data' => ['source', 'data', 'latitude', 'longitude']]);
    });

    it('handles empty results correctly', function () {
        $response = $this->getJson('/api/v1/crawler/records?search=nonexistent');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    });

    it('supports sort_direction parameter', function () {
        $source = CrawlSource::where('slug', 'dapo')->firstOrFail();

        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'name' => 'AAA']);
        CrawlRecord::factory()->create(['crawl_source_id' => $source->id, 'name' => 'ZZZ']);

        $asc = $this->getJson('/api/v1/crawler/records?sort=name&sort_direction=asc');
        $asc->assertOk()->assertJsonPath('data.0.name', 'AAA');

        $desc = $this->getJson('/api/v1/crawler/records?sort=name&sort_direction=desc');
        $desc->assertOk()->assertJsonPath('data.0.name', 'ZZZ');
    });
});

describe('Crawler API Authorization', function () {
    it('requires authentication', function () {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/v1/crawler')->assertUnauthorized();
        $this->getJson('/api/v1/crawler/records')->assertUnauthorized();
    });

    it('allows viewers to read crawl data', function () {
        $viewer = User::factory()->viewer()->create();
        Sanctum::actingAs($viewer);

        $this->getJson('/api/v1/crawler')->assertOk();
        $this->getJson('/api/v1/crawler/records')->assertOk();
    });
});
