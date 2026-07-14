@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
    @php
        $integrity = $integritySummary ?? [
            'status' => 'warning',
            'total' => 0,
            'critical' => 0,
            'warning' => 0,
            'safe_auto_repair' => 0,
            'active_semester_label' => null,
        ];

        $integrityClass = match ($integrity['status'] ?? 'warning') {
            'critical' => 'danger',
            'healthy' => 'success',
            default => 'warning',
        };

        $integrityLabel = match ($integrity['status'] ?? 'warning') {
            'critical' => 'Kritis',
            'healthy' => 'Sehat',
            default => 'Perlu Perhatian',
        };

        $integrityIcon = match ($integrity['status'] ?? 'warning') {
            'critical' => 'bi-shield-fill-exclamation',
            'healthy' => 'bi-shield-check',
            default => 'bi-shield-fill-check',
        };
    @endphp

    <style>
        :root,
        [data-coreui-theme="light"] {
            --dashboard-surface: #ffffff;
            --dashboard-soft: #f7f8fc;
            --dashboard-muted-surface: #eef1f7;
            --dashboard-text: #202436;
            --dashboard-heading: #171a2a;
            --dashboard-muted: #707586;
            --dashboard-border: rgba(23, 26, 42, .09);
            --dashboard-border-strong: rgba(23, 26, 42, .16);
            --dashboard-purple: var(--islamic-purple-600, #6b4eff);
            --dashboard-purple-dark: var(--islamic-purple-700, #5638d8);
            --dashboard-purple-soft: rgba(107, 78, 255, .11);
            --dashboard-tosca: var(--islamic-tosca-600, #13a3b3);
            --dashboard-tosca-soft: rgba(19, 163, 179, .11);
            --dashboard-success: #198754;
            --dashboard-success-soft: rgba(25, 135, 84, .11);
            --dashboard-warning: #c98000;
            --dashboard-warning-soft: rgba(255, 193, 7, .16);
            --dashboard-danger: #dc3545;
            --dashboard-danger-soft: rgba(220, 53, 69, .11);
            --dashboard-info: #0d6efd;
            --dashboard-info-soft: rgba(13, 110, 253, .10);
            --dashboard-shadow-sm: 0 8px 26px rgba(27, 32, 56, .06);
            --dashboard-shadow-md: 0 17px 46px rgba(27, 32, 56, .11);
        }

        [data-coreui-theme="dark"] {
            color-scheme: dark;
            --dashboard-surface: #20212b;
            --dashboard-soft: #282a35;
            --dashboard-muted-surface: #30323f;
            --dashboard-text: #e8eaf1;
            --dashboard-heading: #ffffff;
            --dashboard-muted: #a8adbc;
            --dashboard-border: rgba(255, 255, 255, .08);
            --dashboard-border-strong: rgba(255, 255, 255, .15);
            --dashboard-purple: #aa9cff;
            --dashboard-purple-dark: #8b79ff;
            --dashboard-purple-soft: rgba(132, 112, 255, .20);
            --dashboard-tosca: #64d5df;
            --dashboard-tosca-soft: rgba(56, 189, 201, .18);
            --dashboard-success: #5fd39a;
            --dashboard-success-soft: rgba(38, 179, 112, .18);
            --dashboard-warning: #ffd166;
            --dashboard-warning-soft: rgba(255, 193, 7, .18);
            --dashboard-danger: #ff8190;
            --dashboard-danger-soft: rgba(255, 92, 108, .18);
            --dashboard-info: #73a7ff;
            --dashboard-info-soft: rgba(86, 142, 255, .18);
            --dashboard-shadow-sm: 0 12px 32px rgba(0, 0, 0, .24);
            --dashboard-shadow-md: 0 20px 52px rgba(0, 0, 0, .34);
        }

        .min-w-0 {
            min-width: 0;
        }

        .superadmin-dashboard {
            position: relative;
            isolation: isolate;
            color: var(--dashboard-text);
            padding-bottom: 2rem;
        }

        .superadmin-dashboard::before {
            content: '';
            position: absolute;
            inset: -1.5rem -1.5rem auto;
            height: 340px;
            z-index: -1;
            pointer-events: none;
            background:
                radial-gradient(circle at 8% 4%, rgba(107, 78, 255, .09), transparent 34%),
                radial-gradient(circle at 88% 6%, rgba(19, 163, 179, .07), transparent 31%);
            mask-image: linear-gradient(to bottom, #000 0%, transparent 100%);
        }

        .dashboard-card {
            border: 1px solid var(--dashboard-border);
            border-radius: 21px;
            background: var(--dashboard-surface);
            box-shadow: var(--dashboard-shadow-sm);
        }

        /* HERO */
        .dashboard-hero {
            position: relative;
            overflow: hidden;
            padding: clamp(1.45rem, 3vw, 2.2rem);
            border-radius: 27px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 10%, rgba(255, 255, 255, .20), transparent 28%),
                linear-gradient(135deg, #433280 0%, #1599aa 100%);
            box-shadow: 0 20px 48px rgba(72, 58, 171, .22);
        }

        .dashboard-hero::after {
            content: '';
            position: absolute;
            right: -92px;
            bottom: -128px;
            width: 260px;
            height: 260px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 50%;
            box-shadow:
                0 0 0 36px rgba(255, 255, 255, .04),
                0 0 0 76px rgba(255, 255, 255, .025);
        }

        .dashboard-hero>* {
            position: relative;
            z-index: 1;
        }

        .dashboard-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(280px, .72fr);
            gap: clamp(1.25rem, 3vw, 2.4rem);
            align-items: center;
        }

        .dashboard-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .75rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(8px);
            font-size: .68rem;
            font-weight: 850;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .dashboard-hero-title {
            margin: .9rem 0 .45rem;
            font-size: clamp(1.6rem, 3vw, 2.35rem);
            line-height: 1.12;
            font-weight: 860;
            letter-spacing: -.035em;
        }

        .dashboard-hero-copy {
            max-width: 740px;
            margin: 0;
            color: rgba(255, 255, 255, .78);
            font-size: .9rem;
            line-height: 1.65;
        }

        .dashboard-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
        }

        .dashboard-hero-meta-item {
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            padding: .48rem .7rem;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 11px;
            background: rgba(255, 255, 255, .09);
            color: rgba(255, 255, 255, .82);
            font-size: .71rem;
            backdrop-filter: blur(7px);
        }

        .dashboard-command-panel {
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: 20px;
            background: rgba(255, 255, 255, .11);
            backdrop-filter: blur(12px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .11);
        }

        .dashboard-command-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .6rem;
        }

        .dashboard-command-actions .btn {
            min-height: 43px;
            border-radius: 12px;
            font-size: .76rem;
            font-weight: 850;
        }

        /* KPI */
        .kpi-card {
            position: relative;
            overflow: hidden;
            min-height: 148px;
            height: 100%;
            border: 1px solid var(--dashboard-border) !important;
            border-radius: 20px;
            background: var(--dashboard-surface) !important;
            box-shadow: var(--dashboard-shadow-sm);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            border-color: var(--dashboard-border-strong) !important;
            box-shadow: var(--dashboard-shadow-md);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: var(--kpi-color, var(--dashboard-purple));
        }

        .kpi-card::after {
            content: '';
            position: absolute;
            top: -34px;
            right: -28px;
            width: 98px;
            height: 98px;
            border-radius: 50%;
            background: var(--kpi-soft, var(--dashboard-purple-soft));
        }

        .kpi-card .card-body {
            position: relative;
            z-index: 1;
        }

        .kpi-icon {
            width: 43px;
            height: 43px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            color: var(--kpi-color, var(--dashboard-purple));
            background: var(--kpi-soft, var(--dashboard-purple-soft));
            font-size: 1.08rem;
        }

        .kpi-label {
            color: var(--dashboard-muted);
            font-size: .64rem;
            font-weight: 850;
            letter-spacing: .075em;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .kpi-value {
            margin-top: .62rem;
            color: var(--dashboard-heading);
            font-size: 1.9rem;
            font-weight: 880;
            line-height: 1;
            letter-spacing: -.04em;
        }

        .kpi-sub {
            margin-top: .38rem;
            color: var(--dashboard-muted);
            font-size: .66rem;
        }

        /* INTEGRITY */
        .integrity-overview {
            overflow: hidden;
        }

        .integrity-overview-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(300px, .8fr);
            gap: 1rem;
            align-items: center;
            padding: 1.15rem 1.25rem;
        }

        .integrity-overview-icon {
            width: 52px;
            height: 52px;
            flex: 0 0 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 1.25rem;
        }

        .integrity-mini-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .65rem;
        }

        .integrity-mini-stat {
            padding: .72rem;
            border: 1px solid var(--dashboard-border);
            border-radius: 13px;
            background: var(--dashboard-soft);
            text-align: center;
        }

        .integrity-mini-stat-value {
            color: var(--dashboard-heading);
            font-size: 1.05rem;
            font-weight: 850;
        }

        .integrity-mini-stat-label {
            margin-top: .18rem;
            color: var(--dashboard-muted);
            font-size: .6rem;
            font-weight: 780;
            text-transform: uppercase;
        }

        /* CONTENT CARDS */
        .dashboard-section-card {
            height: 100%;
            overflow: hidden;
        }

        .dashboard-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.15rem;
            border-bottom: 1px solid var(--dashboard-border);
            background:
                linear-gradient(135deg, rgba(107, 78, 255, .035), rgba(19, 163, 179, .022)),
                var(--dashboard-surface);
        }

        .dashboard-section-kicker {
            margin-bottom: .22rem;
            color: var(--dashboard-purple);
            font-size: .63rem;
            font-weight: 850;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .dashboard-section-title {
            margin: 0;
            color: var(--dashboard-heading);
            font-size: .96rem;
            font-weight: 850;
        }

        .dashboard-section-copy {
            margin: .2rem 0 0;
            color: var(--dashboard-muted);
            font-size: .71rem;
        }

        .dashboard-chart-wrap {
            position: relative;
            width: 100%;
            height: 300px;
            padding: .75rem;
        }

        #dashboard-users-table {
            color: var(--dashboard-text);
        }

        #dashboard-users-table> :not(caption)>*>* {
            padding-top: .85rem;
            padding-bottom: .85rem;
            border-bottom-color: var(--dashboard-border);
        }

        #dashboard-users-table thead th {
            color: var(--dashboard-muted);
            background: var(--dashboard-soft);
            font-size: .63rem;
            font-weight: 850;
            letter-spacing: .06em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        #dashboard-users-table tbody tr:hover {
            background: var(--dashboard-purple-soft);
        }

        .dashboard-section-card .dataTables_wrapper .row:last-child {
            padding: .75rem 1rem .9rem;
            margin: 0;
            border-top: 1px solid var(--dashboard-border);
        }

        .dashboard-section-card .page-link {
            border-color: var(--dashboard-border);
            color: var(--dashboard-text);
            background: var(--dashboard-surface);
        }

        .dashboard-section-card .page-item.active .page-link {
            border-color: var(--dashboard-purple);
            color: #fff;
            background: var(--dashboard-purple);
        }

        /* QUICK ACTIONS */
        .quick-action-card {
            display: flex;
            align-items: center;
            gap: .8rem;
            height: 100%;
            padding: .95rem;
            border: 1px solid var(--dashboard-border);
            border-radius: 16px;
            color: inherit;
            background: var(--dashboard-soft);
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
        }

        .quick-action-card:hover {
            color: inherit;
            transform: translateY(-2px);
            border-color: var(--dashboard-border-strong);
            box-shadow: var(--dashboard-shadow-sm);
        }

        .quick-action-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            color: var(--action-color, var(--dashboard-purple));
            background: var(--action-soft, var(--dashboard-purple-soft));
            font-size: 1.05rem;
        }

        @media (max-width: 991.98px) {

            .dashboard-hero-grid,
            .integrity-overview-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .dashboard-hero {
                padding: 1.15rem;
                border-radius: 20px;
            }

            .dashboard-command-actions,
            .integrity-mini-stats {
                grid-template-columns: 1fr;
            }

            .kpi-card {
                min-height: 134px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .kpi-card,
            .quick-action-card {
                transition: none;
            }
        }
    </style>

    <div class="superadmin-dashboard">
        <section class="dashboard-hero mb-4">
            <div class="dashboard-hero-grid">
                <div>
                    <span class="dashboard-eyebrow">
                        <i class="bi bi-command"></i>
                        System Administration Command Center
                    </span>

                    <h3 class="dashboard-hero-title">
                        Selamat datang, {{ auth()->user()->name }}
                    </h3>

                    <p class="dashboard-hero-copy">
                        Pantau user, approval, struktur akademik, dan integritas data dari satu dashboard.
                        Prioritaskan peringatan sistem sebelum melakukan perubahan administratif besar.
                    </p>

                    <div class="dashboard-hero-meta">
                        <span class="dashboard-hero-meta-item">
                            <i class="bi bi-calendar3"></i>
                            {{ now()->translatedFormat('l, d F Y') }}
                        </span>
                        <span class="dashboard-hero-meta-item">
                            <i class="bi bi-people-fill"></i>
                            {{ number_format($totalUser, 0, ',', '.') }} user terdaftar
                        </span>
                        @if (!empty($integrity['active_semester_label']))
                            <span class="dashboard-hero-meta-item">
                                <i class="bi bi-calendar2-check"></i>
                                Semester {{ $integrity['active_semester_label'] }}
                            </span>
                        @endif
                    </div>
                </div>

                <aside class="dashboard-command-panel">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-4 bg-white bg-opacity-10"
                            style="width: 48px; height: 48px;">
                            <i class="bi {{ $integrityIcon }} fs-4"></i>
                        </span>
                        <div>
                            <div class="small text-white-50 fw-bold text-uppercase">System Status</div>
                            <div class="fw-bold">Integritas {{ $integrityLabel }}</div>
                        </div>
                    </div>

                    <p class="small text-white-50 mb-3">
                        {{ number_format($integrity['total'] ?? 0, 0, ',', '.') }} temuan terdeteksi,
                        termasuk {{ number_format($integrity['critical'] ?? 0, 0, ',', '.') }} temuan kritis.
                    </p>

                    <div class="dashboard-command-actions">
                        <a href="{{ route('superadmin.users.index') }}" class="btn btn-light">
                            <i class="bi bi-people-fill me-1"></i>
                            Kelola User
                        </a>
                        <a href="{{ route('superadmin.system-integrity.index') }}" class="btn btn-warning">
                            <i class="bi bi-database-check me-1"></i>
                            Cek Integritas
                        </a>
                    </div>
                </aside>
            </div>
        </section>

        <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-5">
            <div class="col">
                <div class="card kpi-card accent-pending" style="--kpi-color: #dc3545; --kpi-soft: rgba(220, 53, 69, .11);">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="kpi-label">Butuh Validasi</div>
                            <span class="kpi-icon">
                                <i class="bi bi-person-exclamation"></i>
                            </span>
                        </div>
                        <div class="kpi-value count-up text-danger" data-target="{{ $totalPending }}">0</div>
                        <div class="kpi-sub">Akun menunggu approval</div>
                        <a href="{{ route('superadmin.users.index') }}"
                            class="btn btn-sm btn-outline-danger rounded-pill fw-bold mt-auto">
                            Review User
                        </a>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card accent-user" style="--kpi-color: #6b4eff; --kpi-soft: rgba(107, 78, 255, .11);">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="kpi-label">Total User</div>
                            <span class="kpi-icon">
                                <i class="bi bi-people-fill"></i>
                            </span>
                        </div>
                        <div class="kpi-value count-up" data-target="{{ $totalUser }}">0</div>
                        <div class="kpi-sub">Seluruh akun sistem</div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card accent-dept" style="--kpi-color: #13a3b3; --kpi-soft: rgba(19, 163, 179, .11);">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="kpi-label">Admin Departemen</div>
                            <span class="kpi-icon">
                                <i class="bi bi-diagram-3-fill"></i>
                            </span>
                        </div>
                        <div class="kpi-value count-up" data-target="{{ $totalDepartemen }}">0</div>
                        <div class="kpi-sub">Pengelola operasional</div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card accent-kelas" style="--kpi-color: #d98b00; --kpi-soft: rgba(255, 193, 7, .16);">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="kpi-label">Jumlah Kelas</div>
                            <span class="kpi-icon">
                                <i class="bi bi-easel-fill"></i>
                            </span>
                        </div>
                        <div class="kpi-value count-up" data-target="{{ $totalKelas }}">0</div>
                        <div class="kpi-sub">Kelompok belajar terdaftar</div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card accent-santri" style="--kpi-color: #0d6efd; --kpi-soft: rgba(13, 110, 253, .10);">
                    <div class="card-body p-3 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="kpi-label">Total Santri</div>
                            <span class="kpi-icon">
                                <i class="bi bi-mortarboard-fill"></i>
                            </span>
                        </div>
                        <div class="kpi-value count-up" data-target="{{ $totalSantri }}">0</div>
                        <div class="kpi-sub">Santri pada basis data</div>
                    </div>
                </div>
            </div>
        </div>

        <section class="dashboard-card integrity-overview mb-4">
            <div class="integrity-overview-grid">
                <div class="d-flex align-items-start gap-3">
                    <span
                        class="integrity-overview-icon bg-{{ $integrityClass }} bg-opacity-10 text-{{ $integrityClass }}">
                        <i class="bi {{ $integrityIcon }}"></i>
                    </span>

                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h5 class="mb-0 fw-bold">Integritas User & Profil</h5>
                            <span class="badge bg-{{ $integrityClass }} rounded-pill px-3">
                                {{ $integrityLabel }}
                            </span>
                        </div>
                        <p class="small text-muted mb-3">
                            Sinkronisasi akun, role, profil Musyrif/Santri, semester aktif, dan placement.
                        </p>
                        <a href="{{ route('superadmin.system-integrity.index') }}"
                            class="btn btn-sm btn-outline-{{ $integrityClass }} rounded-pill px-3 fw-bold">
                            Buka Consistency Checker
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="integrity-mini-stats">
                    <div class="integrity-mini-stat">
                        <div class="integrity-mini-stat-value">
                            {{ number_format($integrity['total'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="integrity-mini-stat-label">Total Temuan</div>
                    </div>
                    <div class="integrity-mini-stat">
                        <div class="integrity-mini-stat-value text-danger">
                            {{ number_format($integrity['critical'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="integrity-mini-stat-label">Kritis</div>
                    </div>
                    <div class="integrity-mini-stat">
                        <div class="integrity-mini-stat-value text-success">
                            {{ number_format($integrity['safe_auto_repair'] ?? 0, 0, ',', '.') }}
                        </div>
                        <div class="integrity-mini-stat-label">Safe Repair</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4 mb-4">
            <div class="col-xl-5">
                <section class="dashboard-card dashboard-section-card">
                    <div class="dashboard-section-head">
                        <div>
                            <div class="dashboard-section-kicker">Access Distribution</div>
                            <h4 class="dashboard-section-title">Komposisi User per Role</h4>
                            <p class="dashboard-section-copy">Distribusi seluruh user berdasarkan hak akses.</p>
                        </div>
                        <span class="kpi-icon"
                            style="--kpi-color: var(--dashboard-tosca); --kpi-soft: var(--dashboard-tosca-soft);">
                            <i class="bi bi-pie-chart-fill"></i>
                        </span>
                    </div>
                    <div class="dashboard-chart-wrap">
                        <canvas id="userRoleChart"></canvas>
                    </div>
                </section>
            </div>

            <div class="col-xl-7">
                <section class="dashboard-card dashboard-section-card">
                    <div class="dashboard-section-head">
                        <div>
                            <div class="dashboard-section-kicker">Account Directory</div>
                            <h4 class="dashboard-section-title">User Terdaftar</h4>
                            <p class="dashboard-section-copy">Daftar ringkas akun terbaru pada sistem.</p>
                        </div>
                        <a href="{{ route('superadmin.users.index') }}"
                            class="btn btn-sm text-white px-3 rounded-pill fw-bold"
                            style="background: var(--dashboard-purple);">
                            Kelola User
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table id="dashboard-users-table" class="table table-hover align-middle w-100 mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">No.</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th class="pe-4">Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data diisi DataTables via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <section class="dashboard-card p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <div class="dashboard-section-kicker">Administrative Tools</div>
                    <h4 class="dashboard-section-title">Quick Actions</h4>
                    <p class="dashboard-section-copy">Akses fungsi Super Admin yang paling sering digunakan.</p>
                </div>
                <i class="bi bi-lightning-charge-fill fs-4 text-warning"></i>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <a href="{{ route('superadmin.users.index') }}" class="quick-action-card">
                        <span class="quick-action-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="fw-bold small">Kelola User</div>
                            <div class="small text-muted">Approval dan lifecycle</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-xl-3">
                    <a href="{{ route('superadmin.system-integrity.index') }}" class="quick-action-card"
                        style="--action-color: var(--dashboard-success); --action-soft: var(--dashboard-success-soft);">
                        <span class="quick-action-icon">
                            <i class="bi bi-database-check"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="fw-bold small">Integritas Sistem</div>
                            <div class="small text-muted">Scan dan safe repair</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-xl-3">
                    <a href="{{ route('kelas.index') }}" class="quick-action-card"
                        style="--action-color: var(--dashboard-warning); --action-soft: var(--dashboard-warning-soft);">
                        <span class="quick-action-icon">
                            <i class="bi bi-easel-fill"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="fw-bold small">Master Kelas</div>
                            <div class="small text-muted">Struktur akademik</div>
                        </div>
                    </a>
                </div>

                <div class="col-md-6 col-xl-3">
                    <a href="{{ route('profile.settings') }}" class="quick-action-card"
                        style="--action-color: var(--dashboard-info); --action-soft: var(--dashboard-info-soft);">
                        <span class="quick-action-icon">
                            <i class="bi bi-shield-lock-fill"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="fw-bold small">Profil & Keamanan</div>
                            <div class="small text-muted">Kredensial akun</div>
                        </div>
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ==========================
            // 1) CHART USER PER ROLE
            // ==========================
            const roleLabels = @json($roleCounts->keys());
            const roleData = @json($roleCounts->values());

            const ctx = document.getElementById('userRoleChart').getContext('2d');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        label: 'Jumlah User',
                        data: roleData,
                        backgroundColor: [
                            '#6b4eff', // Islamic Purple
                            '#13a3b3', // Islamic Tosca
                            '#ffc107', // Warning/Gold
                            '#0dcaf0', // Info/Blue
                            '#e83e8c' // Pink (cadangan)
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    animation: {
                        duration: 900,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 18,
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });

            // ==========================
            // 2) DATATABLE USER RINGKAS
            // ==========================
            $('#dashboard-users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('superadmin.users.datatable') }}",
                pageLength: 5,
                lengthChange: false,
                searching: false,
                info: false, // Menghilangkan tulisan "Showing 1 to 5..." agar lebih bersih di dashboard
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4' // Padding rata kiri
                    },
                    {
                        data: 'name',
                        name: 'name',
                        className: 'fw-medium' // Nama di-bold sedikit
                    },
                    {
                        data: 'email',
                        name: 'email',
                        className: 'text-muted small'
                    },
                    {
                        data: 'role',
                        name: 'role',
                        orderable: false,
                        searchable: false,
                        className: 'pe-4',
                        render: function(data, type, row) {
                            if (!data) return '-';

                            let roleName = data.toString().trim().toLowerCase();
                            let badgeClass = 'bg-secondary';

                            if (roleName.includes('superadmin')) {
                                badgeClass = 'bg-danger';
                            } else if (roleName.includes('admin')) {
                                badgeClass = 'bg-success';
                            } else if (roleName.includes('musyrif')) {
                                badgeClass = 'bg-warning text-dark';
                            } else if (roleName.includes('santri')) {
                                badgeClass = 'bg-primary';
                            } else if (roleName.includes('pimpinan')) {
                                badgeClass = 'bg-info text-dark';
                            }

                            return `<span class="badge ${badgeClass} rounded-pill px-3 py-2" style="font-weight: 600; letter-spacing: 0.5px;">${data.toString().trim().toUpperCase()}</span>`;
                        }
                    }
                ],
                order: [
                    [1, 'asc']
                ]
            });

            // ================= COUNT UP =================
            const counters = document.querySelectorAll('.count-up');
            counters.forEach(counter => {
                const target = parseInt(counter.dataset.target) || 0;
                const start = parseInt(counter.textContent.replace(/\D/g, '')) || 0;
                if (start === target) return;

                const duration = 1200;
                const frameRate = 30;
                const totalFrames = Math.round(duration / (1000 / frameRate));
                let frame = 0;

                const interval = setInterval(() => {
                    frame++;
                    const progress = frame / totalFrames;
                    const eased = 1 - Math.pow(1 - progress, 3); // easeOut
                    const current = Math.round(start + (target - start) * eased);
                    counter.textContent = current.toLocaleString('id-ID');

                    if (frame >= totalFrames) {
                        counter.textContent = target.toLocaleString('id-ID');
                        clearInterval(interval);
                    }
                }, 1000 / frameRate);
            });

            // 1. AKTIFKAN LOG PUSHER KE BROWSER CONSOLE
            Pusher.logToConsole = true;

            // 2. Gunakan petik ganda (") di dalam config agar tidak bentrok dengan petik tunggal (') blade
            const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
                cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                forceTLS: true
            });

            const channel = pusher.subscribe('admin-channel');

            channel.bind('user-registered', function(data) {
                console.log("DATA DARI PUSHER DITERIMA:", data);

                const pendingEl = document.querySelector('.count-up.text-danger');
                const totalUserEl = document.querySelector('.accent-user .count-up');

                if (pendingEl) {
                    pendingEl.setAttribute('data-target', data.totalPending);
                    pendingEl.innerText = data.totalPending;
                }

                if (totalUserEl && data.totalUser) {
                    totalUserEl.setAttribute('data-target', data.totalUser);
                    totalUserEl.innerText = data.totalUser;
                }

                if ($.fn.DataTable.isDataTable('#dashboard-users-table')) {
                    $('#dashboard-users-table').DataTable().ajax.reload(null, false);
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Ada Pendaftar Baru!',
                    text: 'Total Pending: ' + data.totalPending,
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            });
        });
    </script>
@endpush
