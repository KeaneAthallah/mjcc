<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\DataCrawlerInterface;
use InvalidArgumentException;

/**
 * Maps a source slug to its concrete crawler connector class.
 */
class CrawlerRegistry
{
    /**
     * @var array<string, class-string<DataCrawlerInterface>>
     */
    private const MAP = [
        'ats' => AtsCrawler::class,
        'dapo' => DapoCrawler::class,
        'sp2kp' => Sp2kpCrawler::class,
        'bps' => BpsCrawler::class,
        'kesehatan' => KesehatanCrawler::class,
    ];

    /**
     * @return string[]
     */
    public function slugs(): array
    {
        return array_keys(self::MAP);
    }

    /**
     * @return class-string<DataCrawlerInterface>
     */
    public function resolve(string $slug): string
    {
        if (! isset(self::MAP[$slug])) {
            throw new InvalidArgumentException("Unknown crawler source [{$slug}].");
        }

        return self::MAP[$slug];
    }
}
