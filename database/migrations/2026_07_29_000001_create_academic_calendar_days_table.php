<?php

use App\Models\AcademicCalendarDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendar_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')
                ->constrained('semesters')
                ->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', [
                AcademicCalendarDay::STATUS_MASUK,
                AcademicCalendarDay::STATUS_LIBUR,
            ])->default(AcademicCalendarDay::STATUS_MASUK);
            $table->string('nama_kegiatan', 150)->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['semester_id', 'tanggal'],
                'academic_calendar_semester_date_unique'
            );
            $table->index(
                ['tanggal', 'status'],
                'academic_calendar_date_status_index'
            );
        });

        /*
         * Backfill aman: hanya semester yang sedang aktif.
         * Semester closed dan transaksi historis tidak disentuh.
         */
        $activeSemesters = DB::table('semesters')
            ->where('status', 'active')
            ->where('is_active', true)
            ->get([
                'id',
                'tanggal_mulai',
                'tanggal_selesai',
            ]);

        $timestamp = now();

        foreach ($activeSemesters as $semester) {
            $start = Carbon::parse($semester->tanggal_mulai)->startOfDay();
            $end = Carbon::parse($semester->tanggal_selesai)->startOfDay();

            if ($start->gt($end)) {
                continue;
            }

            $rows = [];

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $rows[] = [
                    'semester_id' => $semester->id,
                    'tanggal' => $date->toDateString(),
                    'status' => AcademicCalendarDay::STATUS_MASUK,
                    'nama_kegiatan' => null,
                    'keterangan' => null,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('academic_calendar_days')->insertOrIgnore($chunk);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_days');
    }
};
