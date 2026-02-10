<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('santri_kelas_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('santri_id')
                ->constrained('santris')
                ->cascadeOnDelete();

            $table->foreignId('semester_id')
                ->constrained('semesters')
                ->cascadeOnDelete();

            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->restrictOnDelete();

            $table->foreignId('musyrif_id')
                ->nullable()
                ->constrained('musyrifs')
                ->nullOnDelete();

            $table->enum('tipe', [
                'penempatan',
                'mutasi',
                'naik_kelas',
                'tinggal_kelas',
                'lulus'
            ])->default('penempatan');

            $table->text('catatan')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // Satu santri hanya boleh satu kelas per semester
            $table->unique(['santri_id', 'semester_id']);

            // Optimasi query laporan
            $table->index(['semester_id', 'kelas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santri_kelas_histories');
    }
};
