<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Models\Hafalan;
use App\Models\HafalanTemplate;
use App\Models\Musyrif;
use App\Models\Santri;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $musyrif = $user->musyrif ?? Musyrif::where('user_id', $user->id)->first();

        if (!$musyrif) {
            abort(403, 'Profil Musyrif tidak ditemukan. Hubungi admin.');
        }

        $today = Carbon::today();

        // =================== KARTU RINGKASAN ===================
        $jumlahSantri = Santri::where('musyrif_id', $musyrif->id)->count();

        // Hari ini: setor vs tidak setor
        $setoranHariIni = Hafalan::where('musyrif_id', $musyrif->id)
            ->whereDate('tanggal_setoran', $today)
            ->whereIn('status', ['lulus', 'ulang'])
            ->count();

        $hadirTidakSetorHariIni = Hafalan::where('musyrif_id', $musyrif->id)
            ->whereDate('tanggal_setoran', $today)
            ->where('status', 'hadir_tidak_setor')
            ->count();

        $alphaHariIni = Hafalan::where('musyrif_id', $musyrif->id)
            ->whereDate('tanggal_setoran', $today)
            ->where('status', 'alpha')
            ->count();

        $totalSetoran = Hafalan::where('musyrif_id', $musyrif->id)
            ->whereIn('status', ['lulus', 'ulang'])
            ->count();

        // rata-rata nilai label -> numeric
        $nilaiMap = ['mumtaz' => 95, 'jayyid_jiddan' => 85, 'jayyid' => 75];

        $avgNilaiRow = Hafalan::where('musyrif_id', $musyrif->id)
            ->whereIn('status', ['lulus', 'ulang'])
            ->selectRaw("
                AVG(
                    CASE nilai_label
                        WHEN 'mumtaz' THEN 95
                        WHEN 'jayyid_jiddan' THEN 85
                        WHEN 'jayyid' THEN 75
                        ELSE NULL
                    END
                ) as avg_nilai
            ")
            ->first();

        $rataNilai = $avgNilaiRow?->avg_nilai ? round($avgNilaiRow->avg_nilai, 1) : 0;

        // total juz unik dari template (skema baru)
        $totalJuzUnik = Hafalan::where('musyrif_id', $musyrif->id)
            ->whereIn('status', ['lulus', 'ulang'])
            ->whereNotNull('hafalan_template_id')
            ->join('hafalan_templates', 'hafalan_templates.id', '=', 'hafalans.hafalan_template_id')
            ->distinct('hafalan_templates.juz')
            ->count('hafalan_templates.juz');

        // =================== AGENDA HARI INI ===================
        $agendaHarian = Hafalan::with(['santri.kelas', 'template'])
            ->where('musyrif_id', $musyrif->id)
            ->whereDate('tanggal_setoran', $today)
            ->orderBy('created_at')
            ->get();

        // =================== CHART 1: SETORAN PER SANTRI (TOP 7) ===================
        $perSantri = Hafalan::with('santri')
            ->selectRaw('santri_id, COUNT(*) as total')
            ->where('musyrif_id', $musyrif->id)
            ->whereIn('status', ['lulus', 'ulang'])
            ->groupBy('santri_id')
            ->orderByDesc('total')
            ->take(7)
            ->get();

        $chartSetoranPerSantri = [
            'labels' => $perSantri->map(fn($row) => optional($row->santri)->nama ?: 'Tanpa Nama')->values(),
            'data' => $perSantri->map(fn($row) => (int) $row->total)->values(),
        ];

        // =================== CHART 2: DISTRIBUSI STATUS ===================
        $statusRows = Hafalan::where('musyrif_id', $musyrif->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $allStatus = ['lulus', 'ulang', 'hadir_tidak_setor', 'alpha'];
        $statusLabels = [
            'lulus' => 'Lulus',
            'ulang' => 'Ulang',
            'hadir_tidak_setor' => 'Hadir Tidak Setor',
            'alpha' => 'Alpha',
        ];

        $chartStatus = [
            'labels' => array_map(fn($s) => $statusLabels[$s] ?? $s, $allStatus),
            'data' => array_map(fn($s) => (int) ($statusRows[$s] ?? 0), $allStatus),
        ];

        // =================== CHART 3: DISTRIBUSI PER JUZ (berdasarkan template) ===================
        $juzRows = Hafalan::where('hafalans.musyrif_id', $musyrif->id)
            ->whereIn('hafalans.status', ['lulus', 'ulang'])
            ->whereNotNull('hafalans.hafalan_template_id')
            ->join('hafalan_templates', 'hafalan_templates.id', '=', 'hafalans.hafalan_template_id')
            ->selectRaw('hafalan_templates.juz as juz, COUNT(*) as total')
            ->groupBy('hafalan_templates.juz')
            ->orderBy('hafalan_templates.juz')
            ->get();

        $chartJuz = [
            'labels' => $juzRows->map(fn($row) => 'Juz ' . $row->juz)->values(),
            'data' => $juzRows->map(fn($row) => (int) $row->total)->values(),
        ];

        // =================== (OPSIONAL) CHART 4: Rata Nilai per Santri (Top 7) ===================
        $nilaiPerSantri = Hafalan::with('santri')
            ->selectRaw("
                santri_id,
                AVG(
                    CASE nilai_label
                        WHEN 'mumtaz' THEN 95
                        WHEN 'jayyid_jiddan' THEN 85
                        WHEN 'jayyid' THEN 75
                        ELSE NULL
                    END
                ) as avg_nilai
            ")
            ->where('musyrif_id', $musyrif->id)
            ->whereIn('status', ['lulus', 'ulang'])
            ->groupBy('santri_id')
            ->orderByDesc('avg_nilai')
            ->take(7)
            ->get();

        $chartNilaiPerSantri = [
            'labels' => $nilaiPerSantri->map(fn($r) => optional($r->santri)->nama ?: 'Tanpa Nama')->values(),
            'data' => $nilaiPerSantri->map(fn($r) => $r->avg_nilai ? round($r->avg_nilai, 1) : 0)->values(),
        ];

        return view('musyrif.dashboard', compact(
            'jumlahSantri',
            'setoranHariIni',
            'hadirTidakSetorHariIni',
            'alphaHariIni',
            'totalSetoran',
            'rataNilai',
            'totalJuzUnik',
            'agendaHarian',
            'chartSetoranPerSantri',
            'chartStatus',
            'chartJuz',
            'chartNilaiPerSantri'
        ));
    }
}
