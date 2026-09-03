<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class CrawlRun extends Model
{
    use HasFactory;

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'crawl_source_id',
        'started_at',
        'finished_at',
        'status',
        'records_found',
        'records_created',
        'records_updated',
        'records_unchanged',
        'records_failed',
        'error_count',
        'duration',
        'log',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'records_found' => 'integer',
            'records_created' => 'integer',
            'records_updated' => 'integer',
            'records_unchanged' => 'integer',
            'records_failed' => 'integer',
            'error_count' => 'integer',
            'duration' => 'integer',
            'log' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(CrawlSource::class, 'crawl_source_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(CrawlRecord::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(CrawlError::class);
    }

    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_SUCCESS, self::STATUS_PARTIAL, self::STATUS_FAILED], true);
    }

    public function humanDuration(): ?string
    {
        if ($this->duration === null) {
            return null;
        }

        $seconds = (int) round($this->duration / 1000);

        return Carbon::createFromTimestampUTC($seconds)->format('H:i:s');
    }
}
