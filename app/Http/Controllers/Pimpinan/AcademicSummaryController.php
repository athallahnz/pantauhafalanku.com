<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Services\PimpinanAcademicReportService;
use Illuminate\Http\Request;

class AcademicSummaryController extends Controller
{
    public function __invoke(Request $request, PimpinanAcademicReportService $reports)
    {
        abort_unless(strtolower((string) $request->user()?->role) === 'pimpinan', 403);
        $filters = $request->validate([
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $semester = Semester::findOrFail($filters['semester_id']);
        $request->validate([
            'date_from' => ['after_or_equal:'.$semester->tanggal_mulai->toDateString(), 'before_or_equal:'.$semester->tanggal_selesai->toDateString()],
            'date_to' => ['after_or_equal:'.$semester->tanggal_mulai->toDateString(), 'before_or_equal:'.$semester->tanggal_selesai->toDateString()],
        ]);

        return response()->json(['data' => $reports->summary($semester, $filters)])
            ->header('Cache-Control', 'private, no-store');
    }
}
