<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('musyrifs', function (Blueprint $table) {
            // 1. Relasi ke Tabel Kelas (Sesudah user_id)
            $table->foreignId('kelas_id')->after('user_id')->nullable()->constrained('kelas')->onDelete('set null');

            // 2. Data Profil & Domisili (Sesuai Excel)
            $table->text('alamat')->after('nama')->nullable();
            $table->string('pendidikan_terakhir', 50)->after('alamat')->nullable();
            $table->string('domisili', 50)->after('pendidikan_terakhir')->nullable(); // Mukim / Luar Pondok

            // 3. Data Halaqah & Pengabdian
            $table->string('halaqah', 50)->after('domisili')->nullable();
            $table->string('lama_mengabdi', 50)->nullable();
            $table->text('amanah_lain')->nullable(); // Amanah lain di pondok

            // 4. Data Sertifikasi Al-Qur'an
            $table->string('metode_alquran')->nullable();
            $table->boolean('is_sertifikasi_ummi')->default(false);
            $table->integer('tahun_sertifikasi')->nullable();
            $table->string('siap_sertifikasi')->nullable(); // Siap / Belum
        });
    }

    public function down(): void
    {
        Schema::table('musyrifs', function (Blueprint $table) {
            $table->dropForeign(['kelas_id']);
            $table->dropColumn([
                'kelas_id',
                'alamat',
                'pendidikan_terakhir',
                'domisili',
                'halaqah',
                'lama_mengabdi',
                'amanah_lain',
                'metode_alquran',
                'is_sertifikasi_ummi',
                'tahun_sertifikasi',
                'siap_sertifikasi'
            ]);
        });
    }
};
