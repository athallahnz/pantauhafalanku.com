<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeder:
 * - hafalan_templates
 * - surah_segments
 *
 * Seeder ini idempotent dan sekaligus menyinkronkan ulang seluruh segmen.
 * ID hafalan_templates yang sudah dipakai transaksi lama tetap dipertahankan.
 *
 * Notes:
 * - ayat_akhir = 0 berarti FULL surah (1..akhir surah).
 * - segment type 'surah_range_full' akan di-expand menjadi beberapa surah full berdasarkan urutan id surah (1..114).
 */
class HafalanTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $data = require database_path('seeders/data/hafalan_templates_data.php');

        // Map nama surah -> id (canonical) menggunakan normalisasi ringan.
        $surahMap = DB::table('surahs')
            ->select(['id', 'nama'])
            ->get()
            ->mapWithKeys(function ($row) {
                return [$this->normSurah($row->nama) => (int) $row->id];
            })
            ->all();

        DB::transaction(function () use ($data, $now, $surahMap) {
            foreach ($data as $item) {
                $templateKey = [
                    'juz' => (int) $item['juz'],
                    'tahap' => $item['tahap'],
                    'urutan' => (int) $item['urutan'],
                ];

                $templateId = DB::table('hafalan_templates')
                    ->where($templateKey)
                    ->value('id');

                if ($templateId) {
                    DB::table('hafalan_templates')
                        ->where('id', $templateId)
                        ->update([
                            'label' => $item['label'] ?? null,
                            'updated_at' => $now,
                        ]);
                } else {
                    $templateId = DB::table('hafalan_templates')->insertGetId([
                        ...$templateKey,
                        'label' => $item['label'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $templateId = (int) $templateId;

                $segments = $item['segments'] ?? [];
                $segRows = [];

                if (empty($segments)) {
                    throw new RuntimeException(
                        "Template {$templateId} tidak memiliki definisi segmen."
                    );
                }

                $segIndex = 1;
                foreach ($segments as $seg) {
                    // Expand range-full: "SurahA - SurahB" (full, inclusive)
                    if (($seg['type'] ?? null) === 'surah_range_full') {
                        $startId = $this->surahIdFromName($seg['surah_awal'], $surahMap);
                        $endId   = $this->surahIdFromName($seg['surah_akhir'], $surahMap);

                        if (!$startId || !$endId) {
                            throw new RuntimeException(
                                "Rentang surat tidak dikenali pada template {$templateId}: "
                                . ($seg['surah_awal'] ?? '-')
                                . ' - '
                                . ($seg['surah_akhir'] ?? '-')
                            );
                        }

                        if ($startId > $endId) {
                            [$startId, $endId] = [$endId, $startId];
                        }

                        for ($sid = $startId; $sid <= $endId; $sid++) {
                            $segRows[] = [
                                'hafalan_template_id' => $templateId,
                                'surah_id'           => $sid,
                                'ayat_awal'          => 1,
                                'ayat_akhir'         => 0, // FULL
                                'urutan_segmen'      => $segIndex++,
                                'created_at'         => $now,
                                'updated_at'         => $now,
                            ];
                        }
                        continue;
                    }

                    if ($this->isIgnoredSegment($seg['surah'] ?? '')) {
                        continue;
                    }

                    $surahId = $this->surahIdFromName($seg['surah'] ?? '', $surahMap);
                    if (!$surahId) {
                        throw new RuntimeException(
                            "Nama surat tidak dikenali pada template {$templateId}: "
                            . ($seg['surah'] ?? '-')
                        );
                    }

                    $ayatAwal  = (int) ($seg['ayat_awal'] ?? 1);
                    $ayatAkhir = $seg['ayat_akhir'] ?? 0;

                    // Sentinel FULL
                    if ($ayatAkhir === 'FULL') {
                        $ayatAkhir = 0;
                    }

                    $ayatAkhir = (int) $ayatAkhir;
                    if ($ayatAkhir !== 0 && $ayatAwal > $ayatAkhir) {
                        [$ayatAwal, $ayatAkhir] = [$ayatAkhir, $ayatAwal];
                    }

                    $segRows[] = [
                        'hafalan_template_id' => $templateId,
                        'surah_id'           => $surahId,
                        'ayat_awal'          => max(1, $ayatAwal),
                        'ayat_akhir'         => $ayatAkhir, // 0 = FULL
                        'urutan_segmen'      => $segIndex++,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }

                if (empty($segRows)) {
                    DB::table('surah_segments')
                        ->where('hafalan_template_id', $templateId)
                        ->delete();

                    continue;
                }

                DB::table('surah_segments')
                    ->where('hafalan_template_id', $templateId)
                    ->delete();

                DB::table('surah_segments')->insert($segRows);
            }
        });
    }

    private function surahIdFromName(string $name, array $surahMap): ?int
    {
        $key = $this->normSurah($name);

        // Alias paling sering muncul di dokumen muthaba'ah (bisa Anda tambah jika menemukan variasi lain)
        $aliases = [
            $this->normSurah('Adz-Dzariyat') => $this->normSurah('Adh-Dhariyat'),
            $this->normSurah('Al-Hasyr')     => $this->normSurah('Al-Hashr'),
            $this->normSurah('Al-Insyiqaq') => $this->normSurah('Al-Inshiqaq'),
            $this->normSurah('Al-Jatsiyah') => $this->normSurah('Al-Jathiyah'),
            $this->normSurah('Al-Jin')      => $this->normSurah('Al-Jinn'),
            $this->normSurah('Al-Kahfi')    => $this->normSurah('Al-Kahf'),
            $this->normSurah('Al-Lail')     => $this->normSurah('Al-Layl'),
            $this->normSurah('Al-Maidah')   => $this->normSurah('Al-Ma\'idah'),
            $this->normSurah('Al-Muddassir') => $this->normSurah('Al-Muddaththir'),
            $this->normSurah('Al-Mujadalah') => $this->normSurah('Al-Mujadila'),
            $this->normSurah('Al-Qashash')  => $this->normSurah('Al-Qasas'),
            $this->normSurah('Al-Ghasyiyah') => $this->normSurah('Al-Ghashiyah'),
            $this->normSurah('Ali Imran')   => $this->normSurah('Ali \'Imran'),
            $this->normSurah('As-Syuara\'') => $this->normSurah('Ash-Shu\'ara\''),
            $this->normSurah('Ash-Shaf')    => $this->normSurah('As-Saff'),
            $this->normSurah('Ash-Shaffat') => $this->normSurah('As-Saffat'),
            $this->normSurah('Asy-Syams')    => $this->normSurah('Ash-Shams'),
            $this->normSurah('Asy-Syarh')   => $this->normSurah('Ash-Sharh'),
            $this->normSurah('Asy-Syura')   => $this->normSurah('Ash-Shura'),
            $this->normSurah('At-Taqwir')    => $this->normSurah('At-Takwir'),
            $this->normSurah('At-Takasur')   => $this->normSurah('At-Takathur'),
            $this->normSurah('Ath-Thur')    => $this->normSurah('At-Tur'),
            $this->normSurah('Fathir')      => $this->normSurah('Fatir'),
            $this->normSurah('Fushshilat')  => $this->normSurah('Fussilat'),
            $this->normSurah('Al-Qari\'ah')  => $this->normSurah('Al-Qari\'ah'),
            $this->normSurah('Al- Isra\'')  => $this->normSurah('Al-Isra\''),
            $this->normSurah('Al-A\'laq')   => $this->normSurah('Al-\'Alaq'),
            $this->normSurah('Al-A\'la')    => $this->normSurah('Al-A\'la'),
            $this->normSurah('Shad')        => $this->normSurah('Sad'),
            $this->normSurah('Tha-Ha')      => $this->normSurah('Ta-Ha'),
            $this->normSurah('Toha')        => $this->normSurah('Ta-Ha'),
            $this->normSurah('Yasin')       => $this->normSurah('Ya-Sin'),
        ];

        if (isset($surahMap[$key])) {
            return (int) $surahMap[$key];
        }

        if (isset($aliases[$key]) && isset($surahMap[$aliases[$key]])) {
            return (int) $surahMap[$aliases[$key]];
        }

        return null;
    }

    private function isIgnoredSegment(string $name): bool
    {
        return $this->normSurah($name) === 'target selesai';
    }

    private function normSurah(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = str_replace(['’','‘','`'], "'", $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }
}
