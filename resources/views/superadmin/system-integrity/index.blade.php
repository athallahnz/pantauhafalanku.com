@extends('layouts.app')

@section('title', 'Integritas User & Profil')

@section('content')
    @php
        $summary = $scan['summary'];
        $systemStatus = $scan['status'];
        $statusMeta = match ($systemStatus) {
            'critical' => [
                'label' => 'Kritis',
                'class' => 'danger',
                'icon' => 'bi-shield-fill-exclamation',
                'message' => 'Ada masalah struktural yang membutuhkan pemeriksaan segera.',
            ],
            'warning' => [
                'label' => 'Perlu Perhatian',
                'class' => 'warning',
                'icon' => 'bi-shield-fill-check',
                'message' => 'Sistem tetap dapat digunakan, tetapi terdapat data yang belum sinkron.',
            ],
            default => [
                'label' => 'Sehat',
                'class' => 'success',
                'icon' => 'bi-shield-check',
                'message' => 'Tidak ditemukan masalah integritas user, profil, atau placement.',
            ],
        };
    @endphp

    <style>
        :root,
        [data-coreui-theme="light"] {
            --integrity-surface: #ffffff;
            --integrity-soft: #f6f7fb;
            --integrity-text: #24283a;
            --integrity-muted: #6f7485;
            --integrity-border: rgba(23, 26, 42, .09);
            --integrity-purple: var(--islamic-purple-600, #6b4eff);
            --integrity-tosca: var(--islamic-tosca-600, #13a3b3);
            --integrity-shadow: 0 12px 34px rgba(27, 32, 56, .07);
        }

        [data-coreui-theme="dark"] {
            --integrity-surface: #20212b;
            --integrity-soft: #292b36;
            --integrity-text: #f3f4f7;
            --integrity-muted: #a8adbc;
            --integrity-border: rgba(255, 255, 255, .09);
            --integrity-purple: #a99bff;
            --integrity-tosca: #64d5df;
            --integrity-shadow: 0 14px 38px rgba(0, 0, 0, .24);
        }

        .integrity-page {
            color: var(--integrity-text);
            padding-bottom: 2rem;
        }

        .integrity-hero {
            position: relative;
            overflow: hidden;
            padding: clamp(1.35rem, 3vw, 2rem);
            border-radius: 24px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 10%, rgba(255, 255, 255, .22), transparent 28%),
                linear-gradient(135deg, #433280 0%, #1599aa 100%);
            box-shadow: 0 18px 44px rgba(72, 58, 171, .22);
        }

        .integrity-hero::after {
            content: '';
            position: absolute;
            right: -70px;
            bottom: -100px;
            width: 230px;
            height: 230px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 50%;
            box-shadow: 0 0 0 32px rgba(255, 255, 255, .045), 0 0 0 68px rgba(255, 255, 255, .025);
        }

        .integrity-hero>* {
            position: relative;
            z-index: 1;
        }

        .integrity-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .75rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .integrity-status-badge {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .7rem 1rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            background: rgba(255, 255, 255, .14);
            font-size: .8rem;
            font-weight: 800;
            backdrop-filter: blur(8px);
        }

        .integrity-card {
            height: 100%;
            border: 1px solid var(--integrity-border);
            border-radius: 20px;
            background: var(--integrity-surface);
            box-shadow: var(--integrity-shadow);
        }

        .integrity-kpi {
            position: relative;
            overflow: hidden;
            padding: 1.15rem;
        }

        .integrity-kpi::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: var(--kpi-color, var(--integrity-purple));
        }

        .integrity-kpi-icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: var(--kpi-color, var(--integrity-purple));
            background: color-mix(in srgb, var(--kpi-color, var(--integrity-purple)) 12%, transparent);
            font-size: 1.2rem;
        }

        .integrity-kpi-label {
            color: var(--integrity-muted);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .integrity-kpi-value {
            margin-top: .35rem;
            color: var(--integrity-text);
            font-size: 1.9rem;
            font-weight: 850;
            line-height: 1;
        }

        .integrity-filter-card {
            padding: 1.1rem;
        }

        .integrity-filter-card .form-select,
        .integrity-filter-card .form-control {
            min-height: 42px;
            border-color: var(--integrity-border);
            border-radius: 12px;
            background-color: var(--integrity-soft);
            color: var(--integrity-text);
        }

        .integrity-table-card {
            overflow: hidden;
        }

        .integrity-table-card .card-header {
            padding: 1.15rem 1.25rem;
            border-bottom: 1px solid var(--integrity-border);
            background: transparent;
        }

        .severity-badge,
        .category-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem .65rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .severity-critical {
            color: #dc3545;
            background: rgba(220, 53, 69, .11);
        }

        .severity-warning {
            color: #b87500;
            background: rgba(255, 193, 7, .15);
        }

        .severity-info {
            color: #0d6efd;
            background: rgba(13, 110, 253, .11);
        }

        .category-badge {
            color: var(--integrity-purple);
            background: color-mix(in srgb, var(--integrity-purple) 11%, transparent);
        }

        .issue-title {
            color: var(--integrity-text);
            font-size: .84rem;
            font-weight: 800;
        }

        .issue-description {
            max-width: 520px;
            margin-top: .2rem;
            color: var(--integrity-muted);
            font-size: .72rem;
            line-height: 1.45;
        }

        .entity-label {
            color: var(--integrity-text);
            font-size: .78rem;
            font-weight: 700;
        }

        .entity-meta {
            color: var(--integrity-muted);
            font-size: .66rem;
        }

        .repair-log-item {
            display: flex;
            align-items: flex-start;
            gap: .8rem;
            padding: .85rem 0;
            border-bottom: 1px dashed var(--integrity-border);
        }

        .repair-log-item:last-child {
            border-bottom: 0;
        }

        .repair-log-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: #198754;
            background: rgba(25, 135, 84, .11);
        }

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
            color: #fff;
            background: linear-gradient(135deg, var(--islamic-purple-600, #6f42c1), var(--islamic-purple-700, #59359d));
            box-shadow: 0 12px 30px rgba(89, 53, 157, .34);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .page-guide-fab:hover,
        .page-guide-fab:focus-visible {
            color: #fff;
            transform: translateY(-3px) scale(1.03);
            filter: brightness(1.06);
            box-shadow: 0 16px 36px rgba(89, 53, 157, .42);
        }

        .page-guide-fab::after {
            content: '';
            position: absolute;
            inset: -5px;
            border: 2px solid rgba(111, 66, 193, .22);
            border-radius: inherit;
            animation: integrityGuidePulse 2.4s ease-out infinite;
            pointer-events: none;
        }

        .page-guide-hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 92% 10%, rgba(255, 255, 255, .18), transparent 24%),
                linear-gradient(135deg, var(--islamic-purple-700, #59359d), var(--islamic-purple-600, #6f42c1));
        }

        .guide-step {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--integrity-border);
            border-radius: 15px;
            background: var(--integrity-soft);
        }

        .guide-step-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: var(--integrity-purple);
            background: color-mix(in srgb, var(--integrity-purple) 12%, transparent);
        }

        @keyframes integrityGuidePulse {
            0% { transform: scale(.88); opacity: 0; }
            30% { opacity: 1; }
            100% { transform: scale(1.28); opacity: 0; }
        }

        @media (max-width: 575.98px) {
            .page-guide-fab {
                right: max(14px, env(safe-area-inset-right));
                bottom: max(14px, env(safe-area-inset-bottom));
                width: 52px;
                height: 52px;
            }
        }
    

        /* =========================================================
           UI REVAMP OVERRIDES
           ========================================================= */
        .integrity-page {
            position: relative;
            isolation: isolate;
            padding-bottom: 2.25rem;
        }

        .integrity-page::before {
            content: '';
            position: absolute;
            inset: -1.5rem -1.5rem auto;
            height: 320px;
            z-index: -1;
            pointer-events: none;
            background:
                radial-gradient(circle at 8% 3%, rgba(107, 78, 255, .08), transparent 34%),
                radial-gradient(circle at 86% 6%, rgba(19, 163, 179, .07), transparent 32%);
            mask-image: linear-gradient(to bottom, #000 0%, transparent 100%);
        }

        .integrity-card {
            border-radius: 20px;
            box-shadow: 0 8px 26px rgba(27, 32, 56, .065);
        }

        [data-coreui-theme="dark"] .integrity-card {
            box-shadow: 0 12px 32px rgba(0, 0, 0, .25);
        }

        /* HERO */
        .integrity-hero {
            padding: clamp(1.45rem, 3vw, 2.15rem);
            border-radius: 26px;
        }

        .integrity-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(285px, .8fr);
            gap: clamp(1.25rem, 3vw, 2.4rem);
            align-items: center;
        }

        .integrity-hero-title {
            margin: .9rem 0 .45rem;
            font-size: clamp(1.55rem, 3vw, 2.3rem);
            line-height: 1.12;
            font-weight: 850;
            letter-spacing: -.035em;
        }

        .integrity-hero-copy {
            max-width: 760px;
            margin: 0;
            color: rgba(255, 255, 255, .78);
            font-size: .9rem;
            line-height: 1.65;
        }

        .integrity-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
        }

        .integrity-hero-meta-item {
            display: inline-flex;
            align-items: center;
            gap: .42rem;
            padding: .48rem .7rem;
            border: 1px solid rgba(255, 255, 255, .15);
            border-radius: 11px;
            background: rgba(255, 255, 255, .09);
            color: rgba(255, 255, 255, .82);
            font-size: .72rem;
            backdrop-filter: blur(7px);
        }

        .integrity-status-panel {
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: 20px;
            background: rgba(255, 255, 255, .11);
            backdrop-filter: blur(12px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .11);
        }

        .integrity-status-top {
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .integrity-status-icon {
            width: 50px;
            height: 50px;
            flex: 0 0 50px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            background: rgba(255, 255, 255, .14);
            font-size: 1.35rem;
        }

        .integrity-status-label {
            color: rgba(255, 255, 255, .70);
            font-size: .68rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .integrity-status-value {
            margin-top: .15rem;
            font-size: 1.15rem;
            font-weight: 850;
        }

        .integrity-status-message {
            margin: .85rem 0 1rem;
            color: rgba(255, 255, 255, .74);
            font-size: .76rem;
            line-height: 1.55;
        }

        .integrity-hero-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .6rem;
        }

        .integrity-hero-actions .btn {
            min-height: 42px;
            border-radius: 12px;
            font-size: .76rem;
            font-weight: 800;
        }

        /* SECTION TITLES */
        .integrity-section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .85rem;
        }

        .integrity-section-kicker {
            margin-bottom: .28rem;
            color: var(--integrity-purple);
            font-size: .67rem;
            font-weight: 850;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .integrity-section-title {
            margin: 0;
            color: var(--integrity-text);
            font-size: 1.02rem;
            font-weight: 850;
            letter-spacing: -.012em;
        }

        .integrity-section-copy {
            margin: .22rem 0 0;
            color: var(--integrity-muted);
            font-size: .76rem;
            line-height: 1.5;
        }

        /* KPI */
        .integrity-kpi {
            min-height: 132px;
            padding: 1.05rem;
            transition:
                transform .22s ease,
                box-shadow .22s ease,
                border-color .22s ease;
        }

        .integrity-kpi:hover {
            transform: translateY(-3px);
            border-color: rgba(107, 78, 255, .18);
            box-shadow: 0 18px 44px rgba(27, 32, 56, .11);
        }

        .integrity-kpi::after {
            content: '';
            position: absolute;
            top: -30px;
            right: -28px;
            width: 92px;
            height: 92px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--kpi-color, var(--integrity-purple)) 10%, transparent);
            opacity: .8;
        }

        .integrity-kpi > .d-flex {
            position: relative;
            z-index: 1;
            height: 100%;
        }

        .integrity-kpi-label {
            max-width: 120px;
            font-size: .65rem;
            line-height: 1.35;
        }

        .integrity-kpi-value {
            margin-top: .7rem;
            font-size: clamp(1.65rem, 2.4vw, 2rem);
            letter-spacing: -.04em;
        }

        .integrity-kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: 13px;
        }

        /* FILTER */
        .integrity-filter-card {
            padding: 1.15rem 1.2rem 1.2rem;
        }

        .integrity-filter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .95rem;
            padding-bottom: .8rem;
            border-bottom: 1px solid var(--integrity-border);
        }

        .integrity-filter-title {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: var(--integrity-text);
            font-size: .9rem;
            font-weight: 850;
        }

        .integrity-filter-title i {
            color: var(--integrity-purple);
        }

        .integrity-filter-card .form-label {
            margin-bottom: .4rem;
            color: var(--integrity-muted) !important;
            font-size: .64rem !important;
            font-weight: 850 !important;
            letter-spacing: .075em;
        }

        .integrity-filter-card .form-select,
        .integrity-filter-card .form-control {
            min-height: 43px;
            border: 1px solid var(--integrity-border);
            border-radius: 12px;
            font-size: .8rem;
            box-shadow: none;
        }

        .integrity-filter-card .form-select:focus,
        .integrity-filter-card .form-control:focus {
            border-color: var(--integrity-purple);
            box-shadow: 0 0 0 .22rem rgba(107, 78, 255, .11);
        }

        #btnResetFilters {
            min-height: 43px;
            border-radius: 12px !important;
            font-size: .78rem;
        }

        /* TABLE */
        .integrity-table-card .card-header {
            padding: 1.05rem 1.25rem;
            background:
                linear-gradient(135deg, rgba(107, 78, 255, .035), rgba(19, 163, 179, .022)),
                var(--integrity-surface);
        }

        .integrity-table-card .table > :not(caption) > * > * {
            padding-top: .9rem;
            padding-bottom: .9rem;
            border-bottom-color: var(--integrity-border);
        }

        .integrity-table-card .table thead th {
            color: var(--integrity-muted);
            background: var(--integrity-soft);
            font-size: .65rem;
            font-weight: 850;
            letter-spacing: .065em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .integrity-table-card .table tbody tr {
            transition: background-color .18s ease;
        }

        .integrity-table-card .table tbody tr:hover {
            background: color-mix(in srgb, var(--integrity-purple) 7%, transparent);
        }

        .integrity-table-card .dataTables_wrapper .row:first-child {
            padding: 1rem 1.1rem .75rem;
            margin: 0;
            border-bottom: 1px solid var(--integrity-border);
        }

        .integrity-table-card .dataTables_wrapper .row:last-child {
            padding: .85rem 1.1rem 1rem;
            margin: 0;
            border-top: 1px solid var(--integrity-border);
        }

        .integrity-table-card .dataTables_filter input,
        .integrity-table-card .dataTables_length select {
            min-height: 38px;
            border: 1px solid var(--integrity-border);
            border-radius: 10px;
            background: var(--integrity-soft);
            color: var(--integrity-text);
            box-shadow: none;
        }

        .integrity-table-card .dataTables_filter input {
            min-width: 240px;
            padding: .45rem .75rem;
        }

        .integrity-table-card .page-link {
            border-color: var(--integrity-border);
            color: var(--integrity-text);
            background: var(--integrity-surface);
        }

        .integrity-table-card .page-item.active .page-link {
            border-color: var(--integrity-purple);
            background: var(--integrity-purple);
            color: #fff;
        }

        .issue-title {
            font-size: .8rem;
            line-height: 1.35;
        }

        .issue-description {
            margin-top: .24rem;
            font-size: .69rem;
            line-height: 1.5;
        }

        .entity-label {
            font-size: .76rem;
            line-height: 1.35;
        }

        .entity-meta {
            margin-top: .18rem;
            font-size: .64rem;
        }

        /* LOWER CARDS */
        .integrity-support-card {
            height: 100%;
            padding: 1.2rem;
        }

        .integrity-support-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .integrity-support-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            color: var(--integrity-purple);
            background: color-mix(in srgb, var(--integrity-purple) 12%, transparent);
            font-size: 1.15rem;
        }

        .guide-step {
            padding: .95rem;
            transition: border-color .18s ease, transform .18s ease;
        }

        .guide-step:hover {
            transform: translateY(-2px);
            border-color: rgba(107, 78, 255, .18);
        }

        .repair-log-list {
            max-height: 390px;
            overflow-y: auto;
            padding-right: .25rem;
        }

        .repair-log-list::-webkit-scrollbar {
            width: 6px;
        }

        .repair-log-list::-webkit-scrollbar-thumb {
            border-radius: 999px;
            background: var(--integrity-border);
        }

        @media (max-width: 991.98px) {
            .integrity-hero-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .integrity-filter-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .integrity-table-card .dataTables_filter input {
                min-width: 0;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .integrity-hero {
                padding: 1.2rem;
                border-radius: 20px;
            }

            .integrity-hero-actions {
                grid-template-columns: 1fr;
            }

            .integrity-kpi {
                min-height: 118px;
                padding: .9rem;
            }
        }

    </style>

    <div class="integrity-page">
        <section class="integrity-hero mb-4">
            <div class="integrity-hero-grid">
                <div>
                    <span class="integrity-eyebrow">
                        <i class="bi bi-database-check"></i>
                        Data Governance
                    </span>

                    <h3 class="integrity-hero-title">User & Profile Consistency Checker</h3>

                    <p class="integrity-hero-copy">
                        Memeriksa sinkronisasi akun, role, profil Santri/Musyrif, lifecycle akun,
                        semester aktif, dan placement tanpa mengubah data secara diam-diam.
                    </p>

                    <div class="integrity-hero-meta">
                        <span class="integrity-hero-meta-item">
                            <i class="bi bi-clock-history"></i>
                            Scan {{ \Carbon\Carbon::parse($scan['scanned_at'])->translatedFormat('d M Y H:i:s') }}
                        </span>

                        @if ($scan['active_semester'])
                            <span class="integrity-hero-meta-item">
                                <i class="bi bi-calendar2-check"></i>
                                Semester {{ $scan['active_semester']['label'] }}
                            </span>
                        @endif

                        <span class="integrity-hero-meta-item">
                            <i class="bi bi-shield-lock"></i>
                            Repair tercatat dalam audit
                        </span>
                    </div>
                </div>

                <aside class="integrity-status-panel">
                    <div class="integrity-status-top">
                        <span class="integrity-status-icon">
                            <i class="bi {{ $statusMeta['icon'] }}"></i>
                        </span>

                        <div class="min-w-0">
                            <div class="integrity-status-label">Status Integritas</div>
                            <div class="integrity-status-value">{{ $statusMeta['label'] }}</div>
                        </div>
                    </div>

                    <p class="integrity-status-message">
                        {{ $statusMeta['message'] }}
                    </p>

                    <div class="integrity-hero-actions">
                        <button type="button" class="btn btn-light fw-bold" id="btnRefreshScan">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Scan Ulang
                        </button>

                        <button type="button" class="btn btn-warning fw-bold"
                            id="btnRepairSafe" {{ $summary['safe_auto_repair'] < 1 ? 'disabled' : '' }}>
                            <i class="bi bi-magic me-1"></i>
                            Perbaiki Aman
                            <span class="badge bg-dark bg-opacity-25 ms-1">
                                {{ number_format($summary['safe_auto_repair'], 0, ',', '.') }}
                            </span>
                        </button>
                    </div>
                </aside>
            </div>
        </section>

        <div class="integrity-section-heading">
            <div>
                <div class="integrity-section-kicker">Ringkasan Pemeriksaan</div>
                <h4 class="integrity-section-title">Kondisi Integritas Saat Ini</h4>
                <p class="integrity-section-copy">
                    Angka berikut membantu menentukan prioritas sebelum membuka detail temuan.
                </p>
            </div>
        </div>

        <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-6">
            @foreach ([
                ['label' => 'Total Temuan', 'value' => $summary['total'], 'icon' => 'bi-list-check', 'color' => '#6b4eff'],
                ['label' => 'Kritis', 'value' => $summary['critical'], 'icon' => 'bi-exclamation-octagon-fill', 'color' => '#dc3545'],
                ['label' => 'Peringatan', 'value' => $summary['warning'], 'icon' => 'bi-exclamation-triangle-fill', 'color' => '#d98b00'],
                ['label' => 'Informasi', 'value' => $summary['info'], 'icon' => 'bi-info-circle-fill', 'color' => '#0d6efd'],
                ['label' => 'Bisa Diperbaiki', 'value' => $summary['repairable'], 'icon' => 'bi-tools', 'color' => '#198754'],
                ['label' => 'Manual Review', 'value' => $summary['manual'], 'icon' => 'bi-person-check-fill', 'color' => '#687083'],
            ] as $kpi)
                <div class="col">
                    <div class="integrity-card integrity-kpi" style="--kpi-color: {{ $kpi['color'] }};">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="integrity-kpi-label">{{ $kpi['label'] }}</div>
                                <div class="integrity-kpi-value">{{ number_format($kpi['value'], 0, ',', '.') }}</div>
                            </div>
                            <span class="integrity-kpi-icon"><i class="bi {{ $kpi['icon'] }}"></i></span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="integrity-card integrity-filter-card mb-4">
            <div class="integrity-filter-head">
                <div>
                    <div class="integrity-filter-title">
                        <i class="bi bi-funnel-fill"></i>
                        Filter Temuan
                    </div>
                    <div class="small text-muted mt-1">
                        Saring berdasarkan prioritas, kategori, atau cara penanganannya.
                    </div>
                </div>

                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    <i class="bi bi-database-lock me-1"></i>
                    Filter tidak mengubah data
                </span>
            </div>

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Prioritas</label>
                    <select class="form-select" id="filterSeverity">
                        <option value="">Semua prioritas</option>
                        <option value="critical">Kritis</option>
                        <option value="warning">Peringatan</option>
                        <option value="info">Informasi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Kategori</label>
                    <select class="form-select" id="filterCategory">
                        <option value="">Semua kategori</option>
                        <option value="account">Lifecycle Akun</option>
                        <option value="profile">User & Profil</option>
                        <option value="semester">Semester</option>
                        <option value="placement">Placement</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Penanganan</label>
                    <select class="form-select" id="filterRepairability">
                        <option value="">Semua temuan</option>
                        <option value="safe">Perbaikan otomatis aman</option>
                        <option value="repairable">Bisa diperbaiki</option>
                        <option value="manual">Wajib review manual</option>
                    </select>
                </div>
                <div class="col-md-3 d-grid">
                    <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold" id="btnResetFilters">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <div class="integrity-card integrity-table-card mb-4">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <div class="fw-bold"><i class="bi bi-activity me-2 text-primary"></i>Daftar Temuan Integritas</div>
                    <div class="small text-muted mt-1">Perbaikan manual tidak dijalankan otomatis untuk melindungi histori data.</div>
                </div>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    Repair aman selalu memakai transaksi database
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="integrity-table" class="table table-hover align-middle w-100 mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">No.</th>
                                <th>Prioritas</th>
                                <th>Kategori</th>
                                <th>Temuan</th>
                                <th>Objek Data</th>
                                <th class="text-end pe-4">Penanganan</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="integrity-card integrity-support-card">
                    <div class="integrity-support-head">
                        <div>
                            <div class="integrity-section-kicker">Safety Rules</div>
                            <div class="integrity-section-title">Prinsip Perbaikan</div>
                            <div class="integrity-section-copy">Batas antara repair otomatis dan keputusan manusia.</div>
                        </div>
                        <span class="integrity-support-icon">
                            <i class="bi bi-shield-lock-fill"></i>
                        </span>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon text-success"><i class="bi bi-magic"></i></span>
                                    <div>
                                        <div class="fw-bold small mb-1">Aman diotomatisasi</div>
                                        <div class="small text-muted">
                                            Sinkron nama, membuat kode Musyrif, membuat profil Musyrif yang hilang,
                                            dan membuat placement dari kelas yang sudah valid.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon text-warning"><i class="bi bi-person-fill-gear"></i></span>
                                    <div>
                                        <div class="fw-bold small mb-1">Guided repair</div>
                                        <div class="small text-muted">
                                            Profil Santri yang hilang meminta kelas terlebih dahulu agar data tidak
                                            dimasukkan ke kelas default yang salah.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon text-danger"><i class="bi bi-hand-index-thumb-fill"></i></span>
                                    <div>
                                        <div class="fw-bold small mb-1">Wajib keputusan manual</div>
                                        <div class="small text-muted">
                                            Role mismatch, profil orphan, placement duplikat, dan semester aktif ganda
                                            tidak disentuh otomatis karena dapat memiliki histori akademik yang harus dipertahankan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="integrity-card integrity-support-card">
                    <div class="integrity-support-head">
                        <div>
                            <div class="integrity-section-kicker">Audit Trail</div>
                            <div class="integrity-section-title">Perbaikan Terbaru</div>
                            <div class="integrity-section-copy">Audit singkat tindakan consistency checker.</div>
                        </div>
                        <span class="integrity-support-icon text-success">
                            <i class="bi bi-clock-history"></i>
                        </span>
                    </div>

                    <div class="repair-log-list">
                    @forelse ($latestRepairs as $repair)
                        <div class="repair-log-item">
                            <span class="repair-log-icon">
                                <i class="bi {{ $repair->status === 'success' ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="fw-bold small text-break">{{ str_replace('_', ' ', ucfirst($repair->action)) }}</div>
                                <div class="small text-muted text-break">{{ $repair->reason ?: '-' }}</div>
                                <div class="mt-1" style="font-size: .65rem; color: var(--integrity-muted);">
                                    {{ $repair->actor?->name ?? 'System' }} ·
                                    {{ $repair->created_at?->translatedFormat('d M Y H:i') }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Belum ada perbaikan yang tercatat.
                        </div>
                    @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <button type="button" class="page-guide-fab" id="btnPageGuide"
        aria-label="Buka panduan halaman integritas" title="Panduan halaman">
        <i class="bi bi-info-lg fs-4"></i>
    </button>

    <div class="modal fade" id="modalSantriRepair" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formSantriRepair" class="w-100">
                @csrf
                <input type="hidden" name="issue_type" value="missing_santri_profile">
                <input type="hidden" name="entity_id" id="repairSantriUserId">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header page-guide-hero border-0 px-4 py-4">
                        <div>
                            <div class="small text-white-50 fw-semibold mb-1">Guided Repair</div>
                            <h5 class="modal-title fw-bold mb-1">Lengkapi Profil Santri</h5>
                            <p class="small text-white-50 mb-0">Pilih kelas agar profil dan placement dibuat dengan konteks yang benar.</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning border-0 rounded-4 small">
                            Sistem tidak menggunakan kelas ID 1 sebagai default. Kelas harus dipilih secara eksplisit.
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kelas <span class="text-danger">*</span></label>
                            <select class="form-select" name="kelas_id" id="repairKelasId" required>
                                <option value="">Pilih kelas...</option>
                                @foreach ($kelasList as $kelas)
                                    <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label fw-bold">Musyrif <span class="text-muted small">(opsional)</span></label>
                            <select class="form-select" name="musyrif_id" id="repairMusyrifId">
                                <option value="">Belum ditentukan</option>
                                @foreach ($musyrifList as $musyrif)
                                    <option value="{{ $musyrif->id }}">{{ $musyrif->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white rounded-pill px-4 fw-bold"
                            style="background: var(--islamic-purple-600);">
                            <i class="bi bi-tools me-1"></i> Buat Profil
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalPageGuide" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header page-guide-hero border-0 px-4 py-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 52px; height: 52px;">
                            <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-white-50 fw-semibold mb-1">Petunjuk Penggunaan</div>
                            <h5 class="modal-title fw-bold mb-1">Panduan Consistency Checker</h5>
                            <p class="small text-white-50 mb-0">Cara membaca temuan dan menjalankan perbaikan dengan aman.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-4 d-flex gap-3 mb-4">
                        <i class="bi bi-lightbulb-fill fs-5"></i>
                        <div class="small">
                            Jalankan <b>Scan Ulang</b> setelah migrasi, perubahan role, import user, atau aktivasi semester.
                            Temuan manual tidak berarti data pasti salah; sebagian membutuhkan konteks operasional.
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex gap-3">
                                    <span class="guide-step-icon text-danger"><i class="bi bi-exclamation-octagon-fill"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Kritis</div>
                                        <div class="small text-muted">Duplikasi, role mismatch, semester aktif ganda, atau relasi yang dapat mengubah histori.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex gap-3">
                                    <span class="guide-step-icon text-warning"><i class="bi bi-exclamation-triangle-fill"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Peringatan</div>
                                        <div class="small text-muted">Data belum sinkron, tetapi biasanya masih dapat diperbaiki tanpa menghapus histori.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex gap-3">
                                    <span class="guide-step-icon text-success"><i class="bi bi-magic"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Perbaiki Aman</div>
                                        <div class="small text-muted">Menjalankan hanya repair deterministik dan mencatat before/after ke audit log.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="guide-step">
                                <div class="d-flex gap-3">
                                    <span class="guide-step-icon text-primary"><i class="bi bi-person-fill-gear"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Guided Repair</div>
                                        <div class="small text-muted">Meminta input penting seperti kelas sebelum membuat profil Santri.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning border-0 rounded-4 small mt-4 mb-0">
                        Jangan mengubah role atau menghapus profil orphan langsung dari database. Periksa transaksi, placement,
                        dan histori akademik yang terhubung terlebih dahulu.
                    </div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-coreui-dismiss="modal">Tutup</button>
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const modalSantriRepair = new coreui.Modal(document.getElementById('modalSantriRepair'));
            const modalPageGuide = new coreui.Modal(document.getElementById('modalPageGuide'));

            const severityMeta = {
                critical: ['Kritis', 'severity-critical', 'bi-exclamation-octagon-fill'],
                warning: ['Peringatan', 'severity-warning', 'bi-exclamation-triangle-fill'],
                info: ['Informasi', 'severity-info', 'bi-info-circle-fill']
            };

            const categoryLabels = {
                account: 'Lifecycle Akun',
                profile: 'User & Profil',
                semester: 'Semester',
                placement: 'Placement'
            };

            const escapeHtml = (value) => $('<div>').text(value ?? '').html();

            const table = $('#integrity-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 15,
                ajax: {
                    url: "{{ route('superadmin.system-integrity.data') }}",
                    data: function(d) {
                        d.severity = $('#filterSeverity').val();
                        d.category = $('#filterCategory').val();
                        d.repairability = $('#filterRepairability').val();
                    }
                },
                columns: [
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'severity',
                        orderable: false,
                        render: function(value) {
                            const meta = severityMeta[value] || severityMeta.info;
                            return `<span class="severity-badge ${meta[1]}"><i class="bi ${meta[2]}"></i>${meta[0]}</span>`;
                        }
                    },
                    {
                        data: 'category',
                        orderable: false,
                        render: function(value) {
                            return `<span class="category-badge">${escapeHtml(categoryLabels[value] || value)}</span>`;
                        }
                    },
                    {
                        data: 'title',
                        render: function(value, type, row) {
                            return `<div class="issue-title">${escapeHtml(value)}</div><div class="issue-description">${escapeHtml(row.description)}</div>`;
                        }
                    },
                    {
                        data: 'entity_label',
                        render: function(value, type, row) {
                            return `<div class="entity-label">${escapeHtml(value)}</div><div class="entity-meta">${escapeHtml(row.entity_type)} #${row.entity_id || '-'}</div>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4',
                        render: function(data, type, row) {
                            if (!row.repairable) {
                                return '<span class="badge bg-light text-secondary border rounded-pill px-3 py-2"><i class="bi bi-person-check me-1"></i>Review Manual</span>';
                            }

                            const buttonClass = row.safe_auto_repair ? 'btn-success' : 'btn-primary';
                            const icon = row.requires_input ? 'bi-person-fill-gear' : 'bi-tools';

                            return `<button type="button" class="btn btn-sm ${buttonClass} rounded-pill px-3 fw-bold btn-repair" data-type="${escapeHtml(row.type)}" data-entity-id="${row.entity_id}" data-requires-input="${row.requires_input ? 1 : 0}" data-label="${escapeHtml(row.entity_label)}"><i class="bi ${icon} me-1"></i>${escapeHtml(row.repair_label || 'Perbaiki')}</button>`;
                        }
                    }
                ],
                order: [],
                language: {
                    search: '_INPUT_',
                    searchPlaceholder: 'Cari temuan atau objek data...',
                    lengthMenu: 'Tampil _MENU_ data',
                    emptyTable: 'Tidak ada masalah integritas pada filter ini.',
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });

            $('#filterSeverity, #filterCategory, #filterRepairability').on('change', function() {
                table.ajax.reload();
            });

            $('#btnResetFilters').on('click', function() {
                $('#filterSeverity, #filterCategory, #filterRepairability').val('');
                table.ajax.reload();
            });

            $('#btnRefreshScan').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memindai...');
                window.location.reload();
            });

            $('#btnPageGuide').on('click', function() {
                modalPageGuide.show();
            });

            $(document).on('click', '.btn-repair', function() {
                const type = $(this).data('type');
                const entityId = $(this).data('entity-id');
                const requiresInput = Number($(this).data('requires-input')) === 1;
                const label = $(this).data('label');

                if (requiresInput && type === 'missing_santri_profile') {
                    $('#formSantriRepair')[0].reset();
                    $('#repairSantriUserId').val(entityId);
                    modalSantriRepair.show();
                    return;
                }

                const executeRepair = () => $.ajax({
                    url: "{{ route('superadmin.system-integrity.repair') }}",
                    type: 'POST',
                    data: {
                        _token: csrfToken,
                        issue_type: type,
                        entity_id: entityId
                    }
                });

                AppAlert.warning(
                    `Sistem akan memperbaiki data ${label} menggunakan aturan aman dan mencatat audit before/after.`,
                    'Jalankan Perbaikan?'
                ).then(result => {
                    if (!result.isConfirmed) return;

                    executeRepair()
                        .done(res => {
                            AppAlert.success(res.message || 'Data berhasil diperbaiki.').then(() => window.location.reload());
                        })
                        .fail(xhr => {
                            const errors = xhr.responseJSON?.errors;
                            const message = errors
                                ? Object.values(errors).flat().join('\n')
                                : (xhr.responseJSON?.message || 'Perbaikan gagal dijalankan.');
                            AppAlert.error(message);
                        });
                });
            });

            $('#formSantriRepair').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const btn = form.find('button[type="submit"]');
                const original = btn.html();

                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memperbaiki...');

                $.ajax({
                    url: "{{ route('superadmin.system-integrity.repair') }}",
                    type: 'POST',
                    data: form.serialize()
                })
                    .done(res => {
                        modalSantriRepair.hide();
                        AppAlert.success(res.message || 'Profil Santri berhasil dibuat.').then(() => window.location.reload());
                    })
                    .fail(xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors
                            ? Object.values(errors).flat().join('\n')
                            : (xhr.responseJSON?.message || 'Profil Santri gagal dibuat.');
                        AppAlert.error(message);
                    })
                    .always(() => btn.prop('disabled', false).html(original));
            });

            $('#btnRepairSafe').on('click', function() {
                const btn = $(this);

                AppAlert.warning(
                    'Hanya temuan berlabel aman yang akan diperbaiki. Temuan manual dan guided repair akan dilewati.',
                    'Perbaiki Semua yang Aman?'
                ).then(result => {
                    if (!result.isConfirmed) return;

                    const original = btn.html();
                    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memproses...');

                    $.ajax({
                        url: "{{ route('superadmin.system-integrity.repair-safe') }}",
                        type: 'POST',
                        data: { _token: csrfToken }
                    })
                        .done(res => {
                            AppAlert.success(res.message || 'Perbaikan aman selesai.').then(() => window.location.reload());
                        })
                        .fail(xhr => {
                            AppAlert.error(xhr.responseJSON?.message || 'Perbaikan massal gagal dijalankan.');
                        })
                        .always(() => btn.prop('disabled', false).html(original));
                });
            });
        });
    </script>
@endpush
