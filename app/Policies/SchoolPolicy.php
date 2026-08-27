<?php

namespace App\Policies;

use App\Models\User;
use App\Models\School;

class SchoolPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'school';
    }
}
