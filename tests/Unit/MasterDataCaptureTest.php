<?php

use App\Services\PublicData\HttpClient;
use App\Services\PublicData\MasterDataCapture;

beforeEach(function () {
    $this->capture = new MasterDataCapture(Mockery::mock(HttpClient::class));
    $this->fixtures = __DIR__.'/../Fixtures/PublicData';
});

it('parses the Kemkes SISDMK puskesmas table from real HTML', function () {
    $rows = $this->capture->parseKemkesHtml(
        (string) file_get_contents($this->fixtures.'/kemkes-puskesmas.html')
    );

    expect($rows)->toHaveCount(11);

    $bahodopi = collect($rows)->first(fn ($row) => $row['name'] === 'BAHODOPI');

    expect($bahodopi)->not->toBeNull()
        ->and($bahodopi['jenis'])->toBe('Tidak Terpencil Rawat Inap')
        ->and($bahodopi['dokter'])->toBe(4)
        ->and($bahodopi['dokter_gigi'])->toBe(2)
        ->and($bahodopi['perawat'])->toBe(35)
        ->and($bahodopi['bidan'])->toBe(51);
});

it('parses the SP2KP market list response', function () {
    $raw = (string) file_get_contents($this->fixtures.'/sp2kp-raw-response.json');
    $markets = $this->capture->parseSp2kpMarkets($raw);

    expect($markets)->toHaveCount(1);

    $market = $markets[0];

    expect($market['nama'])->toBe('Pasar Rakyat Bungku Tengah')
        ->and($market['kode_kecamatan'])->toBe('7206051')
        ->and($market['latitude'])->toBe(-2.5674091)
        ->and($market['longitude'])->toBe(121.8520499)
        ->and($market['tipe'])->toBe('Pasar Rakyat');
});

it('throws a RuntimeException for an invalid SP2KP payload', function () {
    $this->capture->parseSp2kpMarkets('{"nope":true}');
})->throws(RuntimeException::class);
