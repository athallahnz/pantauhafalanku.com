@extends('layouts.app')

@section('title', 'Laporan Hafalan Departemen Al Qur\'an')

@section('content')
    <style>
        .kpi-card {

            border-radius: 16px;

            background: linear-gradient(180deg,
                    #ffffff 0%,
                    #fbfcfd 100%);

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);

            transition: all .25s ease;
        }

        .kpi-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px rgba(0, 0, 0, 0.06),
                0 3px 6px rgba(0, 0, 0, 0.08);
        }

        .kpi-label {

            font-size: 12px;

            letter-spacing: .08em;

            text-transform: uppercase;

            color: #6c757d;

            margin-bottom: 6px;
        }

        .kpi-value {

            font-size: 32px;

            font-weight: 700;

            letter-spacing: -0.02em;
        }

        .kpi-icon {

            width: 42px;

            height: 42px;

            border-radius: 12px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }

        .kpi-progress {

            height: 6px;

            background: #edf1f5;

            border-radius: 20px;

            overflow: hidden;
        }

        .kpi-progress-bar {

            height: 100%;

            width: 0;

            border-radius: 20px;

            transition: width .8s ease;
        }
    </style>

    {{-- ================= KPI / RINGKASAN ================= --}}
    <div class="row mb-4 g-3">
        {{-- Total Santri --}}
        <div class="col-lg-4 col-md-6">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">
                                Total Santri
                            </div>
                            <div class="kpi-value count-up" data-target="0" id="kpi_total_santri">
                                0
                            </div>
                        </div>
                        <div class="kpi-icon bg-primary-subtle text-primary">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-primary" id="kpi_bar_santri" style="width:0%">
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Total Setoran --}}
        <div class="col-lg-4 col-md-6">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">
                                Total Setoran
                            </div>
                            <div class="kpi-value count-up" data-target="0" id="kpi_total_setor">
                                0
                            </div>
                        </div>
                        <div class="kpi-icon bg-success-subtle text-success">
                            <i class="bi bi-journal-check"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-success" id="kpi_bar_setor" style="width:0%">
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Rata Nilai --}}
        <div class="col-lg-4 col-md-6">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">
                                Rata Nilai
                            </div>

                            <div class="kpi-value count-up" data-target="0" id="kpi_avg_nilai">
                                0
                            </div>
                        </div>
                        <div class="kpi-icon bg-warning-subtle text-warning">
                            <i class="bi bi-star"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-warning" id="kpi_bar_nilai" style="width:0%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- GRAFIK --}}
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    Grafik Jumlah Setoran per Kelas
                </div>
                <div class="card-body">
                    <canvas id="chartKelas" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    Grafik Jumlah Setoran per Musyrif
                </div>
                <div class="card-body">
                    <canvas id="chartMusyrif" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= GRAFIK JUZ LULUS (TAB SEMUA + PER KELAS) ================= --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold">Grafik Lulus Ujian Akhir per Juz (1–30)</div>

            <ul class="nav nav-pills nav-pills-sm flex-nowrap overflow-auto" id="juzTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-nowrap" id="juz-all-tab" data-coreui-toggle="tab"
                        data-coreui-target="#juz-all" type="button" role="tab">
                        Semua Kelas
                    </button>
                </li>

                @foreach ($kelasList as $k)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-nowrap" id="juz-kelas-{{ $k->id }}-tab" data-coreui-toggle="tab"
                            data-coreui-target="#juz-kelas-{{ $k->id }}" type="button" role="tab"
                            title="{{ $k->nama_kelas }}">
                            {{ \Illuminate\Support\Str::limit($k->nama_kelas, 14) }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content" id="juzTabContent">

                {{-- TAB: SEMUA --}}
                <div class="tab-pane fade show active" id="juz-all" role="tabpanel" aria-labelledby="juz-all-tab">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-center text-muted py-4 d-none" id="noteJuzAll">
                                <i class="bi bi-info-circle me-1"></i>
                                Belum ada santri yang lulus ujian.
                            </div>

                            <canvas id="chartJuzAll" height="130"></canvas>
                        </div>
                    </div>
                </div>

                {{-- TAB: PER KELAS --}}
                @foreach ($kelasList as $k)
                    <div class="tab-pane fade" id="juz-kelas-{{ $k->id }}" role="tabpanel"
                        aria-labelledby="juz-kelas-{{ $k->id }}-tab">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="text-center text-muted py-4 d-none" id="noteJuzKelas_{{ $k->id }}">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Belum ada santri yang lulus ujian di kelas ini.
                                </div>

                                <canvas id="chartJuzKelas_{{ $k->id }}" height="130"></canvas>
                            </div>

                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">
            Filter Laporan
        </div>
        <div class="card-body">
            <form class="row g-3 align-items-end" id="formFilter">
                {{-- Kelas --}}
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold">Kelas</label>
                    <select class="form-select" name="kelas_id" id="filter_kelas">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasList as $kelas)
                            <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Musyrif --}}
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold">Musyrif</label>
                    <select class="form-select" name="musyrif_id" id="filter_musyrif">
                        <option value="">Semua Musyrif</option>
                        @foreach ($musyrifList as $musyrif)
                            <option value="{{ $musyrif->id }}">{{ $musyrif->nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Periode --}}
                <div class="col-md-3 col-sm-6">
                    <label class="form-label fw-semibold">Periode (Bulan)</label>
                    <input type="month" class="form-control" id="filter_periode" value="{{ $defaultPeriode }}">
                </div>

                {{-- Aksi: Terapkan + Reset --}}
                <div class="col-md-3 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        Terapkan
                    </button>

                    <button type="button" class="btn btn-danger text-white w-100" id="btnResetFilter">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TAB REKAP --}}
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold">Rekap Laporan</div>

            <ul class="nav nav-pills nav-pills-sm flex-nowrap overflow-auto" id="rekapTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-nowrap" id="tab-santri-tab" data-coreui-toggle="tab"
                        data-coreui-target="#tab-santri" type="button" role="tab">
                        Per Santri
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link text-nowrap" id="tab-kelas-tab" data-coreui-toggle="tab"
                        data-coreui-target="#tab-kelas" type="button" role="tab">
                        Per Kelas
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link text-nowrap" id="tab-musyrif-tab" data-coreui-toggle="tab"
                        data-coreui-target="#tab-musyrif" type="button" role="tab">
                        Per Musyrif
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">
                {{-- TAB SANTRI --}}
                <div class="tab-pane fade show active" id="tab-santri" role="tabpanel">
                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportSantriExcel">
                            Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportSantriPdf">
                            Export PDF
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="table-rekap-santri">
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
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportKelasExcel">
                            Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportKelasPdf">
                            Export PDF
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="table-rekap-kelas">
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
                        <button type="button" class="btn btn-sm btn-outline-success" id="btnExportMusyrifExcel">
                            Export Excel
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="btnExportMusyrifPdf">
                            Export PDF
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle" id="table-rekap-musyrif">
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

            </div>
        </div>
    </div>

