<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hafalans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('santri_id')
                ->constrained('santris')
                ->cascadeOnDelete();

            $table->foreignId('musyrif_id')
                ->constrained('musyrifs')
                ->cascadeOnDelete();

            // Target dan realisasi hafalan
            $table->unsignedTinyInteger('juz')->nullable();  // 1–30
            $table->string('surah', 100)->nullable();         // misal "Al-Baqarah"
            $table->unsignedSmallInteger('ayat_awal')->nullable();
            $table->unsignedSmallInteger('ayat_akhir')->nullable();

            // alternatif kalau mau simpan format bebas seperti di buku ("Al-Baqarah 1–16")
            $table->string('rentang_ayat_label', 150)->nullable();

            $table->date('tanggal_setoran')->nullable();

            $table->unsignedTinyInteger('nilai')->nullable();  // 0–100
            $table->enum('tahap', ['tahap_1', 'tahap_2', 'tahap_3'])
                ->nullable();  // sesuai buku: tahap pertama/kedua/ketiga

            $table->enum('status', ['lulus', 'ulang', 'proses'])
                ->default('proses');

            $table->text('catatan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hafalans');
    }
};
