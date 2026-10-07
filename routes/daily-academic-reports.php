<?php

use App\Http\Controllers\Reports\DailyAcademicReportController;
use Illuminate\Support\Facades\Route;

foreach (['admin', 'pimpinan'] as $readerRole) {
Route::prefix($readerRole)->name($readerRole.'.daily-reports.')
    ->middleware(['auth', 'account.active', 'approved', 'role:'.$readerRole])
    ->group(function (): void {
        foreach (['tahsin' => 'rekap-tahsin-harian', 'tilawah' => 'rekap-seluruh-tilawah'] as $kind => $path) {
            Route::get($path, [DailyAcademicReportController::class, 'index'])->defaults('report_kind', $kind)->name($kind.'.index');
            Route::get($path.'/data', [DailyAcademicReportController::class, 'data'])->defaults('report_kind', $kind)->name($kind.'.data');
            Route::get($path.'/export', [DailyAcademicReportController::class, 'export'])->defaults('report_kind', $kind)->name($kind.'.export');
            Route::get($path.'/history/{santri}', [DailyAcademicReportController::class, 'history'])->whereNumber('santri')->defaults('report_kind', $kind)->name($kind.'.history');
        }
    });
}

Route::get('pimpinan/academic-summary', \App\Http\Controllers\Pimpinan\AcademicSummaryController::class)
    ->middleware(['auth', 'account.active', 'approved', 'role:pimpinan'])->name('pimpinan.academic-summary');
