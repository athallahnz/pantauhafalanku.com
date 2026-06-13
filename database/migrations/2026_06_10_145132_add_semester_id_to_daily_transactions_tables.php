<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hafalans', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->cascadeOnDelete();
        });

        Schema::table('tahsins', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->cascadeOnDelete();
        });

        Schema::table('tilawahs', function (Blueprint $table) {
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_transactions_tables', function (Blueprint $table) {
            //
        });
    }
};
