<?php

namespace App\Services;

use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuranExecutiveMetricsService
{
    private const DAILY_STAGES = [
        'harian',
        'tahap_1',
        'tahap_2',
        'tahap_3',
    ];

    public function build(Request $request): array
    {
        $semester = $this->resolveSemester($request);
        $context = $this->resolveRange($request, $semester);

        $placementRows = DB::table('santri_semester_placements as sp')
            ->where('sp.semester_id', $semester->id)
            ->select('sp.santri_id', 'sp.kelas_id', 'sp.musyrif_id')
            ->distinct()
            ->get();

        $santriIds = $placementRows
            ->pluck('santri_id')
            ->filter()
            ->unique()
            ->values();

        $musyrifIds = $placementRows
            ->pluck('musyrif_id')
            ->filter()
            ->unique()
            ->values();

        $kelasIds = $placementRows
            ->pluck('kelas_id')
            ->filter()
            ->unique()
            ->values();

        $summary = $this->summarizeHafalan(
            (int) $semester->id,
            $santriIds,
            $context['start'],
            $context['end']
        );

        $summary['total_santri'] = $santriIds->count();
        $summary['total_musyrif'] = $musyrifIds->count();
        $summary['total_kelas'] = $kelasIds->count();
        $summary['coverage_pct'] = $this->percentage(
            $summary['santri_aktif'],
            $summary['total_santri']
        );
        $summary['avg_setoran_per_santri'] = $summary['total_santri'] > 0
            ? round($summary['total_setor'] / $summary['total_santri'], 2)
            : 0;
        $summary['alpha_risk_rate_pct'] = $this->percentage(
            $summary['santri_risiko_alpha'],
            $summary['total_santri']
        );
        $summary['santri_belum_setor'] = max(
            0,
            $summary['total_santri'] - $summary['santri_aktif']
        );

        $attendance = $this->attendanceSummary(
            $musyrifIds,
            $context['start'],
            $context['end']
        );

        $comparison = $this->buildComparison(
            $request,
            (int) $semester->id,
            $santriIds,
            $context,
            $semester
        );

        $classPerformance = $this->classPerformance(
            (int) $semester->id,
            $context['start'],
            $context['end']
        );

        $musyrifPerformance = $this->musyrifPerformance(
            (int) $semester->id,
            $context['start'],
            $context['end']
        );

        $juzProgress = $this->juzProgress(
            (int) $semester->id,
            $santriIds,
            $context['start'],
            $context['end']
        );

        $trend = $this->trend(
            (int) $semester->id,
            $santriIds,
            $context['start'],
            $context['end']
        );

        $integrity = $this->dataIntegrity(
            (int) $semester->id,
            $santriIds,
            $context['start'],
            $context['end']
        );

        $inactive = $this->inactiveSantriSummary(
            (int) $semester->id,
            $santriIds,
            $context['start'],
            $context['end']
        );

        $referenceAttendance = $this->referenceDayAttendance(
            $musyrifIds,
            $context['end']
        );

        $health = $this->departmentHealth(
            $summary,
            $attendance,
            $comparison
        );

        $attention = $this->attentionItems(
            $summary,
            $inactive,
            $referenceAttendance,
            $classPerformance,
            $integrity
        );

        return [
            'semester' => [
                'id' => (int) $semester->id,
                'label' => trim(
                    ($semester->nama ?? 'Semester')
                    . ' '
                    . ($semester->tahunAjaran?->nama ?? '')
                ),
                'status' => $semester->status ?? null,
                'is_active' => ($semester->status ?? null) === 'active'
                    || (bool) ($semester->is_active ?? false),
                'start_date' => Carbon::parse($semester->tanggal_mulai)
                    ->toDateString(),
                'end_date' => Carbon::parse($semester->tanggal_selesai)
                    ->toDateString(),
            ],
            'period' => [
                'range' => $context['range'],
                'start_date' => $context['start']->toDateString(),
                'end_date' => $context['end']->toDateString(),
                'label' => $context['start']->translatedFormat('d M Y')
                    . ' — '
                    . $context['end']->translatedFormat('d M Y'),
                'updated_at' => now()->translatedFormat('d M Y, H:i'),
                'semester_progress_pct' => $this->semesterProgress(
                    Carbon::parse($semester->tanggal_mulai)->startOfDay(),
                    Carbon::parse($semester->tanggal_selesai)->endOfDay()
                ),
            ],
            'health' => $health,
            'kpi' => $summary,
            'attendance' => $attendance,
            'comparison' => $comparison,
            'inactive' => $inactive,
            'reference_attendance' => $referenceAttendance,
            'attention' => $attention,
            'class_performance' => $classPerformance,
            'musyrif_performance' => $musyrifPerformance,
            'juz_progress' => $juzProgress,
            'trend' => $trend,
            'integrity' => $integrity,
            'thresholds' => config('quran-executive.thresholds', []),
        ];
    }

    private function resolveSemester(Request $request): Semester
    {
        $semester = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->when(
                $request->filled('semester_id'),
                fn($query) => $query->whereKey((int) $request->input('semester_id'))
            )
            ->when(
                !$request->filled('semester_id'),
                fn($query) => $query->where('status', 'active')
            )
            ->first();

        $semester ??= Semester::query()
            ->with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')
            ->first();

        if (!$semester || !$semester->tanggal_mulai || !$semester->tanggal_selesai) {
            throw ValidationException::withMessages([
                'semester_id' => ['Semester beserta rentang tanggalnya belum tersedia.'],
            ]);
        }

        return $semester;
    }

    private function resolveRange(Request $request, Semester $semester): array
    {
        $semesterStart = Carbon::parse($semester->tanggal_mulai)->startOfDay();
        $semesterEnd = Carbon::parse($semester->tanggal_selesai)->endOfDay();
        $effectiveSemesterEnd = $semesterEnd->isFuture()
            ? now()->endOfDay()->min($semesterEnd)
            : $semesterEnd;

        $range = (string) $request->input('range', 'semester');

        if ($range === 'custom') {
            $start = Carbon::parse((string) $request->input('start_date'))->startOfDay();
            $end = Carbon::parse((string) $request->input('end_date'))->endOfDay();
        } elseif ($range === '7d') {
            $end = $effectiveSemesterEnd->copy();
            $start = $end->copy()->subDays(6)->startOfDay();
        } elseif ($range === '30d') {
            $end = $effectiveSemesterEnd->copy();
            $start = $end->copy()->subDays(29)->startOfDay();
        } else {
            $range = 'semester';
            $start = $semesterStart->copy();
            $end = $effectiveSemesterEnd->copy();
        }

        if ($start->lt($semesterStart)) {
            $start = $semesterStart->copy();
        }

        if ($end->gt($semesterEnd)) {
            $end = $semesterEnd->copy();
        }

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'range' => ['Rentang laporan berada di luar semester terpilih.'],
            ]);
        }

        return compact('range', 'start', 'end');
    }

    private function summarizeHafalan(
        int $semesterId,
        Collection $santriIds,
        Carbon $start,
        Carbon $end
    ): array {
        if ($santriIds->isEmpty()) {
            return $this->emptyHafalanSummary();
        }

        $nilaiSql = $this->nilaiSql('h.nilai_label');
        $dailyStages = "'" . implode("','", self::DAILY_STAGES) . "'";

        $row = DB::table('hafalans as h')
            ->leftJoin('hafalan_templates as ht', 'ht.id', '=', 'h.hafalan_template_id')
            ->where('h.semester_id', $semesterId)
            ->whereIn('h.santri_id', $santriIds)
            ->whereBetween('h.tanggal_setoran', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->selectRaw("SUM(CASE WHEN h.status IN ('lulus','ulang') THEN 1 ELSE 0 END) AS total_setor")
            ->selectRaw("SUM(CASE WHEN h.status IN ('lulus','ulang') AND ht.tahap IN ({$dailyStages}) THEN 1 ELSE 0 END) AS setoran_harian")
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status = 'lulus' AND ht.tahap = 'ujian_akhir' THEN CONCAT(h.santri_id, ':', ht.juz) END) AS lulus_juz")
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status IN ('lulus','ulang') THEN h.santri_id END) AS santri_aktif")
            ->selectRaw("ROUND(AVG(CASE WHEN h.status = 'lulus' AND ht.tahap = 'ujian_akhir' THEN {$nilaiSql} END), 2) AS avg_nilai_ujian")
            ->selectRaw("SUM(CASE WHEN h.status = 'hadir_tidak_setor' THEN 1 ELSE 0 END) AS hadir_tidak_setor")
            ->selectRaw("SUM(CASE WHEN h.status = 'izin' THEN 1 ELSE 0 END) AS izin")
            ->selectRaw("SUM(CASE WHEN h.status = 'sakit' THEN 1 ELSE 0 END) AS sakit")
            ->selectRaw("SUM(CASE WHEN h.status = 'alpha' THEN 1 ELSE 0 END) AS alpha")
            ->selectRaw('COUNT(h.id) AS total_status')
            ->first();

        $alphaMinimum = (int) config('quran-executive.risk.alpha_minimum', 3);

        $alphaRiskSub = DB::table('hafalans as h')
            ->where('h.semester_id', $semesterId)
            ->whereIn('h.santri_id', $santriIds)
            ->whereBetween('h.tanggal_setoran', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->where('h.status', 'alpha')
            ->select('h.santri_id')
            ->groupBy('h.santri_id')
            ->havingRaw('COUNT(*) >= ?', [$alphaMinimum]);

        $riskCount = DB::query()
            ->fromSub($alphaRiskSub, 'alpha_risk')
            ->count();

        return [
            'total_setor' => (int) ($row->total_setor ?? 0),
            'setoran_harian' => (int) ($row->setoran_harian ?? 0),
            'lulus_juz' => (int) ($row->lulus_juz ?? 0),
            'santri_aktif' => (int) ($row->santri_aktif ?? 0),
            'avg_nilai_ujian' => round((float) ($row->avg_nilai_ujian ?? 0), 2),
            'hadir_tidak_setor' => (int) ($row->hadir_tidak_setor ?? 0),
            'izin' => (int) ($row->izin ?? 0),
            'sakit' => (int) ($row->sakit ?? 0),
            'alpha' => (int) ($row->alpha ?? 0),
            'total_status' => (int) ($row->total_status ?? 0),
            'santri_risiko_alpha' => (int) $riskCount,
        ];
    }

    private function emptyHafalanSummary(): array
    {
        return [
            'total_setor' => 0,
            'setoran_harian' => 0,
            'lulus_juz' => 0,
            'santri_aktif' => 0,
            'avg_nilai_ujian' => 0,
            'hadir_tidak_setor' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
            'total_status' => 0,
            'santri_risiko_alpha' => 0,
        ];
    }

    private function attendanceSummary(
        Collection $musyrifIds,
        Carbon $start,
        Carbon $end
    ): array {
        if ($musyrifIds->isEmpty()) {
            return [
                'total_records' => 0,
                'valid_records' => 0,
                'suspect_records' => 0,
                'rejected_records' => 0,
                'valid_pct' => 0,
            ];
        }

        $row = DB::table('musyrif_attendances')
            ->whereIn('musyrif_id', $musyrifIds)
            ->whereBetween('attendance_at', [$start, $end])
            ->selectRaw('COUNT(*) AS total_records')
            ->selectRaw("SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) AS valid_records")
            ->selectRaw("SUM(CASE WHEN status = 'suspect' THEN 1 ELSE 0 END) AS suspect_records")
            ->selectRaw("SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_records")
            ->first();

        $total = (int) ($row->total_records ?? 0);
        $valid = (int) ($row->valid_records ?? 0);

        return [
            'total_records' => $total,
            'valid_records' => $valid,
            'suspect_records' => (int) ($row->suspect_records ?? 0),
            'rejected_records' => (int) ($row->rejected_records ?? 0),
            'valid_pct' => $this->percentage($valid, $total),
        ];
    }

    private function buildComparison(
        Request $request,
        int $semesterId,
        Collection $santriIds,
        array $context,
        Semester $semester
    ): array {
        $semesterStart = Carbon::parse($semester->tanggal_mulai)->startOfDay();
        $range = $context['range'];

        if ($range === 'semester') {
            $days = max(1, (int) config('quran-executive.comparison_days_for_semester', 30));
            $currentEnd = $context['end']->copy();
            $currentStart = $currentEnd->copy()->subDays($days - 1)->startOfDay();

            if ($currentStart->lt($semesterStart)) {
                $currentStart = $semesterStart->copy();
            }
        } else {
            $currentStart = $context['start']->copy();
            $currentEnd = $context['end']->copy();
        }

        $durationDays = $currentStart->copy()->startOfDay()
            ->diffInDays($currentEnd->copy()->startOfDay()) + 1;

        $previousEnd = $currentStart->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()
            ->subDays($durationDays - 1)
            ->startOfDay();

        if ($previousStart->lt($semesterStart)) {
            return [
                'available' => false,
                'label' => 'Belum ada periode pembanding yang setara dalam semester ini',
                'setoran_pct' => null,
                'coverage_points' => null,
                'lulus_juz_pct' => null,
            ];
        }

        $current = $this->summarizeHafalan(
            $semesterId,
            $santriIds,
            $currentStart,
            $currentEnd
        );
        $previous = $this->summarizeHafalan(
            $semesterId,
            $santriIds,
            $previousStart,
            $previousEnd
        );

        $totalSantri = $santriIds->count();
        $currentCoverage = $this->percentage($current['santri_aktif'], $totalSantri);
        $previousCoverage = $this->percentage($previous['santri_aktif'], $totalSantri);

        return [
            'available' => true,
            'label' => $currentStart->translatedFormat('d M')
                . '–'
                . $currentEnd->translatedFormat('d M Y')
                . ' dibanding '
                . $previousStart->translatedFormat('d M')
                . '–'
                . $previousEnd->translatedFormat('d M Y'),
            'setoran_pct' => $this->growthPercentage(
                $current['total_setor'],
                $previous['total_setor']
            ),
            'coverage_points' => round($currentCoverage - $previousCoverage, 1),
            'lulus_juz_pct' => $this->growthPercentage(
                $current['lulus_juz'],
                $previous['lulus_juz']
            ),
            'current' => [
                'start_date' => $currentStart->toDateString(),
                'end_date' => $currentEnd->toDateString(),
                'total_setor' => $current['total_setor'],
                'coverage_pct' => $currentCoverage,
                'lulus_juz' => $current['lulus_juz'],
            ],
            'previous' => [
                'start_date' => $previousStart->toDateString(),
                'end_date' => $previousEnd->toDateString(),
                'total_setor' => $previous['total_setor'],
                'coverage_pct' => $previousCoverage,
                'lulus_juz' => $previous['lulus_juz'],
            ],
        ];
    }

    private function classPerformance(
        int $semesterId,
        Carbon $start,
        Carbon $end
    ): array {
        $rows = DB::table('santri_semester_placements as sp')
            ->join('kelas as k', 'k.id', '=', 'sp.kelas_id')
            ->leftJoin('hafalans as h', function ($join) use ($semesterId, $start, $end): void {
                $join->on('h.santri_id', '=', 'sp.santri_id')
                    ->where('h.semester_id', '=', $semesterId)
                    ->whereBetween('h.tanggal_setoran', [
                        $start->toDateString(),
                        $end->toDateString(),
                    ]);
            })
            ->leftJoin('hafalan_templates as ht', 'ht.id', '=', 'h.hafalan_template_id')
            ->where('sp.semester_id', $semesterId)
            ->select('k.id', 'k.nama_kelas')
            ->selectRaw('COUNT(DISTINCT sp.santri_id) AS total_santri')
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status IN ('lulus','ulang') THEN h.santri_id END) AS santri_aktif")
            ->selectRaw("SUM(CASE WHEN h.status IN ('lulus','ulang') THEN 1 ELSE 0 END) AS total_setor")
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status = 'lulus' AND ht.tahap = 'ujian_akhir' THEN CONCAT(h.santri_id, ':', ht.juz) END) AS lulus_juz")
            ->selectRaw("SUM(CASE WHEN h.status = 'alpha' THEN 1 ELSE 0 END) AS alpha")
            ->selectRaw('COUNT(h.id) AS total_status')
            ->groupBy('k.id', 'k.nama_kelas')
            ->get()
            ->map(function ($row): array {
                $totalSantri = (int) $row->total_santri;
                $coverage = $this->percentage((int) $row->santri_aktif, $totalSantri);
                $alphaRate = $this->percentage((int) $row->alpha, (int) $row->total_status);

                return [
                    'id' => (int) $row->id,
                    'nama' => $row->nama_kelas,
                    'total_santri' => $totalSantri,
                    'santri_aktif' => (int) $row->santri_aktif,
                    'coverage_pct' => $coverage,
                    'total_setor' => (int) $row->total_setor,
                    'avg_setoran_per_santri' => $totalSantri > 0
                        ? round(((int) $row->total_setor) / $totalSantri, 2)
                        : 0,
                    'lulus_juz' => (int) $row->lulus_juz,
                    'alpha_rate_pct' => $alphaRate,
                    'status' => $this->performanceStatus($coverage, $alphaRate),
                ];
            });

        $attention = $rows
            ->sortBy(fn(array $row) =>
                ($this->statusRank($row['status']) * 100000)
                + ($row['coverage_pct'] * 100)
                + $row['avg_setoran_per_santri']
            )
            ->values();

        $best = $rows
            ->sortByDesc(fn(array $row) =>
                ($row['coverage_pct'] * 1000)
                + $row['avg_setoran_per_santri']
            )
            ->first();

        return [
            'best' => $best,
            'rows' => $attention->take(10)->values()->all(),
            'without_activity_count' => $rows
                ->where('total_setor', 0)
                ->count(),
        ];
    }

    private function musyrifPerformance(
        int $semesterId,
        Carbon $start,
        Carbon $end
    ): array {
        $attendanceByMusyrif = DB::table('musyrif_attendances')
            ->whereBetween('attendance_at', [$start, $end])
            ->select('musyrif_id')
            ->selectRaw('COUNT(*) AS total_attendance')
            ->selectRaw("SUM(CASE WHEN status = 'valid' THEN 1 ELSE 0 END) AS valid_attendance")
            ->groupBy('musyrif_id')
            ->get()
            ->keyBy('musyrif_id');

        $rows = DB::table('santri_semester_placements as sp')
            ->join('musyrifs as m', 'm.id', '=', 'sp.musyrif_id')
            ->leftJoin('hafalans as h', function ($join) use ($semesterId, $start, $end): void {
                $join->on('h.santri_id', '=', 'sp.santri_id')
                    ->where('h.semester_id', '=', $semesterId)
                    ->whereBetween('h.tanggal_setoran', [
                        $start->toDateString(),
                        $end->toDateString(),
                    ]);
            })
            ->leftJoin('hafalan_templates as ht', 'ht.id', '=', 'h.hafalan_template_id')
            ->where('sp.semester_id', $semesterId)
            ->whereNotNull('sp.musyrif_id')
            ->select('m.id', 'm.nama')
            ->selectRaw('COUNT(DISTINCT sp.santri_id) AS total_santri')
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status IN ('lulus','ulang') THEN h.santri_id END) AS santri_aktif")
            ->selectRaw("SUM(CASE WHEN h.status IN ('lulus','ulang') THEN 1 ELSE 0 END) AS total_setor")
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status = 'lulus' AND ht.tahap = 'ujian_akhir' THEN CONCAT(h.santri_id, ':', ht.juz) END) AS lulus_juz")
            ->selectRaw("SUM(CASE WHEN h.status = 'alpha' THEN 1 ELSE 0 END) AS alpha")
            ->selectRaw('COUNT(h.id) AS total_status')
            ->groupBy('m.id', 'm.nama')
            ->get()
            ->map(function ($row) use ($attendanceByMusyrif): array {
                $totalSantri = (int) $row->total_santri;
                $coverage = $this->percentage((int) $row->santri_aktif, $totalSantri);
                $alphaRate = $this->percentage((int) $row->alpha, (int) $row->total_status);
                $attendanceRow = $attendanceByMusyrif->get($row->id);
                $attendancePct = $this->percentage(
                    (int) ($attendanceRow->valid_attendance ?? 0),
                    (int) ($attendanceRow->total_attendance ?? 0)
                );

                return [
                    'id' => (int) $row->id,
                    'nama' => $row->nama,
                    'total_santri' => $totalSantri,
                    'coverage_pct' => $coverage,
                    'total_setor' => (int) $row->total_setor,
                    'avg_setoran_per_santri' => $totalSantri > 0
                        ? round(((int) $row->total_setor) / $totalSantri, 2)
                        : 0,
                    'lulus_juz' => (int) $row->lulus_juz,
                    'alpha_rate_pct' => $alphaRate,
                    'attendance_pct' => $attendancePct,
                    'status' => $this->performanceStatus(
                        min($coverage, $attendancePct > 0 ? $attendancePct : $coverage),
                        $alphaRate
                    ),
                ];
            });

        $attention = $rows
            ->sortBy(fn(array $row) =>
                ($this->statusRank($row['status']) * 1000000)
                + ($row['coverage_pct'] * 1000)
                + ($row['attendance_pct'] * 10)
                + $row['avg_setoran_per_santri']
            )
            ->values();

        $best = $rows
            ->sortByDesc(fn(array $row) =>
                ($row['coverage_pct'] * 10000)
                + ($row['attendance_pct'] * 100)
                + $row['avg_setoran_per_santri']
            )
            ->first();

        return [
            'best' => $best,
            'rows' => $attention->take(10)->values()->all(),
        ];
    }

    private function juzProgress(
        int $semesterId,
        Collection $santriIds,
        Carbon $start,
        Carbon $end
    ): array {
        $result = collect(range(1, 30))
            ->mapWithKeys(fn(int $juz) => [
                $juz => [
                    'juz' => $juz,
                    'progress' => 0,
                    'lulus' => 0,
                ],
            ]);

        if ($santriIds->isEmpty()) {
            return $result->values()->all();
        }

        $dailyStages = "'" . implode("','", self::DAILY_STAGES) . "'";

        DB::table('hafalans as h')
            ->join('hafalan_templates as ht', 'ht.id', '=', 'h.hafalan_template_id')
            ->where('h.semester_id', $semesterId)
            ->whereIn('h.santri_id', $santriIds)
            ->whereBetween('h.tanggal_setoran', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->whereBetween('ht.juz', [1, 30])
            ->select('ht.juz')
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status IN ('lulus','ulang') AND ht.tahap IN ({$dailyStages}) THEN h.santri_id END) AS progress_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status = 'lulus' AND ht.tahap = 'ujian_akhir' THEN h.santri_id END) AS lulus_count")
            ->groupBy('ht.juz')
            ->get()
            ->each(function ($row) use ($result): void {
                $juz = (int) $row->juz;

                if ($result->has($juz)) {
                    $result->put($juz, [
                        'juz' => $juz,
                        'progress' => (int) $row->progress_count,
                        'lulus' => (int) $row->lulus_count,
                    ]);
                }
            });

        return $result->values()->all();
    }

    private function trend(
        int $semesterId,
        Collection $santriIds,
        Carbon $start,
        Carbon $end
    ): array {
        if ($santriIds->isEmpty()) {
            return [
                'labels' => [],
                'setoran' => [],
                'lulus_juz' => [],
                'alpha' => [],
            ];
        }

        $days = $start->copy()->startOfDay()
            ->diffInDays($end->copy()->startOfDay()) + 1;
        $bucketSql = $days <= 60
            ? 'DATE(h.tanggal_setoran)'
            : "DATE_FORMAT(h.tanggal_setoran, '%Y-%m')";

        $rows = DB::table('hafalans as h')
            ->leftJoin('hafalan_templates as ht', 'ht.id', '=', 'h.hafalan_template_id')
            ->where('h.semester_id', $semesterId)
            ->whereIn('h.santri_id', $santriIds)
            ->whereBetween('h.tanggal_setoran', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->selectRaw("{$bucketSql} AS bucket")
            ->selectRaw("SUM(CASE WHEN h.status IN ('lulus','ulang') THEN 1 ELSE 0 END) AS total_setor")
            ->selectRaw("COUNT(DISTINCT CASE WHEN h.status = 'lulus' AND ht.tahap = 'ujian_akhir' THEN CONCAT(h.santri_id, ':', ht.juz) END) AS lulus_juz")
            ->selectRaw("SUM(CASE WHEN h.status = 'alpha' THEN 1 ELSE 0 END) AS total_alpha")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return [
            'labels' => $rows->map(function ($row) use ($days): string {
                return $days <= 60
                    ? Carbon::parse($row->bucket)->translatedFormat('d M')
                    : Carbon::createFromFormat('!Y-m', $row->bucket)
                        ->translatedFormat('M Y');
            })->values()->all(),
            'setoran' => $rows->pluck('total_setor')
                ->map(fn($value) => (int) $value)
                ->values()
                ->all(),
            'lulus_juz' => $rows->pluck('lulus_juz')
                ->map(fn($value) => (int) $value)
                ->values()
                ->all(),
            'alpha' => $rows->pluck('total_alpha')
                ->map(fn($value) => (int) $value)
                ->values()
                ->all(),
        ];
    }

    private function inactiveSantriSummary(
        int $semesterId,
        Collection $santriIds,
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $days = max(1, (int) config('quran-executive.risk.inactive_days', 7));
        $referenceEnd = $periodEnd->copy()->min(now()->endOfDay());
        $start = $referenceEnd->copy()->subDays($days - 1)->startOfDay();

        if ($start->lt($periodStart)) {
            $start = $periodStart->copy();
        }

        $activeIds = $santriIds->isEmpty()
            ? collect()
            : DB::table('hafalans')
                ->where('semester_id', $semesterId)
                ->whereIn('santri_id', $santriIds)
                ->whereBetween('tanggal_setoran', [
                    $start->toDateString(),
                    $referenceEnd->toDateString(),
                ])
                ->whereIn('status', ['lulus', 'ulang'])
                ->distinct()
                ->pluck('santri_id');

        return [
            'days' => $days,
            'count' => max(0, $santriIds->count() - $activeIds->count()),
            'start_date' => $start->toDateString(),
            'end_date' => $referenceEnd->toDateString(),
        ];
    }

    private function referenceDayAttendance(
        Collection $musyrifIds,
        Carbon $periodEnd
    ): array {
        $referenceDay = $periodEnd->copy()->min(now()->endOfDay())->startOfDay();

        if ($musyrifIds->isEmpty()) {
            return [
                'date' => $referenceDay->toDateString(),
                'date_label' => $referenceDay->translatedFormat('d M Y'),
                'complete' => 0,
                'incomplete' => 0,
                'morning' => 0,
                'afternoon' => 0,
            ];
        }

        $validRows = DB::table('musyrif_attendances')
            ->whereIn('musyrif_id', $musyrifIds)
            ->whereDate('attendance_at', $referenceDay->toDateString())
            ->where('status', 'valid')
            ->whereIn('type', ['morning', 'afternoon'])
            ->select('musyrif_id', 'type')
            ->distinct()
            ->get();

        $morning = $validRows->where('type', 'morning')
            ->pluck('musyrif_id')
            ->unique();
        $afternoon = $validRows->where('type', 'afternoon')
            ->pluck('musyrif_id')
            ->unique();
        $complete = $morning->intersect($afternoon)->count();

        return [
            'date' => $referenceDay->toDateString(),
            'date_label' => $referenceDay->translatedFormat('d M Y'),
            'complete' => $complete,
            'incomplete' => max(0, $musyrifIds->count() - $complete),
            'morning' => $morning->count(),
            'afternoon' => $afternoon->count(),
        ];
    }

    private function dataIntegrity(
        int $semesterId,
        Collection $placementSantriIds,
        Carbon $start,
        Carbon $end
    ): array {
        $unplaced = DB::table('hafalans')
            ->where('semester_id', $semesterId)
            ->whereBetween('tanggal_setoran', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->when(
                $placementSantriIds->isNotEmpty(),
                fn($query) => $query->whereNotIn('santri_id', $placementSantriIds)
            )
            ->distinct('santri_id')
            ->count('santri_id');

        $legacy = DB::table('hafalans')
            ->whereNull('semester_id')
            ->whereBetween('tanggal_setoran', [
                $start->toDateString(),
                $end->toDateString(),
            ])
            ->count();

        $warnings = [];

        if ($placementSantriIds->isEmpty()) {
            $warnings[] = 'Placement santri semester belum tersedia.';
        }

        if ($unplaced > 0) {
            $warnings[] = "{$unplaced} santri memiliki transaksi tetapi belum mempunyai placement semester.";
        }

        if ($legacy > 0) {
            $warnings[] = "{$legacy} transaksi masih memiliki semester_id NULL dan tidak masuk perhitungan.";
        }

        return [
            'unplaced_santri' => (int) $unplaced,
            'legacy_transactions' => (int) $legacy,
            'warnings' => $warnings,
            'has_warning' => $warnings !== [],
        ];
    }

    private function departmentHealth(
        array $summary,
        array $attendance,
        array $comparison
    ): array {
        $thresholds = config('quran-executive.thresholds', []);
        $coverage = (float) $summary['coverage_pct'];
        $attendancePct = (float) $attendance['valid_pct'];
        $alphaRate = (float) $summary['alpha_risk_rate_pct'];
        $setoranDelta = $comparison['setoran_pct'] ?? null;
        $reasons = [];
        $status = 'good';

        if (
            $coverage < (float) ($thresholds['coverage_attention'] ?? 70)
            || ($attendance['total_records'] > 0
                && $attendancePct < (float) ($thresholds['attendance_attention'] ?? 75))
            || $alphaRate >= (float) ($thresholds['alpha_rate_critical'] ?? 10)
            || ($setoranDelta !== null
                && $setoranDelta <= (float) ($thresholds['setoran_delta_critical'] ?? -15))
        ) {
            $status = 'critical';
        } elseif (
            $coverage < (float) ($thresholds['coverage_good'] ?? 85)
            || ($attendance['total_records'] > 0
                && $attendancePct < (float) ($thresholds['attendance_good'] ?? 90))
            || $alphaRate >= (float) ($thresholds['alpha_rate_attention'] ?? 5)
            || ($setoranDelta !== null
                && $setoranDelta <= (float) ($thresholds['setoran_delta_attention'] ?? -5))
        ) {
            $status = 'attention';
        }

        if ($coverage < (float) ($thresholds['coverage_good'] ?? 85)) {
            $reasons[] = "coverage santri baru {$coverage}%";
        }

        if (
            $attendance['total_records'] > 0
            && $attendancePct < (float) ($thresholds['attendance_good'] ?? 90)
        ) {
            $reasons[] = "validitas absensi musyrif {$attendancePct}%";
        }

        if ($alphaRate >= (float) ($thresholds['alpha_rate_attention'] ?? 5)) {
            $reasons[] = "risiko alpha mencakup {$alphaRate}% santri";
        }

        if ($setoranDelta !== null && $setoranDelta < 0) {
            $reasons[] = 'setoran turun ' . abs($setoranDelta) . '% dari periode pembanding';
        }

        $label = match ($status) {
            'critical' => 'Kritis',
            'attention' => 'Perlu Perhatian',
            default => 'Baik / On Track',
        };

        $summaryText = match ($status) {
            'critical' => 'Terdapat indikator utama yang membutuhkan intervensi pimpinan dan tindak lanjut segera dari Departemen Al-Qur’an.',
            'attention' => 'Kegiatan departemen berjalan, namun beberapa indikator belum berada pada ambang kendali yang diharapkan.',
            default => 'Kegiatan Departemen Al-Qur’an berada pada jalur yang baik dan tidak ditemukan penyimpangan utama pada periode ini.',
        };

        if ($reasons !== []) {
            $summaryText .= ' Perhatian utama: ' . implode(', ', $reasons) . '.';
        }

        return [
            'status' => $status,
            'label' => $label,
            'summary' => $summaryText,
            'reasons' => $reasons,
        ];
    }

    private function attentionItems(
        array $summary,
        array $inactive,
        array $referenceAttendance,
        array $classPerformance,
        array $integrity
    ): array {
        $items = [
            [
                'priority' => $summary['santri_belum_setor'] > 0 ? 1 : 4,
                'tone' => $summary['santri_belum_setor'] > 0 ? 'warning' : 'success',
                'icon' => 'bi-person-dash-fill',
                'title' => 'Santri belum memiliki setoran',
                'value' => $summary['santri_belum_setor'],
                'description' => 'Belum tercatat status lulus atau ulang pada periode terpilih.',
            ],
            [
                'priority' => $summary['santri_risiko_alpha'] > 0 ? 0 : 4,
                'tone' => $summary['santri_risiko_alpha'] > 0 ? 'danger' : 'success',
                'icon' => 'bi-exclamation-octagon-fill',
                'title' => 'Santri berisiko alpha',
                'value' => $summary['santri_risiko_alpha'],
                'description' => 'Memiliki alpha minimal '
                    . (int) config('quran-executive.risk.alpha_minimum', 3)
                    . ' kali pada periode terpilih.',
            ],
            [
                'priority' => $inactive['count'] > 0 ? 1 : 4,
                'tone' => $inactive['count'] > 0 ? 'warning' : 'success',
                'icon' => 'bi-clock-history',
                'title' => 'Tidak aktif dalam ' . $inactive['days'] . ' hari terakhir',
                'value' => $inactive['count'],
                'description' => 'Tidak memiliki setoran lulus atau ulang pada rentang pemantauan terakhir.',
            ],
            [
                'priority' => $classPerformance['without_activity_count'] > 0 ? 1 : 4,
                'tone' => $classPerformance['without_activity_count'] > 0 ? 'warning' : 'success',
                'icon' => 'bi-buildings-fill',
                'title' => 'Kelas tanpa aktivitas setoran',
                'value' => $classPerformance['without_activity_count'],
                'description' => 'Tidak memiliki transaksi setoran lulus atau ulang pada periode terpilih.',
            ],
            [
                'priority' => $referenceAttendance['incomplete'] > 0 ? 1 : 4,
                'tone' => $referenceAttendance['incomplete'] > 0 ? 'info' : 'success',
                'icon' => 'bi-person-check-fill',
                'title' => 'Absensi musyrif belum lengkap',
                'value' => $referenceAttendance['incomplete'],
                'description' => 'Belum memiliki absensi valid pagi dan sore pada '
                    . $referenceAttendance['date_label']
                    . '.',
            ],
        ];

        if ($integrity['has_warning']) {
            $items[] = [
                'priority' => 0,
                'tone' => 'secondary',
                'icon' => 'bi-database-exclamation',
                'title' => 'Integritas data perlu diperiksa',
                'value' => count($integrity['warnings']),
                'description' => implode(' ', $integrity['warnings']),
            ];
        }

        return collect($items)
            ->sortBy('priority')
            ->values()
            ->all();
    }

    private function performanceStatus(float $coverage, float $alphaRate): string
    {
        $thresholds = config('quran-executive.thresholds', []);

        if (
            $coverage < (float) ($thresholds['coverage_attention'] ?? 70)
            || $alphaRate >= (float) ($thresholds['alpha_rate_critical'] ?? 10)
        ) {
            return 'critical';
        }

        if (
            $coverage < (float) ($thresholds['coverage_good'] ?? 85)
            || $alphaRate >= (float) ($thresholds['alpha_rate_attention'] ?? 5)
        ) {
            return 'attention';
        }

        return 'good';
    }

    private function statusRank(string $status): int
    {
        return match ($status) {
            'critical' => 0,
            'attention' => 1,
            default => 2,
        };
    }

    private function semesterProgress(Carbon $start, Carbon $end): float
    {
        if (now()->lte($start)) {
            return 0;
        }

        if (now()->gte($end)) {
            return 100;
        }

        $totalDays = max(1, $start->copy()->startOfDay()
            ->diffInDays($end->copy()->startOfDay()) + 1);
        $elapsedDays = $start->copy()->startOfDay()
            ->diffInDays(now()->startOfDay()) + 1;

        return round(min(100, ($elapsedDays / $totalDays) * 100), 1);
    }

    private function nilaiSql(string $column): string
    {
        return "CASE
            WHEN {$column} = 'mumtaz' THEN 95
            WHEN {$column} = 'jayyid_jiddan' THEN 85
            WHEN {$column} = 'jayyid' THEN 75
            WHEN {$column} = 'mardud' THEN 65
            ELSE NULL
        END";
    }

    private function percentage(int|float $value, int|float $total): float
    {
        return $total > 0
            ? round(($value / $total) * 100, 1)
            : 0;
    }

    private function growthPercentage(int|float $current, int|float $previous): ?float
    {
        if ($previous <= 0) {
            return $current <= 0 ? 0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
