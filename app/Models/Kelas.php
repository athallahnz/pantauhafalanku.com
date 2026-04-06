<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity; // <-- 1. Import Trait

class Kelas extends Model
{
    use HasFactory;
    use LogsActivity; // <-- 2. Gunakan Trait untuk logging aktivitas

    protected $table = 'kelas';

    protected $fillable = [
        'nama_kelas',
        'deskripsi',
    ];

    public function santris()
    {
        return $this->hasMany(Santri::class);
    }
}
