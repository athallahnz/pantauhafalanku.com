<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versi Struktur Snapshot
    |--------------------------------------------------------------------------
    */

    'snapshot_schema_version' => 'raport-snapshot-v2',

    /*
    |--------------------------------------------------------------------------
    | Konversi Nilai
    |--------------------------------------------------------------------------
    */

    'nilai_map' => [
        'mumtaz' => 95,
        'jayyid_jiddan' => 85,
        'jayyid' => 75,
        'mardud' => 65,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tahap Hafalan
    |--------------------------------------------------------------------------
    */

    'hafalan_tahap_rank' => [
        'harian' => 1,
        'tahap_1' => 2,
        'tahap_2' => 3,
        'tahap_3' => 4,
        'ujian_akhir' => 5,
    ],

    'hafalan_tahap_weight' => [
        'harian' => 20,
        'tahap_1' => 40,
        'tahap_2' => 60,
        'tahap_3' => 80,
        'ujian_akhir' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Buku Tahsin
    |--------------------------------------------------------------------------
    */

    'tahsin_books' => [
        'ummi_1' => [
            'label' => 'Ummi Jilid 1',
            'max' => 40,
        ],
        'ummi_2' => [
            'label' => 'Ummi Jilid 2',
            'max' => 40,
        ],
        'ummi_3' => [
            'label' => 'Ummi Jilid 3',
            'max' => 40,
        ],
        'gharib_1' => [
            'label' => 'Gharib 1',
            'max' => 28,
        ],
        'gharib_2' => [
            'label' => 'Gharib 2',
            'max' => 28,
        ],
        'tajwid' => [
            'label' => 'Tajwid',
            'max' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Indeks Progress
    |--------------------------------------------------------------------------
    |
    | Indeks ini bukan nilai Raport final dan bukan predikat akademik.
    | Nilainya hanya ringkasan progress untuk membantu proses review.
    |
    */

    'progress_index' => [
        'weights' => [
            'hafalan' => 50,
            'tahsin' => 25,
            'tilawah' => 15,
            'discipline' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Predikat Otomatis
    |--------------------------------------------------------------------------
    |
    | Dinonaktifkan sampai lembaga menyepakati kebijakan penilaian resmi.
    |
    */

    'raport_predicate' => [
        'enabled' => false,

        'thresholds' => [
            [
                'min' => 90,
                'label' => 'Sangat Baik',
            ],
            [
                'min' => 80,
                'label' => 'Baik',
            ],
            [
                'min' => 70,
                'label' => 'Cukup',
            ],
            [
                'min' => 0,
                'label' => 'Perlu Pembinaan',
            ],
        ],
    ],
];
