<?php

namespace Database\Seeders;

use App\Models\SosAlert;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Optional demo data for the SOS module.
 *
 *  - Generates deterministic "historical" alerts (resolved/cancelled) so the
 *    inbox has realistic content.
 *  - Open (active) alerts are NEVER created unless SOS_DEMO_WITH_ACTIVE=true,
 *    to avoid fake live emergencies in real deployments.
 */
class SosDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction() && env('SOS_DEMO_WITH_ACTIVE') !== true) {
            $this->command?->warn('SOS demo seeder diganti di lingkungan production tanpa SOS_DEMO_WITH_ACTIVE=true.');
        }

        if ((int) env('SOS_DEMO_ALERTS', 20) < 1) {
            $this->command?->warn('SOS_DEMO_ALERTS harus >= 1 untuk membuat data demo.');

            return;
        }

        if (SosAlert::query()->exists()) {
            $this->command?->warn('Tabel sos_alerts sudah memiliki data, seeder dilewati.');

            return;
        }

        $users = User::query()->whereIn('role', ['viewer', 'operator'])->get(['id', 'name']);

        if ($users->isEmpty()) {
            $this->command?->warn('Tidak ada user untuk membuat data demo SOS.');

            return;
        }

        $total = (int) env('SOS_DEMO_ALERTS', 20);
        $now = now()->subDays(max(1, (int) env('SOS_DEMO_DAYS', 30)));

        foreach ($users as $i => $user) {
            $createdAt = $now->copy()->addDays(fake()->numberBetween(0, 6))->addHours($user->id % 12);

            SosAlert::factory()->resolved()->create([
                'user_id' => $user->id,
                'message' => 'Kejadian darurat di sekitar pemukiman, mohon bantuan.',
                'created_at' => $createdAt,
                'responded_at' => $createdAt->copy()->addMinutes(20),
                'resolved_at' => $createdAt->copy()->addHours(2),
                'accuracy' => 15 + ($i * 7) % 60,
            ]);

            if ($total > $users->count()) {
                SosAlert::factory()->cancelled()->create([
                    'user_id' => $user->id,
                    'message' => 'Dibatalkan karena kejadian sudah tertangani mandiri.',
                    'created_at' => $now->copy()->addDays(fake()->numberBetween(10, 25))->addHours(($user->id + 3) % 12),
                ]);
            }
        }
    }
}
