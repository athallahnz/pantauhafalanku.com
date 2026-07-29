<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Support\Academic\ResolvesActiveSemester;
use App\Models\Tahsin;
use App\Models\Santri;
use App\Models\Musyrif;
use App\Models\Tilawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class TahsinController extends Controller
{
    use ResolvesActiveSemester;

    /*
     * Mapping target syarat Tilawah.
     *
     * Nilai target tidak hanya berarti santri pernah tercatat pada Juz
     * tersebut. Santri wajib memiliki Tilawah berstatus "hadir" pada setiap
     * Juz secara berurutan, mulai Juz 1 sampai Juz target.
     *
     * Buku yang tidak tercantum di mapping ini, termasuk Ummi 1–3, tidak
     * memiliki syarat Tilawah.
     */
    private const TARGET_TILAWAH = [
        'gharib_1' => 5,
        'gharib_2' => 10,
        'tajwid'   => 15,
    ];

    private const DRILL_MATERI_CATATAN = 'Mengulang Materi Bersama';

    /**
     * Mengambil jumlah Juz Tilawah yang sudah tercatat hadir secara unik
     * untuk setiap santri dalam rentang Juz 1 sampai Juz target.
     *
     * Karena query dibatasi ke rentang 1..$targetJuz, jumlah yang sama dengan
     * $targetJuz berarti tidak ada Juz yang terlewat di dalam rentang wajib.
     */
    private function completedTilawahJuzCounts($santriIds, int $targetJuz)
    {
        return DB::table('tilawahs')
            ->join(
                'hafalan_templates',
                'tilawahs.hafalan_template_id',
                '=',
                'hafalan_templates.id'
            )
            ->whereIn('tilawahs.santri_id', $santriIds)
            ->where('tilawahs.status', 'hadir')
            ->whereBetween('hafalan_templates.juz', [1, $targetJuz])
            ->select(
                'tilawahs.santri_id',
                DB::raw(
                    'COUNT(DISTINCT hafalan_templates.juz) as completed_juz_count'
                )
            )
            ->groupBy('tilawahs.santri_id')
            ->pluck('completed_juz_count', 'tilawahs.santri_id');
    }

    /**
     * Buku NULL digunakan secara khusus untuk pencatatan Drill Materi.
     */
    private function tahsinBookLabel(?string $buku): string
    {
        if ($buku === null || $buku === '') {
            return 'Drill Materi';
        }

        return [
            'ummi_1'   => 'Ummi Jilid 1',
            'ummi_2'   => 'Ummi Jilid 2',
            'ummi_3'   => 'Ummi Jilid 3',
            'gharib_1' => 'Gharib 1',
            'gharib_2' => 'Gharib 2',
            'tajwid'   => 'Tajwid',
        ][$buku] ?? ucfirst(str_replace('_', ' ', $buku));
    }

    /**
     * TAMPILAN UTAMA
     */
    public function index()
    {
        $musyrif = Musyrif::where('user_id', Auth::id())->firstOrFail();

        $santriBinaan = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->with('kelas')
            ->get();

        $totalSantri = $santriBinaan->count();

        /*
        |--------------------------------------------------------------------------
        | STATISTIK HARIAN & PROGRESS (DASHBOARD MINI)
        |--------------------------------------------------------------------------
        */
        // 1. Aktivitas Input Hari Ini
        $tahsinToday = Tahsin::where('musyrif_id', $musyrif->id)
            ->whereDate('tanggal', today())
            ->distinct('santri_id')
            ->count('santri_id');

        $tilawahToday = Tilawah::where('musyrif_id', $musyrif->id)
            ->whereDate('tanggal', today())
            ->distinct('santri_id')
            ->count('santri_id');

        // 2. Rata-rata Capaian Tilawah
        $progressSantri = DB::table('tilawahs')
            ->join('hafalan_templates', 'tilawahs.hafalan_template_id', '=', 'hafalan_templates.id')
            ->where('tilawahs.musyrif_id', $musyrif->id)
            ->where('tilawahs.status', 'hadir')
            ->select('tilawahs.santri_id', DB::raw('MAX(hafalan_templates.juz) as max_juz'))
            ->groupBy('tilawahs.santri_id')
            ->get();

        $avgJuz = $progressSantri->count() > 0 ? round($progressSantri->avg('max_juz')) : 0;

        // 3. Buku Tahsin Mayoritas Saat Ini (Mencari buku terbaru dari tiap santri lalu di-grouping)
        $latestTahsinSubquery = DB::table('tahsins')
            ->select('santri_id', DB::raw('MAX(id) as max_id'))
            ->where('musyrif_id', $musyrif->id)
            ->where('status', 'hadir')
            ->whereNotNull('buku')
            ->groupBy('santri_id');

        $mayoritasBukuData = DB::table('tahsins as t')
            ->joinSub($latestTahsinSubquery, 'latest', function ($join) {
                $join->on('t.santri_id', '=', 'latest.santri_id')
                    ->on('t.id', '=', 'latest.max_id');
            })
            ->select('t.buku', DB::raw('COUNT(*) as total'))
            ->groupBy('t.buku')
            ->orderByDesc('total')
            ->first();

        $mayoritasBuku = $mayoritasBukuData
            ? $this->tahsinBookLabel($mayoritasBukuData->buku)
            : 'Belum Ada';

        return view('musyrif.tahsin.index', compact(
            'santriBinaan',
            'totalSantri',
            'tahsinToday',
            'tilawahToday',
            'avgJuz',
            'mayoritasBuku'
        ));
    }
    /**
     * DATATABLE (Server Side)
     */
    public function datatable(Request $request)
    {
        $musyrif = Musyrif::where('user_id', Auth::id())->firstOrFail();

        $query = Tahsin::where('musyrif_id', $musyrif->id)
            ->with(['santri.kelas'])
            ->select('tahsins.*')
            ->orderBy('tanggal', 'desc');

        // Logic Filter Tanggal
        if ($request->filter_tanggal == 'today') {
            $query->whereDate('tanggal', today());
        } elseif ($request->filter_tanggal == 'yesterday') {
            $query->whereDate('tanggal', today()->subDay());
        } elseif ($request->filter_tanggal == 'last_7_days') {
            $query->whereDate('tanggal', '>=', today()->subDays(7));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('santri', fn($row) => $row->santri->nama)
            ->addColumn(
                'buku_label',
                fn($row) => $this->tahsinBookLabel($row->buku)
            )
            ->addColumn(
                'halaman',
                fn($row) => $row->halaman === null
                    ? '<span class="text-muted">—</span>'
                    : 'Hal. ' . $row->halaman
            )
            ->addColumn('tanggal', fn($row) => \Carbon\Carbon::parse($row->tanggal)->format('d M Y'))
            ->addColumn('nilai_format', function ($row) {
                if (!$row->nilai_label) return '<span class="text-muted small fst-italic">-</span>';

                // Menggunakan bahasa Arab dengan tambahan fs-6 agar huruf hijaiyah terbaca jelas
                $badges = [
                    'mumtaz'        => '<span class="badge bg-success-subtle text-success border border-success fs-6">ممتاز</span>',
                    'jayyid_jiddan' => '<span class="badge bg-primary-subtle text-primary border border-primary fs-6">جيد جدًا</span>',
                    'jayyid'        => '<span class="badge bg-info-subtle text-info border border-info fs-6">جيد</span>',
                    'mardud'        => '<span class="badge bg-danger-subtle text-danger border border-danger fs-6">مردود</span>',
                ];
                return $badges[$row->nilai_label] ?? '-';
            })
            ->addColumn('status_label', function ($row) {
                $color = match ($row->status) {
                    'hadir' => 'success',
                    'izin'  => 'secondary',
                    'sakit' => 'primary',
                    'alpha' => 'danger',
                    default => 'dark'
                };
                return '<span class="badge bg-' . $color . '-subtle text-' . $color . ' rounded-pill px-3">' . ucfirst($row->status) . '</span>';
            })
            ->addColumn('aksi', function ($row) {
                $bukuLabel = $this->tahsinBookLabel($row->buku);
                $halaman = $row->halaman === null ? '' : (string) $row->halaman;

                return '
        <div class="d-flex gap-2 flex-nowrap justify-content-end">
            <button type="button" class="btn btn-sm btn-info btn-detail"
                data-santri_nama="' . e($row->santri->nama) . '"
                data-buku_label="' . e($bukuLabel) . '"
                data-halaman="' . e($halaman) . '"
                data-tanggal_label="' . \Carbon\Carbon::parse($row->tanggal)->format('d M Y') . '"
                data-status_text="' . $row->status . '"
                data-nilai_label="' . $row->nilai_label . '"
                data-catatan="' . e($row->catatan ?? 'Tidak ada catatan.') . '"
                data-coreui-toggle="tooltip" title="Lihat Detail">
                <i class="bi bi-eye text-white"></i>
            </button>

            <button type="button" class="btn btn-sm btn-primary btn-edit"
                data-id="' . $row->id . '"
                data-santri_nama="' . e($row->santri->nama) . '"
                data-status="' . $row->status . '"
                data-buku_label="' . e($bukuLabel) . '"
                data-halaman="' . e($halaman) . '"
                data-nilai_label="' . $row->nilai_label . '"
                data-catatan="' . e($row->catatan) . '"
                data-coreui-toggle="tooltip" title="Edit Status">
                <i class="bi bi-pencil-square"></i>
            </button>

            <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                data-id="' . $row->id . '"
                data-coreui-toggle="tooltip" title="Hapus Data">
                <i class="bi bi-trash"></i>
            </button>
        </div>';
            })
            ->rawColumns(['halaman', 'status_label', 'nilai_format', 'aksi'])
            ->make(true);
    }

    /**
     * Mengecek berapa santri yang sudah memenuhi syarat Tilawah untuk Buku Tahsin tertentu
     */
    public function checkEligibility(Request $request)
    {
        $buku = $request->buku;
        if (!$buku) return response()->json(['eligible' => 0, 'total' => 0]);

        $musyrif = Musyrif::where('user_id', Auth::id())->firstOrFail();
        $santris = Santri::query()
            ->active()
            ->where('musyrif_id', $musyrif->id)
            ->pluck('id');
        $total = $santris->count();

        // Buku yang tidak ada di mapping, termasuk Ummi 1–3, bebas syarat Tilawah.
        $syaratJuz = self::TARGET_TILAWAH[$buku] ?? null;

        if ($syaratJuz === null) {
            return response()->json([
                'eligible'         => $total,
                'total'            => $total,
                'requires_tilawah' => false,
                'syarat_juz'       => null,
                'syarat_label'     => 'Tanpa syarat Tilawah',
            ]);
        }

        $completedJuzCounts = $this->completedTilawahJuzCounts(
            $santris,
            $syaratJuz
        );

        $eligible = 0;
        foreach ($santris as $id) {
            $completedJuzCount = (int) ($completedJuzCounts[$id] ?? 0);

            if ($completedJuzCount === $syaratJuz) {
                $eligible++;
            }
        }

        return response()->json([
            'eligible'         => $eligible,
            'total'            => $total,
            'requires_tilawah' => true,
            'syarat_juz'       => $syaratJuz,
            'syarat_label'     => $syaratJuz === 1
                ? 'Juz 1'
                : "Juz 1–{$syaratJuz}",
        ]);
    }

    /**
     * SIMPAN DATA MASAL
     */
    public function store(Request $request)
    {
        $activeSemester = $this->assertAcademicInputOpen();
        $semesterId = (int) $activeSemester->id;

        $validated = $request->validate([
            'buku' => [
                'required',
                Rule::in([
                    'ummi_1',
                    'ummi_2',
                    'ummi_3',
                    'gharib_1',
                    'gharib_2',
                    'tajwid',
                    'drill_materi',
                ]),
            ],
            'halaman' => [
                Rule::requiredIf(
                    fn() => $request->input('buku') !== 'drill_materi'
                ),
                'nullable',
                'array',
                'min:1',
            ],
            'halaman.*' => ['nullable', 'integer', 'min:1'],
            'nilai_label' => [
                'nullable',
                Rule::in([
                    'mumtaz',
                    'jayyid_jiddan',
                    'jayyid',
                    'mardud',
                ]),
            ],
            'catatan' => ['nullable', 'string'],
        ]);

        try {
            $musyrif = Musyrif::query()
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $tanggal = now('Asia/Jakarta')->toDateString();

            $santris = Santri::query()
                ->active()
                ->where('musyrif_id', $musyrif->id)
                ->get(['id', 'nama']);

            if ($santris->isEmpty()) {
                return response()->json([
                    'ok' => false,
                    'icon' => 'error',
                    'message' => 'Gagal! Anda belum memiliki santri binaan.',
                ], 422);
            }

            $isDrillMateri = $validated['buku'] === 'drill_materi';
            $bukuTujuan = $isDrillMateri ? null : $validated['buku'];
            $syaratJuz = $bukuTujuan === null
                ? null
                : (self::TARGET_TILAWAH[$bukuTujuan] ?? null);
            $halamanTujuan = $isDrillMateri
                ? [null]
                : $validated['halaman'];
            $catatanTujuan = $isDrillMateri
                ? self::DRILL_MATERI_CATATAN
                : ($validated['catatan'] ?? null);

            /*
             * Progress Tilawah bersifat kumulatif dan tidak dibatasi semester.
             * Santri harus memiliki data hadir pada setiap Juz dalam rentang
             * 1 sampai target; Juz yang terlewat membuat santri tidak lolos.
             * Query ini hanya diperlukan untuk Gharib 1–2 dan Tajwid.
             */
            $completedJuzCounts = $syaratJuz === null
                ? collect()
                : $this->completedTilawahJuzCounts(
                    $santris->pluck('id'),
                    $syaratJuz
                );

            $insertedCount = 0;
            $skippedNames = [];

            DB::transaction(function () use (
                $santris,
                $validated,
                $bukuTujuan,
                $halamanTujuan,
                $catatanTujuan,
                $syaratJuz,
                $completedJuzCounts,
                $tanggal,
                $semesterId,
                $musyrif,
                &$insertedCount,
                &$skippedNames
            ): void {
                foreach ($santris as $santri) {
                    $completedJuzCount =
                        (int) ($completedJuzCounts[$santri->id] ?? 0);

                    if (
                        $syaratJuz !== null
                        && $completedJuzCount !== $syaratJuz
                    ) {
                        $skippedNames[] = $santri->nama;
                        continue;
                    }

                    foreach ($halamanTujuan as $halaman) {
                        Tahsin::query()->updateOrCreate(
                            [
                                'santri_id' => $santri->id,
                                'semester_id' => $semesterId,
                                'tanggal' => $tanggal,
                                'buku' => $bukuTujuan,
                                'halaman' => $halaman === null
                                    ? null
                                    : (int) $halaman,
                            ],
                            [
                                'musyrif_id' => $musyrif->id,
                                'status' => 'hadir',
                                'nilai_label' =>
                                $validated['nilai_label'] ?? null,
                                'catatan' => $catatanTujuan,
                            ]
                        );
                    }

                    $insertedCount++;
                }
            });

            if ($insertedCount === 0) {
                return response()->json([
                    'ok' => false,
                    'icon' => 'error',
                    'message' => "Gagal! Semua santri belum menuntaskan Tilawah lengkap dari Juz 1 sampai Juz {$syaratJuz}.",
                ], 422);
            }

            if ($skippedNames !== []) {
                return response()->json([
                    'ok' => true,
                    'icon' => 'warning',
                    'message' =>
                    "Berhasil untuk {$insertedCount} santri. Namun, "
                        . count($skippedNames)
                        . " santri dilewati karena Tilawah Juz 1 sampai Juz {$syaratJuz} belum lengkap.",
                    'skipped_santri' => $skippedNames,
                ]);
            }

            return response()->json([
                'ok' => true,
                'icon' => 'success',
                'message' => $isDrillMateri
                    ? 'Berhasil! Drill Materi diterapkan ke semua santri.'
                    : 'Berhasil! Materi diterapkan ke semua santri.',
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Terjadi kesalahan sistem saat menyimpan data Tahsin.',
            ], 500);
        }
    }

    public function update(
        Request $request,
        Tahsin $tahsin
    ) {
        $musyrif = Musyrif::query()
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ((int) $tahsin->musyrif_id !== (int) $musyrif->id) {
            return response()->json([
                'message' => 'Unauthorized! Data ini bukan milik Anda.',
            ], 403);
        }

        $activeSemester =
            $this->assertRecordEditableInActiveSemester(
                $tahsin->semester_id
            );

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'hadir',
                    'izin',
                    'sakit',
                    'alpha',
                ]),
            ],
            'nilai_label' => [
                'nullable',
                Rule::in([
                    'mumtaz',
                    'jayyid_jiddan',
                    'jayyid',
                    'mardud',
                ]),
            ],
            'catatan' => ['nullable', 'string'],
        ]);

        /*
         * Record legacy dengan semester_id null akan dimasukkan ke semester
         * aktif. Record semester lama sudah ditolak oleh trait.
         */
        $validated['semester_id'] =
            (int) $activeSemester->id;

        if ($tahsin->buku === null && $tahsin->halaman === null) {
            $validated['catatan'] = self::DRILL_MATERI_CATATAN;
        }

        $tahsin->update($validated);

        return response()->json([
            'ok' => true,
            'message' => 'Status individu berhasil diperbarui.',
        ]);
    }

    public function destroy(Tahsin $tahsin)
    {
        $userId = Auth::id();

        $musyrif = Musyrif::query()
            ->where('user_id', $userId)
            ->first();

        if (!$musyrif) {
            return response()->json([
                'message' => 'Unauthorized! Data Musyrif tidak ditemukan untuk user ini.',
            ], 403);
        }

        if ((int) $tahsin->musyrif_id !== (int) $musyrif->id) {
            \Log::warning(
                'Percobaan hapus Tahsin yang bukan milik musyrif.',
                [
                    'user_id' => $userId,
                    'tahsin_id' => $tahsin->id,
                    'target_musyrif_id' => $tahsin->musyrif_id,
                    'login_musyrif_id' => $musyrif->id,
                ]
            );

            return response()->json([
                'message' => 'Unauthorized! Data ini bukan milik Anda.',
            ], 403);
        }

        $this->assertRecordEditableInActiveSemester(
            $tahsin->semester_id
        );

        $tahsin->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Data Tahsin berhasil dihapus.',
        ]);
    }

    public function detail(Santri $santri)
    {
        $musyrif = Musyrif::where('user_id', auth()->id())->firstOrFail();
        if ((int) $santri->musyrif_id !== (int) $musyrif->id) {
            abort(403, 'Akses ditolak: Santri bukan binaan Anda.');
        }

        /* ================== DATA TAHSIN ================== */
        $statusCounts = Tahsin::where('santri_id', $santri->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bukuMap = [
            'ummi_1'   => ['label' => 'Ummi 1',   'max' => 40],
            'ummi_2'   => ['label' => 'Ummi 2',   'max' => 40],
            'ummi_3'   => ['label' => 'Ummi 3',   'max' => 40],
            'gharib_1' => ['label' => 'Gharib 1', 'max' => 28],
            'gharib_2' => ['label' => 'Gharib 2', 'max' => 28],
            'tajwid'   => ['label' => 'Tajwid',   'max' => 50],
        ];

        $maxPages = Tahsin::where('santri_id', $santri->id)->where('status', 'hadir')
            ->selectRaw('buku, MAX(halaman) as max_halaman')->groupBy('buku')->pluck('max_halaman', 'buku');

        $progressPerBuku = collect($bukuMap)->map(function ($info, $key) use ($maxPages) {
            $currentHal = $maxPages[$key] ?? 0;
            $pct = min(100, round(($currentHal / $info['max']) * 100));

            $config = match (true) {
                $pct >= 100 => ['status' => 'Selesai',         'color' => 'success'],
                $pct > 0    => ['status' => 'Sedang Berjalan', 'color' => 'primary'],
                default     => ['status' => 'Belum Mulai',     'color' => 'light'],
            };

            return array_merge(['buku_key' => $key, 'label' => $info['label'], 'max' => $info['max'], 'current' => $currentHal, 'pct' => $pct], $config);
        });

        $overallPct = round($progressPerBuku->avg('pct') ?? 0);
        $lastTahsin = Tahsin::where('santri_id', $santri->id)->where('status', 'hadir')->latest()->first();

        /* ================== DATA TILAWAH ================== */
        $tilawahCounts = Tilawah::where('santri_id', $santri->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $maxJuz = DB::table('tilawahs')
            ->join('hafalan_templates', 'tilawahs.hafalan_template_id', '=', 'hafalan_templates.id')
            ->where('tilawahs.santri_id', $santri->id)
            ->where('tilawahs.status', 'hadir')
            ->max('hafalan_templates.juz') ?? 0;

        $tilawahPct = round(($maxJuz / 30) * 100); // 30 Juz Al-Quran
        $lastTilawah = Tilawah::with('template')->where('santri_id', $santri->id)->where('status', 'hadir')->latest()->first();

        return view('musyrif.tahsin.detail', [
            'santri'          => $santri,
            // Tahsin
            'progressPerBuku' => $progressPerBuku,
            'overallPct'      => $overallPct,
            'totalHadir'      => $statusCounts['hadir'] ?? 0,
            'totalIzin'       => $statusCounts['izin'] ?? 0,
            'totalSakit'      => $statusCounts['sakit'] ?? 0,
            'totalAlpha'      => $statusCounts['alpha'] ?? 0,
            'lastTahsin'      => $lastTahsin,
            // Tilawah
            'maxJuz'          => $maxJuz,
            'tilawahPct'      => $tilawahPct,
            'tilawahHadir'    => $tilawahCounts['hadir'] ?? 0,
            'tilawahIzin'     => $tilawahCounts['izin'] ?? 0,
            'tilawahSakit'    => $tilawahCounts['sakit'] ?? 0,
            'tilawahAlpha'    => $tilawahCounts['alpha'] ?? 0,
            'lastTilawah'     => $lastTilawah,
        ]);
    }

    public function timeline(Request $request, Santri $santri)
    {
        if (!$request->ajax()) abort(404);

        $musyrif = Musyrif::where('user_id', auth()->id())->firstOrFail();
        if ((int) $santri->musyrif_id !== (int) $musyrif->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Tahsin::where('santri_id', $santri->id)->latest();

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn($row) => \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y'))
            ->addColumn(
                'buku_label',
                fn($row) => $this->tahsinBookLabel($row->buku)
            )
            ->addColumn('status', function ($row) {
                $colors = [
                    'hadir' => 'success',
                    'izin'  => 'secondary',
                    'sakit' => 'primary',
                    'alpha' => 'danger'
                ];
                $color = $colors[$row->status] ?? 'dark';
                return '<span class="badge bg-' . $color . ' rounded-pill px-3 py-2">' . strtoupper($row->status) . '</span>';
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    // TIMELINE TILAWAH:
    public function timelineTilawah(Request $request, Santri $santri)
    {
        if (!$request->ajax()) abort(404);

        $musyrif = Musyrif::where('user_id', auth()->id())->firstOrFail();
        if ((int) $santri->musyrif_id !== (int) $musyrif->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Tilawah::with('template')->where('santri_id', $santri->id)->latest();

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('tanggal', fn($row) => \Carbon\Carbon::parse($row->created_at)->translatedFormat('d M Y'))
            ->addColumn('target_bacaan', function ($row) {
                return $row->template ? "Juz {$row->template->juz} <br><small class='text-muted'>{$row->template->label}</small>" : '-';
            })
            ->addColumn('status', function ($row) {
                $colors = ['hadir' => 'success', 'izin' => 'secondary', 'sakit' => 'primary', 'alpha' => 'danger'];
                $color = $colors[$row->status] ?? 'dark';
                return '<span class="badge bg-' . $color . ' rounded-pill px-3 py-2">' . strtoupper($row->status) . '</span>';
            })
            ->rawColumns(['target_bacaan', 'status'])
            ->make(true);
    }
}
