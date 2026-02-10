<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Musyrif;
use App\Models\Santri;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Generate kode musyrif, contoh:
     * "Athallah Naufal Zuhdi" + id=1 → "ANZ-01"
     */
    private function generateKode(string $nama, int $id): string
    {
        // ambil inisial per kata
        $parts = preg_split('/\s+/', trim($nama));
        $initials = '';

        foreach ($parts as $p) {
            $initials .= strtoupper(substr($p, 0, 1));
        }

        $initials = substr($initials, 0, 3); // maksimal 3 huruf
        $suffix = str_pad($id, 2, '0', STR_PAD_LEFT);

        return $initials . '-' . $suffix;
    }

    public function index()
    {
        // View saja, data di-load lewat AJAX DataTables
        return view('superadmin.users.index');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nomor' => 'nullable|string|max:20|unique:users,nomor,',
            'role' => 'required|string|in:superadmin,admin,musyrif,santri',
            'password' => 'required|min:6'
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'nomor' => $request->nomor,
                'role' => $request->role,
                'password' => bcrypt($request->password)
            ]);

            // ROLE: MUSYRIF
            if ($user->role === 'musyrif') {
                $musyrif = Musyrif::create([
                    'user_id' => $user->id,
                    'nama' => $user->name,
                ]);

                $musyrif->kode = $this->generateKode($musyrif->nama, $musyrif->id);
                $musyrif->save();
            }

            // ROLE: SANTRI
            if ($user->role === 'santri') {
                $santri = Santri::create([
                    'user_id' => $user->id,
                    'kelas_id' => 1,         // default kelas ID
                    'nama' => $user->name,
                ]);

                $santri->nis = null;        // kalau memang belum ada
                $santri->save();
            }

            DB::commit();

            return response()->json(['message' => 'User berhasil ditambahkan.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            // supaya error di swal lebih informatif
            return response()->json([
                'message' => 'Gagal menyimpan user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'nomor' => 'nullable|string|max:20|unique:users,nomor,' . $user->id,
            'role' => 'required|in:superadmin,admin,musyrif,santri',
        ]);

        DB::beginTransaction();

        try {
            $oldRole = $user->role;

            // update basic user
            $user->update($request->only(['name', 'email', 'role']));

            if ($request->filled('password')) {
                $user->update([
                    'password' => bcrypt($request->password),
                ]);
            }

            // ==========================
            // UPDATE / CREATE MUSYRIF
            // ==========================
            if ($user->role === 'musyrif') {

                if (!$user->musyrif) {
                    $musyrif = Musyrif::create([
                        'user_id' => $user->id,
                        'nama' => $user->name,
                    ]);

                    $musyrif->kode = $this->generateKode($musyrif->nama, $musyrif->id);
                    $musyrif->save();
                } else {
                    $user->musyrif->update([
                        'nama' => $user->name,
                    ]);
                }
            }

            // ==========================
            // UPDATE / CREATE SANTRI
            // ==========================
            if ($user->role === 'santri') {

                if (!$user->santri) {
                    $santri = Santri::create([
                        'user_id' => $user->id,
                        'kelas_id' => 1,            // default kelas
                        'nama' => $user->name,
                    ]);

                    $santri->nis = null;
                    $santri->save();
                } else {
                    $user->santri->update([
                        'nama' => $user->name,
                    ]);
                }
            }

            // ==========================
            // BERSIHKAN PROFIL LAMA
            // ==========================
            if ($oldRole === 'musyrif' && $user->role !== 'musyrif') {
                // hapus profil musyrif lama jika ada
                $user->musyrif()->delete();
            }

            if ($oldRole === 'santri' && $user->role !== 'santri') {
                // hapus profil santri lama jika ada
                $user->santri()->delete();
            }

            DB::commit();

            return response()->json(['message' => 'User berhasil diperbarui.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal memperbarui user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('superadmin.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    public function getData(Request $request)
    {
        if (!$request->ajax())
            abort(404);

        $role = $request->get('role'); // superadmin|admin|musyrif|santri|null
        $query = User::query()->select(['id', 'name', 'email', 'nomor', 'role']);

        if ($role) {
            $query->where('role', $role);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('role', function ($row) {
                $colors = [
                    'superadmin' => 'danger',
                    'admin' => 'primary',
                    'musyrif' => 'success',
                    'santri' => 'warning',
                ];
                $color = $colors[$row->role] ?? 'secondary';

                return '<span class="badge bg-' . $color . '">'
                    . strtoupper(e($row->role)) .
                    '</span>';
            })
            ->addColumn('aksi', function ($row) {
                return '
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary btn-edit flex-sm-grow-0"
                        data-id="' . $row->id . '"
                        data-name="' . e($row->name) . '"
                        data-email="' . e($row->email) . '"
                        data-nomor="' . e($row->nomor) . '"
                        data-role="' . e($row->role) . '">
                        Edit
                    </button>

                    <button class="btn btn-sm btn-outline-danger btn-delete flex-sm-grow-0"
                        data-id="' . $row->id . '">
                        Hapus
                    </button>
                </div>
            ';
            })
            ->rawColumns(['role', 'aksi'])
            ->make(true);
    }
}
