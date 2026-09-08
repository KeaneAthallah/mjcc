<?php

namespace App\Services\PublicData;

use App\Models\ExternalData;
use RuntimeException;

/**
 * Walks the Satu Data Morowali catalog, filters datasets by the topics
 * configured for a sector, downloads each matching detail page and persists
 * normalized records (upserting on the dedupe key).
 */
class SatuDataScraper
{
    public function __construct(
        private readonly SatuDataClient $client,
        private readonly SatuDataParser $parser,
        private readonly DataNormalizer $normalizer,
    ) {}

    /**
     * Discovers matching datasets for a sector without downloading details.
     *
     * @return array<string, mixed>
     */
    public function discover(string $sector, ?int $maxPages = null): array
    {
        return $this->walk($sector, $maxPages, limit: 0, fetchDetails: false);
    }

    /**
     * @return array<mixed>
     */
    public function scrape(string $sector, int $limit = 0, bool $discoverOnly = false): array
    {
        $walk = $this->walk($sector, null, $limit, ! $discoverOnly);

        if ($discoverOnly) {
            unset($walk['datasets'], $walk['created'], $walk['updated'], $walk['records'], $walk['failures']);

            return $walk;
        }

        return $walk;
    }

    /**
     * Walks the catalog pages for a sector.
     *
     * @return array{
     *     sector: string,
     *     pages: int,
     *     discovered: int,
     *     matched: array<int, array<string, string>>,
     *     datasets: int,
     *     created: int,
     *     updated: int,
     *     records: int,
     *     failures: array<int, array{url: string, message: string}>,
     * }
     */
    private function walk(string $sector, ?int $maxPages = null, int $limit = 0, bool $fetchDetails = true): array
    {
        $config = config("public_data.sectors.{$sector}");

        if (! is_array($config)) {
            throw new RuntimeException("Sektor '{$sector}' tidak dikonfigurasi.");
        }

        $topics = array_map(fn ($t) => $this->normalizeTopic((string) $t), $config['topics'] ?? []);
        $maxPages = $maxPages ?? (int) config('public_data.satudata.max_pages', 85);

        $matched = [];
        $pages = 0;
        $failures = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $items = $this->parser->catalog($this->client->catalogPage($page));

            if ($items === []) {
                break;
            }

            $pages++;

            foreach ($items as $item) {
                $topic = $this->normalizeTopic($item['topic']);

                if ($topic !== '' && $this->matchesTopic($topic, $topics)) {
                    $matched[] = [
                        'url' => (string) $item['url'],
                        'title' => (string) $item['title'],
                        'org' => (string) $item['org'],
                        'topic' => (string) $item['topic'],
                    ];
                }
            }

            if ($limit > 0 && count($matched) >= $limit) {
                break;
            }
        }

        $datasets = 0;
        $created = 0;
        $updated = 0;

        if ($fetchDetails) {
            foreach ($matched as $item) {
                if ($limit > 0 && $datasets >= $limit) {
                    break;
                }

                try {
                    $detail = $this->parser->detail($this->client->detail((string) $item['url']));

                    $records = $this->normalizer->normalize(
                        $detail,
                        $sector,
                        'satudata',
                        (string) $item['url'],
                        (string) $item['topic'],
                    );

                    foreach ($records as $record) {
                        $existing = ExternalData::where('dedupe_key', $record['dedupe_key'])->first();

                        if ($existing !== null) {
                            $existing->update($record);
                            $updated++;
                        } else {
                            ExternalData::create($record);
                            $created++;
                        }
                    }

                    $datasets++;
                } catch (\Throwable $e) {
                    $failures[] = [
                        'url' => (string) $item['url'],
                        'message' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'sector' => $sector,
            'label' => (string) $config['label'],
            'pages' => $pages,
            'discovered' => count($matched),
            'matched' => array_slice($matched, 0, 50),
            'datasets' => $datasets,
            'created' => $created,
            'updated' => $updated,
            'records' => $created + $updated,
            'failures' => $failures,
        ];
    }

    private function normalizeTopic(string $value): string
    {
        $value = strtolower(trim($value));

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return str_replace(['’', '‘', '´', '`'], "'", $value);
    }

    private function matchesTopic(string $itemTopic, array $topics): bool
    {
        foreach ($topics as $topic) {
            if ($itemTopic === $topic || str_starts_with($itemTopic, $topic) || str_starts_with($topic, $itemTopic)) {
                return true;
            }
        }

        return false;
    }
}
