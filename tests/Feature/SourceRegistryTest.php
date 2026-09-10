<?php

use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use App\Services\PublicData\SourceRegistry;

it('returns all source definitions', function () {
    $sources = SourceRegistry::all();

    expect($sources)->toBeArray()
        ->toHaveKeys(['satudata', 'ats', 'dapodik', 'sp2kp', 'bps', 'irbi', 'sitaba', 'apbd']);
});

it('gets adapter class for a valid source key', function () {
    $adapterClass = SourceRegistry::adapterClass('ats');

    expect($adapterClass)->toBeString()
        ->toContain('AnakTidakSekolahAdapter');
});

it('returns null for an unknown source key', function () {
    $adapterClass = SourceRegistry::adapterClass('unknown');

    expect($adapterClass)->toBeNull();
});

it('gets a source definition by key', function () {
    $source = SourceRegistry::get('bps');

    expect($source)->toBeArray()
        ->toHaveKeys(['adapter', 'name', 'category', 'description', 'source_url'])
        ->and($source['category'])->toBe('pemantauan');
});

it('returns null for an unknown source', function () {
    $source = SourceRegistry::get('nonexistent');

    expect($source)->toBeNull();
});

it('gets source keys for a specific category', function () {
    $keys = SourceRegistry::keysForCategory('pendidikan');

    expect($keys)->toBeArray()
        ->toContain('ats')
        ->toContain('dapodik')
        ->not->toContain('bps');
});

it('resolves an adapter instance', function () {
    $adapter = SourceRegistry::resolve('sp2kp');

    expect($adapter)->toBeInstanceOf(PublicDataSourceAdapter::class);
});

it('returns null when resolving an unknown adapter', function () {
    $adapter = SourceRegistry::resolve('unknown');

    expect($adapter)->toBeNull();
});

it('seeds the database with all sources', function () {
    SourceRegistry::seed();

    expect(PublicDataSource::count())->toBeGreaterThanOrEqual(8);

    $ats = PublicDataSource::where('key', 'ats')->first();
    expect($ats)->not->toBeNull()
        ->and($ats->name)->toBe('Anak Tidak Sekolah')
        ->and($ats->category)->toBe('pendidikan')
        ->and($ats->enabled)->toBeTrue();
});

it('syncs an inactive source and returns skipped', function () {
    PublicDataSource::create([
        'key' => 'test_source',
        'name' => 'Test Source',
        'category' => 'pemantauan',
        'adapter_class' => 'App\\Services\\PublicData\\SatuDataMorowaliAdapter',
        'enabled' => false,
        'status' => PublicDataSource::STATUS_PENDING,
    ]);

    $result = SourceRegistry::sync('test_source');

    expect($result['status'])->toBe('skipped');
});

it('syncs a nonexistent source and returns skipped', function () {
    $result = SourceRegistry::sync('nonexistent_source');

    expect($result['status'])->toBe('skipped');
});
