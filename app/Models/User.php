<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'nomor',
        'password',
        'role',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Relasi profil
    public function santri()
    {
        return $this->hasOne(Santri::class);
    }
    
    // app/Models/User.php
    public function santriProfile()
    {
        return $this->hasOne(Santri::class, 'user_id');
    }

    public function musyrif()
    {
        return $this->hasOne(Musyrif::class);
    }

    // Helper sederhana
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
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

    public function isPimpinan(): bool
    {
        return $this->role === 'pimpinan';
    }
}
