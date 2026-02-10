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

        // Upsert by id (114 surah)
        DB::table('surahs')->upsert($rows, ['id'], ['nama', 'jumlah_ayat', 'updated_at']);
    }
}
