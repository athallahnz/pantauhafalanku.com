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

        #modalTilawah #formTilawah {
            min-height: 0;
        }

        #modalTilawah .modal-body {
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
        }

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

        /* FAB (FLOATING ACTION BUTTON) STYLES */
        .fab-group-wrapper {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1050;
            display: flex;
            align-items: flex-end;
            justify-content: flex-end;
            gap: 15px;
            pointer-events: none;
        }

        .fab-left,
        .fab-right {
            pointer-events: auto;
        }

        .fab-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }

        .fab-left {
            position: relative;
            display: flex;
            align-items: flex-end;
            margin-bottom: 4px;
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

        /* CUSTOM CHECKBOX LIST CONTAINER FOR MATERI */
        .materi-checkbox-container {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid var(--cui-border-color, #d8dbe0);
            border-radius: 12px;
            padding: 12px;
            background-color: var(--cui-body-bg);
        }

        .materi-item-box {
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            border: 1px solid transparent;
        }

        .materi-item-box:hover {
            background-color: var(--cui-tertiary-bg, #f8f9fa);
        }

        .materi-item-box.selected-item {
            background-color: rgba(111, 66, 193, 0.08);
            border-color: rgba(111, 66, 193, 0.2);
        }

        @media (max-width: 768px) {
            #modalTilawah,
            #modalTilawahCatchup {
                padding: 0 !important;
            }

            #modalTilawah .modal-dialog,
            #modalTilawahCatchup .modal-dialog {
                width: 100%;
                max-width: none;
                height: 100vh;
                height: 100dvh;
                min-height: 100vh;
                min-height: 100dvh;
                margin: 0;
                align-items: stretch;
            }

            #modalTilawah .modal-content,
            #modalTilawahCatchup .modal-content {
                width: 100%;
                height: 100vh;
                height: 100dvh;
                max-height: 100vh;
                max-height: 100dvh;
                border-radius: 0 !important;
            }

            #modalTilawah #formTilawah,
            #modalTilawahCatchup #formTilawahCatchup {
                height: 100%;
            }

            #modalTilawah .modal-header,
            #modalTilawahCatchup .modal-header {
                flex: 0 0 auto;
                padding: calc(0.875rem + env(safe-area-inset-top)) 1rem 0.875rem !important;
            }

            #modalTilawah .modal-body,
            #modalTilawahCatchup .modal-body {
                flex: 1 1 auto;
                padding: 1rem !important;
            }

            #modalTilawah .modal-footer,
            #modalTilawahCatchup .modal-footer {
                flex: 0 0 auto;
                flex-wrap: nowrap;
                gap: 0.5rem;
                padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom)) !important;
                margin-top: 0 !important;
                background: var(--cui-body-bg);
                box-shadow: 0 -8px 24px rgba(0, 0, 0, 0.08);
            }

            #modalTilawah .modal-footer .btn,
            #modalTilawahCatchup .modal-footer .btn {
                margin: 0;
                padding-right: 0.75rem !important;
                padding-left: 0.75rem !important;
                font-size: 0.875rem;
            }

            #modalTilawah #btnSubmitTilawah,
            #modalTilawahCatchup #btnSubmitTilawahCatchup {
                flex: 1 1 auto;
                min-width: 0;
            }

            #modalTilawah .tilawah-status-scroll {
                max-height: none !important;
                overflow: visible !important;
            }

            .fab-group-wrapper {
                position: static;
                display: block;
            }

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

            .fab-right {
                position: fixed;
                right: 20px;
                bottom: 20px;
                z-index: 1050;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

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

            .help-bubble {
                bottom: 75px;
                left: 0px;
                font-size: 11px;
                padding: 8px 12px;
            }

            .bubble-arrow {
                left: 25px;
            }

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

    @include('musyrif.partials.academic-day-status')

    {{-- KPI Cards Section --}}
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

    {{-- Main Data Card --}}
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
                {{-- Tab Tahsin --}}
                <div class="tab-pane fade show active p-3 p-md-4" id="tab-tahsin" role="tabpanel">
                    <div class="table-responsive">
                        <table id="tahsin-table" class="table table-hover align-middle w-100 text-nowrap mb-0">
                            <thead class="bg-body-tertiary">
                                <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                    <th class="border-top-0 ps-3">No.</th>
                                    <th class="border-top-0">Santri</th>
                                    <th class="border-top-0">Buku/Jilid</th>
                                    <th class="border-top-0">Halaman/Materi</th>
                                    <th class="border-top-0">Nilai</th>
                                    <th class="border-top-0">Status</th>
                                    <th class="text-end pe-4 border-top-0">Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab Tilawah --}}
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
    {{-- FAB wrapper --}}
    <div class="fab-group-wrapper">
        <div class="fab-left">
            <div id="bubbleHelp" class="help-bubble">
                <div class="bubble-content">
                    <i class="bi bi-magic me-1"></i> Butuh bantuan? Klik untuk panduan cepat!
                </div>
                <div class="bubble-arrow"></div>
            </div>
            <button type="button" class="btn btn-fab-info" id="btnPanduanTahsin"
                title="Buka Panduan & Pembaruan" aria-label="Buka Panduan dan Pembaruan">
                <i class="bi bi-question-circle-fill"></i>
            </button>
        </div>

        <div class="fab-right">
            <button class="btn btn-warning text-dark btn-fab-main shadow" id="btnTilawahCatchup"
                @disabled(!($academicDayContext['academic_input_open'] ?? false))
                title="{{ ($academicDayContext['academic_input_open'] ?? false) ? 'Tilawah Susulan' : $academicDayContext['message'] }}">
                <i class="bi bi-arrow-repeat me-md-2"></i>
                <span class="fab-text">Tilawah Susulan</span>
            </button>
            <button class="btn btn-success text-white btn-fab-main shadow" id="btnAddTilawah"
                @disabled(!($academicDayContext['academic_input_open'] ?? false))
                title="{{ ($academicDayContext['academic_input_open'] ?? false) ? 'Catat Tilawah' : $academicDayContext['message'] }}">
                <i class="bi bi-journal-bookmark me-md-2"></i>
                <span class="fab-text">Catat Tilawah</span>
            </button>
            <button class="btn btn-primary btn-fab-main shadow" id="btnAddTahsin"
                @disabled(!($academicDayContext['academic_input_open'] ?? false))
                title="{{ ($academicDayContext['academic_input_open'] ?? false) ? 'Input Tahsin' : $academicDayContext['message'] }}">
                <i class="bi bi-book me-md-2"></i>
                <span class="fab-text">Input Tahsin Masal</span>
            </button>
        </div>
    </div>

    {{-- MODAL PANDUAN --}}
    <div class="modal fade" id="modalPanduanTahsin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-header px-4 bg-body-tertiary border-bottom-0"
                    style="border-top-left-radius: 25px; border-top-right-radius: 25px;">
                    <h5 class="modal-title fw-bold text-adaptive-purple">
                        <i class="bi bi-lightbulb-fill text-warning me-2"></i>Panduan & Pembaruan
                    </h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <div class="alert alert-warning border-0 rounded-4 mb-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark">PEMBARUAN</span>
                            <strong class="text-warning-emphasis">Lanjut dari bacaan terakhir</strong>
                        </div>
                        <p class="small mb-2">
                            Musyrif <b>tidak perlu memulai lagi dari Al-Fatihah</b>. Jika masih ada catatan lama,
                            sistem otomatis menyarankan kolom <b>Dari</b> ke ayat setelah bacaan terakhir.
                        </p>
                        <div class="small bg-body rounded-3 p-2 border">
                            Contoh: catatan terakhir <b>Al-Hijr:1–15</b>, maka pencatatan baru dimulai dari
                            <b>Al-Hijr:16</b>. Periksa titik Dari, lalu pilih titik Sampai sesuai bacaan hari ini.
                        </div>
                    </div>

                    <div class="guide-step mb-4" style="border-left-color: #0dcaf0;">
                        <span class="guide-number" style="background: #0dcaf0; color: white;">1</span>
                        <h6 class="fw-bold mb-1 text-info-emphasis">Catat Rentang Tilawah Hari Ini</h6>
                        <p class="text-muted small mb-0">
                            Pada kondisi histori lama, periksa saran titik <b>Dari</b> yang sudah diisi sistem.
                            Selanjutnya pilih titik <b>Sampai</b>. Semua ayat di antara kedua titik otomatis tercatat.
                        </p>
                    </div>

                    <div class="guide-step mb-4">
                        <span class="guide-number">2</span>
                        <h6 class="fw-bold mb-1 text-adaptive-purple">Tentukan Status Setiap Santri</h6>
                        <p class="text-muted small mb-0">
                            Rentang bacaan sama untuk seluruh santri. Hanya status <b>Hadir</b> yang menambah cakupan
                            ayat. Izin, Sakit, dan Alpha akan dicatat sebagai bagian yang masih terlewat.
                        </p>
                    </div>

                    <div class="guide-step mb-4" style="border-left-color: #ffc107;">
                        <span class="guide-number text-dark" style="background: #ffc107;">3</span>
                        <h6 class="fw-bold mb-1 text-warning-emphasis">Gunakan Tilawah Susulan</h6>
                        <p class="text-muted small mb-0">
                            Klik tombol kuning <b>Tilawah Susulan</b> untuk santri yang memiliki ayat terlewat.
                            Susulan selalu dimulai dari ayat pertama yang belum selesai dan boleh dicatat sebagian.
                        </p>
                    </div>

                    <div class="guide-step mb-4" style="border-left-color: #198754;">
                        <span class="guide-number" style="background: #198754; color: white;">4</span>
                        <h6 class="fw-bold mb-1 text-success">Syarat Materi Tahsin</h6>
                        <p class="text-muted small mb-0">
                            Ummi 1–3 dan Drill Materi tidak memiliki syarat Tilawah. Gharib 1, Gharib 2, dan Tajwid
                            hanya dapat dicatat jika cakupan ayat santri sudah kontinu sampai targetnya.
                        </p>
                    </div>

                    <div class="guide-step" style="border-left-color: #6f42c1; margin-bottom: 0;">
                        <span class="guide-number" style="background: #6f42c1; color: white;">5</span>
                        <h6 class="fw-bold mb-1 text-adaptive-purple">Panduan Bisa Dibuka Kembali</h6>
                        <p class="text-muted small mb-0">
                            Informasi ini muncul satu kali setiap sesi. Klik tombol <b>?</b> di pojok kiri bawah
                            kapan pun Musyrif ingin membaca panduan ini kembali.
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm"
                        data-coreui-dismiss="modal">Saya Mengerti, Mulai Mencatat</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL INPUT MASAL (TAHSIN) - FULLY DARK/LIGHT COMPATIBLE --}}
    <div class="modal fade" id="modalTahsin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <form id="formTahsin" class="w-100" novalidate>
                @csrf
                <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                    <div class="modal-header px-4 py-3 border-0">
                        <h5 class="modal-title fw-bold text-primary"><i class="bi bi-people-fill me-2"></i>Materi Hari Ini
                        </h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        {{-- Alert menggunakan bg-warning-subtle agar kontrasnya pas di kedua mode --}}
                        <div
                            class="alert alert-warning border-0 rounded-4 shadow-sm mb-4 bg-warning-subtle text-warning-emphasis">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-info-circle-fill fs-5 me-3 mt-1"></i>
                                <small>Ummi Jilid 1–3 tidak memiliki syarat Tilawah. Khusus Gharib dan Tajwid, sistem
                                    akan melewati santri yang belum menuntaskan Tilawah dari Juz 1 sampai target
                                    buku.</small>
                            </div>
                        </div>

                        <div class="row g-4">
                            {{-- KOLOM KIRI --}}
                            <div class="col-12 col-md-5">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-body-secondary text-uppercase">Buku /
                                        Jilid</label>
                                    <select name="buku" id="buku"
                                        class="form-select form-select-lg bg-body-tertiary border-0 text-body" required>
                                        <option value="">-- Pilih Buku --</option>
                                        <option value="ummi_1">Ummi Jilid 1</option>
                                        <option value="ummi_2">Ummi Jilid 2</option>
                                        <option value="ummi_3">Ummi Jilid 3</option>
                                        <option value="gharib_1">Gharib Jilid 1</option>
                                        <option value="gharib_2">Gharib Jilid 2</option>
                                        <option value="tajwid">Tajwid Ummi</option>
                                        <option value="drill_materi">Drill Materi</option>
                                    </select>
                                </div>
                                {{-- Eligibility Container --}}
                                <div id="eligibility-container" class="mt-3 p-3 bg-light rounded-3 border"
                                    style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-bold text-muted" id="elig-rule-label"
                                            style="font-size: 9px; letter-spacing: 0.5px;">MEMENUHI SYARAT</small>
                                        <small class="fw-bold text-primary" style="font-size: 10px;"><span
                                                id="elig-count">0</span> / <span id="elig-total">0</span> SANTRI</small>
                                    </div>
                                    <div class="progress mb-1" style="height: 6px; border-radius: 10px;">
                                        <div id="elig-progress"
                                            class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%;"></div>
                                    </div>
                                    <small id="elig-warning" class="text-danger fw-bold mt-1"
                                        style="font-size: 10px; display: none; line-height: 1.2;"></small>
                                </div>
                                <div class="mt-3">
                                    <label
                                        class="form-label small fw-bold text-body-secondary text-uppercase">Nilai</label>
                                    <select name="nilai_label"
                                        class="form-select form-select-lg bg-body-tertiary border-0 text-body">
                                        <option value="">-- Belum Dinilai --</option>
                                        <option value="mumtaz">ممتاز (Mumtaz)</option>
                                        <option value="jayyid_jiddan">جيد جدًا (Jayyid Jiddan)</option>
                                        <option value="jayyid">جيد (Jayyid)</option>
                                        <option value="mardud">مردود (Mardud)</option>
                                    </select>
                                </div>
                            </div>

                            {{-- KOLOM KANAN --}}
                            <div class="col-12 col-md-7">
                                <label
                                    class="form-label small fw-bold text-body-secondary text-uppercase d-flex justify-content-between mb-3">
                                    <span id="label-pilih-halaman">PILIH HALAMAN</span>
                                    <span id="counter-materi-selected" class="badge bg-primary rounded-pill">0
                                        Terpilih</span>
                                </label>

                                <div class="materi-checkbox-container p-3 rounded-4 bg-body-tertiary"
                                    id="container-materi-checkbox" style="max-height: 280px; overflow-y: auto;">
                                    <p class="text-body-secondary small text-center my-4">-- Pilih Buku Terlebih Dahulu --
                                    </p>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-body-secondary text-uppercase">Catatan
                                    Umum</label>
                                <textarea name="catatan" id="catatan_tahsin" class="form-control bg-body-tertiary border-0 rounded-4 text-body"
                                    rows="2" placeholder="Tulis catatan jika perlu..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 p-4 pt-0 mt-2">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary text-white rounded-pill px-4 shadow-sm fw-bold"
                            id="btnSubmitTahsin">Simpan Tahsin</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT STATUS TAHSIN --}}
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
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 pt-2">
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

                        <div class="mb-3">
                            <label class="form-label small fw-bold">NILAI MATERI</label>
                            <select name="nilai_label" id="edit_nilai_label"
                                class="form-select bg-body-tertiary border-0 fw-bold" style="font-size: 1.05rem;">
                                <option value="">-- Belum Dinilai --</option>
                                <option value="mumtaz" class="text-success">ممتاز (Mumtaz)</option>
                                <option value="jayyid_jiddan" class="text-primary">جيد جدًا (Jayyid Jiddan)</option>
                                <option value="jayyid" class="text-info">جيد (Jayyid)</option>
                                <option value="mardud" class="text-danger">مردود (Mardud)</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">CATATAN KHUSUS</label>
                            <textarea name="catatan" id="edit_catatan" class="form-control bg-body-tertiary border-0" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
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

    {{-- MODAL DETAIL TAHSIN (REVAMPED FOR LIGHT/DARK THEME) --}}
    <div class="modal fade" id="modalDetailTahsin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 28px;">

                {{-- HEADER --}}
                <div class="p-4 text-center bg-body-tertiary border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge rounded-pill px-3 py-2 shadow-sm" id="det_status_badge"
                            style="font-size: 10px; letter-spacing: 1px;"></span>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <h3 class="fw-bold text-primary mb-1" id="det_buku_halaman" style="letter-spacing: -0.5px;"></h3>
                    <p class="text-muted small fw-bold mb-0 text-uppercase" style="letter-spacing: 2px;"
                        id="det_tanggal_label"></p>
                </div>

                {{-- BODY --}}
                <div class="modal-body p-4">

                    {{-- 1. Info Santri --}}
                    <div class="d-flex align-items-center mb-4 p-3 rounded-4 bg-body-tertiary border-0 shadow-sm">
                        <div class="flex-shrink-0 bg-body rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 50px; height: 50px;">
                            <i class="bi bi-person-fill text-primary fs-4"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0 text-body" id="det_santri_nama"></h6>
                            <small class="text-muted">Santri Binaan</small>
                        </div>
                    </div>

                    {{-- 2. Nilai Capaian (Menggunakan bg-body-secondary agar adaptif di Dark Mode) --}}
                    <div class="mb-4 p-3 rounded-4 bg-body-secondary border-0 text-center shadow-sm">
                        <small class="fw-bold text-muted d-block mb-2" style="letter-spacing: 1px;">NILAI CAPAIAN</small>
                        <h5 class="mb-0 fw-bold" id="det_nilai_label"></h5>
                    </div>

                    {{-- 3. Evaluasi Pengajar --}}
                    <div class="p-3 rounded-4 border-start border-primary border-4 bg-primary-subtle">
                        <small class="fw-bold text-primary d-block mb-2" style="font-size: 10px; letter-spacing: 0.5px;">
                            <i class="bi bi-chat-left-text-fill me-1"></i> EVALUASI PENGAJAR
                        </small>
                        <p class="small mb-0 fst-italic text-body text-opacity-75" id="det_catatan_val"></p>
                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm"
                        data-coreui-dismiss="modal">Tutup Detail</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL INPUT TILAWAH MASAL --}}
    <div class="modal fade" id="modalTilawah" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0" style="border-radius: 25px;">
                <form id="formTilawah" class="d-flex flex-column h-100 overflow-hidden" novalidate>
                    @csrf
                    <div class="modal-header px-4 bg-success bg-opacity-10 border-0">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="bi bi-journal-bookmark-fill me-2"></i>Target Tilawah Hari Ini
                        </h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 small">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            Satu rentang bacaan berlaku untuk <b>seluruh santri binaan</b>. Yang dibedakan hanya status
                            kehadiran masing-masing santri. Hanya status <b>Hadir</b> yang menambah cakupan ayat;
                            ketidakhadiran dapat ditutup melalui <b>Tilawah Susulan</b>.
                        </div>

                        <div id="tilawahLoading" class="text-center py-5">
                            <div class="spinner-border text-success mb-3" role="status"></div>
                            <p class="small text-muted mb-0">Memuat progress Tilawah kelompok...</p>
                        </div>

                        <div id="tilawahFormContent" class="d-none">
                            <div id="tilawahLegacyAlert" class="alert alert-warning border-0 rounded-4 small d-none"></div>

                            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                                <div>
                                    <small class="text-muted fw-bold d-block">PROGRESS TERAKHIR</small>
                                    <span id="tilawahLastProgress" class="fw-bold text-success">Belum ada progress</span>
                                </div>
                                <span id="tilawahTodayMode" class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                                    Input Baru
                                </span>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-body-tertiary h-100">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <label class="small fw-bold text-uppercase mb-0">Dari</label>
                                            <span id="tilawahFromBadge"
                                                class="badge bg-secondary-subtle text-secondary">Otomatis</span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-8">
                                                <label class="form-label small text-muted">Surat</label>
                                                <select name="from_surah_id" id="from_surah_tilawah"
                                                    class="form-select border-0" required>
                                                    <option value="">-- Pilih Surat --</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small text-muted">Ayat</label>
                                                <select name="from_ayat" id="from_ayat_tilawah"
                                                    class="form-select border-0" required disabled>
                                                    <option value="">--</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 rounded-4 bg-success bg-opacity-10 h-100">
                                        <label class="small fw-bold text-uppercase text-success mb-3 d-block">Sampai</label>
                                        <div class="row g-2">
                                            <div class="col-8">
                                                <label class="form-label small text-muted">Surat</label>
                                                <select name="to_surah_id" id="to_surah_tilawah"
                                                    class="form-select border-0" required>
                                                    <option value="">-- Pilih Surat --</option>
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label small text-muted">Ayat</label>
                                                <select name="to_ayat" id="to_ayat_tilawah"
                                                    class="form-select border-0" required disabled>
                                                    <option value="">--</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="tilawahRangePreview" class="alert alert-light border rounded-4 small mb-4">
                                Pilih titik mulai dan titik akhir bacaan.
                            </div>

                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-2">
                                <div>
                                    <label class="form-label small fw-bold mb-0">STATUS SANTRI</label>
                                    <small class="text-muted d-block">Semua santri otomatis berstatus Hadir.</small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-success rounded-pill dropdown-toggle" type="button"
                                        data-coreui-toggle="dropdown">
                                        Terapkan Status ke Semua
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li><button type="button" class="dropdown-item tilawah-status-all" data-status="hadir">Hadir</button></li>
                                        <li><button type="button" class="dropdown-item tilawah-status-all" data-status="izin">Izin</button></li>
                                        <li><button type="button" class="dropdown-item tilawah-status-all" data-status="sakit">Sakit</button></li>
                                        <li><button type="button" class="dropdown-item tilawah-status-all" data-status="alpha">Alpha</button></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="border rounded-4 overflow-hidden mb-3">
                                <div class="table-responsive tilawah-status-scroll" style="max-height: 290px;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="position-sticky top-0 bg-body-tertiary" style="z-index: 1;">
                                            <tr>
                                                <th class="ps-3">Santri</th>
                                                <th style="width: 190px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tilawahStatusRows"></tbody>
                                    </table>
                                </div>
                                <div id="tilawahStatusSummary" class="px-3 py-2 bg-body-tertiary small text-muted"></div>
                            </div>

                            <div>
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
                            id="btnSubmitTilawah" disabled>Simpan Tilawah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL TILAWAH SUSULAN INDIVIDU --}}
    <div class="modal fade" id="modalTilawahCatchup" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0" style="border-radius: 25px;">
                <form id="formTilawahCatchup" class="d-flex flex-column h-100 overflow-hidden" novalidate>
                    @csrf
                    <div class="modal-header px-4 bg-warning bg-opacity-10 border-0">
                        <h5 class="modal-title fw-bold text-warning-emphasis">
                            <i class="bi bi-arrow-repeat me-2"></i>Tilawah Susulan
                        </h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 rounded-4 small">
                            Susulan selalu dimulai dari <b>ayat pertama yang masih terlewat</b>. Musyrif dapat
                            menyelesaikan seluruh celah atau sebagian terlebih dahulu.
                        </div>

                        <div id="tilawahCatchupLoading" class="text-center py-5">
                            <div class="spinner-border text-warning mb-3" role="status"></div>
                            <p class="small text-muted mb-0">Memeriksa celah progress santri...</p>
                        </div>

                        <div id="tilawahCatchupContent" class="d-none">
                            <div id="tilawahCatchupEmpty" class="alert alert-success border-0 rounded-4 d-none"></div>

                            <div id="tilawahCatchupFields">
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">SANTRI</label>
                                    <select name="santri_id" id="tilawah_catchup_santri"
                                        class="form-select bg-body-tertiary border-0" required>
                                        <option value="">-- Pilih Santri --</option>
                                    </select>
                                </div>

                                <div id="tilawahCatchupProgress" class="p-3 rounded-4 bg-body-tertiary small mb-4 d-none"></div>

                                <input type="hidden" name="from_surah_id" id="catchup_from_surah_id">
                                <input type="hidden" name="from_ayat" id="catchup_from_ayat">

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-body-tertiary h-100">
                                            <label class="small fw-bold text-uppercase mb-3 d-block">Dari</label>
                                            <div id="tilawahCatchupFromLabel" class="fw-bold text-warning-emphasis">—</div>
                                            <small class="text-muted">Otomatis dari celah pertama</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 rounded-4 bg-warning bg-opacity-10 h-100">
                                            <label class="small fw-bold text-uppercase mb-3 d-block">Sampai</label>
                                            <div class="row g-2">
                                                <div class="col-8">
                                                    <select name="to_surah_id" id="catchup_to_surah_id"
                                                        class="form-select border-0" required disabled>
                                                        <option value="">-- Surat --</option>
                                                    </select>
                                                </div>
                                                <div class="col-4">
                                                    <select name="to_ayat" id="catchup_to_ayat"
                                                        class="form-select border-0" required disabled>
                                                        <option value="">--</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="tilawahCatchupPreview" class="alert alert-light border rounded-4 small mb-3">
                                    Pilih santri untuk melihat celah Tilawah.
                                </div>

                                <div>
                                    <label class="form-label small fw-bold">CATATAN (OPTIONAL)</label>
                                    <textarea name="catatan" class="form-control bg-body-tertiary border-0" rows="2"
                                        placeholder="Catatan Tilawah Susulan..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm fw-bold"
                            id="btnSubmitTilawahCatchup" disabled>Simpan Susulan</button>
                    </div>
                </form>
            </div>
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
        /**
         * @typedef {Object} MateriItem
         * @property {number} halaman
         * @property {string} materi
         */

        /**
         * HARDCODED MAP DATA MATERI (Mencocokkan spreadsheet Ghorib & Tajwid Anda)
         * @type {Object.<string, MateriItem[]>}
         */
        const DATA_MATERI = {
            'ummi_1': Array.from({
                length: 40
            }, (_, i) => ({
                halaman: i + 1,
                materi: `Halaman ${i+1}`
            })),
            'ummi_2': Array.from({
                length: 40
            }, (_, i) => ({
                halaman: i + 1,
                materi: `Halaman ${i+1}`
            })),
            'ummi_3': Array.from({
                length: 40
            }, (_, i) => ({
                halaman: i + 1,
                materi: `Halaman ${i+1}`
            })),

            // Gharib Jilid 1 (Halaman 1 s/d 14 dari file Ghorib.csv)
            'gharib_1': [{
                    halaman: 1,
                    materi: 'Ana'
                },
                {
                    halaman: 2,
                    materi: 'Anaaba'
                },
                {
                    halaman: 4,
                    materi: 'Afain / Min naba-in'
                },
                {
                    halaman: 5,
                    materi: 'Malaa-ihim / Malaa-ihii'
                },
                {
                    halaman: 6,
                    materi: 'Mi-ataini / Litatsluwa'
                },
                {
                    halaman: 9,
                    materi: 'Laakinna / Walaakinna'
                },
                {
                    halaman: 10,
                    materi: 'Addzunuuna'
                },
                {
                    halaman: 12,
                    materi: 'Tsamuuda'
                },
                {
                    halaman: 13,
                    materi: 'Salaasila / Qowaariiro'
                }
            ],

            // Gharib Jilid 2 (Halaman 15 s/d 26 - Mulai tanda G 2 Yabsuthu)
            'gharib_2': [{
                    halaman: 15,
                    materi: 'Yabsuthu / Basthotan (Shod dibaca Sin)'
                },
                {
                    halaman: 16,
                    materi: 'Amhumulmushoithiruuna / Bimushoithirin'
                },
                {
                    halaman: 17,
                    materi: 'Baroo-atun'
                },
                {
                    halaman: 18,
                    materi: 'Majreeha (Imalah) / Laata-manwna (Isymam)'
                },
                {
                    halaman: 19,
                    materi: 'Iwajaa_Qoyyiman (Saktah)'
                },
                {
                    halaman: 21,
                    materi: "Dho'fin-Dho'fan / Aa'jamiyyu (Tashil)"
                },
                {
                    halaman: 22,
                    materi: 'I-tuunii'
                },
                {
                    halaman: 23,
                    materi: "Bi'sal-ismu (Naql)"
                }
            ],

            // Tajwid Ummi (Dari file Tajwid.csv)
            'tajwid': [
                // Halaman 1 - 11 (Materi Dasar Tajwid)
                {
                    halaman: 1,
                    materi: 'BAB I Hukum nun sukun/tanwin (Idzhar Halqi)'
                },
                {
                    halaman: 2,
                    materi: 'Id-ghom Bighunnah / Id-ghom Bilaghunnah'
                },
                {
                    halaman: 3,
                    materi: "Iqlab / Ikhfa' Haqiqi"
                },
                {
                    halaman: 5,
                    materi: 'BAB II Hukum mim & nun bertasydid / BAB III Hukum mim sukun'
                },
                {
                    halaman: 6,
                    materi: "Ikhfa' Syafawi / BAB IV Id-ghom Mutamatsilain & Mutajanisain"
                },
                {
                    halaman: 7,
                    materi: 'Id-ghom Mutaqoribain / BAB V Hukum Lafadz Allah'
                },
                {
                    halaman: 8,
                    materi: 'BAB VI Hukum Qolqolah / BAB VII Hukum Idzhar Wajib'
                },
                {
                    halaman: 9,
                    materi: "BAB VII Hukum Ro' (Ro' Tafkhim)"
                },
                {
                    halaman: 10,
                    materi: "Ro' Tarqiq"
                },
                {
                    halaman: 11,
                    materi: "BAB VIII Lam Ta'rif (Idzhar Qomariyah & Id-ghom Syamsiyah)"
                },

                // Halaman 12 - 19 (Pembaruan Materi Hukum Mad)
                {
                    halaman: 12,
                    materi: "BAB IX Hukum Mad / Mad Thobi'i / Mad Far'i / Mad Wajib Muttashil"
                },
                {
                    halaman: 13,
                    materi: 'Mad Jaiz Munfashil'
                },
                {
                    halaman: 14,
                    materi: "Mad 'aridl Lissukun"
                },
                {
                    halaman: 15,
                    materi: "Mad 'iwadl / Mad Shilah"
                },
                {
                    halaman: 16,
                    materi: 'Mad Badal'
                },
                {
                    halaman: 17,
                    materi: 'Mad Tamkin / Mad Lin / Mad Lazim Mutsaqqol Kalimi / Mad Lazim Mukhoffaf Kalimi'
                },
                {
                    halaman: 18,
                    materi: 'Mad Lazim Mutsaqqol Harfi / Mad Lazim Mukhoffaf Harfi'
                },
                {
                    halaman: 19,
                    materi: 'Mad Farq'
                }
            ]
        };

        document.addEventListener('DOMContentLoaded', function() {
            const ACADEMIC_INPUT_OPEN = @json($academicDayContext['academic_input_open'] ?? false);
            const ACADEMIC_CLOSED_MESSAGE = @json($academicDayContext['message'] ?? 'Pencatatan sedang ditutup.');

            function guardAcademicInput(event) {
                if (ACADEMIC_INPUT_OPEN) return true;
                event?.preventDefault();
                event?.stopImmediatePropagation();
                Swal.fire('Pencatatan Ditutup', ACADEMIC_CLOSED_MESSAGE, 'info');
                return false;
            }

            if (!ACADEMIC_INPUT_OPEN) {
                $(document).on(
                    'click',
                    '#btnAddTahsin, #btnAddTilawah, #btnTilawahCatchup, .btn-edit, .btn-delete, .btn-edit-tilawah, .btn-delete-tilawah',
                    guardAcademicInput
                );
            }

            let filterTanggal = 'today';
            let tilawahSurahs = [];
            let tilawahGroupProgress = null;
            let tilawahSubmitting = false;
            let tilawahCatchupData = [];
            let tilawahCatchupSubmitting = false;
            const DRILL_MATERI_CATATAN = 'Mengulang Materi Bersama';

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
            const modalTilawahCatchup = new coreui.Modal(
                document.getElementById('modalTilawahCatchup')
            );
            const modalPanduanTahsin = new coreui.Modal(
                document.getElementById('modalPanduanTahsin')
            );
            const modalEditTilawah = new coreui.Modal(document.getElementById('modalEditTilawah'));
            const modalDetailTilawah = new coreui.Modal(document.getElementById('modalDetailTilawah'));

            // Panduan pembaruan tampil sekali per tab/browser session.
            const TILAWAH_GUIDE_SESSION_KEY = 'simtaqu.tilawah-guide.v2.shown';

            $('#btnPanduanTahsin').on('click', function() {
                modalPanduanTahsin.show();
            });

            try {
                if (sessionStorage.getItem(TILAWAH_GUIDE_SESSION_KEY) !== '1') {
                    sessionStorage.setItem(TILAWAH_GUIDE_SESSION_KEY, '1');
                    setTimeout(() => modalPanduanTahsin.show(), 500);
                }
            } catch (error) {
                // Storage dapat dinonaktifkan browser; tombol Panduan tetap bekerja.
            }

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
                        data: 'nilai_format',
                        name: 'nilai_label'
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
            $('#btnAddTilawah').on('click', function() {
                tilawahSubmitting = false;
                $('#formTilawah')[0].reset();
                $('#formTilawah').find('.is-invalid').removeClass('is-invalid');
                $('#tilawahLoading').removeClass('d-none');
                $('#tilawahFormContent').addClass('d-none');
                $('#tilawahStatusRows').empty();
                $('#tilawahLegacyAlert').addClass('d-none').empty();
                $('#btnSubmitTilawah').prop('disabled', true).html('Simpan Tilawah');
                modalTilawah.show();

                $.get("{{ route('musyrif.tilawah.progress') }}")
                    .done(hydrateTilawahForm)
                    .fail(xhr => {
                        modalTilawah.hide();
                        Swal.fire(
                            'Gagal Memuat Form',
                            xhr.responseJSON?.message ?? 'Data progress Tilawah tidak dapat dimuat.',
                            'error'
                        );
                    });
            });

            function hydrateTilawahForm(res) {
                tilawahSurahs = Array.isArray(res.surahs) ? res.surahs : [];
                tilawahGroupProgress = res.group_progress ?? {};

                populateTilawahSurahSelect($('#from_surah_tilawah'));
                populateTilawahSurahSelect($('#to_surah_tilawah'));

                const initialFrom = tilawahGroupProgress.initial_from;
                const initialTo = tilawahGroupProgress.initial_to;

                if (initialFrom) {
                    $('#from_surah_tilawah').val(String(initialFrom.surah_id));
                    populateTilawahAyatSelect(
                        $('#from_ayat_tilawah'),
                        initialFrom.surah_id,
                        initialFrom.ayat
                    );
                } else {
                    populateTilawahAyatSelect($('#from_ayat_tilawah'), null);
                }

                if (initialTo) {
                    $('#to_surah_tilawah').val(String(initialTo.surah_id));
                    populateTilawahAyatSelect(
                        $('#to_ayat_tilawah'),
                        initialTo.surah_id,
                        initialTo.ayat
                    );
                } else {
                    populateTilawahAyatSelect($('#to_ayat_tilawah'), null);
                }

                $('#tilawahLastProgress').text(
                    tilawahGroupProgress.last_range ?? 'Belum ada progress terstruktur'
                );

                $('#tilawahTodayMode')
                    .text(tilawahGroupProgress.editing_today ? 'Memperbarui Hari Ini' : 'Input Baru')
                    .toggleClass('bg-warning-subtle text-warning', Boolean(tilawahGroupProgress.editing_today))
                    .toggleClass('bg-success-subtle text-success', !tilawahGroupProgress.editing_today);

                $('#btnSubmitTilawah').html(
                    tilawahGroupProgress.editing_today ? 'Perbarui Tilawah Hari Ini' : 'Simpan Tilawah'
                );

                const legacy = tilawahGroupProgress.legacy_reference;
                if (legacy) {
                    const legacyText = [legacy.tanggal, legacy.target, legacy.catatan]
                        .filter(Boolean)
                        .join(' · ');
                    $('#tilawahLegacyAlert')
                        .removeClass('d-none')
                        .html(
                            '<i class="bi bi-exclamation-triangle-fill me-2"></i>' +
                            '<b>Lanjutkan dari bacaan terakhir.</b> Sistem telah menyarankan kolom Dari ke ayat ' +
                            'berikutnya. Periksa saran tersebut, lalu pilih titik Sampai. Jangan mulai ulang dari ' +
                            'Al-Fatihah kecuali memang diperintahkan. <br><span class="d-inline-block mt-1">' +
                            '<b>Catatan terakhir:</b> ' + escapeTilawahHtml(legacyText) + '</span>'
                        );
                }

                setTilawahFromLocked(!legacy);

                renderTilawahStatusRows(res.data_santri ?? []);
                $('textarea[name="catatan"]', '#formTilawah').val(
                    tilawahGroupProgress.note ?? ''
                );
                $('#tilawahLoading').addClass('d-none');
                $('#tilawahFormContent').removeClass('d-none');

                if (tilawahGroupProgress.is_complete && !tilawahGroupProgress.editing_today) {
                    $('#tilawahRangePreview')
                        .removeClass('alert-light alert-success alert-danger')
                        .addClass('alert-warning')
                        .html('<i class="bi bi-trophy-fill me-2"></i>Tilawah kelompok sudah mencapai An-Nas ayat terakhir.');
                    syncTilawahSubmitState();
                    return;
                }

                updateTilawahRangePreview();
            }

            function setTilawahFromLocked(locked) {
                $('#formTilawah .tilawah-from-hidden').remove();
                $('#from_surah_tilawah').prop('disabled', locked);
                $('#from_ayat_tilawah').prop(
                    'disabled',
                    locked || !$('#from_surah_tilawah').val()
                );

                $('#tilawahFromBadge')
                    .text(locked ? 'Otomatis' : 'Tentukan Sekali')
                    .toggleClass('bg-secondary-subtle text-secondary', locked)
                    .toggleClass('bg-warning-subtle text-warning', !locked);

                if (!locked) return;

                $('<input>', {
                    type: 'hidden',
                    name: 'from_surah_id',
                    value: $('#from_surah_tilawah').val(),
                    class: 'tilawah-from-hidden'
                }).appendTo('#formTilawah');
                $('<input>', {
                    type: 'hidden',
                    name: 'from_ayat',
                    value: $('#from_ayat_tilawah').val(),
                    class: 'tilawah-from-hidden'
                }).appendTo('#formTilawah');
            }

            function populateTilawahSurahSelect($select) {
                $select.empty().append('<option value="">-- Pilih Surat --</option>');
                tilawahSurahs.forEach(surah => {
                    $select.append(
                        $('<option>', {
                            value: surah.id,
                            text: `${surah.id}. ${surah.nama}`
                        })
                    );
                });
            }

            function populateTilawahAyatSelect($select, surahId, selectedAyat = null) {
                const surah = tilawahSurahs.find(item => Number(item.id) === Number(surahId));
                $select.empty().append('<option value="">--</option>');

                if (!surah || Number(surah.jumlah_ayat) < 1) {
                    $select.prop('disabled', true);
                    return;
                }

                for (let ayat = 1; ayat <= Number(surah.jumlah_ayat); ayat++) {
                    $select.append($('<option>', {
                        value: ayat,
                        text: ayat
                    }));
                }

                $select.prop('disabled', false);
                if (selectedAyat !== null) $select.val(String(selectedAyat));
            }

            function renderTilawahStatusRows(santris) {
                const $rows = $('#tilawahStatusRows').empty();

                if (!santris.length) {
                    $rows.append(
                        '<tr><td colspan="2" class="text-center text-muted py-4">Belum ada santri binaan aktif.</td></tr>'
                    );
                    updateTilawahStatusSummary();
                    return;
                }

                santris.forEach((santri, index) => {
                    const status = ['hadir', 'izin', 'sakit', 'alpha'].includes(santri.status)
                        ? santri.status
                        : 'hadir';
                    const row = `
                        <tr>
                            <td class="ps-3">
                                <span class="text-muted small me-2">${index + 1}.</span>
                                <span class="fw-semibold">${escapeTilawahHtml(santri.nama)}</span>
                            </td>
                            <td>
                                <select name="statuses[${Number(santri.id)}]"
                                    class="form-select form-select-sm border-0 bg-body-tertiary tilawah-status-select">
                                    <option value="hadir" ${status === 'hadir' ? 'selected' : ''}>Hadir</option>
                                    <option value="izin" ${status === 'izin' ? 'selected' : ''}>Izin</option>
                                    <option value="sakit" ${status === 'sakit' ? 'selected' : ''}>Sakit</option>
                                    <option value="alpha" ${status === 'alpha' ? 'selected' : ''}>Alpha</option>
                                </select>
                            </td>
                        </tr>`;
                    $rows.append(row);
                });

                updateTilawahStatusSummary();
            }

            function updateTilawahStatusSummary() {
                const counts = { hadir: 0, izin: 0, sakit: 0, alpha: 0 };
                $('.tilawah-status-select').each(function() {
                    if (Object.prototype.hasOwnProperty.call(counts, this.value)) {
                        counts[this.value]++;
                    }
                });
                $('#tilawahStatusSummary').text(
                    `Hadir ${counts.hadir} · Izin ${counts.izin} · Sakit ${counts.sakit} · Alpha ${counts.alpha}`
                );
                syncTilawahSubmitState();
            }

            function tilawahQuranIndex(surahId, ayat) {
                let total = Number(ayat);
                for (const surah of tilawahSurahs) {
                    if (Number(surah.id) >= Number(surahId)) break;
                    total += Number(surah.jumlah_ayat);
                }
                return total;
            }

            function tilawahPointLabel(surahId, ayat) {
                const surah = tilawahSurahs.find(item => Number(item.id) === Number(surahId));
                return surah ? `${surah.nama}:${ayat}` : '-';
            }

            function getTilawahFormState() {
                const fromSurahId = $('#from_surah_tilawah').val();
                const fromAyat = $('#from_ayat_tilawah').val();
                const toSurahId = $('#to_surah_tilawah').val();
                const toAyat = $('#to_ayat_tilawah').val();
                const hasRange = Boolean(fromSurahId && fromAyat && toSurahId && toAyat);
                const hasStatuses = $('#tilawahStatusRows .tilawah-status-select').length > 0;
                const isCompleted = Boolean(
                    tilawahGroupProgress?.is_complete &&
                    !tilawahGroupProgress?.editing_today
                );
                let rangeValid = false;

                if (hasRange) {
                    rangeValid = tilawahQuranIndex(toSurahId, toAyat) >=
                        tilawahQuranIndex(fromSurahId, fromAyat);
                }

                return {
                    ready: hasRange && rangeValid && hasStatuses && !isCompleted,
                    hasRange,
                    rangeValid,
                    hasStatuses,
                    isCompleted
                };
            }

            function syncTilawahSubmitState() {
                const state = getTilawahFormState();
                const hardDisabled = tilawahSubmitting ||
                    !state.hasStatuses ||
                    state.isCompleted;

                $('#btnSubmitTilawah')
                    .prop('disabled', hardDisabled)
                    .attr('aria-disabled', hardDisabled ? 'true' : 'false');

                return state;
            }

            function updateTilawahRangePreview() {
                const fromSurahId = $('#from_surah_tilawah').val();
                const fromAyat = $('#from_ayat_tilawah').val();
                const toSurahId = $('#to_surah_tilawah').val();
                const toAyat = $('#to_ayat_tilawah').val();
                const $preview = $('#tilawahRangePreview');

                if (!fromSurahId || !fromAyat || !toSurahId || !toAyat) {
                    $preview
                        .removeClass('alert-success alert-danger alert-warning')
                        .addClass('alert-light')
                        .text('Pilih titik mulai dan titik akhir bacaan.');
                    syncTilawahSubmitState();
                    return;
                }

                const startIndex = tilawahQuranIndex(fromSurahId, fromAyat);
                const endIndex = tilawahQuranIndex(toSurahId, toAyat);
                const total = endIndex - startIndex + 1;

                if (total < 1) {
                    $preview
                        .removeClass('alert-light alert-success alert-warning')
                        .addClass('alert-danger')
                        .html('<i class="bi bi-exclamation-circle-fill me-2"></i>Titik Sampai tidak boleh sebelum titik Dari.');
                    syncTilawahSubmitState();
                    return;
                }

                const fromLabel = tilawahPointLabel(fromSurahId, fromAyat);
                const toLabel = tilawahPointLabel(toSurahId, toAyat);
                $preview
                    .removeClass('alert-light alert-danger alert-warning')
                    .addClass('alert-success')
                    .html(
                        `<i class="bi bi-check-circle-fill me-2"></i><b>${escapeTilawahHtml(fromLabel)} – ` +
                        `${escapeTilawahHtml(toLabel)}</b> akan ditandai terlewati · <b>${total} ayat</b>`
                    );
                syncTilawahSubmitState();
            }

            function escapeTilawahHtml(value) {
                return $('<div>').text(value ?? '').html();
            }

            $('#from_surah_tilawah').on('change', function() {
                populateTilawahAyatSelect($('#from_ayat_tilawah'), this.value, 1);
                updateTilawahRangePreview();
            });

            $('#to_surah_tilawah').on('change', function() {
                populateTilawahAyatSelect($('#to_ayat_tilawah'), this.value, 1);
                updateTilawahRangePreview();
            });

            $('#from_ayat_tilawah, #to_ayat_tilawah').on('change', updateTilawahRangePreview);

            $(document).on('change', '.tilawah-status-select', updateTilawahStatusSummary);

            $(document).on('click', '.tilawah-status-all', function() {
                $('.tilawah-status-select').val($(this).data('status'));
                updateTilawahStatusSummary();
            });

            $('#formTilawah').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btnSubmitTilawah');
                const formState = syncTilawahSubmitState();

                if (!formState.ready) {
                    const message = !formState.hasStatuses
                        ? 'Belum ada santri binaan aktif yang dapat dicatat.'
                        : !formState.hasRange
                            ? 'Lengkapi surat dan ayat pada bagian Dari dan Sampai.'
                            : !formState.rangeValid
                                ? 'Titik Sampai tidak boleh berada sebelum titik Dari.'
                                : 'Progress Tilawah belum dapat disimpan.';
                    Swal.fire('Periksa Form', message, 'warning');
                    return;
                }

                $(this).find('.is-invalid').removeClass('is-invalid');
                tilawahSubmitting = true;
                syncTilawahSubmitState();
                btn.html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');

                $.ajax({
                    url: "{{ route('musyrif.tilawah.masal') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: res => {
                        tilawahSubmitting = false;
                        modalTilawah.hide();
                        btn.html(
                            tilawahGroupProgress?.editing_today
                                ? 'Perbarui Tilawah Hari Ini'
                                : 'Simpan Tilawah'
                        );
                        tableTilawah.ajax.reload();

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
                        tilawahSubmitting = false;
                        btn.html(
                            tilawahGroupProgress?.editing_today
                                ? 'Perbarui Tilawah Hari Ini'
                                : 'Simpan Tilawah'
                        );
                        syncTilawahSubmitState();
                        if (xhr.status === 422) {
                            const res = xhr.responseJSON;
                            if (res.message && !res.errors) Swal.fire({
                                icon: 'warning',
                                title: 'Perhatian',
                                text: res.message
                            });
                            if (res.errors) {
                                $.each(res.errors, (k) => $(`[name="${k}"]`).addClass('is-invalid'));
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Periksa Form',
                                    text: Object.values(res.errors).flat()[0] ?? 'Data Tilawah belum valid.'
                                });
                            }
                        } else {
                            Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
                        }
                    }
                });
            });

            /* =========================================================
               MODAL & FORM LOGIC: TILAWAH SUSULAN
               ========================================================= */
            $('#btnTilawahCatchup').on('click', function() {
                tilawahCatchupSubmitting = false;
                tilawahCatchupData = [];
                $('#formTilawahCatchup')[0].reset();
                $('#formTilawahCatchup').find('.is-invalid').removeClass('is-invalid');
                $('#tilawahCatchupLoading').removeClass('d-none');
                $('#tilawahCatchupContent').addClass('d-none');
                $('#tilawahCatchupEmpty').addClass('d-none').empty();
                $('#tilawahCatchupFields').removeClass('d-none');
                $('#btnSubmitTilawahCatchup').prop('disabled', true).html('Simpan Susulan');
                modalTilawahCatchup.show();

                $.get("{{ route('musyrif.tilawah.catchup.options') }}")
                    .done(hydrateTilawahCatchupForm)
                    .fail(xhr => {
                        modalTilawahCatchup.hide();
                        Swal.fire(
                            'Gagal Memuat Susulan',
                            xhr.responseJSON?.message ?? 'Data celah Tilawah tidak dapat dimuat.',
                            'error'
                        );
                    });
            });

            function hydrateTilawahCatchupForm(res) {
                tilawahCatchupData = Array.isArray(res.data_santri) ? res.data_santri : [];
                tilawahSurahs = Array.isArray(res.surahs) ? res.surahs : tilawahSurahs;
                const $santri = $('#tilawah_catchup_santri')
                    .empty()
                    .append('<option value="">-- Pilih Santri --</option>');

                tilawahCatchupData.forEach(item => {
                    const gap = item.gap;
                    $santri.append($('<option>', {
                        value: item.id,
                        text: `${item.nama} · ${gap.total_ayat} ayat terlewat`
                    }));
                });

                $('#tilawahCatchupLoading').addClass('d-none');
                $('#tilawahCatchupContent').removeClass('d-none');

                if (!tilawahCatchupData.length) {
                    $('#tilawahCatchupFields').addClass('d-none');
                    $('#tilawahCatchupEmpty')
                        .removeClass('d-none')
                        .html(
                            '<i class="bi bi-check-circle-fill me-2"></i>' +
                            '<b>Tidak ada celah.</b> Semua santri sudah mengikuti progress kelompok.'
                        );
                }
            }

            function renderTilawahCatchupSelection() {
                const santriId = Number($('#tilawah_catchup_santri').val());
                const item = tilawahCatchupData.find(row => Number(row.id) === santriId);

                if (!item) {
                    $('#tilawahCatchupProgress').addClass('d-none').empty();
                    $('#tilawahCatchupFromLabel').text('—');
                    $('#catchup_from_surah_id, #catchup_from_ayat').val('');
                    $('#catchup_to_surah_id, #catchup_to_ayat')
                        .empty()
                        .append('<option value="">--</option>')
                        .prop('disabled', true);
                    $('#tilawahCatchupPreview').text('Pilih santri untuk melihat celah Tilawah.');
                    $('#btnSubmitTilawahCatchup').prop('disabled', true);
                    return;
                }

                const gap = item.gap;
                const coveredLabel = item.covered_through
                    ? tilawahPointLabel(item.covered_through.surah_id, item.covered_through.ayat)
                    : 'Belum dari Al-Fatihah:1';
                const groupLabel = item.group_through
                    ? tilawahPointLabel(item.group_through.surah_id, item.group_through.ayat)
                    : 'Belum ada progress kelompok';

                $('#tilawahCatchupProgress')
                    .removeClass('d-none')
                    .html(
                        `<b>${escapeTilawahHtml(item.nama)}</b><br>` +
                        `Kontinu sampai: <b>${escapeTilawahHtml(coveredLabel)}</b> · ` +
                        `Kelompok sampai: <b>${escapeTilawahHtml(groupLabel)}</b>`
                    );
                $('#catchup_from_surah_id').val(gap.from.surah_id);
                $('#catchup_from_ayat').val(gap.from.ayat);
                $('#tilawahCatchupFromLabel').text(
                    tilawahPointLabel(gap.from.surah_id, gap.from.ayat)
                );

                populateTilawahCatchupToSurahs(gap);
                $('#catchup_to_surah_id').val(String(gap.to.surah_id));
                populateTilawahCatchupAyat(gap, gap.to.ayat);
                updateTilawahCatchupPreview();
            }

            function selectedTilawahCatchupItem() {
                const santriId = Number($('#tilawah_catchup_santri').val());
                return tilawahCatchupData.find(row => Number(row.id) === santriId) ?? null;
            }

            function populateTilawahCatchupToSurahs(gap) {
                const $select = $('#catchup_to_surah_id')
                    .empty()
                    .append('<option value="">-- Surat --</option>');

                tilawahSurahs
                    .filter(surah =>
                        Number(surah.id) >= Number(gap.from.surah_id) &&
                        Number(surah.id) <= Number(gap.to.surah_id)
                    )
                    .forEach(surah => {
                        $select.append($('<option>', {
                            value: surah.id,
                            text: surah.nama
                        }));
                    });

                $select.prop('disabled', false);
            }

            function populateTilawahCatchupAyat(gap, selectedAyat = null) {
                const surahId = Number($('#catchup_to_surah_id').val());
                const surah = tilawahSurahs.find(row => Number(row.id) === surahId);
                const $select = $('#catchup_to_ayat')
                    .empty()
                    .append('<option value="">--</option>');

                if (!surah) {
                    $select.prop('disabled', true);
                    return;
                }

                const minAyat = surahId === Number(gap.from.surah_id)
                    ? Number(gap.from.ayat)
                    : 1;
                const maxAyat = surahId === Number(gap.to.surah_id)
                    ? Number(gap.to.ayat)
                    : Number(surah.jumlah_ayat);

                for (let ayat = minAyat; ayat <= maxAyat; ayat++) {
                    $select.append($('<option>', {
                        value: ayat,
                        text: ayat
                    }));
                }

                $select.prop('disabled', false);
                if (selectedAyat !== null) $select.val(String(selectedAyat));
            }

            function updateTilawahCatchupPreview() {
                const item = selectedTilawahCatchupItem();
                const toSurahId = $('#catchup_to_surah_id').val();
                const toAyat = $('#catchup_to_ayat').val();
                const $preview = $('#tilawahCatchupPreview');

                if (!item || !toSurahId || !toAyat) {
                    $preview.text('Lengkapi titik akhir Tilawah Susulan.');
                    $('#btnSubmitTilawahCatchup').prop('disabled', true);
                    return;
                }

                const start = Number(item.gap.from_index);
                const end = tilawahQuranIndex(toSurahId, toAyat);
                const valid = end >= start && end <= Number(item.gap.to_index);
                const total = end - start + 1;

                $preview
                    .toggleClass('alert-danger', !valid)
                    .toggleClass('alert-success', valid)
                    .removeClass('alert-light')
                    .html(valid
                        ? `<i class="bi bi-check-circle-fill me-2"></i>` +
                            `<b>${total} ayat</b> akan menutup celah dari ` +
                            `<b>${escapeTilawahHtml(tilawahPointLabel(item.gap.from.surah_id, item.gap.from.ayat))}</b>.`
                        : '<i class="bi bi-exclamation-circle-fill me-2"></i>Titik akhir berada di luar celah pertama.'
                    );
                $('#btnSubmitTilawahCatchup').prop(
                    'disabled',
                    !valid || tilawahCatchupSubmitting
                );
            }

            $('#tilawah_catchup_santri').on('change', renderTilawahCatchupSelection);

            $('#catchup_to_surah_id').on('change', function() {
                const item = selectedTilawahCatchupItem();
                if (!item) return;
                populateTilawahCatchupAyat(item.gap, null);
                updateTilawahCatchupPreview();
            });

            $('#catchup_to_ayat').on('change', updateTilawahCatchupPreview);

            $('#formTilawahCatchup').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btnSubmitTilawahCatchup');

                if (btn.prop('disabled') || tilawahCatchupSubmitting) return;

                tilawahCatchupSubmitting = true;
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...'
                );

                $.ajax({
                    url: "{{ route('musyrif.tilawah.catchup.store') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    success: res => {
                        tilawahCatchupSubmitting = false;
                        modalTilawahCatchup.hide();
                        btn.html('Simpan Susulan');
                        tableTilawah.ajax.reload();

                        if (window.AppAlert) AppAlert.success(res.message);
                        else Swal.fire('Berhasil!', res.message, 'success');
                    },
                    error: xhr => {
                        tilawahCatchupSubmitting = false;
                        btn.html('Simpan Susulan');
                        updateTilawahCatchupPreview();
                        const res = xhr.responseJSON;

                        if (xhr.status === 422) {
                            Swal.fire(
                                'Periksa Form',
                                Object.values(res?.errors ?? {}).flat()[0] ??
                                    res?.message ??
                                    'Data Tilawah Susulan belum valid.',
                                'warning'
                            );
                        } else {
                            Swal.fire('Error', res?.message ?? 'Terjadi kesalahan sistem.', 'error');
                        }
                    }
                });
            });

            /* =========================================================
               MODAL & FORM LOGIC: TAHSIN MASAL (CHECKBOX MULTIPLE)
               ========================================================= */
            $('#btnAddTahsin').on('click', () => {
                $('#formTahsin')[0].reset();
                $('#formTahsin').find('.is-invalid').removeClass('is-invalid');
                $('#container-materi-checkbox').html(
                    '<p class="text-muted small text-center my-4">-- Silakan Pilih Buku/Jilid Terlebih Dahulu --</p>'
                );
                $('#counter-materi-selected').text('0 Terpilih').removeClass('bg-primary text-white')
                    .addClass('bg-secondary-subtle text-secondary');
                $('#label-pilih-halaman').text('PILIH HALAMAN');
                $('#catatan_tahsin').prop('readonly', false);
                $('#eligibility-container').hide();
                $('#elig-warning').stop(true, true).hide().text('');
                modalTahsin.show();
            });

            // Handle Perubahan Dropdown Buku -> Render UI Checkbox List Dinamis
            $('#buku').on('change', function() {
                const bukuKey = $(this).val();
                const $container = $('#container-materi-checkbox');
                const $catatan = $('#catatan_tahsin');
                const isDrillMateri = bukuKey === 'drill_materi';

                $container.empty();
                $('#counter-materi-selected').text('0 Terpilih').removeClass('bg-primary text-white')
                    .addClass('bg-secondary-subtle text-secondary');
                $('#label-pilih-halaman').text('PILIH HALAMAN');
                $('#eligibility-container').hide();
                $('#elig-warning').stop(true, true).hide().text('');

                if (isDrillMateri) {
                    $('#label-pilih-halaman').text('HALAMAN TIDAK DIGUNAKAN');
                    $('#counter-materi-selected')
                        .text('Nonaktif')
                        .removeClass('bg-primary text-white')
                        .addClass('bg-secondary-subtle text-secondary');
                    $container.html(`
                        <div class="text-center py-5 px-3 text-body-secondary">
                            <i class="bi bi-arrow-repeat fs-2 d-block mb-2 text-primary"></i>
                            <div class="fw-bold text-body">Drill Materi Bersama</div>
                            <small>Pilihan halaman dinonaktifkan untuk pencatatan ini.</small>
                        </div>
                    `);
                    $catatan.val(DRILL_MATERI_CATATAN).prop('readonly', true);
                } else {
                    if ($catatan.val() === DRILL_MATERI_CATATAN) {
                        $catatan.val('');
                    }
                    $catatan.prop('readonly', false);
                }

                if (!bukuKey) {
                    $container.html(
                        '<p class="text-muted small text-center my-4">-- Silakan Pilih Buku/Jilid Terlebih Dahulu --</p>'
                    );
                    return;
                }

                if (!isDrillMateri) {
                    if (!DATA_MATERI[bukuKey]) {
                        $container.html(
                            '<p class="text-danger small text-center my-4">Materi buku tidak ditemukan.</p>'
                        );
                        return;
                    }

                    // Render Hardcoded Checkbox berdasarkan dataset di atas
                    DATA_MATERI[bukuKey].forEach(item => {
                        $container.append(`
                            <div class="materi-item-box" id="box-materi-${item.halaman}">
                                <div class="form-check w-100 cursor-pointer">
                                    <input class="form-check-input checkbox-materi-item" type="checkbox"
                                        name="halaman[]"
                                        value="${item.halaman}"
                                        id="materi-${item.halaman}"
                                        data-materi-name="${item.materi}">
                                    <label class="form-check-label d-block cursor-pointer fw-semibold w-100 text-body" for="materi-${item.halaman}">
                                        <span class="text-primary me-2">[Hal. ${item.halaman}]</span> ${item.materi}
                                    </label>
                                </div>
                            </div>
                        `);
                    });
                }

                // Fetch Eligibility Check (Sinkronisasi Validasi Tilawah)
                $.ajax({
                    url: "{{ route('musyrif.tahsin.check') }}",
                    type: "GET",
                    data: {
                        buku: bukuKey
                    },
                    beforeSend: function() {
                        $('#eligibility-container').fadeIn('fast');
                        $('#elig-progress').removeClass('bg-success bg-warning').addClass(
                            'bg-primary').css('width', '100%');
                        $('#elig-rule-label').text('MEMERIKSA SYARAT...');
                        $('#elig-count').text('0');
                        $('#elig-total').text('0');
                        $('#elig-warning').stop(true, true).hide().text('');
                    },
                    success: function(res) {
                        const requiresTilawah = res.requires_tilawah !== false;
                        const syaratLabel = res.syarat_label ||
                            (requiresTilawah ? `Juz 1–${res.syarat_juz}` :
                                'Tanpa syarat Tilawah');

                        $('#elig-rule-label').text(
                            requiresTilawah ?
                            `CAKUPAN AYAT KONTINU (${syaratLabel})` :
                            'TANPA SYARAT TILAWAH'
                        );
                        $('#elig-count').text(res.eligible);
                        $('#elig-total').text(res.total);

                        let pct = res.total > 0 ? (res.eligible / res.total) * 100 : 0;
                        $('#elig-progress').css('width', pct + '%');

                        if (res.eligible < res.total) {
                            $('#elig-progress').removeClass('bg-primary bg-success').addClass(
                                'bg-warning');
                            let selisih = res.total - res.eligible;
                            $('#elig-warning')
                                .stop(true, true)
                                .text(
                                    `*Ada ${selisih} santri yang dilewati karena cakupan ayat ${syaratLabel} belum kontinu. Gunakan Tilawah Susulan untuk menutup celah.`
                                )
                                .slideDown('fast');
                        } else {
                            $('#elig-progress').removeClass('bg-primary bg-warning').addClass(
                                'bg-success');
                            $('#elig-warning').stop(true, true).hide().text('');
                        }
                    }
                });
            });

            // Efek Visual Klik Highlight Row & Realtime Badge Counter
            $(document).on('change', '.checkbox-materi-item', function() {
                const halId = $(this).val();
                const isChecked = $(this).is(':checked');
                const $box = $(`#box-materi-${halId}`);

                if (isChecked) {
                    $box.addClass('selected-item');
                } else {
                    $box.removeClass('selected-item');
                }

                // Update Counter
                const totalChecked = $('.checkbox-materi-item:checked').length;
                const $badge = $('#counter-materi-selected');
                $badge.text(`${totalChecked} Terpilih`);

                if (totalChecked > 0) {
                    $badge.removeClass('bg-secondary-subtle text-secondary').addClass(
                        'bg-primary text-white');
                } else {
                    $badge.removeClass('bg-primary text-white').addClass(
                        'bg-secondary-subtle text-secondary');
                }
            });

            // Submit AJAX Form Masal Tahsin
            $('#formTahsin').on('submit', function(e) {
                e.preventDefault();

                // Validasi Client-side: Minimal wajib memilih 1 materi/halaman
                const bukuDipilih = $('#buku').val();
                const isDrillMateri = bukuDipilih === 'drill_materi';

                if (
                    !isDrillMateri &&
                    $('.checkbox-materi-item:checked').length === 0 &&
                    bukuDipilih !== ''
                ) {
                    Swal.fire('Perhatian',
                        'Silakan pilih minimal 1 halaman/materi materi sebelum menyimpan!', 'warning');
                    return;
                }

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
                        btn.prop('disabled', false).html('Catat Materi');

                        // Swal sekarang dinamis berdasarkan icon dari controller
                        Swal.fire({
                            icon: res.icon, // 'success' atau 'warning'
                            title: res.icon === 'warning' ? 'Tindakan Selesai' :
                                'Berhasil!',
                            text: res.message,
                            confirmButtonText: 'Tutup'
                        });
                    },
                    error: xhr => {
                        btn.prop('disabled', false).html('Catat Materi');
                        const res = xhr.responseJSON;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: res.message || 'Terjadi kesalahan sistem.'
                        });
                    }
                });
            });

            /* =========================================================
               CRUD ACTIONS: EDIT & DELETE & DETAIL
               ========================================================= */
            $(document).on('click', '.btn-edit', function() {
                const d = $(this).data();
                $('#edit_id').val(d.id);
                $('#edit_nama_santri').text(d.santri_nama);
                $('#edit_info_materi').text(
                    d.halaman === '' ?
                    d.buku_label :
                    d.buku_label + ' - Halaman ' + d.halaman
                );
                $('#edit_status').val(d.status);
                $('#edit_nilai_label').val(d.nilai_label || ''); // Setel nilai jika ada
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
                    },
                    error: xhr => {
                        Swal.fire(
                            'Gagal!',
                            xhr.responseJSON?.message ?? 'Data Tahsin tidak dapat diperbarui.',
                            'error'
                        );
                    }
                });
            });

            // 3. Update Event Klik Detail
            $(document).on('click', '.btn-detail', function() {
                const d = $(this).data();
                $('#det_santri_nama').text(d.santri_nama);
                $('#det_buku_halaman').text(
                    d.halaman === '' ?
                    d.buku_label :
                    d.buku_label + ' - Hal ' + d.halaman
                );
                $('#det_tanggal_label').text(d.tanggal_label);
                $('#det_catatan_val').text(d.catatan || 'Tidak ada catatan khusus.');

                // Format Text Nilai di Modal Detail dengan bahasa Arab
                const nilaiText = {
                    'mumtaz': '<span class="text-success fs-4" dir="rtl">ممتاز</span>',
                    'jayyid_jiddan': '<span class="text-primary fs-4" dir="rtl">جيد جدًا</span>',
                    'jayyid': '<span class="text-info fs-4" dir="rtl">جيد</span>',
                    'mardud': '<span class="text-danger fs-4" dir="rtl">مردود</span>',
                };
                $('#det_nilai_label').html(nilaiText[d.nilai_label] ||
                    '<span class="text-muted fst-italic">Belum Dinilai</span>');

                // Setel badge status (sama seperti sebelumnya)
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

            $(document).on('click', '.btn-delete', function() {
                handleDelete("{{ url('musyrif/tahsin') }}/" + $(this).data('id'), tableTahsin);
            });

            $(document).on('click', '.btn-delete-tilawah', function() {
                handleDelete("{{ url('musyrif/tilawah') }}/" + $(this).data('id'), tableTilawah);
            });

            $('#formEditTilawah').on('submit', function(e) {
                e.preventDefault();
                let formData = $(this).serialize() + '&_method=PUT';

                $.ajax({
                    url: "{{ url('musyrif/tilawah') }}/" + $('#edit_tilawah_id').val(),
                    type: 'POST',
                    data: formData,
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
                        Swal.fire('Gagal!', xhr.responseJSON?.message ??
                            'Terjadi kesalahan. Cek log console.', 'error');
                        console.log(xhr.responseText);
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
                            },
                            error: xhr => {
                                Swal.fire('Gagal!', xhr.responseJSON?.message ??
                                    'Data tidak dapat dihapus.', 'error');
                            }
                        });
                    }
                });
            }
        });
    </script>
@endpush
