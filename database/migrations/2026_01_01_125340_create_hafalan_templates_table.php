<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hafalan_templates', function (Blueprint $table) {
            $table->id();

            $table->unsignedTinyInteger('juz'); // 1..30

            $table->enum('tahap', [
                'harian',
                'tahap_1',
                'tahap_2',
                'tahap_3',
                'ujian_akhir',
            ]);

            // urutan dalam (juz, tahap) mis: harian 1..20, tahap_1 1..10, dst.
            $table->unsignedTinyInteger('urutan');

            // label display, mis: "Al-Fatihah – Al-Baqarah : 1 – 16"
            // atau "Al-Baqarah : 17 – 24"
            $table->string('label', 180)->nullable();

            $table->timestamps();

            // Cegah template dobel
            $table->unique(['juz', 'tahap', 'urutan'], 'uniq_template_juz_tahap_urutan');
            $table->index(['juz', 'tahap'], 'idx_template_juz_tahap');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hafalan_templates');
    }
};
