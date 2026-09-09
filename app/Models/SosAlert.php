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

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_ON_THE_WAY = 'on_the_way';

    public const STATUS_ARRIVED = 'arrived';

    public const CATEGORY_GENERAL = 'general';

    public const CATEGORY_MEDICAL = 'medical';

    public const CATEGORY_FIRE = 'fire';

    public const CATEGORY_POLICE = 'police';

    /** @use HasFactory<SosAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'latitude',
        'longitude',
        'accuracy',
        'status',
        'category',
        'message',
        'responded_by',
        'response_message',
        'responded_at',
        'resolved_by',
        'resolved_at',
        'accepted_by',
        'accepted_at',
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

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
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
            self::STATUS_ACCEPTED,
            self::STATUS_ON_THE_WAY,
            self::STATUS_ARRIVED,
        ];
    }

    /**
     * All statuses that are not terminal (resolved/cancelled).
     *
     * @return string[]
     */
    public static function isOpenStatuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_RESPONDING,
            self::STATUS_ACCEPTED,
            self::STATUS_ON_THE_WAY,
            self::STATUS_ARRIVED,
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

    /**
     * Indonesian label for the category.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_GENERAL => 'Umum',
            self::CATEGORY_MEDICAL => 'Medis',
            self::CATEGORY_FIRE => 'Pemadam Kebakaran',
            self::CATEGORY_POLICE => 'Polisi',
            default => 'Umum',
        };
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
