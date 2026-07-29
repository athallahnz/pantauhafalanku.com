<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('musyrif_kelas')) {
            Schema::create('musyrif_kelas', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('musyrif_id')
                    ->constrained('musyrifs')
                    ->cascadeOnDelete();
                $table->foreignId('kelas_id')
                    ->constrained('kelas')
                    ->restrictOnDelete();
                $table->timestamps();

                $table->unique(
                    ['musyrif_id', 'kelas_id'],
                    'musyrif_kelas_unique'
                );
                $table->index(
                    ['kelas_id', 'musyrif_id'],
                    'musyrif_kelas_lookup_index'
                );
            });
        }

        $now = now();

        /* Kelas utama lama selalu dimasukkan sebagai salah satu kelas binaan. */
        DB::table('musyrifs')
            ->whereNotNull('kelas_id')
            ->select(['id', 'kelas_id'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($now): void {
                $payload = collect($rows)
                    ->map(fn ($row) => [
                        'musyrif_id' => (int) $row->id,
                        'kelas_id' => (int) $row->kelas_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($payload !== []) {
                    DB::table('musyrif_kelas')->insertOrIgnore($payload);
                }
            });

        /*
         * Sebaran santri aktif menjadi sumber backfill kelas binaan tambahan.
         * Contoh: satu halaqah berisi Kelas 7 A, 7 B, dan 7 C.
         */
        DB::table('santris')
            ->where('status', 'aktif')
            ->whereNotNull('musyrif_id')
            ->whereNotNull('kelas_id')
            ->select(['musyrif_id', 'kelas_id'])
            ->distinct()
            ->orderBy('musyrif_id')
            ->orderBy('kelas_id')
            ->chunk(500, function ($rows) use ($now): void {
                $payload = collect($rows)
                    ->map(fn ($row) => [
                        'musyrif_id' => (int) $row->musyrif_id,
                        'kelas_id' => (int) $row->kelas_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                if ($payload !== []) {
                    DB::table('musyrif_kelas')->insertOrIgnore($payload);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('musyrif_kelas');
    }
};
