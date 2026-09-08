<?php

namespace App\Services\DataImport;

use App\Models\ExternalData;
use App\Models\Kecamatan;
use App\Services\PublicData\LocationResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Shared machinery for turning per-entity datasets (a row per school or health
 * facility) into master-table rows. Concrete importers declare which header
 * identifies the entity name, how to infer its type, and where to store it.
 *
 * Only datasets whose entity name column is also the normalized "location" are
 * imported: that shape proves the normalizer preserved one row per entity.
 * Datasets where a kecamatan column took the location slot have already been
 * collapsed into aggregate rows by the normalizer and are handled as
 * statistics only.
 */
abstract class EntityImporter
{
    public function __construct(protected LocationResolver $resolver) {}

    abstract public function sector(): string;

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    /**
     * Header regexes that mark the entity name column of a dataset.
     *
     * @return array<int, string>
     */
    abstract protected function nameColumnPatterns(): array;

    /**
     * Whether the target table requires a kecamatan (schools do, health
     * facilities allow none yet).
     */
    protected function requiresKecamatan(): bool
    {
        return true;
    }

    /**
     * Infer the domain type (school_type / facility_type) for an entity row.
     */
    abstract protected function inferType(array $row, string $name): ?string;

    /**
     * Locate an existing master row for the same real-world entity.
     */
    protected function findExisting(string $name, string $type): ?Model
    {
        $model = $this->modelClass();

        return $model::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
    }

    /**
     * Translate a parsed entity row into master-table fillable data.
     */
    abstract protected function toFillable(array $row, string $name, string $type, ?Kecamatan $kecamatan): array;

    /**
     * Analyze one dataset's normalized records: detect whether it is an entity
     * dataset and, when so, extract the unique entity rows it holds plus the
     * kecamatan places those rows reference.
     *
     * @param  Collection<int, ExternalData>  $records
     * @return array{
     *     entity: bool,
     *     rows: array<string, array{rawName: string, row: array<string, string>, year: int|null, dataset: string}>,
     *     kecamatan: array<string, array{name: string, latitude: float, longitude: float}>,
     * }
     */
    public function extract(Collection $records): array
    {
        $nameColumn = $this->nameColumn($records);

        if ($nameColumn === null) {
            return ['entity' => false, 'rows' => [], 'kecamatan' => []];
        }

        if (! $this->isNameAsLocation($records, $nameColumn)) {
            return ['entity' => false, 'rows' => [], 'kecamatan' => []];
        }

        $rows = [];
        $kecamatan = [];

        foreach ($records as $record) {
            $row = $this->drawRow($record);

            if ($row === null) {
                continue;
            }

            $rawName = trim((string) ($row[$nameColumn] ?? ''));

            if ($this->isAggregateRowName($rawName)) {
                continue;
            }

            $key = mb_strtolower(preg_replace('/\s+/', ' ', $rawName) ?? $rawName);

            if (array_key_exists($key, $rows)) {
                continue;
            }

            $rows[$key] = [
                'rawName' => $rawName,
                'row' => $row,
                'year' => $record->year,
                'dataset' => (string) $record->dataset,
            ];

            $resolved = $this->resolveKecamatan($row, $rawName);

            if ($resolved !== null && KecamatanImporter::isDistrict($resolved['name'])) {
                $kecamatan[$resolved['name']] = $resolved;
            }
        }

        return ['entity' => true, 'rows' => $rows, 'kecamatan' => $kecamatan];
    }

    /**
     * Upsert the extracted entity rows into the master table.
     *
     * @param  array<string, array{rawName: string, row: array<string, string>, year: int|null, dataset: string}>  $rows
     * @return array{created: int, updated: int, skipped: int, failed: int}
     */
    public function persistRows(array $rows): array
    {
        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($rows as $entity) {
            $counts[$this->persistRow($entity)]++;
        }

        return $counts;
    }

