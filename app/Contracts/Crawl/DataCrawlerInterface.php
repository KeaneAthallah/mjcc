<?php

namespace App\Contracts\Crawl;

use App\Support\Crawl\CrawlResult;

/**
 * Contract every crawler connector must implement. Connectors are responsible
 * only for fetching from the external source and producing normalized
 * RawRecord objects. Persisting to the database is handled by the sync layer.
 */
interface DataCrawlerInterface
{
    /**
     * The canonical source slug (must match a CrawlSource entry).
     */
    public function source(): string;

    /**
     * Fetch external data and persist new/changed/unchanged records.
     */
    public function sync(): CrawlResult;
}
