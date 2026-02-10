@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Manajemen User</span>
            <button class="btn btn-light btn-sm" id="btnAddUser">Tambah User</button>
        </div>

        <div class="card-body">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-coreui-toggle="tab"
                        data-coreui-target="#tab-superadmin" type="button">SuperAdmin</button></li>
                <li class="nav-item"><button class="nav-link" data-coreui-toggle="tab" data-coreui-target="#tab-admin"
                        type="button">Admin</button></li>
                <li class="nav-item"><button class="nav-link" data-coreui-toggle="tab" data-coreui-target="#tab-musyrif"
                        type="button">Musyrif</button></li>
                <li class="nav-item"><button class="nav-link" data-coreui-toggle="tab" data-coreui-target="#tab-santri"
                        type="button">Santri</button></li>
            </ul>

            <div class="tab-content pt-3">
                @php
                    $tables = [
                        'superadmin' => 'table-superadmin',
                        'admin' => 'table-admin',
                        'musyrif' => 'table-musyrif',
                        'santri' => 'table-santri',
                    ];
                @endphp

                <div class="tab-pane fade show active" id="tab-superadmin">
                    <div class="table-responsive">
                        <table id="table-superadmin" class="table table-striped align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Nomor</th>
                                    <th>Role</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-admin">
                    <div class="table-responsive">
                        <table id="table-admin" class="table table-striped align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Nomor</th>
                                    <th>Role</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-musyrif">
                    <div class="table-responsive">
                        <table id="table-musyrif" class="table table-striped align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Nomor</th>
                                    <th>Role</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-santri">
                    <div class="table-responsive">
                        <table id="table-santri" class="table table-striped align-middle w-100">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Nomor</th>
                                    <th>Role</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('modals')
    {{-- ===================== MODAL CREATE & EDIT ===================== --}}
    <div class="modal fade" id="modalUser" tabindex="-1">
        <div class="modal-dialog">
            <form id="formUser">
                @csrf
                <input type="hidden" name="id" id="user_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalUserTitle">Tambah User</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" name="name" id="name" required
                                placeholder="Masukkan Nama...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email" required
                                placeholder="Masukkan Email...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nomor</label>
                            <input type="text" class="form-control" name="nomor" id="nomor"
                                placeholder="Contoh: 08123456789">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" id="role" required>
                                <option value="superadmin">SuperAdmin</option>
                                <option value="admin">Admin</option>
                                <option value="musyrif">Musyrif</option>
                                <option value="santri">Santri</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Password <small class="text-muted">(kosongkan jika tidak diganti)</small>
                            </label>

                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="password">

                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="iconPassword"></i>
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-light" id="btnSaveUser">
                            Simpan
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

            const modalEl = document.getElementById('modalUser');
            const modalUser = new coreui.Modal(modalEl);
            const input = document.getElementById('password');
            const btn = document.getElementById('togglePassword');
            const icon = document.getElementById('iconPassword');

            // ================================
            //  TOGGLE SHOW/HIDE PASSWORD
            // ================================
            btn.addEventListener('click', () => {
                const show = input.type === "password";
                input.type = show ? "text" : "password";

                icon.classList.toggle("bi-eye", !show);
                icon.classList.toggle("bi-eye-slash", show);
            });

            // ================================
            //  INIT DATATABLES
            // ================================
            const dtConfig = (role) => ({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('superadmin.users.datatable') }}",
                    data: function(d) {
                        d.role = role;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'nomor',
                        name: 'nomor'
                    },
                    {
                        data: 'role',
                        name: 'role',
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
                ]
            });

            const dtSuperadmin = $('#table-superadmin').DataTable(dtConfig('superadmin'));
            const dtAdmin = $('#table-admin').DataTable(dtConfig('admin'));
            const dtMusyrif = $('#table-musyrif').DataTable(dtConfig('musyrif'));
            const dtSantri = $('#table-santri').DataTable(dtConfig('santri'));

            function reloadAllTables() {
                dtSuperadmin.ajax.reload(null, false);
                dtAdmin.ajax.reload(null, false);
                dtMusyrif.ajax.reload(null, false);
                dtSantri.ajax.reload(null, false);
            }

            // ================================
            //  OPEN CREATE MODAL
            // ================================
            $('#btnAddUser').on('click', function() {
                $('#formUser')[0].reset();
                $('#user_id').val('');
                $('#nomor').val('');
                $('#modalUserTitle').text("Tambah User");
                modalUser.show();
            });

            // ================================
            //  OPEN EDIT MODAL
            // ================================
            $(document).on('click', '.btn-edit', function() {
                let d = $(this).data();

                $('#modalUserTitle').text("Edit User");
                $('#user_id').val(d.id);
                $('#name').val(d.name);
                $('#email').val(d.email);
                $('#nomor').val(d.nomor);
                $('#role').val(d.role);
                $('#password').val('');

                modalUser.show();
            });

            // ================================
            //  SUBMIT (CREATE/UPDATE)
            // ================================
            $('#formUser').on('submit', function(e) {
                e.preventDefault();

                const id = $('#user_id').val();
                const url = id ?
                    "{{ url('superadmin/users') }}/" + id :
                    "{{ route('superadmin.users.store') }}";

                const method = id ? "PUT" : "POST";

                $.ajax({
                    url: url,
                    type: method,
                    data: $('#formUser').serialize(),
                    success: function(res) {
                        modalUser.hide();
                        reloadAllTables();
                        if (window.AppAlert) AppAlert.success(res.message ??
                            'User berhasil disimpan.');
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan.';
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            msg = Object.values(errors).map(e => e[0]).join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }

                        if (window.AppAlert) {
                            AppAlert.error(msg);
                        }
                    }
                });
            });


            // ================================
            //  DELETE USER
            // ================================
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');

                AppAlert.warning('Data user tidak dapat dikembalikan!', 'Hapus User?')
                    .then(result => {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ url('superadmin/users') }}/" + id,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                reloadAllTables();
                                AppAlert.success(res.message ?? 'User berhasil dihapus.');
                            },
                            error: function() {
                                AppAlert.error('Tidak dapat menghapus user.');
                            }
                        });
                    });
            });

            document.querySelectorAll('button[data-coreui-toggle="tab"]').forEach(btn => {
                btn.addEventListener('shown.coreui.tab', function() {
                    dtSuperadmin.columns.adjust();
                    dtAdmin.columns.adjust();
                    dtMusyrif.columns.adjust();
                    dtSantri.columns.adjust();
                });
            });
        });
    </script>
@endpush
