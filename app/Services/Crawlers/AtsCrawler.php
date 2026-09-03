<?php

namespace App\Services\Crawlers;

use App\Contracts\Crawl\RawRecord;
use App\Models\CrawlRun;

/**
 * Crawler untuk data Anak Tidak Sekolah (ATS) dari Pusdatin Kemendikdasmen.
 *
 * Temuan investigasi: data individu ATS berskala wilayah (kecamatan/desa)
 * DIBUTUHKAN sesion login (rangkuman/ats-by-wilayah -> 302, dan
 * get-data-dashboard/ats -> 404 tanpa sesion). Sesuai prinsip crawler yang
 * bertanggung jawab, modul ini TIDAK mencoba melewati autentikasi.
 *
 * Sumber publik yang bisa dipakai adalah dashboard Metabase publik:
 *   GET /api/public/dashboard/{dashboard}
 * yang berisi statistik ATS (total ATS, drop out, LTM, PD aktif kembali)
 * secara agregat. Statistik per wilayah disimpan sebagai data statistik,
 * sedangkan rincian per-individu tetap dilaporkan sebagai tidak tersedia.
 */
class AtsCrawler extends AbstractCrawler
{
    private const DASHBOARD_UUID = 'c94f14ac-05c4-4c30-a9b7-5fd8283ff928';

    public function source(): string
    {
        return 'ats';
    }

    protected function crawl(CrawlRun $run): array
    {
        $base = (string) config('crawler.sources.ats.base_url', 'https://dasbor.data.kemendikdasmen.go.id');

        $response = $this->http()->get(rtrim($base, '/').'/api/public/dashboard/'.self::DASHBOARD_UUID);

        if ($response->failed()) {
            $this->sync->recordError($this->source, $run, $response->reason() ?? 'Gagal mengambil dashboard ATS', $response->status(), $response->effectiveUri());

            return [];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            $this->sync->recordError($this->source, $run, 'Payload dashboard ATS tidak valid');

            return [];
        }

        // Ekstraksi agregat statistik nasional yang tersedia secara publik.
        $totals = $this->extractTotals($payload);

        $stats = [
            'dashboard_id' => self::DASHBOARD_UUID,
            'totals' => $totals,
            'granularity' => 'nasional-agregat',
            'note' => 'Rincian per-kecamatan/desa ATS berada di belakang autentikasi dan tidak diambil.',
        ];

        return [
            new RawRecord(
                externalId: 'ats-nasional-agregat',
                recordType: 'ats-statistik',
                name: 'Statistik ATS Nasional (publik)',
                provinceCode: '72',
                data: $stats,
                sourceUrl: $base.'/public/dashboard/'.self::DASHBOARD_UUID,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractTotals(array $payload): array
    {
        $cards = $payload['dashcards'] ?? [];

        $scalars = [];

        foreach ($cards as $card) {
            $visualSettings = $card['visualization_settings'] ?? [];
            $request = $card['card'] ?? null;

            $title = $visualSettings['card.title']
                ?? $request['name']
                ?? $card['id']
                ?? null;

            $value = $visualSettings['scalar_switcher'] ?? null;

            if ($title !== null && $value !== null) {
                $scalars[(string) $title] = $value;
            }
        }

        return [
            'scalar_cards' => $scalars,
            'cards' => collect($cards)->map(function ($card) {
                $request = $card['card'] ?? [];

                return [
                    'id' => $card['id'] ?? null,
                    'title' => $request['name'] ?? $card['visualization_settings']['card.title'] ?? null,
                    'display' => $request['display'] ?? null,
                ];
            })->filter()->values()->all(),
        ];
    }
}
