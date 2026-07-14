<table>
    <thead>
        <tr>
            <th colspan="11">Rekap Hafalan per Santri - Periode: {{ $periode }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Kelas</th>
            <th>Nama Santri</th>
            <th>Musyrif</th>
            <th>Jumlah Setoran Harian</th>
            <th>Jumlah Ujian Juz</th>
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
                <td>{{ $row->kelas->nama_kelas ?? '-' }}</td>
                <td>{{ $row->nama ?? '-' }}</td>
                <td>{{ $row->musyrif->nama ?? '-' }}</td>
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
