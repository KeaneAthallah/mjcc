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
        $allowed = array_map('mb_strtolower', config('public_data.morowali.kecamatan', []));

        foreach (MorowaliData::kecamatan() as $name => $data) {
            if (! in_array(mb_strtolower($name), $allowed, true)) {
                continue;
            }

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
        ];

        return $map[$name] ?? '72.06.00';
    }
}
