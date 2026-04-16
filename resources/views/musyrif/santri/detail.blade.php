@extends('layouts.app')

@section('title', 'Detail Santri')

@section('content')
    <style>
        /* ================= TEMA ISLAMIC PURPLE & GLASSMORPHISM ================= */
        :root {
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #fff !important;
        }

        /* KPI & Main Card Style (Glassmorphism) */
        .kpi-card,
        .card-main {
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
            overflow: hidden;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.7) !important;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.1);
            border-color: rgba(111, 66, 193, 0.2) !important;
        }

        /* KPI Elements */
        .kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 5px;
            color: #9aa0a6;
        }

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: var(--transition-smooth);
        }

        .kpi-card:hover .kpi-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        /* Progress Bar Refinement */
        .kpi-progress {
            height: 6px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            margin-top: 15px;
        }

        .kpi-progress-bar {
            border-radius: 10px;
            transition: width 1.5s cubic-bezier(0.1, 0.5, 0.5, 1);
        }

        /* Container Progress (Juz List) */
        .enterprise-progress-container {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .enterprise-progress-container::-webkit-scrollbar {
            width: 4px;
        }

        .enterprise-progress-container::-webkit-scrollbar-thumb {
            background: #e0e0e0;
            border-radius: 10px;
        }

        .enterprise-progress-row {
            margin-bottom: 18px;
        }

        .enterprise-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .enterprise-juz {
            font-weight: 700;
            font-size: 14px;
        }

        .enterprise-percent {
            font-weight: 700;
            font-size: 12px;
            color: var(--islamic-purple-600);
        }

        .enterprise-progress {
            height: 8px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 999px;
            overflow: hidden;
        }

        .enterprise-progress .progress-bar {
            width: 0;
            transition: width 1.2s cubic-bezier(.4, 0, .2, 1);
            box-shadow: 0 0 10px rgba(111, 66, 193, 0.2);
        }

        /* Section Header */
        .section-title {
            font-size: 13px;
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        /* Table Padding */
        .table-padding-container {
            padding: 1.5rem !important;
        }

        #timelineTable th,
        #timelineTable td {
            white-space: nowrap !important;
            padding: 1.1rem 1rem !important;
        }

        /* FIX DARK THEME */
        [data-coreui-theme="dark"] .kpi-card,
        [data-coreui-theme="dark"] .card-main {
            background: rgba(42, 42, 53, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-coreui-theme="dark"] .kpi-value,
        [data-coreui-theme="dark"] .section-title {
            color: #ffffff !important;
        }

        [data-coreui-theme="dark"] .kpi-label {
            color: #a0a0a0;
        }
    </style>

    <div class="row mb-4 align-items-center px-3 px-md-0 g-3">
        <div class="col-12 col-md-auto text-start">
            <h4 class="fw-bold text-adaptive-purple mb-1">{{ $santri->nama }}</h4>
            <p class="text-muted small mb-0">
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3">Kelas:
                    {{ $santri->kelas?->nama_kelas ?? '-' }}</span>
                @if ($santri->nis)
                    <span class="ms-2 opacity-50">|</span> <span class="ms-2">NIS: {{ $santri->nis }}</span>
                @endif
            </p>
        </div>
        <div class="col-12 col-md-auto ms-auto text-end">
            <a href="{{ route('musyrif.santri.index') }}"
                class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-bold w-100 w-md-auto">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    {{-- KPI SECTION (GLASSMORPHISM) --}}
    <div class="row g-3 mb-4">
        {{-- Total Setor --}}
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Setor</div>
                            <div class="kpi-value text-success">{{ $totalSetor ?? 0 }}</div>
                        </div>
                        <div class="kpi-icon bg-success-subtle text-success shadow-sm rounded-3 p-2">
                            <i class="bi bi-journal-check"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="progress-bar kpi-progress-bar bg-success"
                            style="height: 6px; width: {{ min(100, ($totalSetor ?? 0) * 5) }}%"></div>
                    </div>
                    <div class="kpi-sub fst-italic mt-1" style="font-size: 11px;">Lulus / Ulang</div>
                </div>
            </div>
        </div>

        {{-- Hadir Tidak Setor --}}
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Hadir (TS)</div>
                            <div class="kpi-value text-warning">{{ $totalHadirTidakSetor ?? 0 }}</div>
                        </div>
                        <div class="kpi-icon bg-warning-subtle text-warning shadow-sm rounded-3 p-2">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="progress-bar kpi-progress-bar bg-warning"
                            style="height: 6px; width: {{ min(100, ($totalHadirTidakSetor ?? 0) * 10) }}%"></div>
                    </div>
                    <div class="kpi-sub fst-italic mt-1" style="font-size: 11px;">Tidak Setor</div>
                </div>
            </div>
        </div>

        {{-- Sakit --}}
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Sakit</div>
                            <div class="kpi-value text-primary">{{ $totalSakit ?? 0 }}</div>
                        </div>
                        <div class="kpi-icon bg-primary-subtle text-primary shadow-sm rounded-3 p-2">
                            <i class="bi bi-heart-pulse"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="progress-bar kpi-progress-bar bg-primary"
                            style="height: 6px; width: {{ min(100, ($totalSakit ?? 0) * 10) }}%"></div>
                    </div>
                    <div class="kpi-sub fst-italic mt-1" style="font-size: 11px;">Izin Sakit</div>
                </div>
            </div>
        </div>

        {{-- Izin --}}
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Izin</div>
                            <div class="kpi-value text-secondary">{{ $totalIzin ?? 0 }}</div>
                        </div>
                        <div class="kpi-icon bg-secondary-subtle text-secondary shadow-sm rounded-3 p-2">
                            <i class="bi bi-envelope-paper"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="progress-bar kpi-progress-bar bg-secondary"
                            style="height: 6px; width: {{ min(100, ($totalIzin ?? 0) * 10) }}%"></div>
                    </div>
                    <div class="kpi-sub fst-italic mt-1" style="font-size: 11px;">Izin Syar'i</div>
                </div>
            </div>
        </div>

        {{-- Alpha --}}
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card kpi-card h-100 border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Alpha</div>
                            <div class="kpi-value text-danger">{{ $totalAlpha ?? 0 }}</div>
                        </div>
                        <div class="kpi-icon bg-danger-subtle text-danger shadow-sm rounded-3 p-2">
                            <i class="bi bi-x-octagon"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="progress-bar kpi-progress-bar bg-danger"
                            style="height: 6px; width: {{ min(100, ($totalAlpha ?? 0) * 10) }}%"></div>
                    </div>
                    <div class="kpi-sub fst-italic mt-1" style="font-size: 11px;">Tanpa Keterangan</div>
                </div>
            </div>
        </div>

        {{-- Rata Rata Nilai --}}
        <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">
            <div class="card kpi-card h-100 border-0 shadow-sm"
                style="background: linear-gradient(135deg, var(--islamic-purple-50) 0%, #ffffff 100%);">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Rata Nilai</div>
                            <div class="kpi-value" style="color: var(--islamic-purple-600);">{{ $avgNilai ?? 0 }}</div>
                        </div>
                        <div class="kpi-icon shadow-sm rounded-3 p-2"
                            style="background-color: var(--islamic-purple-100); color: var(--islamic-purple-600);">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="progress-bar kpi-progress-bar"
                            style="height: 6px; background-color: var(--islamic-purple-500); width: {{ min(100, $avgNilai ?? 0) }}%">
                        </div>
                    </div>
                    <div class="kpi-sub fst-italic mt-1" style="font-size: 11px;">Indeks Prestasi</div>
                </div>
            </div>
        </div>
    </div>

    {{-- OVERALL PROGRESS CARD --}}
    <div class="card card-main mb-4 overflow-hidden border-0">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-adaptive-purple">Overall Hafalan Summary</h6>
                    <small class="text-muted">Dihitung berdasarkan tahap tertinggi yang diselesaikan per juz.</small>
                </div>
                <div class="text-end">
                    <span class="display-6 fw-bold text-primary">{{ $overallPct }}<small
                            style="font-size: 20px;">%</small></span>
                </div>
            </div>
            <div class="progress" style="height: 16px; border-radius: 50px; background: rgba(0,0,0,0.05);">
                <div class="progress-bar progress-bar-animated progress-bar-striped" role="progressbar"
                    style="width: {{ $overallPct }}%; background-color: var(--islamic-purple-600);"
                    aria-valuenow="{{ $overallPct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        {{-- PROGRESS LIST --}}
        <div class="col-lg-6">
            <div class="card card-main h-100 border-0">
                <div class="card-header bg-transparent py-3 px-4 border-bottom">
                    <span class="section-title"><i class="bi bi-list-stars me-2"></i>Progress per Juz (Ringkas)</span>
                </div>
                <div class="card-body enterprise-progress-container p-4">
                    @foreach ($progressPerJuz as $p)
                        @if ($p['pct'] > 0 || $p['tahap'])
                            <div class="enterprise-progress-row" data-coreui-toggle="tooltip"
                                title="Juz {{ $p['juz'] }} • Status: {{ $p['status'] }} @if ($p['tahap']) • Tahap: {{ ucfirst(str_replace('_', ' ', $p['tahap'])) }} @endif">
                                <div class="enterprise-progress-header">
                                    <div class="enterprise-juz text-adaptive-purple">Juz {{ $p['juz'] }}</div>
                                    <div class="enterprise-meta">
                                        @if ($p['tahap'])
                                            <span class="badge bg-light text-dark border-0 shadow-sm"
                                                style="font-size: 9px;">{{ strtoupper(str_replace('_', ' ', $p['tahap'])) }}</span>
                                        @endif
                                        <span class="badge bg-{{ $p['color'] }} shadow-sm"
                                            style="font-size: 9px;">{{ $p['status'] }}</span>
                                        <span class="enterprise-percent">{{ $p['pct'] }}%</span>
                                    </div>
                                </div>
                                <div class="progress enterprise-progress">
                                    <div class="progress-bar bg-{{ $p['color'] }}" data-width="{{ $p['pct'] }}">
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- CHART --}}
        <div class="col-lg-6">
            <div class="card card-main h-100 border-0">
                <div class="card-header bg-transparent py-3 px-4 border-bottom">
                    <span class="section-title"><i class="bi bi-activity me-2"></i>Analitik Capaian (%)</span>
                </div>
                <div class="card-body p-4">
                    <canvas id="chartJuzPct" style="height: 250px !important;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- JUZ MAPPING VISUALIZATION --}}
    <div class="card card-main border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-bold mb-0 text-adaptive-purple">Visualisasi Segmentasi Juz (1-30)</span>
                <span class="fw-bold text-primary">{{ $overallPct }}% Complete</span>
            </div>
            <div class="progress"
                style="height:12px; border-radius:999px; overflow:hidden; background: rgba(0,0,0,0.05);">
                @foreach ($progressPerJuz as $p)
                    <div class="progress-bar bg-{{ $p['color'] }}" role="progressbar" style="width:0%"
                        data-width="{{ 100 / 30 }}" data-coreui-toggle="tooltip"
                        title="Juz {{ $p['juz'] }} • {{ $p['pct'] }}% • {{ $p['status'] }} @if ($p['tahap']) • Tahap: {{ ucfirst(str_replace('_', ' ', $p['tahap'])) }} @endif">
                    </div>
                @endforeach
            </div>
            <div class="text-muted small mt-3"><i class="bi bi-info-circle me-1"></i> Segment mewakili setiap juz • warna
                menunjukkan status progress.</div>
        </div>
    </div>

    {{-- TIMELINE TABLE --}}
    <div class="card card-main overflow-hidden border-0">
        <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <span class="section-title">Timeline Setoran Hafalan</span>
            <span class="badge bg-dark rounded-pill shadow-sm">Riwayat Aktif</span>
        </div>
        <div class="card-body table-padding-container table-responsive p-0">
            <table id="timelineTable" class="table table-hover align-middle w-100 mb-0">
                <thead class="bg-light bg-opacity-50">
                    <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                        <th class="ps-4">No</th>
                        <th>Tanggal</th>
                        <th>Juz</th>
                        <th>Surah / Ayat</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th class="text-end pe-4">Catatan</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animasi Progress Bars
            const allBars = document.querySelectorAll('.progress-bar[data-width]');
            allBars.forEach((bar, index) => {
                const width = bar.dataset.width;
                bar.style.width = "0%";
                setTimeout(() => {
                    bar.style.transition = "width 1.2s cubic-bezier(0.1, 0.5, 0.5, 1)";
                    bar.style.width = width + "%";
                }, 150 + (index * 50));
            });

            // Tooltip Init
            document.querySelectorAll('[data-coreui-toggle="tooltip"]').forEach(el => new coreui.Tooltip(el));

            // Chart.js (UTUH)
            const juzLabels = @json(collect($progressPerJuz)->pluck('juz')->map(fn($j) => 'Juz ' . $j)->values());
            const juzPct = @json(collect($progressPerJuz)->pluck('pct')->values());
            const ctx = document.getElementById('chartJuzPct');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: juzLabels,
                        datasets: [{
                            label: 'Capaian (%)',
                            data: juzPct,
                            fill: true,
                            backgroundColor: 'rgba(111, 66, 193, 0.08)',
                            borderColor: '#6f42c1',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: '#6f42c1'
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1500,
                            easing: 'easeOutQuart'
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                grid: {
                                    drawBorder: false
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
            }

            // DataTable (UTUH)
            $('#timelineTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('musyrif.santri.timeline', $santri->id) }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal',
                        searchable: false
                    },
                    {
                        data: 'juz',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'surah_ayat',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nilai',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'catatan',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                pageLength: 10,
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari riwayat...",
                    lengthMenu: "_MENU_ baris"
                }
            });
        });
    </script>
@endpush
