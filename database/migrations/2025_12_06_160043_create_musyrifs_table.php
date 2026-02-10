<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('musyrifs', function (Blueprint $table) {
            $table->id();

            // Hubungkan ke users untuk login musyrif
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('nama', 150);
            $table->string('kode', 50)->nullable();   // kode musyrif jika ada
            $table->text('keterangan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musyrifs');
    }
};
