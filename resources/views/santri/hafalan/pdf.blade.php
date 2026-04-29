<!DOCTYPE html>
<html>

<head>
    <title>Laporan Hafalan - {{ $santri->nama }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Kop Surat Style */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #6f42c1;
            padding-bottom: 10px;
        }

        .logo {
            height: 70px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            color: #6f42c1;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        /* Info & Ringkasan Table */
        .summary-container {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        /* Styling Ringkasan Box */
        .stats-box {
            background-color: #f8f0ff;
            border: 1px solid #e9d8fd;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .stats-item {
            display: inline-block;
            width: 32%;
            font-size: 11px;
            margin-bottom: 5px;
        }

        /* Main Table Style */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .main-table th {
            background-color: #6f42c1;
            color: white;
            font-size: 10px;
            text-transform: uppercase;
            padding: 8px;
            border: 1px solid #5a32a3;
        }

        .main-table td {
            padding: 6px;
            border: 1px solid #dee2e6;
            font-size: 10px;
            text-align: center;
        }

        /* Status Colors */
        .text-success {
            color: #198754;
            font-weight: bold;
        }

        .text-danger {
            color: #dc3545;
            font-weight: bold;
        }

        .text-primary {
            color: #0d6efd;
            font-weight: bold;
        }

        .text-warning {
            color: #856404;
            font-weight: bold;
        }

        .text-secondary {
            color: #6c757d;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            width: 100%;
        }

        .signature {
            float: right;
            width: 200px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="header">
        @php
            // Logic logo aman untuk PDF
            $path = public_path('assets/logos-primary.png');
            if (file_exists($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            } else {
                $base64 = '';
            }
        @endphp

        @if ($base64)
            <img src="{{ $base64 }}" class="logo">
        @endif

        <div class="title">Laporan Capaian Hafalan Al-Qur'an</div>
        <div style="font-size: 13px; font-weight: bold;">PONDOK PESANTREN DARUT TAQWA PONOROGO</div>
        <div style="font-size: 10px; color: #555;">Desan, Pintu, Jenangan, Ponorogo Regency, East Java 63492 • Telp: 0877-5877-1598 •
            ppdaruttaqwa.com</div>
    </div>

    <div class="summary-container">
        <table class="info-table">
            <tr>
                <td width="15%"><strong>Nama Santri</strong></td>
                <td width="35%">: {{ $santri->nama }}</td>
                <td width="15%"><strong>Periode</strong></td>
                <td width="35%">: {{ $periode }}</td>
            </tr>
            <tr>
                <td><strong>NIS / Kelas</strong></td>
                <td>: {{ $santri->nis ?? '-' }} / {{ $santri->kelas?->nama_kelas ?? '-' }}</td>
                <td><strong>Tgl Cetak</strong></td>
                <td>: {{ $tanggal_cetak }}</td>
            </tr>
        </table>

        {{-- Ringkasan Statistik Baru --}}
        <div class="stats-box">
            <div
                style="font-weight: bold; font-size: 12px; color: #6f42c1; margin-bottom: 8px; border-bottom: 1px solid #e9d8fd; padding-bottom: 3px;">
                Ringkasan Kehadiran & Setoran
            </div>
            <div class="stats-item">Total Setor (Lulus/Ulang): <strong>{{ $totalSetor }}</strong></div>
            <div class="stats-item">Hadir Tidak Setor: <strong>{{ $totalHTS }}</strong></div>
            <div class="stats-item">Izin Sakit: <strong>{{ $totalSakit }}</strong></div>
            <div class="stats-item">Izin Syar'i: <strong>{{ $totalIzin }}</strong></div>
            <div class="stats-item">Alpha: <strong style="color:red">{{ $totalAlpha }}</strong></div>
        </div>
    </div>

    <table class="main-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Tanggal</th>
                <th width="8%">Juz</th>
                <th width="20%">Surah / Ayat</th>
                <th width="15%">Status</th>
                <th width="12%">Nilai</th>
                <th>Catatan Musyrif</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($timeline as $index => $r)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $r->tanggal_setoran ? $r->tanggal_setoran->format('d/m/Y') : '-' }}</td>
                    <td>{{ $r->template?->juz ?? '-' }}</td>
                    <td>{{ $r->template?->label ?? '-' }}</td>
                    <td>
                        @php
                            $statusClass = match ($r->status) {
                                'lulus' => 'text-success',
                                'ulang' => 'text-warning',
                                'sakit' => 'text-primary',
                                'izin' => 'text-secondary',
                                'alpha' => 'text-danger',
                                default => '',
                            };
                        @endphp
                        <span class="{{ $statusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $r->status)) }}
                        </span>
                    </td>
                    <td>
                        @if ($r->status == 'lulus' || $r->status == 'ulang')
                            {{ match ($r->nilai_label) {
                                'mumtaz' => 'Mumtaz',
                                'jayyid_jiddan' => 'Jayyid Jiddan',
                                'jayyid' => 'Jayyid',
                                'mardud' => 'Mardud',
                                default => '-',
                            } }}
                        @else
                            -
                        @endif
                    </td>
                    <td style="text-align: left; font-style: italic; color: #555;">
                        {{ $r->catatan ?? '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Tidak ada data riwayat pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
            <span>Musyrif Pembimbing,</span>
            <br><br><br><br>
            <strong>_______________________</strong><br>
            <span> {{ $santri->musyrif->nama ?? '-' }}</span>
        </div>
        <div style="clear: both;"></div>
    </div>
</body>

</html>
