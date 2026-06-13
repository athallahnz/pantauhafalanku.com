@extends('layouts.app')

@section('title', 'Dashboard Progress Saya')

@section('content')
    <style>
        :root {
            --student-bg: #f5f7fb;
            --student-surface: #ffffff;
            --student-surface-soft: #f8f9fc;
            --student-border: rgba(31, 41, 55, 0.10);
            --student-text: #1f2937;
            --student-muted: #6b7280;
            --student-purple: var(--islamic-purple-600, #6f42c1);
            --student-purple-dark: var(--islamic-purple-700, #59359d);
            --student-purple-soft: rgba(111, 66, 193, 0.10);
            --student-green-soft: rgba(25, 135, 84, 0.11);
            --student-shadow: 0 14px 34px rgba(31, 41, 55, 0.07);
        }

        [data-coreui-theme="dark"] {
            --student-bg: #15151d;
            --student-surface: #20212b;
            --student-surface-soft: #282a35;
            --student-border: rgba(255, 255, 255, 0.10);
            --student-text: #f3f4f6;
            --student-muted: #a9afbb;
            --student-purple-soft: rgba(147, 108, 246, 0.16);
            --student-green-soft: rgba(25, 135, 84, 0.17);
            --student-shadow: 0 16px 36px rgba(0, 0, 0, 0.24);
        }

        .min-w-0 {
            min-width: 0;
        }

        .text-white-75 {
            color: rgba(255, 255, 255, .78);
        }

        .student-dashboard {
            color: var(--student-text);
        }

        .student-card {
            background: var(--student-surface);
            border: 1px solid var(--student-border);
            border-radius: 20px;
            box-shadow: var(--student-shadow);
        }

        .student-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--student-border);
            background: transparent;
        }

        .student-section-title {
            margin: 0;
            font-size: 0.86rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
            color: var(--student-text);
        }

        .student-section-copy {
            margin: 0.25rem 0 0;
            color: var(--student-muted);
            font-size: 0.78rem;
        }

        .welcome-card {
            position: relative;
            overflow: hidden;
            border: 0;
            border-radius: 24px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 16%, rgba(255, 255, 255, .18), transparent 18%),
                linear-gradient(135deg, var(--student-purple-dark), #8e44ad);
            box-shadow: 0 18px 42px rgba(89, 53, 157, 0.24);
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            right: -65px;
            bottom: -80px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .08);
        }

        .welcome-card .card-body {
            position: relative;
            z-index: 1;
        }

        .welcome-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.9rem;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            background: rgba(255, 255, 255, .13);
            font-size: 0.76rem;
        }

        .modern-tabs-container {
            padding: 0.4rem;
            border: 1px solid var(--student-border);
            border-radius: 17px;
            background: var(--student-surface-soft);
        }

        .modern-tabs-container .nav-link {
            min-height: 48px;
            border: 0 !important;
            border-radius: 12px !important;
            color: var(--student-muted) !important;
            font-weight: 750;
            transition: background-color .2s ease, color .2s ease, box-shadow .2s ease;
        }

        .modern-tabs-container .nav-link.active {
            color: var(--student-purple-dark) !important;
            background: var(--student-purple-soft) !important;
            box-shadow: 0 6px 16px rgba(111, 66, 193, 0.12);
        }

        .modern-tabs-container #tab-tilawah-btn.active {
            color: #157347 !important;
            background: var(--student-green-soft) !important;
        }

        [data-coreui-theme="dark"] .modern-tabs-container .nav-link.active {
            color: #d8c6ff !important;
        }

        [data-coreui-theme="dark"] .modern-tabs-container #tab-tilawah-btn.active {
            color: #8fe3b6 !important;
        }

        .kpi-card {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--student-border);
            border-radius: 17px;
            background: var(--student-surface);
            box-shadow: 0 8px 22px rgba(31, 41, 55, 0.05);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--student-shadow);
        }

        .kpi-label {
            margin-bottom: 0.3rem;
            color: var(--student-muted);
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .kpi-value {
            color: var(--student-text);
            font-size: 1.8rem;
            font-weight: 850;
            line-height: 1;
        }

        .kpi-sub {
            margin-top: 0.4rem;
            color: var(--student-muted);
            font-size: 0.72rem;
        }

        .kpi-icon {
            width: 46px;
            height: 46px;
            flex: 0 0 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            font-size: 1.15rem;
        }

        .summary-card {
            padding: 1.25rem;
        }

        .summary-value {
            color: var(--student-purple);
            font-size: 2rem;
            font-weight: 850;
            line-height: 1;
        }

        .summary-progress,
        .enterprise-progress {
            overflow: hidden;
            border-radius: 999px;
            background: var(--student-surface-soft);
        }

        .summary-progress {
            height: 13px;
        }

        .enterprise-progress {
            height: 8px;
        }

        .enterprise-progress-container {
            max-height: 390px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .enterprise-progress-row {
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--student-border);
        }

        .enterprise-progress-row:first-child {
            padding-top: 0;
        }

        .enterprise-progress-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .enterprise-progress-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.55rem;
        }

        .enterprise-progress-name {
            color: var(--student-text);
            font-size: 0.82rem;
            font-weight: 750;
        }

        .enterprise-progress-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: 0.35rem;
        }

        .enterprise-percent {
            min-width: 42px;
            color: var(--student-purple);
            font-size: 0.76rem;
            font-weight: 800;
            text-align: right;
        }

        .chart-wrap {
            position: relative;
            min-height: 280px;
        }

        .student-dashboard .table {
            --cui-table-color: var(--student-text);
            --cui-table-bg: transparent;
            --cui-table-border-color: var(--student-border);
            margin-bottom: 0;
        }

        .student-dashboard table.dataTable th,
        .student-dashboard table.dataTable td {
            padding: 0.9rem 0.85rem !important;
            vertical-align: middle;
            white-space: nowrap;
        }

        .student-dashboard table.dataTable thead th {
            color: var(--student-muted);
            background: var(--student-surface-soft);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.055em;
            text-transform: uppercase;
        }

        .student-dashboard .dataTables_wrapper .dataTables_filter input,
        .student-dashboard .dataTables_wrapper .dataTables_length select {
            color: var(--student-text);
            border: 1px solid var(--student-border);
            border-radius: 10px;
            background: var(--student-surface-soft);
        }

        .student-dashboard .dataTables_wrapper .dataTables_info,
        .student-dashboard .dataTables_wrapper .dataTables_length,
        .student-dashboard .dataTables_wrapper .dataTables_filter {
            color: var(--student-muted) !important;
            font-size: 0.78rem;
        }

        .student-dashboard .page-link,
        .student-dashboard .paginate_button {
            color: var(--student-text) !important;
            border-color: var(--student-border) !important;
            background: var(--student-surface) !important;
        }

        .student-dashboard .page-item.active .page-link,
        .student-dashboard .paginate_button.current {
            color: #fff !important;
            border-color: var(--student-purple) !important;
            background: var(--student-purple) !important;
        }

        .empty-note {
            padding: 1.5rem;
            color: var(--student-muted);
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .welcome-card {
                border-radius: 18px;
            }

            .modern-tabs-container {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .modern-tabs-container .nav-item {
                min-width: 150px;
            }

            .student-card-header {
                padding: 0.9rem 1rem;
            }

            .summary-card {
                padding: 1rem;
            }

            .summary-value {
                font-size: 1.7rem;
            }

            .chart-wrap {
                min-height: 240px;
            }

            .enterprise-progress-header {
                flex-direction: column;
                gap: 0.45rem;
            }

            .enterprise-progress-meta {
                justify-content: flex-start;
            }

            .enterprise-percent {
                text-align: left;
            }

            .dataTables_wrapper .row:first-child,
            .dataTables_wrapper .row:last-child {
                row-gap: 0.75rem;
            }
        }
    </style>

    <div class="student-dashboard">
        {{-- WELCOME --}}
        <div class="card welcome-card mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div class="small text-white-50 fw-semibold mb-2">Dashboard Progress Santri</div>
                        <h2 class="fw-bold mb-2">Assalamu'alaikum, {{ $santri->nama }} 👋</h2>
                        <p class="mb-3 text-white-75">
                            Kelas: <strong>{{ $santri->kelas?->nama_kelas ?? '-' }}</strong>
                            @if ($santri->nis)
                                <span class="mx-2">•</span>NIS: <strong>{{ $santri->nis }}</strong>
                            @endif
                            @if ($santri->musyrif)
                                <span class="mx-2">•</span>Musyrif: <strong>{{ $santri->musyrif->nama }}</strong>
                            @endif
                        </p>
                        <div class="welcome-chip">
                            <i class="bi bi-lightbulb-fill text-warning"></i>
                            Konsistensi adalah kunci kesuksesan. Terus semangat!
                        </div>
                    </div>
                    <div class="col-auto d-none d-md-block">
                        <i class="bi bi-stars" style="font-size:4.4rem; opacity:.28;"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABS --}}
        <ul class="nav nav-pills nav-fill modern-tabs-container gap-2 mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active w-100" id="tab-hafalan-btn" data-coreui-toggle="tab"
                    data-coreui-target="#tab-hafalan" type="button" role="tab" aria-selected="true">
                    <i class="bi bi-award-fill me-2"></i>Data Hafalan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link w-100" id="tab-tahsin-btn" data-coreui-toggle="tab" data-coreui-target="#tab-tahsin"
                    type="button" role="tab" aria-selected="false">
                    <i class="bi bi-book-half me-2"></i>Data Tahsin
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link w-100" id="tab-tilawah-btn" data-coreui-toggle="tab"
                    data-coreui-target="#tab-tilawah" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-journal-bookmark-fill me-2"></i>Data Tilawah
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- ========================================================
                TAB HAFALAN
            ========================================================= --}}
            <div class="tab-pane fade show active" id="tab-hafalan" role="tabpanel">
                @php
                    $hafalanKpi = [
                        [
                            'label' => 'Setor',
                            'value' => $totalSetor ?? 0,
                            'sub' => 'Lulus atau ulang',
                            'color' => 'success',
                            'icon' => 'journal-check',
                        ],
                        [
                            'label' => 'Hadir (TS)',
                            'value' => $totalHadirTidakSetor ?? 0,
                            'sub' => 'Hadir tidak setor',
                            'color' => 'warning',
                            'icon' => 'person-exclamation',
                        ],
                        [
                            'label' => 'Sakit',
                            'value' => $totalSakit ?? 0,
                            'sub' => 'Izin sakit',
                            'color' => 'primary',
                            'icon' => 'heart-pulse',
                        ],
                        [
                            'label' => 'Izin',
                            'value' => $totalIzin ?? 0,
                            'sub' => 'Izin syar\'i',
                            'color' => 'secondary',
                            'icon' => 'envelope-paper',
                        ],
                        [
                            'label' => 'Alpha',
                            'value' => $totalAlpha ?? 0,
                            'sub' => 'Tanpa keterangan',
                            'color' => 'danger',
                            'icon' => 'x-octagon',
                        ],
                        [
                            'label' => 'Rata Nilai',
                            'value' => $avgNilai ?? 0,
                            'sub' => 'Skala 0–100',
                            'color' => 'info',
                            'icon' => 'graph-up-arrow',
                        ],
                    ];
                @endphp

                <div class="row g-3 mb-4">
                    @foreach ($hafalanKpi as $item)
                        <div class="col-6 col-md-4 col-xl-2">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="min-w-0">
                                        <div class="kpi-label">{{ $item['label'] }}</div>
                                        <div class="kpi-value text-{{ $item['color'] }}">{{ $item['value'] }}</div>
                                        <div class="kpi-sub">{{ $item['sub'] }}</div>
                                    </div>
                                    <div class="kpi-icon bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }}">
                                        <i class="bi bi-{{ $item['icon'] }}"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <section class="student-card summary-card mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h3 class="student-section-title">Overall Progress Hafalan</h3>
                            <p class="student-section-copy">Rata-rata tahap tertinggi yang telah lulus pada seluruh 30 Juz.
                            </p>
                        </div>
                        <div class="text-md-end">
                            <div class="summary-value">{{ $overallPct ?? 0 }}%</div>
                            <div class="small text-muted">{{ $juzSelesai ?? 0 }} Juz selesai ujian akhir</div>
                        </div>
                    </div>
                    <div class="progress summary-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                            style="width: {{ $overallPct ?? 0 }}%; background: var(--student-purple);"></div>
                    </div>
                </section>

                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <section class="student-card h-100">
                            <header class="student-card-header">
                                <h3 class="student-section-title"><i class="bi bi-list-stars me-2"></i>Progress per Juz</h3>
                                <p class="student-section-copy">Semua Juz tetap ditampilkan, termasuk yang belum dimulai.
                                </p>
                            </header>
                            <div class="p-4 enterprise-progress-container">
                                @foreach ($progressPerJuz as $p)
                                    <div class="enterprise-progress-row">
                                        <div class="enterprise-progress-header">
                                            <div class="enterprise-progress-name">Juz {{ $p['juz'] }}</div>
                                            <div class="enterprise-progress-meta">
                                                @if ($p['tahap'])
                                                    <span class="badge bg-body-secondary text-body">
                                                        {{ strtoupper(str_replace('_', ' ', $p['tahap'])) }}
                                                    </span>
                                                @endif
                                                <span
                                                    class="badge bg-{{ $p['color'] }} {{ $p['color'] === 'light' ? 'text-dark' : '' }}">
                                                    {{ $p['status'] }}
                                                </span>
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
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="student-card h-100">
                            <header class="student-card-header">
                                <h3 class="student-section-title"><i class="bi bi-activity me-2"></i>Analitik Capaian per
                                    Juz</h3>
                                <p class="student-section-copy">Visualisasi persentase tahapan Hafalan Juz 1 sampai 30.</p>
                            </header>
                            <div class="p-4">
                                <div class="chart-wrap"><canvas id="chartJuzPct"></canvas></div>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="student-card overflow-hidden mb-4">
                    <header class="student-card-header">
                        <h3 class="student-section-title">
                            <i class="bi bi-clock-history me-2"></i>Timeline Setoran Hafalan
                        </h3>
                        <p class="student-section-copy">
                            Riwayat Hafalan mengikuti data yang sama dengan KPI di atas.
                        </p>
                    </header>
                    <div class="p-3 p-lg-4 table-responsive">
                        <table id="timelineTable" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Juz</th>
                                    <th>Surah / Ayat</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Catatan Musyrif</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </section>
            </div>

            {{-- ========================================================
                TAB TAHSIN
            ========================================================= --}}
            <div class="tab-pane fade" id="tab-tahsin" role="tabpanel">
                @php
                    $tahsinKpi = [
                        [
                            'label' => 'Hadir',
                            'value' => $tahsinHadir ?? 0,
                            'color' => 'success',
                            'icon' => 'person-check-fill',
                        ],
                        [
                            'label' => 'Izin',
                            'value' => $tahsinIzin ?? 0,
                            'color' => 'secondary',
                            'icon' => 'envelope-paper',
                        ],
                        [
                            'label' => 'Sakit',
                            'value' => $tahsinSakit ?? 0,
                            'color' => 'primary',
                            'icon' => 'heart-pulse',
                        ],
                        ['label' => 'Alpha', 'value' => $tahsinAlpha ?? 0, 'color' => 'danger', 'icon' => 'x-octagon'],
                    ];
                @endphp

                <div class="row g-3 mb-4">
                    @foreach ($tahsinKpi as $item)
                        <div class="col-6 col-md-3 col-xl-2">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="kpi-label">{{ $item['label'] }}</div>
                                        <div class="kpi-value text-{{ $item['color'] }}">{{ $item['value'] }}</div>
                                    </div>
                                    <div class="kpi-icon bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }}">
                                        <i class="bi bi-{{ $item['icon'] }}"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="col-12 col-md-12 col-xl-4">
                        <div class="kpi-card">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div class="min-w-0">
                                    <div class="kpi-label">Progres Tahsin Terakhir</div>
                                    @if ($lastTahsin)
                                        <div class="fw-bold fs-5 text-body">{{ $lastTahsin->buku_label }}</div>
                                        <div class="kpi-sub">Halaman {{ $lastTahsin->halaman }} •
                                            {{ $lastTahsin->tanggal?->translatedFormat('d M Y') }}</div>
                                    @else
                                        <div class="fw-bold fs-5 text-muted">Belum Ada</div>
                                        <div class="kpi-sub">Belum ada record hadir dengan buku dan halaman.</div>
                                    @endif
                                </div>
                                <div class="kpi-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-book-half"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="student-card summary-card mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h3 class="student-section-title">Overall Tahsin Summary</h3>
                            <p class="student-section-copy">Rata-rata progres halaman pada seluruh kurikulum Tahsin.</p>
                        </div>
                        <div class="summary-value">{{ $overallTahsinPct ?? 0 }}%</div>
                    </div>
                    <div class="progress summary-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                            style="width: {{ $overallTahsinPct ?? 0 }}%; background: var(--student-purple);"></div>
                    </div>
                </section>

                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <section class="student-card h-100">
                            <header class="student-card-header">
                                <h3 class="student-section-title"><i class="bi bi-list-stars me-2"></i>Progress per
                                    Buku/Jilid</h3>
                                <p class="student-section-copy">Seluruh buku ditampilkan meskipun progresnya masih nol.</p>
                            </header>
                            <div class="p-4 enterprise-progress-container">
                                @foreach ($progressPerBuku as $p)
                                    <div class="enterprise-progress-row">
                                        <div class="enterprise-progress-header">
                                            <div class="enterprise-progress-name">{{ $p['label'] }}</div>
                                            <div class="enterprise-progress-meta">
                                                <span class="badge bg-body-secondary text-body">Hal
                                                    {{ $p['current'] }}/{{ $p['max'] }}</span>
                                                <span class="badge bg-{{ $p['color'] }}">{{ $p['status'] }}</span>
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
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="student-card h-100">
                            <header class="student-card-header">
                                <h3 class="student-section-title"><i class="bi bi-activity me-2"></i>Analitik Capaian
                                    Tahsin</h3>
                                <p class="student-section-copy">Persentase halaman tertinggi yang sudah dicapai per buku.
                                </p>
                            </header>
                            <div class="p-4">
                                <div class="chart-wrap"><canvas id="chartBukuPct"></canvas></div>
                            </div>
                        </section>
                    </div>
                </div>

                <section class="student-card overflow-hidden mb-4">
                    <header class="student-card-header">
                        <h3 class="student-section-title"><i class="bi bi-clock-history me-2"></i>Timeline Pertemuan
                            Tahsin</h3>
                    </header>
                    <div class="p-3 p-lg-4 table-responsive">
                        <table id="timelineTahsinTable" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Buku/Jilid</th>
                                    <th>Halaman</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </section>
            </div>

            {{-- ========================================================
                TAB TILAWAH
            ========================================================= --}}
            <div class="tab-pane fade" id="tab-tilawah" role="tabpanel">
                @php
                    $tilawahKpi = [
                        [
                            'label' => 'Hadir',
                            'value' => $tilawahHadir ?? 0,
                            'color' => 'success',
                            'icon' => 'person-check-fill',
                        ],
                        [
                            'label' => 'Izin',
                            'value' => $tilawahIzin ?? 0,
                            'color' => 'secondary',
                            'icon' => 'envelope-paper',
                        ],
                        [
                            'label' => 'Sakit',
                            'value' => $tilawahSakit ?? 0,
                            'color' => 'primary',
                            'icon' => 'heart-pulse',
                        ],
                        ['label' => 'Alpha', 'value' => $tilawahAlpha ?? 0, 'color' => 'danger', 'icon' => 'x-octagon'],
                    ];
                @endphp

                <div class="row g-3 mb-4">
                    @foreach ($tilawahKpi as $item)
                        <div class="col-6 col-md-3 col-xl-2">
                            <div class="kpi-card">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="kpi-label">{{ $item['label'] }}</div>
                                        <div class="kpi-value text-{{ $item['color'] }}">{{ $item['value'] }}</div>
                                    </div>
                                    <div class="kpi-icon bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }}">
                                        <i class="bi bi-{{ $item['icon'] }}"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="col-12 col-md-12 col-xl-4">
                        <div class="kpi-card">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div class="min-w-0">
                                    <div class="kpi-label text-success">Progres Tilawah Terakhir</div>
                                    @if ($lastTilawah && $lastTilawah->template)
                                        <div class="fw-bold fs-5 text-success">Juz {{ $lastTilawah->template->juz }}</div>
                                        <div class="kpi-sub text-truncate" title="{{ $lastTilawah->template->label }}">
                                            {{ $lastTilawah->template->label }} •
                                            {{ $lastTilawah->tanggal?->translatedFormat('d M Y') }}
                                        </div>
                                    @else
                                        <div class="fw-bold fs-5 text-muted">Belum Ada</div>
                                        <div class="kpi-sub">Belum ada record Tilawah berstatus hadir.</div>
                                    @endif
                                </div>
                                <div class="kpi-icon bg-success-subtle text-success">
                                    <i class="bi bi-book-half"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <section class="student-card summary-card mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h3 class="student-section-title text-success">Khatam Al-Qur'an (30 Juz)</h3>
                            <p class="student-section-copy">Dihitung dari Juz tertinggi pada Tilawah berstatus hadir.</p>
                        </div>
                        <div class="text-md-end">
                            <div class="summary-value text-success">{{ $tilawahPct ?? 0 }}%</div>
                            <div class="small text-muted">Posisi tertinggi: Juz {{ $maxJuzTilawah ?? 0 ?: 0 }}</div>
                        </div>
                    </div>
                    <div class="progress summary-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                            style="width: {{ $tilawahPct ?? 0 }}%;"></div>
                    </div>
                </section>

                <section class="student-card overflow-hidden mb-4">
                    <header class="student-card-header">
                        <h3 class="student-section-title text-success"><i class="bi bi-clock-history me-2"></i>Timeline
                            Pertemuan Tilawah</h3>
                        <p class="student-section-copy">Tilawah tidak memiliki kolom nilai pada database, sehingga tabel
                            hanya menampilkan data yang tersedia.</p>
                    </header>
                    <div class="p-3 p-lg-4 table-responsive">
                        <table id="timelineTilawahTable" class="table table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Juz & Target</th>
                                    <th>Status</th>
                                    <th>Catatan / Detail Ayat</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hasJquery = typeof window.jQuery !== 'undefined';
            const hasDataTable = hasJquery && $.fn && typeof $.fn.DataTable !== 'undefined';
            const hasChart = typeof window.Chart !== 'undefined';

            const chartJuzLabels = @json($progressPerJuz->pluck('juz')->map(fn($juz) => 'Juz ' . $juz)->values());
            const chartJuzData = @json($progressPerJuz->pluck('pct')->map(fn($value) => (float) $value)->values());
            const chartBukuLabels = @json($progressPerBuku->pluck('label')->values());
            const chartBukuData = @json($progressPerBuku->pluck('pct')->map(fn($value) => (float) $value)->values());

            let chartJuz = null;
            let chartBuku = null;
            let tables = null;

            function isDarkTheme() {
                return document.documentElement.getAttribute('data-coreui-theme') === 'dark';
            }

            function chartTheme() {
                const dark = isDarkTheme();

                return {
                    text: dark ? '#cfd3dc' : '#6b7280',
                    grid: dark ? 'rgba(255,255,255,.08)' : 'rgba(31,41,55,.08)',
                    tooltipBg: dark ? '#11131a' : '#ffffff',
                    tooltipText: dark ? '#f3f4f6' : '#1f2937'
                };
            }

            function chartOptions() {
                const theme = chartTheme();

                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: theme.tooltipBg,
                            titleColor: theme.tooltipText,
                            bodyColor: theme.tooltipText,
                            borderColor: theme.grid,
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    return ` ${context.parsed.y}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: theme.text,
                                maxRotation: 60,
                                minRotation: 0
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                color: theme.grid
                            }
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                color: theme.text,
                                callback: value => value + '%'
                            },
                            grid: {
                                color: theme.grid
                            },
                            border: {
                                color: theme.grid
                            }
                        }
                    }
                };
            }

            function renderJuzChart() {
                const canvas = document.getElementById('chartJuzPct');
                if (!hasChart || !canvas || chartJuz) return;

                chartJuz = new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: chartJuzLabels,
                        datasets: [{
                            data: chartJuzData,
                            borderColor: '#6f42c1',
                            backgroundColor: 'rgba(111,66,193,.10)',
                            fill: true,
                            tension: .35,
                            borderWidth: 3,
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: chartOptions()
                });
            }

            function renderBukuChart() {
                const canvas = document.getElementById('chartBukuPct');
                if (!hasChart || !canvas || chartBuku) return;

                chartBuku = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: chartBukuLabels,
                        datasets: [{
                            data: chartBukuData,
                            backgroundColor: 'rgba(111,66,193,.76)',
                            borderRadius: 7,
                            maxBarThickness: 48
                        }]
                    },
                    options: chartOptions()
                });
            }

            function updateChartTheme() {
                [chartJuz, chartBuku].forEach(function(chart) {
                    if (!chart) return;
                    chart.options = chartOptions();
                    chart.update();
                });
            }

            function animateProgressBars() {
                document.querySelectorAll('.progress-bar[data-width]').forEach(function(bar, index) {
                    const width = Math.min(100, Math.max(0, Number(bar.dataset.width || 0)));
                    bar.style.width = '0%';
                    setTimeout(() => bar.style.width = width + '%', 120 + index * 25);
                });
            }

            function dataTableLanguage(placeholder, emptyText) {
                return {
                    search: '',
                    searchPlaceholder: placeholder,
                    lengthMenu: 'Tampilkan _MENU_',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Belum ada data',
                    zeroRecords: emptyText,
                    processing: 'Memuat data...',
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                };
            }

            function commonTableOptions() {
                return {
                    processing: true,
                    serverSide: true,
                    responsive: Boolean($.fn.dataTable && $.fn.dataTable.Responsive),
                    autoWidth: false,
                    pageLength: 10
                };
            }

            function initDataTables() {
                if (!hasDataTable) {
                    console.error('jQuery DataTables belum termuat di layouts.app.');
                    return null;
                }

                const tableHafalan = $('#timelineTable').DataTable({
                    ...commonTableOptions(),
                    ajax: {
                        url: "{{ route('santri.hafalan.timeline') }}"
                    },
                    columns: [{
                            data: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'tanggal',
                            name: 'hafalans.tanggal_setoran'
                        },
                        {
                            data: 'juz',
                            name: 'ht.juz'
                        },
                        {
                            data: 'surah_ayat',
                            name: 'ht.label'
                        },
                        {
                            data: 'status',
                            name: 'hafalans.status'
                        },
                        {
                            data: 'nilai',
                            name: 'hafalans.nilai_label'
                        },
                        {
                            data: 'catatan',
                            name: 'hafalans.catatan',
                            defaultContent: '-',
                            className: 'text-wrap'
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    language: dataTableLanguage('Cari riwayat hafalan...', 'Belum ada riwayat Hafalan.')
                });

                const tableTahsin = $('#timelineTahsinTable').DataTable({
                    ...commonTableOptions(),
                    ajax: "{{ route('santri.tahsin.timeline') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'tanggal',
                            name: 'tanggal'
                        },
                        {
                            data: 'buku_label',
                            name: 'buku'
                        },
                        {
                            data: 'halaman',
                            name: 'halaman',
                            defaultContent: '-'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'nilai',
                            name: 'nilai_label'
                        },
                        {
                            data: 'catatan',
                            name: 'catatan',
                            defaultContent: '-',
                            className: 'text-wrap'
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    language: dataTableLanguage('Cari riwayat tahsin...', 'Belum ada riwayat Tahsin.')
                });

                const tableTilawah = $('#timelineTilawahTable').DataTable({
                    ...commonTableOptions(),
                    ajax: "{{ route('santri.tilawah.timeline') }}",
                    columns: [{
                            data: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'tanggal',
                            name: 'tilawahs.tanggal'
                        },
                        {
                            data: 'target_bacaan',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'status',
                            name: 'tilawahs.status'
                        },
                        {
                            data: 'catatan',
                            name: 'tilawahs.catatan',
                            defaultContent: '-',
                            className: 'text-wrap'
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    language: dataTableLanguage('Cari riwayat tilawah...', 'Belum ada riwayat Tilawah.')
                });

                return {
                    tableHafalan,
                    tableTahsin,
                    tableTilawah
                };
            }

            function adjustVisibleDataTables() {
                if (!hasDataTable) return;

                setTimeout(function() {
                    try {
                        const api = $.fn.dataTable.tables({
                            visible: true,
                            api: true
                        });
                        api.columns.adjust();

                        if ($.fn.dataTable.Responsive && api.responsive && typeof api.responsive.recalc ===
                            'function') {
                            api.responsive.recalc();
                        }
                    } catch (error) {
                        console.warn('Penyesuaian DataTables dilewati:', error);
                    }
                }, 120);
            }

            function bindTabs() {
                document.querySelectorAll('[data-coreui-toggle="tab"]').forEach(function(button) {
                    const handler = function() {
                        const target = button.getAttribute('data-coreui-target');
                        adjustVisibleDataTables();

                        if (target === '#tab-tahsin') {
                            renderBukuChart();
                        }
                    };

                    button.addEventListener('shown.coreui.tab', handler);
                    button.addEventListener('shown.bs.tab', handler);
                    button.addEventListener('click', function() {
                        setTimeout(handler, 180);
                    });
                });
            }

            renderJuzChart();
            animateProgressBars();
            tables = initDataTables();
            bindTabs();

            const observer = new MutationObserver(updateChartTheme);
            observer.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-coreui-theme']
            });
        });
    </script>
@endpush
