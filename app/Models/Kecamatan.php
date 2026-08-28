<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kecamatan extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'latitude',
        'longitude',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function kelurahans(): HasMany
    {
        return $this->hasMany(Kelurahan::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function polseks(): HasMany
    {
        return $this->hasMany(Polsek::class);
    }

    public function tipkamtikmas(): HasMany
    {
        return $this->hasMany(Tipkamtikmas::class);
    }

    public function poskamlings(): HasMany
    {
        return $this->hasMany(Poskamling::class);
    }

    public function markets(): HasMany
    {
        return $this->hasMany(Market::class);
    }

    public function healthFacilities(): HasMany
    {
        return $this->hasMany(HealthFacility::class);
    }
}
