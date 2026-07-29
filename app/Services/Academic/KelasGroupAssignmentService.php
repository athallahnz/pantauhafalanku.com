<?php

namespace App\Services\Academic;

use App\Models\Kelas;
use App\Models\KelasGroupAssignmentBatch;
use App\Models\KelasGroupAssignmentItem;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Support\Academic\SantriSemesterPlacementService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class KelasGroupAssignmentService
{
    public function __construct(
        private readonly SantriSemesterPlacementService $placementService
    ) {}

    /**
     * Ringkasan keadaan saat ini untuk halaman admin.
     *
     * @return array<string, mixed>
     */
    public function dashboardState(): array
    {
        $semester = $this->resolveActiveSemester(
            null,
            false
        );

        $mappingResult = $this->resolveMappings(
            $semester
        );

        $santriLegacy = Santri::query()
            ->active()
            ->usingLegacyClass()
            ->count();

        $musyrifLegacy = Musyrif::query()
            ->usingLegacyClass()
            ->count();

        $placementLegacy = $semester
            ? SantriSemesterPlacement::query()
            ->forSemester($semester->id)
            ->active()
            ->usingLegacyClass()
            ->count()
            : 0;

        $missingPlacements = 0;

        if ($semester) {
            $missingPlacements = Santri::query()
                ->active()
                ->usingLegacyClass()
                ->whereDoesntHave(
                    'semesterPlacements',
                    fn($query) => $query->where(
                        'semester_id',
                        $semester->id
                    )
                )
                ->count();
        }

        return [
            'semester' => $semester,
            'mapping' => $mappingResult['mapping'],
            'blockers' => $mappingResult['blockers'],
            'summary' => [
                'santri_legacy' => $santriLegacy,
                'musyrif_legacy' => $musyrifLegacy,
                'placement_legacy' => $placementLegacy,
                'missing_placements' => $missingPlacements,
            ],
        ];
    }

    public function createPreview(
        ?int $semesterId,
        ?int $actorId,
        ?string $note = null
    ): KelasGroupAssignmentBatch {
        $semester = $this->resolveActiveSemester(
            $semesterId,
            true
        );

        $plan = $this->buildPlan(
            $semester
        );

        $status = count($plan['blockers']) > 0
            ? KelasGroupAssignmentBatch::STATUS_BLOCKED
            : KelasGroupAssignmentBatch::STATUS_PREVIEWED;

        $batchId = (string) Str::uuid();
        $batchCode = $this->makeBatchCode();

        return DB::transaction(
            function () use (
                $batchId,
                $batchCode,
                $semester,
                $actorId,
                $note,
                $plan,
                $status
            ): KelasGroupAssignmentBatch {
                $batch = KelasGroupAssignmentBatch::query()
                    ->create([
                        'id' => $batchId,
                        'code' => $batchCode,
                        'semester_id' => $semester->id,
                        'status' => $status,
                        'mapping' => $plan['mapping'],
                        'summary' => $plan['summary'],
                        'checksum' => $plan['checksum'],
                        'note' => $note,
                        'metadata' => [
                            'blockers' => $plan['blockers'],
                            'warnings' => $plan['warnings'],
                            'generated_at' => now()
                                ->toIso8601String(),
                            'config' => [
                                'require_active_semester_placement' =>
                                (bool) config(
                                    'kelas_group_assignment.require_active_semester_placement',
                                    true
                                ),
                                'infer_unassigned_musyrif_from_students' =>
                                (bool) config(
                                    'kelas_group_assignment.infer_unassigned_musyrif_from_students',
                                    true
                                ),
                            ],
                        ],
                        'previewed_at' => now(),
                        'created_by' => $actorId,
                    ]);

                foreach (
                    array_chunk(
                        $plan['items'],
                        200
                    ) as $chunk
                ) {
                    foreach ($chunk as $item) {
                        $batch->items()->create($item);
                    }
                }

                return $batch->fresh([
                    'semester.tahunAjaran',
                    'items',
                ]);
            }
        );
    }

    public function execute(
        KelasGroupAssignmentBatch $batch,
        ?int $actorId
    ): KelasGroupAssignmentBatch {
        return DB::transaction(
            function () use (
                $batch,
                $actorId
            ): KelasGroupAssignmentBatch {
                $lockedBatch = KelasGroupAssignmentBatch::query()
                    ->whereKey($batch->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedBatch->canExecute()) {
                    throw ValidationException::withMessages([
                        'batch' => [
                            'Batch hanya dapat dieksekusi ketika statusnya previewed.',
                        ],
                    ]);
                }

                $items = $lockedBatch->items()
                    ->orderByRaw(
                        "FIELD(entity_type, 'musyrif', 'santri', 'placement')"
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $this->assertSourceStateStillValid(
                    $items
                );

                $executedCounts = [
                    'musyrif' => 0,
                    'santri' => 0,
                    'placement' => 0,
                ];

                foreach ($items as $item) {
                    $targetClass = $this->findOperationalTarget(
                        $item
                    );

                    match ($item->entity_type) {
                        KelasGroupAssignmentItem::ENTITY_MUSYRIF =>
                        $this->executeMusyrifItem(
                            $item,
                            $targetClass
                        ),

                        KelasGroupAssignmentItem::ENTITY_SANTRI =>
                        $this->executeSantriItem(
                            $item,
                            $targetClass
                        ),

                        KelasGroupAssignmentItem::ENTITY_PLACEMENT =>
                        $this->executePlacementItem(
                            $item,
                            $targetClass,
                            $lockedBatch,
                            $actorId
                        ),

                        default => throw new RuntimeException(
                            "Jenis item {$item->entity_type} tidak didukung."
                        ),
                    };

                    $snapshotAfter =
                        $this->snapshotForItem(
                            $item
                        );

                    $item->forceFill([
                        'snapshot_after' =>
                        $snapshotAfter,
                        'target_hash' =>
                        $this->hashAssignmentState(
                            $snapshotAfter
                        ),
                        'status' =>
                        KelasGroupAssignmentItem::STATUS_EXECUTED,
                        'error_message' => null,
                    ])->saveQuietly();

                    $executedCounts[$item->entity_type]++;
                }

                $summary = $lockedBatch->summary ?? [];
                $summary['executed'] = $executedCounts;
                $summary['executed_total'] =
                    array_sum($executedCounts);

                $lockedBatch->forceFill([
                    'status' =>
                    KelasGroupAssignmentBatch::STATUS_EXECUTED,
                    'summary' => $summary,
                    'executed_at' => now(),
                    'executed_by' => $actorId,
                ])->saveQuietly();

                return $lockedBatch->fresh([
                    'semester.tahunAjaran',
                    'items',
                ]);
            }
        );
    }

    public function rollback(
        KelasGroupAssignmentBatch $batch,
        ?int $actorId
    ): KelasGroupAssignmentBatch {
        return DB::transaction(
            function () use (
                $batch,
                $actorId
            ): KelasGroupAssignmentBatch {
                $lockedBatch = KelasGroupAssignmentBatch::query()
                    ->whereKey($batch->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedBatch->canRollback()) {
                    throw ValidationException::withMessages([
                        'batch' => [
                            'Rollback hanya dapat dilakukan pada batch yang sudah executed.',
                        ],
                    ]);
                }

                $items = $lockedBatch->items()
                    ->orderByRaw(
                        "FIELD(entity_type, 'placement', 'santri', 'musyrif')"
                    )
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get();

                $this->assertTargetStateStillValid(
                    $items
                );

                $rollbackCounts = [
                    'musyrif' => 0,
                    'santri' => 0,
                    'placement' => 0,
                ];

                foreach ($items as $item) {
                    match ($item->entity_type) {
                        KelasGroupAssignmentItem::ENTITY_PLACEMENT =>
                        $this->rollbackPlacementItem(
                            $item,
                            $lockedBatch,
                            $actorId
                        ),

                        KelasGroupAssignmentItem::ENTITY_SANTRI =>
                        $this->rollbackSantriItem(
                            $item
                        ),

                        KelasGroupAssignmentItem::ENTITY_MUSYRIF =>
                        $this->rollbackMusyrifItem(
                            $item
                        ),

                        default => throw new RuntimeException(
                            "Jenis item {$item->entity_type} tidak didukung."
                        ),
                    };

                    $restoredSnapshot =
                        $this->snapshotForItem(
                            $item
                        );

                    $restoredHash =
                        $this->hashAssignmentState(
                            $restoredSnapshot
                        );

                    if (
                        !hash_equals(
                            (string) $item->source_hash,
                            $restoredHash
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'batch' => [
                                "Rollback item {$item->entity_type} #{$item->entity_id} tidak menghasilkan state asal.",
                            ],
                        ]);
                    }

                    $item->forceFill([
                        'status' =>
                        KelasGroupAssignmentItem::STATUS_ROLLED_BACK,
                        'error_message' => null,
                    ])->saveQuietly();

                    $rollbackCounts[$item->entity_type]++;
                }

                $summary = $lockedBatch->summary ?? [];
                $summary['rolled_back'] =
                    $rollbackCounts;
                $summary['rolled_back_total'] =
                    array_sum($rollbackCounts);

                $lockedBatch->forceFill([
                    'status' =>
                    KelasGroupAssignmentBatch::STATUS_ROLLED_BACK,
                    'summary' => $summary,
                    'rolled_back_at' => now(),
                    'rolled_back_by' => $actorId,
                ])->saveQuietly();

                return $lockedBatch->fresh([
                    'semester.tahunAjaran',
                    'items',
                ]);
            }
        );
    }

    /**
     * @return array{
     *     mapping:array<int, array<string, mixed>>,
     *     blockers:array<int, string>,
     *     warnings:array<int, string>,
     *     items:array<int, array<string, mixed>>,
     *     summary:array<string, mixed>,
     *     checksum:string
     * }
     */
    private function buildPlan(
        Semester $semester
    ): array {
        $mappingResult = $this->resolveMappings(
            $semester
        );

        $mapping = $mappingResult['mapping'];
        $blockers = $mappingResult['blockers'];
        $warnings = [];
        $items = [];

        $mappingByParent = collect($mapping)
            ->filter(
                fn(array $row) =>
                !empty($row['target_id'])
            )
            ->keyBy('parent_id');

        $parentIds = $mappingByParent
            ->keys()
            ->map(fn($id) => (int) $id)
            ->values();

        $santris = Santri::query()
            ->active()
            ->whereIn(
                'kelas_id',
                $parentIds
            )
            ->with([
                'kelas:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'musyrif.kelas:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'musyrif.kelasBinaan:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'semesterPlacements' => fn($query) =>
                $query->where(
                    'semester_id',
                    $semester->id
                ),
            ])
            ->orderBy('id')
            ->get();

        $directMusyrifs = Musyrif::query()
            ->whereIn(
                'kelas_id',
                $parentIds
            )
            ->with([
                'kelas:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'kelasBinaan:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'santris' => fn($query) =>
                $query
                    ->active()
                    ->select([
                        'santris.id',
                        'santris.nama',
                        'santris.kelas_id',
                        'santris.musyrif_id',
                    ]),
            ])
            ->orderBy('id')
            ->get();

        $musyrifPlans = [];
        $blockedMusyrifIds = [];

        foreach ($directMusyrifs as $musyrif) {
            /*
             * Sumber assignment musyrif diprioritaskan dari santri aktif
             * binaannya. Ini aman untuk memperbaiki kelas musyrif yang stale:
             *
             * musyrif.kelas_id = Kelas 7
             * seluruh santri aktifnya = Kelas 8
             *
             * Dalam kondisi tersebut target musyrif mengikuti Kelas 8 A,
             * bukan memblokir setiap santri satu per satu.
             */
            $studentParentCounts = $musyrif->santris
                ->map(
                    fn(Santri $santri) =>
                    $this->sourceParentForCurrentClass(
                        $santri->kelas_id
                            ? (int) $santri->kelas_id
                            : null,
                        $mappingByParent
                    )
                )
                ->filter()
                ->countBy();

            if ($studentParentCounts->count() > 1) {
                $detail = $studentParentCounts
                    ->map(function (int $count, int|string $parentId): string {
                        $parent = Kelas::query()
                            ->select(['id', 'nama_kelas'])
                            ->find((int) $parentId);

                        return ($parent?->nama_kelas ?? "Kelas #{$parentId}")
                            . ": {$count} santri";
                    })
                    ->values()
                    ->implode(', ');

                $blockers[] =
                    "Musyrif {$musyrif->nama} membina santri aktif dari lebih dari satu kelas induk ({$detail}). Pisahkan penugasan musyrif atau koreksi data santri sebelum eksekusi.";

                $blockedMusyrifIds[(int) $musyrif->id] = true;

                continue;
            }

            $storedSourceParentId =
                $this->sourceParentForCurrentClass(
                    $musyrif->kelas_id
                        ? (int) $musyrif->kelas_id
                        : null,
                    $mappingByParent
                );

            $studentSourceParentId =
                $studentParentCounts->count() === 1
                ? (int) $studentParentCounts->keys()->first()
                : null;

            $effectiveSourceParentId =
                $studentSourceParentId
                ?? $storedSourceParentId;

            if (!$effectiveSourceParentId) {
                $blockers[] =
                    "Kelas sumber musyrif {$musyrif->nama} tidak dapat ditentukan.";

                $blockedMusyrifIds[(int) $musyrif->id] = true;

                continue;
            }

            $target = $mappingByParent->get(
                $effectiveSourceParentId
            );

            if (!$target) {
                $blockers[] =
                    "Target untuk musyrif {$musyrif->nama} tidak tersedia.";

                $blockedMusyrifIds[(int) $musyrif->id] = true;

                continue;
            }

            $inferredFromStudents =
                $studentSourceParentId !== null
                && $studentSourceParentId !== $storedSourceParentId;

            if ($inferredFromStudents) {
                $storedParent = $storedSourceParentId
                    ? Kelas::query()
                    ->select(['id', 'nama_kelas'])
                    ->find($storedSourceParentId)
                    : null;

                $studentParent = Kelas::query()
                    ->select(['id', 'nama_kelas'])
                    ->find($studentSourceParentId);

                $warnings[] =
                    "Kelas musyrif {$musyrif->nama} akan dikoreksi dari "
                    . ($storedParent?->nama_kelas ?? 'Belum ditentukan')
                    . " menjadi "
                    . ($studentParent?->nama_kelas ?? "Kelas #{$studentSourceParentId}")
                    . " berdasarkan seluruh santri aktif binaannya.";
            }

            $musyrifPlans[(int) $musyrif->id] = [
                'musyrif' => $musyrif,
                'from_kelas_id' =>
                $musyrif->kelas_id !== null
                    ? (int) $musyrif->kelas_id
                    : null,
                'to_kelas_id' =>
                (int) $target['target_id'],
                'inferred' => $inferredFromStudents,
            ];
        }

        $unassignedMusyrifIds = $santris
            ->filter(
                fn(Santri $santri) =>
                $santri->musyrif_id
                    && !$santri->musyrif?->kelas_id
            )
            ->pluck('musyrif_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if (
            $unassignedMusyrifIds->isNotEmpty()
            && (bool) config(
                'kelas_group_assignment.infer_unassigned_musyrif_from_students',
                true
            )
        ) {
            $unassignedMusyrifs = Musyrif::query()
                ->whereIn(
                    'id',
                    $unassignedMusyrifIds
                )
                ->with([
                    'santris' => fn($query) =>
                    $query
                        ->active()
                        ->select([
                            'santris.id',
                            'santris.nama',
                            'santris.kelas_id',
                            'santris.musyrif_id',
                        ]),
                ])
                ->get()
                ->keyBy('id');

            foreach (
                $unassignedMusyrifIds
                as $musyrifId
            ) {
                $musyrif = $unassignedMusyrifs
                    ->get($musyrifId);

                if (!$musyrif) {
                    $blockers[] =
                        "Musyrif ID {$musyrifId} tidak ditemukan.";

                    continue;
                }

                $parentCandidates = $musyrif
                    ->santris
                    ->map(
                        fn(Santri $santri) =>
                        $this->sourceParentForCurrentClass(
                            $santri->kelas_id
                                ? (int) $santri->kelas_id
                                : null,
                            $mappingByParent
                        )
                    )
                    ->filter()
                    ->unique()
                    ->values();

                if ($parentCandidates->count() !== 1) {
                    $blockers[] =
                        "Musyrif {$musyrif->nama} belum memiliki kelas dan membina santri dari lebih dari satu kelas induk.";

                    continue;
                }

                $sourceParentId =
                    (int) $parentCandidates->first();

                $target = $mappingByParent->get(
                    $sourceParentId
                );

                if (!$target) {
                    $blockers[] =
                        "Target inferensi untuk musyrif {$musyrif->nama} tidak tersedia.";

                    continue;
                }

                $musyrifPlans[(int) $musyrif->id] = [
                    'musyrif' => $musyrif,
                    'from_kelas_id' => null,
                    'to_kelas_id' =>
                    (int) $target['target_id'],
                    'inferred' => true,
                ];

                $warnings[] =
                    "Kelas musyrif {$musyrif->nama} akan diinferensikan dari santri binaannya.";
            }
        }

        foreach ($musyrifPlans as $plan) {
            /** @var Musyrif $musyrif */
            $musyrif = $plan['musyrif'];
            $targetClassId =
                (int) $plan['to_kelas_id'];

            $snapshot =
                $this->snapshotMusyrif(
                    $musyrif
                );

            $items[] = [
                'entity_type' =>
                KelasGroupAssignmentItem::ENTITY_MUSYRIF,
                'entity_id' => (int) $musyrif->id,
                'santri_id' => null,
                'musyrif_id' => (int) $musyrif->id,
                'placement_id' => null,
                'semester_id' => $semester->id,
                'from_kelas_id' =>
                $plan['from_kelas_id'],
                'to_kelas_id' =>
                $targetClassId,
                'status' =>
                KelasGroupAssignmentItem::STATUS_PENDING,
                'snapshot_before' => array_merge(
                    $snapshot,
                    [
                        'assignment_source' =>
                        $plan['inferred']
                            ? 'inferred_from_students'
                            : 'existing_parent_class',
                    ]
                ),
                'snapshot_after' => null,
                'source_hash' =>
                $this->hashAssignmentState(
                    $snapshot
                ),
                'target_hash' => null,
                'error_message' => null,
            ];
        }

        foreach ($santris as $santri) {
            $target = $mappingByParent->get(
                (int) $santri->kelas_id
            );

            if (!$target) {
                $blockers[] =
                    "Target kelas untuk {$santri->nama} tidak tersedia.";

                continue;
            }

            $targetId =
                (int) $target['target_id'];

            if ($santri->musyrif_id) {
                $musyrif = $santri->musyrif;

                if (!$musyrif) {
                    $blockers[] =
                        "Musyrif ID {$santri->musyrif_id} milik {$santri->nama} tidak ditemukan.";
                } elseif (
                    !isset(
                        $blockedMusyrifIds[(int) $musyrif->id]
                    )
                ) {
                    if (
                        !$this->eventualMusyrifHandlesClass(
                            $musyrif,
                            $targetId,
                            $musyrifPlans
                        )
                    ) {
                        $blockers[] =
                            "Musyrif {$musyrif->nama} belum tercatat membina target kelas {$santri->nama}.";
                    }
                }
            }

            $placement = $santri
                ->semesterPlacements
                ->first();

            if (
                !$placement
                && (bool) config(
                    'kelas_group_assignment.require_active_semester_placement',
                    true
                )
            ) {
                $blockers[] =
                    "{$santri->nama} belum memiliki placement pada semester aktif.";

                continue;
            }

            if ($placement) {
                if (
                    (int) ($placement->musyrif_id ?? 0)
                    !== (int) ($santri->musyrif_id ?? 0)
                ) {
                    $blockers[] =
                        "Musyrif placement semester aktif {$santri->nama} tidak sama dengan current projection.";

                    continue;
                }

                if ($placement->kelas_id === null) {
                    $blockers[] =
                        "Placement semester aktif {$santri->nama} belum memiliki kelas.";

                    continue;
                }

                $placementTarget =
                    $this->targetForCurrentClass(
                        $placement->kelas_id
                            ? (int) $placement->kelas_id
                            : null,
                        $mappingByParent
                    );

                if (
                    $placementTarget !== null
                    && $placementTarget !== $targetId
                ) {
                    $blockers[] =
                        "Placement semester aktif {$santri->nama} tidak menuju kelompok yang sama dengan current projection.";
                }

                if (
                    $placement->kelas_id !== null
                    && $placementTarget === null
                    && (int) $placement->kelas_id !== $targetId
                ) {
                    $blockers[] =
                        "Placement semester aktif {$santri->nama} berada pada kelas yang tidak kompatibel.";
                }
            }

            $snapshot =
                $this->snapshotSantri(
                    $santri
                );

            $items[] = [
                'entity_type' =>
                KelasGroupAssignmentItem::ENTITY_SANTRI,
                'entity_id' => (int) $santri->id,
                'santri_id' => (int) $santri->id,
                'musyrif_id' =>
                $santri->musyrif_id
                    ? (int) $santri->musyrif_id
                    : null,
                'placement_id' => null,
                'semester_id' => $semester->id,
                'from_kelas_id' =>
                (int) $santri->kelas_id,
                'to_kelas_id' => $targetId,
                'status' =>
                KelasGroupAssignmentItem::STATUS_PENDING,
                'snapshot_before' => $snapshot,
                'snapshot_after' => null,
                'source_hash' =>
                $this->hashAssignmentState(
                    $snapshot
                ),
                'target_hash' => null,
                'error_message' => null,
            ];

            if (
                $placement
                && $placement->kelas_id !== null
                && (int) $placement->kelas_id
                !== $targetId
            ) {
                $placementSnapshot =
                    $this->snapshotPlacement(
                        $placement
                    );

                $items[] = [
                    'entity_type' =>
                    KelasGroupAssignmentItem::ENTITY_PLACEMENT,
                    'entity_id' =>
                    (int) $placement->id,
                    'santri_id' =>
                    (int) $santri->id,
                    'musyrif_id' =>
                    $placement->musyrif_id
                        ? (int) $placement->musyrif_id
                        : null,
                    'placement_id' =>
                    (int) $placement->id,
                    'semester_id' =>
                    (int) $semester->id,
                    'from_kelas_id' =>
                    (int) $placement->kelas_id,
                    'to_kelas_id' =>
                    $targetId,
                    'status' =>
                    KelasGroupAssignmentItem::STATUS_PENDING,
                    'snapshot_before' =>
                    $placementSnapshot,
                    'snapshot_after' => null,
                    'source_hash' =>
                    $this->hashAssignmentState(
                        $placementSnapshot
                    ),
                    'target_hash' => null,
                    'error_message' => null,
                ];
            }
        }


        /*
         * Placement diproses mandiri agar state parsial tetap tertangani,
         * misalnya santri sudah berada di child tetapi placement masih parent.
         */
        $legacyPlacements = SantriSemesterPlacement::query()
            ->forSemester($semester->id)
            ->active()
            ->whereIn('kelas_id', $parentIds)
            ->with([
                'kelas:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'santri.kelas:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'musyrif.kelas:id,parent_id,nama_kelas,kelompok,kode,is_active',
                'musyrif.kelasBinaan:id,parent_id,nama_kelas,kelompok,kode,is_active',
            ])
            ->orderBy('id')
            ->get();

        foreach ($legacyPlacements as $placement) {
            $target = $mappingByParent->get(
                (int) $placement->kelas_id
            );

            if (!$target) {
                $blockers[] =
                    "Target placement ID {$placement->id} tidak tersedia.";

                continue;
            }

            $targetId = (int) $target['target_id'];
            $santri = $placement->santri;

            if (!$santri) {
                $blockers[] =
                    "Placement ID {$placement->id} tidak mempunyai santri.";

                continue;
            }

            if (!$santri->isActive()) {
                $blockers[] =
                    "Placement aktif ID {$placement->id} dimiliki santri nonaktif {$santri->nama}.";

                continue;
            }

            $studentTargetId =
                $this->targetForCurrentClass(
                    $santri->kelas_id
                        ? (int) $santri->kelas_id
                        : null,
                    $mappingByParent
                );

            if ($studentTargetId !== $targetId) {
                $blockers[] =
                    "Placement semester aktif {$santri->nama} tidak sama dengan target current projection.";

                continue;
            }

            if (
                (int) ($placement->musyrif_id ?? 0)
                !== (int) ($santri->musyrif_id ?? 0)
            ) {
                $blockers[] =
                    "Musyrif placement {$santri->nama} tidak sama dengan current projection.";

                continue;
            }

            if ($placement->musyrif_id) {
                $musyrif = $placement->musyrif;

                if (!$musyrif) {
                    $blockers[] =
                        "Musyrif placement ID {$placement->id} tidak ditemukan.";

                    continue;
                }

                if (
                    isset(
                        $blockedMusyrifIds[(int) $musyrif->id]
                    )
                ) {
                    continue;
                }

                if (
                    !$this->eventualMusyrifHandlesClass(
                        $musyrif,
                        $targetId,
                        $musyrifPlans
                    )
                ) {
                    $blockers[] =
                        "Musyrif placement {$santri->nama} belum tercatat membina target kelompok.";

                    continue;
                }
            }

            $placementSnapshot =
                $this->snapshotPlacement(
                    $placement
                );

            $items[] = [
                'entity_type' =>
                KelasGroupAssignmentItem::ENTITY_PLACEMENT,
                'entity_id' =>
                (int) $placement->id,
                'santri_id' =>
                (int) $santri->id,
                'musyrif_id' =>
                $placement->musyrif_id
                    ? (int) $placement->musyrif_id
                    : null,
                'placement_id' =>
                (int) $placement->id,
                'semester_id' =>
                (int) $semester->id,
                'from_kelas_id' =>
                (int) $placement->kelas_id,
                'to_kelas_id' =>
                $targetId,
                'status' =>
                KelasGroupAssignmentItem::STATUS_PENDING,
                'snapshot_before' =>
                $placementSnapshot,
                'snapshot_after' => null,
                'source_hash' =>
                $this->hashAssignmentState(
                    $placementSnapshot
                ),
                'target_hash' => null,
                'error_message' => null,
            ];
        }

        $items = collect($items)
            ->unique(
                fn(array $item) =>
                $item['entity_type']
                    . ':'
                    . $item['entity_id']
            )
            ->values()
            ->all();

        $counts = collect($items)
            ->countBy('entity_type')
            ->all();

        $summary = [
            'semester_id' => (int) $semester->id,
            'semester_label' =>
            $this->semesterLabel($semester),
            'mapping_count' => count($mapping),
            'items' => [
                'musyrif' =>
                (int) ($counts[KelasGroupAssignmentItem::ENTITY_MUSYRIF] ?? 0),
                'santri' =>
                (int) ($counts[KelasGroupAssignmentItem::ENTITY_SANTRI] ?? 0),
                'placement' =>
                (int) ($counts[KelasGroupAssignmentItem::ENTITY_PLACEMENT] ?? 0),
            ],
            'total_items' => count($items),
            'blocker_count' =>
            count(array_unique($blockers)),
            'warning_count' =>
            count(array_unique($warnings)),
        ];

        $checksumPayload = [
            'semester_id' => $semester->id,
            'mapping' => collect($mapping)
                ->map(fn(array $row) => [
                    'parent_id' =>
                    $row['parent_id'],
                    'target_id' =>
                    $row['target_id'],
                ])
                ->values()
                ->all(),
            'items' => collect($items)
                ->map(fn(array $item) => [
                    'entity_type' =>
                    $item['entity_type'],
                    'entity_id' =>
                    $item['entity_id'],
                    'source_hash' =>
                    $item['source_hash'],
                    'to_kelas_id' =>
                    $item['to_kelas_id'],
                ])
                ->sortBy(
                    fn(array $row) =>
                    $row['entity_type']
                        . ':'
                        . str_pad(
                            (string) $row['entity_id'],
                            20,
                            '0',
                            STR_PAD_LEFT
                        )
                )
                ->values()
                ->all(),
        ];

        return [
            'mapping' => $mapping,
            'blockers' =>
            array_values(
                array_unique($blockers)
            ),
            'warnings' =>
            array_values(
                array_unique($warnings)
            ),
            'items' => $items,
            'summary' => $summary,
            'checksum' => hash(
                'sha256',
                $this->canonicalJson(
                    $checksumPayload
                )
            ),
        ];
    }

    /**
     * @return array{
     *     mapping:array<int, array<string, mixed>>,
     *     blockers:array<int, string>
     * }
     */
    private function resolveMappings(
        ?Semester $semester
    ): array {
        $configuredMappings = (array) config(
            'kelas_group_assignment.mapping',
            []
        );

        $parents = Kelas::query()
            ->induk()
            ->with([
                'children' => fn($query) =>
                $query
                    ->where('is_active', true)
                    ->orderBy('urutan')
                    ->orderBy('kelompok'),
            ])
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();

        $mapping = [];
        $blockers = [];

        foreach ($parents as $parent) {
            $counts = [
                'santri' => Santri::query()
                    ->active()
                    ->where('kelas_id', $parent->id)
                    ->count(),

                'musyrif' => Musyrif::query()
                    ->where('kelas_id', $parent->id)
                    ->count(),

                'placement' => $semester
                    ? SantriSemesterPlacement::query()
                    ->forSemester($semester->id)
                    ->active()
                    ->where('kelas_id', $parent->id)
                    ->count()
                    : 0,
            ];

            $assignmentCount =
                array_sum($counts);

            $target = $this->resolveTargetChild(
                $parent,
                $configuredMappings
            );

            if (
                $assignmentCount > 0
                && !$parent->is_active
            ) {
                $blockers[] =
                    "{$parent->nama_kelas} tidak aktif tetapi masih mempunyai assignment.";
            }

            if (
                $assignmentCount > 0
                && !$target
            ) {
                $blockers[] =
                    "Target kelompok untuk {$parent->nama_kelas} belum dapat ditentukan.";
            }

            $mapping[] = [
                'parent_id' => (int) $parent->id,
                'parent_name' =>
                (string) $parent->nama_kelas,
                'parent_code' =>
                (string) ($parent->kode ?? ''),
                'parent_active' =>
                (bool) $parent->is_active,
                'target_id' =>
                $target?->id
                    ? (int) $target->id
                    : null,
                'target_name' =>
                $target?->nama_kelas,
                'target_code' =>
                $target?->kode,
                'target_group' =>
                $target?->kelompok,
                'counts' => $counts,
                'assignment_count' =>
                $assignmentCount,
                'available_children' =>
                $parent->children
                    ->map(fn(Kelas $child) => [
                        'id' => (int) $child->id,
                        'name' =>
                        $child->nama_kelas,
                        'code' => $child->kode,
                        'group' =>
                        $child->kelompok,
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return [
            'mapping' => $mapping,
            'blockers' =>
            array_values(
                array_unique($blockers)
            ),
        ];
    }

    private function resolveTargetChild(
        Kelas $parent,
        array $configuredMappings
    ): ?Kelas {
        $selector = null;

        foreach (
            [
                (string) $parent->id,
                (string) ($parent->kode ?? ''),
                (string) $parent->nama_kelas,
            ] as $key
        ) {
            if (
                $key !== ''
                && array_key_exists(
                    $key,
                    $configuredMappings
                )
            ) {
                $selector =
                    $configuredMappings[$key];

                break;
            }
        }

        $children = $parent->children;

        if ($selector !== null) {
            $normalized = mb_strtolower(
                trim((string) $selector)
            );

            $matched = $children->first(
                function (Kelas $child) use (
                    $selector,
                    $normalized
                ): bool {
                    return (
                        is_numeric($selector)
                        && (int) $child->id
                        === (int) $selector
                    ) || mb_strtolower(
                        trim((string) $child->kelompok)
                    ) === $normalized
                        || mb_strtolower(
                            trim((string) $child->kode)
                        ) === $normalized
                        || mb_strtolower(
                            trim((string) $child->nama_kelas)
                        ) === $normalized;
                }
            );

            if ($matched) {
                return $matched;
            }
        }

        if (
            (bool) config(
                'kelas_group_assignment.allow_single_active_child',
                true
            )
            && $children->count() === 1
        ) {
            return $children->first();
        }

        if (
            (bool) config(
                'kelas_group_assignment.allow_default_group_when_multiple',
                false
            )
        ) {
            $defaultGroup = mb_strtolower(
                trim(
                    (string) config(
                        'kelas_group_assignment.default_group',
                        'A'
                    )
                )
            );

            return $children->first(
                fn(Kelas $child) =>
                mb_strtolower(
                    trim(
                        (string) $child->kelompok
                    )
                ) === $defaultGroup
            );
        }

        return null;
    }

    private function resolveActiveSemester(
        ?int $semesterId,
        bool $required
    ): ?Semester {
        if ($semesterId) {
            $semester = Semester::query()
                ->with('tahunAjaran:id,nama')
                ->find($semesterId);
        } else {
            $semester = Semester::query()
                ->with('tahunAjaran:id,nama')
                ->where(
                    function ($query): void {
                        $query
                            ->where('is_active', true)
                            ->orWhere(
                                'status',
                                'active'
                            );
                    }
                )
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->first();
        }

        if (!$semester && $required) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Semester aktif tidak ditemukan.',
                ],
            ]);
        }

        if (
            $semester
            && !(
                (bool) $semester->is_active
                || $semester->status === 'active'
            )
        ) {
            throw ValidationException::withMessages([
                'semester_id' => [
                    'Tahap 4 hanya boleh dijalankan pada semester aktif.',
                ],
            ]);
        }

        return $semester;
    }

    private function eventualMusyrifHandlesClass(
        Musyrif $musyrif,
        int $targetClassId,
        array $musyrifPlans
    ): bool {
        if (
            isset(
                $musyrifPlans[(int) $musyrif->id]
            )
        ) {
            return (int) $musyrifPlans[(int) $musyrif->id]['to_kelas_id']
                === $targetClassId;
        }

        return $musyrif->handlesKelas($targetClassId);
    }

    private function sourceParentForCurrentClass(
        ?int $kelasId,
        Collection $mappingByParent
    ): ?int {
        if (!$kelasId) {
            return null;
        }

        if ($mappingByParent->has($kelasId)) {
            return $kelasId;
        }

        $kelas = Kelas::query()
            ->select([
                'id',
                'parent_id',
            ])
            ->find($kelasId);

        if (
            $kelas
            && $kelas->parent_id !== null
            && $mappingByParent->has(
                (int) $kelas->parent_id
            )
        ) {
            return (int) $kelas->parent_id;
        }

        return null;
    }

    private function targetForCurrentClass(
        ?int $kelasId,
        Collection $mappingByParent
    ): ?int {
        if (!$kelasId) {
            return null;
        }

        $mapping = $mappingByParent->get(
            $kelasId
        );

        if ($mapping) {
            return (int) $mapping['target_id'];
        }

        $kelas = Kelas::query()
            ->select([
                'id',
                'parent_id',
                'is_active',
            ])
            ->find($kelasId);

        if (
            $kelas
            && $kelas->parent_id !== null
            && $kelas->is_active
        ) {
            return (int) $kelas->id;
        }

        return null;
    }

    private function assertSourceStateStillValid(
        Collection $items
    ): void {
        foreach ($items as $item) {
            $snapshot = $this->snapshotForItem(
                $item,
                true
            );

            $currentHash =
                $this->hashAssignmentState(
                    $snapshot
                );

            if (
                !hash_equals(
                    (string) $item->source_hash,
                    $currentHash
                )
            ) {
                throw ValidationException::withMessages([
                    'batch' => [
                        "Data {$item->entity_type} #{$item->entity_id} berubah setelah preview. Buat preview baru.",
                    ],
                ]);
            }
        }
    }

    private function assertTargetStateStillValid(
        Collection $items
    ): void {
        foreach ($items as $item) {
            if (!$item->target_hash) {
                throw ValidationException::withMessages([
                    'batch' => [
                        "Target hash item {$item->entity_type} #{$item->entity_id} tidak tersedia.",
                    ],
                ]);
            }

            $snapshot = $this->snapshotForItem(
                $item,
                true
            );

            $currentHash =
                $this->hashAssignmentState(
                    $snapshot
                );

            if (
                !hash_equals(
                    (string) $item->target_hash,
                    $currentHash
                )
            ) {
                throw ValidationException::withMessages([
                    'batch' => [
                        "Assignment {$item->entity_type} #{$item->entity_id} berubah setelah eksekusi. Rollback otomatis diblokir.",
                    ],
                ]);
            }
        }
    }

    private function executeMusyrifItem(
        KelasGroupAssignmentItem $item,
        Kelas $targetClass
    ): void {
        $musyrif = Musyrif::query()
            ->whereKey($item->entity_id)
            ->lockForUpdate()
            ->firstOrFail();

        $sourceKelasId = $musyrif->kelas_id;

        $musyrif->forceFill([
            'kelas_induk_id' => $targetClass->parent_id ?: $targetClass->id,
            'kelas_id' => $targetClass->id,
        ])->saveQuietly();

        $musyrif->kelasBinaan()->syncWithoutDetaching([
            (int) $targetClass->id,
        ]);

        if ($sourceKelasId) {
            $sourceClass = Kelas::query()->find($sourceKelasId);

            if ($sourceClass?->isInduk()) {
                $musyrif->kelasBinaan()->detach((int) $sourceKelasId);
            }
        }
    }

    private function executeSantriItem(
        KelasGroupAssignmentItem $item,
        Kelas $targetClass
    ): void {
        $santri = Santri::query()
            ->whereKey($item->entity_id)
            ->lockForUpdate()
            ->firstOrFail();

        $santri->forceFill([
            'kelas_id' => $targetClass->id,
        ])->saveQuietly();
    }

    private function executePlacementItem(
        KelasGroupAssignmentItem $item,
        Kelas $targetClass,
        KelasGroupAssignmentBatch $batch,
        ?int $actorId
    ): void {
        $placement =
            SantriSemesterPlacement::query()
            ->whereKey($item->entity_id)
            ->lockForUpdate()
            ->firstOrFail();

        $this->placementService
            ->applyHierarchyClassAssignment(
                $placement,
                $targetClass,
                $batch,
                $actorId,
                now()
            );
    }

    private function rollbackMusyrifItem(
        KelasGroupAssignmentItem $item
    ): void {
        $musyrif = Musyrif::query()
            ->whereKey($item->entity_id)
            ->lockForUpdate()
            ->firstOrFail();

        $sourceKelasId = data_get(
            $item->snapshot_before,
            'kelas_id'
        );

        $sourceKelasIndukId = data_get(
            $item->snapshot_before,
            'kelas_induk_id'
        );

        if (!$sourceKelasIndukId && $sourceKelasId) {
            $sourceClass = Kelas::query()->find($sourceKelasId);
            $sourceKelasIndukId = $sourceClass?->parent_id ?: $sourceClass?->id;
        }

        $musyrif->forceFill([
            'kelas_induk_id' => $sourceKelasIndukId,
            'kelas_id' => $sourceKelasId,
        ])->saveQuietly();

        if ($sourceKelasId) {
            $musyrif->kelasBinaan()->syncWithoutDetaching([
                (int) $sourceKelasId,
            ]);
        }
    }

    private function rollbackSantriItem(
        KelasGroupAssignmentItem $item
    ): void {
        $santri = Santri::query()
            ->whereKey($item->entity_id)
            ->lockForUpdate()
            ->firstOrFail();

        $santri->forceFill([
            'kelas_id' =>
            data_get(
                $item->snapshot_before,
                'kelas_id'
            ),
        ])->saveQuietly();
    }

    private function rollbackPlacementItem(
        KelasGroupAssignmentItem $item,
        KelasGroupAssignmentBatch $batch,
        ?int $actorId
    ): void {
        $placement =
            SantriSemesterPlacement::query()
            ->whereKey($item->entity_id)
            ->lockForUpdate()
            ->firstOrFail();

        $this->placementService
            ->restoreHierarchyClassAssignment(
                $placement,
                $item->snapshot_before ?? [],
                $batch,
                $actorId,
                now()
            );
    }

    private function findOperationalTarget(
        KelasGroupAssignmentItem $item
    ): Kelas {
        $targetClass = Kelas::query()
            ->with('parent')
            ->operasional()
            ->find($item->to_kelas_id);

        if (!$targetClass) {
            throw ValidationException::withMessages([
                'batch' => [
                    "Target kelas item {$item->entity_type} #{$item->entity_id} bukan kelas kelompok aktif.",
                ],
            ]);
        }

        $sourceParentId = data_get(
            $item->snapshot_before,
            'source_parent_id'
        );

        if (
            $sourceParentId !== null
            && (int) $targetClass->parent_id
            !== (int) $sourceParentId
        ) {
            throw ValidationException::withMessages([
                'batch' => [
                    "Target kelas item {$item->entity_type} #{$item->entity_id} tidak berada di parent asal.",
                ],
            ]);
        }

        return $targetClass;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotForItem(
        KelasGroupAssignmentItem $item,
        bool $lockForUpdate = false
    ): array {
        $santriQuery = Santri::query()
            ->with('kelas');

        $musyrifQuery = Musyrif::query()
            ->with('kelas');

        $placementQuery =
            SantriSemesterPlacement::query()
            ->with('kelas');

        if ($lockForUpdate) {
            $santriQuery->lockForUpdate();
            $musyrifQuery->lockForUpdate();
            $placementQuery->lockForUpdate();
        }

        return match ($item->entity_type) {
            KelasGroupAssignmentItem::ENTITY_SANTRI =>
            $this->snapshotSantri(
                $santriQuery->findOrFail(
                    $item->entity_id
                )
            ),

            KelasGroupAssignmentItem::ENTITY_MUSYRIF =>
            $this->snapshotMusyrif(
                $musyrifQuery->findOrFail(
                    $item->entity_id
                )
            ),

            KelasGroupAssignmentItem::ENTITY_PLACEMENT =>
            $this->snapshotPlacement(
                $placementQuery->findOrFail(
                    $item->entity_id
                )
            ),

            default => throw new RuntimeException(
                "Jenis item {$item->entity_type} tidak didukung."
            ),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotSantri(
        Santri $santri
    ): array {
        $santri->loadMissing('kelas');

        return [
            'entity_type' =>
            KelasGroupAssignmentItem::ENTITY_SANTRI,
            'id' => (int) $santri->id,
            'nama' => $santri->nama,
            'status' => $santri->status,
            'kelas_id' =>
            $santri->kelas_id
                ? (int) $santri->kelas_id
                : null,
            'kelas_nama' =>
            $santri->kelas?->nama_kelas,
            'source_parent_id' =>
            $santri->kelas
                ? (int) (
                    $santri->kelas->parent_id
                    ?: $santri->kelas->id
                )
                : null,
            'musyrif_id' =>
            $santri->musyrif_id
                ? (int) $santri->musyrif_id
                : null,
            'updated_at' =>
            $santri->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotMusyrif(
        Musyrif $musyrif
    ): array {
        $musyrif->loadMissing(['kelas', 'kelasInduk', 'kelasBinaan']);

        $sourceParentId = $musyrif->kelas
            ? (int) (
                $musyrif->kelas->parent_id
                ?: $musyrif->kelas->id
            )
            : null;

        return [
            'entity_type' =>
            KelasGroupAssignmentItem::ENTITY_MUSYRIF,
            'id' => (int) $musyrif->id,
            'nama' => $musyrif->nama,
            'kelas_induk_id' =>
            $musyrif->kelas_induk_id
                ? (int) $musyrif->kelas_induk_id
                : $sourceParentId,
            'kelas_induk_nama' =>
            $musyrif->kelasInduk?->nama_kelas,
            'kelas_id' =>
            $musyrif->kelas_id
                ? (int) $musyrif->kelas_id
                : null,
            'kelas_nama' =>
            $musyrif->kelas?->nama_kelas,
            'source_parent_id' =>
            $sourceParentId,
            'kelas_binaan_ids' =>
            $musyrif->kelasBinaan
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all(),
            'updated_at' =>
            $musyrif->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotPlacement(
        SantriSemesterPlacement $placement
    ): array {
        $placement->loadMissing('kelas');

        return [
            'entity_type' =>
            KelasGroupAssignmentItem::ENTITY_PLACEMENT,
            'id' => (int) $placement->id,
            'santri_id' =>
            (int) $placement->santri_id,
            'semester_id' =>
            (int) $placement->semester_id,
            'kelas_id' =>
            $placement->kelas_id
                ? (int) $placement->kelas_id
                : null,
            'kelas_nama' =>
            $placement->kelas?->nama_kelas,
            'source_parent_id' =>
            $placement->kelas
                ? (int) (
                    $placement->kelas->parent_id
                    ?: $placement->kelas->id
                )
                : null,
            'musyrif_id' =>
            $placement->musyrif_id
                ? (int) $placement->musyrif_id
                : null,
            'status' =>
            $placement->status,
            'placement_type' =>
            $placement->placement_type,
            'started_at' =>
            $placement->started_at?->toIso8601String(),
            'ended_at' =>
            $placement->ended_at?->toIso8601String(),
            'note' => $placement->note,
            'metadata' =>
            $placement->metadata ?? [],
            'created_by' =>
            $placement->created_by,
            'updated_by' =>
            $placement->updated_by,
            'updated_at' =>
            $placement->updated_at?->toIso8601String(),
        ];
    }

    private function hashAssignmentState(
        array $snapshot
    ): string {
        $entityType = data_get(
            $snapshot,
            'entity_type'
        );

        $state = match ($entityType) {
            KelasGroupAssignmentItem::ENTITY_SANTRI => [
                'entity_type' => $entityType,
                'id' => data_get($snapshot, 'id'),
                'kelas_id' =>
                data_get($snapshot, 'kelas_id'),
                'musyrif_id' =>
                data_get($snapshot, 'musyrif_id'),
                'status' =>
                data_get($snapshot, 'status'),
            ],

            KelasGroupAssignmentItem::ENTITY_MUSYRIF => [
                'entity_type' => $entityType,
                'id' => data_get($snapshot, 'id'),
                'kelas_id' =>
                data_get($snapshot, 'kelas_id'),
            ],

            KelasGroupAssignmentItem::ENTITY_PLACEMENT => [
                'entity_type' => $entityType,
                'id' => data_get($snapshot, 'id'),
                'santri_id' =>
                data_get($snapshot, 'santri_id'),
                'semester_id' =>
                data_get($snapshot, 'semester_id'),
                'kelas_id' =>
                data_get($snapshot, 'kelas_id'),
                'musyrif_id' =>
                data_get($snapshot, 'musyrif_id'),
                'status' =>
                data_get($snapshot, 'status'),
                'placement_type' =>
                data_get(
                    $snapshot,
                    'placement_type'
                ),
                'started_at' =>
                data_get($snapshot, 'started_at'),
                'ended_at' =>
                data_get($snapshot, 'ended_at'),
                'note' =>
                data_get($snapshot, 'note'),
                'metadata' =>
                data_get(
                    $snapshot,
                    'metadata',
                    []
                ),
                'created_by' =>
                data_get($snapshot, 'created_by'),
                'updated_by' =>
                data_get($snapshot, 'updated_by'),
            ],

            default => throw new RuntimeException(
                'Entity snapshot tidak valid.'
            ),
        };

        return hash(
            'sha256',
            $this->canonicalJson($state)
        );
    }

    private function canonicalJson(
        mixed $value
    ): string {
        $normalized =
            $this->normalizeForHash(
                $value
            );

        return json_encode(
            $normalized,
            JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
        );
    }

    private function normalizeForHash(
        mixed $value
    ): mixed {
        if (!is_array($value)) {
            return $value;
        }

        if (
            array_keys($value)
            !== range(
                0,
                count($value) - 1
            )
        ) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] =
                $this->normalizeForHash(
                    $item
                );
        }

        return $value;
    }

    private function makeBatchCode(): string
    {
        $prefix = strtoupper(
            trim(
                (string) config(
                    'kelas_group_assignment.batch_prefix',
                    'KGA'
                )
            )
        );

        return sprintf(
            '%s-%s-%s',
            $prefix ?: 'KGA',
            now()->format('Ymd-His'),
            strtoupper(
                Str::random(5)
            )
        );
    }

    private function semesterLabel(
        Semester $semester
    ): string {
        return trim(
            ucfirst((string) $semester->nama)
                . ' — '
                . (
                    $semester->tahunAjaran?->nama
                    ?? 'Tahun ajaran tidak tersedia'
                )
        );
    }
}
