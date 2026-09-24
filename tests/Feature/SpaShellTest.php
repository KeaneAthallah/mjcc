<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('serves the SPA shell at the root path', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertSee('id="app"', false)
        ->assertSee('MOROWALI JUARA COMMAND CENTER');
});

it('renders the SPA for any unknown frontend path', function () {
    $this->get('/pendidikan')
        ->assertOk()
        ->assertSee('id="app"', false);
});

it('does not hijack unknown assets, api or broadcasting paths', function () {
    $this->get('/build/missing-assets-123.js')
        ->assertNotFound();

    $this->get('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertJsonStructure(['success', 'message', 'errors'])
        ->assertJson(['success' => false]);

    $this->get('/broadcasting/nonexistent')
        ->assertNotFound();
});

it('keeps the named dashboard blade route available', function () {
    $route = Route::getRoutes()->getByName('dashboard');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('dashboard');
});
