<?php

namespace App\Http\Controllers\Santri;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Models\Tahsin;
use App\Models\Tilawah;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class HafalanController extends Controller
{
    private const SCOPE_SEMESTER = 'semester';
    private const SCOPE_CUMULATIVE = 'cumulative';

    private const NILAI_MAP = [
        'mumtaz' => 95,
        'jayyid_jiddan' => 85,
        'jayyid' => 75,
        'mardud' => 65,
    ];

    private const TAHAP_RANK = [
        'harian' => 1,
        'tahap_1' => 2,
        'tahap_2' => 3,
        'tahap_3' => 4,
        'ujian_akhir' => 5,
    ];

    private const TAHAP_WEIGHT = [
        'harian' => 20,
        'tahap_1' => 40,
        'tahap_2' => 60,
        'tahap_3' => 80,
        'ujian_akhir' => 100,
    ];

    private const BUKU_TAHSIN = [
        'ummi_1' => [
            'label' => 'Ummi Jilid 1',
            'max' => 40,
        ],
        'ummi_2' => [
            'label' => 'Ummi Jilid 2',
            'max' => 40,
        ],
        'ummi_3' => [
            'label' => 'Ummi Jilid 3',
            'max' => 40,
        ],
        'gharib_1' => [
            'label' => 'Gharib 1',
            'max' => 28,
        ],
        'gharib_2' => [
            'label' => 'Gharib 2',
            'max' => 28,
        ],
        'tajwid' => [
            'label' => 'Tajwid',
            'max' => 50,
        ],
    ];

    /**
     * Halaman gabungan progress Hafalan, Tahsin, dan Tilawah.
     *
     * Scope:
     * - semester: hanya transaksi semester terpilih;
     * - cumulative: seluruh riwayat, termasuk data legacy semester_id NULL.
     */
    public function index(
        Request $request
    ) {
        $santri = $this
            ->authenticatedSantri($request)
            ->loadMissing([
                'kelas',
                'musyrif',
            ]);
        $scope = $this->resolveScope(
            $request
        );

        $semesterList = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get();

        $selectedSemester =
            $this->resolveSelectedSemester(
                $request,
                $semesterList
            );

        if (
            $scope === self::SCOPE_SEMESTER
            && !$selectedSemester
        ) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Semester wajib tersedia untuk menampilkan progress per semester.',
                ],
            ]);
        }

        $selectedSemesterId =
            $selectedSemester?->id;

        $selectedPlacement = null;

        if ($selectedSemesterId) {
            $selectedPlacement =
                SantriSemesterPlacement::query()
                ->with([
                    'kelas:id,nama_kelas',
                    'musyrif:id,nama,kode',
                ])
                ->where(
                    'santri_id',
                    $santri->id
                )
                ->where(
                    'semester_id',
                    $selectedSemesterId
                )
                ->first();
        }

        $semesterSummary =
            $selectedSemesterId
            ? $this->buildSummary(
                $santri->id,
                (int) $selectedSemesterId
            )
            : $this->emptySummary();

        $cumulativeSummary =
            $this->buildSummary(
                $santri->id,
                null
            );

        $scopeSummary =
            $scope === self::SCOPE_CUMULATIVE
            ? $cumulativeSummary
            : $semesterSummary;

        $scopeLabel =
            $scope === self::SCOPE_CUMULATIVE
            ? 'Kumulatif Seluruh Semester'
            : (
                $selectedSemester
                ? $this->semesterLabel(
                    $selectedSemester
                )
                : 'Semester Belum Tersedia'
            );

        $displayKelas =
            $scope === self::SCOPE_SEMESTER
            ? (
                $selectedPlacement?->kelas
                ?->nama_kelas
                ?? '-'
            )
            : (
                $santri->kelas?->nama_kelas
                ?? '-'
            );

        $displayMusyrif =
            $scope === self::SCOPE_SEMESTER
            ? (
                $selectedPlacement?->musyrif
                ?->nama
                ?? '-'
            )
            : (
                $santri->musyrif?->nama
                ?? '-'
            );

        $legacyCounts = [
            'hafalan' => Hafalan::query()
                ->where(
                    'santri_id',
                    $santri->id
                )
                ->whereNull('semester_id')
                ->count(),

            'tahsin' => Tahsin::query()
                ->where(
                    'santri_id',
                    $santri->id
                )
                ->whereNull('semester_id')
                ->count(),

            'tilawah' => Tilawah::query()
                ->where(
                    'santri_id',
                    $santri->id
                )
                ->whereNull('semester_id')
                ->count(),
        ];

        $warnings = [];

        if (
            $selectedSemester
            && !$selectedPlacement
        ) {
            $warnings[] =
                'Informasi kelas dan musyrif pada semester terpilih belum tersedia. Silakan hubungi Admin apabila data ini seharusnya sudah tampil.';
        }

        $legacyTotal = array_sum(
            $legacyCounts
        );

        if ($legacyTotal > 0) {
            $warnings[] =
                "{$legacyTotal} riwayat lama belum terhubung ke semester tertentu dan hanya ditampilkan pada mode Kumulatif.";
        }

        if (
            $scope === self::SCOPE_SEMESTER
            && array_sum(
                $semesterSummary['record_counts']
            ) === 0
        ) {
            $warnings[] =
                'Belum ada transaksi Hafalan, Tahsin, atau Tilawah pada semester terpilih.';
        }

        return view(
            'santri.hafalan.index',
            [
                'santri' => $santri,
                'scope' => $scope,
                'scopeLabel' => $scopeLabel,
                'semesterList' =>
                $semesterList,
                'selectedSemester' =>
                $selectedSemester,
                'selectedSemesterId' =>
                $selectedSemesterId,
                'selectedPlacement' =>
                $selectedPlacement,
                'displayKelas' =>
                $displayKelas,
                'displayMusyrif' =>
                $displayMusyrif,
                'warnings' => $warnings,
                'legacyCounts' =>
                $legacyCounts,
                'semesterSummary' =>
                $semesterSummary,
                'cumulativeSummary' =>
                $cumulativeSummary,

                // Hafalan pada scope aktif.
                'progressPerJuz' =>
                $scopeSummary['hafalan']['progress'],
                'overallPct' =>
                $scopeSummary['hafalan']['overall_pct'],
                'juzSelesai' =>
                $scopeSummary['hafalan']['juz_selesai'],
                'totalSetor' =>
                $scopeSummary['hafalan']['setor'],
                'totalHadirTidakSetor' =>
                $scopeSummary['hafalan']['hadir_tidak_setor'],
                'totalSakit' =>
                $scopeSummary['hafalan']['sakit'],
                'totalIzin' =>
                $scopeSummary['hafalan']['izin'],
                'totalAlpha' =>
                $scopeSummary['hafalan']['alpha'],
                'avgNilai' =>
                $scopeSummary['hafalan']['avg_nilai'],

                // Tahsin pada scope aktif.
                'progressPerBuku' =>
                $scopeSummary['tahsin']['progress'],
                'overallTahsinPct' =>
                $scopeSummary['tahsin']['overall_pct'],
                'tahsinHadir' =>
                $scopeSummary['tahsin']['hadir'],
                'tahsinIzin' =>
                $scopeSummary['tahsin']['izin'],
                'tahsinSakit' =>
                $scopeSummary['tahsin']['sakit'],
                'tahsinAlpha' =>
                $scopeSummary['tahsin']['alpha'],
                'lastTahsin' =>
                $scopeSummary['tahsin']['last'],

                // Tilawah pada scope aktif.
                'maxJuzTilawah' =>
                $scopeSummary['tilawah']['max_juz'],
                'tilawahPct' =>
                $scopeSummary['tilawah']['overall_pct'],
                'tilawahHadir' =>
                $scopeSummary['tilawah']['hadir'],
                'tilawahIzin' =>
                $scopeSummary['tilawah']['izin'],
                'tilawahSakit' =>
                $scopeSummary['tilawah']['sakit'],
                'tilawahAlpha' =>
                $scopeSummary['tilawah']['alpha'],
                'lastTilawah' =>
                $scopeSummary['tilawah']['last'],
            ]
        );
    }

    /**
     * DataTables timeline Hafalan.
     */
    public function timeline(
        Request $request
    ) {
        abort_unless(
            $request->ajax(),
            404
        );

        $santri = $this
            ->authenticatedSantri($request);

        $context =
            $this->resolveTimelineContext(
                $request
            );

        $semesterLabels =
            $this->semesterLabelMap();

        $query = Hafalan::query()
            ->leftJoin(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'hafalans.hafalan_template_id'
            )
            ->where(
                'hafalans.santri_id',
                $santri->id
            )
            ->when(
                $context['semester_id'],
                fn(Builder $query) =>
                $query->where(
                    'hafalans.semester_id',
                    $context['semester_id']
                )
            )
            ->select([
                'hafalans.id',
                'hafalans.semester_id',
                'hafalans.tanggal_setoran',
                'hafalans.status',
                'hafalans.nilai_label',
                'hafalans.catatan',
                'hafalans.created_at',
                'ht.juz as template_juz',
                'ht.label as template_label',
            ])
            ->orderByDesc(
                'hafalans.tanggal_setoran'
            )
            ->orderByDesc(
                'hafalans.id'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn(
                'tanggal',
                fn($row) =>
                $this->formatDate(
                    $row->tanggal_setoran
                )
            )
            ->addColumn(
                'semester',
                fn($row) =>
                $this->timelineSemesterLabel(
                    $row->semester_id,
                    $semesterLabels
                )
            )
            ->addColumn(
                'juz',
                fn($row) =>
                $row->template_juz
                    ?? '-'
            )
            ->addColumn(
                'surah_ayat',
                fn($row) =>
                e(
                    $row->template_label
                        ?? '-'
                )
            )
            ->addColumn(
                'nilai',
                fn($row) =>
                $this->nilaiLabel(
                    $row->nilai_label
                )
            )
            ->editColumn(
                'status',
                fn($row) =>
                $this->hafalanStatusBadge(
                    $row->status
                )
            )
            ->editColumn(
                'catatan',
                fn($row) =>
                e(
                    $row->catatan
                        ?: '-'
                )
            )
            ->filterColumn(
                'juz',
                function (
                    $query,
                    $keyword
                ): void {
                    $query->where(
                        'ht.juz',
                        'like',
                        "%{$keyword}%"
                    );
                }
            )
            ->filterColumn(
                'surah_ayat',
                function (
                    $query,
                    $keyword
                ): void {
                    $query->where(
                        'ht.label',
                        'like',
                        "%{$keyword}%"
                    );
                }
            )
            ->rawColumns([
                'semester',
                'status',
            ])
            ->make(true);
    }

    /**
     * DataTables timeline Tahsin.
     */
    public function tahsinTimeline(
        Request $request
    ) {
        abort_unless(
            $request->ajax(),
            404
        );

        $santri = $this
            ->authenticatedSantri($request);

        $context =
            $this->resolveTimelineContext(
                $request
            );

        $semesterLabels =
            $this->semesterLabelMap();

        $query = Tahsin::query()
            ->where(
                'santri_id',
                $santri->id
            )
            ->when(
                $context['semester_id'],
                fn(Builder $query) =>
                $query->where(
                    'semester_id',
                    $context['semester_id']
                )
            )
            ->select([
                'id',
                'semester_id',
                'tanggal',
                'buku',
                'halaman',
                'status',
                'nilai_label',
                'catatan',
                'created_at',
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn(
                'tanggal',
                fn($row) =>
                $this->formatDate(
                    $row->tanggal
                )
            )
            ->addColumn(
                'semester',
                fn($row) =>
                $this->timelineSemesterLabel(
                    $row->semester_id,
                    $semesterLabels
                )
            )
            ->addColumn(
                'buku_label',
                fn($row) =>
                self::BUKU_TAHSIN[$row->buku]['label']
                    ?? ucfirst(
                        str_replace(
                            '_',
                            ' ',
                            (string) $row->buku
                        )
                    )
            )
            ->editColumn(
                'halaman',
                fn($row) =>
                $row->halaman
                    ?: '-'
            )
            ->editColumn(
                'status',
                fn($row) =>
                $this->attendanceStatusBadge(
                    $row->status
                )
            )
            ->addColumn(
                'nilai',
                fn($row) =>
                $this->nilaiLabel(
                    $row->nilai_label
                )
            )
            ->editColumn(
                'catatan',
                fn($row) =>
                e(
                    $row->catatan
                        ?: '-'
                )
            )
            ->rawColumns([
                'semester',
                'status',
            ])
            ->make(true);
    }

    /**
     * DataTables timeline Tilawah.
     */
    public function tilawahTimeline(
        Request $request
    ) {
        abort_unless(
            $request->ajax(),
            404
        );

        $santri = $this
            ->authenticatedSantri($request);

        $context =
            $this->resolveTimelineContext(
                $request
            );

        $semesterLabels =
            $this->semesterLabelMap();

        $query = Tilawah::query()
            ->leftJoin(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'tilawahs.hafalan_template_id'
            )
            ->where(
                'tilawahs.santri_id',
                $santri->id
            )
            ->when(
                $context['semester_id'],
                fn(Builder $query) =>
                $query->where(
                    'tilawahs.semester_id',
                    $context['semester_id']
                )
            )
            ->select([
                'tilawahs.id',
                'tilawahs.semester_id',
                'tilawahs.tanggal',
                'tilawahs.status',
                'tilawahs.catatan',
                'tilawahs.created_at',
                'ht.juz as template_juz',
                'ht.label as template_label',
            ])
            ->orderByDesc(
                'tilawahs.tanggal'
            )
            ->orderByDesc(
                'tilawahs.id'
            );

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn(
                'tanggal',
                fn($row) =>
                $this->formatDate(
                    $row->tanggal
                )
            )
            ->addColumn(
                'semester',
                fn($row) =>
                $this->timelineSemesterLabel(
                    $row->semester_id,
                    $semesterLabels
                )
            )
            ->addColumn(
                'target_bacaan',
                function ($row) {
                    if (
                        !$row->template_juz
                        && !$row->template_label
                    ) {
                        return '-';
                    }

                    $juz = e(
                        $row->template_juz
                            ?? '-'
                    );

                    $label = e(
                        $row->template_label
                            ?? '-'
                    );

                    return "
                        <span class='fw-bold'>
                            Juz {$juz}
                        </span>
                        <br>
                        <small class='text-muted'>
                            {$label}
                        </small>
                    ";
                }
            )
            ->editColumn(
                'status',
                fn($row) =>
                $this->attendanceStatusBadge(
                    $row->status
                )
            )
            ->editColumn(
                'catatan',
                fn($row) =>
                e(
                    $row->catatan
                        ?: '-'
                )
            )
            ->rawColumns([
                'semester',
                'target_bacaan',
                'status',
            ])
            ->make(true);
    }

    /**
     * Endpoint PDF Hafalan lama tetap dipertahankan agar route existing
     * tidak rusak. Raport/Ijazah resmi akan dibuat pada Step 2.
     */
    public function exportPdf(Request $request)
    {
        if ($request->query('debug') === 'route') {
            return response('EXPORT CONTROLLER HIT', 200, [
                'Content-Type' => 'text/plain',
                'X-Export-Debug' => 'controller-hit',
            ]);
        }
        Log::channel('single')->info('EXPORT PDF START', [
            'user_id' => $request->user()?->id,
            'filters' => $request->only(['start_date', 'end_date']),
        ]);

        try {
            $validated = $request->validate([
                'start_date' => ['nullable', 'date_format:Y-m-d'],
                'end_date' => ['nullable', 'date_format:Y-m-d'],
            ]);

            $startDate = $validated['start_date'] ?? null;
            $endDate = $validated['end_date'] ?? null;

            if ($startDate && $endDate && $endDate < $startDate) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'end_date' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
                ]);
            }

            $santri = $this->authenticatedSantri($request)
                ->loadMissing(['kelas', 'musyrif']);

            /*
             * Gunakan query builder + LEFT JOIN agar proses export tidak
             * bergantung pada lazy-loading relasi saat Blade sedang dirender.
             */
            $query = DB::table('hafalans as h')
                ->leftJoin(
                    'hafalan_templates as ht',
                    'ht.id',
                    '=',
                    'h.hafalan_template_id'
                )
                ->where('h.santri_id', $santri->id)
                ->select([
                    'h.id',
                    'h.tanggal_setoran',
                    'h.status',
                    'h.nilai_label',
                    'h.catatan',
                    'ht.juz as template_juz',
                    'ht.label as template_label',
                ])
                ->orderByDesc('h.tanggal_setoran')
                ->orderByDesc('h.id');

            if ($startDate) {
                $query->whereDate('h.tanggal_setoran', '>=', $startDate);
            }

            if ($endDate) {
                $query->whereDate('h.tanggal_setoran', '<=', $endDate);
            }

            $records = $query->get();

            $statusCounts = $records
                ->groupBy(fn($item) => strtolower(trim((string) ($item->status ?? ''))))
                ->map->count();

            $timeline = $records->values()->map(function ($record, int $index) {
                $status = strtolower(trim((string) ($record->status ?? '')));
                $nilai = strtolower(trim((string) ($record->nilai_label ?? '')));

                return [
                    'no' => $index + 1,
                    'tanggal' => $record->tanggal_setoran
                        ? Carbon::parse($record->tanggal_setoran)->format('d/m/Y')
                        : '-',
                    'juz' => $record->template_juz ?? '-',
                    'materi' => $record->template_label ?: '-',
                    'status_key' => $status,
                    'status_label' => $status !== ''
                        ? ucwords(str_replace('_', ' ', $status))
                        : '-',
                    'nilai_label' => in_array($status, ['lulus', 'ulang'], true)
                        ? match ($nilai) {
                            'mumtaz' => 'Mumtaz',
                            'jayyid_jiddan' => 'Jayyid Jiddan',
                            'jayyid' => 'Jayyid',
                            'mardud' => 'Mardud',
                            default => '-',
                        }
                        : '-',
                    'catatan' => $record->catatan ?: '-',
                ];
            });

            if ($startDate && $endDate) {
                $periode = Carbon::parse($startDate)->translatedFormat('d F Y')
                    . ' s.d. '
                    . Carbon::parse($endDate)->translatedFormat('d F Y');
            } elseif ($startDate) {
                $periode = 'Mulai '
                    . Carbon::parse($startDate)->translatedFormat('d F Y');
            } elseif ($endDate) {
                $periode = 'Sampai '
                    . Carbon::parse($endDate)->translatedFormat('d F Y');
            } else {
                $periode = 'Semua Riwayat';
            }

            /*
             * Logo hanya disisipkan jika benar-benar merupakan gambar yang
             * didukung DomPDF. File rusak atau ekstensi palsu akan dilewati.
             */
            $logoDataUri = null;
            $logoPath = public_path('assets/logos-primary.png');

            if (is_file($logoPath) && is_readable($logoPath)) {
                $imageInfo = @getimagesize($logoPath);
                $mime = $imageInfo['mime'] ?? null;
                $allowedMimes = ['image/png', 'image/jpeg', 'image/gif'];

                if (in_array($mime, $allowedMimes, true)) {
                    $logoBinary = file_get_contents($logoPath);

                    if ($logoBinary !== false && $logoBinary !== '') {
                        $logoDataUri = 'data:' . $mime . ';base64,'
                            . base64_encode($logoBinary);
                    }
                }
            }

            $data = [
                'santri' => $santri,
                'timeline' => $timeline,
                'totalSetor' => (int) $statusCounts->get('lulus', 0)
                    + (int) $statusCounts->get('ulang', 0),
                'totalHadirTidakSetor' => (int) $statusCounts->get('hadir_tidak_setor', 0),
                'totalHTS' => (int) $statusCounts->get('hadir_tidak_setor', 0),
                'totalSakit' => (int) $statusCounts->get('sakit', 0),
                'totalIzin' => (int) $statusCounts->get('izin', 0),
                'totalAlpha' => (int) $statusCounts->get('alpha', 0),
                'periode' => $periode,
                'tanggal_cetak' => now()->translatedFormat('d F Y, H:i'),
                'logoDataUri' => $logoDataUri,
            ];

            $filename = 'laporan_hafalan_'
                . Str::slug((string) $santri->nama, '_')
                . '_'
                . now()->format('Ymd_His')
                . '.pdf';

            $pdf = Pdf::loadView('santri.hafalan.pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => false,
                    'defaultFont' => 'DejaVu Sans',
                ]);

            Log::channel('single')->info('EXPORT PDF SUCCESS', [
                'santri_id' => $santri->id,
                'record_count' => $timeline->count(),
                'filename' => $filename,
            ]);

            /*
             * Jangan membuat binary response dan Content-Length sendiri.
             * Biarkan wrapper DomPDF/Laravel menyiapkan response attachment.
             */
            return $pdf->download($filename);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Log::channel('single')->warning('EXPORT PDF VALIDATION FAILED', [
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        } catch (\Throwable $exception) {
            Log::channel('single')->error('EXPORT PDF FAILED', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'message' => app()->isLocal()
                    ? 'Export PDF gagal: ' . $exception->getMessage()
                    : 'Export PDF gagal diproses. Silakan hubungi administrator.',
            ], 500);
        }
    }

    private function authenticatedSantri(
        Request $request
    ): Santri {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            abort(
                401,
                'Sesi pengguna tidak ditemukan. Silakan login kembali.'
            );
        }

        $santri = $user->santri;

        if (!$santri) {
            abort(
                403,
                'Profil santri tidak ditemukan. Hubungi Admin.'
            );
        }

        return $santri;
    }

    private function buildSummary(
        int $santriId,
        ?int $semesterId
    ): array {
        $hafalanStatus =
            $this->statusCounts(
                Hafalan::class,
                $santriId,
                $semesterId
            );

        $tahsinStatus =
            $this->statusCounts(
                Tahsin::class,
                $santriId,
                $semesterId
            );

        $tilawahStatus =
            $this->statusCounts(
                Tilawah::class,
                $santriId,
                $semesterId
            );

        $progressPerJuz =
            $this->buildHafalanProgress(
                $santriId,
                $semesterId
            );

        $overallPct = (int) round(
            $progressPerJuz->avg('pct')
                ?? 0
        );

        $juzSelesai =
            $progressPerJuz
            ->where('pct', 100)
            ->count();

        $avgNilaiQuery = Hafalan::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->whereIn(
                'status',
                [
                    'lulus',
                    'ulang',
                ]
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'semester_id',
                    $semesterId
                )
            );

        $avgNilai = (float) (
            $avgNilaiQuery
            ->selectRaw(
                "
                    ROUND(
                        AVG(
                            CASE nilai_label
                                WHEN 'mumtaz' THEN 95
                                WHEN 'jayyid_jiddan' THEN 85
                                WHEN 'jayyid' THEN 75
                                WHEN 'mardud' THEN 65
                                ELSE NULL
                            END
                        ),
                        1
                    ) AS average_score
                    "
            )
            ->value('average_score')
            ?? 0
        );

        $progressPerBuku =
            $this->buildTahsinProgress(
                $santriId,
                $semesterId
            );

        $overallTahsinPct =
            (int) round(
                $progressPerBuku->avg('pct')
                    ?? 0
            );

        $lastTahsin = Tahsin::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'status',
                'hadir'
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'semester_id',
                    $semesterId
                )
            )
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        $maxJuzTilawahQuery =
            DB::table('tilawahs')
            ->join(
                'hafalan_templates',
                'tilawahs.hafalan_template_id',
                '=',
                'hafalan_templates.id'
            )
            ->where(
                'tilawahs.santri_id',
                $santriId
            )
            ->where(
                'tilawahs.status',
                'hadir'
            )
            ->when(
                $semesterId,
                fn($query) =>
                $query->where(
                    'tilawahs.semester_id',
                    $semesterId
                )
            );

        $maxJuzTilawah = (int) (
            $maxJuzTilawahQuery
            ->max(
                'hafalan_templates.juz'
            )
            ?? 0
        );

        $tilawahPct = min(
            100,
            (int) round(
                ($maxJuzTilawah / 30)
                    * 100
            )
        );

        $lastTilawah = Tilawah::query()
            ->with('template')
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'status',
                'hadir'
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'semester_id',
                    $semesterId
                )
            )
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        return [
            'record_counts' => [
                'hafalan' =>
                $this->countRecords(
                    Hafalan::class,
                    $santriId,
                    $semesterId
                ),
                'tahsin' =>
                $this->countRecords(
                    Tahsin::class,
                    $santriId,
                    $semesterId
                ),
                'tilawah' =>
                $this->countRecords(
                    Tilawah::class,
                    $santriId,
                    $semesterId
                ),
            ],

            'hafalan' => [
                'progress' =>
                $progressPerJuz,
                'overall_pct' =>
                $overallPct,
                'juz_selesai' =>
                $juzSelesai,
                'setor' =>
                (int) (
                    $hafalanStatus['lulus']
                    ?? 0
                )
                    + (int) (
                        $hafalanStatus['ulang']
                        ?? 0
                    ),
                'hadir_tidak_setor' =>
                (int) (
                    $hafalanStatus['hadir_tidak_setor']
                    ?? 0
                ),
                'sakit' =>
                (int) (
                    $hafalanStatus['sakit']
                    ?? 0
                ),
                'izin' =>
                (int) (
                    $hafalanStatus['izin']
                    ?? 0
                ),
                'alpha' =>
                (int) (
                    $hafalanStatus['alpha']
                    ?? 0
                ),
                'avg_nilai' =>
                $avgNilai,
            ],

            'tahsin' => [
                'progress' =>
                $progressPerBuku,
                'overall_pct' =>
                $overallTahsinPct,
                'hadir' =>
                (int) (
                    $tahsinStatus['hadir']
                    ?? 0
                ),
                'izin' =>
                (int) (
                    $tahsinStatus['izin']
                    ?? 0
                ),
                'sakit' =>
                (int) (
                    $tahsinStatus['sakit']
                    ?? 0
                ),
                'alpha' =>
                (int) (
                    $tahsinStatus['alpha']
                    ?? 0
                ),
                'last' => $lastTahsin,
            ],

            'tilawah' => [
                'max_juz' =>
                $maxJuzTilawah,
                'overall_pct' =>
                $tilawahPct,
                'hadir' =>
                (int) (
                    $tilawahStatus['hadir']
                    ?? 0
                ),
                'izin' =>
                (int) (
                    $tilawahStatus['izin']
                    ?? 0
                ),
                'sakit' =>
                (int) (
                    $tilawahStatus['sakit']
                    ?? 0
                ),
                'alpha' =>
                (int) (
                    $tilawahStatus['alpha']
                    ?? 0
                ),
                'last' => $lastTilawah,
            ],
        ];
    }

    private function emptySummary(): array
    {
        return [
            'record_counts' => [
                'hafalan' => 0,
                'tahsin' => 0,
                'tilawah' => 0,
            ],
            'hafalan' => [
                'progress' =>
                $this->emptyHafalanProgress(),
                'overall_pct' => 0,
                'juz_selesai' => 0,
                'setor' => 0,
                'hadir_tidak_setor' => 0,
                'sakit' => 0,
                'izin' => 0,
                'alpha' => 0,
                'avg_nilai' => 0,
            ],
            'tahsin' => [
                'progress' =>
                $this->emptyTahsinProgress(),
                'overall_pct' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'last' => null,
            ],
            'tilawah' => [
                'max_juz' => 0,
                'overall_pct' => 0,
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'alpha' => 0,
                'last' => null,
            ],
        ];
    }

    private function statusCounts(
        string $modelClass,
        int $santriId,
        ?int $semesterId
    ): Collection {
        return $modelClass::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'semester_id',
                    $semesterId
                )
            )
            ->selectRaw(
                'status, COUNT(*) AS total'
            )
            ->groupBy('status')
            ->pluck(
                'total',
                'status'
            );
    }

    private function countRecords(
        string $modelClass,
        int $santriId,
        ?int $semesterId
    ): int {
        return $modelClass::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'semester_id',
                    $semesterId
                )
            )
            ->count();
    }

    private function buildHafalanProgress(
        int $santriId,
        ?int $semesterId
    ): Collection {
        $tahapPerJuz = Hafalan::query()
            ->join(
                'hafalan_templates',
                'hafalan_templates.id',
                '=',
                'hafalans.hafalan_template_id'
            )
            ->where(
                'hafalans.santri_id',
                $santriId
            )
            ->where(
                'hafalans.status',
                'lulus'
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'hafalans.semester_id',
                    $semesterId
                )
            )
            ->select(
                'hafalan_templates.juz',
                'hafalan_templates.tahap'
            )
            ->get()
            ->groupBy('juz')
            ->map(
                function (
                    Collection $rows
                ) {
                    return $rows
                        ->sortByDesc(
                            fn($row) =>
                            self::TAHAP_RANK[$row->tahap]
                                ?? 0
                        )
                        ->first()?->tahap;
                }
            );

        return collect(
            range(1, 30)
        )->map(
            function (
                int $juz
            ) use ($tahapPerJuz) {
                $tahap =
                    $tahapPerJuz->get(
                        $juz
                    );

                $pct = $tahap
                    ? (
                        self::TAHAP_WEIGHT[$tahap]
                        ?? 0
                    )
                    : 0;

                [$status, $color] =
                    match (true) {
                        $pct >= 100 => [
                            'Selesai',
                            'success',
                        ],
                        $pct >= 80 => [
                            'Tahap 3',
                            'info',
                        ],
                        $pct >= 60 => [
                            'Tahap 2',
                            'primary',
                        ],
                        $pct >= 40 => [
                            'Tahap 1',
                            'warning',
                        ],
                        $pct > 0 => [
                            'Harian',
                            'secondary',
                        ],
                        default => [
                            'Belum mulai',
                            'light',
                        ],
                    };

                return [
                    'juz' => $juz,
                    'pct' => $pct,
                    'status' => $status,
                    'color' => $color,
                    'tahap' => $tahap,
                ];
            }
        );
    }

    private function emptyHafalanProgress(): Collection
    {
        return collect(
            range(1, 30)
        )->map(
            fn(int $juz) => [
                'juz' => $juz,
                'pct' => 0,
                'status' => 'Belum mulai',
                'color' => 'light',
                'tahap' => null,
            ]
        );
    }

    private function buildTahsinProgress(
        int $santriId,
        ?int $semesterId
    ): Collection {
        $maxPages = Tahsin::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'status',
                'hadir'
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'semester_id',
                    $semesterId
                )
            )
            ->selectRaw(
                'buku, MAX(halaman) AS max_halaman'
            )
            ->groupBy('buku')
            ->pluck(
                'max_halaman',
                'buku'
            );

        return collect(
            self::BUKU_TAHSIN
        )->map(
            function (
                array $book,
                string $key
            ) use ($maxPages) {
                $current = (int) (
                    $maxPages[$key]
                    ?? 0
                );

                $pct = min(
                    100,
                    (int) round(
                        (
                            $current
                            / $book['max']
                        )
                            * 100
                    )
                );

                [$status, $color] =
                    match (true) {
                        $pct >= 100 => [
                            'Selesai',
                            'success',
                        ],
                        $pct > 0 => [
                            'Sedang Berjalan',
                            'primary',
                        ],
                        default => [
                            'Belum Mulai',
                            'light',
                        ],
                    };

                return [
                    'buku_key' => $key,
                    'label' =>
                    $book['label'],
                    'max' =>
                    $book['max'],
                    'current' =>
                    $current,
                    'pct' => $pct,
                    'status' =>
                    $status,
                    'color' =>
                    $color,
                ];
            }
        );
    }

    private function emptyTahsinProgress(): Collection
    {
        return collect(
            self::BUKU_TAHSIN
        )->map(
            fn(
                array $book,
                string $key
            ) => [
                'buku_key' => $key,
                'label' => $book['label'],
                'max' => $book['max'],
                'current' => 0,
                'pct' => 0,
                'status' => 'Belum Mulai',
                'color' => 'light',
            ]
        );
    }

    private function resolveScope(
        Request $request
    ): string {
        $scope = (string) $request->input(
            'scope',
            self::SCOPE_SEMESTER
        );

        if (
            !in_array(
                $scope,
                [
                    self::SCOPE_SEMESTER,
                    self::SCOPE_CUMULATIVE,
                ],
                true
            )
        ) {
            return self::SCOPE_SEMESTER;
        }

        return $scope;
    }

    private function resolveSelectedSemester(
        Request $request,
        Collection $semesterList
    ): ?Semester {
        if (
            $request->filled(
                'semester_id'
            )
        ) {
            $requestedId = (int) $request->input(
                'semester_id'
            );

            $requested =
                $semesterList->firstWhere(
                    'id',
                    $requestedId
                );

            if ($requested) {
                return $requested;
            }
        }

        return $semesterList->first(
            fn(Semester $semester) =>
            $semester->status
                === Semester::STATUS_ACTIVE
                || (bool) $semester->is_active
        )
            ?? $semesterList->first();
    }

    private function resolveTimelineContext(
        Request $request
    ): array {
        $scope = $this->resolveScope(
            $request
        );

        if (
            $scope
            === self::SCOPE_CUMULATIVE
        ) {
            return [
                'scope' => $scope,
                'semester_id' => null,
            ];
        }

        $semesterId = $request->filled(
            'semester_id'
        )
            ? (int) $request->input(
                'semester_id'
            )
            : null;

        if (
            !$semesterId
            || !Semester::query()
                ->whereKey($semesterId)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Semester timeline tidak valid.',
                ],
            ]);
        }

        return [
            'scope' => $scope,
            'semester_id' => $semesterId,
        ];
    }

    private function semesterLabelMap(): Collection
    {
        return Semester::query()
            ->with('tahunAjaran:id,nama')
            ->get()
            ->mapWithKeys(
                fn(Semester $semester) => [
                    (int) $semester->id =>
                    $this->semesterLabel(
                        $semester
                    ),
                ]
            );
    }

    private function semesterLabel(
        Semester $semester
    ): string {
        $semesterName = str(
            str_replace(
                '_',
                ' ',
                (string) $semester->nama
            )
        )->title();

        $academicYear = str(
            str_replace(
                '_',
                ' ',
                (string) (
                    $semester
                    ->tahunAjaran
                    ?->nama
                    ?? '-'
                )
            )
        )->title();

        return "{$semesterName} — {$academicYear}";
    }

    private function timelineSemesterLabel(
        mixed $semesterId,
        Collection $semesterLabels
    ): string {
        if (!$semesterId) {
            return '<span class="badge bg-warning text-dark">Legacy / Tanpa Semester</span>';
        }

        return e(
            $semesterLabels->get(
                (int) $semesterId,
                "Semester #{$semesterId}"
            )
        );
    }

    private function formatDate(
        mixed $value
    ): string {
        if (!$value) {
            return '-';
        }

        try {
            return Carbon::parse($value)
                ->locale('id')
                ->translatedFormat(
                    'd M Y'
                );
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function nilaiLabel(
        ?string $nilai
    ): string {
        return match ($nilai) {
            'mumtaz' => 'ممتاز',
            'jayyid_jiddan' => 'جيد جدًا',
            'jayyid' => 'جيد',
            'mardud' => 'مردود',
            default => '-',
        };
    }

    private function hafalanStatusBadge(
        ?string $status
    ): string {
        [$class, $label] =
            match ($status) {
                'lulus' => [
                    'bg-success',
                    'Lulus',
                ],
                'ulang' => [
                    'bg-warning text-dark',
                    'Ulang',
                ],
                'hadir_tidak_setor' => [
                    'bg-info text-dark',
                    'Hadir Tidak Setor',
                ],
                'sakit' => [
                    'bg-primary',
                    'Sakit',
                ],
                'izin' => [
                    'bg-secondary',
                    'Izin',
                ],
                'alpha' => [
                    'bg-danger',
                    'Alpha',
                ],
                default => [
                    'bg-secondary',
                    '-',
                ],
            };

        return "
            <span class='badge {$class}'>
                {$label}
            </span>
        ";
    }

    private function attendanceStatusBadge(
        ?string $status
    ): string {
        [$color, $label] =
            match ($status) {
                'hadir' => [
                    'success',
                    'Hadir',
                ],
                'izin' => [
                    'secondary',
                    'Izin',
                ],
                'sakit' => [
                    'primary',
                    'Sakit',
                ],
                'alpha' => [
                    'danger',
                    'Alpha',
                ],
                default => [
                    'dark',
                    '-',
                ],
            };

        return "
            <span class='badge bg-{$color} rounded-pill px-3 py-2'>
                {$label}
            </span>
        ";
    }
}