    /**
     * @param  array{rawName: string, row: array<string, string>, year: int|null, dataset: string}  $entity
     */
    private function persistRow(array $entity): string
    {
        try {
            $type = $this->inferType($entity['row'], $entity['rawName']);

            if ($type === null) {
                return 'skipped';
            }

            $kecamatan = $this->resolveKecamatan($entity['row'], $entity['rawName']);
            $kecamatanModel = null;

            if ($kecamatan !== null) {
                $kecamatanModel = Kecamatan::whereRaw('LOWER(name) = ?', [mb_strtolower($kecamatan['name'])])->first();
            }

            if ($this->requiresKecamatan() && $kecamatanModel === null) {
                return 'skipped';
            }

            $model = $this->modelClass();
            $data = $this->toFillable($entity['row'], $entity['rawName'], $type, $kecamatanModel);

            $existing = $this->findExisting($entity['rawName'], $type);

            if ($existing !== null) {
                $this->applySafeUpdate($existing, $data);
                $existing->save();

                return 'updated';
            }

            $model::create($data);

            return 'created';
        } catch (Throwable) {
            return 'failed';
        }
    }

    /**
     * Never let the portal wipe out values the master table has but the source
     * dataset does not truthfully provide.
     */
    private function applySafeUpdate(Model $model, array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value === null && $model->getAttribute($key) !== null) {
                continue;
            }

