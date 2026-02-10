<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Surah extends Model
{
    use HasFactory;

    // Karena id surah 1..114 biasanya tinyIncrements
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id',          // opsional: jika Anda seed dengan id tetap
        'nama',
        'nama_latin',
        'jumlah_ayat',
    ];

    public function segments()
    {
        return $this->hasMany(SurahSegment::class, 'surah_id');
    }
}
