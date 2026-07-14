<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Musyrif;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\SantriKelasHistory;
use App\Models\SantriMigrationBatch;
use App\Support\Academic\HandlesSantriMigrationBatches;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MigrasiSantriController extends Controller
{
    use HandlesSantriMigrationBatches;

    /**
     * Centralized validation
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'from_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:to_semester_id',
            ],
            'to_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:from_semester_id',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.santri_id' => [
                'required',
                'integer',
                'exists:santris,id',
            ],
            'items.*.to_kelas_id' => [
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
            'items.*.to_musyrif_id' => [
                'nullable',
                'integer',
                'exists:musyrifs,id',
            ],
            'items.*.tipe' => [
                'nullable',
                Rule::in([
                    'penempatan',
                    'mutasi',
                    'naik_kelas',
                    'tinggal_kelas',
                    'lulus',
                ]),
            ],
            'items.*.catatan' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
    }

    private function validatePreviewMassalPayload(
        Request $request
    ): array {
        return $request->validate([
            'from_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:to_semester_id',
            ],
            'to_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:from_semester_id',
            ],
            'from_kelas_id' => [
                'required',
                'integer',
                'exists:kelas,id',
            ],
            'to_kelas_id' => [
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
            'tipe' => [
                'nullable',
                Rule::in([
                    'penempatan',
                    'mutasi',
                    'naik_kelas',
                    'tinggal_kelas',
                    'lulus',
                ]),
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
    }

    private function validateExecuteMassalPayload(
        Request $request
    ): array {
        return $request->validate([
            'from_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:to_semester_id',
            ],
            'to_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:from_semester_id',
            ],
            'from_kelas_id' => [
                'required',
                'integer',
                'exists:kelas,id',
            ],
            'to_kelas_id' => [
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
            'tipe' => [
                'nullable',
                Rule::in([
                    'penempatan',
                    'mutasi',
                    'naik_kelas',
                    'tinggal_kelas',
                    'lulus',
                ]),
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.santri_id' => [
                'required',
                'integer',
                'distinct',
                'exists:santris,id',
            ],
            'items.*.to_musyrif_id' => [
                'nullable',
                'integer',
                'exists:musyrifs,id',
            ],
        ]);
    }

    /**
     * Memastikan proses migrasi bergerak dari semester aktif
     * menuju semester lain yang belum aktif.
     *
     * Pada langkah berikutnya aturan ini akan diganti menggunakan
     * lifecycle semester: draft, active, dan closed.
     *
     * @return array{0: Semester, 1: Semester}
     */
    private function resolveSemesterTransition(
        int $fromSemesterId,
        int $toSemesterId,
        bool $requireInputLocked = false
    ): array {
        $fromSemester = Semester::query()
            ->findOrFail($fromSemesterId);

        $toSemester = Semester::query()
            ->findOrFail($toSemesterId);

        if ((int) $fromSemester->id === (int) $toSemester->id) {
            throw ValidationException::withMessages([
                'to_semester_id' => [
                    'Semester tujuan harus berbeda dari semester asal.',
                ],
            ]);
        }

        if (!$fromSemester->isActive()) {
            throw ValidationException::withMessages([
                'from_semester_id' => [
                    'Semester asal harus berstatus active.',
                ],
            ]);
        }

        if (!$toSemester->isDraft()) {
            throw ValidationException::withMessages([
                'to_semester_id' => [
                    'Semester tujuan harus berstatus draft.',
                ],
            ]);
        }

        if (
            $requireInputLocked
            && !$fromSemester->isInputLocked()
        ) {
            throw ValidationException::withMessages([
                'from_semester_id' => [
                    'Kunci input semester asal sebelum menjalankan eksekusi migrasi.',
                ],
            ]);
        }

        return [$fromSemester, $toSemester];
    }

    private function validateClassTransition(
        string $tipe,
        ?int $fromKelasId,
        ?int $toKelasId
    ): void {
        if ($tipe === 'lulus') {
            return;
        }

        if ($toKelasId === null) {
            throw ValidationException::withMessages([
                'to_kelas_id' => [
                    'Kelas tujuan wajib dipilih kecuali untuk proses kelulusan.',
                ],
            ]);
        }

        if (
            $tipe === 'tinggal_kelas'
            && (
                $fromKelasId === null
                || $fromKelasId !== $toKelasId
            )
        ) {
            throw ValidationException::withMessages([
                'to_kelas_id' => [
                    'Proses tinggal kelas harus menggunakan kelas tujuan yang sama dengan kelas asal.',
                ],
            ]);
        }

        if (
            in_array(
                $tipe,
                [
                    'naik_kelas',
                    'mutasi',
                ],
                true
            )
            && $fromKelasId === $toKelasId
        ) {
            throw ValidationException::withMessages([
                'to_kelas_id' => [
                    'Kelas tujuan harus berbeda dari kelas asal untuk proses kenaikan kelas atau mutasi.',
                ],
            ]);
        }

        /*
         * Penempatan boleh menggunakan kelas yang sama atau berbeda.
         * Ini berguna untuk koreksi/penetapan awal pada semester tujuan.
         */
    }

    private function resolveEffectiveMusyrifId(
        Santri $santri,
        string $tipe,
        ?int $toKelasId,
        ?int $toMusyrifId,
        string $errorKey = 'to_musyrif_id'
    ): ?int {
        if ($tipe === 'lulus') {
            return null;
        }

        if ($toKelasId === null) {
            throw ValidationException::withMessages([
                'to_kelas_id' => [
                    'Kelas tujuan wajib dipilih sebelum menentukan musyrif.',
                ],
            ]);
        }

        /*
         * Aturan khusus migrasi semester:
         * - null berarti mempertahankan musyrif lama;
         * - pilihan baru boleh berasal dari seluruh Master Musyrif;
         * - kelas_id pada musyrifs tidak membatasi assignment migrasi.
         */
        $effectiveMusyrifId =
            $toMusyrifId ?? $santri->musyrif_id;

        if ($effectiveMusyrifId === null) {
            throw ValidationException::withMessages([
                $errorKey => [
                    "Santri {$santri->nama} belum memiliki musyrif. Pilih musyrif melalui mapping individual.",
                ],
            ]);
        }

        return (int) $effectiveMusyrifId;
    }

    private function saveSourceSnapshot(
        Santri $santri,
        Semester $fromSemester,
        int $userId
    ): void {
        SantriKelasHistory::query()->firstOrCreate(
            [
                'santri_id' => $santri->id,
                'semester_id' => $fromSemester->id,
            ],
            [
                'kelas_id' => $santri->kelas_id,
                'musyrif_id' => $santri->musyrif_id,
                'tipe' => 'penempatan',
                'catatan' => 'Snapshot otomatis sebelum migrasi semester.',
                'created_by' => $userId,
            ]
        );
    }

    private function applyTransition(
        Santri $santri,
        Semester $toSemester,
        string $tipe,
        ?int $toKelasId,
        ?int $toMusyrifId,
        ?string $catatan,
        int $userId,
        string $errorKey = 'to_musyrif_id'
    ): void {
        $this->validateClassTransition(
            $tipe,
            $santri->kelas_id,
            $toKelasId
        );

        if ($tipe === 'lulus') {
            SantriKelasHistory::query()->updateOrCreate(
                [
                    'santri_id' => $santri->id,
                    'semester_id' => $toSemester->id,
                ],
                [
                    'kelas_id' => $santri->kelas_id,
                    'musyrif_id' => null,
                    'tipe' => 'lulus',
                    'catatan' => $catatan,
                    'created_by' => $userId,
                ]
            );

            $santri->markAsGraduated(
                $toSemester
            );

            return;
        }

        $effectiveMusyrifId =
            $this->resolveEffectiveMusyrifId(
                $santri,
                $tipe,
                $toKelasId,
                $toMusyrifId,
                $errorKey
            );

        SantriKelasHistory::query()->updateOrCreate(
            [
                'santri_id' => $santri->id,
                'semester_id' => $toSemester->id,
            ],
            [
                'kelas_id' => $toKelasId,
                'musyrif_id' => $effectiveMusyrifId,
                'tipe' => $tipe,
                'catatan' => $catatan,
                'created_by' => $userId,
            ]
        );

        $santri->update([
            'kelas_id' => $toKelasId,
            'musyrif_id' => $effectiveMusyrifId,
        ]);
    }

    /**
     * Mengubah konfigurasi nama kelas menjadi mapping berbasis ID.
     *
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     missing: array<int, array<string, mixed>>,
     *     mapping_by_from_id: array<int, array<string, mixed>>,
     *     source_class_ids: array<int, int>,
     *     target_class_ids: array<int, int>
     * }
     */
    private function resolveAutoMappingContext(
        bool $includeGraduation
    ): array {
        $mapping = $this->getAutoKelasMapping();

        $classNames = array_values(
            array_unique(
                array_merge(
                    array_keys($mapping),
                    array_values(
                        array_filter($mapping)
                    )
                )
            )
        );

        $classes = Kelas::query()
            ->whereIn(
                'nama_kelas',
                $classNames
            )
            ->get([
                'id',
                'nama_kelas',
            ])
            ->keyBy('nama_kelas');

        $rows = [];
        $missing = [];
        $mappingByFromId = [];
        $sourceClassIds = [];
        $targetClassIds = [];

        foreach ($mapping as $fromName => $toName) {
            if (
                $toName === null
                && !$includeGraduation
            ) {
                continue;
            }

            $fromClass = $classes->get($fromName);
            $toClass = $toName !== null
                ? $classes->get($toName)
                : null;

            $status = (
                !$fromClass
                || (
                    $toName !== null
                    && !$toClass
                )
            )
                ? 'MISSING_KELAS'
                : 'OK';

            $row = [
                'mapping_key' =>
                $fromName . '->' . ($toName ?? 'LULUS'),
                'from_id' => $fromClass?->id,
                'from_nama' => $fromName,
                'to_id' => $toClass?->id,
                'to_nama' => $toName ?? 'LULUS',
                'tipe' => $toName !== null
                    ? 'naik_kelas'
                    : 'lulus',
                'status' => $status,
            ];

            $rows[] = $row;

            if ($status !== 'OK') {
                $missing[] = $row;
                continue;
            }

            $fromId = (int) $fromClass->id;
            $toId = $toClass
                ? (int) $toClass->id
                : null;

            $mappingByFromId[$fromId] = [
                ...$row,
                'from_id' => $fromId,
                'to_id' => $toId,
            ];

            $sourceClassIds[] = $fromId;

            if ($toId !== null) {
                $targetClassIds[] = $toId;
            }
        }

        return [
            'rows' => $rows,
            'missing' => $missing,
            'mapping_by_from_id' => $mappingByFromId,
            'source_class_ids' => array_values(
                array_unique($sourceClassIds)
            ),
            'target_class_ids' => array_values(
                array_unique($targetClassIds)
            ),
        ];
    }

    /**
     * Membentuk snapshot dari posisi kelas asli.
     *
     * Snapshot harus dibuat sebelum ada perubahan kelas satu pun.
     */
    private function buildAutoSnapshot(
        Collection $santris,
        array $mappingByFromId
    ): Collection {
        return $santris
            ->map(function (Santri $santri) use (
                $mappingByFromId
            ) {
                $fromClassId =
                    (int) $santri->kelas_id;

                $mapping =
                    $mappingByFromId[$fromClassId]
                    ?? null;

                if (!$mapping) {
                    throw ValidationException::withMessages([
                        'auto_mapping' => [
                            "Kelas asal santri {$santri->nama} tidak memiliki mapping otomatis.",
                        ],
                    ]);
                }

                return [
                    'santri_id' => (int) $santri->id,
                    'nama' => $santri->nama,
                    'nis' => $santri->nis,
                    'from_kelas_id' => $fromClassId,
                    'from_kelas_nama' =>
                    $mapping['from_nama'],
                    'from_musyrif_id' =>
                    $santri->musyrif_id
                        ? (int) $santri->musyrif_id
                        : null,
                    'from_musyrif_nama' =>
                    $santri->musyrif?->nama,
                    'from_musyrif_kode' =>
                    $santri->musyrif?->kode,
                    'to_kelas_id' =>
                    $mapping['to_id'],
                    'to_kelas_nama' =>
                    $mapping['to_nama'],
                    'tipe' =>
                    $mapping['tipe'],
                    'mapping_key' =>
                    $mapping['mapping_key'],
                ];
            })
            ->sortBy('santri_id')
            ->values();
    }

    private function makeAutoSnapshotHash(
        Collection $snapshot,
        int $fromSemesterId,
        int $toSemesterId,
        bool $includeGraduation
    ): string {
        $hashPayload = [
            'from_semester_id' => $fromSemesterId,
            'to_semester_id' => $toSemesterId,
            'include_graduation' => $includeGraduation,
            'items' => $snapshot
                ->map(
                    fn(array $item) => [
                        'santri_id' =>
                        $item['santri_id'],
                        'from_kelas_id' =>
                        $item['from_kelas_id'],
                        'from_musyrif_id' =>
                        $item['from_musyrif_id'],
                        'to_kelas_id' =>
                        $item['to_kelas_id'],
                        'tipe' =>
                        $item['tipe'],
                    ]
                )
                ->values()
                ->all(),
        ];

        return hash(
            'sha256',
            json_encode(
                $hashPayload,
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
            )
        );
    }

    private function validateAutoExecutePayload(
        Request $request
    ): array {
        return $request->validate([
            'from_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:to_semester_id',
            ],
            'to_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:from_semester_id',
            ],
            'include_graduation' => [
                'nullable',
                'boolean',
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'snapshot_hash' => [
                'required',
                'string',
                'size:64',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.santri_id' => [
                'required',
                'integer',
                'distinct',
                'exists:santris,id',
            ],
            'items.*.from_kelas_id' => [
                'required',
                'integer',
                'exists:kelas,id',
            ],
            'items.*.to_kelas_id' => [
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
            'items.*.tipe' => [
                'required',
                Rule::in([
                    'naik_kelas',
                    'lulus',
                ]),
            ],
            'items.*.to_musyrif_id' => [
                'nullable',
                'integer',
                'exists:musyrifs,id',
            ],
        ]);
    }

    private function assertAutoSubmissionMatchesSnapshot(
        Collection $snapshot,
        Collection $submittedItems
    ): void {
        $expectedIds = $snapshot
            ->pluck('santri_id')
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values();

        $submittedIds = $submittedItems
            ->pluck('santri_id')
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values();

        if ($expectedIds->all() !== $submittedIds->all()) {
            throw ValidationException::withMessages([
                'items' => [
                    'Daftar santri berubah setelah Auto Preview. Jalankan Auto Preview ulang.',
                ],
            ]);
        }

        $snapshotBySantri = $snapshot
            ->keyBy('santri_id');

        foreach (
            $submittedItems->values()
            as $index => $submitted
        ) {
            $santriId =
                (int) $submitted['santri_id'];

            $expected =
                $snapshotBySantri->get($santriId);

            if (!$expected) {
                throw ValidationException::withMessages([
                    "items.{$index}.santri_id" => [
                        'Santri tidak termasuk dalam snapshot Auto-Mapping.',
                    ],
                ]);
            }

            $submittedFromClassId =
                (int) $submitted['from_kelas_id'];

            $submittedToClassId = isset(
                $submitted['to_kelas_id']
            )
                ? (int) $submitted['to_kelas_id']
                : null;

            if (
                $submittedFromClassId
                !== (int) $expected['from_kelas_id']
                || $submittedToClassId
                !== $expected['to_kelas_id']
                || $submitted['tipe']
                !== $expected['tipe']
            ) {
                throw ValidationException::withMessages([
                    "items.{$index}" => [
                        "Mapping kelas untuk {$expected['nama']} tidak sesuai hasil Auto Preview.",
                    ],
                ]);
            }
        }
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
        $kelasList = Kelas::query()
            ->orderBy('nama_kelas')
            ->get();

        $semesterAktif = Semester::query()
            ->with('tahunAjaran')
            ->active()
            ->first();

        $semesterTujuanList = Semester::query()
            ->with('tahunAjaran')
            ->draft()
            ->orderByDesc('id')
            ->get();

        return view(
            'admin.santri.naik-kelas-massal',
            compact(
                'kelasList',
                'semesterAktif',
                'semesterTujuanList'
            )
        );
    }

    public function byKelas(Request $request)
    {
        $data = $request->validate([
            'kelas_id' => [
                'required',
                'integer',
                'exists:kelas,id',
            ],
        ]);

        $santris = Santri::query()
            ->active()
            ->where(
                'kelas_id',
                $data['kelas_id']
            )
            ->orderBy('nama')
            ->get([
                'id',
                'nama',
                'nis',
                'kelas_id',
                'musyrif_id',
                'status',
            ]);

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

        [$fromSemester, $toSemester] =
            $this->resolveSemesterTransition(
                (int) $data['from_semester_id'],
                (int) $data['to_semester_id']
            );

        $santriIds = collect($data['items'])
            ->pluck('santri_id')
            ->unique()
            ->values();

        $santris = Santri::query()
            ->active()
            ->with([
                'kelas',
                'musyrif',
            ])
            ->whereIn(
                'id',
                $santriIds
            )
            ->get()
            ->keyBy('id');

        $missing = $santriIds->diff(
            $santris->keys()
        );

        $rows = collect($data['items'])
            ->values()
            ->map(function (
                array $item,
                int $index
            ) use ($santris) {
                $santri = $santris->get(
                    $item['santri_id']
                );

                $tipe =
                    $item['tipe'] ?? 'naik_kelas';

                $toKelasId = isset(
                    $item['to_kelas_id']
                )
                    ? (int) $item['to_kelas_id']
                    : null;

                $toMusyrifId = isset(
                    $item['to_musyrif_id']
                )
                    ? (int) $item['to_musyrif_id']
                    : null;

                $effectiveMusyrifId = null;

                if ($santri) {
                    $this->validateClassTransition(
                        $tipe,
                        $santri->kelas_id,
                        $toKelasId
                    );

                    $effectiveMusyrifId =
                        $this->resolveEffectiveMusyrifId(
                            $santri,
                            $tipe,
                            $toKelasId,
                            $toMusyrifId,
                            "items.{$index}.to_musyrif_id"
                        );
                }

                return [
                    'santri_id' => $item['santri_id'],
                    'nama' => $santri?->nama,
                    'nis' => $santri?->nis,
                    'status_santri' => $santri?->status,
                    'kelas_sekarang_id' => $santri?->kelas_id,
                    'musyrif_sekarang_id' => $santri?->musyrif_id,
                    'to_kelas_id' =>
                    $tipe === 'lulus'
                        ? null
                        : $toKelasId,
                    'to_musyrif_id' =>
                    $tipe === 'lulus'
                        ? null
                        : $effectiveMusyrifId,
                    'tipe' => $tipe,
                ];
            });

        return response()->json([
            'ok' => $missing->isEmpty(),
            'from_semester' => [
                'id' => $fromSemester->id,
                'nama' => $fromSemester->nama,
                'status' => $fromSemester->status,
            ],
            'to_semester' => [
                'id' => $toSemester->id,
                'nama' => $toSemester->nama,
                'status' => $toSemester->status,
            ],
            'missing_or_inactive_santri_ids' =>
            $missing->values(),
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

        [$fromSemester, $toSemester] =
            $this->resolveSemesterTransition(
                (int) $data['from_semester_id'],
                (int) $data['to_semester_id'],
                true
            );

        $userId = (int) auth()->id();

        $result = DB::transaction(function () use (
            $data,
            $fromSemester,
            $toSemester,
            $userId
        ) {
            $items = collect($data['items']);

            $santriIds = $items
                ->pluck('santri_id')
                ->unique()
                ->values();

            $santris = Santri::query()
                ->active()
                ->whereIn(
                    'id',
                    $santriIds
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $missing = $santriIds->diff(
                $santris->keys()
            );

            if ($missing->isNotEmpty()) {
                return [
                    'ok' => false,
                    'message' =>
                    'Ada santri yang tidak ditemukan atau sudah tidak aktif.',
                    'missing_or_inactive_santri_ids' =>
                    $missing->values()->all(),
                ];
            }

            $affected = 0;
            $graduated = 0;

            foreach ($items as $item) {
                /** @var \App\Models\Santri $santri */
                $santri = $santris->get(
                    $item['santri_id']
                );

                $tipe =
                    $item['tipe'] ?? 'naik_kelas';

                $toKelasId = isset(
                    $item['to_kelas_id']
                )
                    ? (int) $item['to_kelas_id']
                    : null;

                $toMusyrifId = isset(
                    $item['to_musyrif_id']
                )
                    ? (int) $item['to_musyrif_id']
                    : null;

                $catatan =
                    $item['catatan'] ?? null;

                $this->saveSourceSnapshot(
                    $santri,
                    $fromSemester,
                    $userId
                );

                $this->applyTransition(
                    $santri,
                    $toSemester,
                    $tipe,
                    $toKelasId,
                    $toMusyrifId,
                    $catatan,
                    $userId
                );

                $affected++;

                if ($tipe === 'lulus') {
                    $graduated++;
                }
            }

            return [
                'ok' => true,
                'affected' => $affected,
                'graduated' => $graduated,
                'from_semester_id' => $fromSemester->id,
                'to_semester_id' => $toSemester->id,
            ];
        });

        if (!$result['ok']) {
            return response()->json(
                $result,
                422
            );
        }

        return response()->json([
            'ok' => true,
            'message' =>
            "Berhasil memproses {$result['affected']} santri. "
                . "Santri lulus: {$result['graduated']}.",
            'data' => $result,
        ]);
    }

    public function previewMassal(Request $request)
    {
        $data = $this->validatePreviewMassalPayload($request);

        [$fromSemester, $toSemester] = $this->resolveSemesterTransition(
            (int) $data['from_semester_id'],
            (int) $data['to_semester_id']
        );

        $fromKelasId = (int) $data['from_kelas_id'];
        $tipe = $data['tipe'] ?? 'naik_kelas';
        $toKelasId = isset($data['to_kelas_id'])
            ? (int) $data['to_kelas_id']
            : null;

        $this->validateClassTransition(
            $tipe,
            $fromKelasId,
            $toKelasId
        );

        $fromKelas = Kelas::query()->findOrFail($fromKelasId);
        $toKelas = $toKelasId !== null
            ? Kelas::query()->findOrFail($toKelasId)
            : null;

        $santris = Santri::query()
            ->active()
            ->with(['musyrif:id,nama,kode,kelas_id'])
            ->where('kelas_id', $fromKelasId)
            ->orderBy('nama')
            ->get([
                'id',
                'nama',
                'nis',
                'kelas_id',
                'musyrif_id',
                'status',
            ]);

        $plans = $santris->map(function (Santri $santri) use (
            $tipe,
            $toKelasId,
            $fromKelas,
            $toKelas
        ) {
            return [
                'santri_id' => (int) $santri->id,
                'from_kelas_id' => (int) $santri->kelas_id,
                'to_kelas_id' => $tipe === 'lulus'
                    ? null
                    : $toKelasId,
                'from_musyrif_id' => $santri->musyrif_id !== null
                    ? (int) $santri->musyrif_id
                    : null,
                'transition_type' => $tipe,
                'assignment_required' =>
                $tipe !== 'lulus'
                    && $santri->musyrif_id === null,
                'source_snapshot' => [
                    'santri_id' => (int) $santri->id,
                    'nama' => $santri->nama,
                    'nis' => $santri->nis,
                    'status' => $santri->status,
                    'kelas_id' => (int) $santri->kelas_id,
                    'kelas_nama' => $fromKelas->nama_kelas,
                    'musyrif_id' => $santri->musyrif_id,
                    'musyrif_nama' => $santri->musyrif?->nama,
                    'musyrif_kode' => $santri->musyrif?->kode,
                ],
                'target_snapshot' => [
                    'kelas_id' => $tipe === 'lulus'
                        ? null
                        : $toKelasId,
                    'kelas_nama' => $tipe === 'lulus'
                        ? 'LULUS'
                        : $toKelas?->nama_kelas,
                    'tipe' => $tipe,
                ],
            ];
        });

        $batch = $this->createPersistentBatch(
            SantriMigrationBatch::MODE_MANUAL,
            $fromSemester,
            $toSemester,
            $plans,
            [
                'from_kelas_id' => $fromKelasId,
                'to_kelas_id' => $tipe === 'lulus'
                    ? null
                    : $toKelasId,
                'transition_type' => $tipe,
                'note' => $data['catatan'] ?? null,
                'metadata' => [
                    'source' => 'manual_massal_preview',
                ],
            ]
        );

        $batchItems = $batch->items->keyBy('santri_id');

        $santriRows = $santris->map(function (Santri $santri) use ($batchItems) {
            $batchItem = $batchItems->get((int) $santri->id);

            return [
                'id' => $santri->id,
                'batch_item_id' => $batchItem?->id,
                'nama' => $santri->nama,
                'nis' => $santri->nis,
                'kelas_id' => $santri->kelas_id,
                'musyrif_id' => $santri->musyrif_id,
                'musyrif_nama' => $santri->musyrif?->nama,
                'musyrif_kode' => $santri->musyrif?->kode,
                'status' => $santri->status,
                'assignment_required' => (bool) $batchItem?->assignment_required,
            ];
        })->values();

        $targetMusyrifs = collect();

        if ($tipe !== 'lulus' && $toKelasId !== null) {
            $targetMusyrifs = Musyrif::query()
                ->leftJoin(
                    'kelas',
                    'kelas.id',
                    '=',
                    'musyrifs.kelas_id'
                )
                ->orderBy('musyrifs.nama')
                ->get([
                    'musyrifs.id',
                    'musyrifs.nama',
                    'musyrifs.kode',
                    'musyrifs.kelas_id',
                    'kelas.nama_kelas as kelas_nama',
                ]);
        }

        return response()->json([
            'ok' => true,
            'message' => "Batch {$batch->code} berhasil dibuat.",
            'batch' => $this->serializeBatch($batch),
            'from_semester' => [
                'id' => $fromSemester->id,
                'nama' => $fromSemester->nama,
                'status' => $fromSemester->status,
            ],
            'to_semester' => [
                'id' => $toSemester->id,
                'nama' => $toSemester->nama,
                'status' => $toSemester->status,
            ],
            'from_kelas_id' => $fromKelasId,
            'to_kelas_id' => $tipe === 'lulus' ? null : $toKelasId,
            'tipe' => $tipe,
            'count' => $santriRows->count(),
            'santris' => $santriRows,
            'target_musyrifs' => $targetMusyrifs->values(),
        ]);
    }

    public function executeMassal(Request $request)
    {
        return $this->executePersistentBatch(
            $request,
            SantriMigrationBatch::MODE_MANUAL
        );
    }

    public function previewAutoMapping(Request $request)
    {
        $data = $request->validate([
            'from_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:to_semester_id',
            ],
            'to_semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
                'different:from_semester_id',
            ],
            'include_graduation' => [
                'required',
                'boolean',
            ],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        [$fromSemester, $toSemester] = $this->resolveSemesterTransition(
            (int) $data['from_semester_id'],
            (int) $data['to_semester_id']
        );

        /*
         * Kelulusan tidak memiliki default implisit.
         * UI wajib mengirim keputusan Admin secara eksplisit.
         */
        $includeGraduation =
            (bool) $data['include_graduation'];
        $context = $this->resolveAutoMappingContext($includeGraduation);

        if ($context['missing'] !== []) {
            return response()->json([
                'ok' => false,
                'message' => 'Terdapat kelas mapping yang belum tersedia.',
                'missing' => $context['missing'],
                'rows' => $context['rows'],
                'total_santri_affected' => 0,
            ], 422);
        }

        $santris = Santri::query()
            ->active()
            ->with(['musyrif:id,nama,kode,kelas_id'])
            ->whereIn('kelas_id', $context['source_class_ids'])
            ->orderBy('id')
            ->get([
                'id',
                'nama',
                'nis',
                'kelas_id',
                'musyrif_id',
                'status',
            ]);

        $snapshot = $this->buildAutoSnapshot(
            $santris,
            $context['mapping_by_from_id']
        );

        $plans = $snapshot->map(function (array $item) {
            return [
                'santri_id' => $item['santri_id'],
                'from_kelas_id' => $item['from_kelas_id'],
                'to_kelas_id' => $item['to_kelas_id'],
                'from_musyrif_id' => $item['from_musyrif_id'],
                'transition_type' => $item['tipe'],
                'assignment_required' =>
                $item['tipe'] !== 'lulus'
                    && $item['from_musyrif_id'] === null,
                'source_snapshot' => [
                    'santri_id' => $item['santri_id'],
                    'nama' => $item['nama'],
                    'nis' => $item['nis'],
                    'status' => Santri::STATUS_AKTIF,
                    'kelas_id' => $item['from_kelas_id'],
                    'kelas_nama' => $item['from_kelas_nama'],
                    'musyrif_id' => $item['from_musyrif_id'],
                    'musyrif_nama' => $item['from_musyrif_nama'],
                    'musyrif_kode' => $item['from_musyrif_kode'],
                ],
                'target_snapshot' => [
                    'kelas_id' => $item['to_kelas_id'],
                    'kelas_nama' => $item['to_kelas_nama'],
                    'tipe' => $item['tipe'],
                    'mapping_key' => $item['mapping_key'],
                ],
            ];
        });

        $batch = $this->createPersistentBatch(
            SantriMigrationBatch::MODE_AUTO,
            $fromSemester,
            $toSemester,
            $plans,
            [
                'include_graduation' => $includeGraduation,
                'note' => $data['catatan'] ?? null,
                'metadata' => [
                    'source' => 'auto_mapping_preview',
                    'mapping_rows' => $context['rows'],
                ],
            ]
        );

        $batchItemsBySantri = $batch->items->keyBy('santri_id');

        $snapshot = $snapshot->map(function (array $item) use ($batchItemsBySantri) {
            $batchItem = $batchItemsBySantri->get((int) $item['santri_id']);

            return [
                ...$item,
                'batch_item_id' => $batchItem?->id,
                'assignment_required' => (bool) $batchItem?->assignment_required,
            ];
        });

        $targetMusyrifs = Musyrif::query()
            ->leftJoin(
                'kelas',
                'kelas.id',
                '=',
                'musyrifs.kelas_id'
            )
            ->orderBy('musyrifs.nama')
            ->get([
                'musyrifs.id',
                'musyrifs.nama',
                'musyrifs.kode',
                'musyrifs.kelas_id',
                'kelas.nama_kelas as kelas_nama',
            ]);

        $snapshotByMapping = $snapshot->groupBy('mapping_key');

        $rows = collect($context['rows'])->map(function (array $row) use (
            $snapshotByMapping,
            $targetMusyrifs
        ) {
            $santriRows = $snapshotByMapping
                ->get($row['mapping_key'], collect())
                ->values();

            return [
                ...$row,
                'count_santri' => $santriRows->count(),
                'santris' => $santriRows,
                'target_musyrifs' => $row['to_id'] !== null
                    ? $targetMusyrifs->values()
                    : collect(),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'message' => "Batch {$batch->code} berhasil dibuat.",
            'batch' => $this->serializeBatch($batch),
            'from_semester' => [
                'id' => $fromSemester->id,
                'nama' => $fromSemester->nama,
                'status' => $fromSemester->status,
            ],
            'to_semester' => [
                'id' => $toSemester->id,
                'nama' => $toSemester->nama,
                'status' => $toSemester->status,
            ],
            'include_graduation' => $includeGraduation,
            'snapshot_count' => $snapshot->count(),
            'rows' => $rows,
            'total_santri_affected' => $snapshot->count(),
            'total_graduation' => $snapshot->where('tipe', 'lulus')->count(),
        ]);
    }

    public function executeAutoMapping(Request $request)
    {
        return $this->executePersistentBatch(
            $request,
            SantriMigrationBatch::MODE_AUTO
        );
    }



    public function batches(Request $request)
    {
        $data = $request->validate([
            'mode' => [
                'nullable',
                Rule::in([
                    SantriMigrationBatch::MODE_MANUAL,
                    SantriMigrationBatch::MODE_AUTO,
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    SantriMigrationBatch::STATUS_PREVIEWED,
                    SantriMigrationBatch::STATUS_EXECUTING,
                    SantriMigrationBatch::STATUS_COMPLETED,
                    SantriMigrationBatch::STATUS_FAILED,
                    SantriMigrationBatch::STATUS_CANCELLED,
                    SantriMigrationBatch::STATUS_EXPIRED,
                ]),
            ],
        ]);

        $query = SantriMigrationBatch::query()
            ->with([
                'fromSemester:id,nama',
                'toSemester:id,nama',
                'creator:id,name',
                'executor:id,name',
            ])
            ->latest('created_at');

        if (!empty($data['mode'])) {
            $query->where('mode', $data['mode']);
        }

        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        return response()->json($query->paginate(25));
    }

    public function showBatch(SantriMigrationBatch $batch)
    {
        $batch->load([
            'fromSemester:id,nama',
            'toSemester:id,nama',
            'fromKelas:id,nama_kelas',
            'toKelas:id,nama_kelas',
            'creator:id,name',
            'executor:id,name',
            'items' => fn($query) => $query->orderBy('id'),
        ]);

        return response()->json([
            'ok' => true,
            'batch' => $batch,
        ]);
    }
}
