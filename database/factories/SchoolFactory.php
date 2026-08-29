<?php

namespace Database\Factories;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([School::TYPE_SD, School::TYPE_SMP]);

        return [
            'kecamatan_id' => Kecamatan::factory(),
            'kelurahan_id' => Kelurahan::factory(),
            'name' => ($type === School::TYPE_SD ? 'SDN ' : 'SMPN ').fake()->unique()->numberBetween(1, 200).' '.fake()->city(),
            'school_type' => $type,
            'npsn' => fake()->unique()->numerify('#########'),
            'address' => fake()->streetAddress(),
            'latitude' => fake()->latitude(-3.7, -2.6),
            'longitude' => fake()->longitude(121.4, 122.3),
            'condition' => fake()->randomElement(['baik', 'rusak ringan', 'rusak berat']),
            'students_male' => fake()->numberBetween(20, 400),
            'students_female' => fake()->numberBetween(20, 400),
            'teachers' => fake()->numberBetween(5, 80),
            'classes' => fake()->numberBetween(3, 30),
            'capacity' => fake()->numberBetween(50, 900),
            'library_percentage' => fake()->numberBetween(30, 100),
            'science_lab_percentage' => fake()->numberBetween(30, 100),
            'computer_lab_percentage' => fake()->numberBetween(30, 100),
            'teacher_room_percentage' => fake()->numberBetween(30, 100),
            'toilet_percentage' => fake()->numberBetween(30, 100),
            'worship_room_percentage' => fake()->numberBetween(30, 100),
            'is_active' => true,
        ];
    }

    public function sd(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_type' => School::TYPE_SD,
        ]);
    }

    public function smp(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_type' => School::TYPE_SMP,
        ]);
    }
}
