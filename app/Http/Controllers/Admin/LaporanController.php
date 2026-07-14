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
use Illuminate\Support\Collection;
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
     * Konversi nilai_label ke angka untuk kebutuhan perhitungan detail PHP.
     */
    private function nilaiLabelToAngka(?string $nilaiLabel): ?int
    {
        return match ($nilaiLabel) {
            'mumtaz' => 95,
            'jayyid_jiddan' => 85,
            'jayyid' => 75,
            'mardud' => 65,
            default => null,
        };
    }

    /**
     * Tahapan yang masuk kategori proses harian sebelum ujian akhir.
     */
    private function tahapHarianList(): array
    {
        return [
            'harian',
            'tahap_1',
            'tahap_2',
            'tahap_3',
        ];
    }

    /**
     * Label tahapan proses setoran yang mudah dibaca.
     */
    private function tahapHafalanLabel(?string $tahap): string
    {
        return match ($tahap) {
            'harian' => 'Harian',
            'tahap_1' => 'Tahap 1',
            'tahap_2' => 'Tahap 2',
            'tahap_3' => 'Tahap 3',
            'ujian_akhir' => 'Ujian Akhir',
            default => '-',
        };
    }

    /**
     * Mengubah ranking tahapan menjadi kode tahap.
     */
    private function tahapHafalanFromRank(?int $rank): ?string
    {
        return match ((int) $rank) {
            1 => 'harian',
            2 => 'tahap_1',
            3 => 'tahap_2',
            4 => 'tahap_3',
            5 => 'ujian_akhir',
            default => null,
        };
    }

    /**
     * SQL ranking tahapan untuk memilih progress tertinggi.
     */
    private function tahapHafalanRankSql(string $tableAlias = 'ht'): string
    {
        return "CASE
            WHEN {$tableAlias}.tahap = 'harian' THEN 1
            WHEN {$tableAlias}.tahap = 'tahap_1' THEN 2
            WHEN {$tableAlias}.tahap = 'tahap_2' THEN 3
            WHEN {$tableAlias}.tahap = 'tahap_3' THEN 4
            WHEN {$tableAlias}.tahap = 'ujian_akhir' THEN 5
            ELSE 0
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
     *
     * Catatan perhitungan:
     * - Setoran harian = template tahap harian, tahap_1, tahap_2, tahap_3.
     * - Nilai rata-rata sementara hanya dari setoran harian dan dibatasi maksimal 70.
     * - Ujian = template tahap ujian_akhir, jumlahnya dihitung sebagai jumlah Juz yang lulus.
     * - Nilai ujian/final hanya keluar jika santri sudah memiliki ujian akhir yang lulus.
     */
    private function hafalanAggregate(
        int $semesterId,
        string $startDate,
        string $endDate
    ) {
        $nilaiSql = $this->sqlNilaiLabelToAngka('hafalans');

        return DB::table('hafalans')
            ->leftJoin(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'hafalans.hafalan_template_id'
            )
            ->select(
                'hafalans.santri_id',
                DB::raw(
                    "SUM(CASE WHEN hafalans.status IN ('lulus', 'ulang') THEN 1 ELSE 0 END) AS total_setor"
                ),
                DB::raw(
                    "SUM(CASE
                        WHEN hafalans.status IN ('lulus', 'ulang')
                            AND ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                        THEN 1 ELSE 0
                    END) AS jumlah_setoran_harian"
                ),
                DB::raw(
                    "COUNT(DISTINCT CASE
                        WHEN hafalans.status = 'lulus'
                            AND ht.tahap = 'ujian_akhir'
                        THEN ht.juz ELSE NULL
                    END) AS jumlah_ujian"
                ),
                DB::raw(
                    "SUM(CASE
                        WHEN hafalans.status IN ('lulus', 'ulang')
                            AND ht.tahap = 'ujian_akhir'
                        THEN 1 ELSE 0
                    END) AS total_setoran_ujian"
                ),
                DB::raw(
                    "SUM(CASE WHEN hafalans.status = 'hadir_tidak_setor' THEN 1 ELSE 0 END) AS hadir_tidak_setor"
                ),
                DB::raw(
                    "SUM(CASE WHEN hafalans.status = 'sakit' THEN 1 ELSE 0 END) AS sakit"
                ),
                DB::raw(
                    "SUM(CASE WHEN hafalans.status = 'izin' THEN 1 ELSE 0 END) AS izin"
                ),
                DB::raw(
                    "SUM(CASE WHEN hafalans.status = 'alpha' THEN 1 ELSE 0 END) AS alpha"
                ),
                DB::raw(
                    "ROUND(AVG(CASE
                        WHEN hafalans.status IN ('lulus', 'ulang') THEN {$nilaiSql}
                        ELSE NULL
                    END), 2) AS rata_nilai"
                ),
                DB::raw(
                    "ROUND(LEAST(70, AVG(CASE
                        WHEN hafalans.status IN ('lulus', 'ulang')
                            AND ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                        THEN {$nilaiSql}
                        ELSE NULL
                    END)), 2) AS rata_nilai_sementara"
                ),
                DB::raw(
                    "ROUND(AVG(CASE
                        WHEN hafalans.status = 'lulus'
                            AND ht.tahap = 'ujian_akhir'
                        THEN {$nilaiSql}
                        ELSE NULL
                    END), 2) AS rata_nilai_ujian"
                ),
                DB::raw(
                    "SUM(CASE
                        WHEN hafalans.status IN ('lulus', 'ulang')
                            AND ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                        THEN {$nilaiSql} ELSE 0
                    END) AS total_nilai_harian"
                ),
                DB::raw(
                    "COUNT(CASE
                        WHEN hafalans.status IN ('lulus', 'ulang')
                            AND ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                            AND {$nilaiSql} IS NOT NULL
                        THEN 1 ELSE NULL
                    END) AS count_nilai_harian"
                ),
                DB::raw(
                    "SUM(CASE
                        WHEN hafalans.status = 'lulus'
                            AND ht.tahap = 'ujian_akhir'
                        THEN {$nilaiSql} ELSE 0
                    END) AS total_nilai_ujian"
                ),
                DB::raw(
                    "COUNT(CASE
                        WHEN hafalans.status = 'lulus'
                            AND ht.tahap = 'ujian_akhir'
                            AND {$nilaiSql} IS NOT NULL
                        THEN 1 ELSE NULL
                    END) AS count_nilai_ujian"
                ),
                DB::raw(
                    "MAX(CASE
                        WHEN hafalans.status = 'lulus'
                            AND ht.tahap = 'ujian_akhir'
                        THEN hafalans.tanggal_setoran ELSE NULL
                    END) AS tanggal_ujian_terakhir"
                )
            )
            ->where(
                'hafalans.semester_id',
                $semesterId
            )
            ->whereBetween(
                'hafalans.tanggal_setoran',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->groupBy('hafalans.santri_id');
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

            $items = Hafalan::query()
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
                ->get();

            $tahapHarian = $this->tahapHarianList();

            $harianItems = $items->filter(function ($item) use ($tahapHarian): bool {
                return in_array($item->status, ['lulus', 'ulang'], true)
                    && in_array($item->template?->tahap, $tahapHarian, true);
            });

            $harianScores = $harianItems
                ->map(fn($item) => $this->nilaiLabelToAngka($item->nilai_label))
                ->filter(fn($score) => $score !== null)
                ->values();

            $rataSementaraRaw = $harianScores->isNotEmpty()
                ? round((float) $harianScores->avg(), 2)
                : null;

            $rataSementara = $rataSementaraRaw !== null
                ? min(70, $rataSementaraRaw)
                : null;

            $ujianLulusItems = $items->filter(function ($item): bool {
                return $item->status === 'lulus'
                    && $item->template?->tahap === 'ujian_akhir';
            });

            $ujianScores = $ujianLulusItems
                ->map(fn($item) => $this->nilaiLabelToAngka($item->nilai_label))
                ->filter(fn($score) => $score !== null)
                ->values();

            $rataUjian = $ujianScores->isNotEmpty()
                ? round((float) $ujianScores->avg(), 2)
                : null;

            $ujianTerakhir = $ujianLulusItems
                ->sortByDesc('tanggal_setoran')
                ->first();

            $nilaiUjianTerakhir = $ujianTerakhir
                ? $this->nilaiLabelToAngka($ujianTerakhir->nilai_label)
                : null;

            $statusEvaluasi = 'Belum Ujian';
            $statusEvaluasiTone = 'secondary';

            if ($rataUjian !== null && $rataSementara !== null) {
                if ($rataUjian < $rataSementara) {
                    $statusEvaluasi = 'Ujian Mengulang';
                    $statusEvaluasiTone = 'danger';
                } else {
                    $statusEvaluasi = 'Ujian Memenuhi Nilai';
                    $statusEvaluasiTone = 'success';
                }
            } elseif ($rataUjian !== null) {
                $statusEvaluasi = 'Ujian Tercatat';
                $statusEvaluasiTone = 'success';
            }

            $riwayat = $items->map(function ($item) use ($tahapHarian) {
                $tanggal =
                    $item->tanggal_setoran
                    ?? optional(
                        $item->created_at
                    )->toDateString();

                $tahap = $item->template?->tahap ?? '-';
                $score = $this->nilaiLabelToAngka($item->nilai_label);

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
                    'juz' => $item->template?->juz,
                    'tahap' => $tahap,
                    'tahap_label' => match ($tahap) {
                        'harian' => 'Harian',
                        'tahap_1' => 'Tahap 1',
                        'tahap_2' => 'Tahap 2',
                        'tahap_3' => 'Tahap 3',
                        'ujian_akhir' => 'Ujian Akhir',
                        default => '-',
                    },
                    'kategori' => $tahap === 'ujian_akhir'
                        ? 'ujian'
                        : (in_array($tahap, $tahapHarian, true) ? 'harian' : 'lainnya'),
                    'status' =>
                    $item->status
                        ?? '-',
                    'nilai_label' =>
                    $item->nilai_label
                        ?? '-',
                    'nilai_angka' => $score,
                    'catatan' =>
                    $item->catatan
                        ?? '',
                ];
            });

            $summaryNilai = [
                'jumlah_setoran_harian' => $harianItems->count(),
                'jumlah_ujian_juz' => $ujianLulusItems
                    ->pluck('template.juz')
                    ->filter()
                    ->unique()
                    ->count(),
                'rata_nilai_sementara_raw' => $rataSementaraRaw,
                'rata_nilai_sementara' => $rataSementara,
                'rata_nilai_ujian' => $rataUjian,
                'nilai_ujian_terakhir' => $nilaiUjianTerakhir,
                'status_evaluasi' => $statusEvaluasi,
                'status_evaluasi_tone' => $statusEvaluasiTone,
            ];

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
                'summary_nilai' => $summaryNilai,
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
                'h.jumlah_setoran_harian',
                'h.jumlah_ujian',
                'h.total_setoran_ujian',
                'h.hadir_tidak_setor',
                'h.sakit',
                'h.izin',
                'h.alpha',
                'h.rata_nilai',
                'h.rata_nilai_sementara',
                'h.rata_nilai_ujian',
                'h.tanggal_ujian_terakhir'
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
                'jumlah_setoran_harian',
                fn($row) =>
                (int) (
                    $row->jumlah_setoran_harian
                    ?? 0
                )
            )
            ->editColumn(
                'jumlah_ujian',
                fn($row) =>
                (int) (
                    $row->jumlah_ujian
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
                'rata_nilai_ujian',
                fn($row) =>
                is_null(
                    $row->rata_nilai_ujian
                )
                    ? '-'
                    : number_format(
                        (float) $row
                            ->rata_nilai_ujian,
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
                    'COALESCE(SUM(h.jumlah_setoran_harian), 0) AS jumlah_setoran_harian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.jumlah_ujian), 0) AS jumlah_ujian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setoran_ujian), 0) AS total_setoran_ujian'
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
                    'ROUND(SUM(h.total_nilai_ujian) / NULLIF(SUM(h.count_nilai_ujian), 0), 2) AS rata_nilai_ujian'
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
                'jumlah_setoran_harian',
                fn($row) =>
                (int) (
                    $row->jumlah_setoran_harian
                    ?? 0
                )
            )
            ->editColumn(
                'jumlah_ujian',
                fn($row) =>
                (int) (
                    $row->jumlah_ujian
                    ?? 0
                )
            )
            ->editColumn(
                'total_setoran_ujian',
                fn($row) =>
                (int) (
                    $row->total_setoran_ujian
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
                'rata_nilai_ujian',
                fn($row) =>
                is_null(
                    $row->rata_nilai_ujian
                )
                    ? '-'
                    : number_format(
                        (float) $row
                            ->rata_nilai_ujian,
                        2
                    )
            )
            ->addColumn(
                'aksi',
                function ($row) {
                    return '
                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-kelas-juz-report"
                            data-id="'
                        . (int) $row->id
                        . '"
                            data-kelas="'
                        . e($row->nama_kelas)
                        . '"
                            data-coreui-toggle="tooltip"
                            title="Raport kelulusan ujian akhir per Juz">
                            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Raport Juz
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
     * Detail rekap satu musyrif untuk modal drill-down.
     * Data tetap memakai placement semester dan agregasi hafalan yang sama dengan tabel utama.
     */
    private function buildRekapMusyrifDetail(
        Request $request,
        array $context,
        ?int $kelasId
    ) {
        $musyrifDetailId = $request->filled('musyrif_detail_id')
            ? (int) $request->input('musyrif_detail_id')
            : null;

        if (!$musyrifDetailId) {
            throw ValidationException::withMessages([
                'musyrif_detail_id' => [
                    'Musyrif belum dipilih.',
                ],
            ]);
        }

        $musyrif = Musyrif::query()
            ->findOrFail($musyrifDetailId);

        $hafalanAgg = $this->hafalanAggregate(
            $context['semester_id'],
            $context['start_date'],
            $context['end_date']
        );

        $rows = Santri::query()
            ->join(
                'santri_semester_placements as sp',
                function ($join) use (
                    $context,
                    $musyrifDetailId,
                    $kelasId
                ): void {
                    $join
                        ->on(
                            'sp.santri_id',
                            '=',
                            'santris.id'
                        )
                        ->where(
                            'sp.semester_id',
                            '=',
                            $context['semester_id']
                        )
                        ->where(
                            'sp.musyrif_id',
                            '=',
                            $musyrifDetailId
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
                'kelas as k',
                'k.id',
                '=',
                'sp.kelas_id'
            )
            ->leftJoinSub(
                $hafalanAgg,
                'h',
                'h.santri_id',
                '=',
                'santris.id'
            )
            ->select(
                'santris.id',
                'santris.nama',
                'santris.nis',
                'sp.kelas_id',
                'k.nama_kelas',
                DB::raw('COALESCE(h.total_setor, 0) AS total_setor'),
                DB::raw('COALESCE(h.jumlah_setoran_harian, 0) AS jumlah_setoran_harian'),
                DB::raw('COALESCE(h.jumlah_ujian, 0) AS jumlah_ujian'),
                DB::raw('COALESCE(h.total_setoran_ujian, 0) AS total_setoran_ujian'),
                DB::raw('COALESCE(h.hadir_tidak_setor, 0) AS hadir_tidak_setor'),
                DB::raw('COALESCE(h.sakit, 0) AS sakit'),
                DB::raw('COALESCE(h.izin, 0) AS izin'),
                DB::raw('COALESCE(h.alpha, 0) AS alpha'),
                'h.rata_nilai_sementara',
                'h.rata_nilai_ujian',
                'h.tanggal_ujian_terakhir'
            )
            ->orderBy('k.nama_kelas')
            ->orderBy('santris.nama')
            ->get();

        $totalSantri = $rows->count();

        $santriAktif = $rows
            ->filter(
                fn($row) => ((int) $row->jumlah_setoran_harian) > 0
                    || ((int) $row->total_setoran_ujian) > 0
            )
            ->count();

        $santriSudahUjian = $rows
            ->filter(
                fn($row) => ((int) $row->jumlah_ujian) > 0
            )
            ->count();

        $coverageUjianPct = $totalSantri > 0
            ? round(($santriSudahUjian / $totalSantri) * 100, 1)
            : 0;

        $nilaiSementara = $rows
            ->pluck('rata_nilai_sementara')
            ->filter(fn($value) => $value !== null);

        $nilaiUjian = $rows
            ->pluck('rata_nilai_ujian')
            ->filter(fn($value) => $value !== null);

        $kelasGroups = $rows
            ->groupBy(
                fn($row) =>
                $row->nama_kelas ?: 'Tanpa Kelas'
            )
            ->map(function (Collection $items, string $kelasName) {
                $santri = $items->map(function ($row) {
                    $jumlahHarian = (int) $row->jumlah_setoran_harian;
                    $jumlahUjian = (int) $row->jumlah_ujian;
                    $totalUjian = (int) $row->total_setoran_ujian;

                    $statusLabel = 'Belum Setor';
                    $statusTone = 'secondary';

                    if ($jumlahUjian > 0) {
                        $statusLabel = 'Sudah Ujian';
                        $statusTone = 'success';
                    } elseif ($jumlahHarian > 0 || $totalUjian > 0) {
                        $statusLabel = 'Proses Setoran';
                        $statusTone = 'primary';
                    }

                    return [
                        'id' => (int) $row->id,
                        'nama' => $row->nama,
                        'nis' => $row->nis ?: '-',
                        'jumlah_setoran_harian' => $jumlahHarian,
                        'jumlah_ujian' => $jumlahUjian,
                        'total_setoran_ujian' => $totalUjian,
                        'hadir_tidak_setor' => (int) $row->hadir_tidak_setor,
                        'sakit' => (int) $row->sakit,
                        'izin' => (int) $row->izin,
                        'alpha' => (int) $row->alpha,
                        'rata_nilai_sementara' => $row->rata_nilai_sementara !== null
                            ? round((float) $row->rata_nilai_sementara, 2)
                            : null,
                        'rata_nilai_ujian' => $row->rata_nilai_ujian !== null
                            ? round((float) $row->rata_nilai_ujian, 2)
                            : null,
                        'tanggal_ujian_terakhir' => $row->tanggal_ujian_terakhir
                            ? Carbon::parse($row->tanggal_ujian_terakhir)->translatedFormat('d M Y')
                            : '-',
                        'status_label' => $statusLabel,
                        'status_tone' => $statusTone,
                    ];
                })->values();

                return [
                    'nama_kelas' => $kelasName,
                    'total_santri' => $items->count(),
                    'santri_aktif' => $santri
                        ->filter(
                            fn(array $item) =>
                            $item['jumlah_setoran_harian'] > 0
                                || $item['total_setoran_ujian'] > 0
                        )
                        ->count(),
                    'santri_sudah_ujian' => $santri
                        ->where('jumlah_ujian', '>', 0)
                        ->count(),
                    'jumlah_setoran_harian' => $items->sum('jumlah_setoran_harian'),
                    'jumlah_ujian' => $items->sum('jumlah_ujian'),
                    'santri' => $santri,
                ];
            })
            ->values();

        return response()->json([
            'musyrif' => [
                'id' => (int) $musyrif->id,
                'nama' => $musyrif->nama,
            ],
            'semester_label' => $context['semester_label'],
            'period_label' => $context['period_label'],
            'summary' => [
                'total_santri' => $totalSantri,
                'santri_aktif_setoran' => $santriAktif,
                'santri_sudah_ujian' => $santriSudahUjian,
                'coverage_ujian_pct' => $coverageUjianPct,
                'jumlah_setoran_harian' => (int) $rows->sum('jumlah_setoran_harian'),
                'jumlah_ujian' => (int) $rows->sum('jumlah_ujian'),
                'total_setoran_ujian' => (int) $rows->sum('total_setoran_ujian'),
                'hadir_tidak_setor' => (int) $rows->sum('hadir_tidak_setor'),
                'sakit' => (int) $rows->sum('sakit'),
                'izin' => (int) $rows->sum('izin'),
                'alpha' => (int) $rows->sum('alpha'),
                'rata_nilai_sementara' => $nilaiSementara->isNotEmpty()
                    ? round((float) $nilaiSementara->avg(), 2)
                    : null,
                'rata_nilai_ujian' => $nilaiUjian->isNotEmpty()
                    ? round((float) $nilaiUjian->avg(), 2)
                    : null,
            ],
            'kelas' => $kelasGroups,
        ]);
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

        if ($request->boolean('detail')) {
            return $this->buildRekapMusyrifDetail(
                $request,
                $context,
                $kelasId
            );
        }

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
                    "COUNT(DISTINCT CASE
                        WHEN COALESCE(h.jumlah_setoran_harian, 0) > 0
                            OR COALESCE(h.total_setoran_ujian, 0) > 0
                        THEN sp.santri_id ELSE NULL
                    END) AS santri_aktif_setoran"
                ),
                DB::raw(
                    "COUNT(DISTINCT CASE
                        WHEN COALESCE(h.jumlah_ujian, 0) > 0
                        THEN sp.santri_id ELSE NULL
                    END) AS santri_sudah_ujian"
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setor), 0) AS total_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.jumlah_setoran_harian), 0) AS jumlah_setoran_harian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.jumlah_ujian), 0) AS jumlah_ujian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setoran_ujian), 0) AS total_setoran_ujian'
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
                ),
                DB::raw(
                    'ROUND(LEAST(70, SUM(h.total_nilai_harian) / NULLIF(SUM(h.count_nilai_harian), 0)), 2) AS rata_nilai_sementara'
                ),
                DB::raw(
                    'ROUND(SUM(h.total_nilai_ujian) / NULLIF(SUM(h.count_nilai_ujian), 0), 2) AS rata_nilai_ujian'
                ),
                DB::raw(
                    "ROUND(
                        (
                            COUNT(DISTINCT CASE
                                WHEN COALESCE(h.jumlah_ujian, 0) > 0
                                THEN sp.santri_id ELSE NULL
                            END) / NULLIF(COUNT(DISTINCT sp.santri_id), 0)
                        ) * 100,
                        1
                    ) AS coverage_ujian_pct"
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
                'nama',
                function ($row) {
                    $nama = e($row->nama ?? '-');
                    $aktif = (int) ($row->santri_aktif_setoran ?? 0);
                    $total = (int) ($row->jumlah_santri ?? 0);

                    return "
                        <div class='fw-bold'>{$nama}</div>
                        <div class='small text-muted'>Aktif setor: {$aktif}/{$total} santri</div>
                    ";
                }
            )
            ->editColumn(
                'jumlah_santri',
                fn($row) =>
                (int) (
                    $row->jumlah_santri
                    ?? 0
                )
            )
            ->addColumn(
                'santri_aktif',
                function ($row) {
                    $aktif = (int) ($row->santri_aktif_setoran ?? 0);
                    $total = (int) ($row->jumlah_santri ?? 0);
                    $pct = $total > 0
                        ? round(($aktif / $total) * 100, 1)
                        : 0;

                    return "
                        <div class='fw-bold'>{$aktif}/{$total}</div>
                        <div class='progress-thin mt-1' style='min-width:90px;'>
                            <span style='width: {$pct}%; background: var(--report-purple);'></span>
                        </div>
                        <div class='small text-muted mt-1'>{$pct}% aktif</div>
                    ";
                }
            )
            ->editColumn(
                'jumlah_setoran_harian',
                fn($row) =>
                (int) (
                    $row->jumlah_setoran_harian
                    ?? 0
                )
            )
            ->editColumn(
                'jumlah_ujian',
                fn($row) =>
                (int) (
                    $row->jumlah_ujian
                    ?? 0
                )
            )
            ->addColumn(
                'coverage_ujian',
                function ($row) {
                    $sudah = (int) ($row->santri_sudah_ujian ?? 0);
                    $total = (int) ($row->jumlah_santri ?? 0);
                    $pct = round((float) ($row->coverage_ujian_pct ?? 0), 1);

                    return "
                        <div class='fw-bold'>{$sudah}/{$total} santri</div>
                        <div class='progress-thin mt-1' style='min-width:100px;'>
                            <span style='width: {$pct}%; background: var(--report-success);'></span>
                        </div>
                        <div class='small text-muted mt-1'>{$pct}% sudah ujian</div>
                    ";
                }
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
                'rata_nilai_ujian',
                fn($row) =>
                is_null(
                    $row->rata_nilai_ujian
                )
                    ? '-'
                    : number_format(
                        (float) $row
                            ->rata_nilai_ujian,
                        2
                    )
            )
            ->addColumn(
                'aksi',
                function ($row) {
                    return '
                        <button
                            type="button"
                            class="btn btn-sm btn-primary btn-detail-musyrif-progress"
                            data-id="'
                        . (int) $row->id
                        . '"
                            data-nama="'
                        . e($row->nama)
                        . '"
                            data-coreui-toggle="tooltip"
                            title="Lihat rincian progress santri binaan">
                            <i class="bi bi-people-fill me-1"></i> Detail
                        </button>
                    ';
                }
            )
            ->rawColumns([
                'nama',
                'santri_aktif',
                'coverage_ujian',
                'aksi',
            ])
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

        $isSetoranMode = $request->boolean('setoran')
            || $request->input('mode') === 'setoran';

        if ($isSetoranMode) {
            if ($request->boolean('detail')) {
                return $this->getChartJuzSetoranDetailPayload(
                    $request,
                    $context,
                    $kelasId,
                    $musyrifId
                );
            }

            return $this->getChartJuzSetoranPayload(
                $request,
                $context,
                $kelasId,
                $musyrifId
            );
        }

        if ($request->boolean('detail')) {
            return $this->getChartJuzLulusDetailPayload(
                $request,
                $context,
                $kelasId,
                $musyrifId
            );
        }

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

        $totalJuzLulus = array_sum($data);

        /*
         * Jumlah santri yang PERNAH mengikuti ujian akhir per Juz,
         * baik hasilnya lulus maupun ulang. Data ini dipakai di UI Raport Juz
         * untuk membedakan:
         * - belum pernah diujiankan  => card terkunci/gembok
         * - sudah diujiankan tetapi belum ada yang lulus => card aktif namun 0 lulus
         */
        $testedRows = DB::table(
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
            ->whereIn(
                'h.status',
                [
                    'lulus',
                    'ulang',
                ]
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

        $testedMap = $testedRows->pluck(
            'jumlah',
            'juz'
        );

        $testedData = array_map(
            fn($juz) =>
            (int) (
                $testedMap[$juz]
                ?? 0
            ),
            $labels
        );

        $totalJuzDiujiankan = array_sum($testedData);

        $totalSantriLulusUjian = DB::table(
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
            ->distinct('h.santri_id')
            ->count('h.santri_id');

        $kelasLabel = $kelasId
            ? (Kelas::query()->whereKey($kelasId)->value('nama_kelas') ?: 'Kelas terpilih')
            : 'Semua Kelas';

        return response()->json([
            'labels' => array_map(
                fn($juz) =>
                "Juz {$juz}",
                $labels
            ),
            'data' => $data,
            'kelas_id' => $kelasId,
            'kelas_label' => $kelasLabel,
            'period_label' => $context['period_label'],
            'semester_label' => $context['semester_label'],
            'total_juz_lulus' => $totalJuzLulus,
            'tested_data' => $testedData,
            'total_juz_diujiankan' => $totalJuzDiujiankan,
            'total_santri_lulus_ujian' => $totalSantriLulusUjian,
        ]);
    }

    /**
     * Detail santri yang sudah lulus ujian akhir pada Juz tertentu.
     * Dipakai oleh grafik Kelulusan Ujian Akhir per Juz dengan query detail=1.
     */
    private function getChartJuzLulusDetailPayload(
        Request $request,
        array $context,
        ?int $kelasId,
        ?int $musyrifId
    ) {
        $validated = $request->validate([
            'juz' => [
                'required',
                'integer',
                'min:1',
                'max:30',
            ],
        ]);

        $juz = (int) $validated['juz'];

        $rows = DB::table('hafalans as h')
            ->join(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'h.hafalan_template_id'
            )
            ->join(
                'santri_semester_placements as sp',
                function ($join) use ($context): void {
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
            ->join(
                'santris as s',
                's.id',
                '=',
                'h.santri_id'
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
                'h.semester_id',
                $context['semester_id']
            )
            ->where(
                'ht.tahap',
                'ujian_akhir'
            )
            ->where(
                'ht.juz',
                $juz
            )
            ->where(
                'h.status',
                'lulus'
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
                's.id as santri_id',
                's.nama as santri_nama',
                's.nis',
                'k.id as kelas_id',
                'k.nama_kelas',
                'm.nama as musyrif_nama',
                DB::raw('MAX(h.tanggal_setoran) as tanggal_lulus'),
                DB::raw('COUNT(*) as total_record')
            )
            ->groupBy(
                's.id',
                's.nama',
                's.nis',
                'k.id',
                'k.nama_kelas',
                'm.nama'
            )
            ->orderBy('k.nama_kelas')
            ->orderBy('s.nama')
            ->get();

        $kelasGroups = $rows
            ->groupBy(fn($row) => $row->nama_kelas ?: 'Tanpa Kelas')
            ->map(function ($items, $kelasNama) {
                $first = $items->first();

                return [
                    'kelas_id' => $first->kelas_id,
                    'kelas_nama' => $kelasNama,
                    'total' => $items->count(),
                    'santri' => $items
                        ->values()
                        ->map(function ($row, $index) {
                            return [
                                'no' => $index + 1,
                                'id' => $row->santri_id,
                                'nama' => $row->santri_nama,
                                'nis' => $row->nis ?: '-',
                                'musyrif' => $row->musyrif_nama ?: '-',
                                'tanggal_lulus' => $row->tanggal_lulus
                                    ? Carbon::parse($row->tanggal_lulus)
                                    ->translatedFormat('d M Y')
                                    : '-',
                            ];
                        })
                        ->all(),
                ];
            })
            ->values();

        return response()->json([
            'mode' => 'lulus',
            'juz' => $juz,
            'title' => "Juz {$juz}",
            'detail_heading' => 'Detail Kelulusan Ujian Akhir',
            'detail_title' => "Kelulusan Ujian Akhir: Juz {$juz}",
            'date_label' => 'Lulus',
            'empty_message' => "Belum ada santri yang lulus ujian akhir Juz {$juz} pada filter aktif.",
            'period_label' => $context['period_label'],
            'semester_label' => $context['semester_label'],
            'total' => $rows->count(),
            'kelas_count' => $kelasGroups->count(),
            'groups' => $kelasGroups,
        ]);
    }

    /**
     * Grafik progress setoran santri per Juz.
     *
     * Berbeda dari Kelulusan Ujian Akhir per Juz:
     * - grafik ini hanya menghitung proses setoran harian/tahap_1/tahap_2/tahap_3;
     * - nilai bar = jumlah santri unik yang memiliki progress setoran pada Juz tersebut;
     * - ujian_akhir tidak dihitung di sini supaya tidak tercampur dengan chart kelulusan ujian.
     */
    private function getChartJuzSetoranPayload(
        Request $request,
        array $context,
        ?int $kelasId,
        ?int $musyrifId
    ) {
        $labels = range(1, 30);
        $tahapHarian = $this->tahapHarianList();

        $baseQuery = DB::table('hafalans as h')
            ->join(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'h.hafalan_template_id'
            )
            ->join(
                'santri_semester_placements as sp',
                function ($join) use ($context): void {
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
            ->whereIn(
                'ht.tahap',
                $tahapHarian
            )
            ->whereIn(
                'h.status',
                [
                    'lulus',
                    'ulang',
                ]
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
            );

        $rows = (clone $baseQuery)
            ->select(
                'ht.juz',
                DB::raw(
                    'COUNT(DISTINCT h.santri_id) AS jumlah'
                ),
                DB::raw(
                    'COUNT(*) AS total_setoran'
                )
            )
            ->groupBy('ht.juz')
            ->orderBy('ht.juz')
            ->get();

        $map = $rows->pluck(
            'jumlah',
            'juz'
        );

        $setoranMap = $rows->pluck(
            'total_setoran',
            'juz'
        );

        $data = array_map(
            fn($juz) =>
            (int) (
                $map[$juz]
                ?? 0
            ),
            $labels
        );

        $setoranData = array_map(
            fn($juz) =>
            (int) (
                $setoranMap[$juz]
                ?? 0
            ),
            $labels
        );

        $totalProgressJuz = array_sum($data);
        $totalSetoran = array_sum($setoranData);

        $totalSantriProgress = (clone $baseQuery)
            ->distinct('h.santri_id')
            ->count('h.santri_id');

        $kelasLabel = $kelasId
            ? (Kelas::query()->whereKey($kelasId)->value('nama_kelas') ?: 'Kelas terpilih')
            : 'Semua Kelas';

        return response()->json([
            'mode' => 'setoran',
            'labels' => array_map(
                fn($juz) =>
                "Juz {$juz}",
                $labels
            ),
            'data' => $data,
            'setoran_data' => $setoranData,
            'kelas_id' => $kelasId,
            'kelas_label' => $kelasLabel,
            'period_label' => $context['period_label'],
            'semester_label' => $context['semester_label'],
            'total_progress_juz' => $totalProgressJuz,
            'total_setoran' => $totalSetoran,
            'total_santri_progress' => $totalSantriProgress,
        ]);
    }

    /**
     * Detail santri yang memiliki progress setoran pada Juz tertentu.
     * Dipakai oleh grafik Progress Setoran per Juz dengan query setoran=1&detail=1.
     */
    private function getChartJuzSetoranDetailPayload(
        Request $request,
        array $context,
        ?int $kelasId,
        ?int $musyrifId
    ) {
        $validated = $request->validate([
            'juz' => [
                'required',
                'integer',
                'min:1',
                'max:30',
            ],
        ]);

        $juz = (int) $validated['juz'];
        $tahapHarian = $this->tahapHarianList();
        $nilaiSql = $this->sqlNilaiLabelToAngka('h');
        $tahapRankSql = $this->tahapHafalanRankSql('ht');

        $rows = DB::table('hafalans as h')
            ->join(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'h.hafalan_template_id'
            )
            ->join(
                'santri_semester_placements as sp',
                function ($join) use ($context): void {
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
            ->join(
                'santris as s',
                's.id',
                '=',
                'h.santri_id'
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
                'h.semester_id',
                $context['semester_id']
            )
            ->whereIn(
                'ht.tahap',
                $tahapHarian
            )
            ->where(
                'ht.juz',
                $juz
            )
            ->whereIn(
                'h.status',
                [
                    'lulus',
                    'ulang',
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
                's.id as santri_id',
                's.nama as santri_nama',
                's.nis',
                'k.id as kelas_id',
                'k.nama_kelas',
                'm.nama as musyrif_nama',
                DB::raw('COUNT(*) as jumlah_setoran'),
                DB::raw("MAX({$tahapRankSql}) as tahap_rank"),
                DB::raw("ROUND(LEAST(70, AVG({$nilaiSql})), 2) as nilai_sementara"),
                DB::raw('MAX(h.tanggal_setoran) as tanggal_terakhir')
            )
            ->groupBy(
                's.id',
                's.nama',
                's.nis',
                'k.id',
                'k.nama_kelas',
                'm.nama'
            )
            ->orderBy('k.nama_kelas')
            ->orderByDesc('tahap_rank')
            ->orderBy('s.nama')
            ->get();

        $kelasGroups = $rows
            ->groupBy(fn($row) => $row->nama_kelas ?: 'Tanpa Kelas')
            ->map(function ($items, $kelasNama) {
                $first = $items->first();

                return [
                    'kelas_id' => $first->kelas_id,
                    'kelas_nama' => $kelasNama,
                    'total' => $items->count(),
                    'santri' => $items
                        ->values()
                        ->map(function ($row, $index) {
                            $tahap = $this->tahapHafalanFromRank(
                                (int) (
                                    $row->tahap_rank
                                    ?? 0
                                )
                            );

                            $tanggalTerakhir = $row->tanggal_terakhir
                                ? Carbon::parse($row->tanggal_terakhir)
                                ->translatedFormat('d M Y')
                                : '-';

                            return [
                                'no' => $index + 1,
                                'id' => $row->santri_id,
                                'nama' => $row->santri_nama,
                                'nis' => $row->nis ?: '-',
                                'musyrif' => $row->musyrif_nama ?: '-',
                                'tahap' => $tahap,
                                'tahap_label' => $this->tahapHafalanLabel($tahap),
                                'jumlah_setoran' => (int) (
                                    $row->jumlah_setoran
                                    ?? 0
                                ),
                                'nilai_sementara' => is_null($row->nilai_sementara)
                                    ? '-'
                                    : number_format(
                                        (float) $row->nilai_sementara,
                                        2
                                    ),
                                'tanggal_terakhir' => $tanggalTerakhir,
                                'tanggal_lulus' => $tanggalTerakhir,
                            ];
                        })
                        ->all(),
                ];
            })
            ->values();

        return response()->json([
            'mode' => 'setoran',
            'juz' => $juz,
            'title' => "Juz {$juz}",
            'detail_heading' => 'Detail Progress Setoran',
            'detail_title' => "Progress Setoran: Juz {$juz}",
            'date_label' => 'Setoran terakhir',
            'empty_message' => "Belum ada santri yang memiliki progress setoran Juz {$juz} pada filter aktif.",
            'period_label' => $context['period_label'],
            'semester_label' => $context['semester_label'],
            'total' => $rows->count(),
            'total_setoran' => (int) $rows->sum('jumlah_setoran'),
            'kelas_count' => $kelasGroups->count(),
            'groups' => $kelasGroups,
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
                'h.jumlah_setoran_harian',
                'h.jumlah_ujian',
                'h.total_setoran_ujian',
                'h.hadir_tidak_setor',
                'h.sakit',
                'h.izin',
                'h.alpha',
                'h.rata_nilai',
                'h.rata_nilai_sementara',
                'h.rata_nilai_ujian',
                'h.tanggal_ujian_terakhir'
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
                    'COALESCE(SUM(h.jumlah_setoran_harian), 0) AS jumlah_setoran_harian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.jumlah_ujian), 0) AS jumlah_ujian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setoran_ujian), 0) AS total_setoran_ujian'
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
                ),
                DB::raw(
                    'ROUND(LEAST(70, SUM(h.total_nilai_harian) / NULLIF(SUM(h.count_nilai_harian), 0)), 2) AS rata_nilai_sementara'
                ),
                DB::raw(
                    'ROUND(SUM(h.total_nilai_ujian) / NULLIF(SUM(h.count_nilai_ujian), 0), 2) AS rata_nilai_ujian'
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
                    "COUNT(DISTINCT CASE
                        WHEN COALESCE(h.jumlah_setoran_harian, 0) > 0
                            OR COALESCE(h.total_setoran_ujian, 0) > 0
                        THEN sp.santri_id ELSE NULL
                    END) AS santri_aktif_setoran"
                ),
                DB::raw(
                    "COUNT(DISTINCT CASE
                        WHEN COALESCE(h.jumlah_ujian, 0) > 0
                        THEN sp.santri_id ELSE NULL
                    END) AS santri_sudah_ujian"
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setor), 0) AS total_setor'
                ),
                DB::raw(
                    'COALESCE(SUM(h.jumlah_setoran_harian), 0) AS jumlah_setoran_harian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.jumlah_ujian), 0) AS jumlah_ujian'
                ),
                DB::raw(
                    'COALESCE(SUM(h.total_setoran_ujian), 0) AS total_setoran_ujian'
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
                ),
                DB::raw(
                    'ROUND(LEAST(70, SUM(h.total_nilai_harian) / NULLIF(SUM(h.count_nilai_harian), 0)), 2) AS rata_nilai_sementara'
                ),
                DB::raw(
                    'ROUND(SUM(h.total_nilai_ujian) / NULLIF(SUM(h.count_nilai_ujian), 0), 2) AS rata_nilai_ujian'
                ),
                DB::raw(
                    "ROUND(
                        (
                            COUNT(DISTINCT CASE
                                WHEN COALESCE(h.jumlah_ujian, 0) > 0
                                THEN sp.santri_id ELSE NULL
                            END) / NULLIF(COUNT(DISTINCT sp.santri_id), 0)
                        ) * 100,
                        1
                    ) AS coverage_ujian_pct"
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
        $nilaiUjianValid = $data->filter(
            fn($row) => !is_null($row->rata_nilai_ujian)
        );

        $nilaiSementaraValid = $data->filter(
            fn($row) => !is_null($row->rata_nilai_sementara)
        );

        return [
            'summary' => [
                'total_santri' => (int) $data->count(),
                'total_setoran_harian' => (int) $data->sum(
                    fn($row) => (int) ($row->jumlah_setoran_harian ?? 0)
                ),
                'total_ujian_juz' => (int) $data->sum(
                    fn($row) => (int) ($row->jumlah_ujian ?? 0)
                ),
                'total_hts' => (int) $data->sum(
                    fn($row) => (int) ($row->hadir_tidak_setor ?? 0)
                ),
                'total_sakit' => (int) $data->sum(
                    fn($row) => (int) ($row->sakit ?? 0)
                ),
                'total_izin' => (int) $data->sum(
                    fn($row) => (int) ($row->izin ?? 0)
                ),
                'total_alpha' => (int) $data->sum(
                    fn($row) => (int) ($row->alpha ?? 0)
                ),
                'avg_nilai_sementara' => round(
                    (float) ($nilaiSementaraValid->avg('rata_nilai_sementara') ?? 0),
                    2
                ),
                'avg_nilai_ujian' => round(
                    (float) ($nilaiUjianValid->avg('rata_nilai_ujian') ?? 0),
                    2
                ),
                'santri_aktif' => (int) $data
                    ->filter(
                        fn($row) =>
                        (int) ($row->jumlah_setoran_harian ?? 0) > 0
                            || (int) ($row->total_setoran_ujian ?? 0) > 0
                    )
                    ->count(),
                'santri_sudah_ujian' => (int) $data
                    ->filter(
                        fn($row) => (int) ($row->jumlah_ujian ?? 0) > 0
                    )
                    ->count(),
            ],
            'topSantri' => $nilaiUjianValid
                ->sortByDesc('rata_nilai_ujian')
                ->take(10)
                ->values(),
            'statusDistribution' => [
                'mumtaz' => $nilaiUjianValid->where('rata_nilai_ujian', '>=', 90)->count(),
                'jayyid_jiddan' => $nilaiUjianValid->whereBetween('rata_nilai_ujian', [80, 89.99])->count(),
                'jayyid' => $nilaiUjianValid->whereBetween('rata_nilai_ujian', [70, 79.99])->count(),
                'mardud' => $nilaiUjianValid->where('rata_nilai_ujian', '<', 70)->count(),
            ],
        ];
    }


    /**
     * Membuat data visual 30 Juz untuk export PDF.
     *
     * Dipakai oleh export Santri, Kelas, dan Musyrif agar angka visual
     * tetap mengikuti filter semester/periode/kelas/musyrif yang sama
     * dengan tabel utama.
     */
    private function buildPdfJuzProgress(
        Request $request,
        string $groupLabel
    ): array {
        $kelasId = $request->filled('kelas_id')
            ? (int) $request->input('kelas_id')
            : null;

        $musyrifId = $request->filled('musyrif_id')
            ? (int) $request->input('musyrif_id')
            : null;

        $context = $this->resolveReportContext($request);

        $santriIds = $this
            ->filteredSantriQuery(
                $context['semester_id'],
                $kelasId,
                $musyrifId
            )
            ->distinct()
            ->pluck('santris.id');

        $totalSantri = (int) $santriIds->count();

        $emptyItems = collect(range(1, 30))->map(function (int $juz): array {
            return [
                'juz' => $juz,
                'setoran_santri' => 0,
                'setoran_records' => 0,
                'ujian_santri' => 0,
                'ujian_records' => 0,
                'setoran_pct' => 0,
                'ujian_pct' => 0,
                'level' => 'empty',
                'level_label' => 'Belum ada progress',
            ];
        });

        if ($santriIds->isEmpty()) {
            return [
                'group_label' => $groupLabel,
                'total_santri' => 0,
                'total_setoran_santri' => 0,
                'total_setoran_records' => 0,
                'total_ujian_santri' => 0,
                'total_ujian_records' => 0,
                'avg_setoran_pct' => 0,
                'avg_ujian_pct' => 0,
                'completed_juz_count' => 0,
                'active_juz_count' => 0,
                'top_juz' => null,
                'needs_attention' => $emptyItems->take(5)->values(),
                'items' => $emptyItems->values(),
            ];
        }

        $rows = DB::table('hafalans as h')
            ->join(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'h.hafalan_template_id'
            )
            ->where('h.semester_id', $context['semester_id'])
            ->whereIn('h.santri_id', $santriIds)
            ->whereBetween(
                'h.tanggal_setoran',
                [
                    $context['start_date'],
                    $context['end_date'],
                ]
            )
            ->whereIn('h.status', [
                'lulus',
                'ulang',
            ])
            ->select('ht.juz')
            ->selectRaw(
                "COUNT(DISTINCT CASE
                    WHEN ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                    THEN h.santri_id ELSE NULL
                END) AS setoran_santri"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                    THEN 1 ELSE 0
                END) AS setoran_records"
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE
                    WHEN h.status = 'lulus'
                        AND ht.tahap = 'ujian_akhir'
                    THEN h.santri_id ELSE NULL
                END) AS ujian_santri"
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE
                    WHEN h.status = 'lulus'
                        AND ht.tahap = 'ujian_akhir'
                    THEN CONCAT(h.santri_id, ':', ht.juz) ELSE NULL
                END) AS ujian_records"
            )
            ->groupBy('ht.juz')
            ->get()
            ->keyBy('juz');

        $items = collect(range(1, 30))->map(
            function (int $juz) use ($rows, $totalSantri): array {
                $row = $rows->get($juz);

                $setoranSantri = (int) ($row->setoran_santri ?? 0);
                $setoranRecords = (int) ($row->setoran_records ?? 0);
                $ujianSantri = (int) ($row->ujian_santri ?? 0);
                $ujianRecords = (int) ($row->ujian_records ?? 0);

                $setoranPct = $totalSantri > 0
                    ? round(($setoranSantri / $totalSantri) * 100, 1)
                    : 0;

                $ujianPct = $totalSantri > 0
                    ? round(($ujianSantri / $totalSantri) * 100, 1)
                    : 0;

                [$level, $levelLabel] = match (true) {
                    $ujianPct >= 75 => ['excellent', 'Sangat kuat'],
                    $ujianPct >= 50 => ['good', 'Baik'],
                    $ujianPct >= 25 => ['progress', 'Mulai kuat'],
                    $ujianPct > 0 => ['started', 'Sudah ada ujian'],
                    $setoranPct > 0 => ['setoran', 'Masih proses setoran'],
                    default => ['empty', 'Belum ada progress'],
                };

                return [
                    'juz' => $juz,
                    'setoran_santri' => $setoranSantri,
                    'setoran_records' => $setoranRecords,
                    'ujian_santri' => $ujianSantri,
                    'ujian_records' => $ujianRecords,
                    'setoran_pct' => $setoranPct,
                    'ujian_pct' => $ujianPct,
                    'level' => $level,
                    'level_label' => $levelLabel,
                ];
            }
        )->values();

        $activeItems = $items
            ->filter(
                fn(array $item) =>
                (int) $item['setoran_santri'] > 0
                    || (int) $item['ujian_santri'] > 0
            )
            ->values();

        $topJuz = $items
            ->sortByDesc('ujian_santri')
            ->sortByDesc('setoran_santri')
            ->first();

        $needsAttention = $items
            ->filter(
                fn(array $item) =>
                (int) $item['ujian_santri'] === 0
            )
            ->sortByDesc('setoran_santri')
            ->take(5)
            ->values();

        return [
            'group_label' => $groupLabel,
            'total_santri' => $totalSantri,
            'total_setoran_santri' => (int) $items->sum('setoran_santri'),
            'total_setoran_records' => (int) $items->sum('setoran_records'),
            'total_ujian_santri' => (int) $items->sum('ujian_santri'),
            'total_ujian_records' => (int) $items->sum('ujian_records'),
            'avg_setoran_pct' => round((float) ($items->avg('setoran_pct') ?? 0), 1),
            'avg_ujian_pct' => round((float) ($items->avg('ujian_pct') ?? 0), 1),
            'completed_juz_count' => (int) $items
                ->filter(fn(array $item) => (float) $item['ujian_pct'] >= 100)
                ->count(),
            'active_juz_count' => (int) $activeItems->count(),
            'top_juz' => $topJuz,
            'needs_attention' => $needsAttention,
            'items' => $items,
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
                        'juzProgress' => $this->buildPdfJuzProgress(
                            $request,
                            'Santri'
                        ),
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

                $nilaiUjianValid = $data->filter(
                    fn($row) => !is_null($row->rata_nilai_ujian)
                );

                $summary = [
                    'total_kelas' => (int) $data->count(),
                    'total_santri' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_santri ?? 0)
                    ),
                    'total_setoran_harian' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_setoran_harian ?? 0)
                    ),
                    'total_ujian_juz' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_ujian ?? 0)
                    ),
                    'total_hts' => (int) $data->sum(
                        fn($row) => (int) ($row->hadir_tidak_setor ?? 0)
                    ),
                    'total_sakit' => (int) $data->sum(
                        fn($row) => (int) ($row->sakit ?? 0)
                    ),
                    'total_izin' => (int) $data->sum(
                        fn($row) => (int) ($row->izin ?? 0)
                    ),
                    'total_alpha' => (int) $data->sum(
                        fn($row) => (int) ($row->alpha ?? 0)
                    ),
                    'avg_nilai_ujian' => round(
                        (float) ($nilaiUjianValid->avg('rata_nilai_ujian') ?? 0),
                        2
                    ),
                ];

                $topKelas = $data
                    ->sortByDesc(
                        fn($row) => (int) ($row->jumlah_ujian ?? 0)
                    )
                    ->take(10)
                    ->values();

                return [
                    'view_data' => [
                        'data' => $data,
                        'periode' => $export['label'],
                        'summary' => $summary,
                        'topKelas' => $topKelas,
                        'juzProgress' => $this->buildPdfJuzProgress(
                            $request,
                            'Kelas'
                        ),
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

                $nilaiUjianValid = $data->filter(
                    fn($row) => !is_null($row->rata_nilai_ujian)
                );

                $summary = [
                    'total_musyrif' => (int) $data->count(),
                    'total_santri' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_santri ?? 0)
                    ),
                    'santri_aktif_setoran' => (int) $data->sum(
                        fn($row) => (int) ($row->santri_aktif_setoran ?? 0)
                    ),
                    'santri_sudah_ujian' => (int) $data->sum(
                        fn($row) => (int) ($row->santri_sudah_ujian ?? 0)
                    ),
                    'total_setoran_harian' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_setoran_harian ?? 0)
                    ),
                    'total_ujian_juz' => (int) $data->sum(
                        fn($row) => (int) ($row->jumlah_ujian ?? 0)
                    ),
                    'total_hts' => (int) $data->sum(
                        fn($row) => (int) ($row->hadir_tidak_setor ?? 0)
                    ),
                    'total_sakit' => (int) $data->sum(
                        fn($row) => (int) ($row->sakit ?? 0)
                    ),
                    'total_izin' => (int) $data->sum(
                        fn($row) => (int) ($row->izin ?? 0)
                    ),
                    'total_alpha' => (int) $data->sum(
                        fn($row) => (int) ($row->alpha ?? 0)
                    ),
                    'avg_nilai_ujian' => round(
                        (float) ($nilaiUjianValid->avg('rata_nilai_ujian') ?? 0),
                        2
                    ),
                ];

                $topMusyrif = $data
                    ->sortByDesc(
                        fn($row) => (int) ($row->jumlah_ujian ?? 0)
                    )
                    ->take(10)
                    ->values();

                return [
                    'view_data' => [
                        'data' => $data,
                        'periode' => $export['label'],
                        'summary' => $summary,
                        'topMusyrif' => $topMusyrif,
                        'juzProgress' => $this->buildPdfJuzProgress(
                            $request,
                            'Musyrif'
                        ),
                    ],
                    'filename' => 'rekap_hafalan_musyrif_' .
                        $export['filename_suffix'] .
                        '.pdf',
                ];
            }
        );
    }
}
