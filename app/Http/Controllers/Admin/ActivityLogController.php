<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Exports\ActivityLogsExport;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $q = ActivityLog::with('causer')->latest();

            return DataTables::of($q)
                ->addColumn('waktu', function ($log) {
                    $d = $log->created_at->format('d M Y');
                    $t = $log->created_at->format('H:i:s');
                    return "<div class='fw-bold'>{$d}</div><div class='text-muted small'>{$t} WIB</div>";
                })
                ->addColumn('aktor', function ($log) {
                    if ($log->causer) {
                        $type = class_basename($log->causer_type);
                        $name = e($log->causer->name);
                        return "<div class='fw-semibold text-adaptive-purple'>{$name}</div><div class='text-muted small'>{$type}</div>";
                    }
                    return "<div class='fw-semibold text-secondary'>Sistem / Guest</div>";
                })
                ->addColumn('aktivitas', function ($log) {
                    $desc = e($log->description);
                    $actionBadge = str_contains($log->log_name, 'created') ? 'bg-success' : (str_contains($log->log_name, 'deleted') ? 'bg-danger' : 'bg-warning text-dark');
                    $actionText = strtoupper(explode('_', $log->log_name)[1] ?? 'ACTION');
                    return "<div class='mb-1'>{$desc}</div><span class='badge {$actionBadge} rounded-pill px-2' style='font-size: 0.65rem;'>{$actionText}</span>";
                })
                ->addColumn('modul', function ($log) {
                    $type = class_basename($log->subject_type);
                    return "<div class='fw-semibold'>{$type}</div><div class='text-muted small'>ID: {$log->subject_id}</div>";
                })
                ->addColumn('ip', function ($log) {
                    $ip = $log->ip_address ?? '-';
                    return "<span class='small font-monospace'>{$ip}</span>";
                })
                ->addColumn('aksi', function ($log) {
                    // Gunakan htmlspecialchars agar format JSON aman dimasukkan ke dalam atribut HTML
                    $properties = htmlspecialchars(json_encode($log->properties), ENT_QUOTES, 'UTF-8');
                    $desc = htmlspecialchars($log->description, ENT_QUOTES, 'UTF-8');

                    return "<button class='btn btn-sm btn-outline-primary rounded-pill btn-detail'
                                data-properties='{$properties}'
                                data-desc='{$desc}'>
                                <i class='bi bi-eye'></i> Cek Data
                            </button>";
                })
                ->rawColumns(['waktu', 'aktor', 'aktivitas', 'modul', 'ip', 'aksi'])
                ->make(true);
        }

        return view('admin.activity_logs.index');
    }

    public function export()
    {
        // File akan otomatis terunduh dengan nama activity_logs_TANGGAL.xlsx
        $fileName = 'activity_logs_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new ActivityLogsExport, $fileName);
    }
}
