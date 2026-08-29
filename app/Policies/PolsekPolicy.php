<?php

namespace App\Policies;

class PolsekPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'polsek';
    }
}
