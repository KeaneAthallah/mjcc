<?php

use App\Models\Kecamatan;
use App\Models\User;

it('redirects guests to login for every command-center page', function () {
    $kecamatan = Kecamatan::factory()->create();

    foreach ([
        route('dashboard'),
        route('alerts.index'),
        route('kecamatan.overview'),
        route('kecamatan.show', $kecamatan),
        route('search.index'),
        route('maps.index'),
    ] as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }

    $this->getJson(route('alerts.summary'))->assertUnauthorized();
    $this->getJson(route('maps.data'))->assertUnauthorized();
    $this->post(route('dashboard.refresh'))->assertRedirect(route('login'));
});

it('lets viewers read every command-center page', function () {
    $kecamatan = Kecamatan::factory()->create();
    $viewer = User::factory()->viewer()->create();

    $this->actingAs($viewer);

    foreach ([
        route('dashboard'),
        route('alerts.index'),
        route('kecamatan.overview'),
        route('kecamatan.show', $kecamatan),
        route('search.index'),
        route('maps.index'),
    ] as $url) {
        $this->get($url)->assertOk();
    }

    $this->getJson(route('alerts.summary'))->assertOk();
    $this->getJson(route('maps.data'))->assertOk();
    $this->post(route('dashboard.refresh'))->assertRedirect();
});

it('lets operators read every command-center page', function () {
    $kecamatan = Kecamatan::factory()->create();
    $operator = User::factory()->operator()->create();

    $this->actingAs($operator);

    foreach ([
        route('dashboard'),
        route('alerts.index'),
        route('kecamatan.overview'),
        route('kecamatan.show', $kecamatan),
        route('search.index'),
        route('maps.index'),
    ] as $url) {
        $this->get($url)->assertOk();
    }

    $this->getJson(route('alerts.summary'))->assertOk();
    $this->getJson(route('maps.data'))->assertOk();
    $this->post(route('dashboard.refresh'))->assertRedirect();
});
