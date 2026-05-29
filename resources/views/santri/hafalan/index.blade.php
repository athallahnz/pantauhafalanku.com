@extends('layouts.app')

@section('title', 'Dashboard Progress Saya')

@section('content')
    <style>
        /* ================= KONSISTENSI TEMA ISLAMIC PURPLE & GLASSMORPHISM ================= */
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
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #9aa0a6;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 0.75rem;
            color: #9aa0a6;
            margin-top: 4px;
            line-height: 1.2;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            transition: var(--transition-smooth);
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

        /* Welcome Banner */
        .welcome-card {
            background: linear-gradient(135deg, var(--islamic-purple-700), #8e44ad);
            border-radius: 24px;
            color: white;
            border: none;
            overflow: hidden;
            position: relative;
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        /* Container Progress */
        .enterprise-progress-container {
            max-height: 400px;
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

        .enterprise-juz,
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

        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        /* TAB NAVIGATION UX */
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

        .modern-tabs-container .nav-link#tab-hafalan-btn.active,
        .modern-tabs-container .nav-link#tab-tahsin-btn.active {
            background-color: var(--islamic-purple-100, #f3e8ff);
            color: var(--islamic-purple-700, #6f42c1) !important;
            opacity: 1;
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.15);
        }

        [data-coreui-theme="dark"] .modern-tabs-container .nav-link#tab-hafalan-btn.active,
        [data-coreui-theme="dark"] .modern-tabs-container .nav-link#tab-tahsin-btn.active {
            background-color: rgba(111, 66, 193, 0.2);
            color: #d8b4fe !important;
        }

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

        /* ADAPTIVE FILTER & DARK MODE FIX */
        .adaptive-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            background: #f8f9fa !important;
        }

        .adaptive-label {
            background: rgba(0, 0, 0, 0.03) !important;
            border: none !important;
            color: #495057 !important;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .adaptive-input {
            background: transparent !important;
            border: none !important;
            color: #212529 !important;
            font-size: 12px;
        }

        [data-coreui-theme="dark"] .adaptive-group {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-coreui-theme="dark"] .adaptive-label {
            color: #ced4da !important;
        }

        [data-coreui-theme="dark"] .adaptive-input {
            color: #fff !important;
        }

        [data-coreui-theme="dark"] .adaptive-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

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

        .table-padding-container {
            padding: 1.5rem !important;
        }

        table.dataTable th,
        table.dataTable td {
            white-space: nowrap !important;
            padding: 1.1rem 1rem !important;
        }
    </style>

    <div class="card welcome-card shadow-lg border-0 mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="fw-bold mb-1">Assalamu'alaikum, {{ $santri->nama ?? 'Santri' }}! 👋</h3>
                    <p class="mb-3 opacity-75">
                        Kelas: {{ $santri->kelas?->nama_kelas ?? '-' }}
                        @if ($santri->nis)
                            <span class="mx-2">|</span> NIS: {{ $santri->nis }}
                        @endif
                    </p>
                    <div class="bg-white bg-opacity-25 rounded-pill px-3 py-2 d-inline-block">
                        <small class="fw-medium">💡 <strong>Motivasi:</strong> "Konsistensi adalah kunci kesuksesan. Terus
                            semangat!"</small>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block text-end">
                    <i class="bi bi-stars" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- NAV TABS --}}
    <div class="px-3 px-md-0 mb-4">
        <ul class="nav nav-pills nav-fill modern-tabs-container gap-2" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active py-3 shadow-none" id="tab-hafalan-btn" data-coreui-toggle="tab"
                    data-coreui-target="#tab-hafalan" type="button" role="tab">
                    <i class="bi bi-award-fill me-2"></i>Data Hafalan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-3 shadow-none" id="tab-tahsin-btn" data-coreui-toggle="tab"
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
        {{-- ======================== TAB HAFALAN ======================== --}}
        <div class="tab-pane fade show active" id="tab-hafalan" role="tabpanel">
            <div class="row g-3 mb-4">
                @php
                    $kpiItems = [
                        [
                            'label' => 'Setor',
                            'value' => $totalSetor ?? 0,
                            'sub' => 'Lulus / Ulang',
                            'color' => 'success',
                            'icon' => 'journal-check',
                            'max' => max(10, $totalSetor ?? 0),
                        ],
                        [
                            'label' => 'Hadir (TS)',
                            'value' => $totalHadirTidakSetor ?? 0,
                            'sub' => 'Tidak Setor',
                            'color' => 'warning',
                            'icon' => 'exclamation-triangle',
                            'max' => max(10, $totalHadirTidakSetor ?? 0),
                        ],
                        [
                            'label' => 'Sakit',
                            'value' => $totalSakit ?? 0,
                            'sub' => 'Izin Sakit',
                            'color' => 'primary',
                            'icon' => 'heart-pulse',
                            'max' => max(10, $totalSakit ?? 0),
                        ],
                        [
                            'label' => 'Izin',
                            'value' => $totalIzin ?? 0,
                            'sub' => 'Izin Syar\'i',
                            'color' => 'secondary',
                            'icon' => 'envelope-paper',
                            'max' => max(10, $totalIzin ?? 0),
                        ],
                        [
                            'label' => 'Alpha',
                            'value' => $totalAlpha ?? 0,
                            'sub' => 'Tanpa Ket.',
                            'color' => 'danger',
                            'icon' => 'x-octagon',
                            'max' => max(10, $totalAlpha ?? 0),
                        ],
                        [
                            'label' => 'Rata Nilai',
                            'value' => $avgNilai ?? 0,
                            'sub' => 'Indeks Prestasi',
                            'color' => 'special',
                            'icon' => 'graph-up-arrow',
                            'max' => 100,
                        ],
                    ];
                @endphp

                @foreach ($kpiItems as $item)
                    <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                        <div class="card kpi-card border-0 h-100 shadow-sm" {!! $item['color'] === 'special'
                            ? 'style="background: linear-gradient(135deg, var(--islamic-purple-50) 0%, #ffffff 100%);"'
                            : '' !!}>
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="kpi-label">{{ $item['label'] }}</div>
                                        <div class="kpi-value text-{{ $item['color'] }}" {!! $item['color'] === 'special' ? 'style="color: var(--islamic-purple-600) !important;"' : '' !!}>
                                            {{ $item['value'] }}</div>
                                    </div>
                                    <div class="kpi-icon shadow-sm rounded-3 p-2 {{ $item['color'] !== 'special' ? 'bg-' . $item['color'] . '-subtle text-' . $item['color'] : '' }}"
                                        {!! $item['color'] === 'special'
                                            ? 'style="background-color: var(--islamic-purple-100); color: var(--islamic-purple-600);"'
                                            : '' !!}>
                                        <i class="bi bi-{{ $item['icon'] }}"></i>
                                    </div>
                                </div>
                                <div class="kpi-progress mt-2">
                                    <div class="progress-bar kpi-progress-bar {{ $item['color'] !== 'special' ? 'bg-' . $item['color'] : '' }}"
                                        style="height: 6px; width: {{ min(100, ((float) $item['value'] / max(1, $item['max'])) * 100) }}%; {!! $item['color'] === 'special' ? 'background-color: var(--islamic-purple-500);' : '' !!}">
                                    </div>
                                </div>
                                <div class="kpi-sub fst-italic mt-1" style="font-size: 11px;">{{ $item['sub'] }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card card-main border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-adaptive-purple">Overall Progress Hafalan</h6>
                            <small class="text-muted">Capaian tahap tertinggi yang diselesaikan per juz.</small>
                        </div>
                        <div class="text-end">
                            <span class="display-6 fw-bold text-primary">{{ $overallPct ?? 0 }}<small
                                    style="font-size: 20px;">%</small></span>
                        </div>
                    </div>
                    <div class="progress" style="height: 16px; border-radius: 50px; background: rgba(0,0,0,0.05);">
                        <div class="progress-bar progress-bar-animated progress-bar-striped"
                            style="width: {{ $overallPct ?? 0 }}%; background-color: var(--islamic-purple-600);"></div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card card-main h-100 border-0">
                        <div class="card-header bg-transparent border-bottom py-3 px-4">
                            <span class="section-title text-adaptive-purple"><i class="bi bi-list-stars me-2"></i>Progress
                                per Juz (Ringkas)</span>
                        </div>
                        <div class="card-body enterprise-progress-container p-4">
                            @foreach ($progressPerJuz ?? [] as $p)
                                @if ($p['pct'] > 0 || $p['tahap'])
                                    <div class="enterprise-progress-row" data-coreui-toggle="tooltip"
                                        title="Juz {{ $p['juz'] }} • Status: {{ $p['status'] }}">
                                        <div class="enterprise-progress-header">
                                            <div class="enterprise-juz text-adaptive-purple">Juz {{ $p['juz'] }}</div>
                                            <div class="enterprise-meta">
                                                @if ($p['tahap'])
                                                    <span class="badge bg-light text-dark border-0 shadow-sm"
                                                        style="font-size: 9px;">{{ strtoupper(str_replace('_', ' ', $p['tahap'])) }}</span>
                                                @endif
                                                <span class="badge bg-{{ $p['color'] }} shadow-sm"
                                                    style="font-size: 9px;">{{ $p['status'] }}</span>
                                                <span class="enterprise-percent">{{ $p['pct'] }}%</span>
                                            </div>
                                        </div>
                                        <div class="progress enterprise-progress">
                                            <div class="progress-bar bg-{{ $p['color'] }}"
                                                data-width="{{ $p['pct'] }}"></div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card card-main h-100 border-0">
                        <div class="card-header bg-transparent border-bottom py-3 px-4">
                            <span class="section-title text-adaptive-purple"><i class="bi bi-activity me-2"></i>Analitik
                                Capaian per Juz (%)</span>
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 250px; position: relative;">
                                <canvas id="chartJuzPct"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-main overflow-hidden border-0">
                <div
                    class="card-header bg-transparent py-3 px-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <span class="section-title text-adaptive-purple"><i class="bi bi-clock-history me-2"></i>Riwayat
                        Timeline Setoran</span>
                    <form id="formExportPdf" action="{{ route('santri.hafalan.export-pdf') }}" method="GET"
                        target="_blank" class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="input-group input-group-sm w-auto adaptive-group">
                            <span class="input-group-text adaptive-label">Dari</span>
                            <input type="date" name="start_date" class="form-control adaptive-input shadow-none">
                        </div>
                        <div class="input-group input-group-sm w-auto adaptive-group">
                            <span class="input-group-text adaptive-label">Sampai</span>
                            <input type="date" name="end_date" class="form-control adaptive-input shadow-none">
                        </div>
                        <button type="button" id="btnCetakPdf"
                            class="btn btn-sm btn-danger text-white rounded-pill px-3 shadow-sm fw-bold">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
                        </button>
                    </form>
                </div>
                <div class="card-body p-0 table-responsive py-4 px-4">
                    <table id="timelineTable" class="table table-hover align-middle w-100 mb-0">
                        <thead class="bg-light bg-opacity-50 text-nowrap">
                            <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                <th class="ps-4">No</th>
                                <th>Tanggal</th>
                                <th>Juz</th>
                                <th>Surah / Ayat</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th class="pe-4">Catatan Musyrif</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- ======================== TAB TAHSIN ======================== --}}
        <div class="tab-pane fade" id="tab-tahsin" role="tabpanel">
            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
                    <div class="card kpi-card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="kpi-label">Hadir</div>
                                    <div class="kpi-value text-success">{{ $tahsinHadir ?? 0 }}</div>
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
                                    <div class="kpi-value text-secondary">{{ $tahsinIzin ?? 0 }}</div>
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
                                    <div class="kpi-value text-primary">{{ $tahsinSakit ?? 0 }}</div>
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
                                    <div class="kpi-value text-danger">{{ $tahsinAlpha ?? 0 }}</div>
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
                                    @if (!empty($lastTahsin))
                                        <div class="kpi-value"
                                            style="color: var(--islamic-purple-600); font-size: 1.6rem;">
                                            {{ strtoupper(str_replace('_', ' ', $lastTahsin->buku ?? '')) }}</div>
                                        <div class="kpi-sub fw-bold text-dark mt-1">Halaman
                                            {{ $lastTahsin->halaman ?? '-' }}</div>
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

            <div class="card card-main mb-4 overflow-hidden border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-adaptive-purple">Overall Tahsin Summary</h6>
                            <small class="text-muted">Kalkulasi penyelesaian dari seluruh kurikulum buku Tahsin.</small>
                        </div>
                        <div class="text-end">
                            <span class="display-6 fw-bold text-primary">{{ $overallTahsinPct ?? 0 }}<small
                                    style="font-size: 20px;">%</small></span>
                        </div>
                    </div>
                    <div class="progress" style="height: 16px; border-radius: 50px; background: rgba(0,0,0,0.05);">
                        <div class="progress-bar progress-bar-animated progress-bar-striped"
                            style="width: {{ $overallTahsinPct ?? 0 }}%; background-color: var(--islamic-purple-600);">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card card-main h-100 border-0">
                        <div class="card-header bg-transparent py-3 px-4 border-bottom text-adaptive-purple">
                            <span class="section-title text-adaptive-purple"><i class="bi bi-list-stars me-2"></i>Progress
                                per Buku/Jilid</span>
                        </div>
                        <div class="card-body enterprise-progress-container p-4">
                            @foreach ($progressPerBuku ?? [] as $p)
                                <div class="enterprise-progress-row" data-coreui-toggle="tooltip"
                                    title="Buku {{ $p['label'] }} • Halaman {{ $p['current'] }} dari {{ $p['max'] }}">
                                    <div class="enterprise-progress-header">
                                        <div class="enterprise-buku text-adaptive-purple">{{ $p['label'] }}</div>
                                        <div class="enterprise-meta">
                                            <span class="badge bg-light text-dark border-0 shadow-sm"
                                                style="font-size: 9px;">HAL
                                                {{ $p['current'] }}/{{ $p['max'] }}</span>
                                            <span class="badge bg-{{ $p['color'] }} shadow-sm"
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
                        <div class="card-header bg-transparent py-3 px-4 border-bottom text-adaptive-purple">
                            <span class="section-title text-adaptive-purple"><i class="bi bi-activity me-2"></i>Analitik
                                Capaian Tahsin</span>
                        </div>
                        <div class="card-body p-4">
                            <div style="height: 250px; position: relative;">
                                <canvas id="chartBukuPct"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-main overflow-hidden border-0">
                <div class="card-header bg-transparent py-3 px-4 border-bottom">
                    <span class="section-title text-adaptive-purple">Timeline Pertemuan Tahsin</span>
                </div>
                <div class="card-body table-padding-container table-responsive p-0 py-4 px-4">
                    <table id="timelineTahsinTable" class="table table-hover align-middle w-100 mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                <th class="ps-4">No</th>
                                <th>Tanggal</th>
                                <th>Buku/Jilid</th>
                                <th>Halaman</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th class="text-end pe-4">Catatan</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- ======================== TAB TILAWAH ======================== --}}
        <div class="tab-pane fade" id="tab-tilawah" role="tabpanel">
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
                                    @if (!empty($lastTilawah) && !empty($lastTilawah->template))
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

            <div class="card card-main mb-4 overflow-hidden border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-0 text-success">Khatam Al-Quran (30 Juz)</h6>
                            <small class="text-muted">Kalkulasi persentase tilawah hingga khatam 30 Juz.</small>
                        </div>
                        <div class="text-end">
                            <span class="display-6 fw-bold text-success">{{ $tilawahPct ?? 0 }}<small
                                    style="font-size: 20px;">%</small></span>
                        </div>
                    </div>
                    <div class="progress" style="height: 16px; border-radius: 50px; background: rgba(0,0,0,0.05);">
                        <div class="progress-bar progress-bar-animated progress-bar-striped bg-success"
                            style="width: {{ $tilawahPct ?? 0 }}%;"></div>
                    </div>
                </div>
            </div>

            <div class="card card-main overflow-hidden border-0">
                <div
                    class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <span class="section-title text-success"><i
                            class="bi bi-journal-bookmark-fill text-success me-2"></i>Timeline Pertemuan Tilawah</span>
                </div>
                <div class="card-body table-padding-container table-responsive p-0 py-4 px-4">
                    <table id="timelineTilawahTable" class="table table-hover align-middle w-100 mb-0">
                        <thead class="bg-success bg-opacity-10">
                            <tr class="text-success small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                <th class="ps-4">No</th>
                                <th>Tanggal</th>
                                <th>Juz & Target</th>
                                <th>Status</th>
                                <th>Nilai</th>
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
            const hasJquery = typeof window.jQuery !== 'undefined';
            const hasDataTable = hasJquery && typeof $.fn.DataTable !== 'undefined';
            const hasChart = typeof window.Chart !== 'undefined';

            const chartJuzLabels = @json(collect($progressPerJuz ?? [])->pluck('juz')->map(fn($j) => 'Juz ' . $j)->values()->all());
            const chartJuzData = @json(collect($progressPerJuz ?? [])->pluck('pct')->map(fn($v) => (float) $v)->values()->all());
            const chartBukuLabels = @json(collect($progressPerBuku ?? [])->pluck('label')->values()->all());
            const chartBukuData = @json(collect($progressPerBuku ?? [])->pluck('pct')->map(fn($v) => (float) $v)->values()->all());

            let juzChartInstance = null;
            let tahsinChartInstance = null;

            function initTooltips() {
                const tooltipEls = document.querySelectorAll(
                    '[data-coreui-toggle="tooltip"], [data-bs-toggle="tooltip"]');

                tooltipEls.forEach(function(el) {
                    if (window.coreui && typeof window.coreui.Tooltip === 'function') {
                        new window.coreui.Tooltip(el);
                    } else if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
                        new window.bootstrap.Tooltip(el);
                    }
                });
            }

            function animateProgressBars() {
                const allBars = document.querySelectorAll('.progress-bar[data-width]');

                allBars.forEach(function(bar, index) {
                    const width = Number(bar.dataset.width || 0);
                    const safeWidth = Math.max(0, Math.min(100, width));

                    bar.style.width = '0%';

                    setTimeout(function() {
                        bar.style.transition = 'width 1.2s cubic-bezier(0.1, 0.5, 0.5, 1)';
                        bar.style.width = safeWidth + '%';
                    }, 150 + (index * 50));
                });
            }

            function chartOptions(maxValue = 100) {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: maxValue,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                };
            }

            function renderJuzChart() {
                const ctxJuz = document.getElementById('chartJuzPct');

                if (!hasChart || !ctxJuz || juzChartInstance) {
                    return;
                }

                juzChartInstance = new Chart(ctxJuz, {
                    type: 'line',
                    data: {
                        labels: chartJuzLabels,
                        datasets: [{
                            label: 'Progress (%)',
                            data: chartJuzData,
                            fill: true,
                            backgroundColor: 'rgba(111, 66, 193, 0.05)',
                            borderColor: '#6f42c1',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#6f42c1'
                        }]
                    },
                    options: chartOptions(100)
                });
            }

            function renderTahsinChart() {
                const ctxBuku = document.getElementById('chartBukuPct');

                if (!hasChart || !ctxBuku || tahsinChartInstance) {
                    return;
                }

                tahsinChartInstance = new Chart(ctxBuku, {
                    type: 'line',
                    data: {
                        labels: chartBukuLabels,
                        datasets: [{
                            label: 'Capaian (%)',
                            data: chartBukuData,
                            fill: true,
                            backgroundColor: 'rgba(111, 66, 193, 0.08)',
                            borderColor: '#6f42c1',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 5,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#6f42c1'
                        }]
                    },
                    options: chartOptions(100)
                });
            }

            function initDataTables() {
                if (!hasDataTable) {
                    return null;
                }

                const tableHafalan = $('#timelineTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 10,
                    ajax: {
                        url: "{{ route('santri.hafalan.timeline') }}",
                        data: function(d) {
                            d.start_date = $('input[name="start_date"]').val();
                            d.end_date = $('input[name="end_date"]').val();
                        }
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false,
                            className: 'ps-4'
                        },
                        {
                            data: 'tanggal',
                            name: 'hafalans.tanggal_setoran',
                            searchable: true
                        },
                        {
                            data: 'juz',
                            name: 'hafalan_templates.juz',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'surah_ayat',
                            name: 'hafalan_templates.label',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'status',
                            name: 'hafalans.status',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'nilai',
                            name: 'hafalans.nilai_label',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'catatan',
                            name: 'hafalans.catatan',
                            orderable: true,
                            searchable: true,
                            className: 'pe-4 text-wrap',
                            defaultContent: '-'
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    language: {
                        searchPlaceholder: 'Cari riwayat hafalan...',
                        zeroRecords: 'Belum ada riwayat hafalan.'
                    }
                });

                const tableTahsin = $('#timelineTahsinTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 10,
                    ajax: "{{ route('santri.tahsin.timeline') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false,
                            className: 'ps-4'
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
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
                            searchable: false,
                            defaultContent: '-'
                        },
                        {
                            data: 'status',
                            name: 'status',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'nilai',
                            name: 'nilai_label',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'catatan',
                            name: 'catatan',
                            orderable: false,
                            searchable: true,
                            className: 'text-end pe-4 text-wrap',
                            width: '30%',
                            defaultContent: '-'
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    language: {
                        searchPlaceholder: 'Cari riwayat tahsin...',
                        zeroRecords: 'Belum ada riwayat tahsin.'
                    }
                });

                const tableTilawah = $('#timelineTilawahTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    pageLength: 10,
                    ajax: "{{ route('santri.tilawah.timeline') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false,
                            className: 'ps-4'
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal',
                            searchable: false
                        },
                        {
                            data: 'target_bacaan',
                            name: 'hafalan_template_id',
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
                            data: 'nilai',
                            name: 'nilai_label',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'catatan',
                            name: 'catatan',
                            orderable: false,
                            searchable: true,
                            className: 'text-end pe-4 text-wrap',
                            width: '30%',
                            defaultContent: '-'
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    language: {
                        searchPlaceholder: 'Cari riwayat tilawah...',
                        zeroRecords: 'Belum ada riwayat tilawah.'
                    }
                });

                $('input[name="start_date"], input[name="end_date"]').on('change', function() {
                    tableHafalan.draw();
                });

                return {
                    tableHafalan,
                    tableTahsin,
                    tableTilawah
                };
            }

            function adjustVisibleDataTables() {
                if (!window.jQuery || !$.fn.DataTable || !$.fn.dataTable) {
                    return;
                }

                try {
                    const tablesApi = $.fn.dataTable.tables({
                        visible: true,
                        api: true
                    });

                    if (!tablesApi) {
                        return;
                    }

                    // Selalu aman untuk adjust columns
                    if (tablesApi.columns && typeof tablesApi.columns === 'function') {
                        tablesApi.columns.adjust();
                    }

                    // Jalankan responsive.recalc() hanya kalau extension Responsive tersedia
                    if (
                        $.fn.dataTable.Responsive &&
                        tablesApi.responsive &&
                        typeof tablesApi.responsive.recalc === 'function'
                    ) {
                        tablesApi.responsive.recalc();
                    }
                } catch (error) {
                    console.warn('DataTables adjust skipped:', error);
                }
            }

            function activateTabManually(button, targetSelector) {
                if (!targetSelector) {
                    return;
                }

                document.querySelectorAll('button[data-coreui-toggle="tab"]').forEach(function(btn) {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                });

                document.querySelectorAll('.tab-pane').forEach(function(pane) {
                    pane.classList.remove('show', 'active');
                });

                button.classList.add('active');
                button.setAttribute('aria-selected', 'true');

                const targetPane = document.querySelector(targetSelector);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                }
            }

            function bindTabEvents() {
                const tabButtons = document.querySelectorAll('button[data-coreui-toggle="tab"]');
                const hasCoreUiTab = !!(window.coreui && typeof window.coreui.Tab === 'function');
                const hasBootstrapTab = !!(window.bootstrap && typeof window.bootstrap.Tab === 'function');

                tabButtons.forEach(function(button) {
                    button.addEventListener('shown.coreui.tab', function(event) {
                        const target = event.target.getAttribute('data-coreui-target');
                        setTimeout(function() {
                            adjustVisibleDataTables();

                            if (target === '#tab-tahsin') {
                                renderTahsinChart();
                            }
                        }, 150);
                    });

                    button.addEventListener('shown.bs.tab', function(event) {
                        const target = event.target.getAttribute('data-coreui-target') || event
                            .target.getAttribute('data-bs-target');

                        setTimeout(function() {
                            adjustVisibleDataTables();

                            if (target === '#tab-tahsin') {
                                renderTahsinChart();
                            }
                        }, 150);
                    });

                    // Fallback apabila library tab CoreUI/Bootstrap tidak tersedia.
                    button.addEventListener('click', function(event) {
                        const target = button.getAttribute('data-coreui-target') || button
                            .getAttribute('data-bs-target');

                        if (!hasCoreUiTab && !hasBootstrapTab) {
                            event.preventDefault();
                            activateTabManually(button, target);
                        }

                        setTimeout(function() {
                            adjustVisibleDataTables();

                            if (target === '#tab-tahsin') {
                                renderTahsinChart();
                            }
                        }, 250);
                    });
                });
            }

            function bindExportPdf() {
                if (!hasJquery) {
                    return;
                }

                $('#btnCetakPdf').on('click', function() {
                    const $form = $('#formExportPdf');
                    const $btn = $(this);
                    const originalHtml = $btn.html();

                    $form.submit();

                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...'
                    );

                    setTimeout(function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }, 3500);
                });
            }

            initTooltips();
            animateProgressBars();
            renderJuzChart();
            initDataTables();
            bindTabEvents();
            bindExportPdf();
        });
    </script>
@endpush
