@extends('layouts.app')

@section('title', 'Tilawah Mandiri')

@section('content')
    <style>
        .tilawah-mandiri-page {
            --tm-primary: var(--islamic-purple-600, #6f42c1);
        }

        .tm-hero,
        .main-card,
        .tm-kpi {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .045);
        }

        .tm-hero {
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(255, 255, 255, .32), transparent 34%),
                linear-gradient(135deg, var(--tm-primary), #4f2d87);
        }

        .tm-hero-icon,
        .tm-kpi-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
        }

        .tm-hero-icon {
            width: 54px;
            height: 54px;
            flex: 0 0 54px;
            background: rgba(255, 255, 255, .16);
            font-size: 1.5rem;
        }

        .tm-kpi {
            height: 100%;
            background: var(--cui-body-bg);
            transition: transform .2s ease;
        }

        .tm-kpi:hover {
            transform: translateY(-3px);
        }

        .tm-kpi-icon {
            width: 44px;
            height: 44px;
            font-size: 1.2rem;
        }

        .tm-kpi-label {
            color: var(--cui-secondary-color);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .tm-kpi-value {
            margin-top: .25rem;
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
        }

        .main-card {
            overflow: hidden;
        }

        .card-header-purple {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, .05);
        }

        #filterProgressGroup .nav-link {
            margin-right: 8px;
            padding: .4rem 1rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 50rem;
            background-color: var(--cui-tertiary-bg);
            color: var(--cui-secondary-color);
            font-size: .85rem;
            font-weight: 600;
            transition: all .25s ease;
        }

        #filterProgressGroup .nav-link.active {
            border-color: var(--tm-primary) !important;
            background-color: var(--tm-primary) !important;
            box-shadow: 0 4px 10px rgba(111, 66, 193, .2);
            color: #fff !important;
        }

        #tilawah-mandiri-table thead th {
            border-top: 0;
            color: var(--cui-secondary-color);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        #tilawah-mandiri-table td {
            vertical-align: middle;
        }

        .tm-progress-track {
            width: 170px;
            max-width: 100%;
            height: 7px;
            overflow: hidden;
            border-radius: 50rem;
            background: var(--cui-tertiary-bg);
        }

        .tm-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #20c997, var(--tm-primary));
        }

        .tm-history-panel {
            margin: .35rem 0 1rem;
            padding: 1rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 16px;
            background: var(--cui-tertiary-bg);
        }

        .tm-history-table {
            min-width: 720px;
        }

        .tm-history-table th {
            color: var(--cui-secondary-color);
            font-size: .7rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .tm-juz-grid {
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: .45rem;
        }

        .tm-juz-chip {
            display: flex;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--cui-border-color);
            border-radius: 10px;
            background: var(--cui-tertiary-bg);
            color: var(--cui-secondary-color);
            font-size: .75rem;
            font-weight: 700;
        }

        .tm-juz-chip.completed {
            border-color: rgba(25, 135, 84, .25);
            background: rgba(25, 135, 84, .12);
            color: var(--cui-success);
        }

        .tm-action-group {
            display: flex;
            justify-content: flex-end;
            gap: .4rem;
            white-space: nowrap;
        }

        .tm-action-group .btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 10px;
        }

        .tm-chevron {
            transition: transform .2s ease;
        }

        .btn-toggle-history[aria-expanded="true"] .tm-chevron {
            transform: rotate(180deg);
        }

        .modal-content.tm-modal {
            overflow: hidden;
            border: 0;
            border-radius: 24px;
        }

        @media (max-width: 767.98px) {
            .tm-hero .btn {
                width: 100%;
            }

            .tm-juz-grid {
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }

            #modalTilawahMandiriPage,
            #modalProgressMandiri,
            #modalDetailMandiri {
                padding: 0 !important;
            }

            #modalTilawahMandiriPage .modal-dialog,
            #modalProgressMandiri .modal-dialog,
            #modalDetailMandiri .modal-dialog {
                width: 100%;
                max-width: none;
                height: 100dvh;
                margin: 0;
            }

            #modalTilawahMandiriPage .modal-content,
            #modalProgressMandiri .modal-content,
            #modalDetailMandiri .modal-content {
                height: 100dvh;
                border-radius: 0;
            }

            #modalTilawahMandiriPage form {
                display: flex;
                min-height: 100%;
                flex-direction: column;
            }

            #modalTilawahMandiriPage .modal-body,
            #modalProgressMandiri .modal-body,
            #modalDetailMandiri .modal-body {
                flex: 1 1 auto;
                overflow-y: auto;
            }
        }
    </style>

    <div class="tilawah-mandiri-page">
        <div class="tm-hero p-4 p-lg-5 mb-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                <div class="d-flex align-items-start gap-3">
                    <span class="tm-hero-icon">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </span>
                    <div>
                        <h2 class="fw-bold mb-2">Tilawah Mandiri</h2>
                        <p class="mb-2 opacity-75">Catat bacaan per Juz dan pantau progres mandiri setiap santri.</p>
                        <span class="badge rounded-pill bg-white bg-opacity-25 px-3 py-2">
                            <i class="bi bi-calendar-check me-1"></i>
                            {{ $semesterLabel ?: 'Belum ada semester aktif' }}
                        </span>
                    </div>
                </div>
                <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-bold shadow-sm"
                    id="btnCreateTilawahMandiri" @disabled(!$inputOpen)
                    title="{{ $inputOpen ? 'Catat Tilawah Mandiri' : $inputMessage }}">
                    <i class="bi bi-plus-circle-fill me-2"></i>Catat Tilawah Mandiri
                </button>
            </div>
        </div>

        @unless($inputOpen)
            <div class="alert alert-warning border-0 rounded-4 shadow-sm mb-4">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-lock-fill fs-4"></i>
                    <div>
                        <div class="fw-bold">Pencatatan sedang ditutup</div>
                        <div class="small">{{ $inputMessage }}</div>
                    </div>
                </div>
            </div>
        @endunless

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="tm-kpi p-3 p-md-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="tm-kpi-label">Santri Binaan</div>
                            <div class="tm-kpi-value" id="statTotalSantri">{{ $statistics['total_santri'] }}</div>
                        </div>
                        <span class="tm-kpi-icon bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="tm-kpi p-3 p-md-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="tm-kpi-label">Sudah Memulai</div>
                            <div class="tm-kpi-value text-info" id="statStartedSantri">{{ $statistics['started_santri'] }}</div>
                        </div>
                        <span class="tm-kpi-icon bg-info-subtle text-info"><i class="bi bi-play-circle-fill"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="tm-kpi p-3 p-md-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="tm-kpi-label">Total Sesi</div>
                            <div class="tm-kpi-value text-success" id="statTotalSessions">{{ $statistics['total_sessions'] }}</div>
                        </div>
                        <span class="tm-kpi-icon bg-success-subtle text-success"><i class="bi bi-journal-check"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="tm-kpi p-3 p-md-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="tm-kpi-label">Khatam 30 Juz</div>
                            <div class="tm-kpi-value text-warning" id="statCompletedSantri">{{ $statistics['completed_santri'] }}</div>
                        </div>
                        <span class="tm-kpi-icon bg-warning-subtle text-warning"><i class="bi bi-trophy-fill"></i></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Data Card --}}
        <div class="card main-card spotlight-card shadow-sm border-0">
            <div class="card-header card-header-purple bg-body-tertiary py-3 px-3 px-md-4 border-bottom-0">
                <div
                    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div class="w-100 w-md-auto overflow-auto">
                        <ul class="nav nav-pills nav-pills-sm flex-nowrap" id="filterProgressGroup" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active text-nowrap" type="button"
                                    data-progress-filter="">Semua</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link text-nowrap" type="button"
                                    data-progress-filter="not_started">Belum Mulai</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link text-nowrap" type="button"
                                    data-progress-filter="in_progress">Berprogres</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link text-nowrap" type="button"
                                    data-progress-filter="completed">Khatam</button>
                            </li>
                        </ul>
                    </div>

                    <div class="w-100 w-md-auto text-md-end">
                        <span
                            class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 d-block d-md-inline-block shadow-sm"
                            id="progressFilterBadge"
                            style="min-width: 150px; border: 1px solid var(--cui-border-color-translucent, rgba(111, 66, 193, 0.2));">
                            <i class="bi bi-info-circle me-1"></i> Menampilkan: Semua
                        </span>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="mb-3">
                    <h5 class="fw-bold mb-1">Detail Progress Santri</h5>
                    <p class="small text-muted mb-0">
                        Progres dihitung dari Juz unik berjenis Lanjut. Klik panah untuk membuka riwayat.
                    </p>
                </div>
                <div class="table-responsive">
                    <table id="tilawah-mandiri-table"
                        class="table table-hover align-middle w-100 text-nowrap mb-0">
                        <thead class="bg-body-tertiary">
                            <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                                <th class="border-top-0 ps-3">No.</th>
                                <th class="border-top-0">Santri</th>
                                <th class="border-top-0">Kelas</th>
                                <th class="border-top-0">Progres Mandiri</th>
                                <th class="border-top-0">Bacaan Terakhir</th>
                                <th class="border-top-0">Total Sesi</th>
                                <th class="text-end pe-4 border-top-0">Aksi</th>
                                <th class="border-top-0">Status Filter</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalTilawahMandiriPage" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content tm-modal shadow-lg">
                <form id="formTilawahMandiriPage" novalidate>
                    @csrf
                    <input type="hidden" name="submission_uuid" id="tmSubmissionUuid">
                    <div class="modal-header border-0 bg-primary bg-opacity-10 px-4">
                        <h5 class="modal-title fw-bold text-white" id="tmFormTitle">
                            <i class="bi bi-plus-circle-fill me-2"></i>Catat Tilawah Mandiri
                        </h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-4 small">
                            Satu pilihan Juz dianggap telah dibaca penuh. <b>Lanjut</b> menambah progres Juz unik,
                            sedangkan <b>Murojaah</b> hanya masuk riwayat.
                        </div>
                        <div id="tmFormLoading" class="text-center py-5">
                            <div class="spinner-border text-primary mb-3"></div>
                            <div class="small text-muted">Memuat pilihan pencatatan...</div>
                        </div>
                        <div id="tmFormContent" class="d-none">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold">SANTRI</label>
                                    <select class="form-select bg-body-tertiary border-0" name="santri_id"
                                        id="tmSantri" required></select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small fw-bold">TANGGAL</label>
                                    <input type="date" class="form-control bg-body-tertiary border-0"
                                        name="tanggal" id="tmTanggal" required>
                                    <small class="text-muted" id="tmSemesterLabel"></small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">TUJUAN BACAAN</label>
                                    <select class="form-select bg-body-tertiary border-0"
                                        name="reading_purpose" id="tmPurpose" required>
                                        <option value="continuation">Lanjut — menambah progres</option>
                                        <option value="review">Murojaah — hanya riwayat</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">JUZ YANG DIBACA</label>
                                    <select class="form-select bg-body-tertiary border-0"
                                        name="juz" id="tmJuz" required></select>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-light border rounded-4 mb-0" id="tmPreview">
                                        Pilih santri dan Juz yang telah selesai dibaca.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">CATATAN (OPSIONAL)</label>
                                    <textarea class="form-control bg-body-tertiary border-0" rows="3"
                                        name="catatan" id="tmNote"
                                        placeholder="Catatan singkat sesi Tilawah Mandiri..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"
                            id="tmSubmitButton" disabled>Simpan Tilawah Mandiri</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalProgressMandiri" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content tm-modal shadow-lg">
                <div class="modal-header border-0 bg-success bg-opacity-10 px-4">
                    <h5 class="modal-title fw-bold text-white">
                        <i class="bi bi-bar-chart-fill me-2"></i>Detail Progress
                    </h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                        <div>
                            <h4 class="fw-bold mb-1" id="tmProgressName">-</h4>
                            <div class="text-muted" id="tmProgressMeta">-</div>
                        </div>
                        <div class="text-md-end">
                            <div class="display-6 fw-bold text-success" id="tmProgressCount">0/30</div>
                            <div class="small text-muted">Juz unik selesai</div>
                        </div>
                    </div>
                    <div class="progress mb-4" style="height: 10px;">
                        <div class="progress-bar bg-success" id="tmProgressBar" style="width: 0%"></div>
                    </div>
                    <h6 class="fw-bold mb-3">Peta Progres Juz</h6>
                    <div class="tm-juz-grid" id="tmProgressGrid"></div>
                    <div class="alert alert-light border rounded-4 small mt-4 mb-0">
                        Juz hijau berasal dari pencatatan <b>Lanjut</b>. Murojaah tidak mengubah peta progres.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDetailMandiri" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content tm-modal shadow-lg">
                <div class="modal-header border-0 bg-info bg-opacity-10 px-4">
                    <h5 class="modal-title fw-bold text-info-emphasis">
                        <i class="bi bi-eye-fill me-2"></i>Detail Tilawah Mandiri
                    </h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center rounded-4 bg-body-tertiary p-4 mb-4">
                        <div class="small text-muted mb-1">Juz yang dibaca</div>
                        <div class="display-6 fw-bold text-primary" id="tmDetailJuz">-</div>
                        <span class="badge rounded-pill mt-2" id="tmDetailPurpose">-</span>
                    </div>
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Santri</dt>
                        <dd class="col-7 fw-semibold" id="tmDetailSantri">-</dd>
                        <dt class="col-5 text-muted">Tanggal</dt>
                        <dd class="col-7" id="tmDetailTanggal">-</dd>
                        <dt class="col-5 text-muted">Semester</dt>
                        <dd class="col-7" id="tmDetailSemester">-</dd>
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7"><span class="badge bg-success-subtle text-success">Hadir</span></dd>
                        <dt class="col-5 text-muted">Catatan</dt>
                        <dd class="col-7 text-break" id="tmDetailNote">-</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const INPUT_OPEN = @json($inputOpen);
            const INPUT_MESSAGE = @json($inputMessage ?? 'Input Tilawah Mandiri sedang ditutup.');
            const routes = {
                data: @json(route('musyrif.tilawah-mandiri.data')),
                options: @json(route('musyrif.tilawah-mandiri.options')),
                store: @json(route('musyrif.tilawah-mandiri.store')),
                historyBase: @json(url('musyrif/tilawah-mandiri/history')),
                recordBase: @json(url('musyrif/tilawah-mandiri'))
            };
            const csrfToken = @json(csrf_token());
            const modalForm = new coreui.Modal(document.getElementById('modalTilawahMandiriPage'));
            const modalProgress = new coreui.Modal(document.getElementById('modalProgressMandiri'));
            const modalDetail = new coreui.Modal(document.getElementById('modalDetailMandiri'));
            let editingId = null;
            let submitting = false;
            let historyCache = {};

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function updateStatistics(statistics) {
                statistics = statistics || {};
                $('#statTotalSantri').text(statistics.total_santri ?? 0);
                $('#statStartedSantri').text(statistics.started_santri ?? 0);
                $('#statTotalSessions').text(statistics.total_sessions ?? 0);
                $('#statCompletedSantri').text(statistics.completed_santri ?? 0);
            }

            const table = $('#tilawah-mandiri-table').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: routes.data,
                    dataSrc: function(response) {
                        updateStatistics(response.statistics);
                        return response.data || [];
                    }
                },
                pageLength: 25,
                autoWidth: false,
                order: [[1, 'asc']],
                columns: [
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'ps-3',
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (type !== 'display') return row.nama;
                            return '<div class="fw-bold">' + escapeHtml(row.nama) + '</div>'
                                + '<small class="text-muted">'
                                + escapeHtml(row.nis || 'NIS belum tersedia') + '</small>';
                        }
                    },
                    {
                        data: 'kelas',
                        render: function(data) {
                            return '<span class="badge bg-secondary-subtle text-secondary rounded-pill">'
                                + escapeHtml(data) + '</span>';
                        }
                    },
                    {
                        data: 'completed_count',
                        render: function(data, type, row) {
                            if (type !== 'display') return Number(data);
                            return '<div class="d-flex align-items-center gap-2 mb-2">'
                                + '<span class="fw-bold text-success">' + data + '/30 Juz</span>'
                                + '<small class="text-muted">' + row.progress_percentage + '%</small></div>'
                                + '<div class="tm-progress-track"><div class="tm-progress-bar" style="width:'
                                + row.progress_percentage + '%"></div></div>'
                                + '<small class="text-muted d-block mt-1">' + row.remaining_count
                                + ' Juz tersisa</small>';
                        }
                    },
                    {
                        data: 'last_entry',
                        render: function(data, type) {
                            if (!data) {
                                return type === 'display'
                                    ? '<span class="text-muted">Belum ada riwayat</span>'
                                    : '';
                            }

                            if (type !== 'display') return data.tanggal || '';

                            const purposeClass = data.reading_purpose === 'review'
                                ? 'bg-info-subtle text-info'
                                : 'bg-success-subtle text-success';

                            return '<div class="fw-bold">' + escapeHtml(data.juz_label) + '</div>'
                                + '<span class="badge ' + purposeClass + ' rounded-pill">'
                                + escapeHtml(data.reading_purpose_label) + '</span>'
                                + '<small class="text-muted d-block mt-1">'
                                + escapeHtml(data.tanggal_label) + '</small>';
                        }
                    },
                    {
                        data: 'total_sessions',
                        render: function(data, type, row) {
                            if (type !== 'display') return Number(data);
                            return '<div class="fw-bold">' + data + ' sesi</div>'
                                + '<small class="text-muted">' + row.continuation_sessions
                                + ' lanjut · ' + row.review_sessions + ' murojaah</small>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4',
                        render: function(data, type, row) {
                            const disabled = INPUT_OPEN ? '' : ' disabled';
                            return '<div class="tm-action-group">'
                                + '<button type="button" class="btn btn-outline-info btn-progress-detail" '
                                + 'data-id="' + row.id + '" title="Detail Progress"><i class="bi bi-eye-fill"></i></button>'
                                + '<button type="button" class="btn btn-primary btn-create-entry" '
                                + 'data-id="' + row.id + '" title="Catat Tilawah"' + disabled
                                + '><i class="bi bi-plus-lg"></i></button>'
                                + '<button type="button" class="btn btn-outline-secondary btn-toggle-history" '
                                + 'data-id="' + row.id + '" title="Tampilkan Riwayat" aria-expanded="false">'
                                + '<i class="bi bi-chevron-down tm-chevron"></i></button></div>';
                        }
                    },
                    {
                        data: 'progress_state',
                        visible: false,
                        searchable: true
                    }
                ]
            });

            const progressFilterLabels = {
                '': 'Semua',
                not_started: 'Belum Mulai',
                in_progress: 'Berprogres',
                completed: 'Khatam'
            };

            $('#filterProgressGroup [data-progress-filter]').on('click', function() {
                if ($(this).hasClass('active')) return;

                $('#filterProgressGroup [data-progress-filter]').removeClass('active');
                $(this).addClass('active');
                const value = $(this).data('progress-filter');
                $('#progressFilterBadge').html(
                    '<i class="bi bi-info-circle me-1"></i> Menampilkan: '
                    + progressFilterLabels[value]
                );
                table.column(7).search(value ? '^' + value + '$' : '', true, false).draw();
            });

            function openForm(record, santriId) {
                record = record || null;
                santriId = santriId || null;

                if (!INPUT_OPEN) {
                    Swal.fire('Pencatatan Ditutup', INPUT_MESSAGE, 'info');
                    return;
                }

                editingId = record ? record.id : null;
                submitting = false;
                $('#formTilawahMandiriPage')[0].reset();
                $('#formTilawahMandiriPage .is-invalid').removeClass('is-invalid');
                $('#tmFormLoading').removeClass('d-none');
                $('#tmFormContent').addClass('d-none');
                $('#tmSubmitButton').prop('disabled', true).text(
                    editingId ? 'Perbarui Tilawah Mandiri' : 'Simpan Tilawah Mandiri'
                );
                $('#tmFormTitle').html(
                    editingId
                        ? '<i class="bi bi-pencil-square me-2"></i>Edit Tilawah Mandiri'
                        : '<i class="bi bi-plus-circle-fill me-2"></i>Catat Tilawah Mandiri'
                );
                modalForm.show();

                $.get(routes.options)
                    .done(function(response) {
                        hydrateForm(response, record, santriId);
                    })
                    .fail(function(xhr) {
                        modalForm.hide();
                        Swal.fire(
                            'Gagal Memuat Form',
                            xhr.responseJSON?.message || 'Pilihan Tilawah Mandiri tidak dapat dimuat.',
                            'error'
                        );
                    });
            }

            function hydrateForm(response, record, santriId) {
                const santriSelect = $('#tmSantri')
                    .empty()
                    .append('<option value="">-- Pilih Santri --</option>');
                const juzSelect = $('#tmJuz')
                    .empty()
                    .append('<option value="">-- Pilih Juz --</option>');

                (response.data_santri || []).forEach(function(item) {
                    santriSelect.append($('<option>', {
                        value: item.id,
                        text: item.nama
                    }));
                });

                (response.juz_options || []).forEach(function(item) {
                    juzSelect.append($('<option>', {
                        value: item.value,
                        text: item.label
                    }));
                });

                $('#tmSubmissionUuid').val(response.submission_uuid || '');
                $('#tmTanggal')
                    .attr('min', response.semester?.minimum_date || '')
                    .attr('max', response.semester?.maximum_date || '')
                    .val(record?.tanggal || response.semester?.maximum_date || '');
                $('#tmSemesterLabel').text(
                    response.semester?.label
                        ? 'Semester aktif: ' + response.semester.label
                        : ''
                );
                $('#tmSantri').val(String(record?.santri_id || santriId || ''));
                $('#tmPurpose').val(record?.reading_purpose || 'continuation');
                $('#tmJuz').val(record?.juz ? String(record.juz) : '');
                $('#tmNote').val(record?.note || '');
                $('#tmFormLoading').addClass('d-none');
                $('#tmFormContent').removeClass('d-none');
                updateFormPreview();
            }

            function updateFormPreview() {
                const santri = $('#tmSantri option:selected').text();
                const juz = $('#tmJuz').val();
                const purpose = $('#tmPurpose').val();
                const ready = Boolean(
                    $('#tmSantri').val() && $('#tmTanggal').val() && purpose && juz
                );
                const preview = $('#tmPreview');

                if (!ready) {
                    preview
                        .removeClass('alert-success alert-info')
                        .addClass('alert-light')
                        .text('Pilih santri dan Juz yang telah selesai dibaca.');
                } else {
                    const review = purpose === 'review';
                    preview
                        .removeClass('alert-light alert-success alert-info')
                        .addClass(review ? 'alert-info' : 'alert-success')
                        .html(
                            '<i class="bi bi-check-circle-fill me-2"></i><b>'
                            + escapeHtml(santri) + ' · Juz ' + escapeHtml(juz) + '</b> — '
                            + (review
                                ? 'dicatat sebagai Murojaah dan tidak menambah progres.'
                                : 'dicatat sebagai Lanjut dan menambah progres Juz unik.')
                        );
                }

                $('#tmSubmitButton').prop('disabled', !ready || submitting);
                return ready;
            }

            $('#tmSantri, #tmTanggal, #tmPurpose, #tmJuz')
                .on('change input', updateFormPreview);

            $('#btnCreateTilawahMandiri').on('click', function() {
                openForm();
            });

            $(document).on('click', '.btn-create-entry', function() {
                if (this.disabled) return;
                openForm(null, Number($(this).data('id')));
            });

            $('#formTilawahMandiriPage').on('submit', function(event) {
                event.preventDefault();

                if (!updateFormPreview() || submitting) return;

                submitting = true;
                const button = $('#tmSubmitButton')
                    .prop('disabled', true)
                    .html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');
                let url = routes.store;
                let data = $(this).serialize();

                if (editingId) {
                    url = routes.recordBase + '/' + editingId;
                    data += '&_method=PUT';
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        modalForm.hide();
                        historyCache = {};
                        table.ajax.reload(null, false);
                        if (window.AppAlert) AppAlert.success(response.message);
                        else Swal.fire('Berhasil!', response.message, 'success');
                    },
                    error: function(xhr) {
                        submitting = false;
                        button.text(
                            editingId ? 'Perbarui Tilawah Mandiri' : 'Simpan Tilawah Mandiri'
                        );
                        const response = xhr.responseJSON || {};

                        Object.keys(response.errors || {}).forEach(function(field) {
                            $('#formTilawahMandiriPage [name="' + field + '"]')
                                .addClass('is-invalid');
                        });

                        updateFormPreview();
                        Swal.fire(
                            'Gagal Menyimpan',
                            Object.values(response.errors || {}).flat()[0]
                                || response.message
                                || 'Data Tilawah Mandiri tidak dapat disimpan.',
                            xhr.status === 422 ? 'warning' : 'error'
                        );
                    }
                });
            });

            $(document).on('click', '.btn-progress-detail', function() {
                const row = table.row($(this).closest('tr')).data();
                const completed = new Set((row.completed_juz || []).map(Number));

                $('#tmProgressName').text(row.nama);
                $('#tmProgressMeta').text(
                    row.kelas + ' · ' + row.total_sessions + ' sesi Tilawah Mandiri'
                );
                $('#tmProgressCount').text(row.completed_count + '/30');
                $('#tmProgressBar').css('width', row.progress_percentage + '%');
                $('#tmProgressGrid').html(
                    Array.from({length: 30}, function(value, index) {
                        const juz = index + 1;
                        return '<span class="tm-juz-chip '
                            + (completed.has(juz) ? 'completed' : '') + '">'
                            + (completed.has(juz)
                                ? '<i class="bi bi-check-circle-fill me-1"></i>'
                                : '')
                            + 'Juz ' + juz + '</span>';
                    }).join('')
                );
                modalProgress.show();
            });

            $(document).on('click', '.btn-toggle-history', function() {
                const button = $(this);
                const row = table.row(button.closest('tr'));
                const data = row.data();

                if (row.child.isShown()) {
                    row.child.hide();
                    button.attr('aria-expanded', 'false');
                    return;
                }

                row.child(
                    '<div class="tm-history-panel" id="tm-history-' + data.id + '">'
                    + '<div class="text-center py-4"><span class="spinner-border spinner-border-sm '
                    + 'text-primary me-2"></span>Memuat riwayat '
                    + escapeHtml(data.nama) + '...</div></div>'
                ).show();
                button.attr('aria-expanded', 'true');

                if (historyCache[data.id]) {
                    renderHistory(data, historyCache[data.id]);
                    return;
                }

                $.get(routes.historyBase + '/' + data.id)
                    .done(function(response) {
                        historyCache[data.id] = response.data || [];
                        renderHistory(data, historyCache[data.id]);
                    })
                    .fail(function(xhr) {
                        $('#tm-history-' + data.id).html(
                            '<div class="alert alert-danger mb-0">'
                            + escapeHtml(
                                xhr.responseJSON?.message || 'Riwayat tidak dapat dimuat.'
                            ) + '</div>'
                        );
                    });
            });

            function renderHistory(santri, entries) {
                if (!entries.length) {
                    $('#tm-history-' + santri.id).html(
                        '<div class="text-center py-4 text-muted">'
                        + '<i class="bi bi-journal-x fs-3 d-block mb-2"></i>'
                        + 'Belum ada riwayat Tilawah Mandiri.</div>'
                    );
                    return;
                }

                entries.forEach(function(entry) {
                    entry.santri_id = santri.id;
                    entry.santri_nama = santri.nama;
                    historyCache['entry-' + entry.id] = entry;
                });

                const rows = entries.map(function(entry) {
                    const purposeClass = entry.reading_purpose === 'review'
                        ? 'bg-info-subtle text-info'
                        : 'bg-success-subtle text-success';
                    const editDelete = entry.editable
                        ? '<button type="button" class="btn btn-sm btn-primary btn-edit-entry" '
                            + 'data-entry-id="' + entry.id + '" title="Edit">'
                            + '<i class="bi bi-pencil-square"></i></button>'
                            + '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-entry" '
                            + 'data-entry-id="' + entry.id + '" title="Hapus">'
                            + '<i class="bi bi-trash"></i></button>'
                        : '';

                    return '<tr><td><div class="fw-semibold">' + escapeHtml(entry.tanggal_label)
                        + '</div><small class="text-muted">' + escapeHtml(entry.semester_label)
                        + '</small></td><td><span class="badge ' + purposeClass
                        + ' rounded-pill">' + escapeHtml(entry.reading_purpose_label)
                        + '</span></td><td><span class="fw-bold">' + escapeHtml(entry.juz_label)
                        + '</span></td><td class="text-wrap">' + escapeHtml(entry.note || '-')
                        + '</td><td><div class="tm-action-group">'
                        + '<button type="button" class="btn btn-sm btn-outline-info btn-detail-entry" '
                        + 'data-entry-id="' + entry.id + '" title="Detail">'
                        + '<i class="bi bi-eye-fill"></i></button>' + editDelete
                        + '</div></td></tr>';
                }).join('');

                $('#tm-history-' + santri.id).html(
                    '<div class="d-flex align-items-center justify-content-between mb-3">'
                    + '<div><div class="fw-bold">Riwayat ' + escapeHtml(santri.nama)
                    + '</div><small class="text-muted">' + entries.length
                    + ' sesi tercatat</small></div>'
                    + '<span class="badge bg-primary-subtle text-primary rounded-pill">'
                    + 'Terbaru lebih dahulu</span></div>'
                    + '<div class="table-responsive"><table class="table table-sm '
                    + 'align-middle tm-history-table mb-0"><thead><tr><th>Tanggal</th>'
                    + '<th>Tujuan</th><th>Juz</th><th>Catatan</th>'
                    + '<th class="text-end">Aksi</th></tr></thead><tbody>'
                    + rows + '</tbody></table></div>'
                );
            }

            $(document).on('click', '.btn-detail-entry', function() {
                const entry = historyCache['entry-' + $(this).data('entry-id')];
                if (!entry) return;

                $('#tmDetailJuz').text(entry.juz_label);
                $('#tmDetailPurpose')
                    .text(entry.reading_purpose_label)
                    .removeClass('bg-info-subtle text-info bg-success-subtle text-success')
                    .addClass(
                        entry.reading_purpose === 'review'
                            ? 'bg-info-subtle text-info'
                            : 'bg-success-subtle text-success'
                    );
                $('#tmDetailSantri').text(entry.santri_nama);
                $('#tmDetailTanggal').text(entry.tanggal_label);
                $('#tmDetailSemester').text(entry.semester_label);
                $('#tmDetailNote').text(entry.note || 'Tidak ada catatan.');
                modalDetail.show();
            });

            $(document).on('click', '.btn-edit-entry', function() {
                const entry = historyCache['entry-' + $(this).data('entry-id')];
                if (entry?.editable) openForm(entry);
            });

            $(document).on('click', '.btn-delete-entry', function() {
                const entry = historyCache['entry-' + $(this).data('entry-id')];
                if (!entry?.editable) return;

                Swal.fire({
                    title: 'Hapus Riwayat?',
                    text: entry.santri_nama + ' · ' + entry.juz_label
                        + ' · ' + entry.reading_purpose_label,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: routes.recordBase + '/' + entry.id,
                        type: 'POST',
                        data: {
                            _method: 'DELETE',
                            _token: csrfToken
                        },
                        success: function(response) {
                            historyCache = {};
                            table.ajax.reload(null, false);
                            if (window.AppAlert) AppAlert.success(response.message);
                            else Swal.fire('Terhapus!', response.message, 'success');
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Gagal Menghapus',
                                xhr.responseJSON?.message || 'Data tidak dapat dihapus.',
                                'error'
                            );
                        }
                    });
                });
            });
        });
    </script>
@endpush
