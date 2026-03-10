@extends('layouts.app')

@section('title', 'Dashboard Hafalan Saya')

@section('content')
    <style>
        /* ================= KONSISTENSI TEMA ISLAMIC PURPLE & GLASSMORPHISM ================= */
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
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #9aa0a6;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .kpi-sub {
            font-size: 0.75rem;
            color: #9aa0a6;
            margin-top: 4px;
            line-height: 1.2;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
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

        /* Welcome Banner */
        .welcome-card {
            background: linear-gradient(135deg, var(--islamic-purple-700), #8e44ad);
            border-radius: 24px;
            color: white;
            border: none;
            overflow: hidden;
            position: relative;
        }

        .welcome-card::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        /* Container Progress (Juz List) */
        .enterprise-progress-container {
            max-height: 400px;
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

        /* ================= FIX ADAPTIVE FILTER ================= */
        .adaptive-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            background: #f8f9fa !important;
            /* Default Light Mode */
        }

        .adaptive-label {
            background: rgba(0, 0, 0, 0.03) !important;
            border: none !important;
            color: #495057 !important;
            /* Paksa Abu-abu Gelap di Light Mode */
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .adaptive-input {
            background: transparent !important;
            border: none !important;
            color: #212529 !important;
            /* Teks input hitam di Light Mode */
            font-size: 12px;
        }

        /* KHUSUS DARK MODE OVERRIDE */
        [data-coreui-theme="dark"] .adaptive-group {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-coreui-theme="dark"] .adaptive-label {
            color: #ced4da !important;
            /* Jadi Putih Abu-abu di Dark Mode */
        }

        [data-coreui-theme="dark"] .adaptive-input {
            color: #fff !important;
            /* Teks input putih di Dark Mode */
        }

        /* Invert ikon kalender biar kelihatan di Dark Mode */
        [data-coreui-theme="dark"] .adaptive-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }

        .btn-export-pdf:hover {
            transform: scale(1.05);
            background-color: #bb2d3b;
            /* Darker red */
        }

        /* Dark Mode Fix */
        [data-coreui-theme="dark"] .kpi-card,
        [data-coreui-theme="dark"] .card-main {
            background: rgba(42, 42, 53, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-coreui-theme="dark"] .kpi-value,
        [data-coreui-theme="dark"] .section-title {
            color: #ffffff !important;
        }
    </style>

    <div class="card welcome-card shadow-lg border-0 mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="fw-bold mb-1">Assalamu'alaikum, {{ $santri->nama }}! 👋</h3>
                    <p class="mb-3 opacity-75">
                        Kelas: {{ $santri->kelas?->nama_kelas ?? '-' }}
                        @if ($santri->nis)
                            <span class="mx-2">|</span> NIS: {{ $santri->nis }}
                        @endif
                    </p>
                    <div class="bg-white bg-opacity-25 rounded-pill px-3 py-2 d-inline-block">
                        <small class="fw-medium">💡 <strong>Motivasi:</strong> "Konsistensi adalah kunci kesuksesan. Terus
                            semangat!"</small>
                    </div>
                </div>
                <div class="col-auto d-none d-md-block text-end">
                    <i class="bi bi-stars" style="font-size: 4rem; opacity: 0.3;"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI CARDS DENGAN GLASSMORPHISM --}}
    <div class="row g-3 mb-4">
        @php
            $kpiItems = [
                [
                    'label' => 'Total Setor',
                    'value' => $totalSetor,
                    'sub' => 'Status: Lulus / Ulang',
                    'color' => 'success',
                    'icon' => 'journal-check',
                    'max' => 5,
                ],
                [
                    'label' => 'Izin / Tdk Setor',
                    'value' => $totalHadirTidakSetor,
                    'sub' => 'Kehadiran tanpa setoran',
                    'color' => 'warning',
                    'icon' => 'exclamation-triangle',
                    'max' => 5,
                ],
                [
                    'label' => 'Alpha',
                    'value' => $totalAlpha,
                    'sub' => 'Akumulasi ketidakhadiran',
                    'color' => 'danger',
                    'icon' => 'x-octagon',
                    'max' => 5,
                ],
                [
                    'label' => 'Rata-rata Nilai',
                    'value' => $avgNilai,
                    'sub' => 'Predikat hafalan terakhir',
                    'color' => 'primary',
                    'icon' => 'graph-up-arrow',
                    'max' => 100,
                ],
            ];
        @endphp

        @foreach ($kpiItems as $item)
            <div class="col-lg-3 col-md-6">
                <div class="card kpi-card border-0 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="kpi-label">{{ $item['label'] }}</div>
                                <div class="kpi-value text-{{ $item['color'] }}">{{ $item['value'] ?? 0 }}</div>
                            </div>
                            <div class="kpi-icon bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }} shadow-sm">
                                <i class="bi bi-{{ $item['icon'] }}"></i>
                            </div>
                        </div>
                        <div class="kpi-progress">
                            <div class="progress-bar kpi-progress-bar bg-{{ $item['color'] }}"
                                style="width: {{ min(100, ((float) $item['value'] / $item['max']) * 100) }}%"></div>
                        </div>
                        <div class="kpi-sub">{{ $item['sub'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- OVERALL PROGRESS --}}
    <div class="card card-main border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-0 text-adaptive-purple">Overall Progress Hafalan</h6>
                    <small class="text-muted">Capaian tahap tertinggi yang diselesaikan per juz.</small>
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

    {{-- PROGRESS PER JUZ & CHART --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card card-main h-100 border-0">
                <div class="card-header bg-transparent border-bottom py-3 px-4">
                    <span class="section-title"><i class="bi bi-list-stars me-2"></i>Progress per Juz (Ringkas)</span>
                </div>
                <div class="card-body enterprise-progress-container p-4">
                    @foreach ($progressPerJuz as $p)
                        @if ($p['pct'] > 0 || $p['tahap'])
                            <div class="enterprise-progress-row" data-coreui-toggle="tooltip"
                                title="Juz {{ $p['juz'] }} • Status: {{ $p['status'] }}">
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

        <div class="col-lg-6">
            <div class="card card-main h-100 border-0">
                <div class="card-header bg-transparent border-bottom py-3 px-4">
                    <span class="section-title"><i class="bi bi-activity me-2"></i>Analitik Capaian per Juz (%)</span>
                </div>
                <div class="card-body p-4">
                    <canvas id="chartJuzPct" style="height: 250px !important;"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- STACKED JUZ MAPPING --}}
    <div class="card card-main border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-adaptive-purple text-uppercase fw-bold">Visualisasi Segmentasi Juz (1-30)</span>
                <span class="fw-bold text-adaptive-purple">{{ $overallPct }}% Selesai</span>
            </div>
            <div class="progress" style="height:12px; border-radius:999px; overflow:hidden; background: rgba(0,0,0,0.05);">
                @foreach ($progressPerJuz as $p)
                    <div class="progress-bar bg-{{ $p['color'] }}" role="progressbar" style="width:0%"
                        data-width="{{ 100 / 30 }}" data-coreui-toggle="tooltip"
                        title="Juz {{ $p['juz'] }} • {{ $p['pct'] }}% • {{ $p['status'] }}">
                    </div>
                @endforeach
            </div>
            <div class="text-muted small mt-3"><i class="bi bi-info-circle me-1"></i> Segment mewakili setiap juz • warna
                menunjukkan status progress hafalanmu.</div>
        </div>
    </div>

    {{-- TIMELINE TABLE --}}
    <div class="card card-main overflow-hidden border-0">
        <div
            class="card-header bg-transparent py-3 px-4 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <span class="section-title"><i class="bi bi-clock-history me-2"></i>Riwayat Timeline Setoran</span>

            <form action="{{ route('santri.hafalan.export-pdf') }}" method="GET"
                class="d-flex flex-wrap gap-2 align-items-center">

                <div class="input-group input-group-sm w-auto adaptive-group">
                    <span class="input-group-text adaptive-label">Dari</span>
                    <input type="date" name="start_date" class="form-control adaptive-input shadow-none">
                </div>

                <div class="input-group input-group-sm w-auto adaptive-group">
                    <span class="input-group-text adaptive-label">Sampai</span>
                    <input type="date" name="end_date" class="form-control adaptive-input shadow-none">
                </div>

                <button type="submit"
                    class="btn btn-sm btn-danger text-white rounded-pill px-3 shadow-sm fw-bold btn-export">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Cetak PDF
                </button>
            </form>
        </div>
        <div class="card-body p-0 table-responsive py-4 px-4">
            <table id="timelineTable" class="table table-hover align-middle w-100 mb-0">
                <thead class="bg-light bg-opacity-50 text-nowrap">
                    <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                        <th class="ps-4">No</th>
                        <th>Tanggal</th>
                        <th>Juz</th>
                        <th>Surah / Ayat</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th class="pe-4">Catatan Musyrif</th>
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
            /* =====================================================
                ANIMASI PROGRESS BARS (LOGIKA UTUH)
            ===================================================== */
            const allBars = document.querySelectorAll('.progress-bar[data-width]');
            allBars.forEach((bar, index) => {
                const width = bar.dataset.width;
                bar.style.width = "0%";
                setTimeout(() => {
                    bar.style.transition = "width 1.2s cubic-bezier(0.1, 0.5, 0.5, 1)";
                    bar.style.width = width + "%";
                }, 150 + (index * 50));
            });

            /* =====================================================
                TOOLTIP INITIALIZATION (LOGIKA UTUH)
            ===================================================== */
            document.querySelectorAll('[data-coreui-toggle="tooltip"]').forEach(el => {
                new coreui.Tooltip(el, {
                    delay: {
                        show: 80,
                        hide: 50
                    },
                    placement: 'top'
                });
            });

            /* =====================================================
                CHART.JS LINE (LOGIKA UTUH)
            ===================================================== */
            const ctx = document.getElementById('chartJuzPct');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json(collect($progressPerJuz)->pluck('juz')->map(fn($j) => 'Juz ' . $j)),
                        datasets: [{
                            label: 'Progress (%)',
                            data: @json(collect($progressPerJuz)->pluck('pct')),
                            fill: true,
                            backgroundColor: 'rgba(111, 66, 193, 0.05)',
                            borderColor: '#6f42c1',
                            tension: 0.4,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointBackgroundColor: '#fff',
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
                                max: 100
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

            /* =====================================================
                DATATABLES TIMELINE (LOGIKA UTUH)
            ===================================================== */
            $('#timelineTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('santri.hafalan.timeline') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4'
                    },
                    {
                        data: 'tanggal',
                        searchable: true
                    },
                    {
                        data: 'juz',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'surah_ayat',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'status',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'nilai',
                        orderable: true,
                        searchable: true
                    },
                    {
                        data: 'catatan',
                        orderable: true,
                        searchable: true,
                        className: 'pe-4'
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
