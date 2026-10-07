<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DailyAcademicReportExport implements WithMultipleSheets
{
    public function __construct(private Collection $rows, private string $kind, private array $parameters) {}

    public function sheets(): array
    {
        $summary = []; $history = [];
        foreach ($this->rows as $row) {
            $base = [$row['id'], $row['nama'], (string) $row['nis'], $row['kelas'], $row['musyrif'], $row['placement_source'],
                $row['total'], $row['hadir'], $row['izin'], $row['sakit'], $row['alpha'], $row['percentage'], $row['progress_text']];
            if ($this->kind === 'tilawah') {
                $t = $row['tilawah'];
                $base = array_merge($base, [$t['completed_count'], implode(', ', $t['completed'])], array_values($t['activity_counts']), array_values($t['unique_ayat']));
            }
            $summary[] = $base;
            foreach ($row['history'] as $item) {
                $history[] = [$row['id'], $row['nama'], (string) $row['nis'], $item['id'], $item['tanggal'],
                    $item['jenis'], $item['materi'], $item['status'], $item['nilai'], $item['pencatat'], $item['catatan']];
            }
        }
        $headers = ['ID Santri', 'Santri', 'NIS', 'Kelas', 'Musyrif Pembina', 'Sumber Penempatan', 'Record', 'Hadir', 'Izin', 'Sakit', 'Alpha',
            $this->kind === 'tahsin' ? 'Progres Halaman (%)' : 'Mandiri Lanjut (%)', 'Detail Progres'];
        if ($this->kind === 'tilawah') {
            $headers = array_merge($headers, ['Juz Unik Selesai', 'Daftar Juz', 'Mandiri Lanjut', 'Mandiri Murojaah', 'Kelompok', 'Susulan', 'Data Lama',
                'Ayat Unik Kelompok', 'Ayat Unik Susulan', 'Ayat Unik Mandiri Lanjut']);
        }
        return [new AcademicMonitoringSheet('Parameter', ['Keterangan', 'Nilai'], $this->parameters),
            new AcademicMonitoringSheet('Rekap Santri', $headers, $summary),
            new AcademicMonitoringSheet('Riwayat', ['ID Santri', 'Santri', 'NIS', 'ID Record', 'Tanggal', 'Jenis', 'Materi', 'Status', 'Nilai', 'Musyrif Pencatat', 'Catatan'], $history)];
    }
}
