<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('musyrifs', 'kelas_induk_id')) {
            Schema::table('musyrifs', function (Blueprint $table): void {
                $table->unsignedBigInteger('kelas_induk_id')
                    ->nullable()
                    ->after('kelas_id');

                $table->index(
                    'kelas_induk_id',
                    'musyrifs_kelas_induk_id_index'
                );
            });

            Schema::table('musyrifs', function (Blueprint $table): void {
                $table->foreign(
                    'kelas_induk_id',
                    'musyrifs_kelas_induk_id_foreign'
                )
                    ->references('id')
                    ->on('kelas')
                    ->onUpdate('cascade')
                    ->onDelete('restrict');
            });
        }

        /*
         * Backfill utama dari musyrifs.kelas_id.
         * Child -> parent_id, parent legacy -> id sendiri.
         */
        DB::statement("
            UPDATE musyrifs AS m
            JOIN kelas AS k ON k.id = m.kelas_id
            SET m.kelas_induk_id = COALESCE(k.parent_id, k.id)
            WHERE m.kelas_id IS NOT NULL
              AND m.kelas_induk_id IS NULL
        ");

        /*
         * Musyrif tanpa kelas utama dapat diinferensikan dari pivot selama
         * seluruh kelas binaannya masih berada pada satu parent yang sama.
         */
        if (Schema::hasTable('musyrif_kelas')) {
            DB::statement("
                UPDATE musyrifs AS m
                JOIN (
                    SELECT
                        mk.musyrif_id,
                        MIN(k.parent_id) AS parent_id
                    FROM musyrif_kelas AS mk
                    JOIN kelas AS k ON k.id = mk.kelas_id
                    WHERE k.parent_id IS NOT NULL
                    GROUP BY mk.musyrif_id
                    HAVING COUNT(DISTINCT k.parent_id) = 1
                ) AS inferred
                    ON inferred.musyrif_id = m.id
                SET m.kelas_induk_id = inferred.parent_id
                WHERE m.kelas_induk_id IS NULL
            ");
        }

        /*
         * Kelompok SMP tetap A-Z, sedangkan SMA dapat menggunakan angka
         * 1, 2, 3, dan seterusnya. VARCHAR(5) memberi ruang numbering aman.
         */
        $this->dropKelompokCheckIfExists();

        DB::statement("
            ALTER TABLE `kelas`
            MODIFY `kelompok` VARCHAR(5) NULL
        ");

        DB::statement("
            ALTER TABLE `kelas`
            ADD CONSTRAINT `kelas_kelompok_check`
            CHECK (
                `kelompok` IS NULL
                OR `kelompok` REGEXP '^[A-Z]$'
                OR `kelompok` REGEXP '^[1-9][0-9]*$'
            )
        ");
    }

    public function down(): void
    {
        $this->dropKelompokCheckIfExists();

        /* Numbering 1-26 dikembalikan ke A-Z agar rollback tetap valid. */
        DB::statement("
            UPDATE `kelas`
            SET `kelompok` = CHAR(64 + CAST(`kelompok` AS UNSIGNED))
            WHERE `kelompok` REGEXP '^[1-9][0-9]*$'
              AND CAST(`kelompok` AS UNSIGNED) BETWEEN 1 AND 26
        ");

        DB::statement("
            ALTER TABLE `kelas`
            MODIFY `kelompok` CHAR(1) NULL
        ");

        DB::statement("
            ALTER TABLE `kelas`
            ADD CONSTRAINT `kelas_kelompok_check`
            CHECK (
                `kelompok` IS NULL
                OR `kelompok` REGEXP '^[A-Z]$'
            )
        ");

        if (Schema::hasColumn('musyrifs', 'kelas_induk_id')) {
            Schema::table('musyrifs', function (Blueprint $table): void {
                try {
                    $table->dropForeign('musyrifs_kelas_induk_id_foreign');
                } catch (\Throwable $exception) {
                    report($exception);
                }

                try {
                    $table->dropIndex('musyrifs_kelas_induk_id_index');
                } catch (\Throwable $exception) {
                    report($exception);
                }

                $table->dropColumn('kelas_induk_id');
            });
        }
    }

    private function dropKelompokCheckIfExists(): void
    {
        $exists = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'kelas')
            ->where('CONSTRAINT_TYPE', 'CHECK')
            ->where('CONSTRAINT_NAME', 'kelas_kelompok_check')
            ->exists();

        if (!$exists) {
            return;
        }

        try {
            DB::statement("
                ALTER TABLE `kelas`
                DROP CONSTRAINT `kelas_kelompok_check`
            ");
        } catch (\Throwable) {
            DB::statement("
                ALTER TABLE `kelas`
                DROP CHECK `kelas_kelompok_check`
            ");
        }
    }
};
