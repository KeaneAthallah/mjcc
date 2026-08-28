<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    public const ACTION_RESTORE = 'restore';

    public const ACTION_FORCE_DELETE = 'force_delete';

    public const ACTION_ROLE_CHANGE = 'role_change';

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function description(): string
    {
        $label = $this->resource_type ? ucfirst(str_replace('_', ' ', $this->resource_type)) : 'Sistem';

        return match ($this->action) {
            self::ACTION_LOGIN => 'Masuk ke sistem',
            self::ACTION_LOGOUT => 'Keluar dari sistem',
            self::ACTION_CREATE => "Menambah {$label}",
            self::ACTION_UPDATE => "Mengubah {$label}",
            self::ACTION_DELETE => "Menghapus {$label}",
            self::ACTION_RESTORE => "Memulihkan {$label}",
            self::ACTION_FORCE_DELETE => "Menghapus permanen {$label}",
            self::ACTION_ROLE_CHANGE => 'Mengubah peran pengguna',
            default => $this->action,
        };
    }
}
