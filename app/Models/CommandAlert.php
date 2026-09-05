<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Alert operasional terdeteksi yang dipersistenkan untuk ditindaklanjuti.
 *
 * Mewakili rangkaian kerja: BARU -> DITINJAU -> DITANGANI -> SELESAI.
 * Dibuat oleh CommandAlertSyncService dari hasil deteksi AlertService.
 */
class CommandAlert extends Model
{
    use HasFactory;

    public const STATUS_BARU = 'baru';

    public const STATUS_DITINJAU = 'ditinjau';

    public const STATUS_DITANGANI = 'ditangani';

    public const STATUS_SELESAI = 'selesai';

    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_INFO = 'info';

    protected $fillable = [
        'rule',
        'severity',
        'sector_key',
        'sector',
        'title',
        'description',
        'resource_type',
        'resource_slug',
        'resource_id',
        'kecamatan_id',
        'latitude',
        'longitude',
        'detail_route',
        'detail_params',
        'status',
        'opened_at',
        'last_seen_at',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'detail_params' => 'array',
            'resource_id' => 'integer',
            'resolved_by' => 'integer',
            'opened_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Status yang masih perlu dipantau/ditindaklanjuti di halaman aktif.
     *
     * @return string[]
     */
    public static function openStatuses(): array
    {
        return [self::STATUS_BARU, self::STATUS_DITINJAU, self::STATUS_DITANGANI];
    }

    /**
     * @return string[]
     */
    public static function statuses(): array
    {
        return [self::STATUS_BARU, self::STATUS_DITINJAU, self::STATUS_DITANGANI, self::STATUS_SELESAI];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::openStatuses(), true);
    }

    public function resource(): MorphTo
    {
        return $this->morphTo();
    }

    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function url(): ?string
    {
        if (! $this->detail_route || ! is_array($this->detail_params)) {
            return null;
        }

        try {
            return route($this->detail_route, array_values($this->detail_params));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Tautan ke peta yang memfokuskan marker resource terkait.
     */
    public function mapUrl(): ?string
    {
        if (! $this->resource_slug || ! $this->resource_id) {
            return null;
        }

        return route('maps.index', ['focus' => $this->resource_slug.':'.$this->resource_id]);
    }

    public function severityLabel(): string
    {
        return (string) (config("command-center.alerts.severities.{$this->severity}", strtoupper($this->severity)));
    }

    public function statusLabel(): string
    {
        return (string) (config("command-center.alerts.statuses.{$this->status}", strtoupper($this->status)));
    }
}
