<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\User;
use App\Models\PelanggaranPoint;
use App\Exports\ViolationReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;


class SantriController extends Controller
{
    public function index()
    {
        $kelasParents = Kelas::query()
            ->induk()
            ->with([
                'children' => fn ($query) => $query
                    ->aktif()
                    ->orderBy('urutan')
                    ->orderBy('kelompok'),
            ])
            ->orderBy('urutan')
            ->orderBy('nama_kelas')
            ->get();

        $kelasList = Kelas::query()
            ->with('parent:id,nama_kelas')
            ->operasional()
            ->urutHierarki()
            ->get();

        $musyrifList = Musyrif::query()
            ->withCount([
                'santris as santris_count' => fn ($query) => $query->active(),
            ])
            ->orderBy('nama')
            ->get();

        return view('admin.santri.index', compact(
            'kelasParents',
            'kelasList',
            'musyrifList'
        ));
    }

    public function getData(Request $request)
    {
        if (!$request->ajax()) abort(404);

        $query = Santri::leftJoin('users', 'users.id', '=', 'santris.user_id')
            ->leftJoin('kelas', 'kelas.id', '=', 'santris.kelas_id')
            ->leftJoin('kelas as kelas_induk', 'kelas_induk.id', '=', 'kelas.parent_id')
            ->leftJoin('musyrifs', 'musyrifs.id', '=', 'santris.musyrif_id')
            ->select([
                'santris.id',
                'santris.nis',
                'santris.nama',
                'santris.kelas_id',
                'santris.musyrif_id',
                'santris.tanggal_lahir',
                'santris.jenis_kelamin',
                'santris.status',
                'santris.graduated_semester_id',
                'santris.graduated_at',
                'kelas.nama_kelas as kelas_nama',
                'kelas.parent_id as kelas_parent_id',
                'kelas.kelompok as kelas_kelompok',
                'kelas_induk.nama_kelas as kelas_induk_nama',
                'musyrifs.nama as musyrif_nama',
                'users.id as user_id',
                'users.name as user_name',
                'users.nomor as user_nomor',
                'users.email as user_email',
            ]);

        $status = $request->input(
            'status',
            Santri::STATUS_AKTIF
        );

        if (
            in_array(
                $status,
                [
                    Santri::STATUS_AKTIF,
                    Santri::STATUS_LULUS,
                    Santri::STATUS_KELUAR,
                    Santri::STATUS_NONAKTIF,
                ],
                true
            )
        ) {
            $query->where(
                'santris.status',
                $status
            );
        } else {
            $query->where(
                'santris.status',
                Santri::STATUS_AKTIF
            );
        }

        if ($request->filled('kelas_id')) {
            $filterKelas = Kelas::query()->find((int) $request->kelas_id);

            if ($filterKelas?->isInduk()) {
                $query->where(function ($kelasQuery) use ($filterKelas): void {
                    $kelasQuery
                        ->where('santris.kelas_id', $filterKelas->id)
                        ->orWhere('kelas.parent_id', $filterKelas->id);
                });
            } elseif ($filterKelas) {
                $query->where('santris.kelas_id', $filterKelas->id);
            }
        }

        $jenisKelamin = $request->input('jenis_kelamin');

        if (in_array($jenisKelamin, ['L', 'P'], true)) {
            $query->where('santris.jenis_kelamin', $jenisKelamin);
        }
        
        return DataTables::of($query)
            ->addIndexColumn()

            // Hanya kolom utama ditampilkan
            ->addColumn('nis', fn($row) => $row->nis ?: '-')
            ->addColumn('nama', fn($row) => $row->nama)
            ->addColumn('akun', function ($row) {
                if (!$row->user_name && !$row->user_email && !$row->user_nomor)
                    return '-';
                $name = e($row->user_name ?? '-');
                $nomor = e($row->user_nomor ?? '');
                $email = e($row->user_email ?? '');
                $contact = $nomor ?: $email;
                return "<div class='fw-semibold'>{$name}</div><div class='text-muted small'>{$contact}</div>";
            })
            ->addColumn('kelas', function ($row) {
                if (!$row->kelas_nama) {
                    return '<span class="text-body-secondary">-</span>';
                }

                if ($row->kelas_parent_id) {
                    $group = e($row->kelas_kelompok ?: '-');
                    return '<div class="fw-semibold">' . e($row->kelas_nama) . '</div>'
                        . '<span class="badge bg-info-subtle text-info rounded-pill">Kelompok ' . $group . '</span>';
                }

                return '<div class="fw-semibold">' . e($row->kelas_nama) . '</div>'
                    . '<span class="badge bg-warning-subtle text-warning rounded-pill">Legacy Induk</span>';
            })
            ->addColumn('musyrif', fn($row) => $row->musyrif_nama ?: '-')

            // Tombol aksi lengkap
            ->addColumn('aksi', function ($row) {
                $tgl = '';
                if (!empty($row->tanggal_lahir)) {
                    try {
                        $tgl = \Carbon\Carbon::parse($row->tanggal_lahir)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $tgl = (string) $row->tanggal_lahir;
                    }
                }

                $btnDetail = '
                <button class="btn btn-sm btn-outline-info btn-detail"
                    data-id="' . $row->id . '"
                    data-nama="' . e($row->nama) . '"
                    data-nis="' . e($row->nis) . '"
                    data-tanggal_lahir="' . e($tgl) . '"
                    data-jenis_kelamin="' . e($row->jenis_kelamin) . '"
                    data-kelas="' . e($row->kelas_nama) . '"
                    data-musyrif="' . e($row->musyrif_nama) . '"
                    data-user-id="' . ($row->user_id ?? '') . '"
                    data-user-name="' . ($row->user_name ?? '') . '"
                    data-user-nomor="' . ($row->user_nomor ?? '') . '"
                    data-user-email="' . ($row->user_email ?? '') . '"
                ><i class="bi bi-eye" data-toggle="tooltip" data-placement="top" title="Lihat Detail"></i></button>';

                $btnProgress = '
                <a class="btn btn-sm btn-outline-success"
                    href="' . route('admin.santri.master.progress.show', $row->id) . '"
                    data-coreui-toggle="tooltip" title="Lihat Progress Hafalan, Tahsin, dan Tilawah">
                    <i class="bi bi-graph-up-arrow"></i>
                </a>';

                $btnUser = '
                <button class="btn btn-sm btn-outline-primary btn-user"
                    data-id="' . $row->id . '"
                    data-nama="' . e($row->nama) . '"
                    data-user-id="' . ($row->user_id ?? '') . '"
                    data-user-name="' . ($row->user_name ?? '') . '"
                    data-user-nomor="' . ($row->user_nomor ?? '') . '"
                    data-user-email="' . ($row->user_email ?? '') . '"
                ><i class="bi bi-person-plus" data-toggle="tooltip" data-placement="top" title="Buat User"></i></button>';

                $btnEdit = '
                <button class="btn btn-sm btn-warning btn-edit text-white"
                    data-id="' . $row->id . '"
                    data-nama="' . e($row->nama) . '"
                    data-nis="' . e($row->nis) . '"
                    data-kelas_id="' . e($row->kelas_id) . '"
                    data-kelas_legacy="' . ($row->kelas_parent_id ? '0' : '1') . '"
                    data-musyrif_id="' . $row->musyrif_id . '"
                    data-tanggal_lahir="' . e($tgl) . '"
                    data-jenis_kelamin="' . e($row->jenis_kelamin) . '"
                    data-toggle="tooltip" title="Edit">
                    <i class="bi bi-pencil"></i>
                </button>';

                $btnDelete = '
                <button class="btn btn-sm btn-danger btn-delete text-white"
                    data-id="' . $row->id . '"
                ><i class="bi bi-trash" data-toggle="tooltip" data-placement="top" title="Hapus"></i></button>';

                return '<div class="d-flex flex-nowrap gap-1">' . $btnDetail . $btnProgress . $btnUser . $btnEdit . $btnDelete . '</div>';
            })
            ->rawColumns(['aksi', 'akun', 'kelas'])
            ->make(true);
    }

