<?php

use App\Models\ExternalData;

it('backfills latitude and longitude from the location column', function () {
    ExternalData::factory()->create([
        'location' => 'Witaponda',
        'latitude' => null,
        'longitude' => null,
    ]);
    ExternalData::factory()->create([
        'location' => 'Bahodopi',
        'latitude' => null,
        'longitude' => null,
    ]);

    $this->artisan('public-data:geocode')->assertSuccessful();

    $this->assertDatabaseHas('external_data', [
        'location' => 'Witaponda',
        'latitude' => -2.21591805,
        'longitude' => 121.62234363,
    ]);
    $this->assertDatabaseHas('external_data', [
        'location' => 'Bahodopi',
        'latitude' => -2.79611745,
        'longitude' => 122.1265571,
    ]);
});

it('leaves unknown locations without coordinates', function () {
    ExternalData::factory()->create([
        'location' => 'Jumlah',
        'latitude' => null,
        'longitude' => null,
    ]);

    $this->artisan('public-data:geocode')->assertSuccessful();

    $this->assertDatabaseHas('external_data', [
        'location' => 'Jumlah',
        'latitude' => null,
        'longitude' => null,
    ]);
});

it('does not touch rows that already have coordinates', function () {
    ExternalData::factory()->create([
        'location' => 'Bungku Tengah',
        'latitude' => -2.5131,
        'longitude' => 121.7864,
    ]);

    $this->artisan('public-data:geocode')->assertSuccessful();

    $row = ExternalData::where('location', 'Bungku Tengah')->first();

    expect((float) $row->latitude)->toBe(-2.5131)
        ->and((float) $row->longitude)->toBe(121.7864);
});
