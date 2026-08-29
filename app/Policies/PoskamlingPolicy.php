<?php

namespace App\Policies;

class PoskamlingPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'poskamling';
    }
}
