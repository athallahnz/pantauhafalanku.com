<?php

namespace Database\Seeders;

use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\Musyrif;
use Illuminate\Database\Seeder;

class HafalanSeeder extends Seeder
{
    public function run(): void
    {
        $santri = Santri::first();
        $musyrif = Musyrif::first();

        if (!$santri || !$musyrif) {
            return;
        }

        Hafalan::create([
            'santri_id' => $santri->id,
            'musyrif_id' => $musyrif->id,
            'juz' => 1,
            'surah' => 'Al-Fatihah',
            'ayat_awal' => 1,
            'ayat_akhir' => 7,
            'rentang_ayat_label' => 'Al-Fatihah 1–7',
            'tanggal_setoran' => now()->toDateString(),
            'nilai' => 90,
            'tahap' => 'tahap_1',
            'status' => 'lulus',
            'catatan' => 'Setoran pertama, bacaan cukup baik.',
        ]);
    }
}
