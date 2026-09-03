<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\DataCrawlerInterface;
use App\Models\CrawlSource;
use App\Support\Crawl\CrawlResult;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

/**
 * Orchestrates crawler connectors: seeding of the crawl_sources lookup table,
 * connector resolution, and single/all-source sync runs.
 */
class CrawlerManager
{
    public function __construct(
        private readonly Container $container,
        private readonly CrawlerRegistry $registry,
    ) {}

    /**
     * Ensure the crawl_sources lookup table contains the known sources.
     *
     * @return Collection<int, CrawlSource>
     */
    public function seedSources(): Collection
    {
        return collect($this->registry->slugs())->map(function (string $slug) {
            $config = $this->sourceConfig($slug);

            return CrawlSource::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'] ?? strtoupper($slug),
                    'base_url' => $config['base_url'] ?? '',
                    'source_type' => 'remote',
                    'is_active' => (bool) ($config['enabled'] ?? true),
                    'configuration' => $config,
                ]
            );
        });
    }

    public function connector(string $slug): DataCrawlerInterface
    {
        $this->seedSources();

        $class = $this->registry->resolve($slug);

        return $this->container->make($class);
    }

    /**
     * Run a single source.
     */
    public function syncSource(string $slug): CrawlResult
    {
        return $this->connector($slug)->sync();
    }

    /**
     * Run every enabled source sequentially.
     *
     * @return array<string, CrawlResult>
     */
    public function syncAll(): array
    {
        $results = [];

        foreach ($this->availableSources() as $slug) {
            $config = $this->sourceConfig($slug);

            if (! ($config['enabled'] ?? true)) {
                continue;
            }

            $results[$slug] = $this->syncSource($slug);
        }

        return $results;
    }

    /**
     * @return Collection<int, string>
     */
    public function availableSources(): Collection
    {
        return collect($this->registry->slugs());
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceConfig(string $slug): array
    {
        return (array) config("crawler.sources.{$slug}", []);
    }
}
