<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KelasGroupAssignmentItem extends Model
{
    public const ENTITY_SANTRI = 'santri';
    public const ENTITY_MUSYRIF = 'musyrif';
    public const ENTITY_PLACEMENT = 'placement';

    public const STATUS_PENDING = 'pending';
    public const STATUS_EXECUTED = 'executed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $fillable = [
        'batch_id',
        'entity_type',
        'entity_id',
        'santri_id',
        'musyrif_id',
        'placement_id',
        'semester_id',
        'from_kelas_id',
        'to_kelas_id',
        'status',
        'snapshot_before',
        'snapshot_after',
        'source_hash',
        'target_hash',
        'error_message',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'santri_id' => 'integer',
        'musyrif_id' => 'integer',
        'placement_id' => 'integer',
        'semester_id' => 'integer',
        'from_kelas_id' => 'integer',
        'to_kelas_id' => 'integer',
        'snapshot_before' => 'array',
        'snapshot_after' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            KelasGroupAssignmentBatch::class,
            'batch_id'
        );
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(
            Santri::class,
            'santri_id'
        );
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(
            Musyrif::class,
            'musyrif_id'
        );
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(
            SantriSemesterPlacement::class,
            'placement_id'
        );
    }

    public function fromKelas(): BelongsTo
    {
        return $this->belongsTo(
            Kelas::class,
            'from_kelas_id'
        );
    }

    public function toKelas(): BelongsTo
    {
        return $this->belongsTo(
            Kelas::class,
            'to_kelas_id'
        );
    }
}
