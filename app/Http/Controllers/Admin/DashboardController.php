<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Hafalan;
use App\Models\MusyrifAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Menampilkan Dashboard Admin dengan statistik realtime.
     */
    public function index()
    {
        $now = Carbon::now();
        $today = Carbon::today()->toDateString();

        // 1. STATISTIK UTAMA (Stats Cards)
        $jumlahKelas = Kelas::count();
        $jumlahMusyrif = Musyrif::count();

        // PERBAIKAN: Menghitung setoran santri HANYA yang berstatus lulus/ulang
        $setoranBulanIni = Hafalan::whereMonth('tanggal_setoran', $now->month)
            ->whereYear('tanggal_setoran', $now->year)
            ->whereIn('status', ['lulus', 'ulang']) // <-- TAMBAHKAN INI
            ->count();

        // Menghitung kehadiran musyrif hari ini
        $absensiMusyrifHariIni = MusyrifAttendance::whereDate('attendance_at', now()->format('Y-m-d'))
            // ->where('status', 'valid')
            ->distinct('musyrif_id')
            ->count();

        // 2. DATA CHART: Rata-rata Hafalan per Kelas (Bulan Ini)
        // PERBAIKAN: Eager loading hafalans juga harus difilter statusnya
        $chartData = Kelas::with(['santris.hafalans' => function ($q) use ($now) {
            $q->whereMonth('tanggal_setoran', $now->month)
                ->whereYear('tanggal_setoran', $now->year)
                ->whereIn('status', ['lulus', 'ulang']); // <-- TAMBAHKAN INI JUGA
        }])
            ->get()
            ->map(function ($kelas) {
                // Hitung total setoran (karena sudah difilter di atas, tinggal di-count)
                $totalSetoran = $kelas->santris->sum(function ($santri) {
                    return $santri->hafalans->count();
                });

                // Hitung rata-rata per santri
                $jumlahSantri = $kelas->santris->count();
                $rataRata = $jumlahSantri > 0 ? round($totalSetoran / $jumlahSantri, 1) : 0;

                return [
                    'nama_kelas' => $kelas->nama_kelas,
                    'rata_rata'  => $rataRata
                ];
            });

        // 3. RETURN KE VIEW
        return view('admin.dashboard', compact(
            'jumlahKelas',
            'jumlahMusyrif',
            'setoranBulanIni',
            'absensiMusyrifHariIni',
            'chartData'
        ));
    }
}
