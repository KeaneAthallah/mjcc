<?php

use App\Services\PublicData\LocationResolver;

it('resolves canonical kecamatan names to coordinates', function (string $location) {
    $resolved = app(LocationResolver::class)->resolve($location);

    expect($resolved)->not->toBeNull()
        ->and($resolved['latitude'])->toBeFloat()
        ->and($resolved['longitude'])->toBeFloat();
})->with([
    'Bahodopi',
    'Bumi Raya',
    'Bungku Barat',
    'Bungku Pesisir',
    'Bungku Selatan',
    'Bungku Tengah',
    'Bungku Timur',
    'Menui Kepulauan',
    'Sombori Kepulauan',
    'Wita Ponda',
]);

it('normalizes alias spellings to the canonical kecamatan', function (string $alias, string $canonical) {
    $resolver = app(LocationResolver::class);

    expect($resolver->canonicalName($alias))->toBe($canonical);
})->with([
    ['Witaponda', 'Wita Ponda'],
    ['WITA PONDA', 'Wita Ponda'],
    ['Bumiraya', 'Bumi Raya'],
    ['SOMBORI KEPUALAUAN', 'Sombori Kepulauan'],
    ['Kep Sambori', 'Sombori Kepulauan'],
    ['Kep.. Sambori', 'Sombori Kepulauan'],
]);

it('resolves regency-level locations to the regency centroid', function () {
    $resolver = app(LocationResolver::class);

    expect($resolver->canonicalName('Morowali'))->toBe('Kabupaten Morowali')
        ->and($resolver->canonicalName('Kabupaten Morowali'))->toBe('Kabupaten Morowali')
        ->and($resolver->resolve('Jumlah'))->toBeNull()
        ->and($resolver->resolve('Jumlah kab/kota'))->toBeNull();
});

it('rejects values that are not real places', function (string $location) {
    expect(app(LocationResolver::class)->resolve($location))->toBeNull();
})->with([
    'Jumlah',
    'Jumlah kab/kota',
    'Kabupaten',
    'Angka Kematian dilaporkan',
    'Bumi Barat',
    '',
    '   ',
]);
