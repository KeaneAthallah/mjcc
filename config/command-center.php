<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Status Skor Keseluruhan Morowali
    |--------------------------------------------------------------------------
    |
    | Penyusunan status keseluruhan berbasis data nyata (score 0–100, semakin
    | tinggi semakin baik). Setiap sektor menyumbang bobot ke status umum.
    | Aturan-aturan ini transparan dan ditampilkan di antarmuka.
    |
    */

    // Urutan status (baik -> kritis) dan batas minimal skor.
    'status_order' => ['baik', 'waspada', 'perlu_perhatian', 'kritis'],

    'status_thresholds' => [
        'baik' => 85.0,
        'waspada' => 70.0,
        'perlu_perhatian' => 50.0,
        'kritis' => 0.0,
    ],

    'status_labels' => [
        'baik' => 'BAIK',
        'waspada' => 'WASPADA',
        'perlu_perhatian' => 'PERLU PERHATIAN',
        'kritis' => 'KRITIS',
        'tidak_ada_data' => 'BELUM ADA DATA',
    ],

    'overall' => [
        'label' => 'Status Keseluruhan Morowali',
        'weights' => [
            'pendidikan' => 34,
            'ketertiban' => 33,
            'kesehatan' => 33,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Aturan Skor per Sektor
    |--------------------------------------------------------------------------
    |
    | Setiap sektor terdiri dari beberapa aturan (rule) yang masing-masing
    | menghitung sub-skornya sendiri dari agregasi database, lalu digabungkan
    | dengan bobot yang tertera. Jika suatu aturan tidak memiliki data, skornya
    | dianggap kosong dan bobotnya dialihkan ke aturan lain yang tersedia.
    |
    | Tipe aturan yang didukung CommandCenterStatusService:
    |  - good_conditions    : persentase baris yang bernilai "baik"
    |  - has_staff          : persentase baris dengan tenaga (dokter+perawat+bidan) > 0
    |  - non_zero           : persentase baris dengan kolom > 0
    |  - active_status      : persentase baris dengan status = aktif
    |  - active_boolean     : persentase baris dengan is_active = true
    |  - average_percentages: rata-rata dari rata-rata kolom persentase sarana
    |
    */

    'sectors' => [
        'pendidikan' => [
            'label' => 'Pendidikan',
            'rules' => [
                'kondisi_gedung' => [
                    'label' => 'Kondisi gedung sekolah',
                    'weight' => 40,
                    'type' => 'good_conditions',
                    'scope' => 'schools',
                    'column' => 'condition',
                ],
                'kelengkapan_sarana' => [
                    'label' => 'Kelengkapan sarana sekolah',
                    'weight' => 30,
                    'type' => 'average_percentages',
                    'scope' => 'schools',
                    'columns' => [
                        'library_percentage',
                        'science_lab_percentage',
                        'computer_lab_percentage',
                        'teacher_room_percentage',
                        'toilet_percentage',
                        'worship_room_percentage',
                    ],
                ],
                'ketersediaan_guru' => [
                    'label' => 'Sekolah dengan guru',
                    'weight' => 30,
                    'type' => 'non_zero',
                    'scope' => 'schools',
                    'column' => 'teachers',
                ],
            ],
        ],
        'ketertiban' => [
            'label' => 'Ketertiban',
            'rules' => [
                'poskamling_aktif' => [
                    'label' => 'Poskamling aktif',
                    'weight' => 35,
                    'type' => 'active_boolean',
                    'scope' => 'poskamlings',
                    'column' => 'is_active',
                ],
                'tipkamtikmas_aktif' => [
                    'label' => 'Tipkamtikmas aktif',
                    'weight' => 25,
                    'type' => 'active_status',
                    'scope' => 'tipkamtikmas',
                    'column' => 'status',
                ],
                'polsek_aktif' => [
                    'label' => 'Polsek aktif',
                    'weight' => 20,
                    'type' => 'active_status',
                    'scope' => 'polseks',
                    'column' => 'status',
                ],
                'pasar_aktif' => [
                    'label' => 'Pasar aktif',
                    'weight' => 20,
                    'type' => 'active_status',
                    'scope' => 'markets',
                    'column' => 'status',
                ],
            ],
        ],
        'kesehatan' => [
            'label' => 'Kesehatan',
            'rules' => [
                'faskes_aktif' => [
                    'label' => 'Fasilitas kesehatan aktif',
                    'weight' => 30,
                    'type' => 'active_status',
                    'scope' => 'health_facilities',
                    'column' => 'status',
                ],
                'ketersediaan_tenaga' => [
                    'label' => 'Faskes yang memiliki tenaga medis',
                    'weight' => 40,
                    'type' => 'has_staff',
                    'scope' => 'health_facilities',
                ],
                'kondisi_faskes' => [
                    'label' => 'Kondisi fasilitas kesehatan',
                    'weight' => 30,
                    'type' => 'good_conditions',
                    'scope' => 'health_facilities',
                    'column' => 'condition',
                ],
            ],
        ],
    ],

    // Nilai kondisi yang dianggap "baik".
    'good_conditions' => ['baik', 'bagus', 'layak', 'rusak ringan'],

    /*
    |--------------------------------------------------------------------------
    | Kesehatan Data (Data Freshness)
    |--------------------------------------------------------------------------
    |
    | Status kesegaran data dihitung dari waktu pembaruan terakhir data nyata,
    | bukan dari nilai yang dikarang.
    |
    */

    'freshness' => [
        'terbaru_days' => 7,
        'perlu_diperbarui_days' => 30,
        'labels' => [
            'terbaru' => 'TERBARU',
            'perlu_diperbarui' => 'PERLU DIPERBARUI',
            'data_lama' => 'DATA LAMA',
            'tidak_ada_data' => 'BELUM ADA DATA',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sistem Alert Operasional
    |--------------------------------------------------------------------------
    |
    | Alert yang terdeteksi disinkronkan ke tabel command_alerts agar dapat
    | ditindaklanjuti (status rangkaian kerja) dan ditautkan ke peta.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Dashboard Komando (Live Auto-Refresh)
    |--------------------------------------------------------------------------
    |
    | Dashboard memuat ulang data secara otomatis pada interval tertentu agar
    | tetap "live" tanpa perlu menekan tombol refresh manual. Preferensi tiap
    | operator disimpan di localStorage; klik kontrol di header untuk
    | menjeda/mengaktifkan kembali.
    |
    */

    'dashboard' => [
        // Interval auto-refresh dalam detik (minimum diberlakukan 10 dtk).
        'live_refresh_seconds' => 300,
        // Kondisi awal auto-refresh saat belum ada preferensi tersimpan.
        'live_refresh_default' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Data agregasi yang berat (misal peta gabungan) di-cache sementara agar
    | akses berulang tidak membanjiri basis data.
    |
    */

    'cache' => [
        'map_ttl' => 60,
    ],

    'alerts' => [
        'statuses' => [
            'baru' => 'BARU',
            'ditinjau' => 'DITINJAU',
            'ditangani' => 'DITANGANI',
            'selesai' => 'SELESAI',
        ],
        'severities' => [
            'critical' => 'CRITICAL',
            'warning' => 'WARNING',
            'info' => 'INFO',
        ],
        // Rentang waktu sinkronisasi deteksi dijalankan (dalam detik).
        'sync_ttl' => 60,
        // Apabila kondisi terdeteksi sudah hilang, alert terbuka akan
        // otomatis ditandai selesai.
        'auto_resolve' => true,
    ],
];
