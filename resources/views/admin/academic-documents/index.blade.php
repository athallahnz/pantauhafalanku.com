@extends('layouts.app')

@section('title', 'Dokumen Akademik')

@section('content')
    <style>
        .academic-documents-page {
            --academic-primary: #6f42c1;
            --academic-primary-dark: #4f2d95;
            --academic-primary-soft: rgba(111, 66, 193, 0.1);
            --academic-blue: #0d6efd;
            --academic-blue-soft: rgba(13, 110, 253, 0.1);
            --academic-green: #198754;
            --academic-border: var(--cui-border-color, #e4e7ec);
            --academic-surface: var(--cui-body-bg, #ffffff);
            --academic-surface-soft: var(--cui-tertiary-bg, #f8f9fa);
            --academic-text: var(--cui-body-color, #1f2937);
            --academic-muted: var(--cui-secondary-color, #667085);
            --academic-shadow:
                0 14px 36px rgba(15, 23, 42, 0.07),
                0 2px 8px rgba(15, 23, 42, 0.03);
        }

        /* =========================================================
             * PAGE HERO
             * ======================================================= */

        .academic-hero {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(111, 66, 193, 0.14);
            border-radius: 1.25rem;
            background:
                radial-gradient(circle at 88% 10%,
                    rgba(255, 255, 255, 0.2),
                    transparent 28%),
                linear-gradient(135deg,
                    var(--academic-primary-dark),
                    var(--academic-primary) 58%,
                    #8264d9);
            box-shadow: 0 18px 45px rgba(79, 45, 149, 0.2);
            color: #ffffff;
        }

        .academic-hero::before,
        .academic-hero::after {
            position: absolute;
            border-radius: 999px;
            content: '';
            pointer-events: none;
        }

        .academic-hero::before {
            top: -80px;
            right: -30px;
            width: 240px;
            height: 240px;
            border: 42px solid rgba(255, 255, 255, 0.06);
        }

        .academic-hero::after {
            right: 180px;
            bottom: -95px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.045);
        }

        .academic-hero-content {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 2rem;
            padding: 1.6rem;
        }

        .academic-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 0.85rem;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .academic-eyebrow-icon {
            display: inline-grid;
            width: 30px;
            height: 30px;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 0.65rem;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .academic-page-title {
            margin-bottom: 0.6rem;
            color: #ffffff;
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 850;
            letter-spacing: -0.035em;
            line-height: 1.08;
        }

        .academic-page-description {
            max-width: 760px;
            margin-bottom: 1.15rem;
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.95rem;
            line-height: 1.7;
        }

        .academic-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.65rem 1rem;
        }

        .academic-hero-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: rgba(255, 255, 255, 0.88);
            font-size: 0.76rem;
            font-weight: 650;
        }

        .academic-hero-meta i {
            color: #ffe28a;
        }

        .academic-workflow {
            align-self: stretch;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
        }

        .academic-workflow-label {
            margin-bottom: 0.9rem;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }

        .academic-workflow-steps {
            display: flex;
            align-items: center;
        }

        .academic-workflow-step {
            display: flex;
            min-width: 0;
            flex: 1 1 0;
            align-items: center;
            gap: 0.55rem;
            opacity: 0.58;
        }

        .academic-workflow-step.is-active {
            opacity: 1;
        }

        .academic-workflow-number {
            display: inline-grid;
            flex: 0 0 32px;
            width: 32px;
            height: 32px;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.11);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .academic-workflow-step.is-active .academic-workflow-number {
            background: #ffffff;
            color: var(--academic-primary-dark);
            box-shadow: 0 8px 20px rgba(26, 16, 58, 0.2);
        }

        .academic-workflow-step strong,
        .academic-workflow-step small {
            display: block;
        }

        .academic-workflow-step strong {
            color: #ffffff;
            font-size: 0.76rem;
            line-height: 1.2;
        }

        .academic-workflow-step small {
            margin-top: 0.15rem;
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.62rem;
            line-height: 1.2;
        }

        .academic-workflow-divider {
            flex: 0 0 22px;
            height: 1px;
            margin: 0 0.45rem;
            background: rgba(255, 255, 255, 0.25);
        }

        /* =========================================================
             * SECTION CARDS
             * ======================================================= */

        .academic-filter-card,
        .academic-table-card {
            overflow: hidden;
            border: 1px solid var(--academic-border);
            border-radius: 1.15rem;
            background: var(--academic-surface);
            box-shadow: var(--academic-shadow);
        }

        .academic-section-header,
        .academic-table-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1.2rem 1.25rem;
            border-bottom: 1px solid var(--academic-border);
            background:
                linear-gradient(180deg,
                    rgba(111, 66, 193, 0.035),
                    transparent);
        }

        .academic-section-heading,
        .academic-table-title {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .academic-section-heading h2,
        .academic-table-title h2 {
            margin-bottom: 0.15rem;
            color: var(--academic-text);
            font-size: 1rem;
            font-weight: 820;
        }

        .academic-section-heading p,
        .academic-table-title p {
            margin-bottom: 0;
            color: var(--academic-muted);
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .academic-section-icon {
            display: inline-grid;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 0.82rem;
            background: var(--academic-primary-soft);
            color: var(--academic-primary);
            font-size: 1.05rem;
        }

        .academic-section-icon-dark {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        /* =========================================================
             * FILTER
             * ======================================================= */

        .academic-filter-body {
            padding: 1.25rem;
        }

        .academic-form-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.48rem;
            color: var(--academic-text);
            font-size: 0.78rem;
            font-weight: 760;
        }

        .academic-form-label span {
            display: inline-flex;
            align-items: center;
            gap: 0.42rem;
        }

        .academic-form-label i {
            color: var(--academic-primary);
        }

        .academic-form-label small {
            color: var(--academic-muted);
            font-size: 0.65rem;
            font-weight: 600;
        }

        .academic-form-control {
            min-height: 44px;
            border-color: var(--academic-border);
            border-radius: 0.75rem;
            background-color: var(--academic-surface);
            font-size: 0.84rem;
        }

        .academic-form-control:focus {
            border-color: rgba(111, 66, 193, 0.62);
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.11);
        }

        .academic-btn-primary,
        .academic-btn-secondary,
        .academic-btn-ghost {
            min-height: 42px;
            border-radius: 0.75rem;
            font-size: 0.78rem;
            font-weight: 760;
        }

        .academic-btn-primary {
            border-color: var(--academic-primary);
            background: linear-gradient(135deg,
                    var(--academic-primary-dark),
                    var(--academic-primary));
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(111, 66, 193, 0.18);
        }

        .academic-btn-primary:hover,
        .academic-btn-primary:focus {
            border-color: var(--academic-primary-dark);
            background: var(--academic-primary-dark);
            color: #ffffff;
        }

        .academic-btn-secondary {
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
        }

        .academic-btn-secondary:hover,
        .academic-btn-secondary:focus {
            border-color: rgba(255, 255, 255, 0.38);
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        .academic-btn-ghost {
            border: 1px solid var(--academic-border);
            background: var(--academic-surface-soft);
            color: var(--academic-muted);
        }

        .academic-btn-ghost:hover,
        .academic-btn-ghost:focus {
            border-color: rgba(111, 66, 193, 0.24);
            background: var(--academic-primary-soft);
            color: var(--academic-primary);
        }

        .academic-filter-helper {
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            margin-top: 1rem;
            padding: 0.82rem 0.95rem;
            border: 1px solid rgba(111, 66, 193, 0.12);
            border-radius: 0.8rem;
            background: var(--academic-primary-soft);
        }

        .academic-helper-icon {
            display: inline-grid;
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            place-items: center;
            border-radius: 0.55rem;
            background: rgba(111, 66, 193, 0.12);
            color: var(--academic-primary);
        }

        .academic-filter-helper p {
            margin: 0;
            color: var(--academic-muted);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        /* =========================================================
             * TABLE
             * ======================================================= */

        .academic-table-header {
            color: #ffffff;
            background:
                radial-gradient(circle at 92% 0,
                    rgba(255, 255, 255, 0.12),
                    transparent 26%),
                linear-gradient(135deg,
                    #31215f,
                    var(--academic-primary-dark) 58%,
                    var(--academic-primary));
        }

        .academic-table-title h2 {
            color: #ffffff;
        }

        .academic-table-title p {
            color: rgba(255, 255, 255, 0.68);
        }

        .academic-table-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
        }

        .academic-table-status {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.7rem;
            font-weight: 700;
        }

        .academic-table-status i {
            color: #6ee7a0;
            font-size: 0.45rem;
            box-shadow: 0 0 0 4px rgba(110, 231, 160, 0.1);
        }

        .academic-table-body {
            padding: 1rem 1rem 1.25rem;
        }

        .academic-table-note {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            margin-bottom: 1rem;
            padding: 0.75rem 0.85rem;
            border: 1px solid rgba(13, 110, 253, 0.12);
            border-radius: 0.75rem;
            background: rgba(13, 110, 253, 0.08);
        }

        .academic-table-note>div {
            display: inline-grid;
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            place-items: center;
            border-radius: 0.55rem;
            background: rgba(13, 110, 253, 0.12);
            color: var(--academic-blue);
        }

        .academic-table-note p {
            margin: 0;
            color: var(--academic-muted);
            font-size: 0.74rem;
            line-height: 1.5;
        }

        .academic-table-responsive {
            overflow: visible;
        }

        .academic-data-table {
            margin-bottom: 0 !important;
        }

        .academic-data-table thead th {
            padding: 0.82rem 0.75rem;
            border-bottom: 1px solid var(--academic-border) !important;
            background: var(--academic-surface-soft);
            color: var(--academic-muted);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
            vertical-align: middle;
            white-space: nowrap;
        }

        .academic-data-table tbody td {
            padding: 0.92rem 0.75rem;
            border-color: var(--academic-border);
            color: var(--academic-text);
            font-size: 0.78rem;
            vertical-align: middle;
        }

        .academic-data-table tbody tr {
            transition: background-color 0.18s ease;
        }

        .academic-data-table tbody tr:hover {
            background: rgba(111, 66, 193, 0.035);
        }

        .academic-data-table .btn {
            border-radius: 0.65rem;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .academic-data-table .badge {
            padding: 0.48rem 0.7rem;
            font-size: 0.66rem;
            font-weight: 750;
        }

        .academic-table-card .dataTables_length label,
        .academic-table-card .dataTables_filter label {
            color: var(--academic-muted);
            font-size: 0.72rem;
            font-weight: 650;
        }

        .academic-table-card .dataTables_filter input,
        .academic-table-card .dataTables_length select {
            min-height: 38px;
            border: 1px solid var(--academic-border);
            border-radius: 0.65rem;
            background: var(--academic-surface);
            color: var(--academic-text);
            font-size: 0.76rem;
        }

        .academic-table-card .dataTables_filter input {
            min-width: 230px;
            margin-left: 0.5rem;
            padding: 0.45rem 0.7rem;
        }

        .academic-table-card .dataTables_info {
            padding-top: 1rem !important;
            color: var(--academic-muted);
            font-size: 0.72rem;
        }

        .academic-table-card .page-link {
            min-width: 34px;
            border-color: var(--academic-border);
            color: var(--academic-muted);
            font-size: 0.72rem;
            text-align: center;
        }

        .academic-table-card .page-item.active .page-link {
            border-color: var(--academic-primary);
            background: var(--academic-primary);
            color: #ffffff;
        }

        @media (min-width: 768px) {

            .academic-section-header,
            .academic-table-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        @media (min-width: 992px) {
            .academic-hero-content {
                grid-template-columns:
                    minmax(0, 1fr) minmax(340px, 0.52fr);
                align-items: center;
                padding: 2rem;
            }
        }

        @media (max-width: 991.98px) {
            .academic-table-actions {
                justify-content: space-between;
            }

            .academic-table-responsive {
                overflow-x: auto;
            }
        }

        @media (max-width: 767.98px) {
            .academic-documents-page {
                padding-right: 0.75rem !important;
                padding-left: 0.75rem !important;
            }

            .academic-hero-content {
                padding: 1.25rem;
            }

            .academic-hero-meta {
                display: grid;
                grid-template-columns: 1fr;
                gap: 0.55rem;
            }

            .academic-workflow-step {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.45rem;
            }

            .academic-workflow-divider {
                flex-basis: 12px;
                margin: 0 0.3rem;
            }

            .academic-table-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .academic-btn-secondary {
                width: 100%;
            }

            .academic-table-card .dataTables_filter input {
                width: 100%;
                min-width: 0;
                margin: 0.4rem 0 0;
            }

            .academic-table-card .dataTables_filter label {
                display: block;
            }
        }

        #raportDraftModal {
            --raport-primary: #6f42c1;
            --raport-primary-soft: rgba(111, 66, 193, 0.1);
            --raport-blue: #0d6efd;
            --raport-blue-soft: rgba(13, 110, 253, 0.1);
            --raport-green: #198754;
            --raport-green-soft: rgba(25, 135, 84, 0.1);
            --raport-warning-soft: rgba(255, 193, 7, 0.11);
            --raport-border: var(--cui-border-color, #e4e7ec);
            --raport-surface: var(--cui-body-bg, #ffffff);
            --raport-muted-surface: var(--cui-tertiary-bg, #f8f9fa);
        }

        #raportDraftModal .modal-dialog {
            max-width: 1180px;
        }

        .raport-draft-modal {
            overflow: hidden;
            border-radius: 1.15rem;
            background: var(--raport-surface);
        }

        .raport-modal-header {
            align-items: flex-start;
            padding: 1.5rem 1.75rem 1.25rem;
            border-bottom: 1px solid var(--raport-border) !important;
            background:
                linear-gradient(135deg,
                    var(--raport-primary-soft),
                    transparent 55%);
        }

        .raport-modal-header-icon {
            display: grid;
            flex: 0 0 48px;
            width: 48px;
            height: 48px;
            place-items: center;
            border-radius: 0.9rem;
            background: var(--raport-primary);
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 8px 22px rgba(111, 66, 193, 0.22);
        }

        .raport-modal-eyebrow {
            margin-bottom: 0.25rem;
            color: var(--raport-primary);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.065em;
            text-transform: uppercase;
        }

        .raport-modal-body {
            padding: 1.5rem 1.75rem;
        }

        .raport-modal-loading {
            min-height: 330px;
            padding: 7rem 1rem;
        }

        .raport-summary-card {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            padding: 1.35rem;
            border: 1px solid var(--raport-border);
            border-radius: 1rem;
            background:
                linear-gradient(135deg,
                    var(--raport-primary-soft),
                    transparent 65%);
        }

        .raport-student-name {
            margin-bottom: 0.4rem;
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .raport-semester-label {
            color: var(--cui-secondary-color);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .raport-snapshot-box {
            width: 100%;
            padding: 0.85rem 1rem;
            border: 1px solid var(--raport-border);
            border-radius: 0.8rem;
            background: var(--raport-surface);
        }

        .raport-snapshot-label {
            margin-bottom: 0.45rem;
            color: var(--cui-secondary-color);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .raport-snapshot-box code {
            display: block;
            overflow-wrap: anywhere;
            color: var(--cui-body-color);
            font-size: 0.75rem;
            line-height: 1.55;
        }

        .raport-section-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .raport-section-title {
            margin-bottom: 0.2rem;
            font-size: 1rem;
            font-weight: 800;
        }

        .raport-section-description {
            margin-bottom: 0;
            color: var(--cui-secondary-color);
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .raport-info-card {
            padding: 1.1rem;
            border: 1px solid var(--raport-border);
            border-radius: 0.95rem;
            background: var(--raport-surface);
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;
        }

        .raport-info-card:hover,
        .raport-progress-card:hover {
            border-color: rgba(111, 66, 193, 0.25);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            transform: translateY(-2px);
        }

        .raport-info-card-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.15rem;
        }

        .raport-info-icon {
            display: grid;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 0.75rem;
            font-size: 1.1rem;
        }

        .raport-info-icon.icon-purple {
            background: var(--raport-primary-soft);
            color: var(--raport-primary);
        }

        .raport-info-icon.icon-blue {
            background: var(--raport-blue-soft);
            color: var(--raport-blue);
        }

        .raport-info-icon.icon-green {
            background: var(--raport-green-soft);
            color: var(--raport-green);
        }

        .raport-info-title {
            margin-bottom: 0.1rem;
            font-size: 0.9rem;
            font-weight: 800;
        }

        .raport-info-subtitle {
            color: var(--cui-secondary-color);
            font-size: 0.72rem;
        }

        .raport-detail-list {
            display: grid;
            gap: 0;
        }

        .raport-detail-item {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.7rem 0;
            border-bottom: 1px dashed var(--raport-border);
        }

        .raport-detail-item:first-child {
            padding-top: 0;
        }

        .raport-detail-item:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .raport-detail-item dt {
            color: var(--cui-secondary-color);
            font-size: 0.78rem;
            font-weight: 600;
        }

        .raport-detail-item dd {
            max-width: 62%;
            margin: 0;
            text-align: right;
            font-size: 0.8rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .raport-progress-card {
            padding: 1.15rem;
            border: 1px solid var(--raport-border);
            border-radius: 0.95rem;
            background: var(--raport-surface);
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;
        }

        .raport-progress-top {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .raport-domain-icon {
            display: grid;
            flex: 0 0 44px;
            width: 44px;
            height: 44px;
            place-items: center;
            border-radius: 0.8rem;
            font-size: 1.15rem;
        }

        .raport-domain-icon.domain-hafalan {
            background: var(--raport-primary-soft);
            color: var(--raport-primary);
        }

        .raport-domain-icon.domain-tahsin {
            background: var(--raport-blue-soft);
            color: var(--raport-blue);
        }

        .raport-domain-icon.domain-tilawah {
            background: var(--raport-green-soft);
            color: var(--raport-green);
        }

        .raport-progress-title {
            margin-bottom: 0.1rem;
            font-size: 0.95rem;
            font-weight: 800;
        }

        .raport-progress-subtitle {
            color: var(--cui-secondary-color);
            font-size: 0.72rem;
        }

        .raport-progress-number {
            display: flex;
            align-items: baseline;
            gap: 0.35rem;
            margin-top: 1.25rem;
        }

        .raport-progress-number span {
            font-size: 1.85rem;
            font-weight: 850;
            line-height: 1;
        }

        .raport-progress-number small {
            color: var(--cui-secondary-color);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .raport-progress-divider {
            height: 1px;
            margin: 1rem 0;
            background: var(--raport-border);
        }

        .raport-progress-metrics {
            display: grid;
            gap: 0.65rem;
        }

        .raport-progress-metrics>div {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            font-size: 0.78rem;
        }

        .raport-progress-metrics span {
            color: var(--cui-secondary-color);
        }

        .raport-progress-metrics strong {
            text-align: right;
            font-size: 0.8rem;
        }

        .raport-evaluation-card {
            padding: 1.25rem;
            border: 1px solid var(--raport-border);
            border-radius: 1rem;
            background: var(--raport-muted-surface);
        }

        .raport-evaluation-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .raport-evaluation-icon {
            display: grid;
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 0.75rem;
            background: var(--raport-primary-soft);
            color: var(--raport-primary);
            font-size: 1.05rem;
        }

        .raport-form-label {
            margin-bottom: 0.45rem;
            font-size: 0.8rem;
            font-weight: 750;
        }

        .raport-form-control {
            border-radius: 0.7rem;
        }

        .raport-form-control:focus {
            border-color: rgba(111, 66, 193, 0.65);
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.12);
        }

        .preview-warning-list {
            display: grid;
            gap: 0.65rem;
        }

        .preview-warning-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 0.8rem;
            background: var(--raport-warning-soft);
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .raport-modal-footer {
            padding: 1rem 1.75rem 1.25rem;
            border-top: 1px solid var(--raport-border);
            background: var(--raport-surface);
        }

        @media (min-width: 768px) {
            .raport-evaluation-header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }
        }

        @media (min-width: 992px) {
            .raport-summary-card {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
            }

            .raport-summary-main {
                flex: 1 1 auto;
            }

            .raport-snapshot-box {
                flex: 0 0 430px;
                width: 430px;
            }
        }

        @media (max-width: 767.98px) {

            .raport-modal-header,
            .raport-modal-body,
            .raport-modal-footer {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .raport-modal-header-icon {
                display: none;
            }

            .raport-detail-item {
                flex-direction: column;
                gap: 0.25rem;
            }

            .raport-detail-item dd {
                max-width: 100%;
                text-align: left;
            }
        }
    </style>
    <div class="container-fluid px-3 px-xl-4 py-3 py-lg-4 academic-documents-page">

        {{-- =========================================================
        | HERO / PAGE INTRODUCTION
        ========================================================== --}}
        <section class="academic-hero mb-4">
            <div class="academic-hero-content">
                <div class="academic-hero-copy">
                    <div class="academic-eyebrow">
                        <span class="academic-eyebrow-icon">
                            <i class="bi bi-mortarboard"></i>
                        </span>

                        Administrasi Akademik
                    </div>

                    <h1 class="academic-page-title">
                        Dokumen Akademik
                    </h1>

                    <p class="academic-page-description">
                        Kelola snapshot, evaluasi, dan kesiapan Draft Raport semester secara
                        terstruktur sebelum masuk tahap pemeriksaan dan publikasi.
                    </p>

                    <div class="academic-hero-meta">
                        <span>
                            <i class="bi bi-shield-check"></i>
                            Snapshot terverifikasi
                        </span>

                        <span>
                            <i class="bi bi-arrow-repeat"></i>
                            Generate idempotent
                        </span>

                        <span>
                            <i class="bi bi-clock-history"></i>
                            Riwayat revisi tersimpan
                        </span>
                    </div>
                </div>

                <div class="academic-workflow">
                    <div class="academic-workflow-label">
                        Alur Dokumen
                    </div>

                    <div class="academic-workflow-steps">
                        <div class="academic-workflow-step is-active">
                            <span class="academic-workflow-number">1</span>

                            <div>
                                <strong>Draft</strong>
                                <small>Susun snapshot</small>
                            </div>
                        </div>

                        <div class="academic-workflow-divider"></div>

                        <div class="academic-workflow-step">
                            <span class="academic-workflow-number">2</span>

                            <div>
                                <strong>Review</strong>
                                <small>Periksa dokumen</small>
                            </div>
                        </div>

                        <div class="academic-workflow-divider"></div>

                        <div class="academic-workflow-step">
                            <span class="academic-workflow-number">3</span>

                            <div>
                                <strong>Publish</strong>
                                <small>Terbitkan Raport</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- =========================================================
        | FILTER PANEL
        ========================================================== --}}
        <section class="academic-filter-card mb-4">
            <div class="academic-section-header">
                <div class="academic-section-heading">
                    <span class="academic-section-icon">
                        <i class="bi bi-sliders"></i>
                    </span>

                    <div>
                        <h2>Filter Data</h2>
                        <p>
                            Tentukan semester, kelas, dan status dokumen yang ingin ditampilkan.
                        </p>
                    </div>
                </div>

                <button type="button" id="btnResetFilter" class="btn btn-sm academic-btn-ghost" title="Reset filter"
                    aria-label="Reset filter">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    Reset
                </button>
            </div>

            <div class="academic-filter-body">
                <div class="row g-3 align-items-end">

                    {{-- Semester --}}
                    <div class="col-12 col-xl-4">
                        <label for="filter_semester_id" class="academic-form-label">
                            <span>
                                <i class="bi bi-calendar3"></i>
                                Semester
                            </span>

                            <small>Wajib dipilih</small>
                        </label>

                        <select id="filter_semester_id" class="form-select academic-form-control"
                            {{ $semesterList->isEmpty() ? 'disabled' : '' }}>
                            @forelse ($semesterList as $semester)
                                @php
                                    $semesterName = \Illuminate\Support\Str::title(
                                        str_replace('_', ' ', $semester->nama ?? ''),
                                    );

                                    $academicYearName = \Illuminate\Support\Str::title(
                                        str_replace('_', ' ', $semester->tahunAjaran?->nama ?? '-'),
                                    );
                                @endphp

                                <option value="{{ $semester->id }}" @selected((int) $semester->id === (int) $defaultSemesterId)>
                                    {{ $semesterName }} — {{ $academicYearName }}
                                    {{ $semester->is_active ? '(Aktif)' : '' }}
                                </option>
                            @empty
                                <option value="">
                                    Belum Ada Semester
                                </option>
                            @endforelse
                        </select>
                    </div>

                    {{-- Kelas --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="filter_kelas_id" class="academic-form-label">
                            <span>
                                <i class="bi bi-building"></i>
                                Kelas
                            </span>
                        </label>

                        <select id="filter_kelas_id" class="form-select academic-form-control">
                            <option value="">
                                Semua Kelas
                            </option>

                            @foreach ($kelasList as $kelas)
                                <option value="{{ $kelas->id }}">
                                    {{ $kelas->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Dokumen --}}
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="filter_document_status" class="academic-form-label">
                            <span>
                                <i class="bi bi-file-earmark-check"></i>
                                Status Dokumen
                            </span>
                        </label>

                        <select id="filter_document_status" class="form-select academic-form-control">
                            <option value="">
                                Semua Status
                            </option>

                            <option value="none">
                                Belum Dibuat
                            </option>

                            <option value="draft">
                                Draft
                            </option>

                            <option value="review">
                                Menunggu Pemeriksaan
                            </option>

                            <option value="published">
                                Tersedia
                            </option>

                            <option value="revoked">
                                Dicabut
                            </option>

                            <option value="cancelled">
                                Dibatalkan
                            </option>
                        </select>
                    </div>

                    {{-- Apply Filter --}}
                    <div class="col-12 col-xl-2">
                        <button type="button" id="btnApplyFilter" class="btn academic-btn-primary w-100">
                            <i class="bi bi-funnel me-2"></i>
                            Terapkan Filter
                        </button>
                    </div>
                </div>

                <div class="academic-filter-helper">
                    <span class="academic-helper-icon">
                        <i class="bi bi-info-circle"></i>
                    </span>

                    <p>
                        Daftar santri bersumber dari placement semester. Tombol
                        <strong>Buat Draft</strong> akan membekukan progress santri menjadi
                        snapshot Raport pada semester yang dipilih.
                    </p>
                </div>
            </div>
        </section>

        {{-- =========================================================
        | DATA TABLE
        ========================================================== --}}
        <section class="academic-table-card">
            <div class="academic-table-header">
                <div class="academic-table-title">
                    <span class="academic-section-icon academic-section-icon-dark">
                        <i class="bi bi-file-earmark-text"></i>
                    </span>

                    <div>
                        <h2>Daftar Raport Semester</h2>
                        <p>
                            Kelola Draft Raport tanpa membuat dokumen duplikat.
                        </p>
                    </div>
                </div>

                <div class="academic-table-actions">
                    <span class="academic-table-status">
                        <i class="bi bi-circle-fill"></i>
                        Data langsung
                    </span>

                    <button type="button" id="btnReloadTable" class="btn academic-btn-secondary">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Muat Ulang
                    </button>
                </div>
            </div>

            <div class="academic-table-body">
                <div class="academic-table-note">
                    <div>
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>

                    <p>
                        Generate draft bersifat <strong>idempotent</strong>. Proses berulang
                        tidak membuat baris dokumen baru selama Draft aktif masih tersedia.
                    </p>
                </div>

                <div class="table-responsive academic-table-responsive">
                    <table id="academicDocumentsTable" class="table align-middle w-100 academic-data-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 56px;">
                                    No.
                                </th>

                                <th>
                                    Santri
                                </th>

                                <th>
                                    Placement Semester
                                </th>

                                <th class="text-center">
                                    Status Akademik
                                </th>

                                <th class="text-center">
                                    Status Dokumen
                                </th>

                                <th>
                                    Informasi Draft
                                </th>

                                <th class="text-center" style="min-width: 220px;">
                                    Aksi
                                </th>
                            </tr>
                        </thead>

                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="raportDraftModal" tabindex="-1" aria-labelledby="raportDraftModalLabel"
        aria-describedby="raportDraftModalDescription" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content raport-draft-modal border-0 shadow-lg">

                {{-- =====================================================
                | MODAL HEADER
                ====================================================== --}}
                <div class="modal-header raport-modal-header border-0">
                    <div class="d-flex align-items-start gap-3">
                        <div class="raport-modal-header-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>

                        <div>
                            <div class="raport-modal-eyebrow">
                                Preview Dokumen Akademik
                            </div>

                            <h5 class="modal-title fw-bold mb-1" id="raportDraftModalLabel">
                                Draft Raport Semester
                            </h5>

                            <p class="text-body-secondary small mb-0" id="raportDraftModalDescription">
                                Periksa snapshot, warning, dan evaluasi sebelum dokumen
                                diajukan ke tahap pemeriksaan.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup"></button>
                </div>

                {{-- =====================================================
                | MODAL BODY
                ====================================================== --}}
                <div class="modal-body raport-modal-body">

                    {{-- Loading state --}}
                    <div id="draftModalLoading" class="raport-modal-loading text-center">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">
                                Memuat...
                            </span>
                        </div>

                        <div class="fw-semibold mb-1">
                            Memuat snapshot Raport
                        </div>

                        <div class="small text-body-secondary">
                            Mohon tunggu sebentar.
                        </div>
                    </div>

                    {{-- Main content --}}
                    <div id="draftModalContent" class="d-none">

                        {{-- =================================================
                        | DOCUMENT SUMMARY
                        ================================================== --}}
                        <section class="raport-summary-card mb-4">
                            <div class="raport-summary-main">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                    <span id="previewStatusBadge"></span>

                                    <span
                                        class="badge rounded-pill
                                            bg-secondary-subtle
                                            text-secondary-emphasis">
                                        <i class="bi bi-layers me-1"></i>
                                        Revisi
                                        <span id="previewRevision">-</span>
                                    </span>
                                </div>

                                <h2 class="raport-student-name" id="previewStudentName">
                                    -
                                </h2>

                                <div class="raport-semester-label">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <span id="previewSemesterLabel">-</span>
                                </div>
                            </div>

                            <div class="raport-snapshot-box">
                                <div class="raport-snapshot-label">
                                    <i class="bi bi-shield-check me-1"></i>
                                    Snapshot SHA-256
                                </div>

                                <code id="previewSnapshotHash">
                                    -
                                </code>
                            </div>
                        </section>

                        {{-- =================================================
                        | WARNINGS
                        ================================================== --}}
                        <section class="mb-4">
                            <div class="raport-section-heading">
                                <div>
                                    <h3 class="raport-section-title">
                                        Pemeriksaan Snapshot
                                    </h3>

                                    <p class="raport-section-description">
                                        Informasi yang perlu diperiksa sebelum dokumen
                                        dapat dipublikasikan.
                                    </p>
                                </div>
                            </div>

                            <div id="previewWarnings"></div>
                        </section>

                        {{-- =================================================
                        | BASIC INFORMATION
                        ================================================== --}}
                        <section class="mb-4">
                            <div class="raport-section-heading">
                                <div>
                                    <h3 class="raport-section-title">
                                        Informasi Dokumen
                                    </h3>

                                    <p class="raport-section-description">
                                        Identitas, semester, serta placement yang tersimpan
                                        dalam snapshot.
                                    </p>
                                </div>
                            </div>

                            <div class="row g-3">

                                {{-- Identitas Santri --}}
                                <div class="col-12 col-lg-4">
                                    <article class="raport-info-card h-100">
                                        <div class="raport-info-card-header">
                                            <div class="raport-info-icon icon-purple">
                                                <i class="bi bi-person-badge"></i>
                                            </div>

                                            <div>
                                                <h4 class="raport-info-title">
                                                    Identitas Santri
                                                </h4>

                                                <div class="raport-info-subtitle">
                                                    Data pemilik Raport
                                                </div>
                                            </div>
                                        </div>

                                        <dl class="raport-detail-list mb-0">
                                            <div class="raport-detail-item">
                                                <dt>NIS</dt>
                                                <dd id="previewNis">-</dd>
                                            </div>

                                            <div class="raport-detail-item">
                                                <dt>Tanggal Lahir</dt>
                                                <dd id="previewBirthDate">-</dd>
                                            </div>

                                            <div class="raport-detail-item">
                                                <dt>Jenis Kelamin</dt>
                                                <dd id="previewGender">-</dd>
                                            </div>
                                        </dl>
                                    </article>
                                </div>

                                {{-- Semester --}}
                                <div class="col-12 col-lg-4">
                                    <article class="raport-info-card h-100">
                                        <div class="raport-info-card-header">
                                            <div class="raport-info-icon icon-blue">
                                                <i class="bi bi-calendar3"></i>
                                            </div>

                                            <div>
                                                <h4 class="raport-info-title">
                                                    Semester
                                                </h4>

                                                <div class="raport-info-subtitle">
                                                    Periode dokumen
                                                </div>
                                            </div>
                                        </div>

                                        <dl class="raport-detail-list mb-0">
                                            <div class="raport-detail-item">
                                                <dt>Status</dt>
                                                <dd id="previewSemesterStatus">-</dd>
                                            </div>

                                            <div class="raport-detail-item">
                                                <dt>Periode</dt>
                                                <dd id="previewSemesterRange">-</dd>
                                            </div>

                                            <div class="raport-detail-item">
                                                <dt>Siap Publikasi</dt>
                                                <dd id="previewPublicationReady">-</dd>
                                            </div>
                                        </dl>
                                    </article>
                                </div>

                                {{-- Placement --}}
                                <div class="col-12 col-lg-4">
                                    <article class="raport-info-card h-100">
                                        <div class="raport-info-card-header">
                                            <div class="raport-info-icon icon-green">
                                                <i class="bi bi-diagram-3"></i>
                                            </div>

                                            <div>
                                                <h4 class="raport-info-title">
                                                    Placement
                                                </h4>

                                                <div class="raport-info-subtitle">
                                                    Kelas dan pembimbing
                                                </div>
                                            </div>
                                        </div>

                                        <dl class="raport-detail-list mb-0">
                                            <div class="raport-detail-item">
                                                <dt>Kelas</dt>
                                                <dd id="previewClass">-</dd>
                                            </div>

                                            <div class="raport-detail-item">
                                                <dt>Musyrif</dt>
                                                <dd id="previewMusyrif">-</dd>
                                            </div>

                                            <div class="raport-detail-item">
                                                <dt>Status</dt>
                                                <dd id="previewPlacementStatus">-</dd>
                                            </div>
                                        </dl>
                                    </article>
                                </div>
                            </div>
                        </section>

                        {{-- =================================================
                        | PROGRESS SUMMARY
                        ================================================== --}}
                        <section class="mb-4">
                            <div class="raport-section-heading">
                                <div>
                                    <h3 class="raport-section-title">
                                        Ringkasan Progress
                                    </h3>

                                    <p class="raport-section-description">
                                        Aktivitas semester dan capaian kumulatif sampai
                                        akhir periode Raport.
                                    </p>
                                </div>
                            </div>

                            <div class="row g-3">

                                {{-- Hafalan --}}
                                <div class="col-12 col-md-6 col-xl-4">
                                    <article class="raport-progress-card h-100">
                                        <div class="raport-progress-top">
                                            <div class="raport-domain-icon domain-hafalan">
                                                <i class="bi bi-book"></i>
                                            </div>

                                            <div>
                                                <h4 class="raport-progress-title">
                                                    Hafalan
                                                </h4>

                                                <div class="raport-progress-subtitle">
                                                    Aktivitas setoran semester
                                                </div>
                                            </div>
                                        </div>

                                        <div class="raport-progress-number">
                                            <span id="previewHafalanRecords">0</span>
                                            <small>record</small>
                                        </div>

                                        <div class="raport-progress-divider"></div>

                                        <div class="raport-progress-metrics">
                                            <div>
                                                <span>Nilai rata-rata</span>
                                                <strong id="previewHafalanScore">-</strong>
                                            </div>

                                            <div>
                                                <span>Capaian kumulatif</span>
                                                <strong id="previewHafalanProgress">0%</strong>
                                            </div>
                                        </div>
                                    </article>
                                </div>

                                {{-- Tahsin --}}
                                <div class="col-12 col-md-6 col-xl-4">
                                    <article class="raport-progress-card h-100">
                                        <div class="raport-progress-top">
                                            <div class="raport-domain-icon domain-tahsin">
                                                <i class="bi bi-journal-check"></i>
                                            </div>

                                            <div>
                                                <h4 class="raport-progress-title">
                                                    Tahsin
                                                </h4>

                                                <div class="raport-progress-subtitle">
                                                    Pembelajaran bacaan
                                                </div>
                                            </div>
                                        </div>

                                        <div class="raport-progress-number">
                                            <span id="previewTahsinRecords">0</span>
                                            <small>record</small>
                                        </div>

                                        <div class="raport-progress-divider"></div>

                                        <div class="raport-progress-metrics">
                                            <div>
                                                <span>Nilai rata-rata</span>
                                                <strong id="previewTahsinScore">-</strong>
                                            </div>

                                            <div>
                                                <span>Capaian kumulatif</span>
                                                <strong id="previewTahsinProgress">0%</strong>
                                            </div>
                                        </div>
                                    </article>
                                </div>

                                {{-- Tilawah --}}
                                <div class="col-12 col-md-6 col-xl-4">
                                    <article class="raport-progress-card h-100">
                                        <div class="raport-progress-top">
                                            <div class="raport-domain-icon domain-tilawah">
                                                <i class="bi bi-moon-stars"></i>
                                            </div>

                                            <div>
                                                <h4 class="raport-progress-title">
                                                    Tilawah
                                                </h4>

                                                <div class="raport-progress-subtitle">
                                                    Progress bacaan Al-Qur'an
                                                </div>
                                            </div>
                                        </div>

                                        <div class="raport-progress-number">
                                            <span id="previewTilawahRecords">0</span>
                                            <small>record</small>
                                        </div>

                                        <div class="raport-progress-divider"></div>

                                        <div class="raport-progress-metrics">
                                            <div>
                                                <span>Juz tertinggi</span>
                                                <strong id="previewTilawahJuz">0</strong>
                                            </div>

                                            <div>
                                                <span>Capaian kumulatif</span>
                                                <strong id="previewTilawahProgress">0%</strong>
                                            </div>
                                        </div>
                                    </article>
                                </div>
                            </div>
                        </section>

                        {{-- =================================================
                        | EVALUATION FORM
                        ================================================== --}}
                        <section class="raport-evaluation-card">
                            <div class="raport-evaluation-header">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="raport-evaluation-icon">
                                        <i class="bi bi-pencil-square"></i>
                                    </div>

                                    <div>
                                        <h3 class="raport-section-title mb-1">
                                            Evaluasi Draft Raport
                                        </h3>

                                        <p class="raport-section-description mb-0">
                                            Data manual tetap dipertahankan ketika snapshot
                                            diregenerate.
                                        </p>
                                    </div>
                                </div>

                                <span id="draftEditabilityBadge" class="badge rounded-pill"></span>
                            </div>

                            <form id="draftEvaluationForm">
                                <input type="hidden" id="draftUpdateUrl">

                                <div class="row g-3">

                                    {{-- Predikat --}}
                                    <div class="col-12 col-lg-4">
                                        <label for="draftPredikat" class="form-label raport-form-label">
                                            Predikat
                                        </label>

                                        <select id="draftPredikat" name="predikat"
                                            class="form-select raport-form-control">
                                            <option value="">
                                                Pilih predikat...
                                            </option>
                                            <option value="mumtaz">
                                                Mumtaz
                                            </option>
                                            <option value="jayyid_jiddan">
                                                Jayyid Jiddan
                                            </option>
                                            <option value="jayyid">
                                                Jayyid
                                            </option>
                                            <option value="mardud">
                                                Mardud
                                            </option>
                                        </select>

                                        <div class="form-text">
                                            Gunakan kategori yang sama dengan penilaian Hafalan.
                                        </div>
                                    </div>

                                    {{-- Catatan Musyrif --}}
                                    <div class="col-12 col-lg-8">
                                        <label for="draftCatatanMusyrif" class="form-label raport-form-label">
                                            Catatan Musyrif
                                        </label>

                                        <textarea id="draftCatatanMusyrif" name="catatan_musyrif" class="form-control raport-form-control" rows="4"
                                            maxlength="5000" placeholder="Tuliskan catatan pembinaan dari musyrif..."></textarea>
                                    </div>

                                    {{-- Catatan Admin --}}
                                    <div class="col-12 col-lg-6">
                                        <label for="draftCatatanAdmin" class="form-label raport-form-label">
                                            Catatan Admin
                                        </label>

                                        <textarea id="draftCatatanAdmin" name="catatan_admin" class="form-control raport-form-control" rows="4"
                                            maxlength="5000" placeholder="Tuliskan catatan pemeriksaan administratif..."></textarea>
                                    </div>

                                    {{-- Rekomendasi --}}
                                    <div class="col-12 col-lg-6">
                                        <label for="draftRekomendasi" class="form-label raport-form-label">
                                            Rekomendasi Semester Berikutnya
                                        </label>

                                        <textarea id="draftRekomendasi" name="rekomendasi" class="form-control raport-form-control" rows="4"
                                            maxlength="5000" placeholder="Tuliskan rekomendasi pembinaan berikutnya..."></textarea>
                                    </div>
                                </div>
                            </form>
                        </section>
                    </div>
                </div>

                {{-- =====================================================
                | MODAL FOOTER
                ====================================================== --}}
                <div class="modal-footer raport-modal-footer">
                    <button type="button" class="btn btn-light border" data-coreui-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>
                        Tutup
                    </button>

                    <button type="button" id="btnSaveDraftEvaluation" class="btn btn-primary d-none">
                        <i class="bi bi-check2-circle me-1"></i>
                        Simpan Evaluasi
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            'use strict';

            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const urls = {
                data: @json(route('admin.academic-documents.data')),
                generate: @json(route('admin.academic-documents.raport.draft.generate')),
            };

            const semesterSelect = document.getElementById('filter_semester_id');
            const kelasSelect = document.getElementById('filter_kelas_id');
            const statusSelect = document.getElementById('filter_document_status');

            let raportDraftModalInstance = null;

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
            });

            /* =========================================================
             * MODAL
             * ======================================================= */

            function getRaportDraftModalInstance() {
                if (raportDraftModalInstance) {
                    return raportDraftModalInstance;
                }

                const modalElement = document.getElementById('raportDraftModal');

                if (!modalElement) {
                    console.error(
                        'Modal #raportDraftModal tidak ditemukan pada DOM.'
                    );

                    showError(
                        'Komponen modal Draft Raport tidak ditemukan.'
                    );

                    return null;
                }

                if (!window.coreui || !window.coreui.Modal) {
                    console.error(
                        'CoreUI Modal belum tersedia.'
                    );

                    showError(
                        'Library CoreUI Modal belum dimuat.'
                    );

                    return null;
                }

                raportDraftModalInstance =
                    window.coreui.Modal.getOrCreateInstance(
                        modalElement, {
                            backdrop: 'static',
                            keyboard: false,
                            focus: true,
                        }
                    );

                return raportDraftModalInstance;
            }

            function showRaportDraftModal() {
                const modal = getRaportDraftModalInstance();

                if (!modal) {
                    return false;
                }

                modal.show();

                return true;
            }

            function hideRaportDraftModal() {
                const modalElement = document.getElementById('raportDraftModal');

                if (!modalElement || !window.coreui?.Modal) {
                    return;
                }

                const modal =
                    window.coreui.Modal.getInstance(modalElement);

                modal?.hide();
            }

            /* =========================================================
             * DATATABLE
             * ======================================================= */

            const table = $('#academicDocumentsTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                searchDelay: 450,
                pageLength: 25,

                order: [
                    [1, 'asc'],
                ],

                ajax: {
                    url: urls.data,

                    data: function(payload) {
                        payload.semester_id =
                            semesterSelect?.value || '';

                        payload.kelas_id =
                            kelasSelect?.value || '';

                        payload.document_status =
                            statusSelect?.value || '';
                    },

                    error: function(xhr) {
                        showAjaxError(xhr);
                    },
                },

                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                    },
                    {
                        data: 'santri_display',
                        name: 'santri_nama',
                    },
                    {
                        data: 'placement_display',
                        name: 'kelas.nama_kelas',
                    },
                    {
                        data: 'placement_status_badge',
                        name: 'placements.status',
                        className: 'text-center',
                    },
                    {
                        data: 'document_status_badge',
                        name: 'documents.status',
                        className: 'text-center',
                    },
                    {
                        data: 'document_info',
                        name: 'documents.generated_at',
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                    },
                ],

                language: {
                    processing: 'Memuat data...',
                    search: 'Cari Santri:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Tidak ada data',
                    zeroRecords: 'Data tidak ditemukan',
                    emptyTable: 'Belum ada placement pada semester ini',

                    paginate: {
                        previous: 'Sebelumnya',
                        next: 'Berikutnya',
                    },
                },
            });

            /* =========================================================
             * FILTER EVENTS
             * ======================================================= */

            document
                .getElementById('btnApplyFilter')
                ?.addEventListener('click', function() {
                    table.ajax.reload();
                });

            document
                .getElementById('btnResetFilter')
                ?.addEventListener('click', function() {
                    if (kelasSelect) {
                        kelasSelect.value = '';
                    }

                    if (statusSelect) {
                        statusSelect.value = '';
                    }

                    table.ajax.reload();
                });

            document
                .getElementById('btnReloadTable')
                ?.addEventListener('click', function() {
                    table.ajax.reload(null, false);
                });

            semesterSelect?.addEventListener('change', function() {
                table.ajax.reload();
            });

            /* =========================================================
             * GENERATE DRAFT
             * ======================================================= */

            $('#academicDocumentsTable').on(
                'click',
                '.js-generate-draft',
                async function() {
                    const button = this;

                    const confirmed = await confirmAction({
                        title: 'Buat Draft Raport?',
                        text: 'Data progress akan dibekukan menjadi snapshot Draft Raport.',
                        confirmText: 'Ya, Buat Draft',
                        icon: 'question',
                    });

                    if (!confirmed) {
                        return;
                    }

                    setButtonLoading(
                        button,
                        true,
                        'Membuat...'
                    );

                    $.ajax({
                            url: urls.generate,
                            method: 'POST',

                            data: {
                                santri_id: button.dataset.santriId,

                                semester_id: button.dataset.semesterId,
                            },
                        })
                        .done(function(response) {
                            showSuccess(
                                response.message,
                                response.warnings
                            );

                            table.ajax.reload(null, false);
                        })
                        .fail(showAjaxError)
                        .always(function() {
                            setButtonLoading(
                                button,
                                false
                            );
                        });
                }
            );

            /* =========================================================
             * REGENERATE DRAFT
             * ======================================================= */

            $('#academicDocumentsTable').on(
                'click',
                '.js-regenerate-draft',
                async function() {
                    const button = this;

                    const confirmed = await confirmAction({
                        title: 'Regenerate Snapshot?',
                        text: 'Snapshot akan diperbarui dari data terbaru. Catatan manual tetap dipertahankan.',
                        confirmText: 'Ya, Regenerate',
                        icon: 'warning',
                    });

                    if (!confirmed) {
                        return;
                    }

                    setButtonLoading(
                        button,
                        true,
                        'Memproses...'
                    );

                    $.ajax({
                            url: button.dataset.url,
                            method: 'POST',
                            data: {},
                        })
                        .done(function(response) {
                            showSuccess(
                                response.message,
                                response.warnings
                            );

                            table.ajax.reload(null, false);
                        })
                        .fail(showAjaxError)
                        .always(function() {
                            setButtonLoading(
                                button,
                                false
                            );
                        });
                }
            );

            /* =========================================================
             * CANCEL DRAFT
             * ======================================================= */

            $('#academicDocumentsTable').on(
                'click',
                '.js-cancel-draft',
                async function() {
                    const button = this;

                    const reason =
                        await requestCancellationReason();

                    if (!reason) {
                        return;
                    }

                    setButtonLoading(
                        button,
                        true,
                        'Membatalkan...'
                    );

                    $.ajax({
                            url: button.dataset.url,
                            method: 'PATCH',

                            data: {
                                cancellation_reason: reason,
                            },
                        })
                        .done(function(response) {
                            showSuccess(
                                response.message
                            );

                            table.ajax.reload(
                                null,
                                false
                            );
                        })
                        .fail(showAjaxError)
                        .always(function() {
                            setButtonLoading(
                                button,
                                false
                            );
                        });
                }
            );

            /* =========================================================
             * PREVIEW
             * ======================================================= */

            $('#academicDocumentsTable').on(
                'click',
                '.js-preview-document',
                function() {
                    openPreview({
                        showUrl: this.dataset.showUrl,
                        updateUrl: this.dataset.updateUrl,
                    });
                }
            );

            function openPreview(options) {
                resetPreview();

                const updateUrlInput =
                    document.getElementById('draftUpdateUrl');

                if (updateUrlInput) {
                    updateUrlInput.value =
                        options.updateUrl || '';
                }

                if (!showRaportDraftModal()) {
                    return;
                }

                $.ajax({
                        url: options.showUrl,
                        method: 'GET',
                    })
                    .done(function(response) {
                        renderPreview(
                            response.document
                        );
                    })
                    .fail(function(xhr) {
                        hideRaportDraftModal();
                        showAjaxError(xhr);
                    });
            }

            function renderPreview(documentData) {
                const snapshot =
                    documentData.snapshot || {};

                const student =
                    snapshot.student || {};

                const semester =
                    snapshot.semester || {};

                const placement =
                    snapshot.placement || {};

                const recordCounts =
                    snapshot.record_counts
                    ?.semester_activity || {};

                const hafalanActivity =
                    snapshot.hafalan
                    ?.semester_activity || {};

                const hafalanAchievement =
                    snapshot.hafalan
                    ?.cumulative_achievement || {};

                const tahsinActivity =
                    snapshot.tahsin
                    ?.semester_activity || {};

                const tahsinAchievement =
                    snapshot.tahsin
                    ?.cumulative_achievement || {};

                const tilawahAchievement =
                    snapshot.tilawah
                    ?.cumulative_achievement || {};

                setText(
                    'previewStudentName',
                    student.nama
                );

                setText(
                    'previewSemesterLabel',
                    semester.label
                );

                setText(
                    'previewSnapshotHash',
                    documentData.snapshot_sha256
                );

                setText(
                    'previewRevision',
                    documentData.revision
                );

                setText(
                    'previewNis',
                    student.nis
                );

                setText(
                    'previewBirthDate',
                    formatDate(student.tanggal_lahir)
                );

                setText(
                    'previewGender',
                    genderLabel(student.jenis_kelamin)
                );

                setText(
                    'previewSemesterStatus',
                    titleCase(semester.status)
                );

                setText(
                    'previewSemesterRange',
                    [
                        formatDate(
                            semester.tanggal_mulai
                        ),
                        formatDate(
                            semester.tanggal_selesai
                        ),
                    ].join(' — ')
                );

                setHtml(
                    'previewPublicationReady',
                    booleanBadge(
                        semester.lifecycle
                        ?.is_ready_for_publication,

                        'Siap',
                        'Belum'
                    )
                );

                setText(
                    'previewClass',
                    placement.kelas?.nama
                );

                setText(
                    'previewMusyrif',
                    placement.musyrif?.nama
                );

                setText(
                    'previewPlacementStatus',
                    titleCase(placement.status)
                );

                setText(
                    'previewHafalanRecords',
                    recordCounts.hafalan ?? 0
                );

                setText(
                    'previewHafalanScore',
                    nullableScore(
                        hafalanActivity.avg_nilai
                    )
                );

                setText(
                    'previewHafalanProgress',
                    percentage(
                        hafalanAchievement.overall_pct
                    )
                );

                setText(
                    'previewTahsinRecords',
                    recordCounts.tahsin ?? 0
                );

                setText(
                    'previewTahsinScore',
                    nullableScore(
                        tahsinActivity.avg_nilai
                    )
                );

                setText(
                    'previewTahsinProgress',
                    percentage(
                        tahsinAchievement.overall_pct
                    )
                );

                setText(
                    'previewTilawahRecords',
                    recordCounts.tilawah ?? 0
                );

                setText(
                    'previewTilawahJuz',
                    tilawahAchievement.max_juz ?? 0
                );

                setText(
                    'previewTilawahProgress',
                    percentage(
                        tilawahAchievement.overall_pct
                    )
                );

                setHtml(
                    'previewStatusBadge',
                    documentStatusBadge(
                        documentData.status,
                        documentData.status_label
                    )
                );

                renderWarnings(
                    documentData.metadata
                    ?.snapshot_warnings || [],
                    documentData
                );

                setFormValue(
                    'draftPredikat',
                    documentData.predikat
                );

                setFormValue(
                    'draftCatatanMusyrif',
                    documentData.catatan_musyrif
                );

                setFormValue(
                    'draftCatatanAdmin',
                    documentData.catatan_admin
                );

                setFormValue(
                    'draftRekomendasi',
                    documentData.rekomendasi
                );

                const editable =
                    documentData.status === 'draft' &&
                    documentData.is_current === true;

                setEvaluationEditable(editable);

                document
                    .getElementById('draftModalLoading')
                    ?.classList
                    .add('d-none');

                document
                    .getElementById('draftModalContent')
                    ?.classList
                    .remove('d-none');
            }

            function resetPreview() {
                document
                    .getElementById('draftModalLoading')
                    ?.classList
                    .remove('d-none');

                document
                    .getElementById('draftModalContent')
                    ?.classList
                    .add('d-none');

                document
                    .getElementById('draftEvaluationForm')
                    ?.reset();

                document
                    .getElementById('btnSaveDraftEvaluation')
                    ?.classList
                    .add('d-none');

                const warningContainer =
                    document.getElementById('previewWarnings');

                if (warningContainer) {
                    warningContainer.innerHTML = '';
                }
            }

            /* =========================================================
             * SAVE EVALUATION
             * ======================================================= */

            document
                .getElementById('btnSaveDraftEvaluation')
                ?.addEventListener('click', function() {
                    const button = this;

                    const updateUrl =
                        document
                        .getElementById('draftUpdateUrl')
                        ?.value;

                    if (!updateUrl) {
                        showError(
                            'URL penyimpanan evaluasi tidak ditemukan.'
                        );

                        return;
                    }

                    setButtonLoading(
                        button,
                        true,
                        'Menyimpan...'
                    );

                    $.ajax({
                            url: updateUrl,
                            method: 'PUT',

                            data: {
                                predikat: getFormValue(
                                    'draftPredikat'
                                ),

                                catatan_musyrif: getFormValue(
                                    'draftCatatanMusyrif'
                                ),

                                catatan_admin: getFormValue(
                                    'draftCatatanAdmin'
                                ),

                                rekomendasi: getFormValue(
                                    'draftRekomendasi'
                                ),
                            },
                        })
                        .done(function(response) {
                            showSuccess(
                                response.message
                            );

                            table.ajax.reload(
                                null,
                                false
                            );
                        })
                        .fail(showAjaxError)
                        .always(function() {
                            setButtonLoading(
                                button,
                                false
                            );
                        });
                });

            /* =========================================================
             * FORM EDITABILITY
             * ======================================================= */

            function setEvaluationEditable(editable) {
                const fields =
                    document.querySelectorAll(
                        '#draftEvaluationForm input:not([type="hidden"]), ' +
                        '#draftEvaluationForm textarea'
                    );

                fields.forEach(function(field) {
                    field.disabled = !editable;
                });

                const saveButton =
                    document.getElementById(
                        'btnSaveDraftEvaluation'
                    );

                const badge =
                    document.getElementById(
                        'draftEditabilityBadge'
                    );

                if (editable) {
                    saveButton?.classList.remove(
                        'd-none'
                    );

                    if (badge) {
                        badge.className =
                            'badge rounded-pill ' +
                            'bg-success-subtle ' +
                            'text-success-emphasis';

                        badge.textContent =
                            'Dapat Diedit';
                    }

                    return;
                }

                saveButton?.classList.add(
                    'd-none'
                );

                if (badge) {
                    badge.className =
                        'badge rounded-pill ' +
                        'bg-secondary-subtle ' +
                        'text-secondary-emphasis';

                    badge.textContent =
                        'Terkunci';
                }
            }

            /* =========================================================
             * WARNING RENDER
             * ======================================================= */

            function renderWarnings(
                warnings,
                documentData = {}
            ) {
                const container =
                    document.getElementById(
                        'previewWarnings'
                    );

                if (!container) {
                    return;
                }

                const cancellationHtml =
                    documentData.status === 'cancelled' ?
                    `
                            <div class="alert alert-danger border-0 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-x-circle-fill mt-1"></i>

                                    <div>
                                        <div class="fw-bold mb-1">
                                            Draft Raport Dibatalkan
                                        </div>

                                        <div class="small mb-1">
                                            ${escapeHtml(
                                                documentData.cancellation_reason
                                                || 'Tidak ada alasan pembatalan.'
                                            )}
                                        </div>

                                        <div class="small opacity-75">
                                            Dibatalkan:
                                            ${escapeHtml(
                                                formatDateTime(
                                                    documentData.cancelled_at
                                                )
                                            )}

                                            ${documentData.cancelled_by?.name
                                                ? ` · Oleh ${escapeHtml(
                                                        documentData.cancelled_by.name
                                                    )}`
                                                : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ` :
                    '';

                if (
                    !Array.isArray(warnings) ||
                    warnings.length === 0
                ) {
                    container.innerHTML =
                        cancellationHtml +
                        `
                            <div class="alert alert-success border-0 mb-0">
                                <i class="bi bi-check-circle me-2"></i>
                                Tidak ada warning pada snapshot ini.
                            </div>
                        `;

                    return;
                }

                const items = warnings
                    .map(function(warning) {
                        return `
                            <div class="preview-warning-item">
                                <i class="bi bi-exclamation-triangle-fill text-warning"></i>

                                <div>
                                    ${escapeHtml(
                                        warning.message || '-'
                                    )}
                                </div>
                            </div>
                        `;
                    })
                    .join('');

                container.innerHTML =
                    cancellationHtml +
                    `
                        <div class="preview-warning-list">
                            ${items}
                        </div>
                    `;
            }

            /* =========================================================
             * BADGES
             * ======================================================= */

            function documentStatusBadge(status, label) {
                const classes = {
                    draft: 'bg-warning-subtle text-warning-emphasis',

                    review: 'bg-info-subtle text-info-emphasis',

                    published: 'bg-success-subtle text-success-emphasis',

                    revoked: 'bg-danger-subtle text-danger-emphasis',

                    cancelled: 'bg-secondary-subtle text-secondary-emphasis',
                };

                return `
                    <span
                        class="badge rounded-pill ${
                            classes[status] ||
                            'bg-secondary-subtle text-secondary-emphasis'
                        }"
                    >
                        ${escapeHtml(
                            label || titleCase(status)
                        )}
                    </span>
                `;
            }

            function booleanBadge(
                value,
                trueLabel,
                falseLabel
            ) {
                if (value) {
                    return `
                        <span
                            class="badge rounded-pill
                            bg-success-subtle
                            text-success-emphasis"
                        >
                            ${escapeHtml(trueLabel)}
                        </span>
                    `;
                }

                return `
                    <span
                        class="badge rounded-pill
                        bg-warning-subtle
                        text-warning-emphasis"
                    >
                        ${escapeHtml(falseLabel)}
                    </span>
                `;
            }

            /* =========================================================
             * BUTTON LOADING
             * ======================================================= */

            function setButtonLoading(
                button,
                loading,
                loadingText = 'Memproses...'
            ) {
                if (!button) {
                    return;
                }

                if (loading) {
                    button.dataset.originalHtml =
                        button.innerHTML;

                    button.disabled = true;

                    button.innerHTML = `
                        <span
                            class="spinner-border
                            spinner-border-sm me-1"
                        ></span>

                        ${escapeHtml(loadingText)}
                    `;

                    return;
                }

                button.disabled = false;

                if (button.dataset.originalHtml) {
                    button.innerHTML =
                        button.dataset.originalHtml;

                    delete button.dataset.originalHtml;
                }
            }

            /* =========================================================
             * DIALOG
             * ======================================================= */

            async function confirmAction(options) {
                if (!window.Swal) {
                    return window.confirm(
                        options.text ||
                        options.title
                    );
                }

                const result = await Swal.fire({
                    title: options.title,
                    text: options.text,
                    icon: options.icon || 'question',
                    showCancelButton: true,
                    confirmButtonText: options.confirmText || 'Ya',
                    cancelButtonText: 'Batal',
                    reverseButtons: true,
                });

                return result.isConfirmed;
            }

            async function requestCancellationReason() {
                if (window.Swal) {
                    const result = await Swal.fire({
                        icon: 'warning',
                        title: 'Batalkan Draft Raport?',
                        html: `
                            <div class="text-start small">
                                Draft tidak dihapus permanen. Statusnya akan
                                menjadi <strong>Dibatalkan</strong> dan histori
                                tetap tersimpan.
                            </div>
                        `,
                        input: 'textarea',
                        inputLabel: 'Alasan pembatalan',
                        inputPlaceholder: 'Contoh: Draft dibuat untuk testing atau salah memilih semester.',
                        inputAttributes: {
                            maxlength: 2000,
                            rows: 4,
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Batalkan Draft',
                        cancelButtonText: 'Kembali',
                        confirmButtonColor: '#dc3545',
                        reverseButtons: true,

                        inputValidator: function(value) {
                            const reason =
                                String(value || '').trim();

                            if (reason.length < 5) {
                                return 'Alasan pembatalan minimal 5 karakter.';
                            }

                            return undefined;
                        },
                    });

                    if (!result.isConfirmed) {
                        return null;
                    }

                    return String(
                        result.value || ''
                    ).trim();
                }

                const value = window.prompt(
                    'Masukkan alasan pembatalan Draft Raport:'
                );

                if (value === null) {
                    return null;
                }

                const reason =
                    String(value).trim();

                if (reason.length < 5) {
                    showError(
                        'Alasan pembatalan minimal 5 karakter.'
                    );

                    return null;
                }

                return reason;
            }

            function showSuccess(
                message,
                warnings = []
            ) {
                const warningList =
                    Array.isArray(warnings) ?
                    warnings
                    .map(function(item) {
                        return `
                            <li>
                                ${escapeHtml(
                                    item.message || '-'
                                )}
                            </li>
                        `;
                    })
                    .join('') :
                    '';

                const html = warningList ?
                    `
                        <div class="text-start">
                            <div class="mb-2">
                                ${escapeHtml(
                                    message || 'Berhasil.'
                                )}
                            </div>

                            <div class="small fw-semibold mb-1">
                                Warning snapshot:
                            </div>

                            <ul class="small mb-0">
                                ${warningList}
                            </ul>
                        </div>
                    ` :
                    escapeHtml(
                        message || 'Berhasil.'
                    );

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        html: html,
                    });

                    return;
                }

                window.alert(
                    message || 'Berhasil.'
                );
            }

            function showError(message) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Proses Gagal',
                        text: message,
                    });

                    return;
                }

                window.alert(message);
            }

            function showAjaxError(xhr) {
                const response =
                    xhr?.responseJSON || {};

                let message =
                    response.message ||
                    'Terjadi kesalahan pada server.';

                if (response.errors) {
                    const errors = Object
                        .values(response.errors)
                        .flat()
                        .filter(Boolean);

                    if (errors.length > 0) {
                        message = errors.join('\n');
                    }
                }

                showError(message);
            }

            /* =========================================================
             * UTILITIES
             * ======================================================= */

            function setText(id, value) {
                const element =
                    document.getElementById(id);

                if (!element) {
                    return;
                }

                element.textContent =
                    value === null ||
                    value === undefined ||
                    value === '' ?
                    '-' :
                    String(value);
            }

            function setHtml(id, html) {
                const element =
                    document.getElementById(id);

                if (element) {
                    element.innerHTML = html;
                }
            }

            function setFormValue(id, value) {
                const element =
                    document.getElementById(id);

                if (element) {
                    element.value =
                        value ?? '';
                }
            }

            function getFormValue(id) {
                return document
                    .getElementById(id)
                    ?.value ?? '';
            }

            function nullableScore(value) {
                if (
                    value === null ||
                    value === undefined ||
                    value === ''
                ) {
                    return 'Belum Dinilai';
                }

                return value;
            }

            function percentage(value) {
                return `${Number(value || 0)}%`;
            }

            function genderLabel(value) {
                const normalized =
                    String(value || '')
                    .toLowerCase();

                if (
                    [
                        'l',
                        'laki-laki',
                        'laki_laki',
                    ].includes(normalized)
                ) {
                    return 'Laki-laki';
                }

                if (
                    [
                        'p',
                        'perempuan',
                    ].includes(normalized)
                ) {
                    return 'Perempuan';
                }

                return '-';
            }

            function titleCase(value) {
                return String(value || '-')
                    .replaceAll('_', ' ')
                    .replace(
                        /\b\w/g,
                        function(letter) {
                            return letter.toUpperCase();
                        }
                    );
            }

            function formatDate(value) {
                if (!value) {
                    return '-';
                }

                const date =
                    new Date(`${value}T00:00:00`);

                if (
                    Number.isNaN(
                        date.getTime()
                    )
                ) {
                    return value;
                }

                return new Intl.DateTimeFormat(
                    'id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                    }
                ).format(date);
            }

            function formatDateTime(value) {
                if (!value) {
                    return '-';
                }

                const date = new Date(value);

                if (
                    Number.isNaN(
                        date.getTime()
                    )
                ) {
                    return value;
                }

                return new Intl.DateTimeFormat(
                    'id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                    }
                ).format(date);
            }

            function escapeHtml(value) {
                const div =
                    document.createElement('div');

                div.textContent =
                    value ?? '';

                return div.innerHTML;
            }
        });
    </script>
@endpush
