<?php

namespace App\Console\Commands;

use App\Services\DataImport\DataImportService;
use App\Services\PublicData\PublicDataScraper;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('data:sync {--sector=} {--import-only}')]
#[Description('Menghimpun data publik dari portal Satu Data Morowali dan menyinkronkannya ke data master (sekolah, fasilitas kesehatan, kecamatan)')]
class DataSyncCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DataImportService $importService): int
    {
        $sector = $this->option('sector') !== null && $this->option('sector') !== ''
            ? (string) $this->option('sector')
            : null;

        $importOnly = (bool) $this->option('import-only');

        if ($importOnly) {
            $this->line('Mode: hanya impor dari data tersimpan (tanpa pengambilan dari portal).');
        } elseif (! (bool) config('public_data.enabled', true)) {
            $this->warn('Modul Data Publik dinonaktifkan (PUBLIC_DATA_ENABLED=false).');

            return self::SUCCESS;
        }

        if (! $importOnly) {
            $scrapeResult = app(PublicDataScraper::class)->scrape($sector);

            foreach ($scrapeResult as $result) {
                $this->line(sprintf(
                    '  %-12s pengambilan: %s',
                    (string) $result['sector'],
                    $result['failures'] === []
                        ? sprintf('<info>%s</info>', 'berhasil')
                        : sprintf('<comment>%d</comment> dataset gagal, total %d record', count($result['failures']), (int) $result['records']),
                ));
            }
        }

        $results = $importService->run($sector);

        foreach ($results as $result) {
            if ((string) $result['status'] === 'gagal') {
                $this->error(sprintf('  %-12s impor gagal: %s', (string) $result['sector'], (string) $result['error']));

                continue;
            }

            $this->line(sprintf(
                '  %-12s impor: dataset %-3d entitas baru %-3d diperbarui %-3d dilewati %-3d | kecamatan baru %-3d diperbarui %-3d',
                (string) $result['sector'],
                (int) $result['datasets_scanned'],
                (int) $result['entities_created'],
                (int) $result['entities_updated'],
                (int) $result['entities_skipped'],
                (int) $result['kecamatan_created'],
                (int) $result['kecamatan_updated'],
            ));
        }

        return self::SUCCESS;
    }
}
