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
 * Status/detail SOS berubah (acknowledge, respond, accept, on_the_way,
 * arrived, constrain, resolve, cancel, dll.). Pemilik alert, operator, dan
 * responder yang relevan menerima pembaruan secara realtime.
 */
class SosUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public SosAlert $sos,
        public ?int $triggerUserId = null,
        public ?string $previousStatus = null,
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
        return 'sos.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'sos' => SosAlertResource::make($this->sos)->resolve(),
            'trigger_user_id' => $this->triggerUserId,
            'previous_status' => $this->previousStatus,
            'changed_at' => now()->toISOString(),
        ];
    }
}
