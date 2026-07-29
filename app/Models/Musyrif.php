<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;

class Musyrif extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'nama',
        'kode',
        'jenis_kelamin',
        'keterangan',
        'kelas_induk_id',
        'kelas_id',
        'alamat',
        'pendidikan_terakhir',
        'domisili',
        'halaqah',
        'lama_mengabdi',
        'amanah_lain',
        'metode_alquran',
        'is_sertifikasi_ummi',
        'tahun_sertifikasi',
        'siap_sertifikasi',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'kelas_induk_id' => 'integer',
        'kelas_id' => 'integer',
        'is_sertifikasi_ummi' => 'boolean',
        'tahun_sertifikasi' => 'integer',
    ];

    protected static function booted(): void
    {
        /*
         * Jalur lama/import yang hanya mengisi kelas_id tetap otomatis
         * mendapatkan kelas_induk_id dari parent kelas operasional.
         */
        static::saving(function (self $musyrif): void {
            if (!$musyrif->kelas_id || !Schema::hasColumn('musyrifs', 'kelas_induk_id')) {
                return;
            }

            $kelas = Kelas::query()
                ->select(['id', 'parent_id'])
                ->find($musyrif->kelas_id);

            if (!$kelas) {
                return;
            }

            $musyrif->kelas_induk_id = $kelas->parent_id
                ? (int) $kelas->parent_id
                : (int) $kelas->id;
        });

        /*
         * Pivot merupakan sumber kelas binaan. Kelas utama selalu dijaga
         * sebagai salah satu anggota pivot untuk kompatibilitas modul lama.
         */
        static::saved(function (self $musyrif): void {
            if (
                $musyrif->kelas_id
                && Schema::hasTable('musyrif_kelas')
            ) {
                $musyrif->kelasBinaan()->syncWithoutDetaching([
                    (int) $musyrif->kelas_id,
                ]);
            }
        });
    }

    public function scopeUsingLegacyClass(Builder $query): Builder
    {
        return $query->whereHas(
            'kelas',
            fn (Builder $kelasQuery) => $kelasQuery->whereNull('parent_id')
        );
    }

    public function scopeUsingOperationalClass(Builder $query): Builder
    {
        return $query->whereHas(
            'kelas',
            fn (Builder $kelasQuery) => $kelasQuery
                ->whereNotNull('parent_id')
                ->where('is_active', true)
        );
    }

    public function scopeForKelas(Builder $query, int $kelasId): Builder
    {
        if (Schema::hasTable('musyrif_kelas')) {
            return $query->whereHas(
                'kelasBinaan',
                fn (Builder $kelasQuery) => $kelasQuery->where('kelas.id', $kelasId)
            );
        }

        return $query->where('musyrifs.kelas_id', $kelasId);
    }

    /** @param array<int, int> $kelasIds */
    public function scopeForAnyKelas(Builder $query, array $kelasIds): Builder
    {
        $kelasIds = collect($kelasIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($kelasIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if (Schema::hasTable('musyrif_kelas')) {
            return $query->whereHas(
                'kelasBinaan',
                fn (Builder $kelasQuery) => $kelasQuery->whereIn('kelas.id', $kelasIds)
            );
        }

        return $query->whereIn('musyrifs.kelas_id', $kelasIds);
    }

    public function scopeForKelasInduk(Builder $query, int $kelasIndukId): Builder
    {
        return $query->where(
            'musyrifs.kelas_induk_id',
            $kelasIndukId
        );
    }

    public function handlesKelasInduk(int $kelasIndukId): bool
    {
        return (int) $this->kelas_induk_id === $kelasIndukId;
    }

    public function canHandleOperationalKelas(Kelas $kelas): bool
    {
        return $kelas->parent_id !== null
            && $kelas->is_active
            && $this->handlesKelasInduk((int) $kelas->parent_id);
    }

    /**
     * Pivot tetap menjadi sumber kelas binaan. Saat seorang santri ditugaskan,
     * kelas operasionalnya otomatis dicatat tanpa menghapus binaan lain.
     */
    public function ensureKelasBinaan(int $kelasId): void
    {
        if (!Schema::hasTable('musyrif_kelas')) {
            return;
        }

        $this->kelasBinaan()->syncWithoutDetaching([$kelasId]);

        if ($this->relationLoaded('kelasBinaan')) {
            $this->unsetRelation('kelasBinaan');
        }
    }

    public function usesLegacyParentClass(): bool
    {
        if (!$this->kelas_id) {
            return false;
        }

        if ($this->relationLoaded('kelas')) {
            return (bool) $this->kelas?->isInduk();
        }

        return $this->kelas()->whereNull('parent_id')->exists();
    }

    public function handlesKelas(int $kelasId): bool
    {
        if (!Schema::hasTable('musyrif_kelas')) {
            return (int) $this->kelas_id === $kelasId;
        }

        if ($this->relationLoaded('kelasBinaan')) {
            return $this->kelasBinaan->contains(
                fn (Kelas $kelas) => (int) $kelas->id === $kelasId
            );
        }

        return $this->kelasBinaan()
            ->where('kelas.id', $kelasId)
            ->exists();
    }

    /** @return array<int, int> */
    public function allKelasIds(): array
    {
        $ids = $this->relationLoaded('kelasBinaan')
            ? $this->kelasBinaan->pluck('id')
            : $this->kelasBinaan()->pluck('kelas.id');

        if ($ids->isEmpty() && $this->kelas_id) {
            $ids->push($this->kelas_id);
        }

        return $ids
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class, 'musyrif_id');
    }

    public function hafalans(): HasMany
    {
        return $this->hasMany(Hafalan::class, 'musyrif_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(MusyrifAttendance::class, 'musyrif_id');
    }

    /** Tingkat utama administratif, wajib berupa parent. */
    public function kelasInduk(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_induk_id');
    }

    /** Kelas operasional utama untuk kompatibilitas laporan lama. */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /** Semua kelas operasional yang benar-benar dibina. */
    public function kelasBinaan(): BelongsToMany
    {
        return $this->belongsToMany(
            Kelas::class,
            'musyrif_kelas',
            'musyrif_id',
            'kelas_id'
        )->withTimestamps();
    }

    public function kelasGroupAssignmentItems(): HasMany
    {
        return $this->hasMany(
            KelasGroupAssignmentItem::class,
            'musyrif_id'
        )->latest('id');
    }

    public function systemReview(): HasOne
    {
        return $this->hasOne(SystemReview::class);
    }
}
