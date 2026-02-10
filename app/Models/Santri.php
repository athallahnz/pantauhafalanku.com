<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Santri extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kelas_id',
        'musyrif_id',
        'nama',
        'nis',
        'tanggal_lahir',
        'jenis_kelamin',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // foreign key santris.user_id
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }

    public function musyrif()
    {
        return $this->belongsTo(Musyrif::class, 'musyrif_id');
    }

    public function hafalans()
    {
        return $this->hasMany(Hafalan::class);
    }

    private array $namaAliases = [
        'nama',
        'name',
        'nama santri',
        'nama siswa',
        'siswa',
        'murid',
        'nama murid',
        'nama lengkap',
        'student name',
    ];
}
