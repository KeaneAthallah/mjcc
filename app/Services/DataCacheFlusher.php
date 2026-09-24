<?php

namespace App\Services;

use App\Models\Kecamatan;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized, event-driven invalidation of the aggregate caches that back
 * dashboards, maps, and the command-center status/freshness widgets.
 *
 * Instead of waiting for the 60s TTL (or relying on a manual "refresh"
 * button), every domain change dispatches one of the `App\Events\*` events and
 * this service wipes exactly the affected keys.
 */
class DataCacheFlusher
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly MapService $map,
        private readonly CommandCenterStatusService $status,
    ) {}

    /**
     * Flush every aggregate that depends on the master/public datasets.
     */
    public function masterData(): void
    {
        $this->dashboard->clearCache();
        $this->map->flushCache();
        $this->status->clearCache();
        Cache::forget('command-center.data-freshness');
        Cache::forget('command-alerts.synced_at');
    }

    /**
     * Flush the sidebar/alert caches that back the command-center alert bell.
     */
    public function commandAlerts(): void
    {
        Cache::forget('command-center.sidebar.alerts');
        Cache::forget('command-center.sidebar.alerts.recent');
    }

    /**
     * Flush per-kecamatan caches for map layers and command-center status.
     *
     * @return int[] Kecamatan ids iterated
     */
    private function kecamatanIds(): array
    {
        return Kecamatan::query()->pluck('id')->all();
    }
}
