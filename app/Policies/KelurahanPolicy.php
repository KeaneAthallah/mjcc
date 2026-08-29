<?php

namespace App\Policies;

class KelurahanPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'kelurahan';
    }
}
