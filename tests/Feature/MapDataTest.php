<?php

use App\Models\ApbdRecord;
use App\Models\BpsDataset;
use App\Models\BpsObservation;
use App\Models\CommodityPrice;
use App\Models\DisasterEvent;
use App\Models\DisasterRiskIndex;
use App\Models\ExternalData;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\User;
use App\Observers\DataChangeObserver;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('command-center.maps.all');
});

it('requires authentication', function () {
    $this->getJson(route('maps.data'))->assertUnauthorized();
});

it('returns all markers and kecamatans as json', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'latitude' => null, 'longitude' => null]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'latitude' => -2.5,
        'longitude' => 121.9,
        'school_type' => 'SD',
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk()
        ->assertJsonStructure(['markers', 'kecamatans'])
        ->assertJsonCount(1, 'markers')
        ->assertJsonPath('markers.0.slug', 'school')
        ->assertJsonPath('markers.0.sector', 'pendidikan')
        ->assertJsonPath('markers.0.category', 'SD')
        ->assertJsonPath('markers.0.latitude', -2.5)
        ->assertJsonCount(1, 'kecamatans');
});

it('filters markers by kecamatan id', function () {
    $first = Kecamatan::factory()->create();
    $second = Kecamatan::factory()->create();
    $firstKelurahan = Kelurahan::factory()->create(['kecamatan_id' => $first->id, 'latitude' => null, 'longitude' => null]);
    $secondKelurahan = Kelurahan::factory()->create(['kecamatan_id' => $second->id, 'latitude' => null, 'longitude' => null]);

    School::factory()->create([
        'kecamatan_id' => $first->id,
        'kelurahan_id' => $firstKelurahan->id,
        'latitude' => -2.5,
        'longitude' => 121.9,
    ]);
    School::factory()->create([
        'kecamatan_id' => $second->id,
        'kelurahan_id' => $secondKelurahan->id,
        'latitude' => -2.7,
        'longitude' => 121.95,
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data', ['kecamatan' => $first->id]))
        ->assertOk()
        ->assertJsonCount(1, 'markers')
        ->assertJsonPath('markers.0.latitude', -2.5);
});

it('includes slug and id for every marker type', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'latitude' => -2.4, 'longitude' => 121.8]);
    $hiddenKelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'latitude' => null, 'longitude' => null]);

    School::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $hiddenKelurahan->id, 'latitude' => -2.5, 'longitude' => 121.9]);
    Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $hiddenKelurahan->id, 'latitude' => -2.6, 'longitude' => 121.85]);
    HealthFacility::factory()->create(['kecamatan_id' => $kecamatan->id, 'latitude' => -2.7, 'longitude' => 121.88]);

    $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk()
        ->assertJsonCount(4, 'markers')
        ->assertJsonPath('markers.0.slug', 'school')
        ->assertJsonPath('markers.1.slug', 'poskamling')
        ->assertJsonPath('markers.2.slug', 'kelurahan')
        ->assertJsonPath('markers.3.slug', 'health_facility');
});

it('serves the cached payload until a refresh is requested', function () {
    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id, 'latitude' => null, 'longitude' => null]);
    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'latitude' => -2.5,
        'longitude' => 121.9,
    ]);

    $user = User::factory()->viewer()->create();
    $url = route('maps.data');

    // The seed writes above flush through the change observer; drain them so
    // the reads below exercise genuine caching instead of being busted at the
    // next request boundary by the terminating flush.
    DataChangeObserver::flush($user->id);

    $this->actingAs($user)->getJson($url)->assertOk()->assertJsonCount(1, 'markers');

    expect(Cache::has('command-center.maps.all'))->toBeTrue();

    School::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'kelurahan_id' => $kelurahan->id,
        'latitude' => -2.6,
        'longitude' => 121.95,
    ]);

    $this->actingAs($user)->getJson($url)->assertOk()->assertJsonCount(1, 'markers');
    $this->actingAs($user)->getJson($url.'?refresh=1')->assertOk()->assertJsonCount(2, 'markers');
});

