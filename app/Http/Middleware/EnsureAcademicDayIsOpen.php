<?php

namespace App\Http\Middleware;

use App\Services\Academic\AcademicCalendarService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAcademicDayIsOpen
{
    public function __construct(
        private readonly AcademicCalendarService $calendarService
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $mode = 'academic'
    ): Response {
        if ($mode === 'attendance') {
            $this->calendarService->assertAttendanceOpen();
        } else {
            $this->calendarService->assertAcademicInputOpen();
        }

        return $next($request);
    }
}
