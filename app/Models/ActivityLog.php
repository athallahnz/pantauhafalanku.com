<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

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
