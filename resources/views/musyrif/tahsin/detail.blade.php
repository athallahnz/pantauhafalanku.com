@extends('layouts.app')

@section('title', 'Detail Tahsin Santri')

@section('content')
    <style>
        /* ================= TEMA ISLAMIC PURPLE & GLASSMORPHISM ================= */
        :root {
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #fff !important;
        }

        /* KPI & Main Card Style (Glassmorphism) */
        .kpi-card,
        .card-main {
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
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

        /* KPI Elements */
        .kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 5px;
            color: #9aa0a6;
        }

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: var(--transition-smooth);
        }

        .kpi-card:hover .kpi-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        /* Progress Bar Refinement */
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

        /* Container Progress (Buku List) */
        .enterprise-progress-container {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .enterprise-progress-container::-webkit-scrollbar {
            width: 4px;
        }

        .enterprise-progress-container::-webkit-scrollbar-thumb {
            background: #e0e0e0;
            border-radius: 10px;
        }

        .enterprise-progress-row {
            margin-bottom: 18px;
        }

        .enterprise-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .enterprise-buku {
            font-weight: 700;
            font-size: 14px;
        }

        .enterprise-percent {
            font-weight: 700;
            font-size: 12px;
            color: var(--islamic-purple-600);
        }

        .enterprise-progress {
            height: 8px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 999px;
            overflow: hidden;
        }

        .enterprise-progress .progress-bar {
            width: 0;
            transition: width 1.2s cubic-bezier(.4, 0, .2, 1);
            box-shadow: 0 0 10px rgba(111, 66, 193, 0.2);
        }

        /* Section Header & Table */
        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .table-padding-container {
            padding: 1.5rem !important;
        }

        #timelineTable th,
        #timelineTable td {
            white-space: nowrap !important;
            padding: 1.1rem 1rem !important;
        }

        /* FIX DARK THEME */
        [data-coreui-theme="dark"] .kpi-card,
        [data-coreui-theme="dark"] .card-main {
            background: rgba(42, 42, 53, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-coreui-theme="dark"] .kpi-value,
        [data-coreui-theme="dark"] .section-title {
            color: #ffffff !important;
        }

        [data-coreui-theme="dark"] .kpi-label {
            color: #a0a0a0;
        }

        /* ================= TAB NAVIGATION UX (MODERN PILLS) ================= */
        .modern-tabs-container {
            background-color: rgba(0, 0, 0, 0.03);
            border-radius: 16px;
            padding: 6px;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        [data-coreui-theme="dark"] .modern-tabs-container {
            background-color: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.05);
        }

        .modern-tabs-container .nav-link {
            border: none !important;
            border-radius: 12px !important;
            color: #6c757d !important;
            /* Warna inaktif */
            font-weight: 700;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.6;
        }

        .modern-tabs-container .nav-link:hover {
            opacity: 1;
            background-color: rgba(0, 0, 0, 0.02);
        }

        [data-coreui-theme="dark"] .modern-tabs-container .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }

        /* 🟣 Active State: Tahsin */
        .modern-tabs-container .nav-link#tab-tahsin-btn.active {
            background-color: var(--islamic-purple-100, #f3e8ff);
            color: var(--islamic-purple-700, #6f42c1) !important;
            opacity: 1;
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.15);
        }

        [data-coreui-theme="dark"] .modern-tabs-container .nav-link#tab-tahsin-btn.active {
            background-color: rgba(111, 66, 193, 0.2);
            color: #d8b4fe !important;
        }

        /* 🟢 Active State: Tilawah */
        .modern-tabs-container .nav-link#tab-tilawah-btn.active {
            background-color: #d1e7dd;
            color: #0f5132 !important;
            opacity: 1;
            box-shadow: 0 4px 15px rgba(25, 135, 84, 0.15);
        }

        [data-coreui-theme="dark"] .modern-tabs-container .nav-link#tab-tilawah-btn.active {
            background-color: rgba(25, 135, 84, 0.2);
            color: #86efac !important;
        }
    </style>

    <div class="row mb-4 align-items-center px-3 px-md-0 g-3">
        <div class="col-12 col-md-auto text-start">
            <h4 class="fw-bold text-adaptive-purple mb-1">{{ $santri->nama }}</h4>
            <p class="text-muted small mb-0">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Kelas:
                    {{ $santri->kelas?->nama_kelas ?? '-' }}</span>
                @if ($santri->nis)
                    <span class="ms-2 opacity-50">|</span> <span class="ms-2">NIS: {{ $santri->nis }}</span>
                @endif
            </p>
        </div>
        <div class="col-12 col-md-auto ms-auto text-end">
            <a href="{{ route('musyrif.tahsin.index') }}"
                class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold w-100 w-md-auto">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- NAV TABS (MODERN UX) --}}
    <div class="px-3 px-md-0 mb-4">
        <ul class="nav nav-pills nav-fill modern-tabs-container gap-2" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3 shadow-none" id="tab-tahsin-btn" data-coreui-toggle="tab"
                    data-coreui-target="#tab-tahsin" type="button" role="tab">
                    <i class="bi bi-book me-2"></i>Data Tahsin
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 shadow-none" id="tab-tilawah-btn" data-coreui-toggle="tab"
                    data-coreui-target="#tab-tilawah" type="button" role="tab">
                    <i class="bi bi-journal-bookmark-fill me-2"></i>Data Tilawah
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content">
        {{-- ======================== TAB TAHSIN ======================== --}}
        <div class="tab-pane fade show active" id="tab-tahsin" role="tabpanel">
            {{-- KPI SECTION TAHSIN --}}
            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Hadir</div>
                                    <div class="kpi-value text-success">{{ $totalHadir ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-success-subtle text-success shadow-sm rounded-3 p-2"><i
                                        class="bi bi-person-check-fill"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Izin</div>
                                    <div class="kpi-value text-secondary">{{ $totalIzin ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-secondary-subtle text-secondary shadow-sm rounded-3 p-2"><i
                                        class="bi bi-envelope-paper"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Sakit</div>
                                    <div class="kpi-value text-primary">{{ $totalSakit ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-primary-subtle text-primary shadow-sm rounded-3 p-2"><i
                                        class="bi bi-heart-pulse"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Alpha</div>
                                    <div class="kpi-value text-danger">{{ $totalAlpha ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-danger-subtle text-danger shadow-sm rounded-3 p-2"><i
                                        class="bi bi-x-octagon"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-8 col-md-8 col-sm-12">
                    <div class="card kpi-card h-100 border-0 shadow-sm"
                        style="background: linear-gradient(135deg, var(--islamic-purple-50) 0%, #ffffff 100%);">
                        <div class="card-body p-3 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="kpi-label">Progres Tahsin Terakhir</div>
                                    @if ($lastTahsin)
                                        <div class="kpi-value" style="color: var(--islamic-purple-600); font-size: 1.6rem;">
                                            {{ strtoupper(str_replace('_', ' ', $lastTahsin->buku)) }}</div>
                                        <div class="kpi-sub fw-bold text-dark mt-1">Halaman {{ $lastTahsin->halaman }}</div>
                                    @else
                                        <div class="kpi-value text-muted" style="font-size: 1.4rem;">Belum Ada</div>
                                    @endif
                                </div>
                                <div class="kpi-icon shadow-sm rounded-3 p-3"
                                    style="background-color: var(--islamic-purple-100); color: var(--islamic-purple-600); width: 60px; height: 60px;">
                                    <i class="bi bi-book-half fs-3"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- OVERALL PROGRESS TAHSIN --}}
            <div class="card card-main mb-4 overflow-hidden border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-adaptive-purple">Overall Tahsin Summary</h6>
                            <small class="text-muted">Kalkulasi penyelesaian dari seluruh kurikulum buku Tahsin.</small>
                        </div>
                        <div class="text-end"><span class="display-6 fw-bold text-primary">{{ $overallPct }}<small
                                    style="font-size: 20px;">%</small></span></div>
                    </div>
                    <div class="progress" style="height: 16px; border-radius: 50px; background: rgba(0,0,0,0.05);">
                        <div class="progress-bar progress-bar-animated progress-bar-striped"
                            style="width: {{ $overallPct }}%; background-color: var(--islamic-purple-600);"></div>
                    </div>
                </div>
            </div>

            {{-- EXISTING TAHSIN CHARTS & TABLES --}}
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card card-main h-100 border-0">
                        <div class="card-header bg-transparent py-3 px-4 border-bottom text-adaptive-purple"><span
                                class="section-title text-white"><i class="bi bi-list-stars me-2 text-white"></i>Progress
                                per Buku/Jilid</span></div>
                        <div class="card-body enterprise-progress-container p-4">
                            @foreach ($progressPerBuku as $p)
                                <div class="enterprise-progress-row" data-coreui-toggle="tooltip"
                                    title="Buku {{ $p['label'] }} • Halaman {{ $p['current'] }} dari {{ $p['max'] }}">
                                    <div class="enterprise-progress-header">
                                        <div class="enterprise-buku text-adaptive-purple">{{ $p['label'] }}</div>
                                        <div class="enterprise-meta">
                                            <span class="badge bg-light text-dark border-0 shadow-sm"
                                                style="font-size: 9px;">HAL
                                                {{ $p['current'] }}/{{ $p['max'] }}</span>
                                            <span class="badge bg-{{ $p['color'] }} text-primary shadow-sm"
                                                style="font-size: 9px;">{{ $p['status'] }}</span>
                                            <span class="enterprise-percent">{{ $p['pct'] }}%</span>
                                        </div>
                                    </div>
                                    <div class="progress enterprise-progress">
                                        <div class="progress-bar bg-{{ $p['color'] }}"
                                            data-width="{{ $p['pct'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-main h-100 border-0">
                        <div class="card-header bg-transparent py-3 px-4 border-bottom text-adaptive-purple"><span
                                class="section-title text-white"><i class="bi bi-activity me-2 text-white"></i>Analitik
                                Capaian Tahsin</span></div>
                        <div class="card-body p-4"><canvas id="chartBukuPct" style="height: 250px !important;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-main overflow-hidden border-0">
                <div class="card-header bg-transparent py-3 px-4 border-bottom"><span
                        class="section-title text-white">Timeline Pertemuan Tahsin</span></div>
                <div class="card-body table-padding-container table-responsive p-0">
                    <table id="timelineTable" class="table table-hover align-middle w-100 mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                <th class="ps-4">No</th>
                                <th>Tanggal</th>
                                <th>Buku/Jilid</th>
                                <th>Halaman</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Catatan</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- ======================== TAB TILAWAH ======================== --}}
        <div class="tab-pane fade" id="tab-tilawah" role="tabpanel">
            {{-- KPI SECTION TILAWAH --}}
            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Hadir</div>
                                    <div class="kpi-value text-success">{{ $tilawahHadir ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-success-subtle text-success shadow-sm rounded-3 p-2"><i
                                        class="bi bi-person-check-fill"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Izin</div>
                                    <div class="kpi-value text-secondary">{{ $tilawahIzin ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-secondary-subtle text-secondary shadow-sm rounded-3 p-2"><i
                                        class="bi bi-envelope-paper"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Sakit</div>
                                    <div class="kpi-value text-primary">{{ $tilawahSakit ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-primary-subtle text-primary shadow-sm rounded-3 p-2"><i
                                        class="bi bi-heart-pulse"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Alpha</div>
                                    <div class="kpi-value text-danger">{{ $tilawahAlpha ?? 0 }}</div>
                                </div>
                                <div class="kpi-icon bg-danger-subtle text-danger shadow-sm rounded-3 p-2"><i
                                        class="bi bi-x-octagon"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-8 col-md-8 col-sm-12">
                    <div class="card kpi-card h-100 border-0 shadow-sm"
                        style="background: linear-gradient(135deg, #d1e7dd 0%, #ffffff 100%);">
                        <div class="card-body p-3 d-flex flex-column justify-content-center">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="kpi-label text-success">Progres Tilawah Terakhir</div>
                                    @if ($lastTilawah && $lastTilawah->template)
                                        <div class="kpi-value text-success" style="font-size: 1.6rem;">JUZ
                                            {{ $lastTilawah->template->juz }}</div>
                                        <div class="kpi-sub fw-bold text-dark mt-1">{{ $lastTilawah->template->label }}
                                        </div>
                                    @else
                                        <div class="kpi-value text-muted" style="font-size: 1.4rem;">Belum Ada</div>
                                    @endif
                                </div>
                                <div class="kpi-icon shadow-sm rounded-3 p-3 bg-success text-white"
                                    style="width: 60px; height: 60px;"><i class="bi bi-book-half fs-3"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- OVERALL PROGRESS TILAWAH --}}
            <div class="card card-main mb-4 overflow-hidden border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-success">Khatam Al-Quran (30 Juz)</h6>
                            <small class="text-muted">Kalkulasi persentase tilawah hingga khatam 30 Juz.</small>
                        </div>
                        <div class="text-end">
                            <span class="display-6 fw-bold text-success">{{ $tilawahPct }}<small
                                    style="font-size: 20px;">%</small></span>
                        </div>
                    </div>
                    <div class="progress" style="height: 16px; border-radius: 50px; background: rgba(0,0,0,0.05);">
                        <div class="progress-bar progress-bar-animated progress-bar-striped bg-success"
                            style="width: {{ $tilawahPct }}%;"></div>
                    </div>
                </div>
            </div>

            {{-- TABLE TILAWAH TIMELINE --}}
            <div class="card card-main overflow-hidden border-0">
                <div
                    class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <span class="section-title text-white"><i
                            class="bi bi-journal-bookmark-fill text-success me-2"></i>Timeline Pertemuan Tilawah</span>
                </div>
                <div class="card-body table-padding-container table-responsive p-0">
                    <table id="timelineTilawahTable" class="table table-hover align-middle w-100 mb-0">
                        <thead class="bg-success bg-opacity-10">
                            <tr class="text-success small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                <th class="ps-4">No</th>
                                <th>Tanggal</th>
                                <th>Juz & Target</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Catatan / Detail Ayat</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animasi Progress Bars
            const allBars = document.querySelectorAll('.progress-bar[data-width]');
            allBars.forEach((bar, index) => {
                const width = bar.dataset.width;
                bar.style.width = "0%";
                setTimeout(() => {
                    bar.style.transition = "width 1.2s cubic-bezier(0.1, 0.5, 0.5, 1)";
                    bar.style.width = width + "%";
                }, 150 + (index * 50));
            });

            // Tooltip Init
            document.querySelectorAll('[data-coreui-toggle="tooltip"]').forEach(el => new coreui.Tooltip(el));

            // Chart.js untuk Capaian per Buku
            const bukuLabels = @json(collect($progressPerBuku)->pluck('label')->values());
            const bukuPct = @json(collect($progressPerBuku)->pluck('pct')->values());
            const ctx = document.getElementById('chartBukuPct');

            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: bukuLabels,
                        datasets: [{
                            label: 'Capaian (%)',
                            data: bukuPct,
                            fill: true,
                            backgroundColor: 'rgba(111, 66, 193, 0.08)',
                            borderColor: '#6f42c1',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#6f42c1',
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1500,
                            easing: 'easeOutQuart'
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                grid: {
                                    drawBorder: false
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            // DataTable Timeline
            $('#timelineTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('musyrif.tahsin.timeline', $santri->id) }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'tanggal',
                        searchable: false
                    },
                    {
                        data: 'buku_label',
                        name: 'buku',
                        orderable: false,
                        searchable: true
                    },
                    {
                        data: 'halaman',
                        name: 'halaman',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'catatan',
                        name: 'catatan',
                        orderable: false,
                        searchable: true,
                        className: 'text-end pe-4 text-wrap',
                        width: '30%'
                    }
                ],
                order: [
                    [1, 'desc']
                ], // Urut berdasarkan created_at (tanggal)
                pageLength: 10,
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari riwayat...",
                    lengthMenu: "_MENU_ baris",
                    zeroRecords: "Belum ada riwayat tahsin."
                },
                drawCallback: function() {
                    $('.dataTables_filter input').addClass(
                        'form-control rounded-pill px-3 bg-body-tertiary border-0').css('width',
                        '250px');
                    $('.dataTables_length select').addClass('form-select border-0 bg-body-tertiary');
                }
            });

            // DataTable Timeline Tilawah
            $('#timelineTilawahTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('musyrif.tahsin.timeline-tilawah', $santri->id) }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'tanggal',
                        searchable: false
                    },
                    {
                        data: 'target_bacaan',
                        name: 'template.juz',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'catatan',
                        name: 'catatan',
                        orderable: false,
                        searchable: true,
                        className: 'text-end pe-4 text-wrap',
                        width: '30%'
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                pageLength: 10,
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari riwayat tilawah...",
                    lengthMenu: "_MENU_ baris",
                    zeroRecords: "Belum ada riwayat tilawah."
                },
                drawCallback: function() {
                    $('.dataTables_filter input').addClass(
                        'form-control rounded-pill px-3 bg-body-tertiary border-0').css('width',
                        '250px');
                    $('.dataTables_length select').addClass('form-select border-0 bg-body-tertiary');
                }
            });

            // Re-adjust kolom Datatable saat tab Tilawah di-klik (karena DT bisa error lebar jika ditaruh di tab tersembunyi)
            $('button[data-coreui-toggle="tab"]').on('shown.coreui.tab', function(e) {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust();
            });
        });
    </script>
@endpush
