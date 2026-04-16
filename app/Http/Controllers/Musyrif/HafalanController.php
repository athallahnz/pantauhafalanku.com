<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\Musyrif;
use App\Models\HafalanTemplate;
use App\Models\PelanggaranPoint;
use Illuminate\Http\Request;
use App\Http\Requests\StoreHafalanRequest;
use App\Http\Requests\UpdateHafalanRequest;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class HafalanController extends Controller
{
    private function syncPoinAlpha(Hafalan $hafalan, int $musyrifId): void
    {
        $tanggal = $hafalan->tanggal_setoran;
        if (!$tanggal)
            return;

        // Jika status bukan alpha, pastikan poin alpha hari tsb tidak ada
        if ($hafalan->status !== 'alpha') {
            PelanggaranPoint::where('santri_id', $hafalan->santri_id)
                ->whereDate('tanggal', $tanggal)
                ->delete();
            return;
        }

        // Jika alpha, upsert 1 poin per hari per santri
        PelanggaranPoint::updateOrCreate(
            [
                'santri_id' => $hafalan->santri_id,
                'tanggal' => $tanggal,
            ],
            [
                'musyrif_id' => $musyrifId,
                'hafalan_id' => $hafalan->id,
                'poin' => 1,
                'keterangan' => null,
            ]
        );
    }

    public function index()
    {
        $user = auth()->user();

        // Ambil profil musyrif dari relasi user->musyrif
        $musyrif = $user->musyrif ?? Musyrif::where('user_id', $user->id)->first();

        if (!$musyrif) {
            abort(403, 'Profil musyrif tidak ditemukan. Hubungi admin.');
        }

        // List santri binaan (untuk dropdown di modal)
        $santriBinaan = Santri::with('kelas')
            ->where('musyrif_id', $musyrif->id)
            ->orderBy('nama')
            ->get();

        return view('musyrif.hafalan.index', compact('santriBinaan'));
    }

    public function datatable(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $user = auth()->user();
        $musyrif = $user->musyrif ?? Musyrif::where('user_id', $user->id)->first();

        if (!$musyrif) {
            abort(403, 'Profil musyrif tidak ditemukan.');
        }

        // Gunakan Join agar bisa searching ke tabel relasi
        $query = Hafalan::query()
            ->leftJoin('santris', 'hafalans.santri_id', '=', 'santris.id')
            ->leftJoin('kelas', 'santris.kelas_id', '=', 'kelas.id')
            ->leftJoin('hafalan_templates', 'hafalans.hafalan_template_id', '=', 'hafalan_templates.id')
            ->where('hafalans.musyrif_id', $musyrif->id)
            ->select([
                'hafalans.*',
                'santris.nama as santri_nama',
                'kelas.nama_kelas as kelas_nama',
                'hafalan_templates.label as template_label_sql',
                'hafalan_templates.juz as template_juz_sql'
            ]);

        if ($request->filled('filter_tanggal')) {
            $now = Carbon::now('Asia/Jakarta');
            switch ($request->filter_tanggal) {
                case 'today':
                    $query->whereDate('hafalans.tanggal_setoran', now('Asia/Jakarta')->toDateString());
                    break;
                case 'yesterday':
                    $query->whereDate('hafalans.tanggal_setoran', now('Asia/Jakarta')->subDay()->toDateString());
                    break;
                case 'last_7_days':
                    $query->whereBetween('hafalans.tanggal_setoran', [
                        now('Asia/Jakarta')->subDays(6)->toDateString(),
                        now('Asia/Jakarta')->toDateString(),
                    ]);
                    break;
                case 'this_month':
                    $query->whereBetween('hafalans.tanggal_setoran', [
                        $now->copy()->startOfMonth(),
                        $now->copy()->endOfMonth(),
                    ]);
                    break;
            }
        }

        $query->orderByDesc('hafalans.tanggal_setoran');

        return DataTables::of($query)
            ->addIndexColumn()

            /* ==========================================================
           FIX: Hanya cari di kolom yang BENAR-BENAR ada di database
        ========================================================== */
            ->filterColumn('santri', function ($query, $keyword) {
                $query->where('santris.nama', 'like', "%{$keyword}%");
            })
            ->filterColumn('kelas', function ($query, $keyword) {
                $query->where('kelas.nama_kelas', 'like', "%{$keyword}%");
            })
            ->filterColumn('template_juz', function ($query, $keyword) {
                $query->where('hafalan_templates.juz', 'like', "%{$keyword}%");
            })
            ->filterColumn('template_label', function ($query, $keyword) {
                // HANYA cari di kolom label template, rentang_label dibuang dari sini karena bukan kolom DB
                $query->where('hafalan_templates.label', 'like', "%{$keyword}%");
            })

            /* ==========================================================
           Mapping View (Tetap Menggunakan Logika Mas)
        ========================================================== */
            ->addColumn('santri', function ($row) {
                return $row->santri_nama ?? '-';
            })
            ->addColumn('kelas', function ($row) {
                return $row->kelas_nama ?: '-';
            })
            ->addColumn('template_juz', function ($row) {
                return $row->template_juz_sql ?? '-';
            })
            ->addColumn('template_label', function ($row) {
                return $row->template_label_sql ?? ($row->rentang_label ?? '-');
            })
            ->addColumn('tanggal', function ($row) {
                return $row->tanggal_setoran ? $row->tanggal_setoran->format('d-m-Y') : '-';
            })
            ->addColumn('nilai_label', function ($row) {
                return match ($row->nilai_label) {
                    'mumtaz' => 'ممتاز',
                    'jayyid_jiddan' => 'جيد جدًا',
                    'jayyid' => 'جيد',
                    default => '-',
                };
            })
            ->addColumn('template_tahap', function ($row) {
                return match ($row->template?->tahap) {
                    'harian' => 'Harian',
                    'tahap_1' => 'Tahap 1',
                    'tahap_2' => 'Tahap 2',
                    'tahap_3' => 'Tahap 3',
                    'ujian_akhir' => 'Ujian Akhir',
                    default => '-',
                };
            })
            ->editColumn('status', function ($row) {
                return match ($row->status) {
                    'lulus'             => '<span class="badge bg-success">Lulus</span>',
                    'ulang'             => '<span class="badge bg-warning text-dark">Ulang</span>',
                    'hadir_tidak_setor' => '<span class="badge bg-info text-dark">Hadir Tidak Setor</span>',
                    'alpha'             => '<span class="badge bg-danger">Alpha</span>',
                    'sakit'             => '<span class="badge bg-primary">Sakit</span>',   // <--- TAMBAHAN
                    'izin'              => '<span class="badge bg-secondary">Izin</span>', // <--- TAMBAHAN
                    default             => '<span class="badge bg-secondary">-</span>',
                };
            })
            ->addColumn('aksi', function ($row) {
                $tanggalLabel = $row->tanggal_setoran ? $row->tanggal_setoran->format('d-m-Y') : '-';
                $tanggalYmd = $row->tanggal_setoran ? $row->tanggal_setoran->format('Y-m-d') : '';
                $templateJuz = $row->template_juz_sql ?? '';
                $templateTahap = $row->template?->tahap ?? '';
                $templateLabel = $row->template_label_sql ?? ($row->rentang_label ?? '-');

                $attrs = 'data-id="' . $row->id . '" data-santri_id="' . ($row->santri_id ?? '') . '" data-santri="' . e($row->santri_nama) . '" data-kelas="' . e($row->kelas_nama) . '" data-template_juz="' . e($templateJuz) . '" data-template_tahap="' . e($templateTahap) . '" data-template_label="' . e($templateLabel) . '" data-hafalan_template_id="' . e($row->hafalan_template_id ?? '') . '" data-tanggal_label="' . e($tanggalLabel) . '" data-tanggal_ymd="' . e($tanggalYmd) . '" data-nilai_label="' . e($row->nilai_label ?? '') . '" data-status="' . e($row->status ?? '') . '" data-catatan="' . e($row->catatan ?? '') . '"';

                return '<div class="d-flex flex-nowrap gap-1">
                <button class="btn btn-sm btn-outline-primary btn-detail" ' . $attrs . '><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-warning text-white btn-edit" ' . $attrs . '><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-danger text-white btn-delete" data-id="' . $row->id . '"><i class="bi bi-trash"></i></button>
            </div>';
            })
            ->rawColumns(['status', 'aksi'])
            ->make(true);
    }

    public function templates(Request $request)
    {
        $data = $request->validate([
            'juz' => ['required', 'integer', 'min:1', 'max:30'],
            'tahap' => ['required', 'in:harian,tahap_1,tahap_2,tahap_3,ujian_akhir'],
        ]);

        $templates = HafalanTemplate::query()
            ->select(['id', 'urutan', 'label'])
            ->where('juz', $data['juz'])
            ->where('tahap', $data['tahap'])
            ->orderBy('urutan')
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'urutan' => $t->urutan,
                'label' => $t->label ?? ('Bagian ' . $t->urutan),
            ]);

        return response()->json([
            'ok' => true,
            'templates' => $templates,
        ]);
    }

    public function store(StoreHafalanRequest $request)
    {
        $user = auth()->user();
        $musyrif = $user->musyrif ?? Musyrif::where('user_id', $user->id)->first();

        if (!$musyrif) {
            return response()->json(['message' => 'Profil Musyrif tidak ditemukan.'], 422);
        }

        $v = $request->validated();
        $today = now()->toDateString();

        // ============================================================
        // PENGECEKAN DATA DOUBLE
        // ============================================================
        // Cek apakah santri ini sudah menyetor materi yang sama hari ini
        $isDuplicate = Hafalan::where('santri_id', $v['santri_id'])
            ->where('hafalan_template_id', $v['hafalan_template_id'] ?? null)
            ->where('tanggal_setoran', $today)
            ->exists();

        // Di dalam method store dan update
        if ($isDuplicate && in_array($v['status'], ['lulus', 'ulang'], true)) {
            return response()->json([
                // Kita kirimkan di kedua tempat agar aman
                'message' => 'Data hafalan ini sudah pernah diinput hari ini untuk santri tersebut.',
                'errors' => [
                    'hafalan' => ['Data hafalan ini sudah pernah diinput hari ini untuk santri tersebut.']
                ]
            ], 422);
        }
        // ============================================================

        $payload = [
            'santri_id' => $v['santri_id'],
            'musyrif_id' => $musyrif->id,
            'tanggal_setoran' => $today,
            'status' => $v['status'],
            'catatan' => $v['catatan'] ?? null,
            'hafalan_template_id' => null,
            'nilai_label' => null,
        ];

        if (in_array($v['status'], ['lulus', 'ulang'], true)) {
            $payload['hafalan_template_id'] = $v['hafalan_template_id'];
            $payload['nilai_label'] = $v['nilai_label'];
        }

        $hafalan = Hafalan::create($payload);

        $this->syncPoinAlpha($hafalan, $musyrif->id);

        return response()->json(['message' => 'Input hafalan berhasil disimpan.']);
    }

    public function update(StoreHafalanRequest $request, Hafalan $hafalan)
    {
        $user = auth()->user();
        $musyrif = $user->musyrif ?? Musyrif::where('user_id', $user->id)->firstOrFail();

        if ((int) $hafalan->musyrif_id !== (int) $musyrif->id) {
            abort(403);
        }

        $v = $request->validated();

        // ============================================================
        // PENGECEKAN DATA DOUBLE (Kecuali data ini sendiri)
        // ============================================================
        $isDuplicate = Hafalan::where('santri_id', $v['santri_id'])
            ->where('hafalan_template_id', $v['hafalan_template_id'] ?? null)
            ->where('tanggal_setoran', $hafalan->tanggal_setoran)
            ->where('id', '!=', $hafalan->id) // Abaikan record yang sedang diupdate
            ->exists();

        if ($isDuplicate && in_array($v['status'], ['lulus', 'ulang'], true)) {
            return response()->json([
                'message' => 'Gagal update! Data hafalan serupa sudah ada di sistem.'
            ], 422);
        }
        // ============================================================

        // ... sisa kode payload dan update sama seperti sebelumnya ...
        $payload = [
            'santri_id' => $v['santri_id'],
            'status' => $v['status'],
            'catatan' => $v['catatan'] ?? null,
            'hafalan_template_id' => null,
            'nilai_label' => null,
        ];

        if (in_array($v['status'], ['lulus', 'ulang'], true)) {
            $payload['hafalan_template_id'] = $v['hafalan_template_id'];
            $payload['nilai_label'] = $v['nilai_label'];
        }

        $hafalan->update($payload);
        $hafalan->refresh();
        $this->syncPoinAlpha($hafalan, $musyrif->id);

        return response()->json(['message' => 'Input hafalan berhasil diperbarui.']);
    }

    public function show($id)
    {
        $hafalan = Hafalan::with('santri.kelas')->findOrFail($id);
        return response()->json($hafalan);
    }

    public function destroy($id)
    {
        Hafalan::findOrFail($id)->delete();

        return response()->json(['message' => 'Setoran berhasil dihapus']);
    }
}
