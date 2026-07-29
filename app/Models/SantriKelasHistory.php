<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantriKelasHistory extends Model
{
    protected $fillable = [
        'santri_id',
        'semester_id',
        'kelas_id',
        'musyrif_id',
        'tipe',
        'catatan',
        'created_by',
    ];

    protected $casts = [
        'santri_id' => 'integer',
        'semester_id' => 'integer',
        'kelas_id' => 'integer',
        'musyrif_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class, 'musyrif_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
