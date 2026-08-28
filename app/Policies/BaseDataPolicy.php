<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Access;

abstract class BaseDataPolicy
{
    abstract protected function resourceKey(): string;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, object $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return Access::canWrite($user, $this->resourceKey());
    }

    public function update(User $user, object $model): bool
    {
        return Access::canWrite($user, $this->resourceKey());
    }

    public function delete(User $user, object $model): bool
    {
        return Access::canWrite($user, $this->resourceKey());
    }

    public function restore(User $user, object $model): bool
    {
        return Access::canWrite($user, $this->resourceKey());
    }

    public function forceDelete(User $user, object $model): bool
    {
        return $user->isAdmin();
    }
}
