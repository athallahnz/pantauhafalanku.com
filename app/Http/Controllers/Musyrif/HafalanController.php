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

        $query = Hafalan::with(['santri.kelas', 'template'])
            ->where('musyrif_id', $musyrif->id)
            ->select('hafalans.*');

        if ($request->filled('filter_tanggal')) {
            $now = Carbon::now('Asia/Jakarta');

            switch ($request->filter_tanggal) {
                case 'today':
                    $query->whereDate('tanggal_setoran', now('Asia/Jakarta')->toDateString());
                    break;

                case 'yesterday':
                    $query->whereDate(
                        'tanggal_setoran',
                        now('Asia/Jakarta')->subDay()->toDateString()
                    );
                    break;

                case 'last_7_days':
                    $query->whereBetween('tanggal_setoran', [
                        now('Asia/Jakarta')->subDays(6)->toDateString(),
                        now('Asia/Jakarta')->toDateString(),
                    ]);
                    break;

                case 'this_month':
                    $query->whereBetween('tanggal_setoran', [
                        $now->copy()->startOfMonth(),
                        $now->copy()->endOfMonth(),
                    ]);
                    break;
            }
        }

        $query->orderByDesc('tanggal_setoran');

        return DataTables::of($query)
            ->addIndexColumn()

            ->addColumn('santri', function ($row) {
                return $row->santri->nama ?? '-';
            })

            ->addColumn('kelas', function ($row) {
                return optional($row->santri->kelas)->nama_kelas ?: '-';
            })

            // Juz dari template (bukan input manual)
            ->addColumn('template_juz', function ($row) {
                return $row->template?->juz ?? '-';
            })

            // Label Surah:Ayat dari template.label (fallback ke accessor rentang_label jika masa transisi)
            ->addColumn('template_label', function ($row) {
                // Jika template ada, label dari template
                if ($row->template?->label) {
                    return $row->template->label;
                }

                // fallback (masa transisi)
                return $row->rentang_label ?? '-';
            })

            ->addColumn('tanggal', function ($row) {
                if (!$row->tanggal_setoran)
                    return '-';
                try {
                    return $row->tanggal_setoran->format('d-m-Y');
                } catch (\Throwable $e) {
                    return (string) $row->tanggal_setoran;
                }
            })

            // Nilai label -> Arab
            ->addColumn('nilai_label', function ($row) {
                return match ($row->nilai_label) {
                    'mumtaz' => 'ممتاز',
                    'jayyid_jiddan' => 'جيد جدًا',
                    'jayyid' => 'جيد',
                    default => '-',
                };
            })

            // Tahap dari template
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
                    'lulus' => '<span class="badge bg-success">Lulus</span>',
                    'ulang' => '<span class="badge bg-warning text-dark">Ulang</span>',
                    'hadir_tidak_setor' => '<span class="badge bg-info text-dark">Hadir Tidak Setor</span>',
                    'alpha' => '<span class="badge bg-danger">Alpha</span>',
                    default => '<span class="badge bg-secondary">-</span>',
                };
            })

            ->addColumn('aksi', function ($row) {
                $tanggalLabel = $row->tanggal_setoran ? $row->tanggal_setoran->format('d-m-Y') : '-';
                $tanggalYmd = $row->tanggal_setoran ? $row->tanggal_setoran->format('Y-m-d') : '';

                $templateJuz = $row->template?->juz ?? '';
                $templateTahap = $row->template?->tahap ?? '';
                $templateLabel = $row->template?->label ?? ($row->rentang_label ?? '-');

                // data-* untuk modal edit/detail versi baru
                $attrs =
                    'data-id="' . $row->id . '" ' .
                    'data-santri_id="' . ($row->santri_id ?? '') . '" ' .
                    'data-santri="' . e(optional($row->santri)->nama) . '" ' .
                    'data-kelas="' . e(optional(optional($row->santri)->kelas)->nama_kelas) . '" ' .

                    'data-template_juz="' . e($templateJuz) . '" ' .
                    'data-template_tahap="' . e($templateTahap) . '" ' .
                    'data-template_label="' . e($templateLabel) . '" ' .

                    'data-hafalan_template_id="' . e($row->hafalan_template_id ?? '') . '" ' .

                    'data-tanggal_label="' . e($tanggalLabel) . '" ' .
                    'data-tanggal_ymd="' . e($tanggalYmd) . '" ' .

                    'data-nilai_label="' . e($row->nilai_label ?? '') . '" ' .
                    'data-status="' . e($row->status ?? '') . '" ' .
                    'data-catatan="' . e($row->catatan ?? '') . '"';

                return '<div class="d-flex gap-2 flex-wrap py-2"><button class="btn btn-sm btn-outline-info btn-detail" ' . $attrs . '>Detail</button> <button class="btn btn-sm btn-outline-secondary btn-edit" ' . $attrs . '>Edit</button> <button class="btn btn-sm btn-outline-danger btn-delete" data-id="' . $row->id . '">Hapus</button></div>';
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
            return response()->json(['message' => 'Profil Musyrif tidak ditemukan. Hubungi admin.'], 422);
        }

        $v = $request->validated();

        $payload = [
            'santri_id' => $v['santri_id'],
            'musyrif_id' => $musyrif->id,
            'tanggal_setoran' => now()->toDateString(),
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

        // sinkron poin alpha = 1 (sesuai kesepakatan)
        $this->syncPoinAlpha($hafalan, $musyrif->id);

        return response()->json(['message' => 'Input hafalan berhasil disimpan.']);
    }

    public function update(StoreHafalanRequest $request, Hafalan $hafalan)
    {
        $user = auth()->user();
        $musyrif = $user->musyrif ?? Musyrif::where('user_id', $user->id)->firstOrFail();

        // Pastikan hanya mengubah data miliknya
        if ((int) $hafalan->musyrif_id !== (int) $musyrif->id) {
            abort(403);
        }

        $v = $request->validated();

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
