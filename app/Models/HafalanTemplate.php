<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HafalanTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'juz',
        'tahap',   // harian|tahap_1|tahap_2|tahap_3|ujian_akhir
        'urutan',
        'label',
    ];

    public function segments()
    {
        return $this->hasMany(SurahSegment::class, 'hafalan_template_id')
            ->orderBy('urutan_segmen');
    }

    public function hafalans()
    {
        return $this->hasMany(Hafalan::class, 'hafalan_template_id');
    }

    /**
     * Helper: label fallback jika kolom label kosong
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?: ('Bagian ' . $this->urutan);
    }
}
