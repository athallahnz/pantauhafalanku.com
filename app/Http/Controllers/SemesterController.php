<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Services\Academic\SemesterLifecycleService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class SemesterController extends Controller
{
    public function __construct(
        private readonly SemesterLifecycleService $lifecycleService
    ) {}

    public function getData(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $query = Semester::query()
            ->leftJoin(
                'tahun_ajarans as ta',
                'ta.id',
                '=',
                'semesters.tahun_ajaran_id'
            )
            ->select([
                'semesters.id',
                'semesters.tahun_ajaran_id',
                'semesters.nama',
                'semesters.tanggal_mulai',
                'semesters.tanggal_selesai',
                'semesters.is_active',
                'semesters.status',
                'semesters.input_locked_at',
                'semesters.activated_at',
                'semesters.closed_at',
                'ta.nama as tahun_ajaran_nama',
            ])
            ->orderByDesc('semesters.tanggal_mulai')
            ->orderByDesc('semesters.id');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('periode', function ($row) {
                $mulai = Carbon::parse(
                    $row->tanggal_mulai
                )->translatedFormat('d M Y');

                $selesai = Carbon::parse(
                    $row->tanggal_selesai
                )->translatedFormat('d M Y');

                return '
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">' . e($mulai) . '</span>
                        <small class="text-body-secondary">
                            sampai ' . e($selesai) . '
                        </small>
                    </div>
                ';
            })
            ->addColumn('status_badge', function ($row) {
                return match ($row->status) {
                    Semester::STATUS_ACTIVE => $row->input_locked_at
                        ? '<span class="badge text-bg-warning rounded-pill px-3 py-2">
                            Aktif — Input Dikunci
                           </span>'
                        : '<span class="badge text-bg-success rounded-pill px-3 py-2">
                            Aktif — Input Dibuka
                           </span>',

                    Semester::STATUS_CLOSED =>
                    '<span class="badge text-bg-dark rounded-pill px-3 py-2">
                            Ditutup
                         </span>',

                    default =>
                    '<span class="badge text-bg-secondary rounded-pill px-3 py-2">
                            Draft
                         </span>',
                };
            })
            ->addColumn('aksi', function ($row) {
                $buttons = [];

                if ($row->status === Semester::STATUS_DRAFT) {
                    $buttons[] = '
                        <button type="button"
                            class="btn btn-sm btn-outline-warning rounded-3 btn-edit-semester"
                            data-id="' . $row->id . '"
                            data-nama="' . e($row->nama) . '"
                            data-ta-id="' . $row->tahun_ajaran_id . '"
                            data-mulai="' . e(Carbon::parse($row->tanggal_mulai)->format('Y-m-d')) . '"
                            data-selesai="' . e(Carbon::parse($row->tanggal_selesai)->format('Y-m-d')) . '"
                            title="Edit semester draft">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    ';

                    $buttons[] = '
                        <button type="button"
                            class="btn btn-sm btn-outline-success rounded-3 btn-activate-semester"
                            data-id="' . $row->id . '"
                            data-label="' . e($row->nama . ' - ' . ($row->tahun_ajaran_nama ?? '')) . '"
                            title="Aktifkan semester">
                            <i class="bi bi-play-circle"></i>
                        </button>
                    ';

                    $buttons[] = '
                        <button type="button"
                            class="btn btn-sm btn-outline-danger rounded-3 btn-delete-semester"
                            data-id="' . $row->id . '"
                            data-label="' . e($row->nama . ' - ' . ($row->tahun_ajaran_nama ?? '')) . '"
                            title="Hapus semester draft">
                            <i class="bi bi-trash3"></i>
                        </button>
                    ';
                }

                if ($row->status === Semester::STATUS_ACTIVE) {
                    if ($row->input_locked_at) {
                        $buttons[] = '
                            <button type="button"
                                class="btn btn-sm btn-outline-success rounded-3 btn-unlock-semester-input"
                                data-id="' . $row->id . '"
                                title="Buka kembali input">
                                <i class="bi bi-unlock"></i>
                            </button>
                        ';
                    } else {
                        $buttons[] = '
                            <button type="button"
                                class="btn btn-sm btn-outline-warning rounded-3 btn-lock-semester-input"
                                data-id="' . $row->id . '"
                                title="Kunci input akademik">
                                <i class="bi bi-lock"></i>
                            </button>
                        ';
                    }
                }

                if ($buttons === []) {
                    return '<span class="text-body-secondary small">
                        Tidak ada aksi
                    </span>';
                }

                return '<div class="d-flex justify-content-end gap-2">'
                    . implode('', $buttons)
                    . '</div>';
            })
            ->filterColumn(
                'tahun_ajaran_nama',
                fn($query, $keyword) =>
                $query->where('ta.nama', 'like', "%{$keyword}%")
            )
            ->orderColumn('tahun_ajaran_nama', 'ta.nama $1')
            ->rawColumns([
                'periode',
                'status_badge',
                'aksi',
            ])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        /*
         * Semester baru selalu draft.
         * Aktivasi hanya boleh melalui endpoint lifecycle activate().
         */
        $validated['status'] = Semester::STATUS_DRAFT;
        $validated['is_active'] = false;
        $validated['input_locked_at'] = null;
        $validated['activated_at'] = null;
        $validated['closed_at'] = null;

        $semester = Semester::query()->create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Semester draft berhasil ditambahkan.',
            'data' => $semester->load('tahunAjaran'),
        ], 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $semester = Semester::query()->findOrFail($id);

        if (!$semester->isDraft()) {
            throw ValidationException::withMessages([
                'semester' => [
                    'Hanya semester draft yang dapat diedit.',
                ],
            ]);
        }

        $validated = $this->validateRequest(
            $request,
            $semester->id
        );

        $semester->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Semester draft berhasil diperbarui.',
            'data' => $semester->fresh('tahunAjaran'),
        ]);
    }

    public function activate(Semester $semester): JsonResponse
    {
        $semester = $this->lifecycleService->activate($semester);

        return response()->json([
            'status' => 'success',
            'message' => 'Semester berhasil diaktifkan. Semester aktif sebelumnya otomatis ditutup.',
            'data' => $semester,
        ]);
    }

    public function lockInput(Semester $semester): JsonResponse
    {
        $semester = $this->lifecycleService->lockInput($semester);

        return response()->json([
            'status' => 'success',
            'message' => 'Input akademik semester berhasil dikunci.',
            'data' => $semester,
        ]);
    }

    public function unlockInput(Semester $semester): JsonResponse
    {
        $semester = $this->lifecycleService->unlockInput($semester);

        return response()->json([
            'status' => 'success',
            'message' => 'Input akademik semester berhasil dibuka kembali.',
            'data' => $semester,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $semester = Semester::query()->findOrFail($id);

        if (!$semester->isDraft()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya semester draft yang dapat dihapus.',
            ], 422);
        }

        try {
            $semester->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Semester tidak dapat dihapus karena masih digunakan oleh data lain.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Semester draft berhasil dihapus.',
        ]);
    }

    private function validateRequest(
        Request $request,
        ?int $ignoreId = null
    ): array {
        return $request->validate([
            'tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:tahun_ajarans,id',
            ],
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('semesters', 'nama')
                    ->where(fn($query) => $query->where(
                        'tahun_ajaran_id',
                        $request->integer('tahun_ajaran_id')
                    ))
                    ->ignore($ignoreId),
            ],
            'tanggal_mulai' => [
                'required',
                'date',
            ],
            'tanggal_selesai' => [
                'required',
                'date',
                'after:tanggal_mulai',
            ],
        ]);
    }
}
