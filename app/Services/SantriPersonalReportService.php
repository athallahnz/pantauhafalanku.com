<?php

namespace App\Services;

use App\Models\TahsinExam;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SantriPersonalReportService
{
    public const HEADERS = ['Tanggal', 'Semester', 'Jenis / Tahap', 'Materi', 'Status / Hasil', 'Nilai', 'Percobaan', 'Musyrif Pencatat', 'Catatan'];

    public function query(string $table, int $santriId, ?int $semesterId): Builder
    {
        return DB::table($table)->where($table.'.santri_id', $santriId)
            ->when($semesterId !== null, fn (Builder $q) => $q->where($table.'.semester_id', $semesterId));
    }

    public function examQuery(int $santriId, ?int $semesterId): Builder
    {
        return $this->query('tahsin_exams', $santriId, $semesterId)->orderByDesc('tanggal')->orderByDesc('id');
    }

    public function examSummary(int $santriId, ?int $semesterId): array
    {
        $rows = $this->examQuery($santriId, $semesterId)->get();
        return ['total' => $rows->count(), 'passed' => $rows->where('result', 'passed')->count(),
            'repeat' => $rows->where('result', 'repeat')->count(), 'promotion' => $rows->where('exam_type', 'promotion')->count(),
            'semester' => $rows->where('exam_type', 'semester')->count()];
    }

    /** Plain text rows shared by PDF and Excel; escaping belongs to the renderer. */
    public function histories(int $santriId, ?int $semesterId): array
    {
        $semesters = DB::table('semesters as s')->leftJoin('tahun_ajarans as y', 'y.id', '=', 's.tahun_ajaran_id')
            ->select('s.id', 's.nama', 'y.nama as tahun')->get()->keyBy('id');
        $musyrifs = DB::table('musyrifs')->pluck('nama', 'id');
        $grades = TahsinExam::gradeLabels();
        $statuses = ['lulus' => 'Lulus', 'ulang' => 'Mengulang', 'hadir_tidak_setor' => 'Hadir Tidak Setor',
            'hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha', 'passed' => 'Lulus', 'repeat' => 'Mengulang'];
        $histories = [];
        foreach (['Hafalan' => 'hafalans', 'Tahsin' => 'tahsins', 'Tilawah' => 'tilawahs', 'Ujian Tahsin' => 'tahsin_exams'] as $name => $table) {
            $date = $table === 'hafalans' ? 'tanggal_setoran' : 'tanggal';
            $query = $this->query($table, $santriId, $semesterId);
            if (in_array($table, ['hafalans', 'tilawahs'], true)) {
                $query->leftJoin('hafalan_templates as ht', 'ht.id', '=', $table.'.hafalan_template_id')
                    ->select($table.'.*', 'ht.label as materi', 'ht.juz', 'ht.tahap');
            }
            $rows = $query->orderByDesc($table.'.'.$date)->orderByDesc($table.'.id')->get();
            $histories[$name] = [];
            foreach ($rows as $row) {
                $semester = isset($semesters[$row->semester_id])
                    ? $semesters[$row->semester_id]->nama.' — '.$semesters[$row->semester_id]->tahun : 'Data lama / tanpa semester';
                $attempt = '-';
                $note = $row->catatan ?: '-';
                $grade = $grades[$row->nilai_label ?? ''] ?? ($row->nilai_label ?? '-');
                $status = $statuses[$row->status ?? ''] ?? ($row->status ?? '-');
                if ($table === 'hafalans') {
                    $kind = $row->tahap ?? '-';
                    $materi = ($row->juz ? 'Juz '.$row->juz.' — ' : '').($row->materi ?? '-');
                } elseif ($table === 'tahsins') {
                    $kind = 'Pertemuan Tahsin';
                    $materi = (TahsinExam::bookLabels()[$row->buku] ?? $row->buku).' — Halaman '.($row->halaman ?? '-');
                } elseif ($table === 'tilawahs') {
                    $kind = match ($row->entry_type) {
                        'individual' => match ($row->reading_purpose) {
                            'continuation' => 'Mandiri Lanjut', 'review' => 'Mandiri Murojaah', default => 'Mandiri / tujuan belum diketahui',
                        },
                        'group' => 'Kelompok', 'catchup' => 'Susulan', default => 'Data lama / jenis belum diketahui',
                    };
                    $parser = app(TilawahProgressService::class);
                    $materi = $parser->rangeLabel($row->catatan) ?? ($row->materi ?: '-');
                    $note = $parser->display($row->catatan) ?: '-';
                    $grade = '-';
                } else {
                    $kind = TahsinExam::examTypeLabels()[$row->exam_type] ?? $row->exam_type;
                    $materi = TahsinExam::bookLabels()[$row->buku] ?? $row->buku;
                    $grade = $grades[$row->grade_label] ?? $row->grade_label;
                    $status = $statuses[$row->result] ?? $row->result;
                    $attempt = $row->attempt_number;
                    if ($row->next_book) {
                        $note .= ' | Rekomendasi: '.(TahsinExam::bookLabels()[$row->next_book] ?? $row->next_book);
                    }
                }
                $histories[$name][] = [(string) $row->$date, $semester, $kind, $materi, $status, $grade ?: '-', $attempt,
                    $musyrifs[$row->musyrif_id] ?? '-', (string) $note];
            }
        }
        return $histories;
    }
}
