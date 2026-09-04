<?php

namespace App\Services\Crawlers;

use App\Models\ActivityLog;
use App\Models\CrawlRecord;
use App\Models\CrawlRun;
use App\Models\CrawlSource;
use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthFacilityImportService
{
    private array $kecamatanCache = [];

    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * Import crawl records into the health_facilities table.
     *
     * Returns import statistics.
     *
     * @return array{created: int, updated: int, skipped: int, failed: int, warnings: string[]}
     */
    public function importFromCrawl(CrawlRun $run): array
    {
        $source = CrawlSource::where('slug', 'kesehatan')->first();

        if ($source === null) {
            return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 1, 'warnings' => ['Sumber kesehatan tidak ditemukan.']];
        }

        $records = CrawlRecord::query()
            ->where('crawl_source_id', $source->id)
            ->where('crawl_run_id', $run->id)
            ->get();

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'warnings' => []];

        DB::transaction(function () use ($records, $run, &$stats) {
            foreach ($records as $crawlRecord) {
                try {
                    $result = $this->importRecord($crawlRecord, $run);
                    $stats[$result]++;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    $stats['warnings'][] = "Gagal mengimpor [{$crawlRecord->name}]: {$e->getMessage()}";
                    Log::channel('crawler')->warning('health:import:failed', [
                        'record_id' => $crawlRecord->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        $this->activityLog->record(
            action: ActivityLog::ACTION_UPDATE,
            resourceType: 'CrawlRun',
            resourceId: $run->id,
            newValues: [
                'source' => 'kesehatan',
                'created' => $stats['created'],
                'updated' => $stats['updated'],
                'skipped' => $stats['skipped'],
                'failed' => $stats['failed'],
                'warnings' => $stats['warnings'],
            ],
        );

        return $stats;
    }

    /**
     * Import a single crawl record into health_facilities.
     *
     * @return 'created'|'updated'|'skipped'
     */
    private function importRecord(CrawlRecord $crawlRecord, CrawlRun $run): string
    {
        $data = $crawlRecord->data ?? [];

        $name = $this->normalizeName($crawlRecord->name ?? '');
        if ($name === '') {
            return 'skipped';
        }

        $facilityType = $data['facility_type'] ?? 'Puskesmas';
        if (! in_array($facilityType, ['Puskesmas', 'Pustu', 'Rumah Sakit', 'Posyandu'], true)) {
            $facilityType = 'Puskesmas';
        }

        $kecamatan = $this->resolveKecamatan($crawlRecord->kecamatan_name ?? '', $crawlRecord->kabupaten_code ?? '');

        $existing = $this->findExisting($name, $kecamatan?->id, $crawlRecord->external_id);

        $attributes = [
            'kecamatan_id' => $kecamatan?->id,
            'name' => $name,
            'facility_type' => $facilityType,
            'address' => $this->normalizeAddress($data['address'] ?? $crawlRecord->name ?? ''),
            'latitude' => $crawlRecord->latitude,
            'longitude' => $crawlRecord->longitude,
            'condition' => $data['condition'] ?? 'baik',
            'beds' => max(0, (int) ($data['beds'] ?? 0)),
            'doctors' => max(0, (int) ($data['doctors'] ?? 0)),
            'nurses' => max(0, (int) ($data['nurses'] ?? 0)),
            'midwives' => max(0, (int) ($data['midwives'] ?? 0)),
            'status' => $data['status'] ?? 'aktif',
            'phone' => $data['phone'] ?? null,
            'description' => $data['description'] ?? null,
            'source_name' => 'kemenkes_fasyankes',
            'source_url' => $crawlRecord->source_url,
            'source_id' => $crawlRecord->external_id,
            'source_updated_at' => $crawlRecord->source_updated_at,
            'last_crawled_at' => now(),
        ];

        if ($existing !== null) {
            $existing->fill($attributes);
            $existing->save();

            $this->activityLog->logModel(
                action: ActivityLog::ACTION_UPDATE,
                model: $existing,
                oldValues: $existing->getOriginal(),
                newValues: $existing->getAttributes(),
            );

            return 'updated';
        }

        $facility = HealthFacility::create($attributes);

        $this->activityLog->logModel(
            action: ActivityLog::ACTION_CREATE,
            model: $facility,
            oldValues: null,
            newValues: $facility->getAttributes(),
        );

        return 'created';
    }

    private function findExisting(string $name, ?int $kecamatanId, ?string $externalId): ?HealthFacility
    {
        if ($externalId !== null) {
            $found = HealthFacility::where('source_id', $externalId)->first();
            if ($found !== null) {
                return $found;
            }
        }

        $query = HealthFacility::where('name', $name);

        if ($kecamatanId !== null) {
            $query->where('kecamatan_id', $kecamatanId);
        }

        return $query->first();
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        return (string) $name;
    }

    private function normalizeAddress(string $address): string
    {
        $address = trim($address);
        $address = preg_replace('/\s+/', ' ', $address);

        return (string) $address;
    }

    private function resolveKecamatan(?string $kecamatanName, ?string $kabupatenCode): ?Kecamatan
    {
        if ($kecamatanName === null || $kecamatanName === '') {
            return null;
        }

        $cacheKey = strtolower($kecamatanName);
        if (isset($this->kecamatanCache[$cacheKey])) {
            return $this->kecamatanCache[$cacheKey];
        }

        $normalized = strtolower(trim($kecamatanName));

        $found = Kecamatan::whereRaw('LOWER(name) = ?', [$normalized])->first();

        if ($found === null) {
            $found = Kecamatan::whereRaw('LOWER(name) LIKE ?', ['%'.$normalized.'%'])->first();
        }

        $this->kecamatanCache[$cacheKey] = $found;

        return $found;
    }
}
