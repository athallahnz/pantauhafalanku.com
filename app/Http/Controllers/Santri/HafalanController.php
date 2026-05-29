<?php

namespace App\Http\Controllers\Santri;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\Tahsin;
use App\Models\Tilawah;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class HafalanController extends Controller
{
    public function index()
    {
        $santri = auth()->user()->santri;

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        // ==========================================
        // 1. DATA HAFALAN
        // ==========================================
        $totalSetor = $this->countByStatus(Hafalan::class, $santri->id, ['lulus', 'ulang']);
        $totalAlpha = $this->countByStatus(Hafalan::class, $santri->id, 'alpha');
        $totalSakit = $this->countByStatus(Hafalan::class, $santri->id, 'sakit');
        $totalIzin  = $this->countByStatus(Hafalan::class, $santri->id, 'izin');

        // Nama variabel utama yang dipakai Blade.
        $totalHadirTidakSetor = $this->countByStatus(Hafalan::class, $santri->id, 'hadir_tidak_setor');

        // Alias agar file PDF / view lama yang masih memakai totalHTS tetap aman.
        $totalHTS = $totalHadirTidakSetor;

        $avgNilai = Hafalan::where('santri_id', $santri->id)
            ->whereRaw($this->statusInSql(['lulus', 'ulang']), ['lulus', 'ulang'])
            ->get()
            ->avg(function ($item) {
                return match (strtolower(trim($item->nilai_label ?? ''))) {
                    'mumtaz' => 95,
                    'jayyid_jiddan' => 85,
                    'jayyid' => 75,
                    'mardud' => 65,
                    default => 0,
                };
            });

        $avgNilai = round($avgNilai ?? 0, 1);

        // Progress Hafalan per Juz berdasarkan tahap tertinggi yang sudah LULUS.
        $tahapRank = [
            'harian' => 1,
            'tahap_1' => 2,
            'tahap_2' => 3,
            'tahap_3' => 4,
            'ujian_akhir' => 5,
        ];

        $tahapWeight = [
            'harian' => 20,
            'tahap_1' => 40,
            'tahap_2' => 60,
            'tahap_3' => 80,
            'ujian_akhir' => 100,
        ];

        $tahapPerJuz = Hafalan::join('hafalan_templates', 'hafalan_templates.id', '=', 'hafalans.hafalan_template_id')
            ->where('hafalans.santri_id', $santri->id)
            ->whereRaw('LOWER(TRIM(hafalans.status)) = ?', ['lulus'])
            ->select('hafalan_templates.juz', 'hafalan_templates.tahap')
            ->get()
            ->groupBy(fn($row) => (int) $row->juz)
            ->map(function ($rows) use ($tahapRank) {
                $highest = $rows
                    ->sortByDesc(fn($row) => $tahapRank[strtolower(trim($row->tahap ?? ''))] ?? 0)
                    ->first();

                $tahap = strtolower(trim($highest->tahap ?? ''));

                return $tahap !== '' ? $tahap : null;
            });

        $progressPerJuz = collect(range(1, 30))->map(function ($juz) use ($tahapPerJuz, $tahapWeight) {
            $tahap = $tahapPerJuz->get($juz);
            $pct = $tahap ? ($tahapWeight[$tahap] ?? 0) : 0;

            $config = match (true) {
                $pct >= 100 => ['status' => 'Selesai', 'color' => 'success'],
                $pct >= 80 => ['status' => 'Tahap 3', 'color' => 'info'],
                $pct >= 60 => ['status' => 'Tahap 2', 'color' => 'primary'],
                $pct >= 40 => ['status' => 'Tahap 1', 'color' => 'warning'],
                $pct > 0 => ['status' => 'Harian', 'color' => 'secondary'],
                default => ['status' => 'Belum mulai', 'color' => 'light'],
            };

            return array_merge([
                'juz' => $juz,
                'pct' => $pct,
                'tahap' => $tahap,
            ], $config);
        })->values();

        $overallPct = round($progressPerJuz->avg('pct') ?? 0);

        // ==========================================
        // 2. DATA TAHSIN
        // ==========================================
        $tahsinHadir = $this->countByStatus(Tahsin::class, $santri->id, 'hadir');
        $tahsinIzin  = $this->countByStatus(Tahsin::class, $santri->id, 'izin');
        $tahsinSakit = $this->countByStatus(Tahsin::class, $santri->id, 'sakit');
        $tahsinAlpha = $this->countByStatus(Tahsin::class, $santri->id, 'alpha');

        $lastTahsin = Tahsin::where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->first();

        $bukuMap = [
            'ummi_1' => 40,
            'ummi_2' => 40,
            'ummi_3' => 40,
            'gharib_1' => 28,
            'gharib_2' => 28,
            'tajwid' => 50,
        ];

        $maxPages = Tahsin::where('santri_id', $santri->id)
            ->whereRaw('LOWER(TRIM(status)) = ?', ['hadir'])
            ->selectRaw('LOWER(TRIM(buku)) as buku_key, MAX(halaman) as max_halaman')
            ->groupByRaw('LOWER(TRIM(buku))')
            ->pluck('max_halaman', 'buku_key');

        $progressPerBuku = collect($bukuMap)->map(function ($max, $key) use ($maxPages) {
            $current = (int) ($maxPages[$key] ?? 0);
            $pct = $max > 0 ? min(100, round(($current / $max) * 100)) : 0;

            return [
                'key' => $key,
                'label' => strtoupper(str_replace('_', ' ', $key)),
                'max' => $max,
                'current' => $current,
                'pct' => $pct,
                'status' => $pct >= 100 ? 'Selesai' : ($pct > 0 ? 'Berjalan' : 'Belum'),
                'color' => $pct >= 100 ? 'success' : 'primary',
            ];
        })->values();

        $overallTahsinPct = round($progressPerBuku->avg('pct') ?? 0);

        // ==========================================
        // 3. DATA TILAWAH
        // ==========================================
        $tilawahHadir = $this->countByStatus(Tilawah::class, $santri->id, 'hadir');
        $tilawahIzin  = $this->countByStatus(Tilawah::class, $santri->id, 'izin');
        $tilawahSakit = $this->countByStatus(Tilawah::class, $santri->id, 'sakit');
        $tilawahAlpha = $this->countByStatus(Tilawah::class, $santri->id, 'alpha');

        $maxJuz = (int) (Tilawah::join('hafalan_templates', 'tilawahs.hafalan_template_id', '=', 'hafalan_templates.id')
            ->where('tilawahs.santri_id', $santri->id)
            ->max('hafalan_templates.juz') ?? 0);

        $tilawahPct = min(100, round(($maxJuz / 30) * 100));

        $lastTilawah = Tilawah::with('template')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal')
            ->first();

        return view('santri.hafalan.index', compact(
            'santri',
            'totalSetor',
            'totalAlpha',
            'totalSakit',
            'totalIzin',
            'totalHadirTidakSetor',
            'totalHTS',
            'avgNilai',
            'progressPerJuz',
            'overallPct',
            'tahsinHadir',
            'tahsinIzin',
            'tahsinSakit',
            'tahsinAlpha',
            'lastTahsin',
            'progressPerBuku',
            'overallTahsinPct',
            'tilawahHadir',
            'tilawahIzin',
            'tilawahSakit',
            'tilawahAlpha',
            'lastTilawah',
            'tilawahPct'
        ));
    }

    /*
     * ====================================================
     * TIMELINE & DATATABLES RESPONSES
     * ====================================================
     */
    public function timeline(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $santri = auth()->user()->santri;

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        $query = Hafalan::query()
            ->leftJoin('hafalan_templates', 'hafalan_templates.id', '=', 'hafalans.hafalan_template_id')
            ->where('hafalans.santri_id', $santri->id)
            ->select(
                'hafalans.*',
                'hafalan_templates.juz as template_juz',
                'hafalan_templates.label as template_label'
            );

        if ($request->filled('start_date')) {
            $query->whereDate('hafalans.tanggal_setoran', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('hafalans.tanggal_setoran', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(hafalans.tanggal_setoran, '%d-%m-%Y') LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('juz', function ($query, $keyword) {
                $query->where('hafalan_templates.juz', 'like', "%{$keyword}%");
            })
            ->filterColumn('surah_ayat', function ($query, $keyword) {
                $query->where('hafalan_templates.label', 'like', "%{$keyword}%");
            })
            ->addColumn('tanggal', function ($row) {
                return $row->tanggal_setoran ? Carbon::parse($row->tanggal_setoran)->format('d-m-Y') : '-';
            })
            ->addColumn('juz', fn($row) => $row->template_juz ?? '-')
            ->addColumn('surah_ayat', fn($row) => $row->template_label ?? '-')
            ->addColumn('nilai', fn($row) => $this->nilaiArab($row->nilai_label ?? null))
            ->addColumn('status', fn($row) => $this->statusBadge($row->status ?? null, [
                'lulus' => 'bg-success',
                'ulang' => 'bg-warning text-dark',
                'hadir_tidak_setor' => 'bg-info text-dark',
                'sakit' => 'bg-primary',
                'izin' => 'bg-secondary',
                'alpha' => 'bg-danger',
            ]))
            ->addColumn('catatan', fn($row) => $row->catatan ?? '-')
            ->rawColumns(['status'])
            ->make(true);
    }

    public function tahsinTimeline(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $santri = auth()->user()->santri;

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        $query = Tahsin::where('santri_id', $santri->id);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn($row) => $row->tanggal ? Carbon::parse($row->tanggal)->format('d-m-Y') : '-')
            ->addColumn('buku_label', fn($row) => strtoupper(str_replace('_', ' ', $row->buku ?? '-')))
            ->addColumn('nilai', fn($row) => $this->nilaiArab($row->nilai_label ?? $row->nilai ?? null))
            ->addColumn('status', fn($row) => $this->statusBadge($row->status ?? null, [
                'hadir' => 'bg-success',
                'sakit' => 'bg-primary',
                'izin' => 'bg-secondary',
                'alpha' => 'bg-danger',
            ]))
            ->addColumn('catatan', fn($row) => $row->catatan ?? '-')
            ->rawColumns(['status'])
            ->make(true);
    }

    public function tilawahTimeline(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $santri = auth()->user()->santri;

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        $query = Tilawah::with('template')
            ->where('santri_id', $santri->id);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn($row) => $row->tanggal ? Carbon::parse($row->tanggal)->format('d-m-Y') : '-')
            ->addColumn('target_bacaan', function ($row) {
                return $row->template
                    ? 'Juz ' . $row->template->juz . ' - ' . $row->template->label
                    : '-';
            })
            ->addColumn('nilai', fn($row) => $this->nilaiArab($row->nilai_label ?? $row->nilai ?? null))
            ->addColumn('status', fn($row) => $this->statusBadge($row->status ?? null, [
                'hadir' => 'bg-success',
                'sakit' => 'bg-primary',
                'izin' => 'bg-secondary',
                'alpha' => 'bg-danger',
            ]))
            ->addColumn('catatan', fn($row) => $row->catatan ?? '-')
            ->rawColumns(['status'])
            ->make(true);
    }

    /*
     * ====================================================
     * EXPORT PDF
     * ====================================================
     */
    public function exportPdf(Request $request)
    {
        $santri = auth()->user()->santri;

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        $query = Hafalan::with('template')
            ->where('santri_id', $santri->id)
            ->orderByDesc('tanggal_setoran');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_setoran', [$request->start_date, $request->end_date]);
            $periode = Carbon::parse($request->start_date)->format('d/m/Y') . ' s/d ' . Carbon::parse($request->end_date)->format('d/m/Y');
        } else {
            $periode = 'Semua Riwayat';
        }

        $timeline = $query->get();

        $statusCounts = $timeline
            ->groupBy(fn($item) => strtolower(trim($item->status ?? '')))
            ->map->count();

        $data = [
            'santri' => $santri,
            'timeline' => $timeline,
            'totalSetor' => ($statusCounts['lulus'] ?? 0) + ($statusCounts['ulang'] ?? 0),
            'totalAlpha' => $statusCounts['alpha'] ?? 0,
            'totalSakit' => $statusCounts['sakit'] ?? 0,
            'totalIzin' => $statusCounts['izin'] ?? 0,
            'totalHadirTidakSetor' => $statusCounts['hadir_tidak_setor'] ?? 0,
            'totalHTS' => $statusCounts['hadir_tidak_setor'] ?? 0,
            'periode' => $periode,
            'tanggal_cetak' => now()->translatedFormat('d F Y'),
        ];

        $pdf = Pdf::loadView('santri.hafalan.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan_Hafalan_' . str_replace(' ', '_', $santri->nama) . '.pdf');
    }

    private function countByStatus(string $modelClass, int|string $santriId, string|array $statuses): int
    {
        $statuses = collect((array) $statuses)
            ->map(fn($status) => strtolower(trim($status)))
            ->filter()
            ->values()
            ->all();

        if (empty($statuses)) {
            return 0;
        }

        return $modelClass::where('santri_id', $santriId)
            ->whereRaw($this->statusInSql($statuses), $statuses)
            ->count();
    }

    private function statusInSql(array $statuses): string
    {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));

        return "LOWER(TRIM(status)) IN ({$placeholders})";
    }

    private function nilaiArab(?string $nilai): string
    {
        return match (strtolower(trim($nilai ?? ''))) {
            'mumtaz' => 'ممتاز',
            'jayyid_jiddan' => 'جيد جدًا',
            'jayyid' => 'جيد',
            'mardud' => 'مردود',
            default => $nilai ? strtoupper($nilai) : '-',
        };
    }

    private function statusBadge(?string $status, array $badgeMap): string
    {
        $statusClean = strtolower(trim($status ?? ''));
        $badge = $badgeMap[$statusClean] ?? 'bg-secondary';
        $label = $statusClean !== '' ? ucwords(str_replace('_', ' ', $statusClean)) : '-';

        return '<span class="badge ' . $badge . '">' . e($label) . '</span>';
    }
}