@endsection

@push('modals')
    {{-- MODAL DETAIL RIWAYAT SANTRI --}}
    <div class="modal fade" id="modalRiwayatSantri" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Riwayat Hafalan Santri: <span id="detail_nama_santri"></span>
                    </h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Kelas: <strong id="detail_kelas_santri"></strong><br>
                        Musyrif: <strong id="detail_musyrif_santri"></strong>
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle" id="table-riwayat-santri">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Materi</th>
                                    <th>Status</th>
                                    <th>Nilai</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- diisi via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-coreui-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            // DataTable Rekap per Santri
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

                    animateCounter(
                        document.getElementById('kpi_total_santri'),
                        json.summary.total_santri ?? 0
                    );

                    animateCounter(
                        document.getElementById('kpi_total_setor'),
                        json.summary.total_setor ?? 0
                    );

                    animateCounter(
                        document.getElementById('kpi_avg_nilai'),
                        Math.round(json.summary.avg_nilai ?? 0)
                    );
                }

            });

            // DataTable Rekap per Kelas
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

                columns: [

                    {
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

            // DataTable Rekap per Musyrif
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
                ],
            });

            const chartCache = new Map(); // key: canvasId -> chart instance

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
                const baseUrl = @json(route('admin.laporan.chart.juz-lulus')); // ✅ ini sudah /admin/...

                const url = kelasId ?
                    `${baseUrl}?kelas_id=${encodeURIComponent(kelasId)}` :
                    baseUrl;

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

                const noteId = kelasId ?
                    `noteJuzKelas_${kelasId}` :
                    'noteJuzAll';

                const noteEl = document.getElementById(noteId);

                const json = await fetchJuzData(kelasId);

                // 👉 Jika semua data 0
                if (isAllZero(json.data)) {
                    // sembunyikan chart
                    canvas.classList.add('d-none');

                    // destroy chart kalau ada
                    destroyIfExists(canvasId);

                    // tampilkan note
                    if (noteEl) noteEl.classList.remove('d-none');
                    return;
                }

                // 👉 Jika ada data
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
                            data: json.data
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
            }

            // Lazy init: render tab default + render saat tab dibuka
            (async function initJuzCharts() {
                await renderBarJuz('chartJuzAll', null, 'Jumlah Santri Lulus Ujian Akhir (Semua Kelas)');

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
                        await renderBarJuz(`chartJuzKelas_${kelasId}`, kelasId,
                            `Jumlah Santri Lulus Ujian Akhir (${document.getElementById(`juz-kelas-${kelasId}-tab`)?.title ?? 'Kelas'})`
                        );
                    });
                });
            })();

            // === INIT CHARTS ===
            let ctxKelas = document.getElementById('chartKelas').getContext('2d');
            let ctxMusyrif = document.getElementById('chartMusyrif').getContext('2d');

            let chartKelas = new Chart(ctxKelas, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: [],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                    },
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

            let chartMusyrif = new Chart(ctxMusyrif, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: [],
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                    },
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

            function reloadCharts() {
                let params = {
                    kelas_id: $('#filter_kelas').val(),
                    musyrif_id: $('#filter_musyrif').val(),
                    periode: $('#filter_periode').val()
                };

                // Chart Kelas
                $.get('{{ route('admin.laporan.chart-kelas') }}', params, function(res) {
                    chartKelas.data.labels = res.labels || [];
                    chartKelas.data.datasets[0].data = res.data || [];
                    chartKelas.update();
                });

                // Chart Musyrif
                $.get('{{ route('admin.laporan.chart-musyrif') }}', params, function(res) {
                    chartMusyrif.data.labels = res.labels || [];
                    chartMusyrif.data.datasets[0].data = res.data || [];
                    chartMusyrif.update();
                });
            }

            // Panggil saat pertama kali load
            reloadCharts();

            // Submit filter → reload semua DataTables
            $('#formFilter').on('submit', function(e) {
                e.preventDefault();
                tableSantri.ajax.reload();
                tableKelas.ajax.reload();
                tableMusyrif.ajax.reload();

                reloadCharts();
            });

            // ==== TOMBOL RESET ====
            $('#btnResetFilter').on('click', function() {
                const defaultPeriode = '{{ $defaultPeriode }}';

                // Reset nilai filter
                $('#filter_kelas').val('');
                $('#filter_musyrif').val('');
                $('#filter_periode').val(defaultPeriode);

                // Reload semua DataTables
                tableSantri.ajax.reload(null, true); // -> reload + reset paging
                tableKelas.ajax.reload(null, true);
                tableMusyrif.ajax.reload(null, true);

                // OPTIONAL: KPI reset sementara (biar tidak misleading)
                animateCounter(document.getElementById('kpi_total_santri'), 0);
                animateCounter(document.getElementById('kpi_total_setor'), 0);
                animateCounter(document.getElementById('kpi_avg_nilai'), 0);
                // Reload charts    

                reloadCharts();
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

            // Event klik tombol Detail di Rekap Santri
            $('#table-rekap-santri').on('click', '.btn-detail-santri', function() {
                let santriId = $(this).data('id');
                let periode = $('#filter_periode').val();

                $('#detail_nama_santri').text($(this).data('nama'));
                $('#detail_kelas_santri').text('-');
                $('#detail_musyrif_santri').text('-');
                $('#table-riwayat-santri tbody').html('<tr><td colspan="4">Memuat data...</td></tr>');

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
                                '<tr><td colspan="4" class="text-center">Belum ada setoran pada periode ini.</td></tr>';
                        }

                        $('#table-riwayat-santri tbody').html(rows);

                        let modal = new coreui.Modal(document.getElementById(
                            'modalRiwayatSantri'));
                        modal.show();
                    },
                    error: function() {
                        $('#table-riwayat-santri tbody').html(
                            '<tr><td colspan="4" class="text-center text-danger">Gagal memuat data.</td></tr>'
                        );
                        let modal = new coreui.Modal(document.getElementById(
                            'modalRiwayatSantri'));
                        modal.show();
                    }
                });
            });

            function animateCounter(el, target) {
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

        });
    </script>
@endpush
