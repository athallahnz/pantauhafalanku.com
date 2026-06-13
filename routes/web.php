<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Admin\InstitutionSettingController;
use App\Http\Controllers\Admin\LaporanController as AdminLaporanController;
use App\Http\Controllers\Admin\SantriController as AdminSantriController;
use App\Http\Controllers\Admin\SantriProgressController as AdminSantriProgressController;
use App\Http\Controllers\Admin\MusyrifController as AdminMusyrifController;
use App\Http\Controllers\Admin\MigrasiSantriController as AdminMigrasiSantriController;
use App\Http\Controllers\Admin\SantriMigrationBatchAuditController as AdminSantriMigrationBatchAuditController;
use App\Http\Controllers\Admin\SantriArchiveController as AdminSantriArchiveController;

use App\Http\Controllers\Musyrif\DashboardController as MusyrifDashboardController;
use App\Http\Controllers\Musyrif\HafalanController as MusyrifHafalanController;
use App\Http\Controllers\Musyrif\TahsinController as MusyrifTahsinController;
use App\Http\Controllers\Musyrif\TilawahController as MusyrifTilawahController;
use App\Http\Controllers\Musyrif\SantriController as MusyrifSantriController;
use App\Http\Controllers\Musyrif\MusyrifAttendanceController as MusyrifAttendanceController;

use App\Http\Controllers\Santri\DashboardController as SantriDashboardController;
use App\Http\Controllers\Santri\HafalanController as SantriHafalanController;

use App\Http\Controllers\KelasController;
use App\Http\Controllers\TahunAjaranController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\ProfileSettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|--------------------------------------------------------------------------
*/

// Cukup gunakan Route::view untuk halaman statis
Route::view('/', 'welcome')->name('welcome');

Route::post('/logout', function () {
    Auth::logout();
    return redirect()->route('login');
})->name('logout');

Route::get('/waiting-approval', function () {
    return view('auth.waiting-approval');
})->name('waiting.approval');

