<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRun;
use Illuminate\Support\Arr;

/**
 * Crawler neraca statistik dari API Publik BPS (webapi.bps.go.id).
 *
 * API memerlukan kunci dari env BPS_API_KEY (tidak pernah di-hardcode).
 * Endpoint yang dipakai adalah /v1/api/list (model=data). Domain data BPS
 * untuk Kabupaten Morowali adalah 7203 (berbeda dari kode Kemendagri 7206);
 * pemetaan domain -> kode kanonik didefinisikan di config/crawler.php.
 */
class BpsCrawler extends AbstractCrawler
{
    public function source(): string
    {
        return 'bps';
    }

    protected function crawl(CrawlRun $run): array
    {
        $key = (string) config('services.bps.key');

        if ($key === '') {
            $this->sync->recordError($this->source, $run, 'BPS_API_KEY belum dikonfigurasi. Indikator BPS tidak dapat diambil.', null, null);

            return [];
        }

        $base = rtrim((string) config('services.bps.base_url', 'https://webapi.bps.go.id'), '/');

        $records = [];

        foreach ($this->indicatorSettings() as $var => $settings) {
            foreach ($this->bpsDomains() as $domain => $kode) {
                $period = $this->periodFor($var, $domain);
                $response = $this->http()->get("{$base}/v1/api/list", [
                    'model' => 'data',
                    'key' => $key,
                    'domain' => $domain,
                    'var' => $var,
                    'th' => $period,
                ]);

                if ($response->failed()) {
                    $this->sync->recordError($this->source, $run, $response->reason() ?? "Gagal mengambil indikator [{$var}] domain [{$domain}]", $response->status(), $response->effectiveUri());

                    continue;
                }

                $payload = $response->json();

                if (($payload['status'] ?? '') !== 'OK' || empty($payload['datacontent'])) {
                    $this->sync->recordError($this->source, $run, $payload['message'] ?? "Indikator [{$var}] domain [{$domain}] tidak mengembalikan data", $response->status(), $response->effectiveUri());

                    continue;
                }

                $label = (string) ($settings['label'] ?? "Indikator {$var}");

                $records[] = new RawRecord(
                    externalId: "bps-{$kode}-{$var}-{$period}",
                    recordType: 'statistik',
                    name: $label,
                    provinceCode: '72',
                    kabupatenCode: $kode,
                    kabupatenName: $this->regionName($kode),
                    data: [
                        'indicator' => $var,
                        'label' => $label,
                        'unit' => $settings['unit'] ?? null,
                        'domain_bps' => $domain,
                        'period' => $period,
                        'values' => $this->normalizeDatacontent($payload['datacontent']),
                    ],
                    sourceUrl: "{$base}/v1/api/list?model=data&domain={$domain}&var={$var}&th={$period}",
                );

                $this->politeDelay();
            }
        }

        return $records;
    }

    /**
     * Menentukan periode (th) untuk sebuah var di sebuah domain. Bila indikator
     * dikonfigurasi dengan latest_period, ambil periode terbaru dari API.
     */
    protected function periodFor(int|string $var, int|string $domain): int|string
    {
        $settings = $this->indicatorSettings();
        $fallback = (int) ($settings[$var]['period'] ?? 0);

        if (! (($settings[$var]['latest_period'] ?? false) === true)) {
            return $fallback;
        }

        $response = $this->http()->get(rtrim((string) config('services.bps.base_url', 'https://webapi.bps.go.id'), '/').'/v1/api/list', [
            'model' => 'th',
            'key' => (string) config('services.bps.key'),
            'domain' => $domain,
            'var' => $var,
        ]);

        if ($response->failed()) {
            return $fallback;
        }

        $payload = $response->json();
        $periods = Arr::get($payload, 'data.1', []);

        if (! is_array($periods) || empty($periods)) {
            return $fallback;
        }

        $latest = null;

        foreach ($periods as $entry) {
            if (! is_array($entry) || ! isset($entry['th_id'])) {
                continue;
            }

            if ($latest === null || (int) $entry['th_id'] > (int) $latest) {
                $latest = $entry['th_id'];
            }
        }

        return $latest ?? $fallback;
    }

    /**
     * Iterasi pemetaan BPS data-domain (key) -> kode kanonik (value).
     *
     * @return array<string, string>
     */
    protected function bpsDomains(): array
    {
        return (array) config('crawler.sources.bps.domains', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function indicatorSettings(): array
    {
        return (array) config('crawler.sources.bps.indicators', []);
    }

    private function normalizeDatacontent(mixed $datacontent): array
    {
        if (! is_array($datacontent)) {
            return [];
        }

        $values = [];

        foreach ($datacontent as $key => $value) {
            $values[(string) $key] = $value;
        }

        return $values;
    }

    private function regionName(string $kode): string
    {
        return (string) data_get(config('crawler.target_region.regions'), $kode, 'Kabupaten');
    }
}
