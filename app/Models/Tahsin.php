<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Tahsin extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'santri_id',
        'musyrif_id',
        'tanggal',
        'status',
        'buku',
        'halaman',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    // ===================== RELATIONS =====================
    public function santri()
    {
        return $this->belongsTo(Santri::class);
    }

    public function musyrif()
    {
        return $this->belongsTo(Musyrif::class);
    }

    // ===================== ACCESSORS =====================
    public function getBukuLabelAttribute(): string
    {
        return match ($this->buku) {
            'ummi_1'  => 'Ummi Jilid 1',
            'ummi_2'  => 'Ummi Jilid 2',
            'ummi_3'  => 'Ummi Jilid 3',
            'gharib_1' => 'Gharib 1',
            'gharib_2' => 'Gharib 2',
            'tajwid'   => 'Tajwid',
            default    => '-',
        };
    }
}
