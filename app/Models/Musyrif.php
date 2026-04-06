<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity; // Import trait untuk logging aktivitas

class Musyrif extends Model
{
    use HasFactory;
    use LogsActivity; // Gunakan trait untuk logging aktivitas

    protected $fillable = [
        'user_id',
        'nama',
        'kode',
        'keterangan',
        'kelas_id',
        'alamat',
        'pendidikan_terakhir',
        'domisili',
        'halaqah',
        'lama_mengabdi',
        'amanah_lain',
        'metode_alquran',
        'is_sertifikasi_ummi',
        'tahun_sertifikasi',
        'siap_sertifikasi'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function santris()
    {
        return $this->hasMany(Santri::class, 'musyrif_id');
    }
    public function santri()
    {
        return $this->hasMany(Santri::class, 'musyrif_id');
    }

    public function hafalans()
    {
        return $this->hasMany(Hafalan::class);
    }

    public function attendances()
    {
        return $this->hasMany(MusyrifAttendance::class, 'musyrif_id');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }
}
