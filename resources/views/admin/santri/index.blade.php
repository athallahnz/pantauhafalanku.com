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
                {{-- Tambahkan ini sebagai placeholder, nanti JS yang akan mengubahnya --}}
                <input type="hidden" name="_method" id="form_method" value="POST">
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
                        {{-- Info box tambahan --}}
                        <div
                            class="alert alert-info py-2 small d-flex align-items-center gap-2 border-0 bg-info bg-opacity-10 text-info-emphasis rounded-3 mb-3">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Santri dapat login menggunakan <b>NIS</b>, <b>Nomor WA</b>, atau <b>Email</b>.</span>
                        </div>
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
                @csrf
                <input type="hidden" name="santri_id" id="user_santri_id">
                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4">
                        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check"></i> Akun Akses Santri
                        </h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        {{-- Info box tambahan --}}
                        <div
                            class="alert alert-info py-2 small d-flex align-items-center gap-2 border-0 bg-info bg-opacity-10 text-info-emphasis rounded-3 mb-3">
                            <i class="bi bi-info-circle-fill"></i>
                            <span>Santri dapat login menggunakan <b>NIS</b>, <b>Nomor WA</b>, atau <b>Email</b>.</span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Nama User</label>
                            <input type="text" class="form-control bg-light" name="name" id="user_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Nomor WhatsApp (Optional)</label>
                            <input type="text" class="form-control" name="nomor" id="user_nomor"
                                placeholder="0812...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">E-mail (Optional)</label>
                            <input type="email" class="form-control" name="email" id="user_email">
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
                            style="background: var(--islamic-purple-600);">
                            Update Akun Santri
                        </button>
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
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === 1. MODAL INSTANCES (CoreUI & Bootstrap) ===
            const modalSantri = new coreui.Modal(document.getElementById('modalSantri'));
            const modalImport = new coreui.Modal(document.getElementById('modalImportSantri'));
            const modalDetail = new coreui.Modal(document.getElementById('modalDetailSantri'));
            const modalUser = new coreui.Modal(document.getElementById('modalUserSantri'));

            let selectedKelas = '';

            // === 2. DATATABLES INITIALIZATION ===
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
                        name: 'santris.nama'
                    },
                    {
                        data: 'akun',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kelas',
                        name: 'kelas.nama_kelas'
                    },
                    {
                        data: 'musyrif',
                        name: 'musyrifs.nama'
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

            // Filter Tabs logic
            $('#kelasTabs').on('click', '.nav-link', function() {
                $('#kelasTabs .nav-link').removeClass('active');
                $(this).addClass('active');
                selectedKelas = $(this).data('kelas') || '';
                table.ajax.reload();
            });

            // === 3. CORE FUNCTIONS (SANTRI CRUD) ===

            // Trigger Add Modal
            $('#btnAddSantri').on('click', () => {
                $('#formSantri')[0].reset();
                $('#santri_id').val('');
                $('#modalSantriTitle').html('<i class="bi bi-person-plus-fill"></i> Tambah Santri Baru');
                $('#musyrif_id').html('<option value="">-- Pilih Kelas Terlebih Dahulu --</option>');
                modalSantri.show();
            });

            // Trigger Edit Modal (Refactored to handle Async Dropdown)
            $(document).on('click', '.btn-edit', function() {
                const d = $(this).data();
                $('#formSantri')[0].reset();
                $('#modalSantriTitle').html('<i class="bi bi-pencil-square"></i> Edit Data Santri');

                // Populate Static Fields
                $('#santri_id').val(d.id);
                $('#nama').val(d.nama);
                $('#nis').val(d.nis);
                $('#tanggal_lahir').val(d.tanggal_lahir);
                $('#jenis_kelamin').val(d.jenis_kelamin);
                $('#kelas_id').val(d.kelas_id);

                // Populate Dynamic Dropdown (Musyrif)
                const musyrifSelect = $('#musyrif_id');
                musyrifSelect.html('<option value="">-- Memuat Musyrif... --</option>');

                $.get("{{ route('santri.master.get_by_kelas', '') }}/" + d.kelas_id)
                    .done(function(res) {
                        musyrifSelect.empty().append('<option value="">-- Pilih Musyrif --</option>');
                        if (res.data) {
                            res.data.forEach(m => {
                                musyrifSelect.append(
                                    `<option value="${m.id}">${m.nama}</option>`);
                            });
                            // SET VALUE MUSYRIF setelah data dipastikan termuat
                            musyrifSelect.val(d.musyrif_id);
                        }
                    });

                modalSantri.show();
            });

            // Handle Kelas Change (Auto-populate Musyrif)
            $('#kelas_id').on('change', function() {
                const kelasId = $(this).val();
                const musyrifSelect = $('#musyrif_id');
                if (!kelasId) return musyrifSelect.html(
                    '<option value="">-- Pilih Kelas Terlebih Dahulu --</option>');

                musyrifSelect.html('<option value="">-- Memuat Musyrif... --</option>');
                $.get("{{ route('santri.master.get_by_kelas', '') }}/" + kelasId).done(function(res) {
                    musyrifSelect.empty();
                    if (res.status === 'empty') {
                        musyrifSelect.append('<option value="">-- Tidak ada Musyrif --</option>');
                    } else {
                        musyrifSelect.append('<option value="">-- Pilih Musyrif --</option>');
                        res.data.forEach(m => musyrifSelect.append(
                            `<option value="${m.id}">${m.nama}</option>`));
                    }
                });
            });

            $('#formSantri').on('submit', function(e) {
                e.preventDefault();

                // 1. Ambil ID
                const id = $('#santri_id').val();
                const btnSubmit = $(this).find('button[type="submit"]');
                const originalText = btnSubmit.html();

                // 2. URL Dinamis
                let url = id ? "{{ url('santri-master') }}/" + id : "{{ route('santri.master.store') }}";

                // 3. Siapkan data form dalam bentuk Array (lebih aman dari .serialize() biasa)
                let dataArray = $(this).serializeArray();

                // BERSIHKAN jika ada _method yang nyangkut/terselip dari HTML
                dataArray = dataArray.filter(item => item.name !== '_method');

                // 4. Tambahkan Spoofing PUT secara manual JIKA HANYA ada ID (Edit Mode)
                if (id && id !== "") {
                    dataArray.push({
                        name: "_method",
                        value: "PUT"
                    });
                }

                btnSubmit.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span>');

                $.ajax({
                    url: url,
                    type: "POST", // Tetap POST
                    data: $.param(dataArray), // Convert kembali jadi string aman
                    success: function(res) {
                        modalSantri.hide();
                        table.ajax.reload(null, false);
                        if (window.AppAlert) AppAlert.success(res.message);
                    },
                    error: function(xhr) {
                        let msg = 'Gagal menyimpan data.';
                        if (xhr.status === 422) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        } else if (xhr.status === 405) {
                            msg =
                                `Error 405: Sistem mencoba mengakses [${url}] dengan Method yang salah.`;
                            console.error("405 Payload Data:", $.param(dataArray));
                        }
                        if (window.AppAlert) AppAlert.error(msg);
                    },
                    complete: function() {
                        btnSubmit.prop('disabled', false).html(originalText);
                    }
                });
            });

            // === 4. DETAIL & DELETE FUNCTIONS ===
            $(document).on('click', '.btn-detail', function() {
                const btn = $(this); // Gunakan $(this) langsung

                // Ambil data menggunakan fungsi .data('nama-atribut') secara spesifik
                const nama = btn.data('nama') || '-';
                const nis = btn.data('nis') || '-';
                const tglLahir = btn.data('tanggal_lahir') || '-';
                const jk = btn.data('jenis_kelamin');
                const kelas = btn.data('kelas') || '-';
                const musyrif = btn.data('musyrif') || '-';

                // Akun User (Perhatikan pemanggilan data attribute yang pakai strip)
                const userName = btn.data('user-name');
                const userNomor = btn.data('user-nomor');
                const userEmail = btn.data('user-email') || '-';

                // 1. Set Profil Utama
                $('#detail_nama').text(nama);
                $('#detail_nis').text(nis);
                $('#detail_tanggal_lahir').text(tglLahir);
                $('#detail_kelas').text(kelas);
                $('#detail_musyrif').text(musyrif);

                // 2. Format Jenis Kelamin
                let jkText = '-';
                if (jk === 'L') jkText = 'Laki-laki';
                else if (jk === 'P') jkText = 'Perempuan';
                $('#detail_jenis_kelamin').text(jkText);

                // 3. Format Tampilan Akun User
                let userDisplay = '-';
                if (userName) {
                    userDisplay = userName;
                    if (userNomor) {
                        userDisplay += ` (${userNomor})`; // Gabungkan nama dan nomor
                    }
                }
                $('#detail_user').text(userDisplay);
                $('#detail_email').text(userEmail);

                // 4. Tampilkan Modal
                modalDetail.show();
            });

            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                AppAlert.warning('Data santri dan riwayatnya akan dihapus permanen!', 'Hapus Santri?')
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: "{{ url('santri-master') }}/" + id,
                            type: "POST",
                            data: {
                                _token: "{{ csrf_token() }}",
                                _method: "DELETE"
                            },
                            success: (res) => {
                                table.ajax.reload();
                                AppAlert.success(res.message);
                            }
                        });
                    });
            });

            // === 5. USER ACCOUNT FUNCTIONS ===
            $(document).on('click', '.btn-user', function() {
                const d = $(this).data();

                // Set field values (Perhatikan format camelCase dari atribut data-user-* HTML)
                $('#user_santri_id').val(d.id);
                $('#user_name').val(d.userName || d.nama); // Fallback ke nama santri jika belum punya akun
                $('#user_nomor').val(d.userNomor || '');
                $('#user_email').val(d.userEmail || '');
                $('#user_password').val('');

                // --- LOGIKA UI CERDAS (CREATE vs UPDATE) ---
                const isUpdate = !!d.userId; // Jika d.userId ada isinya, berarti Update
                const btnSubmit = $('#formUserSantri').find('button[type="submit"]');
                const passwordLabel = $('#user_password').closest('.mb-0').find('.text-muted');

                if (isUpdate) {
                    // Mode UPDATE
                    btnSubmit.html('Update Akun Santri');
                    $('#user_password').removeAttr('required'); // Password opsional saat update
                    passwordLabel.html('(kosongkan jika tidak ganti)');
                } else {
                    // Mode CREATE
                    btnSubmit.html('Buat Akun Santri');
                    $('#user_password').attr('required', true); // Password wajib saat bikin akun baru
                    passwordLabel.html('(wajib diisi untuk akun baru)');
                }
                // ------------------------------------------

                // Set URL action secara manual di state/variabel
                const updateUrl = "{{ url('santri-master') }}/" + d.id + "/assign-user";
                $('#formUserSantri').data('action-url', updateUrl);

                modalUser.show();
            });

            // Submit Form (Tetap sama seperti milik Anda)
            $('#formUserSantri').on('submit', function(e) {
                e.preventDefault();

                const btnSubmit = $(this).find('button[type="submit"]');
                const originalText = btnSubmit.html();
                const targetUrl = $(this).data('action-url');

                if (!targetUrl) {
                    return AppAlert.error("Error sistem: URL Update User tidak ditemukan.");
                }

                btnSubmit.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

                let formData = $(this).serialize();
                formData += "&_method=PUT";

                $.ajax({
                    url: targetUrl,
                    type: 'POST',
                    data: formData,
                    success: (res) => {
                        modalUser.hide();
                        table.ajax.reload(null, false);
                        if (window.AppAlert) AppAlert.success(res.message);
                    },
                    error: (xhr) => {
                        let msg = 'Gagal update akun.';
                        if (xhr.status === 422) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        } else if (xhr.status === 405) {
                            msg = "Method 405: URL tidak sesuai dengan Route PUT.";
                        }
                        if (window.AppAlert) AppAlert.error(msg);
                    },
                    complete: function() {
                        btnSubmit.prop('disabled', false).html(originalText);
                    }
                });
            });

            $('#togglePassword').on('click', function() {
                const input = $('#user_password');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('bi-eye bi-eye-slash');
            });

            // === 6. IMPORT EXCEL FUNCTIONS ===
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

            $('#btnPreviewImport').on('click', function() {
                const selections = {};
                let hasSelection = false;
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

                if (!hasSelection) return AppAlert?.error('Pilih sheet dan kelasnya!');

                const btn = $(this);
                const originalText = btn.html();
                btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);

                $.ajax({
                    url: "{{ route('santri.master.import.preview') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        file_path: $('#import_file_path').val(),
                        selections: selections
                    },
                    success: function(res) {
                        if (res.errors?.length > 0) {
                            $('#importErrorBox').removeClass('d-none').html('<ul>' + res.errors
                                .map(e => `<li>${e}</li>`).join('') + '</ul>');
                        } else {
                            $('#importErrorBox').addClass('d-none');
                        }
                        const pb = $('#importPreviewBody').html('');
                        (res.preview || []).forEach(row => {
                            pb.append(
                                `<tr><td class="small">${row.sheet}</td><td class="small fw-bold text-primary">${row.kelas_nama ?? 'ID:'+row.kelas_id}</td><td>${row.nama || '-'}</td><td class="text-muted small">${row.nis || '-'}</td><td><span class="badge bg-light text-dark">${row.jenis_kelamin || '-'}</span></td></tr>`
                            );
                        });
                        AppAlert.success('Preview dimuat!');
                    },
                    complete: () => btn.html(originalText).prop('disabled', false)
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
