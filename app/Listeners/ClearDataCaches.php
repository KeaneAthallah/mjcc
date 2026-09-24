<?php

namespace App\Listeners;

use App\Events\MasterDataChanged;
use App\Events\PublicDataSyncCompleted;
use App\Services\DataCacheFlusher;

/**
 * Busts the aggregate caches (dashboard, maps, status, freshness) whenever
 * master data or public datasets change.
 */
class ClearDataCaches
{
    public function __construct(private readonly DataCacheFlusher $flusher) {}

    public function handleMasterDataChanged(MasterDataChanged $event): void
    {
        $this->flusher->masterData();
    }

    public function handlePublicDataSyncCompleted(PublicDataSyncCompleted $event): void
    {
        $this->flusher->masterData();
    }
}
