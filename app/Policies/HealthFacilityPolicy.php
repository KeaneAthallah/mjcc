<?php

namespace App\Policies;

class HealthFacilityPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'health_facility';
    }
}
