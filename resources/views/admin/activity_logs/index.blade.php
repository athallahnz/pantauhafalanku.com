@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem')

@section('content')
    <style>
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ececec !important;
        }

        .main-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            background-color: var(--cui-card-bg);
        }

        .table thead th {
            background-color: var(--cui-tertiary-bg);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--cui-secondary-color);
            padding: 15px;
            border-bottom: 1px solid var(--cui-border-color);
        }

        /* Styling untuk Modal JSON Detail */
        .log-detail-box {
            background: var(--cui-tertiary-bg);
            border-radius: 8px;
            padding: 15px;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
    </style>

    {{-- HEADER --}}
    <div class="row mb-4 align-items-center px-3 px-md-0 g-2">
        <div class="col">
            <h4 class="fw-bold text-adaptive-purple mb-1">Log Aktivitas Sistem</h4>
            <p class="text-muted small mb-0">
                <i class="bi bi-clock-history me-1"></i> Rekam jejak seluruh perubahan data di aplikasi
            </p>
        </div>
    </div>

    {{-- MAIN TABLE --}}
    <div class="card main-card mb-4">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Daftar Aktivitas Terbaru</h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap w-100" id="activityLogTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Aktor (User)</th>
                            <th>Aktivitas</th>
                            <th>Modul (Target)</th>
                            <th>IP Address</th>
                            <th class="text-end pe-4">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data diisi otomatis oleh Yajra DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- MODAL DETAIL LOG --}}
    <div class="modal fade" id="modalDetailLog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-0">
                    <h6 class="modal-title fw-bold text-white bg-adaptive-purple px-3 py-1 rounded-pill">
                        <i class="bi bi-file-earmark-diff me-1"></i> Detail Perubahan Data
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="fw-bold mb-3" id="modalLogDesc"></p>

                    <div class="row g-3">
                        <div class="col-md-6" id="boxOldData">
                            <h6 class="fw-bold text-danger small"><i class="bi bi-dash-circle me-1"></i> Data Lama</h6>
                            <div class="log-detail-box" id="jsonOld"></div>
                        </div>
                        <div class="col-md-6" id="boxNewData">
                            <h6 class="fw-bold text-success small"><i class="bi bi-plus-circle me-1"></i> Data Baru</h6>
                            <div class="log-detail-box" id="jsonNew"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-adaptive-purple border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4"
                        data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // ================= INISIALISASI DATATABLES =================
            let table = $('#activityLogTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.activity_logs.index') }}",
                columns: [{
                        data: 'waktu',
                        name: 'created_at'
                    },
                    {
                        data: 'aktor',
                        name: 'causer.name',
                        orderable: false
                    }, // causer relasi, orderable false untuk amannya
                    {
                        data: 'aktivitas',
                        name: 'description'
                    },
                    {
                        data: 'modul',
                        name: 'subject_type'
                    },
                    {
                        data: 'ip',
                        name: 'ip_address'
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
                ],
                order: [
                    [0, 'desc']
                ], // Default sorting berdasarkan waktu terbaru
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                }
            });

            // ================= EVENT KLIK TOMBOL CEK DATA =================
            // Gunakan event delegation pada tbody karena ini adalah DataTables (elemen dinamis)
            $('#activityLogTable tbody').on('click', '.btn-detail', function() {
                const desc = $(this).data('desc');
                const properties = $(this).data('properties'); // jQuery otomatis parsing format JSON

                $('#modalLogDesc').text(desc);

                // Reset box
                $('#boxOldData, #boxNewData').show();
                $('#jsonOld, #jsonNew').text('');

                // Format JSON output menjadi string yang rapi dengan spasi
                const formatJSON = (obj) => JSON.stringify(obj, null, 4);

                // Cek isi properties (old vs new)
                if (properties) {
                    if (properties.old && properties.new) {
                        // Kasus: UPDATE
                        $('#jsonOld').text(formatJSON(properties.old));
                        $('#jsonNew').text(formatJSON(properties.new));
                    } else if (properties.attributes) {
                        // Kasus: CREATE
                        $('#boxOldData').hide();
                        $('#boxNewData').removeClass('col-md-6').addClass('col-md-12');
                        $('#jsonNew').text(formatJSON(properties.attributes));
                    } else if (properties.old && !properties.new) {
                        // Kasus: DELETE
                        $('#boxNewData').hide();
                        $('#boxOldData').removeClass('col-md-6').addClass('col-md-12');
                        $('#jsonOld').text(formatJSON(properties.old));
                    } else {
                        // Fallback
                        $('#boxOldData').hide();
                        $('#boxNewData').removeClass('col-md-6').addClass('col-md-12');
                        $('#jsonNew').text(formatJSON(properties));
                    }
                } else {
                    $('#boxOldData, #boxNewData').hide();
                    $('#modalLogDesc').text('Tidak ada detail data yang direkam (properties kosong).');
                }

                // Tampilkan Modal
                const modal = new bootstrap.Modal(document.getElementById('modalDetailLog'));
                modal.show();
            });

            // Kembalikan class grid ke ukuran normal setelah modal ditutup
            document.getElementById('modalDetailLog').addEventListener('hidden.bs.modal', function() {
                $('#boxOldData, #boxNewData').removeClass('col-md-12').addClass('col-md-6').show();
            });
        });
    </script>
@endpush
