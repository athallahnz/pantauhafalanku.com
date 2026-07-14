@extends('layouts.app')

@section('title', 'Progress Santri')

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
            height: 280px;
            min-height: 280px;
        }

        .chart-wrap canvas {
            width: 100% !important;
            height: 100% !important;
        }

        .fullscreen-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .fullscreen-card-title {
            min-width: 0;
        }

        .btn-card-fullscreen {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            padding: .48rem .78rem;
            border: 1px solid var(--student-border);
            border-radius: 999px;
            color: var(--student-text);
            background: var(--student-surface-soft);
            font-size: .72rem;
            font-weight: 800;
            line-height: 1;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
        }

        .btn-card-fullscreen:hover {
            transform: translateY(-1px);
            border-color: rgba(111, 66, 193, .26);
            color: var(--student-purple-dark);
            background: var(--student-purple-soft);
            box-shadow: 0 8px 18px rgba(31, 41, 55, .08);
        }

        [data-coreui-theme="dark"] .btn-card-fullscreen:hover {
            color: #d8c6ff;
        }

        .fullscreen-target.is-card-fullscreen {
            width: 100vw;
            height: 100vh;
            max-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border-radius: 0;
            background: var(--student-surface);
        }

        .fullscreen-target.is-card-fullscreen .student-card-header {
            flex: 0 0 auto;
        }

        .fullscreen-target.is-card-fullscreen .fullscreen-fill {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .fullscreen-target.is-card-fullscreen .enterprise-progress-container {
            max-height: none;
        }

        .fullscreen-target.is-card-fullscreen .juz-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .fullscreen-target.is-card-fullscreen .chart-wrap {
            height: calc(100vh - 215px);
            min-height: 360px;
        }

        .fullscreen-target:not(.is-card-fullscreen) .chart-wrap {
            height: 280px;
            min-height: 280px;
        }

        @media (min-width: 1400px) {
            .fullscreen-target.is-card-fullscreen .juz-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
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

        .scope-filter-card {
            padding: 1rem 1.15rem;
        }

        .scope-badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .75rem;
            border-radius: 999px;
            color: var(--student-purple-dark);
            background: var(--student-purple-soft);
            font-size: .74rem;
            font-weight: 800;
        }

        .comparison-card {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--student-border);
            border-radius: 16px;
            background: var(--student-surface);
        }

        .comparison-value {
            font-size: 1.35rem;
            font-weight: 850;
            color: var(--student-text);
        }

        .comparison-meta {
            margin-top: .25rem;
            color: var(--student-muted);
            font-size: .72rem;
        }

        .placement-context {
            border: 1px dashed var(--student-border);
            border-radius: 14px;
            background: var(--student-surface-soft);
        }

        .readable-note {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .9rem 1rem;
            border: 1px solid var(--student-border);
            border-radius: 16px;
            background: var(--student-surface-soft);
            color: var(--student-muted);
            font-size: .76rem;
            line-height: 1.55;
        }

        .readable-note i {
            color: var(--student-purple);
            font-size: 1.05rem;
            margin-top: .05rem;
        }

        .juz-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .juz-card {
            position: relative;
            overflow: hidden;
            padding: .95rem;
            border: 1px solid var(--student-border);
            border-radius: 18px;
            background: var(--student-surface);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .juz-card:hover {
            transform: translateY(-2px);
            border-color: rgba(111, 66, 193, .24);
            box-shadow: var(--student-shadow);
        }

        .juz-card.is-complete {
            background:
                linear-gradient(135deg, rgba(25, 135, 84, .09), transparent 48%),
                var(--student-surface);
        }

        .juz-card.is-progress {
            background:
                linear-gradient(135deg, rgba(111, 66, 193, .08), transparent 48%),
                var(--student-surface);
        }

        .juz-card.is-empty {
            opacity: .78;
        }

        .juz-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .75rem;
        }

        .juz-number {
            font-size: 1rem;
            font-weight: 850;
            color: var(--student-text);
        }

        .juz-status {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .36rem .58rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .stage-road {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .32rem;
            margin-bottom: .75rem;
        }

        .stage-dot {
            min-height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--student-border);
            border-radius: 999px;
            background: var(--student-surface-soft);
            color: var(--student-muted);
            font-size: .62rem;
            font-weight: 850;
        }

        .stage-dot.is-done {
            color: #fff;
            border-color: transparent;
            background: linear-gradient(135deg, var(--student-purple), #8e44ad);
            box-shadow: 0 6px 14px rgba(111, 66, 193, .16);
        }

        .stage-dot.is-exam.is-done {
            background: linear-gradient(135deg, #198754, #20c997);
            box-shadow: 0 6px 14px rgba(25, 135, 84, .18);
        }

        .juz-mini-metrics {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .45rem;
            margin-top: .75rem;
        }

        .juz-mini-metric {
            padding: .58rem .65rem;
            border-radius: 13px;
            background: var(--student-surface-soft);
        }

        .juz-mini-label {
            color: var(--student-muted);
            font-size: .61rem;
            font-weight: 800;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .juz-mini-value {
            margin-top: .12rem;
            color: var(--student-text);
            font-size: .9rem;
            font-weight: 850;
        }

        .juz-explain {
            min-height: 38px;
            margin-top: .75rem;
            color: var(--student-muted);
            font-size: .68rem;
            line-height: 1.45;
        }

        .chart-legend-simple {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .6rem;
            margin-bottom: .9rem;
        }

        .chart-legend-item {
            padding: .72rem .8rem;
            border: 1px solid var(--student-border);
            border-radius: 14px;
            background: var(--student-surface-soft);
        }

        .chart-legend-title {
            color: var(--student-text);
            font-size: .75rem;
            font-weight: 850;
        }

        .chart-legend-copy {
            margin-top: .15rem;
            color: var(--student-muted);
            font-size: .66rem;
            line-height: 1.4;
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

            .fullscreen-card-header {
                flex-direction: column;
            }

            .btn-card-fullscreen {
                display: none;
            }

            .summary-card {
                padding: 1rem;
            }

            .summary-value {
                font-size: 1.7rem;
            }

            .chart-wrap {
                height: 240px;
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

            .juz-grid,
            .chart-legend-simple {
                grid-template-columns: 1fr;
            }

            .dataTables_wrapper .row:first-child,
            .dataTables_wrapper .row:last-child {
                row-gap: 0.75rem;
            }
        }
    </style>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body">Progress Santri</h4>
            <span class="text-muted small">Monitoring Hafalan, Tahsin, dan Tilawah dalam satu halaman.</span>
        </div>
        <a href="{{ route('santri.master.index') }}" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Data Santri
        </a>
    </div>

    <div class="student-dashboard">
        {{-- WELCOME --}}
        <div class="card welcome-card mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-3">
                    <div class="col">
                        <div class="small text-white-50 fw-semibold mb-2">Dashboard Progress Santri</div>
                        <h2 class="fw-bold mb-2">{{ $santri->nama }}</h2>
                        <p class="mb-3 text-white-75">
                            Kelas: <strong>{{ $displayKelas }}</strong>

                            @if ($santri->nis)
                                <span class="mx-2">•</span>
                                NIS: <strong>{{ $santri->nis }}</strong>
                            @endif

                            <span class="mx-2">•</span>
                            Musyrif: <strong>{{ $displayMusyrif }}</strong>
                        </p>

                        <div class="d-flex flex-wrap gap-2">
                            <div class="welcome-chip">
                                <i class="bi bi-funnel-fill text-warning"></i>
                                Scope: {{ $scopeLabel }}
                            </div>

                            <div class="welcome-chip">
                                <i class="bi bi-database-check"></i>
                                {{ $scope === 'semester' ? 'Progress Semester' : 'Progress Kumulatif' }}
                            </div>
                        </div>
                    </div>
                    <div class="col-auto d-none d-md-block">
                        <i class="bi bi-stars" style="font-size:4.4rem; opacity:.28;"></i>
                    </div>
                </div>
            </div>
        </div>

        <section class="student-card scope-filter-card mb-4">
            <form method="GET" action="{{ route('admin.santri.master.progress.show', $santri) }}"
                class="row g-3 align-items-end" id="progressScopeForm">

                <div class="col-lg-4">
                    <label class="form-label fw-semibold">
                        Mode Progress
                    </label>

                    <select class="form-select" name="scope" id="progressScope">
                        <option value="semester" @selected($scope === 'semester')>
                            Per Semester
                        </option>

                        <option value="cumulative" @selected($scope === 'cumulative')>
                            Kumulatif Seluruh Semester
                        </option>
                    </select>
                </div>

                <div class="col-lg-5">
                    <label class="form-label fw-semibold">
                        Semester Konteks
                    </label>

                    <select class="form-select" name="semester_id" id="progressSemester"
                        {{ $semesterList->isEmpty() ? 'disabled' : '' }}>

                        @forelse ($semesterList as $semester)
                            <option value="{{ $semester->id }}" @selected((int) $semester->id === (int) $selectedSemesterId)>

                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $semester->nama)) }}
                                —
                                {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $semester->tahunAjaran?->nama ?? '-')) }}

                                @if ($semester->status === 'active' || $semester->is_active)
                                    (Aktif)
                                @endif
                            </option>
                        @empty
                            <option value="">
                                Belum Ada Semester
                            </option>
                        @endforelse
                    </select>
                </div>

                <div class="col-lg-3 d-grid">
                    <button type="submit" class="btn btn-primary fw-semibold">
                        <i class="bi bi-arrow-repeat me-1"></i>
                        Terapkan Scope
                    </button>
                </div>
            </form>

            <div class="placement-context p-3 mt-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="kpi-label">
                            Semester Konteks
                        </div>

                        <div class="fw-bold">
                            {{ $selectedSemester
                                ? \Illuminate\Support\Str::title(str_replace('_', ' ', $selectedSemester->nama)) .
                                    ' — ' .
                                    \Illuminate\Support\Str::title(str_replace('_', ' ', $selectedSemester->tahunAjaran?->nama ?? '-'))
                                : '-' }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="kpi-label">
                            Placement Kelas
                        </div>

                        <div class="fw-bold">
                            {{ $selectedPlacement?->kelas?->nama_kelas ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="kpi-label">
                            Placement Musyrif
                        </div>

                        <div class="fw-bold">
                            {{ $selectedPlacement?->musyrif?->nama ?? '-' }}
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="kpi-label">
                            Status Placement
                        </div>

                        <span class="scope-badge">
                            <i class="bi bi-bookmark-check-fill"></i>
                            {{ \Illuminate\Support\Str::title($selectedPlacement?->status ?? 'Tidak Ada') }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        @if (!empty($warnings))
            <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>

                    <div>
                        <div class="fw-bold mb-1">
                            Pemeriksaan Integritas Progress
                        </div>

                        @foreach ($warnings as $warning)
                            <div class="small">
                                • {{ $warning }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="row g-3 mb-4">
            @foreach ([
            [
                'label' => 'Record Hafalan',
                'semester' => $semesterSummary['record_counts']['hafalan'],
                'cumulative' => $cumulativeSummary['record_counts']['hafalan'],
                'icon' => 'journal-check',
                'color' => 'primary',
            ],
            [
                'label' => 'Record Tahsin',
                'semester' => $semesterSummary['record_counts']['tahsin'],
                'cumulative' => $cumulativeSummary['record_counts']['tahsin'],
                'icon' => 'book-half',
                'color' => 'success',
            ],
            [
                'label' => 'Record Tilawah',
                'semester' => $semesterSummary['record_counts']['tilawah'],
                'cumulative' => $cumulativeSummary['record_counts']['tilawah'],
                'icon' => 'journal-bookmark-fill',
                'color' => 'info',
            ],
        ] as $comparison)
                <div class="col-md-4">
                    <div class="comparison-card">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">
                                    {{ $comparison['label'] }}
                                </div>

                                <div class="comparison-value text-{{ $comparison['color'] }}">
                                    {{ $scope === 'semester' ? $comparison['semester'] : $comparison['cumulative'] }}
                                </div>

                                <div class="comparison-meta">
                                    Semester: {{ $comparison['semester'] }}
                                    • Kumulatif: {{ $comparison['cumulative'] }}
                                </div>
                            </div>

                            <div class="kpi-icon bg-{{ $comparison['color'] }}-subtle text-{{ $comparison['color'] }}">
                                <i class="bi bi-{{ $comparison['icon'] }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
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
                            'label' => 'Setoran Harian',
                            'value' => $totalSetorHarian ?? 0,
                            'sub' => 'Harian + Tahap 1–3',
                            'color' => 'success',
                            'icon' => 'journal-check',
                        ],
                        [
                            'label' => 'Ujian / Juz',
                            'value' => $totalUjian ?? 0,
                            'sub' => 'Juz lulus ujian akhir',
                            'color' => 'primary',
                            'icon' => 'award-fill',
                        ],
                        [
                            'label' => 'Nilai Sementara',
                            'value' => $avgNilaiSementara ?? 0,
                            'sub' => 'Dari harian, maksimal 70',
                            'color' => 'warning',
                            'icon' => 'speedometer2',
                        ],
                        [
                            'label' => 'Nilai Ujian',
                            'value' => $avgNilaiUjian ?? 0 ?: '-',
                            'sub' => 'Nilai final dari ujian',
                            'color' => 'info',
                            'icon' => 'graph-up-arrow',
                        ],
                        [
                            'label' => 'HTS',
                            'value' => $totalHadirTidakSetor ?? 0,
                            'sub' => 'Hadir tidak setor',
                            'color' => 'secondary',
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
                    ];
                @endphp

                <div class="row g-3 mb-4">
                    @foreach ($hafalanKpi as $item)
                        <div class="col-6 col-md-3 col-xl-3">
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
                            <p class="student-section-copy">Rata-rata progress berdasarkan tahapan. Proses harian maksimal
                                70%, ujian akhir membuat progress menjadi 100%.
                            </p>
                        </div>
                        <div class="text-md-end">
                            <div class="summary-value">{{ $overallPct ?? 0 }}%</div>
                            <div class="small text-muted">{{ $juzSelesai ?? 0 }} dari 30 Juz sudah lulus ujian akhir</div>
                        </div>
                    </div>
                    <div class="progress summary-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                            style="width: {{ $overallPct ?? 0 }}%; background: var(--student-purple);"></div>
                    </div>
                </section>

                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <section class="student-card h-100 fullscreen-target" id="progressJuzCard">
                            <header class="student-card-header fullscreen-card-header">
                                <div class="fullscreen-card-title">
                                    <h3 class="student-section-title"><i class="bi bi-list-stars me-2"></i>Progress per
                                        Juz
                                    </h3>
                                    <p class="student-section-copy">
                                        Cara baca sederhana: santri berjalan dari Harian → Tahap 1 → Tahap 2 → Tahap 3 →
                                        Ujian.
                                    </p>
                                </div>

                                <button type="button" class="btn-card-fullscreen d-none d-lg-inline-flex"
                                    data-fullscreen-target="progressJuzCard"
                                    aria-label="Lihat Progress per Juz fullscreen">
                                    <i class="bi bi-arrows-fullscreen"></i>
                                    <span>Fullscreen</span>
                                </button>
                            </header>
                            <div class="p-4 enterprise-progress-container fullscreen-fill">
                                <div class="readable-note mb-3">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <div>
                                        <strong>Patokan nilai:</strong> nilai harian/tahap hanya menjadi nilai sementara dan
                                        dibatasi maksimal 70.
                                        Nilai final baru diambil dari <strong>Ujian Akhir</strong>.
                                    </div>
                                </div>

                                <div class="juz-grid">
                                    @foreach ($progressPerJuz as $p)
                                        @php
                                            $cardClass =
                                                ($p['pct'] ?? 0) >= 100
                                                    ? 'is-complete'
                                                    : (($p['pct'] ?? 0) > 0
                                                        ? 'is-progress'
                                                        : 'is-empty');

                                            $stages = [
                                                'harian' => 'H',
                                                'tahap_1' => 'T1',
                                                'tahap_2' => 'T2',
                                                'tahap_3' => 'T3',
                                                'ujian_akhir' => 'U',
                                            ];
                                        @endphp

                                        <article class="juz-card {{ $cardClass }}">
                                            <div class="juz-card-top">
                                                <div>
                                                    <div class="juz-number">Juz {{ $p['juz'] }}</div>
                                                    <div class="small text-muted">{{ $p['pct'] }}% progress</div>
                                                </div>

                                                <span
                                                    class="juz-status bg-{{ $p['color'] }} {{ $p['color'] === 'light' ? 'text-dark' : 'text-white' }}">
                                                    {{ $p['status'] }}
                                                </span>
                                            </div>

                                            <div class="stage-road" aria-label="Tahapan Juz {{ $p['juz'] }}">
                                                @foreach ($stages as $stageKey => $stageShort)
                                                    <span
                                                        class="stage-dot {{ $p['stage_checks'][$stageKey] ?? false ? 'is-done' : '' }} {{ $stageKey === 'ujian_akhir' ? 'is-exam' : '' }}"
                                                        title="{{ $stageKey === 'ujian_akhir' ? 'Ujian Akhir' : \Illuminate\Support\Str::title(str_replace('_', ' ', $stageKey)) }}">
                                                        {{ $stageShort }}
                                                    </span>
                                                @endforeach
                                            </div>

                                            <div class="progress enterprise-progress">
                                                <div class="progress-bar bg-{{ $p['color'] }}"
                                                    data-width="{{ $p['pct'] }}"></div>
                                            </div>

                                            <div class="juz-mini-metrics">
                                                <div class="juz-mini-metric">
                                                    <div class="juz-mini-label">Setoran</div>
                                                    <div class="juz-mini-value">{{ $p['daily_count'] ?? 0 }}</div>
                                                </div>
                                                <div class="juz-mini-metric">
                                                    <div class="juz-mini-label">Ujian</div>
                                                    <div class="juz-mini-value">{{ $p['exam_count'] ?? 0 }}</div>
                                                </div>
                                                <div class="juz-mini-metric">
                                                    <div class="juz-mini-label">Nilai Sementara</div>
                                                    <div class="juz-mini-value">{{ $p['temporary_average'] ?? '-' }}</div>
                                                </div>
                                                <div class="juz-mini-metric">
                                                    <div class="juz-mini-label">Nilai Ujian</div>
                                                    <div class="juz-mini-value">{{ $p['exam_average'] ?? '-' }}</div>
                                                </div>
                                            </div>

                                            <div class="juz-explain">
                                                {{ $p['explanation'] }}
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="col-lg-6">
                        <section class="student-card h-100 fullscreen-target" id="analyticsJuzCard">
                            <header class="student-card-header fullscreen-card-header">
                                <div class="fullscreen-card-title">
                                    <h3 class="student-section-title"><i class="bi bi-activity me-2"></i>Analitik Capaian
                                        per
                                        Juz</h3>
                                    <p class="student-section-copy">Bar makin tinggi berarti Juz makin dekat ke ujian
                                        akhir.
                                    </p>
                                </div>

                                <button type="button" class="btn-card-fullscreen d-none d-lg-inline-flex"
                                    data-fullscreen-target="analyticsJuzCard" data-fullscreen-chart="juz"
                                    aria-label="Lihat Analitik Capaian per Juz fullscreen">
                                    <i class="bi bi-arrows-fullscreen"></i>
                                    <span>Fullscreen</span>
                                </button>
                            </header>
                            <div class="p-4 fullscreen-fill">
                                <div class="chart-legend-simple">
                                    <div class="chart-legend-item">
                                        <div class="chart-legend-title">0%</div>
                                        <div class="chart-legend-copy">Belum ada setoran.</div>
                                    </div>
                                    <div class="chart-legend-item">
                                        <div class="chart-legend-title">25–70%</div>
                                        <div class="chart-legend-copy">Masih harian/tahapan.</div>
                                    </div>
                                    <div class="chart-legend-item">
                                        <div class="chart-legend-title">100%</div>
                                        <div class="chart-legend-copy">Sudah lulus ujian.</div>
                                    </div>
                                </div>
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
                                    <th>Semester</th>
                                    <th>Juz</th>
                                    <th>Tahapan</th>
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
                            <p class="student-section-copy">Rata-rata progres halaman pada scope {{ $scopeLabel }}.</p>
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
                                    <th>Semester</th>
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
                            <p class="student-section-copy">Dihitung dari Juz tertinggi pada scope {{ $scopeLabel }}.
                            </p>
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
                                    <th>Semester</th>
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

            @php
                /*
                 * Data chart sengaja dihitung di blok @php terpisah.
                 * Jangan letakkan closure/match langsung di @json karena parser Blade pada beberapa versi
                 * bisa membaca kurung/kurawal secara keliru dan memunculkan error syntax dekat property `label`.
                 */
                $chartJuzLabels = $progressPerJuz
                    ->pluck('juz')
                    ->map(function ($juz) {
                        return 'Juz ' . $juz;
                    })
                    ->values();

                $chartJuzData = $progressPerJuz
                    ->pluck('pct')
                    ->map(function ($value) {
                        return (float) $value;
                    })
                    ->values();

                $chartJuzStatuses = $progressPerJuz->pluck('status')->values();
                $chartJuzDailyCounts = $progressPerJuz->pluck('daily_count')->values();
                $chartJuzExamCounts = $progressPerJuz->pluck('exam_count')->values();
                $chartJuzTemporaryScores = $progressPerJuz->pluck('temporary_average')->values();
                $chartJuzExamScores = $progressPerJuz->pluck('exam_average')->values();

                $chartJuzColors = $progressPerJuz
                    ->map(function ($item) {
                        $color = $item['color'] ?? 'light';

                        switch ($color) {
                            case 'success':
                                return 'rgba(25, 135, 84, .78)';
                            case 'warning':
                                return 'rgba(255, 193, 7, .82)';
                            case 'info':
                                return 'rgba(13, 202, 240, .72)';
                            case 'primary':
                                return 'rgba(13, 110, 253, .72)';
                            case 'secondary':
                                return 'rgba(108, 117, 125, .72)';
                            default:
                                return 'rgba(148, 163, 184, .45)';
                        }
                    })
                    ->values();

                $chartBukuLabels = $progressPerBuku->pluck('label')->values();
                $chartBukuData = $progressPerBuku
                    ->pluck('pct')
                    ->map(function ($value) {
                        return (float) $value;
                    })
                    ->values();
            @endphp

            const selectedScope = @json($scope);
            const selectedSemesterId = @json($selectedSemesterId);

            const chartJuzLabels = @json($chartJuzLabels);
            const chartJuzData = @json($chartJuzData);
            const chartJuzStatuses = @json($chartJuzStatuses);
            const chartJuzDailyCounts = @json($chartJuzDailyCounts);
            const chartJuzExamCounts = @json($chartJuzExamCounts);
            const chartJuzTemporaryScores = @json($chartJuzTemporaryScores);
            const chartJuzExamScores = @json($chartJuzExamScores);
            const chartJuzColors = @json($chartJuzColors);
            const chartBukuLabels = @json($chartBukuLabels);
            const chartBukuData = @json($chartBukuData);

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

                const baseOptions = chartOptions();
                const theme = chartTheme();

                chartJuz = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: chartJuzLabels,
                        datasets: [{
                            label: 'Progress Juz',
                            data: chartJuzData,
                            backgroundColor: chartJuzColors,
                            borderRadius: 8,
                            maxBarThickness: 34
                        }]
                    },
                    options: {
                        ...baseOptions,
                        interaction: {
                            intersect: true,
                            mode: 'nearest'
                        },
                        plugins: {
                            ...baseOptions.plugins,
                            tooltip: {
                                backgroundColor: theme.tooltipBg,
                                titleColor: theme.tooltipText,
                                bodyColor: theme.tooltipText,
                                borderColor: theme.grid,
                                borderWidth: 1,
                                callbacks: {
                                    label: function(context) {
                                        const i = context.dataIndex;
                                        return [
                                            `Progress: ${context.parsed.y}%`,
                                            `Status: ${chartJuzStatuses[i] || '-'}`,
                                            `Setoran harian/tahap: ${chartJuzDailyCounts[i] ?? 0}`,
                                            `Ujian akhir: ${chartJuzExamCounts[i] ?? 0}`,
                                            `Nilai sementara: ${chartJuzTemporaryScores[i] ?? '-'}`,
                                            `Nilai ujian: ${chartJuzExamScores[i] ?? '-'}`
                                        ];
                                    }
                                }
                            }
                        }
                    }
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

            function resetJuzChartNormalHeight() {
                const canvas = document.getElementById('chartJuzPct');
                if (!canvas) return;

                const wrap = canvas.closest('.chart-wrap');
                const analyticsCard = document.getElementById('analyticsJuzCard');
                const isAnalyticsFullscreen = analyticsCard && analyticsCard.classList.contains(
                    'is-card-fullscreen');

                if (isAnalyticsFullscreen) return;

                if (wrap) {
                    wrap.style.height = '';
                    wrap.style.minHeight = '';
                }

                canvas.style.width = '';
                canvas.style.height = '';
            }

            function resizeJuzChart() {
                if (!chartJuz) return;

                const resize = function() {
                    resetJuzChartNormalHeight();
                    chartJuz.resize();
                    chartJuz.update('none');
                };

                requestAnimationFrame(function() {
                    resize();
                    setTimeout(resize, 120);
                    setTimeout(resize, 320);
                });
            }

            function fullscreenElement() {
                return document.fullscreenElement ||
                    document.webkitFullscreenElement ||
                    document.mozFullScreenElement ||
                    document.msFullscreenElement ||
                    null;
            }

            function requestFullscreen(element) {
                if (element.requestFullscreen) return Promise.resolve(element.requestFullscreen());
                if (element.webkitRequestFullscreen) return Promise.resolve(element.webkitRequestFullscreen());
                if (element.mozRequestFullScreen) return Promise.resolve(element.mozRequestFullScreen());
                if (element.msRequestFullscreen) return Promise.resolve(element.msRequestFullscreen());
                return Promise.reject(new Error('Fullscreen API tidak didukung browser ini.'));
            }

            function exitFullscreen() {
                if (document.exitFullscreen) return Promise.resolve(document.exitFullscreen());
                if (document.webkitExitFullscreen) return Promise.resolve(document.webkitExitFullscreen());
                if (document.mozCancelFullScreen) return Promise.resolve(document.mozCancelFullScreen());
                if (document.msExitFullscreen) return Promise.resolve(document.msExitFullscreen());
                return Promise.resolve();
            }

            function setFullscreenButtonState(activeCard) {
                document.querySelectorAll('[data-fullscreen-target]').forEach(function(button) {
                    const targetId = button.dataset.fullscreenTarget;
                    const isActive = Boolean(activeCard && activeCard.id === targetId);
                    const icon = button.querySelector('i');
                    const label = button.querySelector('span');

                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');

                    if (icon) {
                        icon.className = isActive ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
                    }

                    if (label) {
                        label.textContent = isActive ? 'Keluar' : 'Fullscreen';
                    }
                });
            }

            function syncFullscreenState() {
                const active = fullscreenElement();

                document.querySelectorAll('.fullscreen-target').forEach(function(card) {
                    const isActive = active === card;

                    card.classList.toggle('is-card-fullscreen', isActive);

                    if (!isActive) {
                        card.style.width = '';
                        card.style.height = '';
                        card.style.maxHeight = '';
                    }
                });

                if (!active) {
                    resetJuzChartNormalHeight();
                }

                setFullscreenButtonState(active);
                resizeJuzChart();
            }

            function bindCardFullscreen() {
                document.querySelectorAll('[data-fullscreen-target]').forEach(function(button) {
                    button.addEventListener('click', function() {
                        const target = document.getElementById(button.dataset.fullscreenTarget);
                        if (!target) return;

                        const active = fullscreenElement();

                        if (active === target) {
                            exitFullscreen().catch(function(error) {
                                console.warn('Gagal keluar fullscreen:', error);
                            });
                            return;
                        }

                        requestFullscreen(target).catch(function(error) {
                            console.warn('Gagal membuka fullscreen:', error);
                        });
                    });
                });

                ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange']
                .forEach(function(eventName) {
                    document.addEventListener(eventName, syncFullscreenState);
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
                        url: "{{ route('admin.santri.master.progress.hafalan.timeline', $santri) }}",
                        data: function(data) {
                            data.scope = selectedScope;
                            data.semester_id = selectedSemesterId;
                        }
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
                            data: 'semester',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'juz',
                            name: 'ht.juz'
                        },
                        {
                            data: 'tahap',
                            orderable: false,
                            searchable: false
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
                    ajax: {
                        url: "{{ route('admin.santri.master.progress.tahsin.timeline', $santri) }}",
                        data: function(data) {
                            data.scope = selectedScope;
                            data.semester_id = selectedSemesterId;
                        }
                    },
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
                            data: 'semester',
                            orderable: false,
                            searchable: false
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
                    ajax: {
                        url: "{{ route('admin.santri.master.progress.tilawah.timeline', $santri) }}",
                        data: function(data) {
                            data.scope = selectedScope;
                            data.semester_id = selectedSemesterId;
                        }
                    },
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
                            data: 'semester',
                            orderable: false,
                            searchable: false
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

            const scopeSelect = document.getElementById('progressScope');
            const semesterSelect = document.getElementById('progressSemester');

            if (scopeSelect) {
                scopeSelect.addEventListener('change', function() {
                    if (semesterSelect) {
                        semesterSelect.disabled = false;
                    }
                });
            }

            renderJuzChart();
            animateProgressBars();
            bindCardFullscreen();
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
