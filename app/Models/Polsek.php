<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Polsek extends Model
{
    use HasFactory;

    protected $fillable = [
        'kecamatan_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'personnel_count',
        'poskamling_count',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'personnel_count' => 'integer',
            'poskamling_count' => 'integer',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
