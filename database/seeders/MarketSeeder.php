<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Market;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatanList = Kecamatan::all()->keyBy('name');

        foreach (MorowaliData::kecamatan() as $name => $data) {
            $kecamatan = $kecamatanList[$name];
            $count = $data['ketertiban']['pasar'];

            for ($i = 0; $i < $count; $i++) {
                $coords = CoordinateSeeder::near($kecamatan->latitude, $kecamatan->longitude, $i + 8);

                $marketName = 'Pasar '.$kecamatan->name.' '.($i + 1);

                Market::updateOrCreate(
                    ['name' => $marketName],
                    [
                        'kecamatan_id' => $kecamatan->id,
                        'address' => 'Jl. Pasar '.$kecamatan->name,
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                        'status' => 'aktif',
                    ]
                );
            }
        }
    }
}
