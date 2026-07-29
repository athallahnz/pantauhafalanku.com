<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Hafalan\HafalanScorePolicy;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AuditHafalanScoreIntegrity extends Command
{
    protected $signature = 'hafalan:audit-score-integrity
        {--fix-non-lulus : Kosongkan nilai_label dan nilai pada seluruh status non-lulus}
        {--fail-on-warning : Kembalikan exit code gagal untuk histori lulus + mardud}';

    protected $description = 'Audit konsistensi status dan nilai pada tabel hafalans';

    public function handle(): int
    {
        if (!Schema::hasTable('hafalans')) {
            $this->error('Tabel hafalans tidak ditemukan. Jalankan migration terlebih dahulu.');

            return self::FAILURE;
        }

        $hasLegacyNumericScore = Schema::hasColumn('hafalans', 'nilai');

        if ($this->option('fix-non-lulus')) {
            $this->fixNonLulusScores($hasLegacyNumericScore);
        }

        $summary = $this->summary($hasLegacyNumericScore);

        $this->table(
            ['Pemeriksaan', 'Jumlah', 'Level'],
            [
                [
                    'Non-lulus masih memiliki nilai',
                    $summary['non_lulus_dengan_nilai'],
                    $summary['non_lulus_dengan_nilai'] > 0 ? 'ERROR' : 'OK',
                ],
                [
                    'Lulus tanpa nilai_label',
                    $summary['lulus_tanpa_nilai'],
                    $summary['lulus_tanpa_nilai'] > 0 ? 'ERROR' : 'OK',
                ],
                [
                    'Histori lulus + mardud',
                    $summary['lulus_mardud'],
                    $summary['lulus_mardud'] > 0 ? 'WARNING' : 'OK',
                ],
                [
                    'Total lulus',
                    $summary['total_lulus'],
                    'INFO',
                ],
                [
                    'Total ulang',
                    $summary['total_ulang'],
                    'INFO',
                ],
            ]
        );

        $hasCriticalIssue = $summary['non_lulus_dengan_nilai'] > 0
            || $summary['lulus_tanpa_nilai'] > 0;

        $warningShouldFail = (bool) $this->option('fail-on-warning')
            && $summary['lulus_mardud'] > 0;

        if ($hasCriticalIssue || $warningShouldFail) {
            $this->error('Audit integritas nilai hafalan gagal.');

            return self::FAILURE;
        }

        if ($summary['lulus_mardud'] > 0) {
            $this->warn(
                'Ditemukan histori lulus + mardud. Data tidak diubah otomatis; '
                . 'review keputusan akademiknya secara manual.'
            );
        }

        $this->info('Audit integritas nilai hafalan selesai.');

        return self::SUCCESS;
    }

    private function fixNonLulusScores(bool $hasLegacyNumericScore): void
    {
        $affected = DB::transaction(function () use ($hasLegacyNumericScore): int {
            $updates = [
                'nilai_label' => null,
                'updated_at' => now(),
            ];

            if ($hasLegacyNumericScore) {
                $updates['nilai'] = null;
            }

            return $this
                ->nonLulusWithScoreQuery($hasLegacyNumericScore)
                ->update($updates);
        });

        $this->info("Normalisasi status non-lulus: {$affected} baris diperbarui.");
    }

    /** @return array<string, int> */
    private function summary(bool $hasLegacyNumericScore): array
    {
        return [
            'non_lulus_dengan_nilai' => $this
                ->nonLulusWithScoreQuery($hasLegacyNumericScore)
                ->count(),
            'lulus_tanpa_nilai' => DB::table('hafalans')
                ->where('status', HafalanScorePolicy::SCORED_STATUS)
                ->whereNull('nilai_label')
                ->count(),
            'lulus_mardud' => DB::table('hafalans')
                ->where('status', HafalanScorePolicy::SCORED_STATUS)
                ->where('nilai_label', 'mardud')
                ->count(),
            'total_lulus' => DB::table('hafalans')
                ->where('status', HafalanScorePolicy::SCORED_STATUS)
                ->count(),
            'total_ulang' => DB::table('hafalans')
                ->where('status', 'ulang')
                ->count(),
        ];
    }

    private function nonLulusWithScoreQuery(
        bool $hasLegacyNumericScore
    ): Builder {
        return DB::table('hafalans')
            ->where('status', '<>', HafalanScorePolicy::SCORED_STATUS)
            ->where(function (Builder $query) use ($hasLegacyNumericScore): void {
                $query->whereNotNull('nilai_label');

                if ($hasLegacyNumericScore) {
                    $query->orWhereNotNull('nilai');
                }
            });
    }
}
