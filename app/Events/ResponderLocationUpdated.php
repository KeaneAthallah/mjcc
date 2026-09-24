<?php

namespace App\Events;

use App\Http\Resources\ResponderLocationResource;
use App\Models\ResponderLocation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lokasi live seorang responder diperbarui (di-share dari perangkat petugas
 * saat menuju lokasi). Pemilik alert dan command center melihat pergerakan
 * petugas pada peta tanpa polling.
 */
class ResponderLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ResponderLocation $location,
        public int $sosId,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('command-center'),
            new PrivateChannel("sos.{$this->sosId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'responder-location.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'sos_id' => $this->sosId,
            'location' => ResponderLocationResource::make($this->location)->resolve(),
            'changed_at' => now()->toISOString(),
        ];
    }
}
