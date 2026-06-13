<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    public const TYPE_REAKTIVASI = 'reaktivasi';
    public const TYPE_KOREKSI_STATUS = 'koreksi_status';

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
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
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
