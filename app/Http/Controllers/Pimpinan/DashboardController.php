<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Services\QuranExecutiveMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly QuranExecutiveMetricsService $metricsService
    ) {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'range' => ['nullable', 'in:semester,30d,7d,custom'],
            'start_date' => ['nullable', 'date', 'required_if:range,custom'],
            'end_date' => [
                'nullable',
                'date',
                'required_if:range,custom',
                'after_or_equal:start_date',
            ],
        ]);

        $semesterList = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->orderByDesc('tanggal_mulai')
            ->get();

        $dashboard = $this->metricsService->build(
            $request->merge($validated)
        );

        return view('pimpinan.dashboard', compact(
            'dashboard',
            'semesterList'
        ));
    }
}
