<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('surahs', function (Blueprint $table) {
            $table->tinyIncrements('id'); // 1..114
            $table->string('nama', 100);  // contoh: Al-Fatihah
            $table->unsignedSmallInteger('jumlah_ayat');
            $table->string('nama_latin', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surahs');
    }
};
