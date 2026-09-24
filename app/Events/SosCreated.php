<?php

namespace App\Events;

use App\Http\Resources\SosAlertResource;
use App\Models\SosAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SOS baru dibuat. Disiarkan ke seluruh command center (operator + responder)
 * serta channel privat alert itu sendiri bagi pemiliknya.
 */
class SosCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SosAlert $sos,
        public ?int $triggerUserId = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('command-center'),
            new PrivateChannel("sos.{$this->sos->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sos.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'sos' => SosAlertResource::make($this->sos)->resolve(),
            'trigger_user_id' => $this->triggerUserId,
            'changed_at' => now()->toISOString(),
        ];
    }
}
