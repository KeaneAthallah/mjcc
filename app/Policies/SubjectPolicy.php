<?php

namespace App\Policies;

class SubjectPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'subject';
    }
}
