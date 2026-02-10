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
        Schema::table('hafalans', function (Blueprint $table) {
            $table->dropColumn([
                'juz',
                'surah',
                'ayat_awal',
                'ayat_akhir',
                'rentang_ayat_label',
                'nilai',
                'tahap',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('hafalans', function (Blueprint $table) {
            $table->unsignedTinyInteger('juz')->nullable();
            $table->string('surah', 100)->nullable();
            $table->unsignedSmallInteger('ayat_awal')->nullable();
            $table->unsignedSmallInteger('ayat_akhir')->nullable();
            $table->string('rentang_ayat_label', 150)->nullable();
            $table->unsignedTinyInteger('nilai')->nullable();
            $table->enum('tahap', ['tahap_1', 'tahap_2', 'tahap_3'])->nullable();
        });
    }
};
