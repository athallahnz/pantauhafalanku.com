<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mapping jenjang berbasis kode kanonis
    |--------------------------------------------------------------------------
    |
    | Controller menormalkan parent berdasarkan kode atau nama. Dengan begitu
    | "Kelas 10", "Kelas 10 Reg", dan "Kelas 10 Reguler" dibaca sebagai K10.
    | "Kelas 10 INT" dibaca sebagai K10I. Label database boleh berbeda.
    |
    */
    'transitions' => [
        'K07' => [
            'type' => 'naik_kelas',
            'targets' => ['K08'],
        ],
        'K08' => [
            'type' => 'naik_kelas',
            'targets' => ['K09'],
        ],
        'K09' => [
            'type' => 'naik_kelas',
            'targets' => ['K10', 'K10I'],
            'requires_choice' => true,
        ],
        'K10' => [
            'type' => 'naik_kelas',
            'targets' => ['K11'],
        ],
        'K10I' => [
            'type' => 'naik_kelas',
            'targets' => ['K11I'],
        ],
        'K11' => [
            'type' => 'naik_kelas',
            'targets' => ['K12'],
        ],
        'K11I' => [
            'type' => 'naik_kelas',
            'targets' => ['K12I'],
        ],
        'K12' => [
            'type' => 'lulus',
            'targets' => [],
        ],
        'K12I' => [
            'type' => 'lulus',
            'targets' => [],
        ],
    ],

    /*
     * Menjaga urutan kelompok lintas jenjang.
     * SMP alfabet dipetakan ke SMA numbering: A→1, B→2, C→3.
     */
    'preserve_group' => true,
    'snapshot_version' => 4,
];
