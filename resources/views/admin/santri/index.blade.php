@extends('layouts.app')

@section('title', 'Data Santri')

@section('content')
    <div class="card">

        {{-- Header utama --}}
        <div class="card-header">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div class="fw-semibold">Daftar Santri</div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('/admin/santri/naik-kelas') }}" class="btn btn-outline-light btn-sm">
                        Migrasi / Naik Kelas
                    </a>

                    <button class="btn btn-outline-light btn-sm" id="btnImportSantri" type="button">
                        Import Data Santri
                    </button>

                    <button class="btn btn-light btn-sm" id="btnAddSantri" type="button">
                        Tambah Santri
                    </button>
                </div>
            </div>
        </div>


        {{-- Tabs filter kelas (sub-header) --}}
        <div class="border-bottom">
            <ul class="nav nav-tabs card-header-tabs px-3 pt-2" id="kelasTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-kelas="" type="button" role="tab">
                        Semua
                    </button>
                </li>

                @foreach ($kelasList as $kelas)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-kelas="{{ $kelas->id }}" type="button" role="tab">
                            {{ $kelas->nama_kelas }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Body --}}
        <div class="card-body table-responsive">
            <table id="santri-table" class="table table-striped align-middle w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:60px;">No.</th>
                        <th>Nama Santri</th>
                        <th style="width:120px;">Akun User</th>
                        <th style="width:160px;">Kelas</th>
                        <th style="width:180px;">Musyrif</th>
                        <th class="text-end" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>

@endsection

@push('modals')
    {{-- ===================== MODAL CREATE & EDIT ===================== --}}
    <div class="modal fade" id="modalSantri" tabindex="-1">
        <div class="modal-dialog">
            <form id="formSantri">
                @csrf
                <input type="hidden" name="id" id="santri_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalSantriTitle">Tambah Santri</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Nama Santri</label>
                            <input type="text" class="form-control" name="nama" id="nama"
                                placeholder="Masukkan Nama Santri..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Kelas</label>
                            <select class="form-select" name="kelas_id" id="kelas_id" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($kelasList as $kelas)
                                    <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Musyrif</label>
                            <select class="form-select" name="musyrif_id" id="musyrif_id">
                                <option value="">-- Pilih Musyrif --</option>
                                @foreach ($musyrifList as $musyrif)
                                    <option value="{{ $musyrif->id }}">
                                        {{ $musyrif->nama }} - ({{ $musyrif->santri_count }} santri)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">NIS</label>
                            <input type="text" class="form-control" name="nis" id="nis"
                                placeholder="Masukkan NIS...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="tanggal_lahir" id="tanggal_lahir">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jenis Kelamin</label>
                            <select class="form-select" name="jenis_kelamin" id="jenis_kelamin">
                                <option value="">-- Pilih --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveSantri">Simpan</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- ===================== MODAL IMPORT EXCEL ===================== --}}
    <div class="modal fade" id="modalImportSantri" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Import Data Santri (Excel)</h5>
                        <div class="small text-muted">Wajib hanya kolom: <b>nama</b>. Kelas ditentukan via mapping Sheet →
                            Kelas.</div>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form id="formImportUpload" class="row g-2 align-items-end">
                        @csrf
                        <div class="col-md-7">
                            <label class="form-label">File Excel (.xlsx / .xls / .csv)</label>
                            <input type="file" class="form-control" name="file" id="import_file"
                                accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="col-md-5 d-flex gap-2">
                            <button class="btn btn-primary w-100" type="submit" id="btnUploadReadSheet">
                                Upload & Baca Sheet
                            </button>
                            <button class="btn btn-outline-secondary w-100" type="button" id="btnResetImport">
                                Reset
                            </button>
                        </div>
                    </form>

                    <hr>

                    <input type="hidden" id="import_file_path" value="">

                    <div id="importMappingArea" style="display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Mapping Sheet → Kelas</h6>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                    id="btnPreviewImport">Preview</button>
                                <button class="btn btn-success btn-sm" type="button"
                                    id="btnProcessImport">Import</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">Sheet</th>
                                        <th style="width: 140px;">Estimasi Baris</th>
                                        <th>Kelas Tujuan</th>
                                    </tr>
                                </thead>
                                <tbody id="importMappingBody"></tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning py-2 mt-2" id="importErrorBox" style="display:none;"></div>

                        <div class="mt-3">
                            <h6 class="mb-2">Preview (maks. 300 baris)</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Sheet</th>
                                            <th>Kelas</th>
                                            <th>Nama</th>
                                            <th>NIS</th>
                                            <th>Tgl Lahir</th>
                                            <th>JK</th>
                                        </tr>
                                    </thead>
                                    <tbody id="importPreviewBody"></tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-coreui-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL CREATE / UPDATE USER SANTRI ===================== --}}
    <div class="modal fade" id="modalUserSantri" tabindex="-1">
        <div class="modal-dialog">
            <form id="formUserSantri" method="POST">
                @csrf
                @method('PUT') <!-- method PUT tetap digunakan -->
                <input type="hidden" name="santri_id" id="santri_id">
                <input type="hidden" name="user_id" id="user_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Buat / Update User Santri</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Santri</label>
                            <input type="text" class="form-control" id="nama_santri" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama User</label>
                            <input type="text" class="form-control" name="name" id="user_name" required placeholder="Masukkan Username...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" name="nomor" id="user_nomor" required placeholder="Masukkan Nomor Telephone...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" id="user_email" required placeholder="Masukkan E-mail...">
                        </div>
                        <div class="mb-3 position-relative">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="user_password" placeholder="Masukkan Password...">
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Simpan User</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL DETAIL SANTRI ===================== --}}
    <div class="modal fade" id="modalDetailSantri" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Santri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Nama</th>
                            <td id="detail_nama"></td>
                        </tr>
                        <tr>
                            <th>NIS</th>
                            <td id="detail_nis"></td>
                        </tr>
                        <tr>
                            <th>Tanggal Lahir</th>
                            <td id="detail_tanggal_lahir"></td>
                        </tr>
                        <tr>
                            <th>Jenis Kelamin</th>
                            <td id="detail_jenis_kelamin"></td>
                        </tr>
                        <tr>
                            <th>Kelas</th>
                            <td id="detail_kelas"></td>
                        </tr>
                        <tr>
                            <th>Musyrif</th>
                            <td id="detail_musyrif"></td>
                        </tr>
                        <tr>
                            <th>User</th>
                            <td id="detail_user"></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td id="detail_email"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>


