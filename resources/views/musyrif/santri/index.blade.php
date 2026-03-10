@extends('layouts.app')

@section('title', 'Santri Binaan')

@section('content')
    <style>
        /* ================= KONSISTENSI THEME & RESPONSIVE ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #fff !important;
        }

        .main-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        /* Padding Tabel agar tidak menempel ke tepi card */
        .card-body-table {
            padding: 1.5rem !important;
        }

        /* Memaksa isi tabel tetap dalam satu baris (Single Line) */
        #musyrif-santri-table th,
        #musyrif-santri-table td {
            white-space: nowrap !important;
            text-overflow: ellipsis;
            /* Opsional: nambah titik-titik kalau kepanjangan */
            overflow: hidden;
        }

        /* Tambahkan padding horizontal sedikit agar antar kolom ada jarak */
        #musyrif-santri-table td {
            padding-right: 20px !important;
        }

        /* Styling khusus untuk kolom progress agar terlihat seperti label */
        .progress-pill {
            font-size: 0.85rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .card-body-table {
                padding: 1rem !important;
            }
        }
    </style>

    <div class="row mb-4 align-items-center px-3 px-md-0 g-3">
        <div class="col-12 col-md-auto text-start">
            <h4 class="fw-bold text-adaptive-purple mb-1">Daftar Santri Binaan</h4>
            <p class="text-muted small mb-0">Manajemen data dan pantau perkembangan hafalan santri Anda.</p>
        </div>
    </div>

    <div class="card main-card spotlight-card">
        <div class="card-body card-body-table table-responsive">
            <table id="musyrif-santri-table" class="table table-hover align-middle w-100 text-nowrap">
                <thead class="bg-light bg-opacity-50">
                    <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                        <th>No.</th>
                        <th>Nama Santri</th>
                        <th>Kelas</th>
                        <th>NIS</th>
                        <th>Tgl Lahir</th>
                        <th>L/P</th>
                        <th>Progress Hafalan Harian</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Diisi oleh DataTables --}}
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('modals')
    {{-- MODAL DETAIL PROGRESS (STYLING REFINED) --}}
    <div class="modal fade" id="modalProgressSantri" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header border-0 bg-primary text-white p-4">
                    <h5 class="modal-title fw-bold text-white">Detail Progress Hafalan Harian</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-center mb-4 p-3 rounded-4 bg-light border-dashed border">
                        <div class="bg-primary-subtle text-primary rounded-3 p-3 me-3">
                            <i class="bi bi-person-lines-fill fs-3"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5" id="progressSantriName">-</div>
                            <div class="text-muted small" id="progressSantriMeta">-</div>
                        </div>
                    </div>

                    <div id="progressSantriBody" class="p-2">
                        {{-- Data HTML dari JS --}}
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-outline-secondary w-100 rounded-pill fw-bold"
                        data-coreui-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // LOGIKA TETAP UTUH 100% SESUAI CODE ASLI USER
            const modalProgress = new coreui.Modal(document.getElementById('modalProgressSantri'));

            $('#musyrif-santri-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('musyrif.santri.datatable') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'kelas',
                        name: 'kelas',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nis',
                        name: 'nis'
                    },
                    {
                        data: 'tanggal_lahir',
                        name: 'tanggal_lahir',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'jenis_kelamin',
                        name: 'jenis_kelamin',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'progress_ringkas',
                        name: 'progress_ringkas',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                ],
                order: [
                    [1, 'asc']
                ],
                drawCallback: function() {
                    $('.btn-progress').off('click').on('click', function() {
                        const d = $(this).data();
                        $('#progressSantriName').text(d.nama || '-');
                        $('#progressSantriMeta').text(d.kelas ? `Kelas: ${d.kelas}` : '-');

                        const textarea = document.createElement('textarea');
                        textarea.innerHTML = d.detail_html || '';
                        $('#progressSantriBody').html(textarea.value ||
                            '<div class="text-muted text-center py-4 italic">Tidak ada data progress.</div>'
                        );

                        modalProgress.show();
                    });
                }
            });
        });
    </script>
@endpush
