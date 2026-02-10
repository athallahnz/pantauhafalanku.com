<?php

namespace Database\Seeders;

use App\Models\Musyrif;
use App\Models\User;
use Illuminate\Database\Seeder;

class MusyrifSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'musyrif@sihafalan.test')->first();

        Musyrif::updateOrCreate(
            ['user_id' => $user?->id],
            [
                'nama' => $user?->name ?? 'Ustadz Musyrif',
                'kode' => 'M-001',
                'keterangan' => 'Musyrif utama contoh',
            ]
        );
    }
}
