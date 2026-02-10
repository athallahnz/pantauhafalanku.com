<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surah_segments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hafalan_template_id')
                ->constrained('hafalan_templates')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('surah_id');
            $table->foreign('surah_id')->references('id')->on('surahs');

            $table->unsignedSmallInteger('ayat_awal');
            $table->unsignedSmallInteger('ayat_akhir');

            // urutan segmen dalam satu template (1..n)
            $table->unsignedTinyInteger('urutan_segmen')->default(1);

            $table->timestamps();

            $table->index(['hafalan_template_id'], 'idx_segments_template');
            $table->unique(['hafalan_template_id', 'urutan_segmen'], 'uniq_segments_template_urutan');

            // Opsional: Cegah duplikasi segmen identik
            $table->unique(
                ['hafalan_template_id', 'surah_id', 'ayat_awal', 'ayat_akhir'],
                'uniq_segments_template_surah_range'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surah_segments');
    }
};