@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const modalEl = document.getElementById('modalSantri');
            const modalSantri = new coreui.Modal(modalEl);

            // ================================
            //  INIT DATATABLES
            // ================================
            let selectedKelas = '';

            const table = $('#santri-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('santri.master.datatable') }}",
                    data: function(d) {
                        d.kelas_id = selectedKelas;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'nama',
                        name: 'santris.nama'
                    },

                    {
                        data: 'akun',
                        name: 'users.name',
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
                        searchable: false
                    }
                ]
            });


            $('#kelasTabs').on('click', '.nav-link', function() {
                $('#kelasTabs .nav-link').removeClass('active');
                $(this).addClass('active');

                selectedKelas = $(this).data('kelas') || '';
                table.ajax.reload();
            });

            // ================================
            //  OPEN CREATE MODAL
            // ================================
            $('#btnAddSantri').on('click', function() {
                $('#formSantri')[0].reset();
                $('#santri_id').val('');
                $('#modalSantriTitle').text("Tambah Santri");
                modalSantri.show();
            });

            // ================================
            //  OPEN EDIT MODAL
            // ================================
            $(document).on('click', '.btn-edit', function() {
                let d = $(this).data();

                $('#modalSantriTitle').text("Edit Santri");
                $('#santri_id').val(d.id);
                $('#nama').val(d.nama);
                $('#nis').val(d.nis || '');
                $('#kelas_id').val(d.kelas_id || '');
                $('#musyrif_id').val(d.musyrif_id || '');
                $('#tanggal_lahir').val(d.tanggal_lahir || '');
                $('#jenis_kelamin').val(d.jenis_kelamin || '');

                modalSantri.show();
            });

            // ================================
            //  SUBMIT (CREATE/UPDATE)
            // ================================
            $('#formSantri').on('submit', function(e) {
                e.preventDefault();

                const id = $('#santri_id').val();
                const url = id ?
                    "{{ url('santri-master') }}/" + id :
                    "{{ route('santri.master.store') }}";

                const method = id ? "PUT" : "POST";

                $.ajax({
                    url: url,
                    type: method,
                    data: $('#formSantri').serialize(),
                    success: function(res) {
                        modalSantri.hide();
                        $('#santri-table').DataTable().ajax.reload();

                        if (window.AppAlert) {
                            AppAlert.success(res.message ?? 'Santri berhasil disimpan.');
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

            // Toggle Password Show/Hide
            $('#togglePassword').on('click', function() {
                const input = $('#user_password');
                const type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);
                $(this).find('i').toggleClass('bi-eye bi-eye-slash');
            });

            // Buka modal dan isi data dari tombol
            $(document).on('click', '.btn-user', function() {
                let santri_id = $(this).data('id');
                let nama = $(this).data('nama');
                let user_id = $(this).data('user-id') || '';
                let user_name = $(this).data('user-name') || '';
                let user_nomor = $(this).data('user-nomor') || '';
                let user_email = $(this).data('user-email') || '';
                let url = $(this).data('route'); // ambil route dinamis

                $('#santri_id').val(santri_id);
                $('#user_id').val(user_id);
                $('#nama_santri').val(nama);
                $('#user_name').val(user_name);
                $('#user_nomor').val(user_nomor);
                $('#user_email').val(user_email);
                $('#user_password').val('');

                $('#formUserSantri').attr('action', url); // set action form
                $('#formUserSantri').data('url', url); // simpan untuk AJAX

                $('#modalUserSantri').modal('show');
            });

            // Submit form via AJAX
            $('#formUserSantri').on('submit', function(e) {
                e.preventDefault();

                let santriId = $('#santri_id').val();
                let url = `/santri-master/${santriId}/assign-user`; // pastikan sesuai route

                if (!$('#user_name').val() || !$('#user_nomor').val()) {
                    alert('Nama User dan Nomor Telepon wajib diisi!');
                    return;
                }

                $.ajax({
                    url: url,
                    type: 'PUT',
                    data: $(this).serialize(),
                    success: function(res) {
                        alert(res.message || 'User berhasil disimpan!');
                        $('#modalUserSantri').modal('hide');
                        $('#santri-table').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || 'Terjadi kesalahan');
                    }
                });
            });

            // ================================
            //  DETAIL SANTRI
            // ================================
            $(document).on('click', '.btn-detail', function() {
                $('#detail_nama').text($(this).data('nama'));
                $('#detail_nis').text($(this).data('nis'));
                $('#detail_tanggal_lahir').text($(this).data('tanggal_lahir'));
                $('#detail_jenis_kelamin').text(
                    $(this).data('jenis_kelamin') === 'L' ? 'Laki-laki' :
                    ($(this).data('jenis_kelamin') === 'P' ? 'Perempuan' : '-')
                );
                $('#detail_kelas').text($(this).data('kelas'));
                $('#detail_musyrif').text($(this).data('musyrif'));
                let user = $(this).data('user-name') ? $(this).data('user-name') + ' (' + $(this).data(
                    'user-nomor') + ')' : '-';
                $('#detail_user').text(user);
                $('#detail_email').text($(this).data('user-email') ?? '-');

                $('#modalDetailSantri').modal('show');
            });

            // ================================
            //  DELETE SANTRI
            // ================================
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');

                if (!window.AppAlert) {
                    return;
                }

                AppAlert.warning('Data santri tidak dapat dikembalikan!', 'Hapus Santri?')
                    .then(result => {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ url('santri-master') }}/" + id,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                $('#santri-table').DataTable().ajax.reload();
                                AppAlert.success(res.message ?? 'Santri berhasil dihapus.');
                            },
                            error: function() {
                                AppAlert.error('Tidak dapat menghapus santri.');
                            }
                        });
                    });
            });

            // ==============================
            // Modal Import
            // ==============================
            const modalImportEl = document.getElementById('modalImportSantri');
            const modalImport = new coreui.Modal(modalImportEl);

            $('#btnImportSantri').on('click', function() {
                modalImport.show();
            });

            function resetImportUI() {
                $('#formImportUpload')[0].reset();
                $('#import_file_path').val('');
                $('#importMappingArea').hide();
                $('#importMappingBody').html('');
                $('#importPreviewBody').html('');
                $('#importErrorBox').hide().html('');
            }

            $('#btnResetImport').on('click', resetImportUI);

            function showImportErrors(errors) {
                if (!errors || errors.length === 0) {
                    $('#importErrorBox').hide().html('');
                    return;
                }
                $('#importErrorBox').show().html(
                    '<b>Catatan/Validasi:</b><br>' +
                    errors.map(e => `• ${e}`).join('<br>')
                );
            }

            // ==============================
            // HTML Select Kelas (reuse dari list di halaman)
            // ==============================
            function kelasSelectHtml(sheetIndex) {
                let opts = `<option value="">-- pilih kelas --</option>`;
                @foreach ($kelasList as $kelas)
                    opts +=
                        `<option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }} (ID:{{ $kelas->id }})</option>`;
                @endforeach

                return `
            <select class="form-select form-select-sm kelas-select"
                    data-index="${sheetIndex}"
                    disabled>
                ${opts}
            </select>
        `;
            }

            // ==============================
            // 1) Upload -> Scan sheets (valid/invalid)
            // ==============================
            $('#formImportUpload').on('submit', function(e) {
                e.preventDefault();

                const fd = new FormData(this);

                $.ajax({
                    url: "{{ route('santri.master.import.upload') }}",
                    type: "POST",
                    data: fd,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#import_file_path').val(res.file_path);
                        $('#importMappingArea').show();
                        $('#importPreviewBody').html('');
                        showImportErrors([]);

                        const tbody = $('#importMappingBody');
                        tbody.html('');

                        (res.sheets || []).forEach(s => {
                            const validBadge = s.is_valid ?
                                `<span class="badge bg-success">VALID</span>` :
                                `<span class="badge bg-secondary">TIDAK VALID</span>`;

                            const note = s.is_valid ?
                                `<div class="text-muted small">Kolom nama terdeteksi: <b>${s.nama_key}</b></div>` :
                                `<div class="text-muted small">Tidak ditemukan kolom nama (alias: nama/name/dll)</div>`;

                            const disabled = s.is_valid ? '' : 'disabled';

                            tbody.append(`
                        <tr>
                            <td style="width: 260px;">
                                <div class="d-flex align-items-start gap-2">
                                    <input type="checkbox"
                                            class="form-check-input sheet-check mt-1"
                                            data-index="${s.sheet_index}"
                                            ${disabled}>
                                    <div>
                                        <div class="fw-semibold">${s.label} ${validBadge}</div>
                                        <div class="text-muted small">index: ${s.sheet_index} • estimasi baris: ${s.rows}</div>
                                        ${note}
                                    </div>
                                </div>
                            </td>
                            <td style="width: 220px;">
                                ${kelasSelectHtml(s.sheet_index)}
                                <div class="text-muted small mt-1">Aktif setelah sheet dicentang</div>
                            </td>
                        </tr>
                    `);
                        });

                        if (window.AppAlert) AppAlert.success(
                            'Sheet berhasil dibaca. Pilih sheet yang akan diimport lalu pilih kelas.'
                        );
                    },
                    error: function(xhr) {
                        let msg = 'Gagal upload/baca sheet.';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        } else if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (window.AppAlert) AppAlert.error(msg);
                    }
                });
            });

            // ==============================
            // 2) Checkbox sheet: enable/disable kelas select
            // ==============================
            $(document).on('change', '.sheet-check', function() {
                const idx = $(this).data('index');
                const isChecked = $(this).is(':checked');

                const select = $(`.kelas-select[data-index="${idx}"]`);
                select.prop('disabled', !isChecked);

                // Jika uncheck -> kosongkan pilihan kelas
                if (!isChecked) select.val('');
            });

            // ==============================
            // 3) Collect selections (HANYA sheet yang dicentang)
            // format: selections[sheetIndex] = { kelas_id: X }
            // ==============================
            function collectSelections() {
                const selections = {};
                let missingKelas = [];

                $('.sheet-check:checked').each(function() {
                    const idx = $(this).data('index');
                    const kelasId = $(`.kelas-select[data-index="${idx}"]`).val();

                    if (!kelasId) {
                        missingKelas.push(`Sheet ${parseInt(idx) + 1} belum dipilih kelas`);
                    } else {
                        selections[idx] = {
                            kelas_id: parseInt(kelasId)
                        };
                    }
                });

                return {
                    selections,
                    missingKelas
                };
            }

            // ==============================
            // 4) Preview
            // ==============================
            $('#btnPreviewImport').on('click', function() {
                const filePath = $('#import_file_path').val();
                if (!filePath) return AppAlert?.error('File belum diupload.');

                const {
                    selections,
                    missingKelas
                } = collectSelections();

                if (Object.keys(selections).length === 0) {
                    return AppAlert?.error('Belum ada sheet yang dipilih.');
                }
                if (missingKelas.length > 0) {
                    return AppAlert?.error(missingKelas.join('\n'));
                }

                $.ajax({
                    url: "{{ route('santri.master.import.preview') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        file_path: filePath,
                        selections: selections
                    },
                    success: function(res) {
                        showImportErrors(res.errors);

                        const pb = $('#importPreviewBody');
                        pb.html('');

                        (res.preview || []).forEach(row => {
                            pb.append(`
                            <tr>
                                <td>${row.sheet}</td>
                                <td>${row.kelas_nama ?? ('ID: ' + row.kelas_id)}</td>
                                <td>${row.nama ?? ''}</td>
                                <td>${row.nis ?? ''}</td>
                                <td>${row.tanggal_lahir ?? ''}</td>
                                <td>${row.jenis_kelamin ?? ''}</td>
                            </tr>
                            `);
                        });

                        if (window.AppAlert) AppAlert.success(
                            `Preview siap. Total: ${res.total ?? (res.preview || []).length}`
                        );
                    },
                    error: function(xhr) {
                        let msg = 'Gagal preview.';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        } else if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }
                        if (window.AppAlert) AppAlert.error(msg);
                    }
                });
            });

            // ==============================
            // 5) Process Import
            // ==============================
            $('#btnProcessImport').on('click', function() {
                const filePath = $('#import_file_path').val();
                if (!filePath) return AppAlert?.error('File belum diupload.');

                const {
                    selections,
                    missingKelas
                } = collectSelections();

                if (Object.keys(selections).length === 0) {
                    return AppAlert?.error('Belum ada sheet yang dipilih.');
                }
                if (missingKelas.length > 0) {
                    return AppAlert?.error(missingKelas.join('\n'));
                }

                AppAlert.warning(
                    'Pastikan mapping Sheet → Kelas sudah benar. Import akan menambahkan data santri baru.',
                    'Lanjut Import?'
                ).then(result => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: "{{ route('santri.master.import.process') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            file_path: filePath,
                            selections: selections
                        },
                        success: function(res) {
                            // reload table utama
                            $('#santri-table').DataTable().ajax.reload();

                            if (window.AppAlert) {
                                AppAlert.success(
                                    `${res.message} (Inserted: ${res.inserted}, Skipped: ${res.skipped})`
                                );
                            }

                            // reset UI import + tutup modal
                            resetImportUI();
                            modalImport.hide();

                            // tampilkan errors (kalau ada) sebagai catatan
                            showImportErrors(res.errors || []);

                            if (window.AppAlert) {
                                AppAlert.success(
                                    `${res.message} (Inserted: ${res.inserted}, Skipped: ${res.skipped})`
                                );
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Gagal import.';
                            if (xhr.responseJSON?.message) msg = xhr.responseJSON
                                .message;
                            if (window.AppAlert) AppAlert.error(msg);
                        }
                    });
                });
            });

            modalImportEl.addEventListener('hidden.coreui.modal', function() {
                resetImportUI();
            });

            function resetImportUI() {
                $('#formImportUpload')[0].reset();
                $('#import_file_path').val('');
                $('#importMappingArea').hide();
                $('#importMappingBody').html('');
                $('#importPreviewBody').html('');
                $('#importErrorBox').hide().html('');
            }

        });
    </script>
@endpush
