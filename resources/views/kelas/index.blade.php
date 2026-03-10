@extends('layouts.app')

@section('title', 'Data Kelas')

@section('content')
    <style>
        /* ================= KONSISTENSI STYLING ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
            transition: color 0.3s ease;
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ffffff !important;
        }

        /* Form Controls UI */
        .form-control {
            border-radius: 8px;
            padding: 0.6rem 1rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--cui-secondary-color);
        }

        /* Fix Jarak Header Tabel (Search & Length Menu) */
        .dataTables_wrapper .row:first-child {
            padding: 1.5rem 1.5rem 0.5rem 1.5rem;
            margin: 0;
        }

        /* Fix Jarak Pagination Tabel */
        .dataTables_wrapper .row:last-child {
            padding: 1rem 1.5rem;
            margin: 0;
            border-top: 1px solid var(--cui-border-color);
        }

        .dataTables_wrapper .dataTables_paginate {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        .dataTables_wrapper .dataTables_length select {
            min-width: 75px !important;
            /* Memperlebar kotak agar angka tidak terjepit */
            padding-right: 1.8rem !important;
            /* Memberi ruang khusus untuk ikon panah di kanan */
            margin: 0 0.4rem !important;
            /* Memberi jarak manis antara teks "Tampil" dan "data" */
            display: inline-block !important;
        }

        /* Batasi lebar teks di tabel agar menjadi 1 baris dan ada titik-titik (ellipsis) */
        #kelas-table td {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Khusus kolom ke-3 (Deskripsi), beri batas maksimal lebar */
        #kelas-table td:nth-child(3) {
            max-width: 300px;
            /* Sesuaikan angka ini dengan selera Mas */
        }
    </style>

    {{-- HEADER PAGE --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Data Kelas</h4>
            <span class="text-muted small">Kelola informasi daftar kelas dan deskripsinya</span>
        </div>
        <button class="btn text-white px-4 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2"
            style="background: var(--islamic-purple-600);" id="btnAddKelas">
            <i class="bi bi-plus-circle-fill"></i> Tambah Kelas Baru
        </button>
    </div>

    {{-- MAIN CARD --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="kelas-table" class="table table-striped table-hover align-middle w-100 mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No.</th>
                            <th>Nama Kelas</th>
                            <th>Deskripsi</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Diisi otomatis oleh DataTables via AJAX --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- ===================== MODAL CREATE & EDIT ===================== --}}
    <div class="modal fade" id="modalKelas" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formKelas" class="w-100">
                @csrf
                <input type="hidden" name="id" id="kelas_id">

                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4">
                        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="modalKelasTitle">
                            <i class="bi bi-easel2-fill"></i> Tambah Kelas
                        </h5>
                        <button type="button" class="btn-close bg-light rounded-circle p-2"
                            data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <label class="form-label">Nama Kelas</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-easel"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" name="nama_kelas"
                                    id="nama_kelas" placeholder="Contoh: Kelas Tahfidz A" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Deskripsi & Catatan</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent align-items-start pt-2"><i
                                        class="bi bi-card-text"></i></span>
                                <textarea class="form-control border-start-0 ps-0" name="deskripsi" id="deskripsi" rows="3"
                                    placeholder="Masukkan deskripsi singkat tentang kelas ini..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4 rounded-pill" data-coreui-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn text-white px-4 rounded-pill shadow-sm"
                            style="background: var(--islamic-purple-600);" id="btnSaveKelas">
                            <i class="bi bi-save me-1"></i> Simpan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const modalEl = document.getElementById('modalKelas');
            const modalKelas = new coreui.Modal(modalEl);

            // ================================
            //  INIT DATATABLES
            // ================================
            const table = $('#kelas-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('kelas.datatable') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'nama_kelas',
                        name: 'nama_kelas',
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi',
                        render: function(data) {
                            return data ? data : '-';
                        }
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'd-flex pe-4'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari kelas...",
                    lengthMenu: "Tampil _MENU_ data",
                    info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ kelas",
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });

            // ================================
            //  OPEN CREATE MODAL
            // ================================
            $('#btnAddKelas').on('click', function() {
                $('#formKelas')[0].reset();
                $('#kelas_id').val('');
                $('#modalKelasTitle').html('<i class="bi bi-easel2-fill"></i> Tambah Kelas Baru');
                modalKelas.show();
            });

            // ================================
            //  OPEN EDIT MODAL
            // ================================
            $(document).on('click', '.btn-edit', function() {
                let d = $(this).data();

                $('#modalKelasTitle').html('<i class="bi bi-pencil-square"></i> Edit Kelas');
                $('#kelas_id').val(d.id);
                $('#nama_kelas').val(d.nama);
                $('#deskripsi').val(d.deskripsi);

                modalKelas.show();
            });

            // ================================
            //  SUBMIT (CREATE/UPDATE)
            // ================================
            $('#formKelas').on('submit', function(e) {
                e.preventDefault();

                const id = $('#kelas_id').val();
                const url = id ? "{{ url('kelas') }}/" + id : "{{ route('kelas.store') }}";
                const method = id ? "PUT" : "POST";

                $.ajax({
                    url: url,
                    type: method,
                    data: $('#formKelas').serialize(),
                    success: function(res) {
                        modalKelas.hide();
                        $('#kelas-table').DataTable().ajax.reload(null, false);

                        if (window.AppAlert) {
                            AppAlert.success(res.message ?? 'Kelas berhasil disimpan.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan.';
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            msg = Object.values(errors).map(e => e[0]).join('\n');
                        }

                        if (window.AppAlert) {
                            AppAlert.error(msg);
                        }
                    }
                });
            });

            // ================================
            //  DELETE KELAS
            // ================================
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');

                if (!window.AppAlert) return;

                AppAlert.warning('Data kelas beserta data santri di dalamnya mungkin terpengaruh.',
                        'Hapus Kelas?')
                    .then(result => {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ url('kelas') }}/" + id,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                $('#kelas-table').DataTable().ajax.reload(null, false);
                                AppAlert.success(res.message ?? 'Kelas berhasil dihapus.');
                            },
                            error: function() {
                                AppAlert.error('Tidak dapat menghapus kelas.');
                            }
                        });
                    });
            });

        });
    </script>
@endpush
