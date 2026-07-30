<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendarDay;
use App\Models\Semester;
use App\Services\Academic\AcademicCalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicCalendarController extends Controller
{
    public function __construct(
        private readonly AcademicCalendarService $calendarService
    ) {}

    public function index(Request $request): View
    {
        $semesters = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')
            ->get();

        $selectedSemester = $semesters->firstWhere(
            'id',
            $request->integer('semester_id')
        ) ?? $semesters->first(fn(Semester $semester) => $semester->isActive())
            ?? $semesters->first();

        return view('admin.academic-calendar.index', compact(
            'semesters',
            'selectedSemester'
        ));
    }

    public function days(
        Request $request,
        Semester $semester
    ): JsonResponse {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
        ]);

        $monthStart = Carbon::createFromFormat(
            'Y-m',
            $validated['month'],
            AcademicCalendarService::TIMEZONE
        )->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $days = AcademicCalendarDay::query()
            ->where('semester_id', $semester->id)
            ->whereBetween('tanggal', [
                $monthStart->toDateString(),
                $monthEnd->toDateString(),
            ])
            ->orderBy('tanggal')
            ->get()
            ->map(fn(AcademicCalendarDay $day) => [
                'id' => $day->id,
                'tanggal' => $day->tanggal->toDateString(),
                'status' => $day->status,
                'nama_kegiatan' => $day->nama_kegiatan,
                'keterangan' => $day->keterangan,
            ]);

        $stats = AcademicCalendarDay::query()
            ->where('semester_id', $semester->id)
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'semester' => [
                'id' => $semester->id,
                'label' => ucfirst($semester->nama)
                    . ' · '
                    . ($semester->tahunAjaran?->nama ?? '-'),
                'status' => $semester->status,
                'editable' => !$semester->isClosed(),
                'tanggal_mulai' => $semester->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $semester->tanggal_selesai?->toDateString(),
            ],
            'days' => $days,
            'stats' => [
                'masuk' => (int) ($stats[AcademicCalendarDay::STATUS_MASUK] ?? 0),
                'libur' => (int) ($stats[AcademicCalendarDay::STATUS_LIBUR] ?? 0),
            ],
        ]);
    }

    public function sync(Semester $semester): JsonResponse
    {
        $this->assertEditable($semester);

        $created = $this->calendarService->syncSemesterCalendar(
            $semester,
            $semester->isDraft()
        );

        return response()->json([
            'message' => $created > 0
                ? "{$created} tanggal baru berhasil dibuat sebagai hari masuk."
                : 'Kalender semester sudah lengkap.',
            'created' => $created,
        ]);
    }

    public function update(
        Request $request,
        AcademicCalendarDay $academicCalendarDay
    ): JsonResponse {
        $academicCalendarDay->loadMissing('semester');
        $this->assertEditable($academicCalendarDay->semester);

        $validated = $this->validateDay($request);

        $academicCalendarDay->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Jadwal tanggal berhasil diperbarui.',
            'day' => [
                'id' => $academicCalendarDay->id,
                'tanggal' => $academicCalendarDay->tanggal->toDateString(),
                'status' => $academicCalendarDay->status,
                'nama_kegiatan' => $academicCalendarDay->nama_kegiatan,
                'keterangan' => $academicCalendarDay->keterangan,
            ],
        ]);
    }

    public function bulk(
        Request $request,
        Semester $semester
    ): JsonResponse {
        $this->assertEditable($semester);

        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => [
                'required',
                'date',
                'after_or_equal:tanggal_mulai',
            ],
            'status' => [
                'required',
                Rule::in([
                    AcademicCalendarDay::STATUS_MASUK,
                    AcademicCalendarDay::STATUS_LIBUR,
                ]),
            ],
            'nama_kegiatan' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ]);

        $start = Carbon::parse($validated['tanggal_mulai']);
        $end = Carbon::parse($validated['tanggal_selesai']);
        $this->assertWithinSemester($semester, $start, $end);

        $this->calendarService->syncSemesterCalendar(
            $semester,
            $semester->isDraft()
        );

        $updated = AcademicCalendarDay::query()
            ->where('semester_id', $semester->id)
            ->whereBetween('tanggal', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->update([
                'status' => $validated['status'],
                'nama_kegiatan' => $validated['nama_kegiatan'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'updated_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => "{$updated} tanggal berhasil diperbarui.",
            'updated' => $updated,
        ]);
    }

    private function validateDay(Request $request): array
    {
        return $request->validate([
            'status' => [
                'required',
                Rule::in([
                    AcademicCalendarDay::STATUS_MASUK,
                    AcademicCalendarDay::STATUS_LIBUR,
                ]),
            ],
            'nama_kegiatan' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function assertEditable(Semester $semester): void
    {
        if ($semester->isClosed()) {
            throw ValidationException::withMessages([
                'semester' => [
                    'Kalender semester yang sudah ditutup hanya dapat dilihat.',
                ],
            ]);
        }
    }

    private function assertWithinSemester(
        Semester $semester,
        Carbon $start,
        Carbon $end
    ): void {
        if (
            $start->lt($semester->tanggal_mulai)
            || $end->gt($semester->tanggal_selesai)
        ) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => [
                    'Rentang tanggal harus berada di dalam periode semester.',
                ],
            ]);
        }
    }
}
