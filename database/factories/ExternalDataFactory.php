<?php

namespace Database\Factories;

use App\Models\ExternalData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalData>
 */
class ExternalDataFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sector = $this->faker->randomElement(['pendidikan', 'kesehatan', 'keamanan']);
        $location = 'Kabupaten Morowali';
        $indicator = strtoupper($this->faker->word());

        return [
            'sector' => $sector,
            'source' => 'satudata',
            'source_url' => 'https://data.morowalikab.go.id/dataset/detail/'.fake()->md5(),
            'dataset' => ucfirst(fake()->words(3, true)),
            'topic' => 'Bidang '.ucfirst($sector),
            'year' => fake()->randomElement([2022, 2023, 2024]),
            'location' => $location,
            'indicator' => $indicator,
            'value' => fake()->randomFloat(0, 1, 1000),
            'unit' => fake()->optional()->randomElement([null, 'Jiwa', 'Unit', 'Orang', '%']),
            'dedupe_key' => ExternalData::dedupeKey($sector, 'satudata', fake()->word(), null, $location, $indicator),
            'raw_data' => ['province_code' => '72', 'kabupaten_code' => '7206'],
            'scraped_at' => now(),
        ];
    }
}
