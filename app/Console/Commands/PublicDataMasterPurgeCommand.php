<?php

namespace App\Console\Commands;

use App\Services\PublicData\MasterDataPurge;
use Illuminate\Console\Command;

/**
 * Permanently removes every seeded placeholder master row:
 *
 *  - health facilities, markets, poskamlings and tipkamtikmas tagged
 *    source 'seed';
 *  - placeholder schools left inactive with a null source.
 *
 * Real rows (dapodik/kemkes/sp2kp), manual rows (polsek 'manual') and
 * CRUD-created rows (source null, active) are never touched.
 */
class PublicDataMasterPurgeCommand extends Command
{
    protected $signature = 'public-data:master-purge';

    protected $description = 'Hapus permanen semua data master dummy (seed)';

    public function handle(MasterDataPurge $purge): int
    {
        $counts = $purge->purge();
        $total = array_sum($counts);

        foreach ($counts as $table => $count) {
            $this->line('  - '.$table.': '.$count.' dihapus');
        }

        $this->info("$total baris data dummy dihapus permanen.");

        return self::SUCCESS;
    }
}
