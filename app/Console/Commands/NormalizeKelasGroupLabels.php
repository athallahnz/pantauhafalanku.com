<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class NormalizeKelasGroupLabels extends Command
{
    protected $signature = 'kelas:normalize-group-labels
        {--dry-run : Tampilkan rencana tanpa menyimpan perubahan}
        {--force : Lewati konfirmasi interaktif}';

    protected $description = 'Mengubah kelompok SMA A/B/C menjadi 1/2/3 tanpa mengubah ID kelas dan seluruh relasinya.';

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

        if ($plan === []) {
            $this->info('Tidak ada kelompok SMA alfabet yang perlu dinormalisasi.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Parent', 'Sebelum', 'Kelompok Baru', 'Nama Baru', 'Kode Baru'],
            collect($plan)->map(fn (array $row) => [
                $row['id'],
                $row['parent_name'],
                $row['old_name'],
                $row['new_group'],
                $row['new_name'],
                $row['new_code'],
            ])->all()
        );

        $this->line('Total perubahan: ' . count($plan));

        if ($this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm(
            'Lanjutkan mengubah kelompok SMA menjadi numbering angka?',
            false
        )) {
            $this->warn('Proses dibatalkan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan): void {
            foreach ($plan as $row) {
                Kelas::query()
                    ->whereKey($row['id'])
                    ->lockForUpdate()
                    ->firstOrFail()
                    ->forceFill([
                        'kelompok' => $row['new_group'],
                        'nama_kelas' => $row['new_name'],
                        'kode' => $row['new_code'],
                        'urutan' => $row['new_order'],
                    ])->save();
            }
        });

        $this->info('Kelompok SMA berhasil dinormalisasi ke numbering angka.');
        $this->comment('ID kelas tidak berubah; santri, musyrif, placement, dan histori tetap terhubung.');

        return self::SUCCESS;
    }

    private function buildPlan(): array
    {
        $parents = Kelas::query()
            ->induk()
            ->with(['children' => fn ($query) => $query->orderBy('urutan')->orderBy('id')])
            ->get();

        $plan = [];

        foreach ($parents as $parent) {
            if (!$parent->usesNumericGroups()) {
                continue;
            }

            foreach ($parent->children as $child) {
                $oldGroup = strtoupper(trim((string) $child->kelompok));

                if (preg_match('/^[1-9][0-9]*$/', $oldGroup)) {
                    continue;
                }

                if (!preg_match('/^[A-Z]$/', $oldGroup)) {
                    throw ValidationException::withMessages([
                        'kelompok' => [
                            "Kelompok {$child->nama_kelas} bernilai '{$oldGroup}' dan tidak dapat dikonversi otomatis.",
                        ],
                    ]);
                }

                $newGroup = (string) (ord($oldGroup) - 64);
                $newName = trim($parent->nama_kelas . ' ' . $newGroup);
                $newCode = trim((string) $parent->kode . '-' . $newGroup, '-');

                $groupConflict = Kelas::query()
                    ->where('parent_id', $parent->id)
                    ->where('kelompok', $newGroup)
                    ->where('id', '!=', $child->id)
                    ->exists();

                $nameConflict = Kelas::query()
                    ->whereRaw('LOWER(TRIM(nama_kelas)) = ?', [mb_strtolower($newName)])
                    ->where('id', '!=', $child->id)
                    ->exists();

                $codeConflict = Kelas::query()
                    ->where('kode', $newCode)
                    ->where('id', '!=', $child->id)
                    ->exists();

                if ($groupConflict || $nameConflict || $codeConflict) {
                    throw ValidationException::withMessages([
                        'kelompok' => [
                            "Konversi {$child->nama_kelas} ke {$newName} konflik dengan data kelas yang sudah ada.",
                        ],
                    ]);
                }

                $plan[] = [
                    'id' => (int) $child->id,
                    'parent_name' => (string) $parent->nama_kelas,
                    'old_name' => (string) $child->nama_kelas,
                    'new_group' => $newGroup,
                    'new_name' => $newName,
                    'new_code' => $newCode,
                    'new_order' => ((int) $parent->urutan) + (int) $newGroup,
                ];
            }
        }

        return $plan;
    }
}
