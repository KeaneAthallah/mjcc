<?php

namespace Database\Factories;

use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SosAlert>
 */
class SosAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'latitude' => fake()->latitude(-3.6, -2.1),
            'longitude' => fake()->longitude(120.9, 122.1),
            'accuracy' => fake()->randomFloat(1, 3, 40),
            'status' => SosAlert::STATUS_ACTIVE,
            'message' => fake()->optional(0.6)->sentence(6),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SosAlert::STATUS_ACTIVE,
            'responded_by' => null,
            'response_message' => null,
            'responded_at' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    public function acknowledged(): static
    {
        return $this->state(function (array $attributes) {
            $responder = User::factory()->create();

            return [
                'status' => SosAlert::STATUS_ACKNOWLEDGED,
                'responded_by' => $responder->id,
                'response_message' => 'Permintaan SOS telah diterima.',
                'responded_at' => now(),
            ];
        });
    }

    public function responding(string $message = 'Petugas sedang menuju lokasi Anda.'): static
    {
        return $this->state(function (array $attributes) use ($message) {
            $responder = User::factory()->create();

            return [
                'status' => SosAlert::STATUS_RESPONDING,
                'responded_by' => $responder->id,
                'response_message' => $message,
                'responded_at' => now(),
            ];
        });
    }

    public function resolved(): static
    {
        return $this->state(function (array $attributes) {
            $responder = User::factory()->create();

            return [
                'status' => SosAlert::STATUS_RESOLVED,
                'responded_by' => $responder->id,
                'response_message' => 'SOS telah diselesaikan.',
                'responded_at' => now()->subMinutes(20),
                'resolved_by' => $responder->id,
                'resolved_at' => now(),
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SosAlert::STATUS_CANCELLED,
        ]);
    }

    public function accepted(?User $responder = null): static
    {
        return $this->state(function (array $attributes) use ($responder) {
            $responder ??= User::factory()->create(['responder_type' => 'medical']);

            return [
                'status' => SosAlert::STATUS_ACCEPTED,
                'accepted_by' => $responder->id,
                'accepted_at' => now(),
            ];
        });
    }

    public function onTheWay(): static
    {
        return $this->state(function (array $attributes) {
            $responder = User::factory()->create(['responder_type' => 'medical']);

            return [
                'status' => SosAlert::STATUS_ON_THE_WAY,
                'accepted_by' => $responder->id,
                'accepted_at' => now()->subMinutes(5),
            ];
        });
    }

    public function arrived(): static
    {
        return $this->state(function (array $attributes) {
            $responder = User::factory()->create(['responder_type' => 'medical']);

            return [
                'status' => SosAlert::STATUS_ARRIVED,
                'accepted_by' => $responder->id,
                'accepted_at' => now()->subMinutes(10),
            ];
        });
    }
}
