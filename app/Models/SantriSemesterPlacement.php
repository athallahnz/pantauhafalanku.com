<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SantriSemesterPlacement extends Model
{
    public const STATUS_AKTIF = 'aktif';
    public const STATUS_LULUS = 'lulus';
    public const STATUS_KELUAR = 'keluar';
    public const STATUS_NONAKTIF = 'nonaktif';

    public const TYPE_BACKFILL = 'backfill';
    public const TYPE_PENEMPATAN = 'penempatan';
    public const TYPE_MUTASI = 'mutasi';
    public const TYPE_NAIK_KELAS = 'naik_kelas';
    public const TYPE_TINGGAL_KELAS = 'tinggal_kelas';
    public const TYPE_LULUS = 'lulus';
    public const TYPE_KELUAR = 'keluar';
    public const TYPE_REAKTIVASI = 'reaktivasi';
    public const TYPE_KOREKSI_STATUS = 'koreksi_status';
    public const TYPE_HIERARCHY_ASSIGNMENT = 'hierarchy_assignment';

    protected $fillable = [
        'santri_id',
        'semester_id',
        'kelas_id',
        'musyrif_id',
        'status',
        'placement_type',
        'started_at',
        'ended_at',
        'migration_batch_id',
        'migration_batch_item_id',
        'note',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'santri_id' => 'integer',
        'semester_id' => 'integer',
        'kelas_id' => 'integer',
        'musyrif_id' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function scopeForSemester(
        Builder $query,
        int $semesterId
    ): Builder {
        return $query->where(
            'semester_id',
            $semesterId
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_AKTIF
        );
    }

    public function scopeForClass(
        Builder $query,
        int $kelasId
    ): Builder {
        return $query->where(
            'kelas_id',
            $kelasId
        );
    }

    public function scopeForMusyrif(
        Builder $query,
        int $musyrifId
    ): Builder {
        return $query->where(
            'musyrif_id',
            $musyrifId
        );
    }

    public function scopeUsingLegacyClass(
        Builder $query
    ): Builder {
        return $query->whereHas(
            'kelas',
            fn (Builder $kelasQuery) =>
                $kelasQuery->whereNull('parent_id')
        );
    }

    public function scopeUsingOperationalClass(
        Builder $query
    ): Builder {
        return $query->whereHas(
            'kelas',
            fn (Builder $kelasQuery) =>
                $kelasQuery
                    ->whereNotNull('parent_id')
                    ->where('is_active', true)
        );
    }

    public function usesLegacyParentClass(): bool
    {
        if (!$this->kelas_id) {
            return false;
        }

        if ($this->relationLoaded('kelas')) {
            return (bool) $this->kelas?->isInduk();
        }

        return $this->kelas()
            ->whereNull('parent_id')
            ->exists();
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(
            Santri::class,
            'santri_id'
        );
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(
            Semester::class,
            'semester_id'
        );
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(
            Kelas::class,
            'kelas_id'
        );
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(
            Musyrif::class,
            'musyrif_id'
        );
    }

    public function migrationBatch(): BelongsTo
    {
        return $this->belongsTo(
            SantriMigrationBatch::class,
            'migration_batch_id'
        );
    }

    public function migrationBatchItem(): BelongsTo
    {
        return $this->belongsTo(
            SantriMigrationBatchItem::class,
            'migration_batch_item_id'
        );
    }

    public function kelasGroupAssignmentItems(): HasMany
    {
        return $this->hasMany(
            KelasGroupAssignmentItem::class,
            'placement_id'
        )->latest('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
