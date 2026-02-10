<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('musyrif_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('musyrif_id')->constrained('musyrifs')->cascadeOnDelete();

            $table->enum('type', ['in', 'out'])->index();
            $table->dateTime('attendance_at')->index();

            $table->string('photo_path'); // storage path
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('accuracy')->nullable(); // meters

            $table->string('address_text')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('device_info')->nullable();

            $table->enum('status', ['valid', 'suspect', 'rejected'])->default('valid')->index();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Optional: cegah double check-in/out dalam 1 hari (boleh Anda aktifkan jika kebijakan begitu)
            // $table->unique(['musyrif_id', 'type', 'attendance_at']); // tidak cocok kalau jam beda
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musyrif_attendances');
    }
};
