<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\Musyrif;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncMusyrifClassScope extends Command
{
    protected $signature = 'musyrif:sync-class-scope
        {--dry-run : Tampilkan rencana tanpa menyimpan perubahan}
        {--force : Lewati konfirmasi interaktif}';

    protected $description = 'Menyelaraskan tingkat utama, kelas operasional utama, dan pivot kelas binaan dari santri aktif.';

    public function handle(): int
    {
        try {
            $plan = $this->buildPlan();
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

        $this->table(
            [
                'ID',
                'Musyrif',
                'Tingkat Lama',
                'Tingkat Baru',
                'Kelas Utama Lama',
                'Kelas Utama Baru',
                'Pivot Baru',
                'Aksi',
            ],
            collect($plan)->map(fn (array $row) => [
                $row['musyrif_id'],
                $row['musyrif_nama'],
                $row['old_parent_name'],
                $row['new_parent_name'],
                $row['old_primary_name'],
                $row['new_primary_name'],
                $row['new_pivot_names'],
                $row['action'],
            ])->all()
        );

        $changes = collect($plan)->where('action', 'UPDATE')->count();
        $this->line("Total Musyrif: " . count($plan) . "; perlu diperbarui: {$changes}.");

        if ($this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm(
            'Lanjutkan menyelaraskan scope kelas Musyrif?',
            false
        )) {
            $this->warn('Proses dibatalkan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan): void {
            foreach ($plan as $row) {
                if ($row['action'] !== 'UPDATE') {
                    continue;
                }

                $musyrif = Musyrif::query()
                    ->whereKey($row['musyrif_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $musyrif->forceFill([
                    'kelas_induk_id' => $row['new_parent_id'],
                    'kelas_id' => $row['new_primary_id'],
                ])->save();

                $musyrif->kelasBinaan()->sync($row['new_pivot_ids']);
            }
        });

        $this->info('Scope kelas Musyrif berhasil diselaraskan.');

        return self::SUCCESS;
    }

    private function buildPlan(): array
    {
        $musyrifs = Musyrif::query()
            ->with([
                'kelasInduk:id,nama_kelas,parent_id',
                'kelas:id,nama_kelas,parent_id',
                'kelasBinaan:id,nama_kelas,parent_id,is_active',
                'santris' => fn ($query) => $query
                    ->active()
                    ->whereNotNull('kelas_id')
                    ->with('kelas:id,nama_kelas,parent_id,is_active')
                    ->select(['id', 'kelas_id', 'musyrif_id']),
            ])
            ->orderBy('id')
            ->get();

        $plan = [];

        foreach ($musyrifs as $musyrif) {
            $studentClasses = $musyrif->santris
                ->pluck('kelas')
                ->filter()
                ->unique('id')
                ->values();

            $studentParentIds = $studentClasses
                ->pluck('parent_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($studentParentIds->count() > 1) {
                $labels = $studentClasses->pluck('nama_kelas')->implode(', ');
                throw ValidationException::withMessages([
                    'musyrif' => [
                        "Musyrif {$musyrif->nama} membina santri aktif lintas tingkat ({$labels}). Pisahkan halaqah atau koreksi data terlebih dahulu.",
                    ],
                ]);
            }

            $targetParentId = $studentParentIds->first();

            if (!$targetParentId) {
                $pivotParents = $musyrif->kelasBinaan
                    ->pluck('parent_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($pivotParents->count() === 1) {
                    $targetParentId = $pivotParents->first();
                } elseif ($musyrif->kelas?->parent_id) {
                    $targetParentId = (int) $musyrif->kelas->parent_id;
                } elseif ($musyrif->kelas_induk_id) {
                    $targetParentId = (int) $musyrif->kelas_induk_id;
                }
            }

            $targetParent = $targetParentId
                ? Kelas::query()->find($targetParentId)
                : null;

            $targetPivot = $studentClasses;

            if ($targetPivot->isEmpty() && $targetParentId) {
                $targetPivot = $musyrif->kelasBinaan
                    ->filter(fn (Kelas $kelas) =>
                        (int) $kelas->parent_id === (int) $targetParentId
                        && $kelas->is_active
                    )
                    ->values();
            }

            if (
                $targetPivot->isEmpty()
                && $musyrif->kelas
                && (int) $musyrif->kelas->parent_id === (int) $targetParentId
            ) {
                $targetPivot = collect([$musyrif->kelas]);
            }

            $targetPivotIds = $targetPivot
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $targetPrimaryId = null;

            if (
                $musyrif->kelas_id
                && $targetPivotIds->contains((int) $musyrif->kelas_id)
            ) {
                $targetPrimaryId = (int) $musyrif->kelas_id;
            } elseif ($studentClasses->isNotEmpty()) {
                $counts = $musyrif->santris->countBy('kelas_id');
                $targetPrimaryId = (int) $counts->sortDesc()->keys()->first();
            } else {
                $targetPrimaryId = $targetPivotIds->first();
            }

            $currentPivotIds = collect($musyrif->allKelasIds())
                ->sort()
                ->values();

            $changed = (int) $musyrif->kelas_induk_id !== (int) $targetParentId
                || (int) $musyrif->kelas_id !== (int) $targetPrimaryId
                || $currentPivotIds->all() !== $targetPivotIds->all();

            $newPrimary = $targetPrimaryId
                ? $targetPivot->firstWhere('id', $targetPrimaryId)
                    ?? Kelas::query()->find($targetPrimaryId)
                : null;

            $plan[] = [
                'musyrif_id' => (int) $musyrif->id,
                'musyrif_nama' => (string) $musyrif->nama,
                'old_parent_name' => $musyrif->kelasInduk?->nama_kelas ?? '-',
                'new_parent_id' => $targetParentId ? (int) $targetParentId : null,
                'new_parent_name' => $targetParent?->nama_kelas ?? '-',
                'old_primary_name' => $musyrif->kelas?->nama_kelas ?? '-',
                'new_primary_id' => $targetPrimaryId,
                'new_primary_name' => $newPrimary?->nama_kelas ?? '-',
                'new_pivot_ids' => $targetPivotIds->all(),
                'new_pivot_names' => $targetPivot->pluck('nama_kelas')->implode(', ') ?: '-',
                'action' => $changed ? 'UPDATE' : 'KEEP',
            ];
        }

        return $plan;
    }
}
