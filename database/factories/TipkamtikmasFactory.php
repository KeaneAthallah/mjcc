<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Tipkamtikmas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tipkamtikmas>
 */
class TipkamtikmasFactory extends Factory
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
            'kelurahan_id' => Kelurahan::factory(),
            'title' => 'Tipkamtikmas '.fake()->city(),
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['aktif', 'tidak aktif']),
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
        ];
    }
}
