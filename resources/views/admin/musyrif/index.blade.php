@extends('layouts.app')

@section('title', 'Data Musyrif')

@section('content')
    <style>
        /* ================= KONSISTENSI STYLING ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
            transition: color 0.3s ease;
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ffffff !important;
        }

        /* Form Controls UI */
        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.6rem 1rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--cui-secondary-color);
        }

        /* Fix Jarak Header Tabel (Search & Length Menu) */
        .dataTables_wrapper .row:first-child {
            padding: 1.5rem 1.5rem 0.5rem 1.5rem;
            margin: 0;
        }

        /* Fix Jarak Pagination Tabel */
        .dataTables_wrapper .row:last-child {
            padding: 1rem 1.5rem;
            margin: 0;
            border-top: 1px solid var(--cui-border-color);
        }

        .dataTables_wrapper .dataTables_paginate {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        /* Styling Khusus Checkbox */
        .form-check-input:checked {
            background-color: var(--islamic-purple-600);
            border-color: var(--islamic-purple-600);
        }

        .dataTables_wrapper .dataTables_length select {
            min-width: 75px !important;
            /* Memperlebar kotak agar angka tidak terjepit */
            padding-right: 1.8rem !important;
            /* Memberi ruang khusus untuk ikon panah di kanan */
            margin: 0 0.4rem !important;
            /* Memberi jarak manis antara teks "Tampil" dan "data" */
            display: inline-block !important;
        }

        #tablePreview {
            display: block;
            max-height: 300px;
            overflow-y: auto;
            font-size: 0.75rem;
        }

        #tablePreview th {
            position: sticky;
            top: 0;
            background: var(--cui-tertiary-bg);
            z-index: 1;
        }

        #importMappingBody tr:hover {
            background-color: var(--cui-tertiary-bg);
        }

        .bg-light-subtle {
            background-color: rgba(var(--cui-light-rgb), 0.5) !important;
        }

        #importPreviewBody td {
            white-space: nowrap;
        }

        .invalid-feedback {
            display: block;
            /* Memastikan pesan muncul karena input-group kadang menyembunyikannya */
            font-size: 0.75rem;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }


        /* ================= GENDER FILTER TABS ================= */
        .gender-filter-tabs {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            padding: 0.35rem;
            border-radius: 999px;
            background: var(--cui-tertiary-bg, #f8f9fa);
            border: 1px solid var(--cui-border-color);
        }

        .gender-filter-tabs .nav-link {
            border: 0;
            border-radius: 999px;
            padding: 0.55rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--cui-secondary-color);
            background: transparent;
            transition: all 0.2s ease;
        }

        .gender-filter-tabs .nav-link:hover {
            color: var(--islamic-purple-700, #59359d);
            background: rgba(111, 66, 193, 0.08);
        }

        .gender-filter-tabs .nav-link.active {
            color: #ffffff;
            background: var(--islamic-purple-600, #6f42c1);
            box-shadow: 0 10px 24px rgba(89, 53, 157, 0.22);
        }

        [data-coreui-theme="dark"] .gender-filter-tabs {
            background: var(--cui-tertiary-bg);
        }


        /* ================= DRAG & DROP IMPORT EXCEL ================= */
        .import-dropzone {
            position: relative;
            border: 2px dashed rgba(111, 66, 193, 0.35);
            border-radius: 22px;
            padding: 2rem;
            background:
                radial-gradient(circle at top right, rgba(111, 66, 193, 0.08), transparent 34%),
                var(--cui-tertiary-bg, #f8f9fa);
            transition:
                border-color 0.2s ease,
                background 0.2s ease,
                transform 0.2s ease,
                box-shadow 0.2s ease;
            cursor: pointer;
        }

        .import-dropzone:hover,
        .import-dropzone.is-dragover {
            border-color: var(--islamic-purple-600, #6f42c1);
            background:
                radial-gradient(circle at top right, rgba(111, 66, 193, 0.14), transparent 34%),
                rgba(111, 66, 193, 0.04);
            transform: translateY(-2px);
            box-shadow: 0 16px 36px rgba(89, 53, 157, 0.12);
        }

        .import-dropzone-icon {
            width: 76px;
            height: 76px;
            border-radius: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--islamic-purple-700, #59359d);
            background: rgba(111, 66, 193, 0.12);
            font-size: 2.1rem;
        }

        .import-file-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .import-file-name {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            max-width: 100%;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            background: rgba(25, 135, 84, 0.12);
            color: #198754;
            font-size: 0.82rem;
            font-weight: 700;
            word-break: break-word;
        }

        .import-dropzone-note {
            max-width: 620px;
            margin-left: auto;
            margin-right: auto;
        }

        .import-template-hint {
            border: 1px dashed rgba(25, 135, 84, 0.32);
            background: rgba(25, 135, 84, 0.08);
            border-radius: 16px;
            padding: 0.8rem 1rem;
        }

        [data-coreui-theme="dark"] .import-dropzone {
            background:
                radial-gradient(circle at top right, rgba(147, 108, 246, 0.12), transparent 34%),
                var(--cui-tertiary-bg);
            border-color: rgba(216, 198, 255, 0.28);
        }

        [data-coreui-theme="dark"] .import-dropzone-icon {
            color: #d8c6ff;
            background: rgba(147, 108, 246, 0.18);
        }

        /* ================= FLOATING PAGE GUIDE ================= */
        .page-guide-fab {
            position: fixed;
            right: max(30px, env(safe-area-inset-right));
            bottom: max(60px, env(safe-area-inset-bottom));
            z-index: 1035;
            width: 56px;
            height: 56px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 50%;
            color: #ffffff;
            background:
                linear-gradient(135deg,
                    var(--islamic-purple-600, #6f42c1),
                    var(--islamic-purple-700, #59359d));
            box-shadow: 0 12px 30px rgba(89, 53, 157, 0.34);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                filter 0.2s ease;
        }

        .page-guide-fab:hover,
        .page-guide-fab:focus-visible {
            color: #ffffff;
            transform: translateY(-3px) scale(1.03);
            filter: brightness(1.06);
            box-shadow: 0 16px 36px rgba(89, 53, 157, 0.42);
        }

        .page-guide-fab:focus-visible {
            outline: 3px solid rgba(111, 66, 193, 0.24);
            outline-offset: 4px;
        }

        .page-guide-fab i {
            font-size: 1.45rem;
        }

        .page-guide-fab::after {
            content: '';
            position: absolute;
            inset: -5px;
            border: 2px solid rgba(111, 66, 193, 0.22);
            border-radius: inherit;
            animation: pageGuidePulse 2.4s ease-out infinite;
            pointer-events: none;
        }

        @keyframes pageGuidePulse {
            0% {
                transform: scale(0.88);
                opacity: 0;
            }

            30% {
                opacity: 1;
            }

            100% {
                transform: scale(1.28);
                opacity: 0;
            }
        }

        .page-guide-hero {
            position: relative;
            overflow: hidden;
            color: #ffffff;
            background:
                radial-gradient(circle at 92% 10%, rgba(255, 255, 255, 0.18), transparent 24%),
                linear-gradient(135deg,
                    var(--islamic-purple-700, #59359d),
                    var(--islamic-purple-600, #6f42c1));
        }

        .page-guide-hero::after {
            content: '';
            position: absolute;
            right: -40px;
            bottom: -70px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .page-guide-hero>* {
            position: relative;
            z-index: 1;
        }

        .guide-step {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 14px;
            background: var(--cui-body-bg);
        }

        .guide-step-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: var(--islamic-purple-700, #59359d);
            background: rgba(111, 66, 193, 0.12);
            font-size: 1.1rem;
        }

        .guide-action-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.8rem 0;
            border-bottom: 1px dashed var(--cui-border-color);
        }

        .guide-action-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .guide-action-icon {
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        [data-coreui-theme="dark"] .guide-step {
            background: var(--cui-tertiary-bg);
        }

        [data-coreui-theme="dark"] .guide-step-icon {
            color: #d8c6ff;
            background: rgba(147, 108, 246, 0.18);
        }

        @media (max-width: 575.98px) {
            .page-guide-fab {
                right: max(14px, env(safe-area-inset-right));
                bottom: max(14px, env(safe-area-inset-bottom));
                width: 52px;
                height: 52px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .page-guide-fab,
            .page-guide-fab::after {
                animation: none;
                transition: none;
            }
        }
    </style>

    {{-- HEADER PAGE --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Data Musyrif</h4>
            <span class="text-muted small">Kelola daftar pembimbing dan pantau kehadiran harian</span>
        </div>
        <div class="col-12 col-md-auto ms-auto d-flex flex-wrap justify-content-md-end gap-2">
            <a href="{{ route('admin.musyrif.template_import') }}" target="_blank" rel="noopener noreferrer"
                class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold">
                <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Template Excel
            </a>

            <button type="button" class="btn btn-outline-success rounded-pill px-4 shadow-sm fw-bold"
                data-coreui-toggle="modal" data-coreui-target="#modalImport">
                <i class="bi bi-file-earmark-excel me-1"></i> Import Excel
            </button>

            <button type="button" class="btn text-white px-4 rounded-pill shadow-sm fw-bold"
                style="background: var(--islamic-purple-600);" id="btnAddMusyrif">
                <i class="bi bi-plus-lg me-1"></i> Tambah Musyrif
            </button>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            {{-- TABS FILTER GENDER --}}
            <div class="px-3 pt-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                    <div>
                        <ul class="nav gender-filter-tabs" id="genderTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-jk="" type="button">
                                    <i class="bi bi-people-fill me-1"></i> Semua
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-jk="L" type="button">
                                    <i class="bi bi-gender-male me-1"></i> Putra
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-jk="P" type="button">
                                    <i class="bi bi-gender-female me-1"></i> Putri
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="musyrif-table" class="table table-striped table-hover align-middle w-100 mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width:60px;">No.</th>
                            <th>Nama Musyrif</th>
                            <th>Akun (User)</th>
                            <th>Kelas</th>
                            <th>Pagi (Hari ini)</th>
                            <th>Sore (Hari ini)</th>
                            <th>Rekap Bulan Ini</th>
                            <th class="text-end pe-4" style="width:160px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')

    {{-- FLOATING BUTTON: PANDUAN HALAMAN --}}
    <button type="button" class="page-guide-fab" id="btnPageGuide" aria-label="Buka panduan halaman Data Musyrif"
        title="Panduan halaman">
        <i class="bi bi-info-lg" aria-hidden="true"></i>
    </button>


    {{-- ===================== MODAL CREATE & EDIT ===================== --}}
    <div class="modal fade" id="modalMusyrif" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formMusyrif" class="w-100">
                @csrf
                <input type="hidden" id="musyrif_id">
                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4">
                        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="modalMusyrifTitle">
                            <i class="bi bi-person-badge-fill"></i> Tambah Musyrif
                        </h5>
                        <button type="button" class="btn-close bg-light rounded-circle p-2"
                            data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            {{-- BAGIAN 1: IDENTITAS UTAMA --}}
                            <div class="col-md-6">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" id="nama" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kode/NIP</label>
                                <input type="text" class="form-control" name="kode" id="kode">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-select" name="jenis_kelamin" id="jenis_kelamin">
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Putra / Laki-laki</option>
                                    <option value="P">Putri / Perempuan</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tugas di Kelas</label>
                                <select class="form-select" name="kelas_id" id="kelas_id">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($listKelas as $k)
                                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pendidikan Terakhir</label>
                                <select class="form-select" name="pendidikan_terakhir" id="pendidikan_terakhir">
                                    <option value="">-- Pilih --</option>
                                    <option value="SMA">SMA/Sederajat</option>
                                    <option value="D3">D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                </select>
                            </div>

                            {{-- BAGIAN 2: DETAIL TUGAS & DOMISILI --}}
                            <div class="col-md-6">
                                <label class="form-label">Program Halaqah</label>
                                <select class="form-select" name="halaqah" id="halaqah">
                                    <option value="Reguler">Reguler</option>
                                    <option value="Takhassus">Takhassus</option>
                                    <option value="Pengganti">Pengganti</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Domisili</label>
                                <select class="form-select" name="domisili" id="domisili">
                                    <option value="Dalam Pondok (Mukim)">Dalam Pondok (Mukim)</option>
                                    <option value="Luar Pondok (Pulang-Pergi)">Luar Pondok (Pulang-Pergi)</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control" name="alamat" id="alamat" rows="2"></textarea>
                            </div>
                            {{-- BAGIAN BARU: KETERANGAN --}}
                            <div class="col-12">
                                <label class="form-label">Keterangan Tambahan</label>
                                <textarea class="form-control" name="keterangan" id="keterangan" rows="2"
                                    placeholder="Catatan khusus tentang musyrif ini..."></textarea>
                            </div>
                            {{-- BAGIAN 3: SERTIFIKASI --}}
                            <div class="col-md-12">
                                <hr class="my-2">
                                <h6 class="fw-bold text-adaptive-purple mb-3">Informasi Sertifikasi Al-Qur'an</h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode</label>
                                <input type="text" class="form-control" name="metode_alquran" id="metode_alquran"
                                    placeholder="Contoh: Ummi, Wafa">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Sertifikasi</label>
                                <input type="number" class="form-control" name="tahun_sertifikasi"
                                    id="tahun_sertifikasi" placeholder="Contoh: 2024">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_sertifikasi_ummi"
                                        id="is_sertifikasi_ummi" value="1">
                                    <label class="form-check-label small fw-bold" for="is_sertifikasi_ummi">SUDAH
                                        SERTIFIKASI UMMI</label>
                                </div>
                            </div>



                            {{-- AKUN LOGIN SECTION --}}
                            <div class="col-12 mt-2">
                                <div class="bg-light rounded-3 p-3 border border-dashed">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="create_user"
                                            style="cursor: pointer;">
                                        <label class="form-check-label fw-bold small text-adaptive-purple"
                                            for="create_user" style="cursor: pointer;">
                                            PENGATURAN AKUN LOGIN
                                        </label>
                                    </div>

                                    <div id="createUserFields" class="d-none mt-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small">Email Login</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                    <input type="email" class="form-control" name="email"
                                                        id="email" placeholder="email@contoh.com">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small">Password</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                    <input type="password" class="form-control" name="password"
                                                        id="password" placeholder="Min. 8 karakter">
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        id="togglePassword">
                                                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4 rounded-pill"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white px-4 rounded-pill shadow-sm"
                            style="background: var(--islamic-purple-600);" id="btnSaveMusyrif">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL DETAIL PROFIL ===================== --}}
    <div class="modal fade" id="modalDetailMusyrif" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-info text-white border-bottom-0 px-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Detail Profil Musyrif</h5>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="det_id_hidden">
                    <input type="hidden" id="det_kelas_id_hidden">
                    <div class="text-center mb-4">
                        <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-person-badge text-info fs-1"></i>
                        </div>
                        <h4 class="fw-bold mb-0" id="det_nama"></h4>
                        <span class="badge bg-secondary rounded-pill" id="det_kode"></span>
                    </div>

                    <div class="row g-4">
                        {{-- Info Tugas --}}
                        <div class="col-md-6">
                            <h6 class="fw-bold text-info border-bottom pb-2"><i class="bi bi-briefcase me-2"></i>Penugasan
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted small w-50">Halaqah / Program</td>
                                    <td class="fw-bold" id="det_halaqah"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Tugas di Kelas</td>
                                    <td class="fw-bold" id="det_kelas"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Jenis Kelamin</td>
                                    <td class="fw-bold" id="det_jenis_kelamin"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Domisili</td>
                                    <td class="fw-bold" id="det_domisili"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Amanah Lain</td>
                                    <td id="det_amanah"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Keterangan</td>
                                    <td id="det_keterangan"></td>
                                </tr>
                            </table>
                        </div>

                        {{-- Info Sertifikasi --}}
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success border-bottom pb-2"><i
                                    class="bi bi-patch-check me-2"></i>Kualifikasi Al-Qur'an</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted small w-50">Metode</td>
                                    <td class="fw-bold" id="det_metode"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Status Ummi</td>
                                    <td class="fw-bold" id="det_sertifikasi"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Tahun Lulus</td>
                                    <td class="fw-bold" id="det_tahun"></td>
                                </tr>
                            </table>
                        </div>

                        {{-- Info Kontak --}}
                        <div class="col-md-12">
                            <h6 class="fw-bold text-adaptive-purple border-bottom pb-2"><i
                                    class="bi bi-telephone me-2"></i>Kontak & Alamat</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 small text-muted">WhatsApp / Email</p>
                                    <p class="fw-bold mb-0"><i class="bi bi-whatsapp text-success me-1"></i> <span
                                            id="det_nomor"></span></p>
                                    <p class="small text-muted" id="det_email"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 small text-muted">Alamat</p>
                                    <p class="small" id="det_alamat"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill"
                        data-coreui-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-warning text-white px-4 rounded-pill" id="btnEditFromDetail">
                        <i class="bi bi-pencil-fill me-1"></i> Edit Profil
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL IMPORT EXCEL ===================== --}}
    <div class="modal fade" id="modalImport" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 px-4 bg-primary text-white">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Import Data Musyrif</h5>
                        <p class="small mb-0 opacity-75">Upload file Excel dan petakan sheet untuk diimport ke sistem.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    {{-- STEP 1: UPLOAD AREA --}}
                    <div class="mb-4" id="importUploadBox">
                        <form id="formImportUpload">
                            @csrf

                            <div class="import-dropzone text-center" id="importDropzone">
                                <input type="file" class="import-file-hidden" name="file" id="import_file"
                                    accept=".xlsx,.xls,.csv" required>

                                <div class="import-dropzone-icon mb-3">
                                    <i class="bi bi-cloud-arrow-up-fill"></i>
                                </div>

                                <h6 class="fw-bold mb-1">Drag & Drop File Excel di Sini</h6>

                                <p class="text-muted small import-dropzone-note mb-3">
                                    Tarik file <b>.xlsx</b>, <b>.xls</b>, atau <b>.csv</b> ke area ini,
                                    atau klik tombol di bawah untuk memilih file secara manual.
                                </p>

                                <div class="import-template-hint small text-success-emphasis mb-3 mx-auto"
                                    style="max-width: 720px;">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Template import terbaru sudah memakai dropdown untuk field pilihan:
                                    <b>jenis_kelamin</b>, <b>kelas</b>, <b>pendidikan_terakhir</b>,
                                    <b>domisili</b>, <b>halaqah</b>, dan <b>is_sertifikasi_ummi</b>.
                                </div>

                                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                                    <button type="button"
                                        class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold"
                                        id="btnChooseImportFile">
                                        <i class="bi bi-folder2-open me-1"></i> Pilih File
                                    </button>

                                    <a href="{{ route('admin.musyrif.template_import') }}" target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-outline-success btn-sm rounded-pill px-4 fw-bold">
                                        <i class="bi bi-download me-1"></i> Download Template Validasi
                                    </a>
                                </div>

                                <div id="importSelectedFileWrap" class="d-none mb-3">
                                    <span class="import-file-name">
                                        <i class="bi bi-file-earmark-excel-fill"></i>
                                        <span id="importSelectedFileName">Belum ada file</span>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm" type="submit"
                                        id="btnUploadReadSheet">
                                        <i class="bi bi-search me-1"></i> Baca Isi File
                                    </button>

                                    <button class="btn btn-outline-secondary px-4 rounded-pill fw-bold" type="button"
                                        id="btnResetImport">
                                        Reset
                                    </button>
                                </div>

                                <div class="small text-muted mt-3">
                                    Format database: <b>L</b> untuk Putra/Laki-laki, <b>P</b> untuk Putri/Perempuan,
                                    dan sertifikasi Ummi memakai <b>1</b> atau <b>0</b>.
                                </div>
                            </div>
                        </form>
                    </div>

                    <input type="hidden" id="import_file_path">

                    {{-- STEP 2: MAPPING & PREVIEW AREA (Hidden by default) --}}
                    <div id="importMappingArea" style="display:none;" class="animate__animated animate__fadeIn">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-adaptive-purple">
                                <i class="bi bi-list-check me-1"></i> Pilih Sheet & Verifikasi Data
                            </h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold"
                                    id="btnPreviewImport">
                                    <i class="bi bi-eye me-1"></i> Preview
                                </button>
                                <button class="btn btn-success btn-sm text-white rounded-pill px-4 fw-bold shadow-sm"
                                    id="btnProcessImport">
                                    <i class="bi bi-cloud-download me-1"></i> Mulai Import
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive rounded-3 border mb-4">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase fw-bold text-muted">
                                        <th class="ps-3 py-3">Nama Sheet di Excel</th>
                                        <th>Info Baris</th>
                                        <th>Status Header</th>
                                        <th class="pe-3 text-center">Pilih untuk Import</th>
                                    </tr>
                                </thead>
                                <tbody id="importMappingBody">
                                    {{-- Akan diisi via AJAX --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- PREVIEW DATA --}}
                        <div class="mt-4">
                            <h6 class="fw-bold small mb-2 text-uppercase text-muted" style="letter-spacing: 1px;">
                                Preview Data (10 Baris Pertama)
                            </h6>
                            <div class="table-responsive border rounded-4 shadow-sm" style="max-height: 350px;">
                                <table class="table table-sm table-striped small mb-0">
                                    <thead class="sticky-top table-dark shadow-sm">
                                        <tr id="previewHeader">
                                            {{-- Header Dinamis --}}
                                        </tr>
                                    </thead>
                                    <tbody id="importPreviewBody">
                                        <tr>
                                            <td class="text-center py-4 text-muted">Klik "Preview" untuk melihat data
                                                sebelum diimport</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL PANDUAN HALAMAN ===================== --}}
    <div class="modal fade" id="modalPageGuide" tabindex="-1" aria-labelledby="modalPageGuideLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header page-guide-hero border-0 px-4 py-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 52px; height: 52px;">
                            <i class="bi bi-compass-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-white-50 fw-semibold mb-1">Petunjuk Penggunaan</div>
                            <h5 class="modal-title fw-bold mb-1" id="modalPageGuideLabel">
                                Panduan Halaman Data Musyrif
                            </h5>
                            <p class="small text-white-75 mb-0">
                                Gunakan halaman ini untuk mengelola profil, penugasan, akun, dan pemantauan kehadiran
                                musyrif.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-4 d-flex align-items-start gap-3 mb-4">
                        <i class="bi bi-lightbulb-fill fs-5 mt-1"></i>
                        <div>
                            <div class="fw-bold mb-1">Alur yang disarankan</div>
                            <div class="small">
                                Tambahkan atau import data musyrif, tentukan kelas dan program halaqah,
                                lengkapi akun login bila diperlukan, kemudian pantau kehadiran dan rekap bulanannya.
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-adaptive-purple mb-3">
                        <i class="bi bi-grid-1x2-fill me-2"></i>Fitur Utama
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon">
                                        <i class="bi bi-person-plus-fill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold mb-1">Tambah musyrif</div>
                                        <p class="text-muted small mb-0">
                                            Klik <b>Tambah Musyrif</b> untuk mengisi identitas, kelas tugas,
                                            pendidikan, program halaqah, domisili, alamat, dan keterangan tambahan.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon">
                                        <i class="bi bi-mortarboard-fill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold mb-1">Data sertifikasi</div>
                                        <p class="text-muted small mb-0">
                                            Catat metode Al-Qur'an, tahun sertifikasi, serta status sertifikasi Ummi
                                            agar kualifikasi pembimbing terdokumentasi.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon">
                                        <i class="bi bi-shield-lock-fill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold mb-1">Akun login</div>
                                        <p class="text-muted small mb-0">
                                            Aktifkan <b>Pengaturan Akun Login</b> untuk membuat atau memperbarui
                                            email dan password akses musyrif.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon">
                                        <i class="bi bi-calendar-check-fill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold mb-1">Pantau kehadiran</div>
                                        <p class="text-muted small mb-0">
                                            Kolom Pagi, Sore, dan Rekap Bulan Ini menampilkan status kehadiran
                                            harian serta ringkasan aktivitas musyrif.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon">
                                        <i class="bi bi-file-earmark-excel-fill"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold mb-1">Import Excel</div>
                                        <p class="text-muted small mb-0">
                                            Download template resmi terlebih dahulu, isi sheet <b>Data Musyrif</b>,
                                            upload file, lakukan preview, lalu jalankan import setelah data diverifikasi.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <div>
                                        <div class="fw-bold mb-1">Pencarian dan tabel</div>
                                        <p class="text-muted small mb-0">
                                            Gunakan pencarian DataTables untuk mencari nama musyrif.
                                            Jumlah data per halaman dapat diubah melalui pilihan <b>Tampil</b>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-adaptive-purple mb-2">
                        <i class="bi bi-mouse2-fill me-2"></i>Arti Tombol pada Kolom Aksi
                    </h6>

                    <div class="border rounded-4 px-3 pb-3">
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-primary-subtle text-primary">
                                <i class="bi bi-calendar2-week-fill"></i>
                            </span>
                            <div>
                                <div class="fw-bold small">Riwayat kehadiran</div>
                                <div class="text-muted small">
                                    Membuka data kehadiran musyrif untuk pemeriksaan atau tindak lanjut.
                                </div>
                            </div>
                        </div>

                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-info-subtle text-info">
                                <i class="bi bi-eye-fill"></i>
                            </span>
                            <div>
                                <div class="fw-bold small">Detail profil</div>
                                <div class="text-muted small">
                                    Menampilkan profil lengkap, penugasan, sertifikasi, kontak, dan alamat musyrif.
                                </div>
                            </div>
                        </div>

                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-warning-subtle text-warning">
                                <i class="bi bi-pencil-square"></i>
                            </span>
                            <div>
                                <div class="fw-bold small">Edit data</div>
                                <div class="text-muted small">
                                    Mengubah identitas, kelas tugas, akun login, sertifikasi, dan informasi lainnya.
                                </div>
                            </div>
                        </div>

                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-danger-subtle text-danger">
                                <i class="bi bi-trash-fill"></i>
                            </span>
                            <div>
                                <div class="fw-bold small">Hapus musyrif</div>
                                <div class="text-muted small">
                                    Menghapus data musyrif. Gunakan secara hati-hati karena dapat memengaruhi
                                    relasi kelas, santri binaan, dan riwayat kehadiran.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 rounded-4 small mt-4 mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Pastikan kelas tugas dan akun login sudah benar sebelum menyimpan.
                        Periksa hasil preview sebelum menjalankan import Excel.
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-coreui-dismiss="modal">
                        Tutup
                    </button>
                    <button type="button" class="btn text-white rounded-pill px-4"
                        style="background: var(--islamic-purple-600);" data-coreui-dismiss="modal">
                        <i class="bi bi-check-circle-fill me-1"></i> Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>

@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ==========================================
            // 1. INISIALISASI KOMPONEN & VARIABLE
            // ==========================================
            function getModalInstance(modalId) {
                if (!window.coreui?.Modal) {
                    console.error('CoreUI Modal belum dimuat. Periksa urutan pemuatan JavaScript CoreUI.');
                    return null;
                }

                const element = document.getElementById(modalId);

                if (!element) {
                    console.error(
                        `Elemen modal #${modalId} tidak ditemukan. Pastikan layout menampilkan stack modal.`);
                    return null;
                }

                return window.coreui.Modal.getOrCreateInstance(element);
            }

            const modalMusyrif = getModalInstance('modalMusyrif');
            const modalDetail = getModalInstance('modalDetailMusyrif');
            const modalImport = getModalInstance('modalImport');
            const modalPageGuide = getModalInstance('modalPageGuide');

            $('#btnPageGuide').on('click', function(e) {
                e.preventDefault();
                modalPageGuide?.show();
            });

            let tempPath = ''; // Menyimpan path file sementara untuk import
            let selectedGender = '';

            // Init DataTable
            const table = $('#musyrif-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('admin.musyrif.data') }}",
                    data: function(d) {
                        d.jenis_kelamin = selectedGender;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'nama',
                        name: 'musyrifs.nama'
                    },
                    {
                        data: 'akun',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kelas',
                        name: 'musyrifs.kelas_id',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'absen_pagi',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'absen_sore',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'rekap_bulan',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari musyrif...",
                    lengthMenu: "Tampil _MENU_ data",
                    info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ musyrif",
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });

            // Filter Gender logic
            $('#genderTabs').on('click', '.nav-link', function() {
                $('#genderTabs .nav-link').removeClass('active');
                $(this).addClass('active');

                selectedGender = $(this).data('jk') || '';
                table.ajax.reload();
            });

            // ==========================================
            // 2. FUNGSI HELPER
            // ==========================================
            function resetForm() {
                const form = document.getElementById('formMusyrif');

                if (form) {
                    form.reset();
                }

                $('#musyrif_id').val('');
                $('#modalMusyrifTitle').html('<i class="bi bi-person-badge-fill"></i> Tambah Musyrif');
                $('#btnSaveMusyrif').prop('disabled', false).text('Simpan Musyrif');
                $('#keterangan').val('');
                $('#jenis_kelamin').val('');
                $('#create_user').prop('checked', false);
                $('#createUserFields').stop(true, true).addClass('d-none').hide();
                $('#email').val('');
                $('#password')
                    .val('')
                    .attr('type', 'password')
                    .attr('placeholder', 'Min. 8 karakter');
                $('#eyeIcon').removeClass('bi-eye').addClass('bi-eye-slash');

                $('.invalid-feedback').remove();
                $('.form-control, .form-select').removeClass('is-invalid');
            }

            $('#btnAddMusyrif').on('click', function(e) {
                e.preventDefault();

                resetForm();
                modalMusyrif?.show();
            });

            function loadPreview(index) {
                const previewContainer = $('#tablePreview');
                // Berikan feedback loading yang manis
                previewContainer.html(
                    '<tr><td colspan="100%" class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data...</td></tr>'
                );

                $.post("{{ route('admin.musyrif.sheet_preview') }}", {
                    _token: "{{ csrf_token() }}",
                    temp_path: tempPath,
                    sheet_index: index
                }).done(function(res) {
                    if (!res.headers || res.headers.length === 0) {
                        previewContainer.html(
                            '<tr><td colspan="100%" class="text-center p-4 text-danger">Sheet kosong atau tidak ada header</td></tr>'
                        );
                        return;
                    }

                    // 1. Render Header dengan pengaman tipe data
                    let html = '<thead class="table-light"><tr>';
                    res.headers.forEach(h => {
                        // Pastikan h dikonversi ke string sebelum toUpperCase
                        let headerText = (h !== null && h !== undefined) ? String(h) :
                            'KOLOM TANPA NAMA';
                        html +=
                            `<th class="text-nowrap small fw-bold">${headerText.toUpperCase().replace(/_/g, ' ')}</th>`;
                    });
                    html += '</tr></thead><tbody>';

                    // 2. Render Body Data
                    if (res.preview && res.preview.length > 0) {
                        res.preview.forEach(row => {
                            html += '<tr>';
                            res.headers.forEach(key => {
                                // Ambil nilai, jika null tampilkan tanda strip
                                let val = (row[key] !== null && row[key] !== undefined) ?
                                    row[key] : '-';
                                html += `<td class="small text-nowrap">${val}</td>`;
                            });
                            html += '</tr>';
                        });
                    } else {
                        html +=
                            `<tr><td colspan="${res.headers.length}" class="text-center p-3 text-muted">Tidak ada data di sheet ini</td></tr>`;
                    }

                    html += '</tbody>';
                    previewContainer.html(html);
                }).fail(function(xhr) {
                    let errorMsg = xhr.responseJSON?.message || 'Gagal memuat preview sheet';
                    previewContainer.html(
                        `<tr><td colspan="100%" class="text-center p-4 text-danger"><i class="bi bi-exclamation-triangle me-2"></i> ${errorMsg}</td></tr>`
                    );
                });
            }

            // ==========================================
            // 3. EVENT HANDLERS (CRUD)
            // ==========================================
            $(document).on('click', '.btnDetail', function() {
                const id = $(this).data('id');

                $.get("{{ route('admin.musyrif.show', ':id') }}".replace(':id', id))
                    .done(function(res) {
                        // Mapping Data ke Modal Detail
                        $('#det_id_hidden').val(res.id);
                        $('#det_kelas_id_hidden').val(res.kelas_id); // Simpan juga kelasnya
                        $('#det_nama').text(res.nama);
                        $('#det_kode').text(res.kode);

                        // PAKAI res.nama_kelas
                        $('#det_kelas').text(res.nama_kelas);
                        $('#det_jenis_kelamin').text(res.jenis_kelamin_label || '-');

                        $('#det_halaqah').text(res.halaqah);
                        $('#det_domisili').text(res.domisili);
                        $('#det_amanah').text(res.amanah_lain);
                        $('#det_keterangan').text(res.keterangan || '-');

                        // PAKAI res.metode_alquran
                        $('#det_metode').text(res.metode_alquran);

                        $('#det_sertifikasi').html(res.is_sertifikasi_ummi == 1 ?
                            '<span class="text-success fw-bold">Sudah Sertifikasi</span>' :
                            '<span class="text-danger">Belum</span>');

                        $('#det_tahun').text(res.tahun_sertifikasi);
                        $('#det_nomor').text(res.nomor);
                        $('#det_email').text(res.email);
                        $('#det_alamat').text(res.alamat);

                        modalDetail?.show();
                    });
            });

            $(document).on('click', '#togglePassword', function() {
                const passwordField = $('#password');
                const eyeIcon = $('#eyeIcon');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';

                passwordField.attr('type', type);
                eyeIcon.toggleClass('bi-eye-slash bi-eye');
            });

            $(document).on('click', '.btnEdit', function() {
                const id = $(this).data('id');
                const kelasId = $(this).data('kelas_id'); // Ambil kelas_id dari tombol yang diklik

                resetForm();

                $('#modalMusyrifTitle').html('<i class="bi bi-pencil-square"></i> Edit Data Musyrif');
                $('#btnSaveMusyrif').text('Update Musyrif');

                // Langsung set kelas_id di awal agar user tidak melihat dropdown kosong
                if (kelasId) {
                    $('#kelas_id').val(kelasId);
                }

                $.get("{{ route('admin.musyrif.show', ':id') }}".replace(':id', id))
                    .done(function(res) {
                        // Isi field profil (Nama, Kode, dsb)
                        $('#musyrif_id').val(res.id);
                        $('#nama').val(res.nama);
                        $('#kode').val(res.kode);
                        $('#jenis_kelamin').val(res.jenis_kelamin || '');
                        if (res.kelas_id) {
                            $('#kelas_id').val(res.kelas_id);
                        }
                        $('#alamat').val(res.alamat);
                        $('#pendidikan_terakhir').val(res.pendidikan_terakhir);
                        $('#domisili').val(res.domisili);
                        $('#halaqah').val(res.halaqah);
                        $('#metode_alquran').val(res.metode_alquran);
                        $('#tahun_sertifikasi').val(res.tahun_sertifikasi);
                        $('#is_sertifikasi_ummi').prop('checked', res.is_sertifikasi_ummi == 1);
                        $('#keterangan').val(res.keterangan);

                        // LOGIKA AKUN LOGIN EXISTING
                        if (res.user_id || res.email !== '-') {
                            $('#create_user').prop('checked', true);
                            $('#createUserFields').removeClass(
                                'd-none'); // Paksa muncul tanpa nunggu fade
                            $('#email').val(res.email);
                            $('#password').val(''); // Password dikosongkan saat edit
                            $('#password').attr('placeholder', 'Isi jika ingin ganti pass');
                        } else {
                            $('#create_user').prop('checked', false);
                            $('#createUserFields').addClass('d-none');
                        }

                        modalMusyrif?.show();
                    });
            });

            // Tampilkan atau sembunyikan field akun login.
            // Namespace event mencegah handler terpasang dua kali.
            $('#create_user')
                .off('change.musyrifAccount')
                .on('change.musyrifAccount', function() {
                    const fields = $('#createUserFields');

                    if ($(this).is(':checked')) {
                        fields.stop(true, true).removeClass('d-none').hide().fadeIn(150);
                    } else {
                        fields.stop(true, true).fadeOut(150, function() {
                            fields.addClass('d-none');
                            $('#email, #password').val('');
                        });
                    }
                });

            // Trigger Edit dari dalam Modal Detail
            $(document).on('click', '#btnEditFromDetail', function() {
                const id = $('#det_id_hidden').val();
                const kelasId = $('#det_kelas_id_hidden').val();

                modalDetail?.hide(); // Tutup modal detail

                setTimeout(() => {
                    // Panggil form reset dan set title (Sama seperti logic .btnEdit)
                    resetForm();
                    $('#modalMusyrifTitle').html(
                        '<i class="bi bi-pencil-square"></i> Edit Data Musyrif');
                    $('#btnSaveMusyrif').text('Update Musyrif');

                    if (kelasId) {
                        $('#kelas_id').val(kelasId);
                    }

                    // Jalankan AJAX untuk mengambil data edit
                    $.get("{{ route('admin.musyrif.show', ':id') }}".replace(':id', id))
                        .done(function(res) {
                            $('#musyrif_id').val(res.id);
                            $('#nama').val(res.nama);
                            $('#kode').val(res.kode);
                            $('#jenis_kelamin').val(res.jenis_kelamin || '');
                            if (res.kelas_id) $('#kelas_id').val(res.kelas_id);
                            $('#alamat').val(res.alamat);
                            $('#pendidikan_terakhir').val(res.pendidikan_terakhir);
                            $('#domisili').val(res.domisili);
                            $('#halaqah').val(res.halaqah);
                            $('#metode_alquran').val(res.metode_alquran);
                            $('#tahun_sertifikasi').val(res.tahun_sertifikasi);
                            $('#is_sertifikasi_ummi').prop('checked', res.is_sertifikasi_ummi ==
                                1);
                            $('#keterangan').val(res.keterangan);

                            // LOGIKA AKUN LOGIN EXISTING
                            if (res.user_id || res.email !== '-') {
                                $('#create_user').prop('checked', true);
                                $('#createUserFields').removeClass('d-none');
                                $('#email').val(res.email);
                                $('#password').val('').attr('placeholder',
                                    'Isi jika ingin ganti pass');
                            } else {
                                $('#create_user').prop('checked', false);
                                $('#createUserFields').addClass('d-none');
                            }

                            modalMusyrif?.show(); // Buka modal Edit
                        });
                }, 400); // Jeda 400ms agar animasi modal tutup selesai dulu
            });

            $('#formMusyrif').on('submit', function(e) {
                e.preventDefault();
                const id = $('#musyrif_id').val();
                const url = id ? "{{ url('admin/musyrif') }}/" + id :
                    "{{ route('admin.musyrif.store') }}";

                // Feedback Loading pada tombol
                const btnSave = $('#btnSaveMusyrif');
                const originalText = btnSave.html();
                btnSave.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

                // Hapus feedback error sebelumnya
                $('.invalid-feedback').remove();
                $('.form-control, .form-select').removeClass('is-invalid');

                $.ajax({
                    url: url,
                    type: id ? "PUT" : "POST",
                    data: $(this).serialize() +
                        `&_token={{ csrf_token() }}&create_user=${$('#create_user').is(':checked')?1:0}`,
                    success: function(res) {
                        modalMusyrif?.hide();
                        table.ajax.reload(null, false);
                        if (window.AppAlert) AppAlert.success(res.message);
                    },
                    error: function(xhr) {
                        // JIKA ERROR VALIDASI (Status 422)
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMessages = '';

                            // Loop setiap field yang error
                            Object.keys(errors).forEach(key => {
                                const inputField = $(`[name="${key}"]`);
                                const message = errors[key][0]; // Ambil pesan pertama

                                // Tambahkan class merah pada input
                                inputField.addClass('is-invalid');

                                // Tambahkan pesan error di bawah input
                                inputField.after(
                                    `<div class="invalid-feedback small fw-bold">${message}</div>`
                                );

                                errorMessages += `<li>${message}</li>`;
                            });

                            if (window.AppAlert) {
                                AppAlert.error(`<ul>${errorMessages}</ul>`, 'Validasi Gagal');
                            }
                        } else {
                            // Error Sistem Lainnya (500, dll)
                            if (window.AppAlert) {
                                AppAlert.error(xhr.responseJSON?.message ||
                                    'Terjadi kesalahan sistem.');
                            }
                        }
                    },
                    complete: function() {
                        btnSave.prop('disabled', false).html(originalText);
                    }
                });
            });

            $(document).on('click', '.btnDelete', function() {
                const id = $(this).data('id');
                if (!window.AppAlert) return;

                AppAlert.warning('Data musyrif akan dihapus permanen!', 'Hapus Musyrif?')
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.post("{{ url('admin/musyrif') }}/" + id, {
                                _method: 'DELETE',
                                _token: "{{ csrf_token() }}"
                            })
                            .done(res => {
                                table.ajax.reload(null, false);
                                AppAlert.success(res.message || 'Data berhasil dihapus');
                            })
                            .fail(() => AppAlert.error('Gagal menghapus data'));
                    });
            });

            // ==========================================
            // 4. EVENT HANDLERS (IMPORT & PREVIEW)
            // ==========================================

            $('#btnImportMusyrif').on('click', () => modalImport.show());

            const importDropzone = $('#importDropzone');
            const importFileInput = $('#import_file');
            const importSelectedFileWrap = $('#importSelectedFileWrap');
            const importSelectedFileName = $('#importSelectedFileName');

            function isValidImportFile(file) {
                if (!file) return false;

                const allowedExtensions = ['xlsx', 'xls', 'csv'];
                const fileName = file.name || '';
                const ext = fileName.split('.').pop().toLowerCase();

                return allowedExtensions.includes(ext);
            }

            function setImportFile(file) {
                if (!file) return;

                if (!isValidImportFile(file)) {
                    importFileInput.val('');
                    importSelectedFileWrap.addClass('d-none');
                    importSelectedFileName.text('Belum ada file');

                    if (window.AppAlert) {
                        AppAlert.error('Format file tidak valid. Gunakan file .xlsx, .xls, atau .csv.');
                    } else {
                        alert('Format file tidak valid. Gunakan file .xlsx, .xls, atau .csv.');
                    }

                    return;
                }

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                importFileInput[0].files = dataTransfer.files;

                importSelectedFileName.text(file.name);
                importSelectedFileWrap.removeClass('d-none');

                $('#importMappingArea').hide();
                $('#importMappingBody').html('');
                $('#previewHeader').html('');
                $('#importPreviewBody').html(
                    '<tr><td class="text-center py-4 text-muted">Klik "Baca Isi File", pilih sheet, lalu klik "Preview".</td></tr>'
                );
                $('#import_file_path').val('');
            }

            function resetImportState() {
                $('#formImportUpload')[0].reset();
                $('#import_file_path').val('');

                importDropzone.removeClass('is-dragover');
                importSelectedFileWrap.addClass('d-none');
                importSelectedFileName.text('Belum ada file');

                $('#importMappingArea').hide();
                $('#importMappingBody').html('');
                $('#previewHeader').html('');
                $('#importPreviewBody').html(
                    '<tr><td class="text-center py-4 text-muted">Klik "Preview" untuk melihat data sebelum diimport</td></tr>'
                );
            }

            $('#btnChooseImportFile').on('click', function(e) {
                e.preventDefault();
                importFileInput.trigger('click');
            });

            importDropzone.on('click', function(e) {
                const ignoredTargets = ['button', 'a', 'input', 'select', 'option', 'label'];

                if ($(e.target).closest(ignoredTargets.join(',')).length) return;

                importFileInput.trigger('click');
            });

            importFileInput.on('change', function() {
                const file = this.files && this.files[0];

                if (!file) {
                    importSelectedFileWrap.addClass('d-none');
                    importSelectedFileName.text('Belum ada file');
                    return;
                }

                setImportFile(file);
            });

            importDropzone.on('dragenter dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                importDropzone.addClass('is-dragover');
            });

            importDropzone.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (e.type !== 'drop') {
                    importDropzone.removeClass('is-dragover');
                }
            });

            importDropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();

                importDropzone.removeClass('is-dragover');

                const originalEvent = e.originalEvent;
                const files = originalEvent.dataTransfer && originalEvent.dataTransfer.files;

                if (!files || !files.length) return;

                setImportFile(files[0]);
            });

            $('#formImportUpload').on('submit', function(e) {
                e.preventDefault();

                if (!importFileInput[0].files || !importFileInput[0].files.length) {
                    return AppAlert?.error('Pilih atau drag file Excel terlebih dahulu!');
                }

                const btn = $('#btnUploadReadSheet');
                const originalText = btn.html();

                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...'
                );

                $.ajax({
                    url: "{{ route('admin.musyrif.preview') }}",
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        $('#import_file_path').val(res.temp_path);
                        $('#importMappingArea').fadeIn();

                        const tbody = $('#importMappingBody').html('');
                        const sheetInfo = res.sheet_info || [];

                        (res.sheets || []).forEach((name, index) => {
                            const info = sheetInfo[index] || {};
                            const rowCount = info.rows ?? '-';

                            tbody.append(`
                                <tr>
                                    <td class="ps-3 fw-bold">${name}</td>
                                    <td class="text-muted small">${rowCount} baris terdeteksi</td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                            Ready
                                        </span>
                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="form-check d-inline-block">
                                            <input type="radio"
                                                name="selected_sheet"
                                                class="form-check-input sheet-radio"
                                                value="${index}"
                                                style="transform: scale(1.2);">
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });

                        $('#previewHeader').html('');
                        $('#importPreviewBody').html(
                            '<tr><td class="text-center py-4 text-muted">Pilih sheet, lalu klik "Preview" untuk melihat data.</td></tr>'
                        );

                        if (window.AppAlert) AppAlert.success('Daftar sheet berhasil dibaca.');
                    },
                    error: (xhr) => {
                        if (window.AppAlert) {
                            AppAlert.error(xhr.responseJSON?.message || 'Gagal membaca file.');
                        }
                    },
                    complete: () => btn.prop('disabled', false).html(originalText)
                });
            });

            // Event saat tombol Preview diklik
            $('#btnPreviewImport').on('click', function() {
                const sheetIdx = $('.sheet-radio:checked').val();
                if (sheetIdx === undefined) return AppAlert?.error(
                    'Pilih salah satu sheet terlebih dahulu!');

                const btn = $(this);
                const originalText = btn.html();

                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span>...');

                $.post("{{ route('admin.musyrif.sheet_preview') }}", {
                    _token: "{{ csrf_token() }}",
                    temp_path: $('#import_file_path').val(),
                    sheet_index: sheetIdx
                }).done(function(res) {
                    // Render Header
                    let headHtml = '';
                    res.headers.forEach(h => {
                        headHtml +=
                        `<th>${String(h).toUpperCase().replace(/_/g, ' ')}</th>`;
                    });
                    $('#previewHeader').html(headHtml);

                    // Render Body
                    let bodyHtml = '';
                    if (res.preview && res.preview.length > 0) {
                        res.preview.forEach(row => {
                            bodyHtml += '<tr>';
                            res.headers.forEach(key => {
                                bodyHtml += `<td>${row[key] ?? '-'}</td>`;
                            });
                            bodyHtml += '</tr>';
                        });
                    } else {
                        bodyHtml =
                            `<tr><td colspan="${res.headers.length || 1}" class="text-center py-4 text-muted">Tidak ada data pada sheet ini.</td></tr>`;
                    }

                    $('#importPreviewBody').html(bodyHtml);
                    if (window.AppAlert) AppAlert.success('Preview sheet berhasil dimuat.');
                }).fail(function(xhr) {
                    if (window.AppAlert) AppAlert.error(xhr.responseJSON?.message ||
                        'Gagal memuat preview sheet.');
                }).always(() => btn.prop('disabled', false).html(originalText));
            });

            // Tombol Reset
            $('#btnResetImport').on('click', function() {
                resetImportState();
            });

            // Tombol Final Execute
            $('#btnProcessImport').on('click', function() {
                const sheetIdx = $('.sheet-radio:checked').val();
                if (sheetIdx === undefined) return AppAlert?.error('Pilih sheet yang akan diimport!');

                const btn = $(this);
                const originalText = btn.html();

                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Mengimport...');

                $.post("{{ route('admin.musyrif.execute_import') }}", {
                    _token: "{{ csrf_token() }}",
                    temp_path: $('#import_file_path').val(),
                    sheet_index: sheetIdx
                }).done(res => {
                    if (window.AppAlert) AppAlert.success(res.message);
                    modalImport?.hide();
                    resetImportState();
                    table.ajax.reload(null, false);
                }).fail(xhr => {
                    if (window.AppAlert) AppAlert.error(xhr.responseJSON?.message ||
                        'Gagal import.');
                }).always(() => btn.prop('disabled', false).html(originalText));
            });
        });
    </script>
@endpush
