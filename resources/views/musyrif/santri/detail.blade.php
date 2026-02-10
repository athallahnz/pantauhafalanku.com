@extends('layouts.app')

@section('title', 'Detail Santri')

@section('content')
    <style>
        .enterprise-progress-container {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .enterprise-progress-row {
            margin-bottom: 14px;
        }

        .enterprise-progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .enterprise-juz {
            font-weight: 600;
            font-size: 14px;
        }

        .enterprise-meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .enterprise-percent {
            font-weight: 600;
            font-size: 12px;
            color: #6c757d;
        }

        .enterprise-progress {
            height: 8px;
            background: #edf1f5;
            border-radius: 999px;
            overflow: hidden;
        }

        .enterprise-progress .progress-bar {
            width: 0;
            transition: width 1.2s cubic-bezier(.4, 0, .2, 1);
        }

        /* glow effect */

        .progress-bar.bg-success {
            box-shadow: 0 0 6px rgba(25, 135, 84, .5);
        }

        .progress-bar.bg-primary {
            box-shadow: 0 0 6px rgba(13, 110, 253, .4);
        }

        .progress-bar.bg-info {
            box-shadow: 0 0 6px rgba(13, 202, 240, .4);
        }

        /* Custom styles for dashboard cards */
        .kpi-card {

            border-radius: 16px;

            background: linear-gradient(180deg,
                    #ffffff,
                    #fbfcfd);

            box-shadow:
                0 4px 12px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);

            transition: all .25s ease;
        }

        .kpi-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.06),
                0 4px 8px rgba(0, 0, 0, 0.08);
        }

        .kpi-label {

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: .08em;

            color: #6c757d;

            margin-bottom: 4px;
        }

        .kpi-value {

            font-size: 28px;

            font-weight: 700;

            letter-spacing: -0.02em;
        }

        .kpi-sub {

            font-size: 12px;

            color: #9aa0a6;
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

            border-radius: 20px;

            transition: width .8s ease;
        }
    </style>

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <div class="h5 mb-1">{{ $santri->nama }}</div>
            <div class="text-muted small">
                Kelas: {{ $santri->kelas?->nama_kelas ?? '-' }}
                @if ($santri->nis)
                    | NIS: {{ $santri->nis }}
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('musyrif.santri.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-4">

        {{-- Total Setor --}}
        <div class="col-lg-3 col-md-6">

            <div class="card kpi-card border-0 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <div class="kpi-label">
                                Total Setor
                            </div>

                            <div class="kpi-value text-success">
                                {{ $totalSetor ?? 0 }}
                            </div>

                            <div class="kpi-sub">
                                Status: Lulus / Ulang
                            </div>
                        </div>

                        <div class="kpi-icon bg-success-subtle text-success">
                            <i class="bi bi-journal-check"></i>
                        </div>

                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-success" style="width: {{ min(100, ($totalSetor ?? 0) / 5) }}%">
                        </div>
                    </div>

                </div>

            </div>

        </div>


        {{-- Hadir Tidak Setor --}}
        <div class="col-lg-3 col-md-6">

            <div class="card kpi-card border-0 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <div class="kpi-label">
                                Hadir Tidak Setor
                            </div>

                            <div class="kpi-value text-warning">
                                {{ $totalHadirTidakSetor ?? 0 }}
                            </div>

                            <div class="kpi-sub">
                                Akumulasi kehadiran tanpa setoran
                            </div>
                        </div>

                        <div class="kpi-icon bg-warning-subtle text-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>

                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-warning"
                            style="width: {{ min(100, ($totalHadirTidakSetor ?? 0) / 5) }}%">
                        </div>
                    </div>

                </div>

            </div>

        </div>


        {{-- Alpha --}}
        <div class="col-lg-3 col-md-6">

            <div class="card kpi-card border-0 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <div class="kpi-label">
                                Alpha
                            </div>

                            <div class="kpi-value text-danger">
                                {{ $totalAlpha ?? 0 }}
                            </div>

                            <div class="kpi-sub">
                                Akumulasi ketidakhadiran
                            </div>
                        </div>

                        <div class="kpi-icon bg-danger-subtle text-danger">
                            <i class="bi bi-x-octagon"></i>
                        </div>

                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-danger" style="width: {{ min(100, ($totalAlpha ?? 0) / 5) }}%">
                        </div>
                    </div>

                </div>

            </div>

        </div>


        {{-- Rata Nilai --}}
        <div class="col-lg-3 col-md-6">

            <div class="card kpi-card border-0 h-100">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>
                            <div class="kpi-label">
                                Rata-rata Nilai
                            </div>

                            <div class="kpi-value text-primary">
                                {{ $avgNilai ?? 0 }}
                            </div>

                            <div class="kpi-sub">
                                Mumtaz / Jayyid Jiddan / Jayyid
                            </div>
                        </div>

                        <div class="kpi-icon bg-primary-subtle text-primary">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>

                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-primary" style="width: {{ min(100, $avgNilai ?? 0) }}%">
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- Overall progress --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="fw-semibold">Overall Progress</div>
                <div class="text-muted small">{{ $overallPct }}%</div>
            </div>
            <div class="progress" style="height: 12px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $overallPct }}%;"
                    aria-valuenow="{{ $overallPct }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="text-muted small mt-2">
                Overall dihitung berdasarkan tahap tertinggi yang telah diselesaikan per juz.
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Progress per juz (bar list) --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Progress per Juz (Ringkas)</div>
                <div class="card-body enterprise-progress-container">

                    @foreach ($progressPerJuz as $p)
                        @if ($p['pct'] > 0 || $p['tahap'])
                            <div class="enterprise-progress-row" data-coreui-toggle="tooltip"
                                title="Juz {{ $p['juz'] }}
                                    • Progress: {{ $p['pct'] }}%
                                    • Status: {{ $p['status'] }}
                                    @if ($p['tahap']) • Tahap: {{ ucfirst(str_replace('_', ' ', $p['tahap'])) }} @endif
                                    ">
                                <div class="enterprise-progress-header">

                                    <div class="enterprise-juz">
                                        Juz {{ $p['juz'] }}
                                    </div>

                                    <div class="enterprise-meta">

                                        @if ($p['tahap'])
                                            <span class="badge bg-light text-dark border">
                                                {{ ucfirst(str_replace('_', ' ', $p['tahap'])) }}
                                            </span>
                                        @endif

                                        <span class="badge bg-{{ $p['color'] }}">
                                            {{ $p['status'] }}
                                        </span>

                                        <span class="enterprise-percent">
                                            {{ $p['pct'] }}%
                                        </span>

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

        {{-- Chart persentase per juz --}}
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Chart Progress per Juz (%)</div>
                <div class="card-body">
                    <canvas id="chartJuzPct" height="170"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- ENTERPRISE STACKED PROGRESS HEADER --}}
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-2">

                <div class="fw-semibold">
                    Progress Hafalan Santri
                </div>

                <div class="fw-semibold text-primary">
                    {{ $overallPct }}%
                </div>

            </div>

            <div class="progress" style="height:10px; border-radius:999px; overflow:hidden;">

                @foreach ($progressPerJuz as $p)
                    <div class="progress-bar bg-{{ $p['color'] }}" role="progressbar" style="width:0%"
                        data-width="{{ 100 / 30 }}" data-coreui-toggle="tooltip"
                        title="
                            Juz {{ $p['juz'] }}
                            • {{ $p['pct'] }}%
                            • {{ $p['status'] }}

                            @if ($p['tahap']) • Tahap: {{ ucfirst(str_replace('_', ' ', $p['tahap'])) }} @endif
                            ">
                    </div>
                @endforeach

            </div>

            <div class="text-muted small mt-2">

                Segment mewakili setiap juz • warna menunjukkan status progress

            </div>

        </div>

    </div>

    {{-- Timeline --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Timeline Hafalan</span>
            <span class="text-white small">Riwayat setoran & status kehadiran</span>
        </div>
        <div class="card-body table-responsive">
            <table id="timelineTable" class="table table-striped align-middle w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Juz</th>
                        <th>Surah/Ayat</th>
                        <th>Status</th>
                        <th>Nilai</th>
                        <th>Catatan</th>
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
               ENTERPRISE STACKED HEADER PROGRESS
            ===================================================== */

            const stackedBars = document.querySelectorAll(
                '.card .progress:not(.enterprise-progress) .progress-bar[data-width]'
            );

            stackedBars.forEach((bar, index) => {

                const width = bar.dataset.width;

                bar.style.width = "0%";

                setTimeout(() => {

                    bar.style.transition =
                        "width 1.2s cubic-bezier(.4,0,.2,1)";

                    bar.style.width = width + "%";

                }, 150 + (index * 80));

            });


            /* =====================================================
               ENTERPRISE JUZ LIST PROGRESS
            ===================================================== */

            const juzBars = document.querySelectorAll(
                '.enterprise-progress .progress-bar'
            );

            juzBars.forEach((bar, index) => {

                const width = bar.dataset.width;

                bar.style.width = "0%";

                setTimeout(() => {

                    bar.style.transition =
                        "width 1.2s cubic-bezier(.4,0,.2,1)";

                    bar.style.width = width + "%";

                }, 200 + (index * 60));

            });


            /* =====================================================
               TOOLTIP ENTERPRISE
            ===================================================== */

            document
                .querySelectorAll('[data-coreui-toggle="tooltip"]')
                .forEach(el => {

                    new coreui.Tooltip(el, {
                        delay: {
                            show: 80,
                            hide: 50
                        },
                        placement: 'top'
                    });

                });


            /* =====================================================
               CHART.JS — FIXED VERSION
            ===================================================== */

            const juzLabels = @json(collect($progressPerJuz)->pluck('juz')->map(fn($j) => 'Juz ' . $j)->values());

            const juzPct = @json(collect($progressPerJuz)->pluck('pct')->values());

            const ctx = document.getElementById('chartJuzPct');

            if (ctx) {

                new Chart(ctx, {

                    type: 'line',

                    data: {
                        labels: juzLabels,
                        datasets: [{
                            label: 'Progress (%)',
                            data: juzPct,
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 3
                        }]
                    },

                    options: {

                        maintainAspectRatio: false,

                        animation: {
                            duration: 1200,
                            easing: 'easeOutCubic'
                        },

                        plugins: {
                            legend: {
                                display: true
                            }
                        },

                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }

                    }

                });

            }


            /* =====================================================
               DATATABLE
            ===================================================== */

            $('#timelineTable').DataTable({

                processing: true,
                serverSide: true,

                ajax: "{{ route('musyrif.santri.timeline', $santri->id) }}",

                columns: [

                    {
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
                        searchable: false
                    }

                ],

                order: [
                    [1, 'desc']
                ],

                pageLength: 10,

                responsive: true

            });

        });
    </script>
@endpush
