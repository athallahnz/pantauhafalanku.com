<?php

namespace App\Services;

use App\Models\Tilawah;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TilawahEligibilityService
{
    /**
     * Batas mushaf yang dipakai sebagai syarat buku Tahsin.
     *
     * Juz 5  berakhir pada An-Nisa':147.
     * Juz 10 berakhir pada At-Taubah:92.
     * Juz 15 berakhir pada Al-Kahf:74.
     */
    private const TARGET_ENDPOINTS = [
        5 => ['surah_id' => 4, 'ayat' => 147],
        10 => ['surah_id' => 9, 'ayat' => 92],
        15 => ['surah_id' => 18, 'ayat' => 74],
    ];

    public function __construct(
        private readonly TilawahProgressService $progressService
    ) {
    }

    /**
     * @param Collection<int, int>|array<int, int> $santriIds
     * @return Collection<int, array<string, mixed>>
     */
    public function summaries(
        Collection|array $santriIds,
        ?int $targetJuz = null
    ): Collection {
        $ids = collect($santriIds)
            ->map(fn($id): int => (int) $id)
            ->filter(fn(int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $recordsBySantri = Tilawah::query()
            ->with('template.segments')
            ->whereIn('santri_id', $ids)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->groupBy(fn(Tilawah $tilawah): int => (int) $tilawah->santri_id);

        $targetIndex = $targetJuz === null
            ? null
            : $this->targetIndex($targetJuz);

        return $ids->mapWithKeys(function (int $santriId) use (
            $recordsBySantri,
            $targetJuz,
            $targetIndex
        ): array {
            $records = $recordsBySantri->get($santriId, collect())->values();

            return [
                $santriId => $this->summarizeRecords(
                    $records,
                    $targetJuz,
                    $targetIndex
                ),
            ];
        });
    }

    /**
     * @param Collection<int, Tilawah> $records
     * @return array<string, mixed>
     */
    private function summarizeRecords(
        Collection $records,
        ?int $targetJuz,
        ?int $targetIndex
    ): array {
        $parsedRecords = $records
            ->map(fn(Tilawah $record): array => [
                'record' => $record,
                'payload' => $this->progressService->parse($record->catatan),
            ])
            ->values();

        $firstStructuredOffset = $parsedRecords->search(
            fn(array $item): bool => is_array($item['payload'])
                && $item['record']->entry_type === Tilawah::ENTRY_TYPE_GROUP
        );
        $hasLegacyBeforeFirstStructured = false;

        if ($firstStructuredOffset !== false) {
            $hasLegacyBeforeFirstStructured = $parsedRecords
                ->slice(0, (int) $firstStructuredOffset)
                ->contains(fn(array $item): bool =>
                    $item['payload'] === null
                    && $item['record']->status === 'hadir'
                );
        }

        $intervals = [];
        $baselineThroughIndex = 0;
        $legacyGroupThroughIndex = 0;
        $structuredGroupThroughIndex = 0;

        foreach ($parsedRecords as $offset => $item) {
            /** @var Tilawah $record */
            $record = $item['record'];
            $payload = $item['payload'];

            if (!is_array($payload)) {
                if ($record->entry_type === Tilawah::ENTRY_TYPE_INDIVIDUAL) {
                    continue;
                }

                $legacyIntervals = $this->legacyIntervals($record);

                foreach ($legacyIntervals as [$start, $end]) {
                    // Target kelompok tetap maju meskipun santri tidak hadir.
                    $legacyGroupThroughIndex = max(
                        $legacyGroupThroughIndex,
                        $end
                    );

                    if ($record->status === 'hadir') {
                        $intervals[] = [$start, $end];
                    }
                }

                continue;
            }

            $fromIndex = (int) ($payload['from']['quran_index'] ?? 0);
            $toIndex = (int) ($payload['to']['quran_index'] ?? 0);

            if ($fromIndex < 1 || $toIndex < $fromIndex) {
                continue;
            }

            $baselineIndex = (int) (
                $payload['baseline']['through']['quran_index'] ?? 0
            );

            /*
             * Kompatibilitas record v1 yang dibuat melalui form transisi lama:
             * jika ada history hadir sebelum record terstruktur pertama, titik
             * sebelum "Dari" dianggap baseline yang telah dikonfirmasi Musyrif.
             */
            if (
                $baselineIndex < 1
                && ($payload['schema'] ?? null) === 'tilawah.v1'
                && $offset === $firstStructuredOffset
                && $hasLegacyBeforeFirstStructured
                && $fromIndex > 1
            ) {
                $baselineIndex = $fromIndex - 1;
            }

            if ($baselineIndex > 0) {
                $baselineThroughIndex = max(
                    $baselineThroughIndex,
                    $baselineIndex
                );
                $intervals[] = [1, $baselineIndex];
            }

            if (
                $record->entry_type === Tilawah::ENTRY_TYPE_GROUP
                && $this->progressService->isGroupPayload($payload)
            ) {
                $structuredGroupThroughIndex = max(
                    $structuredGroupThroughIndex,
                    $toIndex
                );
            }

            if (
                $record->contributesToProgress()
                && $this->progressService->contributesToProgress($payload)
            ) {
                $intervals[] = [$fromIndex, $toIndex];
            }
        }

        /*
         * Begitu pencatatan terstruktur dimulai, rentang itulah posisi aktif
         * kelompok. Cakupan legacy yang lebih jauh tetap dihitung, tetapi
         * tidak membuat daftar Susulan melompat ke posisi kelompok lama.
         */
        $groupThroughIndex = $structuredGroupThroughIndex > 0
            ? $structuredGroupThroughIndex
            : $legacyGroupThroughIndex;
        $groupThroughIndex = max($groupThroughIndex, $baselineThroughIndex);
        $mergedIntervals = $this->mergeIntervals($intervals);
        $coveredThroughIndex = $this->continuousCoverage($mergedIntervals);
        $firstGap = $this->firstGap(
            $mergedIntervals,
            $coveredThroughIndex,
            $groupThroughIndex
        );

        return [
            'covered_through_index' => $coveredThroughIndex,
            'covered_through' => $coveredThroughIndex > 0
                ? $this->progressService->pointFromIndex($coveredThroughIndex)
                : null,
            'group_through_index' => $groupThroughIndex,
            'group_through' => $groupThroughIndex > 0
                ? $this->progressService->pointFromIndex($groupThroughIndex)
                : null,
            'baseline_through_index' => $baselineThroughIndex,
            'first_gap' => $firstGap,
            'target_juz' => $targetJuz,
            'target_index' => $targetIndex,
            'eligible' => $targetIndex !== null
                && $coveredThroughIndex >= $targetIndex,
        ];
    }

    /**
     * Record lama menyimpan target bacaan pada hafalan_template_id. Setelah
     * surah_segments dilengkapi oleh seeder, target itu dapat dikembalikan
     * menjadi rentang ayat tanpa mengubah satu pun record historis.
     *
     * @return array<int, array{0:int,1:int}>
     */
    private function legacyIntervals(Tilawah $record): array
    {
        $segments = $record->template?->segments;

        if (!$segments || $segments->isEmpty()) {
            return [];
        }

        $intervals = [];

        foreach ($segments as $segment) {
            $surah = $this->progressService->surahs()->firstWhere(
                'id',
                (int) $segment->surah_id
            );

            if (!$surah) {
                continue;
            }

            $ayatAwal = max(1, (int) $segment->ayat_awal);
            $ayatAkhir = (int) $segment->ayat_akhir;

            if ($ayatAkhir === 0) {
                $ayatAkhir = (int) $surah->jumlah_ayat;
            }

            if ($ayatAkhir < $ayatAwal) {
                continue;
            }

            $from = $this->progressService->makePoint(
                (int) $segment->surah_id,
                $ayatAwal,
                'tilawah_lama'
            );
            $to = $this->progressService->makePoint(
                (int) $segment->surah_id,
                $ayatAkhir,
                'tilawah_lama'
            );

            $intervals[] = [
                (int) $from['quran_index'],
                (int) $to['quran_index'],
            ];
        }

        return $intervals;
    }

    /**
     * @param array<int, array{0:int,1:int}> $intervals
     * @return array<int, array{0:int,1:int}>
     */
    private function mergeIntervals(array $intervals): array
    {
        usort($intervals, function (array $left, array $right): int {
            return $left[0] <=> $right[0] ?: $left[1] <=> $right[1];
        });

        $merged = [];

        foreach ($intervals as [$start, $end]) {
            if ($start < 1 || $end < $start) {
                continue;
            }

            $lastIndex = array_key_last($merged);

            if (
                $lastIndex === null
                || $start > $merged[$lastIndex][1] + 1
            ) {
                $merged[] = [$start, $end];
                continue;
            }

            $merged[$lastIndex][1] = max($merged[$lastIndex][1], $end);
        }

        return $merged;
    }

    /**
     * @param array<int, array{0:int,1:int}> $mergedIntervals
     */
    private function continuousCoverage(array $mergedIntervals): int
    {
        $coveredThrough = 0;

        foreach ($mergedIntervals as [$start, $end]) {
            if ($start > $coveredThrough + 1) {
                break;
            }

            $coveredThrough = max($coveredThrough, $end);
        }

        return $coveredThrough;
    }

    /**
     * @param array<int, array{0:int,1:int}> $mergedIntervals
     * @return array<string, mixed>|null
     */
    private function firstGap(
        array $mergedIntervals,
        int $coveredThrough,
        int $groupThrough
    ): ?array {
        if ($coveredThrough >= $groupThrough) {
            return null;
        }

        $gapStart = $coveredThrough + 1;
        $gapEnd = $groupThrough;

        foreach ($mergedIntervals as [$start, $end]) {
            if ($end < $gapStart) {
                continue;
            }

            if ($start > $gapStart) {
                $gapEnd = min($gapEnd, $start - 1);
                break;
            }
        }

        return [
            'from_index' => $gapStart,
            'to_index' => $gapEnd,
            'from' => $this->progressService->pointFromIndex($gapStart),
            'to' => $this->progressService->pointFromIndex($gapEnd),
            'total_ayat' => $gapEnd - $gapStart + 1,
        ];
    }

    private function targetIndex(int $targetJuz): int
    {
        $endpoint = self::TARGET_ENDPOINTS[$targetJuz] ?? null;

        if (!$endpoint) {
            throw ValidationException::withMessages([
                'buku' => "Batas akhir Juz {$targetJuz} belum dikonfigurasi.",
            ]);
        }

        return (int) $this->progressService->makePoint(
            $endpoint['surah_id'],
            $endpoint['ayat'],
            'buku'
        )['quran_index'];
    }
}
