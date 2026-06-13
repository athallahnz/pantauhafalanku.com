<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'santri_migration_batches',
            function (Blueprint $table): void {
                $table->foreignId('rolled_back_by')
                    ->nullable()
                    ->after('executed_by')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('rolled_back_at')
                    ->nullable()
                    ->after('cancelled_at')
                    ->index();

                $table->text('rollback_reason')
                    ->nullable()
                    ->after('rolled_back_at');

                $table->json('rollback_metadata')
                    ->nullable()
                    ->after('rollback_reason');

                $table->text('rollback_error')
                    ->nullable()
                    ->after('rollback_metadata');
            }
        );

        Schema::table(
            'santri_migration_batch_items',
            function (Blueprint $table): void {
                $table->timestamp('rolled_back_at')
                    ->nullable()
                    ->after('executed_at');

                $table->text('rollback_error')
                    ->nullable()
                    ->after('rolled_back_at');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'santri_migration_batch_items',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'rolled_back_at',
                    'rollback_error',
                ]);
            }
        );

        Schema::table(
            'santri_migration_batches',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'rolled_back_by',
                ]);

                $table->dropIndex([
                    'rolled_back_at',
                ]);

                $table->dropColumn([
                    'rolled_back_by',
                    'rolled_back_at',
                    'rollback_reason',
                    'rollback_metadata',
                    'rollback_error',
                ]);
            }
        );
    }
};
