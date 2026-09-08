<?php

namespace App\Services\PublicData;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

/**
 * Fetches public HTML pages from the Satu Data Morowali portal with
 * responsible-crawling settings (timeouts, descriptive user agent, small
 * inter-request delay). No deeper contract: callers only receive the body.
 */
class SatuDataClient
{
    public function __construct(private readonly Http $http) {}

    public function baseUrl(): string
    {
        return rtrim((string) config('public_data.satudata.base_url'), '/');
    }

    public function catalogUrl(int $page): string
    {
        return $this->baseUrl().(string) config('public_data.satudata.catalog_path').'?page='.$page;
    }

    /**
     * @return string The raw HTML body of the catalog page.
     */
    public function catalogPage(int $page): string
    {
        return $this->fetch($this->catalogUrl($page));
    }

    /**
     * @return string The raw HTML body of a dataset detail page.
     */
    public function detail(string $url): string
    {
        return $this->fetch($url);
    }

    /**
     * @throws RuntimeException on unrecoverable transport/HTTP failures
     */
    private function fetch(string $url): string
    {
        $configured = config('public_data.http');
        $delayMs = (int) ($configured['delay_ms'] ?? 200);

        try {
            $response = $this->http
                ->withHeaders([
                    'User-Agent' => (string) ($configured['user_agent'] ?? 'MorowaliJuaraCommandCenterBot/2.0 (+public-data-sync)'),
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->timeout((int) ($configured['timeout'] ?? 45))
                ->connectTimeout((int) ($configured['connect_timeout'] ?? 10))
                ->retry(3, 500)
                ->get($url);

            $response->throw();

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }

            return $response->body();
        } catch (RequestException $e) {
            throw new RuntimeException(
                sprintf('HTTP %d saat mengambil %s: %s', (int) $e->response->status(), $url, $e->getMessage()),
                0,
                $e,
            );
        } catch (\Throwable $e) {
            throw new RuntimeException(sprintf('Gagal mengambil %s: %s', $url, $e->getMessage()), 0, $e);
        }
    }
}
