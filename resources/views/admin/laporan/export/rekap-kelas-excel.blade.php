<table>
    <thead>
        <tr>
            <th colspan="10">Rekap Hafalan per Kelas - Periode: {{ $periode }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Kelas</th>
            <th>Jumlah Santri</th>
            <th>Setoran Harian</th>
            <th>Ujian / Juz</th>
            <th>HTS</th>
            <th>Sakit</th>
            <th>Izin</th>
            <th>Alpha</th>
            <th>Rata-rata Nilai Ujian</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row->nama_kelas ?? '-' }}</td>
                <td>{{ (int) ($row->jumlah_santri ?? 0) }}</td>
                <td>{{ (int) ($row->jumlah_setoran_harian ?? 0) }}</td>
                <td>{{ (int) ($row->jumlah_ujian ?? 0) }}</td>
                <td>{{ (int) ($row->hadir_tidak_setor ?? 0) }}</td>
                <td>{{ (int) ($row->sakit ?? 0) }}</td>
                <td>{{ (int) ($row->izin ?? 0) }}</td>
                <td>{{ (int) ($row->alpha ?? 0) }}</td>
                <td>{{ is_null($row->rata_nilai_ujian) ? '-' : number_format((float) $row->rata_nilai_ujian, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
