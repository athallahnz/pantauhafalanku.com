<?php

namespace App\Support\Academic;

use App\Models\Santri;
use App\Models\SantriMigrationBatch;
use App\Models\SantriMigrationBatchItem;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

trait HandlesSantriMigrationBatches
{
    private function makeSourceHash(
        int $santriId,
        ?int $kelasId,
        ?int $musyrifId,
        string $status
    ): string {
        return hash(
            'sha256',
            json_encode(
                [
                    'santri_id' => $santriId,
                    'kelas_id' => $kelasId,
                    'musyrif_id' => $musyrifId,
                    'status' => $status,
                ],
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );
    }

    private function calculateBatchSnapshotHash(
        SantriMigrationBatch $batch,
        Collection $items
    ): string {
        $payload = [
            'mode' => $batch->mode,
            'from_semester_id' => (int) $batch->from_semester_id,
            'to_semester_id' => (int) $batch->to_semester_id,
            'items' => $items
                ->sortBy('santri_id')
                ->values()
                ->map(
                    fn (SantriMigrationBatchItem $item) => [
                        'santri_id' => (int) $item->santri_id,
                        'from_kelas_id' => $item->from_kelas_id !== null
                            ? (int) $item->from_kelas_id
                            : null,
                        'to_kelas_id' => $item->to_kelas_id !== null
                            ? (int) $item->to_kelas_id
                            : null,
                        'from_musyrif_id' => $item->from_musyrif_id !== null
                            ? (int) $item->from_musyrif_id
                            : null,
                        'transition_type' => $item->transition_type,
                        'source_hash' => $item->source_hash,
                    ]
                )
                ->all(),
        ];

        return hash(
            'sha256',
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );
    }

    private function cancelSupersededPreviewBatches(
        int $userId,
        string $mode,
        int $fromSemesterId,
        int $toSemesterId
    ): void {
        SantriMigrationBatch::query()
            ->where('created_by', $userId)
            ->where('mode', $mode)
            ->where('from_semester_id', $fromSemesterId)
            ->where('to_semester_id', $toSemesterId)
            ->where('status', SantriMigrationBatch::STATUS_PREVIEWED)
            ->update([
                'status' => SantriMigrationBatch::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'last_error' => 'Batch dibatalkan karena digantikan Preview yang lebih baru.',
                'updated_at' => now(),
            ]);
    }

    /**
     * @param Collection<int, array<string, mixed>> $plans
     */
    private function createPersistentBatch(
        string $mode,
        Semester $fromSemester,
        Semester $toSemester,
        Collection $plans,
        array $attributes = []
    ): SantriMigrationBatch {
        $userId = (int) auth()->id();

        return DB::transaction(function () use (
            $mode,
            $fromSemester,
            $toSemester,
            $plans,
            $attributes,
            $userId
        ) {
            $this->cancelSupersededPreviewBatches(
                $userId,
                $mode,
                (int) $fromSemester->id,
                (int) $toSemester->id
            );

            $batch = SantriMigrationBatch::query()->create([
                'mode' => $mode,
                'status' => SantriMigrationBatch::STATUS_PREVIEWED,
                'from_semester_id' => $fromSemester->id,
                'to_semester_id' => $toSemester->id,
                'from_kelas_id' => $attributes['from_kelas_id'] ?? null,
                'to_kelas_id' => $attributes['to_kelas_id'] ?? null,
                'transition_type' => $attributes['transition_type'] ?? null,
                'include_graduation' => (bool) ($attributes['include_graduation'] ?? false),
                'note' => $attributes['note'] ?? null,
                'metadata' => $attributes['metadata'] ?? null,
                'created_by' => $userId,
                'previewed_at' => now(),
                'expires_at' => now()->addHours(2),
            ]);

            foreach ($plans as $plan) {
                $sourceSnapshot = $plan['source_snapshot'];

                $batch->items()->create([
                    'santri_id' => $plan['santri_id'],
                    'from_kelas_id' => $plan['from_kelas_id'],
                    'to_kelas_id' => $plan['to_kelas_id'],
                    'from_musyrif_id' => $plan['from_musyrif_id'],
                    'to_musyrif_id' => null,
                    'transition_type' => $plan['transition_type'],
                    'assignment_required' => (bool) $plan['assignment_required'],
                    'status' => SantriMigrationBatchItem::STATUS_PENDING,
                    'source_hash' => $this->makeSourceHash(
                        (int) $plan['santri_id'],
                        $plan['from_kelas_id'] !== null
                            ? (int) $plan['from_kelas_id']
                            : null,
                        $plan['from_musyrif_id'] !== null
                            ? (int) $plan['from_musyrif_id']
                            : null,
                        (string) ($sourceSnapshot['status'] ?? Santri::STATUS_AKTIF)
                    ),
                    'source_snapshot' => $sourceSnapshot,
                    'target_snapshot' => $plan['target_snapshot'] ?? null,
                ]);
            }

            $items = $batch->items()->orderBy('id')->get();

            $batch->forceFill([
                'items_count' => $items->count(),
                'snapshot_hash' => $this->calculateBatchSnapshotHash($batch, $items),
            ])->save();

            return $batch->fresh([
                'items',
                'fromSemester',
                'toSemester',
            ]);
        });
    }

    private function serializeBatch(SantriMigrationBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'code' => $batch->code,
            'mode' => $batch->mode,
            'status' => $batch->status,
            'items_count' => (int) $batch->items_count,
            'completed_count' => (int) $batch->completed_count,
            'graduated_count' => (int) $batch->graduated_count,
            'snapshot_hash' => $batch->snapshot_hash,
            'previewed_at' => $batch->previewed_at?->toIso8601String(),
            'executed_at' => $batch->executed_at?->toIso8601String(),
            'expires_at' => $batch->expires_at?->toIso8601String(),
        ];
    }

