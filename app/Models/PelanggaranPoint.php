<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PelanggaranPoint extends Model
{
    protected $fillable = [
        'santri_id',
        'musyrif_id',
        'hafalan_id',
        'tanggal',
        'poin',
        'keterangan'
    ];

    protected $casts = ['tanggal' => 'date'];

    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }
    public function musyrif()
    {
        return $this->belongsTo(Musyrif::class);
    }
    public function hafalan()
    {
        return $this->belongsTo(Hafalan::class);
    }
}

