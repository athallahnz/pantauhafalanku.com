<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity; // <-- 1. Import Trait untuk logging aktivitas

class MusyrifAttendance extends Model
{
    use LogsActivity; // <-- 2. Gunakan Trait untuk logging aktivitas

    protected $fillable = [
        'musyrif_id',
        'type',
        'attendance_at',
        'photo_path',
        'latitude',
        'longitude',
        'accuracy',
        'address_text',
        'ip_address',
        'device_info',
        'status',
        'notes'
    ];

    protected $casts = [
        'attendance_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function musyrif()
    {
        return $this->belongsTo(Musyrif::class);
    }
}
