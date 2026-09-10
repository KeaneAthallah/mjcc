<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommodityPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'commodity',
        'category',
        'current_price',
        'previous_price',
        'price_change',
        'percentage_change',
        'market',
        'region',
        'record_date',
        'availability',
        'unit',
        'dedupe_key',
        'raw_data',
        'scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
            'previous_price' => 'decimal:2',
            'price_change' => 'decimal:2',
            'percentage_change' => 'decimal:2',
            'record_date' => 'date',
            'raw_data' => 'array',
            'scraped_at' => 'datetime',
        ];
    }

    public static function dedupeKey(string $commodity, string $market, string $date): string
    {
        return hash('sha256', implode("\n", [$commodity, $market, $date]));
    }

    public function priceTrend(): string
    {
        if ($this->percentage_change === null || $this->percentage_change == 0) {
            return 'Stabil';
        }

        return $this->percentage_change > 0 ? 'Naik' : 'Turun';
    }

    public function formattedPercentageChange(): string
    {
        if ($this->percentage_change === null || $this->percentage_change == 0) {
            return 'Stabil';
        }

        $prefix = $this->percentage_change > 0 ? '+' : '';

        return $prefix.number_format($this->percentage_change, 1, ',', '.').'%';
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('record_date');
    }

    public function scopeForCommodity($query, string $commodity)
    {
        return $query->where('commodity', $commodity);
    }
}
