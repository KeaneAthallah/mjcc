<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HealthFacility extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPE_PUSKESMAS = 'Puskesmas';

    public const TYPE_PUSTU = 'Pustu';

    public const TYPE_RS = 'Rumah Sakit';

    public const TYPE_POSYANDU = 'Posyandu';

    protected $fillable = [
        'kecamatan_id',
        'name',
        'facility_type',
        'address',
        'latitude',
        'longitude',
        'condition',
        'beds',
        'doctors',
        'nurses',
        'midwives',
        'status',
        'phone',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'beds' => 'integer',
            'doctors' => 'integer',
            'nurses' => 'integer',
            'midwives' => 'integer',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
