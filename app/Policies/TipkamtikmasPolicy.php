<?php

namespace App\Policies;

class TipkamtikmasPolicy extends BaseDataPolicy
{
    protected function resourceKey(): string
    {
        return 'tipkamtikmas';
    }
}
