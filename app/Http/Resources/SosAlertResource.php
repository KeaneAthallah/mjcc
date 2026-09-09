<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SosAlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'role' => $this->user->role,
                ];
            }),
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'accuracy' => $this->accuracy !== null ? (float) $this->accuracy : null,
            'status' => $this->status,
            'category' => $this->category,
            'category_label' => $this->categoryLabel,
            'message' => $this->message,
            'response_message' => $this->response_message,
            'responded_by' => $this->responded_by,
            'responded_by_user' => $this->whenLoaded('respondedBy', fn () => [
                'id' => $this->respondedBy->id,
                'name' => $this->respondedBy->name,
            ]),
            'responded_at' => $this->responded_at?->toISOString(),
            'resolved_by' => $this->resolved_by,
            'resolved_by_user' => $this->whenLoaded('resolvedBy', fn () => [
                'id' => $this->resolvedBy->id,
                'name' => $this->resolvedBy->name,
            ]),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'accepted_by' => $this->accepted_by,
            'accepted_by_user' => $this->whenLoaded('acceptedBy', fn () => [
                'id' => $this->acceptedBy->id,
                'name' => $this->acceptedBy->name,
            ]),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'is_owner' => $user !== null && $this->ownedBy($user),
            'can_manage' => $user !== null && $user->can('manage', $this->resource),
        ];
    }
}
