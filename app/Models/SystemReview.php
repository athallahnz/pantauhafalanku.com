<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemReview extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'user_id',
        'musyrif_id',
        'display_name',
        'role_label',
        'rating',
        'title',
        'review',
        'is_anonymous',
        'status',
        'sort_order',
        'published_at',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_anonymous' => 'boolean',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class);
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->published()
            ->orderByDesc('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function getPublicNameAttribute(): string
    {
        return $this->is_anonymous
            ? 'Musyrif Pengguna Sistem'
            : $this->display_name;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
