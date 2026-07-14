<?php

namespace App\Support\AcademicDocuments;

use App\Models\AcademicDocument;

final class RaportDraftResult
{
    /**
     * @param array<int, array{code:string, message:string}> $warnings
     */
    public function __construct(
        private readonly AcademicDocument $document,
        private readonly bool $created,
        private readonly bool $regenerated,
        private readonly array $warnings = []
    ) {
    }

    public function document(): AcademicDocument
    {
        return $this->document;
    }

    public function created(): bool
    {
        return $this->created;
    }

    public function regenerated(): bool
    {
        return $this->regenerated;
    }

    public function warnings(): array
    {
        return $this->warnings;
    }

    public function message(): string
    {
        if ($this->created) {
            return 'Draft Raport berhasil dibuat.';
        }

        if ($this->regenerated) {
            return 'Snapshot Draft Raport berhasil diperbarui.';
        }

        return 'Draft Raport sudah tersedia dan tidak diubah.';
    }

    public function toArray(): array
    {
        $document = $this->document;

        return [
            'ok' => true,
            'message' => $this->message(),
            'created' => $this->created,
            'regenerated' => $this->regenerated,
            'warnings' => $this->warnings,

            'document' => [
                'public_id' => $document->public_id,
                'santri_id' => (int) $document->santri_id,
                'semester_id' => $document->semester_id
                    ? (int) $document->semester_id
                    : null,
                'document_type' => $document->document_type,
                'document_type_label' =>
                    $document->document_type_label,
                'status' => $document->status,
                'status_label' => $document->status_label,
                'revision' => (int) $document->revision,
                'is_current' => (bool) $document->is_current,
                'predikat' => $document->predikat,
                'snapshot_sha256' =>
                    $document->snapshot_sha256,
                'generated_at' =>
                    $document->generated_at?->toIso8601String(),
                'updated_at' =>
                    $document->updated_at?->toIso8601String(),
            ],
        ];
    }
}
