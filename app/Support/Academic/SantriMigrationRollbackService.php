<?php

namespace App\Support\Academic;

use App\Models\Santri;
use App\Models\SantriMigrationBatch;
use App\Models\SantriMigrationBatchItem;
use App\Models\SantriSemesterPlacement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SantriMigrationRollbackService
{
    public function inspect(
        SantriMigrationBatch $batch
    ): array {
        $batch->loadMissing([
            'fromSemester',
            'toSemester',
            'items',
        ]);

        return $this->buildInspection(
            $batch,
            $batch->items
        );
    }

    public function rollback(
        SantriMigrationBatch $batch,
        int $userId,
        string $reason
    ): array {
        try {
            return DB::transaction(function () use (
                $batch,
                $userId,
                $reason
            ): array {
                $lockedBatch = SantriMigrationBatch::query()
                    ->whereKey($batch->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedBatch->loadMissing([
                    'fromSemester',
                    'toSemester',
                ]);

                $items = SantriMigrationBatchItem::query()
                    ->where(
                        'batch_id',
                        $lockedBatch->id
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $santriIds = $items
                    ->pluck('santri_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                Santri::query()
                    ->whereIn('id', $santriIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                SantriSemesterPlacement::query()
                    ->whereIn('santri_id', $santriIds)
                    ->whereIn('semester_id', [
                        (int) $lockedBatch->from_semester_id,
                        (int) $lockedBatch->to_semester_id,
                    ])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $inspection = $this->buildInspection(
                    $lockedBatch,
                    $items
                );

                if (!$inspection['eligible']) {
                    throw ValidationException::withMessages([
                        'batch_id' => [
                            $inspection['blockers'][0]['message']
                                ?? 'Batch tidak memenuhi syarat rollback.',
                        ],
                    ]);
                }

                $lockedBatch->forceFill([
                    'status' =>
                        SantriMigrationBatch::STATUS_ROLLING_BACK,
                    'rollback_error' => null,
                ])->save();

                $rolledBackAt = now();
                $rolledBackItems = 0;
                $restoredGraduates = 0;

                $santris = Santri::query()
                    ->whereIn('id', $santriIds)
                    ->get()
                    ->keyBy('id');

                $placements = SantriSemesterPlacement::query()
                    ->whereIn('santri_id', $santriIds)
                    ->whereIn('semester_id', [
                        (int) $lockedBatch->from_semester_id,
                        (int) $lockedBatch->to_semester_id,
                    ])
                    ->get()
                    ->keyBy(
                        fn (SantriSemesterPlacement $placement) =>
                            $placement->santri_id
                            . ':'
                            . $placement->semester_id
                    );

                foreach ($items as $item) {
                    /** @var Santri $santri */
                    $santri = $santris->get(
                        (int) $item->santri_id
                    );

                    $source = $item->source_snapshot ?? [];
                    $sourceStatus = (string) (
                        $source['status']
                        ?? Santri::STATUS_AKTIF
                    );

                    $sourcePlacementKey =
                        $santri->id
                        . ':'
                        . $lockedBatch->from_semester_id;

                    $targetPlacementKey =
                        $santri->id
                        . ':'
                        . $lockedBatch->to_semester_id;

                    /** @var SantriSemesterPlacement $sourcePlacement */
                    $sourcePlacement = $placements->get(
                        $sourcePlacementKey
                    );

                    /** @var SantriSemesterPlacement $targetPlacement */
                    $targetPlacement = $placements->get(
                        $targetPlacementKey
                    );

                    $targetPlacement->delete();

                    $sourceMetadata =
                        $sourcePlacement->metadata ?? [];

                    unset(
                        $sourceMetadata[
                            'closed_by_migration'
                        ]
                    );

                    $sourceMetadata[
                        'rollback_reopened'
                    ] = [
                        'batch_id' => $lockedBatch->id,
                        'batch_code' => $lockedBatch->code,
                        'rolled_back_at' =>
                            $rolledBackAt
                                ->toIso8601String(),
                        'rolled_back_by' => $userId,
                    ];

                    $sourcePlacement->forceFill([
                        'status' => $sourceStatus,
                        'kelas_id' =>
                            $item->from_kelas_id,
                        'musyrif_id' =>
                            $item->from_musyrif_id,
                        'ended_at' => null,
                        'metadata' => $sourceMetadata,
                        'updated_by' => $userId,
                    ])->save();

                    $previousStatus =
                        (string) $santri->status;

                    $restorePayload = [
                        'kelas_id' =>
                            $item->from_kelas_id,
                        'musyrif_id' =>
                            $item->from_musyrif_id,
                        'status' => $sourceStatus,
                        'graduated_semester_id' => null,
                        'graduated_at' => null,
                    ];

                    if (
                        $previousStatus
                        !== $sourceStatus
                    ) {
                        $restorePayload[
                            'status_changed_at'
                        ] = $rolledBackAt;

                        $restorePayload[
                            'status_reason'
                        ] = "Rollback batch {$lockedBatch->code}: {$reason}";

                        $restorePayload[
                            'status_changed_by'
                        ] = $userId;
                    }

                    $santri->forceFill(
                        $restorePayload
                    )->save();

                    if (
                        $previousStatus
                        !== $sourceStatus
                    ) {
                        $santri->statusHistories()
                            ->create([
                                'from_status' =>
                                    $previousStatus,
                                'to_status' =>
                                    $sourceStatus,
                                'semester_id' =>
                                    $lockedBatch
                                        ->to_semester_id,
                                'kelas_id' =>
                                    $item->from_kelas_id,
                                'musyrif_id' =>
                                    $item->from_musyrif_id,
                                'reason' =>
                                    "Rollback batch {$lockedBatch->code}: {$reason}",
                                'changed_by' => $userId,
                                'changed_at' =>
                                    $rolledBackAt,
                                'metadata' => [
                                    'source' =>
                                        'migration_batch_rollback',
                                    'batch_id' =>
                                        $lockedBatch->id,
                                    'batch_item_id' =>
                                        $item->id,
                                ],
                            ]);
                    }

                    if (
                        $item->transition_type
                        === 'lulus'
                    ) {
                        $restoredGraduates++;
                    }

                    $item->forceFill([
                        'status' =>
                            SantriMigrationBatchItem::STATUS_ROLLED_BACK,
                        'rolled_back_at' =>
                            $rolledBackAt,
                        'rollback_error' => null,
                    ])->save();

                    $rolledBackItems++;
                }

                $metadata =
                    $lockedBatch->metadata ?? [];

                $metadata['rollback_summary'] = [
                    'rolled_back_items' =>
                        $rolledBackItems,
                    'restored_graduates' =>
                        $restoredGraduates,
                    'strict_transaction_check' =>
                        $inspection[
                            'transaction_counts'
                        ],
                ];

                $lockedBatch->forceFill([
                    'status' =>
                        SantriMigrationBatch::STATUS_ROLLED_BACK,
                    'rolled_back_by' => $userId,
                    'rolled_back_at' => $rolledBackAt,
                    'rollback_reason' => $reason,
                    'rollback_metadata' => [
                        'inspection' => $inspection,
                        'summary' =>
                            $metadata[
                                'rollback_summary'
                            ],
                    ],
                    'metadata' => $metadata,
                    'rollback_error' => null,
                    'last_error' => null,
                ])->save();

                return [
                    'ok' => true,
                    'message' =>
                        "Batch {$lockedBatch->code} berhasil di-rollback. {$rolledBackItems} santri dikembalikan ke placement semester asal.",
                    'batch_id' => $lockedBatch->id,
                    'rolled_back_items' =>
                        $rolledBackItems,
                    'restored_graduates' =>
                        $restoredGraduates,
                    'rolled_back_at' =>
                        $rolledBackAt
                            ->toIso8601String(),
                ];
            });
        } catch (Throwable $exception) {
            if (!($exception instanceof ValidationException)) {
                report($exception);

                SantriMigrationBatch::query()
                    ->whereKey($batch->id)
                    ->where(
                        'status',
                        SantriMigrationBatch::STATUS_COMPLETED
                    )
                    ->update([
                        'rollback_error' =>
                            $exception->getMessage(),
                        'updated_at' => now(),
                    ]);
            }

            throw $exception;
        }
    }

    private function buildInspection(
        SantriMigrationBatch $batch,
        Collection $items
    ): array {
        $blockers = [];

        if (
            $batch->status
            !== SantriMigrationBatch::STATUS_COMPLETED
        ) {
            $blockers[] = $this->blocker(
                'batch_status',
                "Batch harus berstatus completed. Status saat ini: {$batch->status}."
            );
        }

        if (!$batch->executed_at) {
            $blockers[] = $this->blocker(
                'executed_at',
                'Waktu eksekusi batch tidak tersedia.'
            );
        }

        if (!$batch->fromSemester?->isActive()) {
            $blockers[] = $this->blocker(
                'from_semester',
                'Semester asal harus tetap berstatus active.'
            );
        }

        if (
            $batch->fromSemester
            && !$batch->fromSemester
                ->isInputLocked()
        ) {
            $blockers[] = $this->blocker(
                'from_semester_lock',
                'Input semester asal harus tetap terkunci selama rollback.'
            );
        }

        if (!$batch->toSemester?->isDraft()) {
            $blockers[] = $this->blocker(
                'to_semester',
                'Semester tujuan harus tetap berstatus draft dan belum diaktifkan.'
            );
        }

        if (
            $items->count()
            !== (int) $batch->items_count
        ) {
            $blockers[] = $this->blocker(
                'item_count',
                'Jumlah item database tidak sama dengan jumlah item batch.'
            );
        }

        $notCompletedItems = $items
            ->where(
                'status',
                '!=',
                SantriMigrationBatchItem::STATUS_COMPLETED
            )
            ->count();

        if ($notCompletedItems > 0) {
            $blockers[] = $this->blocker(
                'item_status',
                "{$notCompletedItems} item tidak berstatus completed."
            );
        }

        $transactionCounts = [
            'hafalan' => DB::table('hafalans')
                ->where(
                    'semester_id',
                    $batch->to_semester_id
                )
                ->count(),
            'tahsin' => DB::table('tahsins')
                ->where(
                    'semester_id',
                    $batch->to_semester_id
                )
                ->count(),
            'tilawah' => DB::table('tilawahs')
                ->where(
                    'semester_id',
                    $batch->to_semester_id
                )
                ->count(),
        ];

        foreach (
            $transactionCounts
            as $type => $count
        ) {
            if ($count > 0) {
                $blockers[] = $this->blocker(
                    "transaction_{$type}",
                    "Semester tujuan sudah memiliki {$count} transaksi {$type}."
                );
            }
        }

        $santriIds = $items
            ->pluck('santri_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $laterBatchCount = 0;

        if (
            $batch->executed_at
            && $santriIds->isNotEmpty()
        ) {
            $laterBatchCount =
                SantriMigrationBatchItem::query()
                    ->join(
                        'santri_migration_batches as later_batches',
                        'later_batches.id',
                        '=',
                        'santri_migration_batch_items.batch_id'
                    )
                    ->where(
                        'santri_migration_batch_items.batch_id',
                        '!=',
                        $batch->id
                    )
                    ->whereIn(
                        'santri_migration_batch_items.santri_id',
                        $santriIds
                    )
                    ->where(
                        'later_batches.status',
                        SantriMigrationBatch::STATUS_COMPLETED
                    )
                    ->where(
                        'later_batches.executed_at',
                        '>',
                        $batch->executed_at
                    )
                    ->count();
        }

        if ($laterBatchCount > 0) {
            $blockers[] = $this->blocker(
                'later_batch',
                "Terdapat {$laterBatchCount} item migrasi lanjutan setelah batch ini."
            );
        }

        $laterStatusChanges = 0;

        if (
            $batch->executed_at
            && $santriIds->isNotEmpty()
        ) {
            $laterStatusChanges =
                DB::table(
                    'santri_status_histories'
                )
                    ->whereIn(
                        'santri_id',
                        $santriIds
                    )
                    ->where(
                        'changed_at',
                        '>',
                        $batch->executed_at
                    )
                    ->count();
        }

        if ($laterStatusChanges > 0) {
            $blockers[] = $this->blocker(
                'later_status_change',
                "Terdapat {$laterStatusChanges} perubahan status santri setelah batch dieksekusi."
            );
        }

        $santris = Santri::query()
            ->whereIn('id', $santriIds)
            ->get()
            ->keyBy('id');

        $placements = SantriSemesterPlacement::query()
            ->whereIn('santri_id', $santriIds)
            ->whereIn('semester_id', [
                (int) $batch->from_semester_id,
                (int) $batch->to_semester_id,
            ])
            ->get()
            ->keyBy(
                fn (SantriSemesterPlacement $placement) =>
                    $placement->santri_id
                    . ':'
                    . $placement->semester_id
            );

        $stateMismatchCount = 0;
        $placementMismatchCount = 0;
        $sampleMismatches = [];

        foreach ($items as $item) {
            /** @var Santri|null $santri */
            $santri = $santris->get(
                (int) $item->santri_id
            );

            if (!$santri) {
                $stateMismatchCount++;
                $sampleMismatches[] =
                    'Santri item tidak ditemukan.';
                continue;
            }

            $isGraduation =
                $item->transition_type
                === 'lulus';

            $expectedStatus = $isGraduation
                ? Santri::STATUS_LULUS
                : Santri::STATUS_AKTIF;

            $expectedClassId = $isGraduation
                ? $item->from_kelas_id
                : $item->to_kelas_id;

            $expectedMusyrifId = $isGraduation
                ? null
                : $item->to_musyrif_id;

            $stateMatches =
                $santri->status
                    === $expectedStatus
                && $santri->kelas_id
                    === $expectedClassId
                && $santri->musyrif_id
                    === $expectedMusyrifId;

            if ($isGraduation) {
                $stateMatches = $stateMatches
                    && (int) $santri
                        ->graduated_semester_id
                        === (int) $batch
                            ->to_semester_id;
            }

            if (!$stateMatches) {
                $stateMismatchCount++;

                if (
                    count($sampleMismatches)
                    < 10
                ) {
                    $sampleMismatches[] =
                        "Kondisi {$santri->nama} sudah berbeda dari hasil batch.";
                }
            }

            $sourceKey =
                $santri->id
                . ':'
                . $batch->from_semester_id;

            $targetKey =
                $santri->id
                . ':'
                . $batch->to_semester_id;

            /** @var SantriSemesterPlacement|null $sourcePlacement */
            $sourcePlacement =
                $placements->get($sourceKey);

            /** @var SantriSemesterPlacement|null $targetPlacement */
            $targetPlacement =
                $placements->get($targetKey);

            $sourceClosedBy =
                $sourcePlacement?->metadata[
                    'closed_by_migration'
                ]['batch_id']
                ?? null;

            $sourceMatches =
                $sourcePlacement
                && $sourcePlacement->kelas_id
                    === $item->from_kelas_id
                && $sourcePlacement->musyrif_id
                    === $item->from_musyrif_id
                && $sourcePlacement->ended_at
                    !== null
                && $sourceClosedBy
                    === $batch->id;

            $targetMatches =
                $targetPlacement
                && $targetPlacement
                    ->migration_batch_id
                    === $batch->id
                && $targetPlacement
                    ->migration_batch_item_id
                    === $item->id
                && $targetPlacement->kelas_id
                    === $expectedClassId
                && $targetPlacement->musyrif_id
                    === $expectedMusyrifId
                && $targetPlacement->status
                    === $expectedStatus;

            if (
                !$sourceMatches
                || !$targetMatches
            ) {
                $placementMismatchCount++;

                if (
                    count($sampleMismatches)
                    < 10
                ) {
                    $sampleMismatches[] =
                        "Placement {$santri->nama} tidak lagi identik dengan batch.";
                }
            }
        }

        if ($stateMismatchCount > 0) {
            $blockers[] = $this->blocker(
                'santri_state',
                "{$stateMismatchCount} kondisi santri sudah berubah setelah batch."
            );
        }

        if ($placementMismatchCount > 0) {
            $blockers[] = $this->blocker(
                'placement_state',
                "{$placementMismatchCount} pasangan placement asal/tujuan tidak valid untuk rollback."
            );
        }

        return [
            'eligible' => $blockers === [],
            'batch_id' => $batch->id,
            'batch_code' => $batch->code,
            'items_count' => $items->count(),
            'transaction_counts' =>
                $transactionCounts,
            'later_batch_items' =>
                $laterBatchCount,
            'later_status_changes' =>
                $laterStatusChanges,
            'state_mismatch_count' =>
                $stateMismatchCount,
            'placement_mismatch_count' =>
                $placementMismatchCount,
            'sample_mismatches' =>
                array_values(
                    array_unique(
                        $sampleMismatches
                    )
                ),
            'blockers' => $blockers,
        ];
    }

    private function blocker(
        string $code,
        string $message
    ): array {
        return [
            'code' => $code,
            'message' => $message,
        ];
    }
}
