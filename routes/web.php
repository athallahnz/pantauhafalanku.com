<?php

use App\Http\Controllers\Admin\MigrasiSantriController;
use App\Http\Controllers\Musyrif\HafalanController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\Admin\SantriController as AdminSantriController;
use App\Http\Controllers\Admin\MusyrifController as AdminMusyrifController;
use App\Http\Controllers\Admin\MigrasiSantriController as AdminMigrasiSantriController;

use App\Http\Controllers\Musyrif\DashboardController as MusyrifDashboardController;
use App\Http\Controllers\Musyrif\HafalanController as MusyrifHafalanController;
use App\Http\Controllers\Musyrif\SantriController as MusyrifSantriController;
use App\Http\Controllers\Musyrif\MusyrifAttendanceController as MusyrifAttendanceController;

use App\Http\Controllers\Santri\DashboardController as SantriDashboardController;
use App\Http\Controllers\Santri\HafalanController as SantriHafalanController;

use App\Http\Controllers\KelasController;
use App\Models\Santri;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});
Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');


/*
|--------------------------------------------------------------------------
| SUPERADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {

        // Dashboard SuperAdmin
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        // Manajemen User SuperAdmin
        Route::get('/users', [SuperAdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/datatable', [SuperAdminUserController::class, 'getData'])->name('users.datatable');
        Route::post('/users', [SuperAdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [SuperAdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');
    });

/*
|--------------------------------------------------------------------------
| ADMIN / DEPARTEMEN
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {

    // Dashboard Admin
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Migrasi Santri
    Route::get('/santri/naik-kelas', [AdminMigrasiSantriController::class, 'page'])
        ->name('santri.migrasi.page');

    Route::get('/santri/migrasi/by-kelas', [AdminMigrasiSantriController::class, 'byKelas'])
        ->name('santri.migrasi.by_kelas');

    Route::post('/santri/migrasi/preview', [AdminMigrasiSantriController::class, 'preview'])
        ->name('santri.migrasi.preview');

    Route::post('/santri/migrasi/execute', [AdminMigrasiSantriController::class, 'execute'])
        ->name('santri.migrasi.execute');

    Route::post('/santri/migrasi/massal/preview', [AdminMigrasiSantriController::class, 'previewMassal'])
        ->name('santri.migrasi.massal.preview');

    Route::post('/santri/migrasi/massal/execute', [AdminMigrasiSantriController::class, 'executeMassal'])
        ->name('santri.migrasi.massal.execute');

    Route::post('/santri/migrasi/auto/preview', [AdminMigrasiSantriController::class, 'previewAutoMapping'])
        ->name('santri.migrasi.auto.preview');

    Route::post('/santri/migrasi/auto/execute', [AdminMigrasiSantriController::class, 'executeAutoMapping'])
        ->name('santri.migrasi.auto.execute');


    // Kelola Musyrif
    Route::get('musyrif', [AdminMusyrifController::class, 'index'])->name('musyrif.index');
    Route::get('musyrif/data', [AdminMusyrifController::class, 'data'])->name('musyrif.data');
    Route::get('musyrif/{id}', [AdminMusyrifController::class, 'show'])->name('musyrif.show');
    Route::post('musyrif', [AdminMusyrifController::class, 'store'])->name('musyrif.store');
    Route::put('musyrif/{id}', [AdminMusyrifController::class, 'update'])->name('musyrif.update');
    Route::delete('musyrif/{id}', [AdminMusyrifController::class, 'destroy'])->name('musyrif.destroy');

    // DataTables endpoint untuk attendances musyrif
    Route::get('musyrif/{id}/attendances', [AdminMusyrifController::class, 'attendances'])
        ->name('musyrif.attendances');
    Route::get('musyrif/{id}/attendances', [AdminMusyrifController::class, 'attendances'])
        ->name('musyrif.attendances');
    Route::patch('musyrif/attendances/{attendance}/status', [AdminMusyrifController::class, 'updateAttendanceStatus'])
        ->name('musyrif.attendances.update_status');


    // Laporan Hafalan
    Route::get('laporan-hafalan', [AdminLaporanController::class, 'index'])->name('laporan.index');

    // DataTables endpoints
    Route::get('laporan-hafalan/data', [AdminLaporanController::class, 'getRekapSantri'])->name('laporan.data');
    Route::get('laporan-hafalan/rekap-kelas', [AdminLaporanController::class, 'getRekapKelas'])->name('laporan.rekap-kelas');
    Route::get('laporan-hafalan/rekap-musyrif', [AdminLaporanController::class, 'getRekapMusyrif'])->name('laporan.rekap-musyrif');
    Route::get('laporan-hafalan/riwayat-santri/{id}', [AdminLaporanController::class, 'getRiwayatSantri'])->name('laporan.riwayat-santri');

    // Chart data
    Route::get('laporan-hafalan/chart-kelas', [AdminLaporanController::class, 'getChartKelas'])->name('laporan.chart-kelas');
    Route::get('laporan-hafalan/chart-musyrif', [AdminLaporanController::class, 'getChartMusyrif'])->name('laporan.chart-musyrif');
    Route::get('laporan-hafalan/chart-juz-lulus', [AdminLaporanController::class, 'getChartJuzLulus'])->name('laporan.chart.juz-lulus');

    // Export Excel
    Route::get('laporan-hafalan/export/santri/excel', [AdminLaporanController::class, 'exportSantriExcel'])->name('laporan.export-santri-excel');
    Route::get('laporan-hafalan/export/kelas/excel', [AdminLaporanController::class, 'exportKelasExcel'])->name('laporan.export-kelas-excel');
    Route::get('laporan-hafalan/export/musyrif/excel', [AdminLaporanController::class, 'exportMusyrifExcel'])->name('laporan.export-musyrif-excel');

    // Export PDF
    Route::get('laporan-hafalan/export/santri/pdf', [AdminLaporanController::class, 'exportSantriPdf'])->name('laporan.export-santri-pdf');
    Route::get('laporan-hafalan/export/kelas/pdf', [AdminLaporanController::class, 'exportKelasPdf'])->name('laporan.export-kelas-pdf');
    Route::get('laporan-hafalan/export/musyrif/pdf', [AdminLaporanController::class, 'exportMusyrifPdf'])->name('laporan.export-musyrif-pdf');
});

/*
|--------------------------------------------------------------------------
| MUSYRIF
|--------------------------------------------------------------------------
*/
Route::prefix('musyrif')
    ->name('musyrif.')
    ->middleware(['auth', 'role:musyrif'])
    ->group(function () {

        // ===================== DASHBOARD =====================
        Route::get('/dashboard', [MusyrifDashboardController::class, 'index'])
            ->name('dashboard');

        // ===================== ABSENSI ====================
        Route::get('/absensi', [MusyrifAttendanceController::class, 'index'])
            ->name('absensi.index');

        Route::post('/absensi', [MusyrifAttendanceController::class, 'store'])
            ->name('absensi.store');

        Route::get('/absensi/riwayat', [MusyrifAttendanceController::class, 'history'])
            ->name('absensi.history');

        // ===================== HAFALAN =====================
        Route::prefix('hafalan')
            ->name('hafalan.')
            ->group(function () {

                // Riwayat (index) – view: resources/views/musyrif/hafalan/index.blade.php
                Route::get('/', [MusyrifHafalanController::class, 'index'])
                    ->name('index');

                // DataTables
                Route::get('/datatable', [MusyrifHafalanController::class, 'datatable'])
                    ->name('datatable');

                // AJAX - templates dropdown Surah:Ayat otomatis
                Route::get('/templates', [MusyrifHafalanController::class, 'templates'])
                    ->name('templates');

                // Form halaman sendiri (kalau tetap dipakai)
                // view: resources/views/musyrif/hafalan/create.blade.php
                Route::get('/create', [MusyrifHafalanController::class, 'create'])
                    ->name('create');

                // Store
                Route::post('/', [MusyrifHafalanController::class, 'store'])
                    ->name('store');

                // Update
                Route::put('/{hafalan}', [MusyrifHafalanController::class, 'update'])
                    ->whereNumber('hafalan')
                    ->name('update');

                // Delete
                Route::delete('/{hafalan}', [MusyrifHafalanController::class, 'destroy'])
                    ->whereNumber('hafalan')
                    ->name('destroy');

                // Detail – view: resources/views/musyrif/hafalan/show.blade.php
                Route::get('/{hafalan}', [MusyrifHafalanController::class, 'show'])
                    ->whereNumber('hafalan')
                    ->name('show');
            });

        // ===================== SANTRI BINAAN =====================
        Route::prefix('santri')
            ->name('santri.')
            ->group(function () {

                // List santri binaan – view: resources/views/musyrif/santri/index.blade.php
                Route::get('/', [MusyrifSantriController::class, 'index'])
                    ->name('index');

                // DataTables santri binaan
                Route::get('/datatable', [MusyrifSantriController::class, 'datatable'])
                    ->name('datatable');

                Route::get('/{santri}/detail', [MusyrifSantriController::class, 'detail'])
                    ->whereNumber('santri')
                    ->name('detail');

                Route::get('/{santri}/timeline', [MusyrifSantriController::class, 'timeline'])
                    ->whereNumber('santri')
                    ->name('timeline');
            });
    });


