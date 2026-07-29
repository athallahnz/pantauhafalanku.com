<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Struktur tingkat dan kelompok akademik
    |--------------------------------------------------------------------------
    |
    | SMP (kelas 7-9) menggunakan kelompok alfabet A, B, C.
    | SMA (kelas 10-12, Reg maupun INT) menggunakan numbering 1, 2, 3.
    |
    | Key memakai kode kanonis agar label parent di database boleh berupa
    | "Kelas 10", "Kelas 10 Reg", atau "Kelas 10 Reguler".
    |
    */
    'parents' => [
        'K07' => [
            'names' => ['Kelas 7'],
            'code' => 'K07',
            'order' => 10,
            'group_mode' => 'alpha',
            'groups' => ['A', 'B', 'C'],
        ],
        'K08' => [
            'names' => ['Kelas 8'],
            'code' => 'K08',
            'order' => 20,
            'group_mode' => 'alpha',
            'groups' => ['A', 'B', 'C'],
        ],
        'K09' => [
            'names' => ['Kelas 9'],
            'code' => 'K09',
            'order' => 30,
            'group_mode' => 'alpha',
            'groups' => ['A', 'B', 'C'],
        ],
        'K10' => [
            'names' => ['Kelas 10 Reg', 'Kelas 10 Reguler', 'Kelas 10'],
            'code' => 'K10',
            'order' => 40,
            'group_mode' => 'numeric',
            'groups' => ['1', '2'],
        ],
        'K10I' => [
            'names' => ['Kelas 10 INT', 'Kelas 10 Int'],
            'code' => 'K10I',
            'order' => 50,
            'group_mode' => 'numeric',
            'groups' => ['1', '2'],
        ],
        'K11' => [
            'names' => ['Kelas 11 Reg', 'Kelas 11 Reguler', 'Kelas 11'],
            'code' => 'K11',
            'order' => 60,
            'group_mode' => 'numeric',
            'groups' => ['1', '2'],
        ],
        'K11I' => [
            'names' => ['Kelas 11 INT', 'Kelas 11 Int'],
            'code' => 'K11I',
            'order' => 70,
            'group_mode' => 'numeric',
            'groups' => ['1', '2'],
        ],
        'K12' => [
            'names' => ['Kelas 12 Reg', 'Kelas 12 Reguler', 'Kelas 12'],
            'code' => 'K12',
            'order' => 80,
            'group_mode' => 'numeric',
            'groups' => ['1', '2'],
        ],
        'K12I' => [
            'names' => ['Kelas 12 INT', 'Kelas 12 Int'],
            'code' => 'K12I',
            'order' => 90,
            'group_mode' => 'numeric',
            'groups' => ['1', '2'],
        ],
    ],

    'group_name_pattern' => '{parent} {group}',
    'group_code_pattern' => '{parent_code}-{group}',
    'group_order_step' => 1,
];
