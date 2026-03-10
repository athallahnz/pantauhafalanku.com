@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <style>
        /* Warna Teks Judul Adaptif */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
            transition: color 0.3s ease;
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ffffff !important;
        }

        /* ================= CUSTOM TABS MODERN ================= */
        .custom-tabs {
            border-bottom: 2px solid var(--cui-border-color);
            margin-bottom: 0;
        }

        .custom-tabs .nav-link {
            color: var(--cui-secondary-color);
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            padding: 1rem 1.5rem;
            margin-bottom: -2px;
            transition: all 0.3s ease;
            background: transparent;
        }

        .custom-tabs .nav-link:hover {
            color: var(--islamic-purple-500);
            border-bottom-color: rgba(107, 78, 255, 0.3);
        }

        .custom-tabs .nav-link.active {
            color: var(--islamic-purple-600);
            border-bottom-color: var(--islamic-purple-600);
        }

        [data-coreui-theme="dark"] .custom-tabs .nav-link.active {
            color: #ffffff;
            border-bottom-color: #ffffff;
        }

        /* Form Controls UI */
        .form-control,
        .form-select {
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

        /* Fix Jarak Tabel */
        .dataTables_wrapper .row:first-child {
            padding: 1.5rem 1.5rem 1rem 1.5rem;
            margin: 0;
        }

        .dataTables_wrapper .row:last-child {
            padding: 1rem 1.5rem;
            margin: 0;
            border-top: 1px solid var(--cui-border-color);
        }

        .dataTables_wrapper .dataTables_length select {
            min-width: 75px !important;
            padding-right: 1.8rem !important;
            margin: 0 0.4rem !important;
            display: inline-block !important;
        }

        /* Styling Glassmorphism Action Bar */
        .glass-action-bar {
            background: rgba(30, 30, 30, 0.7);
            /* Background gelap transparan */
            backdrop-filter: blur(15px) saturate(150%);
            /* Efek Blur Kaca */
            -webkit-backdrop-filter: blur(15px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            /* Border tipis agar terlihat seperti tepi kaca */
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        /* Sedikit animasi denyut pada badge jumlah terpilih */
        #selectedCount {
            transition: all 0.3s ease;
            min-width: 22px;
        }

        /* Responsif untuk Mobile */
        @media (max-width: 576px) {
            .glass-action-bar {
                padding: 0.8rem 1rem !important;
                gap: 2 !important;
            }
        }
    </style>

    {{-- HEADER PAGE --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Manajemen User</h4>
            <span class="text-muted small">Kelola data, hak akses, dan akun pengguna sistem</span>
        </div>
        <button class="btn text-white px-4 py-2 rounded-pill shadow-sm d-flex align-items-center gap-2"
            style="background: var(--islamic-purple-600);" id="btnAddUser">
            <i class="bi bi-plus-circle-fill"></i> Tambah User Baru
        </button>
    </div>

    {{-- MAIN CARD --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="px-3 pt-2">
                <ul class="nav nav-tabs custom-tabs" role="tablist">
                    @foreach (['superadmin' => 'Shield-lock', 'pimpinan' => 'Person-circle', 'admin' => 'Diagram-3', 'musyrif' => 'Book', 'santri' => 'Person-badge'] as $role => $icon)
                        <li class="nav-item">
                            <button class="nav-link {{ $loop->first ? 'active' : '' }} d-flex align-items-center gap-2"
                                data-coreui-toggle="tab" data-coreui-target="#tab-{{ $role }}" type="button">
                                <i class="bi bi-{{ $icon }}"></i> {{ ucfirst($role) }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tab-content">
                @foreach (['superadmin', 'pimpinan', 'admin', 'musyrif', 'santri'] as $role)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="tab-{{ $role }}">
                        <div class="table-responsive">
                            <table id="table-{{ $role }}"
                                class="table table-striped table-hover align-middle w-100 mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" style="width: 40px;">
                                            <input type="checkbox" class="form-check-input check-all"
                                                data-role="{{ $role }}">
                                        </th>
                                        <th class="ps-4">No.</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Nomor HP</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

@endsection


@push('modals')
    {{-- FLOATING BULK ACTION BAR --}}
    <div id="bulkActionBar" class="position-fixed bottom-0 start-50 translate-middle-x mb-5 d-none animate__animated"
        style="z-index: 1050;">
        <div class="glass-action-bar text-white px-4 py-3 rounded-pill shadow-lg d-flex align-items-center gap-3">
            <span class="small fw-bold"><span id="selectedCount" class="badge rounded-pill bg-white text-dark me-1">0</span>
                Terpilih</span>
            <div class="vr opacity-25"></div>

            <button class="btn btn-sm btn-success rounded-pill px-3 fw-bold d-flex align-items-center gap-2"
                id="btnBulkApprove">
                <i class="bi bi-check-circle-fill"></i> <span class="d-none d-md-inline">Approve Massal</span>
            </button>

            <button class="btn btn-sm btn-danger rounded-pill px-3 fw-bold d-flex align-items-center gap-2"
                id="btnBulkDelete">
                <i class="bi bi-trash-fill"></i> <span class="d-none d-md-inline">Hapus Massal</span>
            </button>

            <div class="vr opacity-25"></div>
            <button class="btn btn-sm btn-link text-white p-0" id="btnCancelBulk" title="Batalkan Pilihan">
                <i class="bi bi-x-circle-fill fs-5"></i>
            </button>
        </div>
    </div>
    {{-- MODAL CREATE & EDIT --}}
    <div class="modal fade" id="modalUser" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formUser" class="w-100">
                @csrf
                <input type="hidden" name="id" id="user_id">
                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4 bg-primary text-white">
                        <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="modalUserTitle">
                            <i class="bi bi-person-plus-fill"></i> Tambah User Baru
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Email</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nomor HP</label>
                                <input type="text" class="form-control" name="nomor" id="nomor">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role Akses</label>
                                <select class="form-select" name="role" id="role" required>
                                    <option value="superadmin">SuperAdmin</option>
                                    <option value="pimpinan">Pimpinan</option>
                                    <option value="admin">Admin</option>
                                    <option value="musyrif">Musyrif</option>
                                    <option value="santri">Santri</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password <small class="text-muted fw-normal">(Kosongkan jika tidak
                                    ganti)</small></label>
                            <input type="password" class="form-control" name="password" id="password">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 p-4">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL APPROVE --}}
    <div class="modal fade" id="modalApprove" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <form id="formApprove" class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
                @csrf
                <input type="hidden" name="user_id" id="approve_user_id">
                <input type="hidden" name="role" id="approve_user_role">
                <i class="bi bi-shield-check text-success display-4 mb-3"></i>
                <h5 class="fw-bold mb-1">Verifikasi Akun</h5>
                <p class="text-muted small mb-4" id="approveText"></p>
                <div id="divPilihKelas" class="text-start mb-3 d-none">
                    <label class="form-label small fw-bold">Penempatan Kelas</label>
                    <select name="kelas_id" class="form-select border-primary" id="approve_kelas_id">
                        <option value="" hidden selected>Pilih Kelas...</option>
                        @foreach ($kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success rounded-pill fw-bold py-2">Setujui Sekarang</button>
                    <button type="button" class="btn btn-light rounded-pill small"
                        data-coreui-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- 1. KOMPONEN & INITIALIZATION ---
            const modalUserEl = document.getElementById('modalUser');
            const modalApproveEl = document.getElementById('modalApprove');

            // Inisialisasi Modal hanya jika elemennya ada di DOM
            const modalUser = modalUserEl ? new coreui.Modal(modalUserEl) : null;
            const modalApprove = modalApproveEl ? new coreui.Modal(modalApproveEl) : null;

            // Global AJAX Setup (Agar tidak perlu tulis _token di setiap request $.post atau $.ajax)
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // --- 2. DATATABLES CONFIGURATION ---
            const dtConfig = (role) => ({
                processing: true,
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"></div>'
                },
                serverSide: true,
                ajax: {
                    url: "{{ route('superadmin.users.datatable') }}",
                    data: (d) => {
                        d.role = role;
                    }
                },
                columns: [{
                        data: 'checkbox',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4',
                        width: '40px'
                    }, {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
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
                        data: 'status_badge',
                        name: 'status_badge',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari user...",
                    lengthMenu: "Tampil _MENU_ data",
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });

            // Initialize Tables (Cek ketersediaan elemen tabel sebelum init)
            const tables = {};
            ['superadmin', 'admin', 'musyrif', 'santri', 'pimpinan'].forEach(role => {
                const tableEl = $(`#table-${role}`);
                if (tableEl.length) {
                    tables[role] = tableEl.DataTable(dtConfig(role));
                }
            });

            const reloadAllTables = () => {
                Object.values(tables).forEach(table => {
                    if (table) table.ajax.reload(null, false);
                });
            };

            // --- 3. LOGIKA BUTTONS & EVENTS ---

            // Tambah User (Reset Form)
            $('#btnAddUser').on('click', function() {
                $('#formUser')[0].reset();
                $('#user_id').val('');
                $('#modalUserTitle').html('<i class="bi bi-person-plus-fill"></i> Tambah User Baru');
                if (modalUser) modalUser.show();
            });

            // Edit User (Fill Data)
            $(document).on('click', '.btn-edit', function() {
                const d = $(this).data();
                $('#user_id').val(d.id);
                $('#name').val(d.name);
                $('#email').val(d.email);
                $('#nomor').val(d.nomor);
                $('#role').val(d.role);
                $('#modalUserTitle').html('<i class="bi bi-pencil-square"></i> Edit User');
                if (modalUser) modalUser.show();
            });

            // Tombol Approve
            $(document).on('click', '.btn-approve', function() {
                const d = $(this).data();
                $('#approve_user_id').val(d.id);
                $('#approve_user_role').val(d.role);
                $('#approveText').html(
                    `Setujui akun <strong>${d.name}</strong> sebagai ${d.role.toUpperCase()}?`);

                if (d.role === 'santri') {
                    $('#divPilihKelas').removeClass('d-none');
                    $('#approve_kelas_id').attr('required', true);
                } else {
                    $('#divPilihKelas').addClass('d-none');
                    $('#approve_kelas_id').removeAttr('required').val('');
                }
                if (modalApprove) modalApprove.show();
            });

            // --- 4. AJAX SUBMISSIONS ---

            // Submit Approval
            $('#formApprove').on('submit', function(e) {
                e.preventDefault();
                const btn = $(this).find('button[type="submit"]');
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Processing...');

                $.post("{{ route('superadmin.users.approve') }}", $(this).serialize())
                    .done((res) => {
                        if (modalApprove) modalApprove.hide();
                        reloadAllTables();
                        if (window.AppAlert) AppAlert.success(res.message ||
                            'Akun berhasil diaktifkan!');
                    })
                    .fail((xhr) => {
                        if (window.AppAlert) AppAlert.error(xhr.responseJSON?.message ||
                            'Gagal memverifikasi user.');
                    })
                    .always(() => {
                        btn.prop('disabled', false).html('Setujui Sekarang');
                    });
            });

            // Submit Create/Update
            $('#formUser').on('submit', function(e) {
                e.preventDefault();
                const id = $('#user_id').val();
                const url = id ? "{{ url('superadmin/users') }}/" + id :
                    "{{ route('superadmin.users.store') }}";
                const method = id ? "PUT" : "POST";

                $.ajax({
                    url: url,
                    type: method,
                    data: $(this).serialize(),
                    success: function(res) {
                        if (modalUser) modalUser.hide();
                        reloadAllTables();
                        if (window.AppAlert) AppAlert.success(res.message);
                    },
                    error: function(xhr) {
                        let msg = 'Gagal menyimpan data.';
                        if (xhr.status === 422) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '<br>');
                        }
                        if (window.AppAlert) AppAlert.error(msg);
                    }
                });
            });

            // Hapus User
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                if (window.AppAlert) {
                    AppAlert.warning('User akan dihapus permanen!', 'Hapus Akun?')
                        .then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: "{{ url('superadmin/users') }}/" + id,
                                    type: "DELETE",
                                    success: (res) => {
                                        reloadAllTables();
                                        AppAlert.success(res.message);
                                    }
                                });
                            }
                        });
                }
            });

            // Tab Resize Fix
            document.querySelectorAll('button[data-coreui-toggle="tab"]').forEach(btn => {
                btn.addEventListener('shown.coreui.tab', () => {
                    $.fn.dataTable.tables({
                        visible: true,
                        api: true
                    }).columns.adjust();
                });
            });

            // --- LOGIKA BULK ACTION ---
            let selectedIds = [];

            const updateBulkBar = () => {
                selectedIds = [];
                // Cari semua checkbox di baris tabel yang sedang dicentang
                $('.user-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                const count = selectedIds.length;
                const bulkBar = $('#bulkActionBar');
                const countBadge = $('#selectedCount');

                if (count > 0) {
                    countBadge.text(count);
                    countBadge.addClass('animate__animated animate__bounceIn');
                    setTimeout(() => countBadge.removeClass('animate__bounceIn'), 500);

                    // Tambahkan class animasi slideInUp SAAT mau ditampilkan
                    bulkBar.removeClass('d-none').addClass('animate__slideInUp');
                } else {
                    // Hapus animasinya agar saat muncul lagi tetap ada efek slide up
                    bulkBar.addClass('d-none').removeClass('animate__slideInUp');
                    $('.check-all').prop('checked', false);
                }
            };

            // Event Check All
            $(document).on('change', '.check-all', function() {
                const role = $(this).data('role');
                $(`#table-${role} .user-checkbox`).prop('checked', $(this).is(':checked'));
                updateBulkBar();
            });

            // Event Check Per Baris
            $(document).on('change', '.user-checkbox', function() {
                updateBulkBar();
            });

            // Cancel Bulk
            $('#btnCancelBulk').on('click', function() {
                $('.user-checkbox, .check-all').prop('checked', false);
                updateBulkBar();
            });

            // --- EKSEKUSI BULK ACTION ---
            // 1. Bulk Approve
            $('#btnBulkApprove').on('click', function() {
                if (selectedIds.length === 0) return;

                AppAlert.warning(`Setujui ${selectedIds.length} akun terpilih?`, 'Approve Massal')
                    .then(result => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ route('superadmin.users.bulk_approve') }}",
                                type: "POST",
                                data: {
                                    _token: "{{ csrf_token() }}",
                                    ids: selectedIds // Array ID: [50, 56, 63, ...]
                                },
                                success: function(res) {
                                    AppAlert.success(res.message);
                                    reloadAllTables();
                                    $('#btnCancelBulk').click();
                                },
                                error: function(xhr) {
                                    // Cek di konsol jika masih 500
                                    console.error(xhr.responseJSON);
                                    AppAlert.error(xhr.responseJSON?.message ||
                                        'Gagal memproses persetujuan massal.');
                                }
                            });
                        }
                    });
            });

            // 2. Bulk Delete
            $('#btnBulkDelete').on('click', function() {
                if (selectedIds.length === 0) return;

                AppAlert.warning(`Hapus ${selectedIds.length} akun?`, 'Hapus Massal')
                    .then(result => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: "{{ route('superadmin.users.bulk_delete') }}",
                                type: "DELETE", // Pastikan methodnya DELETE sesuai route
                                data: {
                                    ids: selectedIds
                                },
                                success: function(res) {
                                    AppAlert.success(res.message);
                                    reloadAllTables();
                                    $('#btnCancelBulk').click();
                                },
                                error: function(xhr) {
                                    AppAlert.error('Gagal menghapus data.');
                                }
                            });
                        }
                    });
            });
        });
    </script>
@endpush
