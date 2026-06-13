<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SantriMigrationBatch;
use App\Models\SantriMigrationBatchItem;
use App\Models\Semester;
use App\Support\Academic\SantriMigrationRollbackService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class SantriMigrationBatchAuditController extends Controller
{
    public function index()
    {
        $this->expireStaleBatches();

        $semesterList = Semester::query()
            ->with('tahunAjaran')
            ->orderByDesc('id')
            ->get();

        return view(
            'admin.santri.migration-batches.index',
            compact('semesterList')
        );
    }

    public function data(Request $request)
    {
        $this->validateFilters($request);
        $this->expireStaleBatches();

        $query = $this->filteredQuery($request)
            ->with([
                'fromSemester.tahunAjaran',
                'toSemester.tahunAjaran',
                'fromKelas:id,nama_kelas',
                'toKelas:id,nama_kelas',
                'creator:id,name',
                'executor:id,name',
                'rollbackActor:id,name',
            ]);

        return DataTables::eloquent($query)
            ->addColumn('waktu', function (SantriMigrationBatch $batch): string {
                return $this->formatDateCell(
                    $batch->created_at,
                    $batch->previewed_at ? 'Preview' : 'Dibuat'
                );
            })
            ->addColumn('batch_info', function (SantriMigrationBatch $batch): string {
                $mode = $this->modeMeta($batch->mode);
                $code = e($batch->code);
                $modeLabel = e($mode['label']);
                $modeClass = e($mode['class']);
                $hash = $batch->snapshot_hash
                    ? e(substr($batch->snapshot_hash, 0, 10)) . '…'
                    : '-';

                return <<<HTML
                    <div class="fw-bold text-adaptive-purple font-monospace">{$code}</div>
                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                        <span class="badge {$modeClass} rounded-pill">{$modeLabel}</span>
                        <span class="small text-body-secondary font-monospace">Hash {$hash}</span>
                    </div>
                HTML;
            })
            ->addColumn('semester_flow', function (SantriMigrationBatch $batch): string {
                $from = e($this->semesterLabel($batch->fromSemester));
                $to = e($this->semesterLabel($batch->toSemester));

                return <<<HTML
                    <div class="fw-semibold">{$from}</div>
                    <div class="small text-body-secondary my-1">
                        <i class="bi bi-arrow-down"></i>
                    </div>
                    <div class="fw-semibold text-success">{$to}</div>
                HTML;
            })
            ->addColumn('cakupan', function (SantriMigrationBatch $batch): string {
                if ($batch->mode === SantriMigrationBatch::MODE_AUTO) {
                    $graduation = $batch->include_graduation
                        ? '<span class="badge text-bg-dark rounded-pill ms-1">Termasuk Lulus</span>'
                        : '';

                    return <<<HTML
                        <div class="fw-semibold">
                            <i class="bi bi-diagram-3 me-1"></i> Semua Mapping Kelas
                        </div>
                        <div class="small text-body-secondary mt-1">
                            Snapshot lintas kelas {$graduation}
                        </div>
                    HTML;
                }

                $from = e($batch->fromKelas?->nama_kelas ?? '-');
                $to = $batch->transition_type === 'lulus'
                    ? 'LULUS'
                    : e($batch->toKelas?->nama_kelas ?? '-');
                $transition = e($this->transitionLabel($batch->transition_type));

                return <<<HTML
                    <div class="fw-semibold">{$from} <i class="bi bi-arrow-right mx-1"></i> {$to}</div>
                    <div class="small text-body-secondary mt-1">{$transition}</div>
                HTML;
            })
            ->addColumn('progress', function (SantriMigrationBatch $batch): string {
                $total = max(0, (int) $batch->items_count);
                $completed = max(0, (int) $batch->completed_count);
                $percentage = $total > 0
                    ? min(100, (int) round(($completed / $total) * 100))
                    : 0;
                $graduates = (int) $batch->graduated_count;

                return <<<HTML
                    <div class="d-flex justify-content-between gap-2 small mb-1">
                        <span><strong>{$completed}</strong> / {$total} selesai</span>
                        <span>{$percentage}%</span>
                    </div>
                    <div class="progress audit-progress" role="progressbar" aria-valuenow="{$percentage}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar" style="width: {$percentage}%"></div>
                    </div>
                    <div class="small text-body-secondary mt-1">{$graduates} santri lulus</div>
                HTML;
            })
            ->addColumn('status_badge', function (SantriMigrationBatch $batch): string {
                $status = $this->statusMeta($batch->status);
                $label = e($status['label']);
                $class = e($status['class']);
                $icon = e($status['icon']);
                $error = $batch->last_error
                    ? '<div class="small text-danger mt-1 text-truncate audit-error" title="' . e($batch->last_error) . '">' . e($batch->last_error) . '</div>'
                    : '';

                return <<<HTML
                    <span class="badge {$class} rounded-pill px-3 py-2">
                        <i class="bi {$icon} me-1"></i>{$label}
                    </span>
                    {$error}
                HTML;
            })
            ->addColumn('aktor', function (SantriMigrationBatch $batch): string {
                $creator = e($batch->creator?->name ?? 'Sistem / User Terhapus');
                $executor = e($batch->executor?->name ?? '-');

                return <<<HTML
                    <div class="small text-body-secondary">Dibuat oleh</div>
                    <div class="fw-semibold">{$creator}</div>
                    <div class="small text-body-secondary mt-1">Dieksekusi: {$executor}</div>
                HTML;
            })
            ->addColumn('aksi', function (SantriMigrationBatch $batch): string {
                $id = e($batch->id);
                $code = e($batch->code);
                $cancelButton = '';
                $rollbackButton = '';

                if ($this->canCancel($batch)) {
                    $cancelButton = <<<HTML
                        <button type="button"
                            class="btn btn-sm btn-outline-danger rounded-pill btn-cancel-batch"
                            data-batch-id="{$id}"
                            data-batch-code="{$code}"
                            title="Batalkan batch Preview">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    HTML;
                }

                if ($this->canRequestRollback($batch)) {
                    $rollbackButton = <<<HTML
                        <button type="button"
                            class="btn btn-sm btn-outline-warning rounded-pill btn-rollback-batch"
                            data-batch-id="{$id}"
                            data-batch-code="{$code}"
                            title="Periksa dan rollback batch">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    HTML;
                }

                return <<<HTML
                    <div class="d-inline-flex gap-1">
                        <button type="button"
                            class="btn btn-sm btn-outline-primary rounded-pill btn-detail-batch"
                            data-batch-id="{$id}"
                            title="Buka detail audit">
                            <i class="bi bi-eye"></i> Detail
                        </button>
                        {$rollbackButton}
                        {$cancelButton}
                    </div>
                HTML;
            })
            ->filterColumn('batch_info', function (Builder $query, string $keyword): void {
                $query->where('code', 'like', "%{$keyword}%");
            })
            ->rawColumns([
                'waktu',
                'batch_info',
                'semester_flow',
                'cakupan',
                'progress',
                'status_badge',
                'aktor',
                'aksi',
            ])
            ->toJson();
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->validateFilters($request);
        $this->expireStaleBatches();

        $query = $this->filteredQuery($request);

        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'ok' => true,
            'data' => [
                'total' => (clone $query)->count(),
                'previewed' => (int) ($statusCounts[SantriMigrationBatch::STATUS_PREVIEWED] ?? 0),
                'completed' => (int) ($statusCounts[SantriMigrationBatch::STATUS_COMPLETED] ?? 0),
                'failed' => (int) ($statusCounts[SantriMigrationBatch::STATUS_FAILED] ?? 0),
                'cancelled' => (int) ($statusCounts[SantriMigrationBatch::STATUS_CANCELLED] ?? 0),
                'expired' => (int) ($statusCounts[SantriMigrationBatch::STATUS_EXPIRED] ?? 0),
                'rolled_back' => (int) ($statusCounts[SantriMigrationBatch::STATUS_ROLLED_BACK] ?? 0),
                'items' => (int) (clone $query)->sum('items_count'),
                'graduated' => (int) (clone $query)->sum('graduated_count'),
            ],
        ]);
    }

    public function show(SantriMigrationBatch $batch): JsonResponse
    {
        $this->expireStaleBatches();

        $batch->refresh()->load([
            'fromSemester.tahunAjaran',
            'toSemester.tahunAjaran',
            'fromKelas:id,nama_kelas',
            'toKelas:id,nama_kelas',
            'creator:id,name',
            'executor:id,name',
            'rollbackActor:id,name',
        ]);

        $status = $this->statusMeta($batch->status);
        $mode = $this->modeMeta($batch->mode);
        $percentage = $batch->items_count > 0
            ? min(100, (int) round(($batch->completed_count / $batch->items_count) * 100))
            : 0;

        return response()->json([
            'ok' => true,
            'batch' => [
                'id' => $batch->id,
                'code' => $batch->code,
                'mode' => $batch->mode,
                'mode_label' => $mode['label'],
                'status' => $batch->status,
                'status_label' => $status['label'],
                'status_class' => $status['class'],
                'can_cancel' => $this->canCancel($batch),
                'can_request_rollback' => $this->canRequestRollback($batch),
                'from_semester' => $this->semesterLabel($batch->fromSemester),
                'to_semester' => $this->semesterLabel($batch->toSemester),
                'from_kelas' => $batch->fromKelas?->nama_kelas,
                'to_kelas' => $batch->transition_type === 'lulus'
                    ? 'LULUS'
                    : $batch->toKelas?->nama_kelas,
                'transition_type' => $batch->transition_type,
                'transition_label' => $this->transitionLabel($batch->transition_type),
                'include_graduation' => (bool) $batch->include_graduation,
                'items_count' => (int) $batch->items_count,
                'completed_count' => (int) $batch->completed_count,
                'graduated_count' => (int) $batch->graduated_count,
                'progress_percentage' => $percentage,
                'creator' => $batch->creator?->name ?? 'Sistem / User Terhapus',
                'executor' => $batch->executor?->name,
                'note' => $batch->note,
                'last_error' => $batch->last_error,
                'snapshot_hash' => $batch->snapshot_hash,
                'metadata' => $batch->metadata,
                'created_at' => $this->formatDateTime($batch->created_at),
                'previewed_at' => $this->formatDateTime($batch->previewed_at),
                'executing_at' => $this->formatDateTime($batch->executing_at),
                'executed_at' => $this->formatDateTime($batch->executed_at),
                'failed_at' => $this->formatDateTime($batch->failed_at),
                'cancelled_at' => $this->formatDateTime($batch->cancelled_at),
                'rolled_back_at' => $this->formatDateTime($batch->rolled_back_at),
                'rolled_back_by' => $batch->rollbackActor?->name,
                'rollback_reason' => $batch->rollback_reason,
                'rollback_metadata' => $batch->rollback_metadata,
                'rollback_error' => $batch->rollback_error,
                'expires_at' => $this->formatDateTime($batch->expires_at),
            ],
        ]);
    }

    public function itemsData(
        Request $request,
        SantriMigrationBatch $batch
    ) {
        $query = SantriMigrationBatchItem::query()
            ->where('batch_id', $batch->id)
            ->with([
                'santri:id,nama,nis',
                'fromKelas:id,nama_kelas',
                'toKelas:id,nama_kelas',
                'fromMusyrif:id,nama,kode',
                'toMusyrif:id,nama,kode',
            ]);

        return DataTables::eloquent($query)
            ->addColumn('santri_info', function (SantriMigrationBatchItem $item): string {
                $source = $item->source_snapshot ?? [];
                $name = e($source['nama'] ?? $item->santri?->nama ?? 'Santri terhapus');
                $nis = e($source['nis'] ?? $item->santri?->nis ?? '-');

                return <<<HTML
                    <div class="fw-semibold">{$name}</div>
                    <div class="small text-body-secondary">NIS: {$nis}</div>
                HTML;
            })
            ->addColumn('asal', function (SantriMigrationBatchItem $item): string {
                $source = $item->source_snapshot ?? [];
                $kelas = e($source['kelas_nama'] ?? $item->fromKelas?->nama_kelas ?? '-');
                $musyrif = e($source['musyrif_nama'] ?? $item->fromMusyrif?->nama ?? 'Belum ada musyrif');

                return <<<HTML
                    <div class="fw-semibold">{$kelas}</div>
                    <div class="small text-body-secondary">{$musyrif}</div>
                HTML;
            })
            ->addColumn('tujuan', function (SantriMigrationBatchItem $item): string {
                $target = $item->target_snapshot ?? [];
                $kelas = $item->transition_type === 'lulus'
                    ? 'LULUS'
                    : e($target['kelas_nama'] ?? $item->toKelas?->nama_kelas ?? '-');
                $musyrif = $item->transition_type === 'lulus'
                    ? 'Musyrif dikosongkan'
                    : e($item->toMusyrif?->nama ?? 'Belum ditentukan');

                return <<<HTML
                    <div class="fw-semibold text-success">{$kelas}</div>
                    <div class="small text-body-secondary">{$musyrif}</div>
                HTML;
            })
            ->addColumn('transition_badge', function (SantriMigrationBatchItem $item): string {
                $label = e($this->transitionLabel($item->transition_type));
                $class = match ($item->transition_type) {
                    'lulus' => 'text-bg-dark',
                    'tinggal_kelas' => 'text-bg-warning',
                    'mutasi' => 'text-bg-info',
                    'penempatan' => 'text-bg-secondary',
                    default => 'text-bg-primary',
                };

                return "<span class=\"badge {$class} rounded-pill\">{$label}</span>";
            })
            ->addColumn('status_badge', function (SantriMigrationBatchItem $item): string {
                $meta = $this->itemStatusMeta($item->status);
                $label = e($meta['label']);
                $class = e($meta['class']);
                $error = $item->error_message
                    ? '<div class="small text-danger mt-1 text-truncate audit-error" title="' . e($item->error_message) . '">' . e($item->error_message) . '</div>'
                    : '';

                return "<span class=\"badge {$class} rounded-pill\">{$label}</span>{$error}";
            })
            ->addColumn('executed_time', function (SantriMigrationBatchItem $item): string {
                if ($item->rolled_back_at) {
                    return $this->formatDateCell(
                        $item->rolled_back_at,
                        'Rollback'
                    );
                }

                return $this->formatDateCell(
                    $item->executed_at,
                    'Eksekusi'
                );
            })
            ->addColumn('aksi', function (SantriMigrationBatchItem $item): string {
                $source = htmlspecialchars(
                    json_encode($item->source_snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $target = htmlspecialchars(
                    json_encode($item->target_snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $error = htmlspecialchars($item->error_message ?? '', ENT_QUOTES, 'UTF-8');

                return <<<HTML
                    <button type="button"
                        class="btn btn-sm btn-outline-secondary rounded-pill btn-detail-item"
                        data-source="{$source}"
                        data-target="{$target}"
                        data-error="{$error}">
                        <i class="bi bi-braces"></i>
                    </button>
                HTML;
            })
            ->rawColumns([
                'santri_info',
                'asal',
                'tujuan',
                'transition_badge',
                'status_badge',
                'executed_time',
                'aksi',
            ])
            ->toJson();
    }

    public function cancel(
        Request $request,
        SantriMigrationBatch $batch
    ): JsonResponse {
        $data = $request->validate([
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $result = DB::transaction(function () use (
            $batch,
            $data
        ): array {
            $lockedBatch = SantriMigrationBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $user = auth()->user();
            $isAdmin = $user?->role === 'admin';
            $isCreator = (int) $lockedBatch->created_by === (int) auth()->id();

            abort_unless(
                $isAdmin || $isCreator,
                403,
                'Anda tidak berhak membatalkan batch ini.'
            );

            if ($lockedBatch->isExpired()) {
                $lockedBatch->forceFill([
                    'status' => SantriMigrationBatch::STATUS_EXPIRED,
                    'last_error' => 'Batch kedaluwarsa sebelum dibatalkan.',
                ])->save();

                return [
                    'ok' => false,
                    'message' => 'Batch sudah kedaluwarsa dan tidak dapat dibatalkan.',
                ];
            }

            if ($lockedBatch->status !== SantriMigrationBatch::STATUS_PREVIEWED) {
                return [
                    'ok' => false,
                    'message' => "Batch berstatus {$lockedBatch->status} dan tidak dapat dibatalkan.",
                ];
            }

            $reason = trim((string) ($data['reason'] ?? ''));
            $message = $reason !== ''
                ? "Dibatalkan manual: {$reason}"
                : 'Dibatalkan manual dari halaman audit migrasi.';

            $lockedBatch->forceFill([
                'status' => SantriMigrationBatch::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'last_error' => $message,
            ])->save();

            return [
                'ok' => true,
                'message' => "Batch {$lockedBatch->code} berhasil dibatalkan.",
            ];
        });

        return response()->json(
            $result,
            $result['ok'] ? 200 : 422
        );
    }

    public function rollbackCheck(
        SantriMigrationBatch $batch,
        SantriMigrationRollbackService $service
    ): JsonResponse {
        $this->authorizeRollback();

        return response()->json([
            'ok' => true,
            'inspection' => $service->inspect(
                $batch
            ),
        ]);
    }

    public function rollback(
        Request $request,
        SantriMigrationBatch $batch,
        SantriMigrationRollbackService $service
    ): JsonResponse {
        $this->authorizeRollback();

        $data = $request->validate([
            'confirmation_code' => [
                'required',
                'string',
                'max:100',
            ],
            'reason' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
        ]);

        if (
            !hash_equals(
                $batch->code,
                trim(
                    (string) $data['confirmation_code']
                )
            )
        ) {
            throw ValidationException::withMessages([
                'confirmation_code' => [
                    'Kode konfirmasi tidak sama dengan kode batch.',
                ],
            ]);
        }

        $result = $service->rollback(
            $batch,
            (int) auth()->id(),
            trim((string) $data['reason'])
        );

        return response()->json($result);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->validateFilters($request);
        $this->expireStaleBatches();

        $fileName = 'audit_migrasi_santri_' . now()->format('Ymd_His') . '.csv';
        $query = $this->filteredQuery($request)
            ->with([
                'fromSemester.tahunAjaran',
                'toSemester.tahunAjaran',
                'fromKelas:id,nama_kelas',
                'toKelas:id,nama_kelas',
                'creator:id,name',
                'executor:id,name',
                'rollbackActor:id,name',
            ])
            ->orderByDesc('created_at');

        return response()->streamDownload(
            function () use ($query): void {
                $handle = fopen('php://output', 'wb');
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'Kode Batch',
                    'Mode',
                    'Status',
                    'Semester Asal',
                    'Semester Tujuan',
                    'Kelas Asal',
                    'Kelas Tujuan',
                    'Tipe',
                    'Jumlah Item',
                    'Selesai',
                    'Lulus',
                    'Pembuat',
                    'Pelaksana',
                    'Preview',
                    'Eksekusi',
                    'Kedaluwarsa',
                    'Catatan',
                    'Error Terakhir',
                    'Rollback Oleh',
                    'Waktu Rollback',
                    'Alasan Rollback',
                    'Error Rollback',
                ]);

                $query->chunkById(500, function ($batches) use ($handle): void {
                    foreach ($batches as $batch) {
                        fputcsv($handle, [
                            $batch->code,
                            $this->modeMeta($batch->mode)['label'],
                            $this->statusMeta($batch->status)['label'],
                            $this->semesterLabel($batch->fromSemester),
                            $this->semesterLabel($batch->toSemester),
                            $batch->fromKelas?->nama_kelas,
                            $batch->transition_type === 'lulus'
                                ? 'LULUS'
                                : $batch->toKelas?->nama_kelas,
                            $this->transitionLabel($batch->transition_type),
                            $batch->items_count,
                            $batch->completed_count,
                            $batch->graduated_count,
                            $batch->creator?->name,
                            $batch->executor?->name,
                            $this->formatDateTime($batch->previewed_at),
                            $this->formatDateTime($batch->executed_at),
                            $this->formatDateTime($batch->expires_at),
                            $batch->note,
                            $batch->last_error,
                            $batch->rollbackActor?->name,
                            $this->formatDateTime($batch->rolled_back_at),
                            $batch->rollback_reason,
                            $batch->rollback_error,
                        ]);
                    }
                }, 'id');

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = SantriMigrationBatch::query();

        if ($request->filled('mode')) {
            $query->where('mode', $request->string('mode')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('semester_id')) {
            $semesterId = (int) $request->input('semester_id');

            $query->where(function (Builder $subQuery) use ($semesterId): void {
                $subQuery
                    ->where('from_semester_id', $semesterId)
                    ->orWhere('to_semester_id', $semesterId);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . trim((string) $request->input('code')) . '%');
        }

        return $query;
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'mode' => [
                'nullable',
                Rule::in([
                    SantriMigrationBatch::MODE_MANUAL,
                    SantriMigrationBatch::MODE_AUTO,
                ]),
            ],
            'status' => [
                'nullable',
                Rule::in([
                    SantriMigrationBatch::STATUS_PREVIEWED,
                    SantriMigrationBatch::STATUS_EXECUTING,
                    SantriMigrationBatch::STATUS_COMPLETED,
                    SantriMigrationBatch::STATUS_FAILED,
                    SantriMigrationBatch::STATUS_CANCELLED,
                    SantriMigrationBatch::STATUS_EXPIRED,
                    SantriMigrationBatch::STATUS_ROLLED_BACK,
                ]),
            ],
            'semester_id' => [
                'nullable',
                'integer',
                'exists:semesters,id',
            ],
            'date_from' => [
                'nullable',
                'date',
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'code' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);
    }

    private function expireStaleBatches(): void
    {
        SantriMigrationBatch::query()
            ->where('status', SantriMigrationBatch::STATUS_PREVIEWED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update([
                'status' => SantriMigrationBatch::STATUS_EXPIRED,
                'last_error' => DB::raw(
                    "COALESCE(last_error, 'Batch kedaluwarsa sebelum dieksekusi.')"
                ),
                'updated_at' => now(),
            ]);
    }

    private function canCancel(SantriMigrationBatch $batch): bool
    {
        if (
            $batch->status !== SantriMigrationBatch::STATUS_PREVIEWED
            || $batch->isExpired()
        ) {
            return false;
        }

        $user = auth()->user();

        return $user?->role === 'admin'
            || (int) $batch->created_by === (int) auth()->id();
    }

    private function canRequestRollback(
        SantriMigrationBatch $batch
    ): bool {
        return auth()->user()?->role === 'admin'
            && $batch->canRequestRollback();
    }

    private function authorizeRollback(): void
    {
        abort_unless(
            auth()->user()?->role === 'admin',
            403,
            'Rollback batch hanya dapat dilakukan oleh Admin.'
        );
    }

    private function semesterLabel($semester): string
    {
        if (!$semester) {
            return '-';
        }

        $semesterName = str($semester->nama ?? '-')
            ->replace('_', ' ')
            ->title()
            ->toString();

        $yearName = str($semester->tahunAjaran?->nama ?? '-')
            ->replace('_', ' ')
            ->title()
            ->toString();

        return "{$semesterName} — {$yearName}";
    }

    private function formatDateCell($date, string $label): string
    {
        if (!$date) {
            return '<span class="text-body-secondary">-</span>';
        }

        $day = e($date->format('d M Y'));
        $time = e($date->format('H:i:s'));
        $safeLabel = e($label);

        return <<<HTML
            <div class="fw-semibold">{$day}</div>
            <div class="small text-body-secondary">{$safeLabel} • {$time} WIB</div>
        HTML;
    }

    private function formatDateTime($date): ?string
    {
        return $date
            ? $date->format('d M Y H:i:s') . ' WIB'
            : null;
    }

    private function modeMeta(?string $mode): array
    {
        return match ($mode) {
            SantriMigrationBatch::MODE_AUTO => [
                'label' => 'Auto',
                'class' => 'text-bg-info',
            ],
            default => [
                'label' => 'Manual',
                'class' => 'text-bg-primary',
            ],
        };
    }

    private function statusMeta(?string $status): array
    {
        return match ($status) {
            SantriMigrationBatch::STATUS_PREVIEWED => [
                'label' => 'Previewed',
                'class' => 'text-bg-warning',
                'icon' => 'bi-eye',
            ],
            SantriMigrationBatch::STATUS_EXECUTING => [
                'label' => 'Executing',
                'class' => 'text-bg-info',
                'icon' => 'bi-arrow-repeat',
            ],
            SantriMigrationBatch::STATUS_COMPLETED => [
                'label' => 'Completed',
                'class' => 'text-bg-success',
                'icon' => 'bi-check-circle',
            ],
            SantriMigrationBatch::STATUS_FAILED => [
                'label' => 'Failed',
                'class' => 'text-bg-danger',
                'icon' => 'bi-exclamation-octagon',
            ],
            SantriMigrationBatch::STATUS_CANCELLED => [
                'label' => 'Cancelled',
                'class' => 'text-bg-secondary',
                'icon' => 'bi-x-circle',
            ],
            SantriMigrationBatch::STATUS_EXPIRED => [
                'label' => 'Expired',
                'class' => 'text-bg-dark',
                'icon' => 'bi-hourglass-bottom',
            ],
            SantriMigrationBatch::STATUS_ROLLED_BACK => [
                'label' => 'Rolled Back',
                'class' => 'text-bg-warning',
                'icon' => 'bi-arrow-counterclockwise',
            ],
            default => [
                'label' => ucfirst((string) $status),
                'class' => 'text-bg-light',
                'icon' => 'bi-circle',
            ],
        };
    }

    private function itemStatusMeta(?string $status): array
    {
        return match ($status) {
            SantriMigrationBatchItem::STATUS_COMPLETED => [
                'label' => 'Completed',
                'class' => 'text-bg-success',
            ],
            SantriMigrationBatchItem::STATUS_FAILED => [
                'label' => 'Failed',
                'class' => 'text-bg-danger',
            ],
            SantriMigrationBatchItem::STATUS_ROLLED_BACK => [
                'label' => 'Rolled Back',
                'class' => 'text-bg-warning',
            ],
            default => [
                'label' => 'Pending',
                'class' => 'text-bg-warning',
            ],
        };
    }

    private function transitionLabel(?string $transition): string
    {
        return match ($transition) {
            'naik_kelas' => 'Naik Kelas',
            'tinggal_kelas' => 'Tinggal Kelas',
            'mutasi' => 'Mutasi',
            'penempatan' => 'Penempatan',
            'lulus' => 'Lulus',
            null, '' => 'Multi Mapping',
            default => str($transition)->replace('_', ' ')->title()->toString(),
        };
    }
}
