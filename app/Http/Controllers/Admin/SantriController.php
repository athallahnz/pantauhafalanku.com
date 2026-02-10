<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class SantriController extends Controller
{
    public function index()
    {
        $kelasList = Kelas::orderByRaw("
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
        ")->get();

        $musyrifList = Musyrif::withCount('santri')
            ->orderBy('nama')
            ->get();

        return view('admin.santri.index', compact('kelasList', 'musyrifList'));
    }

    public function getData(Request $request)
    {
        if (!$request->ajax()) abort(404);

        $query = Santri::leftJoin('users', 'users.id', '=', 'santris.user_id')
            ->leftJoin('kelas', 'kelas.id', '=', 'santris.kelas_id')
            ->leftJoin('musyrifs', 'musyrifs.id', '=', 'santris.musyrif_id')
            ->select([
                'santris.id',
                'santris.nis',
                'santris.nama',
                'santris.kelas_id',
                'santris.musyrif_id',
                'santris.tanggal_lahir',
                'santris.jenis_kelamin',
                'kelas.nama_kelas as kelas_nama',
                'musyrifs.nama as musyrif_nama',
                'users.id as user_id',
                'users.name as user_name',
                'users.nomor as user_nomor',
                'users.email as user_email',
            ])
            ->orderBy('santris.nama');

        if ($request->filled('kelas_id')) {
            $query->where('santris.kelas_id', $request->kelas_id);
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
            ->addColumn('kelas', fn($row) => $row->kelas_nama ?: '-')
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
                >Detail</button>';

                $btnEdit = '
                <button class="btn btn-sm btn-outline-secondary btn-edit"
                    data-id="' . $row->id . '"
                    data-nama="' . e($row->nama) . '"
                    data-nis="' . e($row->nis) . '"
                    data-kelas_id="' . e($row->kelas_id) . '"
                    data-musyrif_id="' . e($row->musyrif_id) . '"
                    data-tanggal_lahir="' . e($tgl) . '"
                    data-jenis_kelamin="' . e($row->jenis_kelamin) . '"
                >Edit</button>';

                $btnDelete = '
                <button class="btn btn-sm btn-outline-danger btn-delete"
                    data-id="' . $row->id . '"
                >Hapus</button>';

                $btnUser = '
                <button class="btn btn-sm btn-outline-primary btn-user"
                    data-id="' . $row->id . '"
                    data-nama="' . e($row->nama) . '"
                    data-user-id="' . ($row->user_id ?? '') . '"
                    data-user-name="' . ($row->user_name ?? '') . '"
                    data-user-nomor="' . ($row->user_nomor ?? '') . '"
                    data-user-email="' . ($row->user_email ?? '') . '"
                >Buat User</button>';

                return '<div class="d-flex flex-wrap gap-1 gap-md-2">' . $btnDetail . $btnEdit . $btnDelete . $btnUser . '</div>';
            })
            ->rawColumns(['aksi', 'akun'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'musyrif_id' => 'nullable|exists:musyrifs,id',
            'nama' => 'required|string|max:150',
            'nis' => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:L,P',
        ]);

        $santri = Santri::create([
            'user_id' => null,
            'kelas_id' => $validated['kelas_id'],
            'musyrif_id' => $validated['musyrif_id'] ?? null,
            'nama' => $validated['nama'],
            'nis' => $validated['nis'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Santri berhasil dibuat.',
                'data' => $santri,
            ]);
        }

        return redirect()->route('santri.master.index')->with('success', 'Santri berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $santri = Santri::findOrFail($id);

        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'musyrif_id' => 'nullable|exists:musyrifs,id',
            'nama' => 'required|string|max:150',
            'nis' => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|in:L,P',
        ]);

        // Update data Santri
        $santri->update([
            'kelas_id' => $validated['kelas_id'],
            'musyrif_id' => $validated['musyrif_id'] ?? null,
            'nama' => $validated['nama'],
            'nis' => $validated['nis'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $validated['jenis_kelamin'] ?? null,
        ]);

        // Update nama di user jika ada
        if ($santri->user) {
            $santri->user->update([
                'name' => $validated['nama'],
            ]);
        }

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Santri berhasil diupdate.',
                'data' => $santri,
            ]);
        }

        return redirect()->route('santri.master.index')->with('success', 'Santri berhasil diupdate.');
    }

    public function addUser(Request $request, $id)
    {
        $santri = Santri::findOrFail($id);
        $isUpdate = $santri->user_id ? true : false;

        $request->validate([
            'name' => 'required|string|max:255',
            'nomor' => 'required|string|max:20|unique:users,nomor,' . ($santri->user_id ?? 'NULL'),
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
        $santri = Santri::findOrFail($id);
        $santri->delete();

        if ($request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Santri berhasil dihapus.',
            ]);
        }

        return redirect()->route('santri.master.index')->with('success', 'Santri berhasil dihapus.');
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
        $kelasList = Kelas::orderBy('nama_kelas')->get();
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

        foreach ($request->selections as $sheetIndex => $cfg) {
            $sheetIndex = (int) $sheetIndex;
            $kelasId = (int) ($cfg['kelas_id'] ?? 0);
            if (!isset($kelasCache[$kelasId])) {
                $kelasCache[$kelasId] = Kelas::find($kelasId);
            }
            $namaKelas = $kelasCache[$kelasId]->nama_kelas ?? '-';


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

                // Optional: NIS kalau header ada 'nis'
                $nisColIndex = array_search($this->normHeader('nis'), $header, true);
                $nis = ($nisColIndex !== false) ? trim((string) ($r[$nisColIndex] ?? '')) : null;

                $preview[] = [
                    'sheet' => 'Sheet ' . ($sheetIndex + 1),
                    'kelas_id' => $kelasId,
                    'kelas_nama' => $namaKelas,
                    'nama' => $nama,
                    'nis' => $nis ?: null,
                    'header_row' => $headerRow + 1, // info tambahan (1-based)
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

                if (!isset($allSheets[$sheetIndex])) {
                    $errors[] = "Sheet " . ($sheetIndex + 1) . ": tidak ditemukan.";
                    continue;
                }

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
                        'musyrif_id' => null,
                        'nama' => $nama,
                        'nis' => $nis ?: null,
                        'tanggal_lahir' => $tgl ?: null,
                        'jenis_kelamin' => $jk,
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
}
