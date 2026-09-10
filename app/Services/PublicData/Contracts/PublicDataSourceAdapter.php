<?php

namespace App\Services\PublicData\Contracts;

use App\Models\PublicDataSource;

/**
 * Interface for all public data source adapters.
 * Each source (ATS, Dapodik, SP2KP, BPS, IRBI, Sitaba, APBD, SatuData)
 * implements this contract to provide a uniform sync/normalize/store pipeline.
 */
interface PublicDataSourceAdapter
{
    /**
     * Fetch raw data from the external source.
     *
     * @return array{data: mixed, metadata: array}
     */
    public function fetch(PublicDataSource $source, array $config): array;

    /**
     * Normalize raw data into a consistent format for storage.
     *
     * @param  array{data: mixed, metadata: array}  $raw
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $raw, PublicDataSource $source): array;

    /**
     * Store normalized records into the database (upsert for idempotency).
     *
     * @param  array<int, array<string, mixed>>  $records
     * @return array{created: int, updated: int, failed: int}
     */
    public function store(array $records, PublicDataSource $source): array;

    /**
     * Get source-specific summary statistics for the dashboard.
     *
     * @return array<string, mixed>
     */
    public function getSummary(PublicDataSource $source): array;

    /**
     * Validate that the source configuration is complete.
     */
    public function validateConfig(array $config): bool;

    /**
     * Get human-readable name of this adapter.
     */
    public function name(): string;
}
