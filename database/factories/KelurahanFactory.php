<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kelurahan>
 */
class KelurahanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kecamatan_id' => Kecamatan::factory(),
            'name' => fake()->unique()->city() . ' ' . fake()->randomElement(['Desa', 'Kelurahan']),
            'code' => fake()->unique()->numerify('7402####'),
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
            'population' => fake()->numberBetween(500, 8000),
            'status' => fake()->randomElement(['aktif', 'aktif', 'tidak aktif']),
        ];
    }
}
