<?php

use App\Models\CommandAlert;
use App\Models\User;
use App\Services\CommandAlertSyncService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    Sanctum::actingAs($this->user);

    $this->mock(CommandAlertSyncService::class)->shouldReceive('sync')->andReturn(0);
});

it('returns persisted command alerts with counts', function () {
    $open = CommandAlert::factory()->critical()->create();
    CommandAlert::factory()->create(['status' => CommandAlert::STATUS_SELESAI]);

    $response = $this->getJson('/api/v1/alerts');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'alerts',
                'counts' => ['critical', 'warning', 'info', 'open'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);

    $item = collect($response->json('data.alerts'))->firstWhere('id', $open->id);
    expect($item)->not->toBeNull()
        ->and($item['severity'])->toBe(CommandAlert::SEVERITY_CRITICAL)
        ->and($item['severity_label'])->toBeString()
        ->and($item['status'])->toBe(CommandAlert::STATUS_BARU)
        ->and($item['status_label'])->toBeString()
        ->and($item['is_open'])->toBeTrue()
        ->and($item['sector_key'])->toBe('kesehatan')
        ->and(array_key_exists('kecamatan_name', $item))->toBeTrue();

    expect($response->json('data.counts.open'))->toBe(1)
        ->and($response->json('data.counts.critical'))->toBe(1);
});

it('filters alerts by status, severity, sector and search', function () {
    CommandAlert::factory()->critical()->create();
    CommandAlert::factory()->create(['severity' => CommandAlert::SEVERITY_INFO, 'title' => 'Buku unik sekali']);

    $this->getJson('/api/v1/alerts?severity=critical')
        ->assertOk()
        ->assertJsonPath('data.counts.critical', 1)
        ->assertJsonCount(1, 'data.alerts');

    $response = $this->getJson('/api/v1/alerts?search=Buku%20unik');
    $response->assertOk();
    expect(collect($response->json('data.alerts'))->pluck('title'))->toContain('Buku unik sekali');
});

it('requires authentication', function () {
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/v1/alerts')->assertUnauthorized();
});
