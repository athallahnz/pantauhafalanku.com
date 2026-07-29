<?php

namespace App\Models\Concerns;

use App\Models\Kelas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasKelasHierarchy
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Kelas::class, 'parent_id')
            ->orderBy('urutan')
            ->orderBy('kelompok')
            ->orderBy('nama_kelas');
    }

    public function scopeInduk(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeKelompok(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Kelas yang boleh dipakai untuk assignment baru Santri/Musyrif.
     */
    public function scopeOperasional(Builder $query): Builder
    {
        return $query
            ->whereNotNull('parent_id')
            ->where('is_active', true)
            ->whereHas('parent', fn (Builder $parent) => $parent->where('is_active', true));
    }

    /**
     * Root lama yang masih mungkin dipakai assignment sebelum Tahap 4.
     */
    public function scopeLegacy(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeUrutHierarki(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN id ELSE parent_id END')
            ->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('urutan')
            ->orderBy('kelompok')
            ->orderBy('nama_kelas');
    }

    public function isInduk(): bool
    {
        return $this->parent_id === null;
    }

    public function isKelompok(): bool
    {
        return $this->parent_id !== null;
    }

    public function isOperational(): bool
    {
        return $this->isKelompok()
            && (bool) $this->is_active
            && (!$this->relationLoaded('parent') || (bool) $this->parent?->is_active);
    }

    public function getNamaTampilanAttribute(): string
    {
        if ($this->isInduk()) {
            return (string) $this->nama_kelas;
        }

        if ($this->relationLoaded('parent') && $this->parent) {
            return trim($this->parent->nama_kelas . ' ' . $this->kelompok);
        }

        return (string) $this->nama_kelas;
    }

    public function getTingkatIdAttribute(): int
    {
        return (int) ($this->parent_id ?: $this->id);
    }

    public function getJenisAttribute(): string
    {
        return $this->isKelompok() ? 'kelompok' : 'induk';
    }
}
