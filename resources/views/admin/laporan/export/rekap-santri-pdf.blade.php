<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Executive Report Hafalan Santri</title>

    <style>
        @page {
            margin: 18mm 14mm;
        }

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
            padding: 8px;
        }

        /* HERO */
        .hero {
            background: #0f172a;
            color: white;
            padding: 24px;
            border-radius: 16px;
            margin-bottom: 24px;
        }

        .hero-title {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .hero-subtitle {
            font-size: 12px;
            color: #cbd5e1;
        }

        .period {
            margin-top: 14px;
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
            background: #f8fafc;
            border: 1px solid #e2e8f0;
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

        /* INSIGHT */
        .info-grid {
            width: 100%;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .box {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            background: #ffffff;
        }

        .legend-item {
            margin-bottom: 10px;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
            margin-right: 6px;
        }

        .green {
            background: #22c55e;
        }

        .blue {
            background: #3b82f6;
        }

        .yellow {
            background: #facc15;
        }

        .red {
            background: #ef4444;
        }

        /* PROGRESS */
        .bar-wrap {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 4px;
        }

        .bar {
            height: 10px;
            background: #0f172a;
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

        /* RANK */
        .rank-badge {
            background: #0f172a;
            color: white;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }

        /* FOOTER */
        .footer {
            margin-top: 26px;
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
                Executive Report Hafalan Santri
            </div>

            <div class="hero-subtitle">
                Dashboard performa akademik & monitoring hafalan santri
            </div>

            <div class="period">
                Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y') }}
            </div>
        </div>

        {{-- KPI --}}
        <table class="kpi-table">
            <tr>
                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">TOTAL SANTRI</div>
                        <div class="kpi-value">
                            {{ $summary['total_santri'] }}
                        </div>
                    </div>
                </td>

                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">TOTAL SETORAN</div>
                        <div class="kpi-value">
                            {{ $summary['total_setoran'] }}
                        </div>
                    </div>
                </td>

                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">AVG NILAI</div>
                        <div class="kpi-value">
                            {{ $summary['avg_nilai'] }}
                        </div>
                    </div>
                </td>

                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">SANTRI AKTIF</div>
                        <div class="kpi-value">
                            {{ $summary['santri_aktif'] }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- INSIGHT ANALYTICS --}}
        <div class="section">
            <div class="section-title">
                Insight Analytics
            </div>

            <table class="info-grid">
                <tr>
                    <td>
                        <div class="box">
                            <strong>Distribusi Performa</strong>

                            <div style="margin-top: 14px;">

                                <div class="legend-item">
                                    <span class="legend-dot green"></span>
                                    Mumtaz : {{ $statusDistribution['mumtaz'] }}
                                </div>

                                <div class="legend-item">
                                    <span class="legend-dot blue"></span>
                                    Jayyid Jiddan : {{ $statusDistribution['jayyid_jiddan'] }}
                                </div>

                                <div class="legend-item">
                                    <span class="legend-dot yellow"></span>
                                    Jayyid : {{ $statusDistribution['jayyid'] }}
                                </div>

                                <div class="legend-item">
                                    <span class="legend-dot red"></span>
                                    Mardud : {{ $statusDistribution['mardud'] }}
                                </div>

                            </div>
                        </div>
                    </td>

                    <td>
                        <div class="box">
                            <strong>Trend Akademik</strong>

                            <div style="margin-top: 14px;">

                                <div style="margin-bottom: 12px;">
                                    Performa Hafalan

                                    <div class="bar-wrap">
                                        <div class="bar" style="width: {{ min($summary['avg_nilai'], 100) }}%;">
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-bottom: 12px;">
                                    Aktivitas Setoran

                                    <div class="bar-wrap">
                                        <div class="bar" style="width: 82%;"></div>
                                    </div>
                                </div>

                                <div>
                                    Konsistensi Santri

                                    <div class="bar-wrap">
                                        <div class="bar" style="width: 76%;"></div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- TOP 10 --}}
        <div class="section">
            <div class="section-title">
                Ranking Top 10 Santri
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th width="10%">Rank</th>
                        <th>Santri</th>
                        <th>Kelas</th>
                        <th width="15%">Setoran</th>
                        <th width="15%">Nilai</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($topSantri as $row)
                        <tr>
                            <td class="text-center">
                                <span class="rank-badge">
                                    #{{ $loop->iteration }}
                                </span>
                            </td>

                            <td>
                                {{ $row->nama }}
                            </td>

                            <td>
                                {{ $row->kelas->nama_kelas ?? '-' }}
                            </td>

                            <td class="text-center">
                                {{ $row->total_setor }}
                            </td>

                            <td class="text-center">
                                {{ number_format($row->rata_nilai, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- DETAIL TABLE --}}
        <div class="section">
            <div class="section-title">
                Detail Rekap Hafalan
            </div>

            <table class="report-table">
                <thead>
                    <tr>
                        <th width="6%">No</th>
                        <th>Santri</th>
                        <th>Kelas</th>
                        <th>Musyrif</th>
                        <th width="15%">Setoran</th>
                        <th width="15%">Rata-rata</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($data as $row)
                        <tr>
                            <td class="text-center">
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $row->nama }}
                            </td>

                            <td>
                                {{ $row->kelas->nama_kelas ?? '-' }}
                            </td>

                            <td>
                                {{ $row->musyrif->nama ?? '-' }}
                            </td>

                            <td class="text-center">
                                {{ $row->total_setor }}
                            </td>

                            <td class="text-center">
                                {{ !is_null($row->rata_nilai) ? number_format($row->rata_nilai, 2) : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- FOOTER --}}
        <div class="footer">
            Generated by SIMTAQU at {{ now()->format('d M Y H:i') }}
        </div>

    </div>

</body>

</html>
