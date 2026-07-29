<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KelasGroupAssignmentBatch extends Model
{
    use HasFactory;

    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_PREVIEWED = 'previewed';
    public const STATUS_EXECUTED = 'executed';
    public const STATUS_ROLLED_BACK = 'rolled_back';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'semester_id',
        'status',
        'mapping',
        'summary',
        'checksum',
        'note',
        'metadata',
        'previewed_at',
        'executed_at',
        'rolled_back_at',
        'created_by',
        'executed_by',
        'rolled_back_by',
    ];

    protected $casts = [
        'semester_id' => 'integer',
        'mapping' => 'array',
        'summary' => 'array',
        'metadata' => 'array',
        'previewed_at' => 'datetime',
        'executed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'created_by' => 'integer',
        'executed_by' => 'integer',
        'rolled_back_by' => 'integer',
    ];

    public function scopeLatestFirst(
        Builder $query
    ): Builder {
        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('code');
    }

    public function canExecute(): bool
    {
        return $this->status === self::STATUS_PREVIEWED;
    }

    public function canRollback(): bool
    {
        return $this->status === self::STATUS_EXECUTED;
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(
            Semester::class,
            'semester_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            KelasGroupAssignmentItem::class,
            'batch_id'
        );
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'executed_by'
        );
    }

    public function rolledBackBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'rolled_back_by'
        );
    }
}
