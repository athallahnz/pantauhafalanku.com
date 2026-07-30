<?php

namespace App\Services\Academic;

use App\Models\AcademicCalendarDay;
use App\Models\Semester;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicCalendarService
{
    public const TIMEZONE = 'Asia/Jakarta';

    /**
     * Membuat baris kalender yang belum ada tanpa menimpa keputusan Admin.
     */
    public function syncSemesterCalendar(
        Semester $semester,
        bool $trimOutsidePeriod = false
    ): int {
        $start = $semester->tanggal_mulai?->copy()->startOfDay();
        $end = $semester->tanggal_selesai?->copy()->startOfDay();

        if (!$start || !$end || $start->gt($end)) {
            throw ValidationException::withMessages([
                'semester' => ['Periode semester tidak valid.'],
            ]);
        }

        return DB::transaction(function () use (
            $semester,
            $start,
            $end,
            $trimOutsidePeriod
        ): int {
            if ($trimOutsidePeriod) {
                AcademicCalendarDay::query()
                    ->where('semester_id', $semester->id)
                    ->where(function ($query) use ($start, $end) {
                        $query
                            ->whereDate('tanggal', '<', $start->toDateString())
                            ->orWhereDate('tanggal', '>', $end->toDateString());
                    })
                    ->delete();
            }

            $existingDates = AcademicCalendarDay::query()
                ->where('semester_id', $semester->id)
                ->whereBetween('tanggal', [
                    $start->toDateString(),
                    $end->toDateString(),
                ])
                ->pluck('tanggal')
                ->map(fn($date) => Carbon::parse($date)->toDateString())
                ->flip();

            $timestamp = now();
            $rows = [];

            foreach (CarbonPeriod::create($start, $end) as $date) {
                $dateString = $date->toDateString();

                if ($existingDates->has($dateString)) {
                    continue;
                }

                $rows[] = [
                    'semester_id' => $semester->id,
                    'tanggal' => $dateString,
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
                AcademicCalendarDay::query()->insertOrIgnore($chunk);
            }

            return count($rows);
        });
    }

    /**
     * @return array{
     *   date: string,
     *   semester: Semester|null,
     *   calendar_day: AcademicCalendarDay|null,
     *   state: string,
     *   label: string,
     *   message: string,
     *   attendance_open: bool,
     *   academic_input_open: bool
     * }
     */
    public function contextForDate(
        CarbonInterface|string|null $date = null
    ): array {
        $date = $date instanceof CarbonInterface
            ? Carbon::instance($date)->setTimezone(self::TIMEZONE)
            : Carbon::parse($date ?? 'now', self::TIMEZONE);

        $dateString = $date->toDateString();
        $activeSemesters = Semester::query()
            ->active()
            ->orderByDesc('tanggal_mulai')
            ->get();

        if ($activeSemesters->count() !== 1) {
            return $this->closedContext(
                $dateString,
                $activeSemesters->first(),
                null,
                'no_active_semester',
                'Jadwal belum tersedia',
                $activeSemesters->isEmpty()
                    ? 'Belum ada semester aktif. Pencatatan sementara ditutup.'
                    : 'Ditemukan lebih dari satu semester aktif. Hubungi Admin.'
            );
        }

        /** @var Semester $semester */
        $semester = $activeSemesters->first();
        $start = $semester->tanggal_mulai?->toDateString();
        $end = $semester->tanggal_selesai?->toDateString();

        if (!$start || !$end || $dateString < $start || $dateString > $end) {
            return $this->closedContext(
                $dateString,
                $semester,
                null,
                'outside_semester',
                'Di luar periode semester',
                'Tanggal ini berada di luar periode semester aktif. Pencatatan ditutup.'
            );
        }

        $calendarDay = AcademicCalendarDay::query()
            ->where('semester_id', $semester->id)
            ->whereDate('tanggal', $dateString)
            ->first();

        if (!$calendarDay) {
            return $this->closedContext(
                $dateString,
                $semester,
                null,
                'unconfigured',
                'Jadwal belum ditentukan',
                'Jadwal tanggal ini belum dibuat oleh Admin. Pencatatan sementara ditutup.'
            );
        }

        if ($calendarDay->isHoliday()) {
            $event = $calendarDay->nama_kegiatan
                ? ': ' . $calendarDay->nama_kegiatan
                : '';

            return $this->closedContext(
                $dateString,
                $semester,
                $calendarDay,
                'holiday',
                'Hari Libur',
                "Hari ini ditetapkan sebagai libur{$event}. Hafalan, Tahsin, Tilawah, dan absensi Musyrif ditutup."
            );
        }

        if ($semester->isInputLocked()) {
            return [
                'date' => $dateString,
                'semester' => $semester,
                'calendar_day' => $calendarDay,
                'state' => 'input_locked',
                'label' => 'Hari Masuk — Input Akademik Dikunci',
                'message' => 'Absensi Musyrif tetap tersedia, tetapi input Hafalan, Tahsin, dan Tilawah sedang dikunci.',
                'attendance_open' => true,
                'academic_input_open' => false,
            ];
        }

        return [
            'date' => $dateString,
            'semester' => $semester,
            'calendar_day' => $calendarDay,
            'state' => 'open',
            'label' => 'Hari Masuk',
            'message' => $calendarDay->nama_kegiatan
                ?: 'Pencatatan akademik dan absensi Musyrif tersedia.',
            'attendance_open' => true,
            'academic_input_open' => true,
        ];
    }

    public function todayContext(): array
    {
        return $this->contextForDate(
            now(self::TIMEZONE)
        );
    }

    public function assertAcademicInputOpen(
        CarbonInterface|string|null $date = null
    ): array {
        $context = $this->contextForDate($date);

        if (!$context['academic_input_open']) {
            throw ValidationException::withMessages([
                'academic_calendar' => [$context['message']],
            ]);
        }

        return $context;
    }

    public function assertAttendanceOpen(
        CarbonInterface|string|null $date = null
    ): array {
        $context = $this->contextForDate($date);

        if (!$context['attendance_open']) {
            throw ValidationException::withMessages([
                'academic_calendar' => [$context['message']],
            ]);
        }

        return $context;
    }

    /**
     * @return Collection<string, AcademicCalendarDay>
     */
    public function daysForRange(
        CarbonInterface|string $start,
        CarbonInterface|string $end,
        ?int $semesterId = null
    ): Collection {
        $start = Carbon::parse($start, self::TIMEZONE)->toDateString();
        $end = Carbon::parse($end, self::TIMEZONE)->toDateString();

        return AcademicCalendarDay::query()
            ->when(
                $semesterId,
                fn($query) => $query->where('semester_id', $semesterId)
            )
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal')
            ->get()
            ->keyBy(fn(AcademicCalendarDay $day) => $day->tanggal->toDateString());
    }

    private function closedContext(
        string $date,
        ?Semester $semester,
        ?AcademicCalendarDay $calendarDay,
        string $state,
        string $label,
        string $message
    ): array {
        return [
            'date' => $date,
            'semester' => $semester,
            'calendar_day' => $calendarDay,
            'state' => $state,
            'label' => $label,
            'message' => $message,
            'attendance_open' => false,
            'academic_input_open' => false,
        ];
    }
}
