<table>

    {{-- REPORT TITLE --}}
    <tr>
        <td colspan="11">
            EXECUTIVE REPORT HAFALAN SANTRI
        </td>
    </tr>

    <tr>
        <td colspan="11">
            Periode: {{ $periode }}
        </td>
    </tr>

    <tr></tr>

    {{-- TABLE HEADER --}}
    <thead>
        <tr>
            <th>No</th>

            <th>Kelas</th>

            <th>Santri</th>

            <th>Musyrif</th>

            <th>Total Setoran</th>

            <th>Hadir Tidak Setor</th>

            <th>Sakit</th>

            <th>Izin</th>

            <th>Alpha</th>

            <th>Rata-rata Nilai</th>

            <th>Kategori Performa</th>
        </tr>
    </thead>

    <tbody>

        @foreach ($data as $row)
            @php

                $nilai = $row->rata_nilai;

                $kategori = '-';

                if (!is_null($nilai)) {
                    if ($nilai >= 90) {
                        $kategori = 'Mumtaz';
                    } elseif ($nilai >= 80) {
                        $kategori = 'Jayyid Jiddan';
                    } elseif ($nilai >= 70) {
                        $kategori = 'Jayyid';
                    } else {
                        $kategori = 'Mardud';
                    }
                }

            @endphp

            <tr>

                <td>{{ $loop->iteration }}</td>

                <td>
                    {{ $row->kelas->nama_kelas ?? '-' }}
                </td>

                <td>
                    {{ $row->nama }}
                </td>

                <td>
                    {{ $row->musyrif->nama ?? '-' }}
                </td>

                <td>
                    {{ (int) ($row->total_setor ?? 0) }}
                </td>

                <td>
                    {{ (int) ($row->hadir_tidak_setor ?? 0) }}
                </td>

                <td>
                    {{ (int) ($row->sakit ?? 0) }}
                </td>

                <td>
                    {{ (int) ($row->izin ?? 0) }}
                </td>

                <td>
                    {{ (int) ($row->alpha ?? 0) }}
                </td>

                <td>
                    {{ !is_null($nilai) ? number_format($nilai, 2) : '' }}
                </td>

                <td>
                    {{ $kategori }}
                </td>

            </tr>
        @endforeach

    </tbody>

</table>
