<?php

namespace App\Services;

use App\Models\Semester;
use Illuminate\Support\Collection;

class PimpinanAcademicReportService
{
    public function summary(Semester $semester, array $filters): array
    {
        $daily = app(DailyAcademicReportService::class);
        $tahsin = $daily->report('tahsin', $semester, $filters, false);
        $tilawah = $daily->report('tilawah', $semester, $filters, false);
        $monitoring = app(AcademicMonitoringService::class);
        $exams = $monitoring->report('exams', $semester, $filters);
        $mandiri = $monitoring->report('tilawah', $semester, $filters);
        $stats = static fn (Collection $rows): array => [
            'population' => $rows->count(),
            'active' => $rows->where('total', '>', 0)->count(),
            'inactive' => $rows->where('total', 0)->count(),
            'records' => (int) $rows->sum('total'),
        ];

        return ['tahsin' => $stats($tahsin), 'tilawah' => $stats($tilawah),
            'exams' => $stats($exams), 'mandiri' => $stats($mandiri)];
    }
}
