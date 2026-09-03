<?php

namespace App\Support\Crawl;

use App\Contracts\Crawl\RawRecord;

/**
 * Immutable result returned by a crawler connector after a crawl + sync run.
 */
class CrawlResult
{
    /**
     * @param  RawRecord[]  $records
     */
    public function __construct(
        public readonly string $source,
        public readonly array $records,
        public readonly int $found = 0,
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $unchanged = 0,
        public readonly int $failed = 0,
        public readonly array $errors = [],
    ) {}

    public function totalSynced(): int
    {
        return $this->created + $this->updated + $this->unchanged;
    }
}
