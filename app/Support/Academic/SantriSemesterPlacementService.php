<?php

namespace App\Support\Academic;

use App\Models\Santri;
use App\Models\SantriMigrationBatch;
use App\Models\SantriMigrationBatchItem;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class SantriSemesterPlacementService
{
    public function ensureSourcePlacement(
        Santri $santri,
        Semester $semester,
        SantriMigrationBatch $batch,
        SantriMigrationBatchItem $batchItem,
        int $userId,
        CarbonInterface $executedAt
    ): SantriSemesterPlacement {
        $placement = SantriSemesterPlacement::query()
            ->where('santri_id', $santri->id)
            ->where('semester_id', $semester->id)
            ->lockForUpdate()
            ->first();

        $expectedClassId =
            $batchItem->from_kelas_id !== null
                ? (int) $batchItem->from_kelas_id
                : null;

        $expectedMusyrifId =
            $batchItem->from_musyrif_id !== null
                ? (int) $batchItem->from_musyrif_id
                : null;

        if ($placement) {
            if (
                $placement->kelas_id !== $expectedClassId
                || $placement->musyrif_id !== $expectedMusyrifId
            ) {
                throw ValidationException::withMessages([
                    'batch_id' => [
                        "Placement semester asal untuk {$santri->nama} tidak sama dengan snapshot batch.",
                    ],
                ]);
            }

            $metadata = $placement->metadata ?? [];

            $metadata['closed_by_migration'] = [
                'batch_id' => $batch->id,
                'batch_code' => $batch->code,
                'batch_item_id' => $batchItem->id,
                'closed_at' =>
                    $executedAt->toIso8601String(),
            ];

            $placement->forceFill([
                'ended_at' => $executedAt,
                'metadata' => $metadata,
                'updated_by' => $userId,
            ])->save();

            return $placement;
        }

        return SantriSemesterPlacement::query()
            ->create([
                'santri_id' => $santri->id,
                'semester_id' => $semester->id,
                'kelas_id' => $expectedClassId,
                'musyrif_id' => $expectedMusyrifId,
                'status' =>
                    SantriSemesterPlacement::STATUS_AKTIF,
                'placement_type' =>
                    SantriSemesterPlacement::TYPE_BACKFILL,
                'started_at' =>
                    $semester->tanggal_mulai
                        ? $semester->tanggal_mulai
                            ->copy()
                            ->startOfDay()
                        : null,
                'ended_at' => $executedAt,
                'note' =>
                    'Placement semester asal dibuat otomatis saat migrasi.',
                'metadata' => [
                    'source' =>
                        'migration_source_snapshot',
                    'closed_by_migration' => [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->code,
                        'batch_item_id' =>
                            $batchItem->id,
                        'closed_at' =>
                            $executedAt
                                ->toIso8601String(),
                    ],
                ],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
    }

    public function writeTargetPlacement(
        Santri $santri,
        Semester $semester,
        SantriMigrationBatch $batch,
        SantriMigrationBatchItem $batchItem,
        ?int $effectiveMusyrifId,
        int $userId,
        CarbonInterface $executedAt
    ): SantriSemesterPlacement {
        $isGraduation =
            $batchItem->transition_type
            === SantriSemesterPlacement::TYPE_LULUS;

        $targetClassId = $isGraduation
            ? (
                $batchItem->from_kelas_id !== null
                    ? (int) $batchItem->from_kelas_id
                    : null
            )
            : (
                $batchItem->to_kelas_id !== null
                    ? (int) $batchItem->to_kelas_id
                    : null
            );

        $targetMusyrifId = $isGraduation
            ? null
            : $effectiveMusyrifId;

        $targetStatus = $isGraduation
            ? SantriSemesterPlacement::STATUS_LULUS
            : SantriSemesterPlacement::STATUS_AKTIF;

        $startedAt = $semester->tanggal_mulai
            ? $semester->tanggal_mulai
                ->copy()
                ->startOfDay()
            : $executedAt;

        $placement = SantriSemesterPlacement::query()
            ->where('santri_id', $santri->id)
            ->where('semester_id', $semester->id)
            ->lockForUpdate()
            ->first();

        if (
            $placement
            && $placement->migration_batch_id
            && $placement->migration_batch_id
                !== $batch->id
        ) {
            throw ValidationException::withMessages([
                'batch_id' => [
                    "Santri {$santri->nama} sudah memiliki placement semester tujuan dari batch lain.",
                ],
            ]);
        }

        $payload = [
            'kelas_id' => $targetClassId,
            'musyrif_id' => $targetMusyrifId,
            'status' => $targetStatus,
            'placement_type' =>
                $batchItem->transition_type,
            'started_at' => $startedAt,
            'ended_at' => $isGraduation
                ? $executedAt
                : null,
            'migration_batch_id' => $batch->id,
            'migration_batch_item_id' =>
                $batchItem->id,
            'note' => $batch->note,
            'metadata' => [
                'source' => 'migration_batch',
                'batch_code' => $batch->code,
                'batch_mode' => $batch->mode,
                'source_snapshot' =>
                    $batchItem->source_snapshot,
                'target_snapshot' =>
                    $batchItem->target_snapshot,
                'executed_at' =>
                    $executedAt->toIso8601String(),
            ],
            'updated_by' => $userId,
        ];

        if ($placement) {
            $placement->forceFill($payload)->save();

            return $placement;
        }

        return SantriSemesterPlacement::query()
            ->create([
                'santri_id' => $santri->id,
                'semester_id' => $semester->id,
                ...$payload,
                'created_by' => $userId,
            ]);
    }

    public function recordStatusChange(
        Santri $santri,
        Semester $semester,
        string $status,
        string $placementType,
        ?int $kelasId,
        ?int $musyrifId,
        ?string $reason,
        ?int $userId,
        CarbonInterface $changedAt,
        array $metadata = []
    ): SantriSemesterPlacement {
        $placement = SantriSemesterPlacement::query()
            ->where('santri_id', $santri->id)
            ->where('semester_id', $semester->id)
            ->lockForUpdate()
            ->first();

        $payload = [
            'kelas_id' => $kelasId,
            'musyrif_id' =>
                $status
                    === SantriSemesterPlacement::STATUS_AKTIF
                    ? $musyrifId
                    : null,
            'status' => $status,
            'placement_type' => $placementType,
            'started_at' => $placement?->started_at
                ?? (
                    $semester->tanggal_mulai
                        ? $semester->tanggal_mulai
                            ->copy()
                            ->startOfDay()
                        : $changedAt
                ),
            'ended_at' =>
                $status
                    === SantriSemesterPlacement::STATUS_AKTIF
                    ? null
                    : $changedAt,
            'note' => $reason,
            'metadata' => array_merge(
                $placement?->metadata ?? [],
                $metadata,
                [
                    'status_changed_at' =>
                        $changedAt
                            ->toIso8601String(),
                ]
            ),
            'updated_by' => $userId,
        ];

        if ($placement) {
            $placement->forceFill($payload)->save();

            return $placement;
        }

        return SantriSemesterPlacement::query()
            ->create([
                'santri_id' => $santri->id,
                'semester_id' => $semester->id,
                ...$payload,
                'created_by' => $userId,
            ]);
    }
}
