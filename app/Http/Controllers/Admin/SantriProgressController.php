<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Models\Tahsin;
use App\Models\Tilawah;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class SantriProgressController extends Controller
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
        // Disamakan dengan logika laporan: proses harian/tahap hanya nilai sementara,
        // maksimal 70 sebelum santri lulus ujian akhir.
        'harian' => 25,
        'tahap_1' => 40,
        'tahap_2' => 55,
        'tahap_3' => 70,
        'ujian_akhir' => 100,
    ];

    private const DAILY_TAHAP = [
        'harian',
        'tahap_1',
        'tahap_2',
        'tahap_3',
    ];

    private const TAHAP_LABEL = [
        'harian' => 'Harian',
        'tahap_1' => 'Tahap 1',
        'tahap_2' => 'Tahap 2',
        'tahap_3' => 'Tahap 3',
        'ujian_akhir' => 'Ujian Akhir',
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
    public function show(
        Request $request,
        Santri $santri
    ) {
        $santri->loadMissing([
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
                'Placement santri pada semester terpilih belum tersedia. Kelas dan musyrif historis tidak akan diambil dari posisi santri saat ini.';
        }

        $legacyTotal = array_sum(
            $legacyCounts
        );

        if ($legacyTotal > 0) {
            $warnings[] =
                "{$legacyTotal} transaksi legacy belum memiliki semester_id. Data tersebut hanya masuk pada scope Kumulatif.";
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
            'admin.santri.progress',
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
                'totalSetorHarian' =>
                $scopeSummary['hafalan']['setor_harian'],
                'totalUjian' =>
                $scopeSummary['hafalan']['ujian'],
                'avgNilaiSementara' =>
                $scopeSummary['hafalan']['avg_nilai_sementara'],
                'avgNilaiUjian' =>
                $scopeSummary['hafalan']['avg_nilai_ujian'],
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
    public function hafalanTimeline(
        Request $request,
        Santri $santri
    ) {
        abort_unless(
            $request->ajax(),
            404
        );

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
                'ht.tahap as template_tahap',
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
                'tahap',
                fn($row) =>
                $this->hafalanTahapBadge(
                    $row->template_tahap
                )
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
                'status',
                'tahap',
            ])
            ->make(true);
    }

    /**
     * DataTables timeline Tahsin.
     */
    public function tahsinTimeline(
        Request $request,
        Santri $santri
    ) {
        abort_unless(
            $request->ajax(),
            404
        );

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
                'status',
            ])
            ->make(true);
    }

    /**
     * DataTables timeline Tilawah.
     */
    public function tilawahTimeline(
        Request $request,
        Santri $santri
    ) {
        abort_unless(
            $request->ajax(),
            404
        );

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
                'target_bacaan',
                'status',
            ])
            ->make(true);
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

        $hafalanStageSummary =
            $this->hafalanStageSummary(
                $santriId,
                $semesterId
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
                $hafalanStageSummary['setor_total'],
                'setor_harian' =>
                $hafalanStageSummary['setor_harian'],
                'ujian' =>
                $hafalanStageSummary['ujian_juz'],
                'avg_nilai_sementara' =>
                $hafalanStageSummary['avg_nilai_sementara'],
                'avg_nilai_ujian' =>
                $hafalanStageSummary['avg_nilai_ujian'],
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
                // Sama seperti Laporan Page: nilai final dashboard santri memakai rata-rata ujian akhir yang lulus.
                'avg_nilai' =>
                $hafalanStageSummary['avg_nilai_ujian'],
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
                'setor_harian' => 0,
                'ujian' => 0,
                'avg_nilai_sementara' => 0,
                'avg_nilai_ujian' => 0,
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

    private function nilaiScoreSql(string $column = 'hafalans.nilai_label'): string
    {
        return "CASE {$column}
            WHEN 'mumtaz' THEN 95
            WHEN 'jayyid_jiddan' THEN 85
            WHEN 'jayyid' THEN 75
            WHEN 'mardud' THEN 65
            ELSE NULL
        END";
    }

    private function hafalanStageSummary(
        int $santriId,
        ?int $semesterId
    ): array {
        $scoreSql = $this->nilaiScoreSql();

        $summary = Hafalan::query()
            ->join(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'hafalans.hafalan_template_id'
            )
            ->where(
                'hafalans.santri_id',
                $santriId
            )
            ->whereIn(
                'hafalans.status',
                [
                    'lulus',
                    'ulang',
                ]
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'hafalans.semester_id',
                    $semesterId
                )
            )
            ->selectRaw(
                "COUNT(*) AS setor_total"
            )
            ->selectRaw(
                "SUM(CASE WHEN ht.tahap IN ('harian','tahap_1','tahap_2','tahap_3') THEN 1 ELSE 0 END) AS setor_harian"
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE WHEN ht.tahap = 'ujian_akhir' AND hafalans.status = 'lulus' THEN ht.juz ELSE NULL END) AS ujian_juz"
            )
            ->selectRaw(
                "AVG(CASE WHEN ht.tahap IN ('harian','tahap_1','tahap_2','tahap_3') THEN {$scoreSql} ELSE NULL END) AS avg_harian_raw"
            )
            ->selectRaw(
                "AVG(CASE WHEN ht.tahap = 'ujian_akhir' AND hafalans.status = 'lulus' THEN {$scoreSql} ELSE NULL END) AS avg_ujian_raw"
            )
            ->first();

        $avgHarianRaw = $summary?->avg_harian_raw !== null
            ? (float) $summary->avg_harian_raw
            : null;

        $avgUjianRaw = $summary?->avg_ujian_raw !== null
            ? (float) $summary->avg_ujian_raw
            : null;

        return [
            'setor_total' => (int) (
                $summary->setor_total
                ?? 0
            ),
            'setor_harian' => (int) (
                $summary->setor_harian
                ?? 0
            ),
            'ujian_juz' => (int) (
                $summary->ujian_juz
                ?? 0
            ),
            'avg_nilai_sementara' => $avgHarianRaw !== null
                ? min(
                    70,
                    round(
                        $avgHarianRaw,
                        1
                    )
                )
                : 0,
            'avg_nilai_ujian' => $avgUjianRaw !== null
                ? round(
                    $avgUjianRaw,
                    1
                )
                : 0,
        ];
    }

    private function buildHafalanProgress(
        int $santriId,
        ?int $semesterId
    ): Collection {
        $rows = Hafalan::query()
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
            ->whereIn(
                'hafalans.status',
                [
                    'lulus',
                    'ulang',
                ]
            )
            ->when(
                $semesterId,
                fn(Builder $query) =>
                $query->where(
                    'hafalans.semester_id',
                    $semesterId
                )
            )
            ->select([
                'hafalans.id',
                'hafalans.status',
                'hafalans.nilai_label',
                'hafalans.tanggal_setoran',
                'hafalan_templates.juz',
                'hafalan_templates.tahap',
            ])
            ->orderBy('hafalan_templates.juz')
            ->orderBy('hafalan_templates.urutan')
            ->orderByDesc('hafalans.tanggal_setoran')
            ->orderByDesc('hafalans.id')
            ->get()
            ->groupBy('juz');

        return collect(
            range(1, 30)
        )->map(
            function (
                int $juz
            ) use ($rows) {
                $juzRows = $rows->get(
                    $juz,
                    collect()
                );

                $dailyRows = $juzRows
                    ->whereIn(
                        'tahap',
                        self::DAILY_TAHAP
                    );

                $dailyScoredRows = $dailyRows
                    ->filter(
                        fn($row) =>
                        in_array(
                            $row->status,
                            [
                                'lulus',
                                'ulang',
                            ],
                            true
                        )
                            && isset(
                                self::NILAI_MAP[$row->nilai_label]
                            )
                    );

                $examRows = $juzRows
                    ->where(
                        'tahap',
                        'ujian_akhir'
                    );

                $examScoredRows = $examRows
                    ->filter(
                        fn($row) =>
                        $row->status === 'lulus'
                            && isset(
                                self::NILAI_MAP[$row->nilai_label]
                            )
                    );

                $tempRawAverage = $dailyScoredRows
                    ->map(
                        fn($row) =>
                        self::NILAI_MAP[$row->nilai_label]
                    )
                    ->avg();

                $temporaryAverage = $tempRawAverage !== null
                    ? min(
                        70,
                        round(
                            (float) $tempRawAverage,
                            1
                        )
                    )
                    : null;

                $examAverage = $examScoredRows
                    ->map(
                        fn($row) =>
                        self::NILAI_MAP[$row->nilai_label]
                    )
                    ->avg();

                $examAverage = $examAverage !== null
                    ? round(
                        (float) $examAverage,
                        1
                    )
                    : null;

                $stageChecks = collect(
                    [
                        'harian',
                        'tahap_1',
                        'tahap_2',
                        'tahap_3',
                        'ujian_akhir',
                    ]
                )->mapWithKeys(
                    fn(string $stage) => [
                        $stage => $juzRows
                            ->where(
                                'tahap',
                                $stage
                            )
                            ->where(
                                'status',
                                'lulus'
                            )
                            ->isNotEmpty(),
                    ]
                );

                $highestDailyTahap = $dailyRows
                    ->where(
                        'status',
                        'lulus'
                    )
                    ->sortByDesc(
                        fn($row) =>
                        self::TAHAP_RANK[$row->tahap]
                            ?? 0
                    )
                    ->first()?->tahap;

                $hasFinalPass = (bool) $stageChecks
                    ->get(
                        'ujian_akhir',
                        false
                    );

                $latestExam = $examRows
                    ->sortByDesc(
                        fn($row) =>
                        sprintf(
                            '%s-%010d',
                            $row->tanggal_setoran
                                ?: '',
                            (int) $row->id
                        )
                    )
                    ->first();

                $currentTahap = $hasFinalPass
                    ? 'ujian_akhir'
                    : $highestDailyTahap;

                $pct = $currentTahap
                    ? (
                        self::TAHAP_WEIGHT[$currentTahap]
                        ?? 0
                    )
                    : 0;

                if ($hasFinalPass) {
                    [$status, $color, $explanation] = [
                        'Selesai Ujian',
                        'success',
                        'Juz ini sudah lulus ujian akhir. Nilai final memakai nilai ujian.',
                    ];
                } elseif ($latestExam) {
                    [$status, $color, $explanation] = [
                        'Ujian Mengulang',
                        'warning',
                        'Sudah pernah ujian akhir, tetapi belum tercatat lulus. Perlu penguatan atau ujian ulang.',
                    ];
                } elseif ($currentTahap) {
                    [$status, $color, $explanation] = [
                        self::TAHAP_LABEL[$currentTahap]
                            ?? 'Proses',
                        match ($currentTahap) {
                            'tahap_3' => 'info',
                            'tahap_2' => 'primary',
                            'tahap_1' => 'warning',
                            default => 'secondary',
                        },
                        'Masih proses setoran harian/tahapan. Progress maksimal 70% sampai lulus ujian akhir.',
                    ];
                } else {
                    [$status, $color, $explanation] = [
                        'Belum Mulai',
                        'light',
                        'Belum ada setoran lulus/ulang pada Juz ini.',
                    ];
                }

                return [
                    'juz' => $juz,
                    'pct' => $pct,
                    'status' => $status,
                    'color' => $color,
                    'tahap' => $currentTahap,
                    'tahap_label' => $currentTahap
                        ? (
                            self::TAHAP_LABEL[$currentTahap]
                            ?? $currentTahap
                        )
                        : null,
                    'stage_checks' => $stageChecks->all(),
                    'daily_count' => $dailyRows->count(),
                    'exam_count' => $examRows->count(),
                    'temporary_average' => $temporaryAverage,
                    'exam_average' => $examAverage,
                    'latest_exam_status' => $latestExam?->status,
                    'is_exam_passed' => $hasFinalPass,
                    'explanation' => $explanation,
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
                'status' => 'Belum Mulai',
                'color' => 'light',
                'tahap' => null,
                'tahap_label' => null,
                'stage_checks' => [
                    'harian' => false,
                    'tahap_1' => false,
                    'tahap_2' => false,
                    'tahap_3' => false,
                    'ujian_akhir' => false,
                ],
                'daily_count' => 0,
                'exam_count' => 0,
                'temporary_average' => null,
                'exam_average' => null,
                'latest_exam_status' => null,
                'is_exam_passed' => false,
                'explanation' => 'Belum ada setoran lulus/ulang pada Juz ini.',
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

    private function hafalanTahapBadge(
        ?string $tahap
    ): string {
        if (!$tahap) {
            return '-';
        }

        [$class, $label] = match ($tahap) {
            'ujian_akhir' => [
                'bg-success',
                'Ujian Akhir',
            ],
            'tahap_3' => [
                'bg-info',
                'Tahap 3',
            ],
            'tahap_2' => [
                'bg-primary',
                'Tahap 2',
            ],
            'tahap_1' => [
                'bg-warning text-dark',
                'Tahap 1',
            ],
            'harian' => [
                'bg-secondary',
                'Harian',
            ],
            default => [
                'bg-dark',
                str(
                    str_replace(
                        '_',
                        ' ',
                        $tahap
                    )
                )->title(),
            ],
        };

        return "<span class='badge {$class}'>{$label}</span>";
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
