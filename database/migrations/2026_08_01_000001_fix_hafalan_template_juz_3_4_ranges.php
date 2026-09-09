<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $corrections = [
            ['juz' => 3, 'urutan' => 7,  'label' => 'Al-Baqarah:282',   'surah_id' => 2, 'ayat_awal' => 282, 'ayat_akhir' => 282],
            ['juz' => 3, 'urutan' => 12, 'label' => 'Ali Imran:23-29', 'surah_id' => 3, 'ayat_awal' => 23,  'ayat_akhir' => 29],
            ['juz' => 4, 'urutan' => 1,  'label' => 'Ali Imran:92-100',  'surah_id' => 3, 'ayat_awal' => 92,  'ayat_akhir' => 100],
            ['juz' => 4, 'urutan' => 2,  'label' => 'Ali Imran:101-108', 'surah_id' => 3, 'ayat_awal' => 101, 'ayat_akhir' => 108],
            ['juz' => 4, 'urutan' => 3,  'label' => 'Ali Imran:109-115', 'surah_id' => 3, 'ayat_awal' => 109, 'ayat_akhir' => 115],
            ['juz' => 4, 'urutan' => 4,  'label' => 'Ali Imran:116-121', 'surah_id' => 3, 'ayat_awal' => 116, 'ayat_akhir' => 121],
            ['juz' => 4, 'urutan' => 5,  'label' => 'Ali Imran:122-132', 'surah_id' => 3, 'ayat_awal' => 122, 'ayat_akhir' => 132],
            ['juz' => 4, 'urutan' => 6,  'label' => 'Ali Imran:133-140', 'surah_id' => 3, 'ayat_awal' => 133, 'ayat_akhir' => 140],
            ['juz' => 4, 'urutan' => 7,  'label' => 'Ali Imran:141-148', 'surah_id' => 3, 'ayat_awal' => 141, 'ayat_akhir' => 148],
            ['juz' => 4, 'urutan' => 8,  'label' => 'Ali Imran:149-153', 'surah_id' => 3, 'ayat_awal' => 149, 'ayat_akhir' => 153],
            ['juz' => 4, 'urutan' => 9,  'label' => 'Ali Imran:154-157', 'surah_id' => 3, 'ayat_awal' => 154, 'ayat_akhir' => 157],
            ['juz' => 4, 'urutan' => 10, 'label' => 'Ali Imran:158-165', 'surah_id' => 3, 'ayat_awal' => 158, 'ayat_akhir' => 165],
        ];

        DB::transaction(function () use ($corrections) {
            $now = now();

            foreach ($corrections as $correction) {
                $templateId = DB::table('hafalan_templates')
                    ->where('juz', $correction['juz'])
                    ->where('tahap', 'harian')
                    ->where('urutan', $correction['urutan'])
                    ->value('id');

                if (! $templateId) {
                    throw new \RuntimeException(
                        "Template harian Juz {$correction['juz']} urutan {$correction['urutan']} tidak ditemukan."
                    );
                }

                DB::table('hafalan_templates')
                    ->where('id', $templateId)
                    ->update([
                        'label' => $correction['label'],
                        'updated_at' => $now,
                    ]);

                DB::table('surah_segments')
                    ->where('hafalan_template_id', $templateId)
                    ->delete();

                DB::table('surah_segments')->insert([
                    'hafalan_template_id' => $templateId,
                    'surah_id' => $correction['surah_id'],
                    'ayat_awal' => $correction['ayat_awal'],
                    'ayat_akhir' => $correction['ayat_akhir'],
                    'urutan_segmen' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Koreksi data kanonik tidak dikembalikan ke rentang yang salah.
    }
};
