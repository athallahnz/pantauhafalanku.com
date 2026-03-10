<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\Kelas;
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
        // Ambil data kelas untuk dropdown di modal
        $kelas = Kelas::all();
        return view('superadmin.users.index', compact('kelas'));
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
        if (!$request->ajax()) abort(404);

        $role = $request->get('role');
        $query = User::query()->select(['id', 'name', 'email', 'nomor', 'role', 'is_approved']);

        if ($role) {
            $query->where('role', $role);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            // Tambahkan di bagian awal penambahan kolom
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="form-check-input user-checkbox" value="' . $row->id . '">';
            })
            ->editColumn('role', function ($row) {
                $colors = ['superadmin' => 'danger', 'admin' => 'success', 'musyrif' => 'warning', 'santri' => 'primary', 'pimpinan' => 'info'];
                $color = $colors[$row->role] ?? 'secondary';
                return '<span class="badge bg-' . $color . ' rounded-pill px-3">' . strtoupper($row->role) . '</span>';
            })
            ->addColumn('status_badge', function ($row) {
                return $row->is_approved
                    ? '<span class="badge bg-light text-success border border-success rounded-pill px-3"><i class="bi bi-check-circle-fill me-1"></i> Aktif</span>'
                    : '<span class="badge bg-light text-danger border border-danger rounded-pill px-3"><i class="bi bi-clock-history me-1"></i> Pending</span>';
            })
            ->addColumn('aksi', function ($row) {
                $btnApprove = '';
                // Jika Belum di-approve, munculkan tombol centang (Approve)
                if (!$row->is_approved) {
                    $btnApprove = '
                        <button class="btn btn-sm btn-success text-white btn-approve shadow-sm"
                            data-id="' . $row->id . '"
                            data-name="' . e($row->name) . '"
                            data-role="' . $row->role . '">
                            <i class="bi bi-check-lg"></i>
                        </button>';
                }

                return '
                <div class="d-flex justify-content-end gap-2">
                    ' . $btnApprove . '
                    <button class="btn btn-sm btn-warning text-white btn-edit shadow-sm"
                        data-id="' . $row->id . '" data-name="' . e($row->name) . '"
                        data-email="' . e($row->email) . '" data-nomor="' . e($row->nomor) . '"
                        data-role="' . e($row->role) . '">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger text-white btn-delete shadow-sm" data-id="' . $row->id . '">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['checkbox', 'role', 'status_badge', 'aksi'])
            ->make(true);
    }

    public function approve(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required',
            'kelas_id' => 'required_if:role,santri'
        ]);

        return DB::transaction(function () use ($request) {
            $user = User::findOrFail($request->user_id);

            // 2. Update Status User (PASTIKAN is_approved ada di $fillable Model User)
            $user->update(['is_approved' => true]);

            // 3. Logika Role: SANTRI
            if ($user->role === 'santri') {
                Santri::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nama' => $user->name,
                        'kelas_id' => $request->kelas_id // Diambil dari input modal approval
                    ]
                );
            }

            // 4. Logika Role: MUSYRIF (Tambahkan ini agar MATCH dengan logic update Mas)
            if ($user->role === 'musyrif') {
                $musyrif = Musyrif::updateOrCreate(
                    ['user_id' => $user->id],
                    ['nama' => $user->name]
                );

                // Generate kode hanya jika belum punya kode
                if (!$musyrif->kode) {
                    $musyrif->kode = $this->generateKode($musyrif->nama, $musyrif->id);
                    $musyrif->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Akun ' . $user->name . ' berhasil diaktifkan!'
            ]);
        });
    }

    public function bulkApprove(Request $request)
    {
        // 1. Validasi input
        $ids = $request->input('ids');
        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'Tidak ada user yang dipilih.'], 400);
        }

        // 2. Gunakan kolom is_approved sesuai database Mas
        // Kita set ke 1 (True) dan isi email_verified_at agar bisa login (jika perlu)
        $updated = User::whereIn('id', $ids)->update([
            'is_approved'       => 1,
            'email_verified_at' => now(), // Opsional: agar user dianggap verified
            'updated_at'        => now()
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => $updated . ' user berhasil disetujui dan diaktifkan.'
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (!$ids || !is_array($ids)) {
            return response()->json(['message' => 'Pilih data terlebih dahulu'], 400);
        }

        // Hapus masal
        User::whereIn('id', $ids)->delete();

        return response()->json([
            'status' => 'success',
            'message' => count($ids) . ' user berhasil dihapus permanen.'
        ]);
    }
}
