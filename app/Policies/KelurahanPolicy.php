<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Kelurahan;

class KelurahanPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'kelurahan';
    }
}
