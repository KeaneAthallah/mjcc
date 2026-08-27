<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['Matematika', 'MTK', 'Mata pelajaran matematika untuk jenjang SD dan SMP.'],
            ['Bahasa Indonesia', 'BIN', 'Mata pelajaran Bahasa Indonesia.'],
            ['Bahasa Inggris', 'BIG', 'Mata pelajaran Bahasa Inggris.'],
            ['IPA', 'IPA', 'Ilmu Pengetahuan Alam.'],
            ['IPS', 'IPS', 'Ilmu Pengetahuan Sosial.'],
            ['PPKn', 'PKN', 'Pendidikan Pancasila dan Kewarganegaraan.'],
            ['Pendidikan Agama', 'PABP', 'Pendidikan Agama dan Budi Pekerti.'],
            ['PJOK', 'PJO', 'Pendidikan Jasmani, Olahraga, dan Kesehatan.'],
            ['Seni Budaya', 'SBK', 'Seni Budaya dan Keterampilan.'],
            ['Prakarya', 'PRK', 'Mata pelajaran Prakarya.'],
            ['Informatika', 'INF', 'Mata pelajaran informatika.'],
            ['Bahasa Daerah', 'BDA', 'Muatan lokal Bahasa Daerah.'],
            ['Sejarah', 'SJH', 'Mata pelajaran Sejarah.'],
            ['Geografi', 'GEO', 'Mata pelajaran Geografi.'],
            ['Ekonomi', 'EKO', 'Mata pelajaran Ekonomi.'],
            ['Sosiologi', 'SOS', 'Mata pelajaran Sosiologi.'],
            ['Biologi', 'BIO', 'Mata pelajaran Biologi.'],
            ['Fisika', 'FIS', 'Mata pelajaran Fisika.'],
        ];

        foreach ($subjects as [$name, $code, $description]) {
            Subject::updateOrCreate(
                ['name' => $name],
                [
                    'code' => $code,
                    'description' => $description,
                    'is_active' => true,
                ]
            );
        }
    }
}
