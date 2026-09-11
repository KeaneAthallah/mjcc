<?php

use App\Console\Commands\PublicDataMasterCaptureCommand;
use App\Services\PublicData\MasterDataCapture;
use Illuminate\Support\Facades\Http;

it('captures puskesmas and market snapshots from the live responses', function () {
    $fixtures = base_path('tests/Fixtures/PublicData');

    Http::fake([
        'https://dreams.kemkes.go.id/*' => Http::response(
            (string) file_get_contents($fixtures.'/kemkes-puskesmas.html')
        ),
        'https://api-sp2kp.kemendag.go.id/*' => Http::response(
            (string) file_get_contents($fixtures.'/sp2kp-raw-response.json')
        ),
    ]);

    $out = storage_path('app/data/capture-test-'.uniqid());
    $result = app(MasterDataCapture::class)->capture($out);

    expect(is_file($result['puskesmas']))->toBeTrue()
        ->and(is_file($result['markets']))->toBeTrue();

    $puskesmas = json_decode((string) file_get_contents($result['puskesmas']), true);
    $markets = json_decode((string) file_get_contents($result['markets']), true);

    expect($puskesmas['puskesmas'])->toHaveCount(11)
        ->and($markets['markets'])->toHaveCount(1)
        ->and($markets['markets'][0]['nama'])->toBe('Pasar Rakyat Bungku Tengah');

    $this->artisan(PublicDataMasterCaptureCommand::class, ['dir' => $out])
        ->expectsOutputToContain('Snapshot tersimpan')
        ->assertExitCode(0);

    array_map('unlink', [$result['puskesmas'], $result['markets']]);
    rmdir($out);
});
