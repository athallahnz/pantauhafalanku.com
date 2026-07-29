<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            /*
             * parent_id:
             * - NULL  = kelas induk/tingkat, misalnya "Kelas 7"
             * - berisi ID kelas induk = kelompok/rombel, misalnya "Kelas 7 A"
             */
            $table->unsignedBigInteger('parent_id')
                ->nullable()
                ->after('id');

            /*
             * kelompok hanya dipakai oleh child/rombel.
             * Nilai yang diizinkan: A-Z.
             */
            $table->char('kelompok', 1)
                ->nullable()
                ->after('nama_kelas');

            /*
             * Kode stabil untuk integrasi/import pada tahap berikutnya.
             * Dibiarkan nullable agar data lama tidak perlu langsung diubah.
             */
            $table->string('kode', 30)
                ->nullable()
                ->after('kelompok');

            $table->boolean('is_active')
                ->default(true)
                ->after('kode');

            $table->unsignedSmallInteger('urutan')
                ->default(0)
                ->after('is_active');

            $table->index('parent_id', 'kelas_parent_id_index');
            $table->unique('kode', 'kelas_kode_unique');
            $table->unique(
                ['parent_id', 'kelompok'],
                'kelas_parent_kelompok_unique'
            );

            $table->foreign(
                'parent_id',
                'kelas_parent_id_foreign'
            )
                ->references('id')
                ->on('kelas')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        /*
         * Dump database memakai MariaDB 10.4.28.
         * CHECK ini menjaga nilai kelompok tetap NULL atau satu huruf A-Z.
         */
        DB::statement("
            ALTER TABLE `kelas`
            ADD CONSTRAINT `kelas_kelompok_check`
            CHECK (
                `kelompok` IS NULL
                OR `kelompok` REGEXP '^[A-Z]$'
            )
        ");
    }

    public function down(): void
    {
        /*
         * MariaDB memakai DROP CONSTRAINT untuk CHECK.
         * try/catch menjaga rollback tetap lanjut bila constraint sudah
         * dihapus manual sebelumnya.
         */
        try {
            DB::statement("
                ALTER TABLE `kelas`
                DROP CONSTRAINT `kelas_kelompok_check`
            ");
        } catch (Throwable $exception) {
            report($exception);
        }

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropForeign('kelas_parent_id_foreign');
            $table->dropUnique('kelas_parent_kelompok_unique');
            $table->dropUnique('kelas_kode_unique');
            $table->dropIndex('kelas_parent_id_index');

            $table->dropColumn([
                'parent_id',
                'kelompok',
                'kode',
                'is_active',
                'urutan',
            ]);
        });
    }
};