it('includes aggregated external data markers per kecamatan and sector', function () {
    ExternalData::factory()->create([
        'sector' => 'pendidikan',
        'location' => 'Witaponda',
        'latitude' => -2.21591805,
        'longitude' => 121.62234363,
        'dataset' => 'Jumlah SD',
    ]);
    ExternalData::factory()->create([
        'sector' => 'pendidikan',
        'location' => 'Wita Ponda',
        'latitude' => -2.21591805,
        'longitude' => 121.62234363,
        'dataset' => 'Jumlah SMP',
    ]);
    ExternalData::factory()->create([
        'sector' => 'kesehatan',
        'location' => 'Bahodopi',
        'latitude' => -2.79611745,
        'longitude' => 122.1265571,
        'dataset' => 'Akses Puskesmas',
    ]);
    ExternalData::factory()->create([
        'sector' => 'keamanan',
        'location' => 'Kep Sambori',
        'latitude' => -3.03,
        'longitude' => 122.5,
        'dataset' => 'Pos Kamling',
    ]);

    $response = $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk()
        ->assertJsonCount(3, 'markers');

    $markers = collect($response->json('markers'));

    $external = $markers->where('slug', 'external_data');

    expect($external->count())->toBe(3);

    $wita = $external->firstWhere('name', 'Wita Ponda');

    expect($wita['sector'])->toBe('pendidikan')
        ->and($wita['category'])->toBe('data-publik-pendidikan')
        ->and($wita['details']['Dataset'])->toBe(2)
        ->and($wita['details']['Rekor'])->toBe(2)
        ->and($external->firstWhere('name', 'Bahodopi')['sector'])->toBe('kesehatan')
        ->and($external->firstWhere('name', 'Sombori Kepulauan')['sector'])->toBe('ketertiban');
});

it('maps keamanan external sectors onto the ketertiban map sector', function () {
    ExternalData::factory()->create([
        'sector' => 'keamanan',
        'location' => 'Bahodopi',
        'latitude' => -2.79611745,
        'longitude' => 122.1265571,
        'dataset' => 'Kejadian Bencana',
    ]);

    $response = $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk();

    $external = collect($response->json('markers'))->where('slug', 'external_data')->first();

    expect($external['sector'])->toBe('ketertiban')
        ->and($external['category'])->toBe('data-publik-keamanan');
});

