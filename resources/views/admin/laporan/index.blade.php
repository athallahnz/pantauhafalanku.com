@extends('layouts.app')

@section('title', 'Laporan Hafalan & Kinerja Departemen Al Qur\'an')

@section('content')
    <style>
        :root,
        [data-coreui-theme="light"] {
            --report-purple: var(--islamic-purple-600, #6b4eff);
            --report-purple-soft: rgba(107, 78, 255, .10);
            --report-tosca: var(--islamic-tosca-600, #13a3b3);
            --report-tosca-soft: rgba(19, 163, 179, .10);
            --report-success: #198754;
            --report-warning: #d99a00;
            --report-danger: #dc3545;
            --report-primary: #0d6efd;

            --report-radius-sm: 12px;
            --report-radius-md: 18px;
            --report-radius-lg: 24px;

            --report-page-surface: #f7f8fb;
            --report-surface: #ffffff;
            --report-surface-elevated: #ffffff;
            --report-surface-muted: #f5f6fa;
            --report-control-surface: #ffffff;
            --report-table-head: #f4f5f8;
            --report-table-stripe: rgba(107, 78, 255, .026);
            --report-table-hover: rgba(107, 78, 255, .065);
            --report-border: rgba(31, 41, 55, .10);
            --report-border-strong: rgba(31, 41, 55, .16);
            --report-text: #20242c;
            --report-text-muted: #6f7580;
            --report-text-soft: #8c929d;
            --report-placeholder: #9ca3af;
            --report-overlay: rgba(255, 255, 255, .88);
            --report-info-bg: rgba(13, 202, 240, .10);
            --report-info-border: rgba(13, 202, 240, .20);
            --report-info-text: #245b69;
            --report-chart-text: #69707d;
            --report-chart-grid: rgba(31, 41, 55, .08);

            --report-shadow-sm: 0 4px 14px rgba(27, 31, 59, .055);
            --report-shadow-md: 0 14px 34px rgba(27, 31, 59, .09);
            --report-shadow-hover: 0 18px 38px rgba(27, 31, 59, .12);
        }

        [data-coreui-theme="dark"] {
            color-scheme: dark;
            --report-purple: #9a86ff;
            --report-purple-soft: rgba(154, 134, 255, .16);
            --report-tosca: #58c8d3;
            --report-tosca-soft: rgba(88, 200, 211, .14);
            --report-success: #52c788;
            --report-warning: #f3c34f;
            --report-danger: #f06a78;
            --report-primary: #70a7ff;

            --report-page-surface: #171a21;
            --report-surface: #20242d;
            --report-surface-elevated: #252a34;
            --report-surface-muted: #1c2028;
            --report-control-surface: #191d25;
            --report-table-head: #292e38;
            --report-table-stripe: rgba(255, 255, 255, .025);
            --report-table-hover: rgba(154, 134, 255, .09);
            --report-border: rgba(255, 255, 255, .09);
            --report-border-strong: rgba(255, 255, 255, .15);
            --report-text: #f1f3f5;
            --report-text-muted: #aeb4bf;
            --report-text-soft: #8e96a3;
            --report-placeholder: #7f8794;
            --report-overlay: rgba(23, 26, 33, .88);
            --report-info-bg: rgba(51, 179, 205, .12);
            --report-info-border: rgba(88, 200, 211, .22);
            --report-info-text: #bfeaf0;
            --report-chart-text: #aeb4bf;
            --report-chart-grid: rgba(255, 255, 255, .075);

            --report-shadow-sm: 0 8px 22px rgba(0, 0, 0, .24);
            --report-shadow-md: 0 18px 42px rgba(0, 0, 0, .30);
            --report-shadow-hover: 0 22px 48px rgba(0, 0, 0, .38);
        }

        .report-page {
            position: relative;
            isolation: isolate;
            padding-bottom: 1.75rem;
        }

        .report-page::before {
            content: '';
            position: absolute;
            inset: -1.5rem -1.5rem auto;
            height: 340px;
            z-index: -1;
            pointer-events: none;
            background:
                radial-gradient(circle at 10% 5%, rgba(107, 78, 255, .09), transparent 38%),
                radial-gradient(circle at 84% 6%, rgba(19, 163, 179, .08), transparent 34%);
            mask-image: linear-gradient(to bottom, #000 0%, transparent 100%);
        }

        .report-page>* {
            position: relative;
        }

        .min-w-0 {
            min-width: 0;
        }

        .report-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            margin-bottom: 1.25rem;
            padding: 1.35rem 1.5rem;
            border: 1px solid var(--report-border);
            border-radius: var(--report-radius-lg);
            background:
                linear-gradient(135deg, rgba(107, 78, 255, .075), rgba(19, 163, 179, .035)),
                var(--report-surface);
            box-shadow: var(--report-shadow-sm);
            overflow: hidden;
        }

        .report-hero::after {
            content: '';
            position: absolute;
            top: -76px;
            right: -58px;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(107, 78, 255, .15), rgba(19, 163, 179, .08));
            filter: blur(2px);
            pointer-events: none;
        }

        .report-hero__content,
        .report-hero__meta {
            position: relative;
            z-index: 1;
        }

        .report-eyebrow,
        .section-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-bottom: .5rem;
            font-size: .69rem;
            line-height: 1;
            letter-spacing: .1em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--report-purple);
        }

        .report-hero__title {
            margin: 0;
            font-size: clamp(1.45rem, 2vw, 2rem);
            font-weight: 800;
            letter-spacing: -.025em;
            color: var(--report-text);
        }

        .report-hero__subtitle {
            max-width: 760px;
            margin: .45rem 0 0;
            color: var(--report-text-muted);
            font-size: .9rem;
            line-height: 1.65;
        }

        .report-updated {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .68rem .9rem;
            border: 1px solid var(--report-border);
            border-radius: 999px;
            background: color-mix(in srgb, var(--report-surface) 90%, transparent);
            color: var(--report-text-muted);
            font-size: .78rem;
            white-space: nowrap;
            box-shadow: 0 3px 10px rgba(27, 31, 59, .04);
        }

        .dashboard-section {
            margin-bottom: 1.6rem;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .section-heading__title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: -.012em;
        }

        .section-heading__description {
            margin-top: .22rem;
            color: var(--report-text-muted);
            font-size: .79rem;
            line-height: 1.5;
        }

        .semester-banner {
            position: relative;
            overflow: hidden;
            margin-bottom: 1.25rem;
            border-radius: var(--report-radius-lg);
            color: #fff;
            background:
                radial-gradient(circle at 88% 0%, rgba(255, 255, 255, .28), transparent 31%),
                linear-gradient(118deg, var(--report-purple) 0%, var(--report-tosca) 100%);
            box-shadow: 0 16px 34px rgba(82, 63, 196, .22);
        }

        .semester-banner::before {
            content: '';
            position: absolute;
            inset: auto -48px -84px auto;
            width: 220px;
            height: 220px;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 50%;
            box-shadow:
                0 0 0 30px rgba(255, 255, 255, .045),
                0 0 0 65px rgba(255, 255, 255, .03);
        }

        .semester-banner__body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.35rem 1.5rem;
        }

        .semester-banner__identity {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .semester-icon {
            width: 58px;
            height: 58px;
            min-width: 58px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 17px;
            background: rgba(255, 255, 255, .14);
            font-size: 1.55rem;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .18);
        }

        .semester-kicker {
            margin-bottom: .28rem;
            color: rgba(255, 255, 255, .76);
            font-size: .68rem;
            letter-spacing: .09em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .semester-title {
            margin: 0;
            font-size: clamp(1.18rem, 2vw, 1.55rem);
            font-weight: 800;
            letter-spacing: -.015em;
            text-transform: capitalize !important;
        }

        .semester-range {
            margin-top: .28rem;
            color: rgba(255, 255, 255, .78);
            font-size: .79rem;
        }

        .semester-status {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .68rem .92rem;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            background: rgba(255, 255, 255, .16);
            color: #fff;
            font-size: .77rem;
            font-weight: 700;
            backdrop-filter: blur(8px);
            white-space: nowrap;
        }

        .semester-status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #8ff0b3;
            box-shadow: 0 0 0 4px rgba(143, 240, 179, .16);
        }

        .report-card,
        .kpi-card,
        .insight-card {
            border: 1px solid var(--report-border) !important;
            border-radius: var(--report-radius-md);
            background: var(--report-surface);
            box-shadow: var(--report-shadow-sm);
        }

        .report-card {
            overflow: hidden;
        }

        .kpi-card,
        .insight-card {
            position: relative;
            overflow: hidden;
            transition:
                transform .24s ease,
                box-shadow .24s ease,
                border-color .24s ease;
        }

        .kpi-card::before,
        .insight-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: linear-gradient(90deg, var(--report-purple), var(--report-tosca));
            opacity: .82;
        }

        .kpi-card:hover,
        .insight-card:hover {
            transform: translateY(-3px);
            border-color: color-mix(in srgb, var(--report-purple) 22%, var(--report-border)) !important;
            box-shadow: var(--report-shadow-hover);
        }

        .kpi-card .card-body,
        .insight-card .card-body {
            padding: 1.2rem 1.25rem !important;
        }

        .report-card>.card-header {
            padding: 1.15rem 1.3rem .9rem !important;
        }

        .report-card>.card-body {
            padding: 1.25rem 1.3rem !important;
        }

        .report-card .card-header .fw-bold {
            font-size: .95rem;
            letter-spacing: -.008em;
        }

        .kpi-label,
        .insight-label {
            margin-bottom: .38rem;
            color: var(--report-text-muted);
            font-size: .68rem;
            line-height: 1.25;
            letter-spacing: .075em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .kpi-value {
            font-size: clamp(1.55rem, 2vw, 2rem);
            line-height: 1.08;
            letter-spacing: -.035em;
            font-weight: 850;
        }

        .kpi-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 1.25rem;
            transition: transform .22s ease;
        }

        .kpi-card:hover .kpi-icon {
            transform: rotate(-4deg) scale(1.06);
        }

        .metric-note {
            margin-top: .38rem;
            color: var(--report-text-muted);
            font-size: .69rem;
            line-height: 1.45;
        }

        .filter-card {
            border-radius: var(--report-radius-lg);
        }

        .filter-card>.card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.12rem 1.35rem .55rem !important;
        }

        .filter-card>.card-body {
            padding: .9rem 1.35rem 1.35rem !important;
        }

        .filter-card .form-label {
            margin-bottom: .42rem;
            color: var(--report-text-muted);
            font-size: .69rem;
            letter-spacing: .06em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            min-height: 43px;
            border-color: var(--report-border);
            border-radius: var(--report-radius-sm);
            background-color: var(--report-control-surface);
            font-size: .84rem;
            box-shadow: none;
            transition:
                border-color .2s ease,
                box-shadow .2s ease,
                background-color .2s ease;
        }

        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: color-mix(in srgb, var(--report-purple) 58%, var(--report-border));
            box-shadow: 0 0 0 .22rem rgba(107, 78, 255, .11);
        }

        .filter-card .form-text {
            margin-top: .4rem;
            font-size: .68rem;
        }

        .btn-report-primary,
        .btn-report-reset {
            min-height: 43px;
            border-radius: var(--report-radius-sm);
            font-size: .82rem;
            font-weight: 750;
        }

        .btn-report-primary {
            border: 0;
            color: #fff;
            background: linear-gradient(135deg, var(--report-purple), #805ef6);
            box-shadow: 0 7px 15px rgba(107, 78, 255, .2);
        }

        .btn-report-primary:hover {
            color: #fff;
            filter: brightness(1.04);
            transform: translateY(-1px);
        }

        .btn-report-reset {
            border-color: rgba(220, 53, 69, .28);
            color: var(--report-danger);
            background: rgba(220, 53, 69, .045);
        }

        .btn-report-reset:hover {
            color: #fff;
            background: var(--report-danger);
        }

        .attendance-card {
            position: relative;
            overflow: hidden;
        }

        .attendance-card::after {
            content: '';
            position: absolute;
            right: -36px;
            bottom: -55px;
            width: 132px;
            height: 132px;
            border-radius: 50%;
            background: currentColor;
            opacity: .05;
            pointer-events: none;
        }

        .progress-thin {
            height: 6px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--report-border);
        }

        .progress-thin>span {
            display: block;
            width: 0;
            height: 100%;
            border-radius: inherit;
            transition: width .65s ease;
        }

        .insight-card h3 {
            letter-spacing: -.025em;
        }

        .insight-ranking {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(107, 78, 255, .045), rgba(19, 163, 179, .035)),
                var(--report-surface);
        }

        .insight-ranking__item {
            display: flex;
            align-items: center;
            gap: .9rem;
            min-height: 92px;
            padding: .15rem .25rem;
        }

        .insight-ranking__icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: var(--report-purple-soft);
            color: var(--report-purple);
            font-size: 1.15rem;
        }

        .insight-ranking__item.is-tosca .insight-ranking__icon {
            background: var(--report-tosca-soft);
            color: var(--report-tosca);
        }

        .chart-box {
            position: relative;
            min-height: 310px;
        }

        .chart-box-sm {
            position: relative;
            min-height: 270px;
        }

        .chart-card-subtitle {
            margin-top: .25rem;
            color: var(--report-text-muted);
            font-size: .72rem;
            line-height: 1.45;
        }

        .nav-pills {
            gap: .32rem;
            padding: .28rem;
            border: 1px solid var(--report-border);
            border-radius: 999px;
            background: var(--report-surface-muted);
        }

        .nav-pills .nav-link {
            min-height: 34px;
            padding: .44rem .78rem;
            border-radius: 999px;
            color: var(--report-text);
            font-size: .76rem;
            line-height: 1;
            font-weight: 700;
            transition: all .2s ease;
        }

        .nav-pills .nav-link:hover:not(.active) {
            background: var(--report-purple-soft);
            color: var(--report-purple);
        }

        .nav-pills .nav-link.active {
            color: #fff;
            background: linear-gradient(135deg, var(--report-purple), #805ef6);
            box-shadow: 0 5px 12px rgba(107, 78, 255, .2);
        }

        .table-responsive {
            border: 1px solid var(--report-border);
            border-radius: 14px;
            background: var(--report-surface);
        }

        .table {
            margin-bottom: 0;
            color: var(--report-text);
        }

        .table> :not(caption)>*>* {
            padding: .82rem .9rem;
            border-color: color-mix(in srgb, var(--report-border) 88%, transparent);
        }

        .table thead th {
            border-bottom-width: 1px;
            background: var(--report-table-head);
            color: var(--report-text-muted);
            font-size: .66rem;
            line-height: 1.25;
            letter-spacing: .065em;
            text-transform: uppercase;
            font-weight: 850;
            white-space: nowrap;
        }

        .table tbody td {
            font-size: .79rem;
        }

        .table-striped>tbody>tr:nth-of-type(odd)>* {
            --cui-table-accent-bg: var(--report-table-stripe);
        }

        .table-hover>tbody>tr:hover>* {
            --cui-table-accent-bg: var(--report-table-hover);
        }

        .dataTables_wrapper .row {
            row-gap: .75rem;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            font-size: .75rem;
            color: var(--report-text-muted);
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            min-height: 36px;
            margin-left: .45rem;
            border: 1px solid var(--report-border);
            border-radius: 10px;
            background: var(--report-control-surface);
            color: var(--report-text);
            box-shadow: none;
        }

        .dataTables_wrapper .pagination {
            gap: .2rem;
        }

        .dataTables_wrapper .page-link {
            min-width: 34px;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            border-radius: 9px !important;
            color: var(--report-text);
            background: transparent;
            font-size: .75rem;
        }

        .dataTables_wrapper .page-item.active .page-link {
            color: #fff;
            background: var(--report-purple);
            box-shadow: 0 5px 12px rgba(107, 78, 255, .18);
        }

        .table-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .9rem;
        }

        .export-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .export-actions .btn {
            min-height: 34px;
            padding-inline: .85rem !important;
            border-radius: 10px !important;
            font-size: .72rem;
            box-shadow: none !important;
        }

        .modal-content {
            border: 1px solid var(--report-border) !important;
            border-radius: 20px !important;
            overflow: hidden;
            background: var(--report-surface);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .22) !important;
        }

        .modal-header {
            padding: 1.15rem 1.3rem;
        }

        .modal-body {
            padding: 1.25rem 1.3rem;
        }

        .modal-header-accent {
            color: #fff !important;
            background: linear-gradient(90deg, #6448df, #168f9e);
            border-bottom: 0 !important;
        }

        .modal-header-accent .modal-title,
        .modal-header-accent small {
            color: #fff !important;
        }

        /* ================= THEME-AWARE COMPONENT NORMALIZATION ================= */
        .report-page,
        .report-page .card,
        .report-page .card-header,
        .report-page .card-body,
        .report-page .tab-content,
        .modal-content {
            color: var(--report-text);
        }

        .report-page .text-muted,
        .report-page .form-text,
        .report-page small.text-muted,
        .modal-content .text-muted {
            color: var(--report-text-muted) !important;
        }

        .report-page .card,
        .report-page .card-header,
        .report-page .card-footer,
        .modal-content,
        .modal-header,
        .modal-footer {
            border-color: var(--report-border) !important;
        }

        .report-page .bg-transparent,
        .modal-content .bg-transparent {
            background-color: transparent !important;
        }

        .report-page .bg-body-tertiary,
        .modal-content .bg-body-tertiary {
            background-color: var(--report-surface-muted) !important;
            color: var(--report-text) !important;
        }

        .report-page .form-control,
        .report-page .form-select,
        .modal-content .form-control,
        .modal-content .form-select {
            color-scheme: light;
            color: var(--report-text);
            background-color: var(--report-control-surface);
            border-color: var(--report-border-strong);
        }

        [data-coreui-theme="dark"] .report-page .form-control,
        [data-coreui-theme="dark"] .report-page .form-select,
        [data-coreui-theme="dark"] .modal-content .form-control,
        [data-coreui-theme="dark"] .modal-content .form-select {
            color-scheme: dark;
        }

        .report-page .form-control::placeholder,
        .modal-content .form-control::placeholder {
            color: var(--report-placeholder);
            opacity: 1;
        }

        .report-page .form-select option,
        .modal-content .form-select option {
            color: var(--report-text);
            background: var(--report-control-surface);
        }

        .report-page .form-control:disabled,
        .report-page .form-select:disabled,
        .modal-content .form-control:disabled,
        .modal-content .form-select:disabled {
            color: var(--report-text-soft);
            background-color: var(--report-surface-muted);
            opacity: .78;
        }

        .report-page .table,
        .modal-content .table {
            --cui-table-color: var(--report-text);
            --cui-table-bg: transparent;
            --cui-table-border-color: var(--report-border);
            --cui-table-striped-color: var(--report-text);
            --cui-table-striped-bg: var(--report-table-stripe);
            --cui-table-hover-color: var(--report-text);
            --cui-table-hover-bg: var(--report-table-hover);
            color: var(--report-text);
        }

        .report-page .table-light,
        .modal-content .table-light,
        .report-page .table-light>tr>th,
        .modal-content .table-light>tr>th {
            --cui-table-color: var(--report-text);
            --cui-table-bg: var(--report-table-head);
            --cui-table-border-color: var(--report-border);
            color: var(--report-text-muted) !important;
            background-color: var(--report-table-head) !important;
        }

        .report-page .table-responsive,
        .modal-content .table-responsive {
            background-color: var(--report-surface);
            border-color: var(--report-border) !important;
        }

        .report-page .alert-info {
            color: var(--report-info-text);
            background-color: var(--report-info-bg);
            border: 1px solid var(--report-info-border) !important;
        }

        .report-page .dataTables_wrapper .dataTables_processing {
            color: var(--report-text);
            background: var(--report-overlay);
            border: 1px solid var(--report-border);
            border-radius: 12px;
            box-shadow: var(--report-shadow-sm);
        }

        .report-page .dataTables_wrapper .dataTables_filter input::placeholder {
            color: var(--report-placeholder);
        }

        .report-page .dataTables_wrapper .page-item.disabled .page-link {
            color: var(--report-text-soft);
            background: transparent;
            opacity: .58;
        }

        .report-page .dataTables_wrapper .page-link:hover {
            color: var(--report-purple);
            background: var(--report-purple-soft);
        }

        .report-page .dropdown-menu,
        .modal-content .dropdown-menu {
            color: var(--report-text);
            background-color: var(--report-surface-elevated);
            border-color: var(--report-border);
            box-shadow: var(--report-shadow-md);
        }

        .report-page .dropdown-item,
        .modal-content .dropdown-item {
            color: var(--report-text);
        }

        .report-page .dropdown-item:hover,
        .report-page .dropdown-item:focus,
        .modal-content .dropdown-item:hover,
        .modal-content .dropdown-item:focus {
            color: var(--report-purple);
            background-color: var(--report-purple-soft);
        }

        [data-coreui-theme="dark"] .report-page::before {
            opacity: .58;
        }

        [data-coreui-theme="dark"] .report-hero {
            background:
                linear-gradient(135deg, rgba(154, 134, 255, .12), rgba(88, 200, 211, .065)),
                var(--report-surface);
        }

        [data-coreui-theme="dark"] .report-hero::after {
            opacity: .62;
        }

        [data-coreui-theme="dark"] .report-updated {
            background: rgba(255, 255, 255, .035);
        }

        [data-coreui-theme="dark"] .btn-close:not(.btn-close-white) {
            filter: invert(1) grayscale(100%) brightness(190%);
        }

        [data-coreui-theme="dark"] .modal-backdrop.show {
            opacity: .72;
        }

        [data-coreui-theme="dark"] .semester-banner {
            box-shadow: 0 18px 40px rgba(0, 0, 0, .36);
        }

        @media (min-width: 768px) {
            .border-md-end {
                border-right: 1px solid var(--report-border);
            }
        }

        @media (max-width: 991.98px) {
            .report-hero {
                align-items: flex-start;
            }

            .report-hero__meta {
                align-self: flex-start;
            }

            .semester-banner__body {
                align-items: flex-start;
            }

            .chart-box,
            .chart-box-sm {
                min-height: 290px;
            }
        }

        @media (max-width: 767.98px) {
            .report-page::before {
                inset-inline: -.75rem;
            }

            .report-hero {
                flex-direction: column;
                padding: 1.15rem;
                border-radius: 18px;
            }

            .report-updated {
                width: 100%;
                justify-content: center;
            }

            .semester-banner {
                border-radius: 18px;
            }

            .semester-banner__body {
                flex-direction: column;
                padding: 1.15rem;
            }

            .semester-status {
                width: 100%;
                justify-content: center;
            }

            .filter-card>.card-header,
            .filter-card>.card-body,
            .report-card>.card-header,
            .report-card>.card-body {
                padding-inline: 1rem !important;
            }

            .kpi-card .card-body,
            .insight-card .card-body {
                padding: 1.05rem !important;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
                margin-bottom: .75rem;
            }

            .chart-box,
            .chart-box-sm {
                min-height: 260px;
            }

            .nav-pills {
                max-width: 100%;
                overflow-x: auto;
                flex-wrap: nowrap;
            }

            .table-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .export-actions {
                width: 100%;
            }

            .export-actions .btn {
                flex: 1;
            }
        }

        @media (max-width: 575.98px) {
            .semester-banner__identity {
                align-items: flex-start;
            }

            .semester-icon {
                width: 50px;
                height: 50px;
                min-width: 50px;
                border-radius: 14px;
            }

            .kpi-value {
                font-size: 1.72rem;
            }

            .kpi-icon {
                width: 42px;
                height: 42px;
                min-width: 42px;
            }

            .chart-box,
            .chart-box-sm {
                min-height: 235px;
            }
        }
    </style>
    <div class="report-page">
        {{-- HEADER --}}
        <header class="report-hero">
            <div class="report-hero__content">
                <div class="report-eyebrow">
                    <i class="bi bi-stars"></i>
                    Executive Academic Insight
                </div>
                <h1 class="report-hero__title">Dashboard Laporan & Kinerja</h1>
                <p class="report-hero__subtitle">
                    Ringkasan hafalan, kehadiran santri, produktivitas musyrif, dan kualitas operasional
                    Departemen Al-Qur'an dalam satu dashboard analitik.
                </p>
            </div>

            <div class="report-hero__meta">
                <div class="report-updated">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Diperbarui <strong id="lastUpdatedAt">-</strong></span>
                </div>
            </div>
        </header>

        {{-- SEMESTER CONTEXT --}}
        @php
            $semesterLabel = $semesterAktif
                ? mb_convert_case(
                    str_replace(
                        '_',
                        ' ',
                        trim(($semesterAktif->nama ?? '') . ' ' . ($semesterAktif->tahunAjaran?->nama ?? '')),
                    ),
                    MB_CASE_TITLE,
                    'UTF-8',
                )
                : 'Belum Ada Semester';
        @endphp
        <section class="semester-banner" aria-labelledby="semesterContextLabel">
            <div class="semester-banner__body">
                <div class="semester-banner__identity">
                    <div class="semester-icon" aria-hidden="true">
                        <i class="bi bi-calendar2-week-fill"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="semester-kicker">
                            Konteks Periode Laporan
                        </div>

                        <h2 class="semester-title text-truncate" id="semesterContextLabel">
                            @if ($semesterAktif)
                                {{ $semesterAktif->nama }}
                                {{ $semesterAktif->tahunAjaran?->nama ?? '' }}
                            @else
                                Belum Ada Semester
                            @endif
                        </h2>

                        <div class="semester-range" id="semesterContextRange">
                            @if ($semesterAktif?->tanggal_mulai && $semesterAktif?->tanggal_selesai)
                                {{ \Carbon\Carbon::parse($semesterAktif->tanggal_mulai)->translatedFormat('d M Y') }}
                                —
                                {{ \Carbon\Carbon::parse($semesterAktif->tanggal_selesai)->translatedFormat('d M Y') }}
                            @else
                                Rentang Semester Belum Tersedia
                            @endif
                        </div>
                    </div>
                </div>

                <span class="semester-status" id="semesterContextStatus">
                    {{ $semesterAktif?->is_active ? 'Semester Aktif' : 'Semester Terpilih' }}
                </span>
            </div>
        </section>

        <div class="alert alert-warning border-0 rounded-4 shadow-sm d-none" id="placementIntegrityAlert" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>

                <div>
                    <div class="fw-bold mb-1">
                        Pemeriksaan Integritas Data Semester
                    </div>

                    <div class="small" id="placementIntegrityMessage">
                        -
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="section-heading">
            <div>
                <div class="section-eyebrow">
                    <i class="bi bi-sliders"></i>
                    Kontrol Data
                </div>
                <h2 class="section-heading__title">Filter Analisis Laporan</h2>
                <div class="section-heading__description">
                    Kelas dan musyrif dibaca dari placement semester terpilih; transaksi dibatasi oleh semester_id dan
                    rentang tanggal.

                </div>
            </div>
        </div>

        <section class="card report-card filter-card dashboard-section" aria-labelledby="filterReportTitle">
            <div class="card-body">
                <form class="row g-3 align-items-end" id="formFilter">
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label" for="filter_kelas">Kelas</label>
                        <select class="form-select" name="kelas_id" id="filter_kelas">
                            <option value="">Semua Kelas</option>
                            @foreach ($kelasList as $kelas)
                                <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <label class="form-label" for="filter_musyrif">Musyrif</label>
                        <select class="form-select" name="musyrif_id" id="filter_musyrif">
                            <option value="">Semua Musyrif</option>
                            @foreach ($musyrifList as $musyrif)
                                <option value="{{ $musyrif->id }}">{{ $musyrif->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-4 col-sm-6">
                        <label class="form-label" for="filter_semester">
                            Semester
                        </label>

                        <select class="form-select" name="semester_id" id="filter_semester"
                            {{ $semesterList->isEmpty() ? 'disabled' : '' }}>
                            @forelse ($semesterList as $semester)
                                @php
                                    $namaSemester = \Illuminate\Support\Str::title(
                                        str_replace('_', ' ', $semester->nama ?? ''),
                                    );

                                    $namaTahunAjaran = \Illuminate\Support\Str::title(
                                        str_replace('_', ' ', $semester->tahunAjaran?->nama ?? '-'),
                                    );
                                @endphp

                                <option value="{{ $semester->id }}" @selected((int) $semester->id === (int) $defaultSemesterId)>
                                    {{ $namaSemester }} — {{ $namaTahunAjaran }}
                                    {{ $semester->is_active ? '(Aktif)' : '' }}
                                </option>
                            @empty
                                <option value="">
                                    Belum Ada Semester
                                </option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6 col-sm-6">
                        <label class="form-label" for="filter_periode">Bulan dalam semester</label>
                        <input type="month" class="form-control" name="periode" id="filter_periode" value="">
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <div class="d-grid d-sm-flex gap-2">
                            <button type="submit" class="btn btn-report-primary flex-fill" id="btnApplyFilter">
                                <i class="bi bi-funnel-fill me-1"></i>
                                Terapkan
                            </button>
                            <button type="button" class="btn btn-report-reset flex-fill" id="btnResetFilter">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        {{-- KPI UTAMA --}}
        <div class="section-heading">
            <div>
                <div class="section-eyebrow">
                    <i class="bi bi-speedometer2"></i>
                    Executive Summary
                </div>
                <h2 class="section-heading__title">Indikator Kinerja Utama</h2>
                <div class="section-heading__description">
                    Snapshot performa organisasi berdasarkan ruang lingkup filter aktif.
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-5">
            <div class="col">
                <div class="card kpi-card h-100">
                    <div class="card-body p-4 d-flex justify-content-between gap-3">
                        <div>
                            <div class="kpi-label">Total Santri</div>
                            <div class="kpi-value" style="color: var(--report-purple);" id="kpi_total_santri">0</div>
                            <div class="metric-note">Sesuai filter organisasi</div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(107,78,255,.14); color: var(--report-purple);">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card h-100">
                    <div class="card-body p-4 d-flex justify-content-between gap-3">
                        <div>
                            <div class="kpi-label">Total Musyrif</div>
                            <div class="kpi-value text-primary" id="kpi_total_musyrif">0</div>
                            <div class="metric-note">Memiliki santri binaan</div>
                        </div>
                        <div class="kpi-icon text-primary" style="background: rgba(13,110,253,.14);">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card h-100">
                    <div class="card-body p-4 d-flex justify-content-between gap-3">
                        <div>
                            <div class="kpi-label">Absensi Musyrif Valid</div>
                            <div class="kpi-value text-success"><span id="kpi_kehadiran_musyrif">0</span>%</div>
                            <div class="metric-note">Valid dibanding seluruh absensi</div>
                        </div>
                        <div class="kpi-icon text-success" style="background: rgba(25,135,84,.14);">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card h-100">
                    <div class="card-body p-4 d-flex justify-content-between gap-3">
                        <div>
                            <div class="kpi-label">Total Setoran</div>
                            <div class="kpi-value" style="color: var(--report-tosca);" id="kpi_total_setor">0</div>
                            <div class="metric-note">Status lulus dan ulang</div>
                        </div>
                        <div class="kpi-icon" style="background: rgba(19,163,179,.14); color: var(--report-tosca);">
                            <i class="bi bi-journal-check"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card h-100">
                    <div class="card-body p-4 d-flex justify-content-between gap-3">
                        <div>
                            <div class="kpi-label">Rata-rata Nilai</div>
                            <div class="kpi-value text-warning" id="kpi_avg_nilai">0</div>
                            <div class="metric-note">Skala nilai 0–100</div>
                        </div>
                        <div class="kpi-icon text-warning" style="background: rgba(255,193,7,.14);">
                            <i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KPI KEHADIRAN SANTRI --}}
        <div class="section-heading">
            <div>
                <div class="section-eyebrow">
                    <i class="bi bi-person-check"></i>
                    Student Attendance
                </div>
                <h2 class="section-heading__title">Status Kehadiran Santri</h2>
                <div class="section-heading__description">
                    Hadir mencakup setoran lulus, setoran ulang, dan hadir tanpa setoran.
                </div>
            </div>

            <span class="report-updated" id="hadirTidakSetorNote">
                <i class="bi bi-journal-minus"></i>
                Hadir tidak setor: 0
            </span>
        </div>


        <div class="row g-3 mb-4 row-cols-1 row-cols-sm-2 row-cols-xl-4">
            <div class="col">
                <div class="card kpi-card attendance-card h-100 text-success">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Hadir</div>
                                <div class="kpi-value" id="kpi_hadir_santri">0</div>
                                <div class="metric-note"><span id="kpi_hadir_santri_pct">0%</span> dari total status</div>
                            </div>
                            <div class="kpi-icon text-success" style="background: rgba(25,135,84,.14);">
                                <i class="bi bi-person-check-fill"></i>
                            </div>
                        </div>
                        <div class="progress-thin mt-3"><span class="bg-success" id="progress_hadir"></span></div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card attendance-card h-100 text-warning">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Sakit</div>
                                <div class="kpi-value" id="kpi_sakit_santri">0</div>
                                <div class="metric-note" id="kpi_sakit_santri_pct">0%</div>
                            </div>
                            <div class="kpi-icon text-warning" style="background: rgba(255,193,7,.14);">
                                <i class="bi bi-bandaid-fill"></i>
                            </div>
                        </div>
                        <div class="progress-thin mt-3"><span class="bg-warning" id="progress_sakit"></span></div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card attendance-card h-100 text-primary">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Izin</div>
                                <div class="kpi-value" id="kpi_izin_santri">0</div>
                                <div class="metric-note" id="kpi_izin_santri_pct">0%</div>
                            </div>
                            <div class="kpi-icon text-primary" style="background: rgba(13,110,253,.14);">
                                <i class="bi bi-envelope-check-fill"></i>
                            </div>
                        </div>
                        <div class="progress-thin mt-3"><span class="bg-primary" id="progress_izin"></span></div>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card kpi-card attendance-card h-100 text-danger">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Alpha</div>
                                <div class="kpi-value" id="kpi_alpha_santri">0</div>
                                <div class="metric-note" id="kpi_alpha_santri_pct">0%</div>
                            </div>
                            <div class="kpi-icon text-danger" style="background: rgba(220,53,69,.14);">
                                <i class="bi bi-person-x-fill"></i>
                            </div>
                        </div>
                        <div class="progress-thin mt-3"><span class="bg-danger" id="progress_alpha"></span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- INSIGHT OPERASIONAL --}}
        <div class="section-heading">
            <div>
                <div class="section-eyebrow">
                    <i class="bi bi-lightbulb-fill"></i>
                    Operational Insight
                </div>
                <h2 class="section-heading__title">Insight Tindak Lanjut</h2>
                <div class="section-heading__description">
                    Prioritas pendampingan santri dan indikator produktivitas pembinaan.
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card insight-card h-100">
                    <div class="card-body p-4">
                        <div class="insight-label">Santri Aktif Setor</div>
                        <div class="d-flex align-items-end justify-content-between gap-3">
                            <h3 class="fw-bold text-success mb-0" id="insight_santri_aktif">0</h3>
                            <span class="badge bg-success-subtle text-success rounded-pill"
                                id="insight_coverage_santri">0%</span>
                        </div>
                        <div class="metric-note mt-2">Persentase santri yang memiliki setoran.</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card insight-card h-100">
                    <div class="card-body p-4">
                        <div class="insight-label">Belum Ada Setoran</div>
                        <h3 class="fw-bold text-warning mb-0" id="insight_belum_setor">0</h3>
                        <div class="metric-note mt-2">Perlu tindak lanjut dari musyrif pendamping.</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card insight-card h-100">
                    <div class="card-body p-4">
                        <div class="insight-label">Risiko Alpha</div>
                        <h3 class="fw-bold text-danger mb-0" id="insight_risiko_alpha">0</h3>
                        <div class="metric-note mt-2">Santri dengan alpha minimal tiga kali.</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6">
                <div class="card insight-card h-100">
                    <div class="card-body p-4">
                        <div class="insight-label">Rata-rata Setoran</div>
                        <h3 class="fw-bold text-primary mb-0" id="insight_avg_setoran">0</h3>
                        <div class="metric-note mt-2">Jumlah setoran rata-rata per santri.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card report-card insight-ranking mb-4">
            <div class="card-body">
                <div class="row g-0">
                    <div class="col-md-6 border-md-end pe-md-4">
                        <div class="insight-ranking__item">
                            <div class="insight-ranking__icon">
                                <i class="bi bi-building-check"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="insight-label">Kelas paling produktif</div>
                                <h5 class="fw-bold mb-1 text-truncate" id="insight_top_kelas">-</h5>
                                <div class="small text-muted">
                                    <strong id="insight_top_kelas_total">0</strong> setoran tercatat
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 ps-md-4 mt-3 mt-md-0">
                        <div class="insight-ranking__item is-tosca">
                            <div class="insight-ranking__icon">
                                <i class="bi bi-person-video3"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="insight-label">Musyrif paling produktif</div>
                                <h5 class="fw-bold mb-1 text-truncate" id="insight_top_musyrif">-</h5>
                                <div class="small text-muted">
                                    <strong id="insight_top_musyrif_total">0</strong> setoran tercatat
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- GRAFIK INSIGHT --}}
        <div class="section-heading">
            <div>
                <div class="section-eyebrow">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    Visual Analytics
                </div>
                <h2 class="section-heading__title">Pola dan Tren Kinerja</h2>
                <div class="section-heading__description">
                    Visualisasi distribusi kehadiran, tren semester, dan produktivitas pembinaan.
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="card report-card h-100">
                    <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                        <div>
                            <div class="fw-bold text-white"><i
                                    class="bi bi-pie-chart-fill text-primary me-2"></i>Distribusi
                                Kehadiran Santri</div>
                            <div class="chart-card-subtitle text-white">Komposisi hadir, sakit, izin, dan alpha pada
                                periode terpilih.
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="chart-box">
                            <canvas id="chartKehadiranSantri"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card report-card h-100">
                    <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                        <div>
                            <div class="fw-bold text-white"><i class="bi bi-graph-up-arrow text-success me-2"></i>Tren
                                Kinerja
                                Semester</div>
                            <div class="chart-card-subtitle text-white">Pergerakan jumlah setoran dan alpha sepanjang
                                periode laporan.
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="chart-box">
                            <canvas id="chartTrendSemester"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card report-card h-100">
                    <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                        <div>
                            <div class="fw-bold text-white"><i class="bi bi-bar-chart-fill text-success me-2"
                                    style="color: var(--report-tosca);"></i>Setoran per Kelas</div>
                            <div class="chart-card-subtitle text-white">Perbandingan volume setoran antar kelas.</div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="chart-box-sm"><canvas id="chartKelas"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card report-card h-100">
                    <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                        <div>
                            <div class="fw-bold text-white"><i class="bi bi-bar-chart-fill me-2"
                                    style="color: var(--report-tosca);"></i>Setoran per Musyrif</div>
                            <div class="chart-card-subtitle text-white">Produktivitas pendampingan berdasarkan jumlah
                                setoran.</div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="chart-box-sm"><canvas id="chartMusyrif"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- GRAFIK JUZ LULUS --}}
        <div class="card report-card mb-4">
            <div
                class="card-header bg-transparent border-0 px-4 pt-4 pb-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="fw-bold text-white"><i class="bi bi-award-fill text-success me-2"></i>Kelulusan Ujian
                        Akhir per Juz
                    </div>
                    <div class="chart-card-subtitle text-white">Jumlah santri yang berhasil menyelesaikan ujian akhir
                        setiap Juz.
                    </div>
                </div>
                <ul class="nav nav-pills nav-pills-sm flex-nowrap overflow-auto" id="juzTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-nowrap" id="juz-all-tab" data-coreui-toggle="tab"
                            data-coreui-target="#juz-all" type="button" role="tab">Semua Kelas</button>
                    </li>
                    @foreach ($kelasList as $kelas)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-nowrap" id="juz-kelas-{{ $kelas->id }}-tab"
                                data-coreui-toggle="tab" data-coreui-target="#juz-kelas-{{ $kelas->id }}"
                                type="button" role="tab" title="{{ $kelas->nama_kelas }}">
                                {{ \Illuminate\Support\Str::limit($kelas->nama_kelas, 15) }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="card-body p-4">
                <div class="tab-content" id="juzTabContent">
                    <div class="tab-pane fade show active" id="juz-all" role="tabpanel">
                        <div class="text-center text-muted py-5 d-none" id="noteJuzAll">
                            <i class="bi bi-info-circle me-1"></i>Belum ada kelulusan ujian akhir pada periode ini.
                        </div>
                        <div class="chart-box-sm"><canvas id="chartJuzAll"></canvas></div>
                    </div>
                    @foreach ($kelasList as $kelas)
                        <div class="tab-pane fade" id="juz-kelas-{{ $kelas->id }}" role="tabpanel">
                            <div class="text-center text-muted py-5 d-none" id="noteJuzKelas_{{ $kelas->id }}">
                                <i class="bi bi-info-circle me-1"></i>Belum ada kelulusan ujian akhir di kelas ini.
                            </div>
                            <div class="chart-box-sm"><canvas id="chartJuzKelas_{{ $kelas->id }}"></canvas></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- REKAP TABLE --}}
        <div class="card report-card mb-5">
            <div
                class="card-header bg-transparent border-0 px-4 pt-4 pb-0 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <div class="fw-bold text-white"><i class="bi bi-table me-2"></i>Rekap Laporan Lengkap</div>
                    <div class="chart-card-subtitle text-white">Drill-down data per santri, kelas, musyrif, dan histori
                        absensi.</div>
                </div>
                <ul class="nav nav-pills nav-pills-sm flex-nowrap overflow-auto" id="rekapTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-nowrap" id="tab-santri-tab" data-coreui-toggle="tab"
                            data-coreui-target="#tab-santri" type="button" role="tab">Per Santri</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="tab-kelas-tab" data-coreui-toggle="tab"
                            data-coreui-target="#tab-kelas" type="button" role="tab">Per Kelas</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="tab-musyrif-tab" data-coreui-toggle="tab"
                            data-coreui-target="#tab-musyrif" type="button" role="tab">Per Musyrif</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="tab-absensi-tab" data-coreui-toggle="tab"
                            data-coreui-target="#tab-absensi" type="button" role="tab">
                            <i class="bi bi-geo-alt me-1"></i>Histori Kehadiran
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-santri" role="tabpanel">
                        <div class="table-toolbar justify-content-end export-actions">
                            <button type="button" class="btn btn-sm btn-success text-white fw-bold no-loader"
                                id="btnExportSantriExcel">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-danger text-white fw-bold no-loader"
                                id="btnExportSantriPdf">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                                id="table-rekap-santri">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Kelas</th>
                                        <th>Santri</th>
                                        <th>Musyrif</th>
                                        <th>Jumlah Setoran</th>
                                        <th>Hadir Tidak Setor</th>
                                        <th>Sakit</th>
                                        <th>Izin</th>
                                        <th>Alpha</th>
                                        <th>Rata-rata Nilai</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-kelas" role="tabpanel">
                        <div class="table-toolbar justify-content-end export-actions">
                            <button type="button" class="btn btn-sm btn-success text-white fw-bold no-loader"
                                id="btnExportKelasExcel">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-danger text-white fw-bold no-loader"
                                id="btnExportKelasPdf">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                                id="table-rekap-kelas">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Kelas</th>
                                        <th>Jumlah Santri</th>
                                        <th>Jumlah Setoran</th>
                                        <th>Rata-rata Nilai</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-musyrif" role="tabpanel">
                        <div class="table-toolbar justify-content-end export-actions">
                            <button type="button" class="btn btn-sm btn-success text-white fw-bold no-loader"
                                id="btnExportMusyrifExcel">
                                <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-danger text-white fw-bold no-loader"
                                id="btnExportMusyrifPdf">
                                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                                id="table-rekap-musyrif">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Musyrif</th>
                                        <th>Jumlah Santri Binaan</th>
                                        <th>Jumlah Setoran</th>
                                        <th>Rata-rata Nilai</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-absensi" role="tabpanel">
                        <div
                            class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
                            <div class="alert alert-info border-0 d-flex align-items-center mb-0 flex-grow-1 py-2">
                                <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                                <small>
                                    Status suspect atau rejected menunjukkan absensi yang perlu diverifikasi karena radius,
                                    akurasi, atau data GPS.
                                </small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0 fw-semibold text-nowrap" for="filter_waktu_absensi">
                                    <i class="bi bi-calendar3 me-1"></i>Waktu:
                                </label>
                                <select class="form-select form-select-sm" id="filter_waktu_absensi"
                                    style="min-width: 190px;">
                                    <option value="today">Hari Ini</option>
                                    <option value="periode" selected>Sesuai Filter Laporan</option>
                                    <option value="all">Semua Riwayat</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                                id="table-absensi-musyrif">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Waktu Absen</th>
                                        <th>Musyrif</th>
                                        <th>Sesi</th>
                                        <th>Koordinat & Lokasi</th>
                                        <th>Status</th>
                                        <th>Akurasi / Bukti</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalRiwayatSantri" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header modal-header-accent text-white">
                    <div>
                        <h5 class="modal-title mb-0">Riwayat Hafalan: <span id="detail_nama_santri"></span></h5>
                        <small class="opacity-75" id="detail_periode_santri"></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        Kelas: <strong id="detail_kelas_santri"></strong>
                        <span class="mx-2">|</span>
                        Musyrif: <strong id="detail_musyrif_santri"></strong>
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered align-middle" id="table-riwayat-santri">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Materi</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPreviewPhoto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Bukti Kehadiran</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center p-3">
                    <img id="previewImage" src="" alt="Foto Absensi" class="img-fluid rounded-3 shadow-sm w-100"
                        style="object-fit: contain; max-height: 70vh;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPreviewMap" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-primary">
                        <i class="bi bi-geo-alt text-primary me-2"></i>Preview Lokasi
                    </h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="previewMapIframe" width="100%" height="450" style="border:0; display:block;"
                        allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            const DEFAULT_SEMESTER_ID = @json($defaultSemesterId);
            const chartCache = new Map();

            const reportPage = document.querySelector('.report-page');

            function getChartTheme() {
                const styles = getComputedStyle(reportPage || document.documentElement);

                return {
                    axis: styles.getPropertyValue('--report-chart-text').trim() || '#6c757d',
                    grid: styles.getPropertyValue('--report-chart-grid').trim() || 'rgba(0,0,0,.06)',
                    tooltipBg: styles.getPropertyValue('--report-surface-elevated').trim() || '#ffffff',
                    tooltipText: styles.getPropertyValue('--report-text').trim() || '#20242c',
                    tooltipBorder: styles.getPropertyValue('--report-border-strong').trim() || 'rgba(0,0,0,.12)'
                };
            }

            let chartTheme = getChartTheme();

            function getReportFilters() {
                return {
                    kelas_id: $('#filter_kelas').val() || '',
                    musyrif_id: $('#filter_musyrif').val() || '',
                    semester_id: $('#filter_semester').val() || '',
                    periode: $('#filter_periode').val() || ''
                };
            }

            function appendReportFilters(data) {
                Object.assign(data, getReportFilters());
            }

            function formatNumber(value) {
                return Number(value || 0).toLocaleString('id-ID');
            }

            function formatDecimal(value) {
                return Number(value || 0).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            }

            function setProgress(id, value) {
                document.getElementById(id)?.style.setProperty('width', `${Math.min(100, Number(value || 0))}%`);
            }

            function showError(message) {
                if (window.AppAlert) {
                    AppAlert.error(message);
                } else {
                    console.error(message);
                    alert(message);
                }
            }

            function initializeTooltips() {
                document.querySelectorAll('[data-coreui-toggle="tooltip"]').forEach(function(element) {
                    coreui.Tooltip.getOrCreateInstance(element);
                });
            }

            function dataTableLanguage(searchPlaceholder) {
                return {
                    processing: 'Memproses...',
                    search: '_INPUT_',
                    searchPlaceholder: searchPlaceholder,
                    lengthMenu: 'Tampil _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    paginate: {
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>'
                    }
                };
            }

            const tableSantri = $('#table-rekap-santri').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.laporan.data') }}',
                    data: appendReportFilters,
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            showError(Object.values(xhr.responseJSON?.errors || {}).flat().join('\n'));
                        }
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kelas',
                        name: 'kelas'
                    },
                    {
                        data: 'nama_santri',
                        name: 'nama_santri'
                    },
                    {
                        data: 'musyrif',
                        name: 'musyrif'
                    },
                    {
                        data: 'total_setor',
                        searchable: false
                    },
                    {
                        data: 'hadir_tidak_setor',
                        searchable: false
                    },
                    {
                        data: 'sakit',
                        searchable: false
                    },
                    {
                        data: 'izin',
                        searchable: false
                    },
                    {
                        data: 'alpha',
                        searchable: false
                    },
                    {
                        data: 'rata_nilai',
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: dataTableLanguage('Cari santri...'),
                drawCallback: initializeTooltips
            });

            const tableKelas = $('#table-rekap-kelas').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.laporan.rekap-kelas') }}',
                    data: appendReportFilters
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama_kelas',
                        name: 'kelas.nama_kelas'
                    },
                    {
                        data: 'jumlah_santri',
                        searchable: false
                    },
                    {
                        data: 'total_setor',
                        searchable: false
                    },
                    {
                        data: 'rata_nilai',
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: dataTableLanguage('Cari kelas...')
            });

            const tableMusyrif = $('#table-rekap-musyrif').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.laporan.rekap-musyrif') }}',
                    data: appendReportFilters
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama',
                        name: 'musyrifs.nama'
                    },
                    {
                        data: 'jumlah_santri',
                        searchable: false
                    },
                    {
                        data: 'total_setor',
                        searchable: false
                    },
                    {
                        data: 'rata_nilai',
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: dataTableLanguage('Cari musyrif...')
            });

            const tableAbsensi = $('#table-absensi-musyrif').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.laporan.absensi-musyrif') }}',
                    data: function(data) {
                        appendReportFilters(data);
                        data.waktu_absensi = $('#filter_waktu_absensi').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'attendance_at',
                        name: 'ma.attendance_at'
                    },
                    {
                        data: 'musyrif_nama',
                        name: 'm.nama'
                    },
                    {
                        data: 'type',
                        name: 'ma.type'
                    },
                    {
                        data: 'location',
                        name: 'ma.address_text',
                        orderable: false
                    },
                    {
                        data: 'status',
                        name: 'ma.status',
                        className: 'text-center'
                    },
                    {
                        data: 'photo',
                        name: 'ma.photo_path',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                language: dataTableLanguage('Cari absensi...')
            });

            const chartKelas = new Chart(document.getElementById('chartKelas'), {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: [],
                        backgroundColor: '#6b4eff',
                        borderRadius: 7
                    }]
                },
                options: barChartOptions()
            });

            const chartMusyrif = new Chart(document.getElementById('chartMusyrif'), {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: [],
                        backgroundColor: '#13a3b3',
                        borderRadius: 7
                    }]
                },
                options: barChartOptions()
            });

            const attendanceChart = new Chart(document.getElementById('chartKehadiranSantri'), {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#198754', '#ffc107', '#0d6efd', '#dc3545'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: chartTheme.axis,
                                usePointStyle: true,
                                padding: 18
                            }
                        },
                        tooltip: tooltipThemeOptions()
                    }
                }
            });

            const semesterTrendChart = new Chart(document.getElementById('chartTrendSemester'), {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                            label: 'Setoran',
                            data: [],
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25,135,84,.12)',
                            fill: true,
                            tension: .35,
                            pointRadius: 3
                        },
                        {
                            label: 'Alpha',
                            data: [],
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220,53,69,.08)',
                            fill: false,
                            tension: .35,
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: chartTheme.axis,
                                usePointStyle: true
                            }
                        },
                        tooltip: tooltipThemeOptions()
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartTheme.axis
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                color: chartTheme.grid
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: chartTheme.axis,
                                precision: 0
                            },
                            grid: {
                                color: chartTheme.grid
                            },
                            border: {
                                color: chartTheme.grid
                            }
                        }
                    }
                }
            });

            function tooltipThemeOptions() {
                return {
                    backgroundColor: chartTheme.tooltipBg,
                    titleColor: chartTheme.tooltipText,
                    bodyColor: chartTheme.tooltipText,
                    borderColor: chartTheme.tooltipBorder,
                    borderWidth: 1,
                    padding: 12,
                    displayColors: true
                };
            }

            function barChartOptions() {
                return {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: tooltipThemeOptions()
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: chartTheme.axis
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                color: chartTheme.grid
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: chartTheme.axis,
                                precision: 0
                            },
                            grid: {
                                color: chartTheme.grid
                            },
                            border: {
                                color: chartTheme.grid
                            }
                        }
                    }
                };
            }

            function applyChartTheme() {
                chartTheme = getChartTheme();

                [chartKelas, chartMusyrif].forEach(function(chart) {
                    chart.options.scales.x.ticks.color = chartTheme.axis;
                    chart.options.scales.x.border.color = chartTheme.grid;
                    chart.options.scales.y.ticks.color = chartTheme.axis;
                    chart.options.scales.y.grid.color = chartTheme.grid;
                    chart.options.scales.y.border.color = chartTheme.grid;
                    chart.options.plugins.tooltip = tooltipThemeOptions();
                    chart.update('none');
                });

                attendanceChart.options.plugins.legend.labels.color = chartTheme.axis;
                attendanceChart.options.plugins.tooltip = tooltipThemeOptions();
                attendanceChart.update('none');

                semesterTrendChart.options.plugins.legend.labels.color = chartTheme.axis;
                semesterTrendChart.options.plugins.tooltip = tooltipThemeOptions();
                semesterTrendChart.options.scales.x.ticks.color = chartTheme.axis;
                semesterTrendChart.options.scales.x.border.color = chartTheme.grid;
                semesterTrendChart.options.scales.y.ticks.color = chartTheme.axis;
                semesterTrendChart.options.scales.y.grid.color = chartTheme.grid;
                semesterTrendChart.options.scales.y.border.color = chartTheme.grid;
                semesterTrendChart.update('none');

                chartCache.forEach(function(chart) {
                    if (!chart?.options?.scales) return;
                    chart.options.scales.x.ticks.color = chartTheme.axis;
                    chart.options.scales.x.border.color = chartTheme.grid;
                    chart.options.scales.y.ticks.color = chartTheme.axis;
                    chart.options.scales.y.grid.color = chartTheme.grid;
                    chart.options.scales.y.border.color = chartTheme.grid;
                    chart.options.plugins.tooltip = tooltipThemeOptions();
                    chart.update('none');
                });
            }

            const themeObserver = new MutationObserver(function(mutations) {
                const themeChanged = mutations.some(function(mutation) {
                    return mutation.type === 'attributes' && mutation.attributeName ===
                        'data-coreui-theme';
                });

                if (!themeChanged) return;

                requestAnimationFrame(function() {
                    applyChartTheme();
                    $.fn.dataTable.tables({
                        visible: true,
                        api: true
                    }).columns.adjust();
                });
            });

            themeObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-coreui-theme']
            });

            if (document.body) {
                themeObserver.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['data-coreui-theme']
                });
            }

            function animateCounter(element, target) {
                if (!element) return;

                const numericTarget = Number(target || 0);
                const duration = 650;
                const startValue = Number(String(element.textContent).replace(/[^0-9.-]/g, '')) || 0;
                const startTime = performance.now();

                function step(currentTime) {
                    const progress = Math.min((currentTime - startTime) / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const value = startValue + (numericTarget - startValue) * eased;
                    element.textContent = Number.isInteger(numericTarget) ?
                        Math.round(value).toLocaleString('id-ID') :
                        value.toLocaleString('id-ID', {
                            maximumFractionDigits: 2
                        });

                    if (progress < 1) requestAnimationFrame(step);
                }

                requestAnimationFrame(step);
            }

            function reloadDashboardSummary() {
                return $.ajax({
                    url: '{{ route('admin.laporan.data') }}',
                    type: 'GET',
                    data: {
                        ...getReportFilters(),
                        summary_only: 1
                    },
                    success: function(response) {
                        $('#semesterContextLabel').text(response.semester.label || '-');
                        $('#semesterContextRange').text(response.semester.periode_label || '-');
                        $('#semesterContextStatus')
                            .text(
                                `${response.semester.is_active ? 'Semester Aktif' : 'Semester Terpilih'} • Placement Historis`
                            );

                        const placementWarnings =
                            response.data_source?.warnings || [];

                        if (placementWarnings.length > 0) {
                            $('#placementIntegrityMessage').html(
                                placementWarnings
                                .map(message => `<div>• ${String(message)}</div>`)
                                .join('')
                            );

                            $('#placementIntegrityAlert')
                                .removeClass('d-none');
                        } else {
                            $('#placementIntegrityAlert')
                                .addClass('d-none');

                            $('#placementIntegrityMessage')
                                .text('-');
                        }

                        animateCounter(document.getElementById('kpi_total_santri'), response.kpi
                            .total_santri);
                        animateCounter(document.getElementById('kpi_total_musyrif'), response.kpi
                            .total_musyrif);
                        animateCounter(document.getElementById('kpi_kehadiran_musyrif'), response.kpi
                            .valid_absensi_musyrif_pct);
                        animateCounter(document.getElementById('kpi_total_setor'), response.kpi
                            .total_setor);
                        animateCounter(document.getElementById('kpi_avg_nilai'), response.kpi
                            .avg_nilai);

                        animateCounter(document.getElementById('kpi_hadir_santri'), response.attendance
                            .hadir.count);
                        animateCounter(document.getElementById('kpi_sakit_santri'), response.attendance
                            .sakit.count);
                        animateCounter(document.getElementById('kpi_izin_santri'), response.attendance
                            .izin.count);
                        animateCounter(document.getElementById('kpi_alpha_santri'), response.attendance
                            .alpha.count);

                        $('#kpi_hadir_santri_pct').text(
                            `${response.attendance.hadir.percentage || 0}%`);
                        $('#kpi_sakit_santri_pct').text(
                            `${response.attendance.sakit.percentage || 0}% dari total status`);
                        $('#kpi_izin_santri_pct').text(
                            `${response.attendance.izin.percentage || 0}% dari total status`);
                        $('#kpi_alpha_santri_pct').text(
                            `${response.attendance.alpha.percentage || 0}% dari total status`);
                        $('#hadirTidakSetorNote').text(
                            `Hadir tidak setor: ${formatNumber(response.attendance.hadir_tidak_setor)}`
                        );

                        setProgress('progress_hadir', response.attendance.hadir.percentage);
                        setProgress('progress_sakit', response.attendance.sakit.percentage);
                        setProgress('progress_izin', response.attendance.izin.percentage);
                        setProgress('progress_alpha', response.attendance.alpha.percentage);

                        $('#insight_santri_aktif').text(formatNumber(response.insights.santri_aktif));
                        $('#insight_coverage_santri').text(
                            `${response.insights.coverage_santri_pct || 0}% coverage`);
                        $('#insight_belum_setor').text(formatNumber(response.insights
                            .santri_belum_setor));
                        $('#insight_risiko_alpha').text(formatNumber(response.insights
                            .santri_risiko_alpha));
                        $('#insight_avg_setoran').text(formatDecimal(response.insights
                            .avg_setoran_per_santri));
                        $('#insight_top_kelas').text(response.insights.top_kelas.nama || '-');
                        $('#insight_top_kelas_total').text(formatNumber(response.insights.top_kelas
                            .total_setor));
                        $('#insight_top_musyrif').text(response.insights.top_musyrif.nama || '-');
                        $('#insight_top_musyrif_total').text(formatNumber(response.insights.top_musyrif
                            .total_setor));

                        attendanceChart.data.labels = response.charts.attendance.labels || [];
                        attendanceChart.data.datasets[0].data = response.charts.attendance.data || [];
                        attendanceChart.update();

                        semesterTrendChart.data.labels = response.charts.trend.labels || [];
                        semesterTrendChart.data.datasets[0].data = response.charts.trend.setoran || [];
                        semesterTrendChart.data.datasets[1].data = response.charts.trend.alpha || [];
                        semesterTrendChart.update();

                        $('#lastUpdatedAt').text(new Date().toLocaleString('id-ID'));
                    },
                    error: function(xhr) {
                        let message = 'Gagal memuat ringkasan laporan.';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            message = Object.values(xhr.responseJSON.errors).flat().join('\n');
                        }
                        showError(message);
                    }
                });
            }

            function reloadCharts() {
                const params = getReportFilters();

                $.get('{{ route('admin.laporan.chart-kelas') }}', params)
                    .done(function(response) {
                        chartKelas.data.labels = response.labels || [];
                        chartKelas.data.datasets[0].data = response.data || [];
                        chartKelas.update();
                    });

                $.get('{{ route('admin.laporan.chart-musyrif') }}', params)
                    .done(function(response) {
                        chartMusyrif.data.labels = response.labels || [];
                        chartMusyrif.data.datasets[0].data = response.data || [];
                        chartMusyrif.update();
                    });

                renderActiveJuzChart();
            }

            function destroyJuzChart(canvasId) {
                if (!chartCache.has(canvasId)) return;
                chartCache.get(canvasId).destroy();
                chartCache.delete(canvasId);
            }

            function isAllZero(values) {
                return (values || []).every(value => Number(value) === 0);
            }

            async function fetchJuzData(kelasId = null) {
                const params = new URLSearchParams(getReportFilters());
                if (kelasId) params.set('kelas_id', kelasId);

                const response = await fetch(
                    `{{ route('admin.laporan.chart.juz-lulus') }}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json'
                        }
                    });

                if (!response.ok) throw new Error('Gagal mengambil data grafik Juz.');
                return response.json();
            }

            async function renderBarJuz(canvasId, kelasId = null, labelTitle = '') {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;

                const noteId = kelasId ? `noteJuzKelas_${kelasId}` : 'noteJuzAll';
                const noteElement = document.getElementById(noteId);

                try {
                    const json = await fetchJuzData(kelasId);

                    if (isAllZero(json.data)) {
                        canvas.classList.add('d-none');
                        destroyJuzChart(canvasId);
                        noteElement?.classList.remove('d-none');
                        return;
                    }

                    noteElement?.classList.add('d-none');
                    canvas.classList.remove('d-none');
                    destroyJuzChart(canvasId);

                    const chart = new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: json.labels || [],
                            datasets: [{
                                label: labelTitle,
                                data: json.data || [],
                                backgroundColor: '#198754',
                                borderRadius: 5
                            }]
                        },
                        options: barChartOptions()
                    });

                    chartCache.set(canvasId, chart);
                } catch (error) {
                    console.error(error);
                }
            }

            function renderActiveJuzChart() {
                const activeTab = document.querySelector('#juzTabs .nav-link.active');
                if (!activeTab) return;

                const target = activeTab.getAttribute('data-coreui-target');
                if (target === '#juz-all') {
                    renderBarJuz('chartJuzAll', null, 'Santri Lulus Ujian Akhir');
                    return;
                }

                const match = target?.match(/juz-kelas-(\d+)/);
                if (!match) return;

                renderBarJuz(
                    `chartJuzKelas_${match[1]}`,
                    match[1],
                    `Santri Lulus Ujian Akhir (${activeTab.title || 'Kelas'})`
                );
            }

            function reloadAllReports(resetPaging = true) {
                tableSantri.ajax.reload(null, resetPaging);
                tableKelas.ajax.reload(null, resetPaging);
                tableMusyrif.ajax.reload(null, resetPaging);
                tableAbsensi.ajax.reload(null, resetPaging);
                reloadDashboardSummary();
                reloadCharts();
            }

            $('#formFilter').on('submit', function(event) {
                event.preventDefault();
                reloadAllReports(true);
            });

            $('#btnResetFilter').on('click', function() {
                $('#filter_kelas').val('');
                $('#filter_musyrif').val('');
                $('#filter_semester').val(DEFAULT_SEMESTER_ID || '');
                $('#filter_periode').val('');
                $('#filter_waktu_absensi').val('periode');
                reloadAllReports(true);
            });

            $('#filter_semester').on('change', function() {
                $('#filter_periode').val('');
            });

            $('#filter_waktu_absensi').on('change', function() {
                tableAbsensi.ajax.reload(null, true);
            });

            document.querySelectorAll('#juzTabs [data-coreui-toggle="tab"]').forEach(function(button) {
                button.addEventListener('shown.coreui.tab', renderActiveJuzChart);
            });

            document.querySelectorAll('#rekapTabs [data-coreui-toggle="tab"]').forEach(function(button) {
                button.addEventListener('shown.coreui.tab', function() {
                    $.fn.dataTable.tables({
                        visible: true,
                        api: true
                    }).columns.adjust();

                    [tableSantri, tableKelas, tableMusyrif, tableAbsensi].forEach(function(table) {
                        if (table.responsive && typeof table.responsive.recalc ===
                            'function') {
                            table.responsive.recalc();
                        }
                    });
                });
            });

            $('#table-rekap-santri').on('click', '.btn-detail-santri', function() {
                const button = $(this);
                const santriId = button.data('id');
                const filters = getReportFilters();

                $('#detail_nama_santri').text(button.data('nama') || '-');
                $('#detail_kelas_santri').text('-');
                $('#detail_musyrif_santri').text('-');
                $('#detail_periode_santri').text('Memuat periode...');
                $('#table-riwayat-santri tbody').html(
                    '<tr><td colspan="5" class="text-center py-4">Memuat data...</td></tr>'
                );

                let url = '{{ route('admin.laporan.riwayat-santri', ':id') }}'.replace(':id', santriId);

                $.get(url, filters)
                    .done(function(response) {
                        $('#detail_nama_santri').text(response.santri?.nama || '-');
                        $('#detail_kelas_santri').text(response.santri?.kelas || '-');
                        $('#detail_musyrif_santri').text(response.santri?.musyrif || '-');
                        $('#detail_periode_santri').text(response.period_label || '');

                        let rows = '';
                        (response.riwayat || []).forEach(function(item) {
                            rows += `
                                <tr>
                                    <td>${item.tanggal_setoran || '-'}</td>
                                    <td>${item.materi || '-'}</td>
                                    <td>${item.status || '-'}</td>
                                    <td>${item.nilai_label || '-'}</td>
                                    <td>${item.catatan || '-'}</td>
                                </tr>
                            `;
                        });

                        $('#table-riwayat-santri tbody').html(rows ||
                            '<tr><td colspan="5" class="text-center py-4 text-muted">Belum ada setoran pada periode ini.</td></tr>'
                        );
                    })
                    .fail(function() {
                        $('#table-riwayat-santri tbody').html(
                            '<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat data.</td></tr>'
                        );
                    })
                    .always(function() {
                        coreui.Modal.getOrCreateInstance(document.getElementById('modalRiwayatSantri'))
                            .show();
                    });
            });

            function buildQueryString() {
                const params = new URLSearchParams();

                Object.entries(getReportFilters()).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        params.set(key, value);
                    }
                });

                return params.toString();
            }

            /**
             * Membentuk URL export lengkap beserta filter aktif.
             */
            function buildExportUrl(baseUrl) {
                const url = new URL(baseUrl, window.location.origin);

                Object.entries(getReportFilters()).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        url.searchParams.set(key, value);
                    }
                });

                // Mencegah browser/proxy menggunakan response export lama.
                url.searchParams.set('_download', Date.now().toString());

                return url.toString();
            }

            /**
             * Menjamin loader global tetap tersembunyi selama export.
             */
            function hideGlobalLoaderForDownload() {
                const loader = document.getElementById('global-loader');

                if (loader) {
                    loader.classList.add('loader-hidden');
                }

                if (typeof window.skipGlobalLoaderOnce === 'function') {
                    window.skipGlobalLoaderOnce(15000);
                }
            }

            /**
             * Menjalankan unduhan native di tab terpisah.
             * Tidak memakai fetch/Blob, sehingga response attachment tidak
             * berubah menjadi body kosong/HTTP 204 pada request AJAX.
             */
            function triggerDownload(
                baseUrl,
                buttonElement,
                loadingText = 'Menyiapkan file...'
            ) {
                const button = $(buttonElement);
                const originalHtml = button.html();
                const downloadUrl = buildExportUrl(baseUrl);

                button.prop('disabled', true);
                button.html(`
                    <span
                        class="spinner-border spinner-border-sm me-1"
                        aria-hidden="true"
                    ></span>
                    ${loadingText}
                `);

                hideGlobalLoaderForDownload();

                const downloadLink = document.createElement('a');
                downloadLink.href = downloadUrl;
                downloadLink.target = '_blank';
                downloadLink.rel = 'noopener';
                downloadLink.className = 'no-loader';
                downloadLink.setAttribute('data-no-loader', 'true');
                downloadLink.style.display = 'none';

                document.body.appendChild(downloadLink);
                downloadLink.click();
                downloadLink.remove();

                window.setTimeout(function() {
                    hideGlobalLoaderForDownload();
                    button.prop('disabled', false);
                    button.html(originalHtml);
                }, 1800);
            }

            function triggerPdfDownload(baseUrl, buttonElement) {
                return triggerDownload(
                    baseUrl,
                    buttonElement,
                    'Menyiapkan PDF...'
                );
            }

            $('#btnExportSantriExcel').on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                triggerDownload(
                    '{{ route('admin.laporan.export-santri-excel') }}',
                    this
                );
            });

            $('#btnExportKelasExcel').on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                triggerDownload(
                    '{{ route('admin.laporan.export-kelas-excel') }}',
                    this
                );
            });


            $('#btnExportMusyrifExcel').on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                triggerDownload(
                    '{{ route('admin.laporan.export-musyrif-excel') }}',
                    this
                );
            });

            $('#btnExportSantriPdf').on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                triggerPdfDownload(
                    '{{ route('admin.laporan.export-santri-pdf') }}',
                    this
                );
            });

            $('#btnExportKelasPdf').on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                triggerPdfDownload(
                    '{{ route('admin.laporan.export-kelas-pdf') }}',
                    this
                );
            });

            $('#btnExportMusyrifPdf').on('click', function(event) {
                event.preventDefault();
                event.stopPropagation();

                triggerPdfDownload(
                    '{{ route('admin.laporan.export-musyrif-pdf') }}',
                    this
                );
            });

            $('#table-absensi-musyrif').on('click', '.btn-preview-photo', function() {
                $('#previewImage').attr('src', $(this).data('url'));
                coreui.Modal.getOrCreateInstance(document.getElementById('modalPreviewPhoto')).show();
            });

            $('#table-absensi-musyrif').on('click', '.btn-preview-map', function() {
                const lat = $(this).data('lat');
                const lng = $(this).data('lng');
                const embedUrl =
                    `https://maps.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}&t=&z=16&ie=UTF8&iwloc=&output=embed`;
                $('#previewMapIframe').attr('src', embedUrl);
                coreui.Modal.getOrCreateInstance(document.getElementById('modalPreviewMap')).show();
            });

            document.getElementById('modalPreviewMap').addEventListener('hidden.coreui.modal', function() {
                document.getElementById('previewMapIframe').src = '';
            });

            document.getElementById('modalPreviewPhoto').addEventListener('hidden.coreui.modal', function() {
                document.getElementById('previewImage').src = '';
            });

            window.addEventListener('beforeunload', function() {
                themeObserver.disconnect();
            }, {
                once: true
            });

            initializeTooltips();
            applyChartTheme();
            reloadDashboardSummary();
            reloadCharts();
        });
    </script>
@endpush
