<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table): void {
            $table->timestamp('status_changed_at')
                ->nullable()
                ->after('graduated_at')
                ->index();

            $table->text('status_reason')
                ->nullable()
                ->after('status_changed_at');

            $table->foreignId('status_changed_by')
                ->nullable()
                ->after('status_reason')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::create('santri_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('santri_id')
                ->constrained('santris')
                ->restrictOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->index();
            $table->foreignId('semester_id')
                ->nullable()
                ->constrained('semesters')
                ->nullOnDelete();
            $table->foreignId('kelas_id')
                ->nullable()
                ->constrained('kelas')
                ->nullOnDelete();
            $table->foreignId('musyrif_id')
                ->nullable()
                ->constrained('musyrifs')
                ->nullOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('changed_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['santri_id', 'changed_at'], 'ssh_santri_changed_idx');
        });

        DB::table('santris')
            ->whereIn('status', ['lulus', 'keluar', 'nonaktif'])
            ->whereNull('status_changed_at')
            ->update([
                'status_changed_at' => DB::raw('COALESCE(graduated_at, updated_at)'),
            ]);

        DB::table('santris')
            ->select([
                'id',
                'status',
                'graduated_semester_id',
                'kelas_id',
                'musyrif_id',
                'graduated_at',
                'status_changed_at',
                'created_at',
                'updated_at',
            ])
            ->whereIn('status', ['lulus', 'keluar', 'nonaktif'])
            ->orderBy('id')
            ->chunkById(500, function ($santris): void {
                $now = now();
                $rows = [];

                foreach ($santris as $santri) {
                    $rows[] = [
                        'santri_id' => $santri->id,
                        'from_status' => null,
                        'to_status' => $santri->status,
                        'semester_id' => $santri->graduated_semester_id,
                        'kelas_id' => $santri->kelas_id,
                        'musyrif_id' => $santri->musyrif_id,
                        'reason' => 'Backfill status existing sebelum modul arsip.',
                        'changed_by' => null,
                        'changed_at' => $santri->status_changed_at
                            ?? $santri->graduated_at
                            ?? $santri->updated_at
                            ?? $santri->created_at
                            ?? $now,
                        'metadata' => json_encode([
                            'source' => 'migration_backfill',
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('santri_status_histories')->insert($rows);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('santri_status_histories');

        Schema::table('santris', function (Blueprint $table): void {
            $table->dropForeign(['status_changed_by']);
            $table->dropIndex(['status_changed_at']);
            $table->dropColumn([
                'status_changed_at',
                'status_reason',
                'status_changed_by',
            ]);
        });
    }
};
