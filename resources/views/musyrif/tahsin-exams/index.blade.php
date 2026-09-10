@extends('layouts.app')

@section('title', 'Ujian Tahsin')

@section('content')
    <style>
        .tahsin-exam-page {
            --te-primary: var(--islamic-purple-600, #6f42c1);
        }

        .te-hero,
        .te-card,
        .te-kpi {
            border: 0;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, .045);
        }

        .te-hero {
            overflow: hidden;
            color: #fff;
            background: radial-gradient(circle at top right, rgba(255, 255, 255, .3), transparent 34%),
                linear-gradient(135deg, var(--te-primary), #4f2d87);
        }

        .te-icon {
            display: inline-flex;
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: rgba(255, 255, 255, .16);
            font-size: 1.35rem;
        }

        .te-kpi {
            height: 100%;
            background: var(--cui-body-bg);
        }

        .te-kpi-label {
            color: var(--cui-secondary-color);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .te-kpi-value {
            margin-top: .25rem;
            font-size: 1.7rem;
            font-weight: 800;
        }

        .te-card {
            overflow: hidden;
        }

        #examFilterGroup .nav-link {
            margin-right: 8px;
            padding: .4rem 1rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 50rem;
            background: var(--cui-tertiary-bg);
            color: var(--cui-secondary-color);
            font-size: .85rem;
            font-weight: 600;
        }

        #examFilterGroup .nav-link.active {
            border-color: var(--te-primary) !important;
            background: var(--te-primary) !important;
            color: #fff !important;
        }

        #tahsin-exam-table thead th {
            border-top: 0;
            color: var(--cui-secondary-color);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .te-progress-track {
            width: 165px;
            max-width: 100%;
            height: 7px;
            overflow: hidden;
            border-radius: 50rem;
            background: var(--cui-tertiary-bg);
        }

        .te-progress-bar {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #20c997, var(--te-primary));
        }

        .te-action-group {
            display: flex;
            justify-content: flex-end;
            gap: .4rem;
            white-space: nowrap;
        }

        .te-action-group .btn {
            display: inline-flex;
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 10px;
        }

        .te-history-panel {
            margin: .35rem 0 1rem;
            padding: 1rem;
            border: 1px solid var(--cui-border-color);
            border-radius: 16px;
            background: var(--cui-tertiary-bg);
        }

        .te-history-table {
            min-width: 820px;
        }

        .te-history-table th {
            color: var(--cui-secondary-color);
            font-size: .7rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .te-chevron {
            transition: transform .2s ease;
        }

        .btn-toggle-exam-history[aria-expanded="true"] .te-chevron {
            transform: rotate(180deg);
        }

        .modal-content.te-modal {
            overflow: hidden;
            border: 0;
            border-radius: 24px;
        }

        @media (max-width: 767.98px) {
            .te-hero .btn {
                width: 100%;
            }

            #modalTahsinExam,
            #modalTahsinExamDetail {
                padding: 0 !important;
            }

            #modalTahsinExam .modal-dialog,
            #modalTahsinExamDetail .modal-dialog {
                width: 100%;
                max-width: none;
                height: 100dvh;
                margin: 0;
            }

            #modalTahsinExam .modal-content,
            #modalTahsinExamDetail .modal-content {
                height: 100dvh;
                border-radius: 0;
            }
        }
    </style>

    <div class="container-fluid tahsin-exam-page py-3 py-md-4">
        <div class="card te-hero mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="te-icon"><i class="bi bi-clipboard2-check-fill"></i></span>
                        <div>
                            <h3 class="fw-bold mb-1">Ujian Tahsin</h3>
                            <p class="mb-1 opacity-75">Kelola ujian kenaikan buku dan evaluasi semester.</p>
                            <small class="opacity-75">
                                <i class="bi bi-calendar-check me-1"></i>
                                {{ $semesterLabel ?: 'Belum ada semester aktif' }}
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-light fw-semibold px-4" id="btnCreateTahsinExam"
                        @disabled(!$inputOpen) title="{{ $inputOpen ? 'Catat Ujian Tahsin' : $inputMessage }}">
                        <i class="bi bi-plus-circle-fill me-2"></i>Catat Ujian
                    </button>
                </div>
            </div>
        </div>

        @if (!$inputOpen)
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <i class="bi bi-lock-fill me-2"></i>{{ $inputMessage }}
            </div>
        @endif

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="te-kpi p-3 p-md-4">
                    <div class="te-kpi-label">Total Santri</div>
                    <div class="te-kpi-value" id="statExamTotalSantri">{{ $statistics['total_santri'] }}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="te-kpi p-3 p-md-4">
                    <div class="te-kpi-label">Sudah Ujian</div>
                    <div class="te-kpi-value text-info" id="statExaminedSantri">{{ $statistics['examined_santri'] }}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="te-kpi p-3 p-md-4">
                    <div class="te-kpi-label">Total Ujian</div>
                    <div class="te-kpi-value text-success" id="statTotalExams">{{ $statistics['total_exams'] }}</div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="te-kpi p-3 p-md-4">
                    <div class="te-kpi-label">Hasil Terakhir Lulus</div>
                    <div class="te-kpi-value text-warning" id="statLatestPassed">{{ $statistics['latest_passed'] }}</div>
                </div>
            </div>
        </div>

        <div class="card te-card spotlight-card shadow-sm border-0">
            <div class="card-header bg-body-tertiary py-3 px-3 px-md-4 border-bottom-0">
                <div
                    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div class="w-100 w-md-auto overflow-auto">
                        <ul class="nav nav-pills flex-nowrap" id="examFilterGroup">
                            <li class="nav-item"><button class="nav-link active text-nowrap" type="button"
                                    data-exam-filter="">Semua</button></li>
                            <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                    data-exam-filter="not_examined">Belum Ujian</button></li>
                            <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                    data-exam-filter="passed">Lulus</button></li>
                            <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                    data-exam-filter="repeat">Mengulang</button></li>
                        </ul>
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2" id="examFilterBadge">
                        <i class="bi bi-info-circle me-1"></i> Menampilkan: Semua
                    </span>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="mb-3">
                    <h5 class="fw-bold mb-1">Rekap Ujian Per Santri</h5>
                    <p class="small text-muted mb-0">Klik panah untuk melihat seluruh percobaan ujian.</p>
                </div>
                <div class="table-responsive">
                    <table id="tahsin-exam-table" class="table table-hover align-middle w-100 text-nowrap mb-0">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th class="ps-3">No.</th>
                                <th>Santri</th>
                                <th>Kelas</th>
                                <th>Progres Kenaikan</th>
                                <th>Ujian Terakhir</th>
                                <th>Total</th>
                                <th class="text-end pe-4">Aksi</th>
                                <th>Status Filter</th>
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
    <div class="modal fade" id="modalTahsinExam" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content te-modal shadow-lg">
                <form id="formTahsinExam" novalidate>
                    @csrf
                    <input type="hidden" name="submission_uuid" id="examSubmissionUuid">
                    <div class="modal-header border-0 bg-primary bg-opacity-10 px-4">
                        <h5 class="modal-title fw-bold text-white" id="examFormTitle">
                            <i class="bi bi-clipboard-data me-2"></i>Catat Ujian Tahsin
                        </h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div id="examFormLoading" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div id="examFormContent" class="d-none">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Santri</label>
                                    <select class="form-select" name="santri_id" id="examSantri" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Tanggal Ujian</label>
                                    <input type="date" class="form-control" name="tanggal" id="examTanggal" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Jenis Ujian</label>
                                    <select class="form-select" name="exam_type" id="examType" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Buku/Jilid yang Diuji</label>
                                    <select class="form-select" name="buku" id="examBook" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nilai</label>
                                    <select class="form-select" name="grade_label" id="examGrade" required></select>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-light border mb-0" id="examResultPreview">
                                        Pilih nilai untuk melihat hasil otomatis.
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Catatan <span
                                            class="text-muted fw-normal">(opsional)</span></label>
                                    <textarea class="form-control" name="catatan" id="examNote" rows="3" maxlength="2000"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4">
                        <button type="button" class="btn btn-light" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4" id="examSubmitButton">Simpan Ujian</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTahsinExamDetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content te-modal shadow-lg">
                <div class="modal-header border-0 bg-info bg-opacity-10 px-4">
                    <h5 class="modal-title fw-bold text-info">Detail Ujian Tahsin</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="examDetailContent"></div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const INPUT_OPEN = @json($inputOpen);
            const INPUT_MESSAGE = @json($inputMessage ?? 'Input Ujian Tahsin sedang ditutup.');
            const routes = {
                data: @json(route('musyrif.tahsin-exams.data')),
                options: @json(route('musyrif.tahsin-exams.options')),
                store: @json(route('musyrif.tahsin-exams.store')),
                historyBase: @json(url('musyrif/ujian-tahsin/history')),
                recordBase: @json(url('musyrif/ujian-tahsin'))
            };
            const csrfToken = @json(csrf_token());
            const formModal = new coreui.Modal(document.getElementById('modalTahsinExam'));
            const detailModal = new coreui.Modal(document.getElementById('modalTahsinExamDetail'));
            let editingId = null;
            let submitting = false;
            let historyCache = {};
            let optionsCache = null;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function updateStatistics(stats) {
                stats = stats || {};
                $('#statExamTotalSantri').text(stats.total_santri ?? 0);
                $('#statExaminedSantri').text(stats.examined_santri ?? 0);
                $('#statTotalExams').text(stats.total_exams ?? 0);
                $('#statLatestPassed').text(stats.latest_passed ?? 0);
            }

            const table = $('#tahsin-exam-table').DataTable({
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
                order: [
                    [1, 'asc']
                ],
                columns: [{
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'ps-3',
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            if (type !== 'display') return row.nama;
                            return '<div class="fw-bold">' + escapeHtml(row.nama) + '</div>' +
                                '<small class="text-muted">' + escapeHtml(row.nis ||
                                    'NIS belum tersedia') + '</small>';
                        }
                    },
                    {
                        data: 'kelas',
                        render: data =>
                            '<span class="badge bg-secondary-subtle text-secondary rounded-pill">' +
                            escapeHtml(data) + '</span>'
                    },
                    {
                        data: 'completed_count',
                        render: function(data, type, row) {
                            if (type !== 'display') return Number(data);
                            return '<div class="d-flex align-items-center gap-2 mb-2"><span class="fw-bold text-success">' +
                                data + '/6 Buku</span><small class="text-muted">' + row
                                .progress_percentage +
                                '%</small></div><div class="te-progress-track"><div class="te-progress-bar" style="width:' +
                                row.progress_percentage +
                                '%"></div></div><small class="text-muted d-block mt-1">Rekomendasi: ' +
                                escapeHtml(row.recommended_book_label) + '</small>';
                        }
                    },
                    {
                        data: 'latest_exam',
                        render: function(data, type) {
                            if (!data) return type === 'display' ?
                                '<span class="text-muted">Belum ujian</span>' : '';
                            if (type !== 'display') return data.tanggal || '';
                            const badge = data.result === 'passed' ?
                                'bg-success-subtle text-success' :
                                'bg-danger-subtle text-danger';
                            return '<div class="fw-bold">' + escapeHtml(data.buku_label) + ' · ' +
                                escapeHtml(data.grade_text) +
                                '</div><span class="badge ' + badge + ' rounded-pill">' +
                                escapeHtml(data.result_label) +
                                '</span><small class="text-muted d-block mt-1">' + escapeHtml(data
                                    .tanggal_label) + '</small>';
                        }
                    },
                    {
                        data: 'total_exams',
                        render: function(data, type, row) {
                            if (type !== 'display') return Number(data);
                            return '<div class="fw-bold">' + data +
                                ' ujian</div><small class="text-muted">' +
                                row.promotion_exams + ' kenaikan · ' + row.semester_exams +
                                ' semester</small>';
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4',
                        render: function(data, type, row) {
                            return '<div class="te-action-group"><button type="button" class="btn btn-primary btn-create-exam" ' +
                                'data-id="' + row.id + '" title="Catat Ujian"' + (INPUT_OPEN ? '' :
                                    ' disabled') +
                                '><i class="bi bi-plus-lg"></i></button><button type="button" ' +
                                'class="btn btn-outline-secondary btn-toggle-exam-history" data-id="' +
                                row.id +
                                '" title="Riwayat" aria-expanded="false"><i class="bi bi-chevron-down te-chevron"></i></button></div>';
                        }
                    },
                    {
                        data: 'exam_state',
                        visible: false,
                        searchable: true
                    }
                ]
            });

            const filterLabels = {
                '': 'Semua',
                not_examined: 'Belum Ujian',
                passed: 'Lulus',
                repeat: 'Mengulang'
            };

            $('#examFilterGroup [data-exam-filter]').on('click', function() {
                if ($(this).hasClass('active')) return;
                $('#examFilterGroup [data-exam-filter]').removeClass('active');
                $(this).addClass('active');
                const value = $(this).data('exam-filter');
                $('#examFilterBadge').html('<i class="bi bi-info-circle me-1"></i> Menampilkan: ' +
                    filterLabels[value]);
                table.column(7).search(value ? '^' + value + '$' : '', true, false).draw();
            });

            function fillSelect(selector, items, placeholder) {
                const element = $(selector);
                element.empty().append('<option value="">' + placeholder + '</option>');
                (items || []).forEach(item => element.append(
                    '<option value="' + escapeHtml(item.value ?? item.id) + '">' + escapeHtml(item.label ??
                        item.nama) + '</option>'
                ));
            }

            function updateResultPreview() {
                const grade = $('#examGrade').val();
                const selected = optionsCache?.grades?.find(item => item.value === grade);
                if (!selected) {
                    $('#examResultPreview').removeClass('alert-success alert-danger').addClass('alert-light')
                        .text('Pilih nilai untuk melihat hasil otomatis.');
                    return;
                }
                const passed = selected.result === 'passed';
                $('#examResultPreview').removeClass('alert-light alert-success alert-danger')
                    .addClass(passed ? 'alert-success' : 'alert-danger')
                    .html('<strong>Hasil otomatis: ' + (passed ? 'Lulus' : 'Mengulang') + '</strong>');
            }

            function openForm(record, santriId) {
                if (!INPUT_OPEN) {
                    Swal.fire('Pencatatan Ditutup', INPUT_MESSAGE, 'info');
                    return;
                }
                editingId = record?.id || null;
                $('#formTahsinExam')[0].reset();
                $('#examFormLoading').removeClass('d-none');
                $('#examFormContent').addClass('d-none');
                $('#examSubmitButton').prop('disabled', true).text(editingId ? 'Perbarui Ujian' : 'Simpan Ujian');
                $('#examFormTitle').text(editingId ? 'Edit Ujian Tahsin' : 'Catat Ujian Tahsin');
                formModal.show();

                $.get(routes.options).done(function(response) {
                    optionsCache = response;
                    fillSelect('#examSantri', response.data_santri, 'Pilih santri');
                    fillSelect('#examType', response.exam_types, 'Pilih jenis ujian');
                    fillSelect('#examBook', response.books, 'Pilih buku/jilid');
                    fillSelect('#examGrade', response.grades, 'Pilih nilai');
                    $('#examTanggal').attr({
                        min: response.semester.minimum_date,
                        max: response.semester.maximum_date
                    });

                    if (record) {
                        $('#examSantri').val(santriId).prop('disabled', true);
                        $('#examTanggal').val(record.tanggal);
                        $('#examType').val(record.exam_type).prop('disabled', true);
                        $('#examBook').val(record.buku).prop('disabled', true);
                        $('#examGrade').val(record.grade_label);
                        $('#examNote').val(record.catatan || '');
                        $('#examSubmissionUuid').val(record.submission_uuid || '');
                    } else {
                        $('#examSantri').val(santriId || '').prop('disabled', false);
                        $('#examType, #examBook').prop('disabled', false);
                        $('#examTanggal').val(response.semester.maximum_date);
                        $('#examSubmissionUuid').val(response.submission_uuid);
                    }
                    updateResultPreview();
                    $('#examFormLoading').addClass('d-none');
                    $('#examFormContent').removeClass('d-none');
                    $('#examSubmitButton').prop('disabled', false);
                }).fail(function(xhr) {
                    formModal.hide();
                    Swal.fire('Tidak Dapat Membuka Form', xhr.responseJSON?.message || INPUT_MESSAGE,
                        'error');
                });
            }

            $('#examGrade').on('change', updateResultPreview);
            $('#btnCreateTahsinExam').on('click', () => openForm(null, null));
            $('#tahsin-exam-table').on('click', '.btn-create-exam', function() {
                openForm(null, Number($(this).data('id')));
            });

            $('#formTahsinExam').on('submit', function(event) {
                event.preventDefault();
                if (submitting) return;
                submitting = true;
                $('#examSubmitButton').prop('disabled', true);

                $.ajax({
                    url: editingId ? routes.recordBase + '/' + editingId : routes.store,
                    method: editingId ? 'PUT' : 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                }).done(function(response) {
                    formModal.hide();
                    historyCache = {};
                    table.ajax.reload(null, false);
                    Swal.fire('Berhasil', response.message, 'success');
                }).fail(function(xhr) {
                    const errors = xhr.responseJSON?.errors || {};
                    const message = Object.values(errors).flat()[0] || xhr.responseJSON?.message ||
                        'Ujian gagal disimpan.';
                    Swal.fire('Gagal', message, 'error');
                }).always(function() {
                    submitting = false;
                    $('#examSubmitButton').prop('disabled', false);
                });
            });

            function historyHtml(santriId, response) {
                const rows = response.data || [];
                if (!rows.length)
                    return '<div class="te-history-panel text-center text-muted">Belum ada riwayat ujian.</div>';
                let html = '<div class="te-history-panel"><div class="fw-bold mb-3">Riwayat ' + escapeHtml(response
                        .santri.nama) +
                    '</div><div class="table-responsive"><table class="table table-sm te-history-table align-middle mb-0"><thead><tr>' +
                    '<th>Tanggal</th><th>Jenis</th><th>Buku</th><th>Percobaan</th><th>Nilai</th><th>Hasil</th><th class="text-end">Aksi</th>' +
                    '</tr></thead><tbody>';
                rows.forEach(record => {
                    const badge = record.result === 'passed' ? 'bg-success-subtle text-success' :
                        'bg-danger-subtle text-danger';
                    html += '<tr><td>' + escapeHtml(record.tanggal_label) + '</td><td>' + escapeHtml(record
                            .exam_type_label) +
                        '</td><td>' + escapeHtml(record.buku_label) + '</td><td>#' + record.attempt_number +
                        '</td><td>' + escapeHtml(record.grade_text) + '</td><td><span class="badge ' +
                        badge + '">' +
                        escapeHtml(record.result_label) + '</span></td><td><div class="te-action-group">' +
                        '<button class="btn btn-outline-info btn-exam-detail" data-santri="' + santriId +
                        '" data-id="' + record.id +
                        '" title="Detail"><i class="bi bi-eye-fill"></i></button>' +
                        '<button class="btn btn-outline-warning btn-exam-edit" data-santri="' + santriId +
                        '" data-id="' + record.id +
                        '" title="Edit"' + (record.editable ? '' : ' disabled') +
                        '><i class="bi bi-pencil-fill"></i></button>' +
                        '<button class="btn btn-outline-danger btn-exam-delete" data-santri="' + santriId +
                        '" data-id="' + record.id +
                        '" title="Hapus"' + (record.editable ? '' : ' disabled') +
                        '><i class="bi bi-trash-fill"></i></button>' +
                        '</div></td></tr>';
                });
                return html + '</tbody></table></div></div>';
            }

            function getRecord(santriId, recordId) {
                return (historyCache[santriId]?.data || []).find(item => Number(item.id) === Number(recordId));
            }

            $('#tahsin-exam-table').on('click', '.btn-toggle-exam-history', function() {
                const button = $(this);
                const tr = button.closest('tr');
                const row = table.row(tr);
                const santriId = Number(button.data('id'));
                if (row.child.isShown()) {
                    row.child.hide();
                    button.attr('aria-expanded', 'false');
                    return;
                }
                button.attr('aria-expanded', 'true');
                if (historyCache[santriId]) {
                    row.child(historyHtml(santriId, historyCache[santriId])).show();
                    return;
                }
                row.child(
                    '<div class="te-history-panel text-center"><span class="spinner-border spinner-border-sm"></span></div>'
                ).show();
                $.get(routes.historyBase + '/' + santriId).done(function(response) {
                    historyCache[santriId] = response;
                    row.child(historyHtml(santriId, response)).show();
                }).fail(() => row.child(
                    '<div class="te-history-panel text-danger">Riwayat gagal dimuat.</div>').show());
            });

            $('#tahsin-exam-table').on('click', '.btn-exam-detail', function() {
                const record = getRecord($(this).data('santri'), $(this).data('id'));
                if (!record) return;
                const nextBook = record.next_book_label ?
                    '<div><small class="text-muted">Rekomendasi Berikutnya</small><div class="fw-bold">' +
                    escapeHtml(record.next_book_label) + '</div></div>' : '';
                $('#examDetailContent').html(
                    '<div class="vstack gap-3"><div><small class="text-muted">Jenis Ujian</small><div class="fw-bold">' +
                    escapeHtml(record.exam_type_label) +
                    '</div></div><div class="row g-3"><div class="col-6"><small class="text-muted">Buku/Jilid</small><div class="fw-bold">' +
                    escapeHtml(record.buku_label) +
                    '</div></div><div class="col-6"><small class="text-muted">Percobaan</small><div class="fw-bold">#' +
                    record.attempt_number +
                    '</div></div><div class="col-6"><small class="text-muted">Nilai</small><div class="fw-bold">' +
                    escapeHtml(record.grade_text) +
                    '</div></div><div class="col-6"><small class="text-muted">Hasil</small><div class="fw-bold">' +
                    escapeHtml(record.result_label) + '</div></div></div>' + nextBook +
                    '<div><small class="text-muted">Catatan</small><div>' +
                    escapeHtml(record.catatan || '-') + '</div></div><small class="text-muted">' +
                    escapeHtml(record.semester_label) + ' · ' +
                    escapeHtml(record.tanggal_label) + '</small></div>');
                detailModal.show();
            });

            $('#tahsin-exam-table').on('click', '.btn-exam-edit', function() {
                const santriId = Number($(this).data('santri'));
                const record = getRecord(santriId, $(this).data('id'));
                if (record) openForm(record, santriId);
            });

            $('#tahsin-exam-table').on('click', '.btn-exam-delete', function() {
                const santriId = Number($(this).data('santri'));
                const recordId = Number($(this).data('id'));
                Swal.fire({
                    title: 'Hapus hasil ujian?',
                    text: 'Percobaan ujian ini akan dihapus permanen.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({
                        url: routes.recordBase + '/' + recordId,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    }).done(function(response) {
                        delete historyCache[santriId];
                        table.ajax.reload(null, false);
                        Swal.fire('Terhapus', response.message, 'success');
                    }).fail(xhr => Swal.fire('Gagal', xhr.responseJSON?.message ||
                        'Data gagal dihapus.', 'error'));
                });
            });
        });
    </script>
@endpush
