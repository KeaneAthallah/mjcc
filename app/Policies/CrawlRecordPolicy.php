<?php

namespace App\Policies;

use App\Models\CrawlRecord;
use App\Models\User;
use App\Support\Access;

class CrawlRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CrawlRecord $record): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CrawlRecord $record): bool
    {
        return Access::canWrite($user, 'crawler');
    }

    public function delete(User $user, CrawlRecord $record): bool
    {
        return Access::canWrite($user, 'crawler');
    }
}
