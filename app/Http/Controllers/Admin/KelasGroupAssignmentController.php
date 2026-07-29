<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KelasGroupAssignmentBatch;
use App\Models\Semester;
use App\Services\Academic\KelasGroupAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Throwable;

class KelasGroupAssignmentController extends Controller
{
    public function index(
        KelasGroupAssignmentService $service
    ): View {
        $state = $service->dashboardState();

        $semesters = Semester::query()
            ->with('tahunAjaran:id,nama')
            ->orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get([
                'id',
                'tahun_ajaran_id',
                'nama',
                'is_active',
                'status',
                'tanggal_mulai',
                'tanggal_selesai',
            ]);

        $batches = KelasGroupAssignmentBatch::query()
            ->with([
                'semester.tahunAjaran:id,nama',
                'createdBy:id,name',
                'executedBy:id,name',
                'rolledBackBy:id,name',
            ])
            ->withCount('items')
            ->latestFirst()
            ->limit(15)
            ->get();

        return view(
            'admin.kelas-group-assignment.index',
            compact(
                'state',
                'semesters',
                'batches'
            )
        );
    }

    public function preview(
        Request $request,
        KelasGroupAssignmentService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'semester_id' => [
                'nullable',
                'integer',
                'exists:semesters,id',
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        try {
            $batch = $service->createPreview(
                isset($validated['semester_id'])
                    ? (int) $validated['semester_id']
                    : null,
                auth()->id(),
                $validated['note'] ?? null
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors(
                    $exception->errors()
                )
                ->withInput();
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Preview gagal: '
                    . $exception->getMessage()
                );
        }

        $blockerCount = (int) data_get(
            $batch->summary,
            'blocker_count',
            0
        );

        if ($batch->status === KelasGroupAssignmentBatch::STATUS_BLOCKED) {
            return redirect()
                ->route(
                    'admin.kelas-group-assignment.index'
                )
                ->with(
                    'warning',
                    "Preview {$batch->code} tersimpan, tetapi memiliki {$blockerCount} blocker. Eksekusi belum diizinkan."
                );
        }

        return redirect()
            ->route(
                'admin.kelas-group-assignment.index'
            )
            ->with(
                'success',
                "Preview {$batch->code} berhasil dibuat dan siap dieksekusi."
            );
    }

    public function execute(
        KelasGroupAssignmentBatch $batch,
        KelasGroupAssignmentService $service
    ): RedirectResponse {
        try {
            $executedBatch = $service->execute(
                $batch,
                auth()->id()
            );
        } catch (ValidationException $exception) {
            return back()->withErrors(
                $exception->errors()
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Eksekusi gagal: '
                . $exception->getMessage()
            );
        }

        return redirect()
            ->route(
                'admin.kelas-group-assignment.index'
            )
            ->with(
                'success',
                "Batch {$executedBatch->code} berhasil dieksekusi."
            );
    }

    public function rollback(
        KelasGroupAssignmentBatch $batch,
        KelasGroupAssignmentService $service
    ): RedirectResponse {
        try {
            $rolledBackBatch = $service->rollback(
                $batch,
                auth()->id()
            );
        } catch (ValidationException $exception) {
            return back()->withErrors(
                $exception->errors()
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Rollback gagal: '
                . $exception->getMessage()
            );
        }

        return redirect()
            ->route(
                'admin.kelas-group-assignment.index'
            )
            ->with(
                'success',
                "Batch {$rolledBackBatch->code} berhasil di-rollback."
            );
    }
}
