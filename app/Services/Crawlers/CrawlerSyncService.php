<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlError;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Support\TargetRegionService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Persists normalized RawRecord payloads into the crawl_records table,
 * enforcing the NEW / UNCHANGED / CHANGED / FAILED semantics. Records are keyed
 * by (crawl_source_id, external_id). Failed records do not overwrite the last
 * successful payload, and vanishing upstream records are never deleted.
 */
class CrawlerSyncService
{
    public function __construct(private readonly TargetRegionService $targetRegions) {}

    /**
     * @param  RawRecord[]  $records
     * @return array{created:int,updated:int,unchanged:int,failed:int,records:array<int,CrawlRecord>}
     */
    public function sync(CrawlSource $source, CrawlRun $run, array $records): array
    {
        $counts = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'failed' => 0,
            'records' => [],
        ];

        DB::transaction(function () use ($source, $run, $records, &$counts) {
            $now = now();

            foreach ($records as $raw) {
                if (! $raw instanceof RawRecord) {
                    $this->recordError($source, $run, 'invalid record payload', null, null);
                    $counts['failed']++;

                    continue;
                }

                if (! $this->isInScope($source, $raw)) {
                    // Out-of-scope region: count as found but never persisted.
                    $counts['failed']++;

                    continue;
                }

                $hash = $this->hash($raw);

                /** @var CrawlRecord|null $existing */
                $existing = CrawlRecord::query()
                    ->where('crawl_source_id', $source->id)
                    ->where('external_id', $raw->externalId)
                    ->first();

                if ($existing === null) {
                    $record = CrawlRecord::create([
                        'crawl_source_id' => $source->id,
                        'crawl_run_id' => $run->id,
                        'external_id' => $raw->externalId,
                        'record_type' => $raw->recordType,
                        'name' => $raw->name,
                        'province_code' => $raw->provinceCode,
                        'kabupaten_code' => $raw->kabupatenCode,
                        'kabupaten_name' => $raw->kabupatenName,
                        'kecamatan_code' => $raw->kecamatanCode,
                        'kecamatan_name' => $raw->kecamatanName,
                        'desa_code' => $raw->desaCode,
                        'desa_name' => $raw->desaName,
                        'latitude' => $raw->latitude,
                        'longitude' => $raw->longitude,
                        'data' => $raw->data,
                        'source_url' => $raw->sourceUrl,
                        'source_updated_at' => $raw->sourceUpdatedAt,
                        'first_seen_at' => $now,
                        'last_seen_at' => $now,
                        'content_hash' => $hash,
                    ]);
                    $counts['created']++;
                    $counts['records'][] = $record;

                    continue;
                }

                // Refresh the "last seen" timestamp regardless of changes.
                $existing->last_seen_at = $now;
                $existing->crawl_run_id = $run->id;

                if ($existing->content_hash === $hash) {
                    $existing->save();
                    $counts['unchanged']++;

                    continue;
                }

                $existing->fill([
                    'record_type' => $raw->recordType,
                    'name' => $raw->name,
                    'province_code' => $raw->provinceCode,
                    'kabupaten_code' => $raw->kabupatenCode,
                    'kabupaten_name' => $raw->kabupatenName,
                    'kecamatan_code' => $raw->kecamatanCode,
                    'kecamatan_name' => $raw->kecamatanName,
                    'desa_code' => $raw->desaCode,
                    'desa_name' => $raw->desaName,
                    'latitude' => $raw->latitude,
                    'longitude' => $raw->longitude,
                    'data' => $raw->data,
                    'source_url' => $raw->sourceUrl,
                    'source_updated_at' => $raw->sourceUpdatedAt,
                    'content_hash' => $hash,
                ])->save();
                $counts['updated']++;
                $counts['records'][] = $existing;
            }
        });

        return $counts;
    }

    /**
     * Records a per-source error frame and bumps the run's error counter.
     */
    public function recordError(CrawlSource $source, CrawlRun $run, string $message, ?int $httpStatus = null, ?string $url = null): void
    {
        CrawlError::create([
            'crawl_source_id' => $source->id,
            'crawl_run_id' => $run->id,
            'http_status' => $httpStatus,
            'message' => $message,
            'url' => $url,
            'occurred_at' => now(),
        ]);

        $run->increment('error_count');
    }

    /**
     * Whether a normalized record may be persisted for this source. Every
     * source is restricted to the two target kabupaten (Morowali / Morowali
     * Utara); the price source additionally accepts province-scope records
     * pinned to the target province (72) because PIHPS only exposes Sulawesi
     * Tengah aggregate prices. Records for any other region are rejected.
     */
    private function isInScope(CrawlSource $source, RawRecord $raw): bool
    {
        if ($this->targetRegions->isTargetRegion($raw->kabupatenCode)
            || $this->targetRegions->isTargetRegionName($raw->kabupatenName)) {
            return true;
        }

        if ($source->slug === 'sp2kp'
            && $raw->kabupatenCode === null
            && $raw->provinceCode === TargetRegionService::PROVINCE_CODE) {
            return true;
        }

        return false;
    }

    private function hash(RawRecord $raw): string
    {
        $payload = json_encode([
            'record_type' => $raw->recordType,
            'name' => $raw->name,
            'province_code' => $raw->provinceCode,
            'kabupaten_code' => $raw->kabupatenCode,
            'kabupaten_name' => $raw->kabupatenName,
            'kecamatan_code' => $raw->kecamatanCode,
            'kecamatan_name' => $raw->kecamatanName,
            'desa_code' => $raw->desaCode,
            'desa_name' => $raw->desaName,
            'latitude' => $raw->latitude,
            'longitude' => $raw->longitude,
            'data' => $raw->data,
            'source_updated_at' => $raw->sourceUpdatedAt instanceof CarbonInterface ? $raw->sourceUpdatedAt->toIso8601String() : $raw->sourceUpdatedAt?->format('c'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return sha1((string) $payload);
    }
}
