<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('santris', function (Blueprint $table) {
            $table->id();

            // Kalau mau hubungkan ke users (login santri)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('kelas_id')
                ->constrained('kelas')
                ->cascadeOnDelete();

            $table->string('nama', 150);
            $table->string('nis', 50)->nullable();          // nomor induk santri
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 10)->nullable(); // 'L' / 'P' opsional

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santris');
    }
};
