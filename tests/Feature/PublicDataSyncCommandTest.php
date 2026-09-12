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
        '*rangkuman/ats-aktif-kembali*' => Http::response(''),
        '*rangkuman/hasil-verifikasi*' => Http::response(''),
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
        '*rangkuman/ats-aktif-kembali*' => Http::response(''),
        '*rangkuman/hasil-verifikasi*' => Http::response(''),
    ]);

    $this->artisan('public-data:sync')
        ->assertExitCode(0);

    expect(ExternalData::where('source_key', 'ats')->count())->toBe(8);
});

it('syncs recovery and verification indicators from the ats portal', function () {
    Http::fake([
        '*rangkuman/ats-by-wilayah*' => Http::response(atsRangkumanHtml()),
        '*rangkuman/ats-aktif-kembali*' => Http::response(atsAktifKembaliHtml()),
        '*rangkuman/hasil-verifikasi*' => Http::response(atsHasilVerifikasiHtml()),
    ]);

    $this->artisan('public-data:sync', ['--source' => 'ats'])
        ->assertExitCode(0);

    $value = fn (string $location, string $indicator) => (float) ExternalData::where('source_key', 'ats')
        ->where('location', $location)
        ->where('indicator', $indicator)
        ->value('value');

    expect($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah'))->toBe(96.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah DO'))->toBe(91.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah LTM'))->toBe(5.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah DO Jenjang Dasar (SD)'))->toBe(40.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah DO Jenjang Atas (SMA/SMK)'))->toBe(31.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah LTM Jenjang Pertama (SMP)'))->toBe(3.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah DO Tingkat 1'))->toBe(1.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Kembali Sekolah DO Tingkat 13'))->toBe(13.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Verifikasi Sudah'))->toBe(30.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Verifikasi Belum'))->toBe(70.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Verifikasi Alasan k_1'))->toBe(10.0)
        ->and($value('Bahodopi', 'Anak Tidak Sekolah - Verifikasi Alasan k_2'))->toBe(5.0)
        ->and($value('Kabupaten Morowali', 'Anak Tidak Sekolah - Kembali Sekolah'))->toBe(96.0)
        ->and($value('Kabupaten Morowali', 'Anak Tidak Sekolah - Verifikasi Alasan k_1'))->toBe(10.0);
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

function atsAktifKembaliHtml(): string
{
    $tingkatDo = implode('', array_map(fn ($i) => '<td class="text-right"> '.$i.' </td>', range(1, 13)));

    $wilayah = [
        '<th class="text-center">1</th>',
        '<td class="text-left"><a href="https://ats.example/rangkuman/ats-aktif-kembali/180709">Kec. Bahodopi</a></td>',
        '<td class="text-right"> 0 </td>',
        '<td class="text-right"> 0 </td>',
        '<td class="text-right"> 0 </td>',
        '<td class="text-right"> 0 </td>',
        '<td class="text-right"> 0 </td>',
        $tingkatDo,
        '<td class="text-right"> 2 </td>',
        '<td class="text-right"> 3 </td>',
        '<td class="text-right"> 96 </td>',
    ];

    $spDo = ['PAUD', 'Dasar (SD)', 'Pertama (SMP)', 'Atas (SMA/SMK)'];
    $spLtm = ['Dasar (SD)', 'Pertama (SMP)'];

    return '<html><body>'
        .'<table id="ats_wilayah"><thead><tr></tr></thead><tbody><tr>'.implode('', $wilayah).'</tr></tbody></table>'
        .'<table id="ats_sp_do"><thead><tr><th>No</th><th>Kecamatan</th><th>Jenjang Pendidikan</th><th>Total</th></tr>'
        .'<tr><th>'.implode('</th><th>', $spDo).'</th></tr></thead>'
        .'<tbody><tr><td>1</td><td>Kec. Bahodopi</td><td>0</td><td>40</td><td>20</td><td>31</td><td>91</td></tr></tbody></table>'
        .'<table id="ats_sp_ltm"><thead><tr><th>No</th><th>Kecamatan</th><th>Jenjang Pendidikan</th><th>Total</th></tr>'
        .'<tr><th>'.implode('</th><th>', $spLtm).'</th></tr></thead>'
        .'<tbody><tr><td>1</td><td>Kec. Bahodopi</td><td>2</td><td>3</td><td>5</td></tr></tbody></table>'
        .'</body></html>';
}

function atsHasilVerifikasiHtml(): string
{
    $reasons = [];

    foreach (range(1, 25) as $i) {
        $reasons[] = '<td class="text-right"> '.($i === 1 ? 10 : ($i === 2 ? 5 : 0)).' </td>';
    }

    $cells = [
        '<th class="text-center">1</th>',
        '<td class="text-left"><a href="https://ats.example/rangkuman/hasil-verifikasi/180709">Kec. Bahodopi</a></td>',
        ...$reasons,
        '<td class="text-right"> 30 </td>',
        '<td class="text-right"> 70 </td>',
        '<td class="text-right"> 100 </td>',
    ];

    return '<html><body><table id="ats_wilayah_alasan"><thead><tr></tr></thead><tbody><tr>'.implode('', $cells).'</tbody></table></body></html>';
}
