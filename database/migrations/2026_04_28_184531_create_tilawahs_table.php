<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tilawahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->onDelete('cascade');
            $table->foreignId('musyrif_id')->constrained('musyrifs')->onDelete('cascade');
            $table->date('tanggal');

            // Relasi ke hafalan_templates sebagai target/bookmark
            $table->foreignId('hafalan_template_id')->constrained('hafalan_templates');

            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha'])->default('hadir');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tilawahs');
    }
};
