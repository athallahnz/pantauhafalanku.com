@extends('layouts.app')

@section('title', 'Data Musyrif')

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="fw-semibold">Daftar Musyrif</div>

                <div class="d-flex gap-2">
                    <button class="btn btn-light btn-sm" id="btnAddMusyrif" type="button">
                        Tambah Musyrif
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive">
            <table id="musyrif-table" class="table table-striped align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">No.</th>
                        <th>Nama Musyrif</th>
                        <th>Akun (User)</th>
                        <th>Pagi (Hari ini)</th>
                        <th>Sore (Hari ini)</th>
                        <th>Rekap Bulan Ini</th>
                        <th>Keterangan</th>
                        <th class="text-end" style="width:160px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

@endsection

@push('modals')
    {{-- ===================== MODAL CREATE & EDIT ===================== --}}
    <div class="modal fade" id="modalMusyrif" tabindex="-1">
        <div class="modal-dialog">
            <form id="formMusyrif">
                @csrf
                <input type="hidden" id="musyrif_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMusyrifTitle">Tambah Musyrif</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Musyrif</label>
                            <input type="text" class="form-control" name="nama" id="nama"
                                placeholder="Masukkan Nama Musyrif..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kode</label>
                            <input type="text" class="form-control" name="kode" id="kode"
                                placeholder="Contoh: M-001">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" id="keterangan" rows="3"
                                placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>

                        <hr class="my-3">

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="create_user" />
                            <label class="form-check-label" for="create_user">
                                Buat akun login untuk Musyrif
                            </label>
                        </div>

                        <div id="createUserFields" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">Email (akun baru)</label>
                                <input type="email" class="form-control" id="email" placeholder="contoh@domain.com">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password (akun baru)</label>
                                <input type="password" class="form-control" id="password" placeholder="Minimal 8 karakter">
                            </div>
                            <div class="small text-muted">
                                Akun akan dibuat dengan role <b>musyrif</b> dan name mengikuti Nama Musyrif.
                            </div>
                        </div>

                        <div id="pickUserFields">
                            <div class="mb-3">
                                <label class="form-label">Pilih user musyrif (opsional)</label>
                                <select class="form-select" id="user_id">
                                    <option value="">-- Tanpa akun / pilih nanti --</option>
                                    @foreach ($musyrifUserCandidates as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveMusyrif">Simpan</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ================================
            // INIT COREUI MODAL
            // ================================
            const modalEl = document.getElementById('modalMusyrif');
            const modalMusyrif = new coreui.Modal(modalEl);

            // ================================
            // INIT DATATABLE
            // ================================
            const table = $('#musyrif-table').DataTable({

                processing: true,
                serverSide: true,

                ajax: "{{ route('admin.musyrif.data') }}",

                columns: [

                    {
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'nama',
                        name: 'musyrifs.nama'
                    },

                    {
                        data: 'akun',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'absen_pagi',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'absen_sore',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'rekap_bulan',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'keterangan',
                        name: 'musyrifs.keterangan'
                    },

                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false
                    }

                ],

                order: [
                    [1, 'asc']
                ]

            });

            // ================================
            // RESET FORM
            // ================================
            function resetForm() {
                $('#formMusyrif')[0].reset();

                $('#musyrif_id').val('');

                $('#modalMusyrifTitle').text('Tambah Musyrif');

                $('#btnSaveMusyrif').text('Simpan');

                $('#create_user').prop('checked', false);

                $('#createUserFields').addClass('d-none');

                $('#pickUserFields').removeClass('d-none');
            }

            // ================================
            // OPEN CREATE MODAL
            // ================================
            $('#btnAddMusyrif').on('click', function() {
                resetForm();

                modalMusyrif.show();
            });

            // ================================
            // OPEN EDIT MODAL
            // ================================
            $(document).on('click', '.btnEdit', function() {
                const id = $(this).data('id');

                resetForm();

                $('#modalMusyrifTitle').text('Edit Musyrif');

                $('#btnSaveMusyrif').text('Update');

                $.ajax({

                    url: "{{ route('admin.musyrif.show', ':id') }}".replace(':id', id),

                    type: "GET",

                    success: function(res) {
                        $('#musyrif_id').val(res.id);

                        $('#nama').val(res.nama);

                        $('#kode').val(res.kode);

                        $('#keterangan').val(res.keterangan);

                        $('#user_id').val(res.user_id ?? '');

                        modalMusyrif.show();
                    },

                    error: function(xhr) {
                        alert(xhr.responseJSON?.message ?? 'Gagal memuat data');
                    }

                });

            });

            // ================================
            // TOGGLE CREATE USER
            // ================================
            $('#create_user').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#createUserFields').removeClass('d-none');

                    $('#pickUserFields').addClass('d-none');

                    $('#user_id').val('');
                } else {
                    $('#createUserFields').addClass('d-none');

                    $('#pickUserFields').removeClass('d-none');
                }
            });

            // ================================
            // SUBMIT FORM
            // ================================
            $('#formMusyrif').on('submit', function(e) {
                e.preventDefault();

                const id = $('#musyrif_id').val();

                const isCreateUser = $('#create_user').is(':checked');

                const url = id ?
                    "{{ url('admin/musyrif') }}/" + id :
                    "{{ route('admin.musyrif.store') }}";

                const method = id ? "PUT" : "POST";

                const data = {

                    nama: $('#nama').val(),

                    kode: $('#kode').val(),

                    keterangan: $('#keterangan').val(),

                    user_id: isCreateUser ? null : $('#user_id').val(),

                    create_user: isCreateUser ? 1 : 0,

                    email: isCreateUser ? $('#email').val() : null,

                    password: isCreateUser ? $('#password').val() : null,

                    _token: "{{ csrf_token() }}"
                };

                $.ajax({

                    url: url,

                    type: method,

                    data: data,

                    success: function(res) {
                        modalMusyrif.hide();

                        table.ajax.reload();

                        if (window.AppAlert)
                            AppAlert.success(res.message ?? 'Berhasil disimpan');
                    },

                    error: function(xhr) {
                        let msg = xhr.responseJSON?.message ?? 'Terjadi kesalahan';

                        if (window.AppAlert)
                            AppAlert.error(msg);
                    }

                });

            });

            // ================================
            // DELETE
            // ================================
            $(document).on('click', '.btnDelete', function() {
                const id = $(this).data('id');

                if (!window.AppAlert) return;

                AppAlert.warning(
                    'Data musyrif tidak dapat dikembalikan',
                    'Hapus Musyrif?'
                ).then(result => {

                    if (!result.isConfirmed) return;

                    $.ajax({

                        url: "{{ url('admin/musyrif') }}/" + id,

                        type: "DELETE",

                        data: {
                            _token: "{{ csrf_token() }}"
                        },

                        success: function(res) {
                            table.ajax.reload();

                            AppAlert.success(res.message ?? 'Berhasil dihapus');
                        },

                        error: function() {
                            AppAlert.error('Gagal menghapus');
                        }

                    });

                });

            });

        });
    </script>
@endpush
