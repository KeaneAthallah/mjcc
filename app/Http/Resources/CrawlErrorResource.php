<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CrawlErrorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crawl_source_id' => $this->crawl_source_id,
            'crawl_run_id' => $this->crawl_run_id,
            'http_status' => $this->http_status,
            'message' => $this->message,
            'url' => $this->url,
            'retry_count' => $this->retry_count,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
