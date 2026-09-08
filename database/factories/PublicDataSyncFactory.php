<?php

namespace Database\Factories;

use App\Models\PublicDataSync;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PublicDataSync>
 */
class PublicDataSyncFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sector' => $this->faker->unique()->randomElement(PublicDataSync::SECTORS),
            'status' => PublicDataSync::STATUS_PENDING,
            'last_attempt_at' => null,
            'last_success_at' => null,
            'last_error' => null,
            'record_count' => 0,
        ];
    }
}
