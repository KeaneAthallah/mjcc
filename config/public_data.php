<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Publik Config
    |--------------------------------------------------------------------------
    |
    | Central configuration for the Data Publik module, which ingests public
    | statistics from the Satu Data Morowali portal (data.morowalikab.go.id)
    | for the Education, Health, and Security sectors. Environment values are
    | read here only; no API keys are required (public pages only).
    |
    */

    'enabled' => env('PUBLIC_DATA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    |
    | Responsible scraping: honor timeouts, set a descriptive user agent, and
    | space requests to avoid hammering the government portal.
    |
    */
    'http' => [
        'timeout' => env('PUBLIC_DATA_HTTP_TIMEOUT', 45),
        'connect_timeout' => env('PUBLIC_DATA_HTTP_CONNECT_TIMEOUT', 10),
        'user_agent' => env('PUBLIC_DATA_USER_AGENT', 'MorowaliJuaraCommandCenterBot/2.0 (+public-data-sync)'),
        'delay_ms' => env('PUBLIC_DATA_DELAY_MS', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    */
    'schedule' => env('PUBLIC_DATA_SCHEDULE', '03:00'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | How long the catalog/discovery results and UI summaries are cached.
    |
    */
    'cache_ttl' => env('PUBLIC_DATA_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Satu Data Morowali source
    |--------------------------------------------------------------------------
    |
    | The portal is paginated at 10 items per page. `max_pages` bounds the
    | catalog walk even if the portal grows beyond the verified 85 pages.
    |
    */
    'satudata' => [
        'base_url' => env('PUBLIC_DATA_SATUDATA_URL', 'https://data.morowalikab.go.id'),
        'catalog_path' => '/dataset',
        'detail_path' => '/dataset/detail',
        'max_pages' => (int) env('PUBLIC_DATA_SATUDATA_MAX_PAGES', 85),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sectors
    |--------------------------------------------------------------------------
    |
    | Each sector maps to the topic labels as published on the portal under the
    | "Bidang" facet. The scraping walk matches dataset topics against these
    | labels (case-insensitive).
    |
    | Verified availability (2026-09-08): Pendidikan = 50 datasets,
    | Kesehatan = 121, Kesbangpol = 2, Penanggulangan Bencana = 42, Trantibum =
    | 0. Because no trantibum/police/crime datasets are published, the Security
    | sector tracks what IS available: Kesbangpol + BPBD (perlindungan
    | masyarakat) + trantibum stays registered so future data is caught too.
    |
    */
    'sectors' => [
        'pendidikan' => [
            'label' => 'Pendidikan',
            'topics' => ['Bidang Pendidikan'],
        ],
        'kesehatan' => [
            'label' => 'Kesehatan',
            'topics' => ['Bidang Kesehatan'],
        ],
        'keamanan' => [
            'label' => 'Keamanan',
            'topics' => [
                'Bidang Ketentraman dan Ketertiban Umum Serta Perlindungan Masyarakat',
                'Bidang Kesatuan Bangsa dan Politik',
                'Bidang Penanggulangan Bencana',
            ],
        ],
    ],

];
