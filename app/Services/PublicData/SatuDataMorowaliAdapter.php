<?php

namespace App\Services\PublicData;

use App\Models\ExternalData;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;

/**
 * Adapter for the existing Satu Data Morowali scraper.
 * Wraps the existing PublicDataScraper/SatuDataScraper infrastructure.
 */
class SatuDataMorowaliAdapter implements PublicDataSourceAdapter
{
    private PublicDataScraper $scraper;

    public function __construct()
    {
        $this->scraper = app(PublicDataScraper::class);
    }

    public function fetch(PublicDataSource $source, array $config): array
    {
        $results = $this->scraper->scrape(null, 0, false);

        return ['data' => $results, 'metadata' => ['sectors' => array_keys(config('public_data.sectors', []))]];
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        return $raw['data'] ?? [];
    }

    public function store(array $records, PublicDataSource $source): array
    {
        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($records as $result) {
            $totalCreated += $result['created'] ?? 0;
            $totalUpdated += $result['updated'] ?? 0;
        }

        return ['created' => $totalCreated, 'updated' => $totalUpdated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        return [
            'total_records' => ExternalData::where('source_key', 'satudata')->count(),
            'total_datasets' => ExternalData::where('source_key', 'satudata')->distinct()->count('dataset'),
            'sectors' => config('public_data.sectors', []),
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Satu Data Morowali';
    }
}
