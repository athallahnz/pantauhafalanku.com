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
        Schema::create('tahsins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->onDelete('cascade');
            $table->foreignId('musyrif_id')->constrained('musyrifs')->onDelete('cascade');
            $table->date('tanggal');

            // Form Absensi
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha'])->default('hadir');

            // Form Progress (Nullable karena jika absen tidak ada progress)
            $table->enum('buku', [
                'ummi_1',
                'ummi_2',
                'ummi_3',
                'gharib_1',
                'gharib_2',
                'tajwid'
            ])->nullable();
            $table->integer('halaman')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();

            // Constraint agar 1 santri hanya punya 1 record per hari
            $table->unique(['santri_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tahsins');
    }
};
