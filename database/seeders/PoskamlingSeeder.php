<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Poskamling;
use Illuminate\Database\Seeder;

class PoskamlingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatanList = Kecamatan::with('kelurahans')->get()->keyBy('name');

        foreach (MorowaliData::kecamatan() as $name => $data) {
            $kecamatan = $kecamatanList[$name];
            $count = $data['ketertiban']['poskamling'];
            $kelurahans = $kecamatan->kelurahans->all();

            for ($i = 0; $i < $count; $i++) {
                $coords = CoordinateSeeder::near($kecamatan->latitude, $kecamatan->longitude, $i + 5);
                $kelurahan = count($kelurahans) > 0 ? $kelurahans[$i % count($kelurahans)] : null;

                $name = 'Poskamling ' . ($kelurahan?->name ?? $kecamatan->name) . ' ' . ($i + 1);

                Poskamling::updateOrCreate(
                    ['name' => $name],
                    [
                        'kecamatan_id' => $kecamatan->id,
                        'kelurahan_id' => $kelurahan?->id,
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                        'status' => 'aktif',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
