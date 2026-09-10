<?php

use App\Models\ApbdRecord;
use App\Models\BpsDataset;
use App\Models\BpsObservation;
use App\Models\CommodityPrice;
use App\Models\DisasterEvent;
use App\Models\DisasterRiskIndex;
use App\Services\PublicData\SourceRegistry;

beforeEach(function () {
    SourceRegistry::seed();
});

it('returns all categories', function () {
    $response = $this->getJson(route('api.public-data.categories'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(4, 'data');
});

it('returns all sources', function () {
    $response = $this->getJson(route('api.public-data.sources'));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(8, 'data');
});

it('filters sources by category', function () {
    $response = $this->getJson(route('api.public-data.sources').'?category=pendidikan');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

it('returns source detail for generic source', function () {
    $response = $this->getJson(route('api.public-data.source', 'ats'));

    $response->assertOk()
        ->assertJsonPath('data.source.key', 'ats')
        ->assertJsonPath('data.source.name', 'Anak Tidak Sekolah');
});

it('returns source detail for SP2KP', function () {
    $dedupeKey = CommodityPrice::dedupeKey('Beras', 'Pasar', now()->subDay()->toDateString());
    CommodityPrice::create([
        'commodity' => 'Beras',
        'market' => 'Pasar',
        'record_date' => now()->subDay(),
        'current_price' => 12000,
        'region' => 'Kabupaten Morowali',
        'unit' => 'Rp/Kg',
        'dedupe_key' => $dedupeKey,
    ]);

    $response = $this->getJson(route('api.public-data.source', 'sp2kp'));

    $response->assertOk()
        ->assertJsonPath('data.source.key', 'sp2kp')
        ->assertJsonPath('data.total', 1);
});

it('returns source detail for BPS', function () {
    $dataset = BpsDataset::create([
        'dataset_id' => '00001',
        'name' => 'Test Dataset',
        'subject' => 'Ekonomi',
    ]);

    $dedupeKey = BpsObservation::dedupeKey($dataset->id, 'Indikator', 'Morowali', 2024, null);
    BpsObservation::create([
        'bps_dataset_id' => $dataset->id,
        'region_code' => '72.06',
        'region_name' => 'Morowali',
        'indicator' => 'Indikator',
        'year' => 2024,
        'value' => 5.2,
        'dedupe_key' => $dedupeKey,
    ]);

    $response = $this->getJson(route('api.public-data.source', 'bps'));

    $response->assertOk()
        ->assertJsonPath('data.source.key', 'bps')
        ->assertJsonPath('data.total', 1);
});

it('returns source detail for IRBI', function () {
    $dedupeKey = hash('sha256', '72.06.01|Banjir');
    DisasterRiskIndex::create([
        'region_code' => '72.06.01',
        'region_name' => 'Bungku Tengah',
        'hazard_type' => 'Banjir',
        'risk_level' => 'Tinggi',
        'dedupe_key' => $dedupeKey,
    ]);

    $response = $this->getJson(route('api.public-data.source', 'irbi'));

    $response->assertOk()
        ->assertJsonPath('data.source.key', 'irbi')
        ->assertJsonPath('data.total', 1);
});

it('returns source detail for Sitaba', function () {
    $dedupeKey = hash('sha256', 'Banjir|2026-09-10|Bungku Tengah');
    DisasterEvent::create([
        'disaster_type' => 'Banjir',
        'event_date' => now(),
        'district' => 'Bungku Tengah',
        'dedupe_key' => $dedupeKey,
    ]);

    $response = $this->getJson(route('api.public-data.source', 'sitaba'));

    $response->assertOk()
        ->assertJsonPath('data.source.key', 'sitaba')
        ->assertJsonPath('data.total', 1);
});

it('returns source detail for APBD', function () {
    $year = (int) date('Y');
    $dedupeKey = ApbdRecord::dedupeKey($year, 'Pajak', 'Kabupaten Morowali');
    ApbdRecord::create([
        'year' => $year,
        'category' => 'Pendapatan',
        'indicator' => 'Pajak',
        'target_value' => 100000000,
        'realization_value' => 95000000,
        'region' => 'Kabupaten Morowali',
        'dedupe_key' => $dedupeKey,
    ]);

    $response = $this->getJson(route('api.public-data.source', 'apbd'));

    $response->assertOk()
        ->assertJsonPath('data.source.key', 'apbd')
        ->assertJsonPath('data.total', 1);
});

it('returns 404 for unknown source', function () {
    $response = $this->getJson(route('api.public-data.source', 'unknown'));

    $response->assertNotFound();
});
