<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Musyrif extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama',
        'kode',
        'keterangan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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

}
