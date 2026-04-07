<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ActivityLogsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
     * Mengambil data menggunakan query (lebih hemat memori dibanding get() semua).
     */
    public function query()
    {
        return ActivityLog::query()->with('causer')->latest();
    }

    /**
     * Header Kolom di Excel
     */
    public function headings(): array
    {
        return [
            'ID Log',
            'Tanggal & Waktu',
            'Aktor (User)',
            'Aktivitas',
            'Modul (Target)',
            'Target ID',
            'IP Address',
            'User Agent',
        ];
    }

    /**
     * Mapping data per baris sebelum dimasukkan ke Excel
     */
    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->causer ? $log->causer->name : 'Sistem / Guest',
            $log->description,
            class_basename($log->subject_type),
            $log->subject_id,
            $log->ip_address,
            $log->user_agent,
        ];
    }
}
