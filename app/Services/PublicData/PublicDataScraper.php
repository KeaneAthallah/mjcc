<?php

namespace App\Services\PublicData;

use App\Events\PublicDataSyncCompleted;
use App\Models\PublicDataSync;
use RuntimeException;
use Throwable;

/**
 * Orchestrates scraping across one or all configured sectors and records the
 * outcome in `public_data_syncs`. A failed re-scrape never deletes already
 * fetched data: `last_success_at` and `record_count` are only touched after a
 * successful run, while `last_error` carries the reason of the latest failure.
 */
class PublicDataScraper
{
    public function __construct(private readonly SatuDataScraper $scraper) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function scrape(?string $sector = null, int $limit = 0, bool $discoverOnly = false): array
    {
        $sectors = $this->targetSectors($sector);

        $results = [];

        foreach ($sectors as $target) {
            if (! $discoverOnly) {
                $sync = PublicDataSync::firstOrCreate(
                    ['sector' => $target],
                    ['status' => PublicDataSync::STATUS_PENDING, 'record_count' => 0],
                );

                $sync->update(['last_attempt_at' => now()]);
            }

            try {
                $result = $this->scraper->scrape($target, $limit, $discoverOnly);

                if ($discoverOnly) {
                    $results[] = $result;

                    continue;
                }

                $sync->update([
                    'status' => PublicDataSync::STATUS_SUCCESS,
                    'last_success_at' => now(),
                    'record_count' => (int) $result['records'],
                    'last_error' => $result['failures'] === []
                        ? null
                        : count($result['failures']).' dari '.count($result['matched']).' dataset gagal disinkronkan.',
                ]);

                $result['status'] = PublicDataSync::STATUS_SUCCESS;

                PublicDataSyncCompleted::dispatch($target, $result['label'], $result);

                $results[] = $result;
            } catch (Throwable $e) {
                if ($discoverOnly) {
                    throw $e;
                }

                $sync->update([
                    'status' => PublicDataSync::STATUS_FAILED,
                    'last_error' => $e->getMessage(),
                ]);

                $result = [
                    'sector' => $target,
                    'label' => (string) config("public_data.sectors.{$target}.label", $target),
                    'pages' => 0,
                    'discovered' => 0,
                    'matched' => [],
                    'datasets' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'records' => 0,
                    'failures' => [['url' => '', 'message' => $e->getMessage()]],
                    'status' => PublicDataSync::STATUS_FAILED,
                    'error' => $e->getMessage(),
                ];

                PublicDataSyncCompleted::dispatch($target, $result['label'], $result);

                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * @return array<int, string>
     */
    private function targetSectors(?string $sector): array
    {
        if ($sector !== null) {
            if (! array_key_exists($sector, config('public_data.sectors', []))) {
                throw new RuntimeException("Sektor '{$sector}' tidak dikonfigurasi.");
            }

            return [$sector];
        }

        return array_keys(config('public_data.sectors', []));
    }
}
