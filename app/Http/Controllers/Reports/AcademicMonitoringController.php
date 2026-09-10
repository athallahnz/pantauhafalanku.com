<?php

namespace App\Http\Controllers\Reports;

use App\Exports\AcademicMonitoringExport;
use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Semester;
use App\Services\AcademicMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AcademicMonitoringController extends Controller
{
    public function __construct(private AcademicMonitoringService $reports)
    {
    }

    public function index(Request $request)
    {
        $this->authorizeReader($request);
        $kind = $this->kind($request);
        $semesters = Semester::query()->with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')->orderByDesc('id')->get();
        $selected = $semesters->first(fn ($semester) => $semester->isActive()) ?? $semesters->first();
        $classes = Kelas::query()->orderBy('nama_kelas')->orderBy('kelompok')
            ->get(['id', 'nama_kelas', 'kelompok']);
        $musyrifs = Musyrif::query()->orderBy('nama')->get(['id', 'nama']);
        $prefix = str_starts_with($request->route()->getName(), 'admin.') ? 'admin' : 'pimpinan';
        $routeBase = $prefix . '.monitoring.' . $kind;

        return view('reports.academic-monitoring', compact('kind', 'semesters', 'selected', 'classes', 'musyrifs', 'routeBase'));
    }

    public function data(Request $request)
    {
        [$semester, $filters] = $this->context($request);
        $rows = $this->reports->report($this->kind($request), $semester, $filters);

        return response()->json([
            'data' => $rows->map(fn ($row) => collect($row)->except('history')->all())->all(),
            'statistics' => [
                'total_santri' => $rows->count(),
                'with_activity' => $rows->where('total', '>', 0)->count(),
                'without_activity' => $rows->where('total', 0)->count(),
                'total_records' => (int) $rows->sum('total'),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function history(Request $request, int $santri)
    {
        [$semester, $filters] = $this->context($request);
        $row = $this->reports->report($this->kind($request), $semester, $filters)->firstWhere('id', $santri);
        abort_unless($row, 404);

        return response()->json(['santri' => $row['nama'], 'data' => $row['history']])
            ->header('Cache-Control', 'private, no-store');
    }

    public function export(Request $request)
    {
        [$semester, $filters] = $this->context($request);
        $kind = $this->kind($request);
        $rows = $this->reports->report($kind, $semester, $filters);
        $class = !empty($filters['kelas_id']) ? Kelas::find($filters['kelas_id']) : null;
        $musyrif = !empty($filters['musyrif_id']) ? Musyrif::find($filters['musyrif_id']) : null;
        $parameters = [
            ['Laporan', $kind === 'exams' ? 'Rekap Ujian Tahsin' : 'Rekap Tilawah Mandiri'],
            ['Semester', $semester->nama . ' ' . $semester->tahunAjaran?->nama],
            ['Mulai', $filters['date_from'] ?? $semester->tanggal_mulai->toDateString()],
            ['Sampai', $filters['date_to'] ?? $semester->tanggal_selesai->toDateString()],
            ['Kelas', $class ? trim($class->nama_kelas . ' ' . $class->kelompok) : 'Semua kelas'],
            ['Musyrif pembina', $musyrif?->nama ?? 'Semua Musyrif'],
            ['Jenis / tujuan', $filters['exam_type'] ?? $filters['purpose'] ?? 'Semua'],
            ['Status rekap', $filters['state'] ?? 'Semua'],
            ['Pencarian', $filters['q'] ?? ''],
            ['Dicetak pada', now('Asia/Jakarta')->format('Y-m-d H:i:s') . ' WIB'],
            ['Cakupan', 'Transaksi semester dan rentang tanggal terpilih. Kelas/Musyrif mengikuti penempatan semester; fallback data aktif ditandai.'],
            ['Progres', 'Buku/Juz unik dalam periode, dihitung sebelum filter jenis/tujuan. Hasil mengikuti ujian terakhir pada jenis terpilih.'],
        ];

        return Excel::download(new AcademicMonitoringExport($rows, $kind, $parameters),
            'rekap-' . ($kind === 'exams' ? 'ujian-tahsin' : 'tilawah-mandiri') . '-' . $semester->id . '-' . now()->format('Ymd-His') . '.xlsx');
    }

    private function context(Request $request): array
    {
        $this->authorizeReader($request);
        $filters = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
            'musyrif_id' => ['nullable', 'integer', 'exists:musyrifs,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'exam_type' => ['nullable', Rule::in(['promotion', 'semester'])],
            'purpose' => ['nullable', Rule::in(['continuation', 'review'])],
            'state' => ['nullable', Rule::in($this->kind($request) === 'exams'
                ? ['not_examined', 'passed', 'repeat'] : ['no_activity', 'not_started', 'in_progress', 'completed'])],
            'q' => ['nullable', 'string', 'max:150'],
        ]);
        $semester = Semester::with('tahunAjaran:id,nama')->findOrFail($filters['semester_id']);
        $start = $semester->tanggal_mulai->toDateString();
        $end = $semester->tanggal_selesai->toDateString();
        $request->validate([
            'date_from' => ['nullable', 'after_or_equal:' . $start, 'before_or_equal:' . $end],
            'date_to' => ['nullable', 'after_or_equal:' . ($filters['date_from'] ?? $start), 'before_or_equal:' . $end],
        ], [
            '*.after_or_equal' => 'Tanggal harus berurutan dan berada dalam semester terpilih.',
            '*.before_or_equal' => 'Tanggal harus berada dalam semester terpilih.',
        ]);
        $filters['date_from'] ??= $start;
        $filters['date_to'] ??= $end;
        if ($this->kind($request) === 'exams') {
            unset($filters['purpose']);
        } else {
            unset($filters['exam_type']);
        }

        return [$semester, $filters];
    }

    private function kind(Request $request): string
    {
        return $request->route('report_kind');
    }

    private function authorizeReader(Request $request): void
    {
        abort_unless(in_array(strtolower((string) $request->user()?->role), ['admin', 'pimpinan'], true), 403);
    }
}
