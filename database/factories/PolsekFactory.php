<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use App\Models\Polsek;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Polsek>
 */
class PolsekFactory extends Factory
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
            'name' => 'Polsek '.fake()->city(),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
            'personnel_count' => fake()->numberBetween(10, 60),
            'poskamling_count' => fake()->numberBetween(10, 50),
            'status' => 'aktif',
        ];
    }
}
