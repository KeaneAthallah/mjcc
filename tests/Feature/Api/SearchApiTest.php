<?php

use App\Models\School;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    Sanctum::actingAs($this->user);
});

it('searches across master datasets', function () {
    School::factory()->create(['name' => 'SDN Intelijen Satu', 'npsn' => '12345678']);

    $response = $this->getJson('/api/v1/search?q=SDN%20Intelijen');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['query', 'results', 'suggestions'],
        ]);

    $results = $response->json('data.results');
    expect($response->json('data.query'))->toBe('SDN Intelijen')
        ->and($results)->not->toBeEmpty()
        ->and($results[0])->toMatchArray([
            'type' => 'Sekolah',
            'slug' => 'school',
            'icon' => '🏫',
        ])
        ->and($results[0]['title'])->toBe('SDN Intelijen Satu')
        ->and(array_key_exists('id', $results[0]))->toBeTrue();
});

it('searches by npsn too', function () {
    School::factory()->create(['name' => 'SMPN Nirkabel', 'npsn' => '008899123']);

    $response = $this->getJson('/api/v1/search?q=008899123');

    $response->assertOk();
    expect(collect($response->json('data.results'))->pluck('title'))->toContain('SMPN Nirkabel');
});

it('returns an empty payload for an empty query', function () {
    $this->getJson('/api/v1/search?q=')
        ->assertOk()
        ->assertJsonPath('data.results', [])
        ->assertJsonPath('data.query', '');
});

it('requires authentication', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/search?q=test')->assertUnauthorized();
});
