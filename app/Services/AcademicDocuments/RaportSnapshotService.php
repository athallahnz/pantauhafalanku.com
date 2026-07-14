<?php

namespace App\Services\AcademicDocuments;

use App\Models\Hafalan;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Models\Tahsin;
use App\Models\Tilawah;
use App\Support\AcademicDocuments\RaportSnapshotResult;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RaportSnapshotService
{
    private const HAFALAN_STATUSES = [
        'lulus',
        'ulang',
        'hadir_tidak_setor',
        'sakit',
        'izin',
        'alpha',
    ];

    private const ATTENDANCE_STATUSES = [
        'hadir',
        'izin',
        'sakit',
        'alpha',
    ];

    public function buildByIds(
        int $santriId,
        int $semesterId
    ): RaportSnapshotResult {
        return $this->build(
            Santri::query()->findOrFail($santriId),
            Semester::query()->findOrFail($semesterId)
        );
    }

    public function build(
        Santri $santri,
        Semester $semester
    ): RaportSnapshotResult {
        $santri->loadMissing([
            'kelas',
            'musyrif',
        ]);

        $semester->loadMissing([
            'tahunAjaran',
        ]);

        $placement = SantriSemesterPlacement::query()
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
                $semester->id
            )
            ->first();

        $blockers = [];
        $warnings = [];

        /*
        |--------------------------------------------------------------------------
        | Validasi Identitas Santri
        |--------------------------------------------------------------------------
        */

        $missingIdentityFields = collect([
            'NIS' => $santri->nis,
            'Tanggal lahir' => $santri->tanggal_lahir,
            'Jenis kelamin' => $santri->jenis_kelamin,
        ])
            ->filter(
                fn(mixed $value): bool =>
                    blank($value)
            )
            ->keys()
            ->values();

        if ($missingIdentityFields->isNotEmpty()) {
            $warnings[] = [
                'code' => 'student_identity_incomplete',
                'message' =>
                    'Identitas santri belum lengkap: '
                    . $missingIdentityFields->implode(', ')
                    . '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Placement
        |--------------------------------------------------------------------------
        */

        if (!$placement) {
            $blockers[] = [
                'code' => 'placement_missing',
                'message' =>
                    'Placement santri pada semester terpilih belum tersedia.',
            ];
        }

        if ($placement && !$placement->kelas_id) {
            $blockers[] = [
                'code' => 'placement_class_missing',
                'message' =>
                    'Kelas santri pada placement semester belum tersedia.',
            ];
        }

        if ($placement && !$placement->musyrif_id) {
            $warnings[] = [
                'code' => 'placement_musyrif_missing',
                'message' =>
                    'Musyrif pada placement semester belum tersedia.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Lifecycle Semester
        |--------------------------------------------------------------------------
        */

        $isSemesterReadyForPublication =
            $semester->status === 'closed'
            && !$semester->is_active
            && $semester->input_locked_at !== null
            && $semester->closed_at !== null;

        $isLifecycleInconsistent =
            (
                $semester->status === 'active'
                && $semester->closed_at !== null
            )
            ||
            (
                $semester->status === 'active'
                && !$semester->is_active
            )
            ||
            (
                $semester->status === 'closed'
                && $semester->is_active
            )
            ||
            (
                $semester->status === 'closed'
                && $semester->closed_at === null
            )
            ||
            (
                $semester->status === 'closed'
                && $semester->input_locked_at === null
            );

        if (!$isSemesterReadyForPublication) {
            $warnings[] = [
                'code' => 'semester_not_ready_for_publication',
                'message' =>
                    'Semester belum berada pada kondisi closed yang valid. '
                    . 'Draft masih dapat diperiksa, tetapi Raport belum boleh dipublikasikan.',
            ];
        }

        if ($isLifecycleInconsistent) {
            $warnings[] = [
                'code' => 'semester_lifecycle_inconsistent',
                'message' =>
                    'Status semester tidak konsisten dengan is_active, '
                    . 'input_locked_at, atau closed_at. '
                    . 'Periksa lifecycle semester sebelum Raport diterbitkan.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Record Semester dan Legacy
        |--------------------------------------------------------------------------
        */

        $recordCounts = [
            'hafalan' => Hafalan::query()
                ->where('santri_id', $santri->id)
                ->where('semester_id', $semester->id)
                ->count(),

            'tahsin' => Tahsin::query()
                ->where('santri_id', $santri->id)
                ->where('semester_id', $semester->id)
                ->count(),

            'tilawah' => Tilawah::query()
                ->where('santri_id', $santri->id)
                ->where('semester_id', $semester->id)
                ->count(),
        ];

        if (array_sum($recordCounts) === 0) {
            $warnings[] = [
                'code' => 'semester_transactions_empty',
                'message' =>
                    'Belum ada transaksi Hafalan, Tahsin, atau Tilawah pada semester terpilih.',
            ];
        }

        foreach ($recordCounts as $domain => $count) {
            if ($count === 0) {
                $warnings[] = [
                    'code' => "{$domain}_empty",
                    'message' =>
                        'Belum ada data '
                        . Str::title($domain)
                        . ' pada semester terpilih.',
                ];
            }
        }

        $legacyCounts = [
            'hafalan' => Hafalan::query()
                ->where('santri_id', $santri->id)
                ->whereNull('semester_id')
                ->count(),

            'tahsin' => Tahsin::query()
                ->where('santri_id', $santri->id)
                ->whereNull('semester_id')
                ->count(),

            'tilawah' => Tilawah::query()
                ->where('santri_id', $santri->id)
                ->whereNull('semester_id')
                ->count(),
        ];

        if (array_sum($legacyCounts) > 0) {
            $warnings[] = [
                'code' => 'legacy_transactions_exist',
                'message' =>
                    array_sum($legacyCounts)
                    . ' transaksi lama belum mempunyai semester_id '
                    . 'dan tidak dimasukkan ke aktivitas semester Raport.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Bangun Domain Snapshot
        |--------------------------------------------------------------------------
        */

        $semesterEndDate =
            $this->dateValue(
                $semester->tanggal_selesai
            );

        $hafalan = $this->buildHafalan(
            $santri->id,
            $semester->id,
            $semesterEndDate
        );

        $tahsin = $this->buildTahsin(
            $santri->id,
            $semester->id,
            $semesterEndDate
        );

        $tilawah = $this->buildTilawah(
            $santri->id,
            $semester->id,
            $semesterEndDate
        );

        $snapshot = [
            'schema_version' =>
                (string) config(
                    'academic_documents.snapshot_schema_version',
                    'raport-snapshot-v2'
                ),

            'document_type' => 'raport',
            'generated_at' => now()->toIso8601String(),

            'student' => [
                'id' => (int) $santri->id,
                'nama' => (string) $santri->nama,
                'nis' => $santri->nis,
                'tanggal_lahir' =>
                    $this->dateValue(
                        $santri->tanggal_lahir
                    ),
                'jenis_kelamin' =>
                    $santri->jenis_kelamin,
                'status' => $santri->status,
            ],

            'semester' => [
                'id' => (int) $semester->id,
                'nama' => (string) $semester->nama,
                'label' =>
                    $this->semesterLabel($semester),

                'tahun_ajaran' => [
                    'id' =>
                        $semester->tahun_ajaran_id
                            ? (int) $semester->tahun_ajaran_id
                            : null,
                    'nama' =>
                        $semester->tahunAjaran?->nama,
                ],

                'tanggal_mulai' =>
                    $this->dateValue(
                        $semester->tanggal_mulai
                    ),

                'tanggal_selesai' =>
                    $semesterEndDate,

                'status' => $semester->status,
                'is_active' => (bool) $semester->is_active,

                'input_locked_at' =>
                    $this->dateTimeValue(
                        $semester->input_locked_at
                    ),

                'closed_at' =>
                    $this->dateTimeValue(
                        $semester->closed_at
                    ),

                'lifecycle' => [
                    'is_consistent' =>
                        !$isLifecycleInconsistent,

                    'is_ready_for_publication' =>
                        $isSemesterReadyForPublication,
                ],
            ],

            'placement' => [
                'id' =>
                    $placement
                        ? (int) $placement->id
                        : null,

                'status' => $placement?->status,

                'kelas' => [
                    'id' =>
                        $placement?->kelas_id
                            ? (int) $placement->kelas_id
                            : null,
                    'nama' =>
                        $placement?->kelas?->nama_kelas,
                ],

                'musyrif' => [
                    'id' =>
                        $placement?->musyrif_id
                            ? (int) $placement->musyrif_id
                            : null,
                    'nama' =>
                        $placement?->musyrif?->nama,
                    'kode' =>
                        $placement?->musyrif?->kode,
                ],

                'started_at' =>
                    $this->dateTimeValue(
                        $placement?->started_at
                    ),

                'ended_at' =>
                    $this->dateTimeValue(
                        $placement?->ended_at
                    ),
            ],

            'record_counts' => [
                'semester_activity' =>
                    $recordCounts,

                'legacy_without_semester' =>
                    $legacyCounts,
            ],

            'hafalan' => $hafalan,
            'tahsin' => $tahsin,
            'tilawah' => $tilawah,

            'evaluation' =>
                $this->buildEvaluation(
                    $hafalan,
                    $tahsin,
                    $tilawah
                ),

            'publication_readiness' => [
                'semester_ready' =>
                    $isSemesterReadyForPublication,

                'lifecycle_consistent' =>
                    !$isLifecycleInconsistent,

                'identity_complete' =>
                    $missingIdentityFields->isEmpty(),

                'placement_complete' =>
                    $placement !== null
                    && $placement->kelas_id !== null,
            ],

            'source_integrity' =>
                $this->sourceIntegrity(
                    $santri->id,
                    $semester->id,
                    $semesterEndDate,
                    $recordCounts
                ),
        ];

        return new RaportSnapshotResult(
            snapshot: $snapshot,
            blockers: $blockers,
            warnings: $warnings
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHafalan(
        int $santriId,
        int $semesterId,
        ?string $semesterEndDate
    ): array {
        $statusCounts = $this->statusCounts(
            Hafalan::class,
            $santriId,
            $semesterId,
            self::HAFALAN_STATUSES
        );

        $semesterLatest = Hafalan::query()
            ->leftJoin(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'hafalans.hafalan_template_id'
            )
            ->where(
                'hafalans.santri_id',
                $santriId
            )
            ->where(
                'hafalans.semester_id',
                $semesterId
            )
            ->select([
                'hafalans.id',
                'hafalans.tanggal_setoran',
                'hafalans.status',
                'hafalans.nilai_label',
                'hafalans.catatan',
                'ht.juz',
                'ht.tahap',
                'ht.label',
            ])
            ->orderByDesc(
                'hafalans.tanggal_setoran'
            )
            ->orderByDesc(
                'hafalans.id'
            )
            ->first();

        $cumulativeProgress =
            $this->hafalanProgressCumulative(
                $santriId,
                $semesterEndDate
            );

        return [
            'semester_activity' => [
                'status_counts' => $statusCounts,

                'setoran' =>
                    $statusCounts['lulus']
                    + $statusCounts['ulang'],

                'lulus' =>
                    $statusCounts['lulus'],

                'ulang' =>
                    $statusCounts['ulang'],

                'hadir_tidak_setor' =>
                    $statusCounts['hadir_tidak_setor'],

                'sakit' =>
                    $statusCounts['sakit'],

                'izin' =>
                    $statusCounts['izin'],

                'alpha' =>
                    $statusCounts['alpha'],

                'avg_nilai' =>
                    $this->averageNilai(
                        Hafalan::class,
                        $santriId,
                        $semesterId,
                        [
                            'lulus',
                            'ulang',
                        ]
                    ),

                'latest' =>
                    $this->mapHafalanLatest(
                        $semesterLatest
                    ),
            ],

            'cumulative_achievement' => [
                'cutoff_date' =>
                    $semesterEndDate,

                'overall_pct' =>
                    (int) round(
                        collect(
                            $cumulativeProgress
                        )->avg('pct')
                        ?? 0
                    ),

                'juz_selesai' =>
                    collect(
                        $cumulativeProgress
                    )
                        ->where('pct', 100)
                        ->count(),

                'progress_per_juz' =>
                    $cumulativeProgress,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTahsin(
        int $santriId,
        int $semesterId,
        ?string $semesterEndDate
    ): array {
        $statusCounts = $this->statusCounts(
            Tahsin::class,
            $santriId,
            $semesterId,
            self::ATTENDANCE_STATUSES
        );

        $semesterLatest = Tahsin::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        $cumulativeProgress =
            $this->tahsinProgressCumulative(
                $santriId,
                $semesterEndDate
            );

        return [
            'semester_activity' => [
                'status_counts' =>
                    $statusCounts,

                'hadir' =>
                    $statusCounts['hadir'],

                'izin' =>
                    $statusCounts['izin'],

                'sakit' =>
                    $statusCounts['sakit'],

                'alpha' =>
                    $statusCounts['alpha'],

                'avg_nilai' =>
                    $this->averageNilai(
                        Tahsin::class,
                        $santriId,
                        $semesterId,
                        ['hadir']
                    ),

                'latest' =>
                    $this->mapTahsinLatest(
                        $semesterLatest
                    ),
            ],

            'cumulative_achievement' => [
                'cutoff_date' =>
                    $semesterEndDate,

                'overall_pct' =>
                    (int) round(
                        collect(
                            $cumulativeProgress
                        )->avg('pct')
                        ?? 0
                    ),

                'progress_per_buku' =>
                    $cumulativeProgress,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTilawah(
        int $santriId,
        int $semesterId,
        ?string $semesterEndDate
    ): array {
        $statusCounts = $this->statusCounts(
            Tilawah::class,
            $santriId,
            $semesterId,
            self::ATTENDANCE_STATUSES
        );

        $semesterLatest = Tilawah::query()
            ->leftJoin(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'tilawahs.hafalan_template_id'
            )
            ->where(
                'tilawahs.santri_id',
                $santriId
            )
            ->where(
                'tilawahs.semester_id',
                $semesterId
            )
            ->select([
                'tilawahs.id',
                'tilawahs.tanggal',
                'tilawahs.status',
                'tilawahs.catatan',
                'ht.juz',
                'ht.label',
            ])
            ->orderByDesc(
                'tilawahs.tanggal'
            )
            ->orderByDesc(
                'tilawahs.id'
            )
            ->first();

        $cumulativeMaxJuz =
            $this->tilawahMaxJuzCumulative(
                $santriId,
                $semesterEndDate
            );

        return [
            'semester_activity' => [
                'status_counts' =>
                    $statusCounts,

                'hadir' =>
                    $statusCounts['hadir'],

                'izin' =>
                    $statusCounts['izin'],

                'sakit' =>
                    $statusCounts['sakit'],

                'alpha' =>
                    $statusCounts['alpha'],

                'latest' =>
                    $this->mapTilawahLatest(
                        $semesterLatest
                    ),
            ],

            'cumulative_achievement' => [
                'cutoff_date' =>
                    $semesterEndDate,

                'max_juz' =>
                    $cumulativeMaxJuz,

                'overall_pct' =>
                    min(
                        100,
                        (int) round(
                            (
                                $cumulativeMaxJuz
                                / 30
                            )
                            * 100
                        )
                    ),
            ],
        ];
    }


    /**
     * @return array<string, int>
     */
    private function statusCounts(
        string $modelClass,
        int $santriId,
        int $semesterId,
        array $expectedStatuses
    ): array {
        $raw = $modelClass::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->selectRaw(
                'status, COUNT(*) AS total'
            )
            ->groupBy('status')
            ->pluck(
                'total',
                'status'
            );

        return collect($expectedStatuses)
            ->mapWithKeys(
                fn(string $status): array => [
                    $status =>
                        (int) (
                            $raw[$status]
                            ?? 0
                        ),
                ]
            )
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hafalanProgressCumulative(
        int $santriId,
        ?string $cutoffDate
    ): array {
        $rank = (array) config(
            'academic_documents.hafalan_tahap_rank',
            []
        );

        $weights = (array) config(
            'academic_documents.hafalan_tahap_weight',
            []
        );

        $query = Hafalan::query()
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
            );

        $this->applyCutoffDate(
            $query,
            'hafalans.tanggal_setoran',
            $cutoffDate
        );

        $highestStagePerJuz = $query
            ->select([
                'hafalan_templates.juz',
                'hafalan_templates.tahap',
            ])
            ->get()
            ->groupBy('juz')
            ->map(
                fn(Collection $rows): ?string =>
                    $rows
                    ->sortByDesc(
                        fn($row): int =>
                            (int) (
                                $rank[$row->tahap]
                                ?? 0
                            )
                    )
                    ->first()
                    ?->tahap
            );

        return collect(range(1, 30))
            ->map(
                function (
                    int $juz
                ) use (
                    $highestStagePerJuz,
                    $weights
                ): array {
                    $stage =
                        $highestStagePerJuz
                        ->get($juz);

                    $percentage = $stage
                        ? (int) (
                            $weights[$stage]
                            ?? 0
                        )
                        : 0;

                    return [
                        'juz' => $juz,
                        'tahap' => $stage,
                        'pct' => $percentage,
                        'status' =>
                            $this->hafalanProgressLabel(
                                $percentage
                            ),
                    ];
                }
            )
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tahsinProgressCumulative(
        int $santriId,
        ?string $cutoffDate
    ): array {
        $books = (array) config(
            'academic_documents.tahsin_books',
            []
        );

        $query = Tahsin::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'status',
                'hadir'
            );

        $this->applyCutoffDate(
            $query,
            'tanggal',
            $cutoffDate
        );

        $maxPages = $query
            ->selectRaw(
                'buku, MAX(halaman) AS max_halaman'
            )
            ->groupBy('buku')
            ->pluck(
                'max_halaman',
                'buku'
            );

        return collect($books)
            ->map(
                function (
                    array $book,
                    string $key
                ) use ($maxPages): array {
                    $maximum = max(
                        1,
                        (int) (
                            $book['max']
                            ?? 1
                        )
                    );

                    $current = (int) (
                        $maxPages[$key]
                        ?? 0
                    );

                    $percentage = min(
                        100,
                        (int) round(
                            (
                                $current
                                / $maximum
                            )
                            * 100
                        )
                    );

                    return [
                        'buku_key' => $key,

                        'label' =>
                            (string) (
                                $book['label']
                                ?? Str::title(
                                    str_replace(
                                        '_',
                                        ' ',
                                        $key
                                    )
                                )
                            ),

                        'current' => $current,
                        'max' => $maximum,
                        'pct' => $percentage,

                        'status' =>
                            match (true) {
                                $percentage >= 100 =>
                                    'Selesai',

                                $percentage > 0 =>
                                    'Sedang Berjalan',

                                default =>
                                    'Belum Mulai',
                            },
                    ];
                }
            )
            ->values()
            ->all();
    }

    private function tilawahMaxJuzCumulative(
        int $santriId,
        ?string $cutoffDate
    ): int {
        $query = DB::table('tilawahs')
            ->join(
                'hafalan_templates',
                'hafalan_templates.id',
                '=',
                'tilawahs.hafalan_template_id'
            )
            ->where(
                'tilawahs.santri_id',
                $santriId
            )
            ->where(
                'tilawahs.status',
                'hadir'
            );

        if ($cutoffDate) {
            $query->whereDate(
                'tilawahs.tanggal',
                '<=',
                $cutoffDate
            );
        }

        return (int) (
            $query->max(
                'hafalan_templates.juz'
            )
            ?? 0
        );
    }

    private function averageNilai(
        string $modelClass,
        int $santriId,
        int $semesterId,
        array $statuses
    ): ?float {
        $map = (array) config(
            'academic_documents.nilai_map',
            []
        );

        $scores = $modelClass::query()
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->whereIn(
                'status',
                $statuses
            )
            ->whereNotNull(
                'nilai_label'
            )
            ->pluck(
                'nilai_label'
            )
            ->map(
                fn(?string $label): ?int =>
                    isset($map[$label])
                        ? (int) $map[$label]
                        : null
            )
            ->filter(
                fn(?int $score): bool =>
                    $score !== null
            );

        if ($scores->isEmpty()) {
            return null;
        }

        return round(
            (float) $scores->avg(),
            1
        );
    }

    private function nilaiScore(
        ?string $label
    ): ?int {
        if (!$label) {
            return null;
        }

        $map = (array) config(
            'academic_documents.nilai_map',
            []
        );

        return isset($map[$label])
            ? (int) $map[$label]
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEvaluation(
        array $hafalan,
        array $tahsin,
        array $tilawah
    ): array {
        $components = [
            'hafalan_cumulative_progress' =>
                (float) (
                    $hafalan[
                        'cumulative_achievement'
                    ]['overall_pct']
                    ?? 0
                ),

            'tahsin_cumulative_progress' =>
                (float) (
                    $tahsin[
                        'cumulative_achievement'
                    ]['overall_pct']
                    ?? 0
                ),

            'tilawah_cumulative_progress' =>
                (float) (
                    $tilawah[
                        'cumulative_achievement'
                    ]['overall_pct']
                    ?? 0
                ),

            'semester_discipline' =>
                $this->disciplineScore(
                    $hafalan,
                    $tahsin,
                    $tilawah
                ),
        ];

        $weights = (array) config(
            'academic_documents.progress_index.weights',
            []
        );

        $weightMap = [
            'hafalan_cumulative_progress' =>
                (float) (
                    $weights['hafalan']
                    ?? 0
                ),

            'tahsin_cumulative_progress' =>
                (float) (
                    $weights['tahsin']
                    ?? 0
                ),

            'tilawah_cumulative_progress' =>
                (float) (
                    $weights['tilawah']
                    ?? 0
                ),

            'semester_discipline' =>
                (float) (
                    $weights['discipline']
                    ?? 0
                ),
        ];

        $weightTotal = max(
            1,
            array_sum($weightMap)
        );

        $progressIndex = 0.0;

        foreach ($components as $key => $score) {
            $progressIndex +=
                $score
                * (
                    $weightMap[$key]
                    / $weightTotal
                );
        }

        $progressIndex = round(
            $progressIndex,
            1
        );

        return [
            'component_scores' => $components,

            'progress_index' => $progressIndex,

            'progress_index_note' =>
                'Indeks ini hanya ringkasan progress untuk proses review dan bukan nilai Raport final.',

            'predicate_enabled' =>
                (bool) config(
                    'academic_documents.raport_predicate.enabled',
                    false
                ),

            'suggested_predicate' =>
                $this->suggestedPredicate(
                    $progressIndex
                ),

            'predicate_note' =>
                'Predikat otomatis dinonaktifkan sampai lembaga menyepakati kebijakan penilaian resmi.',
        ];
    }

    private function disciplineScore(
        array $hafalan,
        array $tahsin,
        array $tilawah
    ): float {
        $hafalanActivity =
            $hafalan['semester_activity']
            ?? [];

        $tahsinActivity =
            $tahsin['semester_activity']
            ?? [];

        $tilawahActivity =
            $tilawah['semester_activity']
            ?? [];

        $total =
            array_sum(
                $hafalanActivity[
                    'status_counts'
                ]
                ?? []
            )
            + array_sum(
                $tahsinActivity[
                    'status_counts'
                ]
                ?? []
            )
            + array_sum(
                $tilawahActivity[
                    'status_counts'
                ]
                ?? []
            );

        if ($total <= 0) {
            return 0.0;
        }

        $alpha =
            (int) (
                $hafalanActivity['alpha']
                ?? 0
            )
            + (int) (
                $tahsinActivity['alpha']
                ?? 0
            )
            + (int) (
                $tilawahActivity['alpha']
                ?? 0
            );

        return round(
            max(
                0,
                100
                - (
                    ($alpha / $total)
                    * 100
                )
            ),
            1
        );
    }

    private function suggestedPredicate(
        float $score
    ): ?string {
        if (
            !config(
                'academic_documents.raport_predicate.enabled',
                false
            )
        ) {
            return null;
        }

        $thresholds = collect(
            config(
                'academic_documents.raport_predicate.thresholds',
                []
            )
        )->sortByDesc(
            fn(array $item): float =>
                (float) (
                    $item['min']
                    ?? 0
                )
        );

        foreach ($thresholds as $threshold) {
            if (
                $score >= (float) (
                    $threshold['min']
                    ?? 0
                )
            ) {
                return (string) (
                    $threshold['label']
                    ?? '-'
                );
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceIntegrity(
        int $santriId,
        int $semesterId,
        ?string $semesterEndDate,
        array $recordCounts
    ): array {
        $semesterSources = [
            'hafalan' =>
                $this->sourceState(
                    'hafalans',
                    $santriId,
                    $semesterId
                ),

            'tahsin' =>
                $this->sourceState(
                    'tahsins',
                    $santriId,
                    $semesterId
                ),

            'tilawah' =>
                $this->sourceState(
                    'tilawahs',
                    $santriId,
                    $semesterId
                ),
        ];

        $cumulativeSources = [
            'hafalan' =>
                $this->sourceStateUntilDate(
                    'hafalans',
                    'tanggal_setoran',
                    $santriId,
                    $semesterEndDate
                ),

            'tahsin' =>
                $this->sourceStateUntilDate(
                    'tahsins',
                    'tanggal',
                    $santriId,
                    $semesterEndDate
                ),

            'tilawah' =>
                $this->sourceStateUntilDate(
                    'tilawahs',
                    'tanggal',
                    $santriId,
                    $semesterEndDate
                ),
        ];

        $fingerprintPayload = [
            'semester' => $semesterSources,
            'cumulative_until' =>
                $semesterEndDate,
            'cumulative' =>
                $cumulativeSources,
        ];

        return [
            'semester_record_counts' =>
                $recordCounts,

            'semester_sources' =>
                $semesterSources,

            'cumulative_cutoff_date' =>
                $semesterEndDate,

            'cumulative_sources' =>
                $cumulativeSources,

            'fingerprint_sha256' => hash(
                'sha256',
                json_encode(
                    $fingerprintPayload,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                ) ?: ''
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceState(
        string $table,
        int $santriId,
        int $semesterId
    ): array {
        $row = DB::table($table)
            ->where(
                'santri_id',
                $santriId
            )
            ->where(
                'semester_id',
                $semesterId
            )
            ->selectRaw(
                '
                    COUNT(*) AS total,
                    MAX(id) AS max_id,
                    MAX(updated_at) AS max_updated_at
                '
            )
            ->first();

        return [
            'total' =>
                (int) (
                    $row->total
                    ?? 0
                ),

            'max_id' =>
                $row->max_id
                    ? (int) $row->max_id
                    : null,

            'max_updated_at' =>
                $this->dateTimeValue(
                    $row->max_updated_at
                    ?? null
                ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceStateUntilDate(
        string $table,
        string $dateColumn,
        int $santriId,
        ?string $cutoffDate
    ): array {
        $query = DB::table($table)
            ->where(
                'santri_id',
                $santriId
            );

        if ($cutoffDate) {
            $query->whereDate(
                $dateColumn,
                '<=',
                $cutoffDate
            );
        }

        $row = $query
            ->selectRaw(
                '
                    COUNT(*) AS total,
                    MAX(id) AS max_id,
                    MAX(updated_at) AS max_updated_at
                '
            )
            ->first();

        return [
            'total' =>
                (int) (
                    $row->total
                    ?? 0
                ),

            'max_id' =>
                $row->max_id
                    ? (int) $row->max_id
                    : null,

            'max_updated_at' =>
                $this->dateTimeValue(
                    $row->max_updated_at
                    ?? null
                ),
        ];
    }

    private function applyCutoffDate(
        Builder $query,
        string $column,
        ?string $cutoffDate
    ): void {
        if (!$cutoffDate) {
            return;
        }

        $query->whereDate(
            $column,
            '<=',
            $cutoffDate
        );
    }

    private function mapHafalanLatest(
        mixed $latest
    ): ?array {
        if (!$latest) {
            return null;
        }

        return [
            'id' => (int) $latest->id,

            'tanggal' =>
                $this->dateValue(
                    $latest->tanggal_setoran
                ),

            'status' => $latest->status,

            'nilai_label' =>
                $latest->nilai_label,

            'nilai_score' =>
                $this->nilaiScore(
                    $latest->nilai_label
                ),

            'juz' =>
                $latest->juz
                    ? (int) $latest->juz
                    : null,

            'tahap' => $latest->tahap,
            'target' => $latest->label,
            'catatan' => $latest->catatan,
        ];
    }

    private function mapTahsinLatest(
        mixed $latest
    ): ?array {
        if (!$latest) {
            return null;
        }

        return [
            'id' => (int) $latest->id,

            'tanggal' =>
                $this->dateValue(
                    $latest->tanggal
                ),

            'buku' => $latest->buku,

            'buku_label' =>
                $this->tahsinBookLabel(
                    $latest->buku
                ),

            'halaman' =>
                $latest->halaman
                    ? (int) $latest->halaman
                    : null,

            'status' => $latest->status,

            'nilai_label' =>
                $latest->nilai_label,

            'nilai_score' =>
                $this->nilaiScore(
                    $latest->nilai_label
                ),

            'catatan' => $latest->catatan,
        ];
    }

    private function mapTilawahLatest(
        mixed $latest
    ): ?array {
        if (!$latest) {
            return null;
        }

        return [
            'id' => (int) $latest->id,

            'tanggal' =>
                $this->dateValue(
                    $latest->tanggal
                ),

            'status' => $latest->status,

            'juz' =>
                $latest->juz
                    ? (int) $latest->juz
                    : null,

            'target' => $latest->label,
            'catatan' => $latest->catatan,
        ];
    }

    private function semesterLabel(
        Semester $semester
    ): string {
        $semesterName = Str::title(
            str_replace(
                '_',
                ' ',
                (string) $semester->nama
            )
        );

        $academicYear = Str::title(
            str_replace(
                '_',
                ' ',
                (string) (
                    $semester->tahunAjaran?->nama
                    ?? '-'
                )
            )
        );

        return "{$semesterName} — {$academicYear}";
    }

    private function tahsinBookLabel(
        ?string $book
    ): ?string {
        if (!$book) {
            return null;
        }

        return config(
            "academic_documents.tahsin_books.{$book}.label"
        )
            ?? Str::title(
                str_replace(
                    '_',
                    ' ',
                    $book
                )
            );
    }

    private function hafalanProgressLabel(
        int $percentage
    ): string {
        return match (true) {
            $percentage >= 100 =>
                'Selesai',

            $percentage >= 80 =>
                'Tahap 3',

            $percentage >= 60 =>
                'Tahap 2',

            $percentage >= 40 =>
                'Tahap 1',

            $percentage > 0 =>
                'Harian',

            default =>
                'Belum Mulai',
        };
    }

    private function dateValue(
        mixed $value
    ): ?string {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)
                ->toDateString();
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function dateTimeValue(
        mixed $value
    ): ?string {
        if (!$value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->toIso8601String();
        }

        try {
            return Carbon::parse($value)
                ->toIso8601String();
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
