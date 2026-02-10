<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Musyrif;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\SantriKelasHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MigrasiSantriController extends Controller
{
    /**
     * Centralized validation
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.santri_id' => ['required', 'integer', 'exists:santris,id'],
            'items.*.to_kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'items.*.to_musyrif_id' => ['nullable', 'integer', 'exists:musyrifs,id'],
            'items.*.tipe' => ['nullable', Rule::in(['penempatan', 'mutasi', 'naik_kelas', 'tinggal_kelas', 'lulus'])],
            'items.*.catatan' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function validatePayloadMassal(Request $request): array
    {
        return $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'from_kelas_id' => ['required', 'integer', 'exists:kelas,id'],

            'to_kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'to_musyrif_id' => ['nullable', 'integer', 'exists:musyrifs,id'],

            'tipe' => ['nullable', Rule::in(['penempatan', 'mutasi', 'naik_kelas', 'tinggal_kelas', 'lulus'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function getAutoKelasMapping(): array
    {
        // from => to (berdasarkan nama_kelas)
        return [
            'Kelas 7' => 'Kelas 8',
            'Kelas 8' => 'Kelas 9',
            'Kelas 9' => 'Kelas 10',
            'Kelas 10' => 'Kelas 11',
            'Kelas 10 INT' => 'Kelas 11 INT',
            // kelas akhir -> lulus (optional, bisa Anda matikan via request)
            'Kelas 11' => null,
            'Kelas 11 INT' => null,
        ];
    }


    public function page()
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $musyrifList = Musyrif::orderBy('nama')->get();

        // Semester aktif
        $semesterAktif = Semester::query()->where('is_active', true)->first();

        return view('admin.santri.naik-kelas-massal', compact(
            'kelasList',
            'musyrifList',
            'semesterAktif'
        ));
    }

    public function byKelas(Request $request)
    {
        $data = $request->validate([
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
        ]);

        $santris = Santri::query()
            ->where('kelas_id', $data['kelas_id'])
            ->orderBy('nama')
            ->get(['id', 'nama', 'nis', 'kelas_id', 'musyrif_id']);

        return response()->json([
            'ok' => true,
            'count' => $santris->count(),
            'santris' => $santris,
        ]);
    }

    /**
     * Preview: validasi mapping, tampilkan ringkasan perubahan sebelum eksekusi.
     */
    public function preview(Request $request)
    {
        $data = $this->validatePayload($request);

        $semester = Semester::query()->findOrFail($data['semester_id']);

        $santriIds = collect($data['items'])->pluck('santri_id')->unique()->values();
        $santris = Santri::query()
            ->with(['kelas', 'musyrif'])
            ->whereIn('id', $santriIds)
            ->get()
            ->keyBy('id');

        $missing = $santriIds->diff($santris->keys());

        $rows = collect($data['items'])->map(function ($item) use ($santris) {
            $s = $santris->get($item['santri_id']);

            return [
                'santri_id' => $item['santri_id'],
                'nama' => $s?->nama,
                'nis' => $s?->nis,
                'kelas_sekarang_id' => $s?->kelas_id,
                'musyrif_sekarang_id' => $s?->musyrif_id,
                'to_kelas_id' => $item['to_kelas_id'],
                'to_musyrif_id' => $item['to_musyrif_id'] ?? null,
                'tipe' => $item['tipe'] ?? 'naik_kelas',
            ];
        });

        return response()->json([
            'ok' => $missing->isEmpty(),
            'semester' => [
                'id' => $semester->id,
                'nama' => $semester->nama,
                'is_active' => (bool) $semester->is_active,
            ],
            'missing_santri_ids' => $missing->values(),
            'count_items' => $rows->count(),
            'items' => $rows,
        ]);
    }
    /**
     * Execute: eksekusi promosi/mutasi dalam 1 transaksi.
     * - upsert history by (santri_id, semester_id)
     * - update santris.kelas_id & santris.musyrif_id
     */
    public function execute(Request $request)
    {
        $data = $this->validatePayload($request);

        $semester = Semester::query()->findOrFail($data['semester_id']);

        // Optional guard: hanya boleh ke semester aktif
        if (!$semester->is_active) {
            return response()->json([
                'ok' => false,
                'message' => 'Semester yang dipilih tidak aktif. Aktifkan semester terlebih dahulu.'
            ], 422);
        }

        $userId = auth()->id();

        $result = DB::transaction(function () use ($data, $semester, $userId) {
            $items = collect($data['items']);

            $santriIds = $items->pluck('santri_id')->unique()->values();
            $santris = Santri::query()
                ->whereIn('id', $santriIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $missing = $santriIds->diff($santris->keys());
            if ($missing->isNotEmpty()) {
                return [
                    'ok' => false,
                    'message' => 'Ada santri yang tidak ditemukan.',
                    'missing_santri_ids' => $missing->values()->all(),
                ];
            }

            $affected = 0;

            foreach ($items as $item) {
                /** @var \App\Models\Santri $santri */
                $santri = $santris->get($item['santri_id']);

                $toKelasId = (int) $item['to_kelas_id'];
                $toMusyrifId = isset($item['to_musyrif_id']) ? (int) $item['to_musyrif_id'] : null;
                $tipe = $item['tipe'] ?? 'naik_kelas';
                $catatan = $item['catatan'] ?? null;

                // 1) Upsert riwayat untuk semester aktif
                SantriKelasHistory::query()->updateOrCreate(
                    [
                        'santri_id' => $santri->id,
                        'semester_id' => $semester->id,
                    ],
                    [
                        'kelas_id' => $toKelasId,
                        'musyrif_id' => $toMusyrifId,
                        'tipe' => $tipe,
                        'catatan' => $catatan,
                        'created_by' => $userId,
                    ]
                );

                // 2) Update kelas aktif di tabel santris
                $update = [
                    'kelas_id' => $toKelasId,
                ];

                // jika ingin ikut update musyrif aktif
                if (!is_null($toMusyrifId)) {
                    $update['musyrif_id'] = $toMusyrifId;
                }

                $santri->update($update);
                $affected++;
            }

            return [
                'ok' => true,
                'affected' => $affected,
                'semester_id' => $semester->id,
            ];
        });

        if (!$result['ok']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Promosi/mutasi santri berhasil diproses.',
            'data' => $result,
        ]);
    }

    public function previewMassal(Request $request)
    {
        $data = $this->validatePayloadMassal($request);

        $semester = Semester::query()->findOrFail($data['semester_id']);

        $fromKelasId = (int) $data['from_kelas_id'];
        $toKelasId = (int) $data['to_kelas_id'];
        $toMusyrifId = $data['to_musyrif_id'] ?? null;

        // Ambil santri dari kelas asal (source of truth = DB)
        $santris = Santri::query()
            ->where('kelas_id', $fromKelasId)
            ->orderBy('nama')
            ->get(['id', 'nama', 'nis', 'kelas_id', 'musyrif_id']);

        return response()->json([
            'ok' => true,
            'semester' => [
                'id' => $semester->id,
                'nama' => $semester->nama,
                'is_active' => (bool) $semester->is_active,
            ],
            'from_kelas_id' => $fromKelasId,
            'to_kelas_id' => $toKelasId,
            'to_musyrif_id' => $toMusyrifId,
            'tipe' => $data['tipe'] ?? 'naik_kelas',
            'catatan' => $data['catatan'] ?? null,
            'count' => $santris->count(),
            'santris' => $santris->take(30), // ringkasan tampilan
        ]);
    }

    public function executeMassal(Request $request)
    {
        $data = $this->validatePayloadMassal($request);

        $semester = Semester::query()->findOrFail($data['semester_id']);

        if (!$semester->is_active) {
            return response()->json([
                'ok' => false,
                'message' => 'Semester yang dipilih tidak aktif. Aktifkan semester terlebih dahulu.'
            ], 422);
        }

        $fromKelasId = (int) $data['from_kelas_id'];
        $toKelasId = (int) $data['to_kelas_id'];

        if ($fromKelasId === $toKelasId) {
            return response()->json([
                'ok' => false,
                'message' => 'Kelas tujuan tidak boleh sama dengan kelas asal.'
            ], 422);
        }

        $toMusyrifId = $data['to_musyrif_id'] ?? null;
        $tipe = $data['tipe'] ?? 'naik_kelas';
        $catatan = $data['catatan'] ?? null;

        $userId = auth()->id();

        $result = DB::transaction(function () use ($semester, $fromKelasId, $toKelasId, $toMusyrifId, $tipe, $catatan, $userId) {
            // Ambil semua santri dari kelas asal saat ini (truth)
            $santris = Santri::query()
                ->where('kelas_id', $fromKelasId)
                ->lockForUpdate()
                ->get(['id', 'nama', 'nis', 'kelas_id', 'musyrif_id']);

            if ($santris->isEmpty()) {
                return [
                    'ok' => false,
                    'message' => 'Tidak ada santri pada kelas asal yang dipilih.'
                ];
            }

            // GUARD: Double-check semua record masih kelas asal (redundan tapi eksplisit)
            $mismatch = $santris->filter(fn($s) => (int) $s->kelas_id !== $fromKelasId);
            if ($mismatch->isNotEmpty()) {
                return [
                    'ok' => false,
                    'message' => 'Ditemukan santri yang sudah tidak berada di kelas asal. Proses dibatalkan.',
                    'mismatch' => $mismatch->map(fn($s) => [
                        'id' => $s->id,
                        'nama' => $s->nama,
                        'nis' => $s->nis,
                        'kelas_id' => $s->kelas_id,
                    ])->values()->all(),
                ];
            }

            $affected = 0;

            foreach ($santris as $santri) {
                SantriKelasHistory::query()->updateOrCreate(
                    [
                        'santri_id' => $santri->id,
                        'semester_id' => $semester->id,
                    ],
                    [
                        'kelas_id' => $toKelasId,
                        'musyrif_id' => $toMusyrifId,
                        'tipe' => $tipe,
                        'catatan' => $catatan,
                        'created_by' => $userId,
                    ]
                );

                $update = ['kelas_id' => $toKelasId];
                if (!is_null($toMusyrifId)) {
                    $update['musyrif_id'] = $toMusyrifId;
                }

                $santri->update($update);
                $affected++;
            }

            return [
                'ok' => true,
                'affected' => $affected,
                'from_kelas_id' => $fromKelasId,
                'to_kelas_id' => $toKelasId,
                'semester_id' => $semester->id,
            ];
        });

        if (!$result['ok']) {
            return response()->json($result, 422);
        }

        return response()->json([
            'ok' => true,
            'message' => "Berhasil memproses {$result['affected']} santri.",
            'data' => $result,
        ]);
    }

    public function previewAutoMapping(Request $request)
    {
        $data = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'include_graduation' => ['nullable', 'boolean'], // default true
            'tipe' => ['nullable', Rule::in(['naik_kelas', 'mutasi', 'tinggal_kelas', 'penempatan', 'lulus'])],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $semester = Semester::query()->findOrFail($data['semester_id']);
        $includeGraduation = $data['include_graduation'] ?? true;

        $mapping = $this->getAutoKelasMapping();

        // Ambil semua kelas yang dibutuhkan sekaligus
        $kelasNames = array_unique(array_merge(array_keys($mapping), array_values(array_filter($mapping))));
        $kelasRows = Kelas::query()->whereIn('nama_kelas', $kelasNames)->get(['id', 'nama_kelas'])->keyBy('nama_kelas');

        $rows = [];
        foreach ($mapping as $fromName => $toName) {
            if ($toName === null && !$includeGraduation) {
                continue;
            }

            $from = $kelasRows->get($fromName);
            $to = $toName ? $kelasRows->get($toName) : null;

            $rows[] = [
                'from_nama' => $fromName,
                'from_id' => $from?->id,
                'to_nama' => $toName ?? 'LULUS',
                'to_id' => $to?->id,
                'status' => (!$from || ($toName && !$to)) ? 'MISSING_KELAS' : 'OK',
                'count_santri' => $from ? Santri::query()->where('kelas_id', $from->id)->count() : 0,
                'tipe' => $toName ? ($data['tipe'] ?? 'naik_kelas') : 'lulus',
            ];
        }

        $missing = array_values(array_filter($rows, fn($r) => $r['status'] !== 'OK'));

        return response()->json([
            'ok' => count($missing) === 0,
            'semester' => ['id' => $semester->id, 'nama' => $semester->nama, 'is_active' => (bool) $semester->is_active],
            'include_graduation' => (bool) $includeGraduation,
            'catatan' => $data['catatan'] ?? null,
            'rows' => $rows,
            'missing' => $missing,
            'total_santri_affected' => array_sum(array_column($rows, 'count_santri')),
        ]);
    }

    public function executeAutoMapping(Request $request)
    {
        $data = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'include_graduation' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'to_musyrif_id' => ['nullable', 'integer', 'exists:musyrifs,id'], // optional: set musyrif tujuan global
        ]);

        $semester = Semester::query()->findOrFail($data['semester_id']);
        if (!$semester->is_active) {
            return response()->json(['ok' => false, 'message' => 'Semester yang dipilih tidak aktif.'], 422);
        }

        $includeGraduation = $data['include_graduation'] ?? true;
        $toMusyrifId = $data['to_musyrif_id'] ?? null;
        $catatan = $data['catatan'] ?? null;
        $userId = auth()->id();

        $mapping = $this->getAutoKelasMapping();

        // Resolve kelas IDs by nama_kelas
        $kelasNames = array_unique(array_merge(array_keys($mapping), array_values(array_filter($mapping))));
        $kelasRows = Kelas::query()->whereIn('nama_kelas', $kelasNames)->get(['id', 'nama_kelas'])->keyBy('nama_kelas');

        // Validasi mapping: semua kelas yang diperlukan harus ada
        foreach ($mapping as $fromName => $toName) {
            if ($toName === null && !$includeGraduation)
                continue;

            if (!$kelasRows->has($fromName)) {
                return response()->json(['ok' => false, 'message' => "Kelas asal tidak ditemukan: {$fromName}"], 422);
            }
            if ($toName && !$kelasRows->has($toName)) {
                return response()->json(['ok' => false, 'message' => "Kelas tujuan tidak ditemukan: {$toName}"], 422);
            }
        }

        $result = DB::transaction(function () use ($mapping, $kelasRows, $semester, $includeGraduation, $toMusyrifId, $catatan, $userId) {

            $summary = [];
            $totalAffected = 0;

            foreach ($mapping as $fromName => $toName) {
                if ($toName === null && !$includeGraduation)
                    continue;

                $fromId = (int) $kelasRows[$fromName]->id;
                $toId = $toName ? (int) $kelasRows[$toName]->id : null;

                // Ambil santri terkini dari kelas asal (truth) dan lock
                $santris = Santri::query()
                    ->where('kelas_id', $fromId)
                    ->lockForUpdate()
                    ->get(['id', 'nama', 'nis', 'kelas_id', 'musyrif_id']);

                $count = $santris->count();
                if ($count === 0) {
                    $summary[] = ['from' => $fromName, 'to' => ($toName ?? 'LULUS'), 'affected' => 0];
                    continue;
                }

                // Guard: tetap harus match kelas asal
                $mismatch = $santris->filter(fn($s) => (int) $s->kelas_id !== $fromId);
                if ($mismatch->isNotEmpty()) {
                    return [
                        'ok' => false,
                        'message' => "Mismatch kelas ditemukan pada mapping {$fromName} → " . ($toName ?? 'LULUS') . ". Proses dibatalkan.",
                        'mismatch' => $mismatch->map(fn($s) => ['id' => $s->id, 'nama' => $s->nama, 'nis' => $s->nis, 'kelas_id' => $s->kelas_id])->values()->all(),
                    ];
                }

                foreach ($santris as $santri) {
                    // tulis history semester
                    SantriKelasHistory::query()->updateOrCreate(
                        ['santri_id' => $santri->id, 'semester_id' => $semester->id],
                        [
                            'kelas_id' => $toId ?? $fromId, // untuk lulus, history tetap tersimpan (opsional)
                            'musyrif_id' => $toMusyrifId,
                            'tipe' => $toName ? 'naik_kelas' : 'lulus',
                            'catatan' => $catatan,
                            'created_by' => $userId,
                        ]
                    );

                    // update santri (kelas aktif)
                    if ($toName) {
                        $update = ['kelas_id' => $toId];
                        if (!is_null($toMusyrifId))
                            $update['musyrif_id'] = $toMusyrifId;
                        $santri->update($update);
                    } else {
                        // LULUS: rekomendasi tambah kolom status (aktif/lulus), tapi jika belum ada:
                        // minimal: jangan ubah kelas_id (atau pindahkan ke kelas "Alumni" jika ada).
                        // Di sini saya tidak ubah kelas_id, hanya history tipe=lulus.
                    }
                }

                $summary[] = ['from' => $fromName, 'to' => ($toName ?? 'LULUS'), 'affected' => $count];
                $totalAffected += $count;
            }

            return ['ok' => true, 'total_affected' => $totalAffected, 'summary' => $summary];
        });

        if (!$result['ok'])
            return response()->json($result, 422);

        return response()->json([
            'ok' => true,
            'message' => "Auto-mapping berhasil. Total diproses: {$result['total_affected']} santri.",
            'data' => $result,
        ]);
    }


}
