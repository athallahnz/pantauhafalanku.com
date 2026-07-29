<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RollbackKelasHierarchyGroups extends Command
{
    protected $signature = 'kelas:rollback-groups
        {--dry-run : Tampilkan kelompok yang dapat/tidak dapat dihapus}
        {--parent= : Batasi berdasarkan nama kelas induk}
        {--force : Lewati konfirmasi interaktif}';

    protected $description = 'Menghapus kelas kelompok yang belum direferensikan tanpa menghapus kelas induk.';

    private const REFERENCES = [
        ['table' => 'santris', 'column' => 'kelas_id'],
        ['table' => 'musyrifs', 'column' => 'kelas_id'],
        ['table' => 'santri_semester_placements', 'column' => 'kelas_id'],
        ['table' => 'santri_kelas_histories', 'column' => 'kelas_id'],
        ['table' => 'santri_status_histories', 'column' => 'kelas_id'],
        ['table' => 'santri_migration_batches', 'column' => 'from_kelas_id'],
        ['table' => 'santri_migration_batches', 'column' => 'to_kelas_id'],
        ['table' => 'santri_migration_batch_items', 'column' => 'from_kelas_id'],
        ['table' => 'santri_migration_batch_items', 'column' => 'to_kelas_id'],
    ];

    public function handle(): int
    {
        $query = Kelas::query()
            ->with('parent:id,nama_kelas')
            ->whereNotNull('parent_id')
            ->orderBy('parent_id')
            ->orderBy('kelompok');

        $parentName = trim((string) $this->option('parent'));

        if ($parentName !== '') {
            $query->whereHas(
                'parent',
                fn ($parentQuery) => $parentQuery->where('nama_kelas', $parentName)
            );
        }

        $groups = $query->get();

        if ($groups->isEmpty()) {
            $this->warn('Tidak ada kelas kelompok yang cocok.');

            return self::SUCCESS;
        }

        $inspection = $groups->map(function (Kelas $kelas): array {
            $references = $this->referenceCounts((int) $kelas->id);
            $total = array_sum($references);

            return [
                'kelas' => $kelas,
                'references' => $references,
                'total' => $total,
                'eligible' => $total === 0 && $kelas->children()->doesntExist(),
            ];
        });

        $this->table(
            ['ID', 'Kelas', 'Parent', 'Kelompok', 'Referensi', 'Status'],
            $inspection->map(fn (array $row) => [
                $row['kelas']->id,
                $row['kelas']->nama_kelas,
                $row['kelas']->parent?->nama_kelas ?? '-',
                $row['kelas']->kelompok,
                $row['total'],
                $row['eligible'] ? 'DAPAT DIHAPUS' : 'DIBLOKIR',
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada data yang dihapus.');

            return self::SUCCESS;
        }

        $eligible = $inspection->where('eligible', true);

        if ($eligible->isEmpty()) {
            $this->warn('Tidak ada kelompok yang aman untuk dihapus.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm(
            'Hapus ' . $eligible->count() . ' kelas kelompok tanpa referensi?',
            false
        )) {
            $this->warn('Rollback dibatalkan.');

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($eligible): void {
                Kelas::query()
                    ->whereIn(
                        'id',
                        $eligible->pluck('kelas.id')->map(fn ($id) => (int) $id)
                    )
                    ->delete();
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($eligible->count() . ' kelas kelompok berhasil dihapus.');

        return self::SUCCESS;
    }

    private function referenceCounts(int $kelasId): array
    {
        $counts = [];

        foreach (self::REFERENCES as $reference) {
            $table = $reference['table'];
            $column = $reference['column'];

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $counts["{$table}.{$column}"] = DB::table($table)
                ->where($column, $kelasId)
                ->count();
        }

        return $counts;
    }
}
