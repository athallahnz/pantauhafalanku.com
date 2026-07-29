<?php

namespace App\Console\Commands;

use App\Models\KelasGroupAssignmentBatch;
use App\Services\Academic\KelasGroupAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class PreviewKelasGroupAssignment extends Command
{
    protected $signature = 'kelas:assignment-preview
        {--semester= : ID semester aktif}
        {--note= : Catatan preview}';

    protected $description = 'Membuat preview dan audit batch pemindahan kelas induk ke kelas kelompok.';

    public function handle(
        KelasGroupAssignmentService $service
    ): int {
        try {
            $batch = $service->createPreview(
                $this->option('semester')
                    ? (int) $this->option('semester')
                    : null,
                null,
                $this->option('note')
                    ? (string) $this->option('note')
                    : null
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $summary = $batch->summary ?? [];

        $this->table(
            ['Batch', 'Status', 'Santri', 'Musyrif', 'Placement', 'Blocker'],
            [[
                $batch->code,
                $batch->status,
                data_get($summary, 'items.santri', 0),
                data_get($summary, 'items.musyrif', 0),
                data_get($summary, 'items.placement', 0),
                data_get($summary, 'blocker_count', 0),
            ]]
        );

        $this->line('Batch ID: ' . $batch->id);

        $blockers = data_get(
            $batch->metadata,
            'blockers',
            []
        );

        foreach ($blockers as $blocker) {
            $this->error('• ' . $blocker);
        }

        if (
            $batch->status
            === KelasGroupAssignmentBatch::STATUS_BLOCKED
        ) {
            $this->warn('Preview tersimpan tetapi eksekusi diblokir.');

            return self::FAILURE;
        }

        $this->info(
            'Preview siap. Jalankan: php artisan kelas:assignment-execute '
            . $batch->id
        );

        return self::SUCCESS;
    }
}
