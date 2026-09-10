<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Musyrif;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Models\TahsinExam;
use App\Models\Tilawah;
use Illuminate\Support\Collection;

class AcademicMonitoringService
{
    public function __construct(private TilawahProgressService $progress)
    {
    }

    /**
     * One population and filter pipeline for the screen, history and export.
     * Result filters apply AFTER finding the latest exam, never before it.
     */
    public function report(string $kind, Semester $semester, array $filters): Collection
    {
        $model = $kind === 'exams' ? TahsinExam::query() : Tilawah::query()
            ->where('entry_type', Tilawah::ENTRY_TYPE_INDIVIDUAL);
        $semesterRecords = $model->where('semester_id', $semester->id)
            ->orderByDesc('tanggal')->orderByDesc('id')->get();

        $placements = SantriSemesterPlacement::query()
            ->where('semester_id', $semester->id)->get()->keyBy('santri_id');
        $ids = $placements->keys()->merge($semesterRecords->pluck('santri_id'));
        if ($semester->isActive()) {
            $ids = $ids->merge(Santri::query()->active()->pluck('id'));
        }

        $classes = Kelas::query()->get(['id', 'parent_id', 'nama_kelas', 'kelompok'])->keyBy('id');
        $musyrifs = Musyrif::query()->pluck('nama', 'id');
        $classIds = !empty($filters['kelas_id'])
            ? $this->classIds((int) $filters['kelas_id'], $classes) : null;
        $records = $semesterRecords->filter(function ($record) use ($filters): bool {
            $date = $record->tanggal->toDateString();

            return (empty($filters['date_from']) || $date >= $filters['date_from'])
                && (empty($filters['date_to']) || $date <= $filters['date_to']);
        })->groupBy('santri_id');

        return Santri::query()->whereIn('id', $ids->unique()->values())
            ->orderBy('nama')->get(['id', 'nama', 'nis', 'kelas_id', 'musyrif_id', 'status'])
            ->map(function (Santri $santri) use ($kind, $semester, $filters, $placements, $classes, $musyrifs, $classIds, $records): ?array {
                $placement = $placements->get($santri->id);
                // Historical missing placements never fall back to today's class.
                $fallback = !$placement && $semester->isActive() && $santri->status === 'aktif';
                $classId = $placement ? $placement->kelas_id : ($fallback ? $santri->kelas_id : null);
                $musyrifId = $placement ? $placement->musyrif_id : ($fallback ? $santri->musyrif_id : null);
                if ($classIds !== null && !in_array((int) $classId, $classIds, true)) {
                    return null;
                }
                if (!empty($filters['musyrif_id']) && (int) $musyrifId !== (int) $filters['musyrif_id']) {
                    return null;
                }

                $all = $records->get($santri->id, collect());
                $summary = $kind === 'exams' ? $this->examSummary($all, $filters) : $this->tilawahSummary($all, $filters);
                $class = $classes->get($classId);
                $classLabel = $class ? trim($class->nama_kelas . ' ' . ($class->kelompok ?? '')) : '-';
                $row = array_merge([
                    'id' => (int) $santri->id,
                    'nama' => $santri->nama,
                    'nis' => $santri->nis,
                    'kelas' => $classLabel,
                    'musyrif' => $musyrifs->get($musyrifId, '-'),
                    'placement_source' => $placement ? 'Penempatan semester' : ($fallback ? 'Data aktif saat ini' : 'Penempatan belum tersedia'),
                ], $summary);
                if (!empty($filters['state']) && $row['state'] !== $filters['state']) {
                    return null;
                }
                if (!empty($filters['q']) && !str_contains(
                    mb_strtolower(implode(' ', [$row['nama'], $row['nis'], $row['kelas'], $row['musyrif']])),
                    mb_strtolower(trim($filters['q']))
                )) {
                    return null;
                }
                $row['history'] = $row['history']->map(function ($record) use ($kind, $musyrifs): array {
                    $base = [
                        'id' => (int) $record->id,
                        'tanggal' => $record->tanggal->toDateString(),
                        'pencatat' => $musyrifs->get($record->musyrif_id, '-'),
                    ];
                    if ($kind === 'exams') {
                        return $base + [
                            'jenis' => TahsinExam::examTypeLabels()[$record->exam_type] ?? $record->exam_type,
                            'materi' => TahsinExam::bookLabels()[$record->buku] ?? $record->buku,
                            'percobaan' => (int) $record->attempt_number,
                            'nilai' => TahsinExam::gradeLabels()[$record->grade_label] ?? $record->grade_label,
                            'hasil' => $record->result === 'passed' ? 'Lulus' : 'Mengulang',
                            'catatan' => $record->catatan,
                        ];
                    }
                    $payload = $this->progress->parse($record->catatan);
                    $juz = $this->progress->juzFromPayload($payload);

                    return $base + [
                        'jenis' => $record->reading_purpose === 'review' ? 'Murojaah' : 'Lanjut',
                        'materi' => $juz ? "Juz {$juz}" : 'Bacaan ayat / data lama',
                        'percobaan' => null,
                        'nilai' => '-',
                        'hasil' => $record->status,
                        'catatan' => $payload['note'] ?? ($payload ? null : $record->catatan),
                    ];
                })->values()->all();

                return $row;
            })->filter()->values();
    }

