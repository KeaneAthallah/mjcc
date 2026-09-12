<?php

namespace App\Services\PublicData;

use App\Models\ExternalData;
use App\Models\PublicDataSource;
use App\Services\PublicData\Contracts\PublicDataSourceAdapter;
use Illuminate\Support\Facades\Log;

class AnakTidakSekolahAdapter implements PublicDataSourceAdapter
{
    /**
     * Alasan verifikasi yang disajikan portal (k_14 dan k_20 tidak dipublikasikan).
     */
    private const REASON_CODES = ['k_1', 'k_2', 'k_3', 'k_4', 'k_5', 'k_6', 'k_7', 'k_8', 'k_9', 'k_10', 'k_11', 'k_12', 'k_13', 'k_15', 'k_16', 'k_17', 'k_18', 'k_19', 'k_21', 'k_22', 'k_23', 'k_24', 'k_25'];

    public function __construct(
        private readonly HttpClient $http = new HttpClient,
    ) {}

    public function fetch(PublicDataSource $source, array $config): array
    {
        $baseUrl = config('public_data.ats.url', 'https://ats.data.kemendikdasmen.go.id');
        $regionCode = config('public_data.ats.region_code', '180700');
        $regionName = config('public_data.ats.region_name', 'Kabupaten Morowali');
        $query = ['tabulasi' => 'wilayah', 'status' => 'verifikasi'];
        $userAgent = ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'];

        try {
            $wilayahHtml = $this->fetchPage($baseUrl, 'rangkuman/ats-by-wilayah/'.$regionCode, $query, $userAgent);
            $recoveryHtml = $this->fetchPage($baseUrl, 'rangkuman/ats-aktif-kembali/'.$regionCode, $query, $userAgent);
            $verifikasiHtml = $this->fetchPage($baseUrl, 'rangkuman/hasil-verifikasi/'.$regionCode, $query, $userAgent);

            $rows = $wilayahHtml ? $this->parseWilayahTable($wilayahHtml) : [];
            $recovery = $recoveryHtml ? $this->parseRecoveryPages($recoveryHtml) : [];
            $verification = $verifikasiHtml ? $this->parseVerificationTable($verifikasiHtml) : [];

            return [
                'data' => $rows,
                'recovery' => $recovery,
                'verification' => $verification,
                'metadata' => [
                    'source_url' => $baseUrl,
                    'region_code' => $regionCode,
                    'region_name' => $regionName,
                    'period' => date('Y'),
                ],
                'available' => count($rows) > 0 || count($recovery['wilayah'] ?? []) > 0 || count($verification) > 0,
            ];
        } catch (\Throwable $e) {
            Log::warning('ATS fetch failed', ['error' => $e->getMessage()]);

            return ['data' => null, 'metadata' => ['error' => $e->getMessage()], 'available' => false];
        }
    }

