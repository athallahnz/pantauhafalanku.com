<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santri_migration_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();
            $table->string('mode', 20)->index();
            $table->string('status', 20)->default('previewed')->index();

            $table->foreignId('from_semester_id')
                ->constrained('semesters')
                ->restrictOnDelete();

            $table->foreignId('to_semester_id')
                ->constrained('semesters')
                ->restrictOnDelete();

            $table->foreignId('from_kelas_id')
                ->nullable()
                ->constrained('kelas')
                ->nullOnDelete();

            $table->foreignId('to_kelas_id')
                ->nullable()
                ->constrained('kelas')
                ->nullOnDelete();

            $table->string('transition_type', 30)->nullable();
            $table->boolean('include_graduation')->default(false);
            $table->char('snapshot_hash', 64)->nullable()->index();
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('completed_count')->default(0);
            $table->unsignedInteger('graduated_count')->default(0);
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->text('last_error')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('executed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('previewed_at')->nullable();
            $table->timestamp('executing_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['created_by', 'mode', 'status'],
                'smb_creator_mode_status_idx'
            );
        });

        Schema::create('santri_migration_batch_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('batch_id');

            $table->foreign('batch_id')
                ->references('id')
                ->on('santri_migration_batches')
                ->cascadeOnDelete();

            $table->foreignId('santri_id')
                ->nullable()
                ->constrained('santris')
                ->nullOnDelete();

            $table->foreignId('from_kelas_id')
                ->nullable()
                ->constrained('kelas')
                ->nullOnDelete();

            $table->foreignId('to_kelas_id')
                ->nullable()
                ->constrained('kelas')
                ->nullOnDelete();

            $table->foreignId('from_musyrif_id')
                ->nullable()
                ->constrained('musyrifs')
                ->nullOnDelete();

            $table->foreignId('to_musyrif_id')
                ->nullable()
                ->constrained('musyrifs')
                ->nullOnDelete();

            $table->string('transition_type', 30);
            $table->boolean('assignment_required')->default(false);
            $table->string('status', 20)->default('pending')->index();
            $table->char('source_hash', 64);
            $table->json('source_snapshot');
            $table->json('target_snapshot')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['batch_id', 'santri_id'],
                'smbi_batch_santri_unique'
            );

            $table->index(
                ['batch_id', 'status'],
                'smbi_batch_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santri_migration_batch_items');
        Schema::dropIfExists('santri_migration_batches');
    }
};
