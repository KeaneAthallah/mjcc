<?php

namespace App\Services;

use App\Events\CommandAlertChanged;
use App\Models\CommandAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Menyinkronkan hasil deteksi AlertService ke tabel command_alerts.
 *
 * Setiap aturan+resource dipersistenkan sebagai satu alert yang dapat
 * ditindaklanjuti. Sinkronisasi dibatasi (guard) agar tidak berulang-ulang
 * menjalankan query deteksi yang berat; input data baru tetap terdeteksi pada
 * sinkronisasi berikutnya. Apabila kondisi sudah tidak terdeteksi lagi, alert
 * terbuka otomatis ditandai selesai (dapat dikonfigurasi).
 */
class CommandAlertSyncService
{
    public function __construct(private readonly AlertService $detector) {}

    /**
     * @return int Jumlah alert aktif/terbuka setelah sinkronisasi.
     */
    public function sync(bool $force = false): int
    {
        $guardKey = 'command-alerts.synced_at';
        $ttl = (int) config('command-center.alerts.sync_ttl', 60);

        if (! $force && ! Cache::add($guardKey, now(), $ttl)) {
            return CommandAlert::query()->whereIn('status', CommandAlert::openStatuses())->count();
        }

        $syncStartedAt = Carbon::now();
        $seenKeys = [];
        $changed = false;

        foreach ($this->detector->all() as $alert) {
            $resourceClass = $alert['resource_class'] ?? null;
            $resourceId = $alert['resource_id'] ?? null;

            if ($resourceClass === null || $resourceId === null) {
                continue;
            }

            $seenKeys[] = $resourceClass.':'.$resourceId.':'.$alert['rule'];

            $synced = CommandAlert::updateOrCreate(
                [
                    'rule' => $alert['rule'],
                    'resource_type' => $resourceClass,
                    'resource_id' => (int) $resourceId,
                ],
                [
                    'resource_slug' => $alert['resource_type'],
                    'severity' => $alert['severity'],
                    'sector_key' => $alert['sector_key'],
                    'sector' => $alert['sector'],
                    'title' => $alert['title'],
                    'description' => $alert['detail'],
                    'kecamatan_id' => $alert['kecamatan_id'],
                    'latitude' => $alert['latitude'],
                    'longitude' => $alert['longitude'],
                    'detail_route' => $alert['detail_route'],
                    'detail_params' => $alert['detail_params'],
                    'opened_at' => $alert['created_at'],
                    'last_seen_at' => $syncStartedAt,
                ],
            );

            $changed = $changed || $synced->wasRecentlyCreated || $synced->wasChanged();
        }

        if ((bool) config('command-center.alerts.auto_resolve', true)) {
            $changed = $this->resolveMissing($seenKeys) || $changed;
        }

        // Broadcast + bust the sidebar caches only when something actually
        // changed, so clients stop polling the alert bell.
        if ($changed) {
            CommandAlertChanged::dispatch();
        }

        return CommandAlert::query()->whereIn('status', CommandAlert::openStatuses())->count();
    }

    /**
     * Menandai selesai alert terbuka yang tidak lagi terdeteksi pada
     * sinkronisasi saat ini. Komparasi berbasis kunci deteksi (bukan
     * timestamp) agar tetap akurat walau dua sinkronisasi berjalan dalam
     * detik yang sama.
     *
     * @param  string[]  $seenKeys
     */
    private function resolveMissing(array $seenKeys): bool
    {
        $changed = false;

        CommandAlert::query()
            ->whereIn('status', CommandAlert::openStatuses())
            ->whereNotNull('resource_type')
            ->each(function (CommandAlert $alert) use ($seenKeys, &$changed) {
                $key = $alert->resource_type.':'.$alert->resource_id.':'.$alert->rule;

                if (! in_array($key, $seenKeys, true)) {
                    $alert->update([
                        'status' => CommandAlert::STATUS_SELESAI,
                        'resolved_at' => now(),
                    ]);

                    $changed = true;
                }
            });

        return $changed;
    }
}
