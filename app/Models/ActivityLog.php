<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\MassPrunable; // <-- 1. Import ini

class ActivityLog extends Model
{
    use HasFactory, MassPrunable; // <-- 2. Gunakan trait ini

    /**
     * Tentukan kondisi untuk menghapus log yang sudah usang.
     * Misalnya, kita ingin menghapus log yang lebih dari 90 hari.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subMonths(6));
    }

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    /**
     * Pastikan field properties di-*cast* sebagai array
     * agar otomatis menjadi array saat ditarik dari database
     * dan menjadi JSON saat disimpan.
     */
    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Relasi ke data yang dikenai aksi.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke aktor/user yang melakukan aksi.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }
}
