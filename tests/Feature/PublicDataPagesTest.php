<?php

use App\Models\ExternalData;
use App\Models\PublicDataSync;
use App\Models\User;

it('requires authentication', function () {
    $this->get(route('public-data.index'))->assertRedirect(route('login'));
});

it('lists every sector card on the overview page', function () {
    ExternalData::factory()->count(3)->create(['sector' => 'pendidikan']);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.index'))
        ->assertOk()
        ->assertSee('Pendidikan')
        ->assertSee('Kesehatan')
        ->assertSee('Keamanan')
        ->assertSee(route('public-data.show', 'pendidikan'))
        ->assertSee(route('public-data.show', 'kesehatan'))
        ->assertSee(route('public-data.show', 'keamanan'))
        ->assertSee('Belum disinkronkan')
        ->assertSee('Satu Data Morowali');
});

it('shows a filled sync status on the overview page', function () {
    ExternalData::factory()->count(5)->create(['sector' => 'kesehatan']);
    PublicDataSync::factory()->create([
        'sector' => 'kesehatan',
        'status' => PublicDataSync::STATUS_SUCCESS,
        'record_count' => 5,
        'last_attempt_at' => now()->subHour(),
        'last_success_at' => now()->subHour(),
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.index'))
        ->assertOk()
        ->assertSee('Berhasil');
});

it('renders a sector page with its records and filters', function () {
    ExternalData::factory()->create([
        'sector' => 'pendidikan',
        'dataset' => 'Jumlah Siswa di Sekolah',
        'year' => 2024,
        'location' => 'SDN 1 Bungintende',
        'indicator' => 'JUMLAH SISWA',
        'value' => 320,
        'unit' => null,
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.show', 'pendidikan'))
        ->assertOk()
        ->assertSee('Jumlah Siswa di Sekolah')
        ->assertSee('SDN 1 Bungintende')
        ->assertSee('320')
        ->assertSee('Semua Dataset')
        ->assertSee('Semua Tahun');
});

it('filters records by dataset and year', function () {
    ExternalData::factory()->create([
        'sector' => 'pendidikan',
        'dataset' => 'Diterima',
        'year' => 2024,
        'location' => 'SDN 1',
        'indicator' => 'SISWA',
        'value' => 100,
    ]);
    ExternalData::factory()->create([
        'sector' => 'pendidikan',
        'dataset' => 'Lainnya',
        'year' => 2023,
        'location' => 'SDN 2',
        'indicator' => 'SISWA',
        'value' => 200,
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.show', ['pendidikan', 'dataset' => 'Diterima']))
        ->assertOk()
        ->assertSee('Diterima')
        ->assertSee('100,00')
        ->assertDontSee('200,00');
});

it('renders an empty sector with a friendly message', function () {
    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.show', 'keamanan'))
        ->assertOk()
        ->assertSee('Belum Ada Data');
});

it('returns 404 for an unknown sector', function () {
    $this->actingAs(User::factory()->viewer()->create())
        ->get('/data-publik/maritim')
        ->assertNotFound();
});

it('warns when only stale data is available after a failed sync', function () {
    ExternalData::factory()->count(2)->create(['sector' => 'kesehatan']);
    PublicDataSync::factory()->create([
        'sector' => 'kesehatan',
        'status' => PublicDataSync::STATUS_FAILED,
        'last_error' => 'HTTP 500 saat mengambil halaman',
        'last_success_at' => now()->subDay(),
        'record_count' => 2,
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.show', 'kesehatan'))
        ->assertOk()
        ->assertSee('Sumber data belum berhasil diperbarui')
        ->assertSee('HTTP 500 saat mengambil halaman');
});

it('renders a dataset detail page with metadata and records', function () {
    $raw = [
        'metadata' => [
            'period' => 'Tahunan',
            'source' => 'Kompilasi Data Administrasi',
            'created' => '13 Februari 2024',
            'producer' => 'Dinas Pendidikan Daerah',
            'published' => '24 Januari 2025',
        ],
        'headers' => ['Kode Provinsi', 'Nama Provinsi', 'TAHUN', 'JUMLAH SISWA'],
        'row' => ['Kode Provinsi' => '72', 'Nama Provinsi' => 'Sulawesi Tengah', 'TAHUN' => 2024, 'JUMLAH SISWA' => 320],
    ];

    ExternalData::factory()->create([
        'sector' => 'pendidikan',
        'dataset' => 'Jumlah Siswa di Sekolah',
        'topic' => 'Bidang Pendidikan',
        'year' => 2024,
        'location' => 'SDN 1 Bungintende',
        'indicator' => 'JUMLAH SISWA',
        'value' => 320,
        'raw_data' => $raw,
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.dataset', ['sector' => 'pendidikan', 'dataset' => 'Jumlah Siswa di Sekolah']))
        ->assertOk()
        ->assertSee('Jumlah Siswa di Sekolah')
        ->assertSee('Bidang Pendidikan')
        ->assertSee('Tahunan')
        ->assertSee('Dinas Pendidikan Daerah')
        ->assertSee('Informasi Dataset')
        ->assertSee('JUMLAH SISWA')
        ->assertSee('320')
        ->assertSee(route('public-data.show', 'pendidikan'));
});

it('returns 404 for a dataset not present in the sector', function () {
    ExternalData::factory()->create([
        'sector' => 'kesehatan',
        'dataset' => 'Jumlah Puskesmas',
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->get(route('public-data.dataset', ['sector' => 'pendidikan', 'dataset' => 'Jumlah Puskesmas']))
        ->assertNotFound();
});
