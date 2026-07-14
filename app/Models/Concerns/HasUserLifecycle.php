<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\UserLifecycleLog;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Menambahkan lifecycle akun pada model User.
 *
 * @mixin User
 *
 * @property string|null $account_status
 * @property bool $is_approved
 * @property Carbon|null $approved_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $rejected_at
 * @property Carbon|null $archived_at
 * @property Carbon|null $deleted_at
 */
trait HasUserLifecycle
{
    use SoftDeletes;

    /**
     * Cast tambahan yang otomatis digabungkan saat trait diinisialisasi.
     */
    public function initializeHasUserLifecycle(): void
    {
        /** @var User $this */
        $this->mergeCasts([
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
            'rejected_at' => 'datetime',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ]);
    }

    /**
     * Riwayat perubahan lifecycle akun.
     */
    public function lifecycleLogs(): HasMany
    {
        /** @var User $this */
        return $this->hasMany(UserLifecycleLog::class, 'user_id')
            ->latest('id');
    }

    public function isAccountActive(): bool
    {
        return $this->account_status === 'active'
            && $this->is_approved === true
            && !$this->trashed();
    }

    public function isAccountPending(): bool
    {
        return $this->account_status === 'pending';
    }

    public function isAccountSuspended(): bool
    {
        return $this->account_status === 'suspended';
    }

    public function isAccountRejected(): bool
    {
        return $this->account_status === 'rejected';
    }

    public function isAccountArchived(): bool
    {
        return $this->account_status === 'archived'
            || $this->trashed();
    }
}
