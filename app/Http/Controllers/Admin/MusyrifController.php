<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\MusyrifImport;
use App\Models\Musyrif;
use App\Models\User;
use App\Models\Kelas;
use App\Models\MusyrifAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Storage;

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
        $musyrifUserCandidates = User::query()
            ->where('role', 'musyrif')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        // TAMBAHKAN INI: Ambil data kelas untuk dropdown
        $listKelas = Kelas::orderBy('nama_kelas')->get();

        return view('admin.musyrif.index', compact('musyrifUserCandidates', 'listKelas'));
    }

    public function getByKelas($kelas_id)
    {
        $musyrif = Musyrif::where('kelas_id', $kelas_id)->first();
        return response()->json([
            'id' => $musyrif ? $musyrif->id : '',
            'nama' => $musyrif ? $musyrif->nama : 'Belum ada Musyrif'
        ]);
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
            ->leftJoin('kelas', 'kelas.id', '=', 'musyrifs.kelas_id')
            ->select([
                'musyrifs.*',
                'users.name as akun_nama',
                'users.email as akun_email',
                'kelas.nama_kelas as nama_kelas',

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

            ->addColumn('kelas', function ($row) {
                return $row->nama_kelas ? e($row->nama_kelas) : '-';
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
                $kelas_id = $row->kelas_id; // Ambil kelas_id dari row

                $btnAbsensi = "<a href='" . route('admin.musyrif.attendances', $id) . "' class='btn btn-sm btn-outline-primary' data-toggle='tooltip' title='Lihat Absensi'><i class='bi bi-calendar'></i></a>";
                $btnDetail = "<button class='btn btn-sm btn-info text-white btnDetail' data-id='{$id}' data-kelas_id='{$kelas_id}' data-toggle='tooltip' title='Detail Profil'><i class='bi bi-eye'></i></button>";

                // TAMBAHKAN data-kelas_id DI SINI
                $btnEdit = "<button class='btn btn-sm btn-warning text-white btnEdit'
                data-id='{$id}'
                data-kelas_id='{$kelas_id}'
                data-toggle='tooltip' title='Edit'><i class='bi bi-pencil'></i></button>";

                $btnDelete = "<button class='btn btn-sm btn-danger text-white btnDelete' data-id='{$id}' data-toggle='tooltip' title='Hapus'><i class='bi bi-trash'></i></button>";

                return "<div class='d-flex gap-1'>{$btnAbsensi}{$btnDetail}{$btnEdit}{$btnDelete}</div>";
            })

            ->rawColumns(['akun', 'absen_pagi', 'absen_sore', 'rekap_bulan', 'aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'nama'                => ['required', 'string', 'max:150'],
            'kode'                => ['nullable', 'string', 'max:50'],
            'kelas_id'            => ['nullable', 'exists:kelas,id'],
            'alamat'              => ['nullable', 'string'],
            'pendidikan_terakhir' => ['nullable', 'string'],
            'domisili'            => ['nullable', 'string'],
            'halaqah'             => ['nullable', 'string'],
            'metode_alquran'      => ['nullable', 'string'],
            'tahun_sertifikasi'   => ['nullable', 'integer'],
            'keterangan'          => ['nullable', 'string'],
            'email'               => ['nullable', 'required_if:create_user,true', 'email', 'unique:users,email'],
            'password'            => ['nullable', 'required_if:create_user,true', 'string', 'min:8'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $userId = null;

            // 2. Logika Pembuatan User Otomatis
            if ($request->boolean('create_user')) {
                $user = User::create([
                    'name'     => $validated['nama'],
                    'email'    => $validated['email'],
                    'role'     => 'musyrif',
                    'password' => Hash::make($validated['password']),
                ]);
                $userId = $user->id;
            }

            // 3. Simpan Data Musyrif
            Musyrif::create([
                'user_id'             => $userId,
                'kelas_id'            => $validated['kelas_id'],
                'nama'                => $validated['nama'],
                'kode'                => $validated['kode'],
                'alamat'              => $validated['alamat'],
                'pendidikan_terakhir' => $validated['pendidikan_terakhir'],
                'domisili'            => $validated['domisili'],
                'halaqah'             => $validated['halaqah'],
                'metode_alquran'      => $validated['metode_alquran'],
                'is_sertifikasi_ummi' => $request->boolean('is_sertifikasi_ummi'), // Aman & Clean
                'tahun_sertifikasi'   => $validated['tahun_sertifikasi'],
                'keterangan'          => $request->input('keterangan'), // Solusi Error Undefined Key
            ]);

            return response()->json(['message' => 'Musyrif berhasil ditambahkan.']);
        });
    }

    public function update(Request $request, $id)
    {
        $musyrif = Musyrif::findOrFail($id);

        $validated = $request->validate([
            'nama'                => ['required', 'string', 'max:150'],
            'kode'                => ['nullable', 'string', 'max:50'],
            'kelas_id'            => ['nullable', 'exists:kelas,id'],
            'alamat'              => ['nullable', 'string'],
            'pendidikan_terakhir' => ['nullable', 'string'],
            'domisili'            => ['nullable', 'string'],
            'halaqah'             => ['nullable', 'string'],
            'metode_alquran'      => ['nullable', 'string'],
            'tahun_sertifikasi'   => ['nullable', 'integer'],
            'keterangan'          => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $validated, $musyrif) {
            // 1. Update Musyrif
            $musyrif->update([
                'kelas_id'            => $validated['kelas_id'],
                'nama'                => $validated['nama'],
                'kode'                => $validated['kode'],
                'alamat'              => $request->input('alamat'),
                'pendidikan_terakhir' => $request->input('pendidikan_terakhir'),
                'is_sertifikasi_ummi' => $request->boolean('is_sertifikasi_ummi'),
                'keterangan'          => $request->input('keterangan'),
                'domisili'            => $request->input('domisili'),
                'halaqah'             => $request->input('halaqah'),
                'metode_alquran'      => $request->input('metode_alquran'),
                'tahun_sertifikasi'   => $request->input('tahun_sertifikasi'),
            ]);

            // 2. Update Akun User Jika Ada
            if ($musyrif->user) {
                $updateUser = [
                    'name'  => $validated['nama'],
                    'email' => $request->input('email', $musyrif->user->email),
                ];

                if ($request->filled('password')) {
                    $updateUser['password'] = Hash::make($request->password);
                }

                $musyrif->user->update($updateUser);
            }
        });

        return response()->json(['message' => 'Data musyrif diperbarui!']);
    }

    public function show($id)
    {
        // Gunakan Eloquent with() agar relasi 'user' dan 'kelas' terload otomatis
        $musyrif = Musyrif::with(['user', 'kelas'])->find($id);

        if (!$musyrif) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'id'                => $musyrif->id,
            'nama'              => $musyrif->nama,
            'kode'              => $musyrif->kode ?? '-',
            'kelas_id'          => $musyrif->kelas_id, // Penting untuk Edit Mode
            'nama_kelas'        => $musyrif->kelas->nama_kelas ?? '-', // Untuk Detail Mode
            'alamat'            => $musyrif->alamat ?? '-',
            'nomor'             => $musyrif->user->nomor ?? '-',
            'email'             => $musyrif->user->email ?? '-',
            'pendidikan_terakhir' => $musyrif->pendidikan_terakhir ?? '-',
            'domisili'          => $musyrif->domisili ?? '-',
            'halaqah'           => $musyrif->halaqah ?? '-',
            'amanah_lain'       => $musyrif->amanah_lain ?? '-',
            'metode_alquran'    => $musyrif->metode_alquran ?? '-',
            'is_sertifikasi_ummi' => $musyrif->is_sertifikasi_ummi,
            'tahun_sertifikasi' => $musyrif->tahun_sertifikasi ?? '-',
            'keterangan'        => $musyrif->keterangan ?? '-',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $musyrif = Musyrif::findOrFail($id);
        $musyrif->delete();

        // catatan: akun user TIDAK dihapus otomatis (lebih aman)
        return response()->json(['message' => 'Musyrif berhasil dihapus.']);
    }

    // ==========================================
    // MANAJEMEN ABSENSI KESELURUHAN (ALL MUSYRIF)
    // ==========================================

    public function allAttendances(Request $request)
    {
        // 1. Jika request berupa AJAX dari DataTables
        if ($request->ajax()) {
            $q = MusyrifAttendance::with('musyrif')->select('musyrif_attendances.*');

            // Filter
            if ($request->filled('date')) {
                $q->whereDate('attendance_at', $request->date);
            }
            if ($request->filled('musyrif_id')) {
                $q->where('musyrif_id', $request->musyrif_id);
            }
            if ($request->filled('type')) {
                $q->where('type', $request->type);
            }
            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }

            return \Yajra\DataTables\Facades\DataTables::of($q)
                ->addColumn('waktu', function ($row) {
                    $date = $row->attendance_at->format('d M Y');
                    $time = $row->attendance_at->format('H:i');
                    return "<div class='fw-bold'>{$date}</div><div class='text-muted small'>{$time} WIB</div>";
                })
                ->addColumn('musyrif_info', function ($row) {
                    $nama = $row->musyrif?->nama ?? 'Tidak Diketahui';
                    $kode = $row->musyrif?->kode ?? '-';
                    return "<div class='fw-semibold text-adaptive-purple'>{$nama}</div><div class='text-muted small'><i class='bi bi-hash'></i> {$kode}</div>";
                })
                ->addColumn('sesi_status', function ($row) {
                    $sesi = $row->type === 'morning' ? 'Pagi' : 'Malam';
                    $badge = match ($row->status) {
                        'valid' => 'bg-success',
                        'suspect' => 'bg-warning text-dark',
                        'rejected' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                    $status = strtoupper($row->status);
                    return "<div class='mb-1'>{$sesi}</div><span class='badge {$badge} px-3 py-1 rounded-pill' style='font-size: 0.7rem;'>{$status}</span>";
                })
                ->addColumn('lokasi', function ($row) {
                    $latlng = "{$row->latitude},{$row->longitude}";
                    $gmapsLink = "https://maps.google.com/?q={$latlng}";
                    return "
                        <div class='d-flex gap-2 align-items-center mt-1'>
                            <a href='{$gmapsLink}' target='_blank' class='text-decoration-none small fw-semibold'>
                                <i class='bi bi-geo-alt text-danger'></i> {$latlng}
                            </a>
                            <button class='btn btn-sm btn-outline-secondary py-0 px-2 btn-preview-map'
                                data-lat='{$row->latitude}' data-lng='{$row->longitude}' title='Preview di Maps'>
                                <i class='bi bi-map'></i>
                            </button>
                        </div>
                    ";
                })
                ->addColumn('foto', function ($row) {
                    if ($row->photo_path) {
                        $url = asset('storage/' . $row->photo_path);
                        return "<button class='btn btn-sm btn-outline-primary rounded-pill btnPreview' data-photo='{$url}'><i class='bi bi-image'></i></button>";
                    }
                    return "<span class='text-muted small'>-</span>";
                })
                ->addColumn('aksi', function ($row) {
                    return "
                        <div class='d-flex justify-content-end gap-2'>
                            <div class='btn-group shadow-sm rounded-pill overflow-hidden'>
                                <button class='btn btn-sm btn-success text-white btnUpdateStatus' data-id='{$row->id}' data-status='valid' data-current='{$row->status}' title='Validasi'><i class='bi bi-check-lg'></i></button>
                                <button class='btn btn-sm btn-warning text-white btnUpdateStatus' data-id='{$row->id}' data-status='suspect' data-current='{$row->status}' title='Tandai Mencurigakan'><i class='bi bi-exclamation-triangle'></i></button>
                                <button class='btn btn-sm btn-danger text-white btnUpdateStatus' data-id='{$row->id}' data-status='rejected' data-current='{$row->status}' title='Tolak Absen'><i class='bi bi-x-lg'></i></button>
                            </div>
                            <button class='btn btn-sm btn-outline-danger shadow-sm rounded-circle btnDelete' data-id='{$row->id}' title='Hapus Data'><i class='bi bi-trash3-fill'></i></button>
                        </div>
                    ";
                })
                ->rawColumns(['waktu', 'musyrif_info', 'sesi_status', 'lokasi', 'foto', 'aksi'])
                ->make(true);
        }

        // 2. Load View pertama kali (tanpa query data berat)
        $musyrifs = Musyrif::select('id', 'nama', 'kode')->orderBy('nama')->get();
        return view('admin.musyrif.absensi.index', compact('musyrifs'));
    }

    public function destroyAttendance(MusyrifAttendance $attendance)
    {
        // Hapus file foto dari storage
        if ($attendance->photo_path && Storage::disk('public')->exists($attendance->photo_path)) {
            Storage::disk('public')->delete($attendance->photo_path);
        }

        $attendance->delete();

        return back()->with('success', 'Data absensi berhasil dihapus permanen.');
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

        return view('admin.musyrif.absensi.attendances', compact('musyrif', 'data', 'month', 'start', 'end', 'calendar'));
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

    public function importExcel(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(new MusyrifImport, $request->file('file_excel'));
            return response()->json(['message' => 'Seluruh data musyrif berhasil diimport!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal import: ' . $e->getMessage()], 500);
        }
    }

    public function getSheetPreview(Request $request)
    {
        $request->validate([
            'temp_path' => 'required',
            'sheet_index' => 'required|integer'
        ]);

        $path = storage_path('app/' . $request->temp_path);
        $sheetIndex = (int) $request->sheet_index;

        // Baca data hanya pada sheet yang dipilih
        $spreadsheet = Excel::toCollection(collect(), $path);

        // Ambil sheet berdasarkan index
        $sheetData = $spreadsheet->get($sheetIndex);

        if ($sheetData->isEmpty()) {
            return response()->json(['headers' => [], 'preview' => []]);
        }

        // Ambil header (baris pertama) dan data (5 baris berikutnya)
        $headers = $sheetData->first()->keys()->toArray();
        $preview = $sheetData->take(6); // Mengambil 6 baris (1 header + 5 data)

        return response()->json([
            'headers' => $headers,
            'preview' => $preview
        ]);
    }

    public function previewImport(Request $request)
    {
        // Sesuaikan 'file' dengan name="file" di HTML
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        try {
            $file = $request->file('file');
            // Simpan sementara
            $path = $file->storeAs('temp', 'import_musyrif_' . time() . '.xlsx');

            // Ambil daftar sheet dan info jumlah baris
            $reader = \Maatwebsite\Excel\Facades\Excel::toCollection(collect(), $file);
            $sheets = [];

            foreach ($reader as $index => $content) {
                $sheets[] = [
                    'name' => $reader->keys()[$index],
                    'rows' => $content->count(),
                    'index' => $index
                ];
            }

            return response()->json([
                'sheets' => $reader->keys(), // Untuk dropdown/list
                'temp_path' => $path,
                'sheet_info' => $sheets // Opsional jika ingin detail baris
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membaca file: ' . $e->getMessage()], 500);
        }
    }

    public function executeImport(Request $request)
    {
        $request->validate([
            'temp_path' => 'required',
            'sheet_index' => 'required|integer'
        ]);

        $path = storage_path('app/' . $request->temp_path);
        $sheetIndex = (int) $request->sheet_index;

        try {
            // Kita bungkus import-nya agar hanya menjalankan sheet yang dipilih
            Excel::import(
                new class($sheetIndex) implements \Maatwebsite\Excel\Concerns\WithMultipleSheets {
                    private $sheetIndex;
                    public function __construct($index)
                    {
                        $this->sheetIndex = $index;
                    }

                    public function sheets(): array
                    {
                        return [
                            $this->sheetIndex => new \App\Imports\MusyrifImport()
                        ];
                    }
                },
                $path
            );

            // Hapus file temp setelah berhasil
            if (Storage::exists($request->temp_path)) {
                Storage::delete($request->temp_path);
            }

            return response()->json(['message' => 'Data musyrif dari sheet tersebut berhasil diimport!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal import: ' . $e->getMessage()], 500);
        }
    }
}
