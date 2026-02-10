<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\Hafalan;
use App\Models\HafalanTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SantriController extends Controller
{
    public function index()
    {
        // Hanya return view; DataTables ambil via AJAX
        return view('musyrif.santri.index');
    }

    public function datatable(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $musyrif = Musyrif::where('user_id', auth()->id())->firstOrFail();

        // Target per juz (baseline progress: tahap harian)
        $targetPerJuz = HafalanTemplate::query()
            ->select('juz', DB::raw('COUNT(*) as target'))
            ->where('tahap', 'harian')
            ->groupBy('juz')
            ->pluck('target', 'juz'); // [1=>..., 2=>..., ...]

        $query = Santri::with('kelas')
            ->where('musyrif_id', $musyrif->id)
            ->select('santris.*')
            ->orderBy('nama');

        return DataTables::of($query)
            ->addIndexColumn()

            ->addColumn('kelas', function ($row) {
                return optional($row->kelas)->nama_kelas ?: '-';
            })

            ->editColumn('tanggal_lahir', function ($row) {
                return $row->tanggal_lahir
                    ? $row->tanggal_lahir->format('d-m-Y')
                    : '-';
            })

            ->editColumn('jenis_kelamin', function ($row) {
                return match ($row->jenis_kelamin) {
                    'L' => 'Laki-laki',
                    'P' => 'Perempuan',
                    default => '-',
                };
            })

            ->addColumn('progress_ringkas', function ($santri) use ($targetPerJuz) {

                // HANYA tahap harian
                $hafalans = Hafalan::query()
                    ->with(['template:id,juz,tahap'])
                    ->where('santri_id', $santri->id)
                    ->whereIn('status', ['lulus', 'ulang'])
                    ->whereHas('template', function ($q) {
                        $q->where('tahap', 'harian');
                    })
                    ->get(['id', 'nilai_label', 'hafalan_template_id']);

                // pastikan tidak double
                $uniqueTemplates = $hafalans->unique('hafalan_template_id')->values();

                $totalSetor = $uniqueTemplates->count();

                $nilaiMap = [
                    'mumtaz' => 95,
                    'jayyid_jiddan' => 85,
                    'jayyid' => 75,
                ];

                $sum = 0;
                $cnt = 0;

                foreach ($uniqueTemplates as $h) {
                    if (!empty($h->nilai_label) && isset($nilaiMap[$h->nilai_label])) {
                        $sum += $nilaiMap[$h->nilai_label];
                        $cnt++;
                    }
                }

                $avg = $cnt ? round($sum / $cnt, 1) : 0;

                $donePerJuz = [];

                foreach ($uniqueTemplates as $h) {
                    $juz = $h->template?->juz;
                    if ($juz) {
                        $donePerJuz[$juz] = ($donePerJuz[$juz] ?? 0) + 1;
                    }
                }

                // hitung overall berdasarkan TARGET HARIAN
                $sumPct = 0;
                $juzCount = 0;

                for ($j = 1; $j <= 30; $j++) {

                    $target = (int) ($targetPerJuz[$j] ?? 0);

                    if ($target <= 0)
                        continue;

                    $done = (int) ($donePerJuz[$j] ?? 0);

                    $pct = min(100, round(($done / $target) * 100, 1));

                    $sumPct += $pct;
                    $juzCount++;
                }

                $overall = $juzCount ? round($sumPct / $juzCount, 1) : 0;

                return '
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-primary"
                        role="progressbar"
                        style="width: ' . $overall . '%;">
                    </div>
                </div>

                <div class="small text-muted mt-1">
                    Setor Harian: <b>' . $totalSetor . '</b> |
                    Rata-rata: <b>' . $avg . '</b> |
                    Progress: <b>' . $overall . '%</b>
                </div>
            ';
            })


            ->addColumn('aksi', function ($santri) use ($targetPerJuz) {

                // HANYA tahap harian
                $hafalans = Hafalan::query()
                    ->with('template:id,juz,tahap')
                    ->where('santri_id', $santri->id)
                    ->whereIn('status', ['lulus', 'ulang'])
                    ->whereHas('template', function ($q) {
                        $q->where('tahap', 'harian');
                    })
                    ->get(['id', 'hafalan_template_id']);

                // prevent double
                $hafalans = $hafalans->unique('hafalan_template_id')->values();

                $donePerJuz = [];

                foreach ($hafalans as $h) {

                    $juz = $h->template?->juz;

                    if ($juz) {
                        $donePerJuz[$juz] = ($donePerJuz[$juz] ?? 0) + 1;
                    }
                }

                // build html
                $rows = '';

                for ($j = 1; $j <= 30; $j++) {

                    $target = (int) ($targetPerJuz[$j] ?? 0);

                    if ($target <= 0)
                        continue;

                    $done = (int) ($donePerJuz[$j] ?? 0);

                    $pct = min(100, round(($done / $target) * 100, 1));

                    $rows .= '
                    <div class="mb-2">

                        <div class="d-flex justify-content-between small">
                            <span>Juz ' . $j . '</span>
                            <span>' . $done . '/' . $target . ' (' . $pct . '%)</span>
                        </div>

                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary"
                                style="width: ' . $pct . '%;">
                            </div>
                        </div>

                    </div>';
                }

                $detailHtml = '<div>' .
                    ($rows ?: '<div class="text-muted">Belum ada setoran harian.</div>')
                    . '</div>';

                return '
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button"
                            class="btn btn-sm btn-outline-info btn-progress flex-grow-1"
                            style="min-width: 70px;"
                            data-nama="' . e($santri->nama) . '"
                            data-kelas="' . e(optional($santri->kelas)->nama_kelas ?: '-') . '"
                            data-detail_html="' . e($detailHtml) . '">
                            <i class="fas fa-chart-line"></i> Lihat
                        </button>

                        <a class="btn btn-sm btn-outline-info flex-grow-1"
                            style="min-width: 70px;"
                            href="' . route('musyrif.santri.detail', $santri->id) . '">
                            <i class="fas fa-user-circle"></i> Detail
                        </a>
                    </div>
                    ';
            })

            ->rawColumns(['progress_ringkas', 'aksi'])
            ->make(true);
    }

    public function detail(Santri $santri)
    {
        $musyrif = Musyrif::where('user_id', auth()->id())->firstOrFail();

        abort_if($santri->musyrif_id !== $musyrif->id, 403);


        /*
    |--------------------------------------------------------------------------
    | Statistik status
    |--------------------------------------------------------------------------
    */

        $statusCounts = Hafalan::query()
            ->where('santri_id', $santri->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalSetor = ($statusCounts['lulus'] ?? 0) + ($statusCounts['ulang'] ?? 0);
        $totalAlpha = $statusCounts['alpha'] ?? 0;
        $totalHadirTidakSetor = $statusCounts['hadir_tidak_setor'] ?? 0;


        /*
    |--------------------------------------------------------------------------
    | Average nilai
    |--------------------------------------------------------------------------
    */

        $avgNilai = Hafalan::query()
            ->where('santri_id', $santri->id)
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
        ")
            ->value('avg') ?? 0;


        /*
    |--------------------------------------------------------------------------
    | Ranking tahap (untuk menentukan tahap tertinggi)
    |--------------------------------------------------------------------------
    */

        $tahapRank = [
            'harian' => 1,
            'tahap_1' => 2,
            'tahap_2' => 3,
            'tahap_3' => 4,
            'ujian_akhir' => 5,
        ];


        /*
    |--------------------------------------------------------------------------
    | Weight progress per tahap (ENTERPRISE MODEL)
    |--------------------------------------------------------------------------
    */

        $tahapWeight = [
            'harian' => 20,
            'tahap_1' => 40,
            'tahap_2' => 60,
            'tahap_3' => 80,
            'ujian_akhir' => 100,
        ];


        /*
    |--------------------------------------------------------------------------
    | Ambil tahap tertinggi per juz
    |--------------------------------------------------------------------------
    */

        $tahapPerJuz = Hafalan::query()
            ->join('hafalan_templates', 'hafalan_templates.id', '=', 'hafalans.hafalan_template_id')
            ->where('hafalans.santri_id', $santri->id)
            ->where('hafalans.status', 'lulus')
            ->select('hafalan_templates.juz', 'hafalan_templates.tahap')
            ->get()
            ->groupBy('juz')
            ->map(function ($rows) use ($tahapRank) {

                $sorted = $rows->sortByDesc(
                    fn($r) => $tahapRank[$r->tahap] ?? 0
                );

                return $sorted->first()->tahap ?? null;
            });


        /*
    |--------------------------------------------------------------------------
    | Progress per juz (BERDASARKAN TAHAP TERTINGGI)
    |--------------------------------------------------------------------------
    */

        $progressPerJuz = collect(range(1, 30))
            ->map(function ($juz) use ($tahapPerJuz, $tahapWeight) {

                $tahap = $tahapPerJuz[$juz] ?? null;

                $pct = $tahap
                    ? ($tahapWeight[$tahap] ?? 0)
                    : 0;


                /*
            |--------------------------------------------------------------------------
            | Enterprise status logic
            |--------------------------------------------------------------------------
            */

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
                    'tahap' => $tahap,
                ];
            });


        /*
    |--------------------------------------------------------------------------
    | Overall progress
    |--------------------------------------------------------------------------
    */

        $overallPct = round(
            $progressPerJuz
                ->avg('pct') ?? 0
        );


        /*
    |--------------------------------------------------------------------------
    | Return view
    |--------------------------------------------------------------------------
    */

        return view('musyrif.santri.detail', [

            'santri' => $santri,

            'progressPerJuz' => $progressPerJuz,

            'overallPct' => $overallPct,

            'totalSetor' => $totalSetor,

            'totalAlpha' => $totalAlpha,

            'totalHadirTidakSetor' => $totalHadirTidakSetor,

            'avgNilai' => $avgNilai,

        ]);
    }


    public function timeline(Request $request, Santri $santri)
    {
        if (!$request->ajax())
            abort(404);

        $musyrif = Musyrif::where('user_id', auth()->id())->firstOrFail();
        if ((int) $santri->musyrif_id !== (int) $musyrif->id)
            abort(403);

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
                default => '-',
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
