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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->string('description');

            // Relasi Polymorphic untuk entitas yang diubah
            $table->nullableMorphs('subject', 'subject');

            // Relasi Polymorphic untuk aktor yang mengubah
            $table->nullableMorphs('causer', 'causer');

            // Menyimpan payload data (sebelum/sesudah)
            $table->json('properties')->nullable();

            // Menyimpan meta informasi perangkat/jaringan
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
