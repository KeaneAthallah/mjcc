<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlSource extends Model
{
    use HasFactory;

    public const SOURCE_ATS = 'ats';

    public const SOURCE_DAPO = 'dapo';

    public const SOURCE_SP2KP = 'sp2kp';

    public const SOURCE_BPS = 'bps';

    public const SOURCE_KESEHATAN = 'kesehatan';

    const SOURCES = [
        self::SOURCE_ATS,
        self::SOURCE_DAPO,
        self::SOURCE_SP2KP,
        self::SOURCE_BPS,
        self::SOURCE_KESEHATAN,
    ];

    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'source_type',
        'is_active',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'configuration' => 'array',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(CrawlRun::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(CrawlRecord::class);
    }

    public function errors(): HasMany
    {
        return $this->hasMany(CrawlError::class);
    }
}
