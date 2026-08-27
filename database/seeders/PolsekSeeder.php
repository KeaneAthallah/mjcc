<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Polsek;
use Illuminate\Database\Seeder;

class PolsekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MorowaliData::polseks() as $polsek) {
            $kecamatan = Kecamatan::where('name', $polsek['kecamatan'])->first();
            if ($kecamatan === null) {
                continue;
            }

            $coords = CoordinateSeeder::near($kecamatan->latitude, $kecamatan->longitude, 1);

            Polsek::updateOrCreate(
                ['name' => $polsek['name']],
                [
                    'kecamatan_id' => $kecamatan->id,
                    'address' => 'Jl. Poros ' . $kecamatan->name,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'personnel_count' => $polsek['personnel'],
                    'poskamling_count' => $polsek['poskamling'],
                    'status' => 'aktif',
                ]
            );
        }
    }
}
