<!DOCTYPE html>
<html>

<head>
    <title>Laporan Hafalan Santri</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            /* Font ini dukung banyak karakter Unicode */
            color: #333;
            line-height: 1.5;
        }

        /* Kop Surat Style */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #6f42c1;
            /* Garis ganda biar formal */
            padding-bottom: 15px;
        }

        .logo {
            height: 80px;
            /* Atur tinggi logo sesuai kebutuhan */
            margin-bottom: 10px;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
            color: #6f42c1;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 3px 0;
            font-size: 13px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .main-table th {
            background-color: #f8f9fa;
            color: #6f42c1;
            font-size: 11px;
            text-transform: uppercase;
            padding: 10px;
            border: 1px solid #dee2e6;
        }

        .main-table td {
            padding: 8px;
            border: 1px solid #dee2e6;
            font-size: 11px;
            text-align: center;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            color: white;
        }

        .bg-success {
            background-color: #198754;
        }

        .bg-warning {
            background-color: #ffc107;
            color: #000;
        }

        .bg-danger {
            background-color: #dc3545;
        }

        .bg-info {
            background-color: #0dcaf0;
            color: #000;
        }

        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="header">
        @php
            $path = public_path('assets/logos-primary.png'); // Pastikan logo ada di folder public/images/
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        @endphp

        <img src="{{ $base64 }}" class="logo">

        <div class="title">Laporan Capaian Hafalan Al-Qur'an</div>
        <div style="font-size: 14px; font-weight: bold; color: #444;">Nama Pondok Pesantren / Sekolah</div>
        <div style="font-size: 11px; color: #666;">Alamat Lengkap Sekolah • No. Telp • Website</div>
        <div style="font-size: 12px; margin-top: 10px; color: #6f42c1; border-top: 1px solid #eee; padding-top: 5px;">
            Periode: {{ $overallPct ?? $periode }}
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Nama Santri</strong></td>
            <td width="35%">: {{ $santri->nama }}</td>
            <td width="15%"><strong>Total Setor</strong></td>
            <td width="35%">: {{ $totalSetor }} Kali</td>
        </tr>
        <tr>
            <td><strong>NIS</strong></td>
            <td>: {{ $santri->nis ?? '-' }}</td>
            <td><strong>Kelas</strong></td>
            <td>: {{ $santri->kelas?->nama_kelas ?? '-' }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Juz</th>
                <th>Surah / Ayat</th>
                <th>Status</th>
                <th>Nilai</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($timeline as $index => $r)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $r->tanggal_setoran ? $r->tanggal_setoran->format('d/m/Y') : '-' }}</td>
                    <td>{{ $r->template?->juz ?? '-' }}</td>
                    <td>{{ $r->template?->label ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $r->status)) }}</td>
                    <td>
                        @if ($r->nilai_label == 'mumtaz')
                            Mumtaz
                        @elseif($r->nilai_label == 'jayyid_jiddan')
                            Jayyid Jiddan
                        @elseif($r->nilai_label == 'jayyid')
                            Jayyid
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: left;">{{ $r->catatan ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}<br><br><br>
        _______________________<br>
        Musyrif / Pengampu
    </div>
</body>

</html>
