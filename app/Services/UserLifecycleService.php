<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLifecycleLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserLifecycleService
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    public function recordCreated(User $actor, User $user): void
    {
        $this->writeLog(
            $actor,
            $user,
            'created',
            null,
            (string) $user->account_status,
            'Akun dibuat oleh Super Admin.',
            null,
            $this->snapshot($user)
        );
    }

    public function recordUpdated(
        User $actor,
        User $user,
        array $before,
        string $reason = 'Identitas atau role akun diperbarui.'
    ): void {
        $this->writeLog(
            $actor,
            $user,
            'updated',
            $before['account_status'] ?? null,
            (string) $user->account_status,
            $reason,
            $before,
            $this->snapshot($user)
        );
    }

    public function approve(User $actor, User $user): User
    {
        return DB::transaction(function () use ($actor, $user): User {
            $locked = User::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if ($locked->trashed()) {
                throw ValidationException::withMessages([
                    'user' => ['Akun yang telah diarsipkan harus dipulihkan terlebih dahulu.'],
                ]);
            }

            $before = $this->snapshot($locked);
            $fromStatus = (string) ($locked->account_status ?: self::STATUS_PENDING);

            $locked->forceFill([
                'account_status' => self::STATUS_ACTIVE,
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'email_verified_at' => $locked->email_verified_at ?? now(),
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ])->save();

            $this->writeLog(
                $actor,
                $locked,
                'approved',
                $fromStatus,
                self::STATUS_ACTIVE,
                'Akun disetujui dan diaktifkan.',
                $before,
                $this->snapshot($locked)
            );

            return $locked->refresh();
        });
    }

    public function suspend(User $actor, User $user, string $reason): User
    {
        $this->ensureNotSelf($actor, $user, 'menangguhkan');
        $this->ensureSuperadminAvailability($user);

        return $this->transition(
            $actor,
            $user,
            [self::STATUS_ACTIVE],
            self::STATUS_SUSPENDED,
            'suspended',
            $reason,
            [
                'suspended_at' => now(),
                'suspended_by' => $actor->id,
                'suspension_reason' => $reason,
            ]
        );
    }

    public function reactivate(User $actor, User $user, string $reason): User
    {
        return $this->transition(
            $actor,
            $user,
            [self::STATUS_SUSPENDED],
            self::STATUS_ACTIVE,
            'reactivated',
            $reason,
            [
                'is_approved' => true,
                'approved_at' => now(),
                'approved_by' => $actor->id,
                'suspended_at' => null,
                'suspended_by' => null,
                'suspension_reason' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]
        );
    }

    public function reject(User $actor, User $user, string $reason): User
    {
        $this->ensureNotSelf($actor, $user, 'menolak');

        return $this->transition(
            $actor,
            $user,
            [self::STATUS_PENDING],
            self::STATUS_REJECTED,
            'rejected',
            $reason,
            [
                'is_approved' => false,
                'rejected_at' => now(),
                'rejected_by' => $actor->id,
                'rejection_reason' => $reason,
            ]
        );
    }

    public function archive(User $actor, User $user, string $reason): User
    {
        $this->ensureNotSelf($actor, $user, 'mengarsipkan');
        $this->ensureSuperadminAvailability($user);

        return DB::transaction(function () use ($actor, $user, $reason): User {
            $locked = User::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if ($locked->trashed()) {
                throw ValidationException::withMessages([
                    'user' => ['Akun tersebut sudah diarsipkan.'],
                ]);
            }

            $before = $this->snapshot($locked);
            $fromStatus = (string) ($locked->account_status ?: self::STATUS_PENDING);

            $locked->forceFill([
                'account_status' => self::STATUS_ARCHIVED,
                'archived_at' => now(),
                'archived_by' => $actor->id,
                'archive_reason' => $reason,
            ])->save();

            $locked->delete();

            $this->writeLog(
                $actor,
                $locked,
                'archived',
                $fromStatus,
                self::STATUS_ARCHIVED,
                $reason,
                $before,
                $this->snapshot($locked)
            );

            return $locked;
        });
    }

    public function restore(User $actor, User $user, string $reason): User
    {
        return DB::transaction(function () use ($actor, $user, $reason): User {
            $locked = User::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if (!$locked->trashed() && $locked->account_status !== self::STATUS_ARCHIVED) {
                throw ValidationException::withMessages([
                    'user' => ['Akun tersebut tidak sedang diarsipkan.'],
                ]);
            }

            $before = $this->snapshot($locked);
            $locked->restore();

            $targetStatus = $locked->approved_at
                ? self::STATUS_ACTIVE
                : self::STATUS_PENDING;

            $locked->forceFill([
                'account_status' => $targetStatus,
                'is_approved' => $targetStatus === self::STATUS_ACTIVE,
                'archived_at' => null,
                'archived_by' => null,
                'archive_reason' => null,
            ])->save();

            $this->writeLog(
                $actor,
                $locked,
                'restored',
                self::STATUS_ARCHIVED,
                $targetStatus,
                $reason,
                $before,
                $this->snapshot($locked)
            );

            return $locked->refresh();
        });
    }

    public function snapshot(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'nomor' => $user->nomor,
            'role' => (string) $user->role,
            'account_status' => (string) ($user->account_status ?? ''),
            'is_approved' => (bool) $user->is_approved,
            'approved_at' => $this->formatDate($user->approved_at),
            'suspended_at' => $this->formatDate($user->suspended_at),
            'rejected_at' => $this->formatDate($user->rejected_at),
            'archived_at' => $this->formatDate($user->archived_at),
            'deleted_at' => $this->formatDate($user->deleted_at),
        ];
    }

    private function transition(
        User $actor,
        User $user,
        array $allowedFrom,
        string $toStatus,
        string $action,
        string $reason,
        array $attributes = []
    ): User {
        return DB::transaction(function () use (
            $actor,
            $user,
            $allowedFrom,
            $toStatus,
            $action,
            $reason,
            $attributes
        ): User {
            $locked = User::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if ($locked->trashed()) {
                throw ValidationException::withMessages([
                    'user' => ['Akun yang telah diarsipkan harus dipulihkan terlebih dahulu.'],
                ]);
            }

            $fromStatus = (string) ($locked->account_status ?: self::STATUS_PENDING);

            if (!in_array($fromStatus, $allowedFrom, true)) {
                throw ValidationException::withMessages([
                    'account_status' => [
                        'Perubahan status dari ' . $fromStatus . ' ke ' . $toStatus . ' tidak diizinkan.',
                    ],
                ]);
            }

            $before = $this->snapshot($locked);

            $locked->forceFill(array_merge(
                ['account_status' => $toStatus],
                $attributes
            ))->save();

            $this->writeLog(
                $actor,
                $locked,
                $action,
                $fromStatus,
                $toStatus,
                $reason,
                $before,
                $this->snapshot($locked)
            );

            return $locked->refresh();
        });
    }

    private function ensureNotSelf(User $actor, User $target, string $verb): void
    {
        if ((int) $actor->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'user' => [
                    'Anda tidak dapat ' . $verb . ' akun yang sedang digunakan.',
                ],
            ]);
        }
    }

    private function ensureSuperadminAvailability(User $target): void
    {
        if ($target->role !== 'superadmin') {
            return;
        }

        $activeSuperadmins = User::query()
            ->where('role', 'superadmin')
            ->where('account_status', self::STATUS_ACTIVE)
            ->where('is_approved', true)
            ->count();

        if ($activeSuperadmins <= 1) {
            throw ValidationException::withMessages([
                'user' => [
                    'Tindakan dibatalkan karena sistem harus menyisakan minimal satu Super Admin aktif.',
                ],
            ]);
        }
    }

    private function formatDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->toISOString();
    }

    private function writeLog(
        User $actor,
        User $target,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $reason,
        ?array $before,
        ?array $after
    ): void {
        UserLifecycleLog::query()->create([
            'user_id' => $target->id,
            'actor_id' => $actor->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'reason' => $reason,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
