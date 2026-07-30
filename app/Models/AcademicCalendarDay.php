<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicCalendarDay extends Model
{
    use HasFactory;

    public const STATUS_MASUK = 'masuk';
    public const STATUS_LIBUR = 'libur';

    protected $fillable = [
        'semester_id',
        'tanggal',
        'status',
        'nama_kegiatan',
        'keterangan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isHoliday(): bool
    {
        return $this->status === self::STATUS_LIBUR;
    }
}
