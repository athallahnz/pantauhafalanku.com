<table>
    <thead>
        <tr>
            <th colspan="5">
                Rekap Hafalan per Musyrif - Periode: {{ $periode }}
            </th>
        </tr>
        <tr>
            <th>No</th>
            <th>Musyrif</th>
            <th>Jumlah Santri Binaan</th>
            <th>Jumlah Setoran</th>
            <th>Rata-rata Nilai</th>
        </tr>
    </thead>
    <tbody>
        @php $no = 1; @endphp
        @foreach ($data as $row)
            <tr>
                <td>{{ $no++ }}</td>
                <td>{{ $row->nama }}</td>
                <td>{{ (int) $row->jumlah_santri }}</td>
                <td>{{ (int) $row->total_setoran }}</td>
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
