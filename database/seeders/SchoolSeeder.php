<?php

namespace Database\Seeders;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kecamatanList = Kecamatan::with('kelurahans')->get()->keyBy('name');

        foreach (MorowaliData::kecamatan() as $name => $data) {
            $kecamatan = $kecamatanList[$name];
            $p = $data['pendidikan'];
            $kelurahans = $kecamatan->kelurahans->all();

            $sdDistribution = $this->distribute($p['sd'], $p['siswaL'], $p['siswaP'], $p['guru'], $p['kelas'], $p['tampung']);
            $smpDistribution = $this->distribute($p['smp'], 0, 0, 0, 0, 0);

            $index = 0;

            foreach ($sdDistribution as $school) {
                $this->createSchool($kecamatan, $kelurahans, School::TYPE_SD, $name, $index, $school, $p);
                $index++;
            }

            foreach ($smpDistribution as $school) {
                $this->createSchool($kecamatan, $kelurahans, School::TYPE_SMP, $name, $index, $school, $p);
                $index++;
            }
        }
    }

    /**
     * Distribute aggregate values into per-school buckets that sum exactly.
     *
     * @return array<int, array<string, int>>
     */
    private function distribute(int $count, int $siswaL, int $siswaP, int $guru, int $kelas, int $tampung): array
    {
        $buckets = [];
        for ($i = 0; $i < max($count, 1); $i++) {
            $buckets[] = [
                'siswaL' => $this->share($siswaL, $i, $count),
                'siswaP' => $this->share($siswaP, $i, $count),
                'guru' => $this->share($guru, $i, $count),
                'kelas' => $this->share($kelas, $i, $count),
                'tampung' => $this->share($tampung, $i, $count),
            ];
        }

        // Fix rounding remainder on the last bucket so sums match exactly.
        if ($count > 0) {
            $last = count($buckets) - 1;
            foreach (['siswaL', 'siswaP', 'guru', 'kelas', 'tampung'] as $key) {
                $sum = array_sum(array_column($buckets, $key));
                $remainder = match ($key) {
                    'siswaL' => $siswaL,
                    'siswaP' => $siswaP,
                    'guru' => $guru,
                    'kelas' => $kelas,
                    'tampung' => $tampung,
                } - $sum;
                $buckets[$last][$key] += $remainder;
            }
        }

        return $buckets;
    }

    private function share(int $total, int $index, int $count): int
    {
        return intdiv($total, max($count, 1));
    }

    private function createSchool(Kecamatan $kecamatan, array $kelurahans, string $type, string $kecName, int $index, array $school, array $p): void
    {
        $coords = CoordinateSeeder::near($kecamatan->latitude, $kecamatan->longitude, $index);
        $kelurahan = $kelurahans[count($kelurahans) > 0 ? $index % count($kelurahans) : 0] ?? null;

        $name = $this->schoolName($type, $kecName, $index);

        School::updateOrCreate(
            ['name' => $name],
            [
                'kecamatan_id' => $kecamatan->id,
                'kelurahan_id' => $kelurahan?->id,
                'school_type' => $type,
                'npsn' => $this->npsnFor($kecName, $index),
                'address' => 'Jl. ' . ($kelurahan?->name ?? $kecName) . ' No. ' . ($index + 1),
                'latitude' => $coords['latitude'],
                'longitude' => $coords['longitude'],
                'condition' => 'baik',
                'students_male' => $type === School::TYPE_SD ? $school['siswaL'] : 0,
                'students_female' => $type === School::TYPE_SD ? $school['siswaP'] : 0,
                'teachers' => $type === School::TYPE_SD ? $school['guru'] : 0,
                'classes' => $type === School::TYPE_SD ? max($school['kelas'], 1) : 0,
                'capacity' => $type === School::TYPE_SD ? max($school['tampung'], 1) : 0,
                'library_percentage' => $p['perpus'],
                'science_lab_percentage' => $p['labIPA'],
                'computer_lab_percentage' => $p['labKom'],
                'teacher_room_percentage' => $index % 2 === 0 ? 90 : 85,
                'toilet_percentage' => 80 + ($index % 3) * 5,
                'worship_room_percentage' => 60 + ($index % 4) * 8,
                'is_active' => true,
            ]
        );
    }

    private function schoolName(string $type, string $kecName, int $index): string
    {
        $prefix = $type === School::TYPE_SD ? 'SDN' : 'SMPN';
        $district = str_contains($kecName, ' ') ? explode(' ', $kecName)[1] . ' ' . explode(' ', $kecName)[0] : $kecName;

        return "{$prefix} {($index + 1)} {$district}";
    }

    private function npsnFor(string $kecName, int $index): string
    {
        $base = crc32($kecName);

        return (string) (100000000 + ($base % 900000000) + $index);
    }
}
