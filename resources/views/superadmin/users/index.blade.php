@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    {{-- Halaman ini hanya membutuhkan $kelas dan $statusCounts dari UserController@index. --}}
    @php
        $statusOptions = [
            '' => [
                'label' => 'Semua Nonarsip',
                'icon' => 'people-fill',
                'count' => $statusCounts['all'] ?? 0,
                'tone' => 'purple',
            ],
            'pending' => [
                'label' => 'Pending',
                'icon' => 'clock-history',
                'count' => $statusCounts['pending'] ?? 0,
                'tone' => 'warning',
            ],
            'active' => [
                'label' => 'Aktif',
                'icon' => 'check-circle-fill',
                'count' => $statusCounts['active'] ?? 0,
                'tone' => 'success',
            ],
            'suspended' => [
                'label' => 'Suspended',
                'icon' => 'pause-circle-fill',
                'count' => $statusCounts['suspended'] ?? 0,
                'tone' => 'danger',
            ],
            'rejected' => [
                'label' => 'Rejected',
                'icon' => 'x-circle-fill',
                'count' => $statusCounts['rejected'] ?? 0,
                'tone' => 'secondary',
            ],
            'archived' => [
                'label' => 'Archived',
                'icon' => 'archive-fill',
                'count' => $statusCounts['archived'] ?? 0,
                'tone' => 'dark',
            ],
        ];

        $totalUsers = (int) ($statusCounts['all'] ?? 0);
        $totalActive = (int) ($statusCounts['active'] ?? 0);
        $totalPending = (int) ($statusCounts['pending'] ?? 0);
        $totalRestricted = (int) (($statusCounts['suspended'] ?? 0) + ($statusCounts['rejected'] ?? 0));
        $totalArchived = (int) ($statusCounts['archived'] ?? 0);
        $activeRate = $totalUsers > 0 ? round(($totalActive / $totalUsers) * 100) : 0;
    @endphp

    <style>
        :root,
        [data-coreui-theme="light"] {
            --users-surface: #ffffff;
            --users-soft: #f7f8fc;
            --users-muted-surface: #eef1f7;
            --users-text: #202436;
            --users-heading: #171a2a;
            --users-muted: #707586;
            --users-border: rgba(23, 26, 42, .09);
            --users-border-strong: rgba(23, 26, 42, .16);
            --users-purple: var(--islamic-purple-600, #6b4eff);
            --users-purple-dark: var(--islamic-purple-700, #5638d8);
            --users-purple-soft: rgba(107, 78, 255, .11);
            --users-tosca: var(--islamic-tosca-600, #13a3b3);
            --users-tosca-soft: rgba(19, 163, 179, .11);
            --users-success: #198754;
            --users-success-soft: rgba(25, 135, 84, .11);
            --users-warning: #c98000;
            --users-warning-soft: rgba(255, 193, 7, .16);
            --users-danger: #dc3545;
            --users-danger-soft: rgba(220, 53, 69, .11);
            --users-info: #0d6efd;
            --users-info-soft: rgba(13, 110, 253, .10);
            --users-shadow-sm: 0 8px 26px rgba(27, 32, 56, .06);
            --users-shadow-md: 0 17px 46px rgba(27, 32, 56, .11);
        }

        [data-coreui-theme="dark"] {
            color-scheme: dark;
            --users-surface: #20212b;
            --users-soft: #282a35;
            --users-muted-surface: #30323f;
            --users-text: #e8eaf1;
            --users-heading: #ffffff;
            --users-muted: #a8adbc;
            --users-border: rgba(255, 255, 255, .08);
            --users-border-strong: rgba(255, 255, 255, .15);
            --users-purple: #aa9cff;
            --users-purple-dark: #8b79ff;
            --users-purple-soft: rgba(132, 112, 255, .20);
            --users-tosca: #64d5df;
            --users-tosca-soft: rgba(56, 189, 201, .18);
            --users-success: #5fd39a;
            --users-success-soft: rgba(38, 179, 112, .18);
            --users-warning: #ffd166;
            --users-warning-soft: rgba(255, 193, 7, .18);
            --users-danger: #ff8190;
            --users-danger-soft: rgba(255, 92, 108, .18);
            --users-info: #73a7ff;
            --users-info-soft: rgba(86, 142, 255, .18);
            --users-shadow-sm: 0 12px 32px rgba(0, 0, 0, .24);
            --users-shadow-md: 0 20px 52px rgba(0, 0, 0, .34);
        }

        .min-w-0 {
            min-width: 0;
        }

        .users-page {
            position: relative;
            isolation: isolate;
            color: var(--users-text);
            padding-bottom: 2rem;
        }

        .users-page::before {
            content: '';
            position: absolute;
            inset: -1.5rem -1.5rem auto;
            height: 330px;
            z-index: -1;
            pointer-events: none;
            background:
                radial-gradient(circle at 8% 4%, rgba(107, 78, 255, .09), transparent 34%),
                radial-gradient(circle at 88% 6%, rgba(19, 163, 179, .07), transparent 31%);
            mask-image: linear-gradient(to bottom, #000 0%, transparent 100%);
        }

        .users-card {
            border: 1px solid var(--users-border);
            border-radius: 21px;
            background: var(--users-surface);
            box-shadow: var(--users-shadow-sm);
        }

        /* HERO */
        .users-hero {
            position: relative;
            overflow: hidden;
            padding: clamp(1.4rem, 3vw, 2.1rem);
            border-radius: 26px;
            color: #fff;
            background:
                radial-gradient(circle at 88% 12%, rgba(255, 255, 255, .20), transparent 28%),
                linear-gradient(135deg, #433280 0%, #1599aa 100%);
            box-shadow: 0 20px 48px rgba(72, 58, 171, .22);
        }

        .users-hero::after {
            content: '';
            position: absolute;
            right: -92px;
            bottom: -125px;
            width: 255px;
            height: 255px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 50%;
            box-shadow:
                0 0 0 36px rgba(255, 255, 255, .04),
                0 0 0 76px rgba(255, 255, 255, .025);
        }

        .users-hero>* {
            position: relative;
            z-index: 1;
        }

        .users-hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(270px, .72fr);
            gap: clamp(1.2rem, 3vw, 2.4rem);
            align-items: center;
        }

        .users-eyebrow {
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

        .users-hero-title {
            margin: .9rem 0 .45rem;
            font-size: clamp(1.55rem, 3vw, 2.25rem);
            line-height: 1.12;
            font-weight: 860;
            letter-spacing: -.035em;
        }

        .users-hero-copy {
            max-width: 730px;
            margin: 0;
            color: rgba(255, 255, 255, .78);
            font-size: .88rem;
            line-height: 1.65;
        }

        .users-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
        }

        .users-hero-meta-item {
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

        .users-hero-action {
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, .17);
            border-radius: 20px;
            background: rgba(255, 255, 255, .11);
            backdrop-filter: blur(12px);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .11);
        }

        .users-hero-action-icon {
            width: 48px;
            height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: rgba(255, 255, 255, .14);
            font-size: 1.25rem;
        }

        .users-hero-action .btn {
            min-height: 44px;
            border-radius: 12px;
            font-size: .8rem;
            font-weight: 850;
        }

        /* KPI */
        .users-kpi {
            position: relative;
            overflow: hidden;
            min-height: 126px;
            padding: 1rem;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .users-kpi:hover {
            transform: translateY(-3px);
            border-color: var(--users-border-strong);
            box-shadow: var(--users-shadow-md);
        }

        .users-kpi::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: var(--metric-color, var(--users-purple));
        }

        .users-kpi::after {
            content: '';
            position: absolute;
            top: -32px;
            right: -26px;
            width: 92px;
            height: 92px;
            border-radius: 50%;
            background: var(--metric-soft, var(--users-purple-soft));
        }

        .users-kpi-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .users-kpi-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: .7rem;
        }

        .users-kpi-label {
            color: var(--users-muted);
            font-size: .64rem;
            font-weight: 850;
            letter-spacing: .075em;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .users-kpi-icon {
            width: 41px;
            height: 41px;
            flex: 0 0 41px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 13px;
            color: var(--metric-color, var(--users-purple));
            background: var(--metric-soft, var(--users-purple-soft));
            font-size: 1.06rem;
        }

        .users-kpi-value {
            margin-top: auto;
            padding-top: .7rem;
            color: var(--users-heading);
            font-size: 1.8rem;
            font-weight: 880;
            line-height: 1;
            letter-spacing: -.04em;
        }

        .users-kpi-sub {
            margin-top: .35rem;
            color: var(--users-muted);
            font-size: .66rem;
        }

        /* MANAGEMENT */
        .users-management {
            overflow: hidden;
        }

        .users-management-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid var(--users-border);
            background:
                linear-gradient(135deg, rgba(107, 78, 255, .035), rgba(19, 163, 179, .022)),
                var(--users-surface);
        }

        .users-section-kicker {
            margin-bottom: .24rem;
            color: var(--users-purple);
            font-size: .65rem;
            font-weight: 850;
            letter-spacing: .09em;
            text-transform: uppercase;
        }

        .users-section-title {
            margin: 0;
            color: var(--users-heading);
            font-size: 1rem;
            font-weight: 850;
            letter-spacing: -.01em;
        }

        .users-section-copy {
            margin: .2rem 0 0;
            color: var(--users-muted);
            font-size: .74rem;
            line-height: 1.5;
        }

        .account-status-filter {
            display: flex;
            flex-wrap: wrap;
            gap: .52rem;
            padding: 1rem 1.1rem .85rem;
            border-bottom: 1px solid var(--users-border);
        }

        .account-status-filter__button {
            display: inline-flex;
            align-items: center;
            gap: .43rem;
            min-height: 38px;
            padding: .45rem .72rem;
            border: 1px solid var(--users-border);
            border-radius: 999px;
            color: var(--users-muted);
            background: var(--users-soft);
            font-size: .73rem;
            font-weight: 800;
            transition: all .18s ease;
        }

        .account-status-filter__button:hover,
        .account-status-filter__button.active {
            color: var(--users-purple);
            border-color: color-mix(in srgb, var(--users-purple) 34%, transparent);
            background: var(--users-purple-soft);
            transform: translateY(-1px);
        }

        .account-status-filter__count {
            min-width: 23px;
            padding: .14rem .4rem;
            border-radius: 999px;
            color: inherit;
            background: color-mix(in srgb, currentColor 10%, transparent);
            text-align: center;
            font-size: .65rem;
            font-weight: 850;
        }

        .role-tabs-wrap {
            overflow-x: auto;
            padding: .15rem 1.1rem 0;
            scrollbar-width: thin;
        }

        .custom-tabs {
            flex-wrap: nowrap;
            min-width: max-content;
            margin: 0;
            border-bottom: 1px solid var(--users-border);
        }

        .custom-tabs .nav-link {
            display: inline-flex;
            align-items: center;
            gap: .48rem;
            min-height: 50px;
            padding: .8rem .95rem;
            border: 0;
            border-bottom: 3px solid transparent;
            color: var(--users-muted);
            background: transparent;
            font-size: .76rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .custom-tabs .nav-link:hover,
        .custom-tabs .nav-link.active {
            color: var(--users-purple);
            border-bottom-color: var(--users-purple);
            background: var(--users-purple-soft);
        }

        /* DATATABLE */
        .users-management .table {
            --cui-table-bg: transparent;
            color: var(--users-text);
        }

        .users-management .table> :not(caption)>*>* {
            padding-top: .88rem;
            padding-bottom: .88rem;
            border-bottom-color: var(--users-border);
            vertical-align: middle;
        }

        .users-management .table thead th {
            color: var(--users-muted);
            background: var(--users-soft);
            font-size: .64rem;
            font-weight: 850;
            letter-spacing: .065em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .users-management .table tbody tr {
            transition: background-color .18s ease;
        }

        .users-management .table tbody tr:hover {
            background: var(--users-purple-soft);
        }

        .dataTables_wrapper .row:first-child {
            padding: .95rem 1.1rem .75rem;
            margin: 0;
            border-bottom: 1px solid var(--users-border);
        }

        .dataTables_wrapper .row:last-child {
            padding: .85rem 1.1rem 1rem;
            margin: 0;
            border-top: 1px solid var(--users-border);
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            min-height: 38px;
            border: 1px solid var(--users-border);
            border-radius: 10px;
            background: var(--users-soft);
            color: var(--users-text);
            box-shadow: none;
        }

        .dataTables_wrapper .dataTables_length select {
            min-width: 74px !important;
            padding-right: 1.8rem !important;
            margin: 0 .35rem !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            min-width: 240px;
            padding: .42rem .72rem;
        }

        .dataTables_wrapper .page-link {
            border-color: var(--users-border);
            color: var(--users-text);
            background: var(--users-surface);
        }

        .dataTables_wrapper .page-item.active .page-link {
            border-color: var(--users-purple);
            color: #fff;
            background: var(--users-purple);
        }

        /* MODALS & BULK BAR */
        .form-control,
        .form-select {
            border-radius: 11px;
            padding: .62rem .9rem;
        }

        .form-label {
            margin-bottom: .42rem;
            color: var(--users-muted);
            font-size: .71rem;
            font-weight: 800;
            letter-spacing: .055em;
            text-transform: uppercase;
        }

        .glass-action-bar {
            border: 1px solid rgba(255, 255, 255, .15);
            background: rgba(25, 26, 34, .86);
            box-shadow: 0 13px 36px rgba(0, 0, 0, .32);
            backdrop-filter: blur(18px) saturate(155%);
            -webkit-backdrop-filter: blur(18px) saturate(155%);
        }

        #selectedCount {
            min-width: 22px;
        }

        .lifecycle-modal-hero {
            color: #fff;
            background:
                radial-gradient(circle at 90% 5%, rgba(255, 255, 255, .2), transparent 28%),
                linear-gradient(135deg, var(--users-purple-dark), var(--users-purple));
        }

        .audit-timeline {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: .8rem;
        }

        .audit-item {
            display: grid;
            grid-template-columns: 42px minmax(0, 1fr);
            gap: .8rem;
            padding: .9rem;
            border: 1px solid var(--users-border);
            border-radius: 15px;
            background: var(--users-soft);
        }

        .audit-item__icon {
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: var(--users-purple);
            background: var(--users-purple-soft);
        }

        .audit-empty {
            padding: 2.5rem 1rem;
            text-align: center;
            color: var(--users-muted);
        }

        @media (max-width: 991.98px) {
            .users-hero-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .users-management-head {
                align-items: flex-start;
                flex-direction: column;
            }

            .dataTables_wrapper .dataTables_filter input {
                min-width: 0;
                width: 100%;
            }
        }

        @media (max-width: 575.98px) {
            .users-hero {
                padding: 1.15rem;
                border-radius: 20px;
            }

            .users-hero-action {
                padding: .85rem;
            }

            .users-kpi {
                min-height: 116px;
                padding: .88rem;
            }

            .account-status-filter {
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .account-status-filter__button {
                flex: 0 0 auto;
            }

            .glass-action-bar {
                width: calc(100vw - 24px);
                padding: .72rem !important;
                border-radius: 18px !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .users-kpi,
            .account-status-filter__button {
                transition: none;
            }
        }
    </style>

    <div class="users-page">
        <section class="users-hero mb-4">
            <div class="users-hero-grid">
                <div>
                    <span class="users-eyebrow">
                        <i class="bi bi-person-lock"></i>
                        User & Access Governance
                    </span>

                    <h3 class="users-hero-title">Manajemen User & Lifecycle</h3>

                    <p class="users-hero-copy">
                        Kelola pembuatan akun, approval, role akses, suspend, penolakan, arsip,
                        pemulihan, dan audit lifecycle dalam satu pusat kendali.
                    </p>

                    <div class="users-hero-meta">
                        <span class="users-hero-meta-item">
                            <i class="bi bi-people-fill"></i>
                            {{ number_format($totalUsers, 0, ',', '.') }} akun nonarsip
                        </span>
                        <span class="users-hero-meta-item">
                            <i class="bi bi-check-circle-fill"></i>
                            {{ $activeRate }}% berstatus aktif
                        </span>
                        <span class="users-hero-meta-item">
                            <i class="bi bi-shield-check"></i>
                            Semua tindakan tercatat
                        </span>
                    </div>
                </div>

                <aside class="users-hero-action">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="users-hero-action-icon">
                            <i class="bi bi-person-plus-fill"></i>
                        </span>
                        <div>
                            <div class="small text-white-50 fw-bold text-uppercase">Quick Action</div>
                            <div class="fw-bold">Buat akun baru</div>
                        </div>
                    </div>

                    <p class="small text-white-50 mb-3">
                        Akun yang dibuat Super Admin langsung aktif. Role Santri wajib dipasangkan dengan kelas.
                    </p>

                    <button class="btn btn-light w-100 d-flex align-items-center justify-content-center gap-2"
                        id="btnAddUser">
                        <i class="bi bi-plus-circle-fill"></i>
                        Tambah User Baru
                    </button>
                </aside>
            </div>
        </section>

        <div class="row g-3 mb-4 row-cols-2 row-cols-lg-4">
            @foreach ([
            [
                'label' => 'Akun Aktif',
                'value' => $totalActive,
                'sub' => 'Dapat mengakses sistem',
                'icon' => 'bi-check-circle-fill',
                'color' => '#198754',
                'soft' => 'rgba(25, 135, 84, .11)',
            ],
            [
                'label' => 'Menunggu Approval',
                'value' => $totalPending,
                'sub' => 'Perlu validasi Super Admin',
                'icon' => 'bi-clock-history',
                'color' => '#c98000',
                'soft' => 'rgba(255, 193, 7, .16)',
            ],
            [
                'label' => 'Akses Dibatasi',
                'value' => $totalRestricted,
                'sub' => 'Suspended atau rejected',
                'icon' => 'bi-shield-exclamation',
                'color' => '#dc3545',
                'soft' => 'rgba(220, 53, 69, .11)',
            ],
            [
                'label' => 'Akun Diarsipkan',
                'value' => $totalArchived,
                'sub' => 'Tetap tersimpan via soft delete',
                'icon' => 'bi-archive-fill',
                'color' => '#687083',
                'soft' => 'rgba(104, 112, 131, .12)',
            ],
        ] as $metric)
                <div class="col">
                    <div class="users-card users-kpi"
                        style="--metric-color: {{ $metric['color'] }}; --metric-soft: {{ $metric['soft'] }};">
                        <div class="users-kpi-inner">
                            <div class="users-kpi-head">
                                <div class="users-kpi-label">{{ $metric['label'] }}</div>
                                <span class="users-kpi-icon">
                                    <i class="bi {{ $metric['icon'] }}"></i>
                                </span>
                            </div>

                            <div class="users-kpi-value">
                                {{ number_format($metric['value'], 0, ',', '.') }}
                            </div>
                            <div class="users-kpi-sub">{{ $metric['sub'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <section class="users-card users-management mb-4">
            <div class="users-management-head">
                <div>
                    <div class="users-section-kicker">Account Directory</div>
                    <h4 class="users-section-title">Daftar User Berdasarkan Role</h4>
                    <p class="users-section-copy">
                        Gunakan status lifecycle dan tab role untuk mempersempit data. Pilih beberapa akun untuk bulk
                        action.
                    </p>
                </div>

                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    <i class="bi bi-lock-fill me-1 text-primary"></i>
                    Role struktural dilindungi
                </span>
            </div>

            <div class="account-status-filter" id="accountStatusFilter">
                @foreach ($statusOptions as $value => $option)
                    <button type="button" class="account-status-filter__button {{ $loop->first ? 'active' : '' }}"
                        data-status="{{ $value }}">
                        <i class="bi bi-{{ $option['icon'] }}"></i>
                        {{ $option['label'] }}
                        <span class="account-status-filter__count">
                            {{ number_format((int) $option['count'], 0, ',', '.') }}
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="role-tabs-wrap">
                <ul class="nav custom-tabs" role="tablist">
                    @foreach ([
            'superadmin' => ['label' => 'Super Admin', 'icon' => 'shield-lock-fill'],
            'pimpinan' => ['label' => 'Pimpinan', 'icon' => 'person-circle'],
            'admin' => ['label' => 'Admin Dept', 'icon' => 'diagram-3-fill'],
            'musyrif' => ['label' => 'Musyrif', 'icon' => 'book-half'],
            'santri' => ['label' => 'Santri', 'icon' => 'person-badge-fill'],
        ] as $role => $meta)
                        <li class="nav-item">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-coreui-toggle="tab"
                                data-coreui-target="#tab-{{ $role }}" type="button">
                                <i class="bi bi-{{ $meta['icon'] }}"></i>
                                {{ $meta['label'] }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tab-content">
                @foreach (['superadmin', 'pimpinan', 'admin', 'musyrif', 'santri'] as $role)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $role }}">
                        <div class="table-responsive">
                            <table id="table-{{ $role }}" class="table table-hover align-middle w-100 mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width: 40px;">
                                            <input type="checkbox" class="form-check-input check-all"
                                                data-role="{{ $role }}">
                                        </th>
                                        <th class="ps-4">No.</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Nomor HP</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="users-card p-3 p-md-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-start gap-3">
                    <span class="users-kpi-icon"
                        style="--metric-color: var(--users-info); --metric-soft: var(--users-info-soft);">
                        <i class="bi bi-info-circle-fill"></i>
                    </span>
                    <div>
                        <div class="fw-bold mb-1">Pengelolaan akun yang aman</div>
                        <div class="small text-muted">
                            Gunakan suspend untuk pembatasan sementara dan archive untuk penyimpanan jangka panjang.
                            Hindari mengubah role Santri atau Musyrif dari form umum karena berkaitan dengan profil
                            akademik.
                        </div>
                    </div>
                </div>

                <a href="{{ route('superadmin.system-integrity.index') }}"
                    class="btn btn-outline-primary rounded-pill px-4 fw-bold flex-shrink-0">
                    <i class="bi bi-database-check me-1"></i>
                    Periksa Integritas
                </a>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div id="bulkActionBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-5 d-none animate__animated"
        style="z-index: 1050;">
        <div class="glass-action-bar text-white px-4 py-3 rounded-pill shadow-lg d-flex align-items-center gap-3">
            <span class="small fw-bold">
                <span id="selectedCount" class="badge rounded-pill bg-white text-dark me-1">0</span>
                Terpilih
            </span>
            <div class="vr opacity-25"></div>

            <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold d-flex align-items-center gap-2"
                id="btnBulkApprove">
                <i class="bi bi-check-circle-fill"></i>
                <span class="d-none d-md-inline">Approve</span>
            </button>

            <button class="btn btn-sm btn-secondary rounded-pill px-3 fw-bold d-flex align-items-center gap-2"
                id="btnBulkArchive">
                <i class="bi bi-archive-fill"></i>
                <span class="d-none d-md-inline">Arsipkan</span>
            </button>

            <div class="vr opacity-25"></div>
            <button class="btn btn-sm btn-link text-white p-0" id="btnCancelBulk" title="Batalkan pilihan">
                <i class="bi bi-x-circle-fill fs-5"></i>
            </button>
        </div>
    </div>

    <div class="modal fade" id="modalUser" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formUser" class="w-100">
                @csrf
                <input type="hidden" name="id" id="user_id">
                <input type="hidden" name="role" id="role_locked" disabled>

                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4 bg-primary text-white">
                        <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalUserTitle">
                            <i class="bi bi-person-plus-fill"></i> Tambah User Baru
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-3 small d-flex align-items-start gap-2 mb-3">
                            <i class="bi bi-shield-check mt-1"></i>
                            <div>
                                Akun yang dibuat Super Admin langsung aktif. Role Santri wajib memiliki kelas.
                                Perubahan role yang melibatkan Santri atau Musyrif tetap dikunci untuk menjaga riwayat.
                            </div>
                        </div>

                        <div id="roleLockNotice" class="alert alert-warning border-0 rounded-3 small d-none mb-3">
                            <i class="bi bi-lock-fill me-1"></i>
                            Role Santri atau Musyrif dikunci pada form edit.
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor HP</label>
                                <input type="text" class="form-control" name="nomor" id="nomor">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role Akses</label>
                                <select class="form-select" name="role" id="role" required>
                                    <option value="superadmin">SuperAdmin</option>
                                    <option value="pimpinan">Pimpinan</option>
                                    <option value="admin">Admin</option>
                                    <option value="musyrif">Musyrif</option>
                                    <option value="santri">Santri</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 d-none" id="divUserKelas">
                            <label class="form-label">Kelas Santri</label>
                            <select name="kelas_id" class="form-select" id="user_kelas_id">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($kelas as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">
                                Password
                                <small class="text-muted fw-normal text-lowercase">(kosongkan jika tidak ganti)</small>
                            </label>
                            <input type="password" class="form-control" name="password" id="password">
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 p-4">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalApprove" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <form id="formApprove" class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
                @csrf
                <input type="hidden" name="user_id" id="approve_user_id">
                <i class="bi bi-shield-check text-success display-4 mb-3"></i>
                <h5 class="fw-bold mb-1">Verifikasi Akun</h5>
                <p class="text-muted small mb-4" id="approveText"></p>

                <div id="divPilihKelas" class="text-start mb-3 d-none">
                    <label class="form-label">Penempatan Kelas</label>
                    <select name="kelas_id" class="form-select border-primary" id="approve_kelas_id">
                        <option value="" hidden selected>Pilih Kelas...</option>
                        @foreach ($kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success rounded-pill fw-bold py-2">Setujui Sekarang</button>
                    <button type="button" class="btn btn-light rounded-pill" data-coreui-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalLifecycle" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formLifecycle" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                @csrf
                <input type="hidden" id="lifecycle_user_id">
                <input type="hidden" id="lifecycle_action">
                <input type="hidden" id="lifecycle_mode" value="single">

                <div class="modal-header lifecycle-modal-hero border-0 px-4 py-4">
                    <div class="d-flex align-items-center gap-3">
                        <span
                            class="bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 50px; height: 50px;">
                            <i class="bi bi-person-gear fs-4" id="lifecycleHeaderIcon"></i>
                        </span>
                        <div>
                            <div class="small text-white-50 fw-semibold">User Lifecycle</div>
                            <h5 class="modal-title fw-bold mb-0" id="lifecycleTitle">Ubah Status Akun</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 rounded-4 small mb-3" id="lifecycleDescription"></div>

                    <label class="form-label" for="lifecycle_reason">Alasan Tindakan</label>
                    <textarea class="form-control" id="lifecycle_reason" name="reason" rows="4" minlength="5" maxlength="1000"
                        required placeholder="Tuliskan alasan yang jelas agar dapat dipahami pada audit log..."></textarea>
                    <div class="form-text">Minimal 5 karakter. Alasan akan tersimpan pada riwayat lifecycle.</div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-coreui-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white rounded-pill px-4" id="btnSubmitLifecycle"
                        style="background: var(--islamic-purple-600);">
                        Proses Tindakan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalAudit" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header lifecycle-modal-hero border-0 px-4 py-4">
                    <div class="d-flex align-items-center gap-3">
                        <span
                            class="bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 50px; height: 50px;">
                            <i class="bi bi-clock-history fs-4"></i>
                        </span>
                        <div>
                            <div class="small text-white-50 fw-semibold">Audit Trail</div>
                            <h5 class="modal-title fw-bold mb-0" id="auditModalTitle">Riwayat Akun</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div id="auditLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted small mt-2">Memuat riwayat...</div>
                    </div>
                    <div class="audit-timeline d-none" id="auditTimeline"></div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-coreui-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modalUser = new coreui.Modal(document.getElementById('modalUser'));
            const modalApprove = new coreui.Modal(document.getElementById('modalApprove'));
            const modalLifecycle = new coreui.Modal(document.getElementById('modalLifecycle'));
            const modalAudit = new coreui.Modal(document.getElementById('modalAudit'));

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const structuralRoles = ['musyrif', 'santri'];
            let selectedStatus = '';
            let selectedIds = [];

            const lifecycleConfig = {
                suspend: {
                    title: 'Tangguhkan Akun',
                    icon: 'pause-circle-fill',
                    description: 'Pengguna akan langsung kehilangan akses sampai akun diaktifkan kembali.',
                    method: 'PATCH',
                    route: id => `{{ url('superadmin/users') }}/${id}/suspend`
                },
                reactivate: {
                    title: 'Aktifkan Kembali Akun',
                    icon: 'arrow-counterclockwise',
                    description: 'Akun suspended atau rejected akan dipulihkan menjadi aktif.',
                    method: 'PATCH',
                    route: id => `{{ url('superadmin/users') }}/${id}/reactivate`
                },
                reject: {
                    title: 'Tolak Permohonan Akun',
                    icon: 'x-circle-fill',
                    description: 'Permohonan akun pending akan ditandai rejected dan tidak dapat login.',
                    method: 'PATCH',
                    route: id => `{{ url('superadmin/users') }}/${id}/reject`
                },
                archive: {
                    title: 'Arsipkan Akun',
                    icon: 'archive-fill',
                    description: 'Akun dinonaktifkan melalui soft delete. Data dan audit trail tetap tersimpan.',
                    method: 'DELETE',
                    route: id => `{{ url('superadmin/users') }}/${id}`
                },
                restore: {
                    title: 'Pulihkan Akun',
                    icon: 'arrow-up-circle-fill',
                    description: 'Akun archived akan dikembalikan. Status akhir mengikuti riwayat approval akun.',
                    method: 'PATCH',
                    route: id => `{{ url('superadmin/users') }}/${id}/restore`
                },
                bulk_archive: {
                    title: 'Arsipkan Akun Terpilih',
                    icon: 'archive-fill',
                    description: 'Semua akun terpilih akan diarsipkan. Akun sendiri dan Super Admin aktif terakhir otomatis dilewati.',
                    method: 'PATCH',
                    route: () => `{{ route('superadmin.users.bulk_archive') }}`
                }
            };

            const dtConfig = role => ({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('superadmin.users.datatable') }}",
                    data: d => {
                        d.role = role;
                        d.account_status = selectedStatus;
                    }
                },
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4',
                        width: '40px'
                    },
                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'nomor',
                        name: 'nomor',
                        defaultContent: '-'
                    },
                    {
                        data: 'status_badge',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
                ],
                order: [
                    [2, 'asc']
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"></div>',
                    search: '_INPUT_',
                    searchPlaceholder: 'Cari user...',
                    lengthMenu: 'Tampil _MENU_ data',
                    emptyTable: 'Tidak ada akun pada status ini.',
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });

            const tables = {};
            ['superadmin', 'pimpinan', 'admin', 'musyrif', 'santri'].forEach(role => {
                tables[role] = $(`#table-${role}`).DataTable(dtConfig(role));
            });

            const reloadAllTables = () => {
                Object.values(tables).forEach(table => table.ajax.reload(null, false));
            };

            const clearSelections = () => {
                $('.user-checkbox, .check-all').prop('checked', false);
                selectedIds = [];
                $('#bulkActionBar').addClass('d-none').removeClass('animate__slideInUp');
            };

            const updateBulkBar = () => {
                selectedIds = $('.user-checkbox:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length > 0) {
                    $('#selectedCount').text(selectedIds.length);
                    $('#bulkActionBar').removeClass('d-none').addClass('animate__slideInUp');
                } else {
                    clearSelections();
                }
            };

            $('#accountStatusFilter').on('click', '.account-status-filter__button', function() {
                $('#accountStatusFilter .account-status-filter__button').removeClass('active');
                $(this).addClass('active');
                selectedStatus = String($(this).data('status') || '');
                clearSelections();
                reloadAllTables();
            });

            document.querySelectorAll('button[data-coreui-toggle="tab"]').forEach(button => {
                button.addEventListener('shown.coreui.tab', () => {
                    $.fn.dataTable.tables({
                        visible: true,
                        api: true
                    }).columns.adjust();
                    clearSelections();
                });
            });

            const updateUserClassVisibility = () => {
                const isCreate = !$('#user_id').val();
                const selectedRole = $('#role').prop('disabled') ?
                    $('#role_locked').val() :
                    $('#role').val();
                const showClass = isCreate && selectedRole === 'santri';

                $('#divUserKelas').toggleClass('d-none', !showClass);
                $('#user_kelas_id').prop('required', showClass);
                if (!showClass) $('#user_kelas_id').val('');
            };

            const unlockRoleField = () => {
                $('#role').prop('disabled', false).attr('name', 'role');
                $('#role_locked').prop('disabled', true).val('');
                $('#roleLockNotice').addClass('d-none');
            };

            const lockRoleField = role => {
                $('#role').val(role).prop('disabled', true).removeAttr('name');
                $('#role_locked').prop('disabled', false).val(role);
                $('#roleLockNotice').removeClass('d-none');
            };

            $('#btnAddUser').on('click', function() {
                $('#formUser')[0].reset();
                $('#user_id').val('');
                unlockRoleField();
                $('#role').val('admin');
                $('#password').prop('required', true);
                $('#modalUserTitle').html('<i class="bi bi-person-plus-fill"></i> Tambah User Baru');
                updateUserClassVisibility();
                modalUser.show();
            });

            $('#role').on('change', updateUserClassVisibility);

            $(document).on('click', '.btn-edit', function() {
                const data = $(this).data();
                $('#formUser')[0].reset();
                $('#user_id').val(data.id);
                $('#name').val(data.name);
                $('#email').val(data.email);
                $('#nomor').val(data.nomor || '');
                $('#password').prop('required', false);

                if (structuralRoles.includes(data.role)) {
                    lockRoleField(data.role);
                } else {
                    unlockRoleField();
                    $('#role').val(data.role);
                }

                updateUserClassVisibility();
                $('#modalUserTitle').html('<i class="bi bi-pencil-square"></i> Edit User');
                modalUser.show();
            });

            $('#formUser').on('submit', function(event) {
                event.preventDefault();
                const id = $('#user_id').val();
                const url = id ? `{{ url('superadmin/users') }}/${id}` :
                    "{{ route('superadmin.users.store') }}";
                const method = id ? 'PUT' : 'POST';
                const button = $(this).find('button[type="submit"]');
                const original = button.html();

                button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

                $.ajax({
                    url,
                    type: method,
                    data: $(this).serialize(),
                    success: response => {
                        modalUser.hide();
                        reloadAllTables();
                        AppAlert.success(response.message);
                    },
                    error: xhr => AppAlert.error(extractError(xhr, 'Gagal menyimpan data.')),
                    complete: () => button.prop('disabled', false).html(original)
                });
            });

            $(document).on('click', '.btn-approve', function() {
                const data = $(this).data();
                $('#formApprove')[0].reset();
                $('#approve_user_id').val(data.id);
                $('#approveText').html(
                    `Setujui akun <strong>${escapeHtml(data.name)}</strong> sebagai ${String(data.role).toUpperCase()}?`
                    );

                const needsClass = data.role === 'santri';
                $('#divPilihKelas').toggleClass('d-none', !needsClass);
                $('#approve_kelas_id').prop('required', needsClass);
                modalApprove.show();
            });

            $('#formApprove').on('submit', function(event) {
                event.preventDefault();
                const button = $(this).find('button[type="submit"]');
                const original = button.html();
                button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Memproses...');

                $.post("{{ route('superadmin.users.approve') }}", $(this).serialize())
                    .done(response => {
                        modalApprove.hide();
                        reloadAllTables();
                        AppAlert.success(response.message);
                    })
                    .fail(xhr => AppAlert.error(extractError(xhr, 'Gagal memverifikasi user.')))
                    .always(() => button.prop('disabled', false).html(original));
            });

            const openLifecycleModal = (action, userId = '', userName = '', mode = 'single') => {
                const config = lifecycleConfig[action];
                if (!config) return;

                $('#formLifecycle')[0].reset();
                $('#lifecycle_action').val(action);
                $('#lifecycle_user_id').val(userId);
                $('#lifecycle_mode').val(mode);
                $('#lifecycleTitle').text(config.title);
                $('#lifecycleHeaderIcon').attr('class', `bi bi-${config.icon} fs-4`);
                $('#lifecycleDescription').html(
                    `${config.description}${userName ? `<br><strong>Target:</strong> ${escapeHtml(userName)}` : ''}`
                );
                modalLifecycle.show();
            };

            $(document).on('click', '.btn-lifecycle', function() {
                const data = $(this).data();
                openLifecycleModal(data.action, data.id, data.name, 'single');
            });

            $('#btnBulkArchive').on('click', function() {
                if (selectedIds.length === 0) return;
                openLifecycleModal('bulk_archive', '', `${selectedIds.length} akun`, 'bulk');
            });

            $('#formLifecycle').on('submit', function(event) {
                event.preventDefault();
                const action = $('#lifecycle_action').val();
                const mode = $('#lifecycle_mode').val();
                const id = $('#lifecycle_user_id').val();
                const config = lifecycleConfig[action];
                const button = $('#btnSubmitLifecycle');
                const original = button.html();
                const payload = {
                    reason: $('#lifecycle_reason').val()
                };

                if (mode === 'bulk') payload.ids = selectedIds;

                button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Memproses...');

                $.ajax({
                    url: config.route(id),
                    type: config.method,
                    data: payload,
                    success: response => {
                        modalLifecycle.hide();
                        reloadAllTables();
                        clearSelections();

                        let message = response.message;
                        if (Array.isArray(response.skipped) && response.skipped.length) {
                            message += '<br><br><strong>Dilewati:</strong><br>' + response
                                .skipped.map(escapeHtml).join('<br>');
                        }
                        AppAlert.success(message);
                    },
                    error: xhr => AppAlert.error(extractError(xhr, 'Tindakan lifecycle gagal.')),
                    complete: () => button.prop('disabled', false).html(original)
                });
            });

            $('#btnBulkApprove').on('click', function() {
                if (selectedIds.length === 0) return;

                AppAlert.warning(`Setujui ${selectedIds.length} akun terpilih?`, 'Approve Massal')
                    .then(result => {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ route('superadmin.users.bulk_approve') }}",
                            type: 'POST',
                            data: {
                                ids: selectedIds
                            },
                            success: response => {
                                let message = response.message;
                                if (Array.isArray(response.skipped) && response.skipped
                                    .length) {
                                    message += '<br><br><strong>Dilewati:</strong><br>' +
                                        response.skipped.map(escapeHtml).join('<br>');
                                }
                                AppAlert.success(message);
                                reloadAllTables();
                                clearSelections();
                            },
                            error: xhr => AppAlert.error(extractError(xhr,
                                'Bulk approval gagal.'))
                        });
                    });
            });

            $(document).on('click', '.btn-audit', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                $('#auditModalTitle').text(`Riwayat Akun — ${name}`);
                $('#auditLoading').removeClass('d-none');
                $('#auditTimeline').addClass('d-none').html('');
                modalAudit.show();

                $.get(`{{ url('superadmin/users') }}/${id}/lifecycle-logs`)
                    .done(response => renderAuditLogs(response.logs || []))
                    .fail(xhr => {
                        $('#auditLoading').addClass('d-none');
                        $('#auditTimeline').removeClass('d-none').html(
                            `<div class="alert alert-danger">${escapeHtml(extractError(xhr, 'Gagal memuat audit log.'))}</div>`
                        );
                    });
            });

            const renderAuditLogs = logs => {
                $('#auditLoading').addClass('d-none');
                const container = $('#auditTimeline').removeClass('d-none').html('');

                if (!logs.length) {
                    container.html(`
                        <div class="audit-empty">
                            <i class="bi bi-clock-history display-5 d-block mb-2"></i>
                            Belum ada riwayat lifecycle untuk akun ini.
                        </div>
                    `);
                    return;
                }

                const actionLabels = {
                    created: 'Akun dibuat',
                    updated: 'Data akun diperbarui',
                    approved: 'Akun disetujui',
                    suspended: 'Akun ditangguhkan',
                    reactivated: 'Akun diaktifkan kembali',
                    rejected: 'Permohonan ditolak',
                    archived: 'Akun diarsipkan',
                    restored: 'Akun dipulihkan'
                };

                logs.forEach(log => {
                    const statusChange = log.from_status || log.to_status ?
                        `<div class="small mt-1"><span class="badge bg-light text-dark border">${escapeHtml(log.from_status || '-')}</span> <i class="bi bi-arrow-right mx-1"></i> <span class="badge bg-primary">${escapeHtml(log.to_status || '-')}</span></div>` :
                        '';

                    container.append(`
                        <div class="audit-item">
                            <span class="audit-item__icon"><i class="bi bi-shield-check"></i></span>
                            <div class="min-w-0">
                                <div class="d-flex flex-column flex-md-row justify-content-between gap-1">
                                    <div class="fw-bold">${escapeHtml(actionLabels[log.action] || log.action)}</div>
                                    <div class="text-muted small">${escapeHtml(log.created_at || '-')}</div>
                                </div>
                                ${statusChange}
                                <div class="text-muted small mt-2">${escapeHtml(log.reason || 'Tanpa keterangan.')}</div>
                                <div class="small mt-2">
                                    <i class="bi bi-person-fill me-1"></i>${escapeHtml(log.actor || 'Sistem')}
                                    ${log.ip_address ? ` <span class="text-muted ms-2"><i class="bi bi-globe2 me-1"></i>${escapeHtml(log.ip_address)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `);
                });
            };

            $(document).on('change', '.check-all', function() {
                const role = $(this).data('role');
                $(`#table-${role} .user-checkbox:not(:disabled)`).prop('checked', $(this).is(':checked'));
                updateBulkBar();
            });

            $(document).on('change', '.user-checkbox', updateBulkBar);
            $('#btnCancelBulk').on('click', clearSelections);

            function extractError(xhr, fallback) {
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    return Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                return xhr.responseJSON?.message || fallback;
            }

            function escapeHtml(value) {
                return $('<div>').text(value ?? '').html();
            }
        });
    </script>
@endpush
