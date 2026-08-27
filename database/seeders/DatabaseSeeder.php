<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            KecamatanSeeder::class,
            SubjectSeeder::class,
            KelurahanSeeder::class,
            SchoolSeeder::class,
            PolsekSeeder::class,
            PoskamlingSeeder::class,
            TipkamtikmasSeeder::class,
            MarketSeeder::class,
            HealthFacilitySeeder::class,
            UserSeeder::class,
        ]);
    }
}
