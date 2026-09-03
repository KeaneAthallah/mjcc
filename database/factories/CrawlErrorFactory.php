<?php

namespace Database\Factories;

use App\Models\CrawlError;
use App\Models\CrawlSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrawlError>
 */
class CrawlErrorFactory extends Factory
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
            'http_status' => fake()->randomElement([403, 404, 500, 503]),
            'message' => fake()->sentence(),
            'url' => fake()->url(),
            'retry_count' => 0,
            'occurred_at' => now(),
        ];
    }
}
