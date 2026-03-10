<?php

namespace App\Imports;

use App\Models\Musyrif;
use App\Models\User;
use App\Models\Kelas;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class MusyrifImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip baris kosong
        if (!isset($row['nama']) && !isset($row['nama_musyrif'])) return null;

        // Mapping manual untuk mengantisipasi perbedaan header antar sheet
        $nama = $row['nama'] ?? $row['nama_musyrif'] ?? null;
        $noWa = $row['nomor_wa'] ?? $row['nomor'] ?? $row['no_hp'] ?? null;
        $kelasTarget = $row['kelas'] ?? null;

        // Logic User
        $email = $row['email'] ?? Str::slug($nama) . '@daruttaqwa.com';
        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $nama,
                'password' => Hash::make('password123'),
                'role' => 'musyrif',
                'nomor' => $noWa
            ]
        );

        // Cari ID Kelas
        $kelas = \App\Models\Kelas::where('nama_kelas', 'like', "%$kelasTarget%")->first();

        // Validasi Tahun Sertifikasi agar hanya menyimpan angka
        $tahunRaw = $row['tahun_berapa_sertifikasi_ummi'] ?? $row['tahun_sertifikasi'] ?? null;

        // Gunakan filter_var atau is_numeric untuk memastikan hanya angka yang masuk
        $tahunSertifikasi = is_numeric($tahunRaw) ? (int)$tahunRaw : null;

        return new \App\Models\Musyrif([
            'user_id'             => $user->id,
            'kelas_id'            => $kelas?->id,
            'nama'                => $nama,
            'kode'                => $row['kode'] ?? null,
            'alamat'              => $row['alamat'] ?? null,
            'pendidikan_terakhir' => $row['pendidikan_terakhir'] ?? null,
            'domisili'            => $row['domisili_tempat_tinggal'] ?? $row['domisili'] ?? null,
            'halaqah'             => $row['halaqah'] ?? $row['program'] ?? null,
            'is_sertifikasi_ummi' => (isset($row['apakah_sudah_serfikasi_ummi']) && $row['apakah_sudah_serfikasi_ummi'] == 'Sudah') ? 1 : 0,
            'tahun_sertifikasi'   => $tahunSertifikasi,
            'amanah_lain'         => $row['amanah_lain_di_pondok_pesantren_darut_taqwa_ponorogo'] ?? $row['amanah_lain'] ?? null,
        ]);
    }
}
