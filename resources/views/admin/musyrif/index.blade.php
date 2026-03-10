@extends('layouts.app')

@section('title', 'Data Musyrif')

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

        /* Styling Khusus Checkbox */
        .form-check-input:checked {
            background-color: var(--islamic-purple-600);
            border-color: var(--islamic-purple-600);
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

        #tablePreview {
            display: block;
            max-height: 300px;
            overflow-y: auto;
            font-size: 0.75rem;
        }

        #tablePreview th {
            position: sticky;
            top: 0;
            background: var(--cui-tertiary-bg);
            z-index: 1;
        }

        #importMappingBody tr:hover {
            background-color: var(--cui-tertiary-bg);
        }

        .bg-light-subtle {
            background-color: rgba(var(--cui-light-rgb), 0.5) !important;
        }

        #importPreviewBody td {
            white-space: nowrap;
        }

        .invalid-feedback {
            display: block;
            /* Memastikan pesan muncul karena input-group kadang menyembunyikannya */
            font-size: 0.75rem;
        }

        .is-invalid {
            border-color: #dc3545 !important;
        }
    </style>

    {{-- HEADER PAGE --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Data Musyrif</h4>
            <span class="text-muted small">Kelola daftar pembimbing dan pantau kehadiran harian</span>
        </div>
        <div class="col-12 col-md-auto ms-auto d-flex gap-2">
            <button class="btn btn-outline-success rounded-pill px-4 shadow-sm fw-bold" data-coreui-toggle="modal"
                data-coreui-target="#modalImport">
                <i class="bi bi-file-earmark-excel"></i> Import Excel
            </button>
            <button class="btn text-white px-4 rounded-pill shadow-sm fw-bold" style="background: var(--islamic-purple-600);"
                id="btnAddMusyrif">
                <i class="bi bi-plus-lg"></i> Tambah Musyrif
            </button>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="musyrif-table" class="table table-striped table-hover align-middle w-100 mb-0 text-nowrap">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width:60px;">No.</th>
                            <th>Nama Musyrif</th>
                            <th>Akun (User)</th>
                            <th>Kelas</th>
                            <th>Pagi (Hari ini)</th>
                            <th>Sore (Hari ini)</th>
                            <th>Rekap Bulan Ini</th>
                            <th class="text-end pe-4" style="width:160px;">Aksi</th>
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
    <div class="modal fade" id="modalMusyrif" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formMusyrif" class="w-100">
                @csrf
                <input type="hidden" id="musyrif_id">
                <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 px-4">
                        <h5 class="modal-title fw-bold text-white d-flex align-items-center gap-2" id="modalMusyrifTitle">
                            <i class="bi bi-person-badge-fill"></i> Tambah Musyrif
                        </h5>
                        <button type="button" class="btn-close bg-light rounded-circle p-2"
                            data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            {{-- BAGIAN 1: IDENTITAS UTAMA --}}
                            <div class="col-md-8">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" name="nama" id="nama" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kode/NIP</label>
                                <input type="text" class="form-control" name="kode" id="kode">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tugas di Kelas</label>
                                <select class="form-select" name="kelas_id" id="kelas_id">
                                    <option value="">-- Pilih Kelas --</option>
                                    @foreach ($listKelas as $k)
                                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pendidikan Terakhir</label>
                                <select class="form-select" name="pendidikan_terakhir" id="pendidikan_terakhir">
                                    <option value="">-- Pilih --</option>
                                    <option value="SMA">SMA/Sederajat</option>
                                    <option value="D3">D3</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                </select>
                            </div>

                            {{-- BAGIAN 2: DETAIL TUGAS & DOMISILI --}}
                            <div class="col-md-6">
                                <label class="form-label">Program Halaqah</label>
                                <select class="form-select" name="halaqah" id="halaqah">
                                    <option value="Reguler">Reguler</option>
                                    <option value="Takhassus">Takhassus</option>
                                    <option value="Pengganti">Pengganti</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Domisili</label>
                                <select class="form-select" name="domisili" id="domisili">
                                    <option value="Dalam Pondok (Mukim)">Dalam Pondok (Mukim)</option>
                                    <option value="Luar Pondok (Pulang-Pergi)">Luar Pondok (Pulang-Pergi)</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea class="form-control" name="alamat" id="alamat" rows="2"></textarea>
                            </div>
                            {{-- BAGIAN BARU: KETERANGAN --}}
                            <div class="col-12">
                                <label class="form-label">Keterangan Tambahan</label>
                                <textarea class="form-control" name="keterangan" id="keterangan" rows="2"
                                    placeholder="Catatan khusus tentang musyrif ini..."></textarea>
                            </div>
                            {{-- BAGIAN 3: SERTIFIKASI --}}
                            <div class="col-md-12">
                                <hr class="my-2">
                                <h6 class="fw-bold text-adaptive-purple mb-3">Informasi Sertifikasi Al-Qur'an</h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode</label>
                                <input type="text" class="form-control" name="metode_alquran" id="metode_alquran"
                                    placeholder="Contoh: Ummi, Wafa">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun Sertifikasi</label>
                                <input type="number" class="form-control" name="tahun_sertifikasi"
                                    id="tahun_sertifikasi" placeholder="Contoh: 2024">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_sertifikasi_ummi"
                                        id="is_sertifikasi_ummi" value="1">
                                    <label class="form-check-label small fw-bold" for="is_sertifikasi_ummi">SUDAH
                                        SERTIFIKASI UMMI</label>
                                </div>
                            </div>



                            {{-- AKUN LOGIN SECTION --}}
                            <div class="col-12 mt-2">
                                <div class="bg-light rounded-3 p-3 border border-dashed">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="create_user"
                                            style="cursor: pointer;">
                                        <label class="form-check-label fw-bold small text-adaptive-purple"
                                            for="create_user" style="cursor: pointer;">
                                            PENGATURAN AKUN LOGIN
                                        </label>
                                    </div>

                                    <div id="createUserFields" class="d-none mt-3">
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label small">Email Login</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                                    <input type="email" class="form-control" name="email"
                                                        id="email" placeholder="email@contoh.com">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small">Password</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                                                    <input type="password" class="form-control" name="password"
                                                        id="password" placeholder="Min. 8 karakter">
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        id="togglePassword">
                                                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-light px-4 rounded-pill"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn text-white px-4 rounded-pill shadow-sm"
                            style="background: var(--islamic-purple-600);" id="btnSaveMusyrif">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL DETAIL PROFIL ===================== --}}
    <div class="modal fade" id="modalDetailMusyrif" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-info text-white border-bottom-0 px-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Detail Profil Musyrif</h5>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="det_id_hidden">
                    <input type="hidden" id="det_kelas_id_hidden">
                    <div class="text-center mb-4">
                        <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                            style="width: 80px; height: 80px;">
                            <i class="bi bi-person-badge text-info fs-1"></i>
                        </div>
                        <h4 class="fw-bold mb-0" id="det_nama"></h4>
                        <span class="badge bg-secondary rounded-pill" id="det_kode"></span>
                    </div>

                    <div class="row g-4">
                        {{-- Info Tugas --}}
                        <div class="col-md-6">
                            <h6 class="fw-bold text-info border-bottom pb-2"><i class="bi bi-briefcase me-2"></i>Penugasan
                            </h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted small w-50">Halaqah / Program</td>
                                    <td class="fw-bold" id="det_halaqah"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Tugas di Kelas</td>
                                    <td class="fw-bold" id="det_kelas"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Domisili</td>
                                    <td class="fw-bold" id="det_domisili"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Amanah Lain</td>
                                    <td id="det_amanah"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Keterangan</td>
                                    <td id="det_keterangan"></td>
                                </tr>
                            </table>
                        </div>

                        {{-- Info Sertifikasi --}}
                        <div class="col-md-6">
                            <h6 class="fw-bold text-success border-bottom pb-2"><i
                                    class="bi bi-patch-check me-2"></i>Kualifikasi Al-Qur'an</h6>
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted small w-50">Metode</td>
                                    <td class="fw-bold" id="det_metode"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Status Ummi</td>
                                    <td class="fw-bold" id="det_sertifikasi"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted small">Tahun Lulus</td>
                                    <td class="fw-bold" id="det_tahun"></td>
                                </tr>
                            </table>
                        </div>

                        {{-- Info Kontak --}}
                        <div class="col-md-12">
                            <h6 class="fw-bold text-adaptive-purple border-bottom pb-2"><i
                                    class="bi bi-telephone me-2"></i>Kontak & Alamat</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1 small text-muted">WhatsApp / Email</p>
                                    <p class="fw-bold mb-0"><i class="bi bi-whatsapp text-success me-1"></i> <span
                                            id="det_nomor"></span></p>
                                    <p class="small text-muted" id="det_email"></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1 small text-muted">Alamat</p>
                                    <p class="small" id="det_alamat"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill"
                        data-coreui-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-warning text-white px-4 rounded-pill" id="btnEditFromDetail">
                        <i class="bi bi-pencil-fill me-1"></i> Edit Profil
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL IMPORT EXCEL ===================== --}}
    <div class="modal fade" id="modalImport" tabindex="-1" data-coreui-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 px-4 bg-primary text-white">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Import Data Musyrif</h5>
                        <p class="small mb-0 opacity-75">Upload file Excel dan petakan sheet untuk diimport ke sistem.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    {{-- STEP 1: UPLOAD AREA --}}
                    <div class="bg-light rounded-4 p-4 mb-4 border border-dashed text-center" id="importUploadBox">
                        <form id="formImportUpload">
                            @csrf
                            <i class="bi bi-file-earmark-excel text-success" style="font-size: 3rem;"></i>
                            <h6 class="mt-3 fw-bold">Pilih File Master Musyrif</h6>
                            <p class="text-muted small">Pastikan file memiliki header kolom yang sesuai</p>

                            <input type="file" class="form-control mx-auto mt-2 mb-3 shadow-sm" name="file"
                                id="import_file" accept=".xlsx,.xls,.csv" required
                                style="max-width: 450px; border-radius: 10px;">

                            <div class="d-flex justify-content-center gap-2">
                                <button class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm" type="submit"
                                    id="btnUploadReadSheet">
                                    <i class="bi bi-search me-1"></i> Baca Isi File
                                </button>
                                <button class="btn btn-outline-secondary px-4 rounded-pill fw-bold" type="button"
                                    id="btnResetImport">
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    <input type="hidden" id="import_file_path">

                    {{-- STEP 2: MAPPING & PREVIEW AREA (Hidden by default) --}}
                    <div id="importMappingArea" style="display:none;" class="animate__animated animate__fadeIn">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0 text-adaptive-purple">
                                <i class="bi bi-list-check me-1"></i> Pilih Sheet & Verifikasi Data
                            </h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold"
                                    id="btnPreviewImport">
                                    <i class="bi bi-eye me-1"></i> Preview
                                </button>
                                <button class="btn btn-success btn-sm text-white rounded-pill px-4 fw-bold shadow-sm"
                                    id="btnProcessImport">
                                    <i class="bi bi-cloud-download me-1"></i> Mulai Import
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive rounded-3 border mb-4">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase fw-bold text-muted">
                                        <th class="ps-3 py-3">Nama Sheet di Excel</th>
                                        <th>Info Baris</th>
                                        <th>Status Header</th>
                                        <th class="pe-3 text-center">Pilih untuk Import</th>
                                    </tr>
                                </thead>
                                <tbody id="importMappingBody">
                                    {{-- Akan diisi via AJAX --}}
                                </tbody>
                            </table>
                        </div>

                        {{-- PREVIEW DATA --}}
                        <div class="mt-4">
                            <h6 class="fw-bold small mb-2 text-uppercase text-muted" style="letter-spacing: 1px;">
                                Preview Data (10 Baris Pertama)
                            </h6>
                            <div class="table-responsive border rounded-4 shadow-sm" style="max-height: 350px;">
                                <table class="table table-sm table-striped small mb-0">
                                    <thead class="sticky-top table-dark shadow-sm">
                                        <tr id="previewHeader">
                                            {{-- Header Dinamis --}}
                                        </tr>
                                    </thead>
                                    <tbody id="importPreviewBody">
                                        <tr>
                                            <td class="text-center py-4 text-muted">Klik "Preview" untuk melihat data
                                                sebelum diimport</td>
                                        </tr>
                                    </tbody>
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
            // ==========================================
            // 1. INISIALISASI KOMPONEN & VARIABLE
            // ==========================================
            const modalEl = document.getElementById('modalMusyrif');
            const modalMusyrif = new coreui.Modal(modalEl);
            const modalDetail = new coreui.Modal(document.getElementById('modalDetailMusyrif'));
            const modalImport = new coreui.Modal(document.getElementById('modalImport'));

            let tempPath = ''; // Menyimpan path file sementara untuk import

            // Init DataTable
            const table = $('#musyrif-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.musyrif.data') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
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
                        data: 'kelas',
                        name: 'musyrifs.kelas_id',
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
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
                ],
                order: [
                    [1, 'asc']
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari musyrif...",
                    lengthMenu: "Tampil _MENU_ data",
                    info: "Menampilkan _START_ s/d _END_ dari _TOTAL_ musyrif",
                    paginate: {
                        previous: "<i class='bi bi-chevron-left'></i>",
                        next: "<i class='bi bi-chevron-right'></i>"
                    }
                }
            });

            // ==========================================
            // 2. FUNGSI HELPER
            // ==========================================
            function resetForm() {
                $('#formMusyrif')[0].reset();
                $('#musyrif_id').val('');
                $('#modalMusyrifTitle').html('<i class="bi bi-person-badge-fill"></i> Tambah Musyrif');
                $('#btnSaveMusyrif').text('Simpan Musyrif');
                $('#keterangan').val('');
                $('#create_user').prop('checked', false);
                $('#createUserFields').addClass('d-none');
                $('#pickUserFields').removeClass('d-none');
            }

            function loadPreview(index) {
                const previewContainer = $('#tablePreview');
                // Berikan feedback loading yang manis
                previewContainer.html(
                    '<tr><td colspan="100%" class="text-center p-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data...</td></tr>'
                );

                $.post("{{ route('admin.musyrif.sheet_preview') }}", {
                    _token: "{{ csrf_token() }}",
                    temp_path: tempPath,
                    sheet_index: index
                }).done(function(res) {
                    if (!res.headers || res.headers.length === 0) {
                        previewContainer.html(
                            '<tr><td colspan="100%" class="text-center p-4 text-danger">Sheet kosong atau tidak ada header</td></tr>'
                        );
                        return;
                    }

                    // 1. Render Header dengan pengaman tipe data
                    let html = '<thead class="table-light"><tr>';
                    res.headers.forEach(h => {
                        // Pastikan h dikonversi ke string sebelum toUpperCase
                        let headerText = (h !== null && h !== undefined) ? String(h) :
                            'KOLOM TANPA NAMA';
                        html +=
                            `<th class="text-nowrap small fw-bold">${headerText.toUpperCase().replace(/_/g, ' ')}</th>`;
                    });
                    html += '</tr></thead><tbody>';

                    // 2. Render Body Data
                    if (res.preview && res.preview.length > 0) {
                        res.preview.forEach(row => {
                            html += '<tr>';
                            res.headers.forEach(key => {
                                // Ambil nilai, jika null tampilkan tanda strip
                                let val = (row[key] !== null && row[key] !== undefined) ?
                                    row[key] : '-';
                                html += `<td class="small text-nowrap">${val}</td>`;
                            });
                            html += '</tr>';
                        });
                    } else {
                        html +=
                            `<tr><td colspan="${res.headers.length}" class="text-center p-3 text-muted">Tidak ada data di sheet ini</td></tr>`;
                    }

                    html += '</tbody>';
                    previewContainer.html(html);
                }).fail(function(xhr) {
                    let errorMsg = xhr.responseJSON?.message || 'Gagal memuat preview sheet';
                    previewContainer.html(
                        `<tr><td colspan="100%" class="text-center p-4 text-danger"><i class="bi bi-exclamation-triangle me-2"></i> ${errorMsg}</td></tr>`
                    );
                });
            }

            // ==========================================
            // 3. EVENT HANDLERS (CRUD)
            // ==========================================
            $(document).on('click', '.btnDetail', function() {
                const id = $(this).data('id');

                $.get("{{ route('admin.musyrif.show', ':id') }}".replace(':id', id))
                    .done(function(res) {
                        // Mapping Data ke Modal Detail
                        $('#det_id_hidden').val(res.id);
                        $('#det_kelas_id_hidden').val(res.kelas_id); // Simpan juga kelasnya
                        $('#det_nama').text(res.nama);
                        $('#det_kode').text(res.kode);

                        // PAKAI res.nama_kelas
                        $('#det_kelas').text(res.nama_kelas);

                        $('#det_halaqah').text(res.halaqah);
                        $('#det_domisili').text(res.domisili);
                        $('#det_amanah').text(res.amanah_lain);
                        $('#det_keterangan').text(res.keterangan || '-');

                        // PAKAI res.metode_alquran
                        $('#det_metode').text(res.metode_alquran);

                        $('#det_sertifikasi').html(res.is_sertifikasi_ummi == 1 ?
                            '<span class="text-success fw-bold">Sudah Sertifikasi</span>' :
                            '<span class="text-danger">Belum</span>');

                        $('#det_tahun').text(res.tahun_sertifikasi);
                        $('#det_nomor').text(res.nomor);
                        $('#det_email').text(res.email);
                        $('#det_alamat').text(res.alamat);

                        modalDetail.show();
                    });
            });

            $(document).on('click', '#togglePassword', function() {
                const passwordField = $('#password');
                const eyeIcon = $('#eyeIcon');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';

                passwordField.attr('type', type);
                eyeIcon.toggleClass('bi-eye-slash bi-eye');
            });

            $(document).on('click', '.btnEdit', function() {
                const id = $(this).data('id');
                const kelasId = $(this).data('kelas_id'); // Ambil kelas_id dari tombol yang diklik

                resetForm();

                $('#modalMusyrifTitle').html('<i class="bi bi-pencil-square"></i> Edit Data Musyrif');
                $('#btnSaveMusyrif').text('Update Musyrif');

                // Langsung set kelas_id di awal agar user tidak melihat dropdown kosong
                if (kelasId) {
                    $('#kelas_id').val(kelasId);
                }

                $.get("{{ route('admin.musyrif.show', ':id') }}".replace(':id', id))
                    .done(function(res) {
                        // Isi field profil (Nama, Kode, dsb)
                        $('#musyrif_id').val(res.id);
                        $('#nama').val(res.nama);
                        $('#kode').val(res.kode);
                        if (res.kelas_id) {
                            $('#kelas_id').val(res.kelas_id);
                        }
                        $('#alamat').val(res.alamat);
                        $('#pendidikan_terakhir').val(res.pendidikan_terakhir);
                        $('#domisili').val(res.domisili);
                        $('#halaqah').val(res.halaqah);
                        $('#metode_alquran').val(res.metode_alquran);
                        $('#tahun_sertifikasi').val(res.tahun_sertifikasi);
                        $('#is_sertifikasi_ummi').prop('checked', res.is_sertifikasi_ummi == 1);
                        $('#keterangan').val(res.keterangan);

                        // LOGIKA AKUN LOGIN EXISTING
                        if (res.user_id || res.email !== '-') {
                            $('#create_user').prop('checked', true);
                            $('#createUserFields').removeClass(
                                'd-none'); // Paksa muncul tanpa nunggu fade
                            $('#email').val(res.email);
                            $('#password').val(''); // Password dikosongkan saat edit
                            $('#password').attr('placeholder', 'Isi jika ingin ganti pass');
                        } else {
                            $('#create_user').prop('checked', false);
                            $('#createUserFields').addClass('d-none');
                        }

                        modalMusyrif.show();
                    });
            });

            // Pastikan handler switch tetap ada untuk handle klik manual
            $('#create_user').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#createUserFields').removeClass('d-none').hide().fadeIn();
                } else {
                    $('#createUserFields').fadeOut(() => $(this).addClass('d-none'));
                }
            });

            $('#create_user').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#createUserFields').removeClass('d-none').hide().fadeIn();
                    $('#pickUserFields').fadeOut(() => $('#pickUserFields').addClass('d-none'));
                } else {
                    $('#createUserFields').fadeOut(() => $('#createUserFields').addClass('d-none'));
                    $('#pickUserFields').removeClass('d-none').hide().fadeIn();
                }
            });

            // Trigger Edit dari dalam Modal Detail
            $(document).on('click', '#btnEditFromDetail', function() {
                const id = $('#det_id_hidden').val();
                const kelasId = $('#det_kelas_id_hidden').val();

                modalDetail.hide(); // Tutup modal detail

                setTimeout(() => {
                    // Panggil form reset dan set title (Sama seperti logic .btnEdit)
                    resetForm();
                    $('#modalMusyrifTitle').html(
                        '<i class="bi bi-pencil-square"></i> Edit Data Musyrif');
                    $('#btnSaveMusyrif').text('Update Musyrif');

                    if (kelasId) {
                        $('#kelas_id').val(kelasId);
                    }

                    // Jalankan AJAX untuk mengambil data edit
                    $.get("{{ route('admin.musyrif.show', ':id') }}".replace(':id', id))
                        .done(function(res) {
                            $('#musyrif_id').val(res.id);
                            $('#nama').val(res.nama);
                            $('#kode').val(res.kode);
                            if (res.kelas_id) $('#kelas_id').val(res.kelas_id);
                            $('#alamat').val(res.alamat);
                            $('#pendidikan_terakhir').val(res.pendidikan_terakhir);
                            $('#domisili').val(res.domisili);
                            $('#halaqah').val(res.halaqah);
                            $('#metode_alquran').val(res.metode_alquran);
                            $('#tahun_sertifikasi').val(res.tahun_sertifikasi);
                            $('#is_sertifikasi_ummi').prop('checked', res.is_sertifikasi_ummi ==
                                1);
                            $('#keterangan').val(res.keterangan);

                            // LOGIKA AKUN LOGIN EXISTING
                            if (res.user_id || res.email !== '-') {
                                $('#create_user').prop('checked', true);
                                $('#createUserFields').removeClass('d-none');
                                $('#email').val(res.email);
                                $('#password').val('').attr('placeholder',
                                    'Isi jika ingin ganti pass');
                            } else {
                                $('#create_user').prop('checked', false);
                                $('#createUserFields').addClass('d-none');
                            }

                            modalMusyrif.show(); // Buka modal Edit
                        });
                }, 400); // Jeda 400ms agar animasi modal tutup selesai dulu
            });

            $('#formMusyrif').on('submit', function(e) {
                e.preventDefault();
                const id = $('#musyrif_id').val();
                const url = id ? "{{ url('admin/musyrif') }}/" + id : "{{ route('admin.musyrif.store') }}";

                // Feedback Loading pada tombol
                const btnSave = $('#btnSaveMusyrif');
                const originalText = btnSave.html();
                btnSave.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Menyimpan...');

                // Hapus feedback error sebelumnya
                $('.invalid-feedback').remove();
                $('.form-control, .form-select').removeClass('is-invalid');

                $.ajax({
                    url: url,
                    type: id ? "PUT" : "POST",
                    data: $(this).serialize() +
                        `&_token={{ csrf_token() }}&create_user=${$('#create_user').is(':checked')?1:0}`,
                    success: function(res) {
                        modalMusyrif.hide();
                        table.ajax.reload(null, false);
                        if (window.AppAlert) AppAlert.success(res.message);
                    },
                    error: function(xhr) {
                        // JIKA ERROR VALIDASI (Status 422)
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMessages = '';

                            // Loop setiap field yang error
                            Object.keys(errors).forEach(key => {
                                const inputField = $(`[name="${key}"]`);
                                const message = errors[key][0]; // Ambil pesan pertama

                                // Tambahkan class merah pada input
                                inputField.addClass('is-invalid');

                                // Tambahkan pesan error di bawah input
                                inputField.after(
                                    `<div class="invalid-feedback small fw-bold">${message}</div>`
                                );

                                errorMessages += `<li>${message}</li>`;
                            });

                            if (window.AppAlert) {
                                AppAlert.error(`<ul>${errorMessages}</ul>`, 'Validasi Gagal');
                            }
                        } else {
                            // Error Sistem Lainnya (500, dll)
                            if (window.AppAlert) {
                                AppAlert.error(xhr.responseJSON?.message ||
                                    'Terjadi kesalahan sistem.');
                            }
                        }
                    },
                    complete: function() {
                        btnSave.prop('disabled', false).html(originalText);
                    }
                });
            });

            $(document).on('click', '.btnDelete', function() {
                const id = $(this).data('id');
                if (!window.AppAlert) return;

                AppAlert.warning('Data musyrif akan dihapus permanen!', 'Hapus Musyrif?')
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.post("{{ url('admin/musyrif') }}/" + id, {
                                _method: 'DELETE',
                                _token: "{{ csrf_token() }}"
                            })
                            .done(res => {
                                table.ajax.reload(null, false);
                                AppAlert.success(res.message || 'Data berhasil dihapus');
                            })
                            .fail(() => AppAlert.error('Gagal menghapus data'));
                    });
            });

            // ==========================================
            // 4. EVENT HANDLERS (IMPORT & PREVIEW)
            // ==========================================

            $('#btnImportMusyrif').on('click', () => modalImport.show());

            $('#formImportUpload').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btnUploadReadSheet');
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Memproses...');

                $.ajax({
                    url: "{{ route('admin.musyrif.preview') }}", // Sesuaikan dengan route preview Mas
                    type: "POST",
                    data: new FormData(this),
                    processData: false,
                    contentType: false,
                    success: (res) => {
                        $('#import_file_path').val(res.temp_path);
                        $('#importMappingArea').fadeIn();
                        $('#importUploadBox').addClass('bg-light-subtle opacity-75');

                        const tbody = $('#importMappingBody').html('');
                        res.sheets.forEach((name, index) => {
                            tbody.append(`
                    <tr>
                        <td class="ps-3 fw-bold">${name}</td>
                        <td class="text-muted small">Baris terdeteksi</td>
                        <td><span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Ready</span></td>
                        <td class="text-center pe-3">
                            <div class="form-check d-inline-block">
                                <input type="radio" name="selected_sheet" class="form-check-input sheet-radio" value="${index}" style="transform: scale(1.2);">
                            </div>
                        </td>
                    </tr>
                `);
                        });
                        if (window.AppAlert) AppAlert.success('Daftar sheet berhasil dibaca.');
                    },
                    error: (xhr) => {
                        if (window.AppAlert) AppAlert.error(xhr.responseJSON?.message ||
                            'Gagal membaca file.');
                    },
                    complete: () => btn.prop('disabled', false).html(
                        '<i class="bi bi-search me-1"></i> Baca Isi File')
                });
            });

            // Event saat tombol Preview diklik
            $('#btnPreviewImport').on('click', function() {
                const sheetIdx = $('.sheet-radio:checked').val();
                if (sheetIdx === undefined) return AppAlert?.error(
                    'Pilih salah satu sheet terlebih dahulu!');

                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span>...');

                $.post("{{ route('admin.musyrif.sheet_preview') }}", {
                    _token: "{{ csrf_token() }}",
                    temp_path: $('#import_file_path').val(),
                    sheet_index: sheetIdx
                }).done(function(res) {
                    // Render Header
                    let headHtml = '';
                    res.headers.forEach(h => headHtml += `<th>${String(h).toUpperCase()}</th>`);
                    $('#previewHeader').html(headHtml);

                    // Render Body
                    let bodyHtml = '';
                    res.preview.forEach(row => {
                        bodyHtml += '<tr>';
                        res.headers.forEach(key => {
                            bodyHtml += `<td>${row[key] ?? '-'}</td>`;
                        });
                        bodyHtml += '</tr>';
                    });
                    $('#importPreviewBody').html(bodyHtml);
                    if (window.AppAlert) AppAlert.success('Preview sheet berhasil dimuat.');
                }).always(() => btn.prop('disabled', false).html(
                    '<i class="bi bi-eye me-1"></i> Preview'));
            });

            // Tombol Reset
            $('#btnResetImport').on('click', function() {
                $('#formImportUpload')[0].reset();
                $('#importMappingArea').hide();
                $('#importUploadBox').removeClass('bg-light-subtle opacity-75');
                $('#importPreviewBody').html(
                    '<tr><td class="text-center py-4 text-muted">Klik "Preview" untuk melihat data</td></tr>'
                );
            });

            // Tombol Final Execute
            $('#btnProcessImport').on('click', function() {
                const sheetIdx = $('.sheet-radio:checked').val();
                if (sheetIdx === undefined) return AppAlert?.error('Pilih sheet yang akan diimport!');

                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm"></span> Mengimport...');

                $.post("{{ route('admin.musyrif.execute_import') }}", {
                    _token: "{{ csrf_token() }}",
                    temp_path: $('#import_file_path').val(),
                    sheet_index: sheetIdx
                }).done(res => {
                    if (window.AppAlert) AppAlert.success(res.message);
                    setTimeout(() => location.reload(), 1500);
                }).fail(xhr => {
                    if (window.AppAlert) AppAlert.error(xhr.responseJSON?.message ||
                        'Gagal import.');
                    btn.prop('disabled', false).html(
                        '<i class="bi bi-cloud-download me-1"></i> Mulai Import');
                });
            });
        });
    </script>
@endpush
