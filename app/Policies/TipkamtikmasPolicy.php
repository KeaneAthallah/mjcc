<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Tipkamtikmas;

class TipkamtikmasPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'tipkamtikmas';
    }
}
