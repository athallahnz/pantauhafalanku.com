@extends('layouts.app')

@section('title', 'Dashboard Musyrif')

@section('content')

    <style>
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

    {{-- ================== ROW KPI CARDS ================== --}}
    <div class="row g-3 mb-4">
        {{-- Santri Bimbingan --}}
        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">
                                Santri Bimbingan
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $jumlahSantri ?? 0 }}">
                                0
                            </div>
                            <div class="kpi-sub">
                                Total santri binaan
                            </div>
                        </div>
                        <div class="kpi-icon bg-primary-subtle text-primary">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-primary" style="width:100%">
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Setoran Hari Ini --}}
        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">
                                Setoran Hari Ini
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $setoranHariIni ?? 0 }}">
                                0
                            </div>
                            <div class="kpi-sub text-success">
                                Lulus / Ulang
                            </div>
                        </div>
                        <div class="kpi-icon bg-success-subtle text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-success"
                            style="width: {{ min(100, ($setoranHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hadir Tidak Setor --}}
        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">
                                Hadir Tidak Setor
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $hadirTidakSetorHariIni ?? 0 }}">
                                0
                            </div>

                            <div class="kpi-sub">
                                Hari ini
                            </div>
                        </div>
                        <div class="kpi-icon bg-warning-subtle text-warning">
                            <i class="bi bi-exclamation-circle"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-warning"
                            style="width: {{ min(100, ($hadirTidakSetorHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alpha --}}
        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card h-100 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">
                                Alpha
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $alphaHariIni ?? 0 }}">
                                0
                            </div>

                            <div class="kpi-sub text-danger">
                                Tanpa keterangan
                            </div>
                        </div>
                        <div class="kpi-icon bg-danger-subtle text-danger">
                            <i class="bi bi-x-circle"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-danger" style="width: {{ min(100, ($alphaHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>


        {{-- Total Setoran + Rata Nilai --}}
        <div class="col-lg-6 col-md-12">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="kpi-label">
                                Total Setoran
                            </div>

                            <div class="kpi-value text-success count-up" data-target="{{ $totalSetoran ?? 0 }}">
                                0
                            </div>


                            <div class="kpi-sub">
                                Akumulasi hafalan
                            </div>
                            <div class="kpi-progress mt-2">

                                <div class="kpi-progress-bar bg-success"
                                    style="width: {{ min(100, ($totalSetoran ?? 0) / 10) }}%">
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="kpi-label">
                                Rata Nilai
                            </div>

                            <div class="kpi-value text-warning count-up" data-target="{{ $rataNilai ?? 0 }}">
                                0
                            </div>


                            <div class="kpi-sub">
                                Juz unik: {{ $totalJuzUnik ?? 0 }}
                            </div>

                            <div class="kpi-progress mt-2">
                                <div class="kpi-progress-bar bg-warning" style="width: {{ min(100, $rataNilai ?? 0) }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ================== ROW CHART ================== --}}
    <div class="row mb-4">
        {{-- Chart 1: Setoran per Santri --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    Setoran Hafalan per Santri (Top 7)
                </div>
                <div class="card-body">
                    <canvas id="chartSetoranSantri" height="150"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 2: Distribusi Status Hafalan --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    Distribusi Status Hafalan
                </div>
                <div class="card-body">
                    <canvas id="chartStatusHafalan" height="150"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 3: Nilai rata-rata per santri (Top 7) --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    Rata-rata Nilai per Santri (Top 7)
                </div>
                <div class="card-body">
                    <canvas id="chartNilaiSantri" height="150"></canvas>
                </div>
            </div>
        </div>

    </div>

    {{-- ================== ROW CHART JUZ + AGENDA ================== --}}
    <div class="row">
        {{-- Chart 3: Progress per Juz --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    Distribusi Setoran per Juz
                </div>
                <div class="card-body">
                    <canvas id="chartJuzHafalan" height="160"></canvas>
                </div>
            </div>
        </div>

        {{-- Agenda Hari Ini --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">Setoran Santri Hari Ini</div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Santri</th>
                                <th>Kelas</th>
                                <th>Juz</th>
                                <th>Surah / Ayat</th>
                                <th>Jam</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agendaHarian as $item)
                                <tr>
                                    <td>{{ $item->santri?->nama ?? '-' }}</td>
                                    <td>{{ $item->santri?->kelas?->nama_kelas ?? '-' }}</td>

                                    <td>
                                        @if ($item->template)
                                            Juz {{ $item->template->juz ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    <td>
                                        {{ $item->template?->label ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $item->created_at?->format('H:i') ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada agenda setoran hari ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ================== DATA DARI PHP ==================
            const setoranSantriLabels = @json($chartSetoranPerSantri['labels'] ?? []);
            const setoranSantriData = @json($chartSetoranPerSantri['data'] ?? []);

            const nilaiSantriLabels = @json($chartNilaiPerSantri['labels'] ?? []);
            const nilaiSantriData = @json($chartNilaiPerSantri['data'] ?? []);

            const statusLabels = @json($chartStatus['labels'] ?? []);
            const statusData = @json($chartStatus['data'] ?? []);

            const juzLabels = @json($chartJuz['labels'] ?? []);
            const juzData = @json($chartJuz['data'] ?? []);

            // ================== CHART 1: BAR – SETORAN PER SANTRI ==================
            const ctxSantri = document.getElementById('chartSetoranSantri').getContext('2d');

            new Chart(ctxSantri, {
                type: 'bar',
                data: {
                    labels: setoranSantriLabels,
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: setoranSantriData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            precision: 0
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // ================== CHART 2: DOUGHNUT – STATUS HAFALAN ==================
            const ctxStatus = document.getElementById('chartStatusHafalan').getContext('2d');

            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)', // Lulus
                            'rgba(255, 193, 7, 0.8)', // Ulang
                            'rgba(23, 162, 184, 0.8)', // Hadir Tidak Setor
                            'rgba(220, 53, 69, 0.8)', // Alpha
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // ================== CHART 3: LINE – DISTRIBUSI PER JUZ ==================
            const ctxJuz = document.getElementById('chartJuzHafalan').getContext('2d');

            new Chart(ctxJuz, {
                type: 'line',
                data: {
                    labels: juzLabels,
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: juzData,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 3
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            precision: 0
                        }
                    },
                    plugins: {
                        legend: {
                            display: true
                        }
                    }
                }
            });

            const ctxNilai = document.getElementById('chartNilaiSantri')?.getContext('2d');
            if (ctxNilai) {
                new Chart(ctxNilai, {
                    type: 'bar',
                    data: {
                        labels: nilaiSantriLabels,
                        datasets: [{
                            label: 'Rata-rata Nilai',
                            data: nilaiSantriData,
                            backgroundColor: 'rgba(153, 102, 255, 0.7)',
                            borderColor: 'rgba(153, 102, 255, 1)',
                            borderWidth: 1,
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: 100
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });
    </script>
@endpush
