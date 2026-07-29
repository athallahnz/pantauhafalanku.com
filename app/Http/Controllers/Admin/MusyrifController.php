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
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        $kelasParents = Kelas::query()
            ->induk()
            ->with([
                'children' => fn($query) => $query
                    ->aktif()
                    ->orderBy('urutan')
                    ->orderBy('kelompok'),
            ])
            ->orderBy('urutan')
            ->orderBy('nama_kelas')
            ->get();

        $listKelas = Kelas::query()
            ->with('parent:id,nama_kelas')
            ->operasional()
            ->urutHierarki()
            ->get();

        return view('admin.musyrif.index', compact(
            'musyrifUserCandidates',
            'kelasParents',
            'listKelas'
        ));
    }

    public function getByKelas(
        int $kelas_id
    ): JsonResponse {
        $kelas = Kelas::query()
            ->select([
                'id',
                'nama_kelas',
            ])
            ->find($kelas_id);

        if (!$kelas) {
            return response()->json([
                'status' => 'error',
                'message' =>
                'Kelas yang dipilih tidak ditemukan.',
                'data' => [],
            ], 404);
        }

        $musyrifs = Musyrif::query()
            ->forKelas((int) $kelas->id)
            ->with('kelasBinaan:id,nama_kelas')
            ->orderBy('nama')
            ->get([
                'id',
                'nama',
                'kode',
                'kelas_id',
            ])
            ->map(function (Musyrif $musyrif): Musyrif {
                $musyrif->setAttribute(
                    'kelas_ids',
                    $musyrif->allKelasIds()
                );

                return $musyrif;
            });

        return response()->json([
            'status' => $musyrifs->isEmpty()
                ? 'empty'
                : 'success',
            'message' => $musyrifs->isEmpty()
                ? "Belum ada musyrif yang bertugas di {$kelas->nama_kelas}."
                : 'Daftar musyrif berhasil dimuat.',
            'data' => $musyrifs->values(),
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
            ->with([
                'kelasInduk:id,nama_kelas,kode',
                'kelasBinaan' => fn($query) => $query
                    ->select([
                        'kelas.id',
                        'kelas.nama_kelas',
                        'kelas.parent_id',
                        'kelas.kelompok',
                    ])
                    ->orderBy('kelas.urutan')
                    ->orderBy('kelas.kelompok'),
            ])
            ->leftJoinSub($todayAgg, 'today_att', function ($join) {
                $join->on('musyrifs.id', '=', 'today_att.musyrif_id');
            })
            ->leftJoinSub($monthAgg, 'month_att', function ($join) {
                $join->on('musyrifs.id', '=', 'month_att.musyrif_id');
            })
            ->leftJoin('users', 'users.id', '=', 'musyrifs.user_id')
            ->leftJoin('kelas as kelas_utama', 'kelas_utama.id', '=', 'musyrifs.kelas_id')
            ->leftJoin('kelas as kelas_induk', 'kelas_induk.id', '=', 'musyrifs.kelas_induk_id')
            ->select([
                'musyrifs.*',
                'users.name as akun_nama',
                'users.email as akun_email',
                'users.nomor as akun_nomor',
                'kelas_utama.nama_kelas as nama_kelas',
                'kelas_utama.parent_id as kelas_parent_id',
                'kelas_utama.kelompok as kelas_kelompok',
                'kelas_induk.nama_kelas as kelas_induk_nama',

                'today_att.morning_time',
                'today_att.morning_status',
                'today_att.afternoon_time',
                'today_att.afternoon_status',

                'month_att.valid_morning_month',
                'month_att.valid_afternoon_month',
            ]);

        $jenisKelamin = $request->input('jenis_kelamin');

        if (in_array($jenisKelamin, ['L', 'P'], true)) {
            $query->where('musyrifs.jenis_kelamin', $jenisKelamin);
        }

        return DataTables::of($query)
            ->addIndexColumn()

            ->editColumn('nama', function ($row) {
                $badge = '';

                if ($row->jenis_kelamin === 'L') {
                    $badge = " <span class='badge bg-primary-subtle text-primary rounded-pill ms-1'>Putra</span>";
                } elseif ($row->jenis_kelamin === 'P') {
                    $badge = " <span class='badge bg-danger-subtle text-danger rounded-pill ms-1'>Putri</span>";
                }

                $nama = e($row->nama ?? '-');
                $kode = e($row->kode ?: '-');

                return "<div class='fw-semibold'>{$nama}{$badge}</div><div class='text-muted small'><i class='bi bi-hash'></i> {$kode}</div>";
            })

            ->addColumn('akun', function ($row) {
                if (!$row->akun_nama && !$row->akun_email && !$row->akun_nomor) {
                    return "<span class='badge bg-secondary-subtle text-secondary rounded-pill'>Belum ada akun</span>";
                }

                $name = e($row->akun_nama ?? '-');
                $email = e($row->akun_email ?? '-');
                $nomor = e($row->akun_nomor ?? '-');

                return <<<HTML
                    <div class="fw-semibold">{$name}</div>
                    <div class="text-muted small"><i class="bi bi-envelope me-1"></i>{$email}</div>
                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{$nomor}</div>
                HTML;
            })

            ->addColumn('kelas', function ($row) {
                $kelasBinaan = $row->kelasBinaan
                    ->pluck('nama_kelas')
                    ->filter()
                    ->unique()
                    ->values();

                if ($kelasBinaan->isEmpty() && $row->nama_kelas) {
                    $kelasBinaan = collect([$row->nama_kelas]);
                }

                if ($kelasBinaan->isEmpty()) {
                    return '<span class="text-body-secondary">Belum ditugaskan</span>';
                }

                $primary = e($row->nama_kelas ?: $kelasBinaan->first());
                $badges = $kelasBinaan
                    ->map(fn(string $nama) =>
                    '<span class="badge bg-info-subtle text-info rounded-pill me-1 mb-1">'
                        . e($nama)
                        . '</span>')
                    ->implode('');

                $tingkat = e($row->kelas_induk_nama ?: '-');

                return '<div class="small text-body-secondary">Tingkat: ' . $tingkat . '</div>'
                    . '<div class="fw-semibold mb-1">Operasional utama: ' . $primary . '</div>'
                    . '<div>' . $badges . '</div>';
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

            ->rawColumns(['nama', 'akun', 'kelas', 'absen_pagi', 'absen_sore', 'rekap_bulan', 'aksi'])
            ->make(true);
    }

    public function store(Request $request)
    {
        [$validated, $kelasIndukId, $primaryKelasId, $kelasIds] =
            $this->validatedMusyrifPayload($request);

        $createUser = $request->boolean('create_user');

        return DB::transaction(function () use (
            $request,
            $validated,
            $createUser,
            $kelasIndukId,
            $primaryKelasId,
            $kelasIds
        ) {
            $userId = null;

            if ($createUser) {
                $user = new User();
                $user->forceFill([
                    'name' => $validated['nama'],
                    'email' => $validated['email'],
                    'nomor' => $validated['nomor'],
                    'role' => 'musyrif',
                    'password' => Hash::make($validated['password']),
                ])->save();

                $userId = $user->id;
            }

            $musyrif = Musyrif::query()->create([
                'user_id' => $userId,
                'kelas_induk_id' => $kelasIndukId,
                'kelas_id' => $primaryKelasId,
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'kode' => $validated['kode'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'pendidikan_terakhir' => $validated['pendidikan_terakhir'] ?? null,
                'domisili' => $validated['domisili'] ?? null,
                'halaqah' => $validated['halaqah'] ?? null,
                'metode_alquran' => $validated['metode_alquran'] ?? null,
                'is_sertifikasi_ummi' => $request->boolean('is_sertifikasi_ummi'),
                'tahun_sertifikasi' => $validated['tahun_sertifikasi'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            /* Pivot adalah sumber utama seluruh kelas binaan. */
            $musyrif->kelasBinaan()->sync($kelasIds);

            return response()->json([
                'message' => $createUser
                    ? 'Musyrif dan akun login berhasil ditambahkan.'
                    : 'Musyrif berhasil ditambahkan tanpa akun login.',
            ]);
        });
    }

    public function update(Request $request, $id)
    {
        $musyrif = Musyrif::query()
            ->with(['user', 'kelasBinaan'])
            ->findOrFail($id);

        [$validated, $kelasIndukId, $primaryKelasId, $kelasIds] =
            $this->validatedMusyrifPayload($request, $musyrif);

        $createUser = $request->boolean('create_user');
        $existingUser = $musyrif->user;

        DB::transaction(function () use (
            $request,
            $validated,
            $musyrif,
            $existingUser,
            $createUser,
            $kelasIndukId,
            $primaryKelasId,
            $kelasIds
        ): void {
            $musyrif->update([
                'kelas_induk_id' => $kelasIndukId,
                'kelas_id' => $primaryKelasId,
                'nama' => $validated['nama'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
                'kode' => $validated['kode'] ?? null,
                'alamat' => $validated['alamat'] ?? null,
                'pendidikan_terakhir' => $validated['pendidikan_terakhir'] ?? null,
                'is_sertifikasi_ummi' => $request->boolean('is_sertifikasi_ummi'),
                'keterangan' => $validated['keterangan'] ?? null,
                'domisili' => $validated['domisili'] ?? null,
                'halaqah' => $validated['halaqah'] ?? null,
                'metode_alquran' => $validated['metode_alquran'] ?? null,
                'tahun_sertifikasi' => $validated['tahun_sertifikasi'] ?? null,
            ]);

            $musyrif->kelasBinaan()->sync($kelasIds);

            if ($existingUser) {
                $updateUser = ['name' => $validated['nama']];

                if ($createUser) {
                    $updateUser['email'] = $validated['email'];
                    $updateUser['nomor'] = $validated['nomor'];
                    $updateUser['role'] = 'musyrif';
                }

                if ($request->filled('password')) {
                    $updateUser['password'] = Hash::make($validated['password']);
                }

                $existingUser->forceFill($updateUser)->save();
                return;
            }

            if ($createUser) {
                $user = new User();
                $user->forceFill([
                    'name' => $validated['nama'],
                    'email' => $validated['email'],
                    'nomor' => $validated['nomor'],
                    'role' => 'musyrif',
                    'password' => Hash::make($validated['password']),
                ])->save();

                $musyrif->forceFill(['user_id' => $user->id])->save();
            }
        });

        return response()->json([
            'message' => $createUser
                ? 'Data musyrif dan akun login berhasil diperbarui.'
                : 'Data musyrif berhasil diperbarui.',
        ]);
    }

    /**
     * @return array{0:array<string,mixed>,1:?int,2:?int,3:array<int,int>}
     */
    private function validatedMusyrifPayload(
        Request $request,
        ?Musyrif $musyrif = null
    ): array {
        $createUser = $request->boolean('create_user');
        $existingUserId = $musyrif?->user_id;
        $isCreatingNewAccount = $createUser && !$existingUserId;
        $requestedKelasIds = collect($request->input('kelas_ids', []))
            ->filter()
            ->values();
        $hasAssignment = $request->filled('kelas_induk_id')
            || $request->filled('kelas_id')
            || $requestedKelasIds->isNotEmpty();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'kode' => ['nullable', 'string', 'max:50'],
            'kelas_induk_id' => [
                Rule::requiredIf($hasAssignment),
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
            'kelas_id' => [
                Rule::requiredIf($hasAssignment),
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
            'kelas_ids' => [
                Rule::requiredIf($hasAssignment),
                'nullable',
                'array',
                $hasAssignment ? 'min:1' : 'min:0',
            ],
            'kelas_ids.*' => ['integer', 'distinct', 'exists:kelas,id'],
            'alamat' => ['nullable', 'string'],
            'pendidikan_terakhir' => ['nullable', 'string'],
            'domisili' => ['nullable', 'string'],
            'halaqah' => ['nullable', 'string'],
            'metode_alquran' => ['nullable', 'string', 'max:255'],
            'tahun_sertifikasi' => ['nullable', 'integer', 'between:1900,' . (now()->year + 1)],
            'keterangan' => ['nullable', 'string'],
            'create_user' => ['nullable', 'boolean'],
            'email' => [
                Rule::requiredIf($createUser),
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($existingUserId),
            ],
            'nomor' => [
                Rule::requiredIf($createUser),
                'nullable',
                'string',
                'max:25',
                'regex:/^[0-9+()\-\s]{8,25}$/',
                Rule::unique('users', 'nomor')->ignore($existingUserId),
            ],
            'password' => [
                Rule::requiredIf($isCreatingNewAccount),
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ], $this->musyrifValidationMessages());

        if (!$hasAssignment) {
            return [$validated, null, null, []];
        }

        $kelasInduk = Kelas::query()
            ->induk()
            ->aktif()
            ->find((int) $validated['kelas_induk_id']);

        if (!$kelasInduk) {
            throw ValidationException::withMessages([
                'kelas_induk_id' => ['Tingkat utama harus berupa kelas induk aktif.'],
            ]);
        }

        $kelasIds = collect($validated['kelas_ids'] ?? [])
            ->push($validated['kelas_id'] ?? null)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $kelasBinaan = Kelas::query()
            ->with('parent:id,is_active')
            ->whereIn('id', $kelasIds)
            ->get()
            ->keyBy('id');

        if ($kelasBinaan->count() !== $kelasIds->count()) {
            throw ValidationException::withMessages([
                'kelas_ids' => ['Salah satu kelas binaan tidak ditemukan.'],
            ]);
        }

        foreach ($kelasBinaan as $kelas) {
            if (!$kelas->isOperational()) {
                throw ValidationException::withMessages([
                    'kelas_ids' => ["{$kelas->nama_kelas} bukan kelas operasional aktif."],
                ]);
            }

            if ((int) $kelas->parent_id !== (int) $kelasInduk->id) {
                throw ValidationException::withMessages([
                    'kelas_ids' => [
                        "{$kelas->nama_kelas} tidak berada di bawah tingkat utama {$kelasInduk->nama_kelas}.",
                    ],
                ]);
            }
        }

        $primaryKelasId = (int) $validated['kelas_id'];

        if (!$kelasIds->contains($primaryKelasId)) {
            throw ValidationException::withMessages([
                'kelas_id' => ['Kelas operasional utama wajib termasuk dalam kelas yang dibina.'],
            ]);
        }

        return [
            $validated,
            (int) $kelasInduk->id,
            $primaryKelasId,
            $kelasIds->all(),
        ];
    }

    private function musyrifValidationMessages(): array
    {
        return [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Pilihan jenis kelamin tidak valid.',
            'kelas_induk_id.required' => 'Tingkat utama wajib dipilih ketika Musyrif memiliki kelas binaan.',
            'kelas_id.required' => 'Kelas operasional utama wajib dipilih.',
            'kelas_ids.required' => 'Pilih minimal satu kelas yang dibina.',
            'kelas_ids.array' => 'Daftar kelas binaan harus berupa pilihan kelas.',
            'kelas_ids.min' => 'Pilih minimal satu kelas yang dibina.',
            'kelas_ids.*.distinct' => 'Kelas binaan tidak boleh dipilih dua kali.',
            'kelas_ids.*.exists' => 'Salah satu kelas binaan tidak ditemukan.',
            'email.required' => 'Email login wajib diisi saat akun login diaktifkan.',
            'email.email' => 'Format email login tidak valid.',
            'email.unique' => 'Email tersebut sudah digunakan akun lain.',
            'nomor.required' => 'Nomor HP/WhatsApp wajib diisi saat akun login diaktifkan.',
            'nomor.regex' => 'Format nomor HP tidak valid. Gunakan angka, awalan 08 atau +62.',
            'nomor.unique' => 'Nomor HP tersebut sudah digunakan akun lain.',
            'password.required' => 'Password wajib diisi untuk membuat akun login baru.',
            'password.min' => 'Password minimal harus terdiri dari 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'tahun_sertifikasi.between' => 'Tahun sertifikasi harus berada pada rentang yang valid.',
        ];
    }

    public function show($id)
    {
        $musyrif = Musyrif::with([
            'user',
            'kelasInduk:id,nama_kelas,kode',
            'kelas:id,nama_kelas,parent_id,kelompok',
            'kelasBinaan:id,nama_kelas,parent_id,kelompok',
        ])->find($id);

        if (!$musyrif) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json([
            'id' => $musyrif->id,
            'user_id' => $musyrif->user_id,
            'has_user' => (bool) $musyrif->user,
            'nama' => $musyrif->nama,
            'jenis_kelamin' => $musyrif->jenis_kelamin,
            'jenis_kelamin_label' => match ($musyrif->jenis_kelamin) {
                'L' => 'Putra / Laki-laki',
                'P' => 'Putri / Perempuan',
                default => '-',
            },
            'kode' => $musyrif->kode,
            'kelas_induk_id' => $musyrif->kelas_induk_id,
            'nama_kelas_induk' => $musyrif->kelasInduk?->nama_kelas ?? '-',
            'kelas_id' => $musyrif->kelas_id,
            'kelas_ids' => $musyrif->allKelasIds(),
            'nama_kelas' => $musyrif->kelas?->nama_kelas ?? '-',
            'nama_kelas_binaan' => $musyrif->kelasBinaan
                ->pluck('nama_kelas')
                ->filter()
                ->unique()
                ->values()
                ->implode(', ') ?: '-',
            'kelas_binaan' => $musyrif->kelasBinaan
                ->map(fn(Kelas $kelas) => [
                    'id' => (int) $kelas->id,
                    'nama_kelas' => (string) $kelas->nama_kelas,
                    'kelompok' => $kelas->kelompok,
                ])
                ->values(),
            'alamat' => $musyrif->alamat,
            'nomor' => $musyrif->user?->nomor,
            'email' => $musyrif->user?->email,
            'pendidikan_terakhir' => $musyrif->pendidikan_terakhir,
            'domisili' => $musyrif->domisili,
            'halaqah' => $musyrif->halaqah,
            'amanah_lain' => $musyrif->amanah_lain,
            'metode_alquran' => $musyrif->metode_alquran,
            'is_sertifikasi_ummi' => (bool) $musyrif->is_sertifikasi_ummi,
            'tahun_sertifikasi' => $musyrif->tahun_sertifikasi,
            'keterangan' => $musyrif->keterangan,
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
                    $locationName = e($row->address_text ?: 'Lokasi belum teridentifikasi');
                    $accuracy = $row->accuracy !== null
                        ? number_format((float) $row->accuracy, 0, ',', '.') . ' m'
                        : '-';

                    if ($row->latitude === null || $row->longitude === null) {
                        return "
                            <div class='fw-semibold'>{$locationName}</div>
                            <div class='text-warning small mt-1'>
                                <i class='bi bi-exclamation-triangle me-1'></i>GPS tidak tersedia
                            </div>
                        ";
                    }

                    $lat = (float) $row->latitude;
                    $lng = (float) $row->longitude;
                    $latlng = "{$lat},{$lng}";
                    $gmapsLink = "https://maps.google.com/?q={$latlng}";

                    return "
                        <div class='fw-semibold'>{$locationName}</div>
                        <div class='d-flex gap-2 align-items-center mt-1'>
                            <a href='{$gmapsLink}' target='_blank' class='text-decoration-none small fw-semibold'>
                                <i class='bi bi-geo-alt text-danger'></i> {$latlng}
                            </a>
                            <button class='btn btn-sm btn-outline-secondary py-0 px-2 btn-preview-map'
                                data-lat='{$row->latitude}' data-lng='{$row->longitude}' title='Preview di Maps'>
                                <i class='bi bi-map'></i>
                            </button>
                        </div>
                        <div class='text-muted mt-1' style='font-size: 11px;'>Akurasi GPS: {$accuracy}</div>
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


    /**
     * Download template resmi untuk import data musyrif.
     *
     * Template memiliki:
     * - Sheet "Data Musyrif" untuk data yang akan diimport.
     * - Sheet "Petunjuk" untuk penjelasan setiap kolom.
     * - Sheet "Referensi" tersembunyi untuk sumber dropdown Excel.
     */
    public function downloadImportTemplate()
    {
        $kelasList = Kelas::query()
            ->operasional()
            ->urutHierarki()
            ->pluck('nama_kelas')
            ->filter()
            ->values();

        $sampleKelas = $kelasList->first() ?: 'Kelas 7 A';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator(config('app.name', 'Sistem Hafalan Santri'))
            ->setTitle('Template Import Data Musyrif')
            ->setSubject('Template resmi import data musyrif')
            ->setDescription('Gunakan sheet Data Musyrif untuk proses import. Kolom pilihan sudah memakai dropdown untuk mengurangi salah ketik.');

        /* =============================================================
         * SHEET 1: DATA MUSYRIF
         * ============================================================= */
        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data Musyrif');

        $headers = [
            'nama',
            'jenis_kelamin',
            'kode',
            'kelas',
            'pendidikan_terakhir',
            'domisili',
            'halaqah',
            'alamat',
            'keterangan',
            'metode_alquran',
            'is_sertifikasi_ummi',
            'tahun_sertifikasi',
            'email',
            'password',
        ];

        $exampleRow = [
            'Contoh Musyrif (hapus baris ini)',
            'Laki-laki',
            'CONTOH',
            $sampleKelas,
            'S1',
            'Dalam Pondok (Mukim)',
            'Reguler',
            'Ponorogo',
            'Contoh baris. Hapus sebelum import data asli.',
            'Ummi',
            'Sudah',
            now()->year,
            'contoh.musyrif@example.com',
            'password123',
        ];

        $dataSheet->fromArray($headers, null, 'A1');
        $dataSheet->fromArray($exampleRow, null, 'A2');
        $dataSheet->freezePane('A3');
        $dataSheet->setAutoFilter('A1:N1');
        $dataSheet->getSheetView()->setZoomScale(85);

        $dataSheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '59359D'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9E3'],
                ],
            ],
        ]);

        $dataSheet->getStyle('A2:N2')->applyFromArray([
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '7A5A00'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF3CD'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'F1D28A'],
                ],
            ],
        ]);

        $dataSheet->getRowDimension(1)->setRowHeight(34);
        $dataSheet->getRowDimension(2)->setRowHeight(38);

        $columnWidths = [
            'A' => 34,
            'B' => 20,
            'C' => 16,
            'D' => 22,
            'E' => 22,
            'F' => 30,
            'G' => 18,
            'H' => 36,
            'I' => 42,
            'J' => 22,
            'K' => 24,
            'L' => 20,
            'M' => 32,
            'N' => 20,
        ];

        foreach ($columnWidths as $column => $width) {
            $dataSheet->getColumnDimension($column)->setWidth($width);
        }

        // Area input disiapkan hingga 500 baris.
        $dataSheet->getStyle('A2:N501')->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_HAIR,
                    'color' => ['rgb' => 'E6E6EE'],
                ],
            ],
        ]);

        // Pertahankan kode, email, dan password sebagai teks.
        $dataSheet->getStyle('C2:C501')->getNumberFormat()->setFormatCode('@');
        $dataSheet->getStyle('M2:N501')->getNumberFormat()->setFormatCode('@');
        $dataSheet->getStyle('L2:L501')->getNumberFormat()->setFormatCode('0');

        // Catatan pada header penting.
        $dataSheet->getComment('A1')->getText()->createTextRun('WAJIB. Nama lengkap musyrif.');
        $dataSheet->getComment('A2')->getText()->createTextRun('Ini baris contoh. Aman karena sistem akan melewati kode CONTOH, tetapi sebaiknya hapus sebelum import data asli.');
        $dataSheet->getComment('B1')->getText()->createTextRun('Pilih dari dropdown: Laki-laki atau Perempuan. Saat import akan otomatis dikonversi ke L/P sesuai database.');
        $dataSheet->getComment('D1')->getText()->createTextRun('Gunakan nama kelas dari dropdown agar sesuai dengan database.');
        $dataSheet->getComment('F1')->getText()->createTextRun('Pilih salah satu: Dalam Pondok (Mukim) atau Luar Pondok (Pulang-Pergi).');
        $dataSheet->getComment('K1')->getText()->createTextRun('Pilih Sudah atau Belum. Saat import akan otomatis dikonversi menjadi 1/0.');
        $dataSheet->getComment('N1')->getText()->createTextRun('Wajib minimal 8 karakter apabila kolom email diisi untuk membuat akun login baru.');

        /* =============================================================
         * SHEET 2: PETUNJUK
         * ============================================================= */
        $guideSheet = $spreadsheet->createSheet();
        $guideSheet->setTitle('Petunjuk');
        $guideSheet->mergeCells('A1:E1');
        $guideSheet->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE IMPORT MUSYRIF');
        $guideSheet->setCellValue('A3', 'Langkah Penggunaan');
        $guideSheet->setCellValue('A4', '1. Isi data hanya pada sheet "Data Musyrif".');
        $guideSheet->setCellValue('A5', '2. Baris ke-2 adalah contoh. Hapus baris contoh sebelum import data asli.');
        $guideSheet->setCellValue('A6', '3. Jangan mengubah nama header pada baris pertama.');
        $guideSheet->setCellValue('A7', '4. Gunakan dropdown pada kolom pilihan agar tidak salah ketik.');
        $guideSheet->setCellValue('A8', '5. Kolom email dan password hanya diisi jika musyrif perlu dibuatkan akun login.');

        $guideHeaders = ['Header', 'Wajib', 'Contoh', 'Pilihan/Format', 'Keterangan'];
        $guideSheet->fromArray($guideHeaders, null, 'A10');

        $guideRows = [
            ['nama', 'Ya', 'Ahmad Fauzan', 'Teks', 'Nama lengkap musyrif.'],
            ['jenis_kelamin', 'Ya', 'Laki-laki', 'Laki-laki / Perempuan', 'Dropdown. Sistem otomatis menyimpan ke database sebagai L atau P.'],
            ['kode', 'Tidak', 'MSY-001', 'Teks', 'Kode atau NIP internal musyrif.'],
            ['kelas', 'Tidak', $sampleKelas, 'Dropdown kelas kelompok aktif', 'Gunakan nama lengkap seperti Kelas 7 A; kelas induk tidak dapat dipakai untuk assignment baru.'],
            ['pendidikan_terakhir', 'Tidak', 'S1', 'SMA / D3 / S1 / S2', 'Pendidikan terakhir musyrif.'],
            ['domisili', 'Tidak', 'Dalam Pondok (Mukim)', 'Dalam Pondok (Mukim) / Luar Pondok (Pulang-Pergi)', 'Pilih salah satu nilai pada dropdown.'],
            ['halaqah', 'Tidak', 'Reguler', 'Reguler / Takhassus / Pengganti', 'Program halaqah musyrif.'],
            ['alamat', 'Tidak', 'Ponorogo', 'Teks', 'Alamat lengkap.'],
            ['keterangan', 'Tidak', 'Koordinator halaqah', 'Teks', 'Catatan tambahan.'],
            ['metode_alquran', 'Tidak', 'Ummi', 'Teks', 'Metode pembelajaran Al-Qur’an.'],
            ['is_sertifikasi_ummi', 'Tidak', 'Sudah', 'Sudah / Belum', 'Dropdown. Sistem otomatis menyimpan ke database sebagai 1 atau 0.'],
            ['tahun_sertifikasi', 'Tidak', (string) now()->year, '4 digit tahun', 'Contoh: 2025.'],
            ['email', 'Tidak', 'ahmad@example.com', 'Email valid', 'Isi jika akan dibuatkan atau dihubungkan dengan akun login.'],
            ['password', 'Kondisional', 'password123', 'Minimal 8 karakter', 'Wajib apabila email baru diisi untuk membuat akun login.'],
        ];

        $guideSheet->fromArray($guideRows, null, 'A11');
        $guideSheet->freezePane('A11');
        $guideSheet->getSheetView()->setZoomScale(90);

        $guideSheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 15],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '59359D']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $guideSheet->getRowDimension(1)->setRowHeight(30);

        $guideSheet->getStyle('A3:E3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '59359D']],
        ]);

        $guideSheet->getStyle('A10:E10')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9E3'],
                ],
            ],
        ]);

        $guideSheet->getStyle('A11:E24')->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'E6E6EE'],
                ],
            ],
        ]);

        $guideSheet->getColumnDimension('A')->setWidth(28);
        $guideSheet->getColumnDimension('B')->setWidth(15);
        $guideSheet->getColumnDimension('C')->setWidth(28);
        $guideSheet->getColumnDimension('D')->setWidth(42);
        $guideSheet->getColumnDimension('E')->setWidth(58);

        /* =============================================================
         * SHEET 3: REFERENSI DROPDOWN
         * Dibuat terlihat agar dropdown kelas lebih kompatibel di Excel/WPS.
         * ============================================================= */
        $referenceSheet = $spreadsheet->createSheet();
        $referenceSheet->setTitle('Referensi');

        $referenceSheet->fromArray(
            [['Daftar Kelas'], ...$kelasList->map(fn($nama) => [$nama])->all()],
            null,
            'A1'
        );

        $referenceSheet->fromArray([
            ['Pendidikan Terakhir'],
            ['SMA'],
            ['D3'],
            ['S1'],
            ['S2'],
        ], null, 'B1');

        $referenceSheet->fromArray([
            ['Domisili'],
            ['Dalam Pondok (Mukim)'],
            ['Luar Pondok (Pulang-Pergi)'],
        ], null, 'C1');

        $referenceSheet->fromArray([
            ['Program Halaqah'],
            ['Reguler'],
            ['Takhassus'],
            ['Pengganti'],
        ], null, 'D1');

        $referenceSheet->fromArray([
            ['Sertifikasi Ummi'],
            ['Sudah'],
            ['Belum'],
        ], null, 'E1');

        $referenceSheet->fromArray([
            ['Jenis Kelamin'],
            ['Laki-laki'],
            ['Perempuan'],
        ], null, 'F1');

        $kelasEndRow = max(2, $kelasList->count() + 1);

        // Dropdown dibuat lebih kompatibel untuk Excel/WPS:
        // - Semua pilihan memakai range di sheet Referensi, bukan inline list.
        // - setShowDropDown(true) mengikuti dokumentasi PhpSpreadsheet agar in-cell dropdown aktif.
        // - Sheet Referensi dibiarkan terlihat agar WPS lebih aman membaca sumber list.
        $applyDropdownValidation = function (string $column, string $formula1, string $prompt = 'Gunakan pilihan yang sudah disediakan.') use ($dataSheet): void {
            for ($row = 2; $row <= 501; $row++) {
                $validation = $dataSheet->getCell($column . $row)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Pilihan tidak valid');
                $validation->setError('Pilih nilai dari dropdown. Jangan mengetik nilai di luar daftar.');
                $validation->setPromptTitle('Pilih dari dropdown');
                $validation->setPrompt($prompt);
                $validation->setFormula1($formula1);
            }
        };

        $applyDropdownValidation(
            'B',
            '\'Referensi\'!$F$2:$F$3',
            'Pilih Laki-laki atau Perempuan. Sistem akan menyimpan ke database sebagai L atau P.'
        );

        if ($kelasList->isNotEmpty()) {
            $applyDropdownValidation(
                'D',
                '\'Referensi\'!$A$2:$A$' . $kelasEndRow,
                'Pilih kelas sesuai daftar kelas yang ada di database.'
            );
        }

        $applyDropdownValidation(
            'E',
            '\'Referensi\'!$B$2:$B$5',
            'Pilih pendidikan terakhir.'
        );

        $applyDropdownValidation(
            'F',
            '\'Referensi\'!$C$2:$C$3',
            'Pilih status domisili.'
        );

        $applyDropdownValidation(
            'G',
            '\'Referensi\'!$D$2:$D$4',
            'Pilih program halaqah.'
        );

        $applyDropdownValidation(
            'K',
            '\'Referensi\'!$E$2:$E$3',
            'Pilih Sudah jika sudah sertifikasi Ummi, atau Belum jika belum.'
        );

        $yearValidation = new DataValidation();
        $yearValidation->setType(DataValidation::TYPE_WHOLE);
        $yearValidation->setOperator(DataValidation::OPERATOR_BETWEEN);
        $yearValidation->setAllowBlank(true);
        $yearValidation->setShowErrorMessage(true);
        $yearValidation->setErrorTitle('Tahun tidak valid');
        $yearValidation->setError('Masukkan tahun 4 digit yang valid.');
        $yearValidation->setFormula1('1900');
        $yearValidation->setFormula2((string) (now()->year + 1));

        for ($row = 2; $row <= 501; $row++) {
            $dataSheet->getCell('L' . $row)->setDataValidation(clone $yearValidation);
        }

        $referenceSheet->freezePane('A2');
        $referenceSheet->getTabColor()->setRGB('F59E0B');
        foreach (range('A', 'F') as $column) {
            $referenceSheet->getColumnDimension($column)->setWidth(28);
        }
        $referenceSheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '59359D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $spreadsheet->setActiveSheetIndex(0);

        Storage::disk('local')->makeDirectory('temp');
        $relativePath = 'temp/template_import_musyrif_' . now()->format('Ymd_His_u') . '.xlsx';
        $absolutePath = storage_path('app/' . $relativePath);

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);
        $spreadsheet->disconnectWorksheets();

        return response()
            ->download(
                $absolutePath,
                'template_import_musyrif.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ]
            )
            ->deleteFileAfterSend(true);
    }

    private function normalizeImportHeader($value): string
    {
        $header = strtolower(trim((string) $value));
        $header = preg_replace('/[^a-z0-9]+/i', '_', $header);
        return trim((string) $header, '_');
    }

    private function getImportValue(array $row, array $headerMap, array $aliases): ?string
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeImportHeader($alias);
            if (!array_key_exists($key, $headerMap)) {
                continue;
            }

            $index = $headerMap[$key];
            $value = $row[$index] ?? null;

            if ($value === null) {
                return null;
            }

            $value = trim((string) $value);
            return $value === '' ? null : $value;
        }

        return null;
    }

    private function mapJenisKelaminForDatabase(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));
        $normalized = str_replace(['_', ' '], '-', $normalized);

        return match ($normalized) {
            'l', 'lk', 'laki', 'laki-laki', 'putra', 'male' => 'L',
            'p', 'pr', 'perempuan', 'putri', 'female' => 'P',
            default => null,
        };
    }

    private function mapSertifikasiUmmiForDatabase(?string $value): int
    {
        if ($value === null || trim($value) === '') {
            return 0;
        }

        $normalized = strtolower(trim($value));

        return in_array($normalized, ['1', 'ya', 'y', 'yes', 'sudah', 'sudah sertifikasi', 'true'], true)
            ? 1
            : 0;
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
            'file' => 'required|mimes:xlsx,xls,csv'
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

        if (!file_exists($path)) {
            return response()->json(['message' => 'File import sementara tidak ditemukan. Upload ulang file Excel.'], 404);
        }

        try {
            $allSheets = Excel::toArray([], $path);
            $rows = $allSheets[$sheetIndex] ?? [];

            if (count($rows) < 2) {
                return response()->json(['message' => 'Sheet kosong atau hanya berisi header.'], 422);
            }

            $rawHeaders = $rows[0] ?? [];
            $headerMap = [];

            foreach ($rawHeaders as $index => $header) {
                $normalized = $this->normalizeImportHeader($header);
                if ($normalized !== '') {
                    $headerMap[$normalized] = $index;
                }
            }

            if (!array_key_exists('nama', $headerMap)) {
                return response()->json(['message' => 'Header wajib "nama" tidak ditemukan pada sheet yang dipilih.'], 422);
            }

            $kelasCache = Kelas::query()
                ->operasional()
                ->get(['id', 'nama_kelas'])
                ->mapWithKeys(fn($kelas) => [strtolower(trim($kelas->nama_kelas)) => $kelas->id]);

            $inserted = 0;
            $skipped = 0;
            $errors = [];

            DB::beginTransaction();

            foreach (array_slice($rows, 1) as $rowIndex => $row) {
                $excelRowNumber = $rowIndex + 2;

                if (!is_array($row) || count(array_filter($row, fn($value) => $value !== null && trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $nama = $this->getImportValue($row, $headerMap, ['nama', 'nama_musyrif', 'nama musyrif']);
                $kode = $this->getImportValue($row, $headerMap, ['kode', 'nip', 'kode_nip', 'kode/nip']);

                // Lewati baris contoh bawaan template.
                if (
                    $nama === null ||
                    str_contains(strtolower($nama), 'contoh musyrif') ||
                    strtolower((string) $kode) === 'contoh'
                ) {
                    $skipped++;
                    continue;
                }

                $jenisKelaminRaw = $this->getImportValue($row, $headerMap, [
                    'jenis_kelamin',
                    'jenis kelamin',
                    'jk',
                    'gender',
                ]);
                $jenisKelamin = $this->mapJenisKelaminForDatabase($jenisKelaminRaw);

                if ($jenisKelaminRaw === null || $jenisKelamin === null) {
                    $errors[] = "Baris {$excelRowNumber}: jenis_kelamin wajib diisi Laki-laki atau Perempuan.";
                    $skipped++;
                    continue;
                }

                $kelasNama = $this->getImportValue($row, $headerMap, ['kelas', 'nama_kelas', 'nama kelas']);
                $kelasId = null;

                if ($kelasNama !== null) {
                    $kelasKey = strtolower(trim($kelasNama));
                    $kelasId = $kelasCache[$kelasKey] ?? null;

                    if ($kelasId === null) {
                        $errors[] = "Baris {$excelRowNumber}: kelas '{$kelasNama}' tidak ditemukan atau bukan kelompok aktif.";
                        $skipped++;
                        continue;
                    }
                }

                $pendidikan = $this->getImportValue($row, $headerMap, ['pendidikan_terakhir', 'pendidikan terakhir', 'pendidikan']);
                $domisili = $this->getImportValue($row, $headerMap, ['domisili']);
                $halaqah = $this->getImportValue($row, $headerMap, ['halaqah', 'program_halaqah', 'program halaqah']);
                $alamat = $this->getImportValue($row, $headerMap, ['alamat']);
                $keterangan = $this->getImportValue($row, $headerMap, ['keterangan', 'catatan']);
                $metodeAlquran = $this->getImportValue($row, $headerMap, ['metode_alquran', 'metode alquran', 'metode_al_quran', 'metode al-quran', 'metode']);
                $sertifikasiRaw = $this->getImportValue($row, $headerMap, ['is_sertifikasi_ummi', 'sertifikasi_ummi', 'sertifikasi ummi']);
                $tahunSertifikasiRaw = $this->getImportValue($row, $headerMap, ['tahun_sertifikasi', 'tahun sertifikasi']);
                $email = $this->getImportValue($row, $headerMap, ['email', 'email_login']);
                $password = $this->getImportValue($row, $headerMap, ['password', 'password_login']);

                $tahunSertifikasi = null;
                if ($tahunSertifikasiRaw !== null && is_numeric($tahunSertifikasiRaw)) {
                    $tahunSertifikasi = (int) $tahunSertifikasiRaw;
                }

                $userId = null;
                if ($email !== null) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Baris {$excelRowNumber}: email '{$email}' tidak valid.";
                        $skipped++;
                        continue;
                    }

                    $user = User::query()->where('email', $email)->first();

                    if (!$user) {
                        if ($password === null || strlen($password) < 8) {
                            $errors[] = "Baris {$excelRowNumber}: password minimal 8 karakter karena email baru diisi.";
                            $skipped++;
                            continue;
                        }

                        $user = User::create([
                            'name' => $nama,
                            'email' => $email,
                            'role' => 'musyrif',
                            'password' => Hash::make($password),
                        ]);
                    } else {
                        $updateUser = [
                            'name' => $nama,
                        ];

                        if ($user->role !== 'musyrif') {
                            $updateUser['role'] = 'musyrif';
                        }

                        if ($password !== null && strlen($password) >= 8) {
                            $updateUser['password'] = Hash::make($password);
                        }

                        $user->update($updateUser);
                    }

                    $userId = $user->id;
                }

                Musyrif::create([
                    'user_id' => $userId,
                    'kelas_id' => $kelasId,
                    'nama' => $nama,
                    'jenis_kelamin' => $jenisKelamin,
                    'kode' => $kode,
                    'alamat' => $alamat,
                    'pendidikan_terakhir' => $pendidikan,
                    'domisili' => $domisili,
                    'halaqah' => $halaqah,
                    'keterangan' => $keterangan,
                    'metode_alquran' => $metodeAlquran,
                    'is_sertifikasi_ummi' => $this->mapSertifikasiUmmiForDatabase($sertifikasiRaw),
                    'tahun_sertifikasi' => $tahunSertifikasi,
                ]);

                $inserted++;
            }

            DB::commit();

            if (Storage::exists($request->temp_path)) {
                Storage::delete($request->temp_path);
            }

            $message = "Import selesai. Berhasil: {$inserted}, dilewati: {$skipped}.";
            if (!empty($errors)) {
                $message .= ' Beberapa baris dilewati: ' . implode(' | ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $message .= ' dan ' . (count($errors) - 5) . ' error lainnya.';
                }
            }

            return response()->json([
                'message' => $message,
                'inserted' => $inserted,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal import: ' . $e->getMessage()], 500);
        }
    }
}
