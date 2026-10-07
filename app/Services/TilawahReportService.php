<?php

namespace App\Services;

use App\Models\Tilawah;

/** One calculation policy for monitoring, personal reports and new raport snapshots. */
final class TilawahReportService
{
    public function __construct(private TilawahProgressService $progress)
    {
    }

    public function forSantri(int $santriId, ?int $semesterId = null, ?string $cutoffDate = null): array
    {
        $records = Tilawah::query()->where('santri_id', $santriId)
            ->when($semesterId !== null, fn ($query) => $query->where('semester_id', $semesterId))
            ->when($cutoffDate !== null, fn ($query) => $query->whereDate('tanggal', '<=', $cutoffDate))
            ->orderByDesc('tanggal')->orderByDesc('id')->get();

        return $this->summarize($records);
    }

    /** Unknown/legacy notes remain activity; only explicit complete-juz payloads earn juz. */
    public function summarize(iterable $records): array
    {
        $completed = [];
        $counts = ['continuation' => 0, 'review' => 0, 'group' => 0, 'catchup' => 0, 'legacy' => 0];
        $ranges = ['group' => [], 'catchup' => [], 'individual' => []];
        foreach ($records as $record) {
            $type = $record->entry_type ?? null;
            $purpose = $record->reading_purpose ?? null;
            $bucket = match ($type) {
                'individual' => in_array($purpose, ['continuation', 'review'], true) ? $purpose : 'legacy',
                'group' => 'group',
                'catchup' => 'catchup',
                default => 'legacy',
            };
            $counts[$bucket]++;
            if ($record->status !== 'hadir') {
                continue;
            }
            $payload = $this->progress->parse($record->catatan);
            $juz = $this->progress->juzFromPayload($payload);
            if ($type === 'individual' && $purpose === 'continuation' && $juz !== null) {
                $completed[$juz] = $juz;
            }
            // Review does not contribute to first-reading coverage.
            if (!$payload || $purpose === 'review' || !isset($ranges[$type ?? ''])) {
                continue;
            }
            $from = $payload['from']['quran_index'] ?? null;
            $to = $payload['to']['quran_index'] ?? null;
            if (is_numeric($from) && is_numeric($to) && (int) $from >= 1 && (int) $to >= (int) $from && (int) $to <= 6236) {
                $ranges[$type][] = [(int) $from, (int) $to];
            }
        }
        sort($completed, SORT_NUMERIC);

        return [
            'policy' => 'tilawah.report.v1',
            'completed' => array_values($completed),
            'completed_count' => count($completed),
            'target' => 30,
            'percentage' => round(count($completed) / 30 * 100, 1),
            'max_juz' => $completed ? max($completed) : 0,
            'activity_counts' => $counts,
            'unique_ayat' => array_map(fn (array $items): int => $this->uniqueAyat($items), $ranges),
        ];
    }

    private function uniqueAyat(array $ranges): int
    {
        usort($ranges, fn (array $a, array $b): int => $a[0] <=> $b[0]);
        $total = 0;
        $end = 0;
        foreach ($ranges as [$from, $to]) {
            $total += max(0, $to - max($from, $end + 1) + 1);
            $end = max($end, $to);
        }

        return $total;
    }
}
