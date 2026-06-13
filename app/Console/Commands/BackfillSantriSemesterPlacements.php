<?php

namespace App\Console\Commands;

use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSantriSemesterPlacements extends Command
{
    protected $signature = 'academic:backfill-santri-placements
        {--semester= : ID semester yang akan dibackfill}
        {--include-inactive : Sertakan lulus, keluar, dan nonaktif}
        {--force : Perbarui placement yang sudah ada}
        {--dry-run : Hanya hitung tanpa menulis database}';

    protected $description =
        'Membuat placement semester dari posisi santri saat ini.';

    public function handle(): int
    {
        $semester = $this->resolveSemester();

        if (!$semester) {
            $this->error(
                'Semester tidak ditemukan. Tentukan --semester atau aktifkan satu semester.'
            );

            return self::FAILURE;
        }

        $includeInactive =
            (bool) $this->option(
                'include-inactive'
            );

        $force =
            (bool) $this->option('force');

        $dryRun =
            (bool) $this->option('dry-run');

        $query = Santri::query()
            ->when(
                !$includeInactive,
                fn ($builder) =>
                    $builder->active()
            )
            ->orderBy('id');

        $total = (clone $query)->count();

        $this->info(
            "Semester: {$semester->nama} (#{$semester->id})"
        );

        $this->info(
            "Kandidat santri: {$total}"
        );

        if ($dryRun) {
            $candidateIds =
                (clone $query)->pluck('id');

            $existing =
                SantriSemesterPlacement::query()
                    ->where(
                        'semester_id',
                        $semester->id
                    )
                    ->whereIn(
                        'santri_id',
                        $candidateIds
                    )
                    ->count();

            $this->table(
                [
                    'Kandidat',
                    'Sudah Ada',
                    'Akan Dibuat',
                ],
                [[
                    $total,
                    $existing,
                    max(0, $total - $existing),
                ]]
            );

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        $progress = $this->output
            ->createProgressBar($total);

        $progress->start();

        $query->chunkById(
            300,
            function ($santris) use (
                $semester,
                $force,
                &$created,
                &$updated,
                &$skipped,
                $progress
            ): void {
                DB::transaction(
                    function () use (
                        $santris,
                        $semester,
                        $force,
                        &$created,
                        &$updated,
                        &$skipped,
                        $progress
                    ): void {
                        foreach ($santris as $santri) {
                            $placement =
                                SantriSemesterPlacement::query()
                                    ->where(
                                        'santri_id',
                                        $santri->id
                                    )
                                    ->where(
                                        'semester_id',
                                        $semester->id
                                    )
                                    ->lockForUpdate()
                                    ->first();

                            if (
                                $placement
                                && !$force
                            ) {
                                $skipped++;
                                $progress->advance();
                                continue;
                            }

                            $payload = [
                                'kelas_id' =>
                                    $santri->kelas_id,
                                'musyrif_id' =>
                                    $santri->isActive()
                                        ? $santri->musyrif_id
                                        : null,
                                'status' =>
                                    $santri->status,
                                'placement_type' =>
                                    SantriSemesterPlacement::TYPE_BACKFILL,
                                'started_at' =>
                                    $semester->tanggal_mulai
                                        ? $semester
                                            ->tanggal_mulai
                                            ->copy()
                                            ->startOfDay()
                                        : null,
                                'ended_at' =>
                                    $santri->isActive()
                                        ? null
                                        : (
                                            $santri
                                                ->status_changed_at
                                            ?? $santri
                                                ->graduated_at
                                            ?? now()
                                        ),
                                'note' =>
                                    'Backfill posisi santri dari kolom santris.',
                                'metadata' => [
                                    'source' =>
                                        'academic:backfill-santri-placements',
                                    'backfilled_at' =>
                                        now()
                                            ->toIso8601String(),
                                ],
                            ];

                            if ($placement) {
                                $placement
                                    ->forceFill($payload)
                                    ->save();

                                $updated++;
                            } else {
                                SantriSemesterPlacement::query()
                                    ->create([
                                        'santri_id' =>
                                            $santri->id,
                                        'semester_id' =>
                                            $semester->id,
                                        ...$payload,
                                    ]);

                                $created++;
                            }

                            $progress->advance();
                        }
                    }
                );
            }
        );

        $progress->finish();
        $this->newLine(2);

        $this->table(
            [
                'Dibuat',
                'Diperbarui',
                'Dilewati',
            ],
            [[
                $created,
                $updated,
                $skipped,
            ]]
        );

        return self::SUCCESS;
    }

    private function resolveSemester(): ?Semester
    {
        $semesterId = $this->option(
            'semester'
        );

        if ($semesterId !== null) {
            return Semester::query()
                ->find((int) $semesterId);
        }

        return Semester::query()
            ->active()
            ->first();
    }
}
