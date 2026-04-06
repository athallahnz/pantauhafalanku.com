<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity; // <-- 1. Import Trait

class Semester extends Model
{
    use LogsActivity; // <-- 2. Gunakan Trait untuk logging aktivitas
    
    protected $fillable = [
        'tahun_ajaran_id',
        'nama',
        'is_active',
        'tanggal_mulai',
        'tanggal_selesai'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
