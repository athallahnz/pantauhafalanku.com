<?php

namespace App\Http\Controllers\Musyrif;

use App\Http\Controllers\Controller;
use App\Models\Musyrif;
use App\Models\MusyrifAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class MusyrifAttendanceController extends Controller
{

    // Titik pusat lokasi setoran (Masjid Darut Taqwa putra)
    private float $geoLat = -7.8186683;
    private float $geoLng = 111.5244092;
    private int $geoRadiusM = 150; // sesuaikan kebijakan

    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }

    public function index(Request $request)
    {
        $musyrif = $this->resolveMusyrif($request);

        // Status hari ini (untuk tombol UI)
        $today = now()->toDateString();
        $morning = MusyrifAttendance::where('musyrif_id', $musyrif->id)
            ->whereDate('attendance_at', $today)->where('type', 'morning')->latest()->first();

        $afternoon = MusyrifAttendance::where('musyrif_id', $musyrif->id)
            ->whereDate('attendance_at', $today)->where('type', 'afternoon')->latest()->first();

        return view('musyrif.absensi.index', compact('musyrif', 'morning', 'afternoon'));
    }

    public function store(Request $request)
    {
        $musyrif = $this->resolveMusyrif($request);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['morning', 'afternoon'])],
            'photo' => ['required', 'string'], // dataURL base64 dari canvas
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = 'valid';
        $notesExtra = null;

        $lat = $validated['latitude'] ?? null;
        $lng = $validated['longitude'] ?? null;

        // Jika GPS tidak ada → tandai suspect (atau tolak sesuai kebijakan)
        if ($lat === null || $lng === null) {
            $status = 'suspect';
            $notesExtra = 'GPS tidak tersedia.';
        } else {
            $dist = $this->haversineMeters(
                (float) $lat,
                (float) $lng,
                $this->geoLat,
                $this->geoLng
            );

            if ($dist > $this->geoRadiusM) {
                // Pilih salah satu kebijakan:
                $status = 'rejected'; // atau 'suspect' jika masih mau diterima tapi ditandai
                $notesExtra = 'Di luar radius geofence (' . round($dist) . ' m).';
            }
        }

        // Opsional: aturan anti double dalam hari yang sama
        $today = now()->toDateString();
        $already = MusyrifAttendance::where('musyrif_id', $musyrif->id)
            ->whereDate('attendance_at', $today)
            ->where('type', $validated['type'])
            ->exists();

        if ($already) {
            return back()->withErrors([
                'type' => 'Anda sudah melakukan absensi ' . ($validated['type'] === 'in' ? 'Masuk' : 'Pulang') . ' hari ini.'
            ])->withInput();
        }

        // Simpan selfie dari base64
        $photoPath = $this->storeBase64Photo($validated['photo'], $musyrif->id);

        DB::transaction(function () use ($musyrif, $validated, $photoPath, $request, $status, $notesExtra, $lat, $lng) {
            MusyrifAttendance::create([
                'musyrif_id' => $musyrif->id,
                'type' => $validated['type'],
                'attendance_at' => now(),
                'photo_path' => $photoPath,

                'latitude' => $lat,
                'longitude' => $lng,
                'accuracy' => isset($validated['accuracy']) ? (int) round($validated['accuracy']) : null,
                'address_text' => $validated['address_text'] ?? null,

                'ip_address' => $request->ip(),
                'device_info' => (string) $request->userAgent(),

                'status' => $status,
                'notes' => trim(($validated['notes'] ?? '') . ($notesExtra ? "\n" . $notesExtra : '')) ?: null,
            ]);
        });

        return redirect()->route('musyrif.absensi.index')->with('success', 'Absensi berhasil disimpan.');
    }

    public function history(Request $request)
    {
        $musyrif = $this->resolveMusyrif($request);

        // ========================
        // FILTER BULAN
        // ========================

        $month = $request->input('month', now('Asia/Jakarta')->format('Y-m'));

        $start = Carbon::createFromFormat('Y-m', $month)
            ->startOfMonth()
            ->startOfDay();

        $end = Carbon::createFromFormat('Y-m', $month)
            ->endOfMonth()
            ->endOfDay();


        // ========================
        // DATA TABLE (PAGINATION)
        // ========================

        $data = MusyrifAttendance::query()
            ->where('musyrif_id', $musyrif->id)
            ->whereBetween('attendance_at', [$start, $end])
            ->latest('attendance_at')
            ->paginate(20);


        // ========================
        // DATA CALENDAR
        // ========================

        $rows = MusyrifAttendance::query()
            ->where('musyrif_id', $musyrif->id)
            ->whereBetween('attendance_at', [$start, $end])
            ->orderByDesc('attendance_at')
            ->get([
                'type',
                'status',
                'attendance_at'
            ]);


        /*
        Struktur hasil:

        $calendar = [
            '2026-02-01' => [
                'morning' => 'valid',
                'afternoon' => 'suspect'
            ]
        ];
    */

        $calendar = [];

        foreach ($rows as $row) {

            $day = $row->attendance_at->format('Y-m-d');

            $type = $row->type; // morning / afternoon

            // ambil record terbaru saja
            if (!isset($calendar[$day][$type])) {

                $calendar[$day][$type] = $row->status;
            }
        }


        // ========================
        // RETURN VIEW
        // ========================

        return view('musyrif.absensi.history', [

            'musyrif' => $musyrif,

            'data' => $data,

            'calendar' => $calendar,

            'month' => $month,

            'start' => $start,

            'end' => $end,

            'today' => now('Asia/Jakarta')->format('Y-m-d') // tambahkan ini

        ]);
    }


    private function resolveMusyrif(Request $request): Musyrif
    {
        // Skema A (umum): musyrifs punya kolom user_id
        $user = $request->user();

        $musyrif = Musyrif::where('user_id', $user->id)->first();

        // Skema B: kalau tidak pakai user_id, Anda bisa fallback via session/guard khusus
        if (!$musyrif) {
            abort(403, 'Akun ini belum terhubung dengan data Musyrif.');
        }

        return $musyrif;
    }

    private function storeBase64Photo(string $dataUrl, int $musyrifId): string
    {
        // Expect: data:image/jpeg;base64,....
        if (!str_starts_with($dataUrl, 'data:image/')) {
            abort(422, 'Format foto tidak valid.');
        }

        [$meta, $content] = explode(',', $dataUrl, 2);
        $isJpeg = str_contains($meta, 'image/jpeg') || str_contains($meta, 'image/jpg');
        $isPng = str_contains($meta, 'image/png');

        if (!$isJpeg && !$isPng) {
            abort(422, 'Foto harus JPEG atau PNG.');
        }

        $binary = base64_decode($content, true);
        if ($binary === false) {
            abort(422, 'Gagal decode foto.');
        }

        // Batas ukuran (misal 2MB)
        if (strlen($binary) > 2 * 1024 * 1024) {
            abort(422, 'Ukuran foto terlalu besar (maks 2MB).');
        }

        $ext = $isPng ? 'png' : 'jpg';
        $filename = 'musyrif_' . $musyrifId . '_' . now()->format('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        $path = 'attendances/musyrif/' . $filename;

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
