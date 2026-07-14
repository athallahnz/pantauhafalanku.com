<?php

namespace App\Services;

use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\SystemIntegrityRepairLog;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserProfileConsistencyService
{
    private const CACHE_KEY = 'superadmin.system-integrity.scan.v1';
    private const CACHE_SECONDS = 60;

    private const AUTO_REPAIR_TYPES = [
        'missing_musyrif_profile',
        'musyrif_missing_kode',
        'musyrif_name_mismatch',
        'santri_name_mismatch',
        'missing_active_placement',
        'active_account_not_approved',
        'account_status_empty',
        'archived_status_not_deleted',
        'deleted_user_not_archived',
    ];

    /**
     * @return array{
     *     scanned_at:string,
     *     status:string,
     *     summary:array<string,int>,
     *     active_semester:?array<string,mixed>,
     *     issues:array<int,array<string,mixed>>
     * }
     */
    public function scan(bool $useCache = true): array
    {
        if ($useCache) {
            return Cache::remember(
                self::CACHE_KEY,
                now()->addSeconds(self::CACHE_SECONDS),
                fn(): array => $this->performScan()
            );
        }

        return $this->performScan();
    }

    /**
     * @return array<string,int|string|null>
     */
    public function summary(bool $useCache = true): array
    {
        $scan = $this->scan($useCache);

        return array_merge(
            $scan['summary'],
            [
                'status' => $scan['status'],
                'scanned_at' => $scan['scanned_at'],
                'active_semester_id' => $scan['active_semester']['id'] ?? null,
                'active_semester_label' => $scan['active_semester']['label'] ?? null,
            ]
        );
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function repair(
        User $actor,
        string $issueType,
        int $entityId,
        array $payload = []
    ): array {
        $supported = array_merge(
            self::AUTO_REPAIR_TYPES,
            ['missing_santri_profile']
        );

        if (!in_array($issueType, $supported, true)) {
            throw ValidationException::withMessages([
                'issue_type' => [
                    'Masalah ini memerlukan pemeriksaan manual dan tidak dapat diperbaiki otomatis.',
                ],
            ]);
        }

        try {
            $result = DB::transaction(function () use (
                $actor,
                $issueType,
                $entityId,
                $payload
            ): array {
                return match ($issueType) {
                    'missing_musyrif_profile' => $this->repairMissingMusyrifProfile(
                        $actor,
                        $entityId
                    ),
                    'missing_santri_profile' => $this->repairMissingSantriProfile(
                        $actor,
                        $entityId,
                        $payload
                    ),
                    'musyrif_missing_kode' => $this->repairMusyrifCode(
                        $actor,
                        $entityId
                    ),
                    'musyrif_name_mismatch' => $this->repairMusyrifName(
                        $actor,
                        $entityId
                    ),
                    'santri_name_mismatch' => $this->repairSantriName(
                        $actor,
                        $entityId
                    ),
                    'missing_active_placement' => $this->repairMissingPlacement(
                        $actor,
                        $entityId
                    ),
                    'active_account_not_approved' => $this->repairActiveApproval(
                        $actor,
                        $entityId
                    ),
                    'account_status_empty' => $this->repairEmptyAccountStatus(
                        $actor,
                        $entityId
                    ),
                    'archived_status_not_deleted' => $this->repairArchivedSoftDelete(
                        $actor,
                        $entityId
                    ),
                    'deleted_user_not_archived' => $this->repairDeletedStatus(
                        $actor,
                        $entityId
                    ),
                    default => throw ValidationException::withMessages([
                        'issue_type' => ['Jenis perbaikan tidak dikenali.'],
                    ]),
                };
            });

            $this->forgetCache();

            return $result;
        } catch (Throwable $exception) {
            $this->writeRepairLog(
                actor: $actor,
                issueType: $issueType,
                entityType: $this->entityTypeForIssue($issueType),
                entityId: $entityId,
                action: 'repair_failed',
                status: 'failed',
                reason: $exception->getMessage(),
                before: null,
                after: null,
                metadata: ['payload' => $payload]
            );

            throw $exception;
        }
    }

    /**
     * Menjalankan hanya perbaikan deterministik yang tidak meminta input tambahan.
     *
     * @return array{success:int,failed:int,skipped:int,results:array<int,array<string,mixed>>}
     */
    public function repairAllSafe(User $actor): array
    {
        $issues = collect($this->scan(false)['issues'])
            ->filter(
                fn(array $issue): bool =>
                    (bool) ($issue['safe_auto_repair'] ?? false)
                    && in_array(
                        (string) $issue['type'],
                        self::AUTO_REPAIR_TYPES,
                        true
                    )
            )
            ->unique(
                fn(array $issue): string =>
                    $issue['type'] . ':' . $issue['entity_id']
            )
            ->values();

        $results = [];
        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($issues as $issue) {
            try {
                $results[] = $this->repair(
                    $actor,
                    (string) $issue['type'],
                    (int) $issue['entity_id']
                );
                $success++;
            } catch (ValidationException $exception) {
                $skipped++;
                $results[] = [
                    'status' => 'skipped',
                    'type' => $issue['type'],
                    'entity_id' => $issue['entity_id'],
                    'message' => collect($exception->errors())->flatten()->first()
                        ?? $exception->getMessage(),
                ];
            } catch (Throwable $exception) {
                $failed++;
                $results[] = [
                    'status' => 'failed',
                    'type' => $issue['type'],
                    'entity_id' => $issue['entity_id'],
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $this->forgetCache();

        return compact('success', 'failed', 'skipped', 'results');
    }

    /**
     * @return array<string,mixed>
     */
    private function performScan(): array
    {
        $issues = collect();
        $activeSemester = $this->scanSemester($issues);

        $this->scanAccountLifecycle($issues);
        $this->scanUserProfiles($issues);
        $this->scanMusyrifProfiles($issues);
        $this->scanSantriProfiles($issues);
        $this->scanPlacements($issues, $activeSemester);

        $issues = $issues
            ->sortBy(function (array $issue): string {
                $priority = match ($issue['severity']) {
                    'critical' => '1',
                    'warning' => '2',
                    default => '3',
                };

                return $priority . '|' . $issue['category'] . '|' . $issue['title'];
            })
            ->values();

        $summary = [
            'total' => $issues->count(),
            'critical' => $issues->where('severity', 'critical')->count(),
            'warning' => $issues->where('severity', 'warning')->count(),
            'info' => $issues->where('severity', 'info')->count(),
            'repairable' => $issues->where('repairable', true)->count(),
            'safe_auto_repair' => $issues->where('safe_auto_repair', true)->count(),
            'manual' => $issues->where('repairable', false)->count(),
        ];

        $status = $summary['critical'] > 0
            ? 'critical'
            : ($summary['warning'] > 0 ? 'warning' : 'healthy');

        return [
            'scanned_at' => now()->toIso8601String(),
            'status' => $status,
            'summary' => $summary,
            'active_semester' => $activeSemester,
            'issues' => $issues->all(),
        ];
    }

    /**
     * @param Collection<int,array<string,mixed>> $issues
     * @return array<string,mixed>|null
     */
    private function scanSemester(Collection $issues): ?array
    {
        if (!Schema::hasTable('semesters')) {
            $issues->push($this->issue(
                type: 'semester_table_missing',
                severity: 'critical',
                category: 'semester',
                title: 'Tabel semester tidak tersedia',
                description: 'Pemeriksaan placement tidak dapat dilakukan karena tabel semesters tidak ditemukan.',
                entityType: 'system',
                entityId: 0,
                entityLabel: 'Konfigurasi akademik',
                repairable: false
            ));

            return null;
        }

        $query = DB::table('semesters');

        if (Schema::hasColumn('semesters', 'status')) {
            $query->where('status', 'active');
        } elseif (Schema::hasColumn('semesters', 'is_active')) {
            $query->where('is_active', true);
        } else {
            $issues->push($this->issue(
                type: 'semester_active_marker_missing',
                severity: 'critical',
                category: 'semester',
                title: 'Penanda semester aktif tidak ditemukan',
                description: 'Tabel semesters tidak memiliki kolom status maupun is_active.',
                entityType: 'system',
                entityId: 0,
                entityLabel: 'Konfigurasi semester',
                repairable: false
            ));

            return null;
        }

        $active = $query
            ->orderByDesc(
                Schema::hasColumn('semesters', 'tanggal_mulai')
                    ? 'tanggal_mulai'
                    : 'id'
            )
            ->get();

        if ($active->isEmpty()) {
            $issues->push($this->issue(
                type: 'active_semester_missing',
                severity: 'critical',
                category: 'semester',
                title: 'Tidak ada semester aktif',
                description: 'Placement dan transaksi akademik tidak memiliki konteks semester aktif.',
                entityType: 'semester',
                entityId: 0,
                entityLabel: 'Semester aktif',
                repairable: false
            ));

            return null;
        }

        if ($active->count() > 1) {
            $issues->push($this->issue(
                type: 'multiple_active_semesters',
                severity: 'critical',
                category: 'semester',
                title: 'Lebih dari satu semester aktif',
                description: $active->count()
                    . ' semester berstatus aktif. Kondisi ini harus diselesaikan sebelum perbaikan placement.',
                entityType: 'semester',
                entityId: (int) $active->first()->id,
                entityLabel: $active->pluck('nama')->filter()->implode(', ')
                    ?: 'Beberapa semester aktif',
                repairable: false,
                context: ['semester_ids' => $active->pluck('id')->all()]
            ));
        }

        $semester = $active->first();
        $labelParts = [];

        if (isset($semester->nama)) {
            $labelParts[] = $semester->nama;
        }

        if (Schema::hasColumn('semesters', 'tahun_ajaran_id')
            && Schema::hasTable('tahun_ajarans')
            && $semester->tahun_ajaran_id
        ) {
            $tahun = DB::table('tahun_ajarans')
                ->where('id', $semester->tahun_ajaran_id)
                ->value('nama');

            if ($tahun) {
                $labelParts[] = $tahun;
            }
        }

        return [
            'id' => (int) $semester->id,
            'label' => trim(implode(' ', $labelParts)) ?: 'Semester #' . $semester->id,
            'count_active' => $active->count(),
        ];
    }

    /** @param Collection<int,array<string,mixed>> $issues */
    private function scanAccountLifecycle(Collection $issues): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'account_status')) {
            DB::table('users')
                ->where(function (Builder $query): void {
                    $query->whereNull('account_status')
                        ->orWhere('account_status', '');
                })
                ->select(['id', 'name', 'role', 'is_approved'])
                ->orderBy('id')
                ->get()
                ->each(function (object $user) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'account_status_empty',
                        severity: 'warning',
                        category: 'account',
                        title: 'Status lifecycle akun kosong',
                        description: 'Status akan dipetakan dari nilai is_approved agar middleware lifecycle dapat bekerja.',
                        entityType: 'user',
                        entityId: (int) $user->id,
                        entityLabel: $user->name . ' · ' . strtoupper((string) $user->role),
                        repairable: true,
                        safeAutoRepair: true,
                        repairLabel: 'Sinkronkan status'
                    ));
                });

            DB::table('users')
                ->where('account_status', 'active')
                ->where('is_approved', false)
                ->when(
                    Schema::hasColumn('users', 'deleted_at'),
                    fn(Builder $query): Builder => $query->whereNull('deleted_at')
                )
                ->select(['id', 'name', 'role'])
                ->get()
                ->each(function (object $user) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'active_account_not_approved',
                        severity: 'warning',
                        category: 'account',
                        title: 'Akun active tetapi belum approved',
                        description: 'Status active dan is_approved tidak sinkron sehingga akses dapat ditolak middleware.',
                        entityType: 'user',
                        entityId: (int) $user->id,
                        entityLabel: $user->name . ' · ' . strtoupper((string) $user->role),
                        repairable: true,
                        safeAutoRepair: true,
                        repairLabel: 'Aktifkan approval'
                    ));
                });
        }

        if (Schema::hasColumn('users', 'deleted_at')
            && Schema::hasColumn('users', 'account_status')
        ) {
            DB::table('users')
                ->where('account_status', 'archived')
                ->whereNull('deleted_at')
                ->select(['id', 'name', 'role'])
                ->get()
                ->each(function (object $user) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'archived_status_not_deleted',
                        severity: 'warning',
                        category: 'account',
                        title: 'Status archived belum soft delete',
                        description: 'Akun berstatus archived masih terbaca sebagai akun aktif pada query default.',
                        entityType: 'user',
                        entityId: (int) $user->id,
                        entityLabel: $user->name . ' · ' . strtoupper((string) $user->role),
                        repairable: true,
                        safeAutoRepair: true,
                        repairLabel: 'Selesaikan arsip'
                    ));
                });

            DB::table('users')
                ->whereNotNull('deleted_at')
                ->where(function (Builder $query): void {
                    $query->whereNull('account_status')
                        ->orWhere('account_status', '!=', 'archived');
                })
                ->select(['id', 'name', 'role', 'account_status'])
                ->get()
                ->each(function (object $user) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'deleted_user_not_archived',
                        severity: 'warning',
                        category: 'account',
                        title: 'Soft-deleted user tidak berstatus archived',
                        description: 'Nilai account_status perlu diselaraskan dengan deleted_at.',
                        entityType: 'user',
                        entityId: (int) $user->id,
                        entityLabel: $user->name . ' · ' . strtoupper((string) $user->role),
                        repairable: true,
                        safeAutoRepair: true,
                        repairLabel: 'Sinkronkan arsip',
                        context: ['current_status' => $user->account_status]
                    ));
                });
        }
    }

    /** @param Collection<int,array<string,mixed>> $issues */
    private function scanUserProfiles(Collection $issues): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasTable('musyrifs')) {
            $query = DB::table('users as u')
                ->leftJoin('musyrifs as m', 'm.user_id', '=', 'u.id')
                ->where('u.role', 'musyrif')
                ->whereNull('m.id');

            $this->applyActiveUserFilter($query, 'u');

            $query->select(['u.id', 'u.name', 'u.email'])
                ->get()
                ->each(function (object $user) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'missing_musyrif_profile',
                        severity: 'warning',
                        category: 'profile',
                        title: 'Akun Musyrif belum memiliki profil',
                        description: 'User memiliki role musyrif, tetapi tidak terhubung ke tabel musyrifs.',
                        entityType: 'user',
                        entityId: (int) $user->id,
                        entityLabel: $user->name . ' · ' . $user->email,
                        repairable: true,
                        safeAutoRepair: true,
                        repairLabel: 'Buat profil Musyrif'
                    ));
                });
        }

        if (Schema::hasTable('santris')) {
            $query = DB::table('users as u')
                ->leftJoin('santris as s', 's.user_id', '=', 'u.id')
                ->where('u.role', 'santri')
                ->whereNull('s.id');

            $this->applyActiveUserFilter($query, 'u');

            $query->select(['u.id', 'u.name', 'u.email'])
                ->get()
                ->each(function (object $user) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'missing_santri_profile',
                        severity: 'warning',
                        category: 'profile',
                        title: 'Akun Santri belum memiliki profil',
                        description: 'Perbaikan membutuhkan pemilihan kelas agar profil dan placement tidak salah.',
                        entityType: 'user',
                        entityId: (int) $user->id,
                        entityLabel: $user->name . ' · ' . $user->email,
                        repairable: true,
                        safeAutoRepair: false,
                        requiresInput: true,
                        repairLabel: 'Lengkapi profil Santri'
                    ));
                });
        }
    }

    /** @param Collection<int,array<string,mixed>> $issues */
    private function scanMusyrifProfiles(Collection $issues): void
    {
        if (!Schema::hasTable('musyrifs') || !Schema::hasTable('users')) {
            return;
        }

        DB::table('musyrifs as m')
            ->whereNull('m.user_id')
            ->select(['m.id', 'm.nama'])
            ->get()
            ->each(function (object $profile) use ($issues): void {
                $issues->push($this->issue(
                    type: 'musyrif_without_account',
                    severity: 'warning',
                    category: 'profile',
                    title: 'Musyrif belum memiliki akun akses',
                    description: 'Profil masih valid untuk histori, tetapi Musyrif belum dapat login menggunakan profil ini.',
                    entityType: 'musyrif',
                    entityId: (int) $profile->id,
                    entityLabel: $profile->nama,
                    repairable: false
                ));
            });

        DB::table('musyrifs as m')
            ->leftJoin('users as u', 'u.id', '=', 'm.user_id')
            ->whereNotNull('m.user_id')
            ->whereNull('u.id')
            ->select(['m.id', 'm.nama', 'm.user_id'])
            ->get()
            ->each(function (object $profile) use ($issues): void {
                $issues->push($this->issue(
                    type: 'orphan_musyrif_profile',
                    severity: 'critical',
                    category: 'profile',
                    title: 'Profil Musyrif mengarah ke akun yang hilang',
                    description: 'user_id terisi tetapi akun tujuan tidak ditemukan. Tinjau histori sebelum menghubungkan ulang.',
                    entityType: 'musyrif',
                    entityId: (int) $profile->id,
                    entityLabel: $profile->nama . ' · user_id ' . $profile->user_id,
                    repairable: false
                ));
            });

        DB::table('musyrifs as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('u.role', '!=', 'musyrif')
            ->select(['m.id', 'm.nama', 'u.id as user_id', 'u.name as user_name', 'u.role'])
            ->get()
            ->each(function (object $row) use ($issues): void {
                $issues->push($this->issue(
                    type: 'musyrif_role_mismatch',
                    severity: 'critical',
                    category: 'profile',
                    title: 'Profil Musyrif terhubung ke role lain',
                    description: 'Perubahan role otomatis berisiko merusak histori; lakukan keputusan manual setelah memeriksa relasi.',
                    entityType: 'musyrif',
                    entityId: (int) $row->id,
                    entityLabel: $row->nama . ' → ' . $row->user_name . ' (' . strtoupper($row->role) . ')',
                    repairable: false,
                    context: ['user_id' => (int) $row->user_id]
                ));
            });

        DB::table('musyrifs')
            ->whereNotNull('user_id')
            ->select('user_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $row) use ($issues): void {
                $user = DB::table('users')->where('id', $row->user_id)->first();

                $issues->push($this->issue(
                    type: 'duplicate_musyrif_profile',
                    severity: 'critical',
                    category: 'profile',
                    title: 'Satu akun memiliki beberapa profil Musyrif',
                    description: $row->total . ' profil Musyrif menggunakan user_id yang sama.',
                    entityType: 'user',
                    entityId: (int) $row->user_id,
                    entityLabel: ($user->name ?? 'User #' . $row->user_id),
                    repairable: false,
                    context: ['profile_count' => (int) $row->total]
                ));
            });

        if (Schema::hasColumn('musyrifs', 'kode')) {
            DB::table('musyrifs')
                ->where(function (Builder $query): void {
                    $query->whereNull('kode')->orWhere('kode', '');
                })
                ->select(['id', 'nama'])
                ->get()
                ->each(function (object $profile) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'musyrif_missing_kode',
                        severity: 'info',
                        category: 'profile',
                        title: 'Kode Musyrif belum tersedia',
                        description: 'Kode dapat dibuat otomatis dari inisial nama dan ID profil.',
                        entityType: 'musyrif',
                        entityId: (int) $profile->id,
                        entityLabel: $profile->nama,
                        repairable: true,
                        safeAutoRepair: true,
                        repairLabel: 'Buat kode'
                    ));
                });
        }

        DB::table('musyrifs as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->whereColumn('m.nama', '!=', 'u.name')
            ->select(['m.id', 'm.nama', 'u.name as user_name'])
            ->get()
            ->each(function (object $row) use ($issues): void {
                $issues->push($this->issue(
                    type: 'musyrif_name_mismatch',
                    severity: 'info',
                    category: 'profile',
                    title: 'Nama akun dan profil Musyrif berbeda',
                    description: 'Nama profil akan disamakan dengan nama akun sebagai sumber identitas utama.',
                    entityType: 'musyrif',
                    entityId: (int) $row->id,
                    entityLabel: $row->nama . ' ↔ ' . $row->user_name,
                    repairable: true,
                    safeAutoRepair: true,
                    repairLabel: 'Sinkronkan nama'
                ));
            });
    }

    /** @param Collection<int,array<string,mixed>> $issues */
    private function scanSantriProfiles(Collection $issues): void
    {
        if (!Schema::hasTable('santris') || !Schema::hasTable('users')) {
            return;
        }

        $withoutAccount = DB::table('santris as s')
            ->whereNull('s.user_id');
        $this->applyActiveSantriFilter($withoutAccount, 's');
        $withoutAccount
            ->select(['s.id', 's.nama'])
            ->get()
            ->each(function (object $profile) use ($issues): void {
                $issues->push($this->issue(
                    type: 'santri_without_account',
                    severity: 'info',
                    category: 'profile',
                    title: 'Santri aktif belum memiliki akun akses',
                    description: 'Ini tidak selalu salah. Informasi ditampilkan agar Super Admin mengetahui cakupan akses login Santri.',
                    entityType: 'santri',
                    entityId: (int) $profile->id,
                    entityLabel: $profile->nama,
                    repairable: false
                ));
            });

        DB::table('santris as s')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->whereNotNull('s.user_id')
            ->whereNull('u.id')
            ->select(['s.id', 's.nama', 's.user_id'])
            ->get()
            ->each(function (object $profile) use ($issues): void {
                $issues->push($this->issue(
                    type: 'orphan_santri_profile',
                    severity: 'critical',
                    category: 'profile',
                    title: 'Profil Santri mengarah ke akun yang hilang',
                    description: 'user_id terisi tetapi akun tujuan tidak ditemukan. Tinjau histori sebelum menghubungkan ulang.',
                    entityType: 'santri',
                    entityId: (int) $profile->id,
                    entityLabel: $profile->nama . ' · user_id ' . $profile->user_id,
                    repairable: false
                ));
            });

        DB::table('santris as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->where('u.role', '!=', 'santri')
            ->select(['s.id', 's.nama', 'u.id as user_id', 'u.name as user_name', 'u.role'])
            ->get()
            ->each(function (object $row) use ($issues): void {
                $issues->push($this->issue(
                    type: 'santri_role_mismatch',
                    severity: 'critical',
                    category: 'profile',
                    title: 'Profil Santri terhubung ke role lain',
                    description: 'Periksa histori dan tujuan perubahan role sebelum mengubah atau melepas relasi.',
                    entityType: 'santri',
                    entityId: (int) $row->id,
                    entityLabel: $row->nama . ' → ' . $row->user_name . ' (' . strtoupper($row->role) . ')',
                    repairable: false,
                    context: ['user_id' => (int) $row->user_id]
                ));
            });

        DB::table('santris')
            ->whereNotNull('user_id')
            ->select('user_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $row) use ($issues): void {
                $user = DB::table('users')->where('id', $row->user_id)->first();

                $issues->push($this->issue(
                    type: 'duplicate_santri_profile',
                    severity: 'critical',
                    category: 'profile',
                    title: 'Satu akun memiliki beberapa profil Santri',
                    description: $row->total . ' profil Santri menggunakan user_id yang sama.',
                    entityType: 'user',
                    entityId: (int) $row->user_id,
                    entityLabel: ($user->name ?? 'User #' . $row->user_id),
                    repairable: false,
                    context: ['profile_count' => (int) $row->total]
                ));
            });

        DB::table('santris as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->whereColumn('s.nama', '!=', 'u.name')
            ->select(['s.id', 's.nama', 'u.name as user_name'])
            ->get()
            ->each(function (object $row) use ($issues): void {
                $issues->push($this->issue(
                    type: 'santri_name_mismatch',
                    severity: 'info',
                    category: 'profile',
                    title: 'Nama akun dan profil Santri berbeda',
                    description: 'Nama profil akan disamakan dengan nama akun sebagai sumber identitas utama.',
                    entityType: 'santri',
                    entityId: (int) $row->id,
                    entityLabel: $row->nama . ' ↔ ' . $row->user_name,
                    repairable: true,
                    safeAutoRepair: true,
                    repairLabel: 'Sinkronkan nama'
                ));
            });
    }

    /**
     * @param Collection<int,array<string,mixed>> $issues
     * @param array<string,mixed>|null $activeSemester
     */
    private function scanPlacements(Collection $issues, ?array $activeSemester): void
    {
        if (!$activeSemester
            || (int) ($activeSemester['count_active'] ?? 0) !== 1
            || !Schema::hasTable('santri_semester_placements')
            || !Schema::hasTable('santris')
        ) {
            if ($activeSemester && !Schema::hasTable('santri_semester_placements')) {
                $issues->push($this->issue(
                    type: 'placement_table_missing',
                    severity: 'critical',
                    category: 'placement',
                    title: 'Tabel placement semester tidak tersedia',
                    description: 'Santri tidak dapat dipetakan secara historis ke semester aktif.',
                    entityType: 'system',
                    entityId: 0,
                    entityLabel: 'santri_semester_placements',
                    repairable: false
                ));
            }

            return;
        }

        $semesterId = (int) $activeSemester['id'];
        $query = DB::table('santris as s')
            ->leftJoin('santri_semester_placements as sp', function ($join) use ($semesterId): void {
                $join->on('sp.santri_id', '=', 's.id')
                    ->where('sp.semester_id', '=', $semesterId);
            })
            ->whereNull('sp.santri_id');

        $this->applyActiveSantriFilter($query, 's');

        $select = ['s.id', 's.nama'];
        $select[] = Schema::hasColumn('santris', 'kelas_id')
            ? 's.kelas_id'
            : DB::raw('NULL AS kelas_id');
        $select[] = Schema::hasColumn('santris', 'musyrif_id')
            ? 's.musyrif_id'
            : DB::raw('NULL AS musyrif_id');

        $query->select($select)
            ->get()
            ->each(function (object $santri) use ($issues, $semesterId): void {
                $kelasValid = $santri->kelas_id
                    && Schema::hasTable('kelas')
                    && DB::table('kelas')->where('id', $santri->kelas_id)->exists();

                $issues->push($this->issue(
                    type: 'missing_active_placement',
                    severity: 'warning',
                    category: 'placement',
                    title: 'Santri belum memiliki placement semester aktif',
                    description: $kelasValid
                        ? 'Placement dapat dibuat dari kelas dan musyrif terkini pada profil santri.'
                        : 'Profil santri belum memiliki kelas valid sehingga placement harus dilengkapi manual.',
                    entityType: 'santri',
                    entityId: (int) $santri->id,
                    entityLabel: $santri->nama,
                    repairable: (bool) $kelasValid,
                    safeAutoRepair: (bool) $kelasValid,
                    repairLabel: $kelasValid ? 'Buat placement' : null,
                    context: [
                        'semester_id' => $semesterId,
                        'kelas_id' => $santri->kelas_id ? (int) $santri->kelas_id : null,
                        'musyrif_id' => $santri->musyrif_id ? (int) $santri->musyrif_id : null,
                    ]
                ));
            });

        DB::table('santri_semester_placements')
            ->where('semester_id', $semesterId)
            ->select('santri_id')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('santri_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $row) use ($issues, $semesterId): void {
                $santri = DB::table('santris')->where('id', $row->santri_id)->first();

                $issues->push($this->issue(
                    type: 'duplicate_active_placement',
                    severity: 'critical',
                    category: 'placement',
                    title: 'Placement santri pada semester aktif duplikat',
                    description: $row->total . ' placement ditemukan untuk santri dan semester yang sama.',
                    entityType: 'santri',
                    entityId: (int) $row->santri_id,
                    entityLabel: $santri->nama ?? 'Santri #' . $row->santri_id,
                    repairable: false,
                    context: [
                        'semester_id' => $semesterId,
                        'placement_count' => (int) $row->total,
                    ]
                ));
            });

        if (Schema::hasTable('kelas')) {
            DB::table('santri_semester_placements as sp')
                ->leftJoin('kelas as k', 'k.id', '=', 'sp.kelas_id')
                ->leftJoin('santris as s', 's.id', '=', 'sp.santri_id')
                ->where('sp.semester_id', $semesterId)
                ->whereNull('k.id')
                ->select(['sp.id', 'sp.santri_id', 'sp.kelas_id', 's.nama'])
                ->get()
                ->each(function (object $row) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'placement_invalid_class',
                        severity: 'critical',
                        category: 'placement',
                        title: 'Placement mengarah ke kelas yang tidak tersedia',
                        description: 'Relasi kelas harus diperbaiki manual agar histori tidak dipetakan ke kelas yang salah.',
                        entityType: 'placement',
                        entityId: (int) $row->id,
                        entityLabel: ($row->nama ?? 'Santri #' . $row->santri_id)
                            . ' · kelas_id ' . ($row->kelas_id ?? '-'),
                        repairable: false
                    ));
                });
        }

        if (Schema::hasColumn('santri_semester_placements', 'musyrif_id')
            && Schema::hasTable('musyrifs')
        ) {
            DB::table('santri_semester_placements as sp')
                ->leftJoin('musyrifs as m', 'm.id', '=', 'sp.musyrif_id')
                ->leftJoin('santris as s', 's.id', '=', 'sp.santri_id')
                ->where('sp.semester_id', $semesterId)
                ->whereNotNull('sp.musyrif_id')
                ->whereNull('m.id')
                ->select(['sp.id', 'sp.santri_id', 'sp.musyrif_id', 's.nama'])
                ->get()
                ->each(function (object $row) use ($issues): void {
                    $issues->push($this->issue(
                        type: 'placement_invalid_musyrif',
                        severity: 'critical',
                        category: 'placement',
                        title: 'Placement mengarah ke Musyrif yang tidak tersedia',
                        description: 'Relasi Musyrif harus ditinjau manual untuk menjaga histori pembinaan.',
                        entityType: 'placement',
                        entityId: (int) $row->id,
                        entityLabel: ($row->nama ?? 'Santri #' . $row->santri_id)
                            . ' · musyrif_id ' . $row->musyrif_id,
                        repairable: false
                    ));
                });
        }
    }

    /** @return array<string,mixed> */
    private function repairMissingMusyrifProfile(User $actor, int $userId): array
    {
        $user = User::query()->lockForUpdate()->findOrFail($userId);

        if ($user->role !== 'musyrif') {
            throw ValidationException::withMessages([
                'user' => ['User bukan lagi role Musyrif. Muat ulang pemeriksaan.'],
            ]);
        }

        if (Musyrif::query()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => ['Profil Musyrif sudah tersedia.'],
            ]);
        }

        $before = ['user' => $this->userSnapshot($user), 'profile' => null];
        $musyrif = Musyrif::query()->create([
            'user_id' => $user->id,
            'nama' => $user->name,
        ]);

        if (Schema::hasColumn('musyrifs', 'kode') && !$musyrif->kode) {
            $musyrif->kode = $this->generateKode($musyrif->nama, (int) $musyrif->id);
            $musyrif->save();
        }

        $after = ['user' => $this->userSnapshot($user), 'profile' => $musyrif->fresh()->toArray()];
        $this->writeRepairLog(
            $actor,
            'missing_musyrif_profile',
            'user',
            $user->id,
            'create_musyrif_profile',
            'success',
            'Profil Musyrif dibuat dari akun yang sudah ada.',
            $before,
            $after
        );

        return [
            'status' => 'success',
            'message' => 'Profil Musyrif untuk ' . $user->name . ' berhasil dibuat.',
        ];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function repairMissingSantriProfile(
        User $actor,
        int $userId,
        array $payload
    ): array {
        $kelasId = isset($payload['kelas_id']) ? (int) $payload['kelas_id'] : 0;
        $musyrifId = !empty($payload['musyrif_id']) ? (int) $payload['musyrif_id'] : null;

        if ($kelasId <= 0 || !DB::table('kelas')->where('id', $kelasId)->exists()) {
            throw ValidationException::withMessages([
                'kelas_id' => ['Pilih kelas yang valid untuk membuat profil Santri.'],
            ]);
        }

        if ($musyrifId && !Musyrif::query()->whereKey($musyrifId)->exists()) {
            throw ValidationException::withMessages([
                'musyrif_id' => ['Musyrif yang dipilih tidak tersedia.'],
            ]);
        }

        $user = User::query()->lockForUpdate()->findOrFail($userId);

        if ($user->role !== 'santri') {
            throw ValidationException::withMessages([
                'user' => ['User bukan lagi role Santri. Muat ulang pemeriksaan.'],
            ]);
        }

        if (Santri::query()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user' => ['Profil Santri sudah tersedia.'],
            ]);
        }

        $attributes = [
            'user_id' => $user->id,
            'nama' => $user->name,
            'kelas_id' => $kelasId,
        ];

        if (Schema::hasColumn('santris', 'musyrif_id')) {
            $attributes['musyrif_id'] = $musyrifId;
        }

        $before = ['user' => $this->userSnapshot($user), 'profile' => null];
        $santri = Santri::query()->create($attributes);
        $placementCreated = $this->createPlacementFromProfile($santri);
        $after = [
            'user' => $this->userSnapshot($user),
            'profile' => $santri->fresh()->toArray(),
            'placement_created' => $placementCreated,
        ];

        $this->writeRepairLog(
            $actor,
            'missing_santri_profile',
            'user',
            $user->id,
            'create_santri_profile',
            'success',
            'Profil Santri dibuat melalui guided repair.',
            $before,
            $after,
            ['kelas_id' => $kelasId, 'musyrif_id' => $musyrifId]
        );

        return [
            'status' => 'success',
            'message' => 'Profil Santri untuk ' . $user->name . ' berhasil dibuat.'
                . ($placementCreated ? ' Placement semester aktif juga dibuat.' : ''),
        ];
    }

    /** @return array<string,mixed> */
    private function repairMusyrifCode(User $actor, int $musyrifId): array
    {
        $musyrif = Musyrif::query()->lockForUpdate()->findOrFail($musyrifId);

        if (!Schema::hasColumn('musyrifs', 'kode')) {
            throw ValidationException::withMessages([
                'kode' => ['Kolom kode tidak tersedia pada tabel musyrifs.'],
            ]);
        }

        if (!empty($musyrif->kode)) {
            throw ValidationException::withMessages([
                'kode' => ['Kode Musyrif sudah tersedia.'],
            ]);
        }

        $before = $musyrif->toArray();
        $musyrif->kode = $this->generateKode($musyrif->nama, (int) $musyrif->id);
        $musyrif->save();

        $this->writeRepairLog(
            $actor,
            'musyrif_missing_kode',
            'musyrif',
            $musyrif->id,
            'generate_musyrif_code',
            'success',
            'Kode Musyrif dibuat otomatis.',
            $before,
            $musyrif->fresh()->toArray()
        );

        return [
            'status' => 'success',
            'message' => 'Kode Musyrif berhasil dibuat: ' . $musyrif->kode,
        ];
    }

    /** @return array<string,mixed> */
    private function repairMusyrifName(User $actor, int $musyrifId): array
    {
        $musyrif = Musyrif::query()->lockForUpdate()->findOrFail($musyrifId);
        $user = User::query()->withTrashed()->find($musyrif->user_id);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['Akun Musyrif tidak ditemukan.'],
            ]);
        }

        $before = $musyrif->toArray();
        $musyrif->nama = $user->name;
        $musyrif->save();

        $this->writeRepairLog(
            $actor,
            'musyrif_name_mismatch',
            'musyrif',
            $musyrif->id,
            'sync_musyrif_name',
            'success',
            'Nama profil disamakan dengan nama akun.',
            $before,
            $musyrif->fresh()->toArray()
        );

        return ['status' => 'success', 'message' => 'Nama profil Musyrif berhasil disinkronkan.'];
    }

    /** @return array<string,mixed> */
    private function repairSantriName(User $actor, int $santriId): array
    {
        $santri = Santri::query()->lockForUpdate()->findOrFail($santriId);
        $user = User::query()->withTrashed()->find($santri->user_id);

        if (!$user) {
            throw ValidationException::withMessages([
                'user' => ['Akun Santri tidak ditemukan.'],
            ]);
        }

        $before = $santri->toArray();
        $santri->nama = $user->name;
        $santri->save();

        $this->writeRepairLog(
            $actor,
            'santri_name_mismatch',
            'santri',
            $santri->id,
            'sync_santri_name',
            'success',
            'Nama profil disamakan dengan nama akun.',
            $before,
            $santri->fresh()->toArray()
        );

        return ['status' => 'success', 'message' => 'Nama profil Santri berhasil disinkronkan.'];
    }

    /** @return array<string,mixed> */
    private function repairMissingPlacement(User $actor, int $santriId): array
    {
        $santri = Santri::query()->lockForUpdate()->findOrFail($santriId);
        $before = $santri->toArray();
        $created = $this->createPlacementFromProfile($santri, true);

        if (!$created) {
            throw ValidationException::withMessages([
                'placement' => ['Placement sudah tersedia atau data kelas/semester belum valid.'],
            ]);
        }

        $activeSemester = $this->resolveSingleActiveSemester();
        $placement = DB::table('santri_semester_placements')
            ->where('semester_id', $activeSemester['id'])
            ->where('santri_id', $santri->id)
            ->first();

        $this->writeRepairLog(
            $actor,
            'missing_active_placement',
            'santri',
            $santri->id,
            'create_active_placement',
            'success',
            'Placement dibuat dari profil santri terkini.',
            $before,
            ['santri' => $santri->fresh()->toArray(), 'placement' => (array) $placement]
        );

        return ['status' => 'success', 'message' => 'Placement semester aktif berhasil dibuat.'];
    }

    /** @return array<string,mixed> */
    private function repairActiveApproval(User $actor, int $userId): array
    {
        $user = User::query()->lockForUpdate()->findOrFail($userId);

        if ($user->account_status !== 'active') {
            throw ValidationException::withMessages([
                'user' => ['Akun tidak lagi berstatus active.'],
            ]);
        }

        $before = $this->userSnapshot($user);
        $user->forceFill([
            'is_approved' => true,
            'approved_at' => $user->approved_at ?? now(),
            'approved_by' => $user->approved_by ?? $actor->id,
        ])->save();

        $this->writeRepairLog(
            $actor,
            'active_account_not_approved',
            'user',
            $user->id,
            'sync_active_approval',
            'success',
            'is_approved diselaraskan dengan account_status active.',
            $before,
            $this->userSnapshot($user->fresh())
        );

        return ['status' => 'success', 'message' => 'Status approval akun berhasil disinkronkan.'];
    }

    /** @return array<string,mixed> */
    private function repairEmptyAccountStatus(User $actor, int $userId): array
    {
        $user = User::query()->withTrashed()->lockForUpdate()->findOrFail($userId);

        if (!empty($user->account_status)) {
            throw ValidationException::withMessages([
                'user' => ['Status lifecycle akun sudah terisi.'],
            ]);
        }

        $before = $this->userSnapshot($user);
        $target = $user->trashed()
            ? 'archived'
            : ((bool) $user->is_approved ? 'active' : 'pending');

        $user->forceFill(['account_status' => $target])->save();

        $this->writeRepairLog(
            $actor,
            'account_status_empty',
            'user',
            $user->id,
            'map_account_status',
            'success',
            'Status lifecycle dipetakan dari approval dan soft delete.',
            $before,
            $this->userSnapshot($user->fresh())
        );

        return ['status' => 'success', 'message' => 'Lifecycle akun dipetakan menjadi ' . $target . '.'];
    }

    /** @return array<string,mixed> */
    private function repairArchivedSoftDelete(User $actor, int $userId): array
    {
        if ((int) $actor->id === $userId) {
            throw ValidationException::withMessages([
                'user' => ['Akun sendiri tidak dapat diarsipkan melalui consistency checker.'],
            ]);
        }

        $user = User::query()->lockForUpdate()->findOrFail($userId);

        if ($user->account_status !== 'archived') {
            throw ValidationException::withMessages([
                'user' => ['Akun tidak lagi berstatus archived.'],
            ]);
        }

        $before = $this->userSnapshot($user);
        $user->delete();

        $this->writeRepairLog(
            $actor,
            'archived_status_not_deleted',
            'user',
            $user->id,
            'complete_soft_delete',
            'success',
            'Soft delete diselesaikan sesuai account_status archived.',
            $before,
            $this->userSnapshot($user)
        );

        return ['status' => 'success', 'message' => 'Soft archive akun berhasil diselesaikan.'];
    }

    /** @return array<string,mixed> */
    private function repairDeletedStatus(User $actor, int $userId): array
    {
        $user = User::query()->withTrashed()->lockForUpdate()->findOrFail($userId);

        if (!$user->trashed()) {
            throw ValidationException::withMessages([
                'user' => ['Akun tidak lagi dalam kondisi soft delete.'],
            ]);
        }

        $before = $this->userSnapshot($user);
        $user->forceFill([
            'account_status' => 'archived',
            'archived_at' => $user->archived_at ?? $user->deleted_at ?? now(),
            'archived_by' => $user->archived_by ?? $actor->id,
            'archive_reason' => $user->archive_reason
                ?: 'Status disinkronkan oleh consistency checker.',
        ])->save();

        $this->writeRepairLog(
            $actor,
            'deleted_user_not_archived',
            'user',
            $user->id,
            'sync_deleted_status',
            'success',
            'account_status diselaraskan dengan kondisi soft delete.',
            $before,
            $this->userSnapshot($user->fresh())
        );

        return ['status' => 'success', 'message' => 'Status akun soft-deleted berhasil disinkronkan.'];
    }

    private function createPlacementFromProfile(Santri $santri, bool $failWhenExists = false): bool
    {
        if (!Schema::hasTable('santri_semester_placements')
            || !Schema::hasColumn('santris', 'kelas_id')
            || !$santri->kelas_id
            || !Schema::hasTable('kelas')
            || !DB::table('kelas')->where('id', $santri->kelas_id)->exists()
        ) {
            return false;
        }

        $semester = $this->resolveSingleActiveSemester();
        $exists = DB::table('santri_semester_placements')
            ->where('semester_id', $semester['id'])
            ->where('santri_id', $santri->id)
            ->exists();

        if ($exists) {
            return false;
        }

        $attributes = [
            'semester_id' => $semester['id'],
            'santri_id' => $santri->id,
            'kelas_id' => $santri->kelas_id,
        ];

        if (Schema::hasColumn('santri_semester_placements', 'musyrif_id')) {
            $profileMusyrifId = Schema::hasColumn('santris', 'musyrif_id')
                ? $santri->musyrif_id
                : null;

            $attributes['musyrif_id'] = $profileMusyrifId
                && Schema::hasTable('musyrifs')
                && DB::table('musyrifs')->where('id', $profileMusyrifId)->exists()
                    ? $profileMusyrifId
                    : null;
        }

        if (Schema::hasColumn('santri_semester_placements', 'created_at')) {
            $attributes['created_at'] = now();
        }

        if (Schema::hasColumn('santri_semester_placements', 'updated_at')) {
            $attributes['updated_at'] = now();
        }

        DB::table('santri_semester_placements')->insert($attributes);

        return true;
    }

    /** @return array{id:int,label:string} */
    private function resolveSingleActiveSemester(): array
    {
        if (!Schema::hasTable('semesters')) {
            throw ValidationException::withMessages([
                'semester' => ['Tabel semester tidak tersedia.'],
            ]);
        }

        $query = DB::table('semesters');

        if (Schema::hasColumn('semesters', 'status')) {
            $query->where('status', 'active');
        } elseif (Schema::hasColumn('semesters', 'is_active')) {
            $query->where('is_active', true);
        } else {
            throw ValidationException::withMessages([
                'semester' => ['Penanda semester aktif tidak tersedia.'],
            ]);
        }

        $semesters = $query->get();

        if ($semesters->count() !== 1) {
            throw ValidationException::withMessages([
                'semester' => [
                    $semesters->isEmpty()
                        ? 'Tidak ada semester aktif.'
                        : 'Terdapat lebih dari satu semester aktif.',
                ],
            ]);
        }

        $semester = $semesters->first();

        return [
            'id' => (int) $semester->id,
            'label' => $semester->nama ?? 'Semester #' . $semester->id,
        ];
    }

    private function applyActiveUserFilter(Builder $query, string $alias): void
    {
        if (Schema::hasColumn('users', 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }

        if (Schema::hasColumn('users', 'account_status')) {
            $query->where($alias . '.account_status', '!=', 'archived');
        }
    }

    private function applyActiveSantriFilter(Builder $query, string $alias): void
    {
        if (Schema::hasColumn('santris', 'deleted_at')) {
            $query->whereNull($alias . '.deleted_at');
        }

        if (Schema::hasColumn('santris', 'is_active')) {
            $query->where($alias . '.is_active', true);
            return;
        }

        if (Schema::hasColumn('santris', 'status')) {
            $query->whereNotIn($alias . '.status', [
                'alumni',
                'inactive',
                'nonaktif',
                'archived',
            ]);
        }
    }

    /**
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    private function issue(
        string $type,
        string $severity,
        string $category,
        string $title,
        string $description,
        string $entityType,
        int $entityId,
        string $entityLabel,
        bool $repairable,
        bool $safeAutoRepair = false,
        bool $requiresInput = false,
        ?string $repairLabel = null,
        array $context = []
    ): array {
        return [
            'key' => $type . ':' . $entityType . ':' . $entityId,
            'type' => $type,
            'severity' => $severity,
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_label' => $entityLabel,
            'repairable' => $repairable,
            'safe_auto_repair' => $safeAutoRepair,
            'requires_input' => $requiresInput,
            'repair_label' => $repairLabel,
            'context' => $context,
        ];
    }

    private function generateKode(string $nama, int $id): string
    {
        $parts = preg_split('/\s+/', trim($nama)) ?: [];
        $initials = '';

        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        $initials = substr($initials, 0, 3) ?: 'MSY';

        return $initials . '-' . str_pad((string) $id, 2, '0', STR_PAD_LEFT);
    }

    /** @return array<string,mixed> */
    private function userSnapshot(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'role' => (string) $user->role,
            'account_status' => $user->account_status,
            'is_approved' => (bool) $user->is_approved,
            'deleted_at' => optional($user->deleted_at)->toIso8601String(),
        ];
    }

    private function entityTypeForIssue(string $issueType): string
    {
        return match ($issueType) {
            'musyrif_missing_kode', 'musyrif_name_mismatch' => 'musyrif',
            'santri_name_mismatch', 'missing_active_placement' => 'santri',
            default => 'user',
        };
    }

    /**
     * @param array<string,mixed>|null $before
     * @param array<string,mixed>|null $after
     * @param array<string,mixed> $metadata
     */
    private function writeRepairLog(
        User $actor,
        string $issueType,
        string $entityType,
        ?int $entityId,
        string $action,
        string $status,
        ?string $reason,
        ?array $before,
        ?array $after,
        array $metadata = []
    ): void {
        if (!Schema::hasTable('system_integrity_repair_logs')) {
            return;
        }

        SystemIntegrityRepairLog::query()->create([
            'actor_id' => $actor->id,
            'issue_type' => $issueType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'status' => $status,
            'reason' => $reason,
            'before_data' => $before,
            'after_data' => $after,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
