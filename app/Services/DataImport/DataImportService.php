<?php

namespace App\Services\DataImport;

use App\Models\DataImportLog;
use App\Models\ExternalData;
use App\Services\PublicData\LocationResolver;
use Illuminate\Support\Collection;
use RuntimeException;
use Throwable;

/**
 * Turns already-scraped `external_data` rows into master-table records
 * (kecamatan, schools, health facilities) and records one append-only
 * `data_import_log` row per sector per run.
 *
 * Pipeline per sector:
 *  1. classify every dataset as aggregate statistics or per-entity rows
 *  2. upsert kecamatan master rows from every verified location mentioned
 *  3. upsert master entities (schools / health facilities) from entity datasets
 *  4. persist provenance + counters in DataImportLog
 */
class DataImportService
{
    public function __construct(
        private readonly LocationResolver $resolver,
        private readonly KecamatanImporter $kecamatanImporter,
    ) {}

    /**
     * Import for the given sector (or all sectors) from stored external data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function run(?string $sector = null): array
    {
        $sectors = $sector !== null ? [$sector] : DataImportLog::SECTORS;

        $results = [];

        foreach ($sectors as $key) {
            if (! in_array($key, DataImportLog::SECTORS, true)) {
                throw new RuntimeException("Sektor tidak dikenal: {$key}");
            }

            $results[] = $this->runSector($key);
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    private function runSector(string $sector): array
    {
        $log = $this->beginLog($sector);

        try {
            $records = ExternalData::query()->where('sector', $sector)->get();

            /** @var Collection<string, Collection<int, ExternalData>> $byDataset */
            $byDataset = $records->groupBy('dataset');

            $importer = $this->entityImporter($sector);

            $entityRows = [];
            $kecamatanCandidates = [];

            foreach ($byDataset as $group) {
                if ($importer !== null) {
                    $analysis = $importer->extract($group);

                    if ($analysis['entity']) {
                        $entityRows = array_merge($entityRows, $analysis['rows']);

                        foreach ($analysis['kecamatan'] as $name => $location) {
                            $kecamatanCandidates[$name] = $location;
                        }
                    }
                }

                foreach ($group as $record) {
                    $resolved = $this->resolver->resolve((string) $record->location);

                    if ($resolved === null || ! KecamatanImporter::isDistrict($resolved['name'])) {
                        continue;
                    }

                    $kecamatanCandidates[$resolved['name']] = $resolved;
                }
            }

            $kecamatan = $this->kecamatanImporter->import(array_values($kecamatanCandidates));

            $counts = [
                'datasets_scanned' => $byDataset->count(),
                'entity_datasets' => 0,
                'entities_created' => 0,
                'entities_updated' => 0,
                'entities_skipped' => 0,
                'entities_failed' => 0,
                'kecamatan_created' => $kecamatan['created'],
                'kecamatan_updated' => $kecamatan['updated'],
                'locations_resolved' => count($kecamatanCandidates),
            ];

            $summary = [];

            if ($importer !== null && $entityRows !== []) {
                $counts['entity_datasets'] = count(array_unique(array_column(array_values($entityRows), 'dataset')));

                $out = $importer->persistRows($entityRows);

                $counts['entities_created'] = $out['created'];
                $counts['entities_updated'] = $out['updated'];
                $counts['entities_skipped'] = $out['skipped'];
                $counts['entities_failed'] = $out['failed'];

                $summary['entities'] = $out;
            }

            $this->completeLog($log, DataImportLog::STATUS_SUCCESS, $counts, $summary);

            return array_merge(['sector' => $sector, 'status' => DataImportLog::STATUS_SUCCESS], $counts, ['summary' => $summary]);
        } catch (Throwable $e) {
            $this->completeLog($log, DataImportLog::STATUS_FAILED, [], [], $e->getMessage());

            return [
                'sector' => $sector,
                'status' => DataImportLog::STATUS_FAILED,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function beginLog(string $sector): DataImportLog
    {
        return DataImportLog::create([
            'sector' => $sector,
            'status' => DataImportLog::STATUS_RUNNING,
            'started_at' => now(),
            'datasets_scanned' => 0,
            'entity_datasets' => 0,
            'entities_created' => 0,
            'entities_updated' => 0,
            'entities_skipped' => 0,
            'entities_failed' => 0,
            'kecamatan_created' => 0,
            'kecamatan_updated' => 0,
            'locations_resolved' => 0,
        ]);
    }

    /**
     * @param  array<string, int>  $counts
     * @param  array<string, mixed>  $summary
     */
    private function completeLog(DataImportLog $log, string $status, array $counts, array $summary, ?string $error = null): void
    {
        $payload = [
            'status' => $status,
            'finished_at' => now(),
            'summary' => $summary,
            'error_summary' => $error,
        ];

        $log->update(array_merge($payload, $counts));
    }

    private function entityImporter(string $sector): ?EntityImporter
    {
        return match ($sector) {
            'pendidikan' => app(SchoolImporter::class),
            'kesehatan' => app(HealthFacilityImporter::class),
            default => null,
        };
    }
}
