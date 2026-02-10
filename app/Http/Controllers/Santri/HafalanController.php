<?php

namespace App\Http\Controllers\Santri;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\HafalanTemplate;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

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

        $q = Hafalan::with('template')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal_setoran')
            ->orderByDesc('created_at')
            ->select('hafalans.*');

        return DataTables::of($q)
            ->addIndexColumn()
            ->addColumn('tanggal', fn($r) => $r->tanggal_setoran ? $r->tanggal_setoran->format('d-m-Y') : '-')
            ->addColumn('juz', fn($r) => $r->template?->juz ?? '-')
            ->addColumn('surah_ayat', fn($r) => $r->template?->label ?? '-')
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
                    'alpha' => 'bg-danger',
                    default => 'bg-secondary',
                };
                $label = match ($r->status) {
                    'lulus' => 'Lulus',
                    'ulang' => 'Ulang',
                    'hadir_tidak_setor' => 'Hadir Tidak Setor',
                    'alpha' => 'Alpha',
                    default => '-',
                };
                return '<span class="badge ' . $badge . '">' . $label . '</span>';
            })
            ->rawColumns(['status'])
            ->make(true);
    }
}
