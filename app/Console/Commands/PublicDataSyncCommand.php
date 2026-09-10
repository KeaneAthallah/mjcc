<?php

namespace App\Console\Commands;

use App\Models\PublicDataSource;
use App\Services\PublicData\SourceRegistry;
use Illuminate\Console\Command;

class PublicDataSyncCommand extends Command
{
    protected $signature = 'public-data:sync
        {--source= : Sync a specific source key (e.g. bps, ats, all)}
        {--list : List all registered sources}';

    protected $description = 'Synchronize public data from external sources';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listSources();
        }

        $source = $this->option('source');

        if ($source === null || $source === 'all') {
            return $this->syncAll();
        }

        return $this->syncOne($source);
    }

    private function listSources(): int
    {
        $sources = PublicDataSource::orderBy('category')->orderBy('name')->get();

        $rows = $sources->map(fn (PublicDataSource $s) => [
            $s->key,
            $s->name,
            $s->category,
            $s->enabled ? 'Aktif' : 'Nonaktif',
            $s->statusLabel(),
            $s->last_success_at?->format('d M Y H:i') ?? '—',
            number_format($s->record_count),
        ])->all();

        $this->table(
            ['Key', 'Nama', 'Kategori', 'Status', 'Sinkron', 'Terakhir Berhasil', 'Record'],
            $rows
        );

        return self::SUCCESS;
    }

    private function syncAll(): int
    {
        $sources = PublicDataSource::enabled()->get();

        if ($sources->isEmpty()) {
            $this->warn('Tidak ada sumber data yang aktif.');

            return self::SUCCESS;
        }

        $this->info("Sinkronisasi {$sources->count()} sumber data...");

        $bar = $this->output->createProgressBar($sources->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($sources as $source) {
            $result = SourceRegistry::sync($source->key);

            if ($result['status'] === 'success') {
                $success++;
                $this->newLine(1);
                $this->line("  <info>✓</info> {$source->name}: {$result['records']['created']} baru, {$result['records']['updated']} diperbarui ({$result['duration_ms']}ms)");
            } else {
                $failed++;
                $this->newLine(1);
                $this->line("  <error>✗</error> {$source->name}: {$result['message']}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai: {$success} berhasil, {$failed} gagal.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function syncOne(string $key): int
    {
        $source = PublicDataSource::where('key', $key)->first();

        if ($source === null) {
            $this->error("Sumber '{$key}' tidak ditemukan.");

            return self::FAILURE;
        }

        if (! $source->enabled) {
            $this->warn("Sumber '{$key}' tidak aktif.");

            return self::SUCCESS;
        }

        $this->info("Sinkronisasi: {$source->name}...");

        $result = SourceRegistry::sync($key);

        if ($result['status'] === 'success') {
            $this->info("Berhasil: {$result['records']['created']} baru, {$result['records']['updated']} diperbarui ({$result['duration_ms']}ms).");

            return self::SUCCESS;
        }

        $this->error("Gagal: {$result['message']}");

        return self::FAILURE;
    }
}
