<?php

use App\Models\ApbdRecord;
use App\Models\BpsDataset;
use App\Models\BpsObservation;
use App\Models\CommodityPrice;
use App\Models\DisasterEvent;
use App\Models\DisasterRiskIndex;
use App\Models\ExternalData;
use App\Models\Kecamatan;
use App\Models\User;
use App\Services\PublicData\SourceRegistry;

beforeEach(function () {
    SourceRegistry::seed();
    $this->user = User::factory()->viewer()->create();
});

it('requires authentication for dashboard', function () {
    $this->get(route('public-data.dashboard'))->assertRedirect(route('login'));
});

it('shows the main dashboard page', function () {
    $this->actingAs($this->user)
        ->get(route('public-data.dashboard'))
        ->assertOk()
        ->assertSee('Data Publik')
        ->assertSee('Pendidikan')
        ->assertSee('Pemantauan')
        ->assertSee('Kebencanaan')
        ->assertSee('APBD');
});

it('shows the category page', function () {
    $this->actingAs($this->user)
        ->get(route('public-data.category', 'pendidikan'))
        ->assertOk()
        ->assertSee('Pendidikan')
        ->assertSee('Anak Tidak Sekolah')
        ->assertSee('Dapodik');
});

it('shows 404 for unknown category', function () {
    $this->actingAs($this->user)
        ->get(route('public-data.category', 'unknown'))
        ->assertNotFound();
});

it('shows the source page for generic source', function () {
    ExternalData::factory()->create([
        'source_key' => 'ats',
        'indicator' => 'Anak Tidak Sekolah',
        'year' => 2024,
        'location' => 'Bungku Tengah',
        'value' => 1500,
    ]);

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'ats'))
        ->assertOk()
        ->assertSee('Anak Tidak Sekolah')
        ->assertSee('Bungku Tengah')
        ->assertSee('1.500,00')
        ->assertSee(route('public-data.source', 'ats'));
});

it('filters generic source records by indicator', function () {
    ExternalData::factory()->create([
        'source_key' => 'ats',
        'indicator' => 'Anak Tidak Sekolah',
        'year' => 2024,
        'location' => 'Bungku Tengah',
        'value' => 1500,
    ]);
    ExternalData::factory()->create([
        'source_key' => 'ats',
        'indicator' => 'Angka Partisipasi Sekolah',
        'year' => 2023,
        'location' => 'Bungku Tengah',
        'value' => 2500,
    ]);

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'ats'))
        ->assertOk()
        ->assertSee('1.500,00')
        ->assertSee('2.500,00');

    $this->actingAs($this->user)
        ->get(route('public-data.source', ['ats', 'indicator' => 'Anak Tidak Sekolah']))
        ->assertOk()
        ->assertSee('1.500,00')
        ->assertDontSee('2.500,00');
});

it('shows the source page for SP2KP with commodity data', function () {
    $dedupeKey = CommodityPrice::dedupeKey('Beras', 'Pasar Masama', now()->subDay()->toDateString());
    CommodityPrice::create([
        'commodity' => 'Beras',
        'market' => 'Pasar Masama',
        'record_date' => now()->subDay(),
        'current_price' => 12000,
        'previous_price' => 11375,
        'price_change' => 625,
        'percentage_change' => 5.5,
        'region' => 'Kabupaten Morowali',
        'unit' => 'Rp/Kg',
        'dedupe_key' => $dedupeKey,
    ]);

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'sp2kp'))
        ->assertOk()
        ->assertSee('Pasar & Kebutuhan Pokok')
        ->assertSee('Beras')
        ->assertSee('Rp 12.000')
        ->assertSee('Komoditas termonitor');
});

it('shows the source page for BPS with observation data', function () {
    $dataset = BpsDataset::create([
        'dataset_id' => '00001',
        'name' => 'Pertumbuhan Ekonomi',
        'subject' => 'Ekonomi',
    ]);

    $dedupeKey = BpsObservation::dedupeKey($dataset->id, 'Pertumbuhan Ekonomi', 'Morowali', 2024, null);
    BpsObservation::create([
        'bps_dataset_id' => $dataset->id,
        'region_code' => '72.06',
        'region_name' => 'Morowali',
        'indicator' => 'Pertumbuhan Ekonomi',
        'year' => 2024,
        'value' => 5.2,
        'dedupe_key' => $dedupeKey,
    ]);

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'bps'))
        ->assertOk()
        ->assertSee('BPS')
        ->assertSee('Pertumbuhan Ekonomi')
        ->assertSee('5,20')
        ->assertSee('Observasi tersimpan');
});

it('shows the source page for IRBI with risk data', function () {
    $dedupeKey = hash('sha256', '72.06.01|Banjir');
    DisasterRiskIndex::create([
        'region_code' => '72.06.01',
        'region_name' => 'Bungku Tengah',
        'hazard_type' => 'Banjir',
        'risk_level' => 'Tinggi',
        'latitude' => -2.5,
        'longitude' => 121.5,
        'dedupe_key' => $dedupeKey,
    ]);

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'irbi'))
        ->assertOk()
        ->assertSee('Indeks Risiko Bencana')
        ->assertSee('Banjir')
        ->assertSee('Bungku Tengah')
        ->assertSee('Sebaran level risiko');
});

