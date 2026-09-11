<?php

namespace App\Console\Commands;

use App\Services\PublicData\MasterDataCapture;
use App\Services\PublicData\MasterDataImport;
use Illuminate\Console\Command;

/**
 * Replaces seeded placeholder master rows with real data whose sources do not
 * require a browser:
 *
 *  - Puskesmas rows + staffing figures from the Kemkes SISDMK snapshot;
 *  - Market rows + coordinates from the SP2KP snapshot.
 *
 * The command also retires every remaining 'seed' row (health facilities,
 * poskamlings, tipkamtikmas, markets) and re-tags Polsek rows as 'manual' so
 * they stay active without being mistaken for placeholders.
 */
class PublicDataMasterReplaceCommand extends Command
{
    protected $signature = 'public-data:master-replace
        {--capture : Ambil ulang snapshot dari Kemkes & SP2KP sebelum impor}';

    protected $description = 'Ganti data master dummy dengan data nyata (puskesmas & pasar)';

    public function handle(MasterDataCapture $capture, MasterDataImport $import): int
    {
        $paths = [
            'puskesmas' => config('public_data.master.snapshots.puskesmas'),
            'markets' => config('public_data.master.snapshots.markets'),
        ];

        if ($this->option('capture')) {
            $captured = $capture->capture();

            $paths = [
                'puskesmas' => $captured['puskesmas'],
                'markets' => $captured['markets'],
            ];

            $this->info("Snapshot diambil ulang ({$captured['captured_at']}).");
        }

        try {
            $result = $import->import($paths['puskesmas'], $paths['markets']);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Puskesmas: '.$result['puskesmas']['created'].' baru, '.$result['puskesmas']['updated'].' diperbarui (dari '.$result['puskesmas']['total'].').');
        $this->info('Pasar: '.$result['markets']['created'].' baru, '.$result['markets']['updated'].' diperbarui (dari '.$result['markets']['total'].').');
        $this->line('  - polsek ditandai manual: '.$result['polsek_tagged']);
        $this->line('  - nonaktif: kesehatan '.$result['deactivated']['health'].', pasar '.$result['deactivated']['markets'].', poskamling '.$result['deactivated']['poskamlings'].', tipkamtikmas '.$result['deactivated']['tipkamtikmas']);

        if (blank($result['captured_at'])) {
            $this->line('  - (snapshot tanpa waktu pengambilan)');
        }

        return self::SUCCESS;
    }
}
