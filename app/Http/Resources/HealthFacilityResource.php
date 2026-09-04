<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthFacilityResource extends JsonResource
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
            'facility_type' => $this->facility_type,
            'address' => $this->address,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'condition' => $this->condition,
            'beds' => $this->beds,
            'doctors' => $this->doctors,
            'nurses' => $this->nurses,
            'midwives' => $this->midwives,
            'status' => $this->status,
            'phone' => $this->phone,
            'description' => $this->description,
            'source_name' => $this->source_name,
            'source_url' => $this->source_url,
            'source_id' => $this->source_id,
            'source_updated_at' => $this->source_updated_at?->toISOString(),
            'last_crawled_at' => $this->last_crawled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
