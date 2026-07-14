<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AcademicDocument extends Model
{
    use HasFactory;

    public const TYPE_RAPORT = 'raport';

    public const TYPE_SYAHADAH = 'syahadah';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEW = 'review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_REVOKED = 'revoked';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'public_id',
        'santri_id',
        'semester_id',
        'document_type',
        'status',
        'revision',
        'is_current',
        'document_number',
        'template_version',

        'snapshot_json',
        'snapshot_sha256',

        'predikat',
        'catatan_musyrif',
        'catatan_admin',
        'rekomendasi',
        'review_notes',

        'override_reason',
        'override_by',

        'pdf_path',
        'pdf_sha256',
        'pdf_generated_at',

        'verification_token_hash',

        'download_count',
        'last_downloaded_at',

        'generated_at',
        'submitted_at',

        'reviewed_by',
        'reviewed_at',

        'published_by',
        'published_at',

        'revoked_by',
        'revoked_at',
        'revocation_reason',

        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',

        'supersedes_document_id',

        'created_by',
        'updated_by',

        'metadata',
    ];

    protected $casts = [
        'revision' => 'integer',
        'is_current' => 'boolean',

        'snapshot_json' => 'array',
        'metadata' => 'array',

        'download_count' => 'integer',

        'generated_at' => 'datetime',
        'submitted_at' => 'datetime',

        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
        'revoked_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'pdf_generated_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (
                AcademicDocument $document
            ): void {
                if (!$document->public_id) {
                    $document->public_id =
                        (string) Str::uuid();
                }

                if (!$document->document_type) {
                    $document->document_type =
                        self::TYPE_RAPORT;
                }

                if (!$document->status) {
                    $document->status =
                        self::STATUS_DRAFT;
                }

                if (!$document->revision) {
                    $document->revision = 1;
                }
            }
        );
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(
            Santri::class
        );
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(
            Semester::class
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by'
        );
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by'
        );
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }

    public function overrideBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'override_by'
        );
    }

    public function supersededDocument(): BelongsTo
    {
        return $this->belongsTo(
            AcademicDocument::class,
            'supersedes_document_id'
        );
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(
            AcademicDocument::class,
            'supersedes_document_id'
        );
    }

    public function scopeRaport(
        Builder $query
    ): Builder {
        return $query->where(
            'document_type',
            self::TYPE_RAPORT
        );
    }

    public function scopeSyahadah(
        Builder $query
    ): Builder {
        return $query->where(
            'document_type',
            self::TYPE_SYAHADAH
        );
    }

    public function scopeDraft(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_DRAFT
        );
    }

    public function scopeInReview(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_REVIEW
        );
    }

    public function scopePublished(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_PUBLISHED
        );
    }

    public function scopeRevoked(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_REVOKED
        );
    }

    public function scopeCancelled(
        Builder $query
    ): Builder {
        return $query->where(
            'status',
            self::STATUS_CANCELLED
        );
    }

    public function scopeCurrent(
        Builder $query
    ): Builder {
        return $query->where(
            'is_current',
            true
        );
    }

    public function isDraft(): bool
    {
        return $this->status ===
            self::STATUS_DRAFT;
    }

    public function isInReview(): bool
    {
        return $this->status ===
            self::STATUS_REVIEW;
    }

    public function isPublished(): bool
    {
        return $this->status ===
            self::STATUS_PUBLISHED;
    }

    public function isRevoked(): bool
    {
        return $this->status ===
            self::STATUS_REVOKED;
    }

    public function isCancelled(): bool
    {
        return $this->status ===
            self::STATUS_CANCELLED;
    }

    public function canBeEdited(): bool
    {
        return $this->isDraft()
            && $this->is_current
            && $this->submitted_at === null
            && $this->published_at === null
            && $this->cancelled_at === null;
    }

    public function canBeSubmitted(): bool
    {
        return $this->isDraft()
            && $this->is_current
            && is_array($this->snapshot_json)
            && !empty($this->snapshot_json)
            && $this->cancelled_at === null;
    }

    public function canBePublished(): bool
    {
        return $this->isInReview()
            && $this->is_current
            && is_array($this->snapshot_json)
            && !empty($this->snapshot_json)
            && $this->revoked_at === null
            && $this->cancelled_at === null;
    }

    public function canBeRevoked(): bool
    {
        return $this->isPublished()
            && $this->revoked_at === null;
    }

    public function canBeCancelled(): bool
    {
        return $this->isDraft()
            && $this->is_current
            && $this->submitted_at === null
            && $this->reviewed_at === null
            && $this->published_at === null
            && $this->revoked_at === null
            && $this->cancelled_at === null;
    }

    public function isDownloadable(): bool
    {
        return $this->isPublished()
            && $this->is_current
            && $this->revoked_at === null
            && $this->cancelled_at === null
            && !empty($this->pdf_path)
            && !empty($this->pdf_sha256);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT =>
                'Draft',

            self::STATUS_REVIEW =>
                'Menunggu Pemeriksaan',

            self::STATUS_PUBLISHED =>
                'Tersedia',

            self::STATUS_REVOKED =>
                'Dicabut',

            self::STATUS_CANCELLED =>
                'Dibatalkan',

            default =>
                Str::title(
                    str_replace(
                        '_',
                        ' ',
                        $this->status
                    )
                ),
        };
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            self::TYPE_RAPORT =>
                'Raport Semester',

            self::TYPE_SYAHADAH =>
                'Syahadah Kelulusan',

            default =>
                Str::title(
                    str_replace(
                        '_',
                        ' ',
                        $this->document_type
                    )
                ),
        };
    }
}
