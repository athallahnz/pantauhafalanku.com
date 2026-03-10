@extends('layouts.app')

@section('title', 'Data Santri')

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

        /* Custom Tabs Modern */
        .custom-tabs {
            border-bottom: 2px solid var(--cui-border-color);
        }

        .custom-tabs .nav-link {
            color: var(--cui-secondary-color);
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            padding: 1rem 1.2rem;
            margin-bottom: -2px;
            transition: all 0.3s ease;
            background: transparent;
        }

        .custom-tabs .nav-link.active {
            color: var(--islamic-purple-600);
            border-bottom-color: var(--islamic-purple-600);
        }

        /* Form Controls */
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

        /* Fix Jarak DataTables */
        .dataTables_wrapper .row:first-child {
            padding: 1.5rem 1.5rem 0.5rem 1.5rem;
            margin: 0;
        }

        .dataTables_wrapper .row:last-child {
            padding: 1rem 1.5rem;
            margin: 0;
            border-top: 1px solid var(--cui-border-color);
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
    </style>

    {{-- HEADER PAGE --}}
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4 gap-3">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Data Master Santri</h4>
            <span class="text-muted small">Kelola basis data santri, pembagian kelas, dan akun akses</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ url('/admin/santri/naik-kelas') }}" class="btn btn-outline-secondary px-3 rounded-pill fw-bold">
                <i class="bi bi-arrow-up-circle"></i> Migrasi Kelas
            </a>
            <button class="btn btn-outline-success px-3 rounded-pill fw-bold" id="btnImportSantri">
                <i class="bi bi-file-earmark-excel"></i> Import Excel
            </button>
            <button class="btn text-white px-4 rounded-pill shadow-sm fw-bold"
                style="background: var(--islamic-purple-600);" id="btnAddSantri">
                <i class="bi bi-plus-circle-fill"></i> Tambah Santri
            </button>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            {{-- TABS FILTER KELAS --}}
            <div class="px-3 pt-2">
                <ul class="nav nav-tabs custom-tabs" id="kelasTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-kelas="" type="button">Semua Kelas</button>
                    </li>
                    @foreach ($kelasList as $kelas)
                        <li class="nav-item">
                            <button class="nav-link" data-kelas="{{ $kelas->id }}" type="button">
                                {{ $kelas->nama_kelas }}
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="table-responsive">
                <table id="santri-table" class="table table-striped table-hover align-middle w-100 mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">No.</th>
                            <th>Nama Santri</th>
                            <th>Akun User</th>
                            <th>Kelas</th>
                            <th>Musyrif Pembimbing</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- ===================== MODAL CREATE & EDIT ===================== --}}
    <div class="modal fade" id="modalSantri" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formSantri" class="w-100">
                @csrf
                <input type="hidden" name="id" id="santri_id">
                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4">
                        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
                            <i class="bi bi-person-plus-fill"></i> Tambah Santri
                        </h5>
                        <button type="button" class="btn-close bg-light rounded-circle p-2"
                            data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text bg-transparent"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" name="nama" id="nama"
                                    required placeholder="Nama santri...">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">NIS (Nomor Induk)</label>
                                <input type="text" class="form-control" name="nis" id="nis"
                                    placeholder="Opsional...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Kelamin</label>
                                <select class="form-select" name="jenis_kelamin" id="jenis_kelamin">
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="tanggal_lahir" id="tanggal_lahir">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kelas</label>
                                <select class="form-select" name="kelas_id" id="kelas_id" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($kelasList as $kelas)
                                        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Musyrif</label>
                                <select class="form-select" name="musyrif_id" id="musyrif_id">
                                    <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                                </select>
                                <div class="invalid-feedback" id="error-musyrif"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4 rounded-pill"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white px-4 rounded-pill"
                            style="background: var(--islamic-purple-600);">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL DETAIL SANTRI (REFRESHED) ===================== --}}
    <div class="modal fade" id="modalDetailSantri" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                {{-- Header dengan Background Soft --}}
                <div class="modal-header border-bottom-0 px-4">
                    <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
                        <i class="bi bi-person-vcard-fill"></i> Detail Profil Santri
                    </h5>
                    <button type="button" class="btn-close bg-light rounded-circle p-2"
                        data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    {{-- Profile Mini Header --}}
                    <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded-4 bg-light-subtle border border-dashed">
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 64px; height: 64px;">
                            <i class="bi bi-person text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-adaptive-purple fs-5" id="detail_nama">Nama Santri</h6>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 mt-1">
                                NIS: <span id="detail_nis" class="fw-bold text-dark">-</span>
                            </span>
                        </div>
                    </div>

                    {{-- Information Grid --}}
                    <div class="row g-4">
                        <div class="col-6">
                            <label class="form-label small mb-1 opacity-75">Tanggal Lahir</label>
                            <p class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-calendar-event text-primary small"></i>
                                <span id="detail_tanggal_lahir">-</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1 opacity-75">Jenis Kelamin</label>
                            <p class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-gender-ambiguous text-primary small"></i>
                                <span id="detail_jenis_kelamin">-</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1 opacity-75">Kelas Saat Ini</label>
                            <div class="mt-1">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2"
                                    id="detail_kelas">
                                    -
                                </span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small mb-1 opacity-75">Musyrif Pembimbing</label>
                            <p class="fw-bold mb-0 d-flex align-items-center gap-2 text-truncate">
                                <i class="bi bi-briefcase text-primary small"></i>
                                <span id="detail_musyrif">-</span>
                            </p>
                        </div>

                        {{-- Akun Box --}}
                        <div class="col-12 mt-4">
                            <div class="p-3 rounded-4 border bg-light-subtle shadow-sm"
                                style="border-style: dotted !important;">
                                <label class="form-label small mb-2 d-block text-uppercase fw-bold text-muted"
                                    style="letter-spacing: 1px;">
                                    Akun Akses Sistem
                                </label>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <div class="bg-white p-2 rounded-3 border shadow-xs">
                                        <i class="bi bi-person-circle text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small" id="detail_user">-</div>
                                        <div class="small text-muted" id="detail_email">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                    {{-- Tombol Tutup di Footer --}}
                    <button type="button" class="btn btn-light w-100 rounded-pill fw-bold" data-coreui-dismiss="modal">
                        Tutup Detail
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL USER ASSIGN ===================== --}}
    <div class="modal fade" id="modalUserSantri" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formUserSantri" class="w-100">
                @csrf @method('PUT')
                <input type="hidden" name="santri_id" id="user_santri_id">
                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4">
                        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check"></i> Akun Akses Santri
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small">Nama User</label>
                            <input type="text" class="form-control bg-light" name="name" id="user_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Nomor WhatsApp (Username)</label>
                            <input type="text" class="form-control" name="nomor" id="user_nomor" required
                                placeholder="0812...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">E-mail</label>
                            <input type="email" class="form-control" name="email" id="user_email" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small d-flex justify-content-between">
                                Password <span><small class="text-muted fw-normal text-lowercase">(kosongkan jika tidak
                                        ganti)</small></span>
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="user_password">
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword"><i
                                        class="bi bi-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="submit" class="btn text-white w-100 rounded-pill"
                            style="background: var(--islamic-purple-600);">Update Akun Santri</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL IMPORT EXCEL ===================== --}}
    <div class="modal fade" id="modalImportSantri" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom-0 px-4">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">Import Data Santri</h5>
                        <p class="text-white small mb-0">Upload file Excel dan petakan sheet ke kelas tujuan.</p>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light rounded-4 p-4 mb-4 border border-dashed text-center">
                        <form id="formImportUpload">
                            @csrf
                            <i class="bi bi-cloud-arrow-up text-primary" style="font-size: 2.5rem;"></i>
                            <h6 class="mt-2 fw-bold">Pilih File Master Santri</h6>
                            <input type="file" class="form-control mx-auto mt-3 mb-3" name="file" id="import_file"
                                accept=".xlsx,.xls,.csv" required style="max-width: 400px;">
                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-primary px-4 rounded-pill" type="submit"
                                    id="btnUploadReadSheet">Baca Sheet</button>
                                <button class="btn btn-outline-secondary px-4 rounded-pill" type="button"
                                    id="btnResetImport">Reset</button>
                            </div>
                        </form>
                    </div>

                    <input type="hidden" id="import_file_path">

                    <div id="importMappingArea" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-adaptive-purple"><i class="bi bi-layers me-1"></i> Mapping Kelas
                            </h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                    id="btnPreviewImport">Preview</button>
                                <button class="btn btn-success btn-sm rounded-pill px-3" id="btnProcessImport">Mulai
                                    Import</button>
                            </div>
                        </div>
                        <div class="table-responsive rounded-3 border mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 py-2">Sheet Excel</th>
                                        <th>Status</th>
                                        <th class="pe-3">Target Kelas Sistem</th>
                                    </tr>
                                </thead>
                                <tbody id="importMappingBody"></tbody>
                            </table>
                        </div>
                        <div class="alert alert-warning small d-none" id="importErrorBox"></div>
                        <div class="mt-4">
                            <h6 class="fw-bold small mb-2 text-uppercase text-muted">Preview Data (300 Baris Pertama)</h6>
                            <div class="table-responsive border rounded-3" style="max-height: 300px;">
                                <table class="table table-sm table-striped small mb-0">
                                    <thead class="sticky-top table-dark">
                                        <tr>
                                            <th>Sheet</th>
                                            <th>Kelas</th>
                                            <th>Nama</th>
                                            <th>NIS</th>
                                            <th>JK</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importPreviewBody"></tbody>
                                </table>
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
        document.addEventListener('DOMContentLoaded', function() {
            const modalSantri = new coreui.Modal(document.getElementById('modalSantri'));
            const modalImport = new coreui.Modal(document.getElementById('modalImportSantri'));
            const modalUser = new bootstrap.Modal(document.getElementById('modalUserSantri'));

            let selectedKelas = '';

            // ================================
            // INIT DATATABLES
            // ================================
            const table = $('#santri-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('santri.master.datatable') }}",
                    data: (d) => {
                        d.kelas_id = selectedKelas;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'nama',
                        name: 'santris.nama',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'akun',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kelas',
                        name: 'kelas.nama_kelas',
                        orderable: true,
                        searchable: true,
                    },
                    {
                        data: 'musyrif',
                        name: 'musyrifs.nama',
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        width: '160px',
                        className: 'text-start text-nowrap align-middle'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari santri...",
                    lengthMenu: "Tampil _MENU_",
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });

            // Filter Tabs
            $('#kelasTabs').on('click', '.nav-link', function() {
                $('#kelasTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                selectedKelas = $(this).data('kelas') || '';
                table.ajax.reload();
            });

            // Add Santri
            $('#btnAddSantri').on('click', () => {
                $('#formSantri')[0].reset();
                $('#santri_id').val('');
                $('#modalSantriTitle').html('<i class="bi bi-person-plus-fill"></i> Tambah Santri Baru');
                modalSantri.show();
            });

            // ============================================================
            // 7) LOGIK DETAIL SANTRI (POPULATE MODAL)
            // ============================================================
            $(document).on('click', '.btn-detail', function() {
                const d = $(this).data(); // Ambil semua data-* dari tombol

                // Mapping data ke elemen Modal
                $('#detail_nama').text(d.nama || '-');
                $('#detail_nis').text(d.nis || '-');
                $('#detail_tanggal_lahir').text(d.tanggal_lahir || '-');

                // Formatting Jenis Kelamin agar lebih manusiawi
                let jk = '-';
                if (d.jenis_kelamin === 'L') jk = 'Laki-laki';
                else if (d.jenis_kelamin === 'P') jk = 'Perempuan';
                $('#detail_jenis_kelamin').text(jk);

                $('#detail_kelas').text(d.kelas || '-');
                $('#detail_musyrif').text(d.musyrif || '-');

                // Logik Akun User (Jika ada gabungkan Nama + Nomor)
                let userDisplay = '-';
                if (d.userName) {
                    userDisplay = d.userName + (d.userNomor ? ` (${d.userNomor})` : '');
                }
                $('#detail_user').text(userDisplay);
                $('#detail_email').text(d.userEmail || '-');

                // Tampilkan Modal (Gunakan instance CoreUI)
                const modalDetail = new coreui.Modal(document.getElementById('modalDetailSantri'));
                modalDetail.show();
            });

            let isEditMode = false; // Flag untuk mendeteksi mode

            // ============================================================
            // LOGIK AUTO-SELECT MUSYRIF BERDASARKAN KELAS
            // ============================================================
            $('#kelas_id').on('change', function() {
                const kelasId = $(this).val();
                const musyrifSelect = $('#musyrif_id');
                const btnSubmit = $('#formSantri button[type="submit"]');

                // 1. Reset Dropdown Musyrif setiap kali kelas ganti
                musyrifSelect.html('<option value="">-- Memuat Musyrif... --</option>');
                musyrifSelect.removeClass('is-invalid');

                if (!kelasId) {
                    musyrifSelect.html('<option value="">-- Pilih Kelas Terlebih Dahulu --</option>');
                    return;
                }

                // 2. Ambil data Musyrif via AJAX
                $.get("{{ route('santri.master.get_by_kelas', '') }}/" + kelasId)
                    .done(function(res) {
                        musyrifSelect.empty(); // Kosongkan loader

                        if (res.status === 'empty') {
                            musyrifSelect.append('<option value="">-- Tidak ada Musyrif --</option>');
                            musyrifSelect.addClass('is-invalid');
                            $('#error-musyrif').text(res.message);

                            // Opsional: Kunci tombol simpan jika wajib ada Musyrif
                            btnSubmit.prop('disabled', true);
                        } else {
                            musyrifSelect.append('<option value="">-- Pilih Musyrif --</option>');

                            // 3. Loop data dan masukkan ke dropdown
                            res.data.forEach(m => {
                                musyrifSelect.append(
                                    `<option value="${m.id}">${m.nama}</option>`);
                            });

                            musyrifSelect.removeClass('is-invalid');
                            btnSubmit.prop('disabled', false);
                        }
                    })
                    .fail(function() {
                        AppAlert.error('Gagal mengambil data Musyrif.');
                    });
            });

            // ============================================================
            // UPDATE HANDLER EDIT SANTRI
            // ============================================================
            $(document).on('click', '.btn-edit', function() {
                let d = $(this).data();
                isEditMode = true; // Kunci fungsi auto-select

                // Reset Form
                $('#formSantri')[0].reset();
                $('#modalSantriTitle').html('<i class="bi bi-pencil-square"></i> Edit Data Santri');

                // Populate Fields Dasar
                $('#santri_id').val(d.id);
                $('#nama').val(d.nama);
                $('#nis').val(d.nis);
                $('#tanggal_lahir').val(d.tanggal_lahir);
                $('#jenis_kelamin').val(d.jenis_kelamin);

                // 1. Set Kelas terlebih dahulu agar dropdown Musyrif terisi sesuai kelas
                $('#kelas_id').val(d.kelas_id);
                $('#musyrif_id').val(d.musyrif_id);

                // 2. Triger manual change agar dropdown Musyrif terisi
                // Tapi kita butuh jeda agar AJAX getByKelas selesai dulu
                $('#kelas_id').trigger('change');
                modalSantri.show();

                // 3. Beri sedikit delay untuk set nilai Musyrif-nya setelah dropdown terisi, agar tidak tertimpa oleh event change kelas
                // Kembalikan ke mode normal setelah modal tampil sepenuhnya
                setTimeout(() => {
                    $('#musyrif_id').val(d.musyrif_id);
                    isEditMode = false;
                }, 500);
            });

            // Assign User Modal
            $(document).on('click', '.btn-user', function() {
                let d = $(this).data();
                $('#user_santri_id').val(d.id);
                $('#user_name').val(d.user_name || d.nama);
                $('#user_nomor').val(d.user_nomor || '');
                $('#user_email').val(d.user_email || '');
                $('#user_password').val('');
                $('#formUserSantri').attr('action', d.route);
                modalUser.show();
            });

            $('#formUserSantri').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'PUT',
                    data: $(this).serialize(),
                    success: (res) => {
                        modalUser.hide();
                        table.ajax.reload(null, false);
                        if (window.AppAlert) AppAlert.success(res.message);
                    },
                    error: (xhr) => {
                        if (window.AppAlert) AppAlert.error(xhr.responseJSON?.message ||
                            'Error');
                    }
                });
            });

            // ============================================================
            // 8) EKSESKUSI SIMPAN (CREATE & UPDATE SANTRI)
            // ============================================================
            $('#formSantri').on('submit', function(e) {
                e.preventDefault();

                const id = $('#santri_id').val();
                // Jika ada ID berarti Update (PUT), jika kosong berarti Create (POST)
                const url = id ? "{{ url('santri-master') }}/" + id : "{{ route('santri.master.store') }}";
                const method = id ? "PUT" : "POST";

                // Beri efek loading pada tombol simpan
                const btnSubmit = $(this).find('button[type="submit"]');
                const originalBtnText = btnSubmit.html();
                btnSubmit.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

                $.ajax({
                    url: url,
                    type: method,
                    data: $(this).serialize(), // Mengambil semua input form
                    success: function(res) {
                        modalSantri.hide(); // Tutup modal
                        table.ajax.reload(null, false); // Reload datatable tanpa reset paging

                        if (window.AppAlert) {
                            AppAlert.success(res.message ?? 'Data santri berhasil diperbarui.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Gagal menyimpan data.';

                        // Cek jika ada error validasi (Laravel error 422)
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            msg = Object.values(errors).map(e => e[0]).join('\n');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }

                        if (window.AppAlert) {
                            AppAlert.error(msg);
                        }
                    },
                    complete: function() {
                        // Kembalikan tombol ke kondisi semula
                        btnSubmit.prop('disabled', false).html(originalBtnText);
                    }
                });
            });

            // Delete Santri
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                if (!window.AppAlert) return;
                AppAlert.warning('Data santri dan riwayatnya akan hilang!', 'Hapus Santri?')
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: "{{ url('santri-master') }}/" + id,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: (res) => {
                                table.ajax.reload();
                                AppAlert.success(res.message);
                            }
                        });
                    });
            });

            // ==============================
            // LOGIK IMPORT EXCEL (Refactored UI)
            // ==============================
            $('#btnImportSantri').on('click', () => modalImport.show());

            $('#formImportUpload').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('santri.master.import.upload') }}",
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        $('#import_file_path').val(res.file_path);
                        $('#importMappingArea').fadeIn();
                        const tbody = $('#importMappingBody').html('');
                        res.sheets.forEach(s => {
                            const disabled = s.is_valid ? '' : 'disabled';
                            tbody.append(`
                                <tr>
                                    <td class="ps-3">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input sheet-check" data-index="${s.sheet_index}" ${disabled}>
                                            <label class="form-check-label small fw-bold">${s.label}</label>
                                            <div class="text-muted" style="font-size:10px">Baris: ${s.rows}</div>
                                        </div>
                                    </td>
                                    <td><span class="badge ${s.is_valid ? 'bg-success' : 'bg-secondary'} px-2">${s.is_valid ? 'Valid' : 'No Name Col'}</span></td>
                                    <td class="pe-3">
                                        <select class="form-select form-select-sm kelas-select" data-index="${s.sheet_index}" disabled>
                                            <option value="">-- pilih kelas --</option>
                                            @foreach ($kelasList as $kelas)
                                                <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            `);
                        });
                    }
                });
            });

            $(document).on('change', '.sheet-check', function() {
                const idx = $(this).data('index');
                $(`.kelas-select[data-index="${idx}"]`).prop('disabled', !$(this).is(':checked'));
            });

            // ============================================================
            // 6) PREVIEW IMPORT (JS UNTUK ISI #importPreviewBody)
            // ============================================================
            $('#btnPreviewImport').on('click', function() {
                const filePath = $('#import_file_path').val();
                const selections = {};
                let hasSelection = false;

                // Kumpulkan sheet yang dicentang + kelas yang dipilih
                $('.sheet-check:checked').each(function() {
                    const idx = $(this).data('index');
                    const kelasId = $(`.kelas-select[data-index="${idx}"]`).val();
                    if (kelasId) {
                        selections[idx] = {
                            kelas_id: kelasId
                        };
                        hasSelection = true;
                    }
                });

                if (!hasSelection) {
                    return AppAlert?.error('Pilih minimal satu sheet dan tentukan kelasnya!');
                }

                // Tampilkan loading kecil di tombol biar user tahu sistem kerja
                const btn = $(this);
                const originalText = btn.html();
                btn.html('<span class="spinner-border spinner-border-sm"></span> Loading...').prop(
                    'disabled', true);

                $.ajax({
                    url: "{{ route('santri.master.import.preview') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        file_path: filePath,
                        selections: selections
                    },
                    success: function(res) {
                        // Tampilkan error jika ada validasi dari Excel (misal kolom nama kosong)
                        if (res.errors && res.errors.length > 0) {
                            $('#importErrorBox').removeClass('d-none').html(
                                '<strong>Peringatan:</strong><br><ul>' +
                                res.errors.map(e => `<li>${e}</li>`).join('') +
                                '</ul>'
                            );
                        } else {
                            $('#importErrorBox').addClass('d-none');
                        }

                        // ISI TABEL PREVIEW
                        const pb = $('#importPreviewBody').html('');
                        (res.preview || []).forEach(row => {
                            pb.append(`
                    <tr>
                        <td class="small">${row.sheet}</td>
                        <td class="small fw-bold text-primary">${row.kelas_nama ?? 'ID:'+row.kelas_id}</td>
                        <td>${row.nama || '-'}</td>
                        <td class="text-muted small">${row.nis || '-'}</td>
                        <td><span class="badge bg-light text-dark">${row.jenis_kelamin || '-'}</span></td>
                    </tr>
                `);
                        });

                        if (window.AppAlert) AppAlert.success('Preview berhasil dimuat!');
                    },
                    error: function(xhr) {
                        if (window.AppAlert) AppAlert.error(xhr.responseJSON?.message ||
                            'Gagal memuat preview.');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });

            $('#btnProcessImport').on('click', function() {
                const selections = {};
                $('.sheet-check:checked').each(function() {
                    const idx = $(this).data('index');
                    selections[idx] = {
                        kelas_id: $(`.kelas-select[data-index="${idx}"]`).val()
                    };
                });

                $.ajax({
                    url: "{{ route('santri.master.import.process') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        file_path: $('#import_file_path').val(),
                        selections: selections
                    },
                    success: (res) => {
                        modalImport.hide();
                        table.ajax.reload();
                        AppAlert.success(`Berhasil import ${res.inserted} santri.`);
                    }
                });
            });
        });
    </script>
@endpush
