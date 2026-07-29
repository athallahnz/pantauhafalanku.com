<?php

namespace App\Console\Commands;

use App\Models\KelasGroupAssignmentBatch;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use Illuminate\Console\Command;

class StatusKelasGroupAssignment extends Command
{
    protected $signature = 'kelas:assignment-status';

    protected $description = 'Menampilkan status assignment kelas induk dan batch terakhir.';

    public function handle(): int
    {
        $semester = Semester::query()
            ->where(function ($query): void {
                $query
                    ->where('is_active', true)
                    ->orWhere('status', 'active');
            })
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();

        $santriLegacy = Santri::query()
            ->active()
            ->usingLegacyClass()
            ->count();

        $musyrifLegacy = Musyrif::query()
            ->usingLegacyClass()
            ->count();

        $placementLegacy = $semester
            ? SantriSemesterPlacement::query()
                ->forSemester($semester->id)
                ->active()
                ->usingLegacyClass()
                ->count()
            : 0;

        $this->table(
            ['Indikator', 'Jumlah'],
            [
                ['Santri aktif di parent', $santriLegacy],
                ['Musyrif di parent', $musyrifLegacy],
                ['Placement aktif di parent', $placementLegacy],
            ]
        );

        $batches = KelasGroupAssignmentBatch::query()
            ->latestFirst()
            ->limit(10)
            ->get();

        if ($batches->isNotEmpty()) {
            $this->table(
                ['Kode', 'Status', 'Semester', 'Preview', 'Execute', 'Rollback'],
                $batches->map(fn ($batch) => [
                    $batch->code,
                    $batch->status,
                    $batch->semester_id ?? '-',
                    $batch->previewed_at?->format('Y-m-d H:i') ?? '-',
                    $batch->executed_at?->format('Y-m-d H:i') ?? '-',
                    $batch->rolled_back_at?->format('Y-m-d H:i') ?? '-',
                ])->all()
            );
        }

        return (
            $santriLegacy === 0
            && $musyrifLegacy === 0
            && $placementLegacy === 0
        )
            ? self::SUCCESS
            : self::FAILURE;
    }
}
