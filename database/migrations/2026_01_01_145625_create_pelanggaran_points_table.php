<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pelanggaran_points', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained('santris')->cascadeOnDelete();
            $table->foreignId('musyrif_id')->constrained('musyrifs')->cascadeOnDelete();

            // optional link ke hafalan (rekam jejak)
            $table->foreignId('hafalan_id')->nullable()->constrained('hafalans')->nullOnDelete();

            $table->date('tanggal');
            $table->unsignedTinyInteger('poin')->default(1); // alpha = 1
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            // Cegah dobel poin alpha per hari untuk santri yang sama
            $table->unique(['santri_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelanggaran_points');
    }
};
