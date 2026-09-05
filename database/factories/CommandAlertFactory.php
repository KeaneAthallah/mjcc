<?php

namespace Database\Factories;

use App\Models\CommandAlert;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommandAlert>
 */
class CommandAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rule' => 'kondisi_gedung_sekolah',
            'severity' => CommandAlert::SEVERITY_WARNING,
            'sector_key' => 'pendidikan',
            'sector' => 'Pendidikan',
            'title' => 'Kondisi gedung perlu perhatian',
            'description' => 'Sebuah sekolah memerlukan perhatian.',
            'resource_type' => School::class,
            'resource_slug' => 'school',
            'resource_id' => fn () => School::factory()->create(['condition' => 'baik', 'teachers' => 15])->id,
            'kecamatan_id' => Kecamatan::factory(),
            'latitude' => null,
            'longitude' => null,
            'detail_route' => 'education.schools.show',
            'detail_params' => ['id' => 1],
            'status' => CommandAlert::STATUS_BARU,
            'opened_at' => now(),
            'last_seen_at' => now(),
            'resolved_at' => null,
            'resolved_by' => null,
        ];
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => CommandAlert::SEVERITY_CRITICAL,
            'sector_key' => 'kesehatan',
            'sector' => 'Kesehatan',
            'title' => 'Faskes tanpa tenaga medis',
            'rule' => 'faskes_tanpa_tenaga_medis',
            'resource_type' => HealthFacility::class,
            'resource_slug' => 'health_facility',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommandAlert::STATUS_SELESAI,
            'resolved_at' => now(),
        ]);
    }
}