    public function getByKelas($kelas_id)
    {
        $kelas = Kelas::query()
            ->with('parent:id,nama_kelas,is_active')
            ->find($kelas_id);

        if (!$kelas) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kelas tidak ditemukan.',
                'data' => [],
            ], 404);
        }

        $kelasIndukId = (int) ($kelas->parent_id ?: $kelas->id);
        $tingkatUtama = $kelas->parent?->nama_kelas ?: $kelas->nama_kelas;

        $musyrifs = Musyrif::query()
            ->forKelasInduk($kelasIndukId)
            ->with([
                'kelas:id,nama_kelas',
                'kelasBinaan:id,nama_kelas,parent_id,kelompok',
            ])
            ->select([
                'id',
                'nama',
                'kode',
                'jenis_kelamin',
                'kelas_induk_id',
                'kelas_id',
            ])
            ->withCount([
                'santris as santris_aktif_count' => fn ($query) => $query->active(),
            ])
            ->orderBy('nama')
            ->get()
            ->map(function (Musyrif $musyrif) use ($kelas): array {
                $kelasBinaan = $musyrif->kelasBinaan
                    ->pluck('nama_kelas')
                    ->filter()
                    ->values();

                return [
                    'id' => (int) $musyrif->id,
                    'nama' => $musyrif->nama,
                    'kode' => $musyrif->kode,
                    'jenis_kelamin' => $musyrif->jenis_kelamin,
                    'kelas_induk_id' => (int) $musyrif->kelas_induk_id,
                    'kelas_id' => $musyrif->kelas_id !== null
                        ? (int) $musyrif->kelas_id
                        : null,
                    'kelas_utama' => $musyrif->kelas?->nama_kelas,
                    'kelas_binaan' => $kelasBinaan->all(),
                    'santris_aktif_count' => (int) $musyrif->santris_aktif_count,
                    'sudah_membina_kelas_terpilih' => $musyrif->handlesKelas((int) $kelas->id),
                    'label' => sprintf(
                        '%s%s · %d santri aktif',
                        $musyrif->nama,
                        $musyrif->kode ? ' — ' . $musyrif->kode : '',
                        (int) $musyrif->santris_aktif_count
                    ),
                ];
            });

        return response()->json([
            'status' => $musyrifs->isEmpty() ? 'empty' : 'success',
            'message' => $musyrifs->isEmpty()
                ? "Belum ada Musyrif dengan tingkat utama {$tingkatUtama}."
                : "Daftar Musyrif tingkat {$tingkatUtama} berhasil dimuat.",
            'data' => $musyrifs->values(),
            'kelas' => [
                'id' => (int) $kelas->id,
                'nama' => $kelas->nama_kelas,
                'kelas_induk_id' => $kelasIndukId,
                'tingkat_utama' => $tingkatUtama,
                'is_operational' => $kelas->isKelompok() && (bool) $kelas->is_active,
                'is_legacy' => $kelas->isInduk(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'musyrif_id' => ['required', 'integer', 'exists:musyrifs,id'],
            'nama' => ['required', 'string', 'max:150'],
            'nis' => ['nullable', 'string', 'max:50'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
        ]);

        $kelas = $this->assertOperationalKelas((int) $validated['kelas_id']);

        $santri = DB::transaction(function () use ($validated, $kelas): Santri {
            $musyrif = $this->resolveMusyrifForKelasInduk(
                (int) $validated['musyrif_id'],
                $kelas
            );

            // Pivot adalah sumber kelas binaan dan ditambah secara additive.
            $musyrif->ensureKelasBinaan((int) $kelas->id);

            return Santri::query()->create([
                ...$validated,
                'kelas_id' => (int) $kelas->id,
                'musyrif_id' => (int) $musyrif->id,
                'status' => Santri::STATUS_AKTIF,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Santri berhasil ditambahkan ke ' . $kelas->nama_kelas . '.',
            'data' => $santri,
        ]);
    }

    public function update(Request $request, $id)
    {
        $santri = Santri::query()->findOrFail($id);

        if (!$santri->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hanya santri aktif yang dapat diedit dari Data Master Santri.',
            ], 422);
        }

        $validated = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'musyrif_id' => ['nullable', 'integer', 'exists:musyrifs,id'],
            'nama' => ['required', 'string', 'max:150'],
            'nis' => ['nullable', 'string', 'max:50'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', Rule::in(['L', 'P'])],
        ]);

        $kelas = $this->assertOperationalKelas(
            (int) $validated['kelas_id'],
            $santri
        );

        DB::transaction(function () use ($santri, $validated, $kelas): void {
            $musyrifId = null;

            if (!empty($validated['musyrif_id'])) {
                $musyrif = $this->resolveMusyrifForKelasInduk(
                    (int) $validated['musyrif_id'],
                    $kelas
                );

                $musyrif->ensureKelasBinaan((int) $kelas->id);
                $musyrifId = (int) $musyrif->id;
            }

            $santri->update([
                'nama' => $validated['nama'],
                'nis' => $validated['nis'] ?? null,
                'kelas_id' => (int) $kelas->id,
                'musyrif_id' => $musyrifId,
                'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
            ]);

            if ($santri->user) {
                $santri->user->update([
                    'name' => $validated['nama'],
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data santri berhasil diperbarui.',
            'data' => $santri->fresh(),
        ]);
    }

    public function addUser(Request $request, $id)
    {
        $santri = Santri::query()
            ->findOrFail($id);

        if (!$santri->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' =>
                'Akun akses baru hanya dapat dikelola untuk santri aktif.',
            ], 422);
        }

        $isUpdate =
            $santri->user_id !== null;

        $request->validate([
            'name' => 'required|string|max:255',
            'nomor' => 'nullable|string|max:20|unique:users,nomor,' . ($santri->user_id ?? 'NULL'),
            'email' => 'nullable|email|unique:users,email,' . ($santri->user_id ?? 'NULL'),
            'password' => $isUpdate ? 'nullable|string|min:6' : 'required|string|min:6',
        ]);

        if ($isUpdate) {
            $user = User::findOrFail($santri->user_id);
            $user->update([
                'name' => $request->name,
                'nomor' => $request->nomor,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $user->password,
            ]);
        } else {
            $user = User::create([
                'name' => $request->name,
                'nomor' => $request->nomor,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'santri',
            ]);
            $santri->update(['user_id' => $user->id]);
        }

        return response()->json([
            'status' => 'success',
            'message' => $isUpdate ? 'User berhasil diupdate' : 'User berhasil dibuat',
            'data' => $user
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $santri = Santri::query()
            ->findOrFail($id);

        if (!$santri->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' =>
                'Santri arsip tidak boleh dihapus. Gunakan halaman Alumni & Nonaktif untuk melihat histori atau mengaktifkan kembali.',
            ], 422);
        }

        $santri->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Santri berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('santri.master.index')
            ->with('success', 'Santri berhasil dihapus.');
    }

    private function normHeader($v): string
    {
        $s = strtolower(trim((string) $v));
        $s = preg_replace('/\s+/', ' ', $s);       // rapikan spasi
        $s = preg_replace('/[^a-z0-9 ]/i', '', $s); // buang simbol
        return trim($s);
    }

    private function detectHeaderRow(array $rows, array $namaAliases, int $scanMax = 30): array
    {
        $max = min(count($rows), $scanMax);

        for ($r = 0; $r < $max; $r++) {
            $row = $rows[$r] ?? [];
            if (!is_array($row) || count($row) === 0)
                continue;

            $header = array_map(fn($h) => $this->normHeader($h), $row);

            // cek apakah ada salah satu alias nama
            foreach ($header as $cell) {
                if ($cell !== '' && in_array($cell, $namaAliases, true)) {
                    // ketemu header row + namaKey = cell
                    return [$r, $cell, $header];
                }
            }
        }

        return [null, null, []];
    }

    private function findHeaderRowAndNameKey(array $sheetRows): array
    {
        // alias referensi luar
        $aliases = array_map([$this, 'normHeader'], [
            'nama',
            'name',
            'nama santri',
            'nama siswa',
            'siswa',
            'murid',
            'nama murid',
            'nama lengkap',
            'student name'
        ]);

        $bestRowIndex = null;
        $bestNameKey = null;

        // scan 30 baris pertama (silakan naikkan jika perlu)
        $maxScan = min(count($sheetRows), 30);

        for ($r = 0; $r < $maxScan; $r++) {
            $row = $sheetRows[$r] ?? [];
            if (!is_array($row) || count($row) === 0)
                continue;

            // normalisasi tiap cell jadi kandidat header
            $norms = array_map(fn($x) => $this->normHeader($x), $row);

            // cek apakah ada alias nama
            foreach ($norms as $cell) {
                if ($cell !== '' && in_array($cell, $aliases, true)) {
                    $bestRowIndex = $r;
                    $bestNameKey = $cell;
                    break 2; // ketemu header -> stop
                }
            }
        }

        return [$bestRowIndex, $bestNameKey, $aliases];
    }

    public function importIndex()
    {
        $kelasList = Kelas::query()
            ->with('parent:id,nama_kelas')
            ->operasional()
            ->urutHierarki()
            ->get();

        return view('admin.santri.import', compact('kelasList'));
    }

    public function importUpload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $path = $request->file('file')->store('imports');
        $allSheets = Excel::toArray([], storage_path('app/' . $path));

        $sheets = [];

        foreach ($allSheets as $index => $sheetRows) {
            [$headerRow, $namaKey, $aliases] = $this->findHeaderRowAndNameKey($sheetRows);

            $sheets[] = [
                'sheet_index' => $index,
                'label' => 'Sheet ' . ($index + 1),
                'rows' => max(count($sheetRows) - 1, 0),
                'is_valid' => $headerRow !== null,
                'header_row' => $headerRow !== null ? ($headerRow + 1) : null, // tampilkan 1-based ke UI
                'nama_key' => $namaKey,
            ];
        }

        return response()->json([
            'file_path' => $path,
            'sheets' => $sheets,
        ]);
    }

    public function importPreview(Request $request)
    {
        $request->validate([
            'file_path' => ['required', 'string'],
            'selections' => ['required', 'array'],
        ]);

        $allSheets = Excel::toArray([], storage_path('app/' . $request->file_path));

        // Alias "nama" fleksibel (sudah dinormalisasi)
        $namaAliases = array_map([$this, 'normHeader'], [
            'nama',
            'name',
            'nama santri',
            'nama siswa',
            'siswa',
            'murid',
            'nama murid',
            'nama lengkap',
            'student name',
        ]);

        $preview = [];
        $errors = [];
        $kelasCache = [];

        foreach ($request->selections as $sheetIndex => $cfg) {
            $sheetIndex = (int) $sheetIndex;
            $kelasId = (int) ($cfg['kelas_id'] ?? 0);
            if (!isset($kelasCache[$kelasId])) {
                $kelasCache[$kelasId] = Kelas::query()
                    ->operasional()
                    ->whereKey($kelasId)
                    ->first();
            }

            if (!$kelasCache[$kelasId]) {
                $errors[] = 'Sheet ' . ($sheetIndex + 1) . ': kelas tujuan harus berupa kelompok aktif.';
                continue;
            }

            $namaKelas = $kelasCache[$kelasId]->nama_kelas;


            if (!isset($allSheets[$sheetIndex]))
                continue;

            $rows = $allSheets[$sheetIndex];

            // ✅ cari header row yang benar (tidak selalu row 0)
            [$headerRow, $namaKey, $header] = $this->detectHeaderRow($rows, $namaAliases, 30);

            if ($headerRow === null || !$namaKey) {
                $errors[] = "Sheet " . ($sheetIndex + 1) . " tidak memiliki header nama yang valid (header tidak ada di 30 baris pertama).";
                continue;
            }

            $namaColIndex = array_search($namaKey, $header, true);
            if ($namaColIndex === false) {
                $errors[] = "Sheet " . ($sheetIndex + 1) . " gagal menemukan kolom nama pada header.";
                continue;
            }

            // Data mulai setelah headerRow
            foreach (array_slice($rows, $headerRow + 1) as $i => $r) {
                if (!is_array($r))
                    continue;

                // skip baris kosong
                if (count(array_filter($r, fn($x) => $x !== null && $x !== '')) === 0)
                    continue;

                $nama = trim((string) ($r[$namaColIndex] ?? ''));
                if ($nama === '')
                    continue;

                // Optional indexes
                $nisColIndex = array_search($this->normHeader('nis'), $header, true);

                $jkColIndex = array_search($this->normHeader('jenis kelamin'), $header, true);
                if ($jkColIndex === false) {
                    $jkColIndex = array_search($this->normHeader('jenis_kelamin'), $header, true);
                }
                if ($jkColIndex === false) {
                    $jkColIndex = array_search($this->normHeader('jk'), $header, true);
                }

                $nis = ($nisColIndex !== false)
                    ? trim((string) ($r[$nisColIndex] ?? ''))
                    : null;

                $jk = null;
                if ($jkColIndex !== false) {
                    $rawJk = strtolower(trim((string) ($r[$jkColIndex] ?? '')));

                    if (in_array($rawJk, ['l', 'laki-laki', 'laki', 'lk', 'male'], true)) {
                        $jk = 'L';
                    } elseif (in_array($rawJk, ['p', 'perempuan', 'pr', 'female'], true)) {
                        $jk = 'P';
                    }
                }

                $preview[] = [
                    'sheet' => 'Sheet ' . ($sheetIndex + 1),
                    'kelas_id' => $kelasId,
                    'kelas_nama' => $namaKelas,
                    'nama' => $nama,
                    'nis' => $nis ?: null,
                    'jenis_kelamin' => $jk,
                    'header_row' => $headerRow + 1,
                ];

                if (count($preview) >= 300)
                    break 2;
            }
        }

        return response()->json([
            'preview' => $preview,
            'errors' => $errors,
            'total' => count($preview),
        ]);
    }

    public function importProcess(Request $request)
    {
        $request->validate([
            'file_path' => ['required', 'string'],
            'selections' => ['required', 'array'],
        ]);

        $full = storage_path('app/' . $request->file_path);
        abort_unless(file_exists($full), 404);

        $allSheets = Excel::toArray([], $full);

        $namaAliases = array_map([$this, 'normHeader'], [
            'nama',
            'name',
            'nama santri',
            'nama siswa',
            'siswa',
            'murid',
            'nama murid',
            'nama lengkap',
            'student name',
        ]);

        $inserted = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->selections as $sheetIndex => $cfg) {
                $sheetIndex = (int) $sheetIndex;
                $kelasId = (int) ($cfg['kelas_id'] ?? 0);

                if ($kelasId <= 0) {
                    $errors[] = "Sheet " . ($sheetIndex + 1) . ": kelas belum dipilih.";
                    continue;
                }

                $kelasTujuan = Kelas::query()->operasional()->whereKey($kelasId)->first();

                if (!$kelasTujuan) {
                    $errors[] = "Sheet " . ($sheetIndex + 1) . ": kelas tujuan bukan kelompok aktif.";
                    continue;
                }

                if (!isset($allSheets[$sheetIndex])) {
                    $errors[] = "Sheet " . ($sheetIndex + 1) . ": tidak ditemukan.";
                    continue;
                }
                $musyrifTerpilih = Musyrif::where('kelas_id', $kelasId)->first();
                $rows = $allSheets[$sheetIndex];

                // ✅ detect header row
                [$headerRow, $namaKey, $header] = $this->detectHeaderRow($rows, $namaAliases, 30);

                if ($headerRow === null || !$namaKey) {
                    $errors[] = "Sheet " . ($sheetIndex + 1) . ": header nama tidak ditemukan (cek posisi header, maksimal scan 30 baris awal).";
                    continue;
                }

                $namaColIndex = array_search($namaKey, $header, true);
                if ($namaColIndex === false) {
                    $errors[] = "Sheet " . ($sheetIndex + 1) . ": kolom nama gagal dipetakan.";
                    continue;
                }

                // Optional indexes
                $nisColIndex = array_search($this->normHeader('nis'), $header, true);
                $jkColIndex = array_search($this->normHeader('jenis kelamin'), $header, true);
                if ($jkColIndex === false) {
                    // coba variasi header jk
                    $jkColIndex = array_search($this->normHeader('jenis_kelamin'), $header, true);
                    if ($jkColIndex === false)
                        $jkColIndex = array_search($this->normHeader('jk'), $header, true);
                }

                $tglColIndex = array_search($this->normHeader('tanggal lahir'), $header, true);
                if ($tglColIndex === false) {
                    $tglColIndex = array_search($this->normHeader('tanggal_lahir'), $header, true);
                    if ($tglColIndex === false)
                        $tglColIndex = array_search($this->normHeader('tgl lahir'), $header, true);
                }

                // Data mulai setelah header
                foreach (array_slice($rows, $headerRow + 1) as $r) {
                    if (!is_array($r))
                        continue;

                    if (count(array_filter($r, fn($x) => $x !== null && $x !== '')) === 0)
                        continue;

                    $nama = trim((string) ($r[$namaColIndex] ?? ''));
                    if ($nama === '') {
                        $skipped++;
                        continue;
                    }

                    $nis = ($nisColIndex !== false) ? trim((string) ($r[$nisColIndex] ?? '')) : null;

                    $jk = null;
                    if ($jkColIndex !== false) {
                        $raw = strtolower(trim((string) ($r[$jkColIndex] ?? '')));
                        if (in_array($raw, ['l', 'laki-laki', 'laki']))
                            $jk = 'L';
                        elseif (in_array($raw, ['p', 'perempuan']))
                            $jk = 'P';
                    }

                    $tgl = ($tglColIndex !== false) ? ($r[$tglColIndex] ?? null) : null;

                    Santri::create([
                        'user_id' => null,
                        'kelas_id' => $kelasId,
                        'musyrif_id' => $musyrifTerpilih ? $musyrifTerpilih->id : null,
                        'nama' => $nama,
                        'nis' => $nis ?: null,
                        'tanggal_lahir' => $tgl ?: null,
                        'jenis_kelamin' => $jk,
                        'status' => Santri::STATUS_AKTIF,
                    ]);

                    $inserted++;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Import santri selesai.',
                'inserted' => $inserted,
                'skipped' => $skipped,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal import: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function assertOperationalKelas(int $kelasId, ?Santri $currentSantri = null): Kelas
    {
        $kelas = Kelas::query()->with('parent:id,is_active')->findOrFail($kelasId);

        if ($kelas->isKelompok() && $kelas->is_active && $kelas->parent?->is_active) {
            return $kelas;
        }

        if (
            $currentSantri
            && (int) $currentSantri->kelas_id === $kelasId
            && $kelas->isInduk()
        ) {
            return $kelas;
        }

        throw ValidationException::withMessages([
            'kelas_id' => [
                'Assignment baru hanya boleh menggunakan kelas kelompok aktif. Data legacy hanya boleh mempertahankan kelas induk yang sedang dipakai.',
            ],
        ]);
    }

    private function resolveMusyrifForKelasInduk(
        int $musyrifId,
        Kelas $kelas
    ): Musyrif {
        $kelasIndukId = (int) ($kelas->parent_id ?: $kelas->id);

        $musyrif = Musyrif::query()
            ->with([
                'kelasInduk:id,nama_kelas',
                'kelasBinaan:id,nama_kelas,parent_id',
            ])
            ->find($musyrifId);

        if (!$musyrif) {
            throw ValidationException::withMessages([
                'musyrif_id' => ['Musyrif yang dipilih tidak ditemukan.'],
            ]);
        }

        if (!$musyrif->handlesKelasInduk($kelasIndukId)) {
            throw ValidationException::withMessages([
                'musyrif_id' => [
                    "Musyrif {$musyrif->nama} tidak memiliki tingkat utama yang sama dengan {$kelas->nama_kelas}.",
                ],
            ]);
        }

        return $musyrif;
    }

    /**
     * Menampilkan detail santri (untuk AJAX/Modal atau Halaman Profile)
     */
    public function show($id)
    {
        $santri = Santri::with(['kelas', 'musyrif', 'user'])->findOrFail($id);

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
                'data' => $santri
            ]);
        }

        // Jika Anda ingin membuat halaman profile terpisah nantinya
        return view('admin.santri.show', compact('santri'));
    }


    private function getViolationData($request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        /**
         * BASE QUERY
         */
        $baseQuery = PelanggaranPoint::query()
            ->whereBetween('tanggal', [$startDate, $endDate]);

        /**
         * MUSYRIF ANALYTICS
         */
        $musyrifAnalysis = (clone $baseQuery)

            ->with('musyrif')
            ->select(
                'musyrif_id',
                DB::raw('COUNT(*) as total_alpha'),
                DB::raw('SUM(poin) as total_poin'),
                DB::raw('COUNT(DISTINCT santri_id) as total_santri')
            )
            ->groupBy('musyrif_id')
            ->orderByDesc('total_alpha')
            ->get();

        /**
         * TOP SANTRI KRITIS
         */
        $topSantri = (clone $baseQuery)
            ->with([
                'santri.kelas',
                'musyrif'
            ])
            ->select(
                'santri_id',
                'musyrif_id',
                DB::raw('COUNT(*) as total_alpha'),
                DB::raw('SUM(poin) as total_poin')
            )
            ->groupBy(
                'santri_id',
                'musyrif_id'
            )
            ->orderByDesc('total_poin')
            ->limit(15)
            ->get();
        /**
         * ANALYSIS PER KELAS
         */
        $kelasAnalysis = (clone $baseQuery)
            ->join('santris', 'santris.id', '=', 'pelanggaran_points.santri_id')
            ->join('kelas', 'kelas.id', '=', 'santris.kelas_id')
            ->select(
                'kelas.nama_kelas',
                DB::raw('COUNT(*) as total_pelanggaran'),
                DB::raw('SUM(poin) as total_poin')
            )
            ->groupBy('kelas.nama_kelas')
            ->orderByDesc('total_pelanggaran')
            ->get();

        /**
         * HARI RAWAN
         */
        $dayLabels = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Ahad'
        ];

        $dayRaw = (clone $baseQuery)
            ->select(
                DB::raw('DAYNAME(tanggal) as day'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('day')
            ->pluck('total', 'day');
        $dayAnalysis = [];
        foreach ($dayLabels as $eng => $id) {
            $dayAnalysis[] = [
                'hari' => $id,
                'total' => $dayRaw[$eng] ?? 0
            ];
        }

        /**
         * SUMMARY KPI
         */
        $summary = [
            'total_pelanggaran' => (clone $baseQuery)->count(),
            'total_poin' => (clone $baseQuery)->sum('poin'),
            'total_santri_terlibat' => (clone $baseQuery)
                ->distinct('santri_id')
                ->count('santri_id'),
            'total_musyrif' => $musyrifAnalysis->count(),
            'avg_poin_per_santri' => round(
                $topSantri->avg('total_poin'),
                2
            ),
            'hari_terrawan' => collect($dayAnalysis)
                ->sortByDesc('total')
                ->first()['hari'] ?? '-',
        ];

        /**
         * INSIGHT ENGINE
         */
        $riskInsight = [
            'critical_santri' => $topSantri
                ->where('total_poin', '>=', 50)
                ->count(),
            'high_risk_musyrif' => $musyrifAnalysis
                ->where('total_alpha', '>', 15)
                ->count(),
            'most_problematic_kelas' => optional(
                $kelasAnalysis->first()
            )->nama_kelas,
            'most_problematic_musyrif' => optional(
                $musyrifAnalysis->first()
            )->musyrif->nama,
            'trend' => (
                $summary['total_pelanggaran'] > 100
            )
                ? 'Tinggi'
                : 'Terkendali',
        ];

        return [
            'summary' => $summary,
            'topMusyrif' => $musyrifAnalysis,
            'topSantri' => $topSantri,
            'kelasAnalysis' => $kelasAnalysis,
            'dayAnalysis' => $dayAnalysis,
            'riskInsight' => $riskInsight,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * Laporan Analisis Kehadiran/Pelanggaran untuk Manager
     */
    public function violationReport(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date)->startOfDay() : now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date)->endOfDay() : now()->endOfMonth();

        // Untuk Yajra DataTables (Ajax Request)
        if ($request->ajax()) {
            $query = PelanggaranPoint::with('musyrif')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->select('musyrif_id', DB::raw('count(*) as total_alpha'))
                ->groupBy('musyrif_id');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('musyrif_nama', fn($row) => $row->musyrif->nama ?? 'N/A')
                ->addColumn('status', function ($row) {
                    if ($row->total_alpha > 15) return '<span class="badge bg-danger-subtle text-danger rounded-pill px-3">Kontrol Lemah</span>';
                    if ($row->total_alpha > 5) return '<span class="badge bg-warning-subtle text-warning rounded-pill px-3">Waspada</span>';
                    return '<span class="badge bg-success-subtle text-success rounded-pill px-3">Sangat Baik</span>';
                })
                ->addColumn('aksi', function ($row) {
                    return '
        <div class="text-end pe-2">
            <button class="btn btn-sm rounded-pill px-3 fw-bold btn-detail-action btn-detail-musyrif"
                    data-id="' . $row->musyrif_id . '"
                    style="font-size: 0.75rem;">
                <i class="bi bi-layout-text-sidebar-reverse me-1"></i> Detail Kelas
            </button>
        </div>';
                })
                ->rawColumns(['status', 'aksi'])
                ->make(true);
        }
        // Model PelanggaranPoint diasumsikan sudah ada
        $baseQuery = PelanggaranPoint::whereBetween('tanggal', [$startDate, $endDate]);

        // 2. Insight: Hari Rawan (Temporal Analysis)
        $dayLabels = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Ahad'];
        $dayDataRaw = (clone $baseQuery)
            ->select(DB::raw('DAYNAME(tanggal) as day'), DB::raw('count(*) as total'))
            ->groupBy('day')
            ->get()
            ->pluck('total', 'day');

        $chartDays = [];
        foreach ($dayLabels as $eng => $id) {
            $chartDays[] = ['hari' => $id, 'total' => $dayDataRaw[$eng] ?? 0];
        }

        // 3. Insight: Evaluasi Musyrif (Operational Control)
        $musyrifAnalysis = (clone $baseQuery)
            ->with('musyrif')
            ->select('musyrif_id', DB::raw('count(*) as total_alpha'))
            ->groupBy('musyrif_id')
            ->orderBy('total_alpha', 'desc')
            ->get();

        // 4. Insight: Santri Kritis (Intervention Needed)
        $topViolators = (clone $baseQuery)
            ->with(['santri.kelas'])
            ->select('santri_id', DB::raw('sum(poin) as total_poin'))
            ->groupBy('santri_id')
            ->orderBy('total_poin', 'desc')
            ->limit(10)
            ->get();

        return view('admin.santri.violation_report', compact(
            'chartDays',
            'musyrifAnalysis',
            'topViolators',
            'startDate',
            'endDate'
        ));
    }

    public function violationMusyrifDetail(Request $request, $id)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Ambil data pelanggaran spesifik musyrif ini
        $details = PelanggaranPoint::with(['santri.kelas'])
            ->where('musyrif_id', $id)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select('santri_id', DB::raw('count(*) as total_alpha'), DB::raw('sum(poin) as total_poin'))
            ->groupBy('santri_id')
            ->orderBy('total_alpha', 'desc')
            ->get();

        return response()->json([
            'musyrif' => Musyrif::find($id)->nama,
            'data' => $details
        ]);
    }

    public function exportExcel(Request $request)
    {
        $result = $this->getViolationData($request);

        return Excel::download(

            new ViolationReportExport(
                $result['topMusyrif']
            ),

            'laporan-pelanggaran.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $result = $this->getViolationData($request);

        $pdf = Pdf::loadView(
            'admin.santri.export.violation_pdf',

            [

                'summary' => $result['summary'],

                'topMusyrif' => $result['topMusyrif'],

                'topSantri' => $result['topSantri'],

                'kelasAnalysis' => $result['kelasAnalysis'],

                'dayAnalysis' => $result['dayAnalysis'],

                'riskInsight' => $result['riskInsight'],

                'startDate' => $result['startDate'],

                'endDate' => $result['endDate'],
            ]
        )

            ->setPaper('a4', 'portrait');

        return $pdf->stream(
            'laporan-pelanggaran.pdf'
        );
    }
}
