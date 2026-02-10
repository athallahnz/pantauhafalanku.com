<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Musyrif;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Models\MusyrifAttendance;
use Carbon\Carbon;


class MusyrifController extends Controller
{

    private function renderAttendanceCell($time, $status): string
    {
        if (!$time)
            return "<span class='text-muted'>—</span>";

        $t = Carbon::parse($time)->format('H:i');

        $status = $status ?: 'suspect';
        $badge = match ($status) {
            'valid' => 'bg-success',
            'suspect' => 'bg-warning text-dark',
            'rejected' => 'bg-danger',
            default => 'bg-secondary'
        };

        $label = strtoupper($status);
        return "<div class='fw-semibold'>{$t}</div><span class='badge {$badge}'>{$label}</span>";
    }

    public function index()
    {
        // Ambil kandidat user role musyrif yang belum terpakai, + yang sudah terpakai (untuk edit)
        $musyrifUserCandidates = User::query()
            ->where('role', 'musyrif')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.musyrif.index', compact('musyrifUserCandidates'));
    }

    public function data(Request $request)
    {
        if (!$request->ajax())
            abort(404);

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        // Subquery: absensi hari ini (morning/afternoon) -> 1 row per musyrif
        $todayAgg = MusyrifAttendance::query()
            ->selectRaw("
            musyrif_id,
            MAX(CASE WHEN type='morning' THEN attendance_at END) as morning_time,
            MAX(CASE WHEN type='morning' THEN status END) as morning_status,
            MAX(CASE WHEN type='afternoon' THEN attendance_at END) as afternoon_time,
            MAX(CASE WHEN type='afternoon' THEN status END) as afternoon_status
        ")
            ->whereDate('attendance_at', $today)
            ->groupBy('musyrif_id');

        // Subquery: rekap bulan ini (hitung valid)
        $monthAgg = MusyrifAttendance::query()
            ->selectRaw("
            musyrif_id,
            SUM(CASE WHEN type='morning' AND status='valid' THEN 1 ELSE 0 END) as valid_morning_month,
            SUM(CASE WHEN type='afternoon' AND status='valid' THEN 1 ELSE 0 END) as valid_afternoon_month
        ")
            ->whereBetween(DB::raw('DATE(attendance_at)'), [$monthStart, $monthEnd])
            ->groupBy('musyrif_id');

        $query = Musyrif::query()
            ->leftJoinSub($todayAgg, 'today_att', function ($join) {
                $join->on('musyrifs.id', '=', 'today_att.musyrif_id');
            })
            ->leftJoinSub($monthAgg, 'month_att', function ($join) {
                $join->on('musyrifs.id', '=', 'month_att.musyrif_id');
            })
            ->leftJoin('users', 'users.id', '=', 'musyrifs.user_id')
            ->select([
                'musyrifs.*',
                'users.name as akun_nama',
                'users.email as akun_email',

                'today_att.morning_time',
                'today_att.morning_status',
                'today_att.afternoon_time',
                'today_att.afternoon_status',

                'month_att.valid_morning_month',
                'month_att.valid_afternoon_month',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()

            ->addColumn('akun', function ($row) {
                if (!$row->akun_nama && !$row->akun_email)
                    return '-';
                $name = e($row->akun_nama ?? '-');
                $email = e($row->akun_email ?? '');
                return "<div class='fw-semibold'>{$name}</div><div class='text-muted small'>{$email}</div>";
            })

            ->addColumn('absen_pagi', function ($row) {
                return $this->renderAttendanceCell($row->morning_time, $row->morning_status);
            })
            ->addColumn('absen_sore', function ($row) {
                return $this->renderAttendanceCell($row->afternoon_time, $row->afternoon_status);
            })
            ->addColumn('rekap_bulan', function ($row) {
                $m = (int) ($row->valid_morning_month ?? 0);
                $a = (int) ($row->valid_afternoon_month ?? 0);
                return "<span class='badge bg-light text-dark border'>Pagi: {$m}</span> <span class='badge bg-light text-dark border'>Sore: {$a}</span>";
            })

            ->addColumn('aksi', function ($row) {
                $id = (int) $row->id;

                $btnAbsensi = "<a href='" . route('admin.musyrif.attendances', $id) . "' class='btn btn-sm btn-outline-primary'>Absensi</a>";

                $btnEdit = "<button class='btn btn-sm btn-warning text-white btnEdit' data-id='{$id}'>Edit</button>";
                $btnDelete = "<button class='btn btn-sm btn-danger text-white btnDelete' data-id='{$id}'>Hapus</button>";

                return "<div class='d-flex gap-1'>{$btnAbsensi}{$btnEdit}{$btnDelete}</div>";
            })

            ->rawColumns(['akun', 'absen_pagi', 'absen_sore', 'rekap_bulan', 'aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string'],

            // opsi 1: pilih user musyrif yang sudah ada
            'user_id' => ['nullable', 'integer', 'exists:users,id'],

            // opsi 2: buat akun baru (optional)
            'create_user' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        return DB::transaction(function () use ($request, $validated) {

            $userId = $validated['user_id'] ?? null;

            // Jika admin memilih "buat user baru"
            if ($request->boolean('create_user')) {
                // minimal field yang dibutuhkan
                $request->validate([
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', 'string', 'min:8'],
                ]);

                $user = User::create([
                    'name' => $validated['nama'],
                    'email' => $validated['email'],
                    'role' => 'musyrif',
                    'password' => Hash::make($validated['password']),
                ]);

                $userId = $user->id;
            } else {
                // Kalau memilih existing user_id, pastikan rolenya musyrif
                if ($userId) {
                    $u = User::find($userId);
                    if (!$u || $u->role !== 'musyrif') {
                        return response()->json([
                            'message' => 'User yang dipilih harus ber-role musyrif.'
                        ], 422);
                    }
                }
            }

            $m = Musyrif::create([
                'user_id' => $userId,
                'nama' => $validated['nama'],
                'kode' => $validated['kode'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            return response()->json([
                'message' => 'Musyrif berhasil ditambahkan.',
                'data' => $m,
            ]);
        });
    }

    public function show($id)
    {
        $musyrif = Musyrif::with('user')->findOrFail($id);

        return response()->json([
            'id' => $musyrif->id,
            'nama' => $musyrif->nama,
            'kode' => $musyrif->kode,
            'keterangan' => $musyrif->keterangan,
            'user_id' => $musyrif->user_id,
            'email' => optional($musyrif->user)->email,
        ]);
    }

    public function update(Request $request, $id)
    {
        $musyrif = Musyrif::findOrFail($id);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'kode' => ['nullable', 'string', 'max:50'],
            'keterangan' => ['nullable', 'string'],

            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // validasi role jika user_id diisi
        if (!empty($validated['user_id'])) {
            $u = User::find($validated['user_id']);
            if (!$u || $u->role !== 'musyrif') {
                return response()->json(['message' => 'User yang dipilih harus ber-role musyrif.'], 422);
            }
        }

        $musyrif->update([
            'user_id' => $validated['user_id'] ?? null,
            'nama' => $validated['nama'],
            'kode' => $validated['kode'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        // (opsional) sinkronkan nama user = nama musyrif
        if ($musyrif->user) {
            $musyrif->user->update(['name' => $validated['nama']]);
        }

        return response()->json(['message' => 'Musyrif berhasil diperbarui.']);
    }

    public function destroy(Request $request, $id)
    {
        $musyrif = Musyrif::findOrFail($id);
        $musyrif->delete();

        // catatan: akun user TIDAK dihapus otomatis (lebih aman)
        return response()->json(['message' => 'Musyrif berhasil dihapus.']);
    }

    public function attendances($id, Request $request)
    {
        $musyrif = Musyrif::with('user')->findOrFail($id);

        // Ambil bulan dari filter, default bulan ini (WIB)
        $month = $request->input('month', now('Asia/Jakarta')->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->startOfDay();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->endOfDay();

        // Query utama tabel (pagination) - tetap seperti Anda sekarang
        $q = MusyrifAttendance::query()
            ->where('musyrif_id', $musyrif->id)
            ->latest('attendance_at');

        if ($request->filled('month')) {
            $q->whereBetween('attendance_at', [$start, $end]);
        }
        if ($request->filled('type')) {
            $q->where('type', $request->type); // morning|afternoon
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status); // valid|suspect|rejected
        }

        $data = $q->paginate(20);

        // ========= DATA UNTUK CALENDAR (tanpa paginate, 1 record terakhir per hari-per sesi) =========
        $calQuery = MusyrifAttendance::query()
            ->where('musyrif_id', $musyrif->id)
            ->whereBetween('attendance_at', [$start, $end])
            ->orderByDesc('attendance_at');

        // Calendar mengikuti filter type/status juga (agar konsisten dengan filter)
        if ($request->filled('type')) {
            $calQuery->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $calQuery->where('status', $request->status);
        }

        $rows = $calQuery->get(['type', 'status', 'attendance_at']);

        // Bentuk: $calendar['2026-01-03']['morning'] = 'valid'
        $calendar = [];
        foreach ($rows as $r) {
            $day = $r->attendance_at->format('Y-m-d');
            $type = $r->type; // morning/afternoon

            // ambil yang terbaru saja per day+type (karena orderByDesc)
            if (!isset($calendar[$day][$type])) {
                $calendar[$day][$type] = $r->status;
            }
        }

        return view('admin.musyrif.attendances', compact('musyrif', 'data', 'month', 'start', 'end', 'calendar'));
    }

    public function updateAttendanceStatus(Request $request, MusyrifAttendance $attendance)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['valid', 'suspect', 'rejected'])],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        // Optional guard: pastikan record memang punya musyrif (aman)
        if (!$attendance->musyrif_id) {
            abort(404);
        }

        $oldStatus = $attendance->status;
        $newStatus = $validated['status'];

        // Simpan alasan ke notes (append log sederhana)
        $stamp = now()->format('Y-m-d H:i');
        $adminName = optional(auth()->user())->name ?? 'Admin';

        $logLine = "[{$stamp}] {$adminName}: {$oldStatus} -> {$newStatus} | Alasan: {$validated['reason']}";

        $attendance->status = $newStatus;
        $attendance->notes = trim(($attendance->notes ?? '') . "\n" . $logLine);
        $attendance->save();

        return back()->with('success', 'Status absensi berhasil diperbarui.');
    }
}
