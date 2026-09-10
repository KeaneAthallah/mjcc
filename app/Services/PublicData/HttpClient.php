<?php

namespace App\Services\PublicData;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared HTTP client for public data adapters with responsible crawling settings.
 */
class HttpClient
{
    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::timeout(config('public_data.http.timeout', 45))
            ->connectTimeout(config('public_data.http.connect_timeout', 10))
            ->withHeaders([
                'User-Agent' => config('public_data.http.user_agent', 'MorowaliJCC/3.0'),
                'Accept' => 'application/json, text/html, */*',
            ])
            ->retry(2, 500);
    }

    public function get(string $url, array $query = [], array $headers = [], bool $verify = true): Response
    {
        $this->throttle();

        Log::debug('PublicData HTTP GET', ['url' => $url, 'query' => $query]);

        return $this->request($verify)->withHeaders($headers)->get($url, $query);
    }

    public function post(string $url, array $data = [], array $headers = [], bool $verify = true): Response
    {
        $this->throttle();

        Log::debug('PublicData HTTP POST', ['url' => $url]);

        return $this->request($verify)->withHeaders($headers)->post($url, $data);
    }

    public function postForm(string $url, array $data = [], array $headers = [], bool $verify = true): Response
    {
        $this->throttle();

        Log::debug('PublicData HTTP POST form', ['url' => $url]);

        return $this->request($verify)->asForm()->withHeaders($headers)->post($url, $data);
    }

    private function request(bool $verify): PendingRequest
    {
        return $verify ? $this->client : $this->client->withoutVerifying();
    }

    private function throttle(): void
    {
        $delay = config('public_data.http.delay_ms', 200);

        if ($delay > 0) {
            usleep($delay * 1000);
        }
    }
}
