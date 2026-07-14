@extends('layouts.app')

@section('title', 'Command Center Departemen Al-Qur\'an')

@section('content')
    <style>
        :root,
        [data-coreui-theme="light"] {
            --dash-page-bg: transparent;
            --dash-surface: #ffffff;
            --dash-surface-soft: #f7f8fc;
            --dash-surface-muted: #eef1f7;
            --dash-text: #24283a;
            --dash-heading: #171a2a;
            --dash-muted: #6f7485;
            --dash-border: rgba(23, 26, 42, 0.09);
            --dash-border-strong: rgba(23, 26, 42, 0.16);
            --dash-shadow: 0 12px 35px rgba(27, 32, 56, 0.07);
            --dash-shadow-hover: 0 18px 44px rgba(27, 32, 56, 0.12);
            --dash-purple: var(--islamic-purple-600, #6b4eff);
            --dash-purple-soft: rgba(107, 78, 255, 0.11);
            --dash-tosca: var(--islamic-tosca-600, #13a3b3);
            --dash-tosca-soft: rgba(19, 163, 179, 0.12);
            --dash-success: #198754;
            --dash-success-soft: rgba(25, 135, 84, 0.11);
            --dash-warning: #d98b00;
            --dash-warning-soft: rgba(255, 193, 7, 0.16);
            --dash-danger: #dc3545;
            --dash-danger-soft: rgba(220, 53, 69, 0.11);
            --dash-info: #0d6efd;
            --dash-info-soft: rgba(13, 110, 253, 0.11);
            --dash-secondary: #687083;
            --dash-secondary-soft: rgba(104, 112, 131, 0.12);
            --dash-grid: rgba(23, 26, 42, 0.07);
            --dash-tooltip-bg: #171a2a;
            --dash-tooltip-text: #ffffff;
        }

        [data-coreui-theme="dark"] {
            --dash-page-bg: transparent;
            --dash-surface: #20212b;
            --dash-surface-soft: #272934;
            --dash-surface-muted: #30323f;
            --dash-text: #e6e8ef;
            --dash-heading: #ffffff;
            --dash-muted: #a7acbc;
            --dash-border: rgba(255, 255, 255, 0.08);
            --dash-border-strong: rgba(255, 255, 255, 0.15);
            --dash-shadow: 0 14px 38px rgba(0, 0, 0, 0.22);
            --dash-shadow-hover: 0 20px 50px rgba(0, 0, 0, 0.32);
            --dash-purple: #a99bff;
            --dash-purple-soft: rgba(132, 112, 255, 0.2);
            --dash-tosca: #64d5df;
            --dash-tosca-soft: rgba(56, 189, 201, 0.18);
            --dash-success: #5fd39a;
            --dash-success-soft: rgba(38, 179, 112, 0.18);
            --dash-warning: #ffd166;
            --dash-warning-soft: rgba(255, 193, 7, 0.19);
            --dash-danger: #ff8190;
            --dash-danger-soft: rgba(255, 92, 108, 0.18);
            --dash-info: #73a7ff;
            --dash-info-soft: rgba(86, 142, 255, 0.18);
            --dash-secondary: #bec3d1;
            --dash-secondary-soft: rgba(190, 195, 209, 0.13);
            --dash-grid: rgba(255, 255, 255, 0.07);
            --dash-tooltip-bg: #f4f5f8;
            --dash-tooltip-text: #171a2a;
        }

        .min-w-0 {
            min-width: 0;
        }

        .dashboard-shell {
            color: var(--dash-text);
        }

        .dashboard-shell .text-muted,
        .dashboard-shell .form-text {
            color: var(--dash-muted) !important;
        }

        .dashboard-shell .text-body {
            color: var(--dash-text) !important;
        }

        .dash-card {
            background: var(--dash-surface);
            border: 1px solid var(--dash-border);
            border-radius: 1.25rem;
            box-shadow: var(--dash-shadow);
        }

        .dash-card-hover {
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
        }

        .dash-card-hover:hover {
            transform: translateY(-3px);
            box-shadow: var(--dash-shadow-hover);
            border-color: var(--dash-border-strong);
        }

        .dashboard-hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-radius: 1.5rem;
            padding: clamp(1.4rem, 3vw, 2.25rem);
            background:
                radial-gradient(circle at 85% 15%, rgba(255, 255, 255, .22), transparent 30%),
                linear-gradient(135deg, #433280 0%, #1599aa 100%);
            color: #ffffff;
            box-shadow: 0 18px 44px rgba(86, 61, 216, .24);
        }

        .dashboard-hero::after {
            content: '';
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            right: -95px;
            bottom: -145px;
            background: rgba(255, 255, 255, .1);
            z-index: -1;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .8rem;
            border: 1px solid rgba(255, 255, 255, .22);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(8px);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .hero-title {
            font-size: clamp(1.55rem, 3vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -.035em;
            margin: .9rem 0 .45rem;
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, .82);
            max-width: 760px;
            margin-bottom: 0;
        }

        .hero-context {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            margin-top: 1.15rem;
        }

        .hero-context-item {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .52rem .8rem;
            background: rgba(255, 255, 255, .11);
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: .85rem;
            font-size: .82rem;
            backdrop-filter: blur(7px);
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            justify-content: flex-end;
        }

        .hero-action-btn {
            border: 1px solid rgba(255, 255, 255, .22);
            background: rgba(255, 255, 255, .13);
            color: #fff;
            border-radius: 999px;
            padding: .65rem 1rem;
            font-weight: 700;
            transition: all .2s ease;
            text-decoration: none;
        }

        .hero-action-btn:hover,
        .hero-action-btn:focus {
            background: #ffffff;
            color: #5d3df2;
            transform: translateY(-1px);
        }

        .section-kicker {
            color: var(--dash-purple);
            font-size: .73rem;
            font-weight: 800;
            letter-spacing: .1em;
            text-transform: uppercase;
            margin-bottom: .25rem;
        }

        .section-title {
            color: var(--dash-heading);
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: .2rem;
        }

        .section-copy {
            color: var(--dash-muted);
            font-size: .84rem;
            margin: 0;
        }

        .period-toolbar {
            padding: 1rem;
        }

        .period-pills {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .period-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            min-height: 40px;
            padding: .55rem .9rem;
            border: 1px solid var(--dash-border);
            border-radius: 999px;
            background: var(--dash-surface-soft);
            color: var(--dash-text);
            text-decoration: none;
            font-weight: 700;
            font-size: .82rem;
            transition: all .18s ease;
        }

        .period-pill:hover,
        .period-pill.active {
            background: var(--dash-purple-soft);
            border-color: rgba(107, 78, 255, .28);
            color: var(--dash-purple);
        }

        .custom-range-form .form-control {
            background: var(--dash-surface-soft);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: .8rem;
            min-height: 40px;
            color-scheme: light;
        }

        [data-coreui-theme="dark"] .custom-range-form .form-control {
            color-scheme: dark;
        }

        .custom-range-form .form-control:focus {
            background: var(--dash-surface);
            border-color: var(--dash-purple);
            box-shadow: 0 0 0 .22rem var(--dash-purple-soft);
            color: var(--dash-text);
        }

        .metric-card {
            position: relative;
            overflow: hidden;
            height: 100%;
            padding: 1.2rem;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 1rem;
            bottom: 1rem;
            width: 4px;
            border-radius: 0 999px 999px 0;
            background: var(--metric-color, var(--dash-purple));
        }

        .metric-label {
            color: var(--dash-muted);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
            margin-bottom: .45rem;
        }

        .metric-value {
            color: var(--dash-heading);
            font-size: clamp(1.65rem, 3vw, 2.2rem);
            font-weight: 850;
            line-height: 1;
            letter-spacing: -.04em;
        }

        .metric-note {
            color: var(--dash-muted);
            font-size: .78rem;
            margin-top: .55rem;
        }

        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            display: grid;
            place-items: center;
            font-size: 1.25rem;
            color: var(--metric-color, var(--dash-purple));
            background: var(--metric-soft, var(--dash-purple-soft));
            flex: 0 0 auto;
        }

        .metric-progress {
            height: 6px;
            background: var(--dash-surface-muted);
            border-radius: 999px;
            overflow: hidden;
            margin-top: .85rem;
        }

        .metric-progress>span {
            display: block;
            height: 100%;
            width: var(--progress, 0%);
            border-radius: inherit;
            background: var(--metric-color, var(--dash-purple));
            transition: width .6s ease;
        }

        .semester-panel {
            padding: clamp(1.25rem, 2.5vw, 1.7rem);
        }

        .semester-progress-ring {
            width: 126px;
            height: 126px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: conic-gradient(var(--dash-purple) calc(var(--semester-progress) * 1%),
                    var(--dash-surface-muted) 0);
            position: relative;
            flex: 0 0 auto;
        }

        .semester-progress-ring::before {
            content: '';
            position: absolute;
            inset: 10px;
            border-radius: 50%;
            background: var(--dash-surface);
        }

        .semester-progress-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .semester-progress-value {
            color: var(--dash-heading);
            font-size: 1.45rem;
            font-weight: 850;
            line-height: 1;
        }

        .semester-progress-label {
            color: var(--dash-muted);
            font-size: .68rem;
            font-weight: 700;
            margin-top: .3rem;
        }

        .semester-stat {
            padding: .95rem 1rem;
            border: 1px solid var(--dash-border);
            background: var(--dash-surface-soft);
            border-radius: 1rem;
            height: 100%;
        }

        .semester-stat-label {
            color: var(--dash-muted);
            font-size: .7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .semester-stat-value {
            color: var(--dash-heading);
            font-size: 1.3rem;
            font-weight: 850;
            margin-top: .25rem;
        }

        /* =========================================================
           ACTION CENTER
           ========================================================= */

        .action-center-card {
            display: flex;
            flex-direction: column;
            padding: 1.25rem;
        }

        .action-center-header {
            margin-bottom: 1rem;
        }

        .attention-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .attention-item {
            display: grid;
            grid-template-columns: 44px minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.85rem;

            padding: 0.9rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 14px;

            background-color: var(--cui-tertiary-bg);
            transition:
                transform 0.2s ease,
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .attention-item:hover {
            transform: translateY(-1px);
            border-color: rgba(107, 78, 255, 0.35);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .attention-icon {
            width: 44px;
            height: 44px;
            flex-shrink: 0;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;
            font-size: 1.1rem;
        }

        .attention-content {
            min-width: 0;
        }

        .attention-label {
            margin-bottom: 0.2rem;

            color: var(--cui-body-color);
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1.3;

            overflow-wrap: anywhere;
        }

        .attention-description {
            color: var(--cui-secondary-color);
            font-size: 0.78rem;
            line-height: 1.45;

            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;

            overflow-wrap: anywhere;
        }

        .attention-value {
            min-width: 42px;
            padding: 0.4rem 0.65rem;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: 1px solid var(--cui-border-color);
            border-radius: 999px;

            background-color: var(--cui-body-bg);
            color: var(--cui-body-color);

            font-size: 0.875rem;
            font-weight: 800;
            line-height: 1;
            white-space: nowrap;
        }

        .attention-empty {
            display: flex;
            align-items: center;
            gap: 0.85rem;

            padding: 1rem;
            border: 1px dashed var(--cui-border-color);
            border-radius: 14px;

            background-color: var(--cui-tertiary-bg);
        }

        .attention-empty-icon {
            width: 42px;
            height: 42px;
            flex-shrink: 0;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;
            background: rgba(25, 135, 84, 0.14);
            color: var(--cui-success);
            font-size: 1.15rem;
        }

        /* =========================================================
           TABLET
           ========================================================= */

        @media (max-width: 991.98px) {
            .action-center-card {
                padding: 1.15rem;
            }

            .attention-item {
                padding: 0.85rem;
            }
        }

        /* =========================================================
           MOBILE
           ========================================================= */

        @media (max-width: 575.98px) {
            .action-center-card {
                padding: 1rem;
                border-radius: 16px;
            }

            .action-center-header {
                margin-bottom: 0.85rem;
            }

            .attention-list {
                gap: 0.65rem;
            }

            .attention-item {
                grid-template-columns: 40px minmax(0, 1fr);
                align-items: start;
                gap: 0.7rem;

                padding: 0.8rem;
                border-radius: 12px;
            }

            .attention-icon {
                width: 40px;
                height: 40px;
                border-radius: 10px;
                font-size: 1rem;
            }

            .attention-label {
                font-size: 0.82rem;
            }

            .attention-description {
                font-size: 0.74rem;
                line-height: 1.4;
                -webkit-line-clamp: 3;
            }

            .attention-value {
                grid-column: 2;
                justify-self: start;

                min-width: 38px;
                margin-top: 0.15rem;
                padding: 0.35rem 0.6rem;

                font-size: 0.8rem;
            }

            .attention-empty {
                align-items: flex-start;
                padding: 0.85rem;
            }
        }

        /* Sangat kecil: 360px ke bawah */
        @media (max-width: 359.98px) {
            .attention-item {
                grid-template-columns: 36px minmax(0, 1fr);
                gap: 0.6rem;
                padding: 0.7rem;
            }

            .attention-icon {
                width: 36px;
                height: 36px;
            }
        }

        .tone-success {
            color: var(--dash-success);
            background: var(--dash-success-soft);
        }

        .tone-warning {
            color: var(--dash-warning);
            background: var(--dash-warning-soft);
        }

        .tone-danger {
            color: var(--dash-danger);
            background: var(--dash-danger-soft);
        }

        .tone-primary {
            color: var(--dash-purple);
            background: var(--dash-purple-soft);
        }

        .tone-info {
            color: var(--dash-info);
            background: var(--dash-info-soft);
        }

        .tone-secondary {
            color: var(--dash-secondary);
            background: var(--dash-secondary-soft);
        }

        .activity-feed {
            max-height: 460px;
            overflow-y: auto;
            padding-right: .3rem;
        }

        .activity-feed::-webkit-scrollbar {
            width: 6px;
        }

        .activity-feed::-webkit-scrollbar-thumb {
            background: var(--dash-border-strong);
            border-radius: 999px;
        }

        .activity-item {
            display: flex;
            gap: .85rem;
            padding: .85rem 0;
            border-bottom: 1px solid var(--dash-border);
        }

        .activity-item:last-child {
            border-bottom: 0;
        }

        .activity-dot {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: .85rem;
            flex: 0 0 auto;
        }

        .activity-title {
            color: var(--dash-heading);
            font-size: .86rem;
            font-weight: 800;
            margin-bottom: .15rem;
        }

        .activity-description {
            color: var(--dash-muted);
            font-size: .77rem;
            line-height: 1.45;
        }

        .activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem .7rem;
            margin-top: .35rem;
            color: var(--dash-muted);
            font-size: .68rem;
        }

        .chart-card {
            height: 100%;
            padding: 1.2rem;
        }

        .chart-wrap {
            position: relative;
            min-height: 310px;
            margin-top: 1rem;
        }

        .chart-wrap.chart-wrap-sm {
            min-height: 280px;
        }

        .watch-list {
            display: grid;
            gap: .65rem;
        }

        .watch-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .8rem;
            padding: .8rem .9rem;
            background: var(--dash-surface-soft);
            border: 1px solid var(--dash-border);
            border-radius: .95rem;
        }

        .watch-name {
            color: var(--dash-heading);
            font-size: .84rem;
            font-weight: 800;
        }

        .watch-meta {
            color: var(--dash-muted);
            font-size: .7rem;
            margin-top: .15rem;
        }

        .watch-badge {
            flex: 0 0 auto;
            padding: .38rem .6rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 800;
        }

        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: .75rem;
            min-height: 74px;
            padding: .9rem;
            background: var(--dash-surface-soft);
            border: 1px solid var(--dash-border);
            border-radius: 1rem;
            text-decoration: none;
            color: var(--dash-text);
            transition: all .18s ease;
        }

        .quick-action:hover {
            color: var(--dash-purple);
            border-color: rgba(107, 78, 255, .28);
            background: var(--dash-purple-soft);
            transform: translateY(-2px);
        }

        .quick-action-icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            border-radius: .8rem;
            color: var(--dash-purple);
            background: var(--dash-purple-soft);
            flex: 0 0 auto;
        }

        .quick-action-title {
            font-size: .8rem;
            font-weight: 800;
        }

        .quick-action-copy {
            color: var(--dash-muted);
            font-size: .68rem;
            margin-top: .1rem;
        }

        .dashboard-shell .btn-dashboard-primary {
            background: var(--dash-purple);
            border-color: var(--dash-purple);
            color: #fff;
            border-radius: .8rem;
            font-weight: 750;
        }

        .dashboard-shell .btn-dashboard-primary:hover {
            filter: brightness(.94);
            color: #fff;
        }

        .dashboard-shell .btn-dashboard-soft {
            background: var(--dash-surface-soft);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: .8rem;
            font-weight: 750;
        }

        .dashboard-shell .btn-dashboard-soft:hover {
            color: var(--dash-purple);
            border-color: rgba(107, 78, 255, .28);
            background: var(--dash-purple-soft);
        }

        @media (max-width: 1199.98px) {
            .quick-action-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-hero {
                border-radius: 1.25rem;
            }

            .hero-actions {
                justify-content: flex-start;
                margin-top: 1rem;
            }

            .period-toolbar {
                padding: .9rem;
            }

            .custom-range-form {
                margin-top: .8rem;
            }

            .semester-progress-ring {
                width: 110px;
                height: 110px;
            }

            .chart-wrap {
                min-height: 280px;
            }
        }

        @media (max-width: 575.98px) {
            .quick-action-grid {
                grid-template-columns: 1fr;
            }

            .hero-action-btn {
                flex: 1 1 auto;
                text-align: center;
            }

            .metric-card,
            .chart-card,
            .semester-panel {
                padding: 1rem;
            }

            .attention-value {
                min-width: 44px;
            }
        }
    </style>

    <div class="dashboard-shell">
        <audio id="notifSound" src="{{ asset('sounds/notif.mp3') }}" preload="auto"></audio>

        {{-- HERO / COMMAND CENTER CONTEXT --}}
        <section class="dashboard-hero mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="hero-eyebrow">
                        <i class="bi bi-broadcast-pin"></i>
                        Operational Command Center
                    </span>

                    <h1 class="hero-title">Dashboard Departemen Al-Qur'an</h1>
                    <p class="hero-subtitle">
                        Pantau aktivitas santri, kehadiran musyrif, risiko operasional, dan progres semester dalam satu
                        tampilan yang berorientasi tindakan.
                    </p>

                    <div class="hero-context">
                        <span class="hero-context-item">
                            <i class="bi bi-calendar2-week"></i>
                            @if ($semesterAktif)
                                Semester {{ ucfirst($semesterAktif->nama) }} ·
                                {{ $semesterAktif->tahunAjaran?->nama ?? '-' }}
                            @else
                                Semester belum ditentukan
                            @endif
                        </span>

                        <span class="hero-context-item">
                            <i class="bi bi-clock-history"></i>
                            Analitik: {{ $periodLabel }}
                        </span>

                        <span class="hero-context-item">
                            <i class="bi bi-arrow-repeat"></i>
                            Diperbarui {{ now()->translatedFormat('d M Y, H:i') }} WIB
                        </span>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="hero-actions">
                        <button type="button" id="audioStatusBtn" class="hero-action-btn">
                            <i class="bi bi-volume-mute-fill me-1"></i>
                            <span>Suara Off</span>
                        </button>

                        <a href="{{ route('admin.laporan.index') }}" class="hero-action-btn">
                            <i class="bi bi-file-earmark-bar-graph-fill me-1"></i>
                            Laporan
                        </a>

                        <a href="{{ route('admin.musyrif.absensi.index') }}" class="hero-action-btn">
                            <i class="bi bi-geo-alt-fill me-1"></i>
                            Absensi
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- QUICK ACTIONS --}}
        <section class="dash-card p-3 p-lg-4 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
                <div>
                    <div class="section-kicker">Quick Actions</div>
                    <h2 class="section-title">Aksi prioritas kepala departemen</h2>
                    <p class="section-copy">
                        Disusun dari menu sidebar yang paling sering dibutuhkan untuk monitoring, evaluasi, dan tindak
                        lanjut.
                    </p>
                </div>
            </div>

            <div class="quick-action-grid">
                <a href="{{ route('admin.laporan.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-file-earmark-bar-graph-fill"></i></span>
                    <span>
                        <span class="quick-action-title d-block">Laporan & Export</span>
                        <span class="quick-action-copy d-block">Rekap santri, kelas, musyrif</span>
                    </span>
                </a>

                <a href="{{ route('santri.master.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-people-fill"></i></span>
                    <span>
                        <span class="quick-action-title d-block">Progress Santri</span>
                        <span class="quick-action-copy d-block">Cari santri dan cek detail</span>
                    </span>
                </a>

                <a href="{{ route('admin.musyrif.absensi.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-geo-alt-fill"></i></span>
                    <span>
                        <span class="quick-action-title d-block">Absensi Musyrif</span>
                        <span class="quick-action-copy d-block">Validasi pagi, sore, suspect</span>
                    </span>
                </a>

                <a href="{{ route('santri.master.violation.report') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
                    <span>
                        <span class="quick-action-title d-block">Analisis Alpha</span>
                        <span class="quick-action-copy d-block">Santri risiko dan pelanggaran</span>
                    </span>
                </a>

                <a href="{{ route('admin.santri.migrasi.page') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-arrow-up-circle-fill"></i></span>
                    <span>
                        <span class="quick-action-title d-block">Migrasi Semester</span>
                        <span class="quick-action-copy d-block">Placement kelas dan musyrif</span>
                    </span>
                </a>

                <a href="{{ route('kelas.index') }}" class="quick-action">
                    <span class="quick-action-icon"><i class="bi bi-easel2-fill"></i></span>
                    <span>
                        <span class="quick-action-title d-block">Data Akademik</span>
                        <span class="quick-action-copy d-block">Kelas, semester, referensi</span>
                    </span>
                </a>
            </div>
        </section>

        {{-- QUICK PERIOD CONTROL --}}
        <section class="dash-card period-toolbar mb-4">
            <div class="row align-items-center g-3">
                <div class="col-xl-7">
                    <div class="section-kicker">Rentang Analitik</div>
                    <div class="period-pills mt-2">
                        <a href="{{ route('admin.dashboard', ['range' => 'today']) }}"
                            class="period-pill {{ $rangeKey === 'today' ? 'active' : '' }}">
                            <i class="bi bi-sun-fill"></i> Hari Ini
                        </a>
                        <a href="{{ route('admin.dashboard', ['range' => '7d']) }}"
                            class="period-pill {{ $rangeKey === '7d' ? 'active' : '' }}">
                            <i class="bi bi-calendar-week"></i> 7 Hari
                        </a>
                        <a href="{{ route('admin.dashboard', ['range' => '30d']) }}"
                            class="period-pill {{ $rangeKey === '30d' ? 'active' : '' }}">
                            <i class="bi bi-calendar3"></i> 30 Hari
                        </a>
                        <a href="{{ route('admin.dashboard', ['range' => 'semester']) }}"
                            class="period-pill {{ $rangeKey === 'semester' ? 'active' : '' }}">
                            <i class="bi bi-mortarboard-fill"></i> Semester Aktif
                        </a>
                    </div>
                </div>

                <div class="col-xl-5">
                    <form action="{{ route('admin.dashboard') }}" method="GET"
                        class="custom-range-form row g-2 align-items-end">
                        <input type="hidden" name="range" value="custom">
                        <div class="col-5">
                            <label for="start_date" class="form-label small fw-bold mb-1">Dari</label>
                            <input type="date" id="start_date" name="start_date" class="form-control form-control-sm"
                                value="{{ $startDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-5">
                            <label for="end_date" class="form-label small fw-bold mb-1">Sampai</label>
                            <input type="date" id="end_date" name="end_date" class="form-control form-control-sm"
                                value="{{ $endDate->format('Y-m-d') }}">
                        </div>
                        <div class="col-2 d-grid">
                            <button type="submit" class="btn btn-dashboard-primary btn" title="Terapkan periode">
                                <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        {{-- SECTION HEADING --}}
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-3">
            <div>
                <div class="section-kicker">Operasional Hari Ini</div>
                <h2 class="section-title">Kondisi departemen saat ini</h2>
                <p class="section-copy">KPI ini selalu menggunakan data hari ini, terlepas dari rentang grafik yang
                    dipilih.
                </p>
            </div>
            <span class="small text-muted">
                <i class="bi bi-calendar-check me-1"></i>
                {{ today()->translatedFormat('l, d F Y') }}
            </span>
        </div>

        {{-- TODAY KPI --}}
        <div class="row g-3 mb-4">
            <div class="col-xl col-md-4 col-sm-6">
                <article class="dash-card dash-card-hover metric-card"
                    style="--metric-color: var(--dash-purple); --metric-soft: var(--dash-purple-soft);">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label">Santri Aktif</div>
                            <div class="metric-value count-up" data-target="{{ $santriAktifHariIni }}">0</div>
                            <div class="metric-note">dari {{ number_format($jumlahSantri, 0, ',', '.') }} santri</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-person-check-fill"></i></div>
                    </div>
                    <div class="metric-progress"
                        style="--progress: {{ $jumlahSantri > 0 ? min(100, ($santriAktifHariIni / $jumlahSantri) * 100) : 0 }}%;">
                        <span></span>
                    </div>
                </article>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <article class="dash-card dash-card-hover metric-card"
                    style="--metric-color: var(--dash-tosca); --metric-soft: var(--dash-tosca-soft);">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label">Aktivitas Al-Qur'an</div>
                            <div class="metric-value count-up" data-target="{{ $aktivitasHariIni }}">0</div>
                            <div class="metric-note">Hafalan, Tahsin, dan Tilawah</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-activity"></i></div>
                    </div>
                </article>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <article class="dash-card dash-card-hover metric-card"
                    style="--metric-color: var(--dash-success); --metric-soft: var(--dash-success-soft);">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label">Setoran Harian</div>
                            <div class="metric-value count-up"
                                data-target="{{ $hafalanHariIni['jumlah_setoran_harian'] ?? 0 }}">0</div>
                            <div class="metric-note">
                                Tahap harian–T3 · total setor {{ number_format($setoranHariIni, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-journal-check"></i></div>
                    </div>
                </article>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <article class="dash-card dash-card-hover metric-card"
                    style="--metric-color: var(--dash-info); --metric-soft: var(--dash-info-soft);">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label">Ujian Lulus</div>
                            <div class="metric-value count-up" data-target="{{ $hafalanHariIni['jumlah_ujian'] ?? 0 }}">0
                            </div>
                            <div class="metric-note">
                                Juz lulus ujian akhir · percobaan
                                {{ number_format($hafalanHariIni['total_setoran_ujian'] ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-award-fill"></i></div>
                    </div>
                </article>
            </div>

            <div class="col-xl col-md-6 col-sm-6">
                <article class="dash-card dash-card-hover metric-card"
                    style="--metric-color: var(--dash-warning); --metric-soft: var(--dash-warning-soft);">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label">Absen Musyrif Pagi</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <div class="metric-value" id="morningAttendanceCounter">{{ $musyrifHadirPagi }}</div>
                                <span class="text-muted fw-bold">/ {{ $jumlahMusyrif }}</span>
                            </div>
                            <div class="metric-note"><span id="morningMissingCounter">{{ $musyrifBelumPagi }}</span>
                                belum tercatat</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-brightness-high-fill"></i></div>
                    </div>
                    <div class="metric-progress" id="morningAttendanceProgress"
                        style="--progress: {{ $jumlahMusyrif > 0 ? min(100, ($musyrifHadirPagi / $jumlahMusyrif) * 100) : 0 }}%;">
                        <span></span>
                    </div>
                </article>
            </div>

            <div class="col-xl col-md-6 col-sm-6">
                <article class="dash-card dash-card-hover metric-card"
                    style="--metric-color: var(--dash-info); --metric-soft: var(--dash-info-soft);">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="metric-label">Absen Musyrif Sore</div>
                            <div class="d-flex align-items-baseline gap-1">
                                <div class="metric-value" id="afternoonAttendanceCounter">{{ $musyrifHadirSore }}</div>
                                <span class="text-muted fw-bold">/ {{ $jumlahMusyrif }}</span>
                            </div>
                            <div class="metric-note"><span id="afternoonMissingCounter">{{ $musyrifBelumSore }}</span>
                                belum tercatat</div>
                        </div>
                        <div class="metric-icon"><i class="bi bi-moon-stars-fill"></i></div>
                    </div>
                    <div class="metric-progress" id="afternoonAttendanceProgress"
                        style="--progress: {{ $jumlahMusyrif > 0 ? min(100, ($musyrifHadirSore / $jumlahMusyrif) * 100) : 0 }}%;">
                        <span></span>
                    </div>
                </article>
            </div>
        </div>

        {{-- SEMESTER + ATTENTION + FEED --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-5">
                <section class="dash-card semester-panel h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                        <div>
                            <div class="section-kicker">Semester Snapshot</div>
                            <h2 class="section-title">
                                @if ($semesterAktif)
                                    {{ ucfirst($semesterAktif->nama) }} · {{ $semesterAktif->tahunAjaran?->nama ?? '-' }}
                                @else
                                    Semester belum tersedia
                                @endif
                            </h2>
                            <p class="section-copy">
                                {{ $semesterStart->translatedFormat('d M Y') }} —
                                {{ $semesterEnd->translatedFormat('d M Y') }}
                            </p>
                        </div>
                        <span class="badge rounded-pill tone-success px-3 py-2">
                            {{ $semesterAktif?->is_active ? 'Aktif' : 'Terpilih' }}
                        </span>
                    </div>

                    <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                        <div class="semester-progress-ring"
                            style="--semester-progress: {{ $semesterProgress['percentage'] }};">
                            <div class="semester-progress-content">
                                <div class="semester-progress-value">{{ $semesterProgress['percentage'] }}%</div>
                                <div class="semester-progress-label">berjalan</div>
                            </div>
                        </div>

                        <div class="flex-grow-1 w-100">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="semester-stat">
                                        <div class="semester-stat-label">Aktivitas</div>
                                        <div class="semester-stat-value">
                                            {{ number_format($aktivitasSemester, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="semester-stat">
                                        <div class="semester-stat-label">Setoran Harian</div>
                                        <div class="semester-stat-value">
                                            {{ number_format($hafalanSemester['jumlah_setoran_harian'] ?? 0, 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="semester-stat">
                                        <div class="semester-stat-label">Ujian Lulus</div>
                                        <div class="semester-stat-value">
                                            {{ number_format($hafalanSemester['jumlah_ujian'] ?? 0, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="semester-stat">
                                        <div class="semester-stat-label">Nilai Ujian</div>
                                        <div class="semester-stat-value">
                                            {{ $hafalanSemester['rata_nilai_ujian'] !== null ? number_format($hafalanSemester['rata_nilai_ujian'], 1, ',', '.') : '-' }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="semester-stat">
                                        <div class="semester-stat-label">Coverage Santri</div>
                                        <div class="semester-stat-value">{{ $coverageSemester }}%</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="semester-stat">
                                        <div class="semester-stat-label">Sisa Hari</div>
                                        <div class="semester-stat-value">{{ $semesterProgress['remaining_days'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-xl-4 col-lg-6">
                <section class="dash-card action-center-card h-100">
                    <div class="action-center-header">
                        <div class="section-kicker">Action Center</div>
                        <h2 class="section-title mb-1">Perlu perhatian</h2>
                        <p class="section-copy mb-0">
                            Temuan prioritas yang perlu diperiksa kepala departemen.
                        </p>
                    </div>

                    <div class="attention-list">
                        @forelse ($attentionSummary as $item)
                            <article class="attention-item">
                                <div class="attention-icon tone-{{ $item['tone'] }}" aria-hidden="true">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                </div>

                                <div class="attention-content">
                                    <div class="attention-label">
                                        {{ $item['label'] }}
                                    </div>

                                    <div class="attention-description" title="{{ $item['description'] }}">
                                        {{ $item['description'] }}
                                    </div>
                                </div>

                                <div class="attention-value" aria-label="{{ $item['value'] }} temuan">
                                    {{ $item['value'] }}
                                </div>
                            </article>
                        @empty
                            <div class="attention-empty">
                                <div class="attention-empty-icon">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>

                                <div>
                                    <div class="fw-bold">Tidak ada temuan prioritas</div>
                                    <div class="small text-muted">
                                        Kondisi operasional saat ini dalam keadaan baik.
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-xl-3 col-lg-6">
                <section class="dash-card p-3 p-lg-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="section-kicker">Live Feed</div>
                            <h2 class="section-title">Aktivitas terbaru</h2>
                        </div>
                        <span class="badge rounded-pill tone-success"><i class="bi bi-circle-fill me-1"
                                style="font-size:.45rem"></i> Live</span>
                    </div>

                    <div class="activity-feed" id="liveActivityFeed">
                        @forelse ($recentActivities as $activity)
                            <article class="activity-item">
                                <div class="activity-dot tone-{{ $activity['tone'] }}">
                                    <i class="bi {{ $activity['icon'] }}"></i>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="activity-title text-truncate" title="{{ $activity['title'] }}">
                                        {{ $activity['title'] }}
                                    </div>
                                    <div class="activity-description">
                                        {{ \Illuminate\Support\Str::limit($activity['description'], 90) }}
                                    </div>
                                    <div class="activity-meta">
                                        <span><i class="bi bi-clock me-1"></i>{{ $activity['time_ago'] }}</span>
                                        @if ($activity['meta'])
                                            <span><i class="bi bi-tag me-1"></i>{{ $activity['meta'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Belum ada aktivitas terbaru.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        {{-- ANALYTICS --}}
        <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-2 mb-3">
            <div>
                <div class="section-kicker">Visual Analytics</div>
                <h2 class="section-title">Pola aktivitas {{ strtolower($periodLabel) }}</h2>
                <p class="section-copy">Grafik digunakan untuk spotting tren; detail historis tetap tersedia di halaman
                    laporan.</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-7">
                <section class="dash-card chart-card">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h3 class="section-title"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Tren Aktivitas
                            </h3>
                            <p class="section-copy">Perbandingan Hafalan, Tahsin, dan Tilawah.</p>
                        </div>
                        <span class="badge rounded-pill tone-primary px-3 py-2">{{ $periodLabel }}</span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="activityTrendChart"></canvas>
                    </div>
                </section>
            </div>

            <div class="col-xl-5">
                <section class="dash-card chart-card">
                    <div>
                        <h3 class="section-title"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Status Santri Hari
                            Ini</h3>
                        <p class="section-copy">Status terburuk diprioritaskan jika santri memiliki beberapa aktivitas.</p>
                    </div>
                    <div class="chart-wrap chart-wrap-sm">
                        <canvas id="studentStatusChart"></canvas>
                    </div>
                </section>
            </div>

            <div class="col-12">
                <section class="dash-card chart-card">
                    <div>
                        <h3 class="section-title"><i class="bi bi-bar-chart-fill me-2 text-info"></i>Aktivitas per Kelas
                        </h3>
                        <p class="section-copy">Kelas diurutkan berdasarkan total aktivitas pada rentang terpilih.</p>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="classActivityChart"></canvas>
                    </div>
                </section>
            </div>
        </div>

        {{-- WATCHLIST --}}
        <div class="row g-3">
            <div class="col-lg-6">
                <section class="dash-card p-3 p-lg-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="section-kicker">Student Watchlist</div>
                            <h2 class="section-title">Tidak aktif ≥ 7 hari</h2>
                        </div>
                        <span class="badge rounded-pill tone-danger px-3 py-2">{{ $santriTidakAktifCount }}</span>
                    </div>

                    <div class="watch-list">
                        @forelse ($santriTidakAktifList as $santri)
                            <div class="watch-item">
                                <div class="min-w-0">
                                    <div class="watch-name text-truncate">{{ $santri['nama'] }}</div>
                                    <div class="watch-meta">
                                        {{ $santri['kelas'] }} · {{ $santri['musyrif'] }}
                                    </div>
                                </div>
                                <span class="watch-badge tone-danger">
                                    {{ $santri['inactive_days'] !== null ? $santri['inactive_days'] . ' hari' : 'Belum pernah' }}
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">Tidak ada santri dalam watchlist ini.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="col-lg-6">
                <section class="dash-card p-3 p-lg-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="section-kicker">Attendance Risk</div>
                            <h2 class="section-title">Alpha ≥ 3 kali semester ini</h2>
                        </div>
                        <span class="badge rounded-pill tone-warning px-3 py-2">{{ $santriRisikoAlphaCount }}</span>
                    </div>

                    <div class="watch-list">
                        @forelse ($santriRisikoAlphaList as $santri)
                            <div class="watch-item">
                                <div class="min-w-0">
                                    <div class="watch-name text-truncate">{{ $santri['nama'] }}</div>
                                    <div class="watch-meta">
                                        {{ $santri['kelas'] }} · {{ $santri['musyrif'] }}
                                    </div>
                                </div>
                                <span class="watch-badge tone-warning">
                                    {{ $santri['alpha_count'] }} alpha
                                </span>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">Tidak ada santri berisiko alpha.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dashboardState = {
                totalMusyrif: @json($jumlahMusyrif),
                morningIds: new Set(@json($morningMusyrifIds->values())),
                afternoonIds: new Set(@json($afternoonMusyrifIds->values())),
                soundEnabled: localStorage.getItem('dashboardSoundEnabled') === 'true',
                attendanceUrlTemplate: @json(route('admin.musyrif.attendances', ':id')),
                charts: [],
                elements: {
                    sound: document.getElementById('notifSound'),
                    audioButton: document.getElementById('audioStatusBtn'),
                    morningCounter: document.getElementById('morningAttendanceCounter'),
                    morningMissing: document.getElementById('morningMissingCounter'),
                    morningProgress: document.getElementById('morningAttendanceProgress'),
                    afternoonCounter: document.getElementById('afternoonAttendanceCounter'),
                    afternoonMissing: document.getElementById('afternoonMissingCounter'),
                    afternoonProgress: document.getElementById('afternoonAttendanceProgress'),
                    activityFeed: document.getElementById('liveActivityFeed')
                }
            };

            const getCssVariable = (name) => getComputedStyle(document.documentElement)
                .getPropertyValue(name)
                .trim();

            const themeColors = () => ({
                text: getCssVariable('--dash-text'),
                muted: getCssVariable('--dash-muted'),
                grid: getCssVariable('--dash-grid'),
                tooltipBg: getCssVariable('--dash-tooltip-bg'),
                tooltipText: getCssVariable('--dash-tooltip-text'),
                purple: getCssVariable('--dash-purple'),
                tosca: getCssVariable('--dash-tosca'),
                success: getCssVariable('--dash-success'),
                warning: getCssVariable('--dash-warning'),
                danger: getCssVariable('--dash-danger'),
                info: getCssVariable('--dash-info'),
                secondary: getCssVariable('--dash-secondary')
            });

            const commonChartOptions = () => {
                const colors = themeColors();

                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: colors.muted,
                                usePointStyle: true,
                                boxWidth: 8,
                                padding: 18
                            }
                        },
                        tooltip: {
                            backgroundColor: colors.tooltipBg,
                            titleColor: colors.tooltipText,
                            bodyColor: colors.tooltipText,
                            padding: 11,
                            cornerRadius: 10
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: colors.muted
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                color: colors.grid
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: colors.muted,
                                precision: 0
                            },
                            grid: {
                                color: colors.grid
                            },
                            border: {
                                color: colors.grid
                            }
                        }
                    }
                };
            };

            const initCounters = () => {
                document.querySelectorAll('.count-up').forEach(counter => {
                    const target = Number(counter.dataset.target || 0);
                    const duration = 700;
                    const startTime = performance.now();

                    const render = (now) => {
                        const progress = Math.min((now - startTime) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        counter.textContent = Math.round(target * eased).toLocaleString('id-ID');

                        if (progress < 1) {
                            requestAnimationFrame(render);
                        }
                    };

                    requestAnimationFrame(render);
                });
            };

            const initCharts = () => {
                if (typeof Chart === 'undefined') return;

                const colors = themeColors();
                const trendCanvas = document.getElementById('activityTrendChart');
                const statusCanvas = document.getElementById('studentStatusChart');
                const classCanvas = document.getElementById('classActivityChart');

                if (trendCanvas) {
                    const options = commonChartOptions();
                    options.plugins.legend.position = 'bottom';

                    dashboardState.charts.push(new Chart(trendCanvas, {
                        type: 'line',
                        data: {
                            labels: @json($trendChart['labels']),
                            datasets: [{
                                    label: 'Hafalan',
                                    data: @json($trendChart['hafalan']),
                                    borderColor: colors.purple,
                                    backgroundColor: 'rgba(107, 78, 255, .12)',
                                    borderWidth: 3,
                                    pointRadius: 3,
                                    tension: .35,
                                    fill: true
                                },
                                {
                                    label: 'Tahsin',
                                    data: @json($trendChart['tahsin']),
                                    borderColor: colors.tosca,
                                    backgroundColor: 'rgba(19, 163, 179, .08)',
                                    borderWidth: 2.5,
                                    pointRadius: 3,
                                    tension: .35,
                                    fill: false
                                },
                                {
                                    label: 'Tilawah',
                                    data: @json($trendChart['tilawah']),
                                    borderColor: colors.warning,
                                    backgroundColor: 'rgba(255, 193, 7, .08)',
                                    borderWidth: 2.5,
                                    pointRadius: 3,
                                    tension: .35,
                                    fill: false
                                }
                            ]
                        },
                        options
                    }));
                }

                if (statusCanvas) {
                    dashboardState.charts.push(new Chart(statusCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hadir', 'Izin', 'Sakit', 'Alpha', 'Belum Tercatat'],
                            datasets: [{
                                data: [
                                    @json($statusHariIni['hadir']),
                                    @json($statusHariIni['izin']),
                                    @json($statusHariIni['sakit']),
                                    @json($statusHariIni['alpha']),
                                    @json($statusHariIni['belum_tercatat'])
                                ],
                                backgroundColor: [
                                    colors.success,
                                    colors.info,
                                    colors.warning,
                                    colors.danger,
                                    colors.secondary
                                ],
                                borderWidth: 0,
                                hoverOffset: 8
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '67%',
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        color: colors.muted,
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 15
                                    }
                                },
                                tooltip: {
                                    backgroundColor: colors.tooltipBg,
                                    titleColor: colors.tooltipText,
                                    bodyColor: colors.tooltipText,
                                    padding: 11,
                                    cornerRadius: 10
                                }
                            }
                        }
                    }));
                }

                if (classCanvas) {
                    const options = commonChartOptions();
                    options.indexAxis = 'y';
                    options.scales.x.stacked = true;
                    options.scales.y.stacked = true;
                    options.plugins.legend.position = 'bottom';

                    dashboardState.charts.push(new Chart(classCanvas, {
                        type: 'bar',
                        data: {
                            labels: @json($activityByClassChart['labels']),
                            datasets: [{
                                    label: 'Hafalan',
                                    data: @json($activityByClassChart['hafalan']),
                                    backgroundColor: colors.purple,
                                    borderRadius: 5,
                                    borderSkipped: false
                                },
                                {
                                    label: 'Tahsin',
                                    data: @json($activityByClassChart['tahsin']),
                                    backgroundColor: colors.tosca,
                                    borderRadius: 5,
                                    borderSkipped: false
                                },
                                {
                                    label: 'Tilawah',
                                    data: @json($activityByClassChart['tilawah']),
                                    backgroundColor: colors.warning,
                                    borderRadius: 5,
                                    borderSkipped: false
                                }
                            ]
                        },
                        options
                    }));
                }
            };

            const refreshChartTheme = () => {
                const colors = themeColors();

                dashboardState.charts.forEach(chart => {
                    if (chart.options.scales?.x) {
                        chart.options.scales.x.ticks.color = colors.muted;
                        chart.options.scales.x.border.color = colors.grid;
                        chart.options.scales.x.grid.color = colors.grid;
                    }

                    if (chart.options.scales?.y) {
                        chart.options.scales.y.ticks.color = colors.muted;
                        chart.options.scales.y.border.color = colors.grid;
                        chart.options.scales.y.grid.color = colors.grid;
                    }

                    if (chart.options.plugins?.legend?.labels) {
                        chart.options.plugins.legend.labels.color = colors.muted;
                    }

                    if (chart.options.plugins?.tooltip) {
                        chart.options.plugins.tooltip.backgroundColor = colors.tooltipBg;
                        chart.options.plugins.tooltip.titleColor = colors.tooltipText;
                        chart.options.plugins.tooltip.bodyColor = colors.tooltipText;
                    }

                    if (chart.canvas?.id === 'activityTrendChart') {
                        chart.data.datasets[0].borderColor = colors.purple;
                        chart.data.datasets[1].borderColor = colors.tosca;
                        chart.data.datasets[2].borderColor = colors.warning;
                    }

                    if (chart.canvas?.id === 'studentStatusChart') {
                        chart.data.datasets[0].backgroundColor = [
                            colors.success,
                            colors.info,
                            colors.warning,
                            colors.danger,
                            colors.secondary
                        ];
                    }

                    if (chart.canvas?.id === 'classActivityChart') {
                        chart.data.datasets[0].backgroundColor = colors.purple;
                        chart.data.datasets[1].backgroundColor = colors.tosca;
                        chart.data.datasets[2].backgroundColor = colors.warning;
                    }

                    chart.update('none');
                });
            };

            const updateSoundButton = () => {
                const button = dashboardState.elements.audioButton;
                if (!button) return;

                if (dashboardState.soundEnabled) {
                    button.innerHTML = '<i class="bi bi-volume-up-fill me-1"></i><span>Suara On</span>';
                } else {
                    button.innerHTML = '<i class="bi bi-volume-mute-fill me-1"></i><span>Suara Off</span>';
                }
            };

            const setSoundEnabled = async (enabled) => {
                if (enabled && dashboardState.elements.sound) {
                    try {
                        await dashboardState.elements.sound.play();
                        dashboardState.elements.sound.pause();
                        dashboardState.elements.sound.currentTime = 0;
                    } catch (error) {
                        console.warn('Audio belum dapat diaktifkan.', error);
                        enabled = false;
                    }
                }

                dashboardState.soundEnabled = enabled;
                localStorage.setItem('dashboardSoundEnabled', String(enabled));
                updateSoundButton();

                if (enabled && 'Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
            };

            dashboardState.elements.audioButton?.addEventListener('click', () => {
                setSoundEnabled(!dashboardState.soundEnabled);
            });

            const playNotificationEffects = () => {
                if (!dashboardState.soundEnabled) return;

                const sound = dashboardState.elements.sound;
                if (sound) {
                    sound.pause();
                    sound.currentTime = 0;
                    sound.play().catch(() => {});
                }

                navigator.vibrate?.([90, 50, 90]);
            };

            const updateAttendanceUi = (session) => {
                const isMorning = session === 'morning';
                const ids = isMorning ? dashboardState.morningIds : dashboardState.afternoonIds;
                const counter = isMorning ? dashboardState.elements.morningCounter : dashboardState.elements
                    .afternoonCounter;
                const missing = isMorning ? dashboardState.elements.morningMissing : dashboardState.elements
                    .afternoonMissing;
                const progress = isMorning ? dashboardState.elements.morningProgress : dashboardState.elements
                    .afternoonProgress;

                const count = ids.size;
                const missingCount = Math.max(0, dashboardState.totalMusyrif - count);
                const percentage = dashboardState.totalMusyrif > 0 ?
                    Math.min(100, (count / dashboardState.totalMusyrif) * 100) :
                    0;

                if (counter) counter.textContent = count.toLocaleString('id-ID');
                if (missing) missing.textContent = missingCount.toLocaleString('id-ID');
                if (progress) progress.style.setProperty('--progress', `${percentage}%`);
            };

            const prependLiveActivity = (data) => {
                const feed = dashboardState.elements.activityFeed;
                if (!feed) return;

                const session = data.type === 'afternoon' ? 'Sore' : 'Pagi';
                const status = data.status || 'valid';
                const tone = status === 'valid' ? 'success' : (status === 'suspect' ? 'warning' : 'danger');
                const article = document.createElement('article');
                article.className = 'activity-item';
                article.innerHTML = `
                    <div class="activity-dot tone-${tone}">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                        <div class="activity-title text-truncate">${escapeHtml(data.nama || 'Musyrif')} — Absensi ${session}</div>
                        <div class="activity-description">${escapeHtml(status.charAt(0).toUpperCase() + status.slice(1))}</div>
                        <div class="activity-meta">
                            <span><i class="bi bi-clock me-1"></i>baru saja</span>
                        </div>
                    </div>`;

                feed.prepend(article);

                while (feed.children.length > 12) {
                    feed.removeChild(feed.lastElementChild);
                }
            };

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = String(value ?? '');
                return div.innerHTML;
            };

            const notifyAttendance = (data) => {
                const logUrl = dashboardState.attendanceUrlTemplate.replace(':id', data.musyrifId || '');

                if ('Notification' in window && Notification.permission === 'granted') {
                    const notification = new Notification('Absensi Musyrif Masuk', {
                        body: `${data.nama || 'Musyrif'} melakukan absensi ${data.type === 'afternoon' ? 'sore' : 'pagi'}.`,
                        icon: '/vendor/pwa/icons/icon-192x192.png'
                    });

                    notification.onclick = () => {
                        window.focus();
                        window.location.href = logUrl;
                    };
                }

                if (window.Swal) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: (data.status || 'valid') === 'valid' ? 'success' : 'warning',
                        title: 'Absensi Masuk',
                        html: `<b>${escapeHtml(data.nama || 'Musyrif')}</b><br><small>${escapeHtml(data.waktu || 'baru saja')}</small>`,
                        showConfirmButton: true,
                        confirmButtonText: 'Cek Log',
                        confirmButtonColor: '#6b4eff',
                        timer: 9000,
                        timerProgressBar: true
                    }).then(result => {
                        if (result.isConfirmed) window.location.href = logUrl;
                    });
                }
            };

            const handleRealtimeAttendance = (data) => {
                const status = data.status || 'valid';
                const session = data.type;
                const musyrifId = Number(data.musyrifId || data.musyrif_id || 0);

                if (status === 'valid' && musyrifId > 0 && ['morning', 'afternoon'].includes(session)) {
                    const targetSet = session === 'morning' ?
                        dashboardState.morningIds :
                        dashboardState.afternoonIds;

                    targetSet.add(musyrifId);
                    updateAttendanceUi(session);
                }

                prependLiveActivity({
                    ...data,
                    status,
                    type: session || 'morning'
                });

                playNotificationEffects();
                notifyAttendance(data);
            };

            const initRealtime = () => {
                const pusherKey = @json(config('broadcasting.connections.pusher.key'));
                const pusherCluster = @json(config('broadcasting.connections.pusher.options.cluster'));

                if (!pusherKey || typeof Pusher === 'undefined') return;

                const pusher = new Pusher(pusherKey, {
                    cluster: pusherCluster,
                    forceTLS: true
                });

                pusher.subscribe('dashboard-channel')
                    .bind('musyrif-absen-event', handleRealtimeAttendance);
            };

            const themeObserver = new MutationObserver(mutations => {
                const themeChanged = mutations.some(mutation => mutation.attributeName ===
                    'data-coreui-theme');
                if (themeChanged) {
                    requestAnimationFrame(refreshChartTheme);
                }
            });

            themeObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-coreui-theme']
            });

            updateSoundButton();
            initCounters();
            initCharts();
            initRealtime();
        });
    </script>
@endpush
