<?php

namespace Database\Factories;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthFacility>
 */
class HealthFacilityFactory extends Factory
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
            'name' => fake()->unique()->company().' '.fake()->randomElement(['Puskesmas', 'Pustu', 'Posyandu']),
            'facility_type' => fake()->randomElement([
                HealthFacility::TYPE_PUSKESMAS,
                HealthFacility::TYPE_PUSTU,
                HealthFacility::TYPE_POSYANDU,
            ]),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
            'condition' => fake()->randomElement(['baik', 'rusak ringan', 'rusak berat']),
            'beds' => fake()->numberBetween(0, 100),
            'doctors' => fake()->numberBetween(0, 15),
            'nurses' => fake()->numberBetween(0, 40),
            'midwives' => fake()->numberBetween(0, 20),
            'status' => 'aktif',
            'phone' => fake()->phoneNumber(),
            'description' => fake()->sentence(),
        ];
    }
}