    public function normalize(array $raw, PublicDataSource $source): array
    {
        if (! ($raw['available'] ?? false)) {
            return [];
        }

        $records = [];
        $year = (int) ($raw['metadata']['period'] ?? date('Y'));
        $regionName = (string) ($raw['metadata']['region_name'] ?? config('public_data.ats.region_name', 'Kabupaten Morowali'));

        $totals = $this->normalizeWilayah($raw, $records, $year);
        $this->normalizeRecovery($raw, $records, $year);
        $this->normalizeVerification($raw, $records, $year);

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

    /**
     * @return array<string, int>
     */
    private function normalizeWilayah(array $raw, array &$records, int $year): array
    {
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

        return $totals;
    }

    private function normalizeRecovery(array $raw, array &$records, int $year): void
    {
        $rows = $raw['recovery']['wilayah'] ?? [];

        if ($rows === []) {
            return;
        }

        $regionName = (string) ($raw['metadata']['region_name'] ?? '');
        $totals = [];

        foreach ($rows as $row) {
            $location = (string) $row['kecamatan'];

            $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Kembali Sekolah', (float) $row['total']);
            $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Kembali Sekolah DO', (float) $row['do']);
            $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Kembali Sekolah LTM', (float) $row['ltm']);

            foreach ($row['jenjang_do'] as $label => $value) {
                $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Kembali Sekolah DO Jenjang '.$label, (float) $value);
            }

            foreach ($row['jenjang_ltm'] as $label => $value) {
                $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Kembali Sekolah LTM Jenjang '.$label, (float) $value);
            }

            foreach ($row['tingkat_do'] as $tingkat => $value) {
                $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Kembali Sekolah DO Tingkat '.$tingkat, (float) $value);
            }

            foreach ($row['tingkat_ltm'] as $tingkat => $value) {
                $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Kembali Sekolah LTM Tingkat '.$tingkat, (float) $value);
            }
        }

        foreach ($totals as $indicator => $value) {
            $records[] = $this->buildRecord($raw, $year, $regionName, $indicator, $value);
        }
    }

    private function normalizeVerification(array $raw, array &$records, int $year): void
    {
        $rows = $raw['verification'] ?? [];

        if ($rows === []) {
            return;
        }

        $regionName = (string) ($raw['metadata']['region_name'] ?? '');
        $totals = [];

        foreach ($rows as $row) {
            $location = (string) $row['kecamatan'];

            $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Verifikasi Sudah', (float) $row['verified']);
            $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Verifikasi Belum', (float) $row['unverified']);

            foreach ($row['reasons'] as $code => $value) {
                $this->appendRecord($raw, $records, $totals, $year, $location, 'Anak Tidak Sekolah - Verifikasi Alasan '.$code, (float) $value);
            }
        }

        foreach ($totals as $indicator => $value) {
            $records[] = $this->buildRecord($raw, $year, $regionName, $indicator, $value);
        }
    }

    private function appendRecord(array $raw, array &$records, array &$totals, int $year, string $location, string $indicator, float $value): void
    {
        $totals[$indicator] = ($totals[$indicator] ?? 0) + $value;

        $records[] = $this->buildRecord($raw, $year, $location, $indicator, $value);
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

    private function fetchPage(string $baseUrl, string $path, array $query, array $headers): ?string
    {
        $response = $this->http->get($baseUrl.'/index.php/'.$path, $query, $headers, false);

        return $response->successful() ? $response->body() : null;
    }

    /**
     * Portal sometimes uses compact names (Witaponda, Sambori Kepulauan) that
     * differ from the official kecamatan names used by the master data.
     */
    private function normalizeName(string $name): string
    {
        $name = preg_replace('/^Kec\.\s*/i', '', trim($name)) ?? trim($name);

        return match ($name) {
            'Witaponda' => 'Wita Ponda',
            'Sambori Kepulauan' => 'Sombori Kepulauan',
            default => $name,
        };
    }

    /**
     * Parses the "ats-by-wilayah" rangkuman table where each row is:
     * No | Kecamatan | BPB | DO (Kel.A..13 = 18 cols) | LTM (6,9) | Total.
     *
     * @return array<int, array{kecamatan: string, bpb: int, do: int, ltm: int, total: int}>
     */
    private function parseWilayahTable(string $html): array
    {
        $body = $this->tableBody($this->extractTable($html, 'ats_wilayah'));

        if ($body === null) {
            return [];
        }

        $result = [];
        $rows = $this->tableRows($body);

        foreach ($rows as $rowHtml) {
            $cells = $this->rowCells($rowHtml);

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
                'kecamatan' => $this->normalizeName($name),
                'bpb' => $number(2),
                'do' => $do,
                'ltm' => $number(21) + $number(22),
                'total' => $number(23),
            ];
        }

        return $result;
    }

    /**
     * Parses the "ats-aktif-kembali" pages:
     * - `ats_wilayah`: per-kecamatan recovery totals, DO by tingkat 1..13,
     *   LTM by tingkat 6/9.
     * - `ats_sp_do` / `ats_sp_ltm`: per-kecamatan recovery by jenjang.
     *
     * @return array{wilayah: array<int, array<string, mixed>>}
     */
    private function parseRecoveryPages(string $html): array
    {
        $wilayah = [];
        $body = $this->tableBody($this->extractTable($html, 'ats_wilayah'));

        if ($body !== null) {
            foreach ($this->tableRows($body) as $rowHtml) {
                $cells = $this->rowCells($rowHtml);

                if (count($cells) < 23) {
                    continue;
                }

                $name = $cells[1];

                if ($name === '' || stripos($name, 'Kec.') === false) {
                    continue;
                }

                $number = fn (int $index): int => (int) preg_replace('/\D/', '', $cells[$index] ?? '');

                $tingkatDo = [];
                $do = 0;

                for ($i = 2; $i <= 19; $i++) {
                    $value = $number($i);
                    $do += $value;

                    if ($i >= 7) {
                        $tingkatDo[($i - 6)] = $value;
                    }
                }

                $wilayah[] = [
                    'kecamatan' => $this->normalizeName($name),
                    'do' => $do,
                    'ltm' => $number(20) + $number(21),
                    'total' => $number(22),
                    'jenjang_do' => [],
                    'jenjang_ltm' => [],
                    'tingkat_do' => $tingkatDo,
                    'tingkat_ltm' => [6 => $number(20), 9 => $number(21)],
                ];
            }
        }

        $jenjangDo = $this->parseJenjangTable($html, 'ats_sp_do');
        $jenjangLtm = $this->parseJenjangTable($html, 'ats_sp_ltm');

        foreach ($wilayah as &$row) {
            $row['jenjang_do'] = $jenjangDo[$row['kecamatan']] ?? [];
            $row['jenjang_ltm'] = $jenjangLtm[$row['kecamatan']] ?? [];
        }

        return ['wilayah' => $wilayah];
    }

    /**
     * Parses one jenjang table (No | Kecamatan | <jenjang labels> | Total),
     * keyed by normalized kecamatan name.
     *
     * @return array<string, array<string, int>>
     */
    private function parseJenjangTable(string $html, string $tableId): array
    {
        $table = $this->extractTable($html, $tableId);

        if ($table === null) {
            return [];
        }

        $labels = [];
        $reserved = ['No', 'Kecamatan', 'Jenjang Pendidikan', 'Total'];

        if (preg_match('/<thead[^>]*>(.*?)<\/thead>/is', $table, $head)) {
            preg_match_all('/<tr\b[^>]*>(.*?)<\\/tr\s*>/is', $head[1], $headRows);

            foreach ($headRows[1] as $headRowHtml) {
                $headCells = array_values(array_filter(
                    $this->rowCells($headRowHtml),
                    fn (string $cell) => $cell !== ''
                ));

                if (count($headCells) >= 2 && ! array_intersect($reserved, $headCells)) {
                    $labels = $headCells;
                    break;
                }
            }
        }

        $body = $this->tableBody($table);

        if ($body === null) {
            return [];
        }

        $result = [];

        foreach ($this->tableRows($body) as $rowHtml) {
            $cells = $this->rowCells($rowHtml);

            if (count($cells) < 3 + count($labels) || stripos($cells[1] ?? '', 'Kec.') === false) {
                continue;
            }

            $jenjang = [];

            foreach ($labels as $index => $label) {
                $jenjang[$label] = (int) preg_replace('/\D/', '', $cells[$index + 2] ?? '');
            }

            $result[$this->normalizeName($cells[1])] = $jenjang;
        }

        return $result;
    }

    /**
     * Parses the overall verification table (`ats_wilayah_alasan`):
     * No | Kecamatan | Alasan Verifikasi | Sudah Verifikasi | Belum Verifikasi | Total,
     * where "Alasan Verifikasi" expands to the k_* reason columns.
     *
     * @return array<int, array{kecamatan: string, verified: int, unverified: int, total: int, reasons: array<string, int>}>
     */
    private function parseVerificationTable(string $html): array
    {
        $body = $this->tableBody($this->extractTable($html, 'ats_wilayah_alasan'));

        if ($body === null) {
            return [];
        }

        $result = [];

        foreach ($this->tableRows($body) as $rowHtml) {
            $cells = $this->rowCells($rowHtml);

            if (count($cells) < 28) {
                continue;
            }

            $name = $cells[1];

            if ($name === '' || stripos($name, 'Kec.') === false) {
                continue;
            }

            $number = fn (int $index): int => (int) preg_replace('/\D/', '', $cells[$index] ?? '');
            $reasons = [];

            foreach (self::REASON_CODES as $index => $code) {
                $reasons[$code] = $number($index + 2);
            }

            $result[] = [
                'kecamatan' => $this->normalizeName($name),
                'verified' => $number(count($cells) - 3),
                'unverified' => $number(count($cells) - 2),
                'total' => $number(count($cells) - 1),
                'reasons' => $reasons,
            ];
        }

        return $result;
    }

    private function extractTable(string $html, string $tableId): ?string
    {
        $pattern = '/<table[^>]*id="'.preg_quote($tableId, '/').'"[^>]*>(.*?)<\/table>/is';

        if (! preg_match($pattern, $html, $match)) {
            return null;
        }

        return $match[1];
    }

    private function tableBody(?string $table): ?string
    {
        if ($table === null || ! preg_match('/<tbody>(.*?)<\/tbody>/is', $table, $body)) {
            return null;
        }

        return $body[1];
    }

    /**
     * @return array<int, string>
     */
    private function tableRows(string $body): array
    {
        $parts = preg_split('/<tr\b[^>]*>/i', $body);

        if ($parts === false) {
            return [];
        }

        array_shift($parts);

        return array_map(fn (string $part): string => preg_replace('/<\/tr\s*>.*$/is', '', $part) ?? '', $parts);
    }

    /**
     * @return array<int, string>
     */
    private function rowCells(string $rowHtml): array
    {
        preg_match_all('/<(?:td|th)[^>]*>(.*?)<\/(?:td|th)>/is', $rowHtml, $cells);

        return array_map(fn (string $cell) => trim(strip_tags($cell)), $cells[1]);
    }
}
