<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApbdRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'indicator',
        'category',
        'target_value',
        'realization_value',
        'percentage',
        'previous_value',
        'unit',
        'region',
        'source_indicator_code',
        'dedupe_key',
        'raw_data',
        'scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'target_value' => 'decimal:2',
            'realization_value' => 'decimal:2',
            'percentage' => 'decimal:2',
            'previous_value' => 'decimal:2',
            'raw_data' => 'array',
            'scraped_at' => 'datetime',
        ];
    }

    public static function dedupeKey(int $year, string $indicator, string $region): string
    {
        return hash('sha256', implode("\n", [(string) $year, $indicator, $region]));
    }

    public function formattedPercentage(): string
    {
        return number_format($this->percentage ?? 0, 1, ',', '.').'%';
    }

    public function realizationStatus(): string
    {
        $pct = $this->percentage ?? 0;

        if ($pct >= 100) {
            return 'Terealisasi';
        }

        if ($pct >= 80) {
            return 'Hampir Terealisasi';
        }

        if ($pct >= 50) {
            return 'Sebagian';
        }

        return 'Rendah';
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
