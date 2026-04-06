@extends('layouts.app')

@section('title', 'Laporan Hafalan & Kinerja Departemen Al Qur\'an')

@section('content')
    <style>
        /* ================= KPI CARD ADAPTIVE STYLING ================= */
        .kpi-card {
            border-radius: 16px;
            background: var(--cui-card-bg);
            border: 1px solid var(--cui-border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
            transition: all .25s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        .kpi-label {
            font-size: 0.8rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            font-weight: 600;
            color: var(--cui-secondary-color);
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .kpi-progress {
            height: 6px;
            background: var(--cui-border-color);
            border-radius: 20px;
            overflow: hidden;
        }

        .kpi-progress-bar {
            height: 100%;
            width: 0;
            border-radius: 20px;
            transition: width .8s ease;
        }

        /* Penyesuaian khusus Dark Mode */
        [data-coreui-theme="dark"] .kpi-card {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        [data-coreui-theme="dark"] .kpi-icon-purple {
            background: rgba(107, 78, 255, 0.2) !important;
        }

        [data-coreui-theme="dark"] .kpi-icon-tosca {
            background: rgba(19, 163, 179, 0.2) !important;
        }

        [data-coreui-theme="dark"] .kpi-icon-warning {
            background: rgba(255, 193, 7, 0.2) !important;
        }

        [data-coreui-theme="dark"] .kpi-icon-blue {
            background: rgba(13, 110, 253, 0.2) !important;
        }

        [data-coreui-theme="dark"] .kpi-icon-success {
            background: rgba(25, 135, 84, 0.2) !important;
        }
    </style>

    {{-- HEADER TITLE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Dashboard Laporan & Kinerja</h4>
            <span class="text-muted small">Rekapitulasi hafalan santri dan pengawasan kinerja kehadiran musyrif</span>
        </div>
    </div>

    {{-- ================= KPI / RINGKASAN ================= --}}
    <div class="row mb-4 g-3 row-cols-1 row-cols-md-3 row-cols-xl-5">
        {{-- Total Santri --}}
        <div class="col">
            <div class="card kpi-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Total Santri</div>
                            <div class="kpi-value count-up" style="color: var(--islamic-purple-600);" data-target="0"
                                id="kpi_total_santri">0</div>
                        </div>
                        <div class="kpi-icon kpi-icon-purple"
                            style="background: var(--islamic-purple-100); color: var(--islamic-purple-600);">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Musyrif --}}
        <div class="col">
            <div class="card kpi-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Total Musyrif</div>
                            <div class="kpi-value count-up text-primary" data-target="0" id="kpi_total_musyrif">0</div>
                        </div>
                        <div class="kpi-icon kpi-icon-blue text-primary" style="background: rgba(13, 110, 253, 0.15);">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Kehadiran Musyrif --}}
        <div class="col">
            <div class="card kpi-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Kehadiran Musyrif</div>
                            <div class="kpi-value text-success"><span class="count-up" data-target="0"
                                    id="kpi_kehadiran_musyrif">0</span>%</div>
                        </div>
                        <div class="kpi-icon kpi-icon-success text-success" style="background: rgba(25, 135, 84, 0.15);">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Setoran --}}
        <div class="col">
            <div class="card kpi-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Total Setoran</div>
                            <div class="kpi-value count-up" style="color: var(--islamic-tosca-600);" data-target="0"
                                id="kpi_total_setor">0</div>
                        </div>
                        <div class="kpi-icon kpi-icon-tosca"
                            style="background: var(--islamic-tosca-100); color: var(--islamic-tosca-600);">
                            <i class="bi bi-journal-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rata Nilai --}}
        <div class="col">
            <div class="card kpi-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Rata Nilai</div>
                            <div class="kpi-value count-up text-warning" data-target="0" id="kpi_avg_nilai">0</div>
                        </div>
                        <div class="kpi-icon kpi-icon-warning text-warning" style="background: rgba(255, 193, 7, 0.15);">
                            <i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header border-0 py-3 rounded-top-4"
            style="background: linear-gradient(90deg, var(--islamic-purple-600), var(--islamic-purple-400)); color: white;">
            <i class="bi bi-funnel me-2"></i> Filter Laporan
        </div>
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" id="formFilter">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select class="form-select" name="kelas_id" id="filter_kelas">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasList as $kelas)
                            <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold">Musyrif</label>
                    <select class="form-select" name="musyrif_id" id="filter_musyrif">
                        <option value="">Semua Musyrif</option>
                        @foreach ($musyrifList as $musyrif)
                            <option value="{{ $musyrif->id }}">{{ $musyrif->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold">Periode (Bulan)</label>
                    <input type="month" class="form-control" id="filter_periode" value="{{ $defaultPeriode }}">
                </div>
                <div class="col-md-3 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn text-white w-100"
                        style="background: var(--islamic-purple-600);">Terapkan</button>
                    <button type="button" class="btn btn-danger text-white w-100" id="btnResetFilter">Reset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- GRAFIK --}}
    <div class="row mb-4 g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent py-3 fw-semibold">
                    <i class="bi bi-bar-chart-fill me-2" style="color: var(--islamic-purple-500);"></i> Grafik Setoran per
                    Kelas
                </div>
                <div class="card-body">
                    <canvas id="chartKelas" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent py-3 fw-semibold">
                    <i class="bi bi-bar-chart-fill me-2" style="color: var(--islamic-tosca-500);"></i> Grafik Setoran per
                    Musyrif
                </div>
                <div class="card-body">
                    <canvas id="chartMusyrif" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= GRAFIK JUZ LULUS ================= --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold"><i class="bi bi-graph-up text-success me-2"></i> Grafik Lulus Ujian Akhir per Juz
            </div>
            <ul class="nav nav-pills nav-pills-sm flex-nowrap overflow-auto" id="juzTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-nowrap" id="juz-all-tab" data-coreui-toggle="tab"
                        data-coreui-target="#juz-all" type="button" role="tab">Semua Kelas</button>
                </li>
                @foreach ($kelasList as $k)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="juz-kelas-{{ $k->id }}-tab"
                            data-coreui-toggle="tab" data-coreui-target="#juz-kelas-{{ $k->id }}" type="button"
                            role="tab" title="{{ $k->nama_kelas }}">
                            {{ \Illuminate\Support\Str::limit($k->nama_kelas, 14) }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="juzTabContent">
                <div class="tab-pane fade show active" id="juz-all" role="tabpanel" aria-labelledby="juz-all-tab">
                    <div class="text-center text-muted py-4 d-none" id="noteJuzAll"><i
                            class="bi bi-info-circle me-1"></i> Belum ada santri yang lulus ujian.</div>
                    <canvas id="chartJuzAll" height="200"></canvas>
                </div>
                @foreach ($kelasList as $k)
                    <div class="tab-pane fade" id="juz-kelas-{{ $k->id }}" role="tabpanel"
                        aria-labelledby="juz-kelas-{{ $k->id }}-tab">
                        <div class="text-center text-muted py-4 d-none" id="noteJuzKelas_{{ $k->id }}"><i
                                class="bi bi-info-circle me-1"></i> Belum ada santri yang lulus ujian di kelas ini.</div>
                        <canvas id="chartJuzKelas_{{ $k->id }}" height="200"></canvas>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- TAB REKAP TABLE --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-transparent py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold"><i class="bi bi-table me-2"></i> Rekap Laporan Lengkap</div>
            <ul class="nav nav-pills nav-pills-sm flex-nowrap overflow-auto" id="rekapTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active text-nowrap" id="tab-santri-tab"
                        data-coreui-toggle="tab" data-coreui-target="#tab-santri" type="button" role="tab">Per
                        Santri</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link text-nowrap" id="tab-kelas-tab"
                        data-coreui-toggle="tab" data-coreui-target="#tab-kelas" type="button" role="tab">Per
                        Kelas</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link text-nowrap" id="tab-musyrif-tab"
                        data-coreui-toggle="tab" data-coreui-target="#tab-musyrif" type="button" role="tab">Per
                        Musyrif</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link text-nowrap" id="tab-absensi-tab"
                        data-coreui-toggle="tab" data-coreui-target="#tab-absensi" type="button" role="tab"><i
                            class="bi bi-geo-alt me-1"></i> Histori Kehadiran</button></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- TAB SANTRI --}}
                <div class="tab-pane fade show active" id="tab-santri" role="tabpanel">
                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportSantriExcel"><i
                                class="bi bi-file-earmark-excel"></i> Export Excel</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportSantriPdf"><i
                                class="bi bi-file-earmark-pdf"></i> Export PDF</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                            id="table-rekap-santri">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kelas</th>
                                    <th>Santri</th>
                                    <th>Musyrif</th>
                                    <th>Jumlah Setoran</th>
                                    <th>Hadir Tidak Setor</th>
                                    <th>Alpha</th>
                                    <th>Rata-rata Nilai</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB KELAS --}}
                <div class="tab-pane fade" id="tab-kelas" role="tabpanel">
                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportKelasExcel"><i
                                class="bi bi-file-earmark-excel"></i> Export Excel</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportKelasPdf"><i
                                class="bi bi-file-earmark-pdf"></i> Export PDF</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                            id="table-rekap-kelas">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kelas</th>
                                    <th>Jumlah Santri</th>
                                    <th>Jumlah Setoran</th>
                                    <th>Rata-rata Nilai</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB MUSYRIF --}}
                <div class="tab-pane fade" id="tab-musyrif" role="tabpanel">
                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportMusyrifExcel"><i
                                class="bi bi-file-earmark-excel"></i> Export Excel</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportMusyrifPdf"><i
                                class="bi bi-file-earmark-pdf"></i> Export PDF</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                            id="table-rekap-musyrif">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Musyrif</th>
                                    <th>Jumlah Santri Binaan</th>
                                    <th>Jumlah Setoran</th>
                                    <th>Rata-rata Nilai</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- TAB ABSENSI MUSYRIF --}}
                <div class="tab-pane fade" id="tab-absensi" role="tabpanel">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
                        <div class="alert alert-info border-0 d-flex align-items-center shadow-sm mb-0 flex-grow-1 py-2">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <small>Memantau kehadiran operasional harian Musyrif. Data <strong>Suspect/Rejected</strong>
                                mengindikasikan absensi di luar radius atau manipulasi GPS.</small>
                        </div>

                        {{-- FILTER KHUSUS ABSENSI --}}
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 fw-semibold text-nowrap"><i class="bi bi-calendar3 me-1"></i>
                                Waktu:</label>
                            <select class="form-select form-select-sm border-secondary" id="filter_waktu_absensi"
                                style="min-width: 180px;">
                                <option value="today">Hari Ini</option>
                                <option value="periode" selected>Sesuai Periode Laporan</option>
                                <option value="all">Semua Riwayat (All Time)</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle w-100 text-nowrap"
                            id="table-absensi-musyrif">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Waktu Absen</th>
                                    <th>Musyrif</th>
                                    <th>Sesi</th>
                                    <th>Koordinat & Lokasi</th>
                                    <th>Status</th>
                                    <th>Akurasi / Bukti</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- MODAL DETAIL SANTRI --}}
    <div class="modal fade" id="modalRiwayatSantri" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header text-white"
                    style="background: linear-gradient(90deg, var(--islamic-purple-600), var(--islamic-tosca-500));">
                    <h5 class="modal-title mb-0">Riwayat Hafalan: <span id="detail_nama_santri"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Kelas: <strong id="detail_kelas_santri"></strong> | Musyrif: <strong
                            id="detail_musyrif_santri"></strong></p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered align-middle" id="table-riwayat-santri">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Materi</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Riwayat Santri (Yang sudah ada sebelumnya) --}}
    {{-- MODAL PREVIEW FOTO ABSENSI --}}
    <div class="modal fade" id="modalPreviewPhoto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Bukti Kehadiran</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-3">
                    {{-- Placeholder image, akan di-replace oleh Javascript --}}
                    <img id="previewImage" src="" alt="Foto Absensi" class="img-fluid rounded-3 shadow-sm w-100"
                        style="object-fit: cover; max-height: 70vh;">
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW LOKASI (GOOGLE MAPS iframe) --}}
    <div class="modal fade" id="modalPreviewMap" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background: var(--cui-body-bg);">
                    <h5 class="modal-title fw-bold text-primary"><i class="bi bi-geo-alt text-primary me-2"></i> Preview Lokasi</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Iframe ini menggunakan format embed bawaan GMaps yang gratis (tanpa API key) --}}
                    <iframe id="previewMapIframe" width="100%" height="450" style="border:0; display:block;"
                        allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // === INIT TOOLTIPS ===
            const tooltipTriggerList = document.querySelectorAll('[data-toggle="tooltip"]');
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new coreui.Tooltip(
                tooltipTriggerEl));

            // === DATATABLE REKAP SANTRI ===
            let tableSantri = $('#table-rekap-santri').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.laporan.data') }}',
                    data: function(d) {
                        d.kelas_id = $('#filter_kelas').val();
                        d.musyrif_id = $('#filter_musyrif').val();
                        d.periode = $('#filter_periode').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kelas',
                        name: 'kelas.nama_kelas'
                    },
                    {
                        data: 'nama_santri',
                        name: 'nama'
                    },
                    {
                        data: 'musyrif',
                        name: 'musyrif.nama'
                    },
                    {
                        data: 'total_setor',
                        searchable: false
                    },
                    {
                        data: 'hadir_tidak_setor',
                        searchable: false
                    },
                    {
                        data: 'alpha',
                        searchable: false
                    },
                    {
                        data: 'rata_nilai',
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false
                    },
                ],
                order: [
                    [1, 'asc']
                ],
                drawCallback: function(settings) {
                    let json = settings.json || {};
                    if (!json.summary) return;

                    animateCounter(document.getElementById('kpi_total_santri'), json.summary
                        .total_santri ?? 0);
                    animateCounter(document.getElementById('kpi_total_setor'), json.summary
                        .total_setor ?? 0);
                    animateCounter(document.getElementById('kpi_avg_nilai'), Math.round(json.summary
                        .avg_nilai ?? 0));

                    animateCounter(document.getElementById('kpi_total_musyrif'), json.summary
                        .total_musyrif ?? 0);
                    animateCounter(document.getElementById('kpi_kehadiran_musyrif'), Math.round(json
                        .summary.kehadiran_musyrif ?? 0));
                }
            });

            // === DATATABLE REKAP KELAS ===
            let tableKelas = $('#table-rekap-kelas').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route('admin.laporan.rekap-kelas') }}",
                    data: function(d) {
                        d.kelas_id = $('#filter_kelas').val();
                        d.musyrif_id = $('#filter_musyrif').val();
                        d.periode = $('#filter_periode').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama_kelas',
                        name: 'kelas.nama_kelas'
                    },
                    {
                        data: 'jumlah_santri',
                        searchable: false
                    },
                    {
                        data: 'total_setor',
                        searchable: false
                    },
                    {
                        data: 'rata_nilai',
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ]
            });

            // === DATATABLE REKAP MUSYRIF ===
            let tableMusyrif = $('#table-rekap-musyrif').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.laporan.rekap-musyrif') }}',
                    data: function(d) {
                        d.kelas_id = $('#filter_kelas').val();
                        d.musyrif_id = $('#filter_musyrif').val();
                        d.periode = $('#filter_periode').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama',
                        name: 'musyrifs.nama'
                    },
                    {
                        data: 'jumlah_santri',
                        name: 'jumlah_santri',
                        searchable: false
                    },
                    {
                        data: 'total_setor',
                        name: 'total_setor',
                        searchable: false
                    },
                    {
                        data: 'rata_nilai',
                        name: 'rata_nilai',
                        searchable: false
                    },
                ],
                order: [
                    [1, 'asc']
                ]
            });

            // === DATATABLE ABSENSI MUSYRIF ===
            let tableAbsensi = $('#table-absensi-musyrif').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: '{{ route('admin.laporan.absensi-musyrif') }}',
                    data: function(d) {
                        d.musyrif_id = $('#filter_musyrif').val();
                        d.periode = $('#filter_periode').val();
                        // Lempar value filter waktu khusus absensi
                        d.waktu_absensi = $('#filter_waktu_absensi').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'attendance_at',
                        name: 'attendance_at'
                    },
                    {
                        data: 'musyrif_nama',
                        name: 'm.nama'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'location',
                        name: 'address_text',
                        orderable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'photo',
                        name: 'photo_path',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                ],
                order: [
                    [1, 'desc']
                ]
            });


            // Trigger reload khusus untuk tabel absensi saat filter waktunya diubah
            $('#filter_waktu_absensi').on('change', function() {
                tableAbsensi.ajax.reload();
            });


            // === EVENT HANDLER FILTER ===
            $('#formFilter').on('submit', function(e) {
                e.preventDefault();
                tableSantri.ajax.reload();
                tableKelas.ajax.reload();
                tableMusyrif.ajax.reload();
                tableAbsensi.ajax.reload();
                reloadCharts();
            });

            $('#btnResetFilter').on('click', function() {
                const defaultPeriode = '{{ $defaultPeriode }}';
                $('#filter_kelas').val('');
                $('#filter_musyrif').val('');
                $('#filter_periode').val(defaultPeriode);

                tableSantri.ajax.reload(null, true);
                tableKelas.ajax.reload(null, true);
                tableMusyrif.ajax.reload(null, true);
                tableAbsensi.ajax.reload(null, true);

                animateCounter(document.getElementById('kpi_total_santri'), 0);
                animateCounter(document.getElementById('kpi_total_setor'), 0);
                animateCounter(document.getElementById('kpi_avg_nilai'), 0);
                animateCounter(document.getElementById('kpi_total_musyrif'), 0);
                animateCounter(document.getElementById('kpi_kehadiran_musyrif'), 0);

                reloadCharts();
            });


            // === EVENT HANDLER DETAIL SANTRI ===
            $('#table-rekap-santri').on('click', '.btn-detail-santri', function() {
                let santriId = $(this).data('id');
                let periode = $('#filter_periode').val();

                $('#detail_nama_santri').text($(this).data('nama'));
                $('#detail_kelas_santri').text('-');
                $('#detail_musyrif_santri').text('-');
                $('#table-riwayat-santri tbody').html(
                    '<tr><td colspan="5" class="text-center">Memuat data...</td></tr>');

                let url = '{{ route('admin.laporan.riwayat-santri', ':id') }}';
                url = url.replace(':id', santriId);

                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        periode: periode
                    },
                    success: function(res) {
                        if (res.santri) {
                            $('#detail_nama_santri').text(res.santri.nama ?? '');
                            $('#detail_kelas_santri').text(res.santri.kelas ?? '-');
                            $('#detail_musyrif_santri').text(res.santri.musyrif ?? '-');
                        }

                        let rows = '';
                        if (res.riwayat && res.riwayat.length > 0) {
                            res.riwayat.forEach(function(item) {
                                rows += '<tr>' +
                                    '<td>' + (item.tanggal_setoran ?? '-') + '</td>' +
                                    '<td>' + (item.materi ?? '-') + '</td>' +
                                    '<td>' + (item.status ?? '-') + '</td>' +
                                    '<td>' + (item.nilai_label ?? '-') + '</td>' +
                                    '<td>' + (item.catatan ?? '-') + '</td>' +
                                    '</tr>';
                            });
                        } else {
                            rows =
                                '<tr><td colspan="5" class="text-center">Belum ada setoran pada periode ini.</td></tr>';
                        }

                        $('#table-riwayat-santri tbody').html(rows);

                        let modal = new coreui.Modal(document.getElementById(
                            'modalRiwayatSantri'));
                        modal.show();
                    },
                    error: function() {
                        $('#table-riwayat-santri tbody').html(
                            '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data.</td></tr>'
                        );
                        let modal = new coreui.Modal(document.getElementById(
                            'modalRiwayatSantri'));
                        modal.show();
                    }
                });
            });


            // === EXPORT BUTTONS ===
            function buildQueryString() {
                let params = new URLSearchParams();
                if ($('#filter_kelas').val()) params.append('kelas_id', $('#filter_kelas').val());
                if ($('#filter_musyrif').val()) params.append('musyrif_id', $('#filter_musyrif').val());
                if ($('#filter_periode').val()) params.append('periode', $('#filter_periode').val());
                return params.toString();
            }

            $('#btnExportSantriExcel').on('click', function() {
                window.location.href = '{{ route('admin.laporan.export-santri-excel') }}' + '?' +
                    buildQueryString();
            });
            $('#btnExportSantriPdf').on('click', function() {
                window.location.href = '{{ route('admin.laporan.export-santri-pdf') }}' + '?' +
                    buildQueryString();
            });

            $('#btnExportKelasExcel').on('click', function() {
                window.location.href = '{{ route('admin.laporan.export-kelas-excel') }}' + '?' +
                    buildQueryString();
            });
            $('#btnExportKelasPdf').on('click', function() {
                window.location.href = '{{ route('admin.laporan.export-kelas-pdf') }}' + '?' +
                    buildQueryString();
            });

            $('#btnExportMusyrifExcel').on('click', function() {
                window.location.href = '{{ route('admin.laporan.export-musyrif-excel') }}' + '?' +
                    buildQueryString();
            });
            $('#btnExportMusyrifPdf').on('click', function() {
                window.location.href = '{{ route('admin.laporan.export-musyrif-pdf') }}' + '?' +
                    buildQueryString();
            });


            // === CHARTS INISIALISASI & LOGIC ===
            let ctxKelas = document.getElementById('chartKelas').getContext('2d');
            let ctxMusyrif = document.getElementById('chartMusyrif').getContext('2d');

            let chartKelas = new Chart(ctxKelas, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: [],
                        backgroundColor: '#6b4eff',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            let chartMusyrif = new Chart(ctxMusyrif, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: [],
                        backgroundColor: '#13a3b3',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            function reloadCharts() {
                let params = {
                    kelas_id: $('#filter_kelas').val(),
                    musyrif_id: $('#filter_musyrif').val(),
                    periode: $('#filter_periode').val()
                };

                $.get('{{ route('admin.laporan.chart-kelas') }}', params, function(res) {
                    chartKelas.data.labels = res.labels || [];
                    chartKelas.data.datasets[0].data = res.data || [];
                    chartKelas.update();
                });

                $.get('{{ route('admin.laporan.chart-musyrif') }}', params, function(res) {
                    chartMusyrif.data.labels = res.labels || [];
                    chartMusyrif.data.datasets[0].data = res.data || [];
                    chartMusyrif.update();
                });

                // Trigger re-render of active Juz tab
                let activeJuzTab = document.querySelector('#juzTabs .nav-link.active');
                if (activeJuzTab) {
                    let targetId = activeJuzTab.getAttribute('data-coreui-target');
                    if (targetId === '#juz-all') {
                        renderBarJuz('chartJuzAll', null, 'Jumlah Santri Lulus Ujian Akhir (Semua Kelas)');
                    } else {
                        let match = targetId.match(/juz-kelas-(\d+)/);
                        if (match) {
                            let kId = match[1];
                            renderBarJuz(`chartJuzKelas_${kId}`, kId,
                                `Jumlah Santri Lulus Ujian Akhir (${activeJuzTab.title})`);
                        }
                    }
                }
            }

            // Init charts saat halaman load
            reloadCharts();

            // === LOGIC GRAFIK JUZ LULUS ===
            const chartCache = new Map();

            function destroyIfExists(canvasId) {
                if (chartCache.has(canvasId)) {
                    chartCache.get(canvasId).destroy();
                    chartCache.delete(canvasId);
                }
            }

            function isAllZero(arr) {
                return arr.every(v => Number(v) === 0);
            }

            async function fetchJuzData(kelasId = null) {
                const baseUrl = @json(route('admin.laporan.chart.juz-lulus'));
                const url = kelasId ? `${baseUrl}?kelas_id=${encodeURIComponent(kelasId)}` : baseUrl;

                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('Gagal ambil data grafik');
                return await res.json();
            }

            async function renderBarJuz(canvasId, kelasId = null, labelTitle = '') {
                const canvas = document.getElementById(canvasId);
                if (!canvas) return;

                const noteId = kelasId ? `noteJuzKelas_${kelasId}` : 'noteJuzAll';
                const noteEl = document.getElementById(noteId);

                try {
                    const json = await fetchJuzData(kelasId);

                    if (isAllZero(json.data)) {
                        canvas.classList.add('d-none');
                        destroyIfExists(canvasId);
                        if (noteEl) noteEl.classList.remove('d-none');
                        return;
                    }

                    if (noteEl) noteEl.classList.add('d-none');
                    canvas.classList.remove('d-none');

                    destroyIfExists(canvasId);

                    const ctx = canvas.getContext('2d');
                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: json.labels,
                            datasets: [{
                                label: labelTitle,
                                data: json.data,
                                backgroundColor: '#198754',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });

                    chartCache.set(canvasId, chart);
                } catch (error) {
                    console.error("Gagal merender grafik Juz Lulus", error);
                }
            }

            // Event listener saat ganti tab Juz Lulus
            document.querySelectorAll('#juzTabs [data-coreui-toggle="tab"]').forEach(btn => {
                btn.addEventListener('shown.coreui.tab', async (e) => {
                    const target = e.target.getAttribute('data-coreui-target');
                    if (!target) return;

                    if (target === '#juz-all') {
                        await renderBarJuz('chartJuzAll', null,
                            'Jumlah Santri Lulus Ujian Akhir (Semua Kelas)');
                        return;
                    }

                    const match = target.match(/juz-kelas-(\d+)/);
                    if (!match) return;

                    const kelasId = match[1];
                    const tabEl = document.getElementById(`juz-kelas-${kelasId}-tab`);
                    await renderBarJuz(`chartJuzKelas_${kelasId}`, kelasId,
                        `Jumlah Santri Lulus Ujian Akhir (${tabEl?.title ?? 'Kelas'})`);
                });
            });


            // === HELPER ANIMASI COUNTER ===
            function animateCounter(el, target) {
                if (!el) return;
                const duration = 800;
                const frameRate = 30;
                const totalFrames = Math.round(duration / (1000 / frameRate));
                let frame = 0;
                const start = parseInt(el.textContent.replace(/\D/g, '')) || 0;

                const interval = setInterval(() => {
                    frame++;
                    const progress = frame / totalFrames;
                    const value = Math.round(start + (target - start) * easeOut(progress));
                    el.textContent = value.toLocaleString('id-ID');

                    if (frame >= totalFrames) {
                        el.textContent = target.toLocaleString('id-ID');
                        clearInterval(interval);
                    }
                }, 1000 / frameRate);
            }

            function easeOut(t) {
                return 1 - Math.pow(1 - t, 3);
            }

            // ==========================================
            // EVENT LISTENER UNTUK MODAL ABSENSI
            // ==========================================

            // 1. Klik Preview Foto
            $('#table-absensi-musyrif').on('click', '.btn-preview-photo', function() {
                let url = $(this).data('url');

                // Set source image di dalam modal
                $('#previewImage').attr('src', url);

                // Tampilkan modal
                let modal = new coreui.Modal(document.getElementById('modalPreviewPhoto'));
                modal.show();
            });

            // 2. Klik Preview Maps
            $('#table-absensi-musyrif').on('click', '.btn-preview-map', function() {
                let lat = $(this).data('lat');
                let lng = $(this).data('lng');

                // URL ini adalah trik menggunakan widget embed Google Maps gratisan
                let embedUrl =
                    `https://maps.google.com/maps?q=${lat},${lng}&t=&z=16&ie=UTF8&iwloc=&output=embed`;

                // Set source iframe
                $('#previewMapIframe').attr('src', embedUrl);

                // Tampilkan modal
                let modal = new coreui.Modal(document.getElementById('modalPreviewMap'));
                modal.show();
            });

            // 3. Hapus cache iframe saat modal map ditutup (agar tidak membebani browser)
            document.getElementById('modalPreviewMap').addEventListener('hidden.coreui.modal', function() {
                document.getElementById('previewMapIframe').src = '';
            });
        });
    </script>
@endpush
