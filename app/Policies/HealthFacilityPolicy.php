<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HealthFacility;

class HealthFacilityPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'health_facility';
    }
}
