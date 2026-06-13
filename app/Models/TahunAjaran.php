<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terkait dengan model.
     * (Opsional jika penamaannya sudah plural standar, tapi baik untuk kejelasan)
     *
     * @var string
     */
    protected $table = 'tahun_ajarans';

    /**
     * Kolom-kolom yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'is_active',
        'tanggal_mulai',
        'tanggal_selesai',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data asli (native types).
     * Ini memastikan bahwa saat data dikirim via API (JSON), is_active menjadi true/false
     * (bukan 1/0 string), dan tanggal berformat spesifik.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi One-to-Many ke model Semester.
     * Satu Tahun Ajaran memiliki banyak Semester.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }
}
