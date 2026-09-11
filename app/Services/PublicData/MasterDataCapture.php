<?php

namespace App\Services\PublicData;

use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Captures real per-entity master data from reachable official sources and
 * writes them as JSON snapshots for later import.
 *
 * Two sources are available over plain HTTP (no browser needed):
 *
 *  - Kemkes SISDMK (dreams.kemkes.go.id) publishes a per-Puskesmas table of
 *    Kabupaten Morowali with real staffing figures (dokter/perawat/bidan, …).
 *  - SP2KP (api-sp2kp.kemendag.go.id) publishes the real market list of the
 *    region, with coordinates and BPS region codes.
 */
class MasterDataCapture
{
    public function __construct(private readonly HttpClient $http = new HttpClient) {}

    /**
     * Fetch both sources and write their snapshots into the snapshot directory.
     *
     * @return array{puskesmas: string, markets: string, dir: string, captured_at: string}
     */
    public function capture(?string $dir = null): array
    {
        $dir ??= (string) config('public_data.master.dir', storage_path('app/data/morowali'));

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $puskesmasPath = $dir.'/kemkes-puskesmas.json';
        $marketsPath = $dir.'/sp2kp-markets.json';

        $capturedAt = now()->toIso8601String();

        $puskesmasHtml = $this->http->get($this->kemkesUrl(), [], [
            'Accept' => 'text/html,application/xhtml+xml,*/*',
        ], false)->body();

        file_put_contents(
            $puskesmasPath,
            json_encode([
                'region_code' => (string) config('public_data.kemkes.region_code', '7206'),
                'puskesmas' => $this->parseKemkesHtml($puskesmasHtml),
                'captured_at' => $capturedAt,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        $sp2kpBody = $this->http->get($this->sp2kpUrl(), [
            'take' => 9999,
            'kode_provinsi' => (string) config('public_data.sp2kp.province_code', '72'),
            'kode_kab_kota' => (string) config('public_data.sp2kp.region_code', '7206'),
            'is_nasional' => 'true',
        ], ['Accept' => 'application/json'], false)->body();

        file_put_contents(
            $marketsPath,
            json_encode([
                'region_code' => (string) config('public_data.sp2kp.region_code', '7206'),
                'markets' => $this->parseSp2kpMarkets($sp2kpBody),
                'captured_at' => $capturedAt,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        Log::info('Master data captured', ['puskesmas' => $puskesmasPath, 'markets' => $marketsPath]);

        return [
            'puskesmas' => $puskesmasPath,
            'markets' => $marketsPath,
            'dir' => $dir,
            'captured_at' => $capturedAt,
        ];
    }

    /**
     * Extract the per-Puskesmas rows from the SISDMK page table.
     *
     * @return array<int, array<string, int|string|null>>
     */
    public function parseKemkesHtml(string $html): array
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $rows = [];

        $headerFound = false;
        $headerCells = [];

        foreach ($xpath->query('//table') as $table) {
            foreach ($xpath->query('.//tr', $table) as $index => $tr) {
                $cells = $this->cells($xpath, $tr);
                $values = array_map(fn (DOMNode $td) => trim((string) preg_replace('/\s+/', ' ', $td->textContent)), $cells);

                if (! $headerFound) {
                    $joined = implode(' ', $values);

                    if (str_contains($joined, 'Puskesmas Teregistrasi') && str_contains($joined, 'Dokter')) {
                        $headerFound = true;
                        $headerCells = $values;

                        continue;
                    }

                    continue;
                }

                if (count($values) !== count($headerCells) || $values[1] === '') {
                    continue;
                }

                $rows[] = [
                    'name' => $values[1],
                    'jenis' => $values[2] ?: null,
                    'blu' => $values[3] ?: null,
                    'dokter' => $this->leadNumber($values[4]),
                    'dokter_gigi' => $this->leadNumber($values[5]),
                    'perawat' => $this->leadNumber($values[6]),
                    'bidan' => $this->leadNumber($values[7]),
                    'promkes' => $this->leadNumber($values[8]),
                    'sanling' => $this->leadNumber($values[9]),
                    'gizi' => $this->leadNumber($values[10]),
                    'farmasi' => $this->leadNumber($values[11]),
                    'atlm' => $this->leadNumber($values[12]),
                ];
            }
        }

        return $rows;
    }

    /**
     * Extract the market list from the SP2KP `/master/api/pasar` response.
     *
     * @return array<int, array<string, int|string|float|null>>
     */
    public function parseSp2kpMarkets(string $json): array
    {
        $body = json_decode($json, true);

        if (! is_array($body) || ! isset($body['data']) || ! is_array($body['data'])) {
            throw new RuntimeException('Respons SP2KP tidak valid (tidak ada key "data").');
        }

        $markets = [];

        foreach ($body['data'] as $row) {
            $name = trim((string) ($row['nama'] ?? ''));

            if ($name === '') {
                continue;
            }

            $markets[] = [
                'id' => (int) ($row['id'] ?? 0),
                'kode' => $row['kode'] ?? null,
                'nama' => $name,
                'alamat' => trim((string) ($row['alamat'] ?? '')) ?: null,
                'kode_kecamatan' => $row['kode_kecamatan'] ?? null,
                'kode_kelurahan' => $row['kode_kelurahan'] ?? null,
                'tipe' => data_get($row, 'tipe_pasar.nama') ?: null,
                'latitude' => $this->nullableDecimal($row['lat'] ?? null),
                'longitude' => $this->nullableDecimal($row['lon'] ?? null),
            ];
        }

        return $markets;
    }

    private function kemkesUrl(): string
    {
        $template = (string) config('public_data.kemkes.provider_url', '');

        if ($template === '') {
            throw new RuntimeException('URL Kemkes SISDMK belum dikonfigurasi (public_data.kemkes.provider_url).');
        }

        return str_replace(
            ['{province}', '{region}'],
            [
                (string) config('public_data.kemkes.province_code', '72'),
                (string) config('public_data.kemkes.region_code', '7206'),
            ],
            $template
        );
    }

    private function sp2kpUrl(): string
    {
        return rtrim((string) config('public_data.sp2kp.api_base', 'https://api-sp2kp.kemendag.go.id'), '/').'/master/api/pasar';
    }

    /**
     * @return list<DOMNode>
     */
    private function cells(DOMXPath $xpath, DOMNode $tr): array
    {
        return iterator_to_array($xpath->query('.//th|.//td', $tr));
    }

    /**
     * SISDMK writes staffing as "1/0" (eksisting/target); take the eksisting
     * integer on the left of the slash.
     */
    private function leadNumber(string $value): int
    {
        $lead = strtolower(trim($value)) === '' ? '0' : explode('/', $value)[0];

        return is_numeric($lead) ? (int) $lead : 0;
    }

    private function nullableDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
