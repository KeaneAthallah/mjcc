<?php

namespace App\Listeners;

use App\Events\CommandAlertChanged;
use App\Services\DataCacheFlusher;

/**
 * Busts the sidebar/alert-bell caches when a command alert changes.
 */
class ClearCommandAlertCaches
{
    public function __construct(private readonly DataCacheFlusher $flusher) {}

    public function handleCommandAlertChanged(CommandAlertChanged $event): void
    {
        $this->flusher->commandAlerts();
    }
}
