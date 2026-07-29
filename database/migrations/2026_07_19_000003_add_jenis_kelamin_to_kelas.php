<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('kelas', 'jenis_kelamin')) {
            Schema::table('kelas', function (Blueprint $table): void {
                $table->string('jenis_kelamin', 10)
                    ->nullable()
                    ->after('kelompok')
                    ->comment('L=Putra, P=Putri, MIXED=Campuran');

                $table->index(
                    'jenis_kelamin',
                    'kelas_jenis_kelamin_index'
                );
            });
        }

        $this->backfillOperationalClasses();
        $this->backfillParentClasses();
    }

    public function down(): void
    {
        if (!Schema::hasColumn('kelas', 'jenis_kelamin')) {
            return;
        }

        Schema::table('kelas', function (Blueprint $table): void {
            $table->dropColumn('jenis_kelamin');
        });
    }

    private function backfillOperationalClasses(): void
    {
        $classes = DB::table('kelas')
            ->whereNotNull('parent_id')
            ->orderBy('id')
            ->get(['id']);

        foreach ($classes as $kelas) {
            $studentGenders = DB::table('santris')
                ->where('kelas_id', $kelas->id)
                ->whereRaw("LOWER(TRIM(COALESCE(status, ''))) = 'aktif'")
                ->pluck('jenis_kelamin')
                ->map(fn ($value): ?string => $this->normalizeGender($value))
                ->filter()
                ->unique()
                ->values();

            $resolved = $this->resolveGenderCollection($studentGenders);

            if ($resolved === null && Schema::hasTable('musyrif_kelas')) {
                $musyrifGenders = DB::table('musyrif_kelas as mk')
                    ->join('musyrifs as m', 'm.id', '=', 'mk.musyrif_id')
                    ->where('mk.kelas_id', $kelas->id)
                    ->pluck('m.jenis_kelamin')
                    ->map(fn ($value): ?string => $this->normalizeGender($value))
                    ->filter()
                    ->unique()
                    ->values();

                $resolved = $this->resolveGenderCollection(
                    $musyrifGenders
                );
            }

            if ($resolved !== null) {
                DB::table('kelas')
                    ->where('id', $kelas->id)
                    ->whereNull('jenis_kelamin')
                    ->update($this->genderUpdatePayload($resolved));
            }
        }
    }

    private function backfillParentClasses(): void
    {
        $parents = DB::table('kelas')
            ->whereNull('parent_id')
            ->orderBy('id')
            ->get(['id']);

        foreach ($parents as $parent) {
            $childGenders = DB::table('kelas')
                ->where('parent_id', $parent->id)
                ->pluck('jenis_kelamin')
                ->map(fn ($value): ?string => $this->normalizeClassGender($value))
                ->filter()
                ->unique()
                ->values();

            if ($childGenders->isEmpty()) {
                continue;
            }

            $resolved = $childGenders->count() === 1
                ? $childGenders->first()
                : 'MIXED';

            DB::table('kelas')
                ->where('id', $parent->id)
                ->whereNull('jenis_kelamin')
                ->update($this->genderUpdatePayload($resolved));
        }
    }

    /** @return array<string, mixed> */
    private function genderUpdatePayload(string $gender): array
    {
        $payload = ['jenis_kelamin' => $gender];

        if (Schema::hasColumn('kelas', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        return $payload;
    }

    private function resolveGenderCollection($genders): ?string
    {
        $hasPutra = $genders->contains('L');
        $hasPutri = $genders->contains('P');

        if ($hasPutra && $hasPutri) {
            return 'MIXED';
        }

        if ($hasPutra) {
            return 'L';
        }

        if ($hasPutri) {
            return 'P';
        }

        return null;
    }

    private function normalizeGender(mixed $value): ?string
    {
        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            'l', 'lk', 'laki-laki', 'laki laki', 'male', 'putra' => 'L',
            'p', 'pr', 'perempuan', 'female', 'putri' => 'P',
            default => null,
        };
    }

    private function normalizeClassGender(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        return in_array($normalized, ['L', 'P', 'MIXED'], true)
            ? $normalized
            : null;
    }
};
