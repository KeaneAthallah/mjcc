<?php

namespace App\Models;

use Database\Factories\SosAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosAlert extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESPONDING = 'responding';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CANCELLED = 'cancelled';

    /** @use HasFactory<SosAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'status',
        'message',
        'responded_by',
        'response_message',
        'responded_at',
        'resolved_by',
        'resolved_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Statuses where the request is still awaiting a response.
     *
     * @return string[]
     */
    public static function openStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_RESPONDING,
        ];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::openStatuses(), true);
    }

    /**
     * Whether the alert belongs to the given user.
     */
    public function ownedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