    private function validateBatchExecutePayload(Request $request): array
    {
        return $request->validate([
            'batch_id' => [
                'required',
                'uuid',
                'exists:santri_migration_batches,id',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.batch_item_id' => [
                'required',
                'integer',
                'distinct',
                'exists:santri_migration_batch_items,id',
            ],
            'items.*.to_musyrif_id' => [
                'nullable',
                'integer',
                'exists:musyrifs,id',
            ],
        ]);
    }

    private function rememberBatchValidationError(
        ?string $batchId,
        string $message
    ): void {
        if (!$batchId) {
            return;
        }

        SantriMigrationBatch::query()
            ->whereKey($batchId)
            ->where('status', SantriMigrationBatch::STATUS_PREVIEWED)
            ->update([
                'last_error' => $message,
                'updated_at' => now(),
            ]);
    }

    private function executePersistentBatch(
        Request $request,
        string $expectedMode
    ) {
        $data = $this->validateBatchExecutePayload($request);
        $batchId = (string) $data['batch_id'];

        try {
            $result = DB::transaction(function () use (
                $data,
                $batchId,
                $expectedMode
            ) {
                $batch = SantriMigrationBatch::query()
                    ->whereKey($batchId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($batch->mode !== $expectedMode) {
                    throw ValidationException::withMessages([
                        'batch_id' => [
                            'Mode batch tidak sesuai dengan endpoint eksekusi.',
                        ],
                    ]);
                }

                if ((int) $batch->created_by !== (int) auth()->id()) {
                    throw ValidationException::withMessages([
                        'batch_id' => [
                            'Batch hanya dapat dieksekusi oleh Admin yang membuat Preview.',
                        ],
                    ]);
                }

                if ($batch->isExpired()) {
                    $batch->forceFill([
                        'status' => SantriMigrationBatch::STATUS_EXPIRED,
                        'last_error' => 'Batch kedaluwarsa sebelum dieksekusi.',
                    ])->save();

                    return [
                        'ok' => false,
                        'message' => 'Batch Preview sudah kedaluwarsa. Jalankan Preview ulang.',
                    ];
                }

                if ($batch->status !== SantriMigrationBatch::STATUS_PREVIEWED) {
                    throw ValidationException::withMessages([
                        'batch_id' => [
                            "Batch berstatus {$batch->status} dan tidak dapat dieksekusi.",
                        ],
                    ]);
                }

                [$fromSemester, $toSemester] = $this->resolveSemesterTransition(
                    (int) $batch->from_semester_id,
                    (int) $batch->to_semester_id,
                    true
                );

                $batchItems = SantriMigrationBatchItem::query()
                    ->where('batch_id', $batch->id)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $submittedItems = collect($data['items'])
                    ->values()
                    ->map(function (array $item, int $index) {
                        return [...$item, '_index' => $index];
                    });

                $expectedItemIds = $batchItems
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values();

                $submittedItemIds = $submittedItems
                    ->pluck('batch_item_id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values();

                if ($expectedItemIds->all() !== $submittedItemIds->all()) {
                    throw ValidationException::withMessages([
                        'items' => [
                            'Daftar item tidak sama dengan batch Preview. Jalankan Preview ulang.',
                        ],
                    ]);
                }

                $storedHash = $this->calculateBatchSnapshotHash($batch, $batchItems);

                if (
                    !$batch->snapshot_hash
                    || !hash_equals($batch->snapshot_hash, $storedHash)
                ) {
                    throw ValidationException::withMessages([
                        'batch_id' => [
                            'Isi batch berubah setelah Preview. Jalankan Preview ulang.',
                        ],
                    ]);
                }

                $santriIds = $batchItems
                    ->pluck('santri_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                $santris = Santri::query()
                    ->whereIn('id', $santriIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($santris->count() !== $santriIds->count()) {
                    throw ValidationException::withMessages([
                        'batch_id' => [
                            'Ada santri batch yang sudah tidak tersedia. Jalankan Preview ulang.',
                        ],
                    ]);
                }

                $submittedByItemId = $submittedItems->keyBy(
                    fn (array $item) => (int) $item['batch_item_id']
                );

                $resolvedAssignments = [];

                foreach ($batchItems as $batchItem) {
                    /** @var Santri|null $santri */
                    $santri = $santris->get((int) $batchItem->santri_id);

                    if (!$santri || !$santri->isActive()) {
                        throw ValidationException::withMessages([
                            'batch_id' => [
                                'Status santri berubah setelah Preview. Jalankan Preview ulang.',
                            ],
                        ]);
                    }

                    $currentSourceHash = $this->makeSourceHash(
                        (int) $santri->id,
                        $santri->kelas_id !== null ? (int) $santri->kelas_id : null,
                        $santri->musyrif_id !== null ? (int) $santri->musyrif_id : null,
                        (string) $santri->status
                    );

                    if (!hash_equals($batchItem->source_hash, $currentSourceHash)) {
                        throw ValidationException::withMessages([
                            'batch_id' => [
                                "Data {$santri->nama} berubah setelah Preview. Jalankan Preview ulang.",
                            ],
                        ]);
                    }

                    $submitted = $submittedByItemId->get((int) $batchItem->id);

                    $toMusyrifId = isset($submitted['to_musyrif_id'])
                        ? (int) $submitted['to_musyrif_id']
                        : null;

                    $resolvedAssignments[(int) $batchItem->id] =
                        $this->resolveEffectiveMusyrifId(
                            $santri,
                            $batchItem->transition_type,
                            $batchItem->to_kelas_id !== null
                                ? (int) $batchItem->to_kelas_id
                                : null,
                            $toMusyrifId,
                            "items.{$submitted['_index']}.to_musyrif_id"
                        );
                }

                $batch->forceFill([
                    'status' => SantriMigrationBatch::STATUS_EXECUTING,
                    'executing_at' => now(),
                    'executed_by' => auth()->id(),
                    'last_error' => null,
                ])->save();

                $executedAt = now();

                $placementService = app(
                    SantriSemesterPlacementService::class
                );

                /*
                |--------------------------------------------------------------------------
                | Persist placement semester asal sebelum data santris berubah
                |--------------------------------------------------------------------------
                */
                foreach ($batchItems as $batchItem) {
                    $santri = $santris->get(
                        (int) $batchItem->santri_id
                    );

                    $this->saveSourceSnapshot(
                        $santri,
                        $fromSemester,
                        (int) auth()->id()
                    );

                    $placementService
                        ->ensureSourcePlacement(
                            $santri,
                            $fromSemester,
                            $batch,
                            $batchItem,
                            (int) auth()->id(),
                            $executedAt
                        );
                }

                $summary = [];
                $completedCount = 0;
                $graduatedCount = 0;

                foreach ($batchItems as $batchItem) {
                    $santri = $santris->get((int) $batchItem->santri_id);
                    $effectiveMusyrifId = $resolvedAssignments[(int) $batchItem->id];

                    $this->applyTransition(
                        $santri,
                        $toSemester,
                        $batchItem->transition_type,
                        $batchItem->to_kelas_id !== null
                            ? (int) $batchItem->to_kelas_id
                            : null,
                        $effectiveMusyrifId,
                        $batch->note,
                        (int) auth()->id(),
                        "items.{$batchItem->id}.to_musyrif_id"
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Placement semester tujuan menjadi historical source of truth
                    |--------------------------------------------------------------------------
                    */
                    $placementService
                        ->writeTargetPlacement(
                            $santri,
                            $toSemester,
                            $batch,
                            $batchItem,
                            $effectiveMusyrifId,
                            (int) auth()->id(),
                            $executedAt
                        );

                    $batchItem->forceFill([
                        'to_musyrif_id' => $effectiveMusyrifId,
                        'status' => SantriMigrationBatchItem::STATUS_COMPLETED,
                        'error_message' => null,
                        'executed_at' => now(),
                    ])->save();

                    $source = $batchItem->source_snapshot ?? [];
                    $target = $batchItem->target_snapshot ?? [];
                    $summaryKey = sprintf(
                        '%s->%s:%s',
                        $batchItem->from_kelas_id ?? 'null',
                        $batchItem->to_kelas_id ?? 'lulus',
                        $batchItem->transition_type
                    );

                    if (!isset($summary[$summaryKey])) {
                        $summary[$summaryKey] = [
                            'from' => $source['kelas_nama'] ?? $batchItem->from_kelas_id,
                            'to' => $target['kelas_nama'] ?? (
                                $batchItem->transition_type === 'lulus'
                                    ? 'LULUS'
                                    : $batchItem->to_kelas_id
                            ),
                            'tipe' => $batchItem->transition_type,
                            'affected' => 0,
                        ];
                    }

                    $summary[$summaryKey]['affected']++;
                    $completedCount++;

                    if ($batchItem->transition_type === 'lulus') {
                        $graduatedCount++;
                    }
                }

                $metadata = $batch->metadata ?? [];
                $metadata['execution_summary'] = array_values($summary);

                $batch->forceFill([
                    'status' => SantriMigrationBatch::STATUS_COMPLETED,
                    'completed_count' => $completedCount,
                    'graduated_count' => $graduatedCount,
                    'metadata' => $metadata,
                    'executed_at' => now(),
                    'failed_at' => null,
                    'last_error' => null,
                ])->save();

                return [
                    'ok' => true,
                    'message' => "Batch {$batch->code} berhasil memproses {$completedCount} santri.",
                    'batch' => $this->serializeBatch($batch->fresh()),
                    'summary' => array_values($summary),
                ];
            });

            if (!$result['ok']) {
                return response()->json($result, 422);
            }

            return response()->json($result);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?? $exception->getMessage();

            $this->rememberBatchValidationError($batchId, (string) $message);
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            SantriMigrationBatch::query()
                ->whereKey($batchId)
                ->whereIn('status', [
                    SantriMigrationBatch::STATUS_PREVIEWED,
                    SantriMigrationBatch::STATUS_EXECUTING,
                ])
                ->update([
                    'status' => SantriMigrationBatch::STATUS_FAILED,
                    'last_error' => $exception->getMessage(),
                    'failed_at' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'ok' => false,
                'message' => 'Eksekusi batch gagal. Detail kesalahan tersimpan pada log batch.',
            ], 500);
        }
    }
}
