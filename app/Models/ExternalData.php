<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One normalized numeric datapoint extracted from a public statistics portal
 * (Satu Data Morowali). Stored long-format so each table row on the source
 * becomes `sector / source / dataset / year / location / indicator / value`.
 */
class ExternalData extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector',
        'source',
        'source_url',
        'dataset',
        'topic',
        'year',
        'location',
        'latitude',
        'longitude',
        'indicator',
        'value',
        'unit',
        'dedupe_key',
        'raw_data',
        'scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'value' => 'decimal:4',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'raw_data' => 'array',
            'scraped_at' => 'datetime',
        ];
    }

    /**
     * Deterministic unique key so re-scraping updates instead of duplicating.
     */
    public static function dedupeKey(string $sector, string $source, string $dataset, ?int $year, string $location, string $indicator): string
    {
        return hash('sha256', implode("\0", [$sector, $source, $dataset, (string) $year, $location, $indicator]));
    }
}
