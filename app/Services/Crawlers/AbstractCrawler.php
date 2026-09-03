<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\DataCrawlerInterface;
use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Support\Crawl\CrawlResult;
use App\Support\TargetRegionService;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared infrastructure for all crawler connectors: HTTP client factory,
 * run lifecycle management, and high-level error handling. Concrete
 * connectors implement crawl() to fetch and normalize records.
 */
abstract class AbstractCrawler implements DataCrawlerInterface
{
    protected readonly CrawlSource $source;

    public function __construct(
        protected readonly Http $http,
        protected readonly CrawlerSyncService $sync,
        protected readonly TargetRegionService $targetRegions,
    ) {
        $this->source = $this->resolveSource();
    }

    /**
     * Concrete connectors fetch data and return normalized records.
     *
     * @return RawRecord[]
     */
    abstract protected function crawl(CrawlRun $run): array;

    /**
     * Extend per-connector HTTP request (headers, query params, auth).
     */
    protected function configureRequest(PendingRequest $request): PendingRequest
    {
        return $request;
    }

    /**
     * Runs a full crawl + sync cycle and records the outcome on a CrawlRun.
     */
    public function sync(): CrawlResult
    {
        if (! $this->source->is_active) {
            throw new \RuntimeException("Crawl source [{$this->source->slug}] is inactive.");
        }

        $run = CrawlRun::create([
            'crawl_source_id' => $this->source->id,
            'started_at' => now(),
            'status' => CrawlRun::STATUS_RUNNING,
        ]);

        $start = hrtime(true);
        $records = [];

        try {
            $records = $this->crawl($run);
            $counts = $this->sync->sync($this->source, $run, $records);

            // Re-read counts because crawl() records errors (bumps error_count).
            $run->refresh();
            $failed = $counts['failed'] + $run->error_count;

            $status = $this->inferStatus($failed);
            $this->finish($run, $status, $start, $counts, count($records));

            Log::channel('crawler')->info("crawler:sync [{$this->source->slug}] done", [
                'counts' => $counts,
                'status' => $status,
            ]);

            return new CrawlResult(
                source: $this->source->slug,
                records: $records,
                found: count($records),
                created: $counts['created'],
                updated: $counts['updated'],
                unchanged: $counts['unchanged'],
                failed: $failed,
            );
        } catch (Throwable $e) {
            $this->sync->recordError($this->source, $run, $e->getMessage());
            $run->refresh();
            $this->finish($run, CrawlRun::STATUS_FAILED, $start, ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'failed' => $run->error_count], 0);

            Log::channel('crawler')->error("crawler:sync [{$this->source->slug}] failed", [
                'error' => $e->getMessage(),
            ]);

            return new CrawlResult(
                source: $this->source->slug,
                records: [],
                failed: 1,
                errors: [$e->getMessage()],
            );
        }
    }

    protected function http(): PendingRequest
    {
        $request = $this->http
            ->timeout(config('crawler.http.timeout'))
            ->connectTimeout(config('crawler.http.connect_timeout'))
            ->withHeaders([
                'User-Agent' => config('crawler.http.user_agent'),
                'Accept' => 'application/json',
            ])
            ->retry(2, $this->backoffMs(), throw: false);

        return $this->configureRequest($request);
    }

    protected function backoffMs(): int
    {
        return (int) config('crawler.http.base_delay_ms', 700);
    }

    protected function politeDelay(): void
    {
        usleep((int) config('crawler.http.base_delay_ms', 700) * 1000);
    }

    private function resolveSource(): CrawlSource
    {
        $source = CrawlSource::query()
            ->where('slug', $this->source())
            ->first();

        if ($source === null) {
            throw new \RuntimeException("Crawl source [{$this->source()}] has not been seeded.");
        }

        return $source;
    }

    private function inferStatus(int $failed): string
    {
        return $failed > 0 ? CrawlRun::STATUS_PARTIAL : CrawlRun::STATUS_SUCCESS;
    }

    /**
     * @param  array{created:int,updated:int,unchanged:int,failed:int}  $counts
     */
    private function finish(CrawlRun $run, string $status, int|float $start, array $counts, int $found): void
    {
        $elapsed = (int) round((hrtime(true) - $start) / 1_000_000);

        $run->forceFill([
            'finished_at' => now(),
            'status' => $status,
            'records_found' => $found,
            'records_created' => $counts['created'],
            'records_updated' => $counts['updated'],
            'records_unchanged' => $counts['unchanged'],
            'records_failed' => $counts['failed'],
            'duration' => $elapsed,
        ])->save();
    }
}
