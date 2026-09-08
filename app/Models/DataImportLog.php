<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One append-only import run per sector produced by `data:sync`. Each row
 * records what a single pass turned the already-scraped `external_data` into
 * within the master tables (kecamatan, schools, health facilities).
 */
class DataImportLog extends Model
{
    use HasFactory;

    public const STATUS_RUNNING = 'berjalan';

    public const STATUS_SUCCESS = 'berhasil';

    public const STATUS_FAILED = 'gagal';

    public const SECTORS = ['pendidikan', 'kesehatan', 'keamanan'];

    protected $fillable = [
        'sector',
        'status',
        'started_at',
        'finished_at',
        'datasets_scanned',
        'entity_datasets',
        'entities_created',
        'entities_updated',
        'entities_skipped',
        'entities_failed',
        'kecamatan_created',
        'kecamatan_updated',
        'locations_resolved',
        'error_summary',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'datasets_scanned' => 'integer',
            'entity_datasets' => 'integer',
            'entities_created' => 'integer',
            'entities_updated' => 'integer',
            'entities_skipped' => 'integer',
            'entities_failed' => 'integer',
            'kecamatan_created' => 'integer',
            'kecamatan_updated' => 'integer',
            'locations_resolved' => 'integer',
            'summary' => 'array',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_RUNNING => 'Berjalan',
            self::STATUS_SUCCESS => 'Berhasil',
            self::STATUS_FAILED => 'Gagal',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
