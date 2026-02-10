<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Hafalan extends Model
{
    use HasFactory;

    protected $fillable = [
        'santri_id',
        'musyrif_id',
        'hafalan_template_id',
        'tanggal_setoran',
        'nilai_label',
        'status',
        'catatan',

        // ---- LEGACY (opsional sementara) ----
        // jika Anda masih belum drop kolom lama, biarkan ini agar update lama tidak error
        'juz',
        'surah',
        'ayat_awal',
        'ayat_akhir',
        'rentang_ayat_label',
        'nilai',
        'tahap',
    ];

    protected $casts = [
        'tanggal_setoran' => 'date',
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

    public function template()
    {
        return $this->belongsTo(HafalanTemplate::class, 'hafalan_template_id');
    }

    // ===================== ACCESSORS =====================
    /**
     * Label Surah:Ayat untuk display.
     * Prioritas:
     * 1) template.label (hasil seeding)
     * 2) legacy rentang_ayat_label / surah+ayat
     * 3) '-'
     */
    public function getRentangLabelAttribute(): string
    {
        // (1) dari template (recommended)
        if ($this->relationLoaded('template') && $this->template?->label) {
            return $this->template->label;
        }
        if ($this->hafalan_template_id && $this->template()->exists()) {
            return (string) optional($this->template)->label;
        }

        // (2) fallback legacy
        if (!empty($this->rentang_ayat_label)) {
            return $this->rentang_ayat_label;
        }
        if (!empty($this->surah) && !empty($this->ayat_awal) && !empty($this->ayat_akhir)) {
            return $this->surah . ' ' . $this->ayat_awal . '–' . $this->ayat_akhir;
        }

        return '-';
    }

    /**
     * Label nilai untuk display yang konsisten di UI.
     * Jika Anda sepenuhnya pindah ke nilai_label, bagian legacy bisa dihapus.
     */
    public function getNilaiDisplayAttribute(): string
    {
        if (!empty($this->nilai_label)) {
            return match ($this->nilai_label) {
                'mumtaz' => 'Mumtaz',
                'jayyid_jiddan' => 'Jayyid Jiddan',
                'jayyid' => 'Jayyid',
                default => $this->nilai_label,
            };
        }

        // legacy numeric fallback
        if (!is_null($this->nilai)) {
            return (string) $this->nilai;
        }

        return '-';
    }
}
