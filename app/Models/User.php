<?php

namespace App\Models;

use App\Models\Concerns\HasUserLifecycle;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use LogsActivity;
    use HasUserLifecycle;

    /**
     * Kolom yang boleh diisi melalui mass assignment.
     *
     * Kolom lifecycle seperti account_status, suspended_at,
     * approved_by, dan lainnya sengaja tidak dimasukkan.
     * Perubahan lifecycle dilakukan melalui UserLifecycleService
     * menggunakan forceFill().
     */
    protected $fillable = [
        'name',
        'email',
        'nomor',
        'password',
        'role',
        'is_approved',
    ];

    /**
     * Kolom yang disembunyikan saat model menjadi array/JSON.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting atribut.
     *
     * Cast lifecycle juga terdapat pada HasUserLifecycle.
     * Menuliskannya di sini tetap aman dan membuat model
     * lebih mudah dibaca oleh IDE/Intelephense.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_approved' => 'boolean',

        'approved_at' => 'datetime',
        'suspended_at' => 'datetime',
        'rejected_at' => 'datetime',
        'archived_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function profileSetting(): HasOne
    {
        return $this->hasOne(ProfileSetting::class);
    }

    public function santri(): HasOne
    {
        return $this->hasOne(Santri::class, 'user_id');
    }

    /**
     * Alias relasi santri untuk kompatibilitas kode lama.
     *
     * Bila seluruh kode sudah memakai santri(), method ini
     * nantinya dapat dihapus.
     */
    public function santriProfile(): HasOne
    {
        return $this->hasOne(Santri::class, 'user_id');
    }

    public function musyrif(): HasOne
    {
        return $this->hasOne(Musyrif::class, 'user_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMusyrif(): bool
    {
        return $this->role === 'musyrif';
    }

    public function isSantri(): bool
    {
        return $this->role === 'santri';
    }

    public function systemReview(): HasOne
    {
        return $this->hasOne(\App\Models\SystemReview::class);
    }
}
