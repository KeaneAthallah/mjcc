<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Tipkamtikmas;
use Illuminate\Database\Seeder;

class TipkamtikmasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatanList = Kecamatan::with('kelurahans')->get()->keyBy('name');

        foreach (MorowaliData::kecamatan() as $name => $data) {
            $kecamatan = $kecamatanList[$name];
            $count = $data['ketertiban']['tipkamtikmas'];
            $kelurahans = $kecamatan->kelurahans->all();

            for ($i = 0; $i < $count; $i++) {
                $coords = CoordinateSeeder::near($kecamatan->latitude, $kecamatan->longitude, $i + 3);
                $kelurahan = count($kelurahans) > 0 ? $kelurahans[$i % count($kelurahans)] : null;

                $title = 'Tipkamtikmas ' . ($kelurahan?->name ?? $kecamatan->name) . ' ' . ($i + 1);

                Tipkamtikmas::updateOrCreate(
                    ['title' => $title],
                    [
                        'kecamatan_id' => $kecamatan->id,
                        'kelurahan_id' => $kelurahan?->id,
                        'description' => 'Pos keamanan lingkungan ' . ($kelurahan?->name ?? $kecamatan->name),
                        'status' => 'aktif',
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                    ]
                );
            }
        }
    }
}
