<?php

namespace App\Services\PublicData;

use App\Models\ExternalData;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;

class AnakTidakSekolahAdapter implements PublicDataSourceAdapter
{
    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $baseUrl = config('public_data.ats.url', 'https://ats.data.kemendikdasmen.go.id');
        $regionCode = config('public_data.ats.region_code', '180700');

        try {
            $response = $this->http->get(
                $baseUrl.'/index.php/rangkuman/ats-by-wilayah/'.$regionCode,
                ['tabulasi' => 'wilayah', 'status' => 'verifikasi'],
                ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'],
                false
            );

            if (! $response->successful()) {
                return ['data' => null, 'metadata' => ['error' => 'HTTP '.$response->status()], 'available' => false];
            }

            $rows = $this->parseWilayahTable($response->body());

            return [
                'data' => $rows,
                'metadata' => ['source_url' => $baseUrl, 'region_code' => $regionCode, 'period' => date('Y')],
                'available' => count($rows) > 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('ATS fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false) || empty($raw['data'])) {
            return [];
        }

        $records = [];
        $year = (int) ($raw['metadata']['period'] ?? date('Y'));

        $totals = ['Anak Tidak Sekolah' => 0.0, 'Anak Tidak Sekolah - BPB' => 0.0, 'Anak Tidak Sekolah - DO' => 0.0, 'Anak Tidak Sekolah - LTM' => 0.0];

        foreach ($raw['data'] as $row) {
            $location = (string) $row['kecamatan'];

            $indicators = [
                'Anak Tidak Sekolah' => (float) $row['total'],
                'Anak Tidak Sekolah - BPB' => (float) $row['bpb'],
                'Anak Tidak Sekolah - DO' => (float) $row['do'],
                'Anak Tidak Sekolah - LTM' => (float) $row['ltm'],
            ];

            foreach ($indicators as $indicator => $value) {
                $totals[$indicator] += $value;

                $records[] = $this->buildRecord($raw, $year, $location, $indicator, $value);
            }
        }

        $regionName = config('public_data.ats.region_name', 'Kabupaten Morowali');

        foreach ($totals as $indicator => $value) {
            $records[] = $this->buildRecord($raw, $year, $regionName, $indicator, $value);
        }

        return $records;
    }

    public function store(array $records, PublicDataSource $source): array
    {
        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $dedupeKey = ExternalData::dedupeKey(
                $record['sector'],
                $record['source_key'],
                $record['dataset'],
                $record['year'] ?? null,
                $record['location'],
                $record['indicator']
            );

            $existing = ExternalData::where('dedupe_key', $dedupeKey)->first();

            if ($existing) {
                $existing->update($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $updated++;
            } else {
                ExternalData::create($record + ['dedupe_key' => $dedupeKey, 'scraped_at' => now()]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'failed' => 0];
    }

    public function getSummary(PublicDataSource $source): array
    {
        $total = ExternalData::where('source_key', 'ats')->count();

        return [
            'total_records' => $total,
            'total_ats' => (int) ExternalData::where('source_key', 'ats')->where('indicator', 'Anak Tidak Sekolah')->sum('value'),
            'districts' => ExternalData::where('source_key', 'ats')->whereNotNull('location')->distinct()->count('location'),
            'latest_year' => ExternalData::where('source_key', 'ats')->max('year'),
        ];
    }

    public function validateConfig(array $config): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Anak Tidak Sekolah';
    }

    private function buildRecord(array $raw, int $year, string $location, string $indicator, float $value): array
    {
        return [
            'sector' => 'pendidikan',
            'source' => 'ats',
            'source_key' => 'ats',
            'source_url' => $raw['metadata']['source_url'] ?? '',
            'dataset' => 'Verval ATS',
            'topic' => 'Pendidikan',
            'year' => $year,
            'location' => $location,
            'indicator' => $indicator,
            'value' => $value,
            'unit' => 'Anak',
        ];
    }

    /**
     * Parses the "ats-by-wilayah" rangkuman table where each row is:
     * No | Kecamatan | BPB | DO (Kel.A..13 = 18 cols) | LTM (6,9) | Total.
     *
     * @return array<int, array{kecamatan: string, bpb: int, do: int, ltm: int, total: int}>
     */
    private function parseWilayahTable(string $html): array
    {
        if (! preg_match('/<tbody>(.*?)<\/tbody>/is', $html, $body)) {
            return [];
        }

        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $body[1], $rows);

        $result = [];

        foreach ($rows[1] as $rowHtml) {
            preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', $rowHtml, $cells);

            $cells = array_map(fn (string $cell) => trim(strip_tags($cell)), $cells[1]);

            if (count($cells) < 24) {
                continue;
            }

            $name = $cells[1];

            if ($name === '' || stripos($name, 'Kec.') === false) {
                continue;
            }

            $number = fn (int $index): int => (int) preg_replace('/\D/', '', $cells[$index] ?? '');

            $do = 0;

            for ($i = 3; $i <= 20; $i++) {
                $do += $number($i);
            }

            $result[] = [
                'kecamatan' => preg_replace('/^Kec\.\s*/i', '', $name),
                'bpb' => $number(2),
                'do' => $do,
                'ltm' => $number(21) + $number(22),
                'total' => $number(23),
            ];
        }

        return $result;
    }
}
