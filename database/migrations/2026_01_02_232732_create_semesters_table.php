<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tahun_ajaran_id')
                ->constrained('tahun_ajarans')
                ->cascadeOnDelete();

            $table->enum('nama', ['ganjil', 'genap']);
            $table->boolean('is_active')->default(false);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            $table->timestamps();

            // Satu tahun ajaran hanya punya 1 ganjil + 1 genap
            $table->unique(['tahun_ajaran_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
