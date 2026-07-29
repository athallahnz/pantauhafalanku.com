<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'kelas_group_assignment_batches',
            function (Blueprint $table): void {
                $table->char('id', 36)->primary();
                $table->string('code', 50)->unique();
                $table->foreignId('semester_id')
                    ->nullable()
                    ->constrained('semesters')
                    ->nullOnDelete();
                $table->string('status', 20)
                    ->default('previewed')
                    ->index();
                $table->json('mapping')->nullable();
                $table->json('summary')->nullable();
                $table->char('checksum', 64)->nullable()->index();
                $table->text('note')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('previewed_at')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamp('rolled_back_at')->nullable();
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('executed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('rolled_back_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(
                    ['semester_id', 'status'],
                    'kga_batches_semester_status_idx'
                );
            }
        );

        Schema::create(
            'kelas_group_assignment_items',
            function (Blueprint $table): void {
                $table->id();
                $table->char('batch_id', 36);
                $table->string('entity_type', 20);
                $table->unsignedBigInteger('entity_id');
                $table->foreignId('santri_id')
                    ->nullable()
                    ->constrained('santris')
                    ->nullOnDelete();
                $table->foreignId('musyrif_id')
                    ->nullable()
                    ->constrained('musyrifs')
                    ->nullOnDelete();
                $table->foreignId('placement_id')
                    ->nullable()
                    ->constrained('santri_semester_placements')
                    ->nullOnDelete();
                $table->foreignId('semester_id')
                    ->nullable()
                    ->constrained('semesters')
                    ->nullOnDelete();
                $table->foreignId('from_kelas_id')
                    ->nullable()
                    ->constrained('kelas')
                    ->nullOnDelete();
                $table->foreignId('to_kelas_id')
                    ->nullable()
                    ->constrained('kelas')
                    ->nullOnDelete();
                $table->string('status', 20)
                    ->default('pending')
                    ->index();
                $table->json('snapshot_before');
                $table->json('snapshot_after')->nullable();
                $table->char('source_hash', 64);
                $table->char('target_hash', 64)->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->foreign('batch_id')
                    ->references('id')
                    ->on('kelas_group_assignment_batches')
                    ->cascadeOnDelete();

                $table->unique(
                    ['batch_id', 'entity_type', 'entity_id'],
                    'kga_items_batch_entity_unique'
                );

                $table->index(
                    ['batch_id', 'status'],
                    'kga_items_batch_status_idx'
                );

                $table->index(
                    ['entity_type', 'entity_id'],
                    'kga_items_entity_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'kelas_group_assignment_items'
        );

        Schema::dropIfExists(
            'kelas_group_assignment_batches'
        );
    }
};
