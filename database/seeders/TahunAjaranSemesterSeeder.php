<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TahunAjaranSemesterSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Nonaktifkan semua tahun ajaran & semester
            DB::table('semesters')->update(['is_active' => false]);
            DB::table('tahun_ajarans')->update(['is_active' => false]);

            // Buat tahun ajaran aktif: otomatis berdasarkan tahun sekarang
            $now = Carbon::now();
            $y = (int) $now->format('Y');

            // Pola umum di sekolah: tahun ajaran mulai di pertengahan tahun
            // Jika bulan >= 7 => tahun ajaran y/(y+1), kalau < 7 => (y-1)/y
            if ((int) $now->format('n') >= 7) {
                $namaTA = "{$y}/" . ($y + 1);
                $start = Carbon::create($y, 7, 1);
                $end = Carbon::create($y + 1, 6, 30);
            } else {
                $namaTA = ($y - 1) . "/{$y}";
                $start = Carbon::create($y - 1, 7, 1);
                $end = Carbon::create($y, 6, 30);
            }

            $tahunAjaranId = DB::table('tahun_ajarans')->insertGetId([
                'nama' => $namaTA,
                'is_active' => true,
                'tanggal_mulai' => $start->toDateString(),
                'tanggal_selesai' => $end->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Semester aktif: otomatis ganjil jika bulan 7-12, genap jika 1-6
            $activeSemesterNama = ((int) $now->format('n') >= 7) ? 'ganjil' : 'genap';

            // Buat 2 semester
            $semesters = [
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'nama' => 'ganjil',
                    'is_active' => $activeSemesterNama === 'ganjil',
                    'tanggal_mulai' => Carbon::parse($start)->toDateString(),
                    'tanggal_selesai' => Carbon::parse($start)->addMonths(5)->endOfMonth()->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tahun_ajaran_id' => $tahunAjaranId,
                    'nama' => 'genap',
                    'is_active' => $activeSemesterNama === 'genap',
                    'tanggal_mulai' => Carbon::parse($start)->addMonths(6)->startOfMonth()->toDateString(),
                    'tanggal_selesai' => Carbon::parse($end)->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            DB::table('semesters')->insert($semesters);
        });
    }
}
