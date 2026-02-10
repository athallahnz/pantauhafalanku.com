<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Santri;
use App\Models\Kelas;

class DashboardController extends Controller
{
    public function index()
    {
        // ================= KPI RINGKASAN =================
        $totalUser       = User::count();
        $totalDepartemen = User::where('role', 'admin')->count();
        $totalSantri     = Santri::count();
        $totalKelas      = class_exists(\App\Models\Kelas::class)
            ? Kelas::count()
            : 0;

        // ================= STATISTIK USER PER ROLE =================
        $roleCounts = User::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('superadmin.dashboard', compact(
            'totalUser',
            'totalDepartemen',
            'totalSantri',
            'totalKelas',
            'roleCounts'
        ));
    }
}
