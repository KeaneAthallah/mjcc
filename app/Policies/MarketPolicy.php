<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Market;

class MarketPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'market';
    }
}
