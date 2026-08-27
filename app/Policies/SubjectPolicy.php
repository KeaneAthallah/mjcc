<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Subject;

class SubjectPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'subject';
    }
}
