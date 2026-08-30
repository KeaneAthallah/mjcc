<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single-use, time-limited email verification code.
 *
 * Only a hash of the code is persisted; the raw code is never stored, logged
 * or returned by the API.
 */
class EmailVerificationCode extends Model
{
    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'verified_at',
        'attempts',
        'is_active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
