<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hafalan;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\MusyrifAttendance;
use App\Models\Santri;
use App\Models\Semester;
use App\Models\Tahsin;
use App\Models\Tilawah;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const INACTIVE_DAYS = 7;
    private const ALPHA_RISK_THRESHOLD = 3;

    public function index(Request $request)
    {
        $request->validate([
            'range' => ['nullable', 'in:today,7d,30d,semester,custom'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $semesterAktif = Semester::with('tahunAjaran:id,nama')
            ->active()
            ->first()
            ?? Semester::with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')
            ->first();

        [$startDate, $endDate, $rangeKey, $periodLabel] = $this->resolvePeriod(
            $request,
            $semesterAktif
        );

        $today = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $semesterStart = $semesterAktif?->tanggal_mulai
            ? $semesterAktif->tanggal_mulai->copy()->startOfDay()
            : now()->startOfMonth();

        $semesterEnd = $semesterAktif?->tanggal_selesai
            ? $semesterAktif->tanggal_selesai->copy()->endOfDay()
            : now()->endOfMonth();

        $jumlahKelas = Kelas::count();
        $jumlahMusyrif = Musyrif::count();
        $jumlahSantri = Santri::count();

        /*
        |------------------------------------------------------------------
        | OPERASIONAL HARI INI
        |------------------------------------------------------------------
        */
        $todayActivityQuery = DB::query()->fromSub(
            $this->buildActivityUnion($today, $todayEnd, $semesterAktif),
            'activity_today'
        );

        $aktivitasHariIni = (clone $todayActivityQuery)->count();
        $santriAktifHariIni = (clone $todayActivityQuery)
            ->distinct()
            ->count('santri_id');

        $activeSantriIdsToday = (clone $todayActivityQuery)
            ->distinct()
            ->pluck('santri_id');

        /*
         * Perhitungan Hafalan dashboard disamakan dengan Laporan Page:
         * - total_setor: semua transaksi status lulus/ulang;
         * - jumlah_setoran_harian: hanya tahap harian, tahap_1, tahap_2, tahap_3;
         * - jumlah_ujian: DISTINCT Juz yang lulus pada tahap ujian_akhir;
         * - nilai sementara dibatasi maksimal 70, nilai final hanya dari ujian akhir lulus.
         */
        $hafalanHariIni = $this->hafalanReportSummary(
            $semesterAktif,
            $today,
            $todayEnd
        );

        $setoranHariIni = $hafalanHariIni['total_setor'];

        $validAttendanceToday = MusyrifAttendance::query()
            ->whereDate('attendance_at', $today->toDateString())
            ->where('status', 'valid');

        $morningMusyrifIds = (clone $validAttendanceToday)
            ->where('type', 'morning')
            ->distinct()
            ->pluck('musyrif_id');

        $afternoonMusyrifIds = (clone $validAttendanceToday)
            ->where('type', 'afternoon')
            ->distinct()
            ->pluck('musyrif_id');

        $musyrifHadirPagi = $morningMusyrifIds->count();
        $musyrifHadirSore = $afternoonMusyrifIds->count();

        $musyrifBelumPagi = max(0, $jumlahMusyrif - $musyrifHadirPagi);
        $musyrifBelumSore = max(0, $jumlahMusyrif - $musyrifHadirSore);

        $suspectHariIni = MusyrifAttendance::query()
            ->whereDate('attendance_at', $today->toDateString())
            ->where('status', 'suspect')
            ->distinct()
            ->count('musyrif_id');

        $rejectedHariIni = MusyrifAttendance::query()
            ->whereDate('attendance_at', $today->toDateString())
            ->where('status', 'rejected')
            ->distinct()
            ->count('musyrif_id');

        $statusPrioritySub = DB::query()
            ->fromSub(
                $this->buildActivityUnion($today, $todayEnd, $semesterAktif),
                'daily_activity'
            )
            ->select('santri_id')
            ->selectRaw("MAX(
                CASE normalized_status
                    WHEN 'alpha' THEN 4
                    WHEN 'sakit' THEN 3
                    WHEN 'izin' THEN 2
                    WHEN 'hadir' THEN 1
                    ELSE 0
                END
            ) AS status_priority")
            ->groupBy('santri_id');

        $statusHariIni = DB::query()
            ->fromSub($statusPrioritySub, 'daily_status')
            ->selectRaw('SUM(CASE WHEN status_priority = 1 THEN 1 ELSE 0 END) AS hadir')
            ->selectRaw('SUM(CASE WHEN status_priority = 2 THEN 1 ELSE 0 END) AS izin')
            ->selectRaw('SUM(CASE WHEN status_priority = 3 THEN 1 ELSE 0 END) AS sakit')
            ->selectRaw('SUM(CASE WHEN status_priority = 4 THEN 1 ELSE 0 END) AS alpha')
            ->first();

        $statusHariIni = [
            'hadir' => (int) ($statusHariIni->hadir ?? 0),
            'izin' => (int) ($statusHariIni->izin ?? 0),
            'sakit' => (int) ($statusHariIni->sakit ?? 0),
            'alpha' => (int) ($statusHariIni->alpha ?? 0),
        ];

        $statusHariIni['belum_tercatat'] = max(
            0,
            $jumlahSantri - array_sum($statusHariIni)
        );

        /*
        |------------------------------------------------------------------
        | ALERT OPERASIONAL
        |------------------------------------------------------------------
        */
        $inactiveThreshold = now()
            ->subDays(self::INACTIVE_DAYS - 1)
            ->startOfDay();

        $recentActiveIds = DB::query()
            ->fromSub(
                $this->buildActivityUnion($inactiveThreshold, $todayEnd, null),
                'recent_activity'
            )
            ->distinct()
            ->pluck('santri_id');

        $inactiveBase = Santri::query()
            ->where(function ($query) use ($inactiveThreshold) {
                $query->whereNull('created_at')
                    ->orWhereDate('created_at', '<=', $inactiveThreshold->toDateString());
            });

        if ($recentActiveIds->isNotEmpty()) {
            $inactiveBase->whereNotIn('id', $recentActiveIds);
        }

        $santriTidakAktifCount = (clone $inactiveBase)->count();

        $lastActivitySub = DB::query()
            ->fromSub(
                $this->buildActivityUnion(null, $todayEnd, null),
                'all_activity'
            )
            ->select('santri_id')
            ->selectRaw('MAX(activity_date) AS last_activity')
            ->groupBy('santri_id');

        $santriTidakAktifList = Santri::query()
            ->with(['kelas:id,nama_kelas', 'musyrif:id,nama'])
            ->leftJoinSub($lastActivitySub, 'last_activity', function ($join) {
                $join->on('last_activity.santri_id', '=', 'santris.id');
            })
            ->select('santris.*', 'last_activity.last_activity')
            ->where(function ($query) use ($inactiveThreshold) {
                $query->whereNull('santris.created_at')
                    ->orWhereDate('santris.created_at', '<=', $inactiveThreshold->toDateString());
            })
            ->where(function ($query) use ($inactiveThreshold) {
                $query->whereNull('last_activity.last_activity')
                    ->orWhereDate(
                        'last_activity.last_activity',
                        '<',
                        $inactiveThreshold->toDateString()
                    );
            })
            ->orderByRaw('last_activity.last_activity IS NULL DESC')
            ->orderBy('last_activity.last_activity')
            ->limit(6)
            ->get()
            ->map(function ($santri) {
                $lastActivity = $santri->last_activity
                    ? Carbon::parse($santri->last_activity)
                    : null;

                return [
                    'id' => $santri->id,
                    'nama' => $santri->nama,
                    'kelas' => $santri->kelas?->nama_kelas ?? '-',
                    'musyrif' => $santri->musyrif?->nama ?? '-',
                    'last_activity' => $lastActivity?->translatedFormat('d M Y'),
                    'inactive_days' => $lastActivity
                        ? $lastActivity->startOfDay()->diffInDays(today())
                        : null,
                ];
            });

        $semesterActivityUnionForRisk = $this->buildActivityUnion(
            $semesterStart,
            $semesterEnd,
            $semesterAktif
        );

        $alphaRiskSub = DB::query()
            ->fromSub($semesterActivityUnionForRisk, 'semester_activity')
            ->where('normalized_status', 'alpha')
            ->select('santri_id')
            ->selectRaw('COUNT(*) AS alpha_count')
            ->groupBy('santri_id')
            ->havingRaw('COUNT(*) >= ?', [self::ALPHA_RISK_THRESHOLD]);

        $santriRisikoAlphaCount = DB::query()
            ->fromSub(clone $alphaRiskSub, 'alpha_risk_count')
            ->count();

        $santriRisikoAlphaList = Santri::query()
            ->with(['kelas:id,nama_kelas', 'musyrif:id,nama'])
            ->joinSub(clone $alphaRiskSub, 'alpha_risk', function ($join) {
                $join->on('alpha_risk.santri_id', '=', 'santris.id');
            })
            ->select('santris.*', 'alpha_risk.alpha_count')
            ->orderByDesc('alpha_risk.alpha_count')
            ->limit(6)
            ->get()
            ->map(fn($santri) => [
                'id' => $santri->id,
                'nama' => $santri->nama,
                'kelas' => $santri->kelas?->nama_kelas ?? '-',
                'musyrif' => $santri->musyrif?->nama ?? '-',
                'alpha_count' => (int) $santri->alpha_count,
            ]);

        $activeKelasIdsToday = Santri::query()
            ->whereIn('id', $activeSantriIdsToday)
            ->whereNotNull('kelas_id')
            ->distinct()
            ->pluck('kelas_id');

        $kelasTanpaAktivitasQuery = Kelas::query();

        if ($activeKelasIdsToday->isNotEmpty()) {
            $kelasTanpaAktivitasQuery->whereNotIn('id', $activeKelasIdsToday);
        }

        $kelasTanpaAktivitasCount = (clone $kelasTanpaAktivitasQuery)->count();
        $kelasTanpaAktivitasList = (clone $kelasTanpaAktivitasQuery)
            ->orderBy('nama_kelas')
            ->limit(6)
            ->pluck('nama_kelas');

        /*
        |------------------------------------------------------------------
        | RINGKASAN SEMESTER AKTIF
        |------------------------------------------------------------------
        */
        $semesterActivityQuery = DB::query()->fromSub(
            $this->buildActivityUnion(
                $semesterStart,
                $semesterEnd,
                $semesterAktif
            ),
            'semester_activity_summary'
        );

        $aktivitasSemester = (clone $semesterActivityQuery)->count();
        $santriAktifSemester = (clone $semesterActivityQuery)
            ->distinct()
            ->count('santri_id');

        $hafalanSemester = $this->hafalanReportSummary(
            $semesterAktif,
            $semesterStart,
            $semesterEnd
        );

        $setoranSemester = $hafalanSemester['total_setor'];

        $coverageSemester = $jumlahSantri > 0
            ? round(($santriAktifSemester / $jumlahSantri) * 100, 1)
            : 0;

        $semesterProgress = $this->calculateSemesterProgress(
            $semesterStart,
            $semesterEnd
        );

        /*
        |------------------------------------------------------------------
        | CHART PERIODE TERPILIH
        |------------------------------------------------------------------
        */
        $periodActivityUnion = $this->buildActivityUnion(
            $startDate,
            $endDate,
            $semesterAktif
        );

        $rangeDays = $startDate->copy()->startOfDay()
            ->diffInDays($endDate->copy()->startOfDay()) + 1;

        $bucketExpression = $rangeDays <= 45
            ? 'DATE(activity_date)'
            : "DATE_FORMAT(activity_date, '%Y-%m')";

        $trendRows = DB::query()
            ->fromSub($periodActivityUnion, 'period_activity')
            ->selectRaw("{$bucketExpression} AS bucket")
            ->selectRaw("SUM(CASE WHEN source = 'hafalan' THEN 1 ELSE 0 END) AS hafalan")
            ->selectRaw("SUM(CASE WHEN source = 'tahsin' THEN 1 ELSE 0 END) AS tahsin")
            ->selectRaw("SUM(CASE WHEN source = 'tilawah' THEN 1 ELSE 0 END) AS tilawah")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        $trendChart = [
            'labels' => $trendRows->map(function ($row) use ($rangeDays) {
                $date = $rangeDays <= 45
                    ? Carbon::parse($row->bucket)
                    : Carbon::createFromFormat('!Y-m', $row->bucket);

                return $date->translatedFormat(
                    $rangeDays <= 45 ? 'd M' : 'M Y'
                );
            })->values(),
            'hafalan' => $trendRows->pluck('hafalan')
                ->map(fn($value) => (int) $value)
                ->values(),
            'tahsin' => $trendRows->pluck('tahsin')
                ->map(fn($value) => (int) $value)
                ->values(),
            'tilawah' => $trendRows->pluck('tilawah')
                ->map(fn($value) => (int) $value)
                ->values(),
        ];

        $activityByClassRows = DB::query()
            ->fromSub(
                $this->buildActivityUnion($startDate, $endDate, $semesterAktif),
                'class_activity'
            )
            ->join('santris as s', 's.id', '=', 'class_activity.santri_id')
            ->join('kelas as k', 'k.id', '=', 's.kelas_id')
            ->select('k.id', 'k.nama_kelas')
            ->selectRaw("SUM(CASE WHEN class_activity.source = 'hafalan' THEN 1 ELSE 0 END) AS hafalan")
            ->selectRaw("SUM(CASE WHEN class_activity.source = 'tahsin' THEN 1 ELSE 0 END) AS tahsin")
            ->selectRaw("SUM(CASE WHEN class_activity.source = 'tilawah' THEN 1 ELSE 0 END) AS tilawah")
            ->selectRaw('COUNT(*) AS total_activity')
            ->groupBy('k.id', 'k.nama_kelas')
            ->orderByDesc('total_activity')
            ->limit(12)
            ->get();

        $activityByClassChart = [
            'labels' => $activityByClassRows->pluck('nama_kelas')->values(),
            'hafalan' => $activityByClassRows->pluck('hafalan')
                ->map(fn($value) => (int) $value)
                ->values(),
            'tahsin' => $activityByClassRows->pluck('tahsin')
                ->map(fn($value) => (int) $value)
                ->values(),
            'tilawah' => $activityByClassRows->pluck('tilawah')
                ->map(fn($value) => (int) $value)
                ->values(),
        ];

        $recentActivities = $this->buildRecentActivities();

        $attentionSummary = [
            [
                'tone' => 'danger',
                'icon' => 'bi-person-x-fill',
                'label' => 'Santri tidak aktif 7 hari',
                'value' => $santriTidakAktifCount,
                'description' => 'Belum memiliki aktivitas Hafalan, Tahsin, atau Tilawah.',
            ],
            [
                'tone' => 'warning',
                'icon' => 'bi-exclamation-triangle-fill',
                'label' => 'Risiko alpha semester',
                'value' => $santriRisikoAlphaCount,
                'description' => 'Akumulasi alpha minimal ' . self::ALPHA_RISK_THRESHOLD . ' kali.',
            ],
            [
                'tone' => 'primary',
                'icon' => 'bi-buildings-fill',
                'label' => 'Kelas tanpa aktivitas hari ini',
                'value' => $kelasTanpaAktivitasCount,
                'description' => 'Belum ada aktivitas Al-Qur’an yang tercatat.',
            ],
            [
                'tone' => 'info',
                'icon' => 'bi-person-check-fill',
                'label' => 'Belum absen pagi / sore',
                'value' => $musyrifBelumPagi . ' / ' . $musyrifBelumSore,
                'description' => 'Seluruh musyrif diwajibkan absen pada kedua sesi.',
            ],
            [
                'tone' => 'secondary',
                'icon' => 'bi-geo-alt-fill',
                'label' => 'Absensi suspect / rejected',
                'value' => $suspectHariIni . ' / ' . $rejectedHariIni,
                'description' => 'Perlu verifikasi lokasi, perangkat, atau bukti foto.',
            ],
        ];

        return view('admin.dashboard', compact(
            'semesterAktif',
            'semesterStart',
            'semesterEnd',
            'semesterProgress',
            'startDate',
            'endDate',
            'rangeKey',
            'periodLabel',
            'jumlahKelas',
            'jumlahMusyrif',
            'jumlahSantri',
            'aktivitasHariIni',
            'santriAktifHariIni',
            'setoranHariIni',
            'hafalanHariIni',
            'musyrifHadirPagi',
            'musyrifHadirSore',
            'musyrifBelumPagi',
            'musyrifBelumSore',
            'morningMusyrifIds',
            'afternoonMusyrifIds',
            'suspectHariIni',
            'rejectedHariIni',
            'statusHariIni',
            'santriTidakAktifCount',
            'santriTidakAktifList',
            'santriRisikoAlphaCount',
            'santriRisikoAlphaList',
            'kelasTanpaAktivitasCount',
            'kelasTanpaAktivitasList',
            'aktivitasSemester',
            'santriAktifSemester',
            'setoranSemester',
            'hafalanSemester',
            'coverageSemester',
            'trendChart',
            'activityByClassChart',
            'recentActivities',
            'attentionSummary'
        ));
    }

    private function resolvePeriod(Request $request, ?Semester $semester): array
    {
        $range = $request->input('range', 'today');

        if (
            $range === 'custom'
            && $request->filled('start_date')
            && $request->filled('end_date')
        ) {
            $start = Carbon::parse($request->input('start_date'))->startOfDay();
            $end = Carbon::parse($request->input('end_date'))->endOfDay();

            return [
                $start,
                $end,
                'custom',
                $start->translatedFormat('d M Y') . ' — ' . $end->translatedFormat('d M Y'),
            ];
        }

        return match ($range) {
            '7d' => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                '7d',
                '7 hari terakhir',
            ],
            '30d' => [
                now()->subDays(29)->startOfDay(),
                now()->endOfDay(),
                '30d',
                '30 hari terakhir',
            ],
            'semester' => [
                $semester?->tanggal_mulai?->copy()->startOfDay()
                    ?? now()->startOfMonth(),
                $semester?->tanggal_selesai?->copy()->endOfDay()
                    ?? now()->endOfMonth(),
                'semester',
                $semester
                    ? 'Semester ' . ucfirst($semester->nama)
                    : 'Bulan berjalan',
            ],
            default => [
                now()->startOfDay(),
                now()->endOfDay(),
                'today',
                'Hari ini',
            ],
        };
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

    private function hafalanReportSummary(
        ?Semester $semester,
        Carbon $start,
        Carbon $end
    ): array {
        $nilaiSql = $this->nilaiScoreSql('hafalans.nilai_label');

        $summary = Hafalan::query()
            ->leftJoin(
                'hafalan_templates as ht',
                'ht.id',
                '=',
                'hafalans.hafalan_template_id'
            )
            ->when(
                $semester,
                fn($query) =>
                $query->where(
                    'hafalans.semester_id',
                    $semester->id
                )
            )
            ->whereBetween(
                'hafalans.tanggal_setoran',
                [
                    $start->toDateString(),
                    $end->toDateString(),
                ]
            )
            ->selectRaw(
                "SUM(CASE WHEN hafalans.status IN ('lulus', 'ulang') THEN 1 ELSE 0 END) AS total_setor"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN hafalans.status IN ('lulus', 'ulang')
                        AND ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                    THEN 1 ELSE 0
                END) AS jumlah_setoran_harian"
            )
            ->selectRaw(
                "COUNT(DISTINCT CASE
                    WHEN hafalans.status = 'lulus'
                        AND ht.tahap = 'ujian_akhir'
                    THEN ht.juz ELSE NULL
                END) AS jumlah_ujian"
            )
            ->selectRaw(
                "SUM(CASE
                    WHEN hafalans.status IN ('lulus', 'ulang')
                        AND ht.tahap = 'ujian_akhir'
                    THEN 1 ELSE 0
                END) AS total_setoran_ujian"
            )
            ->selectRaw(
                "SUM(CASE WHEN hafalans.status = 'hadir_tidak_setor' THEN 1 ELSE 0 END) AS hadir_tidak_setor"
            )
            ->selectRaw(
                "SUM(CASE WHEN hafalans.status = 'sakit' THEN 1 ELSE 0 END) AS sakit"
            )
            ->selectRaw(
                "SUM(CASE WHEN hafalans.status = 'izin' THEN 1 ELSE 0 END) AS izin"
            )
            ->selectRaw(
                "SUM(CASE WHEN hafalans.status = 'alpha' THEN 1 ELSE 0 END) AS alpha"
            )
            ->selectRaw(
                "ROUND(AVG(CASE
                    WHEN hafalans.status IN ('lulus', 'ulang') THEN {$nilaiSql}
                    ELSE NULL
                END), 2) AS rata_nilai"
            )
            ->selectRaw(
                "ROUND(LEAST(70, AVG(CASE
                    WHEN hafalans.status IN ('lulus', 'ulang')
                        AND ht.tahap IN ('harian', 'tahap_1', 'tahap_2', 'tahap_3')
                    THEN {$nilaiSql}
                    ELSE NULL
                END)), 2) AS rata_nilai_sementara"
            )
            ->selectRaw(
                "ROUND(AVG(CASE
                    WHEN hafalans.status = 'lulus'
                        AND ht.tahap = 'ujian_akhir'
                    THEN {$nilaiSql}
                    ELSE NULL
                END), 2) AS rata_nilai_ujian"
            )
            ->first();

        return [
            'total_setor' => (int) ($summary->total_setor ?? 0),
            'jumlah_setoran_harian' => (int) ($summary->jumlah_setoran_harian ?? 0),
            'jumlah_ujian' => (int) ($summary->jumlah_ujian ?? 0),
            'total_setoran_ujian' => (int) ($summary->total_setoran_ujian ?? 0),
            'hadir_tidak_setor' => (int) ($summary->hadir_tidak_setor ?? 0),
            'sakit' => (int) ($summary->sakit ?? 0),
            'izin' => (int) ($summary->izin ?? 0),
            'alpha' => (int) ($summary->alpha ?? 0),
            'rata_nilai' => $summary->rata_nilai !== null ? (float) $summary->rata_nilai : null,
            'rata_nilai_sementara' => $summary->rata_nilai_sementara !== null
                ? (float) $summary->rata_nilai_sementara
                : null,
            'rata_nilai_ujian' => $summary->rata_nilai_ujian !== null
                ? (float) $summary->rata_nilai_ujian
                : null,
        ];
    }

    private function applySemesterFilter(
        $query,
        ?Semester $semester,
        string $dateColumn,
        string $semesterColumn = 'semester_id'
    ) {
        if (!$semester) {
            return $query;
        }

        $semesterId = $semester->id;
        $semesterStart = $semester->tanggal_mulai?->toDateString();
        $semesterEnd = $semester->tanggal_selesai?->toDateString();

        return $query->where(function ($scope) use (
            $semesterId,
            $semesterStart,
            $semesterEnd,
            $semesterColumn,
            $dateColumn
        ) {
            $scope->where($semesterColumn, $semesterId);

            if ($semesterStart && $semesterEnd) {
                $scope->orWhere(function ($legacy) use (
                    $semesterStart,
                    $semesterEnd,
                    $semesterColumn,
                    $dateColumn
                ) {
                    $legacy->whereNull($semesterColumn)
                        ->whereBetween($dateColumn, [$semesterStart, $semesterEnd]);
                });
            }
        });
    }

    private function buildActivityUnion(
        ?Carbon $start,
        ?Carbon $end,
        ?Semester $semester
    ): QueryBuilder {
        $hafalan = DB::table('hafalans')
            ->select(
                'santri_id',
                DB::raw("'hafalan' AS source"),
                DB::raw('tanggal_setoran AS activity_date'),
                DB::raw("CASE
                    WHEN status IN ('lulus', 'ulang', 'hadir_tidak_setor') THEN 'hadir'
                    ELSE status
                END AS normalized_status")
            );

        $tahsin = DB::table('tahsins')
            ->select(
                'santri_id',
                DB::raw("'tahsin' AS source"),
                DB::raw('tanggal AS activity_date'),
                DB::raw('status AS normalized_status')
            );

        $tilawah = DB::table('tilawahs')
            ->select(
                'santri_id',
                DB::raw("'tilawah' AS source"),
                DB::raw('tanggal AS activity_date'),
                DB::raw('status AS normalized_status')
            );

        if ($start && $end) {
            $hafalan->whereBetween('tanggal_setoran', [
                $start->toDateString(),
                $end->toDateString(),
            ]);

            $tahsin->whereBetween('tanggal', [
                $start->toDateString(),
                $end->toDateString(),
            ]);

            $tilawah->whereBetween('tanggal', [
                $start->toDateString(),
                $end->toDateString(),
            ]);
        } elseif ($end) {
            $hafalan->whereDate('tanggal_setoran', '<=', $end->toDateString());
            $tahsin->whereDate('tanggal', '<=', $end->toDateString());
            $tilawah->whereDate('tanggal', '<=', $end->toDateString());
        }

        $this->applySemesterFilter($hafalan, $semester, 'tanggal_setoran');
        $this->applySemesterFilter($tahsin, $semester, 'tanggal');
        $this->applySemesterFilter($tilawah, $semester, 'tanggal');

        return $hafalan
            ->unionAll($tahsin)
            ->unionAll($tilawah);
    }

    private function calculateSemesterProgress(Carbon $start, Carbon $end): array
    {
        $totalDays = max(1, $start->copy()->startOfDay()->diffInDays(
            $end->copy()->startOfDay()
        ) + 1);

        if (now()->lt($start)) {
            $elapsedDays = 0;
        } elseif (now()->gt($end)) {
            $elapsedDays = $totalDays;
        } else {
            $elapsedDays = $start->copy()->startOfDay()->diffInDays(today()) + 1;
        }

        $remainingDays = max(0, $totalDays - $elapsedDays);
        $percentage = round(($elapsedDays / $totalDays) * 100, 1);

        return [
            'total_days' => $totalDays,
            'elapsed_days' => $elapsedDays,
            'remaining_days' => $remainingDays,
            'percentage' => min(100, max(0, $percentage)),
        ];
    }

    private function buildRecentActivities(): Collection
    {
        $hafalanItems = Hafalan::query()
            ->with([
                'santri:id,nama,kelas_id',
                'santri.kelas:id,nama_kelas',
                'template:id,juz,tahap,urutan,label',
            ])
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(function ($item) {
                $statusLabel = match ($item->status) {
                    'lulus' => 'Lulus',
                    'ulang' => 'Perlu mengulang',
                    'hadir_tidak_setor' => 'Hadir tanpa setoran',
                    'izin' => 'Izin',
                    'sakit' => 'Sakit',
                    'alpha' => 'Alpha',
                    default => ucfirst((string) $item->status),
                };

                $material = $item->template?->display_label
                    ?? $item->template?->label
                    ?? 'materi hafalan';

                return $this->activityItem(
                    'hafalan',
                    'bi-journal-check',
                    in_array($item->status, ['lulus', 'ulang'], true)
                        ? 'success'
                        : ($item->status === 'alpha' ? 'danger' : 'warning'),
                    ($item->santri?->nama ?? 'Santri') . ' — Hafalan',
                    $statusLabel . ' · ' . $material,
                    $item->created_at ?? $item->tanggal_setoran,
                    $item->santri?->kelas?->nama_kelas
                );
            });

        $tahsinItems = Tahsin::query()
            ->with([
                'santri:id,nama,kelas_id',
                'santri.kelas:id,nama_kelas',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return $this->activityItem(
                    'tahsin',
                    'bi-book-half',
                    $item->status === 'hadir'
                        ? 'primary'
                        : ($item->status === 'alpha' ? 'danger' : 'warning'),
                    ($item->santri?->nama ?? 'Santri') . ' — Tahsin',
                    ucfirst($item->status) . ' · ' . $item->buku_label
                        . ($item->halaman ? ' halaman ' . $item->halaman : ''),
                    $item->created_at ?? $item->tanggal,
                    $item->santri?->kelas?->nama_kelas
                );
            });

        $tilawahItems = Tilawah::query()
            ->with([
                'santri:id,nama,kelas_id',
                'santri.kelas:id,nama_kelas',
                'template:id,juz,tahap,urutan,label',
            ])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $material = $item->template?->display_label
                    ?? $item->template?->label
                    ?? 'materi tilawah';

                return $this->activityItem(
                    'tilawah',
                    'bi-book',
                    $item->status === 'hadir'
                        ? 'info'
                        : ($item->status === 'alpha' ? 'danger' : 'warning'),
                    ($item->santri?->nama ?? 'Santri') . ' — Tilawah',
                    ucfirst($item->status) . ' · ' . $material,
                    $item->created_at ?? $item->tanggal,
                    $item->santri?->kelas?->nama_kelas
                );
            });

        $attendanceItems = MusyrifAttendance::query()
            ->with('musyrif:id,nama')
            ->latest('attendance_at')
            ->limit(6)
            ->get()
            ->map(function ($item) {
                $session = $item->type === 'morning' ? 'Pagi' : 'Sore';

                return $this->activityItem(
                    'attendance',
                    'bi-geo-alt-fill',
                    match ($item->status) {
                        'valid' => 'success',
                        'suspect' => 'warning',
                        default => 'danger',
                    },
                    ($item->musyrif?->nama ?? 'Musyrif') . ' — Absensi ' . $session,
                    ucfirst($item->status)
                        . ($item->address_text ? ' · ' . $item->address_text : ''),
                    $item->attendance_at,
                    null
                );
            });

        $systemItems = collect();

        if (Schema::hasTable('activity_logs')) {
            $systemItems = ActivityLog::query()
                ->latest('created_at')
                ->limit(4)
                ->get()
                ->map(fn($item) => $this->activityItem(
                    'system',
                    'bi-activity',
                    'secondary',
                    'Aktivitas Sistem',
                    $item->description ?: 'Perubahan data tercatat.',
                    $item->created_at,
                    $item->log_name
                ));
        }

        return collect()
            ->concat($hafalanItems)
            ->concat($tahsinItems)
            ->concat($tilawahItems)
            ->concat($attendanceItems)
            ->concat($systemItems)
            ->sortByDesc(fn($item) => $item['timestamp']?->timestamp ?? 0)
            ->take(12)
            ->values()
            ->map(function ($item) {
                $timestamp = $item['timestamp'];

                return array_merge($item, [
                    'time_ago' => $timestamp?->diffForHumans() ?? '-',
                    'time_label' => $timestamp?->translatedFormat('d M Y, H:i') ?? '-',
                ]);
            });
    }

    private function activityItem(
        string $type,
        string $icon,
        string $tone,
        string $title,
        string $description,
        $timestamp,
        ?string $meta
    ): array {
        return [
            'type' => $type,
            'icon' => $icon,
            'tone' => $tone,
            'title' => $title,
            'description' => $description,
            'timestamp' => $timestamp ? Carbon::parse($timestamp) : null,
            'meta' => $meta,
        ];
    }
}
