<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class KelasController extends Controller
{
    public function index()
    {
        return view('kelas.index');
    }

    public function getData(Request $request)
    {
        abort_unless($request->ajax(), 404);

        $query = Kelas::query()
            ->select(['id', 'nama_kelas', 'deskripsi'])
            ->orderByRaw("
                CASE
                    WHEN nama_kelas = 'Kelas 7' THEN 1
                    WHEN nama_kelas = 'Kelas 8' THEN 2
                    WHEN nama_kelas = 'Kelas 9' THEN 3
                    WHEN nama_kelas = 'Kelas 10' THEN 4
                    WHEN nama_kelas = 'Kelas 10 INT' THEN 5
                    WHEN nama_kelas = 'Kelas 11' THEN 6
                    WHEN nama_kelas = 'Kelas 11 INT' THEN 7
                    ELSE 99
                END
            ")
            ->orderBy('nama_kelas');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('deskripsi', fn(Kelas $row) => $row->deskripsi
                ? e(Str::limit($row->deskripsi, 80))
                : '<span class="text-body-secondary">Tidak ada deskripsi</span>')
            ->addColumn('aksi', function (Kelas $row) {
                return '
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-warning rounded-3 btn-edit-kelas"
                            data-id="' . $row->id . '"
                            data-nama="' . e($row->nama_kelas) . '"
                            data-deskripsi="' . e($row->deskripsi ?? '') . '"
                            title="Edit kelas">
                            <i class="bi bi-pencil-square"></i>
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-danger rounded-3 btn-delete-kelas"
                            data-id="' . $row->id . '"
                            data-label="' . e($row->nama_kelas) . '"
                            title="Hapus kelas">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['deskripsi', 'aksi'])
            ->make(true);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kelas', 'nama_kelas'),
            ],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ]);

        $kelas = Kelas::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil ditambahkan.',
            'data' => $kelas,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $kelas = Kelas::findOrFail($id);

        $validated = $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:100',
                Rule::unique('kelas', 'nama_kelas')->ignore($kelas->id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ]);

        $kelas->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil diperbarui.',
            'data' => $kelas->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $kelas = Kelas::findOrFail($id);

        try {
            $kelas->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kelas tidak dapat dihapus karena masih digunakan oleh data lain.',
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Kelas berhasil dihapus.',
        ]);
    }
}
