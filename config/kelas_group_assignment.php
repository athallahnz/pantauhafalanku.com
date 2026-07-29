<?php

return [
    /*
     * Mapping default Tahap 4.
     *
     * Key menggunakan kode kelas induk. Value dapat berupa:
     * - huruf kelompok, contoh "A";
     * - kode child, contoh "K07-A";
     * - nama child, contoh "Kelas 7 A";
     * - ID child.
     *
     * Saat ini setiap kelas induk hanya mempunyai kelompok A.
     */
    'mapping' => [
        'K07' => 'A',
        'K08' => 'A',
        'K09' => 'A',
        'K10' => 'A',
        'K10I' => 'A',
        'K11' => 'A',
        'K11I' => 'A',
    ],

    /*
     * Jika mapping tidak ditulis dan parent hanya memiliki satu child aktif,
     * child tersebut boleh dipilih otomatis.
     */
    'allow_single_active_child' => true,

    /*
     * Dibiarkan false agar sistem tidak menebak ketika parent memiliki
     * lebih dari satu kelompok aktif.
     */
    'allow_default_group_when_multiple' => false,

    'default_group' => 'A',

    /*
     * Placement semester aktif wajib tersedia untuk setiap santri aktif
     * yang akan dipindahkan. Ini mencegah current projection dan histori
     * semester menjadi tidak sinkron.
     */
    'require_active_semester_placement' => true,

    /*
     * Musyrif tanpa kelas dapat diinferensikan dari seluruh santri aktif
     * binaannya apabila semuanya berasal dari satu kelas induk yang sama.
     */
    'infer_unassigned_musyrif_from_students' => true,

    'batch_prefix' => 'KGA',
];
