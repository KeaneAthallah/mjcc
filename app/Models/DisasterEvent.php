<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisasterEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'disaster_type',
        'disaster_name',
        'event_date',
        'province',
        'district',
        'sub_district',
        'village',
        'affected_area',
        'impact',
        'affected_population',
        'infrastructure_impact',
        'status',
        'latitude',
        'longitude',
        'source_name',
        'source_url',
        'dedupe_key',
        'raw_data',
        'scraped_at',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'affected_population' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'raw_data' => 'array',
            'scraped_at' => 'datetime',
        ];
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('event_date', '>=', now()->subDays($days));
    }

    public function scopeForDistrict($query, string $district)
    {
        return $query->where('district', $district);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('disaster_type', $type);
    }

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
