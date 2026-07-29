<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Kondisi awal dump:
         * santris.kelas_id -> kelas.id ON DELETE CASCADE
         *
         * Itu berbahaya karena menghapus kelas dapat ikut menghapus santri.
         * Diubah menjadi RESTRICT agar kelas yang masih dipakai tidak dapat
         * dihapus secara tidak sengaja.
         */
        Schema::table('santris', function (Blueprint $table) {
            $table->dropForeign('santris_kelas_id_foreign');
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->foreign(
                'kelas_id',
                'santris_kelas_id_foreign'
            )
                ->references('id')
                ->on('kelas')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropForeign('santris_kelas_id_foreign');
        });

        /*
         * Mengembalikan perilaku lama hanya saat rollback migration.
         * Perilaku CASCADE ini tidak direkomendasikan untuk produksi.
         */
        Schema::table('santris', function (Blueprint $table) {
            $table->foreign(
                'kelas_id',
                'santris_kelas_id_foreign'
            )
                ->references('id')
                ->on('kelas')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
};
