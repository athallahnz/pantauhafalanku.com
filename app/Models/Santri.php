<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Santri extends Model
{
    use HasFactory;
    use LogsActivity;

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_LULUS = 'lulus';
    public const STATUS_KELUAR = 'keluar';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $fillable = [
        'user_id',
        'kelas_id',
        'musyrif_id',
        'nama',
        'nis',
        'tanggal_lahir',
        'jenis_kelamin',
        'status',
        'graduated_semester_id',
        'graduated_at',
        'status_changed_at',
        'status_reason',
        'status_changed_by',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'graduated_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    public static function inactiveStatuses(): array
    {
        return [
            self::STATUS_LULUS,
            self::STATUS_KELUAR,
            self::STATUS_NONAKTIF,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AKTIF);
    }

    public function scopeGraduated(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_LULUS);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->whereIn('status', self::inactiveStatuses());
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_AKTIF;
    }

    public function isGraduated(): bool
    {
        return $this->status === self::STATUS_LULUS;
    }

    public function isInactive(): bool
    {
        return in_array($this->status, self::inactiveStatuses(), true);
    }

    public function changeStatus(
        string $toStatus,
        ?string $reason = null,
        ?Semester $semester = null,
        ?int $changedBy = null,
        CarbonInterface|string|null $changedAt = null
    ): void {
        if (!in_array($toStatus, self::inactiveStatuses(), true)) {
            throw new InvalidArgumentException('Status tujuan arsip tidak valid.');
        }

        if ($this->status === $toStatus) {
            throw new InvalidArgumentException('Status tujuan sama dengan status santri saat ini.');
        }

        if ($toStatus === self::STATUS_LULUS && !$semester) {
            throw new InvalidArgumentException('Semester kelulusan wajib ditentukan.');
        }

        $actorId = $changedBy ?? auth()->id();
        $changedAtValue = $changedAt
            ? Carbon::parse($changedAt)
            : now();

        DB::transaction(function () use (
            $toStatus,
            $reason,
            $semester,
            $actorId,
            $changedAtValue
        ): void {
            $fromStatus = $this->status;
            $oldKelasId = $this->kelas_id;
            $oldMusyrifId = $this->musyrif_id;
            $oldGraduatedSemesterId = $this->graduated_semester_id;
            $oldGraduatedAt = $this->graduated_at;

            $payload = [
                'status' => $toStatus,
                'status_changed_at' => $changedAtValue,
                'status_reason' => $reason,
                'status_changed_by' => $actorId,
                'musyrif_id' => null,
            ];

            if ($toStatus === self::STATUS_LULUS) {
                $payload['graduated_semester_id'] = $semester?->id;
                $payload['graduated_at'] = $changedAtValue;
            } else {
                $payload['graduated_semester_id'] = null;
                $payload['graduated_at'] = null;
            }

            $this->forceFill($payload)->save();

            $this->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'semester_id' => $toStatus === self::STATUS_LULUS
                    ? $semester?->id
                    : null,
                'kelas_id' => $oldKelasId,
                'musyrif_id' => $oldMusyrifId,
                'reason' => $reason,
                'changed_by' => $actorId,
                'changed_at' => $changedAtValue,
                'metadata' => [
                    'previous_graduated_semester_id' => $oldGraduatedSemesterId,
                    'previous_graduated_at' => $oldGraduatedAt?->toIso8601String(),
                ],
            ]);
        });
    }

    public function markAsGraduated(
        Semester $semester,
        ?string $reason = null,
        ?int $changedBy = null
    ): void {
        $this->changeStatus(
            self::STATUS_LULUS,
            $reason ?? 'Kelulusan melalui migrasi semester.',
            $semester,
            $changedBy
        );
    }

    public function reactivate(
        ?int $kelasId = null,
        ?int $musyrifId = null,
        ?string $reason = null,
        ?int $changedBy = null,
        CarbonInterface|string|null $changedAt = null
    ): void {
        if ($this->isActive()) {
            throw new InvalidArgumentException('Santri sudah berstatus aktif.');
        }

        $targetKelasId = $kelasId ?? $this->kelas_id;
        $targetMusyrifId = $musyrifId ?? $this->musyrif_id;

        if (!$targetKelasId) {
            throw new InvalidArgumentException('Kelas reaktivasi wajib ditentukan.');
        }

        if (!$targetMusyrifId) {
            throw new InvalidArgumentException('Musyrif reaktivasi wajib ditentukan.');
        }

        $actorId = $changedBy ?? auth()->id();
        $changedAtValue = $changedAt
            ? Carbon::parse($changedAt)
            : now();

        DB::transaction(function () use (
            $targetKelasId,
            $targetMusyrifId,
            $reason,
            $actorId,
            $changedAtValue
        ): void {
            $fromStatus = $this->status;
            $oldKelasId = $this->kelas_id;
            $oldMusyrifId = $this->musyrif_id;
            $oldGraduatedSemesterId = $this->graduated_semester_id;

            $this->forceFill([
                'status' => self::STATUS_AKTIF,
                'kelas_id' => $targetKelasId,
                'musyrif_id' => $targetMusyrifId,
                'graduated_semester_id' => null,
                'graduated_at' => null,
                'status_changed_at' => $changedAtValue,
                'status_reason' => $reason,
                'status_changed_by' => $actorId,
            ])->save();

            $this->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => self::STATUS_AKTIF,
                'semester_id' => $oldGraduatedSemesterId,
                'kelas_id' => $targetKelasId,
                'musyrif_id' => $targetMusyrifId,
                'reason' => $reason,
                'changed_by' => $actorId,
                'changed_at' => $changedAtValue,
                'metadata' => [
                    'previous_kelas_id' => $oldKelasId,
                    'previous_musyrif_id' => $oldMusyrifId,
                ],
            ]);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class, 'musyrif_id');
    }

    public function graduatedSemester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'graduated_semester_id');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SantriStatusHistory::class, 'santri_id')
            ->latest('changed_at');
    }

    public function semesterPlacements(): HasMany
    {
        return $this->hasMany(
            SantriSemesterPlacement::class,
            'santri_id'
        )->orderByDesc('semester_id');
    }

    public function placementForSemester(
        int $semesterId
    ): ?SantriSemesterPlacement {
        return $this->semesterPlacements()
            ->where(
                'semester_id',
                $semesterId
            )
            ->first();
    }

    public function hafalans(): HasMany
    {
        return $this->hasMany(Hafalan::class, 'santri_id');
    }

    public function tahsins(): HasMany
    {
        return $this->hasMany(Tahsin::class, 'santri_id');
    }

    public function tilawahs(): HasMany
    {
        return $this->hasMany(Tilawah::class, 'santri_id');
    }

    public function academicDocuments(): HasMany
    {
        return $this->hasMany(
            AcademicDocument::class
        );
    }

    public function raportDocuments(): HasMany
    {
        return $this
            ->academicDocuments()
            ->where(
                'document_type',
                AcademicDocument::TYPE_RAPORT
            );
    }

    private array $namaAliases = [
        'nama',
        'name',
        'nama santri',
        'nama siswa',
        'siswa',
        'murid',
        'nama murid',
        'nama lengkap',
        'student name',
    ];
}
