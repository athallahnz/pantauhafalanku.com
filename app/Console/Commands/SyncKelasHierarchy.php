<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncKelasHierarchy extends Command
{
    protected $signature = 'kelas:sync-hierarchy
        {--dry-run : Tampilkan rencana tanpa menyimpan perubahan}
        {--parent= : Proses hanya satu kode/nama kelas induk}
        {--force : Lewati konfirmasi interaktif}';

    protected $description = 'Menyinkronkan kelas induk dan kelompok SMP alfabet/SMA numbering tanpa memindahkan assignment.';

    public function handle(): int
    {
        $parents = collect(config('kelas_hierarchy.parents', []));

        if ($parents->isEmpty()) {
            $this->error('Konfigurasi config/kelas_hierarchy.php tidak berisi parents.');
            return self::FAILURE;
        }

        $requestedParent = strtoupper(trim((string) $this->option('parent')));

        if ($requestedParent !== '') {
            $parents = $parents->filter(function (array $settings, string $key) use ($requestedParent): bool {
                $names = collect($settings['names'] ?? [$key])
                    ->map(fn ($name) => strtoupper(trim((string) $name)));

                return strtoupper($key) === $requestedParent
                    || strtoupper((string) ($settings['code'] ?? '')) === $requestedParent
                    || $names->contains($requestedParent);
            });

            if ($parents->isEmpty()) {
                $this->error("Kelas induk '{$requestedParent}' tidak ditemukan dalam konfigurasi.");
                return self::FAILURE;
            }
        }

        try {
            $plan = $this->buildPlan($parents);
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

        $this->renderPlan($plan);

        if ($this->option('dry-run')) {
            $this->info('Dry-run selesai. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm(
            'Lanjutkan menyimpan struktur kelas induk dan kelompok?',
            false
        )) {
            $this->warn('Proses dibatalkan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($plan): void {
            foreach ($plan as $row) {
                $parent = Kelas::query()
                    ->whereKey($row['parent_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $parent->forceFill([
                    'parent_id' => null,
                    'kelompok' => null,
                    'kode' => $row['parent_code'],
                    'is_active' => true,
                    'urutan' => $row['parent_order'],
                ])->save();

                $child = Kelas::query()
                    ->where('parent_id', $parent->id)
                    ->where('kelompok', $row['group'])
                    ->lockForUpdate()
                    ->first();

                if (!$child) {
                    $child = Kelas::query()
                        ->where('kode', $row['child_code'])
                        ->lockForUpdate()
                        ->first();
                }

                if (!$child) {
                    $child = Kelas::query()
                        ->whereRaw('LOWER(TRIM(nama_kelas)) = ?', [mb_strtolower($row['child_name'])])
                        ->lockForUpdate()
                        ->first();
                }

                $child ??= new Kelas();

                $child->forceFill([
                    'parent_id' => $parent->id,
                    'nama_kelas' => $row['child_name'],
                    'kelompok' => $row['group'],
                    'deskripsi' => $parent->deskripsi,
                    'kode' => $row['child_code'],
                    'is_active' => true,
                    'urutan' => $row['child_order'],
                ])->save();
            }
        });

        $this->newLine();
        $this->info('Struktur kelas berhasil disinkronkan/diperbaiki.');
        $this->comment('Assignment santri, musyrif, placement, histori, dan batch migrasi tidak diubah.');

        return self::SUCCESS;
    }

    private function buildPlan(Collection $parents): array
    {
        $namePattern = (string) config('kelas_hierarchy.group_name_pattern', '{parent} {group}');
        $codePattern = (string) config('kelas_hierarchy.group_code_pattern', '{parent_code}-{group}');
        $step = max(1, (int) config('kelas_hierarchy.group_order_step', 1));

        $plan = [];
        $seenCodes = [];
        $seenNames = [];

        foreach ($parents as $configKey => $settings) {
            $parentCode = strtoupper(trim((string) ($settings['code'] ?? $configKey)));
            $parentOrder = max(0, (int) ($settings['order'] ?? 0));
            $groupMode = (string) ($settings['group_mode'] ?? 'alpha');
            $aliases = collect($settings['names'] ?? [$configKey])
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->values();

            $groups = collect($settings['groups'] ?? [])
                ->map(fn ($group) => strtoupper(trim((string) $group)))
                ->filter()
                ->unique()
                ->values();

            if ($parentCode === '' || $aliases->isEmpty() || $groups->isEmpty()) {
                throw ValidationException::withMessages([
                    $configKey => ['Kode, alias nama, dan daftar kelompok wajib diisi.'],
                ]);
            }

            $invalidGroups = $groups->reject(function (string $group) use ($groupMode): bool {
                return $groupMode === 'numeric'
                    ? preg_match('/^[1-9][0-9]*$/', $group) === 1
                    : preg_match('/^[A-Z]$/', $group) === 1;
            });

            if ($invalidGroups->isNotEmpty()) {
                $format = $groupMode === 'numeric' ? 'angka positif' : 'satu huruf A-Z';
                throw ValidationException::withMessages([
                    $configKey => [
                        "Kelompok tidak valid: {$invalidGroups->implode(', ')}. Gunakan {$format}.",
                    ],
                ]);
            }

            $parent = Kelas::query()
                ->whereNull('parent_id')
                ->where(function ($query) use ($parentCode, $aliases): void {
                    $query->where('kode', $parentCode)
                        ->orWhereIn('nama_kelas', $aliases->all());
                })
                ->orderByRaw('CASE WHEN kode = ? THEN 0 ELSE 1 END', [$parentCode])
                ->first();

            if (!$parent) {
                throw ValidationException::withMessages([
                    $configKey => [
                        "Parent {$parentCode} tidak ditemukan. Alias: {$aliases->implode(', ')}.",
                    ],
                ]);
            }

            $parentName = (string) $parent->nama_kelas;

            foreach ($groups as $index => $group) {
                $childName = trim(strtr($namePattern, [
                    '{parent}' => $parentName,
                    '{group}' => $group,
                ]));
                $childCode = strtoupper(trim(strtr($codePattern, [
                    '{parent_code}' => $parentCode,
                    '{group}' => $group,
                ])));
                $childOrder = $parentOrder + (($index + 1) * $step);

                if (isset($seenNames[mb_strtolower($childName)]) || isset($seenCodes[$childCode])) {
                    throw ValidationException::withMessages([
                        $configKey => ["Nama/kode child duplikat: {$childName} / {$childCode}."],
                    ]);
                }

                $seenNames[mb_strtolower($childName)] = true;
                $seenCodes[$childCode] = true;

                $plan[] = [
                    'action' => Kelas::query()
                        ->where('parent_id', $parent->id)
                        ->where('kelompok', $group)
                        ->exists() ? 'UPDATE' : 'CREATE',
                    'parent_id' => (int) $parent->id,
                    'parent_name' => $parentName,
                    'parent_code' => $parentCode,
                    'parent_order' => $parentOrder,
                    'group_mode' => $groupMode,
                    'group' => $group,
                    'child_name' => $childName,
                    'child_code' => $childCode,
                    'child_order' => $childOrder,
                ];
            }
        }

        return $plan;
    }

    private function renderPlan(array $plan): void
    {
        $this->table(
            ['Aksi', 'Parent ID', 'Kelas Induk', 'Kode', 'Mode', 'Kelompok', 'Nama Child', 'Kode Child'],
            collect($plan)->map(fn (array $row) => [
                $row['action'],
                $row['parent_id'],
                $row['parent_name'],
                $row['parent_code'],
                $row['group_mode'] === 'numeric' ? 'Angka' : 'ABC',
                $row['group'],
                $row['child_name'],
                $row['child_code'],
            ])->all()
        );

        $this->line('Total kelompok dalam rencana: ' . count($plan));
    }
}
