<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Executive Report Hafalan per Musyrif</title>

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

        /* BOX */
        .box {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 16px;
            background: white;
        }

        /* ANALYTICS */
        .analytics-table {
            width: 100%;
        }

        .analytics-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .progress-item {
            margin-bottom: 14px;
        }

        .bar-wrap {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
            margin-top: 5px;
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

        /* BADGE */
        .rank-badge {
            background: #0f172a;
            color: white;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: bold;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
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
                Executive Report Hafalan per Musyrif
            </div>

            <div class="hero-subtitle">
                Dashboard performa pembinaan & monitoring halaqah santri
            </div>
            
            <div class="period">
                Periode: {{ $periode }}
            </div>
        </div>

        {{-- KPI --}}
        <table class="kpi-table">
            <tr>
                <td>
                    <div class="kpi-card">
                        <div class="kpi-label">TOTAL MUSYRIF</div>
                        <div class="kpi-value">
                            {{ $summary['total_musyrif'] }}
                        </div>
                    </div>
                </td>

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
            </tr>
        </table>

        {{-- ANALYTICS --}}
        <div class="section">

            <div class="section-title">
                Insight Analytics Pembinaan
            </div>

            <table class="analytics-table">
                <tr>

                    <td>
                        <div class="box">

                            <strong>Monitoring Pembinaan</strong>

                            <div style="margin-top: 14px;">

                                <div class="progress-item">
                                    Konsistensi Halaqah

                                    <div class="bar-wrap">
                                        <div class="bar" style="width: 82%;"></div>
                                    </div>
                                </div>

                                <div class="progress-item">
                                    Produktivitas Setoran

                                    <div class="bar-wrap">
                                        <div class="bar" style="width: 88%;"></div>
                                    </div>
                                </div>

                                <div class="progress-item">
                                    Kualitas Hafalan

                                    <div class="bar-wrap">
                                        <div class="bar" style="width: {{ min($summary['avg_nilai'], 100) }}%;">
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </td>

                    <td>
                        <div class="box">

                            <strong>Insight Evaluasi</strong>

                            <div style="margin-top: 14px; line-height: 1.8;">

                                <div>
                                    • Total musyrif aktif:
                                    <strong>{{ $summary['total_musyrif'] }}</strong>
                                </div>

                                <div>
                                    • Total santri binaan:
                                    <strong>{{ $summary['total_santri'] }}</strong>
                                </div>

                                <div>
                                    • Total aktivitas setoran:
                                    <strong>{{ $summary['total_setoran'] }}</strong>
                                </div>

                                <div>
                                    • Rata-rata performa hafalan:
                                    <strong>{{ $summary['avg_nilai'] }}</strong>
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
                Ranking Top 10 Musyrif
            </div>

            <table class="report-table">

                <thead>
                    <tr>
                        <th width="10%">Rank</th>
                        <th>Musyrif</th>
                        <th width="18%">Santri</th>
                        <th width="18%">Setoran</th>
                        <th width="18%">Nilai</th>
                        <th width="18%">Evaluasi</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach ($topMusyrif as $row)
                        @php
                            $badge = 'badge-danger';
                            $label = 'Perlu Evaluasi';

                            if ($row->rata_nilai >= 90) {
                                $badge = 'badge-success';
                                $label = 'Excellent';
                            } elseif ($row->rata_nilai >= 80) {
                                $badge = 'badge-warning';
                                $label = 'Good';
                            }
                        @endphp

                        <tr>

                            <td class="text-center">
                                <span class="rank-badge">
                                    #{{ $loop->iteration }}
                                </span>
                            </td>

                            <td>
                                {{ $row->nama }}
                            </td>

                            <td class="text-center">
                                {{ $row->jumlah_santri }}
                            </td>

                            <td class="text-center">
                                {{ $row->total_setor }}
                            </td>

                            <td class="text-center">
                                {{ number_format($row->rata_nilai, 2) }}
                            </td>

                            <td class="text-center">
                                <span class="badge {{ $badge }}">
                                    {{ $label }}
                                </span>
                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

        {{-- DETAIL TABLE --}}
        <div class="section">

            <div class="section-title">
                Detail Rekap Musyrif
            </div>

            <table class="report-table">

                <thead>
                    <tr>
                        <th width="6%">No</th>
                        <th>Musyrif</th>
                        <th width="18%">Jumlah Santri</th>
                        <th width="18%">Jumlah Setoran</th>
                        <th width="18%">Rata-rata</th>
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

                            <td class="text-center">
                                {{ $row->jumlah_santri }}
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
