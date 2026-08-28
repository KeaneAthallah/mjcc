<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolsekResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kecamatan_id' => $this->kecamatan_id,
            'kecamatan' => $this->whenLoaded('kecamatan', fn () => $this->kecamatan->name),
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'personnel_count' => $this->personnel_count,
            'poskamling_count' => $this->poskamling_count,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
