<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tracks the latest scrape attempt per industry sector so the UI can report
 * status and last-success timestamps and so admins can trust that the data
 * shown is the latest successful scrape (failing rescrapes never wipe it).
 */
class PublicDataSync extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'belum';

    public const STATUS_SUCCESS = 'berhasil';

    public const STATUS_FAILED = 'gagal';

    public const SECTORS = ['pendidikan', 'kesehatan', 'keamanan'];

    protected $fillable = [
        'sector',
        'status',
        'last_attempt_at',
        'last_success_at',
        'last_error',
        'record_count',
    ];

    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
            'last_success_at' => 'datetime',
            'record_count' => 'integer',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Belum disinkronkan',
            self::STATUS_SUCCESS => 'Berhasil',
            self::STATUS_FAILED => 'Gagal',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }
}
