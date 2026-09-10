<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahsin_exams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('santri_id')
                ->constrained('santris')
                ->cascadeOnDelete();
            $table->foreignId('musyrif_id')
                ->nullable()
                ->constrained('musyrifs')
                ->nullOnDelete();
            $table->foreignId('semester_id')
                ->nullable()
                ->constrained('semesters')
                ->nullOnDelete();
            $table->date('tanggal');
            $table->string('exam_type', 20);
            $table->string('buku', 30);
            $table->unsignedSmallInteger('attempt_number')->default(1);
            $table->string('grade_label', 30);
            $table->string('result', 20);
            $table->string('next_book', 30)->nullable();
            $table->uuid('submission_uuid')->unique();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'santri_id',
                    'semester_id',
                    'exam_type',
                    'buku',
                    'attempt_number',
                ],
                'tahsin_exams_attempt_unique'
            );
            $table->index(
                ['musyrif_id', 'tanggal'],
                'tahsin_exams_musyrif_date_index'
            );
            $table->index(
                ['santri_id', 'result'],
                'tahsin_exams_santri_result_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahsin_exams');
    }
};
