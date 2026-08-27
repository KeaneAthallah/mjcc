<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Poskamling;

class PoskamlingPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'poskamling';
    }
}
