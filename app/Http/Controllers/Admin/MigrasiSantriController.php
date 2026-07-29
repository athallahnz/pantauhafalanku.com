<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\Musyrif;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\SantriKelasHistory;
use App\Models\SantriMigrationBatch;
use App\Models\SantriSemesterPlacement;
use App\Support\Academic\HandlesSantriMigrationBatches;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MigrasiSantriController extends Controller
{
    use HandlesSantriMigrationBatches;

    private function normalizeJenisKelamin(mixed $value): ?string
    {
        $normalized = mb_strtolower(
            trim((string) $value)
        );

        return match ($normalized) {
            'l',
            'lk',
            'laki-laki',
            'laki laki',
            'male',
            'putra' => 'L',

            'p',
            'pr',
            'perempuan',
            'female',
            'putri' => 'P',

            default => null,
        };
    }

    private function assertClassAcceptsGender(
        Kelas $kelas,
        mixed $gender,
        string $errorKey,
        string $label
    ): void {
        $normalized = $this->normalizeJenisKelamin($gender);

        if ($normalized === null || $kelas->acceptsGender($normalized)) {
            return;
        }

        $genderLabel = $normalized === 'L' ? 'Putra' : 'Putri';

        throw ValidationException::withMessages([
            $errorKey => [
                "{$label} {$kelas->nama_kelas} tidak menerima santri {$genderLabel}. Atur jenis kelamin kelas melalui Data Akademik.",
            ],
        ]);
    }

    private function assertStudentsCompatibleWithTargetClass(
        Collection $santris,
        ?Kelas $targetClass,
        string $errorKey = 'to_kelas_id'
    ): void {
        if (!$targetClass) {
            return;
        }

        $incompatible = $santris
            ->filter(
                fn(Santri $santri): bool =>
                !$targetClass->acceptsGender(
                    $this->normalizeJenisKelamin($santri->jenis_kelamin)
                )
            )
            ->values();

        if ($incompatible->isEmpty()) {
            return;
        }

        $sample = $incompatible
            ->take(3)
            ->pluck('nama')
            ->implode(', ');

        throw ValidationException::withMessages([
            $errorKey => [
                "Kelas tujuan {$targetClass->nama_kelas} tidak kompatibel dengan {$incompatible->count()} santri terpilih"
                    . ($sample !== '' ? " ({$sample})" : '')
                    . '. Pisahkan proses berdasarkan jenis kelamin atau ubah metadata kelas.',
            ],
        ]);
    }

    /**
     * Menerima variasi data lama agar filter tetap kompatibel
     * dengan record yang belum seluruhnya memakai kode L/P.
     *
     * @return array<int, string>
     */
    private function jenisKelaminDatabaseValues(
        string $jenisKelamin
    ): array {
        return $jenisKelamin === 'L'
            ? [
                'L',
                'l',
                'LK',
                'lk',
                'Laki-laki',
                'laki-laki',
                'Laki Laki',
                'laki laki',
                'Male',
                'male',
                'Putra',
                'putra',
            ]
            : [
                'P',
                'p',
                'PR',
                'pr',
                'Perempuan',
                'perempuan',
                'Female',
                'female',
                'Putri',
                'putri',
            ];
    }

    /**
     * @param Collection<int, Santri|array<string, mixed>> $rows
     * @return array{L:int,P:int,unknown:int}
     */
    private function countJenisKelamin(
        Collection $rows,
        string $key = 'jenis_kelamin'
    ): array {
        $counts = [
            'L' => 0,
            'P' => 0,
            'unknown' => 0,
        ];

        foreach ($rows as $row) {
            $value = $row instanceof Santri
                ? $row->jenis_kelamin
                : data_get($row, $key);

            $gender = $this->normalizeJenisKelamin(
                $value
            );

            if ($gender === 'L') {
                $counts['L']++;
            } elseif ($gender === 'P') {
                $counts['P']++;
            } else {
                $counts['unknown']++;
            }
        }

        return $counts;
    }

    /**
     * @param array{L:int,P:int,unknown:int} $counts
     */
    private function resolveBatchJenisKelamin(
        array $counts,
        ?string $requestedGender = null
    ): ?string {
        if ($requestedGender !== null) {
            return $requestedGender;
        }

        if ($counts['L'] > 0 && $counts['P'] > 0) {
            return 'MIXED';
        }

        if ($counts['L'] > 0) {
            return 'L';
        }

        if ($counts['P'] > 0) {
            return 'P';
        }

        return null;
    }


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
            'jenis_kelamin' => [
                'nullable',
                Rule::in([
                    'L',
                    'P',
                ]),
            ],
            'tipe' => [
                'nullable',
                Rule::in([
                    'penempatan',
                    'mutasi',
                    'naik_kelas',
                    'tinggal_kelas',
                    'lulus',
                    'keluar',
                ]),
            ],
            'catatan' => [
                Rule::requiredIf(
                    fn() => $request->input('tipe') === 'keluar'
                ),
                'nullable',
                'string',
                'max:1000',
            ],
            'exit_reason_code' => [
                Rule::requiredIf(
                    fn() => $request->input('tipe') === 'keluar'
                ),
                'nullable',
                Rule::in([
                    'pindah_sekolah',
                    'mengundurkan_diri',
                    'dikeluarkan',
                    'tidak_melanjutkan',
                    'alasan_keluarga',
                    'kesehatan',
                    'lainnya',
                ]),
            ],
            'exit_effective_at' => [
                Rule::requiredIf(
                    fn() => $request->input('tipe') === 'keluar'
                ),
                'nullable',
                'date',
            ],
            'exit_destination' => ['nullable', 'string', 'max:255'],
            'exit_document_number' => ['nullable', 'string', 'max:100'],
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
                    'keluar',
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
        ?int $toKelasId,
        bool $preserveGroup = false
    ): void {
        $fromKelas = $this->operationalClassOrFail(
            $fromKelasId,
            'from_kelas_id',
            'Kelas asal'
        );

        if (in_array($tipe, ['lulus', 'keluar'], true)) {
            return;
        }

        $toKelas = $this->operationalClassOrFail(
            $toKelasId,
            'to_kelas_id',
            'Kelas tujuan'
        );

        if (
            $tipe === 'tinggal_kelas'
            && (int) $fromKelas->id !== (int) $toKelas->id
        ) {
            throw ValidationException::withMessages([
                'to_kelas_id' => [
                    'Proses tinggal kelas harus menggunakan kelompok kelas yang sama dengan kelas asal.',
                ],
            ]);
        }

        if (
            in_array($tipe, ['naik_kelas', 'mutasi'], true)
            && (int) $fromKelas->id === (int) $toKelas->id
        ) {
            throw ValidationException::withMessages([
                'to_kelas_id' => [
                    'Kelas tujuan harus berbeda dari kelas asal untuk kenaikan kelas atau mutasi.',
                ],
            ]);
        }

        if ($tipe !== 'naik_kelas') {
            return;
        }

        $sourceParent = $fromKelas->parent;
        $targetParent = $toKelas->parent;
        $allowedTargetKeys = $this->allowedTargetParentKeys(
            $sourceParent
        );
        $targetKey = $this->canonicalParentKey(
            $targetParent
        );

        if (
            $targetKey === null
            || !in_array($targetKey, $allowedTargetKeys, true)
        ) {
            throw ValidationException::withMessages([
                'to_kelas_id' => [
                    "{$toKelas->nama_kelas} bukan jalur kenaikan yang diizinkan dari {$fromKelas->nama_kelas}.",
                ],
            ]);
        }

        /*
         * preserve_group hanya untuk Auto-Mapping.
         *
         * Migrasi manual/massal berbasis checkbox sengaja boleh memilih
         * kelompok tujuan lain selama parent/tingkat tujuan merupakan jalur
         * kenaikan yang sah. Dengan begitu sebagian santri dapat diproses ke
         * kelompok 1 dan sisanya dieksekusi kemudian ke kelompok 2.
         *
         * Auto-Mapping tetap mempertahankan urutan kelompok melalui
         * buildAutoMappingSnapshot(), yang memanggil mapTargetGroup() ketika
         * menentukan target child.
         */
        if (
            $preserveGroup
            && config('kelas_transition.preserve_group', true)
        ) {
            $expectedGroup = $this->mapTargetGroup(
                $fromKelas,
                $targetParent
            );

            if (
                strtoupper($expectedGroup)
                !== strtoupper((string) $toKelas->kelompok)
            ) {
                throw ValidationException::withMessages([
                    'to_kelas_id' => [
                        "Kelompok tujuan yang benar adalah {$targetParent->nama_kelas} {$expectedGroup}.",
                    ],
                ]);
            }
        }
    }

    private function mapTargetGroup(
        Kelas $sourceChild,
        Kelas $targetParent
    ): string {
        $sourceGroup = strtoupper(trim((string) $sourceChild->kelompok));
        $sourceParent = $sourceChild->parent;
        $sourceMode = $sourceParent?->groupMode() ?? 'alpha';
        $targetMode = $targetParent->groupMode();

        if ($sourceMode === $targetMode) {
            return $sourceGroup;
        }

        if (
            $sourceMode === 'alpha'
            && $targetMode === 'numeric'
            && preg_match('/^[A-Z]$/', $sourceGroup)
        ) {
            return (string) (ord($sourceGroup) - 64);
        }

        if (
            $sourceMode === 'numeric'
            && $targetMode === 'alpha'
            && preg_match('/^[1-9][0-9]*$/', $sourceGroup)
        ) {
            $number = (int) $sourceGroup;

            if ($number >= 1 && $number <= 26) {
                return chr(64 + $number);
            }
        }

        return $sourceGroup;
    }

    private function resolveEffectiveMusyrifId(
        Santri $santri,
        string $tipe,
        ?int $toKelasId,
        ?int $toMusyrifId,
        string $errorKey = 'to_musyrif_id'
    ): ?int {
        if (in_array($tipe, ['lulus', 'keluar'], true)) {
            return null;
        }

        $targetClass = $this->operationalClassOrFail(
            $toKelasId,
            'to_kelas_id',
            'Kelas tujuan'
        );

        $effectiveMusyrifId = $toMusyrifId ?? $santri->musyrif_id;

        if ($effectiveMusyrifId === null) {
            throw ValidationException::withMessages([
                $errorKey => [
                    "Santri {$santri->nama} belum memiliki musyrif tujuan.",
                ],
            ]);
        }

        $musyrif = Musyrif::query()
            ->with([
                'kelasInduk:id,nama_kelas',
                'kelasBinaan:id,nama_kelas,parent_id',
            ])
            ->select([
                'id',
                'nama',
                'kelas_induk_id',
                'kelas_id',
            ])
            ->find($effectiveMusyrifId);

        if (!$musyrif) {
            throw ValidationException::withMessages([
                $errorKey => ['Musyrif tujuan tidak ditemukan.'],
            ]);
        }

        $targetParentId = (int) $targetClass->parent_id;

        if (!$musyrif->handlesKelasInduk($targetParentId)) {
            throw ValidationException::withMessages([
                $errorKey => [
                    "Musyrif {$musyrif->nama} tidak berada pada tingkat utama {$targetClass->parent?->nama_kelas}. Pilih Musyrif dengan kelas_induk_id yang sama.",
                ],
            ]);
        }

        /*
         * Pivot tetap menjadi sumber kelas binaan. Penempatan santri pada child
         * baru otomatis memperluas pivot tanpa menghapus kelas binaan lainnya.
         */
        $musyrif->ensureKelasBinaan((int) $targetClass->id);

        return (int) $musyrif->id;
    }

    private function operationalClassOrFail(
        ?int $kelasId,
        string $errorKey,
        string $label
    ): Kelas {
        if ($kelasId === null) {
            throw ValidationException::withMessages([
                $errorKey => ["{$label} wajib dipilih."],
            ]);
        }

        $kelas = Kelas::query()
            ->with('parent:id,nama_kelas,is_active,jenis_kelamin')
            ->find($kelasId);

        if (
            !$kelas
            || $kelas->parent_id === null
            || !$kelas->is_active
            || !$kelas->parent
            || !$kelas->parent->is_active
        ) {
            throw ValidationException::withMessages([
                $errorKey => [
                    "{$label} harus berupa kelas kelompok aktif, bukan kelas induk.",
                ],
            ]);
        }

        return $kelas;
    }

    private function canonicalParentKey(?Kelas $parent): ?string
    {
        if (!$parent) {
            return null;
        }

        $code = strtoupper(
            preg_replace('/[^A-Z0-9]/', '', (string) $parent->kode)
        );

        if (preg_match('/^K0*(\d+)(I|INT)$/', $code, $matches)) {
            return sprintf('K%02dI', (int) $matches[1]);
        }

        if (preg_match('/^K0*(\d+)(R|REG)?$/', $code, $matches)) {
            return sprintf('K%02d', (int) $matches[1]);
        }

        $name = strtoupper(
            preg_replace('/\s+/', ' ', trim((string) $parent->nama_kelas))
        );

        if (!preg_match('/KELAS\s*0*(\d+)/', $name, $matches)) {
            return null;
        }

        $isInt = str_contains($name, 'INT');

        return sprintf(
            'K%02d%s',
            (int) $matches[1],
            $isInt ? 'I' : ''
        );
    }

    /** @return array<int, string> */
    private function allowedTargetParentKeys(?Kelas $sourceParent): array
    {
        $sourceKey = $this->canonicalParentKey($sourceParent);

        if ($sourceKey === null) {
            return [];
        }

        $transition = config(
            "kelas_transition.transitions.{$sourceKey}",
            []
        );

        return collect($transition['targets'] ?? [])
            ->map(fn($key) => strtoupper(trim((string) $key)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function parentIndexByTransitionKey(
        Collection $parents
    ): Collection {
        return $parents
            ->mapWithKeys(function (Kelas $parent): array {
                $key = $this->canonicalParentKey($parent);

                return $key !== null
                    ? [$key => $parent]
                    : [];
            });
    }

    /**
     * Dropdown Musyrif mengikuti kelas_induk_id target tanpa filter gender.
     * Musyrif Putra/Putri sama-sama dapat membina santri lintas gender selama
     * penugasannya berada pada tingkat utama tujuan. Pivot tetap dimuat sebagai
     * informasi kelas binaan dan akan ditambah saat eksekusi.
     *
     * @param array<int, int> $kelasIds
     */
    private function targetMusyrifsForClasses(
        array $kelasIds
    ): Collection {
        $kelasIds = collect($kelasIds)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($kelasIds->isEmpty()) {
            return collect();
        }

        $targetClasses = Kelas::query()
            ->with('parent:id,nama_kelas')
            ->whereIn('id', $kelasIds)
            ->get([
                'id',
                'parent_id',
                'nama_kelas',
            ]);

        $parentIds = $targetClasses
            ->pluck('parent_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($parentIds->isEmpty()) {
            return collect();
        }

        return Musyrif::query()
            // Gender hanya dikirim sebagai informasi, bukan syarat penugasan.
            ->whereIn('kelas_induk_id', $parentIds)
            ->with([
                'kelas:id,nama_kelas',
                'kelasInduk:id,nama_kelas',
                'kelasBinaan:id,nama_kelas,parent_id,kelompok',
            ])
            ->orderBy('nama')
            ->get([
                'id',
                'nama',
                'kode',
                'jenis_kelamin',
                'kelas_induk_id',
                'kelas_id',
            ])
            ->map(function (Musyrif $musyrif): Musyrif {
                $musyrif->jenis_kelamin =
                    $this->normalizeJenisKelamin(
                        $musyrif->jenis_kelamin
                    );

                $musyrif->setAttribute(
                    'kelas_ids',
                    $musyrif->allKelasIds()
                );
                $musyrif->setAttribute(
                    'kelas_nama',
                    $musyrif->kelas?->nama_kelas
                );
                $musyrif->setAttribute(
                    'tingkat_utama',
                    $musyrif->kelasInduk?->nama_kelas
                );
                $musyrif->setAttribute(
                    'kelas_binaan_nama',
                    $musyrif->kelasBinaan
                        ->pluck('nama_kelas')
                        ->filter()
                        ->values()
                        ->all()
                );

                return $musyrif;
            });
    }

    private function hierarchySnapshot(
        ?Kelas $kelas
    ): array {
        if (!$kelas) {
            return [
                'kelas_id' => null,
                'kelas_nama' => null,
                'kelas_parent_id' => null,
                'kelas_parent_nama' => null,
                'kelompok' => null,
                'kode' => null,
                'is_operasional' => false,
            ];
        }

        $kelas->loadMissing('parent:id,nama_kelas,is_active');

        return [
            'kelas_id' => (int) $kelas->id,
            'kelas_nama' => (string) $kelas->nama_kelas,
            'kelas_parent_id' => $kelas->parent_id !== null
                ? (int) $kelas->parent_id
                : null,
            'kelas_parent_nama' => $kelas->parent?->nama_kelas,
            'kelompok' => $kelas->kelompok,
            'kode' => $kelas->kode,
            'is_operasional' => $kelas->isOperational(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function normalizeTargetParentOverrides(
        mixed $value
    ): array {
        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $sourceParentId => $targetParentId) {
            if (
                !is_numeric($sourceParentId)
                || !is_numeric($targetParentId)
            ) {
                continue;
            }

            $result[(int) $sourceParentId] =
                (int) $targetParentId;
        }

        return $result;
    }

    /**
     * Pilihan jalur bercabang untuk ditampilkan di halaman Auto-Mapping.
     *
     * @return array<int, array<string, mixed>>
     */
    private function transitionChoicePayload(): array
    {
        $transitions = config(
            'kelas_transition.transitions',
            []
        );

        $parents = Kelas::query()
            ->induk()
            ->aktif()
            ->orderBy('urutan')
            ->orderBy('id')
            ->get([
                'id',
                'nama_kelas',
                'kode',
                'urutan',
            ]);

        $parentsByKey = $this->parentIndexByTransitionKey(
            $parents
        );
        $choices = [];

        foreach ($transitions as $sourceKey => $transition) {
            $targetKeys = collect($transition['targets'] ?? [])
                ->map(fn($key) => strtoupper(trim((string) $key)))
                ->filter()
                ->values();

            if ($targetKeys->count() < 2) {
                continue;
            }

            /** @var Kelas|null $source */
            $source = $parentsByKey->get(
                strtoupper((string) $sourceKey)
            );

            if (!$source) {
                continue;
            }

            $targetRows = $targetKeys
                ->map(function (string $targetKey) use ($parentsByKey) {
                    /** @var Kelas|null $target */
                    $target = $parentsByKey->get($targetKey);

                    return $target
                        ? [
                            'id' => (int) $target->id,
                            'nama' => (string) $target->nama_kelas,
                            'key' => $targetKey,
                        ]
                        : null;
                })
                ->filter()
                ->values()
                ->all();

            if (count($targetRows) < 2) {
                continue;
            }

            $choices[] = [
                'from_parent_id' => (int) $source->id,
                'from_parent_nama' => (string) $source->nama_kelas,
                'from_parent_key' => strtoupper((string) $sourceKey),
                'targets' => $targetRows,
            ];
        }

        return $choices;
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
        string $errorKey = 'to_musyrif_id',
        ?Semester $sourceSemester = null,
        CarbonInterface|string|null $changedAt = null,
        array $statusMetadata = []
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

        if ($tipe === 'keluar') {
            if (!$sourceSemester) {
                throw ValidationException::withMessages([
                    'from_semester_id' => [
                        'Semester asal wajib tersedia untuk mencatat santri keluar.',
                    ],
                ]);
            }

            $santri->changeStatus(
                Santri::STATUS_KELUAR,
                $catatan ?: 'Santri keluar melalui Migrasi Manual.',
                $sourceSemester,
                $userId,
                $changedAt,
                $statusMetadata
            );

            return;
        }

        $targetClass = $this->operationalClassOrFail(
            $toKelasId,
            'to_kelas_id',
            'Kelas tujuan'
        );

        $this->assertClassAcceptsGender(
            $targetClass,
            $santri->jenis_kelamin,
            'to_kelas_id',
            'Kelas tujuan'
        );

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
        bool $includeGraduation,
        array $targetParentOverrides = []
    ): array {
        $transitions = config(
            'kelas_transition.transitions',
            []
        );

        $parents = Kelas::query()
            ->induk()
            ->with([
                'children' => fn($query) => $query
                    ->where('is_active', true)
                    ->orderBy('urutan')
                    ->orderBy('kelompok'),
            ])
            ->orderBy('urutan')
            ->orderBy('id')
            ->get([
                'id',
                'nama_kelas',
                'kode',
                'is_active',
                'urutan',
            ]);

        $parentsByKey = $this->parentIndexByTransitionKey(
            $parents
        );

        $rows = [];
        $missing = [];
        $mappingByFromId = [];
        $sourceClassIds = [];
        $targetClassIds = [];
        $resolvedOverrides = [];

        foreach ($transitions as $sourceKey => $transition) {
            $sourceKey = strtoupper((string) $sourceKey);
            $type = (string) ($transition['type'] ?? 'naik_kelas');

            if ($type === 'lulus' && !$includeGraduation) {
                continue;
            }

            /** @var Kelas|null $sourceParent */
            $sourceParent = $parentsByKey->get($sourceKey);

            if (!$sourceParent || !$sourceParent->is_active) {
                $missing[] = [
                    'from_nama' => $sourceKey,
                    'to_nama' => '-',
                    'status' => 'MISSING_SOURCE_PARENT',
                ];
                continue;
            }

            $sourceChildren = $sourceParent->children
                ->filter(
                    fn(Kelas $kelas) =>
                    $kelas->is_active
                        && $kelas->parent_id !== null
                )
                ->values();

            if ($sourceChildren->isEmpty()) {
                $missing[] = [
                    'from_nama' => $sourceParent->nama_kelas,
                    'to_nama' => '-',
                    'status' => 'MISSING_SOURCE_GROUP',
                ];
                continue;
            }

            $targetParent = null;

            if ($type !== 'lulus') {
                $allowedTargetKeys = collect($transition['targets'] ?? [])
                    ->map(fn($key) => strtoupper(trim((string) $key)))
                    ->filter()
                    ->values();

                $allowedTargets = $allowedTargetKeys
                    ->map(fn(string $key) => $parentsByKey->get($key))
                    ->filter(
                        fn(?Kelas $kelas) =>
                        $kelas !== null
                            && (bool) $kelas->is_active
                    )
                    ->values();

                if ($allowedTargets->count() !== $allowedTargetKeys->count()) {
                    $missing[] = [
                        'from_nama' => $sourceParent->nama_kelas,
                        'to_nama' => $allowedTargetKeys->implode(' / '),
                        'status' => 'MISSING_TARGET_PARENT',
                    ];
                    continue;
                }

                if ($allowedTargets->count() > 1) {
                    $selectedId = $targetParentOverrides[(int) $sourceParent->id] ?? null;

                    $targetParent = $allowedTargets->first(
                        fn(Kelas $kelas) =>
                        (int) $kelas->id === (int) $selectedId
                    );

                    if (!$targetParent) {
                        $missing[] = [
                            'from_id' => (int) $sourceParent->id,
                            'from_nama' => $sourceParent->nama_kelas,
                            'to_nama' => $allowedTargets
                                ->pluck('nama_kelas')
                                ->implode(' / '),
                            'status' => 'TARGET_SELECTION_REQUIRED',
                        ];
                        continue;
                    }
                } else {
                    $targetParent = $allowedTargets->first();
                }

                if (!$targetParent) {
                    $missing[] = [
                        'from_nama' => $sourceParent->nama_kelas,
                        'to_nama' => '-',
                        'status' => 'MISSING_TARGET_PARENT',
                    ];
                    continue;
                }

                $resolvedOverrides[(int) $sourceParent->id] =
                    (int) $targetParent->id;
            }

            foreach ($sourceChildren as $sourceChild) {
                $targetChild = null;

                if ($type !== 'lulus') {
                    $expectedTargetGroup = $this->mapTargetGroup(
                        $sourceChild,
                        $targetParent
                    );

                    $targetChild = $targetParent->children
                        ->first(function (Kelas $kelas) use ($expectedTargetGroup) {
                            return $kelas->is_active
                                && strtoupper((string) $kelas->kelompok)
                                === strtoupper($expectedTargetGroup);
                        });

                    if (!$targetChild) {
                        $missing[] = [
                            'from_id' => (int) $sourceChild->id,
                            'from_nama' => $sourceChild->nama_kelas,
                            'to_nama' => trim(
                                $targetParent->nama_kelas
                                    . ' '
                                    . $expectedTargetGroup
                            ),
                            'status' => 'MISSING_TARGET_GROUP',
                        ];
                        continue;
                    }
                }

                $row = [
                    'mapping_key' =>
                    $sourceChild->id
                        . '->'
                        . ($targetChild?->id ?? 'LULUS'),
                    'from_id' => (int) $sourceChild->id,
                    'from_nama' => (string) $sourceChild->nama_kelas,
                    'from_parent_id' => (int) $sourceParent->id,
                    'from_parent_nama' => (string) $sourceParent->nama_kelas,
                    'from_parent_key' => $sourceKey,
                    'kelompok' => (string) $sourceChild->kelompok,
                    'kelompok_tujuan' => $targetChild?->kelompok,
                    'to_id' => $targetChild
                        ? (int) $targetChild->id
                        : null,
                    'to_nama' => $targetChild?->nama_kelas ?? 'LULUS',
                    'to_parent_id' => $targetParent
                        ? (int) $targetParent->id
                        : null,
                    'to_parent_nama' => $targetParent?->nama_kelas,
                    'to_parent_key' => $this->canonicalParentKey($targetParent),
                    'tipe' => $type,
                    'status' => 'OK',
                ];

                $rows[] = $row;
                $mappingByFromId[(int) $sourceChild->id] = $row;
                $sourceClassIds[] = (int) $sourceChild->id;

                if ($targetChild) {
                    $targetClassIds[] = (int) $targetChild->id;
                }
            }
        }

        return [
            'rows' => $rows,
            'missing' => $missing,
            'mapping_by_from_id' => $mappingByFromId,
            'source_class_ids' => array_values(array_unique($sourceClassIds)),
            'target_class_ids' => array_values(array_unique($targetClassIds)),
            'target_parent_overrides' => $resolvedOverrides,
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
        $classIds = collect($mappingByFromId)
            ->flatMap(
                fn(array $mapping) => [
                    $mapping['from_id'] ?? null,
                    $mapping['to_id'] ?? null,
                ]
            )
            ->filter()
            ->unique()
            ->values();

        $classes = Kelas::query()
            ->with('parent:id,nama_kelas,is_active,jenis_kelamin')
            ->whereIn('id', $classIds)
            ->get()
            ->keyBy('id');

        return $santris
            ->map(function (Santri $santri) use (
                $mappingByFromId,
                $classes
            ) {
                $fromClassId = (int) $santri->kelas_id;
                $mapping = $mappingByFromId[$fromClassId] ?? null;

                if (!$mapping) {
                    throw ValidationException::withMessages([
                        'auto_mapping' => [
                            "Kelas asal santri {$santri->nama} tidak memiliki mapping otomatis.",
                        ],
                    ]);
                }

                /** @var Kelas|null $fromClass */
                $fromClass = $classes->get($fromClassId);
                /** @var Kelas|null $toClass */
                $toClass = $mapping['to_id'] !== null
                    ? $classes->get((int) $mapping['to_id'])
                    : null;

                $isGraduation = $mapping['tipe'] === 'lulus';

                if (!$isGraduation && $toClass) {
                    $this->assertClassAcceptsGender(
                        $toClass,
                        $santri->jenis_kelamin,
                        'auto_mapping',
                        'Kelas tujuan'
                    );
                }

                $currentMusyrif = $santri->musyrif;
                $canKeepMusyrif = !$isGraduation
                    && $currentMusyrif
                    && $toClass
                    && $currentMusyrif->handlesKelasInduk(
                        (int) $toClass->parent_id
                    );

                return [
                    'santri_id' => (int) $santri->id,
                    'nama' => $santri->nama,
                    'nis' => $santri->nis,
                    'jenis_kelamin' => $this->normalizeJenisKelamin(
                        $santri->jenis_kelamin
                    ),
                    'from_kelas_id' => $fromClassId,
                    'from_kelas_nama' => $mapping['from_nama'],
                    'from_kelas_snapshot' =>
                    $this->hierarchySnapshot($fromClass),
                    'from_musyrif_id' => $santri->musyrif_id
                        ? (int) $santri->musyrif_id
                        : null,
                    'from_musyrif_nama' => $currentMusyrif?->nama,
                    'from_musyrif_kode' => $currentMusyrif?->kode,
                    'from_musyrif_kelas_id' => $currentMusyrif?->kelas_id,
                    'from_musyrif_jenis_kelamin' =>
                    $this->normalizeJenisKelamin(
                        $currentMusyrif?->jenis_kelamin
                    ),
                    'to_kelas_id' => $mapping['to_id'],
                    'to_kelas_nama' => $mapping['to_nama'],
                    'to_kelas_snapshot' =>
                    $this->hierarchySnapshot($toClass),
                    'tipe' => $mapping['tipe'],
                    'mapping_key' => $mapping['mapping_key'],
                    'assignment_required' =>
                    !$isGraduation && !$canKeepMusyrif,
                    'suggested_to_musyrif_id' =>
                    $canKeepMusyrif
                        ? (int) $currentMusyrif->id
                        : null,
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
        return config('kelas_transition.transitions', []);
    }

    /**
     * Memakai placement semester asal sebagai konteks in-memory santri.
     *
     * Atribut ini hanya digunakan saat membentuk preview dan snapshot. Tidak
     * ada perubahan yang disimpan ke tabel santris dari method ini.
     */
    private function applySourcePlacementContext(
        Collection $santris
    ): Collection {
        return $santris->each(function (Santri $santri): void {
            /** @var SantriSemesterPlacement|null $sourcePlacement */
            $sourcePlacement = $santri->semesterPlacements->first();

            if (!$sourcePlacement) {
                throw ValidationException::withMessages([
                    'from_semester_id' => [
                        "Placement semester asal untuk {$santri->nama} tidak ditemukan atau sudah ditutup.",
                    ],
                ]);
            }

            $santri->setAttribute(
                'kelas_id',
                (int) $sourcePlacement->kelas_id
            );
            $santri->setAttribute(
                'musyrif_id',
                $sourcePlacement->musyrif_id !== null
                    ? (int) $sourcePlacement->musyrif_id
                    : null
            );
            $santri->setRelation(
                'musyrif',
                $sourcePlacement->musyrif
            );
            $santri->unsetRelation('semesterPlacements');
        });
    }


    public function page()
    {
        $semesterAktif = Semester::query()
            ->with('tahunAjaran')
            ->active()
            ->first();

        $sourceSemesterId = (int) ($semesterAktif?->id ?? 0);

        $kelasList = Kelas::query()
            ->operasional()
            ->with('parent:id,nama_kelas,is_active,urutan,jenis_kelamin')
            ->withCount([
                'semesterPlacements as active_santri_count' =>
                    fn($query) => $query
                        ->where('semester_id', $sourceSemesterId)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at')
                        ->whereHas(
                            'santri',
                            fn($santriQuery) => $santriQuery->active()
                        ),
                'semesterPlacements as active_putra_count' =>
                    fn($query) => $query
                        ->where('semester_id', $sourceSemesterId)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at')
                        ->whereHas(
                            'santri',
                            fn($santriQuery) => $santriQuery
                                ->active()
                                ->whereIn(
                                    'jenis_kelamin',
                                    $this->jenisKelaminDatabaseValues('L')
                                )
                        ),
                'semesterPlacements as active_putri_count' =>
                    fn($query) => $query
                        ->where('semester_id', $sourceSemesterId)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at')
                        ->whereHas(
                            'santri',
                            fn($santriQuery) => $santriQuery
                                ->active()
                                ->whereIn(
                                    'jenis_kelamin',
                                    $this->jenisKelaminDatabaseValues('P')
                                )
                        ),
            ])
            ->urutHierarki()
            ->get([
                'id',
                'parent_id',
                'nama_kelas',
                'kelompok',
                'kode',
                'jenis_kelamin',
                'is_active',
                'urutan',
            ])
            ->each(function (Kelas $kelas): void {
                $kelas->jenis_kelamin = Kelas::normalizeGender(
                    $kelas->jenis_kelamin
                );
                $kelas->setAttribute(
                    'gender_label',
                    $kelas->genderLabel()
                );
            });

        $semesterTujuanList = Semester::query()
            ->with('tahunAjaran')
            ->draft()
            ->orderByDesc('id')
            ->get();

        $transitionChoices =
            $this->transitionChoicePayload();

        return view(
            'admin.santri.naik-kelas-massal',
            compact(
                'kelasList',
                'semesterAktif',
                'semesterTujuanList',
                'transitionChoices'
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

        $this->operationalClassOrFail(
            (int) $data['kelas_id'],
            'kelas_id',
            'Kelas'
        );

        $semesterAktif = Semester::query()
            ->active()
            ->first();

        if (!$semesterAktif) {
            throw ValidationException::withMessages([
                'kelas_id' => [
                    'Semester aktif tidak ditemukan.',
                ],
            ]);
        }

        $sourceSemesterId = (int) $semesterAktif->id;
        $sourceKelasId = (int) $data['kelas_id'];

        $santris = $this->applySourcePlacementContext(
            Santri::query()
                ->active()
                ->with([
                    'semesterPlacements' => fn($query) => $query
                        ->where('semester_id', $sourceSemesterId)
                        ->where('kelas_id', $sourceKelasId)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at'),
                    'semesterPlacements.musyrif:id,nama,kode,kelas_induk_id,kelas_id,jenis_kelamin',
                    'semesterPlacements.musyrif.kelasBinaan:id,nama_kelas',
                ])
                ->whereHas(
                    'semesterPlacements',
                    fn($query) => $query
                        ->where('semester_id', $sourceSemesterId)
                        ->where('kelas_id', $sourceKelasId)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at')
                )
                ->orderBy('nama')
                ->get([
                    'id',
                    'nama',
                    'nis',
                    'jenis_kelamin',
                    'kelas_id',
                    'musyrif_id',
                    'status',
                ])
        );

        $santris->transform(function (Santri $santri) {
            $santri->jenis_kelamin =
                $this->normalizeJenisKelamin(
                    $santri->jenis_kelamin
                );

            return $santri;
        });

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
        $isTerminal = in_array($tipe, ['lulus', 'keluar'], true);
        $toKelasId = !$isTerminal && isset($data['to_kelas_id'])
            ? (int) $data['to_kelas_id']
            : null;
        $jenisKelamin = isset($data['jenis_kelamin'])
            ? $this->normalizeJenisKelamin(
                $data['jenis_kelamin']
            )
            : null;

        if ($tipe === 'keluar') {
            $effectiveAt = Carbon::parse($data['exit_effective_at']);
            $semesterStart = $fromSemester->tanggal_mulai?->copy()->startOfDay();
            $semesterEnd = $fromSemester->tanggal_selesai?->copy()->endOfDay();

            if (
                ($semesterStart && $effectiveAt->lt($semesterStart))
                || ($semesterEnd && $effectiveAt->gt($semesterEnd))
            ) {
                throw ValidationException::withMessages([
                    'exit_effective_at' => [
                        'Tanggal efektif keluar harus berada dalam rentang semester asal.',
                    ],
                ]);
            }
        }

        $this->validateClassTransition(
            $tipe,
            $fromKelasId,
            $toKelasId
        );

        $fromKelas = Kelas::query()->findOrFail($fromKelasId);
        $toKelas = $toKelasId !== null
            ? Kelas::query()->findOrFail($toKelasId)
            : null;

        if ($jenisKelamin !== null) {
            $this->assertClassAcceptsGender(
                $fromKelas,
                $jenisKelamin,
                'from_kelas_id',
                'Kelas asal'
            );

            if ($toKelas && !$isTerminal) {
                $this->assertClassAcceptsGender(
                    $toKelas,
                    $jenisKelamin,
                    'to_kelas_id',
                    'Kelas tujuan'
                );
            }
        }

        $fromSemesterId = (int) $fromSemester->id;
        $toSemesterId = (int) $toSemester->id;

        $santris = $this->applySourcePlacementContext(
            Santri::query()
                ->active()
                ->with([
                    'semesterPlacements' => fn($query) => $query
                        ->where('semester_id', $fromSemesterId)
                        ->where('kelas_id', $fromKelasId)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at'),
                    'semesterPlacements.musyrif:id,nama,kode,kelas_induk_id,kelas_id,jenis_kelamin',
                    'semesterPlacements.musyrif.kelasBinaan:id,nama_kelas',
                ])
                ->whereHas(
                    'semesterPlacements',
                    fn($query) => $query
                        ->where('semester_id', $fromSemesterId)
                        ->where('kelas_id', $fromKelasId)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at')
                )
                ->when(
                    $tipe !== 'keluar',
                    fn($query) => $query->whereDoesntHave(
                        'semesterPlacements',
                        fn($placementQuery) => $placementQuery
                            ->where('semester_id', $toSemesterId)
                    )
                )
                ->when(
                    $jenisKelamin !== null,
                    fn($query) => $query->whereIn(
                        'jenis_kelamin',
                        $this->jenisKelaminDatabaseValues(
                            $jenisKelamin
                        )
                    )
                )
                ->orderBy('nama')
                ->get([
                    'id',
                    'nama',
                    'nis',
                    'jenis_kelamin',
                    'kelas_id',
                    'musyrif_id',
                    'status',
                ])
        );

        $exitTargetPlacementIds = collect();

        if ($tipe === 'keluar' && $santris->isNotEmpty()) {
            $exitTargetPlacementIds =
                SantriSemesterPlacement::query()
                    ->where('semester_id', $toSemester->id)
                    ->whereIn('santri_id', $santris->pluck('id'))
                    ->pluck('santri_id');
        }


        if (!$isTerminal) {
            $this->assertStudentsCompatibleWithTargetClass(
                $santris,
                $toKelas
            );
        }

        $genderCounts = $this->countJenisKelamin(
            $santris
        );
        $batchGender = $this->resolveBatchJenisKelamin(
            $genderCounts,
            $jenisKelamin
        );

        $plans = $santris->map(function (Santri $santri) use (
            $tipe,
            $isTerminal,
            $toKelasId,
            $fromKelas,
            $toKelas,
            $data
        ) {
            $canKeepMusyrif = !$isTerminal
                && $santri->musyrif
                && $toKelas
                && $santri->musyrif->handlesKelasInduk(
                    (int) $toKelas->parent_id
                );

            return [
                'santri_id' => (int) $santri->id,
                'from_kelas_id' => (int) $santri->kelas_id,
                'to_kelas_id' => $isTerminal
                    ? null
                    : $toKelasId,
                'from_musyrif_id' => $santri->musyrif_id !== null
                    ? (int) $santri->musyrif_id
                    : null,
                'transition_type' => $tipe,
                'assignment_required' => !$isTerminal && !$canKeepMusyrif,
                'source_snapshot' => [
                    'snapshot_version' =>
                    (int) config('kelas_transition.snapshot_version', 2),
                    'santri_id' => (int) $santri->id,
                    'nama' => $santri->nama,
                    'nis' => $santri->nis,
                    'jenis_kelamin' =>
                    $this->normalizeJenisKelamin(
                        $santri->jenis_kelamin
                    ),
                    'status' => $santri->status,
                    ...$this->hierarchySnapshot($fromKelas),
                    'musyrif_id' => $santri->musyrif_id,
                    'musyrif_nama' => $santri->musyrif?->nama,
                    'musyrif_kode' => $santri->musyrif?->kode,
                    'musyrif_kelas_id' => $santri->musyrif?->kelas_id,
                    'musyrif_jenis_kelamin' =>
                    $this->normalizeJenisKelamin(
                        $santri->musyrif?->jenis_kelamin
                    ),
                ],
                'target_snapshot' => [
                    'snapshot_version' =>
                    (int) config('kelas_transition.snapshot_version', 2),
                    ...$this->hierarchySnapshot(
                        $isTerminal ? null : $toKelas
                    ),
                    'tipe' => $tipe,
                    'assignment_required' => !$isTerminal && !$canKeepMusyrif,
                    'exit' => $tipe === 'keluar'
                        ? [
                            'reason_code' => $data['exit_reason_code'],
                            'effective_at' => $data['exit_effective_at'],
                            'destination' => $data['exit_destination'] ?? null,
                            'document_number' => $data['exit_document_number'] ?? null,
                        ]
                        : null,
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
                'to_kelas_id' => $isTerminal
                    ? null
                    : $toKelasId,
                'transition_type' => $tipe,
                'note' => $data['catatan'] ?? null,
                'metadata' => [
                    'source' => 'manual_massal_preview',
                    'hierarchy_version' =>
                    (int) config('kelas_transition.snapshot_version', 2),
                    'jenis_kelamin' => $batchGender,
                    'gender_counts' => $genderCounts,
                    'exit' => $tipe === 'keluar'
                        ? [
                            'reason_code' => $data['exit_reason_code'],
                            'effective_at' => $data['exit_effective_at'],
                            'destination' => $data['exit_destination'] ?? null,
                            'document_number' => $data['exit_document_number'] ?? null,
                        ]
                        : null,
                ],
            ]
        );

        $batchItems = $batch->items->keyBy('santri_id');

        $santriRows = $santris->map(function (Santri $santri) use (
            $batchItems,
            $isTerminal,
            $tipe,
            $exitTargetPlacementIds
        ) {
            $batchItem = $batchItems->get((int) $santri->id);
            $exitBlocked = $tipe === 'keluar'
                && $exitTargetPlacementIds->contains($santri->id);

            return [
                'id' => $santri->id,
                'batch_item_id' => $batchItem?->id,
                'nama' => $santri->nama,
                'nis' => $santri->nis,
                'jenis_kelamin' =>
                $this->normalizeJenisKelamin(
                    $santri->jenis_kelamin
                ),
                'kelas_id' => $santri->kelas_id,
                'musyrif_id' => $santri->musyrif_id,
                'musyrif_nama' => $santri->musyrif?->nama,
                'musyrif_kode' => $santri->musyrif?->kode,
                'musyrif_jenis_kelamin' =>
                $this->normalizeJenisKelamin(
                    $santri->musyrif?->jenis_kelamin
                ),
                'status' => $santri->status,
                'exit_blocked' => $exitBlocked,
                'exit_blocked_reason' => $exitBlocked
                    ? 'Sudah memiliki placement pada semester tujuan.'
                    : null,
                'assignment_required' => (bool) $batchItem?->assignment_required,
                'suggested_to_musyrif_id' =>
                !$isTerminal
                    && !$batchItem?->assignment_required
                    && $santri->musyrif_id !== null
                    ? (int) $santri->musyrif_id
                    : null,
            ];
        })->values();

        $targetMusyrifs =
            !$isTerminal && $toKelasId !== null
            ? $this->targetMusyrifsForClasses([$toKelasId])
            : collect();

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
            'to_kelas_id' => $isTerminal ? null : $toKelasId,
            'tipe' => $tipe,
            'jenis_kelamin' => $batchGender,
            'gender_counts' => $genderCounts,
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
            'jenis_kelamin' => [
                'nullable',
                Rule::in([
                    'L',
                    'P',
                ]),
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'target_parent_overrides' => [
                'nullable',
                'array',
            ],
            'target_parent_overrides.*' => [
                'nullable',
                'integer',
                'exists:kelas,id',
            ],
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
        $jenisKelamin = isset($data['jenis_kelamin'])
            ? $this->normalizeJenisKelamin(
                $data['jenis_kelamin']
            )
            : null;

        $targetParentOverrides =
            $this->normalizeTargetParentOverrides(
                $data['target_parent_overrides'] ?? []
            );

        $context = $this->resolveAutoMappingContext(
            $includeGraduation,
            $targetParentOverrides
        );

        if ($context['missing'] !== []) {
            return response()->json([
                'ok' => false,
                'message' => 'Terdapat kelas mapping yang belum tersedia.',
                'missing' => $context['missing'],
                'rows' => $context['rows'],
                'total_santri_affected' => 0,
            ], 422);
        }

        $fromSemesterId = (int) $fromSemester->id;
        $toSemesterId = (int) $toSemester->id;
        $sourceClassIds = array_map(
            'intval',
            $context['source_class_ids']
        );

        $santris = $this->applySourcePlacementContext(
            Santri::query()
                ->active()
                ->with([
                    'semesterPlacements' => fn($query) => $query
                        ->where('semester_id', $fromSemesterId)
                        ->whereIn('kelas_id', $sourceClassIds)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at'),
                    'semesterPlacements.musyrif:id,nama,kode,kelas_induk_id,kelas_id,jenis_kelamin',
                    'semesterPlacements.musyrif.kelasBinaan:id,nama_kelas',
                ])
                ->whereHas(
                    'semesterPlacements',
                    fn($query) => $query
                        ->where('semester_id', $fromSemesterId)
                        ->whereIn('kelas_id', $sourceClassIds)
                        ->where(
                            'status',
                            SantriSemesterPlacement::STATUS_AKTIF
                        )
                        ->whereNull('ended_at')
                )
                ->whereDoesntHave(
                    'semesterPlacements',
                    fn($query) => $query
                        ->where('semester_id', $toSemesterId)
                )
                ->when(
                    $jenisKelamin !== null,
                    fn($query) => $query->whereIn(
                        'jenis_kelamin',
                        $this->jenisKelaminDatabaseValues(
                            $jenisKelamin
                        )
                    )
                )
                ->orderBy('id')
                ->get([
                    'id',
                    'nama',
                    'nis',
                    'jenis_kelamin',
                    'kelas_id',
                    'musyrif_id',
                    'status',
                ])
        );

        $snapshot = $this->buildAutoSnapshot(
            $santris,
            $context['mapping_by_from_id']
        );

        $genderCounts = $this->countJenisKelamin(
            $snapshot
        );
        $batchGender = $this->resolveBatchJenisKelamin(
            $genderCounts,
            $jenisKelamin
        );

        $plans = $snapshot->map(function (array $item) {
            return [
                'santri_id' => $item['santri_id'],
                'from_kelas_id' => $item['from_kelas_id'],
                'to_kelas_id' => $item['to_kelas_id'],
                'from_musyrif_id' => $item['from_musyrif_id'],
                'transition_type' => $item['tipe'],
                'assignment_required' =>
                (bool) $item['assignment_required'],
                'source_snapshot' => [
                    'snapshot_version' =>
                    (int) config('kelas_transition.snapshot_version', 2),
                    'santri_id' => $item['santri_id'],
                    'nama' => $item['nama'],
                    'nis' => $item['nis'],
                    'jenis_kelamin' => $item['jenis_kelamin'],
                    'status' => Santri::STATUS_AKTIF,
                    ...$item['from_kelas_snapshot'],
                    'musyrif_id' => $item['from_musyrif_id'],
                    'musyrif_nama' => $item['from_musyrif_nama'],
                    'musyrif_kode' => $item['from_musyrif_kode'],
                    'musyrif_kelas_id' =>
                    $item['from_musyrif_kelas_id'],
                    'musyrif_jenis_kelamin' =>
                    $item['from_musyrif_jenis_kelamin'],
                ],
                'target_snapshot' => [
                    'snapshot_version' =>
                    (int) config('kelas_transition.snapshot_version', 2),
                    ...$item['to_kelas_snapshot'],
                    'tipe' => $item['tipe'],
                    'mapping_key' => $item['mapping_key'],
                    'assignment_required' =>
                    (bool) $item['assignment_required'],
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
                    'hierarchy_version' =>
                    (int) config('kelas_transition.snapshot_version', 2),
                    'mapping_rows' => $context['rows'],
                    'target_parent_overrides' =>
                    $context['target_parent_overrides'],
                    'jenis_kelamin' => $batchGender,
                    'gender_counts' => $genderCounts,
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

        $targetMusyrifs = $this->targetMusyrifsForClasses(
            $context['target_class_ids']
        );

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
                'gender_counts' =>
                $this->countJenisKelamin(
                    $santriRows
                ),
                'santris' => $santriRows,
                'target_musyrifs' => $row['to_id'] !== null
                    ? $targetMusyrifs
                    ->filter(
                        fn(Musyrif $musyrif) =>
                        (int) $musyrif->kelas_induk_id
                            === (int) $row['to_parent_id']
                    )
                    ->values()
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
            'target_parent_overrides' =>
            $context['target_parent_overrides'],
            'transition_choices' =>
            $this->transitionChoicePayload(),
            'jenis_kelamin' => $batchGender,
            'gender_counts' => $genderCounts,
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