/*
|--------------------------------------------------------------------------
| PROFILE SETTINGS
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get(
        '/profile/settings',
        [ProfileSettingController::class, 'index']
    )
        ->name('profile.settings');

    Route::post(
        '/profile/settings',
        [ProfileSettingController::class, 'store']
    )
        ->name('profile.settings.store');
});

/*
|--------------------------------------------------------------------------
| SUPERADMIN
|--------------------------------------------------------------------------
*/
Route::prefix('superadmin')
    ->name('superadmin.')
    ->middleware(['auth', 'role:superadmin'])
    ->group(function () {

        // 1. Dashboard
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // 2. Bulk Actions (WAJIB DI ATAS ROUTE {id})
        Route::post('/users/bulk-approve', [SuperAdminUserController::class, 'bulkApprove'])->name('users.bulk_approve');

        // UBAH POST JADI DELETE DI SINI
        Route::delete('/users/bulk-delete', [SuperAdminUserController::class, 'bulkDelete'])->name('users.bulk_delete');

        // 3. Manajemen User
        Route::get('/users', [SuperAdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/datatable', [SuperAdminUserController::class, 'getData'])->name('users.datatable');
        Route::post('/users', [SuperAdminUserController::class, 'store'])->name('users.store');
        Route::post('/users/approve', [SuperAdminUserController::class, 'approve'])->name('users.approve');

        // 4. Route Parameter (WAJIB DI BAWAH)
        Route::put('/users/{id}', [SuperAdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{id}', [SuperAdminUserController::class, 'destroy'])->name('users.destroy');
    });

/*
    |--------------------------------------------------------------------------
    | ADMIN / DEPARTEMEN
    |--------------------------------------------------------------------------
    */
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin|pimpinan'])->group(function () {

    // Dashboard Admin
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get(
        '/settings/institution',
        [InstitutionSettingController::class, 'index']
    )
        ->name('settings.institution');

    Route::post(
        '/settings/institution',
        [InstitutionSettingController::class, 'store']
    )
        ->name('settings.institution.store');

    // Log Aktivitas
    Route::get('activity-logs', [AdminActivityLogController::class, 'index'])->name('activity_logs.index');
    Route::get('activity-logs/export', [AdminActivityLogController::class, 'export'])->name('activity_logs.export');

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


    /*
    |--------------------------------------------------------------------------
    | Riwayat & Audit Batch Migrasi Santri
    |--------------------------------------------------------------------------
    */
    Route::prefix('santri/migrasi/riwayat')
        ->name('santri.migrasi.audit.')
        ->controller(AdminSantriMigrationBatchAuditController::class)
        ->group(function () {
            Route::get('/', 'index')
                ->name('index');

            Route::get('/data', 'data')
                ->name('data');

            Route::get('/statistics', 'statistics')
                ->name('statistics');

            Route::get('/export', 'export')
                ->name('export');

            Route::get('/{batch}/items', 'itemsData')
                ->where('batch', '[0-9a-fA-F-]{36}')
                ->name('items');

            Route::patch('/{batch}/cancel', 'cancel')
                ->where('batch', '[0-9a-fA-F-]{36}')
                ->name('cancel');

            Route::get('/{batch}', 'show')
                ->where('batch', '[0-9a-fA-F-]{36}')
                ->name('show');

            Route::get('/{batch}/rollback-check', 'rollbackCheck')
                ->where('batch', '[0-9a-fA-F-]{36}')
                ->name('rollback-check');

            Route::patch('/{batch}/rollback', 'rollback')
                ->where('batch', '[0-9a-fA-F-]{36}')
                ->name('rollback');
        });


    /*
    |--------------------------------------------------------------------------
    | Alumni & Santri Nonaktif
    |--------------------------------------------------------------------------
    */
    Route::prefix('santri/arsip')
        ->name('santri.archive.')
        ->controller(AdminSantriArchiveController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/data', 'data')->name('data');
            Route::get('/statistics', 'statistics')->name('statistics');
            Route::get('/export', 'export')->name('export');

            Route::patch('/{santri}/deactivate', 'deactivate')
                ->whereNumber('santri')
                ->name('deactivate');

            Route::patch('/{santri}/status', 'updateStatus')
                ->whereNumber('santri')
                ->name('update-status');

            Route::patch('/{santri}/reactivate', 'reactivate')
                ->whereNumber('santri')
                ->name('reactivate');

            Route::get('/{santri}', 'show')
                ->whereNumber('santri')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Manajemen Semester dan Lifecycle
    |--------------------------------------------------------------------------
    |
    | URL dasar:
    | /admin/semester
    |
    | Semester baru selalu dibuat sebagai draft.
    | Aktivasi dilakukan setelah migrasi kelas selesai.
    |
    */

    Route::prefix('semester')
        ->name('semester.')
        ->controller(SemesterController::class)
        ->group(function () {

            /*
        |--------------------------------------------------------------------------
        | DataTables
        |--------------------------------------------------------------------------
        */

            Route::get('/datatable', 'getData')
                ->name('datatable');

            /*
        |--------------------------------------------------------------------------
        | Tambah Semester Draft
        |--------------------------------------------------------------------------
        */

            Route::post('/', 'store')
                ->name('store');

            /*
        |--------------------------------------------------------------------------
        | Lifecycle Semester
        |--------------------------------------------------------------------------
        |
        | Route spesifik harus berada sebelum route /{id}.
        |
        */

            Route::patch('/{semester}/activate', 'activate')
                ->whereNumber('semester')
                ->name('activate');

            Route::patch('/{semester}/lock-input', 'lockInput')
                ->whereNumber('semester')
                ->name('lock-input');

            Route::patch('/{semester}/unlock-input', 'unlockInput')
                ->whereNumber('semester')
                ->name('unlock-input');

            /*
        |--------------------------------------------------------------------------
        | Edit dan Hapus Semester Draft
        |--------------------------------------------------------------------------
        */

            Route::put('/{id}', 'update')
                ->whereNumber('id')
                ->name('update');

            Route::delete('/{id}', 'destroy')
                ->whereNumber('id')
                ->name('destroy');
        });

    Route::get(
        '/santri/migrasi/batches',
        [AdminMigrasiSantriController::class, 'batches']
    )->name('santri.migrasi.batch.index');

    Route::get(
        '/santri/migrasi/batches/{batch}',
        [AdminMigrasiSantriController::class, 'showBatch']
    )
        ->where('batch', '[0-9a-fA-F-]{36}')
        ->name('santri.migrasi.batch.show');


    // Kelola Musyrif
    Route::get('musyrif', [AdminMusyrifController::class, 'index'])->name('musyrif.index');
    Route::get('musyrif/data', [AdminMusyrifController::class, 'data'])->name('musyrif.data');
    Route::get('musyrif/{id}', [AdminMusyrifController::class, 'show'])->name('musyrif.show');
    Route::post('musyrif', [AdminMusyrifController::class, 'store'])->name('musyrif.store');
    Route::put('musyrif/{id}', [AdminMusyrifController::class, 'update'])->name('musyrif.update');
    Route::delete('musyrif/{id}', [AdminMusyrifController::class, 'destroy'])->name('musyrif.destroy');
    Route::post('musyrif/import', [AdminMusyrifController::class, 'importExcel'])->name('musyrif.import');
    Route::get(
        'musyrif/get-by-kelas/{kelas_id}',
        [AdminMusyrifController::class, 'getByKelas']
    )
        ->whereNumber('kelas_id')
        ->name('musyrif.by_kelas');

    // ==========================================
    // ROUTE BARU UNTUK IMPORT EXCEL & PREVIEW
    // ==========================================

    // 1. Route untuk upload file & ambil preview (Step 1)
    Route::post('musyrif/sheet-preview', [AdminMusyrifController::class, 'getSheetPreview'])->name('musyrif.sheet_preview');
    Route::post('musyrif/preview-import', [AdminMusyrifController::class, 'previewImport'])
        ->name('musyrif.preview');

    // 2. Route untuk eksekusi import setelah pilih sheet (Step 2)
    Route::post('musyrif/execute-import', [AdminMusyrifController::class, 'executeImport'])
        ->name('musyrif.execute_import');

    // Absensi Musyrif
    Route::get('musyrif/absensi/all', [AdminMusyrifController::class, 'allAttendances'])
        ->name('musyrif.absensi.index');

    Route::delete('musyrif/absensi/{attendance}', [AdminMusyrifController::class, 'destroyAttendance'])
        ->name('musyrif.absensi.destroy');

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
    Route::get('laporan-hafalan/absensi-musyrif', [AdminLaporanController::class, 'getAbsensiMusyrif'])->name('laporan.absensi-musyrif');

    // Chart data
    Route::get('laporan-hafalan/chart-kelas', [AdminLaporanController::class, 'getChartKelas'])->name('laporan.chart-kelas');
    Route::get('laporan-hafalan/chart-musyrif', [AdminLaporanController::class, 'getChartMusyrif'])->name('laporan.chart-musyrif');
    Route::get('laporan-hafalan/chart-juz-lulus', [AdminLaporanController::class, 'getChartJuzLulus'])->name('laporan.chart.juz-lulus');

    /*
        |--------------------------------------------------------------------------
        | Export Excel
        |--------------------------------------------------------------------------
        */

    Route::get(
        '/laporan-hafalan/export/santri/excel',
        [AdminLaporanController::class, 'exportSantriExcel']
    )->name('laporan.export-santri-excel');

    Route::get(
        '/laporan-hafalan/export/kelas/excel',
        [AdminLaporanController::class, 'exportKelasExcel']
    )->name('laporan.export-kelas-excel');

    Route::get(
        '/laporan-hafalan/export/musyrif/excel',
        [AdminLaporanController::class, 'exportMusyrifExcel']
    )->name('laporan.export-musyrif-excel');

    /*
        |--------------------------------------------------------------------------
        | Export PDF — Tahap Persiapan
        |--------------------------------------------------------------------------
        | Endpoint ini membuat PDF, menyimpan file sementara, kemudian
        | mengembalikan JSON yang berisi signed download URL.
        |--------------------------------------------------------------------------
        */

    Route::get(
        '/laporan-hafalan/export/santri/pdf',
        [AdminLaporanController::class, 'exportSantriPdf']
    )->name('laporan.export-santri-pdf');

    Route::get(
        '/laporan-hafalan/export/kelas/pdf',
        [AdminLaporanController::class, 'exportKelasPdf']
    )->name('laporan.export-kelas-pdf');

    Route::get(
        '/laporan-hafalan/export/musyrif/pdf',
        [AdminLaporanController::class, 'exportMusyrifPdf']
    )->name('laporan.export-musyrif-pdf');

    /*
        |--------------------------------------------------------------------------
        | Export PDF — Download File Sementara
        |--------------------------------------------------------------------------
        | Route ini dipanggil melalui signed URL yang dibuat controller.
        |--------------------------------------------------------------------------
        */

    Route::get(
        '/laporan-hafalan/export/pdf/download/{file}',
        [AdminLaporanController::class, 'downloadPreparedPdf']
    )
        ->where('file', '[0-9a-fA-F-]{36}\.pdf')
        ->name('laporan.download-prepared-pdf');

    /*
        |--------------------------------------------------------------------------
        | Detail Progress Santri
        |--------------------------------------------------------------------------
        | URL:
        | /admin/santri-master/{santri}/progress
        |--------------------------------------------------------------------------
        */

    Route::prefix('santri-master')
        ->name('santri.master.progress.')
        ->controller(AdminSantriProgressController::class)
        ->group(function () {

            // Halaman utama progress Hafalan, Tahsin, dan Tilawah
            Route::get('/{santri}/progress', 'show')
                ->name('show');

            // DataTables timeline Hafalan
            Route::get('/{santri}/progress/hafalan/timeline', 'hafalanTimeline')
                ->name('hafalan.timeline');

            // DataTables timeline Tahsin
            Route::get('/{santri}/progress/tahsin/timeline', 'tahsinTimeline')
                ->name('tahsin.timeline');

            // DataTables timeline Tilawah
            Route::get('/{santri}/progress/tilawah/timeline', 'tilawahTimeline')
                ->name('tilawah.timeline');
        });
});



/*
|--------------------------------------------------------------------------
| MUSYRIF
|--------------------------------------------------------------------------
*/
Route::prefix('musyrif')
    ->name('musyrif.')
    ->middleware(['auth', 'role:musyrif', 'approved']) // pastikan middleware 'approved' sudah ditambahkan di Kernel.php
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
                Route::get('/', [MusyrifHafalanController::class, 'index'])->name('index');
                Route::get('/datatable', [MusyrifHafalanController::class, 'datatable'])->name('datatable');
                Route::get('/templates', [MusyrifHafalanController::class, 'templates'])->name('templates');
                Route::get('/create', [MusyrifHafalanController::class, 'create'])->name('create');
                Route::post('/', [MusyrifHafalanController::class, 'store'])->name('store');
                Route::put('/{hafalan}', [MusyrifHafalanController::class, 'update'])->whereNumber('hafalan')->name('update');
                Route::delete('/{hafalan}', [MusyrifHafalanController::class, 'destroy'])->whereNumber('hafalan')->name('destroy');
                Route::get('/{hafalan}', [MusyrifHafalanController::class, 'show'])->whereNumber('hafalan')->name('show');
            });


        // ===================== SANTRI BINAAN =====================
        Route::prefix('santri')
            ->name('santri.')
            ->group(function () {
                Route::get('/', [MusyrifSantriController::class, 'index'])->name('index');
                Route::get('/datatable', [MusyrifSantriController::class, 'datatable'])->name('datatable');
                Route::get('/{santri}/detail', [MusyrifSantriController::class, 'detail'])->whereNumber('santri')->name('detail');
                Route::get('/{santri}/timeline', [MusyrifSantriController::class, 'timeline'])->whereNumber('santri')->name('timeline');
            });


        // ===================== TAHSIN =====================
        Route::prefix('tahsin')
            ->name('tahsin.')
            ->group(function () {
                Route::get('/', [MusyrifTahsinController::class, 'index'])->name('index');
                Route::get('/datatable', [MusyrifTahsinController::class, 'datatable'])->name('datatable');
                Route::post('/', [MusyrifTahsinController::class, 'store'])->name('store');
                Route::get('/today/{santriId}', [MusyrifTahsinController::class, 'getTodayProgress'])->whereNumber('santriId')->name('today');
                Route::put('/{tahsin}', [MusyrifTahsinController::class, 'update'])->whereNumber('tahsin')->name('update');
                Route::delete('/{tahsin}', [MusyrifTahsinController::class, 'destroy'])->whereNumber('tahsin')->name('destroy');
                Route::get('/santri/{santri}', [MusyrifTahsinController::class, 'detail'])->name('detail');
                Route::get('/santri/{santri}/timeline', [MusyrifTahsinController::class, 'timeline'])->name('timeline');
                Route::get('/tahsin/{santri}/timeline-tilawah', [MusyrifTahsinController::class, 'timelineTilawah'])->name('timeline-tilawah');
                Route::get('/tahsin/check-eligibility', [MusyrifTahsinController::class, 'checkEligibility'])->name('check');
            });

        // ===================== TILAWAH =====================
        Route::prefix('tilawah')
            ->name('tilawah.')
            ->group(function () {
                // Route ini akan otomatis menjadi: musyrif.tilawah.progress
                Route::get('/progress', [MusyrifTilawahController::class, 'getProgress'])->name('progress');
                Route::put('/{tilawah}', [MusyrifTilawahController::class, 'update'])->name('update');
                // Route ini akan otomatis menjadi: musyrif.tilawah.masal
                Route::post('/masal', [MusyrifTilawahController::class, 'storeMasal'])->name('masal');
                Route::get('/datatable', [MusyrifTilawahController::class, 'datatable'])->name('datatable');
                Route::delete('/{tilawah}', [MusyrifTilawahController::class, 'destroy'])->whereNumber('tilawah')->name('destroy');
            });
    });