/*
|--------------------------------------------------------------------------
| KELAS (MASTER)
|--------------------------------------------------------------------------
| Hanya SuperAdmin + Admin
*/

Route::prefix('kelas')
    ->name('kelas.')
    ->middleware(['auth', 'role:superadmin|admin']) // pakai koma, sesuai RoleMiddleware kamu
    ->group(function () {

        Route::get('/', [KelasController::class, 'index'])->name('index');

        // DataTables source
        Route::get('/datatable', [KelasController::class, 'getData'])->name('datatable');

        // CRUD via AJAX/modal
        Route::post('/', [KelasController::class, 'store'])->name('store');
        Route::put('/{id}', [KelasController::class, 'update'])->name('update');
        Route::delete('/{id}', [KelasController::class, 'destroy'])->name('destroy');

        // create/edit view terpisah sebenarnya sudah tidak diperlukan,
        // tapi kalau mau tetap disimpan boleh di-comment atau dihapus:
        // Route::get('/create', [KelasController::class, 'create'])->name('create');
        // Route::get('/{id}/edit', [KelasController::class, 'edit'])->name('edit');
    });


/*|--------------------------------------------------------------------------
| SANTRI (MASTER)
|--------------------------------------------------------------------------
| Hanya SuperAdmin + Admin
*/
Route::prefix('santri-master')
    ->name('santri.master.')
    ->middleware(['auth', 'role:superadmin|admin|musyrif'])
    ->group(function () {
        Route::get('/', [AdminSantriController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminSantriController::class, 'getData'])->name('datatable');
        Route::post('/', [AdminSantriController::class, 'store'])->name('store');
        Route::put('/{id}', [AdminSantriController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminSantriController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [AdminSantriController::class, 'show'])->name('show');

        // PUT khusus untuk assign / update user
        Route::put('/{id}/assign-user', [AdminSantriController::class, 'addUser'])->name('addUser');

        Route::post('/import/upload', [AdminSantriController::class, 'importUpload'])->name('import.upload');
        Route::post('/import/preview', [AdminSantriController::class, 'importPreview'])->name('import.preview');
        Route::post('/import/process', [AdminSantriController::class, 'importProcess'])->name('import.process');
    });


/*
|--------------------------------------------------------------------------
| SANTRI
|--------------------------------------------------------------------------
*/
// routes/web.php

Route::prefix('santri')
    ->name('santri.')
    ->middleware(['auth', 'role:santri'])
    ->group(function () {
        Route::get('/dashboard', [SantriDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/ringkasan-data', [SantriDashboardController::class, 'ringkasanData'])->name('dashboard.ringkasan-data');
        Route::get('/dashboard/timeline-data', [SantriDashboardController::class, 'timelineData'])->name('dashboard.timeline-data');

        Route::get('/hafalan', [SantriHafalanController::class, 'index'])->name('hafalan.index');
        Route::get('/hafalan/timeline', [SantriHafalanController::class, 'timeline'])->name('hafalan.timeline');
    });
