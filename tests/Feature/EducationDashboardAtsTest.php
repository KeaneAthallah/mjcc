<?php

use App\Models\ExternalData;
use App\Models\Kecamatan;
use App\Models\User;

it('renders the ATS section on the education dashboard with data', function () {
    $admin = User::factory()->admin()->create();
    Kecamatan::factory()->create(['name' => 'Bahodopi']);

    $rows = [
        ['Bahodopi', 'Anak Tidak Sekolah', 100],
        ['Bahodopi', 'Anak Tidak Sekolah - DO', 60],
        ['Bahodopi', 'Anak Tidak Sekolah - LTM', 25],
        ['Bahodopi', 'Anak Tidak Sekolah - BPB', 15],
        ['Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah', 20],
        ['Bahodopi', 'Anak Tidak Sekolah - Verifikasi Sudah', 30],
        ['Bahodopi', 'Anak Tidak Sekolah - Verifikasi Belum', 70],
        ['Bahodopi', 'Anak Tidak Sekolah - Verifikasi Alasan k_1', 10],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah', 100],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - DO', 60],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - LTM', 25],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - BPB', 15],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - Kembali Sekolah', 20],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - Verifikasi Sudah', 30],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - Verifikasi Belum', 70],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - Verifikasi Alasan k_1', 10],
    ];

    foreach ($rows as [$location, $indicator, $value]) {
        ExternalData::create([
            'sector' => 'pendidikan',
            'source' => 'ats',
            'source_key' => 'ats',
            'source_url' => 'https://ats.example',
            'dataset' => 'Verval ATS',
            'topic' => 'Pendidikan',
            'year' => now()->year,
            'location' => $location,
            'indicator' => $indicator,
            'value' => $value,
            'unit' => 'Anak',
            'dedupe_key' => ExternalData::dedupeKey('pendidikan', 'ats', 'Verval ATS', now()->year, $location, $indicator),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('education.dashboard'))
        ->assertOk()
        ->assertSee('Anak Tidak Sekolah (ATS)')
        ->assertSee('Progres Verifikasi')
        ->assertSee('Progres Pemulihan')
        ->assertSee('Kecamatan Prioritas')
        ->assertSee('Alasan Verifikasi')
        ->assertSee('Bahodopi');
});