    public function examSummary(Collection $records, array $filters = []): array
    {
        $completed = $records->where('exam_type', 'promotion')->where('result', 'passed')
            ->pluck('buku')->unique()->intersect(TahsinExam::books())->values();
        $visible = empty($filters['exam_type']) ? $records : $records->where('exam_type', $filters['exam_type']);
        $latest = $visible->first();
        $next = collect(TahsinExam::books())->diff($completed)->first();

        return [
            'completed' => $completed->map(fn ($book) => TahsinExam::bookLabels()[$book])->all(),
            'completed_count' => $completed->count(),
            'target' => 6,
            'percentage' => round($completed->count() / 6 * 100, 1),
            'recommendation' => $next ? TahsinExam::bookLabels()[$next] : 'Kurikulum selesai',
            'total' => $visible->count(),
            'first_count' => $visible->where('exam_type', 'promotion')->count(),
            'second_count' => $visible->where('exam_type', 'semester')->count(),
            'state' => $latest?->result ?? 'not_examined',
            'latest' => $latest ? (TahsinExam::bookLabels()[$latest->buku] ?? $latest->buku) . ' · ' . (TahsinExam::gradeLabels()[$latest->grade_label] ?? '-') : null,
            'latest_date' => $latest?->tanggal?->toDateString(),
            'history' => $visible,
        ];
    }

    public function tilawahSummary(Collection $records, array $filters = []): array
    {
        $records = $records->where('entry_type', Tilawah::ENTRY_TYPE_INDIVIDUAL);
        $completed = $records->filter(fn ($record) => $record->contributesToProgress())
            ->map(fn ($record) => $this->progress->juzNumber($record->catatan))
            ->filter(fn ($juz) => $juz !== null)->unique()->sort()->values();
        $visible = empty($filters['purpose']) ? $records : $records->where('reading_purpose', $filters['purpose']);
        $latest = $visible->first();
        $juz = $latest ? $this->progress->juzNumber($latest->catatan) : null;

        return [
            'completed' => $completed->all(),
            'completed_count' => $completed->count(),
            'target' => 30,
            'percentage' => round($completed->count() / 30 * 100, 1),
            'recommendation' => '',
            'total' => $visible->count(),
            'first_count' => $visible->where('reading_purpose', 'continuation')->count(),
            'second_count' => $visible->where('reading_purpose', 'review')->count(),
            'state' => match (true) {
                $visible->isEmpty() => 'no_activity',
                $completed->count() === 30 => 'completed',
                $completed->isNotEmpty() => 'in_progress',
                default => 'not_started',
            },
            'latest' => $latest ? ($juz ? "Juz {$juz}" : 'Bacaan ayat / data lama') . ' · ' . ($latest->reading_purpose === 'review' ? 'Murojaah' : 'Lanjut') : null,
            'latest_date' => $latest?->tanggal?->toDateString(),
            'history' => $visible,
        ];
    }

    private function classIds(int $id, Collection $classes): array
    {
        $ids = [$id];
        do {
            $before = count($ids);
            foreach ($classes as $class) {
                if (in_array((int) $class->parent_id, $ids, true) && !in_array((int) $class->id, $ids, true)) {
                    $ids[] = (int) $class->id;
                }
            }
        } while (count($ids) > $before);

        return $ids;
    }
}
