<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hafalans', function (Blueprint $table) {

            // 1) Pastikan tanggal_setoran default hari ini (kalau sebelumnya nullable)
            //    Kita biarkan tetap ada, tapi ubah jadi default current_date dan NOT NULL (opsional).
            //    Jika data lama banyak NULL, set dulu nilainya via DB::statement sebelum NOT NULL.
            if (Schema::hasColumn('hafalans', 'tanggal_setoran')) {
                // MySQL: default CURRENT_DATE tidak selalu didukung oleh Blueprint secara langsung
                // jadi pakai statement agar aman.
                DB::statement("ALTER TABLE hafalans MODIFY tanggal_setoran DATE NULL");
            } else {
                $table->date('tanggal_setoran')->nullable();
            }

            // 2) Tambah kolom relasi ke template (nullable karena ada alpha/hadir_tidak_setor)
            if (!Schema::hasColumn('hafalans', 'hafalan_template_id')) {
                $table->foreignId('hafalan_template_id')
                    ->nullable()
                    ->after('tanggal_setoran')
                    ->constrained('hafalan_templates')
                    ->nullOnDelete();
            }

            // 3) Nilai berbasis label (mumtaz/jayyid_jiddan/jayyid)
            if (!Schema::hasColumn('hafalans', 'nilai_label')) {
                $table->enum('nilai_label', ['mumtaz', 'jayyid_jiddan', 'jayyid'])
                    ->nullable()
                    ->after('hafalan_template_id');
            }

            // 4) Ubah enum status menjadi yang operasional (lulus/ulang/hadir_tidak_setor/alpha)
            //    Perubahan enum perlu statement khusus di MySQL.
        });

        // Ubah status ENUM via SQL agar tidak tergantung DBAL
        // Jika sebelumnya enum('lulus','ulang','proses'), kita map:
        // 'proses' -> 'hadir_tidak_setor' (atau Anda bisa pilih 'alpha')
        DB::statement("
            ALTER TABLE hafalans
            MODIFY status ENUM('lulus','ulang','hadir_tidak_setor','alpha')
            NOT NULL DEFAULT 'hadir_tidak_setor'
        ");

        // Map data lama: status 'proses' -> 'hadir_tidak_setor'
        DB::statement("
            UPDATE hafalans
            SET status = 'hadir_tidak_setor'
            WHERE status = 'proses'
        ");

        // 5) Optional: Tambah index/unique untuk mencegah dobel input template yang sama di tanggal yang sama
        Schema::table('hafalans', function (Blueprint $table) {
            // Hindari error jika index sudah ada:
            // Di MySQL, nama index harus unik, jadi kita kasih nama eksplisit.
            $table->index(['santri_id', 'tanggal_setoran'], 'idx_hafalan_santri_tgl');
            $table->index(['musyrif_id', 'tanggal_setoran'], 'idx_hafalan_musyrif_tgl');

            // Unique untuk kombinasi santri+tanggal+template (template boleh NULL → MySQL mengizinkan multiple NULL)
            $table->unique(['santri_id', 'tanggal_setoran', 'hafalan_template_id'], 'uniq_hafalan_santri_tgl_template');
        });

        /**
         * 6) (Tahap akhir) Drop kolom lama
         * Saran saya: JANGAN drop dulu sampai Anda sudah:
         * - bikin seeder templates
         * - migrasi data lama → hafalan_template_id
         * Setelah itu baru buat migration terpisah untuk drop kolom lama.
         */
    }

    public function down(): void
    {
        // Balikkan perubahan sebisanya
        // (Perubahan enum status dan drop kolom biasanya butuh statement juga)
        Schema::table('hafalans', function (Blueprint $table) {
            if (Schema::hasColumn('hafalans', 'hafalan_template_id')) {
                $table->dropConstrainedForeignId('hafalan_template_id');
            }
            if (Schema::hasColumn('hafalans', 'nilai_label')) {
                $table->dropColumn('nilai_label');
            }

            // drop indexes
            $table->dropUnique('uniq_hafalan_santri_tgl_template');
            $table->dropIndex('idx_hafalan_santri_tgl');
            $table->dropIndex('idx_hafalan_musyrif_tgl');
        });

        // Kembalikan status enum lama
        DB::statement("
            ALTER TABLE hafalans
            MODIFY status ENUM('lulus','ulang','proses')
            NOT NULL DEFAULT 'proses'
        ");
    }
};
