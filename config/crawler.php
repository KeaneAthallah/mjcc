<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Crawler Config
    |--------------------------------------------------------------------------
    |
    | Central configuration for the government data crawler module. This module
    | only ingests data for Kabupaten Morowali (7206) and Kabupaten Morowali
    | Utara (7212). Environment values are read here only.
    |
    */

    'enabled' => env('CRAWLER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    |
    | Responsible crawling: honor timeouts, set a descriptive user agent, and
    | space requests to avoid hammering government servers.
    |
    */
    'http' => [
        'timeout' => env('CRAWLER_HTTP_TIMEOUT', 45),
        'connect_timeout' => env('CRAWLER_HTTP_CONNECT_TIMEOUT', 10),
        'user_agent' => env('CRAWLER_USER_AGENT', 'MorowaliJuaraCommandCenterBot/1.0 (+government-data-sync)'),
        'base_delay_ms' => env('CRAWLER_BASE_DELAY_MS', 700),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    */
    'queue' => env('CRAWLER_QUEUE', 'crawler'),
    'jobs_per_source' => env('CRAWLER_JOBS_PER_SOURCE', 10),

    /*
    |--------------------------------------------------------------------------
    | Schedule
    |--------------------------------------------------------------------------
    |
    | Waktu harian (HH:MM, waktu lokal server) untuk masing-masing sumber.
    |
    */
    'schedule' => [
        'ats' => env('CRAWLER_SCHEDULE_ATS', '01:00'),
        'dapo' => env('CRAWLER_SCHEDULE_DAPO', '01:30'),
        'sp2kp' => env('CRAWLER_SCHEDULE_SP2KP', '02:00'),
        'bps' => env('CRAWLER_SCHEDULE_BPS', '02:30'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Source definitions
    |--------------------------------------------------------------------------
    |
    | Each connector reads its base URL and enabled flag from here. Specific
    | per-source options are documented under each entry.
    |
    */
    'sources' => [
        'kesehatan' => [
            'name' => 'Fasilitas Kesehatan',
            'source_label' => 'Kemenkes Fasyankes',
            'description' => 'Data fasilitas kesehatan (puskesmas, pustu, RS, posyandu) dari Kementerian Kesehatan RI untuk wilayah Kabupaten Morowali dan Morowali Utara.',
            'icon' => '🏥',
            'base_url' => env('KESEHATAN_BASE_URL', 'https://api-kfakes.kemkes.go.id/v1/api/fasyankes'),
            'enabled' => env('KESEHATAN_CRAWLER_ENABLED', true),
            'province_code' => '72',
            'kabupaten_codes' => [
                '7206' => 'Kabupaten Morowali',
                '7212' => 'Kabupaten Morowali Utara',
            ],
        ],
        'ats' => [
            'name' => 'Anak Tidak Sekolah',
            'source_label' => 'ATS Kemendikdasmen',
            'description' => 'Data anak tidak sekolah dari Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi.',
            'icon' => '👥',
            'base_url' => env('ATS_BASE_URL', 'https://dasbor.data.kemendikdasmen.go.id'),
            'enabled' => env('ATS_CRAWLER_ENABLED', true),
        ],
        'dapo' => [
            'name' => 'Data Pokok Pendidikan',
            'source_label' => 'DAPO Kemendikdasmen',
            'description' => 'Data pokok satuan pendidikan (sekolah) dari Portal Data Referensi Kemendikdasmen.',
            'icon' => '🏫',
            'base_url' => env('DAPO_BASE_URL', 'https://referensi.data.kemendikdasmen.go.id'),
            'enabled' => env('DAPO_CRAWLER_ENABLED', true),
        ],
        'sp2kp' => [
            'name' => 'Harga Pangan PIHPS',
            'source_label' => 'PIHPS Bank Indonesia',
            'description' => 'Harga harian komoditas pangan strategis (skala provinsi Sulawesi Tengah) dari Pusat Informasi Harga Pangan Strategis Nasional Bank Indonesia.',
            'icon' => '🏪',
            'base_url' => env('SP2KP_BASE_URL', 'https://www.bi.go.id/hargapangan'),
            'enabled' => env('SP2KP_CRAWLER_ENABLED', true),
            /*
             | PIHPS hanya mengekspos harga agregat per provinsi untuk Sulawesi
             | Tengah (province_id 28). Komoditas diambil satu per satu dari
             | endpoint JSON publiknya; nilai tersimpan berskala provinsi.
             */
            'province_id' => 28,
            'region_label' => 'Sulawesi Tengah',
            'commodities' => [
                1 => ['name' => 'Beras', 'unit' => '/kg'],
                2 => ['name' => 'Daging Ayam', 'unit' => '/kg'],
                3 => ['name' => 'Daging Sapi', 'unit' => '/kg'],
                4 => ['name' => 'Telur Ayam', 'unit' => '/kg'],
                5 => ['name' => 'Bawang Merah', 'unit' => '/kg'],
                6 => ['name' => 'Bawang Putih', 'unit' => '/kg'],
                7 => ['name' => 'Cabai Merah', 'unit' => '/kg'],
                8 => ['name' => 'Cabai Rawit', 'unit' => '/kg'],
                9 => ['name' => 'Minyak Goreng', 'unit' => '/liter'],
                10 => ['name' => 'Gula Pasir', 'unit' => '/kg'],
            ],
        ],
        'bps' => [
            'name' => 'Badan Pusat Statistik',
            'source_label' => 'BPS',
            'description' => 'Indikator statistik wilayah Morowali & Morowali Utara dari Web API BPS.',
            'icon' => '📊',
            'base_url' => env('BPS_API_BASE_URL', 'https://webapi.bps.go.id'),
            'key' => env('BPS_API_KEY'),
            'enabled' => env('BPS_CRAWLER_ENABLED', true),
            /*
             | BPS Web-API data-domain codes differ from Kemendagri codes.
             | Morowali is 7203 on BPS (URL morowalikab.bps.go.id), Morowali
             | Utara is 7212. Key   : BPS data-domain used in HTTP requests.
             | Value                      : canonical kemendagri code persisted.
             */
            'domains' => [
                '7203' => '7206',
                '7212' => '7212',
            ],
            /*
             | Verified indicators present (non-empty datacontent) in BOTH
             | target domains (7203 = Morowali, 7212 = Morowali Utara). Var 102
             | is Tingkat Partisipasi Angkatan Kerja (TPAK); var 114 is Jumlah
             | Penduduk Yang Bekerja. latest_period auto-resolves the newest th
             | via model=th, with 'period' as the fallback when unavailable.
             */
            'indicators' => [
                '102' => [
                    'label' => 'Tingkat Partisipasi Angkatan Kerja (TPAK)',
                    'unit' => 'Persen',
                    'latest_period' => true,
                    'period' => 125,
                ],
                '114' => [
                    'label' => 'Jumlah Penduduk yang Bekerja',
                    'unit' => 'Jiwa',
                    'latest_period' => true,
                    'period' => 123,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Target regions (canonical BPS codes)
    |--------------------------------------------------------------------------
    */
    'target_region' => [
        'province_code' => '72',
        'province_name' => 'Sulawesi Tengah',
        'regions' => [
            '7206' => 'Kabupaten Morowali',
            '7212' => 'Kabupaten Morowali Utara',
        ],
    ],

];
