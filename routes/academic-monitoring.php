<?php

use App\Http\Controllers\Reports\AcademicMonitoringController;
use Illuminate\Support\Facades\Route;

foreach (['admin', 'pimpinan'] as $readerRole) {
    Route::prefix($readerRole)->name($readerRole . '.monitoring.')
        ->middleware(['auth', 'account.active', 'approved', 'role:' . $readerRole])
        ->group(function (): void {
            foreach (['exams' => 'rekap-ujian-tahsin', 'tilawah' => 'rekap-tilawah-mandiri'] as $kind => $path) {
                Route::get($path, [AcademicMonitoringController::class, 'index'])
                    ->defaults('report_kind', $kind)->name($kind . '.index');
                Route::get($path . '/data', [AcademicMonitoringController::class, 'data'])
                    ->defaults('report_kind', $kind)->name($kind . '.data');
                Route::get($path . '/export', [AcademicMonitoringController::class, 'export'])
                    ->defaults('report_kind', $kind)->name($kind . '.export');
                Route::get($path . '/history/{santri}', [AcademicMonitoringController::class, 'history'])
                    ->whereNumber('santri')->defaults('report_kind', $kind)->name($kind . '.history');
            }
        });
}
