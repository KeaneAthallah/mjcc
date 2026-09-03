<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRun;
use Illuminate\Http\Client\Response;

/**
 * Crawler data satuan pendidikan (sekolah) dari DAPO Kementerian Pendidikan.
 * Sumber resmi yang dipakai adalah Portal Data Referensi Kemendikdasmen:
 *
 *   https://referensi.data.kemendikdasmen.go.id
 *
 * Portal ini menyediakan halaman publik dengan sertifikat yang valid (tanpa
 * WAF/auth) dan menampilkan hierarki wilayah: provinsi -> kabupaten ->
 * kecamatan -> sekolah. Crawler menelusuri hierarki tersebut, membatasi hanya
 * pada dua kabupaten target (Morowali 7206 dan Morowali Utara 7212), lalu
 * menyimpan tiap sekolah sebagai rekor "sekolah".
 *
 * Catatan: portal memakai kode wilayahnya sendiri (bukan Kemendagri/BPS),
 * sehingga pemilihan kabupaten dilakukan berdasarkan nama, bukan kode numerik.
 */
class DapoCrawler extends AbstractCrawler
{
    private const PROVINCE_CODE = '180000';

    private const PROVINCE_LEVEL = 1;

    private const KABUPATEN_LEVEL = 2;

    private const KECAMATAN_LEVEL = 3;

    public function source(): string
    {
        return 'dapo';
    }

    protected function crawl(CrawlRun $run): array
    {
        $base = rtrim((string) config('crawler.sources.dapo.base_url', 'https://referensi.data.kemendikdasmen.go.id'), '/');

        $records = [];

        foreach ($this->targetKabupaten($base, $run) as $kabupaten) {
            $kecamatans = $this->kecamatans($base, $run, $kabupaten);

            foreach ($kecamatans as $kecamatan) {
                $records = [...$records, ...$this->schools($base, $run, $kabupaten, $kecamatan)];
                $this->politeDelay();
            }
        }

        return $records;
    }

    /**
     * Fetch the province page and return the two target kabupaten.
     *
     * @return array<int, array{portal_code: string, name: string, kanonik: string}>
     */
    private function targetKabupaten(string $base, CrawlRun $run): array
    {
        $response = $this->get($base, self::PROVINCE_LEVEL, self::PROVINCE_CODE);
        $result = [];

        foreach ($this->regionRows((string) ($response?->body() ?? ''), 2) as $code => $name) {
            if (! $this->targetRegions->isTargetRegionName($name)) {
                continue;
            }

            $result[$code] = [
                'portal_code' => (string) $code,
                'name' => (string) $name,
                'kanonik' => $this->kabupatenCodeForName((string) $name),
            ];
        }

        if ($result === []) {
            $this->sync->recordError($this->source, $run, 'Kabupaten target tidak ditemukan pada portal referensi DAPO.', $response?->status() ?? 200, $base);
        }

        return array_values($result);
    }

    /**
     * Fetch one kabupaten page and return its kecamatan rows.
     *
     * @param  array{portal_code: string, name: string, kanonik: string}  $kabupaten
     * @return array<int, array{portal_code: string, name: string}>
     */
    private function kecamatans(string $base, CrawlRun $run, array $kabupaten): array
    {
        $response = $this->get($base, self::KABUPATEN_LEVEL, $kabupaten['portal_code']);

        if ($response === null || $response->failed()) {
            $this->sync->recordError($this->source, $run, 'Gagal mengambil kecamatan DAPO.', $response?->status() ?? 200, $base);

            return [];
        }

        $kecamatans = [];

        foreach ($this->regionRows((string) $response->body(), 3) as $code => $name) {
            $kecamatans[] = [
                'portal_code' => (string) $code,
                'name' => (string) $name,
            ];
        }

        return $kecamatans;
    }

    /**
     * Fetch one kecamatan page and parse its school rows.
     *
     * @param  array{portal_code: string, name: string, kanonik: string}  $kabupaten
     * @param  array{portal_code: string, name: string}  $kecamatan
     * @return RawRecord[]
     */
    private function schools(string $base, CrawlRun $run, array $kabupaten, array $kecamatan): array
    {
        $response = $this->get($base, self::KECAMATAN_LEVEL, $kecamatan['portal_code']);

        if ($response === null || $response->failed()) {
            $this->sync->recordError($this->source, $run, 'Gagal mengambil sekolah DAPO.', $response?->status() ?? 200, $base);

            return [];
        }

        $records = [];

        foreach ($this->schoolRows((string) $response->body()) as $row) {
            $npsn = (string) ($row['npsn'] ?? '');
            $name = (string) ($row['name'] ?? '');

            if ($npsn === '' || $name === '') {
                continue;
            }

            $records[] = new RawRecord(
                externalId: 'dapo-'.$npsn,
                recordType: 'sekolah',
                name: $name,
                provinceCode: '72',
                kabupatenCode: $kabupaten['kanonik'],
                kabupatenName: $this->targetRegions->isMorowali($kabupaten['kanonik'])
                    ? 'Kabupaten Morowali'
                    : 'Kabupaten Morowali Utara',
                kecamatanName: $kecamatan['name'],
                data: [
                    'npsn' => $npsn,
                    'nama' => $name,
                    'jenjang' => $this->jenjangFromName($name),
                    'status' => (string) ($row['status'] ?? ''),
                    'alamat' => (string) ($row['alamat'] ?? ''),
                    'kelurahan' => (string) ($row['kelurahan'] ?? ''),
                ],
                sourceUrl: $base.'/pendidikan/npsn/'.$npsn,
            );
        }

        return $records;
    }

