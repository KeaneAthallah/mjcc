<?php

namespace App\Policies;

use App\Models\CrawlSource;
use App\Models\User;
use App\Support\Access;

class CrawlSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CrawlSource $source): bool
    {
        return true;
    }

    /**
     * Triggering an on-demand crawl is an admin-only operation.
     */
    public function run(User $user, CrawlSource $source): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return Access::canWrite($user, 'crawler');
    }

    public function update(User $user, CrawlSource $source): bool
    {
        return Access::canWrite($user, 'crawler');
    }

    public function delete(User $user, CrawlSource $source): bool
    {
        return Access::canWrite($user, 'crawler');
    }
}