/*
|--------------------------------------------------------------------------
| KELAS (MASTER)
|--------------------------------------------------------------------------
| Hanya SuperAdmin + Admin
*/

// ==================== KELAS ====================
Route::prefix('kelas')
    ->name('kelas.')
    ->middleware(['auth', 'role:superadmin|admin'])
    ->group(function () {
        // Halaman utama pengaturan akademik
        Route::get('/', [KelasController::class, 'index'])
            ->name('index');

        // DataTables source
        Route::get('/datatable', [KelasController::class, 'getData'])
            ->name('datatable');

        // CRUD via AJAX/modal
        Route::post('/', [KelasController::class, 'store'])
            ->name('store');

        Route::put('/{id}', [KelasController::class, 'update'])
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}', [KelasController::class, 'destroy'])
            ->whereNumber('id')
            ->name('destroy');
    });


// ==================== TAHUN AJARAN ====================
Route::prefix('tahun-ajaran')
    ->name('tahun-ajaran.')
    ->middleware(['auth', 'role:superadmin|admin'])
    ->group(function () {
        // DataTables source
        Route::get('/datatable', [TahunAjaranController::class, 'getData'])
            ->name('datatable');

        // Dropdown pilihan tahun ajaran
        Route::get('/options', [TahunAjaranController::class, 'getOptions'])
            ->name('options');

        // CRUD via AJAX/modal
        Route::post('/', [TahunAjaranController::class, 'store'])
            ->name('store');

        Route::put('/{id}', [TahunAjaranController::class, 'update'])
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}', [TahunAjaranController::class, 'destroy'])
            ->whereNumber('id')
            ->name('destroy');
    });


