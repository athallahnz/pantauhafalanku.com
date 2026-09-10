<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Http\Requests\Musyrif\StoreTilawahMandiriRequest;
use App\Http\Requests\Musyrif\UpdateTilawahMandiriRequest;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\Semester;
use App\Models\Tilawah;
use App\Services\TilawahProgressService;
use App\Support\Academic\ResolvesActiveSemester;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TilawahMandiriController extends Controller
{
    use ResolvesActiveSemester;

    public function __construct(
        private readonly TilawahProgressService $progressService
    ) {
    }

    public function index(): View
    {
        $musyrif = $this->currentMusyrif();
        $rows = $this->summaryRows($musyrif);
        [$inputOpen, $inputMessage, $semesterLabel] = $this->inputContext();

        return view('musyrif.tilawah-mandiri.index', [
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
            'reading_purposes' => [
                [
                    'value' => Tilawah::PURPOSE_CONTINUATION,
                    'label' => 'Lanjut',
                ],
                [
                    'value' => Tilawah::PURPOSE_REVIEW,
                    'label' => 'Murojaah',
                ],
            ],
            'juz_options' => collect(range(1, 30))
                ->map(fn (int $juz): array => [
                    'value' => $juz,
                    'label' => "Juz {$juz}",
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

        $records = Tilawah::query()
            ->where('musyrif_id', $musyrif->id)
            ->where('santri_id', $santri->id)
            ->where('entry_type', Tilawah::ENTRY_TYPE_INDIVIDUAL)
            ->with(['template:id,juz,label', 'semester.tahunAjaran'])
            ->latest('tanggal')
            ->latest('id')
            ->get()
            ->map(function (Tilawah $tilawah) use (
                $inputOpen,
                $activeSemesterId
            ): array {
                $payload = $this->progressService->parse($tilawah->catatan);
                $juz = $this->recordJuz($tilawah, $payload);

                return [
                    'id' => (int) $tilawah->id,
                    'submission_uuid' => $tilawah->submission_uuid,
                    'tanggal' => $tilawah->tanggal?->toDateString(),
                    'tanggal_label' => $tilawah->tanggal?->format('d M Y'),
                    'status' => $tilawah->status,
                    'reading_purpose' => $tilawah->reading_purpose,
                    'reading_purpose_label' => $tilawah->reading_purpose
                        === Tilawah::PURPOSE_REVIEW
                            ? 'Murojaah'
                            : 'Lanjut',
                    'juz' => $juz,
                    'juz_label' => $juz ? "Juz {$juz}" : '-',
                    'note' => $this->progressService->note(
                        $tilawah->catatan
                    ),
                    'display' => $this->progressService->display(
                        $tilawah->catatan
                    ),
                    'semester_label' => $tilawah->semester
                        ? $this->semesterLabel($tilawah->semester)
                        : '-',
                    'editable' => $inputOpen
                        && $activeSemesterId !== null
                        && (int) $tilawah->semester_id
                            === $activeSemesterId,
                ];
            })
            ->values();

        return response()->json([
            'santri' => [
                'id' => (int) $santri->id,
                'nama' => $santri->nama,
            ],
            'data' => $records,
        ]);
    }

    public function store(
        StoreTilawahMandiriRequest $request
    ): JsonResponse {
        $semester = $this->assertAcademicInputOpen();
        $musyrif = $this->currentMusyrif();
        $validated = $request->validated();
        $santri = $this->ownedActiveSantri(
            $musyrif,
            (int) $validated['santri_id']
        );
        $this->assertDateWithinSemester($validated['tanggal'], $semester);

        $tilawah = Tilawah::query()->firstOrCreate(
            ['submission_uuid' => $validated['submission_uuid']],
            $this->attributesFromValidated(
                $validated,
                $santri,
                $musyrif,
                $semester
            )
        );

        if (!$tilawah->wasRecentlyCreated) {
            if (
                (int) $tilawah->musyrif_id !== (int) $musyrif->id
                || !$tilawah->isIndividualEntry()
            ) {
                throw ValidationException::withMessages([
                    'submission_uuid' => 'Kode pengiriman sudah digunakan.',
                ]);
            }

            return response()->json([
                'ok' => true,
                'duplicate' => true,
                'id' => (int) $tilawah->id,
                'message' => 'Tilawah Mandiri ini sudah tersimpan sebelumnya.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'duplicate' => false,
            'id' => (int) $tilawah->id,
            'message' => "Tilawah Mandiri {$santri->nama} berhasil disimpan.",
        ], 201);
    }

    public function update(
        UpdateTilawahMandiriRequest $request,
        Tilawah $tilawah
    ): JsonResponse {
        $musyrif = $this->currentMusyrif();
        $this->assertOwnedIndividualRecord($tilawah, $musyrif);
        $semester = $this->assertRecordEditableInActiveSemester(
            $tilawah->semester_id
        );
        $validated = $request->validated();
        $santri = $this->ownedActiveSantri(
            $musyrif,
            (int) $validated['santri_id']
        );
        $this->assertDateWithinSemester($validated['tanggal'], $semester);

        $tilawah->update($this->attributesFromValidated(
            $validated,
            $santri,
            $musyrif,
            $semester
        ));

        return response()->json([
            'ok' => true,
            'message' => 'Tilawah Mandiri berhasil diperbarui.',
        ]);
    }

    public function destroy(Tilawah $tilawah): JsonResponse
    {
        $musyrif = $this->currentMusyrif();
        $this->assertOwnedIndividualRecord($tilawah, $musyrif);
        $this->assertRecordEditableInActiveSemester($tilawah->semester_id);
        $tilawah->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Tilawah Mandiri berhasil dihapus.',
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
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
            : Tilawah::query()
                ->where('musyrif_id', $musyrif->id)
                ->whereIn('santri_id', $santriIds)
                ->where('entry_type', Tilawah::ENTRY_TYPE_INDIVIDUAL)
                ->with('template:id,juz,label')
                ->latest('tanggal')
                ->latest('id')
                ->get()
                ->groupBy(
                    fn (Tilawah $tilawah): int =>
                        (int) $tilawah->santri_id
                );

        return $santris->map(function (Santri $santri) use (
            $recordsBySantri
        ): array {
            $records = $recordsBySantri->get(
                (int) $santri->id,
                collect()
            )->values();
            $completedJuz = $records
                ->filter(
                    fn (Tilawah $record): bool =>
                        $record->contributesToProgress()
                )
                ->map(function (Tilawah $record): ?int {
                    $payload = $this->progressService->parse(
                        $record->catatan
                    );

                    return $this->progressService->juzFromPayload($payload);
                })
                ->filter(fn (?int $juz): bool => $juz !== null)
                ->unique()
                ->sort()
                ->values();
            $completedCount = $completedJuz->count();
            /** @var Tilawah|null $last */
            $last = $records->first();
            $lastPayload = $last
                ? $this->progressService->parse($last->catatan)
                : null;
            $lastJuz = $last
                ? $this->recordJuz($last, $lastPayload)
                : null;

            return [
                'id' => (int) $santri->id,
                'nama' => $santri->nama,
                'nis' => $santri->nis,
                'kelas' => $santri->kelas?->nama_kelas ?? '-',
                'completed_juz' => $completedJuz,
                'completed_count' => $completedCount,
                'remaining_count' => 30 - $completedCount,
                'progress_percentage' => round(
                    ($completedCount / 30) * 100,
                    1
                ),
                'progress_state' => match (true) {
                    $completedCount === 30 => 'completed',
                    $completedCount > 0 => 'in_progress',
                    default => 'not_started',
                },
                'total_sessions' => $records->count(),
                'continuation_sessions' => $records
                    ->where(
                        'reading_purpose',
                        Tilawah::PURPOSE_CONTINUATION
                    )
                    ->count(),
                'review_sessions' => $records
                    ->where(
                        'reading_purpose',
                        Tilawah::PURPOSE_REVIEW
                    )
                    ->count(),
                'last_entry' => $last ? [
                    'id' => (int) $last->id,
                    'juz' => $lastJuz,
                    'juz_label' => $lastJuz
                        ? "Juz {$lastJuz}"
                        : '-',
                    'reading_purpose' => $last->reading_purpose,
                    'reading_purpose_label' => $last->reading_purpose
                        === Tilawah::PURPOSE_REVIEW
                            ? 'Murojaah'
                            : 'Lanjut',
                    'tanggal' => $last->tanggal?->toDateString(),
                    'tanggal_label' => $last->tanggal?->format('d M Y'),
                ] : null,
            ];
        });
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @return array<string, int>
     */
    private function statistics(Collection $rows): array
    {
        return [
            'total_santri' => $rows->count(),
            'started_santri' => $rows
                ->where('total_sessions', '>', 0)
                ->count(),
            'total_sessions' => (int) $rows->sum('total_sessions'),
            'completed_santri' => $rows
                ->where('completed_count', 30)
                ->count(),
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function recordJuz(
        Tilawah $tilawah,
        ?array $payload = null
    ): ?int {
        $juz = $this->progressService->juzFromPayload($payload);

        if ($juz !== null) {
            return $juz;
        }

        $templateJuz = (int) ($tilawah->template?->juz ?? 0);

        return $templateJuz >= 1 && $templateJuz <= 30
            ? $templateJuz
            : null;
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

    private function assertOwnedIndividualRecord(
        Tilawah $tilawah,
        Musyrif $musyrif
    ): void {
        if (
            (int) $tilawah->musyrif_id !== (int) $musyrif->id
            || !$tilawah->isIndividualEntry()
        ) {
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
                'tanggal' => 'Tanggal harus berada dalam rentang semester aktif.',
            ]);
        }

        if ($date->gt($today)) {
            throw ValidationException::withMessages([
                'tanggal' => 'Tanggal Tilawah Mandiri tidak boleh berada di masa depan.',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function attributesFromValidated(
        array $validated,
        Santri $santri,
        Musyrif $musyrif,
        Semester $semester
    ): array {
        $juz = (int) $validated['juz'];
        $payload = $this->progressService->buildJuzPayload(
            $juz,
            $validated['catatan'] ?? null,
            $validated['reading_purpose']
        );
        $template = $this->progressService->resolveJuzBookmark($juz);

        return [
            'santri_id' => $santri->id,
            'musyrif_id' => $musyrif->id,
            'tanggal' => $validated['tanggal'],
            'hafalan_template_id' => $template->id,
            'status' => 'hadir',
            'semester_id' => (int) $semester->id,
            'catatan' => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            ),
            'entry_type' => Tilawah::ENTRY_TYPE_INDIVIDUAL,
            'reading_purpose' => $validated['reading_purpose'],
        ];
    }

    /**
     * @return array{0:bool,1:?string,2:?string}
     */
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
                ?? 'Input Tilawah Mandiri sedang ditutup.';

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
