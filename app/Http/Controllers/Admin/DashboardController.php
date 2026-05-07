<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Hafalan;
use App\Models\MusyrifAttendance;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Definisikan periode (default: bulan ini)
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfMonth();

        // 1. STATISTIK UTAMA (Dalam Range)
        $jumlahKelas = Kelas::count();
        $jumlahMusyrif = Musyrif::count();

        $setoranBulanIni = Hafalan::whereBetween('tanggal_setoran', [$startDate, $endDate])
            ->whereIn('status', ['lulus', 'ulang'])
            ->count();

        $absensiMusyrifHariIni = MusyrifAttendance::whereDate('attendance_at', now()->format('Y-m-d'))
            ->distinct('musyrif_id')
            ->count();

        // 2. DATA CHART 1: Rata-rata Hafalan
        $chartData = Kelas::with(['santris.hafalans' => function ($q) use ($startDate, $endDate) {
            $q->whereBetween('tanggal_setoran', [$startDate, $endDate])
                ->whereIn('status', ['lulus', 'ulang']);
        }])->get()->map(function ($kelas) use ($startDate, $endDate) {
            $totalSetoran = $kelas->santris->sum(fn($s) => $s->hafalans->count());
            $jumlahSantri = $kelas->santris->count();
            return [
                'nama_kelas' => $kelas->nama_kelas,
                'rata_rata'  => $jumlahSantri > 0 ? round($totalSetoran / $jumlahSantri, 1) : 0
            ];
        });

        // 3. DATA CHART 2: Tahsin & Tilawah
        $chartTahsinTilawahData = Kelas::with([
            'santris.tahsins' => fn($q) => $q->whereBetween('tanggal', [$startDate, $endDate]),
            'santris.tilawahs' => fn($q) => $q->whereBetween('tanggal', [$startDate, $endDate])
        ])->get()->map(function ($kelas) {
            $jumlahSantri = $kelas->santris->count();
            return [
                'nama_kelas' => $kelas->nama_kelas,
                'rata_tahsin'  => $jumlahSantri > 0 ? round($kelas->santris->sum(fn($s) => $s->tahsins->count()) / $jumlahSantri, 1) : 0,
                'rata_tilawah' => $jumlahSantri > 0 ? round($kelas->santris->sum(fn($s) => $s->tilawahs->count()) / $jumlahSantri, 1) : 0
            ];
        });

        return view('admin.dashboard', compact(
            'jumlahKelas',
            'jumlahMusyrif',
            'setoranBulanIni',
            'absensiMusyrifHariIni',
            'chartData',
            'chartTahsinTilawahData',
            'startDate',
            'endDate'
        ));
    }
}
