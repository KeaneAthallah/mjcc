<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kecamatan>
 */
class KecamatanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->city() . ' ' . fake()->randomElement(['Utara', 'Selatan', 'Tengah', 'Timur']),
            'code' => fake()->unique()->numerify('7402####'),
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
