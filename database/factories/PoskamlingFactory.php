<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Poskamling;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poskamling>
 */
class PoskamlingFactory extends Factory
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
            'name' => 'Poskamling '.fake()->city().' '.fake()->numberBetween(1, 50),
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
            'status' => 'aktif',
            'is_active' => true,
        ];
    }
}
