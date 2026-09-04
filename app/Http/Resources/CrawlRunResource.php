<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrawlRunResource extends JsonResource
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
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),
            'status' => $this->status,
            'records_found' => $this->records_found,
            'records_created' => $this->records_created,
            'records_updated' => $this->records_updated,
            'records_unchanged' => $this->records_unchanged,
            'records_failed' => $this->records_failed,
            'error_count' => $this->error_count,
            'duration' => $this->duration,
            'human_duration' => $this->humanDuration(),
            'log' => $this->log,
            'errors' => CrawlErrorResource::collection($this->whenLoaded('errors')),
            'records' => CrawlRecordResource::collection($this->whenLoaded('records')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
