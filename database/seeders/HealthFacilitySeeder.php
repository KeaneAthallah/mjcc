<?php

namespace Database\Seeders;

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use Illuminate\Database\Seeder;

class HealthFacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatanList = Kecamatan::all()->keyBy('name');

        foreach (MorowaliData::kecamatan() as $name => $data) {
            $kecamatan = $kecamatanList[$name];
            $k = $data['kesehatan'];

            $plans = [
                ['type' => HealthFacility::TYPE_RS, 'count' => $k['rs'], 'weight' => ['doctors' => 6, 'nurses' => 6, 'midwives' => 2, 'beds' => 10]],
                ['type' => HealthFacility::TYPE_PUSKESMAS, 'count' => $k['puskesmas'], 'weight' => ['doctors' => 3, 'nurses' => 3, 'midwives' => 3, 'beds' => 4]],
                ['type' => HealthFacility::TYPE_PUSTU, 'count' => $k['pustu'], 'weight' => ['doctors' => 1, 'nurses' => 1, 'midwives' => 2, 'beds' => 1]],
                ['type' => HealthFacility::TYPE_POSYANDU, 'count' => $k['posyandu'], 'weight' => ['doctors' => 0, 'nurses' => 0, 'midwives' => 1, 'beds' => 0]],
            ];

            $facilities = [];

            foreach ($plans as $plan) {
                for ($i = 0; $i < $plan['count']; $i++) {
                    $facilities[] = [
                        'type' => $plan['type'],
                        'weight' => $plan['weight'],
                        'index' => $i,
                    ];
                }
            }

            $resources = [
                'doctors' => $k['dokter'],
                'nurses' => $k['perawat'],
                'midwives' => $k['bidan'],
                'beds' => $k['bed'],
            ];

            $allocated = $this->allocate($facilities, $resources);

            foreach ($facilities as $index => $facility) {
                $a = $allocated[$index];
                $coords = CoordinateSeeder::near($kecamatan->latitude, $kecamatan->longitude, $index + 10);

                $facilityName = $this->nameFor($facility['type'], $kecamatan->name, $facility['index']);

                HealthFacility::updateOrCreate(
                    ['name' => $facilityName],
                    [
                        'kecamatan_id' => $kecamatan->id,
                        'facility_type' => $facility['type'],
                        'address' => 'Jl. Layanan '.$kecamatan->name,
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                        'condition' => 'baik',
                        'beds' => $a['beds'],
                        'doctors' => $a['doctors'],
                        'nurses' => $a['nurses'],
                        'midwives' => $a['midwives'],
                        'status' => 'aktif',
                        'phone' => $this->phoneFor($kecamatan->name, $facility['index']),
                        'description' => $facility['type'].' di kecamatan '.$kecamatan->name,
                    ]
                );
            }
        }
    }

    /**
     * Allocate resource totals proportionally by weight, fixing rounding so
     * each resource sums exactly to its target.
     *
     * @param  array<int, array<string, mixed>>  $facilities
     * @param  array<string, int>  $targets
     * @return array<int, array<string, int>>
     */
    private function allocate(array $facilities, array $targets): array
    {
        $result = [];

        foreach ($facilities as $i => $f) {
            $result[$i] = ['doctors' => 0, 'nurses' => 0, 'midwives' => 0, 'beds' => 0];
        }

        $weightSums = [];
        foreach (['doctors', 'nurses', 'midwives', 'beds'] as $resource) {
            $weightSums[$resource] = array_sum(array_column(array_column($facilities, 'weight'), $resource));
        }

        $lastIndex = count($facilities) - 1;

        foreach (['doctors', 'nurses', 'midwives', 'beds'] as $resource) {
            $weightSum = max($weightSums[$resource], 1);

            foreach ($facilities as $i => $f) {
                $weight = $f['weight'][$resource];
                $result[$i][$resource] = intdiv($targets[$resource] * $weight, $weightSum);
            }

            $sum = array_sum(array_column($result, $resource));
            $remainder = $targets[$resource] - $sum;
            if ($lastIndex >= 0 && $sum !== $targets[$resource]) {
                $result[$lastIndex][$resource] += $remainder;
            }
        }

        return $result;
    }

    private function nameFor(string $type, string $kecamatan, int $index): string
    {
        return match ($type) {
            HealthFacility::TYPE_RS => "RSUD {$kecamatan} ".($index + 1),
            HealthFacility::TYPE_PUSKESMAS => "Puskesmas {$kecamatan} ".($index + 1),
            HealthFacility::TYPE_PUSTU => "Pustu {$kecamatan} ".($index + 1),
            default => "Posyandu {$kecamatan} ".($index + 1),
        };
    }

    private function phoneFor(string $name, int $index): string
    {
        return '0'.(100000000 + crc32($name) % 700000000 + $index);
    }
}
