<?php

namespace Database\Factories;

use App\Models\CrawlRun;
use App\Models\CrawlSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrawlRun>
 */
class CrawlRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crawl_source_id' => CrawlSource::factory(),
            'started_at' => now(),
            'finished_at' => now(),
            'status' => CrawlRun::STATUS_SUCCESS,
            'records_found' => 0,
            'records_created' => 0,
            'records_updated' => 0,
            'records_unchanged' => 0,
            'records_failed' => 0,
            'error_count' => 0,
            'duration' => 1000,
            'log' => [],
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
