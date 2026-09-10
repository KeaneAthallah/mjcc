<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisasterRiskIndex extends Model
{
    use HasFactory;

    protected $fillable = [
        'region_name',
        'region_code',
        'hazard_type',
        'risk_index',
        'risk_level',
        'vulnerability_index',
        'exposure_index',
        'capacity_index',
        'year',
        'latitude',
        'longitude',
        'dedupe_key',
        'raw_data',
        'scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'risk_index' => 'decimal:4',
            'vulnerability_index' => 'decimal:4',
            'exposure_index' => 'decimal:4',
            'capacity_index' => 'decimal:4',
            'year' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'raw_data' => 'array',
            'scraped_at' => 'datetime',
        ];
    }

    public function riskLevelColor(): string
    {
        return match ($this->risk_level) {
            'Tinggi' => 'red',
            'Sedang' => 'amber',
            'Rendah' => 'green',
            default => 'gray',
        };
    }

    public function scopeForHazard($query, string $hazard)
    {
        return $query->where('hazard_type', $hazard);
    }

    public function scopeForRegion($query, string $region)
    {
        return $query->where('region_name', $region);
    }
}
