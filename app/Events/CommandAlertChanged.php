<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Command alert (kotak "Perlu Perhatian" di command center) dibuat,
 * diperbarui, atau ditutup. Sidebar + dropdown alert di semua klien langsung
 * disegarkan; listener mem-bust cache sidebar.
 */
class CommandAlertChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ?int $alertId = null,
        public ?string $status = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('command-center')];
    }

    public function broadcastAs(): string
    {
        return 'command-alert.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->alertId,
            'status' => $this->status,
            'changed_at' => now()->toISOString(),
        ];
    }
}
