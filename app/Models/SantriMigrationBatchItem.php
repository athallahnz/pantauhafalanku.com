<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SantriMigrationBatchItem extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $table = 'santri_migration_batch_items';

    protected $fillable = [
        'batch_id', 'santri_id',
        'from_kelas_id', 'to_kelas_id',
        'from_musyrif_id', 'to_musyrif_id',
        'transition_type', 'assignment_required', 'status',
        'source_hash', 'source_snapshot', 'target_snapshot',
        'error_message', 'executed_at',
        'rolled_back_at', 'rollback_error',
    ];

    protected $casts = [
        'assignment_required' => 'boolean',
        'source_snapshot' => 'array',
        'target_snapshot' => 'array',
        'executed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SantriMigrationBatch::class, 'batch_id');
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function fromKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'from_kelas_id');
    }

    public function toKelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'to_kelas_id');
    }

    public function fromMusyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class, 'from_musyrif_id');
    }

    public function toMusyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class, 'to_musyrif_id');
    }
}