it('excludes external locations that do not resolve to coordinates', function () {
    ExternalData::factory()->create([
        'sector' => 'pendidikan',
        'location' => 'Jumlah',
        'latitude' => null,
        'longitude' => null,
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk()
        ->assertJsonCount(0, 'markers');
});

it('appends resolved external kecamatan names to the kecamatan list', function () {
    ExternalData::factory()->create([
        'sector' => 'kesehatan',
        'location' => 'Bungku Tengah',
        'latitude' => -2.5131,
        'longitude' => 121.7864,
        'dataset' => 'Fasilitas',
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk()
        ->assertJsonPath('kecamatans.0.name', 'Bungku Tengah');
});

it('includes SITABA disaster events as kebencanaan markers', function () {
    DisasterEvent::create([
        'disaster_type' => 'Banjir Genangan',
        'disaster_name' => 'Banjir di Morowali',
        'district' => 'KABUPATEN MOROWALI',
        'event_date' => now()->subDays(2),
        'latitude' => -2.5,
        'longitude' => 121.9,
        'status' => 'terkini',
        'dedupe_key' => 'evt-1',
    ]);

    $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk()
        ->assertJsonPath('markers.0.slug', 'disaster_event')
        ->assertJsonPath('markers.0.sector', 'kebencanaan')
        ->assertJsonPath('markers.0.category', 'bencana');
});

it('enriches the market marker with the latest SP2KP commodity summary', function () {
    $kecamatan = Kecamatan::factory()->create();
    $market = Market::factory()->create([
        'kecamatan_id' => $kecamatan->id,
        'name' => 'Pasar Rakyat Bungku Tengah',
        'latitude' => -2.5,
        'longitude' => 121.9,
    ]);

    CommodityPrice::create([
        'commodity' => 'Beras Premium',
        'percentage_change' => 7.1,
        'market' => $market->name,
        'record_date' => now()->toDateString(),
        'dedupe_key' => CommodityPrice::dedupeKey('Beras Premium', $market->name, now()->toDateString()),
    ]);
    CommodityPrice::create([
        'commodity' => 'Minyak Goreng',
        'percentage_change' => -4.8,
        'market' => $market->name,
        'record_date' => now()->toDateString(),
        'dedupe_key' => CommodityPrice::dedupeKey('Minyak Goreng', $market->name, now()->toDateString()),
    ]);
    CommodityPrice::create([
        'commodity' => 'Gula',
        'percentage_change' => 0.0,
        'market' => $market->name,
        'record_date' => now()->subDays(10)->toDateString(),
        'dedupe_key' => CommodityPrice::dedupeKey('Gula', $market->name, now()->subDays(10)->toDateString()),
    ]);

    $response = $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk();

    $marketMarker = collect($response->json('markers'))->firstWhere('slug', 'market');

    expect($marketMarker['details']['Komoditas SP2KP'])->toBe(2)
        ->and(json_encode($marketMarker['details']))->toContain('Beras Premium')
        ->and(json_encode($marketMarker['details']))->toContain('Minyak Goreng')
        ->and($marketMarker['detailUrl'])->toBe(route('public-data.show', 'sp2kp'));
});

it('anchors BPS & APBD region-level data at the kabupaten centroid', function () {
    $dataset = BpsDataset::create(['dataset_id' => 'b-001', 'name' => 'Statistik Dasar']);

    BpsObservation::create([
        'bps_dataset_id' => $dataset->id,
        'indicator' => 'Jumlah Penduduk',
        'region_name' => 'Kabupaten Morowali',
        'year' => now()->year,
        'value' => 150000,
        'dedupe_key' => BpsObservation::dedupeKey($dataset->id, 'Jumlah Penduduk', 'Kabupaten Morowali', now()->year, null),
    ]);

    ApbdRecord::create([
        'year' => now()->year,
        'indicator' => 'Pendapatan',
        'target_value' => 1000000,
        'realization_value' => 900000,
        'percentage' => 90.0,
        'dedupe_key' => ApbdRecord::dedupeKey(now()->year, 'Pendapatan', 'Kabupaten Morowali'),
    ]);

    $response = $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk();

    $markers = collect($response->json('markers'));

    expect($markers->where('slug', 'bps_observation')->count())->toBe(1)
        ->and($markers->where('slug', 'apbd_record')->count())->toBe(1)
        ->and($markers->where('slug', 'bps_observation')->first()['sector'])->toBe('statistik')
        ->and($markers->where('slug', 'apbd_record')->first()['category'])->toBe('data-publik-apbd')
        ->and($markers->where('slug', 'bps_observation')->first()['category'])->toBe('data-publik-bps');
});

it('provides an IRBI risk map for the latest year only', function () {
    DisasterRiskIndex::create([
        'region_name' => 'Morowali',
        'region_code' => '72.06',
        'hazard_type' => 'Banjir',
        'risk_index' => 180.5,
        'risk_level' => 'Tinggi',
        'year' => now()->year,
        'dedupe_key' => 'risk-1',
    ]);
    DisasterRiskIndex::create([
        'region_name' => 'Morowali',
        'region_code' => '72.06',
        'hazard_type' => 'Kekeringan',
        'risk_index' => 120.0,
        'risk_level' => 'Sedang',
        'year' => now()->year,
        'dedupe_key' => 'risk-2',
    ]);
    DisasterRiskIndex::create([
        'region_name' => 'Morowali',
        'region_code' => '72.06',
        'hazard_type' => 'Banjir',
        'risk_index' => 90.0,
        'risk_level' => 'Rendah',
        'year' => now()->year - 1,
        'dedupe_key' => 'risk-3',
    ]);

    $response = $this->actingAs(User::factory()->viewer()->create())
        ->getJson(route('maps.data'))
        ->assertOk();

    $riskMap = $response->json('riskMap');

    expect($riskMap)->toHaveKey('72.06')
        ->and($riskMap['72.06']['index'])->toBe(180.5)
        ->and($riskMap['72.06']['level'])->toBe('Tinggi')
        ->and($riskMap['72.06']['hazard'])->toBe('Banjir');
});
