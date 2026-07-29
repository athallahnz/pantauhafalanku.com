<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hafalans') || ! Schema::hasColumn('hafalans', 'status')) {
            return;
        }

        $this->ensureMysqlDriver();

        /*
         * Enum transisi menerima seluruh nilai lama dan nilai final sehingga
         * normalisasi tidak menyebabkan truncation pada MySQL strict mode.
         */
        DB::statement(<<<'SQL'
            ALTER TABLE hafalans
            MODIFY status ENUM(
                'lulus',
                'ulang',
                'proses',
                'hadir_tidak_setor',
                'alpha',
                'mardud',
                'sakit',
                'izin'
            ) NOT NULL DEFAULT 'hadir_tidak_setor'
        SQL);

        DB::table('hafalans')
            ->where('status', 'proses')
            ->update(['status' => 'hadir_tidak_setor']);

        if (Schema::hasColumn('hafalans', 'nilai_label')) {
            DB::table('hafalans')
                ->where('status', 'mardud')
                ->whereNull('nilai_label')
                ->update(['nilai_label' => 'mardud']);
        }

        DB::table('hafalans')
            ->where('status', 'mardud')
            ->update(['status' => 'ulang']);

        DB::statement(<<<'SQL'
            ALTER TABLE hafalans
            MODIFY status ENUM(
                'lulus',
                'ulang',
                'hadir_tidak_setor',
                'alpha',
                'sakit',
                'izin'
            ) NOT NULL DEFAULT 'hadir_tidak_setor'
        SQL);
    }

    public function down(): void
    {
        if (! Schema::hasTable('hafalans') || ! Schema::hasColumn('hafalans', 'status')) {
            return;
        }

        $this->ensureMysqlDriver();

        DB::table('hafalans')
            ->whereIn('status', ['sakit', 'izin'])
            ->update(['status' => 'hadir_tidak_setor']);

        DB::statement(<<<'SQL'
            ALTER TABLE hafalans
            MODIFY status ENUM(
                'lulus',
                'ulang',
                'hadir_tidak_setor',
                'alpha',
                'mardud'
            ) NOT NULL DEFAULT 'hadir_tidak_setor'
        SQL);
    }

    private function ensureMysqlDriver(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException(
                'Migration sinkronisasi enum hafalan hanya mendukung MySQL/MariaDB.'
            );
        }
    }
};
