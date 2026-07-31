<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Models\HafalanTemplate;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\Tilawah;
use App\Services\TilawahEligibilityService;
use App\Services\TilawahProgressService;
use App\Support\Academic\ResolvesActiveSemester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class TilawahController extends Controller
{
    use ResolvesActiveSemester;

    public function __construct(
        private readonly TilawahProgressService $progressService,
        private readonly TilawahEligibilityService $eligibilityService
    ) {
    }

    /**
     * Mengambil progress Tilawah kelompok dan status santri hari ini.
     */
    public function getProgress(): JsonResponse
    {
        $musyrif = Musyrif::where('user_id', Auth::id())->firstOrFail();
        $semesterId = $this->activeSemesterId();
        $tanggal = now('Asia/Jakarta')->toDateString();

        $santris = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->orderBy('nama')
            ->get(['id', 'nama']);
        $santriIds = $santris->pluck('id');

        $templates = HafalanTemplate::query()
            ->where('tahap', 'harian')
            ->orderBy('juz')
            ->orderBy('urutan')
            ->get(['id', 'juz', 'label']);

        $todayGroupRecords = Tilawah::query()
            ->where('semester_id', $semesterId)
            ->whereDate('tanggal', $tanggal)
            ->whereIn('santri_id', $santriIds)
            ->get()
            ->filter(function (Tilawah $tilawah): bool {
                $payload = $this->progressService->parse($tilawah->catatan);

                return is_array($payload)
                    && $this->progressService->isGroupPayload($payload);
            })
            ->keyBy('santri_id');

        $todayRecord = $todayGroupRecords->first();
        $latestStructured = $todayRecord
            ?? $this->latestGroupRecord($santriIds);
        $latestPayload = $latestStructured
            ? $this->progressService->parse($latestStructured->catatan)
            : null;

        $latestLegacy = null;
        if (!$latestPayload) {
            $legacyRecord = $this->latestLegacyRecord($santriIds);

            if ($legacyRecord) {
                $latestLegacy = [
                    'id' => (int) $legacyRecord->id,
                    'tanggal' => $legacyRecord->tanggal?->format('d-m-Y'),
                    'target' => $legacyRecord->template
                        ? "Juz {$legacyRecord->template->juz} - {$legacyRecord->template->label}"
                        : null,
                    'catatan' => $legacyRecord->catatan,
                ];
            }
        }

        $editingToday = $todayRecord !== null;
        $initialFrom = null;
        $initialTo = null;

        if ($editingToday && $latestPayload) {
            $initialFrom = $latestPayload['from'];
            $initialTo = $latestPayload['to'];
        } elseif ($latestPayload) {
            $initialFrom = $this->progressService->nextPoint($latestPayload);
            $initialTo = $initialFrom;
        } elseif (!$latestLegacy) {
            $firstSurah = $this->progressService->surahs()->first();

            if ($firstSurah && (int) $firstSurah->jumlah_ayat > 0) {
                $initialFrom = $this->progressService->makePoint(
                    (int) $firstSurah->id,
                    1,
                    'from_ayat'
                );
                $initialTo = $initialFrom;
            }
        }

        $santriProgress = $santris->map(function (Santri $santri) use (
            $todayGroupRecords
        ): array {
            $today = $todayGroupRecords->get($santri->id);

            return [
                'id' => (int) $santri->id,
                'nama' => $santri->nama,
                'status' => $today?->status ?? 'hadir',
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'templates' => $templates,
            'surahs' => $this->progressService->surahs(),
            'data_santri' => $santriProgress,
            'group_progress' => [
                'editing_today' => $editingToday,
                'last_range' => $latestStructured
                    ? $this->progressService->rangeLabel(
                        $latestStructured->catatan
                    )
                    : null,
                'initial_from' => $initialFrom,
                'initial_to' => $initialTo,
                'is_complete' => $latestPayload !== null
                    && $initialFrom === null,
                'note' => $editingToday && $latestStructured
                    ? $this->progressService->note(
                        $latestStructured->catatan
                    )
                    : null,
                'legacy_reference' => $latestLegacy,
            ],
        ]);
    }

    /**
     * Menyimpan satu rentang Tilawah kelompok dengan status per santri.
     */
    public function storeMasal(Request $request): JsonResponse
    {
        $activeSemester = $this->assertAcademicInputOpen();
        $semesterId = (int) $activeSemester->id;

        $validated = $request->validate([
            'from_surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'from_ayat' => ['required', 'integer', 'min:1'],
            'to_surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'to_ayat' => ['required', 'integer', 'min:1'],
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*' => ['required', 'in:hadir,izin,sakit,alpha'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $musyrif = Musyrif::query()
                ->where('user_id', Auth::id())
                ->firstOrFail();
            $tanggal = now('Asia/Jakarta')->toDateString();
            $santriIds = Santri::query()
                ->active()
                ->where('musyrif_id', $musyrif->id)
                ->pluck('id');

            if ($santriIds->isEmpty()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Gagal! Anda belum memiliki santri binaan.',
                ], 422);
            }

            $statuses = collect($validated['statuses'])
                ->mapWithKeys(fn(string $status, string|int $santriId): array => [
                    (int) $santriId => $status,
                ]);
            $expectedIds = $santriIds
                ->map(fn($id): int => (int) $id)
                ->sort()
                ->values();
            $submittedIds = $statuses
                ->keys()
                ->map(fn($id): int => (int) $id)
                ->sort()
                ->values();

            if ($submittedIds->all() !== $expectedIds->all()) {
                throw ValidationException::withMessages([
                    'statuses' => 'Status harus diisi untuk seluruh santri binaan yang aktif.',
                ]);
            }

            $from = $this->progressService->makePoint(
                (int) $validated['from_surah_id'],
                (int) $validated['from_ayat'],
                'from_ayat'
            );
            $to = $this->progressService->makePoint(
                (int) $validated['to_surah_id'],
                (int) $validated['to_ayat'],
                'to_ayat'
            );

            $todayStructured = Tilawah::query()
                ->where('semester_id', $semesterId)
                ->whereDate('tanggal', $tanggal)
                ->whereIn('santri_id', $santriIds)
                ->get()
                ->first(function (Tilawah $tilawah): bool {
                    $payload = $this->progressService->parse(
                        $tilawah->catatan
                    );

                    return is_array($payload)
                        && $this->progressService->isGroupPayload($payload);
                });
            $todayPayload = $todayStructured
                ? $this->progressService->parse($todayStructured->catatan)
                : null;
            $previousStructured = $todayStructured
                ? null
                : $this->latestGroupRecord($santriIds, $tanggal);
            $previousPayload = $previousStructured
                ? $this->progressService->parse($previousStructured->catatan)
                : null;

            $baseline = is_array($todayPayload['baseline'] ?? null)
                ? $todayPayload['baseline']
                : null;

            if ($todayPayload) {
                $expectedFromIndex = (int) (
                    $todayPayload['from']['quran_index'] ?? 0
                );
            } elseif ($previousPayload) {
                $expectedNext = $this->progressService->nextPoint(
                    $previousPayload
                );
                $expectedFromIndex = (int) (
                    $expectedNext['quran_index'] ?? 0
                );
            } else {
                $legacyRecord = $this->latestLegacyRecord($santriIds);
                $expectedFromIndex = 0;

                if ($legacyRecord && $from['quran_index'] > 1) {
                    $baselineThrough = $this->progressService->previousPoint(
                        $from
                    );
                    $baseline = [
                        'through' => $baselineThrough,
                        'source' => 'legacy_confirmation',
                        'reference' => [
                            'tilawah_id' => (int) $legacyRecord->id,
                            'tanggal' => $legacyRecord->tanggal?->format(
                                'Y-m-d'
                            ),
                        ],
                    ];
                } elseif (!$legacyRecord && $from['quran_index'] !== 1) {
                    throw ValidationException::withMessages([
                        'from_ayat' => 'Progress pertama tanpa history lama harus dimulai dari Al-Fatihah ayat 1.',
                    ]);
                }
            }

            if (
                $expectedFromIndex > 0
                && $from['quran_index'] !== $expectedFromIndex
            ) {
                throw ValidationException::withMessages([
                    'from_ayat' => 'Titik mulai berubah dari progress kelompok terakhir. Muat ulang form Tilawah.',
                ]);
            }

            $payload = $this->progressService->buildPayload(
                $from,
                $to,
                $validated['catatan'] ?? null,
                'group',
                $baseline
            );
            $template = $this->progressService->resolveEndpointTemplate($to);
            $catatanFinal = json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            );

            DB::transaction(function () use (
                $santriIds,
                $tanggal,
                $semesterId,
                $musyrif,
                $template,
                $statuses,
                $catatanFinal
            ): void {
                foreach ($santriIds as $santriId) {
                    $existing = Tilawah::query()
                        ->where('santri_id', $santriId)
                        ->where('semester_id', $semesterId)
                        ->whereDate('tanggal', $tanggal)
                        ->get()
                        ->first(function (Tilawah $tilawah): bool {
                            $payload = $this->progressService->parse(
                                $tilawah->catatan
                            );

                            return is_array($payload)
                                && $this->progressService->isGroupPayload(
                                    $payload
                                );
                        });

                    $attributes = [
                        'musyrif_id' => $musyrif->id,
                        'hafalan_template_id' => $template->id,
                        'status' => $statuses->get((int) $santriId),
                        'catatan' => $catatanFinal,
                    ];

                    if ($existing) {
                        $existing->update($attributes);
                    } else {
                        Tilawah::query()->create([
                            'santri_id' => $santriId,
                            'semester_id' => $semesterId,
                            'tanggal' => $tanggal,
                            ...$attributes,
                        ]);
                    }
                }
            });

            return response()->json([
                'ok' => true,
                'message' => 'Berhasil! Progress Tilawah kelompok diterapkan ke '
                    . $santriIds->count()
                    . ' santri.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan data Tilawah.',
            ], 500);
        }
    }

    /**
     * Daftar santri yang memiliki celah Tilawah sampai progress kelompok.
     */
    public function getCatchupOptions(): JsonResponse
    {
        $musyrif = Musyrif::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();
        $santris = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->orderBy('nama')
            ->get(['id', 'nama']);
        $summaries = $this->eligibilityService->summaries(
            $santris->pluck('id')
        );

        $options = $santris->map(function (Santri $santri) use (
            $summaries
        ): ?array {
            $summary = $summaries->get((int) $santri->id);
            $gap = $summary['first_gap'] ?? null;

            if (!$gap) {
                return null;
            }

            return [
                'id' => (int) $santri->id,
                'nama' => $santri->nama,
                'covered_through' => $summary['covered_through'],
                'group_through' => $summary['group_through'],
                'gap' => $gap,
            ];
        })->filter()->values();

        return response()->json([
            'status' => 'success',
            'surahs' => $this->progressService->surahs(),
            'data_santri' => $options,
        ]);
    }

    /**
     * Menyimpan Tilawah Susulan individu untuk menutup celah pertama.
     */
    public function storeCatchup(Request $request): JsonResponse
    {
        $activeSemester = $this->assertAcademicInputOpen();
        $validated = $request->validate([
            'santri_id' => ['required', 'integer', 'exists:santris,id'],
            'from_surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'from_ayat' => ['required', 'integer', 'min:1'],
            'to_surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'to_ayat' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $musyrif = Musyrif::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();
        $santri = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->findOrFail((int) $validated['santri_id']);
        $from = $this->progressService->makePoint(
            (int) $validated['from_surah_id'],
            (int) $validated['from_ayat'],
            'from_ayat'
        );
        $to = $this->progressService->makePoint(
            (int) $validated['to_surah_id'],
            (int) $validated['to_ayat'],
            'to_ayat'
        );
        $summary = $this->eligibilityService
            ->summaries([(int) $santri->id])
            ->get((int) $santri->id);
        $gap = $summary['first_gap'] ?? null;

        if (!$gap) {
            throw ValidationException::withMessages([
                'santri_id' => 'Santri ini tidak memiliki celah Tilawah yang perlu disusulkan.',
            ]);
        }

        if ($from['quran_index'] !== (int) $gap['from_index']) {
            throw ValidationException::withMessages([
                'from_ayat' => 'Tilawah Susulan harus dimulai dari ayat pertama yang masih terlewat.',
            ]);
        }

        if (
            $to['quran_index'] < $from['quran_index']
            || $to['quran_index'] > (int) $gap['to_index']
        ) {
            throw ValidationException::withMessages([
                'to_ayat' => 'Titik akhir Susulan harus berada di dalam celah Tilawah pertama.',
            ]);
        }

        $payload = $this->progressService->buildPayload(
            $from,
            $to,
            $validated['catatan'] ?? null,
            'catchup'
        );
        $template = $this->progressService->resolveEndpointTemplate($to);

        Tilawah::query()->create([
            'santri_id' => $santri->id,
            'musyrif_id' => $musyrif->id,
            'tanggal' => now('Asia/Jakarta')->toDateString(),
            'hafalan_template_id' => $template->id,
            'status' => 'hadir',
            'catatan' => json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            ),
            'semester_id' => (int) $activeSemester->id,
        ]);

        return response()->json([
            'ok' => true,
            'message' => "Tilawah Susulan {$santri->nama} berhasil disimpan.",
        ]);
    }

    /**
     * Menampilkan Datatables Riwayat Tilawah.
     */
    public function datatable(Request $request)
    {
        $musyrif = Musyrif::where('user_id', Auth::id())->firstOrFail();
        $query = Tilawah::query()
            ->where('musyrif_id', $musyrif->id)
            ->with(['santri', 'template'])
            ->select('tilawahs.*');

        if ($request->filter_tanggal === 'today') {
            $query->whereDate('tanggal', today());
        } elseif ($request->filter_tanggal === 'yesterday') {
            $query->whereDate('tanggal', today()->subDay());
        } elseif ($request->filter_tanggal === 'last_7_days') {
            $query->whereDate('tanggal', '>=', today()->subDays(7));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('santri', fn($row) => $row->santri->nama)
            ->addColumn('target_bacaan', function ($row) {
                if ($row->template) {
                    return "<span class='fw-bold'>Juz {$row->template->juz}</span><br><small class='text-muted'>{$row->template->label}</small>";
                }

                return '-';
            })
            ->addColumn('catatan_ayat', function ($row) {
                $text = e($this->progressService->display($row->catatan));

                return "<span class='small text-wrap' style='max-width: 250px; display: inline-block;'>{$text}</span>";
            })
            ->addColumn('status_label', function ($row) {
                $color = match ($row->status) {
                    'hadir' => 'success',
                    'izin' => 'secondary',
                    'sakit' => 'primary',
                    'alpha' => 'danger',
                    default => 'dark',
                };

                return '<span class="badge bg-'
                    . $color
                    . '-subtle text-'
                    . $color
                    . ' rounded-pill px-3">'
                    . ucfirst($row->status)
                    . '</span>';
            })
            ->addColumn('aksi', function ($row) {
                $target = $row->template
                    ? "Juz {$row->template->juz} - {$row->template->label}"
                    : '-';
                $catatanDisplay = $this->progressService->display(
                    $row->catatan
                );
                $catatanNote = $this->progressService->note($row->catatan);

                return '
                <div class="d-flex justify-content-end gap-2 flex-nowrap">
                    <button type="button" class="btn btn-sm btn-info btn-detail-tilawah"
                        data-santri_nama="' . e($row->santri->nama) . '"
                        data-target_bacaan="' . e($target) . '"
                        data-tanggal_label="' . $row->tanggal->format('d M Y') . '"
                        data-status_text="' . $row->status . '"
                        data-catatan="' . e($catatanDisplay) . '"
                        data-coreui-toggle="tooltip" title="Lihat Detail">
                        <i class="bi bi-eye text-white"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-primary btn-edit-tilawah"
                        data-id="' . $row->id . '"
                        data-santri_nama="' . e($row->santri->nama) . '"
                        data-target_bacaan="' . e($target) . '"
                        data-status="' . $row->status . '"
                        data-catatan="' . e($catatanNote) . '"
                        data-coreui-toggle="tooltip" title="Edit Status">
                        <i class="bi bi-pencil-square"></i>
                    </button>

                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-tilawah"
                        data-id="' . $row->id . '"
                        data-coreui-toggle="tooltip" title="Hapus Data">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns([
                'target_bacaan',
                'catatan_ayat',
                'status_label',
                'aksi',
            ])
            ->make(true);
    }

    public function update(Request $request, Tilawah $tilawah)
    {
        $musyrif = Musyrif::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ((int) $tilawah->musyrif_id !== (int) $musyrif->id) {
            return response()->json([
                'message' => 'Unauthorized! Data ini bukan milik Anda.',
            ], 403);
        }

        $activeSemester = $this->assertRecordEditableInActiveSemester(
            $tilawah->semester_id
        );
        $validated = $request->validate([
            'status' => ['required', 'in:hadir,izin,sakit,alpha'],
            'catatan' => ['nullable', 'string'],
        ]);
        $validated['semester_id'] = (int) $activeSemester->id;
        $validated['catatan'] = $this->progressService->withNote(
            $tilawah->catatan,
            $validated['catatan'] ?? null
        );
        $tilawah->update($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Status Tilawah individu berhasil diperbarui.',
        ]);
    }

    public function destroy(Tilawah $tilawah)
    {
        $musyrif = Musyrif::query()
            ->where('user_id', Auth::id())
            ->first();

        if (
            !$musyrif
            || (int) $tilawah->musyrif_id !== (int) $musyrif->id
        ) {
            return response()->json([
                'message' => 'Unauthorized! Data ini bukan milik Anda.',
            ], 403);
        }

        $this->assertRecordEditableInActiveSemester($tilawah->semester_id);
        $tilawah->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Data Tilawah berhasil dihapus.',
        ]);
    }

    private function latestGroupRecord(
        $santriIds,
        ?string $beforeDate = null
    ): ?Tilawah {
        $query = Tilawah::query()
            ->whereIn('santri_id', $santriIds)
            ->where(function ($schemaQuery): void {
                $schemaQuery
                    ->where('catatan', 'like', '%"schema":"tilawah.v1"%')
                    ->orWhere('catatan', 'like', '%"schema":"tilawah.v2"%');
            });

        if ($beforeDate !== null) {
            $query->whereDate('tanggal', '<', $beforeDate);
        }

        return $query
            ->latest('tanggal')
            ->latest('id')
            ->get()
            ->first(function (Tilawah $tilawah): bool {
                $payload = $this->progressService->parse($tilawah->catatan);

                return is_array($payload)
                    && $this->progressService->isGroupPayload($payload);
            });
    }

    private function latestLegacyRecord($santriIds): ?Tilawah
    {
        return Tilawah::query()
            ->with('template')
            ->whereIn('santri_id', $santriIds)
            ->latest('tanggal')
            ->latest('id')
            ->get()
            ->first(fn(Tilawah $tilawah): bool =>
                $this->progressService->parse($tilawah->catatan) === null
            );
    }
}
