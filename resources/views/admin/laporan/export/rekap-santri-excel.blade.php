<table>
    <thead>
        <tr>
            <th colspan="6">
                Rekap Hafalan per Santri - Periode: {{ $periode }}
            </th>
        </tr>
        <tr>
            <th>No</th>
            <th>Kelas</th>
            <th>Santri</th>
            <th>Musyrif</th>
            <th>Jumlah Setoran</th>
            <th>Rata-rata Nilai</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach ($data as $row)
            <tr>
                <td>{{ $no++ }}</td>
                <td>{{ $row->kelas->nama_kelas ?? '-' }}</td>
                <td>{{ $row->nama }}</td>
                <td>{{ $row->musyrif->nama ?? '-' }}</td>
                <td>{{ (int) ($row->total_setoran ?? 0) }}</td>
                <td>
                    @if ($row->rata_nilai)
                        {{ number_format($row->rata_nilai, 2) }}
                    @else
                        -
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
