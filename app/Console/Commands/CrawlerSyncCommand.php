<?php

namespace App\Console\Commands;

use App\Jobs\CrawlSourceJob;
use App\Services\Crawlers\CrawlerManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

#[Signature('crawler:sync {--source= : Slug sumber (ats|dapo|sp2kp|bps). Jika kosong, semua sumber aktif dijalankan.} {--queue : Kirim sebagai job antrean alih-alih berjalan sinkron.}')]
#[Description('Menjalankan sinkronisasi data pemerintah untuk Morowali & Morowali Utara')]
class CrawlerSyncCommand extends Command
{
    public function handle(CrawlerManager $manager): int
    {
        if (! config('crawler.enabled')) {
            $this->warn('Crawler dinonaktifkan melalui config crawler.enabled.');

            return self::SUCCESS;
        }

        $manager->seedSources();

        if ($this->option('queue')) {
            $slugs = $this->option('source') ? [$this->option('source')] : $manager->availableSources()->all();

            foreach ($slugs as $slug) {
                Queue::push(new CrawlSourceJob($slug));
                $this->line("Antrekan [{$slug}].");
            }

            return self::SUCCESS;
        }

        if ($this->option('source')) {
            $slug = (string) $this->option('source');
            $result = $manager->syncSource($slug);

            $this->line("[{$result->source}] found={$result->found} created={$result->created} updated={$result->updated} unchanged={$result->unchanged} failed={$result->failed}");

            return self::SUCCESS;
        }

        $results = $manager->syncAll();

        foreach ($results as $slug => $result) {
            $this->line("[{$result->source}] found={$result->found} created={$result->created} updated={$result->updated} unchanged={$result->unchanged} failed={$result->failed}");
        }

        return self::SUCCESS;
    }
}
