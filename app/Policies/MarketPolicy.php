<?php

namespace App\Policies;

class MarketPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'market';
    }
}
