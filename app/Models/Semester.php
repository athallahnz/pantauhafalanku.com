<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
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
