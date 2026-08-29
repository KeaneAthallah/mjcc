<?php

namespace App\Policies;

class KecamatanPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'kecamatan';
    }
}
