<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kelurahan extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'kecamatan_id',
        'name',
        'code',
        'latitude',
        'longitude',
        'population',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'population' => 'integer',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    public function poskamlings(): HasMany
    {
        return $this->hasMany(Poskamling::class);
    }

    public function tipkamtikmas(): HasMany
    {
        return $this->hasMany(Tipkamtikmas::class);
    }
}
