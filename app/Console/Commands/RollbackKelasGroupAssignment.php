<?php

namespace App\Console\Commands;

use App\Models\KelasGroupAssignmentBatch;
use App\Services\Academic\KelasGroupAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class RollbackKelasGroupAssignment extends Command
{
    protected $signature = 'kelas:assignment-rollback
        {batch : UUID atau kode batch}
        {--force : Lewati konfirmasi}';

    protected $description = 'Rollback batch assignment kelas kelompok selama state target belum berubah.';

    public function handle(
        KelasGroupAssignmentService $service
    ): int {
        $value = trim(
            (string) $this->argument('batch')
        );

        $batch = KelasGroupAssignmentBatch::query()
            ->where('id', $value)
            ->orWhere('code', $value)
            ->first();

        if (!$batch) {
            $this->error('Batch tidak ditemukan.');

            return self::FAILURE;
        }

        if (
            !$this->option('force')
            && !$this->confirm(
                "Rollback batch {$batch->code}?",
                false
            )
        ) {
            $this->warn('Rollback dibatalkan.');

            return self::SUCCESS;
        }

        try {
            $rolledBack = $service->rollback(
                $batch,
                null
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

        $this->info(
            "Batch {$rolledBack->code} berhasil di-rollback."
        );

        return self::SUCCESS;
    }
}
