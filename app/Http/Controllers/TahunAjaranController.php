<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\TahunAjaran;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class TahunAjaranController extends Controller
{
    public function getData(Request $request)
    {
        abort_unless($request->ajax(), 404);

        // tanggal_mulai dan tanggal_selesai wajib ikut select karena dipakai
        // pada kolom periode dan payload tombol edit.
        $query = TahunAjaran::query()
            ->select([
                'id',
                'nama',
                'tanggal_mulai',
                'tanggal_selesai',
                'is_active',
            ])
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('nama');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('periode', function (TahunAjaran $row) {
                $mulai = Carbon::parse($row->tanggal_mulai)->translatedFormat('d M Y');
                $selesai = Carbon::parse($row->tanggal_selesai)->translatedFormat('d M Y');

                return '
                    <div class="d-flex flex-column">
                        <span class="fw-semibold">' . e($mulai) . '</span>
                        <small class="text-body-secondary">sampai ' . e($selesai) . '</small>
                    </div>
                ';
            })
            ->addColumn('status_badge', fn(TahunAjaran $row) => $row->is_active
                ? '<span class="badge text-bg-success rounded-pill px-3 py-2">Aktif</span>'
                : '<span class="badge text-bg-secondary rounded-pill px-3 py-2">Nonaktif</span>')
            ->addColumn('aksi', function (TahunAjaran $row) {
                return '
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-warning rounded-3 btn-edit-ta"
                            data-id="' . $row->id . '"
                            data-nama="' . e($row->nama) . '"
                            data-active="' . (int) $row->is_active . '"
                            data-mulai="' . e(Carbon::parse($row->tanggal_mulai)->format('Y-m-d')) . '"
                            data-selesai="' . e(Carbon::parse($row->tanggal_selesai)->format('Y-m-d')) . '"
                            title="Edit tahun ajaran">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-danger rounded-3 btn-delete-ta"
                            data-id="' . $row->id . '"
                            data-label="' . e($row->nama) . '"
                            title="Hapus tahun ajaran">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['periode', 'status_badge', 'aksi'])
            ->make(true);
    }

    public function getOptions(): JsonResponse
    {
        $tahunAjaran = TahunAjaran::query()
            ->select(['id', 'nama', 'is_active'])
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('nama')
            ->get();

        return response()->json(['data' => $tahunAjaran]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);
        $validated['is_active'] = $request->boolean('is_active');

        $tahunAjaran = DB::transaction(function () use ($validated) {
            if ($validated['is_active']) {
                TahunAjaran::query()->update(['is_active' => false]);
            }

            return TahunAjaran::create($validated);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Tahun ajaran berhasil ditambahkan.',
            'data' => $tahunAjaran,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tahunAjaran = TahunAjaran::findOrFail($id);
        $validated = $this->validateRequest($request, $tahunAjaran->id);
        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($tahunAjaran, $validated) {
            if ($validated['is_active']) {
                TahunAjaran::query()
                    ->where('id', '!=', $tahunAjaran->id)
                    ->update(['is_active' => false]);
            }

            $tahunAjaran->update($validated);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Tahun ajaran berhasil diperbarui.',
            'data' => $tahunAjaran->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tahunAjaran = TahunAjaran::findOrFail($id);

        if (Semester::where('tahun_ajaran_id', $tahunAjaran->id)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tahun ajaran tidak dapat dihapus karena masih memiliki data semester.',
            ], 422);
        }

        try {
            $tahunAjaran->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tahun ajaran tidak dapat dihapus karena masih digunakan oleh data lain.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Tahun ajaran berhasil dihapus.',
        ]);
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tahun_ajarans', 'nama')->ignore($ignoreId),
            ],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
