<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Support\Academic\SantriSemesterPlacementService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class SantriArchiveController extends Controller
{
    public function index()
    {
        $kelasList = Kelas::query()
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas']);

        $semesterList = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get([
                'id',
                'tahun_ajaran_id',
                'nama',
                'tanggal_mulai',
                'status',
            ]);

        $activeSantriList = Santri::query()
            ->active()
            ->with('kelas:id,nama_kelas')
            ->orderBy('nama')
            ->get([
                'id',
                'nama',
                'nis',
                'kelas_id',
            ]);

        return view(
            'admin.santri.archive.index',
            compact(
                'kelasList',
                'semesterList',
                'activeSantriList'
            )
        );
    }

    public function data(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $query = $this->archiveQuery($request, true);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('identity', function (Santri $santri): string {
                $nis = e($santri->nis ?: '-');

                $gender = match ($santri->jenis_kelamin) {
                    'L' => 'Laki-laki',
                    'P' => 'Perempuan',
                    default => '-',
                };

                return '
                    <div>
                        <div class="fw-bold">' . e($santri->nama) . '</div>
                        <div class="small text-body-secondary">
                            NIS: ' . $nis . ' • ' . e($gender) . '
                        </div>
                    </div>
                ';
            })
            ->addColumn(
                'status_badge',
                fn(Santri $santri): string => $this->statusBadge($santri->status)
            )
            ->addColumn(
                'last_class',
                fn(Santri $santri): string => e($santri->kelas?->nama_kelas ?? '-')
            )
            ->addColumn('graduation_period', function (Santri $santri): string {
                if (!$santri->isGraduated()) {
                    return '<span class="text-body-secondary">-</span>';
                }

                $semester = $santri->graduatedSemester;

                if (!$semester) {
                    return '<span class="text-warning">Semester tidak tersedia</span>';
                }

                $label = trim(
                    $semester->nama
                        . ' — '
                        . ($semester->tahunAjaran?->nama ?? '-')
                );

                return e($label);
            })
            ->addColumn('status_date', function (Santri $santri): string {
                $date = $santri->isGraduated()
                    ? ($santri->graduated_at ?? $santri->status_changed_at)
                    : $santri->status_changed_at;

                return $date
                    ? e($date->translatedFormat('d M Y, H:i'))
                    : '-';
            })
            ->addColumn('reason', function (Santri $santri): string {
                $reason = trim((string) $santri->status_reason);

                if ($reason === '') {
                    return '<span class="text-body-secondary">Tidak ada catatan</span>';
                }

                return '<div class="archive-reason" title="'
                    . e($reason)
                    . '">'
                    . e($reason)
                    . '</div>';
            })
            ->addColumn('progress_summary', function (Santri $santri): string {
                return '
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge text-bg-primary">Hafalan '
                    . number_format($santri->hafalans_count)
                    . '</span>
                        <span class="badge text-bg-success">Tahsin '
                    . number_format($santri->tahsins_count)
                    . '</span>
                        <span class="badge text-bg-info">Tilawah '
                    . number_format($santri->tilawahs_count)
                    . '</span>
                    </div>
                ';
            })
            ->addColumn('action', function (Santri $santri): string {
                $progressUrl = route(
                    'admin.santri.master.progress.show',
                    $santri->id
                );

                return '
                    <div class="d-flex flex-nowrap gap-1">
                        <button type="button"
                            class="btn btn-sm btn-outline-primary btn-detail-archive"
                            data-id="' . $santri->id . '"
                            title="Detail arsip">
                            <i class="bi bi-eye"></i>
                        </button>

                        <a href="' . e($progressUrl) . '"
                            class="btn btn-sm btn-outline-success"
                            title="Lihat seluruh progress">
                            <i class="bi bi-graph-up-arrow"></i>
                        </a>

                        <button type="button"
                            class="btn btn-sm btn-outline-warning btn-edit-archive-status"
                            data-id="' . $santri->id . '"
                            title="Koreksi status arsip">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-dark btn-reactivate-santri"
                            data-id="' . $santri->id . '"
                            title="Aktifkan kembali">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                ';
            })
            ->filterColumn('identity', function (Builder $query, string $keyword): void {
                $query->where(function (Builder $inner) use ($keyword): void {
                    $inner
                        ->where('nama', 'like', "%{$keyword}%")
                        ->orWhere('nis', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn(
                'last_class',
                fn(Builder $query, string $keyword) =>
                $query->whereHas(
                    'kelas',
                    fn(Builder $kelasQuery) =>
                    $kelasQuery->where('nama_kelas', 'like', "%{$keyword}%")
                )
            )
            ->filterColumn(
                'graduation_period',
                fn(Builder $query, string $keyword) =>
                $query->whereHas(
                    'graduatedSemester',
                    function (Builder $semesterQuery) use ($keyword): void {
                        $semesterQuery
                            ->where('nama', 'like', "%{$keyword}%")
                            ->orWhereHas(
                                'tahunAjaran',
                                fn(Builder $tahunQuery) =>
                                $tahunQuery->where('nama', 'like', "%{$keyword}%")
                            );
                    }
                )
            )
            ->filterColumn(
                'reason',
                fn(Builder $query, string $keyword) =>
                $query->where('status_reason', 'like', "%{$keyword}%")
            )
            ->orderColumn(
                'status_date',
                'COALESCE(santris.graduated_at, santris.status_changed_at) $1'
            )
            ->rawColumns([
                'identity',
                'status_badge',
                'graduation_period',
                'reason',
                'progress_summary',
                'action',
            ])
            ->toJson();
    }

    public function statistics(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Gunakan query agregat murni
        |--------------------------------------------------------------------------
        |
        | Query DataTables membawa eager loading dan withCount(), sehingga SELECT
        | berisi santris.* serta subquery progress. Query tersebut tidak boleh
        | langsung dipakai bersama GROUP BY status pada MySQL ONLY_FULL_GROUP_BY.
        |
        */
        $query = $this->archiveBaseQuery(
            $request,
            false
        );

        $statusCounts = (clone $query)
            ->select('santris.status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('santris.status')
            ->pluck('total', 'status');

        $recentCount = (clone $query)
            ->where(function (Builder $dateQuery): void {
                $dateQuery
                    ->where(
                        'santris.status_changed_at',
                        '>=',
                        now()->subDays(30)
                    )
                    ->orWhere(
                        'santris.graduated_at',
                        '>=',
                        now()->subDays(30)
                    );
            })
            ->count();

        return response()->json([
            'total' => (int) array_sum(
                $statusCounts->all()
            ),
            'lulus' => (int) (
                $statusCounts[Santri::STATUS_LULUS] ?? 0
            ),
            'keluar' => (int) (
                $statusCounts[Santri::STATUS_KELUAR] ?? 0
            ),
            'nonaktif' => (int) (
                $statusCounts[Santri::STATUS_NONAKTIF] ?? 0
            ),
            'recent' => $recentCount,
        ]);
    }

    public function show(Santri $santri): JsonResponse
    {
        abort_unless($santri->isInactive(), 404);

        $santri->load([
            'user:id,name,nomor,email',
            'kelas:id,nama_kelas',
            'graduatedSemester.tahunAjaran:id,nama',
            'statusChangedBy:id,name',
            'statusHistories' => fn($query) =>
            $query
                ->with([
                    'semester.tahunAjaran:id,nama',
                    'kelas:id,nama_kelas',
                    'musyrif:id,nama,kode',
                    'changedBy:id,name',
                ])
                ->orderByDesc('changed_at')
                ->orderByDesc('id'),
        ])->loadCount([
            'hafalans',
            'tahsins',
            'tilawahs',
        ]);

        return response()->json([
            'ok' => true,
            'santri' => [
                'id' => $santri->id,
                'nama' => $santri->nama,
                'nis' => $santri->nis,
                'tanggal_lahir' => $santri->tanggal_lahir?->format('Y-m-d'),
                'jenis_kelamin' => $santri->jenis_kelamin,
                'status' => $santri->status,
                'status_label' => $this->statusLabel($santri->status),
                'kelas_id' => $santri->kelas_id,
                'kelas_nama' => $santri->kelas?->nama_kelas,
                'graduated_semester_id' => $santri->graduated_semester_id,
                'graduated_semester_label' => $santri->graduatedSemester
                    ? trim(
                        $santri->graduatedSemester->nama
                            . ' — '
                            . ($santri->graduatedSemester->tahunAjaran?->nama ?? '-')
                    )
                    : null,
                'graduated_at' => $santri->graduated_at?->toIso8601String(),
                'status_changed_at' => $santri->status_changed_at?->toIso8601String(),
                'status_reason' => $santri->status_reason,
                'status_changed_by' => $santri->statusChangedBy?->name,
                'user' => $santri->user
                    ? [
                        'name' => $santri->user->name,
                        'nomor' => $santri->user->nomor,
                        'email' => $santri->user->email,
                    ]
                    : null,
                'progress' => [
                    'hafalan' => $santri->hafalans_count,
                    'tahsin' => $santri->tahsins_count,
                    'tilawah' => $santri->tilawahs_count,
                ],
            ],
            'histories' => $santri->statusHistories
                ->map(function ($history): array {
                    $semesterLabel = $history->semester
                        ? trim(
                            $history->semester->nama
                                . ' — '
                                . ($history->semester->tahunAjaran?->nama ?? '-')
                        )
                        : null;

                    return [
                        'id' => $history->id,
                        'from_status' => $history->from_status,
                        'from_status_label' => $this->statusLabel($history->from_status),
                        'to_status' => $history->to_status,
                        'to_status_label' => $this->statusLabel($history->to_status),
                        'semester' => $semesterLabel,
                        'kelas' => $history->kelas?->nama_kelas,
                        'musyrif' => $history->musyrif?->nama,
                        'reason' => $history->reason,
                        'changed_by' => $history->changedBy?->name,
                        'changed_at' => $history->changed_at?->toIso8601String(),
                    ];
                })
                ->values(),
        ]);
    }

    public function deactivate(Request $request, Santri $santri): JsonResponse
    {
        if (!$santri->isActive()) {
            throw ValidationException::withMessages([
                'santri' => ['Hanya santri aktif yang dapat diarsipkan.'],
            ]);
        }

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    Santri::STATUS_KELUAR,
                    Santri::STATUS_NONAKTIF,
                ]),
            ],
            'reason' => ['required', 'string', 'max:2000'],
            'changed_at' => ['required', 'date'],
        ]);

        $this->performStatusChange(
            $santri,
            $data['status'],
            $data['reason'],
            null,
            $data['changed_at']
        );

        return response()->json([
            'ok' => true,
            'message' => "{$santri->nama} berhasil dipindahkan ke arsip "
                . $this->statusLabel($data['status'])
                . '.',
        ]);
    }

    public function updateStatus(Request $request, Santri $santri): JsonResponse
    {
        if (!$santri->isInactive()) {
            throw ValidationException::withMessages([
                'santri' => ['Koreksi status hanya tersedia untuk santri arsip.'],
            ]);
        }

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in(Santri::inactiveStatuses()),
            ],
            'graduated_semester_id' => [
                Rule::requiredIf(
                    fn() => $request->input('status') === Santri::STATUS_LULUS
                ),
                'nullable',
                'integer',
                'exists:semesters,id',
            ],
            'reason' => ['required', 'string', 'max:2000'],
            'changed_at' => ['required', 'date'],
        ]);

        if ($data['status'] === $santri->status) {
            throw ValidationException::withMessages([
                'status' => ['Pilih status yang berbeda dari status saat ini.'],
            ]);
        }

        $semester = null;

        if ($data['status'] === Santri::STATUS_LULUS) {
            $semester = Semester::query()
                ->findOrFail($data['graduated_semester_id']);
        }

        $this->performStatusChange(
            $santri,
            $data['status'],
            $data['reason'],
            $semester,
            $data['changed_at']
        );

        return response()->json([
            'ok' => true,
            'message' => 'Status arsip santri berhasil diperbarui.',
        ]);
    }

    public function reactivate(
        Request $request,
        Santri $santri
    ): JsonResponse {
        if (!$santri->isInactive()) {
            throw ValidationException::withMessages([
                'santri' => [
                    'Santri ini tidak berada dalam arsip.',
                ],
            ]);
        }

        $data = $request->validate([
            'kelas_id' => [
                'required',
                'integer',
                'exists:kelas,id',
            ],
            'musyrif_id' => [
                'required',
                'integer',
                'exists:musyrifs,id',
            ],
            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
            'changed_at' => [
                'required',
                'date',
            ],
        ]);

        $musyrifValid = Musyrif::query()
            ->whereKey($data['musyrif_id'])
            ->where(
                'kelas_id',
                $data['kelas_id']
            )
            ->exists();

        if (!$musyrifValid) {
            throw ValidationException::withMessages([
                'musyrif_id' => [
                    'Musyrif tidak bertugas pada kelas reaktivasi.',
                ],
            ]);
        }

        $activeSemester = Semester::query()
            ->active()
            ->first();

        if (!$activeSemester) {
            throw ValidationException::withMessages([
                'semester' => [
                    'Semester aktif tidak ditemukan untuk reaktivasi.',
                ],
            ]);
        }

        $changedAtValue =
            Carbon::parse(
                $data['changed_at']
            );

        try {
            DB::transaction(function () use (
                $santri,
                $data,
                $activeSemester,
                $changedAtValue
            ): void {
                $santri->reactivate(
                    (int) $data['kelas_id'],
                    (int) $data['musyrif_id'],
                    $data['reason'],
                    (int) auth()->id(),
                    $changedAtValue
                );

                app(
                    SantriSemesterPlacementService::class
                )->recordStatusChange(
                    $santri,
                    $activeSemester,
                    SantriSemesterPlacement::STATUS_AKTIF,
                    SantriSemesterPlacement::TYPE_REAKTIVASI,
                    (int) $data['kelas_id'],
                    (int) $data['musyrif_id'],
                    $data['reason'],
                    (int) auth()->id(),
                    $changedAtValue,
                    [
                        'source' =>
                        'santri_archive_reactivation',
                    ]
                );
            });
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'santri' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' =>
            "{$santri->nama} berhasil diaktifkan kembali.",
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filename = 'arsip-santri-' . now()->format('Ymd-His') . '.csv';
        $query = $this->archiveQuery($request, true)->orderBy('id');

        return response()->streamDownload(
            function () use ($query): void {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'NIS',
                    'Nama',
                    'Status',
                    'Kelas Terakhir',
                    'Semester Kelulusan',
                    'Tanggal Status',
                    'Alasan',
                    'Diubah Oleh',
                    'Jumlah Hafalan',
                    'Jumlah Tahsin',
                    'Jumlah Tilawah',
                ], ';');

                $query->chunkById(500, function ($santris) use ($handle): void {
                    foreach ($santris as $santri) {
                        $semesterLabel = $santri->graduatedSemester
                            ? trim(
                                $santri->graduatedSemester->nama
                                    . ' — '
                                    . ($santri->graduatedSemester->tahunAjaran?->nama ?? '-')
                            )
                            : '-';

                        $statusDate = $santri->isGraduated()
                            ? ($santri->graduated_at ?? $santri->status_changed_at)
                            : $santri->status_changed_at;

                        fputcsv($handle, [
                            $santri->nis,
                            $santri->nama,
                            $this->statusLabel($santri->status),
                            $santri->kelas?->nama_kelas,
                            $semesterLabel,
                            $statusDate?->format('Y-m-d H:i:s'),
                            $santri->status_reason,
                            $santri->statusChangedBy?->name,
                            $santri->hafalans_count,
                            $santri->tahsins_count,
                            $santri->tilawahs_count,
                        ], ';');
                    }
                });

                fclose($handle);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    private function archiveQuery(
        Request $request,
        bool $includeStatusFilter
    ): Builder {
        return $this->archiveBaseQuery(
            $request,
            $includeStatusFilter
        )
            ->with([
                'kelas:id,nama_kelas',
                'graduatedSemester.tahunAjaran:id,nama',
                'statusChangedBy:id,name',
            ])
            ->withCount([
                'hafalans',
                'tahsins',
                'tilawahs',
            ]);
    }

    /**
     * Query dasar tanpa eager loading dan tanpa withCount().
     * Aman digunakan untuk COUNT(), GROUP BY, dan statistik agregat.
     */
    private function archiveBaseQuery(
        Request $request,
        bool $includeStatusFilter
    ): Builder {
        $query = Santri::query()
            ->inactive();

        if (
            $includeStatusFilter
            && $request->filled('status')
            && in_array(
                $request->input('status'),
                Santri::inactiveStatuses(),
                true
            )
        ) {
            $query->where(
                'santris.status',
                $request->input('status')
            );
        }

        if ($request->filled('kelas_id')) {
            $query->where(
                'santris.kelas_id',
                (int) $request->input('kelas_id')
            );
        }

        if (
            $request->filled(
                'graduated_semester_id'
            )
        ) {
            $query->where(
                'santris.graduated_semester_id',
                (int) $request->input(
                    'graduated_semester_id'
                )
            );
        }

        if ($request->filled('date_start')) {
            $dateStart = Carbon::parse(
                $request->input('date_start')
            )->startOfDay();

            $query->where(function (
                Builder $dateQuery
            ) use ($dateStart): void {
                $dateQuery
                    ->where(
                        'santris.status_changed_at',
                        '>=',
                        $dateStart
                    )
                    ->orWhere(
                        'santris.graduated_at',
                        '>=',
                        $dateStart
                    );
            });
        }

        if ($request->filled('date_end')) {
            $dateEnd = Carbon::parse(
                $request->input('date_end')
            )->endOfDay();

            $query->where(function (
                Builder $dateQuery
            ) use ($dateEnd): void {
                $dateQuery
                    ->where(
                        'santris.status_changed_at',
                        '<=',
                        $dateEnd
                    )
                    ->orWhere(
                        'santris.graduated_at',
                        '<=',
                        $dateEnd
                    );
            });
        }

        return $query;
    }

    private function performStatusChange(
        Santri $santri,
        string $status,
        string $reason,
        ?Semester $semester,
        string $changedAt
    ): void {
        $changedAtValue =
            Carbon::parse($changedAt);

        /*
         * Untuk lulus, gunakan semester kelulusan.
         * Untuk keluar/nonaktif, gunakan semester aktif.
         */
        $placementSemester = $semester
            ?? Semester::query()
            ->active()
            ->first();

        if (!$placementSemester) {
            throw ValidationException::withMessages([
                'semester' => [
                    'Semester untuk mencatat placement status tidak ditemukan.',
                ],
            ]);
        }

        $oldClassId = $santri->kelas_id;
        $oldMusyrifId = $santri->musyrif_id;

        try {
            DB::transaction(function () use (
                $santri,
                $status,
                $reason,
                $semester,
                $placementSemester,
                $changedAtValue,
                $oldClassId,
                $oldMusyrifId
            ): void {
                $santri->changeStatus(
                    $status,
                    $reason,
                    $semester,
                    (int) auth()->id(),
                    $changedAtValue
                );

                app(
                    SantriSemesterPlacementService::class
                )->recordStatusChange(
                    $santri,
                    $placementSemester,
                    $status,
                    SantriSemesterPlacement::TYPE_KOREKSI_STATUS,
                    $oldClassId,
                    $oldMusyrifId,
                    $reason,
                    (int) auth()->id(),
                    $changedAtValue,
                    [
                        'source' =>
                        'santri_archive_status_change',
                    ]
                );
            });
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'status' => [
                    $exception->getMessage(),
                ],
            ]);
        }
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            Santri::STATUS_AKTIF => 'Aktif',
            Santri::STATUS_LULUS => 'Lulus',
            Santri::STATUS_KELUAR => 'Keluar',
            Santri::STATUS_NONAKTIF => 'Nonaktif',
            null => '-',
            default => ucfirst($status),
        };
    }

    private function statusBadge(string $status): string
    {
        [$class, $icon] = match ($status) {
            Santri::STATUS_LULUS => ['text-bg-success', 'bi-mortarboard-fill'],
            Santri::STATUS_KELUAR => ['text-bg-danger', 'bi-box-arrow-right'],
            Santri::STATUS_NONAKTIF => ['text-bg-secondary', 'bi-pause-circle-fill'],
            default => ['text-bg-light', 'bi-question-circle'],
        };

        return '<span class="badge rounded-pill '
            . $class
            . '"><i class="bi '
            . $icon
            . ' me-1"></i>'
            . e($this->statusLabel($status))
            . '</span>';
    }
}
