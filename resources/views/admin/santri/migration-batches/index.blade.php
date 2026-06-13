@extends('layouts.app')

@section('title', 'Riwayat & Audit Migrasi Santri')

@section('content')
    <style>
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ececec !important;
        }

        .audit-main-card,
        .audit-filter-card,
        .audit-stat-card {
            border: 0;
            border-radius: 20px;
            background: var(--cui-card-bg);
            box-shadow: 0 10px 30px rgba(0, 0, 0, .045);
        }

        .audit-stat-card {
            overflow: hidden;
            position: relative;
        }

        .audit-stat-card::after {
            content: '';
            position: absolute;
            width: 84px;
            height: 84px;
            border-radius: 50%;
            right: -28px;
            top: -30px;
            background: currentColor;
            opacity: .07;
        }

        .audit-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--cui-tertiary-bg);
            font-size: 1.15rem;
        }

        .audit-table thead th,
        .audit-items-table thead th {
            background: var(--cui-tertiary-bg);
            color: var(--cui-secondary-color);
            border-bottom: 1px solid var(--cui-border-color);
            font-size: .73rem;
            font-weight: 700;
            letter-spacing: .45px;
            padding: 14px 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .audit-progress {
            height: 6px;
            min-width: 120px;
            background: var(--cui-tertiary-bg);
        }

        .audit-error {
            max-width: 180px;
        }

        .audit-detail-label {
            color: var(--cui-secondary-color);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .audit-detail-value {
            font-weight: 600;
            margin-top: .2rem;
            word-break: break-word;
        }

        .audit-json-box {
            background: var(--cui-tertiary-bg);
            border: 1px solid var(--cui-border-color);
            border-radius: 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: .8rem;
            max-height: 360px;
            overflow: auto;
            padding: 1rem;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .audit-floating-guide {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 1040;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            box-shadow: 0 12px 30px rgba(13, 110, 253, .28);
        }

        [data-coreui-theme="dark"] .modal-content,
        [data-coreui-theme="dark"] .audit-filter-card,
        [data-coreui-theme="dark"] .audit-main-card,
        [data-coreui-theme="dark"] .audit-stat-card {
            background: var(--cui-card-bg) !important;
        }

        @media (max-width: 767.98px) {
            .audit-floating-guide {
                right: 16px;
                bottom: 16px;
            }
        }
    </style>

    <div class="row mb-4 align-items-center g-3">
        <div class="col-lg">
            <h4 class="fw-bold text-adaptive-purple mb-1">
                Riwayat & Audit Migrasi Santri
            </h4>
            <p class="text-body-secondary small mb-0">
                <i class="bi bi-shield-check me-1"></i>
                Pantau batch Preview, eksekusi, kelulusan, pembatalan, dan kegagalan migrasi semester.
            </p>
        </div>

        <div class="col-lg-auto d-flex flex-wrap gap-2">
            <a href="{{ route('admin.santri.migrasi.page') }}" class="btn btn-outline-primary rounded-pill px-3">
                <i class="bi bi-arrow-repeat me-1"></i>
                Proses Migrasi
            </a>

            <button type="button" class="btn btn-success text-white rounded-pill px-3 no-loader" id="btnExportAudit">
                <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i>
                Export CSV
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl">
            <div class="card audit-stat-card h-100 text-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-body-secondary">Total Batch</div>
                            <div class="fs-4 fw-bold count-up" id="statTotal" data-target="0">0</div>
                        </div>
                        <span class="audit-stat-icon"><i class="bi bi-collection-fill"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card audit-stat-card h-100 text-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-body-secondary">Previewed</div>
                            <div class="fs-4 fw-bold" id="statPreviewed">0</div>
                        </div>
                        <span class="audit-stat-icon"><i class="bi bi-eye-fill"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card audit-stat-card h-100 text-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-body-secondary">Completed</div>
                            <div class="fs-4 fw-bold" id="statCompleted">0</div>
                        </div>
                        <span class="audit-stat-icon"><i class="bi bi-check-circle-fill"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card audit-stat-card h-100 text-danger">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-body-secondary">Failed</div>
                            <div class="fs-4 fw-bold" id="statFailed">0</div>
                        </div>
                        <span class="audit-stat-icon"><i class="bi bi-exclamation-octagon-fill"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card audit-stat-card h-100 text-secondary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-body-secondary">Dibatalkan</div>
                            <div class="fs-4 fw-bold" id="statCancelled">0</div>
                        </div>
                        <span class="audit-stat-icon"><i class="bi bi-x-circle-fill"></i></span>
                    </div>
                </div>
            </div>
        </div>


        <div class="col-6 col-md-4 col-xl">
            <div class="card audit-stat-card h-100 text-warning">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-body-secondary">Rolled Back</div>
                            <div class="fs-4 fw-bold" id="statRolledBack">0</div>
                        </div>
                        <span class="audit-stat-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-4 col-xl">
            <div class="card audit-stat-card h-100 text-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div>
                            <div class="small text-body-secondary">Santri Diproses</div>
                            <div class="fs-4 fw-bold" id="statItems">0</div>
                        </div>
                        <span class="audit-stat-icon"><i class="bi bi-people-fill"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card audit-filter-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="bi bi-funnel-fill me-1"></i> Filter Audit
                </h6>
                <button type="button" class="btn btn-sm btn-link text-decoration-none" id="btnResetFilter">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-2">
                    <label class="form-label small fw-semibold" for="filterMode">Mode</label>
                    <select class="form-select" id="filterMode">
                        <option value="">Semua Mode</option>
                        <option value="manual">Manual</option>
                        <option value="auto">Auto</option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label small fw-semibold" for="filterStatus">Status</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="previewed">Previewed</option>
                        <option value="executing">Executing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                        <option value="rolled_back">Rolled Back</option>
                    </select>
                </div>

                <div class="col-md-6 col-xl-3">
                    <label class="form-label small fw-semibold" for="filterSemester">Semester</label>
                    <select class="form-select" id="filterSemester">
                        <option value="">Semua Semester</option>
                        @foreach ($semesterList as $semester)
                            @php
                                $semesterName = \Illuminate\Support\Str::title(
                                    str_replace('_', ' ', $semester->nama ?? '-'),
                                );
                                $yearName = \Illuminate\Support\Str::title(
                                    str_replace('_', ' ', $semester->tahunAjaran?->nama ?? '-'),
                                );
                            @endphp
                            <option value="{{ $semester->id }}">
                                {{ $semesterName }} — {{ $yearName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label small fw-semibold" for="filterDateFrom">Dari Tanggal</label>
                    <input type="date" class="form-control" id="filterDateFrom">
                </div>

                <div class="col-md-6 col-xl-2">
                    <label class="form-label small fw-semibold" for="filterDateTo">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="filterDateTo">
                </div>

                <div class="col-md-6 col-xl-1 d-grid align-self-end">
                    <button type="button" class="btn btn-primary" id="btnApplyFilter">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card audit-main-card mb-4">
        <div class="card-header bg-transparent border-0 px-4 py-3">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h6 class="fw-bold mb-1">Daftar Batch Migrasi</h6>
                    <div class="small text-white-50 mb-0">
                        Cari kode batch melalui kotak pencarian tabel.
                    </div>
                </div>
                <span class="badge text-bg-light rounded-pill px-3 py-2" id="statGraduatedBadge">
                    0 santri lulus
                </span>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100 audit-table" id="migrationBatchTable">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Batch</th>
                            <th>Semester</th>
                            <th>Cakupan</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Aktor</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-primary audit-floating-guide" data-bs-toggle="modal"
        data-bs-target="#modalAuditGuide" aria-label="Petunjuk halaman">
        <i class="bi bi-info-lg fs-5"></i>
    </button>
@endsection

@push('modals')
    <div class="modal fade" id="modalBatchDetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 px-4 py-3">
                    <div>
                        <div class="small text-white-50 mb-0">Detail Audit Batch</div>
                        <h5 class="modal-title fw-bold font-monospace" id="detailBatchCode">-</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>

                <div class="modal-body px-4 pb-4">
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge rounded-pill px-3 py-2" id="detailStatusBadge">-</span>
                        <span class="badge text-bg-info rounded-pill px-3 py-2" id="detailModeBadge">-</span>
                        <span class="badge text-bg-light rounded-pill px-3 py-2" id="detailProgressBadge">-</span>
                    </div>

                    <div class="alert alert-danger d-none" id="detailErrorAlert"></div>
                    <div class="alert alert-secondary d-none" id="detailNoteAlert"></div>

                    <ul class="nav nav-pills gap-2 mb-4" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active rounded-pill" data-bs-toggle="pill"
                                data-bs-target="#batchOverviewPane" type="button">
                                <i class="bi bi-info-circle me-1"></i> Ringkasan
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill" data-bs-toggle="pill" data-bs-target="#batchItemsPane"
                                type="button">
                                <i class="bi bi-people me-1"></i> Item Santri
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link rounded-pill" data-bs-toggle="pill"
                                data-bs-target="#batchMetadataPane" type="button">
                                <i class="bi bi-braces me-1"></i> Metadata
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="batchOverviewPane">
                            <div class="row g-3" id="batchOverviewGrid"></div>
                        </div>

                        <div class="tab-pane fade" id="batchItemsPane">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle w-100 audit-items-table"
                                    id="batchItemsTable">
                                    <thead>
                                        <tr>
                                            <th>Santri</th>
                                            <th>Asal</th>
                                            <th>Tujuan</th>
                                            <th>Tipe</th>
                                            <th>Status</th>
                                            <th>Eksekusi</th>
                                            <th>Snapshot</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="batchMetadataPane">
                            <div class="audit-json-box" id="detailMetadataJson">{}</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-outline-warning rounded-pill px-4 d-none"
                        id="btnRollbackFromDetail">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Rollback Batch
                    </button>

                    <button type="button" class="btn btn-outline-danger rounded-pill px-4 d-none"
                        id="btnCancelFromDetail">
                        <i class="bi bi-x-circle me-1"></i> Batalkan Batch
                    </button>
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAuditGuide" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 px-4 pt-4">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-info-circle-fill text-primary me-1"></i>
                        Petunjuk Riwayat Migrasi
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="fw-bold mb-2">Status Batch</div>
                                <div class="small text-body-secondary">
                                    <strong>Previewed</strong> masih dapat dieksekusi atau dibatalkan,
                                    <strong>Completed</strong> telah selesai, sedangkan Failed, Cancelled,
                                    dan Expired hanya dapat ditinjau untuk audit.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="fw-bold mb-2">Detail Item</div>
                                <div class="small text-body-secondary">
                                    Buka Detail lalu pilih tab Item Santri untuk membandingkan kelas dan
                                    musyrif asal–tujuan serta melihat snapshot JSON setiap santri.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="fw-bold mb-2">Pembatalan</div>
                                <div class="small text-body-secondary">
                                    Hanya batch Previewed yang belum kedaluwarsa yang dapat dibatalkan.
                                    Riwayat dan item batch tetap tersimpan sebagai bukti audit.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-4 h-100">
                                <div class="fw-bold mb-2">Export</div>
                                <div class="small text-body-secondary">
                                    Export CSV mengikuti filter mode, status, semester, dan tanggal yang
                                    sedang dipilih pada halaman.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            const csrf = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content');

            const routes = {
                data: @json(route('admin.santri.migrasi.audit.data')),
                statistics: @json(route('admin.santri.migrasi.audit.statistics')),
                export: @json(route('admin.santri.migrasi.audit.export')),
                show: @json(route('admin.santri.migrasi.audit.show', ['batch' => '__BATCH__'])),
                items: @json(route('admin.santri.migrasi.audit.items', ['batch' => '__BATCH__'])),
                cancel: @json(route('admin.santri.migrasi.audit.cancel', ['batch' => '__BATCH__'])),
                rollbackCheck: @json(route('admin.santri.migrasi.audit.rollback-check', ['batch' => '__BATCH__'])),
                rollback: @json(route('admin.santri.migrasi.audit.rollback', ['batch' => '__BATCH__']))
            };

            let selectedBatchId = null;
            let selectedBatchCode = null;
            let itemTable = null;

            const detailModal = bootstrap.Modal.getOrCreateInstance(
                document.getElementById('modalBatchDetail')
            );


            function filterPayload() {
                return {
                    mode: $('#filterMode').val(),
                    status: $('#filterStatus').val(),
                    semester_id: $('#filterSemester').val(),
                    date_from: $('#filterDateFrom').val(),
                    date_to: $('#filterDateTo').val()
                };
            }

            function queryString(payload) {
                const params = new URLSearchParams();

                Object.entries(payload).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        params.set(key, value);
                    }
                });

                return params.toString();
            }

            const table = $('#migrationBatchTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                ajax: {
                    url: routes.data,
                    data: function(data) {
                        Object.assign(data, filterPayload());
                    }
                },
                columns: [{
                        data: 'waktu',
                        name: 'created_at'
                    },
                    {
                        data: 'batch_info',
                        name: 'code'
                    },
                    {
                        data: 'semester_flow',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'cakupan',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'progress',
                        name: 'items_count',
                        searchable: false
                    },
                    {
                        data: 'status_badge',
                        name: 'status'
                    },
                    {
                        data: 'aktor',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ],
                order: [
                    [0, 'desc']
                ],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                }
            });

            async function loadStatistics() {
                try {
                    const response = await fetch(
                        `${routes.statistics}?${queryString(filterPayload())}`, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                    const json = await response.json();

                    if (!response.ok) {
                        throw new Error(json?.message || 'Gagal memuat statistik.');
                    }

                    const stats = json.data ?? {};

                    $('#statTotal').text(Number(stats.total ?? 0).toLocaleString('id-ID'));
                    $('#statPreviewed').text(Number(stats.previewed ?? 0).toLocaleString('id-ID'));
                    $('#statCompleted').text(Number(stats.completed ?? 0).toLocaleString('id-ID'));
                    $('#statFailed').text(Number(stats.failed ?? 0).toLocaleString('id-ID'));
                    $('#statCancelled').text(Number(stats.cancelled ?? 0).toLocaleString('id-ID'));
                    $('#statRolledBack').text(Number(stats.rolled_back ?? 0).toLocaleString('id-ID'));
                    $('#statItems').text(Number(stats.items ?? 0).toLocaleString('id-ID'));
                    $('#statGraduatedBadge').text(
                        `${Number(stats.graduated ?? 0).toLocaleString('id-ID')} santri lulus`
                    );
                } catch (error) {
                    console.error(error);
                }
            }

            function reloadAudit() {
                table.ajax.reload(null, false);
                loadStatistics();
            }

            $('#btnApplyFilter').on('click', reloadAudit);

            $('#filterMode, #filterStatus, #filterSemester').on('change', function() {
                reloadAudit();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterMode, #filterStatus, #filterSemester').val('');
                $('#filterDateFrom, #filterDateTo').val('');
                reloadAudit();
            });

            $('#btnExportAudit').on('click', function() {
                window.location.href = `${routes.export}?${queryString(filterPayload())}`;
            });

            function detailField(label, value, column = 'col-md-6 col-xl-4') {
                return `
                    <div class="${column}">
                        <div class="p-3 border rounded-4 h-100">
                            <div class="audit-detail-label">${escapeHtml(label)}</div>
                            <div class="audit-detail-value">${escapeHtml(value ?? '-')}</div>
                        </div>
                    </div>
                `;
            }

            function renderBatchOverview(batch) {
                $('#detailBatchCode').text(batch.code ?? '-');
                $('#detailStatusBadge')
                    .attr('class', `badge ${batch.status_class} rounded-pill px-3 py-2`)
                    .text(batch.status_label ?? '-');
                $('#detailModeBadge').text(batch.mode_label ?? '-');
                $('#detailProgressBadge').text(
                    `${batch.completed_count ?? 0} / ${batch.items_count ?? 0} selesai (${batch.progress_percentage ?? 0}%)`
                );

                $('#detailErrorAlert')
                    .toggleClass('d-none', !batch.last_error)
                    .text(batch.last_error ?? '');

                $('#detailNoteAlert')
                    .toggleClass('d-none', !batch.note)
                    .html(batch.note ? `<strong>Catatan:</strong> ${escapeHtml(batch.note)}` : '');

                $('#batchOverviewGrid').html([
                    detailField('Semester Asal', batch.from_semester),
                    detailField('Semester Tujuan', batch.to_semester),
                    detailField('Mode / Tipe', `${batch.mode_label} / ${batch.transition_label}`),
                    detailField('Kelas Asal', batch.from_kelas ?? (batch.mode === 'auto' ? 'Multi Kelas' :
                        '-')),
                    detailField('Kelas Tujuan', batch.to_kelas ?? (batch.mode === 'auto' ? 'Multi Mapping' :
                        '-')),
                    detailField('Santri Lulus', batch.graduated_count),
                    detailField('Pembuat', batch.creator),
                    detailField('Pelaksana', batch.executor ?? '-'),
                    detailField('Snapshot Hash', batch.snapshot_hash, 'col-12'),
                    detailField('Dibuat', batch.created_at),
                    detailField('Preview', batch.previewed_at),
                    detailField('Kedaluwarsa', batch.expires_at),
                    detailField('Mulai Eksekusi', batch.executing_at),
                    detailField('Selesai Eksekusi', batch.executed_at),
                    detailField('Dibatalkan / Gagal', batch.cancelled_at ?? batch.failed_at ?? '-'),
                    detailField('Rollback Oleh', batch.rolled_back_by ?? '-'),
                    detailField('Waktu Rollback', batch.rolled_back_at ?? '-'),
                    detailField('Alasan Rollback', batch.rollback_reason ?? '-', 'col-12')
                ].join(''));

                $('#detailMetadataJson').text(
                    JSON.stringify(batch.metadata ?? {}, null, 4)
                );

                $('#btnCancelFromDetail')
                    .toggleClass('d-none', !batch.can_cancel);

                $('#btnRollbackFromDetail')
                    .toggleClass(
                        'd-none',
                        !batch.can_request_rollback
                    );
            }

            function initializeItemTable(batchId) {
                if (itemTable) {
                    itemTable.destroy();
                    $('#batchItemsTable tbody').empty();
                }

                const url = routes.items.replace('__BATCH__', batchId);

                itemTable = $('#batchItemsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: false,
                    ajax: url,
                    columns: [{
                            data: 'santri_info',
                            name: 'santri_id'
                        },
                        {
                            data: 'asal',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'tujuan',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'transition_badge',
                            name: 'transition_type'
                        },
                        {
                            data: 'status_badge',
                            name: 'status'
                        },
                        {
                            data: 'executed_time',
                            name: 'executed_at'
                        },
                        {
                            data: 'aksi',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    order: [
                        [0, 'asc']
                    ],
                    pageLength: 10,
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                    }
                });
            }

            async function openBatchDetail(batchId) {
                try {
                    const response = await fetch(
                        routes.show.replace('__BATCH__', batchId), {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                    const json = await response.json();

                    if (!response.ok) {
                        throw new Error(json?.message || 'Gagal mengambil detail batch.');
                    }

                    selectedBatchId = batchId;
                    selectedBatchCode = json.batch?.code ?? null;
                    renderBatchOverview(json.batch ?? {});
                    initializeItemTable(batchId);
                    detailModal.show();
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: error.message
                    });
                }
            }

            $('#migrationBatchTable tbody').on('click', '.btn-detail-batch', function() {
                openBatchDetail($(this).data('batch-id'));
            });

            $('#migrationBatchTable tbody').on('click', '.btn-cancel-batch', function() {
                cancelBatch(
                    $(this).data('batch-id'),
                    $(this).data('batch-code')
                );
            });

            $('#btnCancelFromDetail').on('click', function() {
                cancelBatch(selectedBatchId, selectedBatchCode, true);
            });

            $('#migrationBatchTable tbody').on(
                'click',
                '.btn-rollback-batch',
                function() {
                    rollbackBatch(
                        $(this).data('batch-id'),
                        $(this).data('batch-code')
                    );
                }
            );

            $('#btnRollbackFromDetail').on(
                'click',
                function() {
                    rollbackBatch(
                        selectedBatchId,
                        selectedBatchCode,
                        true
                    );
                }
            );

            async function rollbackBatch(
                batchId,
                batchCode,
                closeDetail = false
            ) {
                if (!batchId) {
                    return;
                }

                try {
                    const checkResponse = await fetch(
                        routes.rollbackCheck.replace(
                            '__BATCH__',
                            batchId
                        ), {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                    const checkJson = await checkResponse.json();

                    if (!checkResponse.ok) {
                        throw new Error(
                            checkJson?.message ||
                            'Gagal memeriksa kelayakan rollback.'
                        );
                    }

                    const inspection =
                        checkJson.inspection ?? {};

                    if (!inspection.eligible) {
                        const blockers = Array.isArray(
                                inspection.blockers
                            ) ?
                            inspection.blockers : [];

                        await Swal.fire({
                            icon: 'error',
                            title: 'Rollback Tidak Diizinkan',
                            width: 760,
                            html: `
                                <div class="text-start">
                                    <p>Batch <strong>${escapeHtml(batchCode ?? '')}</strong> tidak memenuhi syarat rollback.</p>
                                    <ul class="mb-0">
                                        ${blockers.map(blocker => `
                                                    <li class="mb-2">
                                                        ${escapeHtml(blocker.message ?? '-')}
                                                    </li>
                                                `).join('')}
                                    </ul>
                                </div>
                            `,
                            confirmButtonText: 'Tutup'
                        });

                        return;
                    }

                    const result = await Swal.fire({
                        icon: 'warning',
                        title: 'Rollback Batch Migrasi?',
                        width: 760,
                        html: `
                            <div class="text-start">
                                <p>
                                    Batch <strong>${escapeHtml(batchCode ?? '')}</strong>
                                    akan mengembalikan <strong>${Number(inspection.items_count ?? 0)}</strong>
                                    santri ke kelas dan musyrif semester asal.
                                </p>
                                <div class="alert alert-warning small">
                                    Placement semester tujuan akan dihapus. Placement semester asal dibuka kembali.
                                    Jejak batch dan histori status tetap disimpan.
                                </div>
                                <label class="form-label fw-semibold" for="rollbackConfirmationCode">
                                    Ketik kode batch untuk konfirmasi
                                </label>
                                <input type="text" class="form-control mb-3"
                                    id="rollbackConfirmationCode"
                                    placeholder="${escapeHtml(batchCode ?? '')}">

                                <label class="form-label fw-semibold" for="rollbackReason">
                                    Alasan rollback
                                </label>
                                <textarea class="form-control" rows="4"
                                    id="rollbackReason"
                                    placeholder="Minimal 10 karakter"></textarea>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Rollback',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc3545',
                        focusConfirm: false,
                        preConfirm: () => {
                            const confirmationCode = document
                                .getElementById('rollbackConfirmationCode')
                                ?.value
                                ?.trim();

                            const reason = document
                                .getElementById('rollbackReason')
                                ?.value
                                ?.trim();

                            if (confirmationCode !== batchCode) {
                                Swal.showValidationMessage(
                                    'Kode batch tidak sama.'
                                );
                                return false;
                            }

                            if (!reason || reason.length < 10) {
                                Swal.showValidationMessage(
                                    'Alasan rollback minimal 10 karakter.'
                                );
                                return false;
                            }

                            return {
                                confirmation_code: confirmationCode,
                                reason: reason
                            };
                        }
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    const response = await fetch(
                        routes.rollback.replace(
                            '__BATCH__',
                            batchId
                        ), {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(
                                result.value
                            )
                        }
                    );

                    const json = await response.json();

                    if (!response.ok) {
                        const validationMessage = json?.errors ?
                            Object.values(json.errors).flat()[0] :
                            null;

                        throw new Error(
                            validationMessage ||
                            json?.message ||
                            'Rollback batch gagal.'
                        );
                    }

                    if (closeDetail) {
                        detailModal.hide();
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Rollback Berhasil',
                        text: json.message
                    });

                    reloadAudit();
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Rollback Gagal',
                        text: error.message
                    });
                }
            }

            async function cancelBatch(batchId, batchCode, closeDetail = false) {
                if (!batchId) {
                    return;
                }

                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Batalkan Batch?',
                    html: `Batch <strong>${escapeHtml(batchCode ?? '')}</strong> akan ditandai cancelled.<br>` +
                        '<small>Item dan snapshot tetap disimpan untuk audit.</small>',
                    input: 'textarea',
                    inputLabel: 'Alasan pembatalan (opsional)',
                    inputPlaceholder: 'Tuliskan alasan pembatalan...',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Kembali',
                    confirmButtonColor: '#dc3545'
                });

                if (!result.isConfirmed) {
                    return;
                }

                try {
                    const response = await fetch(
                        routes.cancel.replace('__BATCH__', batchId), {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                reason: result.value ?? ''
                            })
                        }
                    );

                    const json = await response.json();

                    if (!response.ok) {
                        throw new Error(json?.message || 'Batch gagal dibatalkan.');
                    }

                    if (closeDetail) {
                        detailModal.hide();
                    }

                    await Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: json.message
                    });

                    reloadAudit();
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: error.message
                    });
                }
            }

            $('#batchItemsTable tbody').on('click', '.btn-detail-item', function() {
                const source = safeJsonParse($(this).attr('data-source'));
                const target = safeJsonParse($(this).attr('data-target'));
                const error = $(this).attr('data-error') || '';

                const errorHtml = error ?
                    `<div class="alert alert-danger text-start mt-3 mb-0">${escapeHtml(error)}</div>` :
                    '';

                Swal.fire({
                    title: 'Snapshot Item',
                    width: 900,
                    html: `
                        <div class="row g-3 text-start">
                            <div class="col-md-6">
                                <div class="fw-bold small mb-2 text-danger">Source Snapshot</div>
                                <pre class="audit-json-box mb-0">${escapeHtml(JSON.stringify(source, null, 4))}</pre>
                            </div>
                            <div class="col-md-6">
                                <div class="fw-bold small mb-2 text-success">Target Snapshot</div>
                                <pre class="audit-json-box mb-0">${escapeHtml(JSON.stringify(target, null, 4))}</pre>
                            </div>
                        </div>
                        ${errorHtml}
                    `,
                    confirmButtonText: 'Tutup'
                });
            });

            function safeJsonParse(value) {
                if (!value) {
                    return {};
                }

                if (typeof value === 'object') {
                    return value;
                }

                try {
                    return JSON.parse(value);
                } catch (error) {
                    return {
                        raw: value
                    };
                }
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            loadStatistics();
        });
    </script>
@endpush
