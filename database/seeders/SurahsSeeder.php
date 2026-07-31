<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SurahsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
    [
        'id' => 1,
        'nama' => 'Al-Fatihah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 2,
        'nama' => 'Al-Baqarah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 3,
        'nama' => 'Ali \'Imran',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 4,
        'nama' => 'An-Nisa\'',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 5,
        'nama' => 'Al-Ma\'idah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 6,
        'nama' => 'Al-An\'am',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 7,
        'nama' => 'Al-A\'raf',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 8,
        'nama' => 'Al-Anfal',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 9,
        'nama' => 'At-Taubah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 10,
        'nama' => 'Yunus',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 11,
        'nama' => 'Hud',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 12,
        'nama' => 'Yusuf',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 13,
        'nama' => 'Ar-Ra\'d',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 14,
        'nama' => 'Ibrahim',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 15,
        'nama' => 'Al-Hijr',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 16,
        'nama' => 'An-Nahl',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 17,
        'nama' => 'Al-Isra\'',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 18,
        'nama' => 'Al-Kahf',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 19,
        'nama' => 'Maryam',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 20,
        'nama' => 'Ta-Ha',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 21,
        'nama' => 'Al-Anbiya\'',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 22,
        'nama' => 'Al-Hajj',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 23,
        'nama' => 'Al-Mu\'minun',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 24,
        'nama' => 'An-Nur',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 25,
        'nama' => 'Al-Furqan',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 26,
        'nama' => 'Ash-Shu\'ara\'',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 27,
        'nama' => 'An-Naml',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 28,
        'nama' => 'Al-Qasas',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 29,
        'nama' => 'Al-Ankabut',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 30,
        'nama' => 'Ar-Rum',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 31,
        'nama' => 'Luqman',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 32,
        'nama' => 'As-Sajdah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 33,
        'nama' => 'Al-Ahzab',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 34,
        'nama' => 'Saba\'',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 35,
        'nama' => 'Fatir',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 36,
        'nama' => 'Ya-Sin',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 37,
        'nama' => 'As-Saffat',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 38,
        'nama' => 'Sad',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 39,
        'nama' => 'Az-Zumar',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 40,
        'nama' => 'Ghafir',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 41,
        'nama' => 'Fussilat',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 42,
        'nama' => 'Ash-Shura',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 43,
        'nama' => 'Az-Zukhruf',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 44,
        'nama' => 'Ad-Dukhan',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 45,
        'nama' => 'Al-Jathiyah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 46,
        'nama' => 'Al-Ahqaf',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 47,
        'nama' => 'Muhammad',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 48,
        'nama' => 'Al-Fath',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 49,
        'nama' => 'Al-Hujurat',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 50,
        'nama' => 'Qaf',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 51,
        'nama' => 'Adh-Dhariyat',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 52,
        'nama' => 'At-Tur',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 53,
        'nama' => 'An-Najm',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 54,
        'nama' => 'Al-Qamar',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 55,
        'nama' => 'Ar-Rahman',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 56,
        'nama' => 'Al-Waqi\'ah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 57,
        'nama' => 'Al-Hadid',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 58,
        'nama' => 'Al-Mujadila',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 59,
        'nama' => 'Al-Hashr',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 60,
        'nama' => 'Al-Mumtahanah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 61,
        'nama' => 'As-Saff',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 62,
        'nama' => 'Al-Jumu\'ah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 63,
        'nama' => 'Al-Munafiqun',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 64,
        'nama' => 'At-Taghabun',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 65,
        'nama' => 'At-Talaq',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 66,
        'nama' => 'At-Tahrim',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 67,
        'nama' => 'Al-Mulk',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 68,
        'nama' => 'Al-Qalam',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 69,
        'nama' => 'Al-Haqqah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 70,
        'nama' => 'Al-Ma\'arij',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 71,
        'nama' => 'Nuh',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 72,
        'nama' => 'Al-Jinn',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 73,
        'nama' => 'Al-Muzzammil',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 74,
        'nama' => 'Al-Muddaththir',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 75,
        'nama' => 'Al-Qiyamah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 76,
        'nama' => 'Al-Insan',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 77,
        'nama' => 'Al-Mursalat',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 78,
        'nama' => 'An-Naba\'',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 79,
        'nama' => 'An-Nazi\'at',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 80,
        'nama' => '\'Abasa',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 81,
        'nama' => 'At-Takwir',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 82,
        'nama' => 'Al-Infitar',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 83,
        'nama' => 'Al-Mutaffifin',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 84,
        'nama' => 'Al-Inshiqaq',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 85,
        'nama' => 'Al-Buruj',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 86,
        'nama' => 'At-Tariq',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 87,
        'nama' => 'Al-A\'la',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 88,
        'nama' => 'Al-Ghashiyah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 89,
        'nama' => 'Al-Fajr',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 90,
        'nama' => 'Al-Balad',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 91,
        'nama' => 'Ash-Shams',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 92,
        'nama' => 'Al-Layl',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 93,
        'nama' => 'Ad-Duha',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 94,
        'nama' => 'Ash-Sharh',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 95,
        'nama' => 'At-Tin',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 96,
        'nama' => 'Al-\'Alaq',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 97,
        'nama' => 'Al-Qadr',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 98,
        'nama' => 'Al-Bayyinah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 99,
        'nama' => 'Az-Zalzalah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 100,
        'nama' => 'Al-\'Adiyat',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 101,
        'nama' => 'Al-Qari\'ah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 102,
        'nama' => 'At-Takathur',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 103,
        'nama' => 'Al-\'Asr',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 104,
        'nama' => 'Al-Humazah',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 105,
        'nama' => 'Al-Fil',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 106,
        'nama' => 'Quraysh',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 107,
        'nama' => 'Al-Ma\'un',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 108,
        'nama' => 'Al-Kawthar',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 109,
        'nama' => 'Al-Kafirun',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 110,
        'nama' => 'An-Nasr',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 111,
        'nama' => 'Al-Masad',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 112,
        'nama' => 'Al-Ikhlas',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 113,
        'nama' => 'Al-Falaq',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ],
    [
        'id' => 114,
        'nama' => 'An-Nas',
        'jumlah_ayat' => 0,
        'created_at' => $now,
        'updated_at' => $now
    ]
];

        // Jumlah ayat kanonik dibutuhkan untuk validasi rentang Tilawah.
        $verseCounts = [
            1 => 7, 2 => 286, 3 => 200, 4 => 176, 5 => 120, 6 => 165,
            7 => 206, 8 => 75, 9 => 129, 10 => 109, 11 => 123, 12 => 111,
            13 => 43, 14 => 52, 15 => 99, 16 => 128, 17 => 111, 18 => 110,
            19 => 98, 20 => 135, 21 => 112, 22 => 78, 23 => 118, 24 => 64,
            25 => 77, 26 => 227, 27 => 93, 28 => 88, 29 => 69, 30 => 60,
            31 => 34, 32 => 30, 33 => 73, 34 => 54, 35 => 45, 36 => 83,
            37 => 182, 38 => 88, 39 => 75, 40 => 85, 41 => 54, 42 => 53,
            43 => 89, 44 => 59, 45 => 37, 46 => 35, 47 => 38, 48 => 29,
            49 => 18, 50 => 45, 51 => 60, 52 => 49, 53 => 62, 54 => 55,
            55 => 78, 56 => 96, 57 => 29, 58 => 22, 59 => 24, 60 => 13,
            61 => 14, 62 => 11, 63 => 11, 64 => 18, 65 => 12, 66 => 12,
            67 => 30, 68 => 52, 69 => 52, 70 => 44, 71 => 28, 72 => 28,
            73 => 20, 74 => 56, 75 => 40, 76 => 31, 77 => 50, 78 => 40,
            79 => 46, 80 => 42, 81 => 29, 82 => 19, 83 => 36, 84 => 25,
            85 => 22, 86 => 17, 87 => 19, 88 => 26, 89 => 30, 90 => 20,
            91 => 15, 92 => 21, 93 => 11, 94 => 8, 95 => 8, 96 => 19,
            97 => 5, 98 => 8, 99 => 8, 100 => 11, 101 => 11, 102 => 8,
            103 => 3, 104 => 9, 105 => 5, 106 => 4, 107 => 7, 108 => 3,
            109 => 6, 110 => 3, 111 => 5, 112 => 4, 113 => 5, 114 => 6,
        ];

        $rows = array_map(function (array $row) use ($verseCounts): array {
            $row['jumlah_ayat'] = $verseCounts[(int) $row['id']];

            return $row;
        }, $rows);

        // Upsert by id (114 surah)
        DB::table('surahs')->upsert($rows, ['id'], ['nama', 'jumlah_ayat', 'updated_at']);
    }
}
