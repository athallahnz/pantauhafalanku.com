<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemIntegrityRepairLog extends Model
{
    protected $fillable = [
        'actor_id',
        'issue_type',
        'entity_type',
        'entity_id',
        'action',
        'status',
        'reason',
        'before_data',
        'after_data',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data' => 'array',
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id')->withTrashed();
    }
}
