<?php

namespace App\Jobs;

use App\Services\Crawlers\CrawlerManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Queued job that runs a single crawler source in the background. It is
 * unique per source so overlapping runs never hit the external source
 * concurrently.
 */
class CrawlSourceJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(private readonly string $sourceSlug) {}

    public function uniqueId(): string
    {
        return $this->sourceSlug;
    }

    public function uniqueVia(): Repository
    {
        return Cache::store('database');
    }

    public function handle(CrawlerManager $manager): void
    {
        Log::channel('crawler')->info('crawler:job starting', ['source' => $this->sourceSlug]);

        $result = $manager->syncSource($this->sourceSlug);

        Log::channel('crawler')->info('crawler:job finished', [
            'source' => $this->sourceSlug,
            'found' => $result->found,
            'created' => $result->created,
            'updated' => $result->updated,
            'unchanged' => $result->unchanged,
            'failed' => $result->failed,
        ]);
    }
}
