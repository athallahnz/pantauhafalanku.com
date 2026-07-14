<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'academic_documents',
            function (Blueprint $table): void {
                $table
                    ->foreignId('cancelled_by')
                    ->nullable()
                    ->after('revocation_reason')
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->timestamp('cancelled_at')
                    ->nullable()
                    ->after('cancelled_by')
                    ->index();

                $table
                    ->text('cancellation_reason')
                    ->nullable()
                    ->after('cancelled_at');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'academic_documents',
            function (Blueprint $table): void {
                $table->dropConstrainedForeignId(
                    'cancelled_by'
                );

                $table->dropIndex([
                    'cancelled_at',
                ]);

                $table->dropColumn([
                    'cancelled_at',
                    'cancellation_reason',
                ]);
            }
        );
    }
};
