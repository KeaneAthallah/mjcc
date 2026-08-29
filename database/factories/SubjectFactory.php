<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Matematika',
                'Bahasa Indonesia',
                'Bahasa Inggris',
                'IPA',
                'IPS',
                'PPKn',
                'Pendidikan Agama',
                'Penjaskes',
                'Seni Budaya',
                'Prakarya',
                'Informatika',
                'Bahasa Daerah',
            ]),
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