it('shows the source page for Sitaba with disaster data', function () {
    $dedupeKey = hash('sha256', 'Banjir|2026-09-05|Bungku Tengah');
    DisasterEvent::create([
        'disaster_type' => 'Banjir',
        'event_date' => now()->subDays(5),
        'district' => 'Bungku Tengah',
        'description' => 'Banjir bandang',
        'status' => 'aktif',
        'latitude' => -2.5,
        'longitude' => 121.5,
        'dedupe_key' => $dedupeKey,
    ]);

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'sitaba'))
        ->assertOk()
        ->assertSee('Bencana Terkini')
        ->assertSee('Banjir')
        ->assertSee('Aktif')
        ->assertSee('Bencana terpantau');
});

it('shows the source page for APBD with financial data', function () {
    $year = (int) date('Y');
    $dedupeKey = ApbdRecord::dedupeKey($year, 'Pajak Daerah', 'Kabupaten Morowali');
    ApbdRecord::create([
        'year' => $year,
        'category' => 'Pendapatan',
        'indicator' => 'Pajak Daerah',
        'target_value' => 100000000,
        'realization_value' => 95000000,
        'region' => 'Kabupaten Morowali',
        'dedupe_key' => $dedupeKey,
    ]);

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'apbd'))
        ->assertOk()
        ->assertSee('Monitoring APBD')
        ->assertSee('Pajak Daerah')
        ->assertSee('Realisasi anggaran');
});

it('shows 404 for unknown source', function () {
    $this->actingAs($this->user)
        ->get(route('public-data.source', 'unknown'))
        ->assertNotFound();
});

it('shows sync button on source page for admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('public-data.source', 'ats'))
        ->assertOk()
        ->assertSee('Sinkronisasi');
});

it('renders the ATS executive widgets on the ats source page', function () {
    Kecamatan::factory()->create(['name' => 'Bahodopi']);

    foreach ([
        ['Kabupaten Morowali', 'Anak Tidak Sekolah', 100],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - DO', 60],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - Verifikasi Sudah', 30],
        ['Kabupaten Morowali', 'Anak Tidak Sekolah - Verifikasi Belum', 70],
        ['Bahodopi', 'Anak Tidak Sekolah', 100],
    ] as [$location, $indicator, $value]) {
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

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'ats'))
        ->assertOk()
        ->assertSee('Anak Tidak Sekolah (ATS)')
        ->assertSee('Progres Verifikasi')
        ->assertSee('Bahodopi')
        ->assertSee('Total Record');
});

it('renders the Dapodik executive widgets on the dapodik source page', function () {
    Kecamatan::factory()->create(['name' => 'Bungku Tengah']);

    foreach ([
        ['Kabupaten Morowali', 'Jumlah Sekolah', 10],
        ['Kabupaten Morowali', 'Jumlah Siswa', 1000],
        ['Kabupaten Morowali', 'Jumlah Guru', 80],
        ['Bungku Tengah', 'Jumlah Sekolah', 3],
        ['Bungku Tengah', 'Jumlah Siswa', 250],
    ] as [$location, $indicator, $value]) {
        ExternalData::create([
            'sector' => 'pendidikan',
            'source' => 'dapodik',
            'source_key' => 'dapodik',
            'source_url' => 'https://dapodik.example',
            'dataset' => 'Data Pokok Pendidikan',
            'topic' => 'Pendidikan',
            'year' => now()->year,
            'location' => $location,
            'indicator' => $indicator,
            'value' => $value,
            'unit' => 'Satuan',
            'dedupe_key' => ExternalData::dedupeKey('pendidikan', 'dapodik', 'Data Pokok Pendidikan', now()->year, $location, $indicator),
        ]);
    }

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'dapodik'))
        ->assertOk()
        ->assertSee('Data Dapodik')
        ->assertSee('Sekolah terdaftar')
        ->assertSee('Siswa per Kecamatan')
        ->assertSee('Bungku Tengah');
});

it('renders the Satu Data Morowali widgets on the satudata source page', function () {
    foreach ([
        ['PDRB Atas Dasar Harga Berlaku', 'Kabupaten Morowali'],
        ['PDRB Per Kapita', 'Kabupaten Morowali'],
    ] as $i => [$indicator, $location]) {
        ExternalData::create([
            'sector' => 'ekonomi',
            'source' => 'satudata',
            'source_key' => 'satudata',
            'source_url' => 'https://satudata.example',
            'dataset' => 'Ekonomi & Pembangunan',
            'topic' => 'PDRB',
            'year' => 2024,
            'location' => $location,
            'indicator' => $indicator,
            'value' => 100 + $i,
            'unit' => 'miliar',
            'dedupe_key' => ExternalData::dedupeKey('ekonomi', 'satudata', 'Ekonomi & Pembangunan', 2024, $location, $indicator),
        ]);
    }

    $this->actingAs($this->user)
        ->get(route('public-data.source', 'satudata'))
        ->assertOk()
        ->assertSee('Satu Data Morowali')
        ->assertSee('Record tersimpan')
        ->assertSee('Komposisi per Dataset')
        ->assertSee('Ekonomi & Pembangunan');
});

it('prevents viewer from syncing a source', function () {
    $this->actingAs($this->user)
        ->post(route('public-data.sync-source', 'ats'))
        ->assertForbidden();
});

it('prevents viewer from syncing all sources', function () {
    $this->actingAs($this->user)
        ->post(route('public-data.sync-all'))
        ->assertForbidden();
});
