<?php

namespace App\Policies;

use App\Models\SosAlert;
use App\Models\User;

class SosAlertPolicy
{
    /**
     * Only operators and admins can browse the full SOS inbox / history.
     * A viewer may still list their own alerts (handled in the controller).
     */
    public function viewAny(User $user): bool
    {
        return $user->canManageData();
    }

    /**
     * Operators/admins may open any alert; every user may open their own.
     */
    public function view(User $user, SosAlert $sos): bool
    {
        return $user->canManageData() || $sos->ownedBy($user);
    }

    /**
     * Any authenticated user may raise an SOS — it is not role-restricted.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Acknowledge / mark responding / resolve is reserved for operators and
     * admins. Viewers can never manage other people's SOS.
     */
    public function manage(User $user, SosAlert $sos): bool
    {
        return $user->canManageData();
    }

    /**
     * Operators/admins may cancel any open alert; the owner may cancel their
     * own open alert (before it is resolved).
     */
    public function cancel(User $user, SosAlert $sos): bool
    {
        if (! $sos->isOpen()) {
            return false;
        }

        return $user->canManageData() || $sos->ownedBy($user);
    }
}
