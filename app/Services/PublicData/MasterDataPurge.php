<?php

namespace App\Services\PublicData;

use App\Models\HealthFacility;
use App\Models\Market;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Tipkamtikmas;

/**
 * Permanently deletes the seeded placeholder master rows.
 *
 * Rows tagged source 'seed' (health facilities, markets, poskamlings,
 * tipkamtikmas) and the remaining placeholder schools kept inactive with a
 * null source are force-deleted. Rows produced by real pipelines (dapodik,
 * kemkes, sp2kp), manual rows (polsek source 'manual') and CRUD-created rows
 * (source null but active) are never touched.
 */
class MasterDataPurge
{
    /**
     * @return array<string, int>
     */
    public function purge(): array
    {
        return [
            'health_facilities' => HealthFacility::where('source', 'seed')->forceDelete(),
            'markets' => Market::where('source', 'seed')->forceDelete(),
            'poskamlings' => Poskamling::where('source', 'seed')->forceDelete(),
            'tipkamtikmas' => Tipkamtikmas::where('source', 'seed')->forceDelete(),
            'schools' => School::whereNull('source')->where('is_active', false)->forceDelete(),
        ];
    }
}