    private function get(string $base, int $level, string $code): ?Response
    {
        return $this->http()->get($base.'/pendidikan/dikdas/'.$code.'/'.$level);
    }

    /**
     * Parse drill-down rows (region links) from a page into a code => name map.
     *
     * @return array<string, string>
     */
    private function regionRows(string $html, int $level): array
    {
        preg_match_all(
            '#href="https://referensi\.data\.kemendikdasmen\.go\.id/pendidikan/dikdas/(\d+)/'.$level.'"[^>]*>([^<]*)</a>#i',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        $rows = [];

        foreach ($matches as $m) {
            $name = trim(strip_tags((string) $m[2]));

            if ($name !== '') {
                $rows[(string) $m[1]] = $name;
            }
        }

        return $rows;
    }

    /**
     * Parse server-rendered <tr> school rows from a kecamatan page.
     *
     * @return array<int, array<string, string>>
     */
    private function schoolRows(string $html): array
    {
        preg_match_all('/<tr>(.*?)<\/tr>/is', $html, $trMatches);
        $rows = [];

        foreach ($trMatches[1] ?? [] as $tr) {
            if (str_contains($tr, 'pendidikan/npsn') === false) {
                continue;
            }

            if (! preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $tr, $tdMatches)) {
                continue;
            }

            $cells = array_map(fn ($c) => trim(strip_tags($c)), $tdMatches[1]);

            if (count($cells) < 6) {
                continue;
            }

            preg_match('/pendidikan\/npsn\/(\d+)/', $tr, $npsn);

            $rows[] = [
                'npsn' => $npsn[1] ?? ($cells[1] ?? ''),
                'name' => $cells[2] ?? '',
                'alamat' => $cells[3] ?? '',
                'kelurahan' => $cells[4] ?? '',
                'status' => $cells[5] ?? '',
            ];
        }

        return $rows;
    }

    private function kabupatenCodeForName(string $name): string
    {
        $lower = mb_strtolower($name);

        if (str_contains($lower, 'utara')) {
            return '7212';
        }

        return '7206';
    }

    private function jenjangFromName(string $name): string
    {
        $clean = mb_strtoupper((string) preg_replace('/[^A-Za-z0-9 ]/', ' ', $name));
        $tokens = array_values(array_filter(
            preg_split('/\s+/', $clean),
            static fn ($t) => $t !== '' && ! ctype_digit($t)
        ));

        $sig = implode('', $tokens);

        return match (true) {
            str_starts_with($sig, 'SEKOLAHMENENGAHPERTAMA') => 'SMP',
            str_starts_with($sig, 'SEKOLAHDASAR') => 'SD',
            str_starts_with($sig, 'SLB') => 'SLB',
            str_starts_with($sig, 'KB') || str_starts_with($sig, 'TPA') || str_starts_with($sig, 'TPQ') || str_starts_with($sig, 'SPS') || str_starts_with($sig, 'PPT') || str_starts_with($sig, 'RA') => 'PAUD',
            str_starts_with($sig, 'TK') => 'TK',
            str_starts_with($sig, 'MTS') => 'MTs',
            str_starts_with($sig, 'MIS') || str_starts_with($sig, 'MIN') || str_starts_with($sig, 'MI') => 'MI',
            str_starts_with($sig, 'MAN') || str_starts_with($sig, 'MA') => 'MA',
            str_starts_with($sig, 'SMP') => 'SMP',
            str_starts_with($sig, 'SMKN') || str_starts_with($sig, 'SMK') => 'SMK',
            str_starts_with($sig, 'SMAN') || str_starts_with($sig, 'SMA') => 'SMA',
            str_starts_with($sig, 'SDN') || str_starts_with($sig, 'SDIT') || str_starts_with($sig, 'SD') => 'SD',
            default => 'Lainnya',
        };
    }
}
