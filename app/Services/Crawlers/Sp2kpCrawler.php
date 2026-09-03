<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRun;
use Carbon\Carbon;

/**
 * Crawler harga kebutuhan pokok dari PIHPS (Pusat Informasi Harga Pangan
 * Strategis Nasional) Bank Indonesia:
 *
 *   https://www.bi.go.id/hargapangan
 *
 * PIHPS menyajikan harga harian komoditas pangan strategis per provinsi dan
 * kabupaten/kota melalui endpoint JSON publik tanpa autentikasi. Sumber ini
 * hanya mengekspos harga agregat Sulawesi Tengah (bukan khusus Morowali),
 * sehingga modul menyimpan harga per komoditas berskala provinsi dan menandai
 * rekor sebagai `skala provinsi`. Jika sumber memblokir atau mengubah skema,
 * modul mencatat error dan kembali tanpa menyimpan data parsial.
 */
class Sp2kpCrawler extends AbstractCrawler
{
    private const ENDPOINT = '/WebSite/Home/GetGridData1';

    public function source(): string
    {
        return 'sp2kp';
    }

    protected function crawl(CrawlRun $run): array
    {
        $base = rtrim((string) config('crawler.sources.sp2kp.base_url', 'https://www.bi.go.id/hargapangan'), '/');
        $provinceId = (int) config('crawler.sources.sp2kp.province_id', 28);
        $commodities = (array) config('crawler.sources.sp2kp.commodities', []);
        $date = Carbon::today();

        if ($commodities === []) {
            $this->sync->recordError($this->source, $run, 'Daftar komoditas PIHPS kosong pada konfigurasi.', 200, $base);

            return [];
        }

        $records = [];

        foreach ($commodities as $commodityId => $commodity) {
            $id = (int) $commodityId;
            $label = (string) $commodity['name'];

            $response = $this->http()->get($base.self::ENDPOINT, [
                'provId' => $provinceId,
                'regency_id' => '286',
                'tanggal' => $date->format('j M Y'),
                'priceType' => 1,
                'commodity' => $id,
                'isPasokan' => 1,
                'jenis' => 1,
                'periode' => 7,
            ]);

            if ($response->failed()) {
                $this->sync->recordError($this->source, $run, $response->reason() ?? 'Gagal mengakses data harga PIHPS.', $response->status(), $response->effectiveUri());
                $this->politeDelay();

                continue;
            }

            $priceRow = $this->firstPriceRow((string) $response->body());

            if ($priceRow === null) {
                $this->sync->recordError($this->source, $run, "PIHPS tidak mengembalikan harga untuk komoditas [{$label}].", $response->status(), $response->effectiveUri());
                $this->politeDelay();

                continue;
            }

            $records[] = new RawRecord(
                externalId: 'pihps-'.$id,
                recordType: 'harga-pangan',
                name: $label,
                provinceCode: '72',
                kabupatenName: 'Provinsi Sulawesi Tengah',
                kecamatanName: 'skala provinsi',
                data: [
                    'komoditas' => $label,
                    'harga' => $priceRow['nilai'],
                    'satuan' => 'Rp'.($commodity['unit'] ?? '/kg'),
                    'tanggal' => $priceRow['tanggal'],
                    'skala' => 'Provinsi Sulawesi Tengah',
                    'price_type' => 'Pasar Tradisional',
                ],
                sourceUrl: $base,
                sourceUpdatedAt: $date,
            );

            $this->politeDelay();
        }

        return $records;
    }

    /**
     * Extracts the first price row from the PIHPS JSON payload.
     *
     * @return array{nilai: float|int, tanggal: string}|null
     */
    private function firstPriceRow(string $json): ?array
    {
        $payload = json_decode($json, true);

        if (! is_array($payload) || ! isset($payload['data'][0])) {
            return null;
        }

        $row = $payload['data'][0];

        $nilai = $row['Nilai'] ?? $row['nilai'] ?? null;
        $tanggal = $row['Tanggal'] ?? $row['tanggal'] ?? null;

        if (! is_numeric($nilai) || $tanggal === null) {
            return null;
        }

        return [
            'nilai' => (float) $nilai,
            'tanggal' => (string) $tanggal,
        ];
    }
}
