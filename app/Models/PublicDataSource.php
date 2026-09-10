<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Registry of all public data sources in the command center.
 * Each source has an adapter class, sync status, and configuration.
 */
class PublicDataSource extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'belum';

    const STATUS_SUCCESS = 'berhasil';

    const STATUS_FAILED = 'gagal';

    const STATUS_UNAVAILABLE = 'tidak_tersedia';

    const CATEGORIES = [
        'pendidikan' => 'Pendidikan',
        'pemantauan' => 'Pemantauan',
        'kebencanaan' => 'Kebencanaan',
        'apbd' => 'APBD',
    ];

    protected $fillable = [
        'key',
        'name',
        'category',
        'description',
        'source_url',
        'adapter_class',
        'enabled',
        'refresh_frequency',
        'supports_map',
        'supports_chart',
        'supports_table',
        'status',
        'last_sync_at',
        'last_success_at',
        'last_error',
        'record_count',
        'sync_duration_ms',
        'data_period',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'supports_map' => 'boolean',
            'supports_chart' => 'boolean',
            'supports_table' => 'boolean',
            'last_sync_at' => 'datetime',
            'last_success_at' => 'datetime',
            'record_count' => 'integer',
            'sync_duration_ms' => 'integer',
            'config' => 'array',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING => 'Belum pernah diperbarui',
            self::STATUS_SUCCESS => 'Aktif',
            self::STATUS_FAILED => 'Gagal diperbarui',
            self::STATUS_UNAVAILABLE => 'Data tidak tersedia',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function isSynced(): bool
    {
        return $this->status === self::STATUS_SUCCESS && $this->last_success_at !== null;
    }

    public function freshnessLabel(): ?string
    {
        if (! $this->last_success_at) {
            return null;
        }

        $minutes = $this->last_success_at->diffInMinutes(now());

        if ($minutes < 60) {
            return $minutes.' menit yang lalu';
        }

        $hours = floor($minutes / 60);

        if ($hours < 24) {
            return $hours.' jam yang lalu';
        }

        $days = floor($hours / 24);

        return $days.' hari yang lalu';
    }
}
