<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SurahSegment extends Model
{
    use HasFactory;

    protected $table = 'surah_segments';

    protected $fillable = [
        'hafalan_template_id',
        'surah_id',
        'ayat_awal',
        'ayat_akhir',
        'urutan_segmen',
    ];

    public function template()
    {
        return $this->belongsTo(HafalanTemplate::class, 'hafalan_template_id');
    }

    public function surah()
    {
        return $this->belongsTo(Surah::class, 'surah_id');
    }

    /**
     * Helper label segmen (untuk debug / tampilan detail)
     */
    public function getRangeLabelAttribute(): string
    {
        $nama = $this->surah?->nama ?? '-';

        // Jika Anda pakai ayat_akhir=0 untuk full surah (opsional)
        if ((int) $this->ayat_akhir === 0) {
            return $nama . ' (Full)';
        }

        return $nama . ' ' . $this->ayat_awal . '–' . $this->ayat_akhir;
    }
}
