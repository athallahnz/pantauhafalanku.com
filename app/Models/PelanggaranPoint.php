<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity; // <-- 1. Import Trait untuk logging aktivitas

class PelanggaranPoint extends Model
{
    use LogsActivity; // <-- 2. Gunakan Trait untuk logging aktivitas

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
