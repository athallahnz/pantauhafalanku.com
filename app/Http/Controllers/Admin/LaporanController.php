<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\Semester;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class LaporanController extends Controller
{
    /**
     * Konversi nilai_label ke skor numerik untuk kebutuhan agregasi laporan.
     */
    private function sqlNilaiLabelToAngka(string $table = ''): string
    {
        $prefix = $table !== '' ? $table . '.' : '';

        return "CASE
            WHEN {$prefix}nilai_label = 'mumtaz' THEN 95
            WHEN {$prefix}nilai_label = 'jayyid_jiddan' THEN 85
            WHEN {$prefix}nilai_label = 'jayyid' THEN 75
            WHEN {$prefix}nilai_label = 'mardud' THEN 65
            ELSE NULL
        END";
    }

    /**
     * Menormalkan nama file agar aman digunakan sebagai attachment.
     */
    private function sanitizePdfFilename(string $filename): string
    {
        $filename = preg_replace('/[^\pL\pN._-]+/u', '_', trim($filename))
            ?: 'laporan.pdf';

        if (!Str::endsWith(Str::lower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return $filename;
    }

    /**
     * Membuat dan mengirim PDF langsung sebagai attachment browser.
     * Tidak menggunakan fetch JSON, file sementara, atau signed URL.
     *
     * Resolver harus mengembalikan:
     * - view_data: array data untuk Blade PDF
     * - filename: nama file ketika diunduh
     */
    private function downloadPdfDirect(
        Request $request,
        string $reportKey,
        string $view,
        callable $resolver
    ) {
        $requestId = (string) Str::uuid();

        Log::info('REPORT PDF DOWNLOAD START', [
            'request_id' => $requestId,
            'report' => $reportKey,
            'view' => $view,
            'url' => $request->fullUrl(),
            'user_id' => $request->user()?->id,
            'filters' => $request->only([
                'semester_id',
                'periode',
                'kelas_id',
                'musyrif_id',
            ]),
        ]);

        try {
            $payload = $resolver();

            $viewData = $payload['view_data'] ?? null;
            $filename = $payload['filename'] ?? null;

            if (!is_array($viewData)) {
                throw new \RuntimeException(
                    'Resolver PDF tidak mengembalikan view_data yang valid.'
                );
            }

            if (!is_string($filename) || trim($filename) === '') {
                throw new \RuntimeException(
                    'Resolver PDF tidak mengembalikan filename yang valid.'
                );
            }

            $filename = $this->sanitizePdfFilename($filename);

            /*
         * Render Blade lebih dahulu supaya error pada template
         * dapat ditangkap dan dicatat di log.
         */
            $html = view($view, $viewData)->render();

            if (trim($html) === '') {
                throw new \RuntimeException(
                    'Hasil render Blade PDF kosong.'
                );
            }

            Log::info('REPORT PDF BLADE RENDERED', [
                'request_id' => $requestId,
                'report' => $reportKey,
                'html_size' => strlen($html),
            ]);

            /*
         * Biarkan DomPDF membuat response download sendiri.
         * Jangan panggil output(), response($binary), atau
         * streamDownload() secara manual.
         */
            $pdf = Pdf::loadHTML($html)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => false,
                    'isJavascriptEnabled' => false,
                    'dpi' => 96,
                ]);

            Log::info('REPORT PDF DOWNLOAD RESPONSE', [
                'request_id' => $requestId,
                'report' => $reportKey,
                'filename' => $filename,
            ]);

            return $pdf->download($filename);
        } catch (ValidationException $exception) {
            Log::warning('REPORT PDF VALIDATION FAILED', [
                'request_id' => $requestId,
                'report' => $reportKey,
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('REPORT PDF DOWNLOAD FAILED', [
                'request_id' => $requestId,
                'report' => $reportKey,
                'view' => $view,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            if (config('app.debug')) {
                throw $exception;
            }

            abort(
                500,
                'Export PDF gagal. Silakan periksa log aplikasi.'
            );
        }
    }

    /**
     * Halaman utama laporan.
     */
    public function index()
    {
        $kelasList = Kelas::query()
            ->orderBy('nama_kelas')
            ->get([
                'id',
                'nama_kelas',
            ]);

        $musyrifList = Musyrif::query()
            ->orderBy('nama')
            ->get([
                'id',
                'nama',
            ]);

        $semesterList = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')
            ->get();

        /*
         * Lifecycle baru menggunakan status=active.
         * is_active tetap dibaca sebagai fallback kompatibilitas.
         */
        $semesterAktif = $semesterList
            ->first(
                fn(Semester $semester) =>
                $semester->status === 'active'
                    || (bool) $semester->is_active
            )
            ?? $semesterList->first();

        $defaultSemesterId =
            $semesterAktif?->id;

        $defaultPeriode = null;

        return view(
            'admin.laporan.index',
            compact(
                'kelasList',
                'musyrifList',
                'semesterList',
                'semesterAktif',
                'defaultSemesterId',
                'defaultPeriode'
            )
        );
    }

    /**
     * Menentukan rentang laporan berdasarkan semester, lalu dibatasi bulan bila dipilih.
     */
    private function resolveReportContext(
        Request $request
    ): array {
        $semesterId = $request->filled(
            'semester_id'
        )
            ? (int) $request->input(
                'semester_id'
            )
            : null;

        $semester = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->when(
                $semesterId,
                fn($query) =>
                $query->whereKey($semesterId)
            )
            ->when(
                !$semesterId,
                fn($query) =>
                $query->where(
                    'status',
                    'active'
                )
            )
            ->first();

        /*
         * Fallback hanya memilih semester terbaru.
         * Tidak pernah fallback ke posisi santri terkini.
         */
        if (!$semester) {
            $semester = Semester::query()
                ->with('tahunAjaran:id,nama')
                ->orderByDesc('tanggal_mulai')
                ->first();
        }

        if (!$semester) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Semester laporan belum tersedia.',
                ],
            ]);
        }

        if (
            $semester->tanggal_mulai
            && $semester->tanggal_selesai
        ) {
            $start = Carbon::parse(
                $semester->tanggal_mulai
            )->startOfDay();

            $end = Carbon::parse(
                $semester->tanggal_selesai
            )->endOfDay();
        } else {
            $start = now()
                ->startOfMonth()
                ->startOfDay();

            $end = now()
                ->endOfMonth()
                ->endOfDay();
        }

        if ($request->filled('periode')) {
            try {
                $monthStart =
                    Carbon::createFromFormat(
                        '!Y-m',
                        (string) $request->input(
                            'periode'
                        )
                    )->startOfMonth();

                $monthEnd =
                    $monthStart
                    ->copy()
                    ->endOfMonth();
            } catch (\Throwable $exception) {
                throw ValidationException::withMessages([
                    'periode' => [
                        'Format bulan tidak valid.',
                    ],
                ]);
            }

            if (
                $monthEnd->lt($start)
                || $monthStart->gt($end)
            ) {
                throw ValidationException::withMessages([
                    'periode' => [
                        'Bulan yang dipilih berada di luar rentang semester terpilih.',
                    ],
                ]);
            }

            if ($monthStart->gt($start)) {
                $start = $monthStart;
            }

            if ($monthEnd->lt($end)) {
                $end = $monthEnd;
            }
        }

        $semesterLabel = trim(
            $semester->nama
                . ' '
                . (
                    $semester->tahunAjaran?->nama
                    ?? ''
                )
        );

        return [
            'semester' => $semester,
            'semester_id' =>
            (int) $semester->id,
            'semester_label' =>
            $semesterLabel,
            'start' => $start,
            'end' => $end,
            'start_date' =>
            $start->toDateString(),
            'end_date' =>
            $end->toDateString(),
            'period_label' =>
            $start->translatedFormat(
                'd M Y'
            )
                . ' — '
                . $end->translatedFormat(
                    'd M Y'
                ),
        ];
    }

    /**
     * Query dasar santri sesuai filter organisasi.
     */
    private function filteredSantriQuery(
        int $semesterId,
        ?int $kelasId,
        ?int $musyrifId
    ): EloquentBuilder {
        return Santri::query()
            ->join(
                'santri_semester_placements as ssp',
                function ($join) use (
                    $semesterId
                ): void {
                    $join
                        ->on(
                            'ssp.santri_id',
                            '=',
                            'santris.id'
                        )
                        ->where(
                            'ssp.semester_id',
                            '=',
                            $semesterId
                        );
                }
            )
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'ssp.kelas_id',
                    $kelasId
                )
            )
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'ssp.musyrif_id',
                    $musyrifId
                )
            );
    }

    /**
     * Agregasi hafalan per santri pada satu rentang tanggal.
     */
    private function hafalanAggregate(
        int $semesterId,
        string $startDate,
        string $endDate
    ) {
        return DB::table('hafalans')
            ->select(
                'santri_id',
                DB::raw(
                    "SUM(CASE WHEN status IN ('lulus', 'ulang') THEN 1 ELSE 0 END) AS total_setor"
                ),
                DB::raw(
                    "SUM(CASE WHEN status = 'hadir_tidak_setor' THEN 1 ELSE 0 END) AS hadir_tidak_setor"
                ),
                DB::raw(
                    "SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) AS sakit"
                ),
                DB::raw(
                    "SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) AS izin"
                ),
                DB::raw(
                    "SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) AS alpha"
                ),
                DB::raw(
                    "ROUND(AVG(CASE
                        WHEN status IN ('lulus', 'ulang') THEN
                            CASE
                                WHEN nilai_label = 'mumtaz' THEN 95
                                WHEN nilai_label = 'jayyid_jiddan' THEN 85
                                WHEN nilai_label = 'jayyid' THEN 75
                                WHEN nilai_label = 'mardud' THEN 65
                                ELSE NULL
                            END
                        ELSE NULL
                    END), 2) AS rata_nilai"
                )
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->whereBetween(
                'tanggal_setoran',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->groupBy('santri_id');
    }

    /**
     * Payload KPI, distribusi kehadiran, tren, dan insight eksekutif.
     * Dipanggil melalui route data yang sama menggunakan summary_only=1.
     */
    private function buildDashboardSummary(
        Request $request
    ): array {
        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $semesterId =
            $context['semester_id'];

        $santriQuery =
            $this->filteredSantriQuery(
                $semesterId,
                $kelasId,
                $musyrifId
            );

        $santriIds = (clone $santriQuery)
            ->distinct()
            ->pluck('santris.id');

        $musyrifIds = (clone $santriQuery)
            ->whereNotNull(
                'ssp.musyrif_id'
            )
            ->distinct()
            ->pluck('ssp.musyrif_id');

        $totalSantri =
            $santriIds->count();

        $totalMusyrif =
            $musyrifIds->count();

        $allPlacementSantriIds =
            DB::table(
                'santri_semester_placements'
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->pluck('santri_id');

        $semesterPlacementCount =
            $allPlacementSantriIds->count();

        /*
         * Progress wajib cocok pada dua dimensi:
         * 1. semester_id transaksi
         * 2. rentang tanggal laporan
         */
        $hafalanBase = Hafalan::query()
            ->where(
                'semester_id',
                $semesterId
            )
            ->whereIn(
                'santri_id',
                $santriIds
            )
            ->whereBetween(
                'tanggal_setoran',
                [
                    $context['start_date'],
                    $context['end_date'],
                ]
            );

        $statusSummary = (clone $hafalanBase)
            ->selectRaw(
                "SUM(CASE WHEN status IN ('lulus', 'ulang') THEN 1 ELSE 0 END) AS setor"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'hadir_tidak_setor' THEN 1 ELSE 0 END) AS hadir_tidak_setor"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'sakit' THEN 1 ELSE 0 END) AS sakit"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'izin' THEN 1 ELSE 0 END) AS izin"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) AS alpha"
            )
            ->first();

        $totalSetor =
            (int) (
                $statusSummary->setor
                ?? 0
            );

        $hadirTidakSetor =
            (int) (
                $statusSummary
                ->hadir_tidak_setor
                ?? 0
            );

        $sakit =
            (int) (
                $statusSummary->sakit
                ?? 0
            );

        $izin =
            (int) (
                $statusSummary->izin
                ?? 0
            );

        $alpha =
            (int) (
                $statusSummary->alpha
                ?? 0
            );

        $hadir =
            $totalSetor
            + $hadirTidakSetor;

        $totalStatus =
            $hadir
            + $sakit
            + $izin
            + $alpha;

        $percentage = static fn(
            int|float $value,
            int|float $total
        ): float =>
        $total > 0
            ? round(
                ($value / $total) * 100,
                1
            )
            : 0;

        $avgNilai = (clone $hafalanBase)
            ->whereIn(
                'status',
                [
                    'lulus',
                    'ulang',
                ]
            )
            ->selectRaw(
                'AVG('
                    . $this->sqlNilaiLabelToAngka(
                        'hafalans'
                    )
                    . ') AS average_value'
            )
            ->value('average_value');

        $avgNilai = $avgNilai
            ? round(
                (float) $avgNilai,
                2
            )
            : 0;

        $santriAktif = (clone $hafalanBase)
            ->whereIn(
                'status',
                [
                    'lulus',
                    'ulang',
                ]
            )
            ->distinct('santri_id')
            ->count('santri_id');

        $santriBelumSetor = max(
            0,
            $totalSantri
                - $santriAktif
        );

        $santriRisikoAlpha =
            (clone $hafalanBase)
            ->where(
                'status',
                'alpha'
            )
            ->select('santri_id')
            ->groupBy('santri_id')
            ->havingRaw(
                'COUNT(*) >= 3'
            )
            ->get()
            ->count();

        $attendanceQuery =
            DB::table(
                'musyrif_attendances'
            )
            ->whereIn(
                'musyrif_id',
                $musyrifIds
            )
            ->whereBetween(
                'attendance_at',
                [
                    $context['start']
                        ->copy()
                        ->startOfDay(),
                    $context['end']
                        ->copy()
                        ->endOfDay(),
                ]
            );

        $totalAttendanceMusyrif =
            (clone $attendanceQuery)
            ->count();

        $validAttendanceMusyrif =
            (clone $attendanceQuery)
            ->where(
                'status',
                'valid'
            )
            ->count();

        $validAttendanceMusyrifPct =
            $percentage(
                $validAttendanceMusyrif,
                $totalAttendanceMusyrif
            );

        $topKelas = DB::table(
            'hafalans as h'
        )
            ->join(
                'santri_semester_placements as sp',
                function ($join) use (
                    $semesterId
                ): void {
                    $join
                        ->on(
                            'sp.santri_id',
                            '=',
                            'h.santri_id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $semesterId
                        );
                }
            )
            ->join(
                'kelas as k',
                'k.id',
                '=',
                'sp.kelas_id'
            )
            ->where(
                'h.semester_id',
                $semesterId
            )
            ->whereBetween(
                'h.tanggal_setoran',
                [
                    $context['start_date'],
                    $context['end_date'],
                ]
            )
            ->whereIn(
                'h.status',
                [
                    'lulus',
                    'ulang',
                ]
            )
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'sp.kelas_id',
                    $kelasId
                )
            )
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'sp.musyrif_id',
                    $musyrifId
                )
            )
            ->select(
                'k.nama_kelas',
                DB::raw(
                    'COUNT(*) AS total_setor'
                )
            )
            ->groupBy(
                'k.id',
                'k.nama_kelas'
            )
            ->orderByDesc('total_setor')
            ->first();

        $topMusyrif = DB::table(
            'hafalans as h'
        )
            ->join(
                'santri_semester_placements as sp',
                function ($join) use (
                    $semesterId
                ): void {
                    $join
                        ->on(
                            'sp.santri_id',
                            '=',
                            'h.santri_id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $semesterId
                        );
                }
            )
            ->join(
                'musyrifs as m',
                'm.id',
                '=',
                'sp.musyrif_id'
            )
            ->where(
                'h.semester_id',
                $semesterId
            )
            ->whereBetween(
                'h.tanggal_setoran',
                [
                    $context['start_date'],
                    $context['end_date'],
                ]
            )
            ->whereIn(
                'h.status',
                [
                    'lulus',
                    'ulang',
                ]
            )
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'sp.kelas_id',
                    $kelasId
                )
            )
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'sp.musyrif_id',
                    $musyrifId
                )
            )
            ->select(
                'm.nama',
                DB::raw(
                    'COUNT(*) AS total_setor'
                )
            )
            ->groupBy(
                'm.id',
                'm.nama'
            )
            ->orderByDesc('total_setor')
            ->first();

        $isMonthlyFilter =
            $request->filled('periode');

        $bucketSql = $isMonthlyFilter
            ? 'DATE(tanggal_setoran)'
            : "DATE_FORMAT(tanggal_setoran, '%Y-%m')";

        $trendRows = (clone $hafalanBase)
            ->selectRaw(
                "{$bucketSql} AS bucket"
            )
            ->selectRaw(
                "SUM(CASE WHEN status IN ('lulus', 'ulang') THEN 1 ELSE 0 END) AS total_setor"
            )
            ->selectRaw(
                "SUM(CASE WHEN status = 'alpha' THEN 1 ELSE 0 END) AS total_alpha"
            )
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $trendLabels =
            $trendRows->map(
                function ($row) use (
                    $isMonthlyFilter
                ) {
                    if ($isMonthlyFilter) {
                        return Carbon::parse(
                            $row->bucket
                        )->translatedFormat(
                            'd M'
                        );
                    }

                    return Carbon::createFromFormat(
                        'Y-m',
                        $row->bucket
                    )->translatedFormat(
                        'M Y'
                    );
                }
            )->values();

        /*
         * Audit integritas: transaksi semester yang santrinya
         * belum mempunyai placement.
         */
        $unplacedProgressCount =
            Hafalan::query()
            ->where(
                'semester_id',
                $semesterId
            )
            ->whereBetween(
                'tanggal_setoran',
                [
                    $context['start_date'],
                    $context['end_date'],
                ]
            )
            ->when(
                $allPlacementSantriIds
                    ->isNotEmpty(),
                fn($query) =>
                $query->whereNotIn(
                    'santri_id',
                    $allPlacementSantriIds
                )
            )
            ->distinct('santri_id')
            ->count('santri_id');

        $legacyProgressCount =
            Hafalan::query()
            ->whereNull('semester_id')
            ->whereBetween(
                'tanggal_setoran',
                [
                    $context['start_date'],
                    $context['end_date'],
                ]
            )
            ->count();

        $warnings = [];

        if ($semesterPlacementCount === 0) {
            $warnings[] =
                'Belum ada placement santri pada semester terpilih. Jalankan backfill placement terlebih dahulu.';
        }

        if ($unplacedProgressCount > 0) {
            $warnings[] =
                "{$unplacedProgressCount} santri memiliki transaksi Hafalan semester ini tetapi belum mempunyai placement.";
        }

        if ($legacyProgressCount > 0) {
            $warnings[] =
                "{$legacyProgressCount} transaksi Hafalan pada rentang ini masih memiliki semester_id NULL dan tidak dimasukkan ke laporan.";
        }

        return [
            'semester' => [
                'id' =>
                $context['semester']->id,
                'label' =>
                $context['semester_label'],
                'is_active' =>
                $context['semester']->status
                    === 'active'
                    || (bool) $context['semester']->is_active,
                'periode_label' =>
                $context['period_label'],
            ],
            'data_source' => [
                'type' =>
                'santri_semester_placements',
                'placement_count' =>
                $totalSantri,
                'semester_placement_count' =>
                $semesterPlacementCount,
                'unplaced_progress_count' =>
                $unplacedProgressCount,
                'legacy_progress_count' =>
                $legacyProgressCount,
                'warnings' => $warnings,
            ],
            'kpi' => [
                'total_santri' =>
                $totalSantri,
                'total_musyrif' =>
                $totalMusyrif,
                'total_setor' =>
                $totalSetor,
                'avg_nilai' =>
                $avgNilai,
                'valid_absensi_musyrif_pct' =>
                $validAttendanceMusyrifPct,
            ],
            'attendance' => [
                'hadir' => [
                    'count' => $hadir,
                    'percentage' =>
                    $percentage(
                        $hadir,
                        $totalStatus
                    ),
                ],
                'sakit' => [
                    'count' => $sakit,
                    'percentage' =>
                    $percentage(
                        $sakit,
                        $totalStatus
                    ),
                ],
                'izin' => [
                    'count' => $izin,
                    'percentage' =>
                    $percentage(
                        $izin,
                        $totalStatus
                    ),
                ],
                'alpha' => [
                    'count' => $alpha,
                    'percentage' =>
                    $percentage(
                        $alpha,
                        $totalStatus
                    ),
                ],
                'hadir_tidak_setor' =>
                $hadirTidakSetor,
            ],
            'insights' => [
                'santri_aktif' =>
                $santriAktif,
                'coverage_santri_pct' =>
                $percentage(
                    $santriAktif,
                    $totalSantri
                ),
                'santri_belum_setor' =>
                $santriBelumSetor,
                'santri_risiko_alpha' =>
                $santriRisikoAlpha,
                'avg_setoran_per_santri' =>
                $totalSantri > 0
                    ? round(
                        $totalSetor
                            / $totalSantri,
                        2
                    )
                    : 0,
                'top_kelas' => [
                    'nama' =>
                    $topKelas
                        ->nama_kelas
                        ?? '-',
                    'total_setor' =>
                    (int) (
                        $topKelas
                        ->total_setor
                        ?? 0
                    ),
                ],
                'top_musyrif' => [
                    'nama' =>
                    $topMusyrif->nama
                        ?? '-',
                    'total_setor' =>
                    (int) (
                        $topMusyrif
                        ->total_setor
                        ?? 0
                    ),
                ],
            ],
            'charts' => [
                'attendance' => [
                    'labels' => [
                        'Hadir',
                        'Sakit',
                        'Izin',
                        'Alpha',
                    ],
                    'data' => [
                        $hadir,
                        $sakit,
                        $izin,
                        $alpha,
                    ],
                ],
                'trend' => [
                    'labels' =>
                    $trendLabels,
                    'setoran' =>
                    $trendRows
                        ->pluck(
                            'total_setor'
                        )
                        ->map(
                            fn($value) =>
                            (int) $value
                        )
                        ->values(),
                    'alpha' =>
                    $trendRows
                        ->pluck(
                            'total_alpha'
                        )
                        ->map(
                            fn($value) =>
                            (int) $value
                        )
                        ->values(),
                ],
            ],
        ];
    }

    /**
     * Riwayat hafalan per santri untuk modal detail.
     */
    public function getRiwayatSantri(
        Request $request,
        $id
    ) {
        try {
            $context =
                $this->resolveReportContext(
                    $request
                );

            $santri = Santri::query()
                ->findOrFail($id);

            $placement = DB::table(
                'santri_semester_placements as sp'
            )
                ->leftJoin(
                    'kelas as k',
                    'k.id',
                    '=',
                    'sp.kelas_id'
                )
                ->leftJoin(
                    'musyrifs as m',
                    'm.id',
                    '=',
                    'sp.musyrif_id'
                )
                ->where(
                    'sp.santri_id',
                    $santri->id
                )
                ->where(
                    'sp.semester_id',
                    $context['semester_id']
                )
                ->select(
                    'sp.status',
                    'k.nama_kelas',
                    'm.nama as musyrif_nama'
                )
                ->first();

            if (!$placement) {
                throw ValidationException::withMessages([
                    'placement' => [
                        'Placement santri pada semester terpilih tidak ditemukan.',
                    ],
                ]);
            }

            $riwayat = Hafalan::query()
                ->with('template')
                ->where(
                    'santri_id',
                    $santri->id
                )
                ->where(
                    'semester_id',
                    $context['semester_id']
                )
                ->whereBetween(
                    'tanggal_setoran',
                    [
                        $context['start_date'],
                        $context['end_date'],
                    ]
                )
                ->orderByDesc(
                    'tanggal_setoran'
                )
                ->get()
                ->map(function ($item) {
                    $tanggal =
                        $item->tanggal_setoran
                        ?? optional(
                            $item->created_at
                        )->toDateString();

                    return [
                        'tanggal_setoran' =>
                        $tanggal
                            ? Carbon::parse(
                                $tanggal
                            )->translatedFormat(
                                'd F Y'
                            )
                            : '-',
                        'materi' =>
                        $item->template?->label
                            ?? $item
                            ->rentang_ayat_label
                            ?? '-',
                        'status' =>
                        $item->status
                            ?? '-',
                        'nilai_label' =>
                        $item->nilai_label
                            ?? '-',
                        'catatan' =>
                        $item->catatan
                            ?? '',
                    ];
                });

            return response()->json([
                'santri' => [
                    'id' => $santri->id,
                    'nama' => $santri->nama,
                    'kelas' =>
                    $placement->nama_kelas
                        ?? '-',
                    'musyrif' =>
                    $placement->musyrif_nama
                        ?? '-',
                    'placement_status' =>
                    $placement->status,
                ],
                'riwayat' => $riwayat,
                'period_label' =>
                $context['period_label'],
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error(
                'Gagal mengambil riwayat hafalan santri.',
                [
                    'santri_id' => $id,
                    'message' =>
                    $exception->getMessage(),
                    'trace' =>
                    $exception
                        ->getTraceAsString(),
                ]
            );

            return response()->json([
                'message' =>
                'Terjadi kesalahan saat mengambil data riwayat.',
            ], 500);
        }
    }

    /**
     * Rekap per santri dan endpoint summary dashboard.
     */
    public function getRekapSantri(
        Request $request
    ) {
        if (!$request->ajax()) {
            abort(404);
        }

        if (
            $request->boolean(
                'summary_only'
            )
        ) {
            return response()->json(
                $this->buildDashboardSummary(
                    $request
                )
            );
        }

        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $hafalanAgg =
            $this->hafalanAggregate(
                $context['semester_id'],
                $context['start_date'],
                $context['end_date']
            );

        /*
         * Jangan mengalias placement menjadi kelas_id/musyrif_id karena
         * santris.* sudah memiliki kedua nama kolom tersebut. Yajra akan
         * membungkus query sebagai derived table untuk COUNT(*) dan MySQL
         * menolak nama kolom hasil yang duplikat.
         */
        $query =
            $this->filteredSantriQuery(
                $context['semester_id'],
                $kelasId,
                $musyrifId
            )
            ->leftJoin(
                'kelas as pk',
                'pk.id',
                '=',
                'ssp.kelas_id'
            )
            ->leftJoin(
                'musyrifs as pm',
                'pm.id',
                '=',
                'ssp.musyrif_id'
            )
            ->leftJoinSub(
                $hafalanAgg,
                'h',
                'h.santri_id',
                '=',
                'santris.id'
            )
            ->select(
                'santris.*',
                'ssp.kelas_id as placement_kelas_id',
                'ssp.musyrif_id as placement_musyrif_id',
                'ssp.status as placement_status',
                'pk.nama_kelas as placement_kelas_nama',
                'pm.nama as placement_musyrif_nama',
                'h.total_setor',
                'h.hadir_tidak_setor',
                'h.sakit',
                'h.izin',
                'h.alpha',
                'h.rata_nilai'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn(
                'kelas',
                fn($row) =>
                $row->placement_kelas_nama
                    ?? '-'
            )
            ->addColumn(
                'musyrif',
                fn($row) =>
                $row->placement_musyrif_nama
                    ?? '-'
            )
            ->addColumn(
                'nama_santri',
                fn($row) =>
                $row->nama
                    ?? '-'
            )
            ->filterColumn(
                'kelas',
                fn($query, $keyword) =>
                $query->where(
                    'pk.nama_kelas',
                    'like',
                    "%{$keyword}%"
                )
            )
            ->filterColumn(
                'musyrif',
                fn($query, $keyword) =>
                $query->where(
                    'pm.nama',
                    'like',
                    "%{$keyword}%"
                )
            )
            ->filterColumn(
                'nama_santri',
                fn($query, $keyword) =>
                $query->where(
                    'santris.nama',
                    'like',
                    "%{$keyword}%"
                )
            )
            ->orderColumn(
                'kelas',
                'pk.nama_kelas $1'
            )
            ->orderColumn(
                'musyrif',
                'pm.nama $1'
            )
            ->orderColumn(
                'nama_santri',
                'santris.nama $1'
            )
            ->editColumn(
                'total_setor',
                fn($row) =>
                (int) (
                    $row->total_setor
                    ?? 0
                )
            )
            ->editColumn(
                'hadir_tidak_setor',
                fn($row) =>
                (int) (
                    $row
                    ->hadir_tidak_setor
                    ?? 0
                )
            )
            ->editColumn(
                'sakit',
                fn($row) =>
                (int) (
                    $row->sakit
                    ?? 0
                )
            )
            ->editColumn(
                'izin',
                fn($row) =>
                (int) (
                    $row->izin
                    ?? 0
                )
            )
            ->editColumn(
                'alpha',
                fn($row) =>
                (int) (
                    $row->alpha
                    ?? 0
                )
            )
            ->editColumn(
                'rata_nilai',
                fn($row) =>
                is_null(
                    $row->rata_nilai
                )
                    ? '-'
                    : number_format(
                        (float) $row
                            ->rata_nilai,
                        2
                    )
            )
            ->addColumn(
                'aksi',
                function ($row) {
                    return '
                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-detail-santri"
                            data-id="'
                        . $row->id
                        . '"
                            data-nama="'
                        . e($row->nama)
                        . '"
                            data-coreui-toggle="tooltip"
                            title="Lihat Detail">
                            <i class="bi bi-eye"></i>
                        </button>
                    ';
                }
            )
            ->rawColumns([
                'aksi',
            ])
            ->make(true);
    }

    /**
     * Rekap per kelas.
     */
    public function getRekapKelas(
        Request $request
    ) {
        if (!$request->ajax()) {
            abort(404);
        }

        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $hafalanAgg =
            $this->hafalanAggregate(
                $context['semester_id'],
                $context['start_date'],
                $context['end_date']
            );

        $query = Kelas::query()
            ->select(
                'kelas.id',
                'kelas.nama_kelas',
                DB::raw(
                    'COUNT(DISTINCT sp.santri_id) AS jumlah_santri'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setor), 0) AS total_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.hadir_tidak_setor), 0) AS hadir_tidak_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.sakit), 0) AS sakit'
                ),
                DB::raw(
                    'COALESCE(SUM(h.izin), 0) AS izin'
                ),
                DB::raw(
                    'COALESCE(SUM(h.alpha), 0) AS alpha'
                ),
                DB::raw(
                    'ROUND(AVG(h.rata_nilai), 2) AS rata_nilai'
                )
            )
            ->leftJoin(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context,
                    $musyrifId
                ): void {
                    $join
                        ->on(
                            'sp.kelas_id',
                            '=',
                            'kelas.id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        );

                    if ($musyrifId) {
                        $join->where(
                            'sp.musyrif_id',
                            '=',
                            $musyrifId
                        );
                    }
                }
            )
            ->leftJoinSub(
                $hafalanAgg,
                'h',
                'h.santri_id',
                '=',
                'sp.santri_id'
            )
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'kelas.id',
                    $kelasId
                )
            )
            ->groupBy(
                'kelas.id',
                'kelas.nama_kelas'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn(
                'jumlah_santri',
                fn($row) =>
                (int) (
                    $row->jumlah_santri
                    ?? 0
                )
            )
            ->editColumn(
                'total_setor',
                fn($row) =>
                (int) (
                    $row->total_setor
                    ?? 0
                )
            )
            ->editColumn(
                'hadir_tidak_setor',
                fn($row) =>
                (int) (
                    $row
                    ->hadir_tidak_setor
                    ?? 0
                )
            )
            ->editColumn(
                'sakit',
                fn($row) =>
                (int) (
                    $row->sakit
                    ?? 0
                )
            )
            ->editColumn(
                'izin',
                fn($row) =>
                (int) (
                    $row->izin
                    ?? 0
                )
            )
            ->editColumn(
                'alpha',
                fn($row) =>
                (int) (
                    $row->alpha
                    ?? 0
                )
            )
            ->editColumn(
                'rata_nilai',
                fn($row) =>
                is_null(
                    $row->rata_nilai
                )
                    ? '-'
                    : number_format(
                        (float) $row
                            ->rata_nilai,
                        2
                    )
            )
            ->make(true);
    }

    /**
     * Rekap per musyrif.
     */
    public function getRekapMusyrif(
        Request $request
    ) {
        if (!$request->ajax()) {
            abort(404);
        }

        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $hafalanAgg =
            $this->hafalanAggregate(
                $context['semester_id'],
                $context['start_date'],
                $context['end_date']
            );

        $query = Musyrif::query()
            ->select(
                'musyrifs.id',
                'musyrifs.nama',
                DB::raw(
                    'COUNT(DISTINCT sp.santri_id) AS jumlah_santri'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setor), 0) AS total_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.hadir_tidak_setor), 0) AS hadir_tidak_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.sakit), 0) AS sakit'
                ),
                DB::raw(
                    'COALESCE(SUM(h.izin), 0) AS izin'
                ),
                DB::raw(
                    'COALESCE(SUM(h.alpha), 0) AS alpha'
                ),
                DB::raw(
                    'ROUND(AVG(h.rata_nilai), 2) AS rata_nilai'
                )
            )
            ->leftJoin(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context,
                    $kelasId
                ): void {
                    $join
                        ->on(
                            'sp.musyrif_id',
                            '=',
                            'musyrifs.id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        );

                    if ($kelasId) {
                        $join->where(
                            'sp.kelas_id',
                            '=',
                            $kelasId
                        );
                    }
                }
            )
            ->leftJoinSub(
                $hafalanAgg,
                'h',
                'h.santri_id',
                '=',
                'sp.santri_id'
            )
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'musyrifs.id',
                    $musyrifId
                )
            )
            ->groupBy(
                'musyrifs.id',
                'musyrifs.nama'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn(
                'jumlah_santri',
                fn($row) =>
                (int) (
                    $row->jumlah_santri
                    ?? 0
                )
            )
            ->editColumn(
                'total_setor',
                fn($row) =>
                (int) (
                    $row->total_setor
                    ?? 0
                )
            )
            ->editColumn(
                'hadir_tidak_setor',
                fn($row) =>
                (int) (
                    $row
                    ->hadir_tidak_setor
                    ?? 0
                )
            )
            ->editColumn(
                'sakit',
                fn($row) =>
                (int) (
                    $row->sakit
                    ?? 0
                )
            )
            ->editColumn(
                'izin',
                fn($row) =>
                (int) (
                    $row->izin
                    ?? 0
                )
            )
            ->editColumn(
                'alpha',
                fn($row) =>
                (int) (
                    $row->alpha
                    ?? 0
                )
            )
            ->editColumn(
                'rata_nilai',
                fn($row) =>
                is_null(
                    $row->rata_nilai
                )
                    ? '-'
                    : number_format(
                        (float) $row
                            ->rata_nilai,
                        2
                    )
            )
            ->make(true);
    }

    /**
     * Grafik setoran per kelas.
     */
    public function getChartKelas(
        Request $request
    ) {
        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $query = Kelas::query()
            ->select(
                'kelas.id',
                'kelas.nama_kelas',
                DB::raw(
                    "COALESCE(SUM(CASE WHEN h.status IN ('lulus', 'ulang') THEN 1 ELSE 0 END), 0) AS total_setor"
                )
            )
            ->leftJoin(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context,
                    $musyrifId
                ): void {
                    $join
                        ->on(
                            'sp.kelas_id',
                            '=',
                            'kelas.id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        );

                    if ($musyrifId) {
                        $join->where(
                            'sp.musyrif_id',
                            '=',
                            $musyrifId
                        );
                    }
                }
            )
            ->leftJoin(
                'hafalans as h',
                function ($join) use (
                    $context
                ): void {
                    $join
                        ->on(
                            'h.santri_id',
                            '=',
                            'sp.santri_id'
                        )
                        ->where(
                            'h.semester_id',
                            '=',
                            $context['semester_id']
                        )
                        ->whereBetween(
                            'h.tanggal_setoran',
                            [
                                $context['start_date'],
                                $context['end_date'],
                            ]
                        );
                }
            )
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'kelas.id',
                    $kelasId
                )
            )
            ->groupBy(
                'kelas.id',
                'kelas.nama_kelas'
            )
            ->orderBy(
                'kelas.nama_kelas'
            );

        $rows = $query->get();

        return response()->json([
            'labels' =>
            $rows
                ->pluck(
                    'nama_kelas'
                )
                ->values(),
            'data' =>
            $rows
                ->pluck(
                    'total_setor'
                )
                ->map(
                    fn($value) =>
                    (int) $value
                )
                ->values(),
        ]);
    }

    /**
     * Grafik setoran per musyrif.
     */
    public function getChartMusyrif(
        Request $request
    ) {
        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $query = Musyrif::query()
            ->select(
                'musyrifs.id',
                'musyrifs.nama',
                DB::raw(
                    "COALESCE(SUM(CASE WHEN h.status IN ('lulus', 'ulang') THEN 1 ELSE 0 END), 0) AS total_setoran"
                )
            )
            ->leftJoin(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context,
                    $kelasId
                ): void {
                    $join
                        ->on(
                            'sp.musyrif_id',
                            '=',
                            'musyrifs.id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        );

                    if ($kelasId) {
                        $join->where(
                            'sp.kelas_id',
                            '=',
                            $kelasId
                        );
                    }
                }
            )
            ->leftJoin(
                'hafalans as h',
                function ($join) use (
                    $context
                ): void {
                    $join
                        ->on(
                            'h.santri_id',
                            '=',
                            'sp.santri_id'
                        )
                        ->where(
                            'h.semester_id',
                            '=',
                            $context['semester_id']
                        )
                        ->whereBetween(
                            'h.tanggal_setoran',
                            [
                                $context['start_date'],
                                $context['end_date'],
                            ]
                        );
                }
            )
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'musyrifs.id',
                    $musyrifId
                )
            )
            ->groupBy(
                'musyrifs.id',
                'musyrifs.nama'
            )
            ->orderBy(
                'musyrifs.nama'
            );

        $rows = $query->get();

        return response()->json([
            'labels' =>
            $rows
                ->pluck('nama')
                ->values(),
            'data' =>
            $rows
                ->pluck(
                    'total_setoran'
                )
                ->map(
                    fn($value) =>
                    (int) $value
                )
                ->values(),
        ]);
    }

    /**
     * Grafik kelulusan ujian akhir per Juz.
     */
    public function getChartJuzLulus(
        Request $request
    ) {
        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $rows = DB::table(
            'hafalans as h'
        )
            ->join(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'h.hafalan_template_id'
            )
            ->join(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context
                ): void {
                    $join
                        ->on(
                            'sp.santri_id',
                            '=',
                            'h.santri_id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        );
                }
            )
            ->where(
                'h.semester_id',
                $context['semester_id']
            )
            ->where(
                'ht.tahap',
                'ujian_akhir'
            )
            ->where(
                'h.status',
                'lulus'
            )
            ->whereBetween(
                'ht.juz',
                [
                    1,
                    30,
                ]
            )
            ->whereBetween(
                'h.tanggal_setoran',
                [
                    $context['start_date'],
                    $context['end_date'],
                ]
            )
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'sp.kelas_id',
                    $kelasId
                )
            )
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'sp.musyrif_id',
                    $musyrifId
                )
            )
            ->select(
                'ht.juz',
                DB::raw(
                    'COUNT(DISTINCT h.santri_id) AS jumlah'
                )
            )
            ->groupBy('ht.juz')
            ->orderBy('ht.juz')
            ->get();

        $map = $rows->pluck(
            'jumlah',
            'juz'
        );

        $labels = range(1, 30);

        $data = array_map(
            fn($juz) =>
            (int) (
                $map[$juz]
                ?? 0
            ),
            $labels
        );

        return response()->json([
            'labels' => array_map(
                fn($juz) =>
                "Juz {$juz}",
                $labels
            ),
            'data' => $data,
        ]);
    }

    /**
     * Rekap histori absensi musyrif.
     */
    public function getAbsensiMusyrif(
        Request $request
    ) {
        if (!$request->ajax()) {
            abort(404);
        }

        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $waktuAbsensi =
            $request->input(
                'waktu_absensi',
                'periode'
            );

        $context =
            $this->resolveReportContext(
                $request
            );

        $placementMusyrifIds =
            DB::table(
                'santri_semester_placements'
            )
            ->where(
                'semester_id',
                $context['semester_id']
            )
            ->whereNotNull('musyrif_id')
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'kelas_id',
                    $kelasId
                )
            )
            ->distinct()
            ->pluck('musyrif_id');

        $query = DB::table(
            'musyrif_attendances as ma'
        )
            ->join(
                'musyrifs as m',
                'm.id',
                '=',
                'ma.musyrif_id'
            )
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
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'ma.musyrif_id',
                    $musyrifId
                ),
                fn($query) =>
                $query->whereIn(
                    'ma.musyrif_id',
                    $placementMusyrifIds
                )
            );

        if ($waktuAbsensi === 'today') {
            $query->whereDate(
                'ma.attendance_at',
                Carbon::today()
            );
        } elseif ($waktuAbsensi !== 'all') {
            $query->whereBetween(
                'ma.attendance_at',
                [
                    $context['start']
                        ->copy()
                        ->startOfDay(),
                    $context['end']
                        ->copy()
                        ->endOfDay(),
                ]
            );
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn(
                'attendance_at',
                fn($row) =>
                Carbon::parse(
                    $row->attendance_at
                )->translatedFormat(
                    'd M Y, H:i'
                )
            )
            ->editColumn(
                'type',
                function ($row) {
                    return $row->type === 'morning'
                        ? '<span class="badge bg-info text-dark rounded-pill px-3"><i class="bi bi-brightness-alt-high-fill me-1"></i>Pagi</span>'
                        : '<span class="badge bg-warning text-dark rounded-pill px-3"><i class="bi bi-moon-stars-fill me-1"></i>Sore</span>';
                }
            )
            ->addColumn(
                'location',
                function ($row) {
                    $latlng = e(
                        "{$row->latitude},{$row->longitude}"
                    );

                    $gmapsLink =
                        'https://www.google.com/maps?q='
                        . rawurlencode(
                            "{$row->latitude},{$row->longitude}"
                        );

                    $address = e(
                        Str::limit(
                            $row->address_text
                                ?? '-',
                            35
                        )
                    );

                    return "
                        <div class='mb-1'>{$address}</div>
                        <div class='d-flex gap-2 align-items-center mt-1'>
                            <a href='{$gmapsLink}' target='_blank' rel='noopener' class='text-decoration-none small fw-semibold'>
                                <i class='bi bi-geo-alt text-danger'></i> {$latlng}
                            </a>
                            <button type='button' class='btn btn-sm btn-outline-secondary py-0 px-2 btn-preview-map'
                                data-lat='"
                        . e($row->latitude)
                        . "'
                                data-lng='"
                        . e($row->longitude)
                        . "'>
                                <i class='bi bi-map'></i>
                            </button>
                        </div>
                    ";
                }
            )
            ->editColumn(
                'status',
                function ($row) {
                    return match ($row->status) {
                        'valid' =>
                        '<span class="badge bg-success">Valid</span>',
                        'suspect' =>
                        '<span class="badge bg-warning text-dark">Suspect</span>',
                        default =>
                        '<span class="badge bg-danger">Rejected</span>',
                    };
                }
            )
            ->addColumn(
                'photo',
                function ($row) {
                    if (!$row->photo_path) {
                        return '<span class="text-muted small">Tidak ada foto</span>';
                    }

                    $url = asset(
                        'storage/'
                            . ltrim(
                                $row->photo_path,
                                '/'
                            )
                    );

                    $accuracy =
                        is_null($row->accuracy)
                        ? '-'
                        : e($row->accuracy)
                        . 'm';

                    return "
                        <button type='button' class='btn btn-sm btn-outline-primary btn-preview-photo'
                            data-url='"
                        . e($url)
                        . "'>
                            <i class='bi bi-image me-1'></i>Foto
                        </button>
                        <div class='mt-1 small text-muted'>Akurasi: {$accuracy}</div>
                    ";
                }
            )
            ->rawColumns([
                'type',
                'location',
                'status',
                'photo',
            ])
            ->make(true);
    }

    /**
     * Data mentah rekap santri untuk export.
     */
    private function fetchRekapSantriRaw(
        Request $request
    ) {
        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $hafalanAgg =
            $this->hafalanAggregate(
                $context['semester_id'],
                $context['start_date'],
                $context['end_date']
            );

        $rows = $this
            ->filteredSantriQuery(
                $context['semester_id'],
                $kelasId,
                $musyrifId
            )
            ->leftJoin(
                'kelas as pk',
                'pk.id',
                '=',
                'ssp.kelas_id'
            )
            ->leftJoin(
                'musyrifs as pm',
                'pm.id',
                '=',
                'ssp.musyrif_id'
            )
            ->leftJoinSub(
                $hafalanAgg,
                'h',
                'h.santri_id',
                '=',
                'santris.id'
            )
            ->select(
                'santris.*',
                'ssp.kelas_id as placement_kelas_id',
                'ssp.musyrif_id as placement_musyrif_id',
                'ssp.status as placement_status',
                'pk.nama_kelas as placement_kelas_nama',
                'pm.nama as placement_musyrif_nama',
                'h.total_setor',
                'h.hadir_tidak_setor',
                'h.sakit',
                'h.izin',
                'h.alpha',
                'h.rata_nilai'
            )
            ->orderBy('ssp.kelas_id')
            ->orderBy('santris.nama')
            ->get();

        /*
         * Pertahankan kompatibilitas Blade export lama yang membaca:
         *
         * $row->kelas?->nama_kelas
         * $row->musyrif?->nama
         *
         * Relasinya diisi dari placement semester, bukan current projection.
         */
        $rows->each(function (Santri $row): void {
            $row->setAttribute(
                'kelas_id',
                $row->placement_kelas_id
            );

            $row->setAttribute(
                'musyrif_id',
                $row->placement_musyrif_id
            );

            $kelas = null;

            if ($row->placement_kelas_id) {
                $kelas = (new Kelas())->forceFill([
                    'id' =>
                    $row->placement_kelas_id,
                    'nama_kelas' =>
                    $row->placement_kelas_nama,
                ]);
            }

            $musyrif = null;

            if ($row->placement_musyrif_id) {
                $musyrif = (new Musyrif())->forceFill([
                    'id' =>
                    $row->placement_musyrif_id,
                    'nama' =>
                    $row->placement_musyrif_nama,
                ]);
            }

            $row->setRelation(
                'kelas',
                $kelas
            );

            $row->setRelation(
                'musyrif',
                $musyrif
            );
        });

        return $rows;
    }

    /**
     * Data mentah rekap kelas untuk export.
     */
    private function fetchRekapKelasRaw(
        Request $request
    ) {
        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $hafalanAgg =
            $this->hafalanAggregate(
                $context['semester_id'],
                $context['start_date'],
                $context['end_date']
            );

        return Kelas::query()
            ->select(
                'kelas.id',
                'kelas.nama_kelas',
                DB::raw(
                    'COUNT(DISTINCT sp.santri_id) AS jumlah_santri'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setor), 0) AS total_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.hadir_tidak_setor), 0) AS hadir_tidak_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.sakit), 0) AS sakit'
                ),
                DB::raw(
                    'COALESCE(SUM(h.izin), 0) AS izin'
                ),
                DB::raw(
                    'COALESCE(SUM(h.alpha), 0) AS alpha'
                ),
                DB::raw(
                    'ROUND(AVG(h.rata_nilai), 2) AS rata_nilai'
                )
            )
            ->leftJoin(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context,
                    $musyrifId
                ): void {
                    $join
                        ->on(
                            'sp.kelas_id',
                            '=',
                            'kelas.id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        );

                    if ($musyrifId) {
                        $join->where(
                            'sp.musyrif_id',
                            '=',
                            $musyrifId
                        );
                    }
                }
            )
            ->leftJoinSub(
                $hafalanAgg,
                'h',
                'h.santri_id',
                '=',
                'sp.santri_id'
            )
            ->when(
                $kelasId,
                fn($query) =>
                $query->where(
                    'kelas.id',
                    $kelasId
                )
            )
            ->groupBy(
                'kelas.id',
                'kelas.nama_kelas'
            )
            ->orderBy(
                'kelas.nama_kelas'
            )
            ->get();
    }

    /**
     * Data mentah rekap musyrif untuk export.
     */

    private function fetchRekapMusyrifRaw(
        Request $request
    ) {
        $kelasId = $request->filled(
            'kelas_id'
        )
            ? (int) $request->input(
                'kelas_id'
            )
            : null;

        $musyrifId = $request->filled(
            'musyrif_id'
        )
            ? (int) $request->input(
                'musyrif_id'
            )
            : null;

        $context =
            $this->resolveReportContext(
                $request
            );

        $hafalanAgg =
            $this->hafalanAggregate(
                $context['semester_id'],
                $context['start_date'],
                $context['end_date']
            );

        return Musyrif::query()
            ->select(
                'musyrifs.id',
                'musyrifs.nama',
                DB::raw(
                    'COUNT(DISTINCT sp.santri_id) AS jumlah_santri'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setor), 0) AS total_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.hadir_tidak_setor), 0) AS hadir_tidak_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.sakit), 0) AS sakit'
                ),
                DB::raw(
                    'COALESCE(SUM(h.izin), 0) AS izin'
                ),
                DB::raw(
                    'COALESCE(SUM(h.alpha), 0) AS alpha'
                ),
                DB::raw(
                    'ROUND(AVG(h.rata_nilai), 2) AS rata_nilai'
                )
            )
            ->leftJoin(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context,
                    $kelasId
                ): void {
                    $join
                        ->on(
                            'sp.musyrif_id',
                            '=',
                            'musyrifs.id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        );

                    if ($kelasId) {
                        $join->where(
                            'sp.kelas_id',
                            '=',
                            $kelasId
                        );
                    }
                }
            )
            ->leftJoinSub(
                $hafalanAgg,
                'h',
                'h.santri_id',
                '=',
                'sp.santri_id'
            )
            ->when(
                $musyrifId,
                fn($query) =>
                $query->where(
                    'musyrifs.id',
                    $musyrifId
                )
            )
            ->groupBy(
                'musyrifs.id',
                'musyrifs.nama'
            )
            ->orderBy(
                'musyrifs.nama'
            )
            ->get();
    }

    private function exportContext(Request $request): array
    {
        $context = $this->resolveReportContext($request);

        $label = $request->filled('periode')
            ? Carbon::createFromFormat(
                '!Y-m',
                (string) $request->input('periode')
            )->translatedFormat('F Y')
            : Str::title(
                str_replace(
                    '_',
                    ' ',
                    $context['semester_label']
                )
            );

        $filenameSuffix = $request->filled('periode')
            ? $request->input('periode')
            : $context['start']->format('Ymd') .
            '_' .
            $context['end']->format('Ymd');

        return [
            'label' => $label,
            'filename_suffix' => $filenameSuffix,
        ];
    }

    public function exportSantriExcel(Request $request)
    {
        $data = $this->fetchRekapSantriRaw($request);
        $export = $this->exportContext($request);

        return Excel::download(new class($data, $export['label']) implements \Maatwebsite\Excel\Concerns\FromView {
            public function __construct(
                private $data,
                private string $periode
            ) {}

            public function view(): \Illuminate\Contracts\View\View
            {
                return view('admin.laporan.export.rekap-santri-excel', [
                    'data' => $this->data,
                    'periode' => $this->periode,
                ]);
            }
        }, 'rekap_hafalan_santri_' . $export['filename_suffix'] . '.xlsx');
    }

    public function exportKelasExcel(Request $request)
    {
        $data = $this->fetchRekapKelasRaw($request);
        $export = $this->exportContext($request);

        return Excel::download(new class($data, $export['label']) implements \Maatwebsite\Excel\Concerns\FromView {
            public function __construct(
                private $data,
                private string $periode
            ) {}

            public function view(): \Illuminate\Contracts\View\View
            {
                return view('admin.laporan.export.rekap-kelas-excel', [
                    'data' => $this->data,
                    'periode' => $this->periode,
                ]);
            }
        }, 'rekap_hafalan_kelas_' . $export['filename_suffix'] . '.xlsx');
    }

    public function exportMusyrifExcel(Request $request)
    {
        $data = $this->fetchRekapMusyrifRaw($request);
        $export = $this->exportContext($request);

        return Excel::download(new class($data, $export['label']) implements \Maatwebsite\Excel\Concerns\FromView {
            public function __construct(
                private $data,
                private string $periode
            ) {}

            public function view(): \Illuminate\Contracts\View\View
            {
                return view('admin.laporan.export.rekap-musyrif-excel', [
                    'data' => $this->data,
                    'periode' => $this->periode,
                ]);
            }
        }, 'rekap_hafalan_musyrif_' . $export['filename_suffix'] . '.xlsx');
    }

    private function buildExecutiveAnalytics($data): array
    {
        return [
            'summary' => [
                'total_santri' => $data->count(),
                'total_setoran' => $data->sum('total_setor'),
                'avg_nilai' => round($data->whereNotNull('rata_nilai')->avg('rata_nilai') ?? 0, 2),
                'santri_aktif' => $data->where('total_setor', '>', 0)->count(),
            ],
            'topSantri' => $data
                ->whereNotNull('rata_nilai')
                ->sortByDesc('rata_nilai')
                ->take(10)
                ->values(),
            'statusDistribution' => [
                'mumtaz' => $data->where('rata_nilai', '>=', 90)->count(),
                'jayyid_jiddan' => $data->whereBetween('rata_nilai', [80, 89.99])->count(),
                'jayyid' => $data->whereBetween('rata_nilai', [70, 79.99])->count(),
                'mardud' => $data->where('rata_nilai', '<', 70)->whereNotNull('rata_nilai')->count(),
            ],
        ];
    }

    public function exportSantriPdf(Request $request)
    {
        return $this->downloadPdfDirect(
            $request,
            'santri',
            'admin.laporan.export.rekap-santri-pdf',
            function () use ($request): array {
                $data = $this->fetchRekapSantriRaw($request);
                $export = $this->exportContext($request);
                $analytics = $this->buildExecutiveAnalytics($data);

                return [
                    'view_data' => [
                        'data' => $data,
                        'periode' => $export['label'],
                        'summary' => $analytics['summary'],
                        'topSantri' => $analytics['topSantri'],
                        'statusDistribution' =>
                        $analytics['statusDistribution'],
                    ],
                    'filename' => 'rekap_hafalan_santri_' .
                        $export['filename_suffix'] .
                        '.pdf',
                ];
            }
        );
    }

    public function exportKelasPdf(Request $request)
    {
        return $this->downloadPdfDirect(
            $request,
            'kelas',
            'admin.laporan.export.rekap-kelas-pdf',
            function () use ($request): array {
                $data = $this->fetchRekapKelasRaw($request);
                $export = $this->exportContext($request);

                $nilaiValid = $data->filter(
                    fn($row) => !is_null($row->rata_nilai)
                );

                $summary = [
                    'total_kelas' => (int) $data->count(),
                    'total_santri' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_santri ?? 0)
                    ),
                    'total_setoran' => (int) $data->sum(
                        fn($row) => (int) ($row->total_setor ?? 0)
                    ),
                    'avg_nilai' => round(
                        (float) ($nilaiValid->avg('rata_nilai') ?? 0),
                        2
                    ),
                ];

                $topKelas = $nilaiValid
                    ->sortByDesc(
                        fn($row) => (float) ($row->rata_nilai ?? 0)
                    )
                    ->take(10)
                    ->values();

                return [
                    'view_data' => [
                        'data' => $data,
                        'periode' => $export['label'],
                        'summary' => $summary,
                        'topKelas' => $topKelas,
                    ],
                    'filename' => 'rekap_hafalan_kelas_' .
                        $export['filename_suffix'] .
                        '.pdf',
                ];
            }
        );
    }

    public function exportMusyrifPdf(Request $request)
    {
        return $this->downloadPdfDirect(
            $request,
            'musyrif',
            'admin.laporan.export.rekap-musyrif-pdf',
            function () use ($request): array {
                $data = $this->fetchRekapMusyrifRaw($request);
                $export = $this->exportContext($request);

                $nilaiValid = $data->filter(
                    fn($row) => !is_null($row->rata_nilai)
                );

                $summary = [
                    'total_musyrif' => (int) $data->count(),
                    'total_santri' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_santri ?? 0)
                    ),
                    'total_setoran' => (int) $data->sum(
                        fn($row) => (int) ($row->total_setor ?? 0)
                    ),
                    'avg_nilai' => round(
                        (float) ($nilaiValid->avg('rata_nilai') ?? 0),
                        2
                    ),
                ];

                $topMusyrif = $nilaiValid
                    ->sortByDesc(
                        fn($row) => (float) ($row->rata_nilai ?? 0)
                    )
                    ->take(10)
                    ->values();

                return [
                    'view_data' => [
                        'data' => $data,
                        'periode' => $export['label'],
                        'summary' => $summary,
                        'topMusyrif' => $topMusyrif,
                    ],
                    'filename' => 'rekap_hafalan_musyrif_' .
                        $export['filename_suffix'] .
                        '.pdf',
                ];
            }
        );
    }
}
