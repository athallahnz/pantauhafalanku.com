<?php

namespace App\Services\AcademicDocuments;

use App\Models\AcademicDocument;
use App\Models\Santri;
use App\Models\Semester;
use App\Support\AcademicDocuments\RaportDraftResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;

final class RaportDraftService
{
    public function __construct(
        private readonly RaportSnapshotService $snapshotService
    ) {
    }

    /**
     * Membuat draft baru secara idempotent.
     *
     * Jika draft aktif untuk Santri dan Semester yang sama sudah tersedia,
     * dokumen tersebut dikembalikan tanpa mengubah snapshot maupun catatan.
     *
     * @param array<string, mixed> $manualFields
     *
     * @throws JsonException
     * @throws ValidationException
     */
    public function createDraft(
        Santri $santri,
        Semester $semester,
        ?int $actorId,
        array $manualFields = []
    ): RaportDraftResult {
        $snapshotResult = $this->snapshotService->build(
            $santri,
            $semester
        );

        $snapshotResult->assertCanCreateDraft();

        return DB::transaction(
            function () use (
                $santri,
                $semester,
                $actorId,
                $manualFields,
                $snapshotResult
            ): RaportDraftResult {
                /*
                 * Lock Santri menserialisasi proses generate dokumen
                 * milik Santri yang sama.
                 */
                Santri::query()
                    ->whereKey($santri->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $currentDocument =
                    AcademicDocument::query()
                    ->where('santri_id', $santri->id)
                    ->where('semester_id', $semester->id)
                    ->where(
                        'document_type',
                        AcademicDocument::TYPE_RAPORT
                    )
                    ->where('is_current', true)
                    ->lockForUpdate()
                    ->latest('revision')
                    ->first();

                if ($currentDocument) {
                    $this->assertCurrentDocumentCanBeReused(
                        $currentDocument
                    );

                    return new RaportDraftResult(
                        document: $currentDocument->fresh([
                            'santri',
                            'semester',
                        ]),
                        created: false,
                        regenerated: false,
                        warnings: $this->snapshotWarnings(
                            $currentDocument
                        )
                    );
                }

                $revision = (
                    (int) AcademicDocument::query()
                        ->where('santri_id', $santri->id)
                        ->where('semester_id', $semester->id)
                        ->where(
                            'document_type',
                            AcademicDocument::TYPE_RAPORT
                        )
                        ->max('revision')
                ) + 1;

                $payload =
                    $snapshotResult->documentPayload();

                $document = new AcademicDocument();

                $document->fill([
                    'santri_id' => $santri->id,
                    'semester_id' => $semester->id,
                    'document_type' =>
                        AcademicDocument::TYPE_RAPORT,
                    'status' =>
                        AcademicDocument::STATUS_DRAFT,
                    'revision' => $revision,
                    'is_current' => true,
                    'template_version' => 'raport-v1',
                    'snapshot_json' =>
                        $payload['snapshot_json'],
                    'snapshot_sha256' =>
                        $payload['snapshot_sha256'],
                    'generated_at' => now(),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'metadata' => $this->buildMetadata(
                        existingMetadata: [],
                        snapshotMetadata:
                            $payload['metadata'] ?? [],
                        action: 'created',
                        actorId: $actorId
                    ),
                ]);

                $this->applyManualFields(
                    $document,
                    $manualFields,
                    preserveMissing: false
                );

                $document->save();

                return new RaportDraftResult(
                    document: $document->fresh([
                        'santri',
                        'semester',
                    ]),
                    created: true,
                    regenerated: false,
                    warnings: $snapshotResult->warnings()
                );
            },
            3
        );
    }

    /**
     * Memperbarui snapshot pada draft yang sudah ada.
     *
     * Catatan manual dan predikat dipertahankan jika field tidak dikirim.
     *
     * @param array<string, mixed> $manualFields
     *
     * @throws JsonException
     * @throws ValidationException
     */
    public function regenerateDraft(
        AcademicDocument $document,
        ?int $actorId,
        array $manualFields = []
    ): RaportDraftResult {
        return DB::transaction(
            function () use (
                $document,
                $actorId,
                $manualFields
            ): RaportDraftResult {
                $lockedDocument =
                    AcademicDocument::query()
                    ->whereKey($document->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertCanRegenerate(
                    $lockedDocument
                );

                $lockedDocument->loadMissing([
                    'santri',
                    'semester',
                ]);

                $snapshotResult =
                    $this->snapshotService->build(
                        $lockedDocument->santri,
                        $lockedDocument->semester
                    );

                $snapshotResult->assertCanCreateDraft();

                $payload =
                    $snapshotResult->documentPayload();

                $lockedDocument->fill([
                    'snapshot_json' =>
                        $payload['snapshot_json'],
                    'snapshot_sha256' =>
                        $payload['snapshot_sha256'],
                    'generated_at' => now(),
                    'updated_by' => $actorId,
                    'metadata' => $this->buildMetadata(
                        existingMetadata:
                            $lockedDocument->metadata ?? [],
                        snapshotMetadata:
                            $payload['metadata'] ?? [],
                        action: 'regenerated',
                        actorId: $actorId
                    ),
                ]);

                /*
                 * File PDF lama tidak lagi valid setelah snapshot berubah.
                 */
                $lockedDocument->fill([
                    'pdf_path' => null,
                    'pdf_sha256' => null,
                    'pdf_generated_at' => null,
                ]);

                $this->applyManualFields(
                    $lockedDocument,
                    $manualFields,
                    preserveMissing: true
                );

                $lockedDocument->save();

                return new RaportDraftResult(
                    document: $lockedDocument->fresh([
                        'santri',
                        'semester',
                    ]),
                    created: false,
                    regenerated: true,
                    warnings: $snapshotResult->warnings()
                );
            },
            3
        );
    }


    /**
     * Membatalkan Draft Raport tanpa menghapus histori dokumen.
     *
     * Draft yang dibatalkan menjadi:
     *
     * status     = cancelled
     * is_current = false
     *
     * Setelah itu sistem dapat membuat Draft revisi berikutnya.
     *
     * @throws ValidationException
     */
    public function cancelDraft(
        AcademicDocument $document,
        int $actorId,
        string $reason
    ): AcademicDocument {
        return DB::transaction(
            function () use (
                $document,
                $actorId,
                $reason
            ): AcademicDocument {
                $lockedDocument =
                    AcademicDocument::query()
                    ->whereKey(
                        $document->getKey()
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertCanCancel(
                    $lockedDocument
                );

                $cleanReason = trim($reason);

                if (mb_strlen($cleanReason) < 5) {
                    throw ValidationException::withMessages([
                        'cancellation_reason' => [
                            'Alasan pembatalan minimal 5 karakter.',
                        ],
                    ]);
                }

                $lockedDocument->fill([
                    'status' =>
                        AcademicDocument::STATUS_CANCELLED,

                    'is_current' => false,

                    'cancelled_by' => $actorId,

                    'cancelled_at' => now(),

                    'cancellation_reason' =>
                        $cleanReason,

                    'updated_by' => $actorId,

                    /*
                     * PDF lama tidak boleh dianggap valid setelah
                     * Draft dibatalkan.
                     */
                    'pdf_path' => null,
                    'pdf_sha256' => null,
                    'pdf_generated_at' => null,

                    'metadata' => $this->buildMetadata(
                        existingMetadata:
                            $lockedDocument->metadata ?? [],
                        snapshotMetadata: [],
                        action: 'cancelled',
                        actorId: $actorId
                    ),
                ]);

                $lockedDocument->save();

                return $lockedDocument->fresh([
                    'santri',
                    'semester',
                    'cancelledBy',
                ]);
            },
            3
        );
    }

    private function assertCanCancel(
        AcademicDocument $document
    ): void {
        if (
            $document->document_type
            !== AcademicDocument::TYPE_RAPORT
        ) {
            throw ValidationException::withMessages([
                'document' => [
                    'Hanya Draft Raport yang dapat dibatalkan.',
                ],
            ]);
        }

        if (!$document->canBeCancelled()) {
            throw ValidationException::withMessages([
                'document' => [
                    'Draft tidak dapat dibatalkan karena sudah '
                    . 'diajukan, bukan revisi aktif, atau statusnya '
                    . 'bukan Draft.',
                ],
            ]);
        }
    }

    private function assertCurrentDocumentCanBeReused(
        AcademicDocument $document
    ): void {
        if (
            $document->document_type
            !== AcademicDocument::TYPE_RAPORT
        ) {
            throw ValidationException::withMessages([
                'document' => [
                    'Dokumen aktif bukan Raport.',
                ],
            ]);
        }

        if (!$document->isDraft()) {
            throw ValidationException::withMessages([
                'document' => [
                    'Dokumen aktif sudah berstatus '
                    . $document->status_label
                    . '. Draft baru tidak dapat dibuat.',
                ],
            ]);
        }
    }

    private function assertCanRegenerate(
        AcademicDocument $document
    ): void {
        if (
            $document->document_type
            !== AcademicDocument::TYPE_RAPORT
        ) {
            throw ValidationException::withMessages([
                'document' => [
                    'Hanya dokumen Raport yang dapat diregenerate.',
                ],
            ]);
        }

        if (!$document->is_current) {
            throw ValidationException::withMessages([
                'document' => [
                    'Dokumen ini bukan revisi aktif.',
                ],
            ]);
        }

        if (!$document->isDraft()) {
            throw ValidationException::withMessages([
                'document' => [
                    'Snapshot hanya dapat diperbarui ketika dokumen masih berstatus Draft.',
                ],
            ]);
        }

        if (
            !$document->santri_id
            || !$document->semester_id
        ) {
            throw ValidationException::withMessages([
                'document' => [
                    'Dokumen tidak mempunyai Santri atau Semester yang valid.',
                ],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $manualFields
     */
    private function applyManualFields(
        AcademicDocument $document,
        array $manualFields,
        bool $preserveMissing
    ): void {
        $allowed = [
            'predikat',
            'catatan_musyrif',
            'catatan_admin',
            'rekomendasi',
        ];

        foreach ($allowed as $field) {
            if (
                $preserveMissing
                && !array_key_exists($field, $manualFields)
            ) {
                continue;
            }

            $document->{$field} =
                $manualFields[$field] ?? null;
        }
    }

    /**
     * @param array<string, mixed> $existingMetadata
     * @param array<string, mixed> $snapshotMetadata
     *
     * @return array<string, mixed>
     */
    private function buildMetadata(
        array $existingMetadata,
        array $snapshotMetadata,
        string $action,
        ?int $actorId
    ): array {
        $history = collect(
            $existingMetadata['draft_history'] ?? []
        )->values();

        $history->push([
            'action' => $action,
            'actor_id' => $actorId,
            'at' => now()->toIso8601String(),
        ]);

        return array_replace_recursive(
            $existingMetadata,
            $snapshotMetadata,
            [
                'draft_history' => $history->all(),
                'last_draft_action' => $action,
                'last_draft_actor_id' => $actorId,
                'last_draft_action_at' =>
                    now()->toIso8601String(),
            ]
        );
    }

    /**
     * @return array<int, array{code:string, message:string}>
     */
    private function snapshotWarnings(
        AcademicDocument $document
    ): array {
        $warnings =
            $document->metadata['snapshot_warnings']
            ?? [];

        return is_array($warnings)
            ? $warnings
            : [];
    }
}
