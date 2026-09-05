<?php

namespace App\Policies;

use App\Models\CommandAlert;
use App\Models\User;

class CommandAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CommandAlert $alert): bool
    {
        return true;
    }

    /**
     * Mengubah status alert hanya untuk operator/admin (bukan viewer).
     */
    public function update(User $user, CommandAlert $alert): bool
    {
        return $user->canManageData();
    }

    /**
     * Menghapus/menutup alert hanya untuk admin.
     */
    public function delete(User $user, CommandAlert $alert): bool
    {
        return $user->isAdmin();
    }
}
