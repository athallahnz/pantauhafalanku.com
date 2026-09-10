<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;

class Tilawah extends Model
{
    use HasFactory, LogsActivity;

    public const ENTRY_TYPE_GROUP = 'group';

    public const ENTRY_TYPE_CATCHUP = 'catchup';

    public const ENTRY_TYPE_INDIVIDUAL = 'individual';

    public const PURPOSE_CONTINUATION = 'continuation';

    public const PURPOSE_REVIEW = 'review';

    protected $fillable = [
        'santri_id',
        'musyrif_id',
        'tanggal',
        'hafalan_template_id',
        'status',
        'semester_id',
        'catatan',
        'entry_type',
        'reading_purpose',
        'submission_uuid',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tilawah $tilawah): void {
            $tilawah->entry_type ??= self::ENTRY_TYPE_GROUP;
            $tilawah->reading_purpose ??= self::PURPOSE_CONTINUATION;
            $tilawah->submission_uuid ??= (string) Str::uuid();
        });
    }

    public function isGroupEntry(): bool
    {
        return $this->entry_type === self::ENTRY_TYPE_GROUP;
    }

    public function isIndividualEntry(): bool
    {
        return $this->entry_type === self::ENTRY_TYPE_INDIVIDUAL;
    }

    public function contributesToProgress(): bool
    {
        return $this->status === 'hadir'
            && $this->reading_purpose === self::PURPOSE_CONTINUATION;
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class, 'santri_id');
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class, 'musyrif_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(HafalanTemplate::class, 'hafalan_template_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
