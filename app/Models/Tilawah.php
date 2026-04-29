<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

class Tilawah extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'santri_id',
        'musyrif_id',
        'tanggal',
        'hafalan_template_id',
        'status',
        'catatan'
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class, 'musyrif_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(HafalanTemplate::class, 'hafalan_template_id');
    }
}
