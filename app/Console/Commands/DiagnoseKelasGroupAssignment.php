<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\Musyrif;
use Illuminate\Console\Command;

class DiagnoseKelasGroupAssignment extends Command
{
    protected $signature = 'kelas:assignment-diagnose
        {--only-problem : Tampilkan hanya penugasan yang belum konsisten}';

    protected $description = 'Mendiagnosis tingkat utama, kelas operasional utama, dan pivot kelas binaan Musyrif.';

    public function handle(): int
    {
        $musyrifs = Musyrif::query()
            ->with([
                'kelasInduk:id,parent_id,nama_kelas,kode,is_active',
                'kelas:id,parent_id,nama_kelas,kelompok,is_active',
                'kelasBinaan:id,parent_id,nama_kelas,kelompok,is_active',
                'santris' => fn ($query) => $query
                    ->active()
                    ->with('kelas:id,parent_id,nama_kelas,kelompok,is_active')
                    ->select(['id', 'nama', 'kelas_id', 'musyrif_id']),
            ])
            ->orderBy('nama')
            ->get();

        $rows = [];

        foreach ($musyrifs as $musyrif) {
            $studentClassIds = $musyrif->santris
                ->pluck('kelas_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $assignedClassIds = collect($musyrif->allKelasIds());
            $uncoveredClassIds = $studentClassIds
                ->diff($assignedClassIds)
                ->values();

            $assignedParentIds = $musyrif->kelasBinaan
                ->pluck('parent_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $primaryInPivot = !$musyrif->kelas_id
                || $assignedClassIds->contains((int) $musyrif->kelas_id);

            $primaryMatchesParent = !$musyrif->kelas_id
                || !$musyrif->kelas
                || (
                    $musyrif->kelas->parent_id !== null
                    && (int) $musyrif->kelas->parent_id === (int) $musyrif->kelas_induk_id
                );

            $indukValid = !$musyrif->kelas_induk_id
                || (
                    $musyrif->kelasInduk
                    && $musyrif->kelasInduk->parent_id === null
                    && $musyrif->kelasInduk->is_active
                );

            $pivotMatchesParent = !$musyrif->kelas_induk_id
                || $assignedParentIds->isEmpty()
                || (
                    $assignedParentIds->count() === 1
                    && (int) $assignedParentIds->first() === (int) $musyrif->kelas_induk_id
                );

            $status = match (true) {
                $assignedClassIds->isNotEmpty() && !$musyrif->kelas_induk_id =>
                    'TINGKAT UTAMA BELUM DIISI',
                !$indukValid =>
                    'TINGKAT UTAMA TIDAK VALID',
                $assignedParentIds->count() > 1 =>
                    'KELAS BINAAN LINTAS TINGKAT',
                !$pivotMatchesParent =>
                    'KELAS BINAAN BEDA TINGKAT',
                !$primaryInPivot =>
                    'KELAS UTAMA BELUM MASUK PIVOT',
                !$primaryMatchesParent =>
                    'KELAS UTAMA BEDA TINGKAT',
                $uncoveredClassIds->isNotEmpty() =>
                    'KELAS SANTRI BELUM DIBINA',
                default => 'AMAN',
            };

            if ($this->option('only-problem') && $status === 'AMAN') {
                continue;
            }

            $studentDistribution = $musyrif->santris
                ->groupBy(fn ($santri) => $santri->kelas?->nama_kelas ?? 'Tanpa kelas')
                ->map(fn ($students) => $students->count())
                ->map(fn (int $count, string $label) => "{$label}: {$count}")
                ->implode(', ');

            $uncoveredLabels = Kelas::query()
                ->whereIn('id', $uncoveredClassIds)
                ->pluck('nama_kelas')
                ->implode(', ');

            $rows[] = [
                $musyrif->id,
                $musyrif->nama,
                $musyrif->kelasInduk?->nama_kelas ?? '-',
                $musyrif->kelas?->nama_kelas ?? '-',
                $musyrif->kelasBinaan->pluck('nama_kelas')->implode(', ') ?: '-',
                $studentDistribution ?: '-',
                $uncoveredLabels ?: '-',
                $status,
            ];
        }

        $this->table(
            [
                'ID',
                'Musyrif',
                'Tingkat Utama',
                'Kelas Operasional',
                'Kelas Binaan (Pivot)',
                'Sebaran Santri Aktif',
                'Belum Tercakup',
                'Status',
            ],
            $rows
        );

        $this->line('Total ditampilkan: ' . count($rows));

        return self::SUCCESS;
    }
}
