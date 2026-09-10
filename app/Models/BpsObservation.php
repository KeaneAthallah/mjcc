<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpsObservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bps_dataset_id',
        'indicator',
        'region_name',
        'region_code',
        'year',
        'period',
        'value',
        'unit',
        'dedupe_key',
        'raw_data',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'value' => 'decimal:4',
            'raw_data' => 'array',
            'fetched_at' => 'datetime',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(BpsDataset::class, 'bps_dataset_id');
    }

    public static function dedupeKey(int $datasetId, string $indicator, string $region, int $year, ?string $period): string
    {
        return hash('sha256', implode("\n", [
            (string) $datasetId,
            $indicator,
            $region,
            (string) $year,
            $period ?? '',
        ]));
    }
}
