<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\EmailVerificationService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'responder_type'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_OPERATOR = 'operator';

    public const ROLE_VIEWER = 'viewer';

    public const RESPONDER_MEDICAL = 'medical';

    public const RESPONDER_FIRE = 'fire';

    public const RESPONDER_POLICE = 'police';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, LogsActivity, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    public function isViewer(): bool
    {
        return $this->role === self::ROLE_VIEWER;
    }

    public function canManageData(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_OPERATOR], true);
    }

    public function canManageUsers(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isResponder(): bool
    {
        return $this->responder_type !== null;
    }

    /**
     * Indonesian label for the responder type.
     */
    public function getResponderTypeLabelAttribute(): ?string
    {
        return match ($this->responder_type) {
            self::RESPONDER_MEDICAL => 'Medis',
            self::RESPONDER_FIRE => 'Pemadam Kebakaran',
            self::RESPONDER_POLICE => 'Polisi',
            default => null,
        };
    }

    /**
     * Whether this responder can handle the given SOS category.
     */
    public function responderForCategory(string $category): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->responder_type === $category;
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill(['email_verified_at' => $this->freshTimestamp()])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationService::class)->send($this);
    }
}
