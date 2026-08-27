<?php

namespace Database\Seeders;

/**
 * Source data extracted from resources/dashboard.html.
 *
 * This is the initial seed data for the Morowali Juara Command Center.
 * Values are the aggregate totals found in the original dashboard per
 * kecamatan. After the first seed, the database becomes the source of truth.
 */
final class MorowaliData
{
    /**
     * Kecamatan-level source data keyed by kecamatan name.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function kecamatan(): array
    {
        return [
            'Bungku Tengah' => [
                'latitude' => -3.38, 'longitude' => 121.85,
                'pendidikan' => ['sd' => 22, 'smp' => 8, 'siswaL' => 3840, 'siswaP' => 3620, 'guru' => 420, 'kelas' => 168, 'tampung' => 4200, 'perpus' => 85, 'labIPA' => 60, 'labKom' => 52],
                'ketertiban' => ['kelurahan' => 16, 'polsek' => 1, 'tipkamtikmas' => 28, 'poskamling' => 42, 'pasar' => 5],
                'kesehatan' => ['puskesmas' => 3, 'pustu' => 8, 'rs' => 1, 'posyandu' => 38, 'dokter' => 12, 'perawat' => 48, 'bidan' => 18, 'bed' => 250],
            ],
            'Bungku Selatan' => [
                'latitude' => -3.55, 'longitude' => 121.75,
                'pendidikan' => ['sd' => 18, 'smp' => 6, 'siswaL' => 2960, 'siswaP' => 2810, 'guru' => 340, 'kelas' => 132, 'tampung' => 3500, 'perpus' => 72, 'labIPA' => 45, 'labKom' => 38],
                'ketertiban' => ['kelurahan' => 12, 'polsek' => 1, 'tipkamtikmas' => 22, 'poskamling' => 35, 'pasar' => 3],
                'kesehatan' => ['puskesmas' => 2, 'pustu' => 6, 'rs' => 0, 'posyandu' => 28, 'dokter' => 6, 'perawat' => 32, 'bidan' => 14, 'bed' => 120],
            ],
            'Bungku Timur' => [
                'latitude' => -3.35, 'longitude' => 122.05,
                'pendidikan' => ['sd' => 14, 'smp' => 4, 'siswaL' => 2100, 'siswaP' => 1980, 'guru' => 260, 'kelas' => 96, 'tampung' => 2800, 'perpus' => 60, 'labIPA' => 35, 'labKom' => 30],
                'ketertiban' => ['kelurahan' => 10, 'polsek' => 1, 'tipkamtikmas' => 18, 'poskamling' => 28, 'pasar' => 3],
                'kesehatan' => ['puskesmas' => 2, 'pustu' => 5, 'rs' => 0, 'posyandu' => 24, 'dokter' => 5, 'perawat' => 26, 'bidan' => 10, 'bed' => 80],
            ],
            'Bungku Pesisir' => [
                'latitude' => -3.45, 'longitude' => 121.65,
                'pendidikan' => ['sd' => 12, 'smp' => 4, 'siswaL' => 1800, 'siswaP' => 1720, 'guru' => 220, 'kelas' => 84, 'tampung' => 2400, 'perpus' => 55, 'labIPA' => 30, 'labKom' => 25],
                'ketertiban' => ['kelurahan' => 8, 'polsek' => 1, 'tipkamtikmas' => 14, 'poskamling' => 22, 'pasar' => 2],
                'kesehatan' => ['puskesmas' => 1, 'pustu' => 4, 'rs' => 0, 'posyandu' => 20, 'dokter' => 4, 'perawat' => 22, 'bidan' => 8, 'bed' => 60],
            ],
            'Lembo' => [
                'latitude' => -3.15, 'longitude' => 121.55,
                'pendidikan' => ['sd' => 10, 'smp' => 3, 'siswaL' => 1400, 'siswaP' => 1340, 'guru' => 180, 'kelas' => 68, 'tampung' => 1900, 'perpus' => 45, 'labIPA' => 25, 'labKom' => 20],
                'ketertiban' => ['kelurahan' => 7, 'polsek' => 1, 'tipkamtikmas' => 12, 'poskamling' => 18, 'pasar' => 2],
                'kesehatan' => ['puskesmas' => 1, 'pustu' => 4, 'rs' => 0, 'posyandu' => 18, 'dokter' => 3, 'perawat' => 18, 'bidan' => 8, 'bed' => 50],
            ],
            'Lembo Raya' => [
                'latitude' => -3.05, 'longitude' => 121.60,
                'pendidikan' => ['sd' => 8, 'smp' => 3, 'siswaL' => 1200, 'siswaP' => 1140, 'guru' => 150, 'kelas' => 56, 'tampung' => 1600, 'perpus' => 38, 'labIPA' => 20, 'labKom' => 18],
                'ketertiban' => ['kelurahan' => 6, 'polsek' => 0, 'tipkamtikmas' => 10, 'poskamling' => 15, 'pasar' => 2],
                'kesehatan' => ['puskesmas' => 1, 'pustu' => 3, 'rs' => 0, 'posyandu' => 16, 'dokter' => 2, 'perawat' => 14, 'bidan' => 6, 'bed' => 40],
            ],
            'Mori Utara' => [
                'latitude' => -2.90, 'longitude' => 121.70,
                'pendidikan' => ['sd' => 12, 'smp' => 4, 'siswaL' => 1680, 'siswaP' => 1600, 'guru' => 200, 'kelas' => 78, 'tampung' => 2100, 'perpus' => 50, 'labIPA' => 28, 'labKom' => 22],
                'ketertiban' => ['kelurahan' => 8, 'polsek' => 1, 'tipkamtikmas' => 14, 'poskamling' => 20, 'pasar' => 2],
                'kesehatan' => ['puskesmas' => 1, 'pustu' => 4, 'rs' => 1, 'posyandu' => 20, 'dokter' => 4, 'perawat' => 20, 'bidan' => 8, 'bed' => 100],
            ],
            'Mori Selatan' => [
                'latitude' => -3.10, 'longitude' => 121.80,
                'pendidikan' => ['sd' => 10, 'smp' => 3, 'siswaL' => 1360, 'siswaP' => 1300, 'guru' => 165, 'kelas' => 62, 'tampung' => 1800, 'perpus' => 42, 'labIPA' => 22, 'labKom' => 18],
                'ketertiban' => ['kelurahan' => 7, 'polsek' => 1, 'tipkamtikmas' => 10, 'poskamling' => 15, 'pasar' => 2],
                'kesehatan' => ['puskesmas' => 1, 'pustu' => 3, 'rs' => 0, 'posyandu' => 16, 'dokter' => 3, 'perawat' => 16, 'bidan' => 7, 'bed' => 45],
            ],
            'Petasia' => [
                'latitude' => -3.25, 'longitude' => 121.90,
                'pendidikan' => ['sd' => 14, 'smp' => 5, 'siswaL' => 2400, 'siswaP' => 2280, 'guru' => 280, 'kelas' => 108, 'tampung' => 3000, 'perpus' => 68, 'labIPA' => 42, 'labKom' => 35],
                'ketertiban' => ['kelurahan' => 8, 'polsek' => 1, 'tipkamtikmas' => 16, 'poskamling' => 26, 'pasar' => 3],
                'kesehatan' => ['puskesmas' => 2, 'pustu' => 5, 'rs' => 1, 'posyandu' => 22, 'dokter' => 8, 'perawat' => 32, 'bidan' => 14, 'bed' => 150],
            ],
            'Petasia Timur' => [
                'latitude' => -3.20, 'longitude' => 122.10,
                'pendidikan' => ['sd' => 8, 'smp' => 2, 'siswaL' => 1500, 'siswaP' => 1470, 'guru' => 180, 'kelas' => 64, 'tampung' => 1800, 'perpus' => 38, 'labIPA' => 20, 'labKom' => 15],
                'ketertiban' => ['kelurahan' => 4, 'polsek' => 1, 'tipkamtikmas' => 8, 'poskamling' => 14, 'pasar' => 2],
                'kesehatan' => ['puskesmas' => 1, 'pustu' => 4, 'rs' => 0, 'posyandu' => 14, 'dokter' => 5, 'perawat' => 22, 'bidan' => 9, 'bed' => 55],
            ],
        ];
    }

    /**
     * Polite list (Polsek) source data.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function polseks(): array
    {
        return [
            ['name' => 'Polsek Bungku Tengah', 'kecamatan' => 'Bungku Tengah', 'personnel' => 45, 'poskamling' => 42],
            ['name' => 'Polsek Bungku Selatan', 'kecamatan' => 'Bungku Selatan', 'personnel' => 32, 'poskamling' => 35],
            ['name' => 'Polsek Bungku Timur', 'kecamatan' => 'Bungku Timur', 'personnel' => 28, 'poskamling' => 28],
            ['name' => 'Polsek Bungku Pesisir', 'kecamatan' => 'Bungku Pesisir', 'personnel' => 25, 'poskamling' => 22],
            ['name' => 'Polsek Lembo', 'kecamatan' => 'Lembo', 'personnel' => 22, 'poskamling' => 18],
            ['name' => 'Polsek Mori Utara', 'kecamatan' => 'Mori Utara', 'personnel' => 24, 'poskamling' => 20],
            ['name' => 'Polsek Mori Selatan', 'kecamatan' => 'Mori Selatan', 'personnel' => 20, 'poskamling' => 15],
            ['name' => 'Polsek Petasia', 'kecamatan' => 'Petasia', 'personnel' => 30, 'poskamling' => 26],
            ['name' => 'Polsek Petasia Timur', 'kecamatan' => 'Petasia Timur', 'personnel' => 18, 'poskamling' => 14],
        ];
    }
}
