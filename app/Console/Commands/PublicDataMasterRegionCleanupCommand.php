<?php

namespace App\Console\Commands;

use App\Services\PublicData\MasterDataRegionCleanup;
use Illuminate\Console\Command;

/**
 * Removes every region row outside Kabupaten Morowali. Kecamatan, kelurahan,
 * polsek and school rows belonging to the Morowali Utara regency are
 * permanently deleted so the master tables stay scoped to Morowali only.
 */
class PublicDataMasterRegionCleanupCommand extends Command
{
    protected $signature = 'public-data:master-region-cleanup';

    protected $description = 'Hapus permanen data wilayah di luar Kabupaten Morowali (Morowali Utara)';

    public function handle(MasterDataRegionCleanup $cleanup): int
    {
        $counts = $cleanup->cleanup();

        $this->line('  - kecamatan di luar Morowali: '.$counts['out_of_scope']);
        $this->line('  - kecamatan dihapus: '.$counts['kecamatan']);
        $this->line('  - kelurahan dihapus: '.$counts['kelurahan']);
        $this->line('  - polsek dihapus: '.$counts['polsek']);
        $this->line('  - sekolah dihapus: '.$counts['schools']);

        $this->info('Data master kini hanya untuk Kabupaten Morowali.');

        return self::SUCCESS;
    }
}
