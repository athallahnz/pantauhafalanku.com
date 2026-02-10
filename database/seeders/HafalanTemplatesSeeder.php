<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder:
 * - hafalan_templates
 * - surah_segments
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
                $templateId = DB::table('hafalan_templates')->insertGetId([
                    'juz'        => (int) $item['juz'],
                    'tahap'      => $item['tahap'],
                    'urutan'     => (int) $item['urutan'],
                    'label'      => $item['label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $segments = $item['segments'] ?? [];
                $segRows = [];

                $segIndex = 1;
                foreach ($segments as $seg) {
                    // Expand range-full: "SurahA - SurahB" (full, inclusive)
                    if (($seg['type'] ?? null) === 'surah_range_full') {
                        $startId = $this->surahIdFromName($seg['surah_awal'], $surahMap);
                        $endId   = $this->surahIdFromName($seg['surah_akhir'], $surahMap);

                        if ($startId && $endId) {
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
                        }
                        continue;
                    }

                    $surahId = $this->surahIdFromName($seg['surah'] ?? '', $surahMap);
                    if (!$surahId) {
                        // Skip jika nama surah tidak terpetakan (lebih aman daripada seeder gagal total).
                        continue;
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

                if (!empty($segRows)) {
                    DB::table('surah_segments')->insert($segRows);
                }
            }
        });
    }

    private function surahIdFromName(string $name, array $surahMap): ?int
    {
        $key = $this->normSurah($name);

        // Alias paling sering muncul di dokumen muthaba'ah (bisa Anda tambah jika menemukan variasi lain)
        $aliases = [
            $this->normSurah('Adz-Dzariyat') => $this->normSurah('Adh-Dhariyat'),
            $this->normSurah('Al-Ghasyiyah') => $this->normSurah('Al-Ghashiyah'),
            $this->normSurah('Asy-Syams')    => $this->normSurah('Ash-Shams'),
            $this->normSurah('At-Taqwir')    => $this->normSurah('At-Takwir'),
            $this->normSurah('At-Takasur')   => $this->normSurah('At-Takathur'),
            $this->normSurah('Al-Qari\'ah')  => $this->normSurah('Al-Qari\'ah'),
            $this->normSurah('Al- Isra\'')  => $this->normSurah('Al-Isra\''),
            $this->normSurah('Al-A\'laq')   => $this->normSurah('Al-\'Alaq'),
            // $this->normSurah('Al-A\'laq')   => $this->normSurah('Al-\'Alaq'),
            $this->normSurah('Al-A\'la')    => $this->normSurah('Al-A\'la'),
        ];

        if (isset($surahMap[$key])) {
            return (int) $surahMap[$key];
        }

        if (isset($aliases[$key]) && isset($surahMap[$aliases[$key]])) {
            return (int) $surahMap[$aliases[$key]];
        }

        return null;
    }

    private function normSurah(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = str_replace(['’','‘','`'], "'", $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }
}
