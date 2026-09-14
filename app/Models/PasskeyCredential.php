<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasskeyCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'credential_id',
        'credential_id_hash',
        'user_handle',
        'public_key',
        'signature_count',
        'transports',
        'last_used_at',
    ];

    protected $hidden = [
        'credential_id',
        'credential_id_hash',
        'user_handle',
        'public_key',
    ];

    protected $casts = [
        'signature_count' => 'integer',
        'transports' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
