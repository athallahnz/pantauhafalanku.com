<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('aktif')
                ->after('jenis_kelamin')
                ->index();

            $table->foreignId('graduated_semester_id')
                ->nullable()
                ->after('status')
                ->constrained('semesters')
                ->nullOnDelete();

            $table->timestamp('graduated_at')
                ->nullable()
                ->after('graduated_semester_id');
        });

        /*
        |--------------------------------------------------------------------------
        | Backfill data lama
        |--------------------------------------------------------------------------
        |
        | Seluruh santri lama dianggap aktif agar tidak mengubah perilaku
        | aplikasi sebelum proses kelulusan dijalankan.
        |
        */
        DB::table('santris')
            ->whereNull('status')
            ->update([
                'status' => 'aktif',
            ]);
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropForeign([
                'graduated_semester_id',
            ]);

            $table->dropIndex([
                'status',
            ]);

            $table->dropColumn([
                'status',
                'graduated_semester_id',
                'graduated_at',
            ]);
        });
    }
};
