<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RepairKelasHierarchyStage2 extends Command
{
    protected $signature = 'kelas:repair-stage-2
        {--dry-run : Tampilkan rencana tanpa menyimpan}
        {--force : Lewati konfirmasi}';

    protected $description = 'Memperbaiki child Tahap 2 yang terbuat sebagai root karena parent_id/kelompok tidak tersimpan.';

    public function handle(): int
    {
        $parents = collect(config('kelas_hierarchy.parents', []));

        if ($parents->isEmpty()) {
            $this->error('Config kelas_hierarchy tidak ditemukan atau kosong.');

            return self::FAILURE;
        }

        $plan = [];

        foreach ($parents as $parentName => $settings) {
            $parent = Kelas::query()
                ->where('nama_kelas', $parentName)
                ->whereNull('parent_id')
                ->whereNull('kelompok')
                ->first();

            if (!$parent) {
                $this->error("Parent '{$parentName}' tidak ditemukan.");

                return self::FAILURE;
            }

            $parentCode = strtoupper(trim((string) ($settings['code'] ?? '')));
            $parentOrder = max(0, (int) ($settings['order'] ?? 0));
            $step = max(1, (int) config('kelas_hierarchy.group_order_step', 1));

            foreach (collect($settings['groups'] ?? [])->values() as $index => $group) {
                $group = strtoupper(trim((string) $group));
                $childName = trim(strtr(
                    (string) config('kelas_hierarchy.group_name_pattern', '{parent} {group}'),
                    [
                        '{parent}' => $parentName,
                        '{group}' => $group,
                    ]
                ));
                $childCode = strtoupper(trim(strtr(
                    (string) config('kelas_hierarchy.group_code_pattern', '{parent_code}-{group}'),
                    [
                        '{parent_code}' => $parentCode,
                        '{group}' => $group,
                    ]
                )));

                $child = Kelas::query()
                    ->where(function ($query) use ($parent, $group, $childName, $childCode): void {
                        $query
                            ->where(function ($nested) use ($parent, $group): void {
                                $nested
                                    ->where('parent_id', $parent->id)
                                    ->where('kelompok', $group);
                            })
                            ->orWhere(function ($nested) use ($childName, $childCode): void {
                                $nested
                                    ->whereNull('parent_id')
                                    ->whereNull('kelompok')
                                    ->where(function ($match) use ($childName, $childCode): void {
                                        $match
                                            ->where('nama_kelas', $childName)
                                            ->orWhere('kode', $childCode);
                                    });
                            });
                    })
                    ->first();

                $plan[] = [
                    'parent' => $parent,
                    'group' => $group,
                    'child' => $child,
                    'child_name' => $childName,
                    'child_code' => $childCode,
                    'child_order' => $parentOrder + (($index + 1) * $step),
                    'action' => $child
                        ? (
                            (int) $child->parent_id === (int) $parent->id
                            && $child->kelompok === $group
                                ? 'SUDAH BENAR'
                                : 'PERBAIKI'
                        )
                        : 'BUAT',
                ];
            }
        }

        $this->table(
            ['Aksi', 'Child ID', 'Nama Child', 'Parent ID', 'Parent', 'Kelompok'],
            collect($plan)->map(fn (array $row) => [
                $row['action'],
                $row['child']?->id ?? '-',
                $row['child_name'],
                $row['parent']->id,
                $row['parent']->nama_kelas,
                $row['group'],
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada data yang diubah.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm(
            'Lanjutkan memperbaiki hierarki Tahap 2?',
            false
        )) {
            $this->warn('Perbaikan dibatalkan.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($plan): void {
                foreach ($plan as $row) {
                    /** @var Kelas $parent */
                    $parent = Kelas::query()
                        ->whereKey($row['parent']->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $child = $row['child']
                        ? Kelas::query()
                            ->whereKey($row['child']->id)
                            ->lockForUpdate()
                            ->firstOrFail()
                        : new Kelas();

                    $child->forceFill([
                        'parent_id' => $parent->id,
                        'nama_kelas' => $row['child_name'],
                        'kelompok' => $row['group'],
                        'deskripsi' => $child->deskripsi ?? $parent->deskripsi,
                        'kode' => $row['child_code'],
                        'is_active' => true,
                        'urutan' => $row['child_order'],
                    ])->save();
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Hierarki Tahap 2 berhasil diperbaiki.');
        $this->comment('Tidak ada assignment santri atau musyrif yang dipindahkan.');

        return self::SUCCESS;
    }
}
