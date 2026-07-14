<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SantriPlacementBackfillController extends Controller
{
    private const BATCH_LIMIT = 200;

    /**
     * Preview tanpa menulis database.
     */
    public function preview(): JsonResponse
    {
        $this->ensureFeatureEnabled();

        $semester = $this->activeSemester();

        $totalSantri = Santri::query()
            ->where('status', 'aktif')
            ->count();

        $existingPlacements =
            SantriSemesterPlacement::query()
            ->where(
                'semester_id',
                $semester->id
            )
            ->whereHas(
                'santri',
                fn(Builder $query) =>
                $query->where(
                    'status',
                    'aktif'
                )
            )
            ->count();

        $missingPlacements = max(
            0,
            $totalSantri - $existingPlacements
        );

        /*
         * Mendeteksi placement existing yang berbeda dengan
         * current projection santri.
         *
         * Operator <=> adalah null-safe equality milik MySQL.
         */
        $mismatchPlacements = DB::table(
            'santri_semester_placements as ssp'
        )
            ->join(
                'santris as s',
                's.id',
                '=',
                'ssp.santri_id'
            )
            ->where(
                'ssp.semester_id',
                $semester->id
            )
            ->where(
                's.status',
                'aktif'
            )
            ->whereRaw(
                'NOT (
                    ssp.kelas_id <=> s.kelas_id
                    AND
                    ssp.musyrif_id <=> s.musyrif_id
                )'
            )
            ->count();

        return response()->json([
            'ok' => true,

            'semester' => [
                'id' => $semester->id,
                'nama' => $semester->nama,
                'tahun_ajaran' =>
                $semester->tahunAjaran?->nama,
            ],

            'summary' => [
                'total_santri' =>
                $totalSantri,

                'existing' =>
                $existingPlacements,

                'missing' =>
                $missingPlacements,

                'mismatch' =>
                $mismatchPlacements,
            ],
        ]);
    }

    /**
     * Memproses placement bertahap.
     *
     * Endpoint dipanggil berulang dari JavaScript sampai done=true.
     */
    public function process(
        Request $request
    ): JsonResponse {
        $this->ensureFeatureEnabled();

        $data = $request->validate([
            'last_id' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $lastId = (int) (
            $data['last_id'] ?? 0
        );

        $semester = $this->activeSemester();

        /*
         * Hanya mengambil santri aktif yang belum mempunyai
         * placement pada semester aktif.
         */
        $santris = Santri::query()
            ->where(
                'santris.status',
                'aktif'
            )
            ->where(
                'santris.id',
                '>',
                $lastId
            )
            ->whereNotExists(
                function ($query) use (
                    $semester
                ): void {
                    $query
                        ->selectRaw('1')
                        ->from(
                            'santri_semester_placements as existing_ssp'
                        )
                        ->whereColumn(
                            'existing_ssp.santri_id',
                            'santris.id'
                        )
                        ->where(
                            'existing_ssp.semester_id',
                            $semester->id
                        );
                }
            )
            ->orderBy('santris.id')
            ->limit(self::BATCH_LIMIT)
            ->get();

        if ($santris->isEmpty()) {
            return response()->json([
                'ok' => true,
                'done' => true,
                'processed' => 0,
                'created' => 0,
                'skipped' => 0,
                'next_last_id' => $lastId,
            ]);
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(
            function () use (
                $santris,
                $semester,
                &$created,
                &$skipped
            ): void {
                foreach ($santris as $santri) {
                    /*
                     * firstOrCreate membuat proses aman ketika request
                     * terkirim dua kali atau browser mencoba ulang.
                     */
                    $placement =
                        SantriSemesterPlacement::query()
                        ->firstOrCreate(
                            [
                                'santri_id' =>
                                $santri->id,

                                'semester_id' =>
                                $semester->id,
                            ],
                            [
                                'kelas_id' =>
                                $santri->kelas_id,

                                'musyrif_id' =>
                                $santri->musyrif_id,

                                'status' =>
                                SantriSemesterPlacement::STATUS_AKTIF,

                                'placement_type' =>
                                SantriSemesterPlacement::TYPE_BACKFILL,

                                'started_at' =>
                                $semester->tanggal_mulai
                                    ? $semester
                                    ->tanggal_mulai
                                    ->copy()
                                    ->startOfDay()
                                    : now(),

                                'ended_at' => null,

                                'note' =>
                                'Backfill placement melalui halaman Admin.',

                                'metadata' => [
                                    'source' =>
                                    'admin_frontend_backfill',

                                    'backfilled_at' =>
                                    now()
                                        ->toIso8601String(),

                                    'backfilled_by' =>
                                    auth()->id(),
                                ],

                                'created_by' =>
                                auth()->id(),

                                'updated_by' =>
                                auth()->id(),
                            ]
                        );

                    if (
                        $placement->wasRecentlyCreated
                    ) {
                        $created++;
                    } else {
                        $skipped++;
                    }
                }
            }
        );

        $nextLastId = (int) (
            $santris->last()?->id
            ?? $lastId
        );

        $hasMore = Santri::query()
            ->where(
                'santris.status',
                'aktif'
            )
            ->where(
                'santris.id',
                '>',
                $nextLastId
            )
            ->whereNotExists(
                function ($query) use (
                    $semester
                ): void {
                    $query
                        ->selectRaw('1')
                        ->from(
                            'santri_semester_placements as existing_ssp'
                        )
                        ->whereColumn(
                            'existing_ssp.santri_id',
                            'santris.id'
                        )
                        ->where(
                            'existing_ssp.semester_id',
                            $semester->id
                        );
                }
            )
            ->exists();

        return response()->json([
            'ok' => true,
            'done' => !$hasMore,
            'processed' => $santris->count(),
            'created' => $created,
            'skipped' => $skipped,
            'next_last_id' => $nextLastId,
        ]);
    }

    private function activeSemester(): Semester
    {
        $semester = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->active()
            ->first();

        if (!$semester) {
            throw ValidationException::withMessages([
                'semester' => [
                    'Semester aktif tidak ditemukan.',
                ],
            ]);
        }

        return $semester;
    }

    private function ensureFeatureEnabled(): void
    {
        abort_unless(
            config(
                'app.allow_placement_backfill_ui',
                false
            ),
            404
        );
    }
}
