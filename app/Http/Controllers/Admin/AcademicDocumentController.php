<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicDocuments\CancelRaportDraftRequest;
use App\Http\Requests\Admin\AcademicDocuments\GenerateRaportDraftRequest;
use App\Http\Requests\Admin\AcademicDocuments\RegenerateRaportDraftRequest;
use App\Http\Requests\Admin\AcademicDocuments\UpdateRaportDraftRequest;
use App\Models\AcademicDocument;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\SantriSemesterPlacement;
use App\Models\Semester;
use App\Services\AcademicDocuments\RaportDraftService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class AcademicDocumentController extends Controller
{
    public function index(Request $request)
    {
        $semesterList = Semester::query()
            ->with('tahunAjaran')
            ->orderByDesc('is_active')
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id')
            ->get();

        $kelasList = Kelas::query()
            ->orderBy('nama_kelas')
            ->get([
                'id',
                'nama_kelas',
            ]);

        $defaultSemesterId = (int) (
            $request->integer('semester_id')
            ?: optional(
                $semesterList->firstWhere(
                    'is_active',
                    true
                )
                ?? $semesterList->first()
            )->id
        );

        return view(
            'admin.academic-documents.index',
            compact(
                'semesterList',
                'kelasList',
                'defaultSemesterId'
            )
        );
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'semester_id' => [
                'required',
                'integer',
                'exists:semesters,id',
            ],

            'kelas_id' => [
                'nullable',
                'integer',
                'exists:kelas,id',
            ],

            'document_status' => [
                'nullable',
                Rule::in([
                    'none',
                    AcademicDocument::STATUS_DRAFT,
                    AcademicDocument::STATUS_REVIEW,
                    AcademicDocument::STATUS_PUBLISHED,
                    AcademicDocument::STATUS_REVOKED,
                    AcademicDocument::STATUS_CANCELLED,
                ]),
            ],
        ]);

        /*
         * Satu row tabel tetap mewakili satu placement.
         *
         * Jika ada dokumen current, tampilkan dokumen current.
         * Jika tidak ada current karena draft dibatalkan, tampilkan
         * dokumen historis terbaru agar status Dibatalkan tetap terlihat.
         */
        $documentRefs = AcademicDocument::query()
            ->selectRaw(
                '
                    santri_id,
                    semester_id,
                    MAX(
                        CASE
                            WHEN is_current = 1
                            THEN id
                            ELSE NULL
                        END
                    ) AS current_id,
                    MAX(id) AS latest_id
                '
            )
            ->where(
                'document_type',
                AcademicDocument::TYPE_RAPORT
            )
            ->groupBy([
                'santri_id',
                'semester_id',
            ]);

        $query = SantriSemesterPlacement::query()
            ->from(
                'santri_semester_placements as placements'
            )
            ->join(
                'santris',
                'santris.id',
                '=',
                'placements.santri_id'
            )
            ->leftJoin(
                'kelas',
                'kelas.id',
                '=',
                'placements.kelas_id'
            )
            ->leftJoin(
                'musyrifs',
                'musyrifs.id',
                '=',
                'placements.musyrif_id'
            )
            ->leftJoinSub(
                $documentRefs,
                'document_refs',
                function ($join): void {
                    $join
                        ->on(
                            'document_refs.santri_id',
                            '=',
                            'placements.santri_id'
                        )
                        ->on(
                            'document_refs.semester_id',
                            '=',
                            'placements.semester_id'
                        );
                }
            )
            ->leftJoin(
                'academic_documents as documents',
                function ($join): void {
                    $join->on(
                        'documents.id',
                        '=',
                        DB::raw(
                            'COALESCE('
                            . 'document_refs.current_id, '
                            . 'document_refs.latest_id'
                            . ')'
                        )
                    );
                }
            )
            ->where(
                'placements.semester_id',
                (int) $validated['semester_id']
            )
            ->when(
                filled(
                    $validated['kelas_id']
                    ?? null
                ),
                fn(Builder $query): Builder =>
                    $query->where(
                        'placements.kelas_id',
                        (int) $validated['kelas_id']
                    )
            )
            ->when(
                filled(
                    $validated['document_status']
                    ?? null
                ),
                function (
                    Builder $query
                ) use ($validated): Builder {
                    $status =
                        $validated['document_status'];

                    if ($status === 'none') {
                        return $query->whereNull(
                            'documents.id'
                        );
                    }

                    return $query->where(
                        'documents.status',
                        $status
                    );
                }
            )
            ->select([
                'placements.id as placement_id',
                'placements.santri_id',
                'placements.semester_id',
                'placements.status as placement_status',

                'santris.nama as santri_nama',
                'santris.nis as santri_nis',
                'santris.status as santri_status',

                'kelas.nama_kelas as kelas_nama',

                'musyrifs.nama as musyrif_nama',
                'musyrifs.kode as musyrif_kode',

                'documents.id as document_id',
                'documents.public_id as document_public_id',
                'documents.status as document_status',
                'documents.revision as document_revision',
                'documents.is_current as document_is_current',
                'documents.predikat as document_predikat',
                'documents.snapshot_sha256',
                'documents.generated_at',
                'documents.updated_at as document_updated_at',
                'documents.cancelled_at',
                'documents.cancellation_reason',
                'documents.cancelled_by',
            ]);

        return DataTables::eloquent($query)
            ->addIndexColumn()

            ->filterColumn(
                'santri_nama',
                function (
                    Builder $query,
                    string $keyword
                ): void {
                    $query->where(
                        function (
                            Builder $nested
                        ) use ($keyword): void {
                            $nested
                                ->where(
                                    'santris.nama',
                                    'like',
                                    "%{$keyword}%"
                                )
                                ->orWhere(
                                    'santris.nis',
                                    'like',
                                    "%{$keyword}%"
                                );
                        }
                    );
                }
            )

            ->addColumn(
                'santri_display',
                function ($row): string {
                    $name = e(
                        $row->santri_nama
                    );

                    $nis = e(
                        $row->santri_nis
                        ?: '-'
                    );

                    return <<<HTML
                    <div class="fw-semibold text-body">{$name}</div>
                    <div class="small text-body-secondary">NIS: {$nis}</div>
                    HTML;
                }
            )

            ->addColumn(
                'placement_display',
                function ($row): string {
                    $kelas = e(
                        $row->kelas_nama
                        ?: 'Belum ada kelas'
                    );

                    $musyrif = e(
                        $row->musyrif_nama
                        ?: 'Belum ada musyrif'
                    );

                    $kode = $row->musyrif_kode
                        ? ' · ' . e(
                            $row->musyrif_kode
                        )
                        : '';

                    return <<<HTML
                    <div class="fw-semibold text-body">{$kelas}</div>
                    <div class="small text-body-secondary">{$musyrif}{$kode}</div>
                    HTML;
                }
            )

            ->addColumn(
                'placement_status_badge',
                fn($row): string =>
                    $this->placementStatusBadge(
                        $row->placement_status
                    )
            )

            ->addColumn(
                'document_status_badge',
                fn($row): string =>
                    $this->documentStatusBadge(
                        $row->document_status
                    )
            )

            ->addColumn(
                'document_info',
                function ($row): string {
                    if (!$row->document_id) {
                        return '<span class="text-body-secondary">Belum dibuat</span>';
                    }

                    $revision =
                        (int) $row->document_revision;

                    $generatedAt =
                        $row->generated_at
                            ? date(
                                'd M Y H:i',
                                strtotime(
                                    $row->generated_at
                                )
                            )
                            : '-';

                    $predicate = e(
                        $this->predicateLabel(
                            $row->document_predikat
                        )
                    );

                    $cancelledInfo = '';

                    if (
                        $row->document_status
                        === AcademicDocument::STATUS_CANCELLED
                    ) {
                        $cancelledAt =
                            $row->cancelled_at
                                ? date(
                                    'd M Y H:i',
                                    strtotime(
                                        $row->cancelled_at
                                    )
                                )
                                : '-';

                        $reason = e(
                            Str::limit(
                                (string) (
                                    $row->cancellation_reason
                                    ?: 'Tidak ada alasan.'
                                ),
                                80
                            )
                        );

                        $cancelledInfo = <<<HTML
                        <div class="mt-1 text-danger">
                            <strong>Dibatalkan:</strong> {$cancelledAt}
                        </div>
                        <div class="text-body-secondary">{$reason}</div>
                        HTML;
                    }

                    return <<<HTML
                    <div class="small">
                        <div><strong>Revisi:</strong> {$revision}</div>
                        <div><strong>Predikat:</strong> {$predicate}</div>
                        <div class="text-body-secondary">Snapshot: {$generatedAt}</div>
                        {$cancelledInfo}
                    </div>
                    HTML;
                }
            )

            ->addColumn(
                'actions',
                fn($row): string =>
                    $this->buildActions($row)
            )

            ->rawColumns([
                'santri_display',
                'placement_display',
                'placement_status_badge',
                'document_status_badge',
                'document_info',
                'actions',
            ])

            ->toJson();
    }

    public function generateRaportDraft(
        GenerateRaportDraftRequest $request,
        RaportDraftService $service
    ): JsonResponse {
        $validated = $request->validated();

        $santri = Santri::query()
            ->findOrFail(
                (int) $validated['santri_id']
            );

        $semester = Semester::query()
            ->findOrFail(
                (int) $validated['semester_id']
            );

        $result = $service->createDraft(
            santri: $santri,
            semester: $semester,
            actorId: $request->user()?->id,
            manualFields: $request->manualFields()
        );

        return response()->json(
            $result->toArray(),
            $result->created()
                ? 201
                : 200
        );
    }

    public function regenerateRaportDraft(
        RegenerateRaportDraftRequest $request,
        AcademicDocument $academicDocument,
        RaportDraftService $service
    ): JsonResponse {
        $result = $service->regenerateDraft(
            document: $academicDocument,
            actorId: $request->user()?->id,
            manualFields: $request->manualFields()
        );

        return response()->json(
            $result->toArray()
        );
    }

    public function cancelRaportDraft(
        CancelRaportDraftRequest $request,
        AcademicDocument $academicDocument,
        RaportDraftService $service
    ): JsonResponse {
        $document = $service->cancelDraft(
            document: $academicDocument,
            actorId: (int) $request->user()->id,
            reason: $request->reason()
        );

        return response()->json([
            'ok' => true,
            'message' =>
                'Draft Raport berhasil dibatalkan.',

            'document' => [
                'public_id' =>
                    $document->public_id,

                'status' =>
                    $document->status,

                'status_label' =>
                    $document->status_label,

                'revision' =>
                    (int) $document->revision,

                'is_current' =>
                    (bool) $document->is_current,

                'cancelled_at' =>
                    $document
                    ->cancelled_at
                    ?->toIso8601String(),

                'cancellation_reason' =>
                    $document->cancellation_reason,
            ],
        ]);
    }

    public function updateRaportDraft(
        UpdateRaportDraftRequest $request,
        AcademicDocument $academicDocument
    ): JsonResponse {
        $this->assertEditableDraft(
            $academicDocument
        );

        $validated = $request->validated();

        DB::transaction(
            function () use (
                $academicDocument,
                $validated,
                $request
            ): void {
                $document =
                    AcademicDocument::query()
                    ->whereKey(
                        $academicDocument->getKey()
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertEditableDraft(
                    $document
                );

                $document->fill([
                    'predikat' =>
                        $validated['predikat']
                        ?? null,

                    'catatan_musyrif' =>
                        $validated['catatan_musyrif']
                        ?? null,

                    'catatan_admin' =>
                        $validated['catatan_admin']
                        ?? null,

                    'rekomendasi' =>
                        $validated['rekomendasi']
                        ?? null,

                    'updated_by' =>
                        $request->user()?->id,

                    'metadata' =>
                        $this->appendDraftHistory(
                            $document->metadata
                            ?? [],
                            'manual_fields_updated',
                            $request->user()?->id
                        ),
                ]);

                $document->save();
            },
            3
        );

        return response()->json([
            'ok' => true,
            'message' =>
                'Evaluasi Draft Raport berhasil disimpan.',
        ]);
    }

    public function show(
        Request $request,
        AcademicDocument $academicDocument
    ): JsonResponse {
        abort_unless(
            $academicDocument->document_type
            === AcademicDocument::TYPE_RAPORT,
            404
        );

        $academicDocument->loadMissing([
            'santri:id,nama,nis,tanggal_lahir,jenis_kelamin,status',
            'semester.tahunAjaran',
            'createdBy:id,name',
            'updatedBy:id,name',
            'cancelledBy:id,name',
        ]);

        return response()->json([
            'ok' => true,

            'document' => [
                'public_id' =>
                    $academicDocument->public_id,

                'document_type' =>
                    $academicDocument->document_type,

                'document_type_label' =>
                    $academicDocument->document_type_label,

                'status' =>
                    $academicDocument->status,

                'status_label' =>
                    $academicDocument->status_label,

                'revision' =>
                    (int) $academicDocument->revision,

                'is_current' =>
                    (bool) $academicDocument->is_current,

                'predikat' =>
                    $academicDocument->predikat,

                'catatan_musyrif' =>
                    $academicDocument->catatan_musyrif,

                'catatan_admin' =>
                    $academicDocument->catatan_admin,

                'rekomendasi' =>
                    $academicDocument->rekomendasi,

                'review_notes' =>
                    $academicDocument->review_notes,

                'snapshot_sha256' =>
                    $academicDocument->snapshot_sha256,

                'snapshot' =>
                    $academicDocument->snapshot_json,

                'metadata' =>
                    $academicDocument->metadata,

                'can_cancel' =>
                    $academicDocument->canBeCancelled(),

                'cancelled_at' =>
                    $academicDocument
                    ->cancelled_at
                    ?->toIso8601String(),

                'cancellation_reason' =>
                    $academicDocument
                    ->cancellation_reason,

                'cancelled_by' =>
                    $academicDocument->cancelledBy
                        ? [
                            'id' =>
                                (int) $academicDocument
                                ->cancelledBy
                                ->id,

                            'name' =>
                                $academicDocument
                                ->cancelledBy
                                ->name,
                        ]
                        : null,

                'generated_at' =>
                    $academicDocument
                    ->generated_at
                    ?->toIso8601String(),

                'created_at' =>
                    $academicDocument
                    ->created_at
                    ?->toIso8601String(),

                'updated_at' =>
                    $academicDocument
                    ->updated_at
                    ?->toIso8601String(),
            ],
        ]);
    }

    private function assertEditableDraft(
        AcademicDocument $document
    ): void {
        if (
            $document->document_type
            !== AcademicDocument::TYPE_RAPORT
        ) {
            throw ValidationException::withMessages([
                'document' => [
                    'Dokumen ini bukan Raport.',
                ],
            ]);
        }

        if (
            !$document->is_current
            || !$document->isDraft()
        ) {
            throw ValidationException::withMessages([
                'document' => [
                    'Evaluasi hanya dapat diubah pada Draft Raport aktif.',
                ],
            ]);
        }
    }

    /**
     * @param array<string, mixed> $metadata
     *
     * @return array<string, mixed>
     */
    private function appendDraftHistory(
        array $metadata,
        string $action,
        ?int $actorId
    ): array {
        $history = collect(
            $metadata['draft_history']
            ?? []
        )->values();

        $history->push([
            'action' => $action,
            'actor_id' => $actorId,
            'at' => now()->toIso8601String(),
        ]);

        $metadata['draft_history'] =
            $history->all();

        $metadata['last_draft_action'] =
            $action;

        $metadata['last_draft_actor_id'] =
            $actorId;

        $metadata['last_draft_action_at'] =
            now()->toIso8601String();

        return $metadata;
    }

    private function placementStatusBadge(
        ?string $status
    ): string {
        $normalized = strtolower(
            (string) $status
        );

        [$class, $label] = match ($normalized) {
            'aktif' => [
                'bg-success-subtle text-success-emphasis',
                'Aktif',
            ],

            'tinggal' => [
                'bg-warning-subtle text-warning-emphasis',
                'Tinggal Kelas',
            ],

            'lulus' => [
                'bg-primary-subtle text-primary-emphasis',
                'Lulus',
            ],

            'keluar' => [
                'bg-danger-subtle text-danger-emphasis',
                'Keluar',
            ],

            default => [
                'bg-secondary-subtle text-secondary-emphasis',
                Str::title(
                    str_replace(
                        '_',
                        ' ',
                        $normalized ?: '-'
                    )
                ),
            ],
        };

        return sprintf(
            '<span class="badge rounded-pill %s">%s</span>',
            $class,
            e($label)
        );
    }

    private function documentStatusBadge(
        ?string $status
    ): string {
        if (!$status) {
            return '<span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Belum Dibuat</span>';
        }

        [$class, $label] = match ($status) {
            AcademicDocument::STATUS_DRAFT => [
                'bg-warning-subtle text-warning-emphasis',
                'Draft',
            ],

            AcademicDocument::STATUS_REVIEW => [
                'bg-info-subtle text-info-emphasis',
                'Menunggu Pemeriksaan',
            ],

            AcademicDocument::STATUS_PUBLISHED => [
                'bg-success-subtle text-success-emphasis',
                'Tersedia',
            ],

            AcademicDocument::STATUS_REVOKED => [
                'bg-danger-subtle text-danger-emphasis',
                'Dicabut',
            ],

            AcademicDocument::STATUS_CANCELLED => [
                'bg-secondary-subtle text-secondary-emphasis',
                'Dibatalkan',
            ],

            default => [
                'bg-secondary-subtle text-secondary-emphasis',
                Str::title(
                    str_replace(
                        '_',
                        ' ',
                        $status
                    )
                ),
            ],
        };

        return sprintf(
            '<span class="badge rounded-pill %s">%s</span>',
            $class,
            e($label)
        );
    }

    private function buildActions(
        mixed $row
    ): string {
        $santriId =
            (int) $row->santri_id;

        $semesterId =
            (int) $row->semester_id;

        $generateButton = <<<HTML
        <button
            type="button"
            class="btn btn-sm btn-primary js-generate-draft"
            data-santri-id="{$santriId}"
            data-semester-id="{$semesterId}"
        >
            <i class="bi bi-file-earmark-plus me-1"></i>
            Buat Draft
        </button>
        HTML;

        if (!$row->document_id) {
            return $generateButton;
        }

        $publicId = e(
            $row->document_public_id
        );

        $showUrl = route(
            'admin.academic-documents.show',
            $row->document_public_id
        );

        $regenerateUrl = route(
            'admin.academic-documents.raport.draft.regenerate',
            $row->document_public_id
        );

        $updateUrl = route(
            'admin.academic-documents.raport.draft.update',
            $row->document_public_id
        );

        $cancelUrl = route(
            'admin.academic-documents.raport.draft.cancel',
            $row->document_public_id
        );

        $buttons = <<<HTML
        <div class="d-flex flex-wrap gap-1 justify-content-center">
            <button
                type="button"
                class="btn btn-sm btn-outline-primary js-preview-document"
                data-public-id="{$publicId}"
                data-show-url="{$showUrl}"
                data-update-url="{$updateUrl}"
            >
                <i class="bi bi-eye me-1"></i>
                Preview
            </button>
        HTML;

        if (
            $row->document_status
            === AcademicDocument::STATUS_DRAFT
            && (bool) $row->document_is_current
        ) {
            $buttons .= <<<HTML
            <button
                type="button"
                class="btn btn-sm btn-outline-warning js-regenerate-draft"
                data-url="{$regenerateUrl}"
            >
                <i class="bi bi-arrow-clockwise me-1"></i>
                Regenerate
            </button>

            <button
                type="button"
                class="btn btn-sm btn-outline-danger js-cancel-draft"
                data-url="{$cancelUrl}"
            >
                <i class="bi bi-x-circle me-1"></i>
                Batalkan
            </button>
            HTML;
        }

        if (
            $row->document_status
            === AcademicDocument::STATUS_CANCELLED
        ) {
            $buttons .= <<<HTML
            <button
                type="button"
                class="btn btn-sm btn-primary js-generate-draft"
                data-santri-id="{$santriId}"
                data-semester-id="{$semesterId}"
            >
                <i class="bi bi-file-earmark-plus me-1"></i>
                Buat Draft Baru
            </button>
            HTML;
        }

        $buttons .= '</div>';

        return $buttons;
    }

    private function predicateLabel(
        ?string $predicate
    ): string {
        return match ($predicate) {
            'mumtaz' => 'Mumtaz',
            'jayyid_jiddan' => 'Jayyid Jiddan',
            'jayyid' => 'Jayyid',
            'mardud' => 'Mardud',
            null, '' => 'Belum ditetapkan',
            default => Str::title(
                str_replace(
                    '_',
                    ' ',
                    $predicate
                )
            ),
        };
    }
}
