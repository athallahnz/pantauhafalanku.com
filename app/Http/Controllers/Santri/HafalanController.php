<?php

namespace App\Http\Controllers\Santri;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\HafalanTemplate;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class HafalanController extends Controller
{
    public function index()
    {
        $santri = auth()->user()->santri; // ambil santri yang login

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        /*
         * Statistik, progress, dll
         */
        $statusCounts = Hafalan::where('santri_id', $santri->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalSetor = ($statusCounts['lulus'] ?? 0) + ($statusCounts['ulang'] ?? 0);
        $totalAlpha = $statusCounts['alpha'] ?? 0;
        $totalHadirTidakSetor = $statusCounts['hadir_tidak_setor'] ?? 0;

        // --- TAMBAHAN BARU ---
        $totalSakit = $statusCounts['sakit'] ?? 0;
        $totalIzin = $statusCounts['izin'] ?? 0;

        $avgNilai = Hafalan::where('santri_id', $santri->id)
            ->whereIn('status', ['lulus', 'ulang'])
            ->selectRaw("
            ROUND(
                AVG(
                    CASE nilai_label
                        WHEN 'mumtaz' THEN 95
                        WHEN 'jayyid_jiddan' THEN 85
                        WHEN 'jayyid' THEN 75
                    END
                ), 1
            ) as avg
        ")->value('avg') ?? 0;

        // Ranking tahap
        $tahapRank = [
            'harian' => 1,
            'tahap_1' => 2,
            'tahap_2' => 3,
            'tahap_3' => 4,
            'ujian_akhir' => 5
        ];

        $tahapWeight = [
            'harian' => 20,
            'tahap_1' => 40,
            'tahap_2' => 60,
            'tahap_3' => 80,
            'ujian_akhir' => 100
        ];

        $tahapPerJuz = Hafalan::join('hafalan_templates', 'hafalan_templates.id', '=', 'hafalans.hafalan_template_id')
            ->where('hafalans.santri_id', $santri->id)
            ->where('hafalans.status', 'lulus')
            ->select('hafalan_templates.juz', 'hafalan_templates.tahap')
            ->get()
            ->groupBy('juz')
            ->map(function ($rows) use ($tahapRank) {
                return $rows->sortByDesc(fn($r) => $tahapRank[$r->tahap] ?? 0)->first()->tahap ?? null;
            });

        $progressPerJuz = collect(range(1, 30))->map(function ($juz) use ($tahapPerJuz, $tahapWeight) {
            $tahap = $tahapPerJuz[$juz] ?? null;
            $pct = $tahap ? ($tahapWeight[$tahap] ?? 0) : 0;

            if ($pct >= 100) {
                $status = 'Selesai';
                $color = 'success';
            } elseif ($pct >= 80) {
                $status = 'Tahap 3';
                $color = 'info';
            } elseif ($pct >= 60) {
                $status = 'Tahap 2';
                $color = 'primary';
            } elseif ($pct >= 40) {
                $status = 'Tahap 1';
                $color = 'warning';
            } elseif ($pct > 0) {
                $status = 'Harian';
                $color = 'secondary';
            } else {
                $status = 'Belum mulai';
                $color = 'light';
            }

            return [
                'juz' => $juz,
                'pct' => $pct,
                'status' => $status,
                'color' => $color,
                'tahap' => $tahap
            ];
        });

        $overallPct = round($progressPerJuz->avg('pct') ?? 0);

        return view('santri.hafalan.index', compact(
            'santri',
            'totalSetor',
            'totalAlpha',
            'totalHadirTidakSetor',
            'totalSakit', // --- TAMBAHAN BARU ---
            'totalIzin',  // --- TAMBAHAN BARU ---
            'avgNilai',
            'progressPerJuz',
            'overallPct'
        ));
    }


    public function timeline(Request $request)
    {
        if (!$request->ajax()) abort(404);

        $santri = auth()->user()->santri;

        if (!$santri) {
            return response()->json(['error' => 'Profil santri tidak ditemukan'], 403);
        }

        $q = Hafalan::query()
            ->leftJoin('hafalan_templates', 'hafalans.hafalan_template_id', '=', 'hafalan_templates.id')
            ->where('hafalans.santri_id', $santri->id)
            ->select('hafalans.*', 'hafalan_templates.juz as template_juz', 'hafalan_templates.label as template_label');

        return DataTables::of($q)
            ->addIndexColumn()
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(tanggal_setoran, '%d-%m-%Y') LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('juz', function ($query, $keyword) {
                $query->where('hafalan_templates.juz', 'like', "%{$keyword}%");
            })
            ->filterColumn('surah_ayat', function ($query, $keyword) {
                $query->where('hafalan_templates.label', 'like', "%{$keyword}%");
            })
            ->filterColumn('nilai', function ($query, $keyword) {
                $query->where('nilai_label', 'like', "%{$keyword}%");
            })
            ->filterColumn('status', function ($query, $keyword) {
                $query->where('status', 'like', "%{$keyword}%");
            })
            ->addColumn('tanggal', fn($r) => $r->tanggal_setoran ? $r->tanggal_setoran->format('d-m-Y') : '-')
            ->addColumn('juz', fn($r) => $r->template_juz ?? '-')
            ->addColumn('surah_ayat', fn($r) => $r->template_label ?? '-')
            ->addColumn('nilai', fn($r) => match ($r->nilai_label) {
                'mumtaz' => 'ممتاز',
                'jayyid_jiddan' => 'جيد جدًا',
                'jayyid' => 'جيد',
                default => '-'
            })
            ->addColumn('status', function ($r) {
                $badge = match ($r->status) {
                    'lulus' => 'bg-success',
                    'ulang' => 'bg-warning text-dark',
                    'hadir_tidak_setor' => 'bg-info text-dark',
                    'sakit' => 'bg-primary',      // --- TAMBAHAN BARU ---
                    'izin' => 'bg-secondary',     // --- TAMBAHAN BARU ---
                    'alpha' => 'bg-danger',
                    default => 'bg-secondary',
                };
                $label = match ($r->status) {
                    'lulus' => 'Lulus',
                    'ulang' => 'Ulang',
                    'hadir_tidak_setor' => 'Hadir Tidak Setor',
                    'sakit' => 'Sakit',           // --- TAMBAHAN BARU ---
                    'izin' => 'Izin',             // --- TAMBAHAN BARU ---
                    'alpha' => 'Alpha',
                    default => '-',
                };
                return '<span class="badge ' . $badge . '">' . $label . '</span>';
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    public function exportPdf(Request $request)
    {
        $santri = auth()->user()->santri;

        // Inisialisasi Query
        $query = Hafalan::with('template')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal_setoran');

        // Cek Filter Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_setoran', [$request->start_date, $request->end_date]);
            $periode = \Carbon\Carbon::parse($request->start_date)->format('d/m/Y') . ' s/d ' . \Carbon\Carbon::parse($request->end_date)->format('d/m/Y');
        } else {
            $periode = "Semua Riwayat";
        }

        $timeline = $query->get();

        // --- UPDATE STATISTIK UNTUK PDF ---
        $statusCounts = $timeline->groupBy('status')->map->count();

        $totalSetor = ($statusCounts['lulus'] ?? 0) + ($statusCounts['ulang'] ?? 0);
        $totalAlpha = $statusCounts['alpha'] ?? 0;
        $totalSakit = $statusCounts['sakit'] ?? 0; // Tambahkan ini
        $totalIzin  = $statusCounts['izin'] ?? 0;  // Tambahkan ini
        $totalHTS   = $statusCounts['hadir_tidak_setor'] ?? 0; // Tambahkan ini (Hadir Tidak Setor)

        $data = [
            'santri'        => $santri,
            'timeline'      => $timeline,
            'totalSetor'    => $totalSetor,
            'totalAlpha'    => $totalAlpha,
            'totalSakit'    => $totalSakit,
            'totalIzin'     => $totalIzin,
            'totalHTS'      => $totalHTS,
            'periode'       => $periode,
            'tanggal_cetak' => now()->format('d F Y'),
        ];

        $pdf = Pdf::loadView('santri.hafalan.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        // Ganti bagian return di akhir fungsi exportPdf
        return $pdf->stream('Laporan_Hafalan_' . str_replace(' ', '_', $santri->nama) . '.pdf');
    }
}
