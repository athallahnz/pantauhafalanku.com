@extends('layouts.app')

@section('title', 'Manajemen Tahsin Santri')

@section('content')
    <style>
        /* ================= TEMA ISLAMIC PURPLE & MODERN TABLE ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700, #6f42c1);
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #fff !important;
        }

        .main-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .card-header-purple {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        /* ================= KPI CARD GLASSMORPHISM ================= */
        .kpi-card {
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.7) !important;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.1);
            border-color: rgba(111, 66, 193, 0.2) !important;
        }

        .kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 5px;
            color: #9aa0a6;
        }

        .kpi-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .kpi-card:hover .kpi-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        .kpi-progress {
            height: 6px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            margin-top: 15px;
        }

        .kpi-progress-bar {
            border-radius: 10px;
            transition: width 1.5s cubic-bezier(0.1, 0.5, 0.5, 1);
        }

        /* 📱 RESPONSIVE MOBILE ADJUSTMENTS (STACK KE BAWAH) 📱 */
        @media (max-width: 767.98px) {
            .kpi-value {
                font-size: 1.5rem !important;
                /* Sedikit dikecilkan dari ukuran Desktop */
            }

            .kpi-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .kpi-label {
                font-size: 0.7rem;
                margin-bottom: 4px;
            }
        }

        /* DARK MODE SUPPORT */
        [data-coreui-theme="dark"] .kpi-card {
            background: rgba(42, 42, 53, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-coreui-theme="dark"] .kpi-label {
            color: #a0a0a0;
        }

        [data-coreui-theme="dark"] .kpi-value {
            color: #ffffff !important;
        }

        /* Styling Filter Nav-Pills (Kapsul) */
        #filterTanggalGroup .nav-link {
            color: var(--cui-secondary-color);
            background-color: var(--cui-tertiary-bg);
            border: 1px solid var(--cui-border-color);
            border-radius: 50px;
            padding: 0.4rem 1rem;
            margin-right: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        #filterTanggalGroup .nav-link.active {
            background-color: var(--islamic-purple-600, #6f42c1) !important;
            color: #ffffff !important;
            border-color: var(--islamic-purple-600, #6f42c1) !important;
            box-shadow: 0 4px 10px rgba(111, 66, 193, 0.2);
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 24px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--cui-tertiary-bg);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        /* ==========================================================
                                                                                                                                   MODAL GUIDE STYLES
                                                                                                                                ========================================================== */
        .guide-step {
            position: relative;
            border-left: 3px solid var(--cui-info);
            padding-left: 15px;
            margin-bottom: 20px;
        }

        .guide-number {
            position: absolute;
            left: -12px;
            top: 0;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cui-info);
            color: #fff;
            border-radius: 50%;
            font-size: 11px;
            font-weight: bold;
        }

        /* ==========================================================
                                                                                                                                   FAB (FLOATING ACTION BUTTON) STYLES
                                                                                                                                ========================================================== */
        .fab-group-wrapper {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1050;
            display: flex;
            align-items: flex-end;
            /* Rata bawah */
            justify-content: flex-end;
            gap: 15px;
            pointer-events: none;
            /* Agar area kosong tidak menghalangi tabel */
        }

        .fab-left,
        .fab-right {
            pointer-events: auto;
            /* Kembalikan pointer events untuk tombol */
        }

        /* Tumpukan tombol utama di kanan */
        .fab-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            /* Rata kanan */
            gap: 12px;
        }

        /* Posisi tombol info di kiri (Desktop: bersebelahan dengan grup kanan) */
        .fab-left {
            position: relative;
            display: flex;
            align-items: flex-end;
            margin-bottom: 4px;
            /* Penyesuaian visual agar sejajar */
        }

        .btn-fab-main {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50px;
            padding: 12px 24px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .btn-fab-info {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--cui-secondary-bg, #e4e6e9);
            color: var(--cui-secondary-color, #4f5d73);
            border: 1px solid transparent;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Bubble Styles */
        .help-bubble {
            position: absolute;
            bottom: 60px;
            left: -20px;
            background: var(--islamic-purple-600, #6f42c1);
            color: white;
            padding: 10px 18px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 10px 25px rgba(111, 66, 193, 0.3);
            animation: floatBubble 2s infinite ease-in-out;
            display: none;
        }

        .bubble-arrow {
            position: absolute;
            bottom: -8px;
            left: 35px;
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid var(--islamic-purple-600, #6f42c1);
        }

        @keyframes floatBubble {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        [data-coreui-theme="dark"] .btn-fab-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: #fff;
        }

        /* ==========================================================
                                                                                                                                   MOBILE RESPONSIVE (Pojok Kiri & Pojok Kanan)
                                                                                                                                ========================================================== */
        @media (max-width: 768px) {
            .fab-group-wrapper {
                position: static;
                display: block;
            }

            /* Tombol Info & Bubble pindah ke pojok KIRI bawah */
            .fab-left {
                position: fixed;
                left: 20px;
                bottom: 20px;
                z-index: 1050;
                margin-bottom: 0;
            }

            .btn-fab-info {
                width: 55px;
                height: 55px;
                font-size: 1.3rem;
            }

            /* Tombol Utama tetap di KANAN bawah, menumpuk rapi */
            .fab-right {
                position: fixed;
                right: 20px;
                bottom: 20px;
                z-index: 1050;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            /* Ubah tombol utama jadi bulat (icon saja) di Mobile */
            .btn-fab-main {
                width: 60px;
                height: 60px;
                padding: 0;
                border-radius: 50%;
            }

            .btn-fab-main .fab-text {
                display: none;
            }

            .btn-fab-main i {
                margin: 0 !important;
                font-size: 1.8rem;
            }

            /* Penyesuaian Bubble di HP */
            .help-bubble {
                bottom: 75px;
                left: 0px;
                font-size: 11px;
                padding: 8px 12px;
            }

            .bubble-arrow {
                left: 25px;
            }

            /* Styling Tabs Tahsin & Tilawah */
            .nav-tabs .nav-link {
                color: var(--cui-secondary-color);
                border-bottom: 3px solid transparent !important;
            }

            .nav-tabs .nav-link#tab-tahsin-btn.active {
                color: var(--islamic-purple-600, #6f42c1) !important;
                border-bottom-color: var(--islamic-purple-600, #6f42c1) !important;
            }

            .nav-tabs .nav-link#tab-tilawah-btn.active {
                color: #198754 !important;
                border-bottom-color: #198754 !important;
            }
        }
    </style>

    <div class="row mb-4 align-items-center px-3 px-md-0 g-3">
        <div class="col-12 col-md-auto text-start">
            <h4 class="fw-bold text-adaptive-purple mb-1">Manajemen Tahsin & Tilawah</h4>
            <p class="text-muted small mb-0">Input materi masal untuk efisiensi absensi santri binaan Anda.</p>
        </div>
    </div>

    <div class="row g-3 mb-4 px-3 px-md-0">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="overflow-hidden w-100 pe-3">
                            <div class="kpi-label text-truncate mb-1">Mayoritas Tahsin</div>
                            <div class="kpi-value text-primary text-truncate mb-0" title="{{ $mayoritasBuku }}">
                                {{ $mayoritasBuku }}</div>
                            <div class="kpi-sub fst-italic mt-1 text-truncate">Buku berjalan</div>
                        </div>
                        <div class="kpi-icon bg-primary-subtle text-primary shadow-sm flex-shrink-0">
                            <i class="bi bi-book-half"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="overflow-hidden w-100 pe-3">
                            <div class="kpi-label text-truncate mb-1">Rata-rata Tilawah</div>
                            <div class="kpi-value text-success text-truncate mb-0">Juz {{ $avgJuz ?? 0 }}</div>
                            <div class="kpi-sub fst-italic mt-1 text-truncate">Capaian saat ini</div>
                        </div>
                        <div class="kpi-icon bg-success-subtle text-success shadow-sm flex-shrink-0">
                            <i class="bi bi-journal-bookmark-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="overflow-hidden w-100 pe-3">
                            <div class="kpi-label text-truncate mb-1">Tahsin Hari Ini</div>
                            <div class="kpi-value text-info text-truncate mb-0">
                                {{ $tahsinToday }} <span class="fs-6 fw-normal text-muted opacity-75">/
                                    {{ $totalSantri }}</span>
                            </div>
                        </div>
                        <div class="kpi-icon bg-info-subtle text-info shadow-sm flex-shrink-0">
                            <i class="bi bi-pencil-square"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="kpi-progress">
                            <div class="progress-bar kpi-progress-bar bg-info"
                                style="width: {{ $totalSantri > 0 ? ($tahsinToday / $totalSantri) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="overflow-hidden w-100 pe-3">
                            <div class="kpi-label text-truncate mb-1">Tilawah Hari Ini</div>
                            <div class="kpi-value text-warning text-truncate mb-0">
                                {{ $tilawahToday }} <span class="fs-6 fw-normal text-muted opacity-75">/
                                    {{ $totalSantri }}</span>
                            </div>
                        </div>
                        <div class="kpi-icon bg-warning-subtle text-warning shadow-sm flex-shrink-0">
                            <i class="bi bi-check2-all"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="kpi-progress">
                            <div class="progress-bar kpi-progress-bar bg-warning"
                                style="width: {{ $totalSantri > 0 ? ($tilawahToday / $totalSantri) * 100 : 0 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card main-card spotlight-card shadow-sm border-0">
        <div class="card-header card-header-purple bg-body-tertiary py-3 px-3 px-md-4 border-bottom-0">
            <div
                class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <div class="w-100 w-md-auto overflow-auto">
                    <ul class="nav nav-pills nav-pills-sm flex-nowrap" id="filterTanggalGroup" role="tablist">
                        <li class="nav-item"><button class="nav-link active text-nowrap" type="button"
                                data-filter="today">Hari Ini</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                data-filter="yesterday">Kemarin</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                data-filter="all">Semuanya</button></li>
                    </ul>
                </div>

                <div class="w-100 w-md-auto text-md-end">
                    <span
                        class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 d-block d-md-inline-block shadow-sm"
                        id="filterBadge"
                        style="min-width: 150px; border: 1px solid var(--cui-border-color-translucent, rgba(111, 66, 193, 0.2));">
                        <i class="bi bi-info-circle me-1"></i> Menampilkan: Hari Ini
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <ul class="nav nav-tabs nav-fill px-4 pt-3 bg-body-tertiary border-bottom-0" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3 border-top-0 border-start-0 border-end-0"
                        id="tab-tahsin-btn" data-coreui-toggle="tab" data-coreui-target="#tab-tahsin" type="button"
                        role="tab">
                        <i class="bi bi-book me-2"></i>Riwayat Tahsin
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3 border-top-0 border-start-0 border-end-0" id="tab-tilawah-btn"
                        data-coreui-toggle="tab" data-coreui-target="#tab-tilawah" type="button" role="tab">
                        <i class="bi bi-journal-bookmark-fill me-2"></i>Riwayat Tilawah
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active p-3 p-md-4" id="tab-tahsin" role="tabpanel">
                    <div class="table-responsive">
                        <table id="tahsin-table" class="table table-hover align-middle w-100 text-nowrap mb-0">
                            <thead class="bg-body-tertiary">
                                <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                    <th class="border-top-0 ps-3">No.</th>
                                    <th class="border-top-0">Santri</th>
                                    <th class="border-top-0">Buku/Jilid</th>
                                    <th class="border-top-0">Halaman</th>
                                    <th class="border-top-0">Status</th>
                                    <th class="text-end pe-4 border-top-0">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade p-3 p-md-4" id="tab-tilawah" role="tabpanel">
                    <div class="table-responsive">
                        <table id="tilawah-table" class="table table-hover align-middle w-100 text-nowrap mb-0">
                            <thead class="bg-body-tertiary">
                                <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                    <th class="border-top-0 ps-3">No.</th>
                                    <th class="border-top-0">Santri</th>
                                    <th class="border-top-0">Juz & Target</th>
                                    <th class="border-top-0">Detail / Catatan</th>
                                    <th class="border-top-0">Status</th>
                                    <th class="text-end pe-4 border-top-0">Aksi</th>
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
    <div class="fab-group-wrapper">
        <div class="fab-left">
            <div id="bubbleHelp" class="help-bubble">
                <div class="bubble-content">
                    <i class="bi bi-magic me-1"></i> Butuh bantuan? Klik untuk panduan cepat!
                </div>
                <div class="bubble-arrow"></div>
            </div>

            <button class="btn btn-fab-info" data-coreui-toggle="modal" data-coreui-target="#modalPanduanTahsin">
                <i class="bi bi-question-circle-fill"></i>
            </button>
        </div>

        <div class="fab-right">
            <button class="btn btn-success text-white btn-fab-main shadow" id="btnAddTilawah">
                <i class="bi bi-journal-bookmark me-md-2"></i>
                <span class="fab-text">Catat Tilawah</span>
            </button>

            <button class="btn btn-primary btn-fab-main shadow" id="btnAddTahsin">
                <i class="bi bi-book me-md-2"></i>
                <span class="fab-text">Input Tahsin Masal</span>
            </button>
        </div>
    </div>

    {{-- MODAL PANDUAN --}}
    <div class="modal fade" id="modalPanduanTahsin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                {{-- Gunakan bg-body-tertiary agar adaptif di Dark/Light mode --}}
                <div class="modal-header px-4 bg-body-tertiary border-bottom-0"
                    style="border-top-left-radius: 25px; border-top-right-radius: 25px;">
                    <h5 class="modal-title fw-bold text-adaptive-purple">
                        <i class="bi bi-lightbulb-fill text-warning me-2"></i>Panduan Sistem
                    </h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-3">

                    {{-- STEP 1: Aturan Tilawah (BARU) --}}
                    <div class="guide-step mb-4" style="border-left-color: #0dcaf0;">
                        <span class="guide-number" style="background: #0dcaf0; color: white;">1</span>
                        <h6 class="fw-bold mb-1 text-info-emphasis">Wajib Input Tilawah Dahulu</h6>
                        <p class="text-muted small mb-0">
                            Sistem memiliki validasi cerdas. Anda harus mencatat <b>Target Tilawah (Juz)</b>
                            terlebih dahulu (Klik tombol Hijau <span class="badge bg-success rounded-pill p-2"><i
                                    class="bi bi-journal-bookmark"></i></span>). Sistem akan otomatis menolak/melewati
                            santri yang capaian Tilawah-nya belum
                            memenuhi syarat minimal Jilid Tahsin yang dipilih.
                        </p>
                    </div>

                    {{-- STEP 2: Input Tahsin --}}
                    <div class="guide-step mb-4">
                        <span class="guide-number">2</span>
                        <h6 class="fw-bold mb-1 text-adaptive-purple">Input Materi Tahsin Masal</h6>
                        <p class="text-muted small mb-0">
                            Klik tombol ungu <span class="badge bg-primary rounded-pill p-2"><i
                                    class="bi bi-book"></i></span>
                            di pojok kanan bawah. Tetapkan Buku & Halaman hari ini, seluruh santri yang memenuhi syarat
                            Tilawah akan otomatis tercatat <b>Hadir</b>.
                        </p>
                    </div>

                    {{-- STEP 3: Koreksi --}}
                    <div class="guide-step mb-4" style="border-left-color: #ffc107;">
                        <span class="guide-number text-dark" style="background: #ffc107;">3</span>
                        <h6 class="fw-bold mb-1 text-warning-emphasis">Koreksi Absensi Individu</h6>
                        <p class="text-muted small mb-0">
                            Jika ada santri yang berhalangan (Sakit/Izin/Alpha), klik tombol <b>Edit (Pensil)</b> pada baris
                            nama santri di tabel riwayat untuk mengubah status kehadirannya.
                        </p>
                    </div>

                    {{-- STEP 4: Pantau --}}
                    <div class="guide-step" style="border-left-color: #198754; margin-bottom: 0;">
                        <span class="guide-number" style="background: #198754; color: white;">4</span>
                        <h6 class="fw-bold mb-1 text-success">Pantau Progres</h6>
                        <p class="text-muted small mb-0">
                            Gunakan tombol <b>Detail (Mata)</b> untuk melihat riwayat spesifik, atau klik nama santri untuk
                            melihat halaman analitik dan <i>timeline</i> lengkap mereka.
                        </p>
                    </div>

                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm"
                        data-coreui-dismiss="modal">Saya Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL INPUT MASAL --}}
    <div class="modal fade" id="modalTahsin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formTahsin" novalidate>
                @csrf
                <div class="modal-content shadow-lg">
                    <div class="modal-header px-4">
                        <h5 class="modal-title fw-bold text-adaptive-purple"><i class="bi bi-people-fill me-2"></i>Materi
                            Hari Ini</h5>
                        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 small">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-exclamation-triangle-fill text-warning fs-4 me-3 mt-1"></i>
                                <div>
                                    <strong class="text-dark d-block mb-1">Validasi Syarat Tilawah</strong>
                                    Pastikan Anda sudah mencatat <b>Tilawah</b> terlebih dahulu. Sistem akan
                                    menolak/melewati santri yang capaian Tilawahnya belum memenuhi standar Jilid/Buku Tahsin
                                    yang dipilih.
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">BUKU / JILID</label>
                                <select name="buku" id="buku" class="form-select bg-body-tertiary border-0"
                                    required>
                                    <option value="">-- Pilih --</option>
                                    <option value="ummi_1">Ummi Jilid 1</option>
                                    <option value="ummi_2">Ummi Jilid 2</option>
                                    <option value="ummi_3">Ummi Jilid 3</option>
                                    <option value="gharib_1">Gharib 1</option>
                                    <option value="gharib_2">Gharib 2</option>
                                    <option value="tajwid">Tajwid</option>
                                </select>

                                <div id="eligibility-container" class="mt-3 p-2 bg-light rounded-3 border"
                                    style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-bold text-muted"
                                            style="font-size: 9px; letter-spacing: 0.5px;">MEMENUHI SYARAT (JUZ <span
                                                id="req-juz"></span>)</small>
                                        <small class="fw-bold text-primary" style="font-size: 10px;"><span
                                                id="elig-count">0</span> / <span id="elig-total">0</span> SANTRI</small>
                                    </div>
                                    <div class="progress mb-1" style="height: 6px; border-radius: 10px;">
                                        <div id="elig-progress"
                                            class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <small id="elig-warning" class="text-danger fw-bold"
                                        style="font-size: 10px; display: none; line-height: 1.2;"></small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">HALAMAN</label>
                                <select name="halaman" id="halaman" class="form-select bg-body-tertiary border-0"
                                    required></select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">CATATAN UMUM (OPTIONAL)</label>
                                <textarea name="catatan" class="form-control bg-body-tertiary border-0" rows="2"
                                    placeholder="Catatan untuk seluruh santri..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow"
                            id="btnSubmitTahsin">Terapkan Materi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT STATUS --}}
    <div class="modal fade" id="modalEditTahsin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditTahsin">
                @csrf @method('PUT')
                <input type="hidden" id="edit_id">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                    <div class="modal-header px-4 border-bottom-0">
                        <h6 class="modal-title fw-bold text-adaptive-purple">
                            <i class="bi bi-pencil-square me-2"></i>Update Kehadiran Individu
                        </h6>
                        {{-- Hapus btn-close-white agar icon close otomatis adaptif (hitam di light, putih di dark) --}}
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 pt-2">
                        {{-- Gunakan bg-body-tertiary agar otomatis menyesuaikan gelap/terang --}}
                        <div class="text-center p-3 mb-4 rounded-4 bg-body-tertiary border-0 shadow-sm">
                            <h6 class="fw-bold mb-0 text-primary" id="edit_nama_santri"></h6>
                            <small class="text-muted d-block mt-1" id="edit_info_materi"></small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">STATUS SANTRI</label>
                            <select name="status" id="edit_status"
                                class="form-select bg-body-tertiary border-0 fw-bold">
                                <option value="hadir" class="text-success">Hadir</option>
                                <option value="izin" class="text-secondary">Izin</option>
                                <option value="sakit" class="text-primary">Sakit</option>
                                <option value="alpha" class="text-danger">Alpha</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">CATATAN KHUSUS</label>
                            <textarea name="catatan" id="edit_catatan" class="form-control bg-body-tertiary border-0" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        {{-- Tombol batal dibuat adaptif --}}
                        <button type="button"
                            class="btn btn-secondary bg-body-tertiary border-0 text-body rounded-pill px-4"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan
                            Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL --}}
    <div class="modal fade" id="modalDetailTahsin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 28px;">
                {{-- Ganti hardcoded linear-gradient menjadi bg-body-tertiary agar aman di Dark Mode --}}
                <div class="p-4 text-center bg-body-tertiary border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge rounded-pill bg-dark px-3 py-2" id="det_status_badge"
                            style="font-size: 10px; letter-spacing: 1px;"></span>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <h3 class="fw-bold text-primary mb-1" id="det_buku_halaman" style="letter-spacing: -0.5px;"></h3>
                    <p class="text-muted small fw-bold mb-0 text-uppercase" style="letter-spacing: 2px;"
                        id="det_tanggal_label"></p>
                </div>

                <div class="modal-body p-4">
                    {{-- Ganti bg-light border-white menjadi bg-body-tertiary border-0 --}}
                    <div class="d-flex align-items-center mb-4 p-3 rounded-4 bg-body-tertiary border-0 shadow-sm">
                        {{-- Ganti bg-white menjadi bg-body agar membaur natural di dark mode --}}
                        <div class="flex-shrink-0 bg-body rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 50px; height: 50px;">
                            <i class="bi bi-person-fill text-primary fs-4"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0" id="det_santri_nama"></h6>
                            <small class="text-muted">Santri Binaan</small>
                        </div>
                    </div>

                    {{-- bg-primary-subtle sudah support dark mode bawaan Bootstrap 5.3 --}}
                    <div class="p-3 rounded-4 border-start border-primary border-4 bg-primary-subtle bg-opacity-10">
                        <small class="fw-bold text-primary d-block mb-1" style="font-size: 10px;">
                            <i class="bi bi-chat-left-text-fill me-1"></i> EVALUASI PENGAJAR
                        </small>
                        {{-- Ganti text-dark menjadi text-body agar berubah putih saat dark mode --}}
                        <p class="small mb-0 fst-italic text-body text-opacity-75" id="det_catatan_val"></p>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm"
                        data-coreui-dismiss="modal">Tutup Detail</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL INPUT TILAWAH MASAL --}}
    <div class="modal fade" id="modalTilawah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formTilawah" novalidate>
                @csrf
                <div class="modal-content shadow-lg border-0" style="border-radius: 25px;">
                    <div class="modal-header px-4 bg-success bg-opacity-10 border-0">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="bi bi-journal-bookmark-fill me-2"></i>Target Tilawah Hari Ini
                        </h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 small">
                            <i class="bi bi-info-circle-fill me-2"></i> Input ini akan mencatat target tilawah yang sama
                            untuk <b>semua santri binaan</b> Anda hari ini sebagai <b>HADIR</b>.
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">JUZ</label>
                                <select id="juz_tilawah" class="form-select bg-body-tertiary border-0" required>
                                    <option value="">-- Pilih Juz --</option>
                                    @for ($i = 1; $i <= 30; $i++)
                                        <option value="{{ $i }}">Juz {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">TARGET BACAAN</label>
                                <select name="template_id" id="template_tilawah"
                                    class="form-select bg-body-tertiary border-0" required disabled>
                                    <option value="">-- Pilih Juz Dulu --</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">DETAIL SURAH & AYAT (Ketik Manual)</label>
                                <input type="text" name="detail_ayat" class="form-control bg-body-tertiary border-0"
                                    placeholder="Catat Ayat Terakhir dari Target Bacaan" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">CATATAN UMUM (OPTIONAL)</label>
                                <textarea name="catatan" class="form-control bg-body-tertiary border-0" rows="2"
                                    placeholder="Catatan untuk seluruh santri..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0 mt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success text-white rounded-pill px-4 shadow-sm fw-bold"
                            id="btnSubmitTilawah">
                            Simpan Tilawah
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT STATUS TILAWAH --}}
    <div class="modal fade" id="modalEditTilawah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditTilawah">
                @csrf @method('PUT')
                <input type="hidden" id="edit_tilawah_id">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                    <div class="modal-header px-4 border-bottom-0">
                        <h6 class="modal-title fw-bold text-success">
                            <i class="bi bi-pencil-square me-2"></i>Update Kehadiran Tilawah
                        </h6>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 pt-2">
                        <div class="text-center p-3 mb-4 rounded-4 bg-body-tertiary border-0 shadow-sm">
                            <h6 class="fw-bold mb-0 text-success" id="edit_tilawah_nama_santri"></h6>
                            <small class="text-muted d-block mt-1" id="edit_tilawah_info_materi"></small>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">STATUS SANTRI</label>
                            <select name="status" id="edit_tilawah_status"
                                class="form-select bg-body-tertiary border-0 fw-bold">
                                <option value="hadir" class="text-success">Hadir</option>
                                <option value="izin" class="text-secondary">Izin</option>
                                <option value="sakit" class="text-primary">Sakit</option>
                                <option value="alpha" class="text-danger">Alpha</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">DETAIL AYAT / CATATAN KHUSUS</label>
                            <textarea name="catatan" id="edit_tilawah_catatan" class="form-control bg-body-tertiary border-0" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button"
                            class="btn btn-secondary bg-body-tertiary border-0 text-body rounded-pill px-4"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit"
                            class="btn btn-success text-white rounded-pill px-4 shadow-sm fw-bold">Simpan
                            Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DETAIL TILAWAH --}}
    <div class="modal fade" id="modalDetailTilawah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 28px;">
                <div class="p-4 text-center bg-body-tertiary border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge rounded-pill bg-dark px-3 py-2" id="det_tilawah_status_badge"
                            style="font-size: 10px; letter-spacing: 1px;"></span>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <h3 class="fw-bold text-success mb-1" id="det_tilawah_target" style="letter-spacing: -0.5px;"></h3>
                    <p class="text-muted small fw-bold mb-0 text-uppercase" style="letter-spacing: 2px;"
                        id="det_tilawah_tanggal_label"></p>
                </div>

                <div class="modal-body p-4">
                    <div class="d-flex align-items-center mb-4 p-3 rounded-4 bg-body-tertiary border-0 shadow-sm">
                        <div class="flex-shrink-0 bg-body rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 50px; height: 50px;">
                            <i class="bi bi-person-fill text-success fs-4"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0" id="det_tilawah_santri_nama"></h6>
                            <small class="text-muted">Santri Binaan</small>
                        </div>
                    </div>

                    <div class="p-3 rounded-4 border-start border-success border-4 bg-success bg-opacity-10">
                        <small class="fw-bold text-success d-block mb-1" style="font-size: 10px;">
                            <i class="bi bi-card-text me-1"></i> DETAIL AYAT & CATATAN
                        </small>
                        <p class="small mb-0 fst-italic text-body text-opacity-75" id="det_tilawah_catatan_val"></p>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-success text-white w-100 py-3 rounded-pill fw-bold shadow-sm"
                        data-coreui-dismiss="modal">Tutup Detail</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        const MAP_HALAMAN = {
            'ummi_1': 40,
            'ummi_2': 40,
            'ummi_3': 40,
            'gharib_1': 28,
            'gharib_2': 28,
            'tajwid': 50
        };

        document.addEventListener('DOMContentLoaded', function() {
            let filterTanggal = 'today';
            let tilawahTemplates = [];

            const filterLabels = {
                all: 'Semua Riwayat',
                today: 'Hari Ini',
                yesterday: 'Kemarin'
            };

            // Inisialisasi Modals
            const modalTahsin = new coreui.Modal(document.getElementById('modalTahsin'));
            const modalEdit = new coreui.Modal(document.getElementById('modalEditTahsin'));
            const modalDetail = new coreui.Modal(document.getElementById('modalDetailTahsin'));
            const modalTilawah = new coreui.Modal(document.getElementById('modalTilawah'));
            // Inisialisasi Modal Tilawah tambahan
            const modalEditTilawah = new coreui.Modal(document.getElementById('modalEditTilawah'));
            const modalDetailTilawah = new coreui.Modal(document.getElementById('modalDetailTilawah'));

            // --- EDIT STATUS TILAWAH ---
            $(document).on('click', '.btn-edit-tilawah', function() {
                const d = $(this).data();
                $('#edit_tilawah_id').val(d.id);
                $('#edit_tilawah_nama_santri').text(d.santri_nama);
                $('#edit_tilawah_info_materi').text(d.target_bacaan);
                $('#edit_tilawah_status').val(d.status);
                $('#edit_tilawah_catatan').val(d.catatan);
                modalEditTilawah.show();
            });

            // --- DETAIL TILAWAH ---
            $(document).on('click', '.btn-detail-tilawah', function() {
                const d = $(this).data();
                $('#det_tilawah_santri_nama').text(d.santri_nama);
                $('#det_tilawah_target').text(d.target_bacaan);
                $('#det_tilawah_tanggal_label').text(d.tanggal_label);
                $('#det_tilawah_catatan_val').text(d.catatan || 'Tidak ada catatan khusus.');

                const badge = $('#det_tilawah_status_badge').text(d.status_text.toUpperCase()).removeClass(
                    'bg-success bg-secondary bg-primary bg-danger text-dark');
                const colorClass = {
                    'hadir': 'bg-success',
                    'izin': 'bg-secondary',
                    'sakit': 'bg-primary',
                    'alpha': 'bg-danger'
                } [d.status_text] || 'bg-light text-dark';
                badge.addClass(colorClass);

                modalDetailTilawah.show();
            });

            // Auto-hide Bubble Help
            setTimeout(() => {
                const bubble = document.getElementById('bubbleHelp');
                if (bubble) {
                    bubble.style.display = 'block';
                    setTimeout(() => {
                        bubble.style.opacity = '0';
                        bubble.style.transition = 'opacity 0.5s ease';
                        setTimeout(() => bubble.remove(), 500);
                    }, 6000);
                }
            }, 1200);

            /* =========================================================
               DATATABLES INITIALIZATION
               ========================================================= */
            const tableTahsin = $('#tahsin-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('musyrif.tahsin.datatable') }}",
                    data: d => {
                        d.filter_tanggal = filterTanggal;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'santri',
                        name: 'santri'
                    },
                    {
                        data: 'buku_label',
                        name: 'buku'
                    },
                    {
                        data: 'halaman',
                        name: 'halaman'
                    },
                    {
                        data: 'status_label',
                        name: 'status'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        className: 'text-end'
                    }
                ],
                drawCallback: function() {
                    const tooltips = document.querySelectorAll(
                        '#tab-tahsin [data-coreui-toggle="tooltip"]');
                    [...tooltips].map(el => new coreui.Tooltip(el));
                }
            });

            const tableTilawah = $('#tilawah-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('musyrif.tilawah.datatable') }}",
                    data: d => {
                        d.filter_tanggal = filterTanggal;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'santri',
                        name: 'santri'
                    },
                    {
                        data: 'target_bacaan',
                        name: 'target_bacaan'
                    },
                    {
                        data: 'catatan_ayat',
                        name: 'catatan'
                    },
                    {
                        data: 'status_label',
                        name: 'status'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        className: 'text-end'
                    }
                ],
                drawCallback: function() {
                    const tooltips = document.querySelectorAll(
                        '#tab-tilawah [data-coreui-toggle="tooltip"]');
                    [...tooltips].map(el => new coreui.Tooltip(el));
                }
            });

            /* =========================================================
               GLOBAL FILTER EVENTS
               ========================================================= */
            $('#filterTanggalGroup button').on('click', function() {
                if ($(this).hasClass('active')) return;
                $('#filterTanggalGroup button').removeClass('active');
                $(this).addClass('active');

                filterTanggal = $(this).data('filter');
                $('#filterBadge').html('<i class="bi bi-info-circle me-1"></i> Menampilkan: ' +
                    filterLabels[filterTanggal]);

                tableTahsin.ajax.reload();
                tableTilawah.ajax.reload();
            });

            /* =========================================================
               MODAL & FORM LOGIC: TILAWAH MASAL
               ========================================================= */
            // 1. Ambil data template tilawah saat halaman dimuat
            $.get("{{ route('musyrif.tilawah.progress') }}", function(res) {
                tilawahTemplates = res.templates;
            });

            // 2. Buka Modal Tilawah
            $('#btnAddTilawah').on('click', function() {
                $('#formTilawah')[0].reset();
                $('#formTilawah').find('.is-invalid').removeClass('is-invalid');
                $('#template_tilawah').empty().append('<option value="">-- Pilih Juz Dulu --</option>')
                    .prop('disabled', true);
                modalTilawah.show();
            });

            // 3. Dropdown Juz -> Target Bacaan
            $('#juz_tilawah').on('change', function() {
                const selectedJuz = $(this).val();
                const $templateSelect = $('#template_tilawah');
                $templateSelect.empty().append('<option value="">-- Pilih Target --</option>');

                if (selectedJuz) {
                    const filteredTemplates = tilawahTemplates.filter(t => t.juz == selectedJuz);
                    filteredTemplates.forEach(t => {
                        $templateSelect.append(`<option value="${t.id}">${t.label}</option>`);
                    });
                    $templateSelect.prop('disabled', false);
                } else {
                    $templateSelect.prop('disabled', true);
                }
            });

            // 4. Submit Tilawah
            $('#formTilawah').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btnSubmitTilawah');
                $(this).find('.is-invalid').removeClass('is-invalid');
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');

                $.ajax({
                    url: "{{ route('musyrif.tilawah.masal') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: res => {
                        modalTilawah.hide();
                        btn.prop('disabled', false).html('Simpan Tilawah');
                        tableTilawah.ajax.reload(); // Reload tabel tilawah

                        if (window.AppAlert) AppAlert.success(res.message);
                        else Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    error: xhr => {
                        btn.prop('disabled', false).html('Simpan Tilawah');
                        if (xhr.status === 422) {
                            const res = xhr.responseJSON;
                            if (res.message && !res.errors) Swal.fire({
                                icon: 'warning',
                                title: 'Perhatian',
                                text: res.message
                            });
                            if (res.errors) $.each(res.errors, (k, v) => $(`[name="${k}"]`)
                                .addClass('is-invalid'));
                        } else {
                            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                        }
                    }
                });
            });

            /* =========================================================
               MODAL & FORM LOGIC: TAHSIN MASAL
               ========================================================= */
            $('#btnAddTahsin').on('click', () => {
                $('#formTahsin')[0].reset();
                $('#formTahsin').find('.is-invalid').removeClass('is-invalid');
                $('#halaman').html('<option value="">-- Pilih Buku Dulu --</option>');
                modalTahsin.show();
            });

            $('#buku').on('change', function() {
                const buku = $(this).val();
                const $h = $('#halaman');

                $h.html('<option value="">-- Pilih --</option>');
                $('#eligibility-container').hide(); // Sembunyikan progress bar dulu

                if (buku) {
                    // 1. Render Pilihan Halaman
                    if (MAP_HALAMAN[buku]) {
                        for (let i = 1; i <= MAP_HALAMAN[buku]; i++) {
                            $h.append(`<option value="${i}">${i}</option>`);
                        }
                    }

                    // 2. Fetch Eligibility Check (Kesesuaian Tilawah)
                    $.ajax({
                        url: "{{ route('musyrif.tahsin.check') }}",
                        type: "GET",
                        data: {
                            buku: buku
                        },
                        beforeSend: function() {
                            $('#eligibility-container').fadeIn('fast');
                            $('#elig-progress').removeClass('bg-success bg-warning').addClass(
                                'bg-primary').css('width', '100%');
                            $('#req-juz').text('...');
                            $('#elig-warning').hide();
                        },
                        success: function(res) {
                            $('#req-juz').text(res.syarat_juz);
                            $('#elig-count').text(res.eligible);
                            $('#elig-total').text(res.total);

                            // Hitung persentase bar
                            let pct = res.total > 0 ? (res.eligible / res.total) * 100 : 0;
                            $('#elig-progress').css('width', pct + '%');

                            // Logika Warna dan Peringatan Bawah
                            if (res.eligible < res.total) {
                                $('#elig-progress').removeClass('bg-primary bg-success')
                                    .addClass('bg-warning');
                                let selisih = res.total - res.eligible;
                                $('#elig-warning').text(
                                    `*Ada ${selisih} santri yang otomatis terlewati karena Tilawah belum sampai Juz ${res.syarat_juz}.`
                                ).slideDown('fast');
                            } else {
                                $('#elig-progress').removeClass('bg-primary bg-warning')
                                    .addClass('bg-success');
                                $('#elig-warning').hide();
                            }
                        }
                    });
                }
            });

            // Pastikan reset state progress bar saat tombol Add Tahsin diklik
            $('#btnAddTahsin').on('click', () => {
                $('#formTahsin')[0].reset();
                $('#formTahsin').find('.is-invalid').removeClass('is-invalid');
                $('#halaman').html('<option value="">-- Pilih Buku Dulu --</option>');
                $('#eligibility-container').hide(); // Sembunyikan container
                modalTahsin.show();
            });

            $('#formTahsin').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btnSubmitTahsin');
                $(this).find('.is-invalid').removeClass('is-invalid');
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');

                $.ajax({
                    url: "{{ route('musyrif.tahsin.store') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: res => {
                        modalTahsin.hide();
                        tableTahsin.ajax.reload();
                        btn.prop('disabled', false).html('Terapkan Materi');

                        Swal.fire({
                            icon: res.icon ?? 'success',
                            title: res.icon === 'warning' ? 'Berhasil Sebagian' :
                                'Berhasil!',
                            text: res.message,
                            timer: res.icon === 'warning' ? undefined : 2000,
                            showConfirmButton: res.icon === 'warning'
                        });
                    },
                    error: xhr => {
                        btn.prop('disabled', false).html('Terapkan Materi');
                        if (xhr.status === 422) {
                            const res = xhr.responseJSON;
                            if (res.message && !res.errors) Swal.fire({
                                icon: 'warning',
                                title: 'Perhatian',
                                text: res.message
                            });
                            if (res.errors) $.each(res.errors, (k, v) => $(`[name="${k}"]`)
                                .addClass('is-invalid'));
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: 'Terjadi kesalahan pada server.'
                            });
                        }
                    }
                });
            });

            /* =========================================================
               CRUD ACTIONS: EDIT & DELETE & DETAIL
               ========================================================= */
            // --- EDIT STATUS TAHSIN ---
            $(document).on('click', '.btn-edit', function() {
                const d = $(this).data();
                $('#edit_id').val(d.id);
                $('#edit_nama_santri').text(d.santri_nama);
                $('#edit_info_materi').text(d.buku_label + ' - Halaman ' + d.halaman);
                $('#edit_status').val(d.status);
                $('#edit_catatan').val(d.catatan);
                modalEdit.show();
            });

            $('#formEditTahsin').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ url('musyrif/tahsin') }}/" + $('#edit_id').val(),
                    type: 'POST',
                    data: $(this).serialize(),
                    success: res => {
                        modalEdit.hide();
                        tableTahsin.ajax.reload();
                        Swal.fire({
                            icon: 'success',
                            title: 'Diperbarui!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                });
            });

            // --- DETAIL TAHSIN ---
            $(document).on('click', '.btn-detail', function() {
                const d = $(this).data();
                $('#det_santri_nama').text(d.santri_nama);
                $('#det_buku_halaman').text(d.buku_label + ' - Hal ' + d.halaman);
                $('#det_tanggal_label').text(d.tanggal_label);
                $('#det_catatan_val').text(d.catatan || 'Tidak ada catatan khusus.');

                const badge = $('#det_status_badge').text(d.status_text.toUpperCase()).removeClass(
                    'bg-success bg-secondary bg-primary bg-danger text-dark');
                const colorClass = {
                    'hadir': 'bg-success',
                    'izin': 'bg-secondary',
                    'sakit': 'bg-primary',
                    'alpha': 'bg-danger'
                } [d.status_text] || 'bg-light text-dark';
                badge.addClass(colorClass);
                modalDetail.show();
            });

            // --- DELETE TAHSIN & TILAWAH ---
            $(document).on('click', '.btn-delete', function() {
                handleDelete("{{ url('musyrif/tahsin') }}/" + $(this).data('id'), tableTahsin);
            });

            $(document).on('click', '.btn-delete-tilawah', function() {
                // Pastikan route delete tilawah dibuat di web.php
                handleDelete("{{ url('musyrif/tilawah') }}/" + $(this).data('id'), tableTilawah);
            });

            $('#formEditTilawah').on('submit', function(e) {
                e.preventDefault();

                // Ambil semua data form, lalu PAKSA tambahkan _method=PUT secara manual
                let formData = $(this).serialize() + '&_method=PUT';

                $.ajax({
                    url: "{{ url('musyrif/tilawah') }}/" + $('#edit_tilawah_id').val(),
                    type: 'POST', // Tetap POST, Laravel akan membaca _method=PUT dari formData
                    data: formData, // Gunakan formData yang sudah dimodifikasi
                    success: res => {
                        modalEditTilawah.hide();
                        tableTilawah.ajax.reload(null, false);
                        Swal.fire({
                            icon: 'success',
                            title: 'Diperbarui!',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: xhr => {
                        Swal.fire('Gagal!', 'Terjadi kesalahan. Cek log console.', 'error');
                        console.log(xhr
                        .responseText); // Untuk mengecek pesan error asli dari Laravel
                    }
                });
            });

            function handleDelete(url, tableInstance) {
                Swal.fire({
                    title: 'Hapus Data?',
                    text: "Data ini akan dihapus permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Ya, Hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: "{{ csrf_token() }}"
                            },
                            success: res => {
                                tableInstance.ajax.reload();
                                Swal.fire('Terhapus!', res.message, 'success');
                            }
                        });
                    }
                });
            }
        });
    </script>
@endpush