// ==================== SEMESTER ====================
Route::prefix('semester')
    ->name('semester.')
    ->middleware(['auth', 'role:superadmin|admin'])
    ->group(function () {
        // DataTables source
        Route::get('/datatable', [SemesterController::class, 'getData'])
            ->name('datatable');

        // CRUD via AJAX/modal
        Route::post('/', [SemesterController::class, 'store'])
            ->name('store');

        Route::put('/{id}', [SemesterController::class, 'update'])
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}', [SemesterController::class, 'destroy'])
            ->whereNumber('id')
            ->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| TAHUN AJARAN (MASTER)
|--------------------------------------------------------------------------
| Hanya SuperAdmin + Admin
*/
Route::prefix('tahun-ajaran')
    ->name('tahun-ajaran.')
    ->middleware(['auth', 'role:superadmin|admin'])
    ->group(function () {
        // DataTables source
        Route::get('/datatable', [TahunAjaranController::class, 'getData'])->name('datatable');

        // Sumber data untuk Dropdown `<select>` di modal Semester
        Route::get('/options', [TahunAjaranController::class, 'getOptions'])->name('options');

        // CRUD via AJAX/modal
        Route::post('/', [TahunAjaranController::class, 'store'])->name('store');
        Route::put('/{id}', [TahunAjaranController::class, 'update'])->name('update');
        Route::delete('/{id}', [TahunAjaranController::class, 'destroy'])->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| SEMESTER (MASTER)
|--------------------------------------------------------------------------
| Hanya SuperAdmin + Admin
*/
Route::prefix('semester')
    ->name('semester.')
    ->middleware(['auth', 'role:superadmin|admin'])
    ->group(function () {
        // DataTables source
        Route::get('/datatable', [SemesterController::class, 'getData'])->name('datatable');

        // CRUD via AJAX/modal
        Route::post('/', [SemesterController::class, 'store'])->name('store');
        Route::put('/{id}', [SemesterController::class, 'update'])->name('update');
        Route::delete('/{id}', [SemesterController::class, 'destroy'])->name('destroy');
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

        /* | 1. RUTE STATIS (HARUS DI ATAS)
        | Daftar rute tanpa parameter dinamis diletakkan paling awal.
        */
        Route::get('/', [AdminSantriController::class, 'index'])->name('index');
        Route::get('/datatable', [AdminSantriController::class, 'getData'])->name('datatable');

        // PINDAH KE SINI: Agar tidak tertabrak rute /{id}
        Route::get('/violation-report', [AdminSantriController::class, 'violationReport'])->name('violation.report');
        Route::get('/violation-report/musyrif/{id}', [AdminSantriController::class, 'violationMusyrifDetail'])->name('violation.musyrif.detail');

        Route::get('/get-by-kelas/{kelas_id}', [AdminSantriController::class, 'getByKelas'])->name('get_by_kelas');


        Route::get('/violation-report/export/excel', [AdminSantriController::class, 'exportExcel'])
            ->name('violation.export.excel');

        Route::get('/violation-report/export/pdf', [AdminSantriController::class, 'exportPdf'])
            ->name('violation.export.pdf');
        /* | 2. RUTE IMPORT
        */
        Route::post('/import/upload', [AdminSantriController::class, 'importUpload'])->name('import.upload');
        Route::post('/import/preview', [AdminSantriController::class, 'importPreview'])->name('import.preview');
        Route::post('/import/process', [AdminSantriController::class, 'importProcess'])->name('import.process');

        /* | 3. RUTE DINAMIS (HARUS DI BAWAH)
        | Rute dengan parameter {id} diletakkan paling akhir dalam grup.
        */
        Route::get('/{id}', [AdminSantriController::class, 'show'])->name('show');
        Route::put('/{id}', [AdminSantriController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminSantriController::class, 'destroy'])->name('destroy');
        Route::put('/{id}/assign-user', [AdminSantriController::class, 'addUser'])->name('addUser');

        // Peringatan: Jangan ada Route::post('/') lagi di bawah sini
        Route::post('/', [AdminSantriController::class, 'store'])->name('store');
    });

/*
|--------------------------------------------------------------------------
| SANTRI
|--------------------------------------------------------------------------
*/

Route::prefix('santri')
    ->name('santri.')
    ->middleware([
        'auth',
        'role:santri',
        'approved',
    ])
    ->group(function () {

        Route::get(
            '/dashboard',
            [SantriHafalanController::class, 'index']
        )->name('dashboard');

        Route::get(
            '/hafalan/export-pdf',
            [SantriHafalanController::class, 'exportPdf']
        )->name('hafalan.export-pdf');

        Route::get(
            '/hafalan/timeline',
            [SantriHafalanController::class, 'timeline']
        )->name('hafalan.timeline');

        Route::get(
            '/tahsin/timeline',
            [SantriHafalanController::class, 'tahsinTimeline']
        )->name('tahsin.timeline');

        Route::get(
            '/tilawah/timeline',
            [SantriHafalanController::class, 'tilawahTimeline']
        )->name('tilawah.timeline');

        Route::get('/hafalan', function () {
            return redirect()->route('santri.dashboard');
        })->name('hafalan.index');
    });
