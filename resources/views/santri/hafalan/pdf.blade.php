<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Laporan Hafalan - {{ $santri->nama ?? 'Santri' }}</title>

    <style>
        @page {
            margin: 26px 30px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #2d2d33;
            font-size: 10px;
            line-height: 1.45;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #6f42c1;
            padding-bottom: 11px;
            margin-bottom: 16px;
            text-align: center;
        }

        .logo {
            height: 62px;
            width: auto;
            margin-bottom: 6px;
        }

        .report-title {
            margin: 0 0 3px;
            color: #6f42c1;
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .institution-name {
            margin: 0 0 2px;
            font-size: 12px;
            font-weight: bold;
        }

        .institution-address {
            margin: 0;
            color: #60606a;
            font-size: 8.5px;
        }

        .info-table,
        .summary-table,
        .main-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table {
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 3px 2px;
            vertical-align: top;
            font-size: 9.5px;
        }

        .info-label {
            width: 16%;
            font-weight: bold;
            color: #474752;
        }

        .info-value {
            width: 34%;
        }

        .summary-box {
            margin-bottom: 15px;
            padding: 9px 10px;
            border: 1px solid #e6d6f5;
            background: #faf6ff;
        }

        .summary-title {
            margin-bottom: 7px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e6d6f5;
            color: #6f42c1;
            font-size: 10.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .summary-table td {
            width: 20%;
            padding: 5px 4px;
            text-align: center;
            vertical-align: top;
        }

        .summary-number {
            display: block;
            margin-bottom: 2px;
            font-size: 15px;
            font-weight: bold;
        }

        .summary-label {
            display: block;
            color: #6a6a73;
            font-size: 8px;
        }

        .main-table {
            table-layout: fixed;
        }

        .main-table thead {
            display: table-header-group;
        }

        .main-table tr {
            page-break-inside: avoid;
        }

        .main-table th {
            padding: 7px 4px;
            border: 1px solid #5b329e;
            background: #6f42c1;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }

        .main-table td {
            padding: 5px 4px;
            border: 1px solid #dedee5;
            font-size: 8.5px;
            text-align: center;
            vertical-align: top;
            word-wrap: break-word;
        }

        .main-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .text-left {
            text-align: left !important;
        }

        .status-lulus {
            color: #198754;
            font-weight: bold;
        }

        .status-ulang {
            color: #9a6700;
            font-weight: bold;
        }

        .status-hadir_tidak_setor {
            color: #087990;
            font-weight: bold;
        }

        .status-sakit {
            color: #0d6efd;
            font-weight: bold;
        }

        .status-izin {
            color: #6c757d;
            font-weight: bold;
        }

        .status-alpha {
            color: #dc3545;
            font-weight: bold;
        }

        .empty-row {
            padding: 18px !important;
            color: #777780;
            text-align: center !important;
        }

        .footer {
            margin-top: 24px;
            page-break-inside: avoid;
        }

        .signature-table td {
            width: 50%;
            vertical-align: top;
            font-size: 9px;
        }

        .signature-box {
            width: 220px;
            margin-left: auto;
            text-align: center;
        }

        .signature-space {
            height: 54px;
        }

        .signature-name {
            display: inline-block;
            min-width: 180px;
            padding-top: 3px;
            border-top: 1px solid #44444c;
            font-weight: bold;
        }

        .print-note {
            color: #777780;
            font-size: 8px;
        }
    </style>
</head>

<body>
    <header class="header">
        @if (!empty($logoDataUri))
            <img src="{{ $logoDataUri }}" class="logo" alt="Logo lembaga">
        @endif

        <div class="report-title">Laporan Capaian Hafalan Al-Qur'an</div>
        <div class="institution-name">PONDOK PESANTREN DARUT TAQWA PONOROGO</div>
        <div class="institution-address">
            Desan, Pintu, Jenangan, Ponorogo Regency, East Java 63492
            &bull; Telp. 0877-5877-1598
            &bull; ppdaruttaqwa.com
        </div>
    </header>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama Santri</td>
            <td class="info-value">: {{ $santri->nama ?? '-' }}</td>
            <td class="info-label">Periode</td>
            <td class="info-value">: {{ $periode ?? 'Semua Riwayat' }}</td>
        </tr>
        <tr>
            <td class="info-label">NIS / Kelas</td>
            <td class="info-value">: {{ $santri->nis ?? '-' }} / {{ $santri->kelas?->nama_kelas ?? '-' }}</td>
            <td class="info-label">Tanggal Cetak</td>
            <td class="info-value">: {{ $tanggal_cetak ?? '-' }}</td>
        </tr>
    </table>

    <section class="summary-box">
        <div class="summary-title">Ringkasan Kehadiran &amp; Setoran</div>
        <table class="summary-table">
            <tr>
                <td><span class="summary-number">{{ $totalSetor ?? 0 }}</span><span class="summary-label">Setor
                        Lulus/Ulang</span></td>
                <td><span class="summary-number">{{ $totalHadirTidakSetor ?? ($totalHTS ?? 0) }}</span><span
                        class="summary-label">Hadir Tidak Setor</span></td>
                <td><span class="summary-number">{{ $totalSakit ?? 0 }}</span><span class="summary-label">Sakit</span>
                </td>
                <td><span class="summary-number">{{ $totalIzin ?? 0 }}</span><span class="summary-label">Izin</span>
                </td>
                <td><span class="summary-number">{{ $totalAlpha ?? 0 }}</span><span class="summary-label">Alpha</span>
                </td>
            </tr>
        </table>
    </section>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 7%;">Juz</th>
                <th style="width: 23%;">Surah / Ayat</th>
                <th style="width: 14%;">Status</th>
                <th style="width: 13%;">Nilai</th>
                <th style="width: 26%;">Catatan Musyrif</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($timeline as $record)
                <tr>
                    <td>{{ $record['no'] }}</td>
                    <td>{{ $record['tanggal'] }}</td>
                    <td>{{ $record['juz'] }}</td>
                    <td class="text-left">{{ $record['materi'] }}</td>
                    <td class="status-{{ $record['status_key'] }}">{{ $record['status_label'] }}</td>
                    <td>{{ $record['nilai_label'] }}</td>
                    <td class="text-left">{{ $record['catatan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-row">Tidak ada data riwayat Hafalan pada periode yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <footer class="footer">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="print-note">Dokumen ini dibuat otomatis oleh sistem pada {{ $tanggal_cetak ?? '-' }}.
                    </div>
                </td>
                <td>
                    <div class="signature-box">
                        <div>Ponorogo, {{ now()->translatedFormat('d F Y') }}</div>
                        <div>Musyrif Pembimbing,</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $santri->musyrif?->nama ?? '_______________________' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </footer>
</body>

</html>
