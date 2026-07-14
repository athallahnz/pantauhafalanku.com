@extends('layouts.app')

@section('title', 'Executive Dashboard Departemen Al-Qur\'an')

@section('content')
    @php
        $d = $dashboard;
        $healthTone =
            [
                'good' => 'success',
                'attention' => 'warning',
                'critical' => 'danger',
            ][$d['health']['status']] ?? 'secondary';

        $performanceLabel = static fn(string $status): string => match ($status) {
            'good' => 'Baik',
            'attention' => 'Perlu Perhatian',
            'critical' => 'Kritis',
            default => 'Belum Dinilai',
        };

        $deltaMeta = static function ($value, string $suffix = '%'): array {
            if ($value === null) {
                return [
                    'class' => 'neutral',
                    'icon' => 'bi-dash',
                    'text' => 'Belum ada pembanding',
                ];
            }

            $numeric = (float) $value;

            if ($numeric > 0) {
                return [
                    'class' => 'positive',
                    'icon' => 'bi-arrow-up-right',
                    'text' => '+' . number_format($numeric, 1, ',', '.') . $suffix,
                ];
            }

            if ($numeric < 0) {
                return [
                    'class' => 'negative',
                    'icon' => 'bi-arrow-down-right',
                    'text' => number_format($numeric, 1, ',', '.') . $suffix,
                ];
            }

            return [
                'class' => 'neutral',
                'icon' => 'bi-arrow-right',
                'text' => '0' . $suffix,
            ];
        };

        $setoranDelta = $deltaMeta($d['comparison']['setoran_pct']);
        $coverageDelta = $deltaMeta($d['comparison']['coverage_points'], ' poin');
        $lulusDelta = $deltaMeta($d['comparison']['lulus_juz_pct']);
        $maxJuzValue = max(
            1,
            collect($d['juz_progress'])->max(fn(array $item) => max($item['progress'], $item['lulus'])) ?? 1,
        );
    @endphp

    <style>
        :root,
        [data-coreui-theme="light"] {
            --exec-surface: #ffffff;
            --exec-surface-soft: #f6f7fb;
            --exec-surface-muted: #eef1f6;
            --exec-text: #202433;
            --exec-heading: #151827;
            --exec-muted: #73798a;
            --exec-border: rgba(27, 31, 48, .09);
            --exec-border-strong: rgba(27, 31, 48, .16);
            --exec-purple: #6b4eff;
            --exec-purple-soft: rgba(107, 78, 255, .11);
            --exec-tosca: #13a3b3;
            --exec-tosca-soft: rgba(19, 163, 179, .12);
            --exec-success: #198754;
            --exec-success-soft: rgba(25, 135, 84, .11);
            --exec-warning: #c98500;
            --exec-warning-soft: rgba(255, 193, 7, .15);
            --exec-danger: #dc3545;
            --exec-danger-soft: rgba(220, 53, 69, .11);
            --exec-info: #0d6efd;
            --exec-info-soft: rgba(13, 110, 253, .11);
            --exec-shadow: 0 12px 34px rgba(31, 36, 63, .07);
            --exec-grid: rgba(31, 36, 63, .08);
            --exec-tooltip: #151827;
            --exec-tooltip-text: #ffffff;
        }

        [data-coreui-theme="dark"] {
            --exec-surface: #20232d;
            --exec-surface-soft: #272b36;
            --exec-surface-muted: #303541;
            --exec-text: #e9ebf2;
            --exec-heading: #ffffff;
            --exec-muted: #a9afbd;
            --exec-border: rgba(255, 255, 255, .08);
            --exec-border-strong: rgba(255, 255, 255, .15);
            --exec-purple: #a897ff;
            --exec-purple-soft: rgba(139, 119, 255, .2);
            --exec-tosca: #62d3dc;
            --exec-tosca-soft: rgba(68, 196, 207, .18);
            --exec-success: #62d49c;
            --exec-success-soft: rgba(44, 184, 115, .18);
            --exec-warning: #ffd166;
            --exec-warning-soft: rgba(255, 193, 7, .18);
            --exec-danger: #ff8290;
            --exec-danger-soft: rgba(255, 92, 108, .18);
            --exec-info: #77a9ff;
            --exec-info-soft: rgba(86, 142, 255, .18);
            --exec-shadow: 0 15px 40px rgba(0, 0, 0, .24);
            --exec-grid: rgba(255, 255, 255, .08);
            --exec-tooltip: #f5f6f9;
            --exec-tooltip-text: #151827;
        }

        .executive-page {
            color: var(--exec-text);
            padding-bottom: 2rem;
        }

        .executive-page .text-muted {
            color: var(--exec-muted) !important;
        }

        .exec-card {
            border: 1px solid var(--exec-border);
            border-radius: 1.25rem;
            background: var(--exec-surface);
            box-shadow: var(--exec-shadow);
        }

        .exec-hero {
            position: relative;
            overflow: hidden;
            isolation: isolate;
            padding: clamp(1.35rem, 3vw, 2.15rem);
            border-radius: 1.5rem;
            color: #fff;
            background:
                radial-gradient(circle at 86% 8%, rgba(255, 255, 255, .24), transparent 30%),
                linear-gradient(132deg, #433280 0%, #6450d9 42%, #1599aa 100%);
            box-shadow: 0 20px 48px rgba(72, 54, 163, .26);
        }

        .exec-hero::before,
        .exec-hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, .14);
            z-index: -1;
        }

        .exec-hero::before {
            width: 230px;
            height: 230px;
            right: -70px;
            bottom: -130px;
            box-shadow: 0 0 0 35px rgba(255, 255, 255, .035);
        }

        .exec-hero::after {
            width: 115px;
            height: 115px;
            right: 190px;
            top: -65px;
        }

        .exec-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .44rem .75rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 999px;
            background: rgba(255, 255, 255, .11);
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }

        .exec-title {
            margin: .85rem 0 .45rem;
            font-size: clamp(1.55rem, 3vw, 2.3rem);
            font-weight: 850;
            letter-spacing: -.035em;
        }

        .exec-subtitle {
            max-width: 760px;
            margin: 0;
            color: rgba(255, 255, 255, .82);
            line-height: 1.65;
        }

        .exec-context {
            display: flex;
            flex-wrap: wrap;
            gap: .6rem;
            margin-top: 1.15rem;
        }

        .exec-context-item {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .52rem .76rem;
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: .85rem;
            background: rgba(255, 255, 255, .1);
            font-size: .78rem;
            backdrop-filter: blur(7px);
        }

        .health-panel {
            min-width: min(100%, 285px);
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 1.1rem;
            background: rgba(255, 255, 255, .12);
            backdrop-filter: blur(10px);
        }

        .health-label {
            color: rgba(255, 255, 255, .7);
            font-size: .67rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .health-value {
            display: flex;
            align-items: center;
            gap: .58rem;
            margin-top: .45rem;
            font-size: 1.25rem;
            font-weight: 850;
        }

        .health-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 0 0 5px rgba(255, 255, 255, .14);
        }

        .health-progress {
            height: 7px;
            margin-top: .85rem;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(255, 255, 255, .17);
        }

        .health-progress>span {
            display: block;
            width: {{ min(100, max(0, $d['period']['semester_progress_pct'])) }}%;
            height: 100%;
            border-radius: inherit;
            background: #fff;
        }

        .health-meta {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: .5rem;
            color: rgba(255, 255, 255, .72);
            font-size: .7rem;
        }

        .filter-panel {
            padding: 1rem;
        }

        .filter-panel .form-label {
            margin-bottom: .35rem;
            color: var(--exec-muted);
            font-size: .67rem;
            font-weight: 800;
            letter-spacing: .07em;
            text-transform: uppercase;
        }

        .filter-panel .form-select,
        .filter-panel .form-control {
            min-height: 42px;
            border: 1px solid var(--exec-border);
            border-radius: .78rem;
            color: var(--exec-text);
            background-color: var(--exec-surface-soft);
            box-shadow: none;
        }

        .filter-panel .form-select:focus,
        .filter-panel .form-control:focus {
            border-color: var(--exec-purple);
            box-shadow: 0 0 0 .2rem var(--exec-purple-soft);
        }

        .range-pills {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
        }

        .range-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: .52rem .85rem;
            border: 1px solid var(--exec-border);
            border-radius: 999px;
            color: var(--exec-text);
            background: var(--exec-surface-soft);
            font-size: .76rem;
            font-weight: 750;
            cursor: pointer;
        }

        .range-pill input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .range-pill:has(input:checked) {
            border-color: rgba(107, 78, 255, .3);
            color: var(--exec-purple);
            background: var(--exec-purple-soft);
        }

        .btn-exec {
            min-height: 42px;
            padding-inline: 1rem;
            border: 0;
            border-radius: .78rem;
            color: #fff;
            background: linear-gradient(135deg, var(--exec-purple), var(--exec-tosca));
            font-size: .8rem;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(107, 78, 255, .2);
        }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: .85rem;
        }

        .section-kicker {
            margin-bottom: .23rem;
            color: var(--exec-purple);
            font-size: .68rem;
            font-weight: 850;
            letter-spacing: .1em;
            text-transform: uppercase;
        }

        .section-title {
            margin: 0;
            color: var(--exec-heading);
            font-size: 1.05rem;
            font-weight: 850;
            letter-spacing: -.015em;
        }

        .section-copy {
            margin: .2rem 0 0;
            color: var(--exec-muted);
            font-size: .76rem;
        }

        .kpi-card {
            position: relative;
            height: 100%;
            overflow: hidden;
            padding: 1.15rem;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: var(--metric-color, var(--exec-purple));
        }

        .kpi-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: .95rem;
            color: var(--metric-color, var(--exec-purple));
            background: var(--metric-soft, var(--exec-purple-soft));
            font-size: 1.18rem;
        }

        .kpi-label {
            color: var(--exec-muted);
            font-size: .66rem;
            font-weight: 850;
            letter-spacing: .075em;
            text-transform: uppercase;
        }

        .kpi-value {
            margin-top: .35rem;
            color: var(--exec-heading);
            font-size: clamp(1.55rem, 3vw, 2rem);
            font-weight: 850;
            line-height: 1;
            letter-spacing: -.04em;
        }

        .kpi-note {
            margin-top: .52rem;
            color: var(--exec-muted);
            font-size: .72rem;
            line-height: 1.45;
        }

        .delta {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin-top: .7rem;
            padding: .35rem .52rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 800;
        }

        .delta.positive {
            color: var(--exec-success);
            background: var(--exec-success-soft);
        }

        .delta.negative {
            color: var(--exec-danger);
            background: var(--exec-danger-soft);
        }

        .delta.neutral {
            color: var(--exec-muted);
            background: var(--exec-surface-muted);
        }

        .summary-card {
            position: relative;
            height: 100%;
            overflow: hidden;
            padding: 1.25rem;
            background:
                linear-gradient(135deg, var(--exec-purple-soft), transparent 52%),
                var(--exec-surface);
        }

        .summary-icon {
            width: 50px;
            height: 50px;
            display: grid;
            place-items: center;
            border-radius: 1rem;
            color: var(--exec-purple);
            background: var(--exec-purple-soft);
            font-size: 1.25rem;
        }

        .summary-title {
            margin: .9rem 0 .42rem;
            color: var(--exec-heading);
            font-size: 1.03rem;
            font-weight: 850;
        }

        .summary-copy {
            margin: 0;
            color: var(--exec-muted);
            line-height: 1.65;
            font-size: .82rem;
        }

        .control-card {
            height: 100%;
            padding: 1.25rem;
        }

        .control-row+.control-row {
            margin-top: 1rem;
        }

        .control-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: .42rem;
            font-size: .73rem;
        }

        .control-name {
            color: var(--exec-heading);
            font-weight: 800;
        }

        .control-value {
            color: var(--exec-muted);
            font-weight: 750;
        }

        .control-progress {
            height: 7px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--exec-surface-muted);
        }

        .control-progress>span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--exec-purple), var(--exec-tosca));
        }

        .attention-list {
            display: grid;
            gap: .72rem;
            padding: 1rem;
        }

        .attention-item {
            display: grid;
            grid-template-columns: 43px minmax(0, 1fr) auto;
            align-items: center;
            gap: .8rem;
            padding: .82rem;
            border: 1px solid var(--exec-border);
            border-radius: 1rem;
            background: var(--exec-surface-soft);
        }

        .attention-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: .9rem;
            font-size: 1.05rem;
        }

        .attention-icon.danger {
            color: var(--exec-danger);
            background: var(--exec-danger-soft);
        }

        .attention-icon.warning {
            color: var(--exec-warning);
            background: var(--exec-warning-soft);
        }

        .attention-icon.info {
            color: var(--exec-info);
            background: var(--exec-info-soft);
        }

        .attention-icon.success {
            color: var(--exec-success);
            background: var(--exec-success-soft);
        }

        .attention-icon.secondary {
            color: var(--exec-muted);
            background: var(--exec-surface-muted);
        }

        .attention-title {
            color: var(--exec-heading);
            font-size: .78rem;
            font-weight: 830;
        }

        .attention-description {
            margin-top: .2rem;
            color: var(--exec-muted);
            font-size: .69rem;
            line-height: 1.45;
        }

        .attention-value {
            min-width: 40px;
            text-align: right;
            color: var(--exec-heading);
            font-size: 1.32rem;
            font-weight: 850;
        }

        .chart-card {
            padding: 1.15rem;
        }

        .chart-box {
            position: relative;
            min-height: 310px;
        }

        .chart-box-sm {
            position: relative;
            min-height: 260px;
        }

        .table-card {
            overflow: hidden;
        }

        .table-card .table {
            margin: 0;
            color: var(--exec-text);
        }

        .table-card .table> :not(caption)>*>* {
            padding: .82rem .9rem;
            border-bottom-color: var(--exec-border);
            background: transparent;
            vertical-align: middle;
        }

        .table-card thead th {
            color: var(--exec-muted);
            background: var(--exec-surface-soft) !important;
            font-size: .65rem;
            font-weight: 850;
            letter-spacing: .065em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .entity-name {
            color: var(--exec-heading);
            font-size: .79rem;
            font-weight: 830;
        }

        .entity-meta {
            margin-top: .18rem;
            color: var(--exec-muted);
            font-size: .66rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: .32rem;
            padding: .36rem .55rem;
            border-radius: 999px;
            font-size: .65rem;
            font-weight: 820;
            white-space: nowrap;
        }

        .status-badge.good {
            color: var(--exec-success);
            background: var(--exec-success-soft);
        }

        .status-badge.attention {
            color: var(--exec-warning);
            background: var(--exec-warning-soft);
        }

        .status-badge.critical {
            color: var(--exec-danger);
            background: var(--exec-danger-soft);
        }

        .mini-progress {
            width: 84px;
            height: 6px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--exec-surface-muted);
        }

        .mini-progress>span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--exec-purple), var(--exec-tosca));
        }

        .juz-grid {
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: .62rem;
            padding: 1rem;
        }

        .juz-item {
            min-width: 0;
            padding: .72rem;
            border: 1px solid var(--exec-border);
            border-radius: .95rem;
            background: var(--exec-surface-soft);
        }

        .juz-number {
            color: var(--exec-heading);
            font-size: .75rem;
            font-weight: 850;
        }

        .juz-stat {
            display: flex;
            justify-content: space-between;
            gap: .4rem;
            margin-top: .48rem;
            color: var(--exec-muted);
            font-size: .62rem;
        }

        .juz-stat strong {
            color: var(--exec-heading);
            font-size: .75rem;
        }

        .juz-bars {
            display: grid;
            gap: .25rem;
            margin-top: .48rem;
        }

        .juz-bar {
            height: 4px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--exec-surface-muted);
        }

        .juz-bar>span {
            display: block;
            height: 100%;
            border-radius: inherit;
        }

        .juz-bar.progress>span {
            background: var(--exec-purple);
        }

        .juz-bar.pass>span {
            background: var(--exec-success);
        }

        .legend-inline {
            display: flex;
            flex-wrap: wrap;
            gap: .8rem;
            color: var(--exec-muted);
            font-size: .68rem;
        }

        .legend-inline span {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .legend-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .integrity-alert {
            padding: 1rem 1.1rem;
            border: 1px solid rgba(217, 139, 0, .22);
            border-radius: 1rem;
            color: var(--exec-warning);
            background: var(--exec-warning-soft);
            font-size: .78rem;
        }


        .section-head-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: .7rem;
        }

        .btn-panel-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            min-height: 38px;
            padding: .52rem .78rem;
            border: 1px solid var(--exec-border-strong);
            border-radius: .8rem;
            color: var(--exec-heading);
            background: var(--exec-surface);
            font-size: .72rem;
            font-weight: 800;
            line-height: 1;
            transition: transform .18s ease, border-color .18s ease, background .18s ease;
        }

        .btn-panel-action:hover,
        .btn-panel-action:focus-visible {
            color: var(--exec-purple);
            border-color: color-mix(in srgb, var(--exec-purple) 42%, var(--exec-border));
            background: var(--exec-purple-soft);
            transform: translateY(-1px);
        }

        .juz-fullscreen-panel {
            scroll-behavior: smooth;
        }

        .juz-fullscreen-panel:fullscreen,
        .juz-fullscreen-panel.is-pseudo-fullscreen {
            width: 100%;
            height: 100%;
            margin: 0 !important;
            padding: clamp(1rem, 2vw, 1.6rem);
            overflow: auto;
            color: var(--exec-text);
            background: var(--exec-surface-soft);
        }

        .juz-fullscreen-panel:-webkit-full-screen {
            width: 100%;
            height: 100%;
            margin: 0 !important;
            padding: clamp(1rem, 2vw, 1.6rem);
            overflow: auto;
            color: var(--exec-text);
            background: var(--exec-surface-soft);
        }

        .juz-fullscreen-panel.is-pseudo-fullscreen {
            position: fixed;
            inset: 0;
            z-index: 1090;
        }

        body.exec-pseudo-fullscreen-active,
        body.exec-help-open {
            overflow: hidden;
        }

        .juz-fullscreen-panel:fullscreen .section-head,
        .juz-fullscreen-panel.is-pseudo-fullscreen .section-head,
        .juz-fullscreen-panel:-webkit-full-screen .section-head {
            position: sticky;
            top: 0;
            z-index: 3;
            margin: -.35rem -.35rem 1rem;
            padding: .75rem;
            border: 1px solid var(--exec-border);
            border-radius: 1rem;
            background: color-mix(in srgb, var(--exec-surface) 94%, transparent);
            box-shadow: var(--exec-shadow);
            backdrop-filter: blur(12px);
        }

        .juz-fullscreen-panel:fullscreen .exec-card,
        .juz-fullscreen-panel.is-pseudo-fullscreen .exec-card,
        .juz-fullscreen-panel:-webkit-full-screen .exec-card {
            min-height: calc(100vh - 130px);
        }

        .juz-fullscreen-panel:fullscreen .juz-grid,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-grid,
        .juz-fullscreen-panel:-webkit-full-screen .juz-grid {
            grid-template-columns: repeat(10, minmax(100px, 1fr));
            align-content: start;
            gap: .8rem;
            padding: 1.15rem;
        }

        .juz-fullscreen-panel:fullscreen .juz-item,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-item,
        .juz-fullscreen-panel:-webkit-full-screen .juz-item {
            min-height: 112px;
            padding: .9rem;
            background: var(--exec-surface);
        }

        .juz-fullscreen-panel:fullscreen .juz-number,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-number,
        .juz-fullscreen-panel:-webkit-full-screen .juz-number {
            font-size: .84rem;
        }

        .juz-fullscreen-panel:fullscreen .juz-stat,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-stat,
        .juz-fullscreen-panel:-webkit-full-screen .juz-stat {
            margin-top: .65rem;
            font-size: .68rem;
        }

        .juz-fullscreen-panel:fullscreen .juz-stat strong,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-stat strong,
        .juz-fullscreen-panel:-webkit-full-screen .juz-stat strong {
            font-size: .9rem;
        }

        /* ================= FLOATING PAGE GUIDE — ADOPSI MASTER SANTRI ================= */
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
            color: #ffffff;
            background:
                linear-gradient(135deg,
                    var(--exec-purple, #6b4eff),
                    color-mix(in srgb, var(--exec-purple, #6b4eff) 78%, #24175f));
            box-shadow: 0 12px 30px rgba(89, 53, 157, 0.34);
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                filter 0.2s ease;
        }

        .page-guide-fab:hover,
        .page-guide-fab:focus-visible {
            color: #ffffff;
            transform: translateY(-3px) scale(1.03);
            filter: brightness(1.06);
            box-shadow: 0 16px 36px rgba(89, 53, 157, 0.42);
        }

        .page-guide-fab:focus-visible {
            outline: 3px solid rgba(107, 78, 255, 0.24);
            outline-offset: 4px;
        }

        .page-guide-fab i {
            font-size: 1.45rem;
        }

        .page-guide-fab::after {
            content: '';
            position: absolute;
            inset: -5px;
            border: 2px solid rgba(107, 78, 255, 0.22);
            border-radius: inherit;
            animation: pageGuidePulse 2.4s ease-out infinite;
            pointer-events: none;
        }

        @keyframes pageGuidePulse {
            0% {
                transform: scale(0.88);
                opacity: 0;
            }

            30% {
                opacity: 1;
            }

            100% {
                transform: scale(1.28);
                opacity: 0;
            }
        }

        .page-guide-hero {
            position: relative;
            overflow: hidden;
            color: #ffffff;
            background:
                radial-gradient(circle at 92% 10%, rgba(255, 255, 255, 0.18), transparent 24%),
                linear-gradient(135deg,
                    color-mix(in srgb, var(--exec-purple, #6b4eff) 76%, #24175f),
                    var(--exec-purple, #6b4eff));
        }

        .page-guide-hero::after {
            content: '';
            position: absolute;
            right: -40px;
            bottom: -70px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .page-guide-hero>* {
            position: relative;
            z-index: 1;
        }

        .guide-step {
            height: 100%;
            padding: 1rem;
            border: 1px solid var(--exec-border);
            border-radius: 14px;
            background: var(--exec-surface);
        }

        .guide-step-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            color: var(--exec-purple);
            background: var(--exec-purple-soft);
            font-size: 1.1rem;
        }

        .guide-action-box {
            overflow: hidden;
            border: 1px solid var(--exec-border);
            border-radius: 1rem;
            background: var(--exec-surface);
        }

        .guide-action-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px dashed var(--exec-border);
        }

        .guide-action-row:last-child {
            border-bottom: 0;
        }

        .guide-action-icon {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .guide-formula {
            display: inline-block;
            margin-top: .35rem;
            padding: .27rem .5rem;
            border-radius: .5rem;
            color: var(--exec-purple);
            background: var(--exec-purple-soft);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: .68rem;
            font-weight: 700;
        }

        .guide-status-card {
            height: 100%;
            padding: .95rem 1rem;
            border: 1px solid var(--exec-border);
            border-radius: 14px;
            background: var(--exec-surface-soft);
        }

        .guide-status-card strong {
            display: block;
            margin-bottom: .25rem;
        }

        .guide-status-card.good strong {
            color: var(--exec-success);
        }

        .guide-status-card.attention strong {
            color: var(--exec-warning);
        }

        .guide-status-card.critical strong {
            color: var(--exec-danger);
        }

        .guide-section-title {
            margin-bottom: .8rem;
            color: var(--exec-heading);
            font-size: .92rem;
            font-weight: 850;
        }

        .guide-highlight {
            border: 1px solid color-mix(in srgb, var(--exec-purple) 22%, var(--exec-border));
            border-radius: 1rem;
            background:
                linear-gradient(135deg, var(--exec-purple-soft), var(--exec-tosca-soft)),
                var(--exec-surface);
        }

        [data-coreui-theme="dark"] .guide-step,
        [data-coreui-theme="dark"] .guide-action-box,
        [data-coreui-theme="dark"] .guide-status-card {
            background: var(--exec-surface-soft);
        }

        /* ================= FULLSCREEN PETA 30 JUZ — CARD LEBIH BESAR ================= */
        .juz-fullscreen-panel:fullscreen .juz-grid,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-grid,
        .juz-fullscreen-panel:-webkit-full-screen .juz-grid {
            grid-template-columns: repeat(6, minmax(180px, 1fr));
            gap: 1rem;
            padding: 1.35rem;
        }

        .juz-fullscreen-panel:fullscreen .juz-item,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-item,
        .juz-fullscreen-panel:-webkit-full-screen .juz-item {
            min-height: 170px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.2rem;
            border-radius: 1.2rem;
            background: var(--exec-surface);
            box-shadow: 0 10px 26px rgba(31, 36, 63, .08);
        }

        .juz-fullscreen-panel:fullscreen .juz-number,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-number,
        .juz-fullscreen-panel:-webkit-full-screen .juz-number {
            font-size: 1rem;
            letter-spacing: -.01em;
        }

        .juz-fullscreen-panel:fullscreen .juz-stat,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-stat,
        .juz-fullscreen-panel:-webkit-full-screen .juz-stat {
            margin-top: 1rem;
            font-size: .78rem;
            line-height: 1.45;
        }

        .juz-fullscreen-panel:fullscreen .juz-stat strong,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-stat strong,
        .juz-fullscreen-panel:-webkit-full-screen .juz-stat strong {
            font-size: 1.35rem;
            line-height: 1.1;
        }

        .juz-fullscreen-panel:fullscreen .juz-bars,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-bars,
        .juz-fullscreen-panel:-webkit-full-screen .juz-bars {
            gap: .55rem;
            margin-top: 1rem;
        }

        .juz-fullscreen-panel:fullscreen .juz-bar,
        .juz-fullscreen-panel.is-pseudo-fullscreen .juz-bar,
        .juz-fullscreen-panel:-webkit-full-screen .juz-bar {
            height: 8px;
        }

        @media (max-width: 1399.98px) {

            .juz-fullscreen-panel:fullscreen .juz-grid,
            .juz-fullscreen-panel.is-pseudo-fullscreen .juz-grid,
            .juz-fullscreen-panel:-webkit-full-screen .juz-grid {
                grid-template-columns: repeat(5, minmax(165px, 1fr));
            }

            .juz-fullscreen-panel:fullscreen .juz-item,
            .juz-fullscreen-panel.is-pseudo-fullscreen .juz-item,
            .juz-fullscreen-panel:-webkit-full-screen .juz-item {
                min-height: 155px;
            }
        }

        @media (max-width: 991.98px) {

            .juz-fullscreen-panel:fullscreen .juz-grid,
            .juz-fullscreen-panel.is-pseudo-fullscreen .juz-grid,
            .juz-fullscreen-panel:-webkit-full-screen .juz-grid {
                grid-template-columns: repeat(4, minmax(145px, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .page-guide-fab {
                right: max(14px, env(safe-area-inset-right));
                bottom: max(14px, env(safe-area-inset-bottom));
                width: 52px;
                height: 52px;
            }

            .juz-fullscreen-panel:fullscreen .juz-grid,
            .juz-fullscreen-panel.is-pseudo-fullscreen .juz-grid,
            .juz-fullscreen-panel:-webkit-full-screen .juz-grid {
                grid-template-columns: repeat(2, minmax(135px, 1fr));
                gap: .75rem;
                padding: .85rem;
            }

            .juz-fullscreen-panel:fullscreen .juz-item,
            .juz-fullscreen-panel.is-pseudo-fullscreen .juz-item,
            .juz-fullscreen-panel:-webkit-full-screen .juz-item {
                min-height: 145px;
                padding: 1rem;
            }

            .btn-panel-action .btn-panel-label {
                display: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .page-guide-fab,
            .page-guide-fab::after {
                animation: none;
                transition: none;
            }
        }

        @media (max-width: 1199.98px) {
            .juz-grid {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .juz-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .attention-item {
                grid-template-columns: 40px minmax(0, 1fr);
            }

            .attention-value {
                grid-column: 2;
                text-align: left;
            }

            .section-head {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>

    <div class="executive-page">
        <section class="exec-hero mb-4">
            <div class="row g-4 align-items-center">
                <div class="col-xl-8">
                    <span class="exec-eyebrow">
                        <i class="bi bi-speedometer2"></i>
                        Executive Command Center
                    </span>
                    <h1 class="exec-title">Perkembangan Departemen Al-Qur’an</h1>
                    <p class="exec-subtitle">
                        Ringkasan strategis untuk melihat kondisi departemen, capaian hafalan, risiko utama,
                        dan unit yang membutuhkan perhatian tanpa masuk ke proses operasional harian.
                    </p>

                    <div class="exec-context">
                        <span class="exec-context-item">
                            <i class="bi bi-calendar3"></i>
                            {{ $d['semester']['label'] }}
                        </span>
                        <span class="exec-context-item">
                            <i class="bi bi-calendar-range"></i>
                            {{ $d['period']['label'] }}
                        </span>
                        <span class="exec-context-item">
                            <i class="bi bi-arrow-repeat"></i>
                            Diperbarui {{ $d['period']['updated_at'] }}
                        </span>
                    </div>
                </div>

                <div class="col-xl-4 d-flex justify-content-xl-end">
                    <div class="health-panel">
                        <div class="health-label">Status Departemen</div>
                        <div class="health-value">
                            <span class="health-dot"></span>
                            {{ $d['health']['label'] }}
                        </div>
                        <div class="health-progress"><span></span></div>
                        <div class="health-meta">
                            <span>Perjalanan semester</span>
                            <strong>{{ number_format($d['period']['semester_progress_pct'], 1, ',', '.') }}%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="exec-card filter-panel mb-4">
            <form method="GET" action="{{ route('pimpinan.dashboard') }}" id="executiveFilterForm">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3">
                        <label class="form-label" for="semester_id">Semester</label>
                        <select class="form-select" id="semester_id" name="semester_id">
                            @foreach ($semesterList as $semester)
                                @php
                                    $semesterLabel = trim(
                                        ($semester->nama ?? 'Semester') . ' ' . ($semester->tahunAjaran?->nama ?? ''),
                                    );
                                @endphp
                                <option value="{{ $semester->id }}" @selected((int) $d['semester']['id'] === (int) $semester->id)>
                                    {{ $semesterLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-5">
                        <label class="form-label">Rentang Analisis</label>
                        <div class="range-pills">
                            @foreach ([
            'semester' => 'Semester',
            '30d' => '30 Hari',
            '7d' => '7 Hari',
            'custom' => 'Kustom',
        ] as $value => $label)
                                <label class="range-pill">
                                    <input type="radio" name="range" value="{{ $value }}"
                                        @checked($d['period']['range'] === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-lg-3" id="customRangeFields"
                        @if ($d['period']['range'] !== 'custom') style="display:none" @endif>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" for="start_date">Mulai</label>
                                <input class="form-control" type="date" id="start_date" name="start_date"
                                    value="{{ request('start_date', $d['period']['start_date']) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="end_date">Selesai</label>
                                <input class="form-control" type="date" id="end_date" name="end_date"
                                    value="{{ request('end_date', $d['period']['end_date']) }}">
                            </div>
                        </div>
                    </div>

                    <div class="col-lg">
                        <button class="btn btn-exec w-100" type="submit">
                            <i class="bi bi-funnel-fill me-1"></i>
                            Terapkan
                        </button>
                    </div>
                </div>
            </form>
        </section>

        @if ($d['integrity']['has_warning'])
            <div class="integrity-alert mb-4">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-database-exclamation fs-5"></i>
                    <div>
                        <strong>Catatan integritas data</strong>
                        <div class="mt-1">
                            {{ implode(' ', $d['integrity']['warnings']) }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <section class="mb-4">
            <div class="section-head">
                <div>
                    <div class="section-kicker">Indikator Utama</div>
                    <h2 class="section-title">Kondisi Departemen dalam Satu Pandangan</h2>
                    <p class="section-copy">
                        Angka utama menggunakan placement semester dan transaksi pada periode yang sama.
                    </p>
                </div>
                <small class="text-muted">{{ $d['comparison']['label'] }}</small>
            </div>

            <div class="row g-3">
                <div class="col-sm-6 col-xl-4 col-xxl-2">
                    <div class="exec-card kpi-card"
                        style="--metric-color:var(--exec-purple);--metric-soft:var(--exec-purple-soft)">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Coverage Santri</div>
                                <div class="kpi-value">{{ number_format($d['kpi']['coverage_pct'], 1, ',', '.') }}%</div>
                            </div>
                            <span class="kpi-icon"><i class="bi bi-people-fill"></i></span>
                        </div>
                        <div class="kpi-note">
                            {{ number_format($d['kpi']['santri_aktif'], 0, ',', '.') }} dari
                            {{ number_format($d['kpi']['total_santri'], 0, ',', '.') }} santri aktif.
                        </div>
                        <span class="delta {{ $coverageDelta['class'] }}">
                            <i class="bi {{ $coverageDelta['icon'] }}"></i>
                            {{ $coverageDelta['text'] }}
                        </span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4 col-xxl-2">
                    <div class="exec-card kpi-card"
                        style="--metric-color:var(--exec-tosca);--metric-soft:var(--exec-tosca-soft)">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Setoran / Santri</div>
                                <div class="kpi-value">
                                    {{ number_format($d['kpi']['avg_setoran_per_santri'], 1, ',', '.') }}</div>
                            </div>
                            <span class="kpi-icon"><i class="bi bi-journal-check"></i></span>
                        </div>
                        <div class="kpi-note">
                            Total {{ number_format($d['kpi']['total_setor'], 0, ',', '.') }} transaksi lulus/ulang.
                        </div>
                        <span class="delta {{ $setoranDelta['class'] }}">
                            <i class="bi {{ $setoranDelta['icon'] }}"></i>
                            {{ $setoranDelta['text'] }}
                        </span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4 col-xxl-2">
                    <div class="exec-card kpi-card"
                        style="--metric-color:var(--exec-success);--metric-soft:var(--exec-success-soft)">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Kelulusan Juz</div>
                                <div class="kpi-value">{{ number_format($d['kpi']['lulus_juz'], 0, ',', '.') }}</div>
                            </div>
                            <span class="kpi-icon"><i class="bi bi-award-fill"></i></span>
                        </div>
                        <div class="kpi-note">Santri–juz yang lulus pada tahap ujian akhir.</div>
                        <span class="delta {{ $lulusDelta['class'] }}">
                            <i class="bi {{ $lulusDelta['icon'] }}"></i>
                            {{ $lulusDelta['text'] }}
                        </span>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4 col-xxl-2">
                    <div class="exec-card kpi-card"
                        style="--metric-color:var(--exec-info);--metric-soft:var(--exec-info-soft)">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Nilai Ujian Final</div>
                                <div class="kpi-value">{{ number_format($d['kpi']['avg_nilai_ujian'], 1, ',', '.') }}
                                </div>
                            </div>
                            <span class="kpi-icon"><i class="bi bi-patch-check-fill"></i></span>
                        </div>
                        <div class="kpi-note">Rata-rata nilai ujian akhir berstatus lulus.</div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4 col-xxl-2">
                    <div class="exec-card kpi-card"
                        style="--metric-color:var(--exec-warning);--metric-soft:var(--exec-warning-soft)">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Absensi Valid</div>
                                <div class="kpi-value">{{ number_format($d['attendance']['valid_pct'], 1, ',', '.') }}%
                                </div>
                            </div>
                            <span class="kpi-icon"><i class="bi bi-person-check-fill"></i></span>
                        </div>
                        <div class="kpi-note">
                            {{ number_format($d['attendance']['valid_records'], 0, ',', '.') }} dari
                            {{ number_format($d['attendance']['total_records'], 0, ',', '.') }} log absensi.
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4 col-xxl-2">
                    <div class="exec-card kpi-card"
                        style="--metric-color:var(--exec-danger);--metric-soft:var(--exec-danger-soft)">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div>
                                <div class="kpi-label">Risiko Alpha</div>
                                <div class="kpi-value">{{ number_format($d['kpi']['santri_risiko_alpha'], 0, ',', '.') }}
                                </div>
                            </div>
                            <span class="kpi-icon"><i class="bi bi-exclamation-octagon-fill"></i></span>
                        </div>
                        <div class="kpi-note">
                            {{ number_format($d['kpi']['alpha_risk_rate_pct'], 1, ',', '.') }}% dari seluruh santri
                            semester.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4">
            <div class="col-xl-5">
                <div class="exec-card summary-card">
                    <span class="summary-icon"><i class="bi bi-stars"></i></span>
                    <div class="section-kicker mt-3">Ringkasan Otomatis</div>
                    <h2 class="summary-title">{{ $d['health']['label'] }}</h2>
                    <p class="summary-copy">{{ $d['health']['summary'] }}</p>

                    <div class="row g-2 mt-3">
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--exec-surface-soft)">
                                <div class="kpi-label">Santri</div>
                                <strong>{{ number_format($d['kpi']['total_santri'], 0, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--exec-surface-soft)">
                                <div class="kpi-label">Kelas</div>
                                <strong>{{ number_format($d['kpi']['total_kelas'], 0, ',', '.') }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 rounded-3" style="background:var(--exec-surface-soft)">
                                <div class="kpi-label">Musyrif</div>
                                <strong>{{ number_format($d['kpi']['total_musyrif'], 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="exec-card control-card">
                    <div class="section-kicker">Ambang Kendali MVP</div>
                    <h2 class="section-title">Realisasi terhadap Benchmark Awal</h2>
                    <p class="section-copy mb-3">
                        Benchmark berikut dapat diganti menjadi target resmi setelah modul target semester dibuat.
                    </p>

                    <div class="control-row">
                        <div class="control-meta">
                            <span class="control-name">Coverage santri aktif</span>
                            <span class="control-value">
                                {{ number_format($d['kpi']['coverage_pct'], 1, ',', '.') }}% /
                                {{ number_format($d['thresholds']['coverage_good'] ?? 85, 0, ',', '.') }}%
                            </span>
                        </div>
                        <div class="control-progress">
                            <span style="width:{{ min(100, $d['kpi']['coverage_pct']) }}%"></span>
                        </div>
                    </div>

                    <div class="control-row">
                        <div class="control-meta">
                            <span class="control-name">Validitas absensi musyrif</span>
                            <span class="control-value">
                                {{ number_format($d['attendance']['valid_pct'], 1, ',', '.') }}% /
                                {{ number_format($d['thresholds']['attendance_good'] ?? 90, 0, ',', '.') }}%
                            </span>
                        </div>
                        <div class="control-progress">
                            <span style="width:{{ min(100, $d['attendance']['valid_pct']) }}%"></span>
                        </div>
                    </div>

                    <div class="control-row">
                        <div class="control-meta">
                            <span class="control-name">Santri tanpa setoran</span>
                            <span class="control-value">
                                {{ number_format($d['kpi']['santri_belum_setor'], 0, ',', '.') }} santri
                            </span>
                        </div>
                        <div class="control-progress">
                            <span
                                style="width:{{ max(0, 100 - $d['kpi']['coverage_pct']) }}%;background:var(--exec-warning)"></span>
                        </div>
                    </div>

                    <div class="control-row">
                        <div class="control-meta">
                            <span class="control-name">Tingkat risiko alpha</span>
                            <span class="control-value">
                                {{ number_format($d['kpi']['alpha_risk_rate_pct'], 1, ',', '.') }}% /
                                batas perhatian
                                {{ number_format($d['thresholds']['alpha_rate_attention'] ?? 5, 0, ',', '.') }}%
                            </span>
                        </div>
                        <div class="control-progress">
                            <span
                                style="width:{{ min(100, $d['kpi']['alpha_risk_rate_pct'] * 5) }}%;background:var(--exec-danger)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4">
            <div class="col-xl-5">
                <div class="exec-card h-100">
                    <div class="p-3 border-bottom" style="border-color:var(--exec-border)!important">
                        <div class="section-kicker">Pusat Perhatian</div>
                        <h2 class="section-title">Hal yang Perlu Diketahui Pimpinan</h2>
                        <p class="section-copy">Urutan otomatis berdasarkan tingkat urgensi.</p>
                    </div>
                    <div class="attention-list">
                        @foreach ($d['attention'] as $item)
                            <div class="attention-item">
                                <span class="attention-icon {{ $item['tone'] }}">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                </span>
                                <div>
                                    <div class="attention-title">{{ $item['title'] }}</div>
                                    <div class="attention-description">{{ $item['description'] }}</div>
                                </div>
                                <div class="attention-value">
                                    {{ is_numeric($item['value']) ? number_format($item['value'], 0, ',', '.') : $item['value'] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="exec-card chart-card h-100">
                    <div class="section-head">
                        <div>
                            <div class="section-kicker">Tren Perkembangan</div>
                            <h2 class="section-title">Setoran, Kelulusan Juz, dan Alpha</h2>
                            <p class="section-copy">Melihat arah perkembangan, bukan hanya total kumulatif.</p>
                        </div>
                    </div>
                    <div class="chart-box">
                        <canvas id="executiveTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-4">
            <div class="col-xl-7">
                <div class="exec-card table-card h-100">
                    <div class="p-3 border-bottom" style="border-color:var(--exec-border)!important">
                        <div class="section-kicker">Evaluasi Unit</div>
                        <h2 class="section-title">Kelas yang Membutuhkan Perhatian</h2>
                        <p class="section-copy">
                            Diurutkan dari kondisi paling membutuhkan intervensi. Produktivitas dinormalisasi per santri.
                        </p>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Coverage</th>
                                    <th>Setoran / Santri</th>
                                    <th>Lulus Juz</th>
                                    <th>Alpha</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($d['class_performance']['rows'] as $row)
                                    <tr>
                                        <td>
                                            <div class="entity-name">{{ $row['nama'] }}</div>
                                            <div class="entity-meta">{{ $row['total_santri'] }} santri</div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="mini-progress">
                                                    <span style="width:{{ min(100, $row['coverage_pct']) }}%"></span>
                                                </div>
                                                <small>{{ number_format($row['coverage_pct'], 1, ',', '.') }}%</small>
                                            </div>
                                        </td>
                                        <td>{{ number_format($row['avg_setoran_per_santri'], 1, ',', '.') }}</td>
                                        <td>{{ number_format($row['lulus_juz'], 0, ',', '.') }}</td>
                                        <td>{{ number_format($row['alpha_rate_pct'], 1, ',', '.') }}%</td>
                                        <td>
                                            <span class="status-badge {{ $row['status'] }}">
                                                <i class="bi bi-circle-fill" style="font-size:.4rem"></i>
                                                {{ $performanceLabel($row['status']) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Data kelas belum tersedia pada semester ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="exec-card chart-card h-100">
                    <div class="section-kicker">Kedisiplinan</div>
                    <h2 class="section-title">Status Absensi Musyrif</h2>
                    <p class="section-copy mb-3">
                        Valid, suspect, dan rejected pada periode terpilih.
                    </p>
                    <div class="chart-box-sm">
                        <canvas id="attendanceStatusChart"></canvas>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <div class="p-2 rounded-3" style="background:var(--exec-surface-soft)">
                                <div class="kpi-label">Pagi {{ $d['reference_attendance']['date_label'] }}</div>
                                <strong>{{ $d['reference_attendance']['morning'] }} musyrif</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded-3" style="background:var(--exec-surface-soft)">
                                <div class="kpi-label">Sore {{ $d['reference_attendance']['date_label'] }}</div>
                                <strong>{{ $d['reference_attendance']['afternoon'] }} musyrif</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="juz-fullscreen-panel mb-4" id="juzFullscreenTarget">
            <div class="section-head">
                <div>
                    <div class="section-kicker">Peta 30 Juz</div>
                    <h2 class="section-title">Progress Setoran dan Kelulusan Ujian Akhir</h2>
                    <p class="section-copy">
                        Progress menunjukkan santri yang menjalani tahap harian–tahap 3; lulus menunjukkan ujian akhir.
                    </p>
                </div>
                <div class="section-head-actions">
                    <div class="legend-inline">
                        <span><i class="legend-dot" style="background:var(--exec-purple)"></i> Progress</span>
                        <span><i class="legend-dot" style="background:var(--exec-success)"></i> Lulus</span>
                    </div>
                    <button type="button" class="btn-panel-action" id="toggleJuzFullscreen"
                        aria-controls="juzFullscreenTarget" aria-pressed="false"
                        title="Tampilkan Peta 30 Juz dalam layar penuh">
                        <i class="bi bi-arrows-fullscreen" data-fullscreen-icon></i>
                        <span class="btn-panel-label" data-fullscreen-label>Fullscreen</span>
                    </button>
                </div>
            </div>

            <div class="exec-card">
                <div class="juz-grid">
                    @foreach ($d['juz_progress'] as $juz)
                        <div class="juz-item">
                            <div class="juz-number">Juz {{ $juz['juz'] }}</div>
                            <div class="juz-stat">
                                <span>Progress<br><strong>{{ $juz['progress'] }}</strong></span>
                                <span class="text-end">Lulus<br><strong>{{ $juz['lulus'] }}</strong></span>
                            </div>
                            <div class="juz-bars">
                                <div class="juz-bar progress"
                                    title="{{ $juz['progress'] }} santri sedang berada pada proses Juz {{ $juz['juz'] }}">
                                    <span style="width:{{ ($juz['progress'] / $maxJuzValue) * 100 }}%"></span>
                                </div>
                                <div class="juz-bar pass"
                                    title="{{ $juz['lulus'] }} santri telah lulus ujian akhir Juz {{ $juz['juz'] }}">
                                    <span style="width:{{ ($juz['lulus'] / $maxJuzValue) * 100 }}%"></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mb-4">
            <div class="section-head">
                <div>
                    <div class="section-kicker">Pembinaan Musyrif</div>
                    <h2 class="section-title">Musyrif yang Membutuhkan Pendampingan</h2>
                    <p class="section-copy">
                        Tidak hanya berdasarkan volume setoran, tetapi juga coverage santri, alpha, dan validitas absensi.
                    </p>
                </div>
            </div>

            <div class="exec-card table-card">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Musyrif</th>
                                <th>Santri Binaan</th>
                                <th>Coverage</th>
                                <th>Setoran / Santri</th>
                                <th>Lulus Juz</th>
                                <th>Absensi Valid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($d['musyrif_performance']['rows'] as $row)
                                <tr>
                                    <td>
                                        <div class="entity-name">{{ $row['nama'] }}</div>
                                        <div class="entity-meta">
                                            Risiko alpha {{ number_format($row['alpha_rate_pct'], 1, ',', '.') }}%
                                        </div>
                                    </td>
                                    <td>{{ number_format($row['total_santri'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($row['coverage_pct'], 1, ',', '.') }}%</td>
                                    <td>{{ number_format($row['avg_setoran_per_santri'], 1, ',', '.') }}</td>
                                    <td>{{ number_format($row['lulus_juz'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($row['attendance_pct'], 1, ',', '.') }}%</td>
                                    <td>
                                        <span class="status-badge {{ $row['status'] }}">
                                            <i class="bi bi-circle-fill" style="font-size:.4rem"></i>
                                            {{ $performanceLabel($row['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Data musyrif belum tersedia pada semester ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>


    </div>
@endsection


@push('modals')
    {{-- FLOATING BUTTON: PANDUAN EXECUTIVE DASHBOARD --}}
    <button type="button" class="page-guide-fab" id="btnPageGuide"
        aria-label="Buka panduan Executive Dashboard Departemen Al-Qur’an" title="Panduan membaca dashboard">
        <i class="bi bi-info-lg" aria-hidden="true"></i>
    </button>

    {{-- MODAL PANDUAN — ADOPSI POLA HALAMAN MASTER SANTRI --}}
    <div class="modal fade" id="modalPageGuide" tabindex="-1" aria-labelledby="modalPageGuideLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header page-guide-hero border-0 px-4 py-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 52px; height: 52px; flex: 0 0 52px;">
                            <i class="bi bi-compass-fill fs-4"></i>
                        </div>
                        <div>
                            <div class="small text-white-50 fw-semibold mb-1">Petunjuk Membaca Data</div>
                            <h5 class="modal-title fw-bold mb-1" id="modalPageGuideLabel">
                                Panduan Executive Dashboard Departemen Al-Qur’an
                            </h5>
                            <p class="small text-white-75 mb-0">
                                Memahami arti angka, rumus, warna status, periode pembanding, dan Peta 30 Juz.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded-4 d-flex align-items-start gap-3 mb-4">
                        <i class="bi bi-lightbulb-fill fs-5 mt-1"></i>
                        <div>
                            <div class="fw-bold mb-1">Cara membaca dashboard dalam 30 detik</div>
                            <div class="small">
                                Mulai dari <b>Status Departemen</b>, cek KPI yang berwarna kuning atau merah,
                                buka <b>Pusat Perhatian</b>, lalu lihat kelas, musyrif, dan Juz yang menjadi penyebabnya.
                                Semua angka mengikuti semester dan rentang analisis yang tampil pada halaman.
                            </div>
                        </div>
                    </div>

                    <h6 class="guide-section-title">
                        <i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Cara Membaca Tampilan
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon"><i class="bi bi-calendar-range-fill"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Rentang analisis</div>
                                        <p class="text-muted small mb-0">
                                            Semua KPI dan grafik dihitung dari tanggal awal sampai akhir yang dipilih.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon"><i class="bi bi-arrow-left-right"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Periode pembanding</div>
                                        <p class="text-muted small mb-0">
                                            Panah naik atau turun membandingkan periode sekarang dengan periode sebelumnya
                                            yang panjangnya sama.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon"><i class="bi bi-traffic-light-fill"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Warna status</div>
                                        <p class="text-muted small mb-0">
                                            Hijau berarti baik, kuning perlu perhatian, dan merah membutuhkan tindak lanjut
                                            segera.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="guide-step">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="guide-step-icon"><i class="bi bi-arrows-fullscreen"></i></span>
                                    <div>
                                        <div class="fw-bold mb-1">Peta layar penuh</div>
                                        <p class="text-muted small mb-0">
                                            Tombol Fullscreen memperbesar Peta 30 Juz. Tekan kembali atau gunakan tombol Esc
                                            untuk keluar.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="guide-section-title">
                        <i class="bi bi-heart-pulse-fill me-2 text-danger"></i>Status Departemen
                    </h6>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="guide-status-card good">
                                <strong><i class="bi bi-check-circle-fill me-1"></i>Baik / On Track</strong>
                                <div class="small text-muted">Indikator utama masih berada dalam ambang aman dan tidak ada
                                    risiko besar yang dominan.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="guide-status-card attention">
                                <strong><i class="bi bi-exclamation-circle-fill me-1"></i>Perlu Perhatian</strong>
                                <div class="small text-muted">Ada indikator yang menurun atau melewati ambang perhatian dan
                                    perlu dipantau Kepala Departemen.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="guide-status-card critical">
                                <strong><i class="bi bi-exclamation-octagon-fill me-1"></i>Kritis</strong>
                                <div class="small text-muted">Terdapat penyimpangan besar yang membutuhkan tindak lanjut
                                    atau keputusan pimpinan.</div>
                            </div>
                        </div>
                    </div>

                    <h6 class="guide-section-title">
                        <i class="bi bi-calculator-fill me-2 text-primary"></i>Arti KPI dan Rumus
                    </h6>

                    <div class="guide-action-box mb-4">
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-primary-subtle text-primary"><i
                                    class="bi bi-people-fill"></i></span>
                            <div>
                                <div class="fw-bold small">Coverage santri aktif</div>
                                <div class="text-muted small">Persentase santri semester yang memiliki minimal satu setoran
                                    berstatus <b>lulus</b> atau <b>ulang</b> pada periode terpilih.</div>
                                <span class="guide-formula">santri aktif setor ÷ total santri semester × 100%</span>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-info-subtle text-info"><i
                                    class="bi bi-journal-check"></i></span>
                            <div>
                                <div class="fw-bold small">Setoran per santri</div>
                                <div class="text-muted small">Rata-rata volume transaksi setoran. Status lulus dan ulang
                                    sama-sama dihitung sebagai aktivitas setoran.</div>
                                <span class="guide-formula">total setoran lulus/ulang ÷ total santri semester</span>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-success-subtle text-success"><i
                                    class="bi bi-patch-check-fill"></i></span>
                            <div>
                                <div class="fw-bold small">Kelulusan Juz</div>
                                <div class="text-muted small">Jumlah pasangan unik santri–Juz yang sudah lulus pada tahap
                                    ujian akhir. Satu santri yang lulus tiga Juz dihitung tiga kelulusan.</div>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-warning-subtle text-warning"><i
                                    class="bi bi-star-fill"></i></span>
                            <div>
                                <div class="fw-bold small">Rata-rata nilai ujian</div>
                                <div class="text-muted small">Hanya berasal dari ujian akhir berstatus lulus. Konversi
                                    nilai: Mumtaz 95, Jayyid Jiddan 85, Jayyid 75, dan Mardud 65.</div>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-success-subtle text-success"><i
                                    class="bi bi-person-check-fill"></i></span>
                            <div>
                                <div class="fw-bold small">Absensi valid musyrif</div>
                                <div class="text-muted small">Persentase catatan absensi musyrif yang diterima sebagai
                                    valid dibanding seluruh log valid, suspect, dan rejected.</div>
                                <span class="guide-formula">log valid ÷ seluruh log absensi × 100%</span>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-danger-subtle text-danger"><i
                                    class="bi bi-person-exclamation"></i></span>
                            <div>
                                <div class="fw-bold small">Santri risiko alpha</div>
                                <div class="text-muted small">Santri yang memiliki minimal
                                    {{ number_format($d['thresholds']['alpha_count'] ?? 3, 0, ',', '.') }} catatan alpha
                                    dalam semester aktif.</div>
                            </div>
                        </div>
                    </div>

                    <div class="guide-highlight p-4 mb-4">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="guide-step-icon"><i class="bi bi-grid-3x3-gap-fill"></i></span>
                            <div>
                                <h6 class="fw-bold mb-1">Cara membaca Peta 30 Juz</h6>
                                <p class="text-muted small mb-0">
                                    Setiap card mewakili satu Juz. Saat fullscreen, card diperbesar agar angka dan batang
                                    lebih mudah dibaca dalam rapat atau layar besar.
                                </p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="guide-step">
                                    <div class="fw-bold text-primary mb-1"><i
                                            class="bi bi-bar-chart-fill me-1"></i>Progress</div>
                                    <div class="text-muted small">Jumlah santri yang tercatat pada tahap harian, tahap 1,
                                        tahap 2, atau tahap 3 untuk Juz tersebut. Progress belum berarti lulus ujian akhir.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="guide-step">
                                    <div class="fw-bold text-success mb-1"><i
                                            class="bi bi-patch-check-fill me-1"></i>Lulus</div>
                                    <div class="text-muted small">Jumlah santri unik yang sudah lulus ujian akhir pada Juz
                                        tersebut.</div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-warning border-0 rounded-4 small mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Panjang batang merupakan perbandingan relatif terhadap angka tertinggi di Peta 30 Juz saat ini,
                            <b>bukan persentase target 0–100%</b>.
                        </div>
                    </div>

                    <h6 class="guide-section-title">
                        <i class="bi bi-exclamation-diamond-fill me-2 text-warning"></i>Pusat Perhatian dan Evaluasi
                    </h6>

                    <div class="guide-action-box mb-4">
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-warning-subtle text-warning"><i
                                    class="bi bi-bell-fill"></i></span>
                            <div>
                                <div class="fw-bold small">Pusat Perhatian</div>
                                <div class="text-muted small">Merangkum santri belum setor, santri tidak aktif, risiko
                                    alpha, kelas tanpa aktivitas, absensi belum lengkap, serta masalah integritas data.
                                </div>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-primary-subtle text-primary"><i
                                    class="bi bi-building-fill-check"></i></span>
                            <div>
                                <div class="fw-bold small">Evaluasi kelas</div>
                                <div class="text-muted small">Dinilai dari coverage, setoran per santri, kelulusan, dan
                                    risiko alpha; bukan hanya dari jumlah setoran mentah.</div>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-info-subtle text-info"><i
                                    class="bi bi-person-badge-fill"></i></span>
                            <div>
                                <div class="fw-bold small">Evaluasi musyrif</div>
                                <div class="text-muted small">Mempertimbangkan coverage santri binaan, produktivitas,
                                    kelulusan, risiko alpha, dan validitas absensi. Label pendampingan bukan hukuman atau
                                    penilaian final personal.</div>
                            </div>
                        </div>
                        <div class="guide-action-row">
                            <span class="guide-action-icon bg-danger-subtle text-danger"><i
                                    class="bi bi-database-exclamation"></i></span>
                            <div>
                                <div class="fw-bold small">Integritas data</div>
                                <div class="text-muted small">Peringatan ketika placement semester atau semester transaksi
                                    belum lengkap. Dalam kondisi ini angka tetap tampil, tetapi perlu dibaca dengan
                                    hati-hati.</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 rounded-4 small mb-0">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Benchmark warna masih menggunakan ambang kendali konfigurasi sistem. Angka tersebut bukan target
                        resmi lembaga sampai target per semester ditetapkan.
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-coreui-dismiss="modal">
                        Tutup
                    </button>
                    <button type="button" class="btn text-white rounded-pill px-4"
                        style="background: var(--exec-purple);" data-coreui-dismiss="modal">
                        <i class="bi bi-check-circle-fill me-1"></i> Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rangeInputs = document.querySelectorAll('input[name="range"]');
            const customFields = document.getElementById('customRangeFields');

            rangeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    customFields.style.display = this.value === 'custom' ? '' : 'none';
                });
            });

            const fullscreenTarget = document.getElementById('juzFullscreenTarget');
            const fullscreenButton = document.getElementById('toggleJuzFullscreen');
            const fullscreenIcon = fullscreenButton?.querySelector('[data-fullscreen-icon]');
            const fullscreenLabel = fullscreenButton?.querySelector('[data-fullscreen-label]');

            const nativeFullscreenElement = () =>
                document.fullscreenElement || document.webkitFullscreenElement || null;

            const isJuzFullscreen = () =>
                nativeFullscreenElement() === fullscreenTarget || fullscreenTarget?.classList.contains(
                    'is-pseudo-fullscreen');

            const syncFullscreenButton = () => {
                const active = isJuzFullscreen();

                fullscreenButton?.setAttribute('aria-pressed', active ? 'true' : 'false');
                fullscreenButton?.setAttribute(
                    'title',
                    active ? 'Keluar dari layar penuh' : 'Tampilkan Peta 30 Juz dalam layar penuh'
                );

                if (fullscreenIcon) {
                    fullscreenIcon.className = active ?
                        'bi bi-fullscreen-exit' :
                        'bi bi-arrows-fullscreen';
                }

                if (fullscreenLabel) {
                    fullscreenLabel.textContent = active ? 'Keluar Fullscreen' : 'Fullscreen';
                }
            };

            const enterJuzFullscreen = async () => {
                if (!fullscreenTarget) return;

                try {
                    if (fullscreenTarget.requestFullscreen) {
                        await fullscreenTarget.requestFullscreen();
                    } else if (fullscreenTarget.webkitRequestFullscreen) {
                        fullscreenTarget.webkitRequestFullscreen();
                    } else {
                        fullscreenTarget.classList.add('is-pseudo-fullscreen');
                        document.body.classList.add('exec-pseudo-fullscreen-active');
                        syncFullscreenButton();
                    }
                } catch (error) {
                    fullscreenTarget.classList.add('is-pseudo-fullscreen');
                    document.body.classList.add('exec-pseudo-fullscreen-active');
                    syncFullscreenButton();
                }
            };

            const exitJuzFullscreen = async () => {
                if (!fullscreenTarget) return;

                if (fullscreenTarget.classList.contains('is-pseudo-fullscreen')) {
                    fullscreenTarget.classList.remove('is-pseudo-fullscreen');
                    document.body.classList.remove('exec-pseudo-fullscreen-active');
                    syncFullscreenButton();
                    return;
                }

                try {
                    if (document.exitFullscreen) {
                        await document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    }
                } catch (error) {
                    syncFullscreenButton();
                }
            };

            fullscreenButton?.addEventListener('click', function() {
                if (isJuzFullscreen()) {
                    exitJuzFullscreen();
                } else {
                    enterJuzFullscreen();
                }
            });

            document.addEventListener('fullscreenchange', syncFullscreenButton);
            document.addEventListener('webkitfullscreenchange', syncFullscreenButton);

            const pageGuideButton = document.getElementById('btnPageGuide');
            const pageGuideElement = document.getElementById('modalPageGuide');
            let pageGuideModal = null;

            if (pageGuideElement) {
                if (window.coreui?.Modal) {
                    pageGuideModal = typeof window.coreui.Modal.getOrCreateInstance === 'function' ?
                        window.coreui.Modal.getOrCreateInstance(pageGuideElement) :
                        new window.coreui.Modal(pageGuideElement);
                } else if (window.bootstrap?.Modal) {
                    pageGuideModal = window.bootstrap.Modal.getOrCreateInstance(pageGuideElement);
                }
            }

            pageGuideButton?.addEventListener('click', function() {
                pageGuideModal?.show();
            });

            document.addEventListener('keydown', function(event) {
                if (event.key !== 'Escape') return;

                if (fullscreenTarget?.classList.contains('is-pseudo-fullscreen')) {
                    exitJuzFullscreen();
                }
            });

            syncFullscreenButton();

            if (typeof Chart === 'undefined') return;

            let charts = [];

            const cssVar = name => getComputedStyle(document.documentElement)
                .getPropertyValue(name)
                .trim();

            const chartTheme = () => ({
                text: cssVar('--exec-muted'),
                grid: cssVar('--exec-grid'),
                purple: cssVar('--exec-purple'),
                tosca: cssVar('--exec-tosca'),
                success: cssVar('--exec-success'),
                danger: cssVar('--exec-danger'),
                warning: cssVar('--exec-warning'),
                tooltip: cssVar('--exec-tooltip'),
                tooltipText: cssVar('--exec-tooltip-text')
            });

            const tooltipOptions = theme => ({
                backgroundColor: theme.tooltip,
                titleColor: theme.tooltipText,
                bodyColor: theme.tooltipText,
                borderColor: theme.grid,
                borderWidth: 1,
                padding: 12,
                cornerRadius: 10
            });

            const renderCharts = () => {
                charts.forEach(chart => chart.destroy());
                charts = [];

                const theme = chartTheme();
                const trendCanvas = document.getElementById('executiveTrendChart');
                const attendanceCanvas = document.getElementById('attendanceStatusChart');

                if (trendCanvas) {
                    charts.push(new Chart(trendCanvas, {
                        type: 'line',
                        data: {
                            labels: @json($d['trend']['labels']),
                            datasets: [{
                                    label: 'Setoran',
                                    data: @json($d['trend']['setoran']),
                                    borderColor: theme.purple,
                                    backgroundColor: theme.purple,
                                    tension: .35,
                                    borderWidth: 2.5,
                                    pointRadius: 2.5,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Kelulusan Juz',
                                    data: @json($d['trend']['lulus_juz']),
                                    borderColor: theme.success,
                                    backgroundColor: theme.success,
                                    tension: .35,
                                    borderWidth: 2.3,
                                    pointRadius: 2.5,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Alpha',
                                    data: @json($d['trend']['alpha']),
                                    borderColor: theme.danger,
                                    backgroundColor: theme.danger,
                                    tension: .35,
                                    borderWidth: 2,
                                    pointRadius: 2,
                                    borderDash: [5, 5],
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    labels: {
                                        color: theme.text,
                                        usePointStyle: true,
                                        boxWidth: 8
                                    }
                                },
                                tooltip: tooltipOptions(theme)
                            },
                            scales: {
                                x: {
                                    ticks: {
                                        color: theme.text,
                                        maxRotation: 0,
                                        autoSkip: true
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
                                    ticks: {
                                        color: theme.text,
                                        precision: 0
                                    },
                                    grid: {
                                        color: theme.grid
                                    },
                                    border: {
                                        color: theme.grid
                                    }
                                },
                                y1: {
                                    beginAtZero: true,
                                    position: 'right',
                                    ticks: {
                                        color: theme.text,
                                        precision: 0
                                    },
                                    grid: {
                                        drawOnChartArea: false
                                    },
                                    border: {
                                        color: theme.grid
                                    }
                                }
                            }
                        }
                    }));
                }

                if (attendanceCanvas) {
                    charts.push(new Chart(attendanceCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: ['Valid', 'Suspect', 'Rejected'],
                            datasets: [{
                                data: [
                                    {{ $d['attendance']['valid_records'] }},
                                    {{ $d['attendance']['suspect_records'] }},
                                    {{ $d['attendance']['rejected_records'] }}
                                ],
                                backgroundColor: [theme.success, theme.warning, theme
                                    .danger],
                                borderWidth: 0,
                                hoverOffset: 5
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
                                        color: theme.text,
                                        usePointStyle: true,
                                        boxWidth: 8,
                                        padding: 16
                                    }
                                },
                                tooltip: tooltipOptions(theme)
                            }
                        }
                    }));
                }
            };

            renderCharts();

            const themeObserver = new MutationObserver(mutations => {
                if (mutations.some(mutation => mutation.attributeName === 'data-coreui-theme')) {
                    requestAnimationFrame(renderCharts);
                }
            });

            themeObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-coreui-theme']
            });
        });
    </script>
@endpush
