<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Polsek;

class PolsekPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'polsek';
    }
}
