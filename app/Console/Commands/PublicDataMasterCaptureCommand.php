<?php

namespace App\Console\Commands;

use App\Services\PublicData\MasterDataCapture;
use Illuminate\Console\Command;

/**
 * Captures real per-entity master data (Kemkes puskesmas, SP2KP markets) into
 * JSON snapshots that `public-data:master-replace` later imports.
 */
class PublicDataMasterCaptureCommand extends Command
{
    protected $signature = 'public-data:master-capture
        {dir? : Direktori tujuan snapshot (default: config public_data.master.dir)}';

    protected $description = 'Ambil data master nyata (puskesmas Kemkes & pasar SP2KP) ke snapshot JSON';

    public function handle(MasterDataCapture $capture): int
    {
        try {
            $result = $capture->capture($this->argument('dir'));
        } catch (\Throwable $e) {
            $this->error('Capture gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Snapshot tersimpan di {$result['dir']}");
        $this->line('  - puskesmas: '.$result['puskesmas']);
        $this->line('  - markets: '.$result['markets']);
        $this->line('  - waktu: '.$result['captured_at']);

        return self::SUCCESS;
    }
}
