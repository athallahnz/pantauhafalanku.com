<?php

namespace Database\Seeders;

use App\Models\Kelas;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama_kelas' => 'Kelas Tahfidz 1', 'deskripsi' => 'Kelas awal tahfidz pemula'],
            ['nama_kelas' => 'Kelas Tahfidz 2', 'deskripsi' => 'Lanjutan tahfidz tingkat menengah'],
            ['nama_kelas' => 'Kelas Takhassus', 'deskripsi' => 'Kelas fokus hafalan intensif'],
        ];

        foreach ($data as $item) {
            Kelas::updateOrCreate(
                ['nama_kelas' => $item['nama_kelas']],
                $item
            );
        }
    }
}
