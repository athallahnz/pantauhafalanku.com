<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SantriKelasHistory extends Model
{
    protected $fillable = [
        'santri_id',
        'semester_id',
        'kelas_id',
        'musyrif_id',
        'tipe',
        'catatan',
        'created_by'
    ];
}
