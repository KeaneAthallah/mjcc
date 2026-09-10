<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BpsDataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'name',
        'description',
        'subject',
        'period_type',
        'enabled',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function observations(): HasMany
    {
        return $this->hasMany(BpsObservation::class);
    }

    public function latestObservation()
    {
        return $this->hasOne(BpsObservation::class)->latestOfMany();
    }
}
