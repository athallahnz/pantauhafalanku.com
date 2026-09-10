<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musyrif\StoreTahsinExamRequest;
use App\Http\Requests\Musyrif\UpdateTahsinExamRequest;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\Semester;
use App\Models\TahsinExam;
use App\Support\Academic\ResolvesActiveSemester;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TahsinExamController extends Controller
{
    use ResolvesActiveSemester;

    public function index(): View
    {
        $rows = $this->summaryRows($this->currentMusyrif());
        [$inputOpen, $inputMessage, $semesterLabel] = $this->inputContext();

        return view('musyrif.tahsin-exams.index', [
            'statistics' => $this->statistics($rows),
            'inputOpen' => $inputOpen,
            'inputMessage' => $inputMessage,
            'semesterLabel' => $semesterLabel,
        ]);
    }

    public function data(): JsonResponse
    {
        $rows = $this->summaryRows($this->currentMusyrif());

        return response()->json([
            'data' => $rows->values(),
            'statistics' => $this->statistics($rows),
        ]);
    }

    public function options(): JsonResponse
    {
        $semester = $this->assertAcademicInputOpen();
        $musyrif = $this->currentMusyrif();
        $today = CarbonImmutable::now('Asia/Jakarta')->toDateString();
        $maximumDate = min(
            $today,
            $semester->tanggal_selesai->toDateString()
        );

        $santris = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn (Santri $santri): array => [
                'id' => (int) $santri->id,
                'nama' => $santri->nama,
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'submission_uuid' => (string) Str::uuid(),
            'semester' => [
                'id' => (int) $semester->id,
                'label' => $this->semesterLabel($semester),
                'minimum_date' => $semester->tanggal_mulai->toDateString(),
                'maximum_date' => $maximumDate,
            ],
            'exam_types' => collect(TahsinExam::examTypeLabels())
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values(),
            'books' => collect(TahsinExam::bookLabels())
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values(),
            'grades' => collect(TahsinExam::gradeLabels())
                ->map(fn (string $label, string $value): array => [
                    'value' => $value,
                    'label' => $label,
                    'result' => TahsinExam::resultForGrade($value),
                ])
                ->values(),
            'data_santri' => $santris,
        ]);
    }

    public function history(Santri $santri): JsonResponse
    {
        $musyrif = $this->currentMusyrif();
        $this->assertOwnedActiveSantri($santri, $musyrif);
        [$inputOpen] = $this->inputContext();
        $activeSemester = Semester::query()->active()->first();
        $activeSemesterId = $activeSemester
            ? (int) $activeSemester->id
            : null;

        $records = TahsinExam::query()
            ->where('musyrif_id', $musyrif->id)
            ->where('santri_id', $santri->id)
            ->with('semester.tahunAjaran')
            ->latest('tanggal')
            ->latest('id')
            ->get()
            ->map(fn (TahsinExam $exam): array => $this->historyRow(
                $exam,
                $inputOpen,
                $activeSemesterId
            ))
            ->values();

        return response()->json([
            'santri' => [
                'id' => (int) $santri->id,
                'nama' => $santri->nama,
            ],
            'data' => $records,
        ]);
    }

    public function store(StoreTahsinExamRequest $request): JsonResponse
    {
        $semester = $this->assertAcademicInputOpen();
        $musyrif = $this->currentMusyrif();
        $validated = $request->validated();
        $santri = $this->ownedActiveSantri(
            $musyrif,
            (int) $validated['santri_id']
        );
        $this->assertDateWithinSemester($validated['tanggal'], $semester);
        $duplicate = false;

        $exam = DB::transaction(function () use (
            $validated,
            $santri,
            $musyrif,
            $semester,
            &$duplicate
        ): TahsinExam {
            $existing = TahsinExam::query()
                ->where('submission_uuid', $validated['submission_uuid'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $duplicate = true;

                return $existing;
            }

            $attempts = TahsinExam::query()
                ->where('santri_id', $santri->id)
                ->where('semester_id', $semester->id)
                ->where('exam_type', $validated['exam_type'])
                ->where('buku', $validated['buku'])
                ->lockForUpdate()
                ->pluck('attempt_number');
            $attemptNumber = ((int) $attempts->max()) + 1;

            return TahsinExam::query()->create([
                'santri_id' => $santri->id,
                'musyrif_id' => $musyrif->id,
                'semester_id' => $semester->id,
                'tanggal' => $validated['tanggal'],
                'exam_type' => $validated['exam_type'],
                'buku' => $validated['buku'],
                'attempt_number' => $attemptNumber,
                'grade_label' => $validated['grade_label'],
                'submission_uuid' => $validated['submission_uuid'],
                'catatan' => $this->nullableNote(
                    $validated['catatan'] ?? null
                ),
            ]);
        });

        if (
            (int) $exam->musyrif_id !== (int) $musyrif->id
            || (int) $exam->santri_id !== (int) $santri->id
        ) {
            throw ValidationException::withMessages([
                'submission_uuid' => 'Kode pengiriman sudah digunakan.',
            ]);
        }

        if ($duplicate) {
            return response()->json([
                'ok' => true,
                'duplicate' => true,
                'id' => (int) $exam->id,
                'message' => 'Ujian Tahsin ini sudah tersimpan sebelumnya.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'duplicate' => false,
            'id' => (int) $exam->id,
            'message' => "Ujian Tahsin {$santri->nama} berhasil disimpan.",
        ], 201);
    }

    public function update(
        UpdateTahsinExamRequest $request,
        TahsinExam $tahsinExam
    ): JsonResponse {
        $musyrif = $this->currentMusyrif();
        $this->assertOwnedRecord($tahsinExam, $musyrif);
        $semester = $this->assertRecordEditableInActiveSemester(
            $tahsinExam->semester_id
        );
        $validated = $request->validated();
        $this->assertDateWithinSemester($validated['tanggal'], $semester);

        $tahsinExam->update([
            'tanggal' => $validated['tanggal'],
            'grade_label' => $validated['grade_label'],
            'catatan' => $this->nullableNote(
                $validated['catatan'] ?? null
            ),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Ujian Tahsin berhasil diperbarui.',
        ]);
    }

    public function destroy(TahsinExam $tahsinExam): JsonResponse
    {
        $musyrif = $this->currentMusyrif();
        $this->assertOwnedRecord($tahsinExam, $musyrif);
        $this->assertRecordEditableInActiveSemester(
            $tahsinExam->semester_id
        );
        $tahsinExam->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Ujian Tahsin berhasil dihapus.',
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function summaryRows(Musyrif $musyrif): Collection
    {
        $santris = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->with('kelas:id,nama_kelas')
            ->orderBy('nama')
            ->get(['id', 'nama', 'nis', 'kelas_id']);
        $santriIds = $santris->pluck('id');

        $recordsBySantri = $santriIds->isEmpty()
            ? collect()
            : TahsinExam::query()
                ->where('musyrif_id', $musyrif->id)
                ->whereIn('santri_id', $santriIds)
                ->latest('tanggal')
                ->latest('id')
                ->get()
                ->groupBy(
                    fn (TahsinExam $exam): int => (int) $exam->santri_id
                );
        $bookLabels = TahsinExam::bookLabels();
        $bookOrder = TahsinExam::books();

        return $santris->map(function (Santri $santri) use (
            $recordsBySantri,
            $bookLabels,
            $bookOrder
        ): array {
            $records = $recordsBySantri->get(
                (int) $santri->id,
                collect()
            )->values();
            $passedBooks = $records
                ->where('exam_type', TahsinExam::TYPE_PROMOTION)
                ->where('result', TahsinExam::RESULT_PASSED)
                ->pluck('buku')
                ->unique()
                ->sortBy(function (string $book) use ($bookOrder): int {
                    $position = array_search($book, $bookOrder, true);

                    return $position === false ? PHP_INT_MAX : $position;
                })
                ->values();
            $completedCount = $passedBooks->count();
            $recommendedBook = collect($bookOrder)->first(
                fn (string $book): bool => !$passedBooks->contains($book)
            );
            /** @var TahsinExam|null $latest */
            $latest = $records->first();

            return [
                'id' => (int) $santri->id,
                'nama' => $santri->nama,
                'nis' => $santri->nis,
                'kelas' => $santri->kelas?->nama_kelas ?? '-',
                'completed_books' => $passedBooks,
                'completed_count' => $completedCount,
                'progress_percentage' => round(
                    ($completedCount / count($bookOrder)) * 100,
                    1
                ),
                'recommended_book' => $recommendedBook,
                'recommended_book_label' => $recommendedBook
                    ? ($bookLabels[$recommendedBook] ?? $recommendedBook)
                    : 'Kurikulum Selesai',
                'total_exams' => $records->count(),
                'promotion_exams' => $records
                    ->where('exam_type', TahsinExam::TYPE_PROMOTION)
                    ->count(),
                'semester_exams' => $records
                    ->where('exam_type', TahsinExam::TYPE_SEMESTER)
                    ->count(),
                'passed_exams' => $records
                    ->where('result', TahsinExam::RESULT_PASSED)
                    ->count(),
                'repeat_exams' => $records
                    ->where('result', TahsinExam::RESULT_REPEAT)
                    ->count(),
                'exam_state' => $latest?->result ?? 'not_examined',
                'latest_exam' => $latest
                    ? $this->summaryExam($latest)
                    : null,
            ];
        });
    }

    /** @return array<string, mixed> */
    private function summaryExam(TahsinExam $exam): array
    {
        return [
            'id' => (int) $exam->id,
            'tanggal' => $exam->tanggal?->toDateString(),
            'tanggal_label' => $exam->tanggal?->format('d M Y'),
            'exam_type' => $exam->exam_type,
            'exam_type_label' => TahsinExam::examTypeLabels()[
                $exam->exam_type
            ] ?? $exam->exam_type,
            'buku' => $exam->buku,
            'buku_label' => TahsinExam::bookLabels()[$exam->buku]
                ?? $exam->buku,
            'attempt_number' => (int) $exam->attempt_number,
            'grade_label' => $exam->grade_label,
            'grade_text' => TahsinExam::gradeLabels()[$exam->grade_label]
                ?? $exam->grade_label,
            'result' => $exam->result,
            'result_label' => $exam->result === TahsinExam::RESULT_PASSED
                ? 'Lulus'
                : 'Mengulang',
        ];
    }

    /** @return array<string, mixed> */
    private function historyRow(
        TahsinExam $exam,
        bool $inputOpen,
        ?int $activeSemesterId
    ): array {
        return array_merge($this->summaryExam($exam), [
            'submission_uuid' => $exam->submission_uuid,
            'next_book' => $exam->next_book,
            'next_book_label' => $exam->next_book
                ? (TahsinExam::bookLabels()[$exam->next_book]
                    ?? $exam->next_book)
                : null,
            'catatan' => $exam->catatan,
            'semester_label' => $exam->semester
                ? $this->semesterLabel($exam->semester)
                : '-',
            'editable' => $inputOpen
                && $activeSemesterId !== null
                && (int) $exam->semester_id === $activeSemesterId,
        ]);
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function statistics(Collection $rows): array
    {
        return [
            'total_santri' => $rows->count(),
            'examined_santri' => $rows
                ->where('total_exams', '>', 0)
                ->count(),
            'total_exams' => (int) $rows->sum('total_exams'),
            'latest_passed' => $rows
                ->where('exam_state', TahsinExam::RESULT_PASSED)
                ->count(),
        ];
    }

    private function currentMusyrif(): Musyrif
    {
        return Musyrif::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    private function ownedActiveSantri(
        Musyrif $musyrif,
        int $santriId
    ): Santri {
        return Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->findOrFail($santriId);
    }

    private function assertOwnedActiveSantri(
        Santri $santri,
        Musyrif $musyrif
    ): void {
        if (
            !$santri->isActive()
            || (int) $santri->musyrif_id !== (int) $musyrif->id
        ) {
            abort(404);
        }
    }

    private function assertOwnedRecord(
        TahsinExam $exam,
        Musyrif $musyrif
    ): void {
        if ((int) $exam->musyrif_id !== (int) $musyrif->id) {
            abort(404);
        }
    }

    private function assertDateWithinSemester(
        string $tanggal,
        Semester $semester
    ): void {
        $date = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            $tanggal,
            'Asia/Jakarta'
        );
        $start = CarbonImmutable::instance($semester->tanggal_mulai)
            ->startOfDay();
        $end = CarbonImmutable::instance($semester->tanggal_selesai)
            ->startOfDay();
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();

        if ($date->lt($start) || $date->gt($end)) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal harus berada dalam semester aktif.',
            ]);
        }

        if ($date->gt($today)) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal ujian tidak boleh di masa depan.',
            ]);
        }
    }

    private function nullableNote(?string $note): ?string
    {
        $note = trim((string) $note);

        return $note === '' ? null : $note;
    }

    /** @return array{0:bool,1:?string,2:?string} */
    private function inputContext(): array
    {
        try {
            $semester = $this->activeSemesterForInput();

            return [true, null, $this->semesterLabel($semester)];
        } catch (ValidationException $exception) {
            $semester = Semester::query()
                ->with('tahunAjaran')
                ->active()
                ->first();
            $message = collect($exception->errors())
                ->flatten()
                ->first()
                ?? 'Input Ujian Tahsin sedang ditutup.';

            return [
                false,
                $message,
                $semester ? $this->semesterLabel($semester) : null,
            ];
        }
    }

    private function semesterLabel(Semester $semester): string
    {
        return trim(
            ($semester->nama ?? '') . ' ' .
            ($semester->tahunAjaran?->nama ?? '')
        );
    }
}
