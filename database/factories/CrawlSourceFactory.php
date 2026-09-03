<?php

namespace Database\Factories;

use App\Models\CrawlSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CrawlSource>
 */
class CrawlSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = 'src-'.Str::lower(Str::random(8));

        return [
            'name' => Str::upper(str_replace('-', ' ', $slug)),
            'slug' => $slug,
            'base_url' => 'https://example.org/'.$slug,
            'source_type' => 'remote',
            'is_active' => true,
            'configuration' => [],
        ];
    }

    public function slug(string $slug): static
    {
        return $this->state(fn () => ['slug' => $slug, 'name' => strtoupper($slug)]);
    }
}
