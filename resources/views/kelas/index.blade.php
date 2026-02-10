@extends('layouts.app')

@section('title', 'Data Kelas')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Daftar Kelas</span>
            <button class="btn btn-light btn-sm" id="btnAddKelas">Tambah Kelas</button>
        </div>
        <div class="card-body table-responsive">
            <table id="kelas-table" class="table table-striped align-middle w-100">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Kelas</th>
                        <th>Deskripsi</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Diisi otomatis oleh DataTables via AJAX --}}
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('modals')
    {{-- ===================== MODAL CREATE & EDIT ===================== --}}
    <div class="modal fade" id="modalKelas" tabindex="-1">
        <div class="modal-dialog">
            <form id="formKelas">
                @csrf
                <input type="hidden" name="id" id="kelas_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalKelasTitle">Tambah Kelas</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Nama Kelas</label>
                            <input type="text" class="form-control" name="nama_kelas" id="nama_kelas"
                                placeholder="Masukkan Nama Kelas..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="deskripsi" rows="3" placeholder="Masukkan Catatan..."></textarea>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveKelas">Simpan</button>
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
                        searchable: false
                    },
                    {
                        data: 'nama_kelas',
                        name: 'nama_kelas'
                    },
                    {
                        data: 'deskripsi',
                        name: 'deskripsi'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ]

            });

            // ================================
            //  OPEN CREATE MODAL
            // ================================
            $('#btnAddKelas').on('click', function() {
                $('#formKelas')[0].reset();
                $('#kelas_id').val('');
                $('#modalKelasTitle').text("Tambah Kelas");
                modalKelas.show();
            });

            // ================================
            //  OPEN EDIT MODAL
            // ================================
            $(document).on('click', '.btn-edit', function() {
                let d = $(this).data();

                $('#modalKelasTitle').text("Edit Kelas");
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
                const url = id ?
                    "{{ url('kelas') }}/" + id :
                    "{{ route('kelas.store') }}";

                const method = id ? "PUT" : "POST";

                $.ajax({
                    url: url,
                    type: method,
                    data: $('#formKelas').serialize(),
                    success: function(res) {
                        modalKelas.hide();
                        $('#kelas-table').DataTable().ajax.reload();

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

                if (!window.AppAlert) {
                    return;
                }

                AppAlert.warning('Data kelas tidak dapat dikembalikan!', 'Hapus Kelas?')
                    .then(result => {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ url('kelas') }}/" + id,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                $('#kelas-table').DataTable().ajax.reload();
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
