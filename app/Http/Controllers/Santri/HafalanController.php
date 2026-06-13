<?php

namespace App\Http\Controllers\Santri;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\Tahsin;
use App\Models\Tilawah;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class HafalanController extends Controller
{
    /**
     * Dashboard progres santri.
     *
     * Seluruh KPI, progres, chart, dan timeline memakai santri_id yang sama.
     * Data pada halaman ini bersifat seluruh riwayat, sama seperti endpoint
     * DataTables, sehingga tidak terjadi perbedaan angka antara KPI dan tabel.
     */
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            abort(401, 'Sesi pengguna tidak ditemukan. Silakan login kembali.');
        }

        $santri = $user->santri()
            ->with(['kelas', 'musyrif'])
            ->first();

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        // ================================================================
        // 1. RINGKASAN HAFALAN
        // ================================================================
        $hafalanBase = Hafalan::query()
            ->where('santri_id', $santri->id);

        $totalSetor = (clone $hafalanBase)
            ->whereIn('status', ['lulus', 'ulang'])
            ->count();

        $totalHadirTidakSetor = (clone $hafalanBase)
            ->where('status', 'hadir_tidak_setor')
            ->count();

        $totalSakit = (clone $hafalanBase)
            ->where('status', 'sakit')
            ->count();

        $totalIzin = (clone $hafalanBase)
            ->where('status', 'izin')
            ->count();

        $totalAlpha = (clone $hafalanBase)
            ->where('status', 'alpha')
            ->count();

        // Alias untuk kompatibilitas PDF/view lama.
        $totalHTS = $totalHadirTidakSetor;

        $avgNilai = (clone $hafalanBase)
            ->whereIn('status', ['lulus', 'ulang'])
            ->whereNotNull('nilai_label')
            ->selectRaw("AVG(
                CASE nilai_label
                    WHEN 'mumtaz' THEN 95
                    WHEN 'jayyid_jiddan' THEN 85
                    WHEN 'jayyid' THEN 75
                    WHEN 'mardud' THEN 65
                    ELSE NULL
                END
            ) AS rata_nilai")
            ->value('rata_nilai');

        $avgNilai = round((float) ($avgNilai ?? 0), 1);

        // Progress Hafalan: tahap LULUS tertinggi pada setiap Juz.
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

        $tahapPerJuz = DB::table('hafalans as h')
            ->join('hafalan_templates as ht', 'ht.id', '=', 'h.hafalan_template_id')
            ->where('h.santri_id', $santri->id)
            ->where('h.status', 'lulus')
            ->whereBetween('ht.juz', [1, 30])
            ->select('ht.juz', 'ht.tahap')
            ->get()
            ->groupBy(fn($row) => (int) $row->juz)
            ->map(function ($rows) use ($tahapRank) {
                $highest = $rows
                    ->sortByDesc(function ($row) use ($tahapRank) {
                        $tahap = strtolower(trim((string) ($row->tahap ?? '')));
                        return $tahapRank[$tahap] ?? 0;
                    })
                    ->first();

                $tahap = strtolower(trim((string) ($highest->tahap ?? '')));

                return $tahap !== '' ? $tahap : null;
            });

        $progressPerJuz = collect(range(1, 30))
            ->map(function (int $juz) use ($tahapPerJuz, $tahapWeight) {
                $tahap = $tahapPerJuz->get($juz);
                $pct = $tahap ? (int) ($tahapWeight[$tahap] ?? 0) : 0;

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
            })
            ->values();

        $overallPct = round((float) ($progressPerJuz->avg('pct') ?? 0));
        $juzSelesai = $progressPerJuz->where('pct', 100)->count();

        // ================================================================
        // 2. RINGKASAN TAHSIN
        // ================================================================
        $tahsinBase = Tahsin::query()
            ->where('santri_id', $santri->id);

        $tahsinHadir = (clone $tahsinBase)
            ->where('status', 'hadir')
            ->count();

        $tahsinIzin = (clone $tahsinBase)
            ->where('status', 'izin')
            ->count();

        $tahsinSakit = (clone $tahsinBase)
            ->where('status', 'sakit')
            ->count();

        $tahsinAlpha = (clone $tahsinBase)
            ->where('status', 'alpha')
            ->count();

        $lastTahsin = (clone $tahsinBase)
            ->where('status', 'hadir')
            ->whereNotNull('buku')
            ->whereNotNull('halaman')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        $bukuMap = [
            'ummi_1' => ['label' => 'Ummi Jilid 1', 'max' => 40],
            'ummi_2' => ['label' => 'Ummi Jilid 2', 'max' => 40],
            'ummi_3' => ['label' => 'Ummi Jilid 3', 'max' => 40],
            'gharib_1' => ['label' => 'Gharib 1', 'max' => 28],
            'gharib_2' => ['label' => 'Gharib 2', 'max' => 28],
            'tajwid' => ['label' => 'Tajwid', 'max' => 50],
        ];

        $maxPages = (clone $tahsinBase)
            ->where('status', 'hadir')
            ->whereNotNull('buku')
            ->whereNotNull('halaman')
            ->selectRaw('buku AS buku_key, MAX(halaman) AS max_halaman')
            ->groupBy('buku')
            ->pluck('max_halaman', 'buku_key');

        $progressPerBuku = collect($bukuMap)
            ->map(function (array $config, string $key) use ($maxPages) {
                $max = (int) $config['max'];
                $current = min($max, (int) ($maxPages[$key] ?? 0));
                $pct = $max > 0 ? min(100, round(($current / $max) * 100)) : 0;

                return [
                    'key' => $key,
                    'label' => $config['label'],
                    'max' => $max,
                    'current' => $current,
                    'pct' => $pct,
                    'status' => $pct >= 100 ? 'Selesai' : ($pct > 0 ? 'Berjalan' : 'Belum'),
                    'color' => $pct >= 100 ? 'success' : ($pct > 0 ? 'primary' : 'secondary'),
                ];
            })
            ->values();

        $overallTahsinPct = round((float) ($progressPerBuku->avg('pct') ?? 0));

        // ================================================================
        // 3. RINGKASAN TILAWAH
        // ================================================================
        $tilawahBase = Tilawah::query()
            ->where('santri_id', $santri->id);

        $tilawahHadir = (clone $tilawahBase)
            ->where('status', 'hadir')
            ->count();

        $tilawahIzin = (clone $tilawahBase)
            ->where('status', 'izin')
            ->count();

        $tilawahSakit = (clone $tilawahBase)
            ->where('status', 'sakit')
            ->count();

        $tilawahAlpha = (clone $tilawahBase)
            ->where('status', 'alpha')
            ->count();

        $lastTilawah = Tilawah::with('template')
            ->where('santri_id', $santri->id)
            ->where('status', 'hadir')
            ->whereNotNull('hafalan_template_id')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        $maxJuzTilawah = (int) (DB::table('tilawahs as t')
            ->join('hafalan_templates as ht', 'ht.id', '=', 't.hafalan_template_id')
            ->where('t.santri_id', $santri->id)
            ->where('t.status', 'hadir')
            ->whereBetween('ht.juz', [1, 30])
            ->max('ht.juz') ?? 0);

        $tilawahPct = min(100, round(($maxJuzTilawah / 30) * 100, 1));

        return view('santri.hafalan.index', [
            'santri' => $santri,

            // Hafalan
            'totalSetor' => (int) $totalSetor,
            'totalHadirTidakSetor' => (int) $totalHadirTidakSetor,
            'totalHTS' => (int) $totalHTS,
            'totalSakit' => (int) $totalSakit,
            'totalIzin' => (int) $totalIzin,
            'totalAlpha' => (int) $totalAlpha,
            'avgNilai' => (float) $avgNilai,

            'progressPerJuz' => $progressPerJuz,
            'overallPct' => (float) $overallPct,
            'juzSelesai' => (int) $juzSelesai,

            // Tahsin
            'tahsinHadir' => (int) $tahsinHadir,
            'tahsinIzin' => (int) $tahsinIzin,
            'tahsinSakit' => (int) $tahsinSakit,
            'tahsinAlpha' => (int) $tahsinAlpha,
            'lastTahsin' => $lastTahsin,
            'progressPerBuku' => $progressPerBuku,
            'overallTahsinPct' => (float) $overallTahsinPct,

            // Tilawah
            'tilawahHadir' => (int) $tilawahHadir,
            'tilawahIzin' => (int) $tilawahIzin,
            'tilawahSakit' => (int) $tilawahSakit,
            'tilawahAlpha' => (int) $tilawahAlpha,
            'lastTilawah' => $lastTilawah,
            'maxJuzTilawah' => (int) $maxJuzTilawah,
            'tilawahPct' => (float) $tilawahPct,
        ]);
    }

    /**
     * Timeline Hafalan untuk Yajra DataTables.
     */
    public function timeline(Request $request)
    {
        $this->ensureAjax($request);
        $santri = $this->authenticatedSantri($request);

        $query = Hafalan::query()
            ->leftJoin('hafalan_templates as ht', 'ht.id', '=', 'hafalans.hafalan_template_id')
            ->where('hafalans.santri_id', $santri->id)
            ->select([
                'hafalans.id',
                'hafalans.tanggal_setoran',
                'hafalans.status',
                'hafalans.nilai_label',
                'hafalans.catatan',
                'hafalans.hafalan_template_id',
                'ht.juz as template_juz',
                'ht.label as template_label',
            ]);

        if ($request->filled('start_date')) {
            $query->whereDate('hafalans.tanggal_setoran', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('hafalans.tanggal_setoran', '<=', $request->input('end_date'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->whereRaw(
                    "DATE_FORMAT(hafalans.tanggal_setoran, '%d-%m-%Y') LIKE ?",
                    ["%{$keyword}%"]
                );
            })
            ->filterColumn('juz', function ($query, $keyword) {
                $query->where('ht.juz', 'like', "%{$keyword}%");
            })
            ->filterColumn('surah_ayat', function ($query, $keyword) {
                $query->where('ht.label', 'like', "%{$keyword}%");
            })
            ->addColumn('tanggal', fn($row) => $this->formatDate($row->tanggal_setoran))
            ->addColumn('juz', fn($row) => $row->template_juz ?? '-')
            ->addColumn('surah_ayat', fn($row) => $row->template_label ?? '-')
            ->addColumn('nilai', fn($row) => $this->nilaiArab($row->nilai_label))
            ->addColumn('status', fn($row) => $this->statusBadge($row->status, [
                'lulus' => 'bg-success',
                'ulang' => 'bg-warning text-dark',
                'hadir_tidak_setor' => 'bg-info text-dark',
                'sakit' => 'bg-primary',
                'izin' => 'bg-secondary',
                'alpha' => 'bg-danger',
            ]))
            ->addColumn('catatan', fn($row) => $row->catatan ?: '-')
            ->rawColumns(['status'])
            ->make(true);
    }

    /**
     * Timeline Tahsin untuk Yajra DataTables.
     */
    public function tahsinTimeline(Request $request)
    {
        $this->ensureAjax($request);
        $santri = $this->authenticatedSantri($request);

        $query = Tahsin::query()
            ->where('santri_id', $santri->id)
            ->select([
                'id',
                'tanggal',
                'buku',
                'halaman',
                'status',
                'nilai_label',
                'catatan',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn($row) => $this->formatDate($row->tanggal))
            ->addColumn('buku_label', fn($row) => $this->bukuLabel($row->buku))
            ->addColumn('nilai', fn($row) => $this->nilaiArab($row->nilai_label))
            ->addColumn('status', fn($row) => $this->statusBadge($row->status, [
                'hadir' => 'bg-success',
                'sakit' => 'bg-primary',
                'izin' => 'bg-secondary',
                'alpha' => 'bg-danger',
            ]))
            ->addColumn('catatan', fn($row) => $row->catatan ?: '-')
            ->rawColumns(['status'])
            ->make(true);
    }

    /**
     * Timeline Tilawah untuk Yajra DataTables.
     *
     * Tabel tilawahs tidak mempunyai kolom nilai_label, sehingga endpoint
     * ini tidak membuat kolom nilai palsu dan Blade tidak memintanya.
     */
    public function tilawahTimeline(Request $request)
    {
        $this->ensureAjax($request);
        $santri = $this->authenticatedSantri($request);

        $query = Tilawah::query()
            ->leftJoin('hafalan_templates as ht', 'ht.id', '=', 'tilawahs.hafalan_template_id')
            ->where('tilawahs.santri_id', $santri->id)
            ->select([
                'tilawahs.id',
                'tilawahs.tanggal',
                'tilawahs.status',
                'tilawahs.catatan',
                'tilawahs.hafalan_template_id',
                'ht.juz as template_juz',
                'ht.label as template_label',
            ]);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn($row) => $this->formatDate($row->tanggal))
            ->addColumn('target_bacaan', function ($row) {
                if (!$row->template_juz && !$row->template_label) {
                    return '-';
                }

                return trim(
                    ($row->template_juz ? 'Juz ' . $row->template_juz : '') .
                        ($row->template_label ? ' - ' . $row->template_label : '')
                );
            })
            ->addColumn('status', fn($row) => $this->statusBadge($row->status, [
                'hadir' => 'bg-success',
                'sakit' => 'bg-primary',
                'izin' => 'bg-secondary',
                'alpha' => 'bg-danger',
            ]))
            ->addColumn('catatan', fn($row) => $row->catatan ?: '-')
            ->rawColumns(['status'])
            ->make(true);
    }

    /**
     * Download PDF riwayat Hafalan.
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

    private function authenticatedSantri(Request $request): Santri
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            abort(401, 'Sesi pengguna tidak ditemukan. Silakan login kembali.');
        }

        $santri = $user->santri;

        if (!$santri) {
            abort(403, 'Profil santri tidak ditemukan. Hubungi admin.');
        }

        return $santri;
    }

    private function ensureAjax(Request $request): void
    {
        if (!$request->ajax()) {
            abort(404);
        }
    }

    private function formatDate(mixed $date): string
    {
        return $date ? Carbon::parse($date)->format('d-m-Y') : '-';
    }

    private function bukuLabel(?string $buku): string
    {
        return match ($buku) {
            'ummi_1' => 'Ummi Jilid 1',
            'ummi_2' => 'Ummi Jilid 2',
            'ummi_3' => 'Ummi Jilid 3',
            'gharib_1' => 'Gharib 1',
            'gharib_2' => 'Gharib 2',
            'tajwid' => 'Tajwid',
            default => '-',
        };
    }

    private function nilaiArab(?string $nilai): string
    {
        return match (strtolower(trim((string) $nilai))) {
            'mumtaz' => 'ممتاز',
            'jayyid_jiddan' => 'جيد جدًا',
            'jayyid' => 'جيد',
            'mardud' => 'مردود',
            default => $nilai ? strtoupper($nilai) : '-',
        };
    }

    private function statusBadge(?string $status, array $badgeMap): string
    {
        $statusClean = strtolower(trim((string) $status));
        $badge = $badgeMap[$statusClean] ?? 'bg-secondary';
        $label = $statusClean !== ''
            ? ucwords(str_replace('_', ' ', $statusClean))
            : '-';

        return '<span class="badge rounded-pill ' . $badge . '">' . e($label) . '</span>';
    }
}
