<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Fitur ajaib Laravel: fungsi yang diawali dengan 'boot' + NamaTrait
     * akan otomatis dijalankan saat Model di-load.
     */
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->recordActivity('created');
        });

        static::updated(function ($model) {
            // Hanya catat log jika benar-benar ada kolom yang berubah
            if ($model->wasChanged()) {
                $model->recordActivity('updated');
            }
        });

        static::deleted(function ($model) {
            $model->recordActivity('deleted');
        });
    }

    /**
     * Fungsi utama untuk merekam log ke database.
     */
    protected function recordActivity(string $action)
    {
        $properties = [];

        // Deteksi perubahan data berdasarkan aksinya
        if ($action === 'created') {
            $properties['attributes'] = $this->getAttributes();
        } elseif ($action === 'updated') {
            // getChanges() berisi data baru, getOriginal() berisi data lama
            $properties['old'] = array_intersect_key($this->getOriginal(), $this->getChanges());
            $properties['new'] = $this->getChanges();
        } elseif ($action === 'deleted') {
            $properties['old'] = $this->getAttributes();
        }

        // Ambil data User yang sedang login (jika ada)
        $causerType = auth()->check() ? get_class(auth()->user()) : null;
        $causerId   = auth()->id();

        // Nama log diambil dari nama model (cth: Musyrif, Kelas)
        $logName = strtolower(class_basename($this));

        ActivityLog::create([
            'log_name'     => "{$logName}_{$action}", // cth: musyrif_updated
            'description'  => "Data {$logName} berhasil di-{$action}",
            'subject_type' => get_class($this),
            'subject_id'   => $this->id,
            'causer_type'  => $causerType,
            'causer_id'    => $causerId,
            'properties'   => $properties,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
