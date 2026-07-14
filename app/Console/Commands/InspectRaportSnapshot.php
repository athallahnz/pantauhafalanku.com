<?php

namespace App\Console\Commands;

use App\Services\AcademicDocuments\RaportSnapshotService;
use Illuminate\Console\Command;
use JsonException;

class InspectRaportSnapshot extends Command
{
    protected $signature =
        'academic:inspect-raport-snapshot
        {santri_id : ID santri}
        {semester_id : ID semester}
        {--json : Tampilkan seluruh snapshot JSON}';

    protected $description =
        'Memeriksa snapshot Raport tanpa menyimpan AcademicDocument.';

    /**
     * @throws JsonException
     */
    public function handle(
        RaportSnapshotService $service
    ): int {
        $result = $service->buildByIds(
            (int) $this->argument(
                'santri_id'
            ),
            (int) $this->argument(
                'semester_id'
            )
        );

        $snapshot = $result->snapshot();

        $hafalanActivity =
            $snapshot['hafalan']['semester_activity']
            ?? [];

        $hafalanAchievement =
            $snapshot['hafalan']['cumulative_achievement']
            ?? [];

        $tahsinActivity =
            $snapshot['tahsin']['semester_activity']
            ?? [];

        $tahsinAchievement =
            $snapshot['tahsin']['cumulative_achievement']
            ?? [];

        $tilawahActivity =
            $snapshot['tilawah']['semester_activity']
            ?? [];

        $tilawahAchievement =
            $snapshot['tilawah']['cumulative_achievement']
            ?? [];

        $this->newLine();

        $this->components->info(
            'Raport Snapshot Inspection'
        );

        $this->table(
            [
                'Item',
                'Nilai',
            ],
            [
                [
                    'Santri',
                    $snapshot['student']['nama']
                        ?? '-',
                ],
                [
                    'NIS',
                    $snapshot['student']['nis']
                        ?? '-',
                ],
                [
                    'Semester',
                    $snapshot['semester']['label']
                        ?? '-',
                ],
                [
                    'Lifecycle Konsisten',
                    (
                        $snapshot['semester']['lifecycle']['is_consistent']
                        ?? false
                    )
                        ? 'Ya'
                        : 'Tidak',
                ],
                [
                    'Siap Dipublikasikan',
                    (
                        $snapshot['semester']['lifecycle']['is_ready_for_publication']
                        ?? false
                    )
                        ? 'Ya'
                        : 'Belum',
                ],
                [
                    'Kelas',
                    $snapshot['placement']['kelas']['nama']
                        ?? '-',
                ],
                [
                    'Musyrif',
                    $snapshot['placement']['musyrif']['nama']
                        ?? '-',
                ],
                [
                    'Record Hafalan Semester',
                    $snapshot['record_counts']['semester_activity']['hafalan']
                        ?? 0,
                ],
                [
                    'Record Tahsin Semester',
                    $snapshot['record_counts']['semester_activity']['tahsin']
                        ?? 0,
                ],
                [
                    'Record Tilawah Semester',
                    $snapshot['record_counts']['semester_activity']['tilawah']
                        ?? 0,
                ],
                [
                    'Nilai Hafalan Semester',
                    $this->nullableValue(
                        $hafalanActivity['avg_nilai']
                        ?? null
                    ),
                ],
                [
                    'Nilai Tahsin Semester',
                    $this->nullableValue(
                        $tahsinActivity['avg_nilai']
                        ?? null
                    ),
                ],
                [
                    'Capaian Hafalan Kumulatif',
                    (
                        $hafalanAchievement['overall_pct']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'Juz Hafalan Selesai',
                    $hafalanAchievement['juz_selesai']
                        ?? 0,
                ],
                [
                    'Capaian Tahsin Kumulatif',
                    (
                        $tahsinAchievement['overall_pct']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'Capaian Tilawah Kumulatif',
                    (
                        $tilawahAchievement['overall_pct']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'Juz Tilawah Tertinggi',
                    $tilawahAchievement['max_juz']
                        ?? 0,
                ],
                [
                    'Disiplin Semester',
                    (
                        $snapshot['evaluation']['component_scores']['semester_discipline']
                        ?? 0
                    )
                    . '%',
                ],
                [
                    'Indeks Progress',
                    $snapshot['evaluation']['progress_index']
                        ?? 0,
                ],
                [
                    'Saran Predikat',
                    $snapshot['evaluation']['suggested_predicate']
                        ?? 'Dinonaktifkan',
                ],
                [
                    'Snapshot SHA-256',
                    $result->snapshotSha256(),
                ],
            ]
        );

        if ($result->hasBlockers()) {
            $this->components->error(
                'Hard Blocker'
            );

            foreach (
                $result->blockers()
                as $blocker
            ) {
                $this->line(
                    ' - '
                    . $blocker['message']
                );
            }
        }

        if ($result->hasWarnings()) {
            $this->components->warn(
                'Warning'
            );

            foreach (
                $result->warnings()
                as $warning
            ) {
                $this->line(
                    ' - '
                    . $warning['message']
                );
            }
        }

        if ($this->option('json')) {
            $this->newLine();

            $this->line(
                json_encode(
                    $result->toArray(),
                    JSON_THROW_ON_ERROR
                    | JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );
        }

        if ($result->hasBlockers()) {
            $this->components->error(
                'Snapshot belum layak dibuat sebagai draft.'
            );

            return self::FAILURE;
        }

        $this->components->info(
            'Snapshot layak dibuat sebagai draft.'
        );

        return self::SUCCESS;
    }

    private function nullableValue(
        mixed $value
    ): string {
        if ($value === null) {
            return 'Belum Dinilai';
        }

        return (string) $value;
    }
}
