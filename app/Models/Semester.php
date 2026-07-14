<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';

    protected $table = 'semesters';

    protected $fillable = [
        'tahun_ajaran_id',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
        'status',
        'input_locked_at',
        'activated_at',
        'closed_at',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_active' => 'boolean',
        'input_locked_at' => 'datetime',
        'activated_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Semester $semester): void {
            /*
             * is_active dipertahankan untuk kompatibilitas query lama,
             * tetapi sumber status utama sekarang adalah kolom status.
             */
            $semester->is_active =
                $semester->status === self::STATUS_ACTIVE;

            if ($semester->status === self::STATUS_CLOSED) {
                $semester->input_locked_at ??= now();
                $semester->closed_at ??= now();
            }

            if ($semester->status === self::STATUS_DRAFT) {
                $semester->is_active = false;
                $semester->input_locked_at = null;
                $semester->activated_at = null;
                $semester->closed_at = null;
            }
        });
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(
            TahunAjaran::class,
            'tahun_ajaran_id'
        );
    }

    public function santriPlacements(): HasMany
    {
        return $this->hasMany(
            SantriSemesterPlacement::class,
            'semester_id'
        );
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_DRAFT
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where('is_active', true);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where(
            'status',
            self::STATUS_CLOSED
        );
    }

    public function scopeInputOpen(Builder $query): Builder
    {
        return $query
            ->active()
            ->whereNull('input_locked_at');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->is_active;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isInputLocked(): bool
    {
        return $this->input_locked_at !== null;
    }

    public function isInputOpen(): bool
    {
        return $this->isActive()
            && !$this->isInputLocked();
    }

    public function getLifecycleLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ACTIVE => $this->isInputLocked()
                ? 'Aktif — Input Dikunci'
                : 'Aktif — Input Dibuka',
            self::STATUS_CLOSED => 'Ditutup',
            default => 'Tidak Diketahui',
        };
    }

    public function academicDocuments(): HasMany
    {
        return $this->hasMany(
            AcademicDocument::class
        );
    }

    public function raportDocuments(): HasMany
    {
        return $this
            ->academicDocuments()
            ->where(
                'document_type',
                AcademicDocument::TYPE_RAPORT
            );
    }
}
