<table>
    <thead>
        <tr>
            <th colspan="12">Rekap Hafalan per Musyrif - Periode: {{ $periode }}</th>
        </tr>
        <tr>
            <th>No</th>
            <th>Musyrif</th>
            <th>Santri Binaan</th>
            <th>Santri Aktif</th>
            <th>Setoran Harian</th>
            <th>Ujian / Juz</th>
            <th>Cakupan Ujian (%)</th>
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
                <td>{{ $row->nama ?? '-' }}</td>
                <td>{{ (int) ($row->jumlah_santri ?? 0) }}</td>
                <td>{{ (int) ($row->santri_aktif_setoran ?? 0) }}</td>
                <td>{{ (int) ($row->jumlah_setoran_harian ?? 0) }}</td>
                <td>{{ (int) ($row->jumlah_ujian ?? 0) }}</td>
                <td>{{ is_null($row->coverage_ujian_pct) ? '0' : number_format((float) $row->coverage_ujian_pct, 1) }}</td>
                <td>{{ (int) ($row->hadir_tidak_setor ?? 0) }}</td>
                <td>{{ (int) ($row->sakit ?? 0) }}</td>
                <td>{{ (int) ($row->izin ?? 0) }}</td>
                <td>{{ (int) ($row->alpha ?? 0) }}</td>
                <td>{{ is_null($row->rata_nilai_ujian) ? '-' : number_format((float) $row->rata_nilai_ujian, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
