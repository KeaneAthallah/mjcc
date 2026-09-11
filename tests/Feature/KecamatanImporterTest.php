<?php

use App\Models\Kecamatan;
use App\Services\DataImport\KecamatanImporter;

it('creates only kecamatan within Kabupaten Morowali', function () {
    $result = app(KecamatanImporter::class)->import([
        ['name' => 'Bahodopi', 'latitude' => -2.7, 'longitude' => 122.1],
        ['name' => 'Petasia', 'latitude' => -3.2, 'longitude' => 121.9],
        ['name' => 'Kabupaten Morowali', 'latitude' => -2.5, 'longitude' => 121.5],
    ]);

    expect($result['created'])->toBe(1)
        ->and(Kecamatan::where('name', 'Bahodopi')->exists())->toBeTrue()
        ->and(Kecamatan::where('name', 'Petasia')->exists())->toBeFalse()
        ->and(Kecamatan::where('name', 'Kabupaten Morowali')->exists())->toBeFalse();
});

it('classifies only Morowali kecamatan as in scope', function () {
    expect(KecamatanImporter::withinMorowali('Bahodopi'))->toBeTrue()
        ->and(KecamatanImporter::withinMorowali('Wita Ponda'))->toBeTrue()
        ->and(KecamatanImporter::withinMorowali('Petasia Timur'))->toBeFalse();
});
