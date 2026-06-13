<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SantriMigrationBatch extends Model
{
    public const MODE_MANUAL = 'manual';
    public const MODE_AUTO = 'auto';

    public const STATUS_PREVIEWED = 'previewed';
    public const STATUS_EXECUTING = 'executing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ROLLING_BACK = 'rolling_back';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $table = 'santri_migration_batches';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'code', 'mode', 'status',
        'from_semester_id', 'to_semester_id',
        'from_kelas_id', 'to_kelas_id', 'transition_type',
        'include_graduation', 'snapshot_hash',
        'items_count', 'completed_count', 'graduated_count',
        'note', 'metadata', 'last_error',
        'created_by', 'executed_by', 'rolled_back_by',
        'previewed_at', 'executing_at', 'executed_at',
        'failed_at', 'cancelled_at', 'rolled_back_at', 'expires_at',
        'rollback_reason', 'rollback_metadata', 'rollback_error',
    ];

    protected $casts = [
        'include_graduation' => 'boolean',
        'metadata' => 'array',
        'rollback_metadata' => 'array',
        'items_count' => 'integer',
        'completed_count' => 'integer',
        'graduated_count' => 'integer',
        'previewed_at' => 'datetime',
        'executing_at' => 'datetime',
        'executed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $batch): void {
            if (!$batch->getKey()) {
                $batch->setAttribute(
                    $batch->getKeyName(),
                    (string) Str::uuid()
                );
            }

            if (!$batch->code) {
                $batch->code = sprintf(
                    'MIG-%s-%s',
                    now()->format('YmdHis'),
                    strtoupper(Str::random(6))
                );
            }
        });
    }

    public function scopePreviewed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PREVIEWED);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function isExecutable(): bool
    {
        return $this->status === self::STATUS_PREVIEWED
            && !$this->isExpired();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SantriMigrationBatchItem::class, 'batch_id');
    }

    public function fromSemester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'from_semester_id');
    }

    public function toSemester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'to_semester_id');
    }

    public function fromKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'from_kelas_id');
    }

    public function toKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'to_kelas_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function rollbackActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    public function canRequestRollback(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->executed_at !== null
            && $this->rolled_back_at === null;
    }
}
