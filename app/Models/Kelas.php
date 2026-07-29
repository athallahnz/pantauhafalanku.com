<?php

namespace App\Models;

use App\Models\Concerns\HasKelasHierarchy;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kelas extends Model
{
    public const GENDER_PUTRA = 'L';
    public const GENDER_PUTRI = 'P';
    public const GENDER_MIXED = 'MIXED';

    use HasFactory;
    use HasKelasHierarchy;
    use LogsActivity;

    protected $table = 'kelas';

    protected $fillable = [
        'parent_id',
        'nama_kelas',
        'kelompok',
        'jenis_kelamin',
        'kode',
        'deskripsi',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    public static function normalizeGender(mixed $value): ?string
    {
        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            'l', 'lk', 'laki-laki', 'laki laki', 'male', 'putra' => self::GENDER_PUTRA,
            'p', 'pr', 'perempuan', 'female', 'putri' => self::GENDER_PUTRI,
            'mixed', 'campuran', 'gabungan', 'semua' => self::GENDER_MIXED,
            default => null,
        };
    }

    public function genderLabel(): string
    {
        return match (self::normalizeGender($this->jenis_kelamin)) {
            self::GENDER_PUTRA => 'Putra',
            self::GENDER_PUTRI => 'Putri',
            self::GENDER_MIXED => 'Campuran',
            default => 'Belum diatur',
        };
    }

    /**
     * Kelas MIXED/NULL tetap kompatibel untuk rollout data lama.
     */
    public function acceptsGender(mixed $gender): bool
    {
        $requested = self::normalizeGender($gender);
        $classGender = self::normalizeGender($this->jenis_kelamin);

        if ($requested === null) {
            return true;
        }

        return $classGender === null
            || $classGender === self::GENDER_MIXED
            || $classGender === $requested;
    }

    public function scopeCompatibleWithGender(
        Builder $query,
        mixed $gender,
        bool $includeUnassigned = true
    ): Builder {
        $normalized = self::normalizeGender($gender);

        if ($normalized === null) {
            return $query;
        }

        return $query->where(function (Builder $genderQuery) use (
            $normalized,
            $includeUnassigned
        ): void {
            $genderQuery->whereIn('jenis_kelamin', [
                $normalized,
                self::GENDER_MIXED,
            ]);

            if ($includeUnassigned) {
                $genderQuery->orWhereNull('jenis_kelamin');
            }
        });
    }

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class, 'kelas_id');
    }

    /**
     * Musyrif yang menjadikan kelas ini sebagai kelas operasional utama.
     */
    public function musyrifs(): HasMany
    {
        return $this->hasMany(Musyrif::class, 'kelas_id');
    }

    /**
     * Musyrif yang menjadikan parent ini sebagai tingkat utama administratif.
     */
    public function musyrifInduks(): HasMany
    {
        return $this->hasMany(Musyrif::class, 'kelas_induk_id');
    }

    /**
     * Musyrif yang membina kelas operasional ini melalui pivot musyrif_kelas.
     */
    public function musyrifBinaan(): BelongsToMany
    {
        return $this->belongsToMany(
            Musyrif::class,
            'musyrif_kelas',
            'kelas_id',
            'musyrif_id'
        )->withTimestamps();
    }

    public function semesterPlacements(): HasMany
    {
        return $this->hasMany(SantriSemesterPlacement::class, 'kelas_id');
    }

    public function kelasHistories(): HasMany
    {
        return $this->hasMany(SantriKelasHistory::class, 'kelas_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(SantriStatusHistory::class, 'kelas_id');
    }

    /**
     * Kode tingkat kanonis untuk membedakan SMP/SMA dan jalur Reg/INT.
     * Contoh: K07, K09, K10, K10I, K12I.
     */
    public function canonicalCode(): ?string
    {
        $source = $this->isKelompok() && $this->relationLoaded('parent')
            ? ($this->parent?->kode ?: $this->parent?->nama_kelas)
            : ($this->kode ?: $this->nama_kelas);

        if (!$source) {
            return null;
        }

        $normalized = strtoupper(trim((string) $source));
        $compact = preg_replace('/[^A-Z0-9]/', '', $normalized) ?: '';

        if (preg_match('/^K(0?7|0?8|0?9|10|11|12)(I)?$/', $compact, $match)) {
            $grade = str_pad((string) ((int) $match[1]), 2, '0', STR_PAD_LEFT);
            return 'K' . $grade . (!empty($match[2]) ? 'I' : '');
        }

        if (preg_match('/KELAS\s*(7|8|9|10|11|12)\s*(INT|INTEGRATED)?/i', $normalized, $match)) {
            $grade = str_pad((string) ((int) $match[1]), 2, '0', STR_PAD_LEFT);
            return 'K' . $grade . (!empty($match[2]) ? 'I' : '');
        }

        return null;
    }

    public function gradeLevel(): ?int
    {
        $code = $this->canonicalCode();

        if (!$code || !preg_match('/^K(\d{2})I?$/', $code, $match)) {
            return null;
        }

        return (int) $match[1];
    }

    /**
     * SMP (7-9) memakai kelompok alfabet, SMA (10-12) memakai angka.
     */
    public function groupMode(): string
    {
        $grade = $this->gradeLevel();

        return $grade !== null && $grade >= 10
            ? 'numeric'
            : 'alpha';
    }

    public function usesNumericGroups(): bool
    {
        return $this->groupMode() === 'numeric';
    }

    public function normalizeGroupValue(string $value): string
    {
        $value = strtoupper(trim($value));

        if ($this->usesNumericGroups()) {
            return preg_match('/^[1-9][0-9]*$/', $value)
                ? (string) ((int) $value)
                : $value;
        }

        return $value;
    }
}
