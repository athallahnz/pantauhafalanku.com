<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AcademicMonitoringExport implements WithMultipleSheets
{
    public function __construct(private Collection $rows, private string $kind, private array $parameters)
    {
    }

    public function sheets(): array
    {
        $exams = $this->kind === 'exams';
        $states = ['passed' => 'Lulus', 'repeat' => 'Mengulang', 'not_examined' => 'Belum ujian',
            'no_activity' => 'Belum ada catatan', 'not_started' => 'Aktif, belum progres',
            'in_progress' => 'Berprogres', 'completed' => 'Khatam'];
        $summary = [];
        $history = [];
        foreach ($this->rows as $i => $row) {
            $summary[] = [$i + 1, $row['id'], $row['nama'], (string) $row['nis'], $row['kelas'], $row['musyrif'],
                $row['placement_source'], $row['completed_count'], $row['percentage'], implode(', ', $row['completed']),
                $row['recommendation'], $row['total'], $row['first_count'], $row['second_count'],
                $states[$row['state']] ?? $row['state'], $row['latest'], $row['latest_date']];
            foreach ($row['history'] as $record) {
                $history[] = [$row['id'], $row['nama'], (string) $row['nis'], $row['kelas'], $row['musyrif'],
                    $record['id'], $record['tanggal'], $record['pencatat'], $record['jenis'], $record['materi'],
                    $record['percobaan'], $record['nilai'], $record['hasil'], $record['catatan']];
            }
        }

        return [
            new AcademicMonitoringSheet('Parameter', ['Parameter', 'Nilai'], $this->parameters),
            new AcademicMonitoringSheet('Rekap Santri', ['No.', 'ID Santri', 'Santri', 'NIS', 'Kelas', 'Musyrif Pembina',
                'Sumber Penempatan', $exams ? 'Buku Lulus Unik' : 'Juz Lanjut Unik', 'Progres (%)',
                $exams ? 'Daftar Buku Lulus' : 'Daftar Juz', 'Rekomendasi Buku dalam Periode',
                $exams ? 'Total Ujian' : 'Total Sesi', $exams ? 'Kenaikan Buku' : 'Lanjut',
                $exams ? 'Ujian Semester' : 'Murojaah', $exams ? 'Hasil Ujian Terakhir' : 'Status Progres',
                'Catatan Terakhir', 'Tanggal Terakhir'], $summary),
            new AcademicMonitoringSheet('Riwayat', ['ID Santri', 'Santri', 'NIS', 'Kelas', 'Musyrif Pembina',
                'ID Catatan', 'Tanggal', 'Musyrif Pencatat', 'Jenis / Tujuan', 'Buku / Juz',
                'Percobaan', 'Nilai', 'Hasil / Kehadiran', 'Catatan'], $history),
        ];
    }
}
