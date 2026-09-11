<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPE_SD = 'SD';

    public const TYPE_SMP = 'SMP';

    public const TYPE_SMA = 'SMA';

    public const TYPE_SMK = 'SMK';

    public const TYPE_MI = 'MI';

    public const TYPE_MTS = 'MTS';

    public const TYPE_MA = 'MA';

    public const SCHOOL_TYPES = [
        self::TYPE_SD,
        self::TYPE_SMP,
        self::TYPE_SMA,
        self::TYPE_SMK,
        self::TYPE_MI,
        self::TYPE_MTS,
        self::TYPE_MA,
    ];

    protected $fillable = [
        'kecamatan_id',
        'kelurahan_id',
        'name',
        'school_type',
        'npsn',
        'address',
        'latitude',
        'longitude',
        'condition',
        'students_male',
        'students_female',
        'teachers',
        'classes',
        'capacity',
        'library_percentage',
        'science_lab_percentage',
        'computer_lab_percentage',
        'teacher_room_percentage',
        'toilet_percentage',
        'worship_room_percentage',
        'is_active',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'students_male' => 'integer',
            'students_female' => 'integer',
            'teachers' => 'integer',
            'classes' => 'integer',
            'capacity' => 'integer',
            'library_percentage' => 'decimal:2',
            'science_lab_percentage' => 'decimal:2',
            'computer_lab_percentage' => 'decimal:2',
            'teacher_room_percentage' => 'decimal:2',
            'toilet_percentage' => 'decimal:2',
            'worship_room_percentage' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(Kelurahan::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }

    public function getTotalStudents(): int
    {
        return $this->students_male + $this->students_female;
    }
}
