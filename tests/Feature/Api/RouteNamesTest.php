<?php

use Illuminate\Support\Facades\Route;

it('does not let api resource route names shadow web route names', function () {
    expect(route('users.index'))->toBe(url('/users'))
        ->and(route('users.show', 1))->toBe(url('/users/1'));
});

it('namespaces api v1 routes under the api prefix', function () {
    expect(Route::has('api.users.index'))->toBeTrue()
        ->and(Route::has('users.index'))->toBeTrue()
        ->and(route('api.users.index'))->toBe(url('/api/v1/users'))
        ->and(route('api.schools.index'))->toBe(url('/api/v1/schools'));
});
