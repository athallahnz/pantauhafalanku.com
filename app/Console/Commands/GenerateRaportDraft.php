<?php

namespace App\Console\Commands;

use App\Models\AcademicDocument;
use App\Models\Santri;
use App\Models\Semester;
use App\Services\AcademicDocuments\RaportDraftService;
use Illuminate\Console\Command;
use JsonException;

class GenerateRaportDraft extends Command
{
    protected $signature =
        'academic:generate-raport-draft
        {santri_id : ID Santri}
        {semester_id : ID Semester}
        {--actor= : ID User pencatat aksi}
        {--regenerate : Perbarui snapshot jika draft sudah ada}
        {--json : Tampilkan hasil JSON}';

    protected $description =
        'Membuat atau meregenerate Draft Raport untuk pengujian backend.';

    /**
     * @throws JsonException
     */
    public function handle(
        RaportDraftService $service
    ): int {
        $santriId =
            (int) $this->argument('santri_id');

        $semesterId =
            (int) $this->argument('semester_id');

        $actorId = $this->option('actor');

        $actorId = $actorId !== null
            ? (int) $actorId
            : null;

        if ($this->option('regenerate')) {
            $document = AcademicDocument::query()
                ->where('santri_id', $santriId)
                ->where('semester_id', $semesterId)
                ->where(
                    'document_type',
                    AcademicDocument::TYPE_RAPORT
                )
                ->where('is_current', true)
                ->latest('revision')
                ->first();

            if (!$document) {
                $this->components->error(
                    'Draft aktif tidak ditemukan.'
                );

                return self::FAILURE;
            }

            $result = $service->regenerateDraft(
                document: $document,
                actorId: $actorId
            );
        } else {
            $result = $service->createDraft(
                santri: Santri::query()
                    ->findOrFail($santriId),

                semester: Semester::query()
                    ->findOrFail($semesterId),

                actorId: $actorId
            );
        }

        $payload = $result->toArray();

        $this->components->info(
            $payload['message']
        );

        $this->table(
            ['Item', 'Nilai'],
            [
                [
                    'Public ID',
                    $payload['document']['public_id'],
                ],
                [
                    'Status',
                    $payload['document']['status_label'],
                ],
                [
                    'Revision',
                    $payload['document']['revision'],
                ],
                [
                    'Created',
                    $payload['created'] ? 'Ya' : 'Tidak',
                ],
                [
                    'Regenerated',
                    $payload['regenerated'] ? 'Ya' : 'Tidak',
                ],
                [
                    'Snapshot SHA-256',
                    $payload['document']['snapshot_sha256'],
                ],
            ]
        );

        foreach ($payload['warnings'] as $warning) {
            $this->components->warn(
                $warning['message']
            );
        }

        if ($this->option('json')) {
            $this->line(
                json_encode(
                    $payload,
                    JSON_THROW_ON_ERROR
                    | JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );
        }

        return self::SUCCESS;
    }
}
