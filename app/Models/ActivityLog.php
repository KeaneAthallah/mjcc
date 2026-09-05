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

    public const ACTION_REGISTER = 'register';

    public const ACTION_EMAIL_VERIFICATION = 'email_verification';

    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    public const ACTION_RESTORE = 'restore';

    public const ACTION_FORCE_DELETE = 'force_delete';

    public const ACTION_ROLE_CHANGE = 'role_change';

    public const ACTION_SOS_CREATED = 'sos_created';

    public const ACTION_SOS_ACKNOWLEDGED = 'sos_acknowledged';

    public const ACTION_SOS_RESPONDING = 'sos_responding';

    public const ACTION_SOS_RESOLVED = 'sos_resolved';

    public const ACTION_SOS_CANCELLED = 'sos_cancelled';

    public const ACTION_ALERT_STATUS = 'alert_status';

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
            self::ACTION_REGISTER => 'Mendaftarkan akun baru',
            self::ACTION_EMAIL_VERIFICATION => 'Memverifikasi email',
            self::ACTION_CREATE => "Menambah {$label}",
            self::ACTION_UPDATE => "Mengubah {$label}",
            self::ACTION_DELETE => "Menghapus {$label}",
            self::ACTION_RESTORE => "Memulihkan {$label}",
            self::ACTION_FORCE_DELETE => "Menghapus permanen {$label}",
            self::ACTION_ROLE_CHANGE => 'Mengubah peran pengguna',
            self::ACTION_SOS_CREATED => 'Mengirim SOS darurat',
            self::ACTION_SOS_ACKNOWLEDGED => 'Menerima SOS darurat',
            self::ACTION_SOS_RESPONDING => 'Menuju lokasi SOS',
            self::ACTION_SOS_RESOLVED => 'Menyelesaikan SOS darurat',
            self::ACTION_SOS_CANCELLED => 'Membatalkan SOS darurat',
            self::ACTION_ALERT_STATUS => 'Memperbarui status alert',
            default => $this->action,
        };
    }
}
