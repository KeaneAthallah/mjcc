<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Generic "data master berubah" event yang disiarkan saat ada record master
 * (sekolah, faskes, poskamling, tipkamtikmas, pasar, polsek, kecamatan,
 * kelurahan, subject, user) dibuat/diubah/dihapus/dipulihkan/force-deleted.
 *
 * Klien pada channel `dashboard` memakai payload untuk invalidasi query yang
 * relevan, dan listener `ClearDataCaches` menyingkirkan agregat yang sudah
 * basi secara event-driven (bukan menunggu TTL 60 detik).
 */
class MasterDataChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, array<string, int>>  $resources  e.g. ['schools' => ['created' => 1, 'updated' => 2]]
     * @param  int|null  $triggerUserId  user yang memicu perubahan (jika ada)
     */
    public function __construct(
        public array $resources,
        public ?int $triggerUserId = null,
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
        return 'master-data.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'resources' => $this->resources,
            'trigger_user_id' => $this->triggerUserId,
            'changed_at' => now()->toISOString(),
        ];
    }
}
