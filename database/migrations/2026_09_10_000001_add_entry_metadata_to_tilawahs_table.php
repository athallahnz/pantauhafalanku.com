<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tilawahs', function (Blueprint $table): void {
            $table->string('entry_type', 20)
                ->default('group')
                ->after('semester_id');
            $table->string('reading_purpose', 20)
                ->default('continuation')
                ->after('entry_type');
            $table->uuid('submission_uuid')
                ->nullable()
                ->unique()
                ->after('reading_purpose');

            $table->index(
                ['santri_id', 'entry_type', 'tanggal'],
                'tilawahs_santri_entry_date_index'
            );
        });

        /*
         * Seluruh record lama berasal dari pencatatan kelompok, kecuali
         * payload terstruktur yang secara eksplisit memakai mode catchup.
         * UUID lama sengaja dibiarkan null; unique index tetap menjamin
         * seluruh submission baru memiliki UUID yang tidak berulang.
         */
        DB::table('tilawahs')
            ->where('catatan', 'like', '%"mode":"catchup"%')
            ->update([
                'entry_type' => 'catchup',
            ]);
    }

    public function down(): void
    {
        Schema::table('tilawahs', function (Blueprint $table): void {
            $table->dropIndex('tilawahs_santri_entry_date_index');
            $table->dropUnique(['submission_uuid']);
            $table->dropColumn([
                'entry_type',
                'reading_purpose',
                'submission_uuid',
            ]);
        });
    }
};
