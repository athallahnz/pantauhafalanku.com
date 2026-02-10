<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kelas;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class KelasController extends Controller
{
    public function index()
    {
        // Hanya return view, data diambil via AJAX DataTables
        return view('kelas.index');
    }

    public function getData(Request $request)
    {
        if (!$request->ajax())
            abort(404);

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
            ");

        return DataTables::of($query)
            ->addIndexColumn()

            // tampil rapi di tabel, tetapi sorting/search tetap dari kolom asli
            ->editColumn('deskripsi', function ($row) {
                return $row->deskripsi
                    ? e(Str::limit($row->deskripsi, 60))
                    : '-';
            })

            ->addColumn('aksi', function ($row) {
                return '
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary btn-edit flex-sm-grow-0"
                        data-id="' . $row->id . '"
                        data-nama="' . e($row->nama_kelas) . '"
                        data-deskripsi="' . e($row->deskripsi) . '">
                        Edit
                    </button>

                    <button class="btn btn-sm btn-outline-danger btn-delete flex-sm-grow-0"
                        data-id="' . $row->id . '">
                        Hapus
                    </button>
                </div>
            ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        $kelas = Kelas::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kelas berhasil dibuat.',
                'data' => $kelas,
            ]);
        }

        return redirect()
            ->route('kelas.index')
            ->with('success', 'Kelas berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);

        $validated = $request->validate([
            'nama_kelas' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        $kelas->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kelas berhasil diupdate.',
                'data' => $kelas,
            ]);
        }

        return redirect()
            ->route('kelas.index')
            ->with('success', 'Kelas berhasil diupdate.');
    }

    public function destroy(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Kelas berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('kelas.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }

    // create() & edit() sudah tidak dipakai jika semua via modal,
    // boleh dibiarkan kosong / dihapus.
}
