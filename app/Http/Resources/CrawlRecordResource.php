<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrawlRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crawl_source_id' => $this->crawl_source_id,
            'source' => $this->whenLoaded('source', fn () => [
                'id' => $this->source->id,
                'name' => $this->source->name,
                'slug' => $this->source->slug,
            ]),
            'external_id' => $this->external_id,
            'record_type' => $this->record_type,
            'name' => $this->name,
            'province_code' => $this->province_code,
            'kabupaten_code' => $this->kabupaten_code,
            'kabupaten_name' => $this->kabupaten_name,
            'kecamatan_code' => $this->kecamatan_code,
            'kecamatan_name' => $this->kecamatan_name,
            'desa_code' => $this->desa_code,
            'desa_name' => $this->desa_name,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'data' => $this->data,
            'source_url' => $this->source_url,
            'source_updated_at' => $this->source_updated_at?->toISOString(),
            'first_seen_at' => $this->first_seen_at?->toISOString(),
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'content_hash' => $this->content_hash,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
