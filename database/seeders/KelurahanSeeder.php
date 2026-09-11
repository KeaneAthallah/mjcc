<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Database\Seeder;

class KelurahanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MorowaliData::kecamatan() as $name => $data) {
            $kecamatan = Kecamatan::where('name', $name)->firstOrFail();

            $count = $data['ketertiban']['kelurahan'];

            for ($i = 0; $i < $count; $i++) {
                $coords = CoordinateSeeder::near($kecamatan->latitude, $kecamatan->longitude, $i);

                Kelurahan::updateOrCreate(
                    [
                        'kecamatan_id' => $kecamatan->id,
                        'name' => $this->nameFor($name, $i),
                    ],
                    [
                        'code' => sprintf('%s-%02d', str_replace('.', '', $kecamatan->code), $i + 1),
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                        'population' => $this->populationFor($name, $i + 1),
                        'status' => 'aktif',
                    ]
                );
            }
        }
    }

    private function nameFor(string $kecamatan, int $i): string
    {
        $names = [
            'Bungku Tengah' => ['Bungi', 'Lahongo', 'Marsaoleh', 'Matano', 'Mendui', 'Po`ona', 'Rompi', 'Sambalagi', 'Toili', 'Tondo', 'Umpanga', 'Bahomotefe', 'Lambelu', 'Lara', 'Matansala', 'Puunipa'],
            'Bungku Selatan' => ['Bungku', 'Kaleroang', 'Lembo', 'One Ete', 'Po`o', 'Sungkur', 'Tamboro', 'Tandaoleo', 'Ululere', 'Umatara', 'Anua', 'Bungku Tengah'],
            'Bungku Timur' => ['Bahoea', 'Beroa', 'Kajulangko', 'Lambelu', 'Lantula Jaya', 'Mahoro', 'Molonggota', 'Noha', 'Sangia', 'Waetuwo'],
            'Bungku Pesisir' => ['Bahomoahi', 'Basing', 'Bunggur', 'Lanto', 'Maleo', 'Nane', 'Pangkea', 'Sarombu'],
        ];

        $list = $names[$kecamatan] ?? [];

        return $list[$i] ?? ($kecamatan.' Kelurahan '.($i + 1));
    }

    private function populationFor(string $kecamatan, int $index): int
    {
        $totals = [
            'Bungku Tengah' => 48000,
            'Bungku Selatan' => 37000,
            'Bungku Timur' => 26000,
            'Bungku Pesisir' => 22000,
        ];

        $total = $totals[$kecamatan] ?? 20000;
        $count = MorowaliData::kecamatan()[$kecamatan]['ketertiban']['kelurahan'];

        return intdiv($total, max($count, 1));
    }
}
