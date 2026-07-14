@extends('layouts.app')

@section('title', 'Pengaturan Akademik')

@section('content')
    <style>
        :root {
            --academic-primary: var(--islamic-purple-600, #6f42c1);
            --academic-primary-dark: var(--islamic-purple-700, #59359b);
            --academic-primary-soft: rgba(111, 66, 193, 0.10);
        }

        .academic-hero {
            position: relative;
            overflow: hidden;
            border: 0;
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.20), transparent 38%),
                linear-gradient(135deg, var(--academic-primary-dark), var(--academic-primary));
            color: #fff;
        }

        .academic-hero::after {
            content: "";
            position: absolute;
            right: -70px;
            bottom: -100px;
            width: 250px;
            height: 250px;
            border: 42px solid rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .academic-hero-content {
            position: relative;
            z-index: 1;
        }

        .academic-stat-card,
        .academic-content-card {
            border: 1px solid var(--cui-border-color);
            border-radius: 1rem;
            background: var(--cui-card-bg, var(--cui-body-bg));
        }

        .academic-stat-icon {
            display: inline-grid;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 14px;
            background: var(--academic-primary-soft);
            color: var(--academic-primary);
            font-size: 1.15rem;
        }

        .academic-tabs {
            display: inline-flex;
            max-width: 100%;
            gap: .35rem;
            padding: .4rem;
            overflow-x: auto;
            border: 1px solid var(--cui-border-color);
            border-radius: 999px;
            background: var(--cui-card-bg, var(--cui-body-bg));
        }

        .academic-tabs .nav-link {
            min-width: max-content;
            border: 0;
            border-radius: 999px;
            padding: .65rem 1.15rem;
            color: var(--cui-body-color);
            font-weight: 600;
        }

        .academic-tabs .nav-link:hover {
            background: var(--academic-primary-soft);
            color: var(--academic-primary);
        }

        .academic-tabs .nav-link.active {
            background: var(--academic-primary);
            color: #fff;
            box-shadow: 0 .35rem 1rem rgba(111, 66, 193, .24);
        }

        .academic-content-card .card-header {
            border-bottom: 1px solid var(--cui-border-color);
            background: transparent;
        }

        .academic-add-button {
            border-color: var(--academic-primary);
            background: var(--academic-primary);
            color: #fff;
        }

        .academic-add-button:hover,
        .academic-add-button:focus {
            border-color: var(--academic-primary-dark);
            background: var(--academic-primary-dark);
            color: #fff;
        }

        .academic-table thead th {
            border-bottom-width: 1px;
            padding-top: .9rem;
            padding-bottom: .9rem;
            color: var(--cui-secondary-color);
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .academic-table tbody td {
            padding-top: .85rem;
            padding-bottom: .85rem;
            vertical-align: middle;
        }

        .table-description {
            display: block;
            max-width: 360px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .academic-modal .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 1rem;
        }

        .academic-modal .modal-header {
            border-bottom: 1px solid var(--cui-border-color);
            background: var(--academic-primary-soft);
        }

        .academic-modal .modal-title-icon {
            display: inline-grid;
            width: 38px;
            height: 38px;
            place-items: center;
            border-radius: 12px;
            background: var(--academic-primary);
            color: #fff;
        }

        .academic-modal .form-label {
            margin-bottom: .45rem;
            font-size: .82rem;
            font-weight: 700;
        }

        .academic-modal .form-control,
        .academic-modal .form-select {
            min-height: 44px;
            border-radius: .75rem;
        }

        .academic-modal textarea.form-control {
            min-height: 105px;
        }

        .academic-modal .modal-footer {
            border-top: 1px solid var(--cui-border-color);
        }

        .semester-lifecycle-note {
            border: 1px solid rgba(13, 202, 240, .18);
            border-radius: .85rem;
            background: rgba(13, 202, 240, .08);
        }

        .semester-lifecycle-flow {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .5rem;
        }

        .semester-lifecycle-flow .badge {
            padding: .55rem .75rem;
        }


        .placement-backfill-card {
            border: 1px solid rgba(111, 66, 193, .18);
            border-radius: 1rem;
            background: rgba(111, 66, 193, .055);
        }

        .placement-backfill-stat {
            height: 100%;
            border: 1px solid var(--cui-border-color);
            border-radius: .9rem;
            background: var(--cui-card-bg, var(--cui-body-bg));
        }

        .placement-backfill-stat-label {
            color: var(--cui-secondary-color);
            font-size: .74rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .placement-backfill-stat-value {
            margin-top: .3rem;
            font-size: 1.45rem;
            font-weight: 800;
        }

        .placement-backfill-progress {
            height: 12px;
            border-radius: 999px;
            overflow: hidden;
        }

        .placement-backfill-warning-list {
            margin: 0;
            padding-left: 1.1rem;
        }

        .placement-backfill-warning-list li+li {
            margin-top: .35rem;
        }

        .dataTables_wrapper>.row:first-child {
            align-items: center;
            padding: 1rem 1.25rem .65rem;
            margin: 0;
        }

        .dataTables_wrapper>.row:last-child {
            align-items: center;
            padding: .85rem 1.25rem 1rem;
            margin: 0;
            border-top: 1px solid var(--cui-border-color);
        }

        .dataTables_wrapper .dataTables_filter input {
            min-width: 220px;
            margin-left: .5rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 999px;
            padding: .45rem .9rem;
            background: var(--cui-body-bg);
            color: var(--cui-body-color);
        }

        .dataTables_wrapper .dataTables_length select {
            min-width: 72px;
            margin: 0 .35rem;
            border-radius: .65rem;
        }

        .dataTables_wrapper .pagination {
            margin-bottom: 0;
        }

        .dataTables_wrapper .page-link {
            border-radius: .6rem !important;
            margin-inline: .12rem;
        }

        .dataTables_wrapper .page-item.active .page-link {
            border-color: var(--academic-primary);
            background: var(--academic-primary);
        }

        @media (max-width: 767.98px) {
            .academic-tabs {
                display: flex;
                width: 100%;
                border-radius: 1rem;
            }

            .academic-tabs .nav-item {
                flex: 1 0 auto;
            }

            .academic-tabs .nav-link {
                width: 100%;
                text-align: center;
            }

            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_length {
                margin-top: .5rem;
                text-align: left;
            }

            .dataTables_wrapper .dataTables_filter input {
                width: calc(100% - 55px);
                min-width: 0;
            }
        }
    </style>

    <section class="academic-hero shadow-sm mb-4">
        <div class="academic-hero-content p-4 p-lg-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill bg-white text-dark mb-3 px-3 py-2">
                        <i class="bi bi-sliders2 me-1"></i>
                        Master Data Akademik
                    </span>

                    <h2 class="mb-2 fw-bold">Pengaturan Akademik</h2>

                    <p class="mb-0 text-white-50">
                        Kelola kelas, tahun ajaran, periode semester, dan lifecycle akademik dalam satu halaman.
                    </p>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <div class="small text-white-50 mb-1">
                        Perubahan tersimpan melalui AJAX
                    </div>

                    <div class="fw-semibold">
                        <i class="bi bi-shield-check me-1"></i>
                        CRUD tanpa reload halaman
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="academic-stat-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <span class="academic-stat-icon">
                        <i class="bi bi-easel2"></i>
                    </span>

                    <div>
                        <div class="small text-body-secondary">Total Kelas</div>
                        <div class="fs-4 fw-bold" id="countKelas">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="academic-stat-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <span class="academic-stat-icon">
                        <i class="bi bi-calendar3"></i>
                    </span>

                    <div>
                        <div class="small text-body-secondary">Tahun Ajaran</div>
                        <div class="fs-4 fw-bold" id="countTahunAjaran">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="academic-stat-card h-100 p-3 shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <span class="academic-stat-icon">
                        <i class="bi bi-calendar2-week"></i>
                    </span>

                    <div>
                        <div class="small text-body-secondary">Total Semester</div>
                        <div class="fs-4 fw-bold" id="countSemester">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills academic-tabs mb-4" id="academicTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="kelas-tab" data-coreui-toggle="pill" data-coreui-target="#kelas-pane"
                type="button" role="tab" aria-controls="kelas-pane" aria-selected="true">
                <i class="bi bi-easel2 me-1"></i>
                Kelas
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tahun-ajaran-tab" data-coreui-toggle="pill" data-coreui-target="#tahun-ajaran-pane"
                type="button" role="tab" aria-controls="tahun-ajaran-pane" aria-selected="false">
                <i class="bi bi-calendar3 me-1"></i>
                Tahun Ajaran
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link" id="semester-tab" data-coreui-toggle="pill" data-coreui-target="#semester-pane"
                type="button" role="tab" aria-controls="semester-pane" aria-selected="false">
                <i class="bi bi-calendar2-week me-1"></i>
                Semester
            </button>
        </li>
    </ul>

    <div class="tab-content" id="academicTabsContent">
        {{-- ==================== TAB KELAS ==================== --}}
        <div class="tab-pane fade show active" id="kelas-pane" role="tabpanel" aria-labelledby="kelas-tab" tabindex="0">

            <div class="card academic-content-card border-0 shadow-sm">
                <div class="card-header px-3 px-md-4 py-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div>
                            <h5 class="mb-1 fw-bold">Daftar Kelas</h5>
                            <div class="small text-body-secondary">
                                Master tingkat atau kelompok kelas santri.
                            </div>
                        </div>

                        <button type="button" class="btn academic-add-button rounded-pill px-3" id="btnAddKelas">
                            <i class="bi bi-plus-lg me-1"></i>
                            Tambah Kelas
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="kelas-table" class="table academic-table table-hover align-middle w-100 mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">No.</th>
                                    <th>Nama Kelas</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== TAB TAHUN AJARAN ==================== --}}
        <div class="tab-pane fade" id="tahun-ajaran-pane" role="tabpanel" aria-labelledby="tahun-ajaran-tab" tabindex="0">

            <div class="card academic-content-card border-0 shadow-sm">
                <div class="card-header px-3 px-md-4 py-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div>
                            <h5 class="mb-1 fw-bold">Daftar Tahun Ajaran</h5>
                            <div class="small text-body-secondary">
                                Tentukan rentang periode dan tahun ajaran aktif.
                            </div>
                        </div>

                        <button type="button" class="btn academic-add-button rounded-pill px-3" id="btnAddTa">
                            <i class="bi bi-plus-lg me-1"></i>
                            Tambah Tahun Ajaran
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="ta-table" class="table academic-table table-hover align-middle w-100 mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">No.</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Periode</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== TAB SEMESTER ==================== --}}
        <div class="tab-pane fade" id="semester-pane" role="tabpanel" aria-labelledby="semester-tab" tabindex="0">

            <div class="semester-lifecycle-note p-3 mb-3">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div>
                        <div class="fw-bold mb-1">
                            <i class="bi bi-diagram-3-fill text-info me-1"></i>
                            Lifecycle Semester
                        </div>

                        <div class="small text-body-secondary">
                            Semester baru dibuat sebagai draft. Kunci input semester aktif sebelum migrasi,
                            lalu aktifkan semester draft setelah seluruh perpindahan kelas selesai.
                        </div>
                    </div>

                    <div class="semester-lifecycle-flow">
                        <span class="badge text-bg-secondary rounded-pill">Draft</span>
                        <i class="bi bi-arrow-right text-body-secondary"></i>
                        <span class="badge text-bg-success rounded-pill">Active</span>
                        <i class="bi bi-arrow-right text-body-secondary"></i>
                        <span class="badge text-bg-dark rounded-pill">Closed</span>
                    </div>
                </div>
            </div>

            <div class="card academic-content-card border-0 shadow-sm">
                <div class="card-header px-3 px-md-4 py-3">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div>
                            <h5 class="mb-1 fw-bold">Daftar Semester</h5>
                            <div class="small text-body-secondary">
                                Kelola semester dan penguncian input Hafalan, Tahsin, serta Tilawah.
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if (
                                \Illuminate\Support\Facades\Route::has('admin.maintenance.semester-placement.preview') &&
                                    \Illuminate\Support\Facades\Route::has('admin.maintenance.semester-placement.process'))
                                <button type="button" class="btn btn-outline-primary rounded-pill px-3"
                                    id="btnOpenPlacementBackfill" data-coreui-toggle="modal"
                                    data-coreui-target="#placementBackfillModal">

                                    <i class="bi bi-database-fill-gear me-1"></i>
                                    Backfill Placement
                                </button>
                            @endif

                            <button type="button" class="btn academic-add-button rounded-pill px-3" id="btnAddSemester">

                                <i class="bi bi-plus-lg me-1"></i>
                                Tambah Semester Draft
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="semester-table" class="table academic-table table-hover align-middle w-100 mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">No.</th>
                                    <th>Semester</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Periode</th>
                                    <th>Status Lifecycle</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- ==================== MODAL KELAS ==================== --}}
    <div class="modal fade academic-modal" id="modalKelas" tabindex="-1" aria-hidden="true"
        data-coreui-backdrop="static">

        <div class="modal-dialog modal-dialog-centered">
            <form id="formKelas" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="id" id="kelas_id">

                <div class="modal-header px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="modal-title-icon">
                            <i class="bi bi-easel2"></i>
                        </span>

                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalKelasTitle">
                                Tambah Kelas
                            </h5>
                            <small class="text-body-secondary">
                                Lengkapi informasi kelas.
                            </small>
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="nama_kelas" class="form-label">
                            Nama Kelas
                        </label>

                        <input type="text" class="form-control" name="nama_kelas" id="nama_kelas" maxlength="100"
                            autocomplete="off" placeholder="Contoh: Kelas 7" required>
                    </div>

                    <div>
                        <label for="deskripsi" class="form-label">
                            Deskripsi
                        </label>

                        <textarea class="form-control" name="deskripsi" id="deskripsi" maxlength="1000" rows="4"
                            placeholder="Keterangan tambahan kelas (opsional)"></textarea>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-coreui-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn academic-add-button rounded-pill px-4"
                        data-submit-text="Simpan Kelas">
                        <span class="submit-label">
                            <i class="bi bi-check2-circle me-1"></i>
                            Simpan Kelas
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL TAHUN AJARAN ==================== --}}
    <div class="modal fade academic-modal" id="modalTa" tabindex="-1" aria-hidden="true"
        data-coreui-backdrop="static">

        <div class="modal-dialog modal-dialog-centered">
            <form id="formTa" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="id" id="ta_id">

                <div class="modal-header px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="modal-title-icon">
                            <i class="bi bi-calendar3"></i>
                        </span>

                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="modalTaTitle">
                                Tambah Tahun Ajaran
                            </h5>
                            <small class="text-body-secondary">
                                Atur nama, periode, dan status aktif.
                            </small>
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="ta_nama" class="form-label">
                            Tahun Ajaran
                        </label>

                        <input type="text" class="form-control" name="nama" id="ta_nama" maxlength="100"
                            autocomplete="off" placeholder="Contoh: 2026/2027" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="ta_tanggal_mulai" class="form-label">
                                Tanggal Mulai
                            </label>

                            <input type="date" class="form-control" name="tanggal_mulai" id="ta_tanggal_mulai"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label for="ta_tanggal_selesai" class="form-label">
                                Tanggal Selesai
                            </label>

                            <input type="date" class="form-control" name="tanggal_selesai" id="ta_tanggal_selesai"
                                required>
                        </div>
                    </div>

                    <div class="rounded-3 border p-3">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active"
                                id="ta_is_active" value="1">

                            <label class="form-check-label fw-semibold" for="ta_is_active">
                                Jadikan tahun ajaran aktif
                            </label>
                        </div>

                        <div class="small text-body-secondary">
                            Saat diaktifkan, tahun ajaran lain otomatis menjadi nonaktif.
                        </div>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-coreui-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn academic-add-button rounded-pill px-4"
                        data-submit-text="Simpan Tahun Ajaran">
                        <span class="submit-label">
                            <i class="bi bi-check2-circle me-1"></i>
                            Simpan Tahun Ajaran
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ==================== MODAL SEMESTER ==================== --}}
    <div class="modal fade academic-modal" id="modalSemester" tabindex="-1" aria-hidden="true"
        data-coreui-backdrop="static">

        <div class="modal-dialog modal-dialog-centered">
            <form id="formSemester" class="modal-content" novalidate>
                @csrf
                <input type="hidden" name="id" id="semester_id">

                <div class="modal-header px-4 py-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="modal-title-icon">
                            <i class="bi bi-calendar2-week"></i>
                        </span>

                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-body-secondary" id="modalSemesterTitle">
                                Tambah Semester Draft
                            </h5>
                            <small class="text-body-secondary">
                                Hubungkan semester dengan tahun ajaran.
                            </small>
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="semester_ta_id" class="form-label">
                            Tahun Ajaran
                        </label>

                        <select class="form-select" name="tahun_ajaran_id" id="semester_ta_id" required>
                            <option value="">Memuat tahun ajaran...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="semester_nama" class="form-label">
                            Nama Semester
                        </label>

                        <input type="text" class="form-control" name="nama" id="semester_nama" maxlength="100"
                            autocomplete="off" placeholder="Contoh: Ganjil" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="semester_tanggal_mulai" class="form-label">
                                Tanggal Mulai
                            </label>

                            <input type="date" class="form-control" name="tanggal_mulai" id="semester_tanggal_mulai"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label for="semester_tanggal_selesai" class="form-label">
                                Tanggal Selesai
                            </label>

                            <input type="date" class="form-control" name="tanggal_selesai"
                                id="semester_tanggal_selesai" required>
                        </div>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 mb-0">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle-fill mt-1"></i>

                            <div class="small">
                                Semester baru disimpan sebagai
                                <strong>Draft</strong>.
                                Aktivasi dilakukan melalui tombol
                                <i class="bi bi-play-circle"></i>
                                pada tabel setelah migrasi kelas selesai.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-coreui-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn academic-add-button rounded-pill px-4"
                        data-submit-text="Simpan Semester Draft">
                        <span class="submit-label">
                            <i class="bi bi-check2-circle me-1"></i>
                            Simpan Semester Draft
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ==================== MODAL BACKFILL PLACEMENT ==================== --}}
    @if (
        \Illuminate\Support\Facades\Route::has('admin.maintenance.semester-placement.preview') &&
            \Illuminate\Support\Facades\Route::has('admin.maintenance.semester-placement.process'))
        <div class="modal fade academic-modal" id="placementBackfillModal" tabindex="-1"
            aria-labelledby="placementBackfillModalLabel" aria-hidden="true" data-coreui-backdrop="static"
            data-coreui-keyboard="false">

            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="modal-title-icon">
                                <i class="bi bi-database-fill-gear"></i>
                            </span>

                            <div>
                                <h5 class="modal-title fw-bold mb-0 text-body-secondary" id="placementBackfillModalLabel">
                                    Backfill Placement Semester
                                </h5>

                                <small class="text-body-secondary">
                                    Membuat placement santri yang belum tersedia pada semester aktif.
                                </small>
                            </div>
                        </div>

                        <button type="button" class="btn-close" id="btnClosePlacementBackfill"
                            data-coreui-dismiss="modal" aria-label="Tutup">
                        </button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="placement-backfill-card p-3 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <span class="academic-stat-icon flex-shrink-0">
                                    <i class="bi bi-shield-check"></i>
                                </span>

                                <div>
                                    <div class="fw-bold mb-1">
                                        Proses Aman dan Idempotent
                                    </div>

                                    <div class="small text-body-secondary">
                                        Sistem hanya membuat placement yang belum tersedia.
                                        Placement yang sudah ada tidak ditimpa dan histori semester lama tidak diubah.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="placement-backfill-stat p-3">
                                    <div class="placement-backfill-stat-label">
                                        Semester Aktif
                                    </div>

                                    <div class="fw-bold mt-2" id="backfillSemesterLabel">
                                        Memuat data...
                                    </div>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="placement-backfill-stat p-3">
                                    <div class="placement-backfill-stat-label">Total Santri</div>
                                    <div class="placement-backfill-stat-value" id="backfillTotal">0</div>
                                </div>
                            </div>

                            <div class="col-6 col-md-3">
                                <div class="placement-backfill-stat p-3">
                                    <div class="placement-backfill-stat-label">Belum Ada</div>
                                    <div class="placement-backfill-stat-value text-warning" id="backfillMissing">0</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="placement-backfill-stat p-3">
                                    <div class="placement-backfill-stat-label">Placement Sudah Ada</div>
                                    <div class="placement-backfill-stat-value text-success" id="backfillExisting">0</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="placement-backfill-stat p-3">
                                    <div class="placement-backfill-stat-label">Data Tidak Sinkron</div>
                                    <div class="placement-backfill-stat-value text-danger" id="backfillMismatch">0</div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning border-0 rounded-4 d-none" id="backfillWarningBox">
                            <div class="fw-bold mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                Pemeriksaan Data
                            </div>
                            <ul class="placement-backfill-warning-list small" id="backfillWarningList"></ul>
                        </div>

                        <div class="mt-4 d-none" id="backfillProgressContainer">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold">Progress Backfill</span>
                                <span class="fw-bold" id="backfillProgressText">0%</span>
                            </div>

                            <div class="progress placement-backfill-progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    id="backfillProgressBar" role="progressbar" style="width: 0%;" aria-valuemin="0"
                                    aria-valuemax="100" aria-valuenow="0">
                                </div>
                            </div>

                            <div class="small text-body-secondary mt-2" id="backfillProcessInfo">
                                Menunggu proses...
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer px-4 py-3">
                        <button type="button" class="btn btn-light rounded-pill px-3" id="btnDismissPlacementBackfill"
                            data-coreui-dismiss="modal">
                            Tutup
                        </button>

                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3"
                            id="btnRefreshBackfillPreview">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Periksa Ulang
                        </button>

                        <button type="button" class="btn academic-add-button rounded-pill px-4"
                            id="btnRunPlacementBackfill" disabled>
                            <i class="bi bi-play-fill me-1"></i>
                            Jalankan Backfill
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ??
                @json(csrf_token());

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            /*
            |--------------------------------------------------------------------------
            | Endpoint
            |--------------------------------------------------------------------------
            |
            | Kelas dan Tahun Ajaran mempertahankan route lama.
            | Semester menggunakan route bersama:
            | /semester dan semester.*
            |
            | Route ini dapat diakses Super Admin dan Admin.
            |
            */
            const endpoints = {
                kelas: @json(route('kelas.store')),
                kelasDatatable: @json(route('kelas.datatable')),

                tahunAjaran: @json(route('tahun-ajaran.store')),
                tahunAjaranDatatable: @json(route('tahun-ajaran.datatable')),
                tahunAjaranOptions: @json(route('tahun-ajaran.options')),

                semester: @json(route('semester.store')),
                semesterDatatable: @json(route('semester.datatable')),

                placementBackfillPreview: @json(
                    \Illuminate\Support\Facades\Route::has('admin.maintenance.semester-placement.preview')
                        ? route('admin.maintenance.semester-placement.preview')
                        : null),

                placementBackfillProcess: @json(
                    \Illuminate\Support\Facades\Route::has('admin.maintenance.semester-placement.process')
                        ? route('admin.maintenance.semester-placement.process')
                        : null)
            };

            const modalKelas = coreui.Modal.getOrCreateInstance(
                document.getElementById('modalKelas')
            );

            const modalTa = coreui.Modal.getOrCreateInstance(
                document.getElementById('modalTa')
            );

            const modalSemester = coreui.Modal.getOrCreateInstance(
                document.getElementById('modalSemester')
            );

            const placementBackfillElement =
                document.getElementById(
                    'placementBackfillModal'
                );

            const placementBackfillModal =
                placementBackfillElement ?
                coreui.Modal.getOrCreateInstance(
                    placementBackfillElement
                ) :
                null;

            /*
            |--------------------------------------------------------------------------
            | Deep Link dari Dashboard Laporan
            |--------------------------------------------------------------------------
            |
            | Contoh:
            | /kelas?tab=semester&action=backfill
            |
            | Membuka tab Semester lalu modal Backfill Placement secara otomatis.
            |
            */
            const academicPageQuery =
                new URLSearchParams(
                    window.location.search
                );

            function openAcademicActionFromQuery() {
                const requestedTab =
                    academicPageQuery.get('tab');

                const requestedAction =
                    academicPageQuery.get('action');

                if (
                    requestedTab !== 'semester' &&
                    requestedAction !== 'backfill'
                ) {
                    return;
                }

                const semesterTabElement =
                    document.getElementById(
                        'semester-tab'
                    );

                const showBackfillModal =
                    function() {
                        if (
                            requestedAction ===
                            'backfill' &&
                            placementBackfillModal
                        ) {
                            window.setTimeout(
                                function() {
                                    placementBackfillModal
                                        .show();
                                },
                                120
                            );
                        }
                    };

                if (
                    semesterTabElement &&
                    !semesterTabElement
                    .classList
                    .contains('active')
                ) {
                    semesterTabElement
                        .addEventListener(
                            'shown.coreui.tab',
                            showBackfillModal, {
                                once: true
                            }
                        );

                    coreui.Tab
                        .getOrCreateInstance(
                            semesterTabElement
                        )
                        .show();

                    return;
                }

                showBackfillModal();
            }

            window.setTimeout(
                openAcademicActionFromQuery,
                120
            );

            const dataTableDefaults = {
                processing: true,
                serverSide: true,
                autoWidth: false,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                language: {
                    processing: '<div class="d-flex justify-content-center align-items-center gap-2 py-3">' +
                        '<span class="spinner-border spinner-border-sm" role="status"></span>' +
                        '<span>Memuat data...</span>' +
                        '</div>',
                    search: '',
                    searchPlaceholder: 'Cari data...',
                    lengthMenu: 'Tampilkan _MENU_',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Belum ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada data yang tersedia',
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                }
            };

            /*
            |--------------------------------------------------------------------------
            | DataTables Kelas
            |--------------------------------------------------------------------------
            */
            const tableKelas = $('#kelas-table').DataTable({
                ...dataTableDefaults,
                ajax: endpoints.kelasDatatable,
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4',
                        width: '70px'
                    },
                    {
                        data: 'nama_kelas',
                        name: 'nama_kelas',
                        render: function(data, type) {
                            if (type !== 'display') {
                                return data;
                            }

                            return `
                                <span class="fw-semibold">
                                    ${escapeHtml(data ?? '-')}
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi',
                        className: 'text-body-secondary',
                        render: function(data, type) {
                            if (type !== 'display') {
                                return data;
                            }

                            const plainText = stripHtml(data ?? '');

                            return `
                                <span class="table-description"
                                    title="${escapeHtml(plainText)}">
                                    ${data ?? '-'}
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4',
                        width: '110px'
                    }
                ]
            });

            /*
            |--------------------------------------------------------------------------
            | DataTables Tahun Ajaran
            |--------------------------------------------------------------------------
            */
            const tableTa = $('#ta-table').DataTable({
                ...dataTableDefaults,
                ajax: endpoints.tahunAjaranDatatable,
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4',
                        width: '70px'
                    },
                    {
                        data: 'nama',
                        name: 'nama',
                        render: function(data, type) {
                            if (type !== 'display') {
                                return data;
                            }

                            return `
                                <span class="fw-semibold">
                                    ${escapeHtml(data ?? '-')}
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'periode',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'is_active',
                        className: 'text-nowrap'
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4',
                        width: '110px'
                    }
                ]
            });

            /*
            |--------------------------------------------------------------------------
            | DataTables Semester Lifecycle
            |--------------------------------------------------------------------------
            */
            const tableSemester = $('#semester-table').DataTable({
                ...dataTableDefaults,
                ajax: {
                    url: endpoints.semesterDatatable,
                    error: function(xhr, textStatus, errorThrown) {
                        console.error('Semester DataTable Ajax Error', {
                            status: xhr.status,
                            textStatus: textStatus,
                            errorThrown: errorThrown,
                            response: xhr.responseText
                        });

                        const message =
                            xhr.responseJSON?.message ??
                            (xhr.status === 403 ?
                                'Akses endpoint Semester ditolak. Periksa middleware role pada route semester.' :
                                xhr.status === 404 ?
                                'Endpoint DataTable Semester tidak ditemukan.' :
                                xhr.status === 500 ?
                                'Terjadi kesalahan server saat memuat Semester. Periksa storage/logs/laravel.log.' :
                                'Data Semester gagal dimuat.');

                        notifyError(message);
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4',
                        width: '70px'
                    },
                    {
                        data: 'nama',
                        name: 'semesters.nama',
                        render: function(data, type) {
                            if (type !== 'display') {
                                return data;
                            }

                            return `
                                <span class="fw-semibold">
                                    ${escapeHtml(data ?? '-')}
                                </span>
                            `;
                        }
                    },
                    {
                        data: 'tahun_ajaran_nama',
                        name: 'tahun_ajaran_nama',
                        defaultContent: '-'
                    },
                    {
                        data: 'periode',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'semesters.status',
                        className: 'text-nowrap'
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4',
                        width: '160px'
                    }
                ]
            });

            tableKelas.on('xhr.dt', function(_event, _settings, json) {
                $('#countKelas').text(json?.recordsTotal ?? 0);
            });

            tableTa.on('xhr.dt', function(_event, _settings, json) {
                $('#countTahunAjaran').text(json?.recordsTotal ?? 0);
            });

            tableSemester.on('xhr.dt', function(_event, _settings, json) {
                $('#countSemester').text(json?.recordsTotal ?? 0);
            });

            document
                .querySelectorAll('[data-coreui-toggle="pill"]')
                .forEach(function(tabButton) {
                    tabButton.addEventListener(
                        'shown.coreui.tab',
                        function() {
                            window.setTimeout(function() {
                                $.fn.dataTable
                                    .tables({
                                        visible: true,
                                        api: true
                                    })
                                    .columns
                                    .adjust();
                            }, 80);
                        }
                    );
                });

            bindDateRange(
                '#ta_tanggal_mulai',
                '#ta_tanggal_selesai'
            );

            bindDateRange(
                '#semester_tanggal_mulai',
                '#semester_tanggal_selesai'
            );

            /*
            |--------------------------------------------------------------------------
            | Tombol Tambah
            |--------------------------------------------------------------------------
            */
            $('#btnAddKelas').on('click', function() {
                resetForm('#formKelas');

                $('#kelas_id').val('');
                $('#modalKelasTitle').text('Tambah Kelas');

                setSubmitLabel(
                    '#formKelas',
                    'Simpan Kelas'
                );

                modalKelas.show();
            });

            $('#btnAddTa').on('click', function() {
                resetForm('#formTa');

                $('#ta_id').val('');
                $('#modalTaTitle').text('Tambah Tahun Ajaran');

                setSubmitLabel(
                    '#formTa',
                    'Simpan Tahun Ajaran'
                );

                modalTa.show();
            });

            $('#btnAddSemester').on('click', async function() {
                resetForm('#formSemester');

                $('#semester_id').val('');
                $('#modalSemesterTitle').text(
                    'Tambah Semester Draft'
                );

                setSubmitLabel(
                    '#formSemester',
                    'Simpan Semester Draft'
                );

                modalSemester.show();

                await loadTahunAjaranOptions();
            });

            /*
            |--------------------------------------------------------------------------
            | Tombol Edit
            |--------------------------------------------------------------------------
            */
            $(document).on(
                'click',
                '.btn-edit-kelas',
                function() {
                    const data = this.dataset;

                    resetForm('#formKelas');

                    $('#kelas_id').val(data.id ?? '');
                    $('#nama_kelas').val(data.nama ?? '');
                    $('#deskripsi').val(data.deskripsi ?? '');
                    $('#modalKelasTitle').text('Edit Kelas');

                    setSubmitLabel(
                        '#formKelas',
                        'Simpan Perubahan'
                    );

                    modalKelas.show();
                }
            );

            $(document).on(
                'click',
                '.btn-edit-ta',
                function() {
                    const data = this.dataset;

                    resetForm('#formTa');

                    $('#ta_id').val(data.id ?? '');
                    $('#ta_nama').val(data.nama ?? '');
                    $('#ta_tanggal_mulai').val(data.mulai ?? '');
                    $('#ta_tanggal_selesai').val(data.selesai ?? '');
                    $('#ta_tanggal_selesai')
                        .attr('min', data.mulai ?? '');

                    $('#ta_is_active')
                        .prop(
                            'checked',
                            Number(data.active) === 1
                        );

                    $('#modalTaTitle').text(
                        'Edit Tahun Ajaran'
                    );

                    setSubmitLabel(
                        '#formTa',
                        'Simpan Perubahan'
                    );

                    modalTa.show();
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Edit Semester Draft
            |--------------------------------------------------------------------------
            |
            | Controller hanya membuat tombol ini untuk semester draft.
            |
            */
            $(document).on(
                'click',
                '.btn-edit-semester',
                async function() {
                    const data = this.dataset;

                    resetForm('#formSemester');

                    $('#semester_id').val(data.id ?? '');
                    $('#semester_nama').val(data.nama ?? '');
                    $('#semester_tanggal_mulai').val(data.mulai ?? '');
                    $('#semester_tanggal_selesai').val(
                        data.selesai ?? ''
                    );

                    $('#semester_tanggal_selesai')
                        .attr('min', data.mulai ?? '');

                    $('#modalSemesterTitle').text(
                        'Edit Semester Draft'
                    );

                    setSubmitLabel(
                        '#formSemester',
                        'Simpan Perubahan'
                    );

                    modalSemester.show();

                    await loadTahunAjaranOptions(
                        data.taId ?? ''
                    );
                }
            );

            /*
            |--------------------------------------------------------------------------
            | AJAX Form CRUD
            |--------------------------------------------------------------------------
            */
            bindAjaxForm({
                formSelector: '#formKelas',
                baseUrl: endpoints.kelas,
                modal: modalKelas,
                table: tableKelas
            });

            bindAjaxForm({
                formSelector: '#formTa',
                baseUrl: endpoints.tahunAjaran,
                modal: modalTa,
                table: tableTa,
                afterSuccess: function() {
                    tableSemester.ajax.reload(null, false);
                }
            });

            bindAjaxForm({
                formSelector: '#formSemester',
                baseUrl: endpoints.semester,
                modal: modalSemester,
                table: tableSemester
            });

            /*
            |--------------------------------------------------------------------------
            | Delete CRUD
            |--------------------------------------------------------------------------
            */
            bindDelete({
                selector: '.btn-delete-kelas',
                baseUrl: endpoints.kelas,
                table: tableKelas
            });

            bindDelete({
                selector: '.btn-delete-ta',
                baseUrl: endpoints.tahunAjaran,
                table: tableTa,
                afterSuccess: function() {
                    tableSemester.ajax.reload(null, false);
                }
            });

            bindDelete({
                selector: '.btn-delete-semester',
                baseUrl: endpoints.semester,
                table: tableSemester
            });

            /*
            |--------------------------------------------------------------------------
            | Lifecycle Semester
            |--------------------------------------------------------------------------
            */
            async function confirmSemesterLifecycle(
                title,
                message
            ) {
                if (window.AppAlert?.warning) {
                    const result = await AppAlert.warning(
                        message,
                        title
                    );

                    return Boolean(result?.isConfirmed);
                }

                if (window.Swal) {
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: title,
                        html: message,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, lanjutkan',
                        cancelButtonText: 'Batal',
                        allowOutsideClick: false
                    });

                    return Boolean(result.isConfirmed);
                }

                return window.confirm(
                    message.replace(/<[^>]*>/g, '')
                );
            }

            async function runSemesterLifecycleAction({
                button,
                endpoint,
                confirmTitle,
                confirmMessage
            }) {
                const confirmed =
                    await confirmSemesterLifecycle(
                        confirmTitle,
                        confirmMessage
                    );

                if (!confirmed) {
                    return;
                }

                const $button = $(button);
                const originalHtml = $button.html();

                $button
                    .prop('disabled', true)
                    .html(
                        '<span class="spinner-border spinner-border-sm" ' +
                        'role="status" aria-hidden="true"></span>'
                    );

                $.ajax({
                        url: endpoint,
                        type: 'POST',
                        data: {
                            _method: 'PATCH',
                            _token: csrfToken
                        }
                    })
                    .done(function(response) {
                        tableSemester.ajax.reload(
                            null,
                            false
                        );

                        notifySuccess(
                            response.message ??
                            'Lifecycle semester berhasil diperbarui.'
                        );
                    })
                    .fail(function(xhr) {
                        notifyError(
                            getErrorMessage(xhr)
                        );
                    })
                    .always(function() {
                        $button
                            .prop('disabled', false)
                            .html(originalHtml);
                    });
            }

            $(document).on(
                'click',
                '.btn-lock-semester-input',
                function() {
                    const id = this.dataset.id;

                    runSemesterLifecycleAction({
                        button: this,
                        endpoint: `${endpoints.semester}/${id}/lock-input`,
                        confirmTitle: 'Kunci Input Semester?',
                        confirmMessage: 'Musyrif tidak akan dapat menambah, mengubah, ' +
                            'atau menghapus data <b>Hafalan, Tahsin, dan Tilawah</b> ' +
                            'sampai input semester dibuka kembali.'
                    });
                }
            );

            $(document).on(
                'click',
                '.btn-unlock-semester-input',
                function() {
                    const id = this.dataset.id;

                    runSemesterLifecycleAction({
                        button: this,
                        endpoint: `${endpoints.semester}/${id}/unlock-input`,
                        confirmTitle: 'Buka Kembali Input Semester?',
                        confirmMessage: 'Musyrif akan kembali dapat menambah, mengubah, ' +
                            'dan menghapus data akademik pada semester aktif.'
                    });
                }
            );

            $(document).on(
                'click',
                '.btn-activate-semester',
                function() {
                    const id = this.dataset.id;
                    const label =
                        this.dataset.label ??
                        'semester ini';

                    runSemesterLifecycleAction({
                        button: this,
                        endpoint: `${endpoints.semester}/${id}/activate`,
                        confirmTitle: 'Aktifkan Semester Baru?',
                        confirmMessage: `Aktifkan <b>${escapeHtml(label)}</b>?<br><br>` +
                            'Semester aktif sebelumnya akan otomatis ditutup. ' +
                            'Pastikan input semester lama sudah dikunci dan ' +
                            'migrasi kelas telah selesai.'
                    });
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Backfill Placement Semester
            |--------------------------------------------------------------------------
            */
            const backfillState = {
                preview: null,
                running: false
            };

            function hasPlacementBackfillEndpoint() {
                return Boolean(
                    endpoints.placementBackfillPreview &&
                    endpoints.placementBackfillProcess
                );
            }

            function setPlacementBackfillLoading(loading) {
                [
                    '#btnRunPlacementBackfill',
                    '#btnRefreshBackfillPreview',
                    '#btnClosePlacementBackfill',
                    '#btnDismissPlacementBackfill'
                ].forEach(function(selector) {
                    $(selector).prop('disabled', loading);
                });

                if (!loading) {
                    const missing = Number(
                        backfillState.preview?.summary?.missing ??
                        0
                    );

                    $('#btnRunPlacementBackfill')
                        .prop('disabled', missing <= 0);
                }
            }

            function resetPlacementBackfillProgress() {
                $('#backfillProgressContainer').addClass('d-none');
                $('#backfillProgressBar')
                    .css('width', '0%')
                    .attr('aria-valuenow', '0');
                $('#backfillProgressText').text('0%');
                $('#backfillProcessInfo').text('Menunggu proses...');
            }

            function renderPlacementBackfillWarnings(summary) {
                const warnings = [];
                const missing = Number(summary?.missing ?? 0);
                const mismatch = Number(summary?.mismatch ?? 0);

                if (missing === 0) {
                    warnings.push(
                        'Seluruh santri aktif sudah mempunyai placement pada semester aktif.'
                    );
                }

                if (mismatch > 0) {
                    warnings.push(
                        `${mismatch} placement existing berbeda dengan kelas atau musyrif pada tabel santris. Backfill tidak akan menimpa data tersebut.`
                    );
                }

                const warningBox = $('#backfillWarningBox');
                const warningList = $('#backfillWarningList');
                warningList.empty();

                if (warnings.length === 0) {
                    warningBox.addClass('d-none');
                    return;
                }

                warnings.forEach(function(message) {
                    $('<li>', {
                        text: message
                    }).appendTo(warningList);
                });

                warningBox.removeClass('d-none');
            }

            async function parsePlacementBackfillResponse(response) {
                const contentType = response.headers.get('content-type') || '';
                const payload = contentType.includes('application/json') ?
                    await response.json() : {
                        message: await response.text()
                    };

                if (!response.ok) {
                    const validationMessage = Object
                        .values(payload.errors || {})
                        .flat()
                        .at(0);

                    throw new Error(
                        validationMessage ||
                        payload.message ||
                        'Request gagal diproses.'
                    );
                }

                return payload;
            }

            async function loadPlacementBackfillPreview() {
                if (!hasPlacementBackfillEndpoint() || backfillState.running) {
                    return;
                }

                setPlacementBackfillLoading(true);
                resetPlacementBackfillProgress();
                $('#backfillSemesterLabel').text('Memuat data...');

                try {
                    const response = await fetch(
                        endpoints.placementBackfillPreview, {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin'
                        }
                    );

                    const json = await parsePlacementBackfillResponse(response);
                    backfillState.preview = json;

                    const semesterName = json.semester?.nama ?? '-';
                    const academicYear = json.semester?.tahun_ajaran ?? '-';

                    $('#backfillSemesterLabel')
                        .text(`${semesterName} — ${academicYear}`);
                    $('#backfillTotal')
                        .text(Number(json.summary?.total_santri ?? 0));
                    $('#backfillExisting')
                        .text(Number(json.summary?.existing ?? 0));
                    $('#backfillMissing')
                        .text(Number(json.summary?.missing ?? 0));
                    $('#backfillMismatch')
                        .text(Number(json.summary?.mismatch ?? 0));

                    renderPlacementBackfillWarnings(json.summary);
                } catch (error) {
                    backfillState.preview = null;
                    notifyError(
                        error.message ||
                        'Preview backfill gagal dimuat.'
                    );
                } finally {
                    setPlacementBackfillLoading(false);
                }
            }

            async function confirmPlacementBackfill(missingTotal) {
                const message =
                    `Sistem akan membuat <b>${missingTotal}</b> placement yang belum tersedia pada semester aktif.<br><br>` +
                    'Placement existing tidak akan ditimpa.';

                if (window.AppAlert?.warning) {
                    const result = await AppAlert.warning(
                        message,
                        'Jalankan Backfill Placement?'
                    );

                    return Boolean(result?.isConfirmed);
                }

                if (window.Swal) {
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: 'Jalankan Backfill Placement?',
                        html: message,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Jalankan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#6f42c1',
                        allowOutsideClick: false
                    });

                    return Boolean(result.isConfirmed);
                }

                return window.confirm(
                    `Jalankan backfill untuk ${missingTotal} santri?`
                );
            }

            async function runPlacementBackfill() {
                if (
                    backfillState.running ||
                    !backfillState.preview ||
                    !hasPlacementBackfillEndpoint()
                ) {
                    return;
                }

                const missingTotal = Number(
                    backfillState.preview?.summary?.missing ??
                    0
                );

                if (missingTotal <= 0) {
                    notifySuccess('Tidak ada placement yang perlu dibuat.');
                    return;
                }

                const confirmed = await confirmPlacementBackfill(missingTotal);
                if (!confirmed) {
                    return;
                }

                backfillState.running = true;
                setPlacementBackfillLoading(true);
                $('#backfillProgressContainer').removeClass('d-none');

                let lastId = 0;
                let createdTotal = 0;
                let skippedTotal = 0;
                let processedTotal = 0;

                try {
                    while (true) {
                        const response = await fetch(
                            endpoints.placementBackfillProcess, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify({
                                    last_id: lastId
                                })
                            }
                        );

                        const json = await parsePlacementBackfillResponse(response);

                        createdTotal += Number(json.created ?? 0);
                        skippedTotal += Number(json.skipped ?? 0);
                        processedTotal += Number(json.processed ?? 0);
                        lastId = Number(json.next_last_id ?? lastId);

                        const percentage = Math.min(
                            100,
                            Math.round((createdTotal / missingTotal) * 100)
                        );

                        $('#backfillProgressBar')
                            .css('width', `${percentage}%`)
                            .attr('aria-valuenow', String(percentage));
                        $('#backfillProgressText').text(`${percentage}%`);
                        $('#backfillProcessInfo').text(
                            `${createdTotal} placement dibuat, ` +
                            `${skippedTotal} dilewati, ` +
                            `${processedTotal} record diproses.`
                        );

                        if (json.done) {
                            break;
                        }

                        await new Promise(function(resolve) {
                            window.setTimeout(resolve, 150);
                        });
                    }

                    $('#backfillProgressBar')
                        .css('width', '100%')
                        .attr('aria-valuenow', '100');
                    $('#backfillProgressText').text('100%');

                    notifySuccess(
                        `${createdTotal} placement berhasil dibuat.`
                    );

                    await loadPlacementBackfillPreview();
                } catch (error) {
                    notifyError(
                        error.message ||
                        'Proses backfill gagal.'
                    );
                } finally {
                    backfillState.running = false;
                    setPlacementBackfillLoading(false);
                }
            }

            placementBackfillElement?.addEventListener(
                'shown.coreui.modal',
                function() {
                    loadPlacementBackfillPreview();
                }
            );

            $('#btnRefreshBackfillPreview').on(
                'click',
                function() {
                    loadPlacementBackfillPreview();
                }
            );

            $('#btnRunPlacementBackfill').on(
                'click',
                function() {
                    runPlacementBackfill();
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Generic AJAX Form
            |--------------------------------------------------------------------------
            */
            function bindAjaxForm({
                formSelector,
                baseUrl,
                modal,
                table,
                afterSuccess = null
            }) {
                $(formSelector).on(
                    'submit',
                    function(event) {
                        event.preventDefault();

                        const $form = $(this);
                        const id =
                            $form.find('[name="id"]').val();

                        const url = id ?
                            `${baseUrl}/${id}` :
                            baseUrl;

                        const payload =
                            $form.serializeArray();

                        if (id) {
                            payload.push({
                                name: '_method',
                                value: 'PUT'
                            });
                        }

                        clearValidationErrors($form);
                        setFormLoading($form, true);

                        $.ajax({
                                url: url,
                                type: 'POST',
                                data: $.param(payload)
                            })
                            .done(function(response) {
                                modal.hide();

                                table.ajax.reload(
                                    null,
                                    false
                                );

                                if (
                                    typeof afterSuccess ===
                                    'function'
                                ) {
                                    afterSuccess(response);
                                }

                                notifySuccess(
                                    response.message ??
                                    'Data berhasil disimpan.'
                                );
                            })
                            .fail(function(xhr) {
                                if (
                                    xhr.status === 422 &&
                                    xhr.responseJSON?.errors
                                ) {
                                    showValidationErrors(
                                        $form,
                                        xhr.responseJSON.errors
                                    );
                                }

                                notifyError(
                                    getErrorMessage(xhr)
                                );
                            })
                            .always(function() {
                                setFormLoading(
                                    $form,
                                    false
                                );
                            });
                    }
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Generic Delete
            |--------------------------------------------------------------------------
            */
            function bindDelete({
                selector,
                baseUrl,
                table,
                afterSuccess = null
            }) {
                $(document).on(
                    'click',
                    selector,
                    async function() {
                        const id = this.dataset.id;
                        const label =
                            this.dataset.label ??
                            'data ini';

                        const confirmed =
                            await confirmDelete(label);

                        if (!confirmed) {
                            return;
                        }

                        const $button = $(this);
                        const originalHtml = $button.html();

                        $button
                            .prop('disabled', true)
                            .html(
                                '<span class="spinner-border spinner-border-sm"></span>'
                            );

                        $.ajax({
                                url: `${baseUrl}/${id}`,
                                type: 'POST',
                                data: {
                                    _method: 'DELETE',
                                    _token: csrfToken
                                }
                            })
                            .done(function(response) {
                                table.ajax.reload(
                                    null,
                                    false
                                );

                                if (
                                    typeof afterSuccess ===
                                    'function'
                                ) {
                                    afterSuccess(response);
                                }

                                notifySuccess(
                                    response.message ??
                                    'Data berhasil dihapus.'
                                );
                            })
                            .fail(function(xhr) {
                                notifyError(
                                    getErrorMessage(xhr)
                                );
                            })
                            .always(function() {
                                $button
                                    .prop('disabled', false)
                                    .html(originalHtml);
                            });
                    }
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Tahun Ajaran Options untuk Modal Semester
            |--------------------------------------------------------------------------
            */
            async function loadTahunAjaranOptions(
                selectedId = ''
            ) {
                const $select =
                    $('#semester_ta_id');

                $select
                    .prop('disabled', true)
                    .empty()
                    .append(
                        new Option(
                            'Memuat tahun ajaran...',
                            ''
                        )
                    );

                try {
                    const response = await $.ajax({
                        url: endpoints.tahunAjaranOptions,
                        type: 'GET',
                        dataType: 'json'
                    });

                    $select
                        .empty()
                        .append(
                            new Option(
                                '-- Pilih Tahun Ajaran --',
                                ''
                            )
                        );

                    (response.data ?? [])
                    .forEach(function(item) {
                        const suffix =
                            Number(item.is_active) === 1 ?
                            ' • Aktif' :
                            '';

                        const option = new Option(
                            `${item.nama}${suffix}`,
                            item.id
                        );

                        option.selected =
                            String(item.id) ===
                            String(selectedId);

                        $select.append(option);
                    });

                    if ((response.data ?? []).length === 0) {
                        $select
                            .empty()
                            .append(
                                new Option(
                                    'Belum ada tahun ajaran',
                                    ''
                                )
                            );
                    }
                } catch (error) {
                    $select
                        .empty()
                        .append(
                            new Option(
                                'Gagal memuat tahun ajaran',
                                ''
                            )
                        );

                    notifyError(
                        'Pilihan tahun ajaran gagal dimuat.'
                    );
                } finally {
                    $select.prop('disabled', false);
                }
            }

            function bindDateRange(
                startSelector,
                endSelector
            ) {
                $(startSelector).on(
                    'change',
                    function() {
                        const startDate =
                            $(this).val();

                        const $end =
                            $(endSelector);

                        $end.attr(
                            'min',
                            startDate || null
                        );

                        if (
                            startDate &&
                            $end.val() &&
                            $end.val() <= startDate
                        ) {
                            $end.val('');
                        }
                    }
                );
            }

            function resetForm(formSelector) {
                const form =
                    document.querySelector(
                        formSelector
                    );

                form.reset();

                const $form = $(form);

                clearValidationErrors($form);

                $form
                    .find(
                        'input[type="hidden"][name="id"]'
                    )
                    .val('');

                $form
                    .find('input[type="date"]')
                    .removeAttr('min');
            }

            function clearValidationErrors($form) {
                $form
                    .find('.is-invalid')
                    .removeClass('is-invalid');

                $form
                    .find(
                        '.invalid-feedback.dynamic-error'
                    )
                    .remove();
            }

            function showValidationErrors(
                $form,
                errors
            ) {
                Object
                    .entries(errors)
                    .forEach(function([
                        fieldName,
                        messages
                    ]) {
                        const $field =
                            $form
                            .find(
                                `[name="${fieldName}"]`
                            )
                            .first();

                        if (!$field.length) {
                            return;
                        }

                        $field.addClass(
                            'is-invalid'
                        );

                        $('<div>', {
                            class: 'invalid-feedback dynamic-error',
                            text: Array.isArray(messages) ?
                                messages[0] : messages
                        }).insertAfter($field);
                    });

                $form
                    .find('.is-invalid')
                    .first()
                    .trigger('focus');
            }

            function setFormLoading(
                $form,
                loading
            ) {
                const $submit =
                    $form.find(
                        'button[type="submit"]'
                    );

                const defaultText =
                    $submit.attr(
                        'data-submit-text'
                    ) ?? 'Simpan';

                $submit.prop(
                    'disabled',
                    loading
                );

                if (loading) {
                    $submit.html(
                        '<span class="spinner-border spinner-border-sm me-2" ' +
                        'role="status"></span>Menyimpan...'
                    );
                } else {
                    const currentText =
                        $submit.data('current-text') ??
                        defaultText;

                    $submit.html(
                        '<span class="submit-label">' +
                        '<i class="bi bi-check2-circle me-1"></i> ' +
                        escapeHtml(currentText) +
                        '</span>'
                    );
                }

                $form
                    .find(
                        'button[data-coreui-dismiss="modal"]'
                    )
                    .prop('disabled', loading);
            }

            function setSubmitLabel(
                formSelector,
                text
            ) {
                const $submit =
                    $(
                        `${formSelector} button[type="submit"]`
                    );

                $submit.data(
                    'current-text',
                    text
                );

                $submit.html(
                    '<span class="submit-label">' +
                    '<i class="bi bi-check2-circle me-1"></i> ' +
                    escapeHtml(text) +
                    '</span>'
                );
            }

            async function confirmDelete(label) {
                if (window.AppAlert?.warning) {
                    const result =
                        await AppAlert.warning(
                            `${label} akan dihapus permanen.`,
                            'Konfirmasi Hapus'
                        );

                    return Boolean(
                        result?.isConfirmed
                    );
                }

                if (window.Swal) {
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: 'Konfirmasi Hapus',
                        text: `${label} akan dihapus permanen.`,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    });

                    return Boolean(
                        result.isConfirmed
                    );
                }

                return window.confirm(
                    `Hapus ${label}?`
                );
            }

            function notifySuccess(message) {
                if (window.AppAlert?.success) {
                    AppAlert.success(message);
                    return;
                }

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: message
                    });

                    return;
                }

                window.alert(message);
            }

            function notifyError(message) {
                if (window.AppAlert?.error) {
                    AppAlert.error(message);
                    return;
                }

                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: message
                    });

                    return;
                }

                window.alert(message);
            }

            function getErrorMessage(xhr) {
                if (
                    xhr.status === 422 &&
                    xhr.responseJSON?.errors
                ) {
                    return Object
                        .values(
                            xhr.responseJSON.errors
                        )
                        .flat()
                        .join('\n');
                }

                return xhr.responseJSON?.message ??
                    'Terjadi kesalahan saat memproses permintaan.';
            }

            function stripHtml(value) {
                const temporary =
                    document.createElement('div');

                temporary.innerHTML = value;

                return temporary.textContent ||
                    temporary.innerText ||
                    '';
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }
        });
    </script>
@endpush
