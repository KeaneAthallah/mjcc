<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PoskamlingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kecamatan_id' => $this->kecamatan_id,
            'kelurahan_id' => $this->kelurahan_id,
            'kecamatan' => $this->whenLoaded('kecamatan', fn () => $this->kecamatan->name),
            'kelurahan' => $this->whenLoaded('kelurahan', fn () => $this->kelurahan->name),
            'name' => $this->name,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