            $model->setAttribute($key, $value);
        }
    }

    /**
     * Read the raw row a record was normalized from (headers + original cells).
     *
     * @return array<string, string>|null
     */
    private function drawRow($record): ?array
    {
        if (! $record instanceof Model) {
            return null;
        }

        $raw = $record->getAttribute('raw_data') ?? [];

        if (! is_array($raw)) {
            return null;
        }

        $headers = $raw['headers'] ?? [];
        $cells = $raw['row'] ?? [];

        if (! is_array($headers) || ! is_array($cells) || $headers === []) {
            return null;
        }

        $headers = array_values(array_map('strval', $headers));

        $cells = array_values(array_map('strval', $cells));

        if (count($cells) < count($headers)) {
            return null;
        }

        $row = [];

        foreach ($headers as $i => $header) {
            $row[$header] = $cells[$i];
        }

        return $row;
    }

    /**
     * @param  Collection<int, ExternalData>  $records
     */
    private function nameColumn(Collection $records): ?string
    {
        foreach ($records as $record) {
            $raw = $record->getAttribute('raw_data') ?? [];

            if (! is_array($raw) || ! is_array($raw['headers'] ?? null)) {
                continue;
            }

            foreach ($raw['headers'] as $header) {
                foreach ($this->nameColumnPatterns() as $pattern) {
                    if (preg_match($pattern, (string) $header) === 1) {
                        return (string) $header;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, ExternalData>  $records
     */
    private function isNameAsLocation(Collection $records, string $nameColumn): bool
    {
        $checked = 0;
        $agree = 0;

        foreach ($records as $record) {
            if ($checked >= 3) {
                break;
            }

            $row = $this->drawRow($record);

            if ($row === null) {
                continue;
            }

            $checked++;

            $nameCell = trim((string) ($row[$nameColumn] ?? ''));

            if ($nameCell === '') {
                continue;
            }

            $cell = mb_strtolower(preg_replace('/\s+/', ' ', $nameCell) ?? $nameCell);
            $location = mb_strtolower(preg_replace('/\s+/', ' ', (string) $record->location) ?? (string) $record->location);

            if ($cell === $location) {
                $agree++;
            }
        }

        return $checked > 0 && $agree === $checked;
    }

    private function isAggregateRowName(string $name): bool
    {
        $name = trim($name);

        if ($name === '' || $name === '-') {
            return true;
        }

        return preg_match('/^(jumlah|total|sub\s*jumlah|jk|kabupaten\s+morowali|provinsi|kecamatan)$/i', $name) === 1;
    }

    /**
     * Locate the kecamatan an entity belongs to: an explicit kecamatan column
     * wins, otherwise the spreader tries to derive it from the entity name.
     *
     * @return array{name: string, latitude: float, longitude: float}|null
     */
    protected function resolveKecamatan(array $row, string $rawName): ?array
    {
        foreach ($row as $header => $value) {
            if (preg_match('/kecamatan/i', (string) $header) !== 1) {
                continue;
            }

            if (preg_match('/\b(kode|id|no)\b/i', (string) $header) === 1) {
                continue;
            }

            $resolved = $this->resolver->resolve(trim((string) $value));

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $this->resolveFromName($rawName);
    }

    /**
     * @return array{name: string, latitude: float, longitude: float}|null
     */
    private function resolveFromName(string $rawName): ?array
    {
        $candidate = trim($this->stripTypePrefix($rawName));

        if ($candidate === '') {
            return null;
        }

        $resolved = $this->resolver->resolve($candidate);

        if ($resolved !== null) {
            return $resolved;
        }

        $words = preg_split('/\s+/', $candidate) ?: [];

        for ($length = min(3, count($words)); $length >= 1; $length--) {
            $tail = implode(' ', array_slice($words, -$length));

            if ($tail === $candidate) {
                continue;
            }

            $resolved = $this->resolver->resolve($tail);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Drop leading type markers ("SDN 1", "Puskesmas", "SMPN 2", ...) so the
     * remaining name can be matched against the gazetteer.
     */
    private function stripTypePrefix(string $name): string
    {
        $tokens = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: [], static fn ($t) => $t !== ''));

        while ($tokens !== []) {
            $token = mb_strtolower((string) $tokens[0]);

            $isType = preg_match('/^(sdn?|smpn?|smkn?|sman?|min|mis|mtsn?|man?|mi|mts|ma|tk|ra|sd|smp|sma|smk|slb|puskesmas|pustu|posyandu|poskesdes|polindes|klinik|rs|rsk|negeri|swasta)$/i', $token) === 1;
            $isNumber = preg_match('/^\d+$/i', $token) === 1;

            if (! $isType && ! $isNumber) {
                break;
            }

            array_shift($tokens);
        }

        return implode(' ', $tokens);
    }

    /**
     * Read a coordinate pair from explicit latitude/longitude cells when the
     * source dataset publishes them, rejecting invalid placeholders.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    protected function explicitCoordinates(array $row): ?array
    {
        $latitude = null;
        $longitude = null;

        foreach ($row as $header => $value) {
            $headerKey = mb_strtolower((string) $header);

            if ($latitude === null && (str_contains($headerKey, 'lat') || $headerKey === 'lintang')) {
                $latitude = $this->cellToFloat((string) $value);
            }

            if ($longitude === null && (str_contains($headerKey, 'lng') || str_contains($headerKey, 'long') || $headerKey === 'bujur')) {
                $longitude = $this->cellToFloat((string) $value);
            }
        }

        if (! Coordinates::valid($latitude, $longitude)) {
            return null;
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
    }

    protected function cellToFloat(string $value): ?float
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * First non-empty, non-placeholder cell whose header contains any needle
     * keyword, used for mapping free-form columns (address, npsn, ...).
     *
     * @param  array<int, string>  $needleKeys
     */
    protected function cell(array $row, array $needleKeys): ?string
    {
        foreach ($row as $header => $value) {
            $headerKey = mb_strtolower((string) $header);

            foreach ($needleKeys as $needle) {
                if (str_contains($headerKey, $needle)) {
                    $value = trim((string) $value);

                    if ($value !== '' && $value !== '-') {
                        return $value;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Fallback marker placement: the kecamatan centroid until the portal
     * publishes per-building coordinates.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    protected function kecamatanCentroid(?Kecamatan $kecamatan): ?array
    {
        if ($kecamatan === null || $kecamatan->latitude === null || $kecamatan->longitude === null) {
            return null;
        }

        return [
            'latitude' => (float) $kecamatan->latitude,
            'longitude' => (float) $kecamatan->longitude,
        ];
    }
}
