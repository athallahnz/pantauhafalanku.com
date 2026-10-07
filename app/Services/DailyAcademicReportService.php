<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Models\Tahsin;
use App\Models\TahsinExam;
use App\Models\Tilawah;
use Illuminate\Support\Collection;

class DailyAcademicReportService
{
    private const BOOK_PAGES = ['ummi_1' => 40, 'ummi_2' => 40, 'ummi_3' => 40, 'gharib_1' => 28, 'gharib_2' => 28, 'tajwid' => 50];

    public function report(string $kind, Semester $semester, array $filters, bool $includeHistory = true, ?int $onlySantri = null): Collection
    {
        $query = $kind === 'tahsin' ? Tahsin::query() : Tilawah::query();
        // Keep population independent of transaction filters and history selection.
        $recordIds = (clone $query)->where('semester_id', $semester->id)->distinct()->pluck('santri_id');
        $placements = SantriSemesterPlacement::where('semester_id', $semester->id)->get()->keyBy('santri_id');
        $ids = $placements->keys()->merge($recordIds);
        if ($semester->isActive()) { $ids = $ids->merge(Santri::query()->active()->pluck('id')); }
        $classes = Kelas::query()->get()->keyBy('id');
        $musyrifs = Musyrif::query()->pluck('nama', 'id');
        $classIds = !empty($filters['kelas_id']) ? $this->classIds((int) $filters['kelas_id'], $classes) : null;
        $query->where('semester_id', $semester->id)
            ->whereDate('tanggal', '>=', $filters['date_from'])->whereDate('tanggal', '<=', $filters['date_to']);
        if ($onlySantri !== null) { $query->where('santri_id', $onlySantri); }
        if (!empty($filters['status'])) { $query->where('status', $filters['status']); }
        if ($kind === 'tahsin' && !empty($filters['buku'])) { $query->where('buku', $filters['buku']); }
        if ($kind === 'tilawah' && !empty($filters['entry_type'])) {
            if ($filters['entry_type'] === 'legacy') {
                $query->where(fn ($q) => $q->whereNull('entry_type')->orWhereNotIn('entry_type', ['individual', 'group', 'catchup']));
            } else { $query->where('entry_type', $filters['entry_type']); }
        }
        if ($kind === 'tilawah' && $includeHistory) { $query->with('template'); }
        $records = $query->orderByDesc('tanggal')->orderByDesc('id')->get()->groupBy('santri_id');
        return Santri::query()->whereIn('id', $ids->unique()->values())->when($onlySantri !== null, fn ($q) => $q->where('id', $onlySantri))->orderBy('nama')->get()
            ->map(function ($santri) use ($semester, $kind, $filters, $placements, $classes, $musyrifs, $classIds, $records, $includeHistory) {
                $placement = $placements->get($santri->id);
                $fallback = !$placement && $semester->isActive() && $santri->status === 'aktif';
                $classId = $placement ? $placement->kelas_id : ($fallback ? $santri->kelas_id : null);
                $musyrifId = $placement ? $placement->musyrif_id : ($fallback ? $santri->musyrif_id : null);
                if ($classIds !== null && !in_array((int) $classId, $classIds, true)) { return null; }
                if (!empty($filters['musyrif_id']) && (int) $musyrifId !== (int) $filters['musyrif_id']) { return null; }
                $row = ['id' => (int) $santri->id, 'nama' => $santri->nama, 'nis' => $santri->nis,
                    'kelas' => app(AcademicMonitoringService::class)->classLabel($classes->get($classId)),
                    'musyrif' => $musyrifs->get($musyrifId, '-'),
                    'placement_source' => $placement ? 'Penempatan semester' : ($fallback ? 'Data aktif saat ini' : 'Penempatan belum tersedia')];
                if (!empty($filters['q']) && !str_contains(mb_strtolower(implode(' ', array_values($row))), mb_strtolower(trim($filters['q'])))) { return null; }
                $items = $records->get($santri->id, collect());
                $row['total'] = $items->count();
                foreach (['hadir', 'izin', 'sakit', 'alpha'] as $status) { $row[$status] = $items->where('status', $status)->count(); }
                if ($kind === 'tahsin') {
                    $books = [];
                    foreach (self::BOOK_PAGES as $book => $target) {
                        $page = (int) ($items->where('status', 'hadir')->where('buku', $book)->max('halaman') ?? 0);
                        $books[] = ['buku' => $book, 'label' => TahsinExam::bookLabels()[$book], 'halaman' => $page,
                            'target' => $target, 'percentage' => min(100, (int) round($page / $target * 100))];
                    }
                    $row['books'] = $books;
                    $row['percentage'] = (int) round(collect($books)->avg('percentage'));
                    $row['progress_text'] = implode('; ', array_map(fn ($b) => $b['label'].': '.$b['halaman'].'/'.$b['target'], $books));
                } else {
                    $row['tilawah'] = app(TilawahReportService::class)->summarize($items);
                    $row['percentage'] = $row['tilawah']['percentage'];
                    $row['progress_text'] = $row['tilawah']['completed_count'].'/30 juz Mandiri Lanjut';
                }
                if (!$includeHistory) { return $row; }
                $row['history'] = $items->map(function ($record) use ($kind, $musyrifs) {
                    $parser = app(TilawahProgressService::class);
                    $type = $kind === 'tahsin' ? 'Pertemuan Tahsin' : match ($record->entry_type) {
                        'individual' => match ($record->reading_purpose) {
                            'continuation' => 'Mandiri Lanjut', 'review' => 'Mandiri Murojaah', default => 'Mandiri / tujuan belum diketahui',
                        },
                        'group' => 'Kelompok', 'catchup' => 'Susulan', default => 'Data lama / jenis belum diketahui',
                    };
                    return ['id' => (int) $record->id, 'tanggal' => $record->tanggal->toDateString(),
                        'jenis' => $type, 'materi' => $kind === 'tahsin'
                            ? (TahsinExam::bookLabels()[$record->buku] ?? $record->buku).' — Halaman '.($record->halaman ?? '-')
                            : ($parser->rangeLabel($record->catatan) ?? ($parser->juzFromPayload($parser->parse($record->catatan)) ? 'Juz '.$parser->juzFromPayload($parser->parse($record->catatan)) : ($record->template?->label ?? 'Target data lama belum terstruktur'))),
                        'status' => $record->status, 'nilai' => $kind === 'tahsin' && $record->status === 'hadir'
                            ? (TahsinExam::gradeLabels()[$record->nilai_label] ?? '-') : '-',
                        'pencatat' => $musyrifs->get($record->musyrif_id, '-'),
                        'catatan' => $kind === 'tahsin' ? ($record->catatan ?: '-') : $parser->display($record->catatan)];
                })->all();
                return $row;
            })->filter()->values();
    }

    private function classIds(int $id, Collection $classes): array
    {
        $ids = [$id];
        do {
            $count = count($ids);
            foreach ($classes as $class) {
                if (in_array((int) $class->parent_id, $ids, true) && !in_array((int) $class->id, $ids, true)) { $ids[] = (int) $class->id; }
            }
        } while (count($ids) > $count);
        return $ids;
    }
}
