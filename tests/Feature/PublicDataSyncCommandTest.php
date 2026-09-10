<?php

use App\Models\ExternalData;
use App\Models\PublicDataSource;
use App\Services\PublicData\SourceRegistry;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    SourceRegistry::seed();
});

it('lists all sources when using --list flag', function () {
    $this->artisan('public-data:sync', ['--list' => true])
        ->expectsOutputToContain('ats')
        ->assertExitCode(0);
});

it('returns success when no sources are active', function () {
    PublicDataSource::query()->update(['enabled' => false]);

    $this->artisan('public-data:sync')
        ->assertExitCode(0);
});

it('syncs a specific source by key', function () {
    Http::fake([
        '*rangkuman/ats-by-wilayah*' => Http::response(atsRangkumanHtml()),
    ]);

    $this->artisan('public-data:sync', ['--source' => 'ats'])
        ->assertExitCode(0);

    expect(ExternalData::where('source_key', 'ats')->count())->toBe(8)
        ->and(ExternalData::where('source_key', 'ats')->where('location', 'Kec. Bahodopi')->count())->toBe(0)
        ->and(ExternalData::where('source_key', 'ats')->where('location', 'Bahodopi')->count())->toBe(4);
});

it('shows error for unknown source', function () {
    $this->artisan('public-data:sync', ['--source' => 'unknown'])
        ->assertExitCode(1);
});

it('warns when source is inactive', function () {
    PublicDataSource::where('key', 'ats')->update(['enabled' => false]);

    $this->artisan('public-data:sync', ['--source' => 'ats'])
        ->assertExitCode(0);
});

it('syncs all active sources', function () {
    PublicDataSource::where('key', '!=', 'ats')->update(['enabled' => false]);

    Http::fake([
        '*rangkuman/ats-by-wilayah*' => Http::response(atsRangkumanHtml()),
    ]);

    $this->artisan('public-data:sync')
        ->assertExitCode(0);

    expect(ExternalData::where('source_key', 'ats')->count())->toBe(8);
});

function atsRangkumanHtml(): string
{
    $do = array_fill(0, 18, '1');
    $cells = [
        '<th class="text-center">1</th>',
        '<td class="text-left"><a href="https://ats.example/rangkuman/ats-by-wilayah/180709">Kec. Bahodopi</a></td>',
        '<td class="text-right"> 100 </td>',
        ...array_map(fn ($v) => '<td class="text-right"> '.$v.' </td>', $do),
        '<td class="text-right"> 25 </td>',
        '<td class="text-right"> 51 </td>',
        '<td class="text-right"> 209 </td>',
    ];

    return '<html><body><table id="ats_wilayah"><thead><tr></tr></thead><tbody><tr>'.implode('', $cells).'</tr></tbody></table></body></html>';
}
