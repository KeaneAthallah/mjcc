<?php

namespace App\Console\Commands;

use App\Services\PublicData\PublicDataScraper;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('public-data:scrape {--sector=} {--limit=0} {--discover-only}')]
#[Description('Menghimpun data statistik publik dari portal Satu Data Morowali per sektor (pendidikan, kesehatan, keamanan)')]
class PublicDataScrapeCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(PublicDataScraper $scraper): int
    {
        if (! (bool) config('public_data.enabled', true)) {
            $this->warn('Modul Data Publik dinonaktifkan (PUBLIC_DATA_ENABLED=false).');

            return self::SUCCESS;
        }

        $sector = $this->option('sector') !== null && $this->option('sector') !== ''
            ? (string) $this->option('sector')
            : null;

        $limit = max(0, (int) $this->option('limit'));
        $discoverOnly = (bool) $this->option('discover-only');

        $results = $scraper->scrape($sector, $limit, $discoverOnly);

        foreach ($results as $result) {
            if ($discoverOnly) {
                $this->line(sprintf(
                    '  %-12s %s: %d dataset ditemukan (%d halaman katalog)',
                    (string) $result['sector'],
                    (string) $result['label'],
                    (int) $result['discovered'],
                    (int) $result['pages'],
                ));

                continue;
            }

            $status = $result['failures'] === []
                ? sprintf('<info>%s</info>', 'berhasil')
                : sprintf('<comment>%s</comment>', count($result['failures']).' dataset gagal');

            $this->line(sprintf(
                '  %-12s %-12s dataset: %-4d baru: %-4d diperbarui: %-4d total: %-4d (%s)',
                (string) $result['sector'],
                (string) $result['label'],
                (int) $result['datasets'],
                (int) $result['created'],
                (int) $result['updated'],
                (int) $result['records'],
                $status,
            ));

            foreach (array_slice($result['failures'], 0, 3) as $failure) {
                $this->error('    '.($failure['url'] !== '' ? $failure['url'].' — ' : '').$failure['message']);
            }
        }

        return self::SUCCESS;
    }
}
