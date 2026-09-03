<?php

namespace App\Models;

use App\Support\TargetRegionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlRecord extends Model
{
    use HasFactory;

    public const TYPE_ATS = 'ats';

    public const TYPE_SCHOOL = 'sekolah';

    public const TYPE_PASAR = 'pasar';

    public const TYPE_INDICATOR = 'statistik';

    protected $fillable = [
        'crawl_source_id',
        'crawl_run_id',
        'external_id',
        'record_type',
        'name',
        'province_code',
        'kabupaten_code',
        'kabupaten_name',
        'kecamatan_code',
        'kecamatan_name',
        'desa_code',
        'desa_name',
        'latitude',
        'longitude',
        'data',
        'source_url',
        'source_updated_at',
        'first_seen_at',
        'last_seen_at',
        'content_hash',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'data' => 'array',
            'source_updated_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
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

    /**
     * Only records that belong to one of the target regions.
     */
    public function scopeTargetRegion(Builder $query): Builder
    {
        $codes = (new TargetRegionService)->getCodes();

        return $query->where(function (Builder $q) use ($codes) {
            $q->whereIn('kabupaten_code', $codes)
                ->orWhereIn('province_code', $codes);
        });
    }

    /**
     * Only records that have valid, displayable coordinates.
     */
    public function scopeHasCoordinates(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [-90, 90])
            ->whereBetween('longitude', [-180, 180]);
    }
}
