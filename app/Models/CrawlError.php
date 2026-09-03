<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlError extends Model
{
    use HasFactory;

    protected $fillable = [
        'crawl_source_id',
        'crawl_run_id',
        'http_status',
        'message',
        'url',
        'retry_count',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'retry_count' => 'integer',
            'occurred_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CrawlSource::class, 'crawl_source_id');
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(CrawlRun::class, 'crawl_run_id');
    }
}
