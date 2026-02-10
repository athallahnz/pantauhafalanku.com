<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Hafalan per Santri</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        h2,
        h3,
        h4 {
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 12px;
        }

        .sub-header {
            text-align: center;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .periode {
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
        }

        th {
            text-align: center;
            background: #f0f0f0;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Rekap Hafalan per Santri</h2>
    </div>
    <div class="sub-header">
        <div>Departemen Al Qur'an</div>
        <div class="periode">Periode: {{ $periode }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th>Kelas</th>
                <th>Santri</th>
                <th>Musyrif</th>
                <th style="width: 80px;">Jumlah Setoran</th>
                <th style="width: 90px;">Rata-rata Nilai</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp

            @forelse ($data as $row)
                <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>{{ $row->kelas->nama_kelas ?? '-' }}</td>
                    <td>{{ $row->nama }}</td>
                    <td>{{ $row->musyrif->nama ?? '-' }}</td>
                    <td class="text-center">{{ (int) ($row->total_setoran ?? 0) }}</td>
                    <td class="text-center">
                        @if ($row->rata_nilai)
                            {{ number_format($row->rata_nilai, 2) }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
