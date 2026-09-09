<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResponderLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'responder_type' => $this->user->responder_type,
                'responder_type_label' => $this->user?->responderTypeLabel,
            ]),
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
