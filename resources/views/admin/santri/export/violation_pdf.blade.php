<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <title>
        Executive Discipline Intelligence Report
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 11px;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .wrapper {
            padding: 24px;
        }

        /* HERO */

        .hero {
            background: #0f172a;
            color: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .hero-title {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .hero-sub {
            font-size: 12px;
            color: #cbd5e1;
        }

        .period {
            margin-top: 12px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 10px;
        }

        /* KPI */

        .kpi-table {
            width: 100%;
            margin-bottom: 24px;
        }

        .kpi-table td {
            width: 25%;
            padding-right: 10px;
        }

        .kpi-card {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 14px;
            padding: 16px;
        }

        .kpi-label {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
        }

        /* SECTION */

        .section {
            margin-bottom: 26px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 14px;
            color: #0f172a;
        }

        /* BOX */

        .box {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            background: white;
        }

        /* TABLE */

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table thead th {
            background: #0f172a;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 10px;
        }

        .report-table tbody td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .text-center {
            text-align: center;
        }

        /* BADGE */

        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        /* INSIGHT */

        .insight {
            line-height: 1.9;
            font-size: 11px;
        }

        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
        }
    </style>
</head>

<body>

    <div class="wrapper">

        {{-- HERO --}}
        <div class="hero">

            <div class="hero-title">
                Executive Discipline Intelligence Report
            </div>

            <div class="hero-sub">
                Monitoring kedisiplinan & evaluasi halaqah santri
            </div>

            <div class="period">

                Periode:

                {{ $startDate->translatedFormat('d F Y') }}

                —

                {{ $endDate->translatedFormat('d F Y') }}

            </div>

        </div>

        {{-- KPI --}}
        <table class="kpi-table">

            <tr>

                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">
                            TOTAL PELANGGARAN
                        </div>

                        <div class="kpi-value">
                            {{ $summary['total_pelanggaran'] }}
                        </div>
                    </div>
                </td>

                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">
                            TOTAL POIN
                        </div>

                        <div class="kpi-value">
                            {{ $summary['total_poin'] }}
                        </div>
                    </div>
                </td>

                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">
                            SANTRI TERLIBAT
                        </div>

                        <div class="kpi-value">
                            {{ $summary['total_santri_terlibat'] }}
                        </div>
                    </div>
                </td>

                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">
                            HARI TERAWAN
                        </div>

                        <div class="kpi-value">
                            {{ $summary['hari_terrawan'] }}
                        </div>
                    </div>
                </td>

            </tr>

        </table>

        {{-- INSIGHT --}}
        <div class="section">

            <div class="section-title">
                Risk Insight Analytics
            </div>

            <div class="box insight">

                <div>
                    • Santri kritis:
                    <strong>
                        {{ $riskInsight['critical_santri'] }}
                    </strong>
                </div>

                <div>
                    • Musyrif high-risk:
                    <strong>
                        {{ $riskInsight['high_risk_musyrif'] }}
                    </strong>
                </div>

                <div>
                    • Kelas paling bermasalah:
                    <strong>
                        {{ $riskInsight['most_problematic_kelas'] ?? '-' }}
                    </strong>
                </div>

                <div>
                    • Musyrif paling bermasalah:
                    <strong>
                        {{ $riskInsight['most_problematic_musyrif'] ?? '-' }}
                    </strong>
                </div>

                <div>
                    • Trend pelanggaran:
                    <strong>
                        {{ $riskInsight['trend'] }}
                    </strong>
                </div>

            </div>

        </div>

        {{-- TOP MUSYRIF --}}
        <div class="section">

            <div class="section-title">
                Top Musyrif Monitoring
            </div>

            <table class="report-table">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Musyrif</th>
                        <th class="text-center">
                            Total Alpha
                        </th>
                        <th class="text-center">
                            Total Poin
                        </th>
                        <th class="text-center">
                            Total Santri
                        </th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($topMusyrif as $item)
                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $item->musyrif->nama ?? '-' }}
                            </td>

                            <td class="text-center">
                                {{ $item->total_alpha }}
                            </td>

                            <td class="text-center">
                                {{ $item->total_poin }}
                            </td>

                            <td class="text-center">
                                {{ $item->total_santri }}
                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

        {{-- TOP SANTRI --}}
        <div class="section">

            <div class="section-title">
                Santri Paling Kritis
            </div>

            <table class="report-table">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Santri</th>

                        <th>Kelas</th>

                        <th>Musyrif</th>

                        <th class="text-center">
                            Alpha
                        </th>

                        <th class="text-center">
                            Poin
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach ($topSantri as $item)
                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $item->santri->nama ?? '-' }}
                            </td>

                            <td>
                                {{ $item->santri->kelas->nama_kelas ?? '-' }}
                            </td>

                            <td>
                                {{ $item->musyrif->nama ?? '-' }}
                            </td>

                            <td class="text-center">
                                {{ $item->total_alpha }}
                            </td>

                            <td class="text-center">
                                {{ $item->total_poin }}
                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

        {{-- FOOTER --}}
        <div class="footer">

            Generated by SIMTAQU at •

            {{ now()->translatedFormat('d F Y H:i') }}

        </div>

    </div>

</body>

</html>
