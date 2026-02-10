@extends('layouts.app')

@section('title', 'Riwayat Hafalan Santri Binaan')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Riwayat Setoran Hafalan</span>
            <button class="btn btn-light btn-sm" id="btnAddHafalan">
                <i class="cil-plus me-1"></i> Input Hafalan
            </button>
        </div>

        <div class="card-header bg-light">
            <div class="d-flex align-items-center gap-3 flex-wrap">

                <!-- Filter Tanggal (Nav Pills) -->
                <ul class="nav nav-pills nav-pills-sm flex-nowrap overflow-auto" id="filterTanggalGroup" role="tablist">

                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-nowrap" type="button" data-filter="today">
                            Hari Ini
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" type="button" data-filter="yesterday">
                            Kemarin
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" type="button" data-filter="last_7_days">
                            7 Hari
                        </button>
                    </li>

                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" type="button" data-filter="this_month">
                            Bulan Ini
                        </button>
                    </li>
                </ul>

                <!-- Badge Info -->
                <span class="badge bg-primary-subtle text-primary" id="filterBadge">
                    Menampilkan: Hari Ini
                </span>

            </div>
        </div>

        <div class="card-body table-responsive">
            <table id="hafalan-table" class="table table-striped align-middle w-100">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Santri</th>
                        <th>Kelas</th>
                        <th>Juz</th>
                        <th>Surah / Ayat</th>
                        <th>Tanggal</th>
                        <th>Nilai</th>
                        <th>Tahap</th>
                        <th>Status</th>
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

    {{-- ===================== MODAL CREATE ===================== --}}
    <div class="modal fade" id="modalCreateHafalan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="formCreateHafalan">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Input Setoran Hafalan</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Setoran</label>
                                <input type="text" name="tanggal_setoran" id="tanggal_create" class="form-control"
                                    readonly>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Santri</label>
                                <select name="santri_id" id="create_santri_id" class="form-select" required>
                                    <option value="">-- Pilih Santri Binaan --</option>
                                    @foreach ($santriBinaan as $santri)
                                        <option value="{{ $santri->id }}">
                                            {{ $santri->nama }}
                                            @if ($santri->kelas)
                                                ({{ $santri->kelas->nama_kelas }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Juz</label>
                                <select id="create_juz_ui" class="form-select" required>
                                    <option value="">-- Pilih Juz --</option>
                                    @for ($i = 1; $i <= 30; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                                <div class="form-text">Untuk memfilter template.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahapan</label>
                                <select id="create_tahap_ui" class="form-select" required>
                                    <option value="harian">Harian</option>
                                    <option value="tahap_1">Tahap 1</option>
                                    <option value="tahap_2">Tahap 2</option>
                                    <option value="tahap_3">Tahap 3</option>
                                    <option value="ujian_akhir">Ujian Akhir</option>
                                </select>
                                <div class="form-text">Untuk memfilter template.</div>
                            </div>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">Surah : Ayat (otomatis)</label>
                            <select name="hafalan_template_id" id="create_template_id" class="form-select">
                                <option value="">-- Pilih Juz & Tahapan dulu --</option>
                            </select>
                            <div class="form-text">Isi dropdown otomatis dari template (juz + tahapan).</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="create_status" class="form-select" required>
                                    <option value="lulus">Lulus</option>
                                    <option value="ulang">Ulang</option>
                                    <option value="hadir_tidak_setor">Hadir Tidak Setor</option>
                                    <option value="alpha">Alpha</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nilai</label>
                                <select name="nilai_label" id="create_nilai_label" class="form-select">
                                    <option value="">-- Pilih Nilai --</option>
                                    <option value="mumtaz">ممتاز</option>
                                    <option value="jayyid_jiddan">جيد جدًا</option>
                                    <option value="jayyid">جيد</option>
                                </select>
                                <div class="form-text">Nilai aktif hanya saat status Lulus/Ulang.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan Musyrif (opsional)</label>
                            <textarea name="catatan" id="create_catatan" class="form-control" rows="3"
                                placeholder="Catatan tajwid, kelancaran, adab, dll."></textarea>
                        </div>

                        <div class="alert alert-warning py-2 d-none" id="create_hint">
                            <small id="create_hint_text"></small>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL EDIT ===================== --}}
    <div class="modal fade" id="modalEditHafalan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="formEditHafalan">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Setoran Hafalan</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Setoran</label>
                                <input type="text" name="tanggal_setoran" id="tanggal_edit" class="form-control"
                                    readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Santri</label>
                                <select name="santri_id" id="edit_santri_id" class="form-select" required>
                                    <option value="">-- Pilih Santri Binaan --</option>
                                    @foreach ($santriBinaan as $santri)
                                        <option value="{{ $santri->id }}">
                                            {{ $santri->nama }}
                                            @if ($santri->kelas)
                                                ({{ $santri->kelas->nama_kelas }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Juz</label>
                                <input type="number" id="edit_juz_ui" class="form-control" min="1"
                                    max="30" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tahapan</label>
                                <select id="edit_tahap_ui" class="form-select" required>
                                    <option value="harian">Harian</option>
                                    <option value="tahap_1">Tahap 1</option>
                                    <option value="tahap_2">Tahap 2</option>
                                    <option value="tahap_3">Tahap 3</option>
                                    <option value="ujian_akhir">Ujian Akhir</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Surah : Ayat (otomatis)</label>
                            <select name="hafalan_template_id" id="edit_template_id" class="form-select">
                                <option value="">-- Pilih Juz & Tahapan dulu --</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="lulus">Lulus</option>
                                    <option value="ulang">Ulang</option>
                                    <option value="hadir_tidak_setor">Hadir Tidak Setor</option>
                                    <option value="alpha">Alpha</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nilai</label>
                                <select name="nilai_label" id="edit_nilai_label" class="form-select">
                                    <option value="">-- Pilih Nilai --</option>
                                    <option value="mumtaz">ممتاز</option>
                                    <option value="jayyid_jiddan">جيد جدًا</option>
                                    <option value="jayyid">جيد</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan Musyrif (opsional)</label>
                            <textarea name="catatan" id="edit_catatan" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="alert alert-warning py-2 d-none" id="edit_hint">
                            <small id="edit_hint_text"></small>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL DETAIL ===================== --}}
    <div class="modal fade" id="modalDetailHafalan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Setoran Hafalan</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Santri</dt>
                        <dd class="col-sm-9" id="detail_santri"></dd>

                        <dt class="col-sm-3">Kelas</dt>
                        <dd class="col-sm-9" id="detail_kelas"></dd>

                        <dt class="col-sm-3">Juz</dt>
                        <dd class="col-sm-9" id="detail_juz"></dd>

                        <dt class="col-sm-3">Surah / Ayat</dt>
                        <dd class="col-sm-9" id="detail_rentang"></dd>

                        <dt class="col-sm-3">Tanggal Setoran</dt>
                        <dd class="col-sm-9" id="detail_tanggal"></dd>

                        <dt class="col-sm-3">Nilai</dt>
                        <dd class="col-sm-9" id="detail_nilai"></dd>

                        <dt class="col-sm-3">Tahap</dt>
                        <dd class="col-sm-9" id="detail_tahap"></dd>

                        <dt class="col-sm-3">Status</dt>
                        <dd class="col-sm-9" id="detail_status"></dd>

                        <dt class="col-sm-3">Catatan Musyrif</dt>
                        <dd class="col-sm-9" id="detail_catatan"></dd>
                    </dl>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ================== Filter Tanggal (Button Group) ==================
            let filterTanggal = 'today';

            const filterLabels = {
                today: 'Hari Ini',
                yesterday: 'Kemarin',
                last_7_days: '7 Hari Terakhir',
                this_month: 'Bulan Ini'
            };

            const modalCreate = new coreui.Modal(document.getElementById('modalCreateHafalan'));
            const modalEdit = new coreui.Modal(document.getElementById('modalEditHafalan'));
            const modalDetail = new coreui.Modal(document.getElementById('modalDetailHafalan'));

            const ROUTE_TEMPLATES = @json(route('musyrif.hafalan.templates'));

            function todayISO() {
                const d = new Date();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${d.getFullYear()}-${mm}-${dd}`;
            }

            function formatTanggalIndonesia(iso) {

                if (!iso) return '';

                const d = new Date(iso);

                return d.toLocaleDateString('id-ID', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            function tahapLabel(v) {
                return ({
                    'harian': 'Harian',
                    'tahap_1': 'Tahap 1',
                    'tahap_2': 'Tahap 2',
                    'tahap_3': 'Tahap 3',
                    'ujian_akhir': 'Ujian Akhir',
                })[v] || '-';
            }

            function statusLabel(v) {
                return ({
                    'lulus': 'Lulus',
                    'ulang': 'Ulang',
                    'hadir_tidak_setor': 'Hadir Tidak Setor',
                    'alpha': 'Alpha',
                })[v] || '-';
            }

            function nilaiArab(v) {
                return ({
                    'mumtaz': 'ممتاز',
                    'jayyid_jiddan': 'جيد جدًا',
                    'jayyid': 'جيد',
                })[v] || '-';
            }

            async function fetchTemplates(juz, tahap) {
                const url =
                    `${ROUTE_TEMPLATES}?juz=${encodeURIComponent(juz)}&tahap=${encodeURIComponent(tahap)}`;
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return {
                    ok: false,
                    templates: []
                };
                return await res.json();
            }

            async function loadTemplateOptions(mode) {
                const juzEl = document.getElementById(`${mode}_juz_ui`);
                const tahapEl = document.getElementById(`${mode}_tahap_ui`);
                const tplEl = document.getElementById(`${mode}_template_id`);

                if (!tplEl) return;

                const juz = juzEl?.value;
                const tahap = tahapEl?.value;

                tplEl.innerHTML = `<option value="">-- Memuat... --</option>`;

                if (!juz || !tahap) {
                    tplEl.innerHTML = `<option value="">-- Pilih Juz & Tahapan dulu --</option>`;
                    return;
                }

                const json = await fetchTemplates(juz, tahap);

                if (!json.ok) {
                    tplEl.innerHTML = `<option value="">-- Gagal memuat template --</option>`;
                    return;
                }

                if (!json.templates || json.templates.length === 0) {
                    tplEl.innerHTML = `<option value="">-- Template tidak ditemukan --</option>`;
                    return;
                }

                tplEl.innerHTML = `<option value="">-- Pilih Surah:Ayat --</option>`;
                json.templates.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = `${t.urutan}. ${t.label}`;
                    tplEl.appendChild(opt);
                });
            }

            function syncRules(mode) {
                const statusEl = document.getElementById(`${mode}_status`);
                const tplEl = document.getElementById(`${mode}_template_id`);
                const nilaiEl = document.getElementById(`${mode}_nilai_label`);

                const hintBox = document.getElementById(`${mode}_hint`);
                const hintText = document.getElementById(`${mode}_hint_text`);

                if (!statusEl || !tplEl || !nilaiEl) return;

                const status = statusEl.value;
                const isSetor = (status === 'lulus' || status === 'ulang');

                tplEl.disabled = !isSetor;
                nilaiEl.disabled = !isSetor;

                if (!isSetor) {
                    tplEl.value = '';
                    nilaiEl.value = '';
                    if (hintBox && hintText) {
                        hintBox.classList.remove('d-none');
                        hintText.textContent = (status === 'alpha') ?
                            'Status Alpha: tidak ada setoran (template & nilai dinonaktifkan).' :
                            'Status Hadir Tidak Setor: tidak ada setoran (template & nilai dinonaktifkan).';
                    }
                } else {
                    if (hintBox) hintBox.classList.add('d-none');
                }
            }

            // ================== DataTables ==================
            // NOTE: Pastikan endpoint datatable Anda sudah mengirim field baru:
            // - template_label (surah:ayat)
            // - template_juz, template_tahap (untuk edit)
            // - hafalan_template_id
            // - nilai_label
            // - status
            const table = $('#hafalan-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('musyrif.hafalan.datatable') }}",
                    data: function(d) {
                        d.filter_tanggal = filterTanggal;
                    }
                },

                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'santri',
                        name: 'santri',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kelas',
                        name: 'kelas',
                        orderable: false,
                        searchable: false
                    },

                    // tampilkan juz dari template (bukan input manual)
                    {
                        data: 'template_juz',
                        name: 'template_juz',
                        searchable: false
                    },

                    // label Surah:Ayat dari template
                    {
                        data: 'template_label',
                        name: 'template_label',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'tanggal',
                        name: 'tanggal_setoran',
                        searchable: false
                    },

                    // nilai arab dari nilai_label
                    {
                        data: 'nilai_label',
                        name: 'nilai_label',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'template_tahap',
                        name: 'template_tahap',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ],
                order: [
                    [5, 'desc']
                ]
            });

            // ================== Filter Tanggal: Button Group ==================
            $('#filterTanggalGroup button').on('click', function() {
                if ($(this).hasClass('active')) return;

                $('#filterTanggalGroup button').removeClass('active');
                $(this).addClass('active');

                filterTanggal = $(this).data('filter');

                $('#filterBadge').text('Menampilkan: ' + filterLabels[filterTanggal]);

                table.ajax.reload(null, true); // true = reset paging
            });

            table.on('draw', function() {
                $('#filterBadge').text('Menampilkan: ' + filterLabels[filterTanggal]);
            });

            // ================== Open Create ==================
            $('#btnAddHafalan').on('click', function() {
                $('#formCreateHafalan')[0].reset();

                // auto today
                const tglCreate = document.getElementById('tanggal_create');
                if (tglCreate) {
                    const iso = todayISO();
                    tglCreate.value = formatTanggalIndonesia(iso);
                    tglCreate.dataset.iso = iso;
                }

                // reset dropdown template
                const tplEl = document.getElementById('create_template_id');
                if (tplEl) tplEl.innerHTML = `<option value="">-- Pilih Juz & Tahapan dulu --</option>`;

                // default status rules
                syncRules('create');

                modalCreate.show();
            });

            // ================== Create: load template when filter changes ==================
            $('#create_juz_ui, #create_tahap_ui').on('change', function() {
                loadTemplateOptions('create');
            });
            $('#create_status').on('change', function() {
                syncRules('create');
            });

            // ================== Store (Create) ==================
            $('#formCreateHafalan').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('musyrif.hafalan.store') }}",
                    type: 'POST',
                    data: $('#formCreateHafalan').serialize(),
                    success: function(res) {
                        modalCreate.hide();
                        table.ajax.reload(null, true); // true = reset paging

                        if (window.AppAlert) {
                            AppAlert.success(res.message ??
                                'Setoran hafalan berhasil disimpan.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan.';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        }
                        if (window.AppAlert) {
                            AppAlert.error(msg);
                        }
                    }
                });
            });

            // ================== Open Edit ==================
            $(document).on('click', '.btn-edit', async function() {
                const d = $(this).data();

                // isi field yang disimpan
                $('#edit_id').val(d.id);
                $('#edit_santri_id').val(d.santri_id);
                if (d.tanggal_ymd) {
                    $('#tanggal_edit')
                        .val(formatTanggalIndonesia(d.tanggal_ymd))
                        .attr('data-iso', d.tanggal_ymd);
                }

                $('#edit_status').val(d.status || 'hadir_tidak_setor');
                $('#edit_nilai_label').val(d.nilai_label || '');
                $('#edit_catatan').val(d.catatan || '');

                // isi filter UI (untuk memuat template list)
                $('#edit_juz_ui').val(d.template_juz || '');
                $('#edit_tahap_ui').val(d.template_tahap || 'harian');

                // load templates then select template_id
                await loadTemplateOptions('edit');
                $('#edit_template_id').val(d.hafalan_template_id || '');

                // apply rules (disable template/nilai jika alpha/hadir_tidak_setor)
                syncRules('edit');

                const iso = d.tanggal_ymd || todayISO();

                $('#tanggal_edit')
                    .val(formatTanggalIndonesia(iso))
                    .attr('data-iso', iso);


                modalEdit.show();
            });

            // ================== Edit: load template when filter changes ==================
            $('#edit_juz_ui, #edit_tahap_ui').on('change', function() {
                loadTemplateOptions('edit');
            });
            $('#edit_status').on('change', function() {
                syncRules('edit');
            });

            // ================== Update (Edit) ==================
            $('#formEditHafalan').on('submit', function(e) {
                e.preventDefault();

                const id = $('#edit_id').val();
                const url = "{{ url('musyrif/hafalan') }}/" + id;

                $.ajax({
                    url: url,
                    type: 'POST', // POST + _method=PUT (sesuai form)
                    data: $('#formEditHafalan').serialize(),
                    success: function(res) {
                        modalEdit.hide();
                        table.ajax.reload(null, true); // true = reset paging

                        if (window.AppAlert) {
                            AppAlert.success(res.message ??
                                'Setoran hafalan berhasil diupdate.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan.';
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        }
                        if (window.AppAlert) {
                            AppAlert.error(msg);
                        }
                    }
                });
            });

            // ================== Detail ==================
            $(document).on('click', '.btn-detail', function() {
                const d = $(this).data();

                $('#detail_santri').text(d.santri || '-');
                $('#detail_kelas').text(d.kelas || '-');

                // sekarang juz/tahap/rentang dari template
                $('#detail_juz').text(d.template_juz || '-');
                $('#detail_rentang').text(d.template_label || '-');

                $('#detail_tanggal').text(d.tanggal_label || '-');

                // nilai arab
                $('#detail_nilai').text(nilaiArab(d.nilai_label) || '-');

                $('#detail_tahap').text(tahapLabel(d.template_tahap));
                $('#detail_status').text(statusLabel(d.status));

                $('#detail_catatan').text(d.catatan || '-');

                modalDetail.show();
            });

            // ================== Delete ==================
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');

                if (!window.AppAlert) return;

                AppAlert.warning('Data hafalan tidak dapat dikembalikan!', 'Hapus Setoran?')
                    .then(result => {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: "{{ url('musyrif/hafalan') }}/" + id,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                table.ajax.reload(null, true); // true = reset paging
                                AppAlert.success(res.message ??
                                    'Setoran hafalan berhasil dihapus.');
                            },
                            error: function() {
                                AppAlert.error('Tidak dapat menghapus setoran hafalan.');
                            }
                        });
                    });
            });

        });
    </script>
@endpush
