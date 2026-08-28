<?php

use App\Models\School;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    Sanctum::actingAs($this->user);
});

it('returns map markers with a consistent structure', function () {
    School::factory()->create([
        'name' => 'SMPN Peta',
        'latitude' => -2.1234567,
        'longitude' => 121.1234567,
    ]);
    School::factory()->create(['latitude' => null, 'longitude' => null]);

    $response = $this->getJson('/api/v1/maps');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'markers',
                'kecamatans',
            ],
        ]);

    $markers = $response->json('data.markers');

    expect(collect($markers)->where('type', 'school')->count())->toBe(1);

    $schoolMarker = collect($markers)->firstWhere('name', 'SMPN Peta');
    expect($schoolMarker)->not->toBeNull()
        ->and($schoolMarker['type'])->toBe('school')
        ->and($schoolMarker['sector'])->toBe('pendidikan')
        ->and(is_numeric($schoolMarker['latitude']))->toBeTrue()
        ->and(is_numeric($schoolMarker['longitude']))->toBeTrue()
        ->and(array_key_exists('status', $schoolMarker))->toBeTrue()
        ->and(array_key_exists('kecamatan', $schoolMarker))->toBeTrue();
});

it('filters map markers by sector', function () {
    School::factory()->create(['latitude' => -2.1, 'longitude' => 121.1]);

    $this->getJson('/api/v1/maps?sector=kesehatan')
        ->assertOk()
        ->assertJsonCount(0, 'data.markers');
});
