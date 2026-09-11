<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Publik Config
    |--------------------------------------------------------------------------
    |
    | Central configuration for the Data Publik module. Manages multiple
    | public data sources including Satu Data Morowali, ATS, Dapodik,
    | SP2KP, BPS, IRBI, Sitaba, and APBD.
    |
    */

    'enabled' => env('PUBLIC_DATA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Settings
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => env('PUBLIC_DATA_HTTP_TIMEOUT', 45),
        'connect_timeout' => env('PUBLIC_DATA_HTTP_CONNECT_TIMEOUT', 10),
        'user_agent' => env('PUBLIC_DATA_USER_AGENT', 'MorowaliJuaraCommandCenterBot/3.0 (+public-data-sync)'),
        'delay_ms' => env('PUBLIC_DATA_DELAY_MS', 200),
    ],

    'schedule' => env('PUBLIC_DATA_SCHEDULE', '03:00'),

    'cache_ttl' => env('PUBLIC_DATA_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Satu Data Morowali source (backward compatible)
    |--------------------------------------------------------------------------
    */
    'satudata' => [
        'base_url' => env('PUBLIC_DATA_SATUDATA_URL', 'https://data.morowalikab.go.id'),
        'catalog_path' => '/dataset',
        'detail_path' => '/dataset/detail',
        'max_pages' => (int) env('PUBLIC_DATA_SATUDATA_MAX_PAGES', 85),
    ],

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

    /*
    |--------------------------------------------------------------------------
    | Source Categories
    |--------------------------------------------------------------------------
    |
    | Categories used for the sidebar navigation dropdown grouping.
    |
    */
    'categories' => [
        'pendidikan' => [
            'label' => 'Pendidikan',
            'icon' => '🎓',
            'description' => 'Data pendidikan dan pendidikan anak',
        ],
        'pemantauan' => [
            'label' => 'Pemantauan',
            'icon' => '📡',
            'description' => 'Pemantauan data sektoral dan publik',
        ],
        'kebencanaan' => [
            'label' => 'Kebencanaan',
            'icon' => '⚠️',
            'description' => 'Risiko dan kejadian bencana',
        ],
        'apbd' => [
            'label' => 'APBD',
            'icon' => '💰',
            'description' => 'Monitoring Anggaran Pendapatan dan Belanja Daerah',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Anak Tidak Sekolah (ATS) Source
    |--------------------------------------------------------------------------
    */
    'ats' => [
        'url' => env('PUBLIC_DATA_ATS_URL', 'https://ats.data.kemendikdasmen.go.id'),
        'region_code' => env('PUBLIC_DATA_ATS_REGION_CODE', '180700'),
        'region_name' => 'Kabupaten Morowali',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dapodik Source
    |--------------------------------------------------------------------------
    */
    'dapodik' => [
        'url' => env('PUBLIC_DATA_DAPODIK_URL', 'https://dapo.kemendikdasmen.go.id'),
        'region_code' => env('PUBLIC_DATA_DAPODIK_REGION_CODE', '180700'),
        'region_name' => 'Kabupaten Morowali',
        /*
         * Per-school snapshot written by scripts/dapodik/dapodik-capture.cjs.
         * The portal WAF rejects plain HTTP clients for the school endpoints,
         * so real rows are captured through a real browser and imported from
         * that JSON file by the `public-data:dapodik-import` command.
         */
        'snapshot' => storage_path('app/data/dapodik/morowali-schools.json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kemkes SISDMK (Puskesmas) Source
    |--------------------------------------------------------------------------
    |
    | SATUSEHAT Perencanaan Tenaga Medis dan Tenaga Kesehatan publishes a
    | per-puskesmas table (nama, jenis, dan isi SDM) that is reachable via plain
    | HTTP. Used to import real Puskesmas rows into the health master table.
    |
    */
    'kemkes' => [
        'provider_url' => env('PUBLIC_DATA_KEMKES_URL', 'https://dreams.kemkes.go.id/user/kekosongan_dev/SKMPKM/prov/{province}/kab/{region}'),
        'province_code' => env('PUBLIC_DATA_KEMKES_PROVINCE', '72'),
        'region_code' => env('PUBLIC_DATA_KEMKES_REGION_CODE', '7206'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Entity Replacement
    |--------------------------------------------------------------------------
    |
    | Real per-entity master data captured from reachable official sources and
    | imported by `public-data:master-replace`. The `--capture` option refreshes
    | these snapshots from the network first; otherwise the command imports the
    | saved snapshot files so a scheduled run never needs live connectivity.
    |
    */
    'master' => [
        'dir' => storage_path('app/data/morowali'),
        'snapshots' => [
            'puskesmas' => storage_path('app/data/morowali/kemkes-puskesmas.json'),
            'markets' => storage_path('app/data/morowali/sp2kp-markets.json'),
        ],
        'source_health' => 'kemkes',
        'source_market' => 'sp2kp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Wilayah Kerja
    |--------------------------------------------------------------------------
    |
    | Command Center hanya mengelola Kabupaten Morowali. Kecamatan di luar
    | daftar ini (misalnya kecamatan Morowali Utara) dianggap di luar jangkauan,
    | tidak diimpor, dan dibersihkan dari tabel master.
    |
    */
    'morowali' => [
        'kecamatan' => [
            'Bahodopi',
            'Bumi Raya',
            'Bungku Barat',
            'Bungku Pesisir',
            'Bungku Selatan',
            'Bungku Tengah',
            'Bungku Timur',
            'Menui Kepulauan',
            'Sombori Kepulauan',
            'Wita Ponda',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SP2KP (Pasar & Kebutuhan Pokok) Source
    |--------------------------------------------------------------------------
    */
    'sp2kp' => [
        'url' => env('PUBLIC_DATA_SP2KP_URL', 'https://sp2kp.kemendag.go.id'),
        'api_base' => env('PUBLIC_DATA_SP2KP_API_URL', 'https://api-sp2kp.kemendag.go.id'),
        'region_code' => env('PUBLIC_DATA_SP2KP_REGION_CODE', '7206'),
        'province_code' => env('PUBLIC_DATA_SP2KP_PROVINCE', '72'),
        'region_name' => 'Kabupaten Morowali',
        'market_id' => (int) env('PUBLIC_DATA_SP2KP_MARKET_ID', 606),
        'market_name' => env('PUBLIC_DATA_SP2KP_MARKET_NAME', 'Pasar Rakyat Bungku Tengah'),
        'max_variants' => (int) env('PUBLIC_DATA_SP2KP_MAX_VARIANTS', 6),
        'months_back' => (int) env('PUBLIC_DATA_SP2KP_MONTHS_BACK', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | BPS (Badan Pusat Statistik) Source
    |--------------------------------------------------------------------------
    */
    'bps' => [
        'app_id' => env('BPS_APP_ID', ''),
        'base_url' => env('BPS_API_BASE_URL', 'https://webapi.bps.go.id'),
        'region_code' => env('BPS_REGION_CODE', '7203'),
        'region_name' => env('BPS_REGION_NAME', 'Kabupaten Morowali'),
        'province_code' => env('BPS_PROVINCE_CODE', '72'),
        'max_vars' => (int) env('BPS_MAX_VARS', 30),
        'timeout' => (int) env('BPS_API_TIMEOUT', 30),
        'retry_count' => (int) env('BPS_API_RETRY', 3),
        'retry_delay_ms' => (int) env('BPS_API_RETRY_DELAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | IRBI (Indeks Risiko Bencana Indonesia) Source
    |--------------------------------------------------------------------------
    */
    'irbi' => [
        'url' => env('PUBLIC_DATA_IRBI_URL', 'https://inarisk.bnpb.go.id'),
        'region_code' => env('PUBLIC_DATA_IRBI_REGION_CODE', '72.06'),
        'province_code' => env('PUBLIC_DATA_IRBI_PROVINCE', '72'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitaba (Bencana Terkini) Source
    |--------------------------------------------------------------------------
    */
    'sitaba' => [
        'url' => env('PUBLIC_DATA_SITABA_URL', 'https://sitaba.pu.go.id/bencana-terkini'),
        'api_base' => env('PUBLIC_DATA_SITABA_API_URL', 'https://sitaba.pu.go.id'),
        'region_name' => 'Sulawesi Tengah',
        'province' => 'Sulawesi Tengah',
        'search' => env('PUBLIC_DATA_SITABA_SEARCH', 'MOROWALI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | APBD (DJPK Kemenkeu) Source
    |--------------------------------------------------------------------------
    */
    'apbd' => [
        'base_url' => env('PUBLIC_DATA_APBD_BASE_URL', 'https://djpk.kemenkeu.go.id'),
        'url' => env('PUBLIC_DATA_APBD_URL', '/portal/data/tkdd'),
        'region_code' => env('PUBLIC_DATA_APBD_REGION_CODE', '72.03'),
        'region_name' => 'Kabupaten Morowali',
        'province_code' => env('PUBLIC_DATA_APBD_PROVINCE', '19'),
        'pemda_code' => env('PUBLIC_DATA_APBD_PEMDA', '06'),
    ],

];
