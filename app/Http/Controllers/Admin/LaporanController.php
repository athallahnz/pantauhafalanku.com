<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Hafalan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    private function sqlNilaiLabelToAngka(string $table = ''): string
    {
        $prefix = $table ? $table . '.' : '';

        return "CASE
        WHEN {$prefix}nilai_label = 'mumtaz' THEN 95
        WHEN {$prefix}nilai_label = 'jayyid_jiddan' THEN 85
        WHEN {$prefix}nilai_label = 'jayyid' THEN 75
        WHEN {$prefix}nilai_label = 'mardud' THEN 65
        ELSE NULL
    END";
    }

    public function index()
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $musyrifList = Musyrif::orderBy('nama')->get();
        $defaultPeriode = now()->format('Y-m');

        return view('admin.laporan.index', compact('kelasList', 'musyrifList', 'defaultPeriode'));
    }

    /**
     * Riwayat hafalan per santri (untuk modal Detail)
     */
    public function getRiwayatSantri(Request $request, $id)
    {
        try {
            $periode = $request->input('periode');
            [$startDate, $endDate] = $this->getRangeFromPeriode($periode);

            // Ambil santri + relasi (kalau ada)
            $santri = Santri::with(['kelas', 'musyrif'])->findOrFail($id);

            $query = Hafalan::with('template')
                ->where('santri_id', $santri->id)
                ->orderBy('tanggal_setoran', 'desc');

            if ($startDate && $endDate) {
                $query->whereBetween('tanggal_setoran', [$startDate, $endDate]);
            }

            $list = $query->get();

            $riwayat = $list->map(function ($item) {
                $tanggal = $item->tanggal_setoran ?? optional($item->created_at)->toDateString();

                return [
                    'tanggal_setoran' => Carbon::parse($tanggal)->translatedFormat('d F Y'),
                    'materi' => $item->template?->label
                        ?? $item->rentang_ayat_label
                        ?? '-', // fallback terakhir
                    'status' => $item->status,
                    'nilai_label' => $item->nilai_label,
                    'catatan' => $item->catatan ?? '',
                ];
            });

            return response()->json([
                'santri' => [
                    'id' => $santri->id,
                    'nama' => $santri->nama,
                    'kelas' => $santri->kelas->nama_kelas ?? '-',
                    'musyrif' => $santri->musyrif->nama ?? '-',
                ],
                'riwayat' => $riwayat,
            ]);
        } catch (\Throwable $e) {
            // Log supaya kalau masih error, gampang dilihat di storage/logs/laravel.log
            Log::error('Gagal ambil riwayat hafalan santri', [
                'santri_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data riwayat.',
            ], 500);
        }
    }

    /**
     * Rekap per Santri (server-side DataTables)
     */

    public function getRekapSantri(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $kelasId = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode = $request->input('periode'); // YYYY-MM

        [$startDate, $endDate] = $this->getRangeFromPeriode($periode);

        // Query dasar santri (untuk filter & summary)
        $baseQuery = Santri::query()
            ->when($kelasId, fn($q) => $q->where('kelas_id', $kelasId))
            ->when($musyrifId, fn($q) => $q->where('musyrif_id', $musyrifId));

        // Summary (tanpa pagination)
        $summaryQuery = clone $baseQuery;
        $santriIds = $summaryQuery->pluck('id');
        $totalSantri = $santriIds->count();

        $hafalanSummary = Hafalan::whereIn('santri_id', $santriIds);

        if ($startDate && $endDate) {
            $hafalanSummary->whereBetween('tanggal_setoran', [$startDate, $endDate]);
        }

        $totalSetor = (clone $hafalanSummary)
            ->whereIn('status', ['lulus', 'ulang'])
            ->count();

        $totalHadirTidakSetor = (clone $hafalanSummary)
            ->where('status', 'hadir_tidak_setor')
            ->count();

        $totalAlpha = (clone $hafalanSummary)
            ->where('status', 'alpha')
            ->count();

        $avgNilai = (clone $hafalanSummary)
            ->whereIn('status', ['lulus', 'ulang'])
            ->selectRaw("AVG(" . $this->sqlNilaiLabelToAngka('hafalans') . ") as avg_nilai")
            ->value('avg_nilai');

        $avgNilai = $avgNilai ? round($avgNilai, 2) : 0;

        // ===== TAMBAHAN UNTUK KPI MUSYRIF =====
        // Hitung total musyrif sesuai filter
        $totalMusyrif = Musyrif::when($musyrifId, fn($q) => $q->where('id', $musyrifId))->count();

        // Hitung persentase kehadiran (Valid / Total Absensi) di periode tersebut
        $attendanceQuery = DB::table('musyrif_attendances')
            ->when($musyrifId, fn($q) => $q->where('musyrif_id', $musyrifId));

        if ($startDate && $endDate) {
            $attendanceQuery->whereBetween('attendance_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        $totalAttendances = $attendanceQuery->count();
        $validAttendances = (clone $attendanceQuery)->where('status', 'valid')->count();
        $kehadiranMusyrif = $totalAttendances > 0 ? round(($validAttendances / $totalAttendances) * 100) : 0;
        // ======================================

        $hafalanAgg = Hafalan::query()
            ->select(
                'santri_id',

                // Total setoran valid
                DB::raw("
                    SUM(
                        CASE
                            WHEN status IN ('lulus', 'ulang')
                            THEN 1
                            ELSE 0
                        END
                    ) as total_setor
                "),

                // Hadir tidak setor
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'hadir_tidak_setor'
                            THEN 1
                            ELSE 0
                        END
                    ) as hadir_tidak_setor
                "),

                // Alpha
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'alpha'
                            THEN 1
                            ELSE 0
                        END
                    ) as alpha
                "),

                // Izin
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'izin'
                            THEN 1
                            ELSE 0
                        END
                    ) as izin
                "),

                // Sakit
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'sakit'
                            THEN 1
                            ELSE 0
                        END
                    ) as sakit
                "),

                // Rata-rata nilai
                DB::raw("
                    ROUND(
                        AVG(
                            CASE
                                WHEN status = 'lulus'
                                THEN
                                    CASE
                                        WHEN nilai_label = 'mumtaz' THEN 95
                                        WHEN nilai_label = 'jayyid_jiddan' THEN 85
                                        WHEN nilai_label = 'jayyid' THEN 75
                                        WHEN nilai_label = 'mardud' THEN 65
                                        ELSE NULL
                                    END
                                ELSE NULL
                            END
                        ),
                    2)
                as rata_nilai
                ")
            )
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('tanggal_setoran', [$startDate, $endDate]))
            ->groupBy('santri_id');

        $dataQuery = Santri::with(['kelas', 'musyrif'])
            ->leftJoinSub($hafalanAgg, 'h', 'h.santri_id', '=', 'santris.id')
            ->select(
                'santris.*',
                'h.total_setor',
                'h.hadir_tidak_setor',
                'h.alpha',
                'h.izin',
                'h.sakit',
                'h.rata_nilai'
            )
            ->when($kelasId, fn($q) => $q->where('santris.kelas_id', $kelasId))
            ->when($musyrifId, fn($q) => $q->where('santris.musyrif_id', $musyrifId));

        return DataTables::of($dataQuery)
            ->addIndexColumn()
            ->addColumn('kelas', function ($row) {
                return $row->kelas->nama_kelas ?? '-';
            })
            ->addColumn('musyrif', function ($row) {
                return $row->musyrif->nama ?? '-';
            })
            ->addColumn('nama_santri', function ($row) {
                return $row->nama ?? '-';
            })
            ->editColumn('total_setor', fn($r) => (int) ($r->total_setor ?? 0))
            ->editColumn('hadir_tidak_setor', fn($row) => (int) ($row->hadir_tidak_setor ?? 0))
            ->editColumn('alpha', fn($row) => (int) ($row->alpha ?? 0))
            ->editColumn('sakit', fn($row) => (int) ($row->sakit ?? 0))
            ->editColumn('izin', fn($row) => (int) ($row->izin ?? 0))
            ->editColumn('rata_nilai', function ($row) {
                return is_null($row->rata_nilai) ? '-' : number_format($row->rata_nilai, 2);
            })

            ->addColumn('aksi', function ($row) {
                $btn = '<button type="button" class="btn btn-sm btn-primary btn-detail-santri"
                        data-id="' . $row->id . '"
                        data-nama="' . e($row->nama) . '">
                        <i class="bi bi-eye" data-toggle="tooltip" data-placement="top" title="Lihat Detail"></i>
                    </button>';
                return $btn;
            })
            ->rawColumns(['aksi'])
            ->with([
                'summary' => [
                    'total_santri' => $totalSantri,
                    'total_setor' => $totalSetor,
                    'hadir_tidak_setor' => $totalHadirTidakSetor,
                    'alpha' => $totalAlpha,
                    'avg_nilai' => $avgNilai,
                    // Tambahkan 2 key ini untuk dikirim ke Frontend
                    'total_musyrif'     => $totalMusyrif,
                    'kehadiran_musyrif' => $kehadiranMusyrif,
                ]
            ])
            ->make(true);
    }

    /**
     * Rekap per Kelas
     */
    public function getRekapKelas(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $kelasId   = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode   = $request->input('periode');

        [$startDate, $endDate] = $this->getRangeFromPeriode($periode);
        $hafalanAgg = DB::table('hafalans')
            ->select(
                'santri_id',

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'lulus'
                            THEN 1
                            ELSE 0
                        END
                    ) as total_setor
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'hadir_tidak_setor'
                            THEN 1
                            ELSE 0
                        END
                    ) as hadir_tidak_setor
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'sakit'
                            THEN 1
                            ELSE 0
                        END
                    ) as sakit
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'izin'
                            THEN 1
                            ELSE 0
                        END
                    ) as izin
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'alpha'
                            THEN 1
                            ELSE 0
                        END
                    ) as alpha
                "),

                DB::raw("
                    ROUND(
                        AVG(
                            CASE
                                WHEN status = 'lulus'
                                THEN
                                    CASE
                                        WHEN nilai_label = 'mumtaz' THEN 95
                                        WHEN nilai_label = 'jayyid_jiddan' THEN 85
                                        WHEN nilai_label = 'jayyid' THEN 75
                                        WHEN nilai_label = 'mardud' THEN 65
                                        ELSE NULL
                                    END
                            END
                        ),
                    2) as rata_nilai
                ")
            )

            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_setoran', [$startDate, $endDate]);
            })

            ->groupBy('santri_id');

        $query = Kelas::select(
            'kelas.id',
            'kelas.nama_kelas',

            DB::raw('COUNT(DISTINCT santris.id) as jumlah_santri'),

            DB::raw('SUM(h.total_setor) as total_setor'),
            DB::raw('SUM(h.hadir_tidak_setor) as hadir_tidak_setor'),
            DB::raw('SUM(h.sakit) as sakit'),
            DB::raw('SUM(h.izin) as izin'),
            DB::raw('SUM(h.alpha) as alpha'),

            DB::raw('ROUND(AVG(h.rata_nilai),2) as rata_nilai')
        )

            ->leftJoin('santris', 'santris.kelas_id', '=', 'kelas.id')

            ->leftJoinSub($hafalanAgg, 'h', function ($join) {
                $join->on('h.santri_id', '=', 'santris.id');
            })

            ->when($kelasId, fn($q) => $q->where('kelas.id', $kelasId))

            ->when(
                $musyrifId,
                fn($q) =>
                $q->where('santris.musyrif_id', $musyrifId)
            )

            ->groupBy('kelas.id', 'kelas.nama_kelas');

        return DataTables::of($query)

            ->addIndexColumn()

            ->editColumn(
                'jumlah_santri',
                fn($row) =>
                (int) $row->jumlah_santri
            )

            ->editColumn(
                'total_setor',
                fn($row) =>
                (int) $row->total_setor
            )

            ->editColumn(
                'hadir_tidak_setor',
                fn($row) =>
                (int) $row->hadir_tidak_setor
            )

            ->editColumn('sakit', fn($row) => (int) ($row->sakit ?? 0))

            ->editColumn('izin', fn($row) => (int) ($row->izin ?? 0))

            ->editColumn(
                'alpha',
                fn($row) =>
                (int) $row->alpha
            )

            ->editColumn(
                'rata_nilai',
                fn($row) =>
                is_null($row->rata_nilai)
                    ? '-'
                    : number_format($row->rata_nilai, 2)
            )

            ->make(true);
    }

    /**
     * Rekap per Musyrif
     */
    public function getRekapMusyrif(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $kelasId = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode = $request->input('periode');

        [$startDate, $endDate] = $this->getRangeFromPeriode($periode);

        $hafalanAgg = DB::table('hafalans')
            ->select(
                'santri_id',

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'lulus'
                            THEN 1
                            ELSE 0
                        END
                    ) as total_setor
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'hadir_tidak_setor'
                            THEN 1
                            ELSE 0
                        END
                    ) as hadir_tidak_setor
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'sakit'
                            THEN 1
                            ELSE 0
                        END
                    ) as sakit
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'izin'
                            THEN 1
                            ELSE 0
                        END
                    ) as izin
                "),

                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'alpha'
                            THEN 1
                            ELSE 0
                        END
                    ) as alpha
                "),

                DB::raw("
                    ROUND(
                        AVG(
                            CASE
                                WHEN status = 'lulus'
                                THEN
                                    CASE
                                        WHEN nilai_label = 'mumtaz' THEN 95
                                        WHEN nilai_label = 'jayyid_jiddan' THEN 85
                                        WHEN nilai_label = 'jayyid' THEN 75
                                        WHEN nilai_label = 'mardud' THEN 65
                                        ELSE NULL
                                    END
                            END
                        ),
                    2) as rata_nilai
                ")
            )

            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween('tanggal_setoran', [$startDate, $endDate]);
            })

            ->groupBy('santri_id');

        $query = Musyrif::select('musyrifs.id', 'musyrifs.nama', DB::raw('COUNT(DISTINCT santris.id) as jumlah_santri'), DB::raw('COALESCE(SUM(h.total_setor),0) as total_setor'), DB::raw('COALESCE(SUM(h.hadir_tidak_setor),0) as hadir_tidak_setor'), DB::raw('COALESCE(SUM(h.sakit),0) as sakit'), DB::raw('COALESCE(SUM(h.izin),0) as izin'), DB::raw('COALESCE(SUM(h.alpha),0) as alpha'), DB::raw('ROUND(AVG(h.rata_nilai),2) as rata_nilai'))->leftJoin('santris', 'santris.musyrif_id', '=', 'musyrifs.id')->leftJoinSub($hafalanAgg, 'h', function ($join) {
            $join->on('h.santri_id', '=', 'santris.id');
        })->when($musyrifId, function ($q) use ($musyrifId) {
            $q->where('musyrifs.id', $musyrifId);
        })->when($kelasId, function ($q) use ($kelasId) {
            $q->where('santris.kelas_id', $kelasId);
        })->groupBy('musyrifs.id', 'musyrifs.nama');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('jumlah_santri', fn($row) => (int) ($row->jumlah_santri ?? 0))
            ->editColumn('total_setor', fn($row) => (int) ($row->total_setor ?? 0))
            ->editColumn('sakit', fn($row) => (int) ($row->sakit ?? 0))
            ->editColumn('izin', fn($row) => (int) ($row->izin ?? 0))
            ->editColumn('rata_nilai', function ($row) {
                if (is_null($row->rata_nilai)) {
                    return '-';
                }
                return number_format($row->rata_nilai, 2);
            })
            ->make(true);
    }

    // Chart data endpoints
    public function getChartKelas(Request $request)
    {
        $kelasId   = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode   = $request->input('periode');

        [$startDate, $endDate] = $this->getRangeFromPeriode($periode);

        $query = Kelas::select(
            'kelas.id',
            'kelas.nama_kelas',

            DB::raw("
            COALESCE(SUM(
                CASE
                    WHEN hafalans.status IN ('lulus')
                    THEN 1 ELSE 0
                END
            ),0) as total_setor
        ")
        )
            ->leftJoin('santris', 'santris.kelas_id', '=', 'kelas.id')
            ->leftJoin('hafalans', 'hafalans.santri_id', '=', 'santris.id')

            ->when(
                $kelasId,
                fn($q) =>
                $q->where('kelas.id', $kelasId)
            )

            ->when(
                $musyrifId,
                fn($q) =>
                $q->where('santris.musyrif_id', $musyrifId)
            )

            ->when(
                $startDate && $endDate,
                fn($q) =>
                $q->whereBetween('hafalans.tanggal_setoran', [$startDate, $endDate])
            )

            ->groupBy('kelas.id', 'kelas.nama_kelas')

            ->orderBy('kelas.nama_kelas');

        $list = $query->get();

        return response()->json([

            'labels' => $list->pluck('nama_kelas'),

            'data' => $list->pluck('total_setor')
                ->map(fn($v) => (int) $v),

        ]);
    }

    public function getChartMusyrif(Request $request)
    {
        $kelasId   = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode   = $request->input('periode');

        [$startDate, $endDate] = $this->getRangeFromPeriode($periode);

        $query = Musyrif::select(
            'musyrifs.id',
            'musyrifs.nama',

            DB::raw("
            COUNT(DISTINCT santris.id) as jumlah_santri
        "),

            DB::raw("
            COALESCE(
                SUM(
                    CASE
                        WHEN hafalans.status IN ('lulus')
                        THEN 1 ELSE 0
                    END
                ), 0
            ) as total_setoran
        ")
        )

            ->leftJoin('santris', function ($join) use ($kelasId) {

                $join->on('santris.musyrif_id', '=', 'musyrifs.id');

                if ($kelasId) {
                    $join->where('santris.kelas_id', $kelasId);
                }
            })

            ->leftJoin('hafalans', function ($join) use ($startDate, $endDate) {

                $join->on('hafalans.santri_id', '=', 'santris.id');

                if ($startDate && $endDate) {
                    $join->whereBetween('hafalans.tanggal_setoran', [$startDate, $endDate]);
                }
            })

            ->when(
                $musyrifId,
                fn($q) =>
                $q->where('musyrifs.id', $musyrifId)
            )

            ->groupBy('musyrifs.id', 'musyrifs.nama')

            ->orderBy('musyrifs.nama');

        $list = $query->get();

        return response()->json([
            'labels' => $list->pluck('nama')->values(),
            'data'   => $list->pluck('total_setoran')
                ->map(fn($v) => (int) $v)
                ->values(),
        ]);
    }

    public function getChartJuzLulus(Request $request)
    {
        $kelasId = $request->query('kelas_id'); // nullable

        $q = DB::table('hafalans as h')
            ->join('hafalan_templates as ht', 'ht.id', '=', 'h.hafalan_template_id')
            ->join('santris as s', 's.id', '=', 'h.santri_id')
            ->where('ht.tahap', 'ujian_akhir')
            ->where('h.status', 'lulus')
            ->whereBetween('ht.juz', [1, 30]);

        if (!empty($kelasId)) {
            $q->where('s.kelas_id', $kelasId);
        }

        $rows = $q->select('ht.juz', DB::raw('COUNT(DISTINCT h.santri_id) as jumlah'))
            ->groupBy('ht.juz')
            ->orderBy('ht.juz')
            ->get();

        $map = $rows->pluck('jumlah', 'juz'); // juz => jumlah

        $labels = range(1, 30);
        $data = array_map(fn($j) => (int) ($map[$j] ?? 0), $labels);

        return response()->json([
            'labels' => array_map(fn($j) => "Juz $j", $labels),
            'data' => $data,
        ]);
    }

    /**
     * Helper: Parse periode (YYYY-MM) jadi range tanggal
     */
    private function getRangeFromPeriode($periode)
    {
        if (!$periode)
            return [null, null];

        try {

            $start = \Carbon\Carbon::createFromFormat(
                'Y-m',
                $periode
            )->startOfMonth();

            $end = \Carbon\Carbon::createFromFormat(
                'Y-m',
                $periode
            )->endOfMonth();

            return [
                $start->toDateString(),
                $end->toDateString()
            ];
        } catch (\Exception $e) {

            return [null, null];
        }
    }

    /**
     * Rekap Histori Absensi Musyrif (server-side DataTables)
     */
    public function getAbsensiMusyrif(Request $request)
    {
        if (!$request->ajax()) {
            abort(404);
        }

        $musyrifId    = $request->input('musyrif_id');
        $periode      = $request->input('periode');
        $waktuAbsensi = $request->input('waktu_absensi'); // 'today', 'periode', 'all'

        $query = DB::table('musyrif_attendances as ma')
            ->join('musyrifs as m', 'm.id', '=', 'ma.musyrif_id')
            ->select(
                'ma.id',
                'ma.attendance_at',
                'ma.type',
                'ma.status',
                'ma.latitude',
                'ma.longitude',
                'ma.address_text',
                'ma.accuracy',
                'ma.photo_path',
                'm.nama as musyrif_nama'
            )
            ->when($musyrifId, fn($q) => $q->where('ma.musyrif_id', $musyrifId));

        // Logic Filter Waktu Khusus Absensi
        if ($waktuAbsensi === 'today') {
            // Gunakan Carbon untuk mengambil tanggal hari ini
            $query->whereDate('ma.attendance_at', Carbon::today());
        } elseif ($waktuAbsensi === 'all') {
            // Biarkan kosong, tidak ada filter tanggal sama sekali (Semua Riwayat ditarik)
        } else {
            // Default: 'periode' -> Ikuti filter bulan global di halaman Laporan
            [$startDate, $endDate] = $this->getRangeFromPeriode($periode);
            if ($startDate && $endDate) {
                $query->whereBetween('ma.attendance_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);
            }
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('attendance_at', function ($row) {
                return Carbon::parse($row->attendance_at)->translatedFormat('d M Y, H:i');
            })
            ->editColumn('type', function ($row) {
                if ($row->type === 'morning') {
                    return '<span class="badge bg-info text-dark rounded-pill px-3"><i class="bi bi-brightness-alt-high-fill me-1"></i> Pagi</span>';
                }
                return '<span class="badge bg-warning text-dark rounded-pill px-3"><i class="bi bi-moon-stars-fill me-1"></i> Sore</span>';
            })
            ->addColumn('location', function ($row) {
                $latlng = "{$row->latitude},{$row->longitude}";
                // Format link yang benar
                $gmapsLink = "https://www.google.com/maps?q={$latlng}";
                $addr = \Illuminate\Support\Str::limit($row->address_text, 35);

                return "
                    <div class='mb-1'>" . e($addr) . "</div>
                    <div class='d-flex gap-2 align-items-center mt-1'>
                        <a href='{$gmapsLink}' target='_blank' class='text-decoration-none small fw-semibold'>
                            <i class='bi bi-geo-alt text-danger'></i> {$latlng}
                        </a>
                        <button class='btn btn-sm btn-outline-secondary py-0 px-2 btn-preview-map'
                            data-lat='{$row->latitude}' data-lng='{$row->longitude}'>
                            <i class='bi bi-map'></i>
                        </button>
                    </div>
                ";
            })
            ->editColumn('status', function ($row) {
                if ($row->status == 'valid') return '<span class="badge bg-success">Valid</span>';
                if ($row->status == 'suspect') return '<span class="badge bg-warning text-dark">Suspect</span>';
                return '<span class="badge bg-danger">Rejected</span>';
            })
            ->addColumn('photo', function ($row) {
                if ($row->photo_path) {
                    $url = asset('storage/' . $row->photo_path);
                    return "
                    <button type='button' class='btn btn-sm btn-outline-primary btn-preview-photo' data-url='{$url}'>
                        <i class='bi bi-image me-1'></i> Foto
                    </button>
                    <div class='mt-1 small text-muted'>Akurasi: {$row->accuracy}m</div>
                ";
                }
                return "<span class='text-muted small'>Tidak ada foto</span>";
            })
            // JANGAN LUPA tambahkan 'type' ke rawColumns
            ->rawColumns(['type', 'location', 'status', 'photo'])
            ->make(true);
    }

    // Raw data fetchers for export (Excel/PDF)

    private function fetchRekapSantriRaw(Request $request)
    {
        $kelasId = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode = $request->input('periode');
        [$startDate, $endDate] = $this->getRangeFromPeriode($periode);

        $hafalanAgg = Hafalan::query()
            ->select(
                'santri_id',

                // Total setoran valid
                DB::raw("
                    SUM(
                        CASE
                            WHEN status IN ('lulus', 'ulang')
                            THEN 1
                            ELSE 0
                        END
                    ) as total_setor
                "),

                // Hadir tidak setor
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'hadir_tidak_setor'
                            THEN 1
                            ELSE 0
                        END
                    ) as hadir_tidak_setor
                "),

                // Alpha
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'alpha'
                            THEN 1
                            ELSE 0
                        END
                    ) as alpha
                "),

                // Izin
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'izin'
                            THEN 1
                            ELSE 0
                        END
                    ) as izin
                "),

                // Sakit
                DB::raw("
                    SUM(
                        CASE
                            WHEN status = 'sakit'
                            THEN 1
                            ELSE 0
                        END
                    ) as sakit
                "),

                // Rata-rata nilai
                DB::raw("
                    ROUND(
                        AVG(
                            CASE
                                WHEN status = 'lulus'
                                THEN
                                    CASE
                                        WHEN nilai_label = 'mumtaz' THEN 95
                                        WHEN nilai_label = 'jayyid_jiddan' THEN 85
                                        WHEN nilai_label = 'jayyid' THEN 75
                                        WHEN nilai_label = 'mardud' THEN 65
                                        ELSE NULL
                                    END
                                ELSE NULL
                            END
                        ),
                    2)
                as rata_nilai
                ")
            )
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('tanggal_setoran', [$startDate, $endDate]))
            ->groupBy('santri_id');

        $data = Santri::with(['kelas', 'musyrif'])
            ->leftJoinSub($hafalanAgg, 'h', 'h.santri_id', '=', 'santris.id')
            ->select(
                'santris.*',
                'h.total_setor',
                'h.hadir_tidak_setor',
                'h.alpha',
                'h.izin',
                'h.sakit',
                'h.rata_nilai'
            )
            ->when($kelasId, fn($q) => $q->where('santris.kelas_id', $kelasId))
            ->when($musyrifId, fn($q) => $q->where('santris.musyrif_id', $musyrifId))
            ->orderBy('kelas_id')
            ->orderBy('nama')
            ->get();

        return $data;
    }

    private function fetchRekapKelasRaw(Request $request)
    {
        $kelasId   = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode   = $request->input('periode');

        [$startDate, $endDate] =
            $this->getRangeFromPeriode($periode);

        /**
         * Aggregate hafalan per santri
         */
        $hafalanAgg = DB::table('hafalans')
            ->select(

                'santri_id',

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'lulus'
                        THEN 1
                        ELSE 0
                    END
                ) as total_setor
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'hadir_tidak_setor'
                        THEN 1
                        ELSE 0
                    END
                ) as hadir_tidak_setor
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'sakit'
                        THEN 1
                        ELSE 0
                    END
                ) as sakit
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'izin'
                        THEN 1
                        ELSE 0
                    END
                ) as izin
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'alpha'
                        THEN 1
                        ELSE 0
                    END
                ) as alpha
            "),

                DB::raw("
                ROUND(
                    AVG(
                        CASE
                            WHEN status = 'lulus'
                            THEN
                                CASE
                                    WHEN nilai_label = 'mumtaz' THEN 95
                                    WHEN nilai_label = 'jayyid_jiddan' THEN 85
                                    WHEN nilai_label = 'jayyid' THEN 75
                                    WHEN nilai_label = 'mardud' THEN 65
                                    ELSE NULL
                                END
                        END
                    ),
                2) as rata_nilai
            ")
            )

            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween(
                    'tanggal_setoran',
                    [$startDate, $endDate]
                );
            })

            ->groupBy('santri_id');

        return Kelas::select(

            'kelas.id',
            'kelas.nama_kelas',

            DB::raw('COUNT(DISTINCT santris.id) as jumlah_santri'),

            DB::raw('COALESCE(SUM(h.total_setor),0) as total_setor'),

            DB::raw('COALESCE(SUM(h.hadir_tidak_setor),0) as hadir_tidak_setor'),

            DB::raw('COALESCE(SUM(h.sakit),0) as sakit'),

            DB::raw('COALESCE(SUM(h.izin),0) as izin'),

            DB::raw('COALESCE(SUM(h.alpha),0) as alpha'),

            DB::raw('ROUND(AVG(h.rata_nilai),2) as rata_nilai')
        )

            ->leftJoin(
                'santris',
                'santris.kelas_id',
                '=',
                'kelas.id'
            )

            ->leftJoinSub($hafalanAgg, 'h', function ($join) {
                $join->on('h.santri_id', '=', 'santris.id');
            })

            ->when(
                $kelasId,
                fn($q) =>
                $q->where('kelas.id', $kelasId)
            )

            ->when(
                $musyrifId,
                fn($q) =>
                $q->where('santris.musyrif_id', $musyrifId)
            )

            ->groupBy(
                'kelas.id',
                'kelas.nama_kelas'
            )

            ->orderBy('kelas.nama_kelas')

            ->get();
    }

    private function fetchRekapMusyrifRaw(Request $request)
    {
        $kelasId   = $request->input('kelas_id');
        $musyrifId = $request->input('musyrif_id');
        $periode   = $request->input('periode');

        [$startDate, $endDate] =
            $this->getRangeFromPeriode($periode);

        /**
         * Aggregate hafalan per santri
         */
        $hafalanAgg = DB::table('hafalans')
            ->select(

                'santri_id',

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'lulus'
                        THEN 1
                        ELSE 0
                    END
                ) as total_setor
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'hadir_tidak_setor'
                        THEN 1
                        ELSE 0
                    END
                ) as hadir_tidak_setor
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'sakit'
                        THEN 1
                        ELSE 0
                    END
                ) as sakit
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'izin'
                        THEN 1
                        ELSE 0
                    END
                ) as izin
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'alpha'
                        THEN 1
                        ELSE 0
                    END
                ) as alpha
            "),

                DB::raw("
                ROUND(
                    AVG(
                        CASE
                            WHEN status = 'lulus'
                            THEN
                                CASE
                                    WHEN nilai_label = 'mumtaz' THEN 95
                                    WHEN nilai_label = 'jayyid_jiddan' THEN 85
                                    WHEN nilai_label = 'jayyid' THEN 75
                                    WHEN nilai_label = 'mardud' THEN 65
                                    ELSE NULL
                                END
                        END
                    ),
                2) as rata_nilai
            ")
            )

            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                $q->whereBetween(
                    'tanggal_setoran',
                    [$startDate, $endDate]
                );
            })

            ->groupBy('santri_id');

        return Musyrif::select(

            'musyrifs.id',
            'musyrifs.nama',

            DB::raw('COUNT(DISTINCT santris.id) as jumlah_santri'),

            DB::raw('COALESCE(SUM(h.total_setor),0) as total_setor'),

            DB::raw('COALESCE(SUM(h.hadir_tidak_setor),0) as hadir_tidak_setor'),

            DB::raw('COALESCE(SUM(h.sakit),0) as sakit'),

            DB::raw('COALESCE(SUM(h.izin),0) as izin'),

            DB::raw('COALESCE(SUM(h.alpha),0) as alpha'),

            DB::raw('ROUND(AVG(h.rata_nilai),2) as rata_nilai')
        )

            ->leftJoin(
                'santris',
                'santris.musyrif_id',
                '=',
                'musyrifs.id'
            )

            ->leftJoinSub($hafalanAgg, 'h', function ($join) {
                $join->on('h.santri_id', '=', 'santris.id');
            })

            ->when(
                $musyrifId,
                fn($q) =>
                $q->where('musyrifs.id', $musyrifId)
            )

            ->when(
                $kelasId,
                fn($q) =>
                $q->where('santris.kelas_id', $kelasId)
            )

            ->groupBy(
                'musyrifs.id',
                'musyrifs.nama'
            )

            ->orderBy('musyrifs.nama')

            ->get();
    }

    // Export to PDF methods

    public function exportSantriExcel(Request $request)
    {
        $data = $this->fetchRekapSantriRaw($request);
        $periode = $request->input('periode') ?: now()->format('Y-m');

        return Excel::download(new class($data, $periode) implements \Maatwebsite\Excel\Concerns\FromView {
            private $data;
            private $periode;

            public function __construct($data, $periode)
            {
                $this->data = $data;
                $this->periode = $periode;
            }

            public function view(): \Illuminate\Contracts\View\View
            {
                return view('admin.laporan.export.rekap-santri-excel', [
                    'data' => $this->data,
                    'periode' => $this->periode,
                ]);
            }
        }, 'rekap_hafalan_santri_' . $periode . '.xlsx');
    }

    public function exportKelasExcel(Request $request)
    {
        $data = $this->fetchRekapKelasRaw($request);
        $periode = $request->input('periode') ?: now()->format('Y-m');

        return Excel::download(new class($data, $periode) implements \Maatwebsite\Excel\Concerns\FromView {
            private $data;
            private $periode;

            public function __construct($data, $periode)
            {
                $this->data = $data;
                $this->periode = $periode;
            }

            public function view(): \Illuminate\Contracts\View\View
            {
                return view('admin.laporan.export.rekap-kelas-excel', [
                    'data' => $this->data,
                    'periode' => $this->periode,
                ]);
            }
        }, 'rekap_hafalan_kelas_' . $periode . '.xlsx');
    }

    public function exportMusyrifExcel(Request $request)
    {
        $data = $this->fetchRekapMusyrifRaw($request);
        $periode = $request->input('periode') ?: now()->format('Y-m');

        return Excel::download(new class($data, $periode) implements \Maatwebsite\Excel\Concerns\FromView {
            private $data;
            private $periode;

            public function __construct($data, $periode)
            {
                $this->data = $data;
                $this->periode = $periode;
            }

            public function view(): \Illuminate\Contracts\View\View
            {
                return view('admin.laporan.export.rekap-musyrif-excel', [
                    'data' => $this->data,
                    'periode' => $this->periode,
                ]);
            }
        }, 'rekap_hafalan_musyrif_' . $periode . '.xlsx');
    }

    // PDF Exports

    private function buildExecutiveAnalytics($data): array
    {
        return [

            'summary' => [
                'total_santri' => $data->count(),

                'total_setoran' => $data->sum('total_setor'),

                'avg_nilai' => round(
                    $data->whereNotNull('rata_nilai')
                        ->avg('rata_nilai'),
                    2
                ),

                'santri_aktif' => $data
                    ->where('total_setor', '>', 0)
                    ->count(),
            ],

            'topSantri' => $data
                ->whereNotNull('rata_nilai')
                ->sortByDesc('rata_nilai')
                ->take(10)
                ->values(),

            'statusDistribution' => [

                'mumtaz' => $data
                    ->where('rata_nilai', '>=', 90)
                    ->count(),

                'jayyid_jiddan' => $data
                    ->whereBetween('rata_nilai', [80, 89.99])
                    ->count(),

                'jayyid' => $data
                    ->whereBetween('rata_nilai', [70, 79.99])
                    ->count(),

                'mardud' => $data
                    ->where('rata_nilai', '<', 70)
                    ->whereNotNull('rata_nilai')
                    ->count(),
            ]
        ];
    }

    public function exportSantriPdf(Request $request)
    {
        $data = $this->fetchRekapSantriRaw($request);

        $periode = $request->input('periode')
            ?: now()->format('Y-m');

        $analytics = $this->buildExecutiveAnalytics($data);

        $pdf = Pdf::loadView(
            'admin.laporan.export.rekap-santri-pdf',
            [

                'data' => $data,

                'periode' => $periode,

                'summary' => $analytics['summary'],

                'topSantri' => $analytics['topSantri'],

                'statusDistribution' => $analytics['statusDistribution'],
            ]
        )
            ->setPaper('A4', 'portrait');

        return $pdf->download(
            'rekap_hafalan_santri_' . $periode . '.pdf'
        );
    }

    public function exportKelasPdf(Request $request)
    {
        $data = $this->fetchRekapKelasRaw($request);

        $periode = $request->input('periode')
            ?: now()->format('Y-m');

        $summary = [
            'total_kelas' => $data->count(),

            'total_setoran' => $data->sum('total_setor'),

            'avg_nilai' => round(
                $data->whereNotNull('rata_nilai')
                    ->avg('rata_nilai'),
                2
            ),

            'total_santri' => $data->sum('jumlah_santri'),
        ];

        $topKelas = $data
            ->whereNotNull('rata_nilai')
            ->sortByDesc('rata_nilai')
            ->take(10)
            ->values();

        $pdf = Pdf::loadView(
            'admin.laporan.export.rekap-kelas-pdf',
            compact(
                'data',
                'periode',
                'summary',
                'topKelas'
            )
        )
            ->setPaper('A4', 'portrait');

        return $pdf->download(
            'rekap_hafalan_kelas_' . $periode . '.pdf'
        );
    }

    public function exportMusyrifPdf(Request $request)
    {
        $data = $this->fetchRekapMusyrifRaw($request);

        $periode = $request->input('periode')
            ?: now()->format('Y-m');

        $summary = [
            'total_musyrif' => $data->count(),

            'total_setoran' => $data->sum('total_setor'),

            'avg_nilai' => round(
                $data->whereNotNull('rata_nilai')
                    ->avg('rata_nilai'),
                2
            ),

            'total_santri' => $data->sum('jumlah_santri'),
        ];

        $topMusyrif = $data
            ->whereNotNull('rata_nilai')
            ->sortByDesc('rata_nilai')
            ->take(10)
            ->values();

        $pdf = Pdf::loadView(
            'admin.laporan.export.rekap-musyrif-pdf',
            compact(
                'data',
                'periode',
                'summary',
                'topMusyrif'
            )
        )
            ->setPaper('A4', 'portrait');

        return $pdf->download(
            'rekap_hafalan_musyrif_' . $periode . '.pdf'
        );
    }
}
