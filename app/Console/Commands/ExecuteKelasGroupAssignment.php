<?php

namespace App\Console\Commands;

use App\Models\KelasGroupAssignmentBatch;
use App\Services\Academic\KelasGroupAssignmentService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExecuteKelasGroupAssignment extends Command
{
    protected $signature = 'kelas:assignment-execute
        {batch : UUID atau kode batch}
        {--force : Lewati konfirmasi}';

    protected $description = 'Mengeksekusi batch pemindahan kelas induk ke kelas kelompok.';

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
                "Eksekusi batch {$batch->code}?",
                false
            )
        ) {
            $this->warn('Eksekusi dibatalkan.');

            return self::SUCCESS;
        }

        try {
            $executed = $service->execute(
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
            "Batch {$executed->code} berhasil dieksekusi."
        );

        $this->line(
            'Verifikasi: php artisan kelas:assignment-status'
        );

        return self::SUCCESS;
    }
}
