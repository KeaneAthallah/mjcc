<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Kecamatan;

class KecamatanPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'kecamatan';
    }
}
