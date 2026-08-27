<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use Illuminate\Database\Seeder;

class KecamatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (MorowaliData::kecamatan() as $name => $data) {
            Kecamatan::updateOrCreate(
                ['name' => $name],
                [
                    'code' => $this->codeFor($name),
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'description' => "Kecamatan {$name} di Kabupaten Morowali, Sulawesi Tengah.",
                    'is_active' => true,
                ]
            );
        }
    }

    private function codeFor(string $name): string
    {
        $map = [
            'Bungku Tengah' => '72.06.05',
            'Bungku Selatan' => '72.06.06',
            'Bungku Timur' => '72.06.18',
            'Bungku Pesisir' => '72.06.15',
            'Lembo' => '72.06.04',
            'Lembo Raya' => '72.06.19',
            'Mori Utara' => '72.06.09',
            'Mori Selatan' => '72.06.08',
            'Petasia' => '72.06.01',
            'Petasia Timur' => '72.06.20',
        ];

        return $map[$name] ?? '72.06.00';
    }
}
