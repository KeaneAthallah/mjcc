<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disiarkan setelah satu sektor data publik berhasil disinkronkan
 * (Satu Data Morowali / BPS / APBD / SITABA / dll.). Klien memakai ini untuk
 * menyegarkan data publik + peta tanpa menunggu polling.
 */
class PublicDataSyncCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $result  ringkasan hasil sinkronisasi
     */
    public function __construct(
        public string $sector,
        public string $label,
        public array $result,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'public-data.sync-completed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'sector' => $this->sector,
            'label' => $this->label,
            'result' => $this->result,
            'completed_at' => now()->toISOString(),
        ];
    }
}
