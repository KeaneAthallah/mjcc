<?php

namespace App\Support;

use App\Models\User;

/**
 * Centralizes the role-based authorization matrix for the application.
 *
 * - admin  : full access to all data, including user management.
 * - operator: create/edit/delete operational data, but cannot manage users.
 * - viewer : read-only.
 */
final class Access
{
    /**
     * Master data that only admin may mutate.
     */
    public const ADMIN_ONLY = ['kecamatan', 'crawler'];

    /**
     * Whether the user can perform a write (create/update/delete) operation
     * on the given resource key.
     */
    public static function canWrite(User $user, string $resource): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isViewer()) {
            return false;
        }

        if (in_array($resource, self::ADMIN_ONLY, true)) {
            return false;
        }

        return true;
    }
}
