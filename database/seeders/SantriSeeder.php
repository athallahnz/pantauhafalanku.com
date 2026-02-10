<?php

namespace Database\Seeders;

use App\Models\Santri;
use App\Models\User;
use App\Models\Kelas;
use Illuminate\Database\Seeder;

class SantriSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'santri@sihafalan.test')->first();
        $kelas = Kelas::first();

        Santri::updateOrCreate(
            ['user_id' => $user?->id],
            [
                'kelas_id' => $kelas?->id,
                'nama' => $user?->name ?? 'Santri Contoh',
                'nis' => 'S-001',
                'tanggal_lahir' => '2010-01-01',
                'jenis_kelamin' => 'L',
            ]
        );
    }
}
