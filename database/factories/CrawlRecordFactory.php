<?php

namespace Database\Factories;

use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrawlRecord>
 */
class CrawlRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $crawlSource = CrawlSource::factory()->create();
        $crawlRun = CrawlRun::factory()->create(['crawl_source_id' => $crawlSource->id]);

        return [
            'crawl_source_id' => $crawlSource->id,
            'crawl_run_id' => $crawlRun->id,
            'external_id' => fake()->unique()->numerify('#######'),
            'record_type' => CrawlRecord::TYPE_SCHOOL,
            'name' => fake()->company(),
            'province_code' => '72',
            'kabupaten_code' => '7206',
            'kabupaten_name' => 'Kabupaten Morowali',
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
            'data' => [],
            'source_url' => null,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'content_hash' => sha1(fake()->sentence()),
        ];
    }

    public function morowaliUtara(): static
    {
        return $this->state(fn () => [
            'kabupaten_code' => '7212',
            'kabupaten_name' => 'Kabupaten Morowali Utara',
        ]);
    }

    public function recordType(string $type): static
    {
        return $this->state(fn () => ['record_type' => $type]);
    }
}
