<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'santri_semester_placements',
            function (Blueprint $table): void {
                $table->id();

                $table->foreignId('santri_id')
                    ->constrained('santris')
                    ->restrictOnDelete();

                $table->foreignId('semester_id')
                    ->constrained('semesters')
                    ->restrictOnDelete();

                $table->foreignId('kelas_id')
                    ->nullable()
                    ->constrained('kelas')
                    ->nullOnDelete();

                $table->foreignId('musyrif_id')
                    ->nullable()
                    ->constrained('musyrifs')
                    ->nullOnDelete();

                $table->string('status', 20)
                    ->default('aktif')
                    ->index();

                $table->string('placement_type', 30)
                    ->default('penempatan')
                    ->index();

                $table->timestamp('started_at')
                    ->nullable();

                $table->timestamp('ended_at')
                    ->nullable();

                $table->uuid('migration_batch_id')
                    ->nullable();

                $table->foreign('migration_batch_id')
                    ->references('id')
                    ->on('santri_migration_batches')
                    ->nullOnDelete();

                $table->foreignId('migration_batch_item_id')
                    ->nullable()
                    ->constrained(
                        'santri_migration_batch_items'
                    )
                    ->nullOnDelete();

                $table->text('note')->nullable();
                $table->json('metadata')->nullable();

                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique(
                    ['santri_id', 'semester_id'],
                    'ssp_santri_semester_unique'
                );

                $table->index(
                    ['semester_id', 'kelas_id', 'status'],
                    'ssp_semester_kelas_status_idx'
                );

                $table->index(
                    ['semester_id', 'musyrif_id', 'status'],
                    'ssp_semester_musyrif_status_idx'
                );

                $table->index(
                    [
                        'migration_batch_id',
                        'migration_batch_item_id',
                    ],
                    'ssp_batch_item_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'santri_semester_placements'
        );
    }
};
