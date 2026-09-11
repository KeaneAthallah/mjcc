<?php

namespace App\Console\Commands;

use App\Services\PublicData\DapodikSchoolService;
use Illuminate\Console\Command;

/**
 * Imports the per-school Dapodik snapshot into the schools master table.
 *
 * The snapshot is written by scripts/dapodik/dapodik-capture.cjs, which runs a
 * real browser because the Dapodik WAF rejects plain HTTP clients. Without the
 * given file path the command falls back to the configured default snapshot.
 */
class PublicDataDapodikImportCommand extends Command
{
    protected $signature = 'public-data:dapodik-import
        {file? : Path to the Dapodik snapshot JSON (default: config snapshot)}
        {--replace : Deactivate current SD/SMP schools absent from the snapshot}';

    protected $description = 'Impor sekolah nyata dari snapshot Dapodik ke tabel master';

    public function handle(DapodikSchoolService $service): int
    {
        $path = $this->argument('file') ?? config('public_data.dapodik.snapshot');

        $this->info("Impor snapshot Dapodik: {$path}");

        try {
            $result = $service->import($path, $this->option('replace'));
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Berhasil: '.$result['created'].' baru, '.$result['updated'].' diperbarui.');
        $this->line('  - dilewati: '.$result['skipped'].' (termasuk '.$result['no_kecamatan'].' tanpa kecamatan)');
        $this->line('  - nonaktif: '.$result['deactivated'].(blank($result['captured_at']) ? '' : ' (snapshot '.$result['captured_at'].')'));

        return self::SUCCESS;
    }
}
