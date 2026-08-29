<?php

namespace App\Policies;

class SchoolPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'school';
    }
}
