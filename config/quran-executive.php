<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ambang Kendali Executive Dashboard
    |--------------------------------------------------------------------------
    |
    | Angka ini belum dianggap sebagai target resmi semester. Nilai berikut
    | dipakai sebagai benchmark awal agar status departemen dapat ditampilkan
    | sebelum modul Target Semester dibuat.
    |
    */
    'thresholds' => [
        'coverage_good' => 85,
        'coverage_attention' => 70,
        'attendance_good' => 90,
        'attendance_attention' => 75,
        'alpha_rate_attention' => 5,
        'alpha_rate_critical' => 10,
        'setoran_delta_attention' => -5,
        'setoran_delta_critical' => -15,
    ],

    'risk' => [
        'alpha_minimum' => 3,
        'inactive_days' => 7,
    ],

    'comparison_days_for_semester' => 30,
];
