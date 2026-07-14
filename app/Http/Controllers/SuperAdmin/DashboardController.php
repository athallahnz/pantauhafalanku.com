<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use App\Services\UserProfileConsistencyService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserProfileConsistencyService $consistency
    ) {
    }

    public function index()
    {
        $totalUser = User::query()->count();
        $totalPending = User::query()
            ->where('account_status', 'pending')
            ->count();
        $totalDepartemen = User::query()
            ->where('role', 'admin')
            ->where('account_status', 'active')
            ->count();
        $totalSantri = Santri::query()->count();
        $totalKelas = class_exists(Kelas::class)
            ? Kelas::query()->count()
            : 0;

        $roleCounts = User::query()
            ->where('account_status', 'active')
            ->selectRaw('role, COUNT(*) AS total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $accountStatusCounts = User::query()
            ->withTrashed()
            ->selectRaw('account_status, COUNT(*) AS total')
            ->groupBy('account_status')
            ->pluck('total', 'account_status');

        /* Cached singkat agar scan integritas tidak membebani dashboard. */
        $integritySummary = $this->consistency->summary();

        return view('superadmin.dashboard', compact(
            'totalUser',
            'totalPending',
            'totalDepartemen',
            'totalSantri',
            'totalKelas',
            'roleCounts',
            'accountStatusCounts',
            'integritySummary'
        ));
    }
}
