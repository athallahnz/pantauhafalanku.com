<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $activeCount = DB::table('semesters')
            ->where('is_active', 1)
            ->count();

        if ($activeCount > 1) {
            throw new \RuntimeException(
                'Migration dibatalkan: ditemukan lebih dari satu semester aktif. ' .
                    'Rapikan data is_active terlebih dahulu.'
            );
        }

        Schema::table('semesters', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('draft')
                ->after('is_active')
                ->index();

            $table->timestamp('input_locked_at')
                ->nullable()
                ->after('status');

            $table->timestamp('activated_at')
                ->nullable()
                ->after('input_locked_at');

            $table->timestamp('closed_at')
                ->nullable()
                ->after('activated_at');
        });

        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Backfill data lama
        |--------------------------------------------------------------------------
        |
        | 1. Semester is_active=1 menjadi active.
        | 2. Semester nonaktif yang tanggal akhirnya sudah lewat menjadi closed.
        | 3. Semester nonaktif lainnya tetap draft.
        |
        */
        DB::table('semesters')
            ->where('is_active', 1)
            ->update([
                'status' => 'active',
                'input_locked_at' => null,
                'activated_at' => $now,
                'closed_at' => null,
            ]);

        DB::table('semesters')
            ->where('is_active', 0)
            ->whereDate('tanggal_selesai', '<', $now->toDateString())
            ->update([
                'status' => 'closed',
                'input_locked_at' => $now,
                'closed_at' => $now,
            ]);

        DB::table('semesters')
            ->where('is_active', 0)
            ->where(function ($query) use ($now) {
                $query->whereNull('tanggal_selesai')
                    ->orWhereDate('tanggal_selesai', '>=', $now->toDateString());
            })
            ->update([
                'status' => 'draft',
                'input_locked_at' => null,
                'activated_at' => null,
                'closed_at' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropIndex('semesters_status_index');
            $table->dropColumn([
                'status',
                'input_locked_at',
                'activated_at',
                'closed_at',
            ]);
        });
    }
};
