@extends('layouts.app')

@section('title', 'Dashboard Musyrif')

@section('content')

    <style>
        /* ================= KONSISTENSI TEMA PURPLE ================= */
        :root {
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .kpi-card {
            border-radius: 20px;
            background: #ffffff;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
            border: 1px solid rgba(0, 0, 0, 0.02) !important;
            overflow: hidden;
            backdrop-filter: blur(8px);
            /* Efek blur di belakang kartu */
            -webkit-backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.7) !important;
            /* Semi transparan putih */
        }


        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.1);
        }

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
            color: var(--islamic-purple-700);
            line-height: 1;
        }

        .kpi-sub {
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 5px;
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
            height: 8px;
            background: #f0f2f5;
            border-radius: 10px;
            margin-top: 15px;
        }

        .kpi-progress-bar {
            border-radius: 10px;
            transition: width 1.5s cubic-bezier(0.1, 0.5, 0.5, 1);
        }

        /* Card Header Styling */
        .card-header-custom {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            color: var(--islamic-purple-700);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ================= FIX DARK THEME UNTUK KPI CARDS ================= */

        /* Gunakan selector data-coreui-theme untuk mendeteksi mode gelap */
        [data-coreui-theme="dark"] .kpi-card {
            background: var(--cui-card-bg, #2a2a35) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }


        [data-coreui-theme="dark"] .kpi-card {
            background: rgba(42, 42, 53, 0.6) !important;
            /* Semi transparan gelap */
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* Penyesuaian warna teks di mode gelap */
        [data-coreui-theme="dark"] .kpi-value {
            color: #ffffff !important;
        }

        [data-coreui-theme="dark"] .kpi-label {
            color: #a0a0a0;
        }

        [data-coreui-theme="dark"] .kpi-sub.text-muted {
            color: #8a8a8a !important;
        }

        /* Background icon box biar nggak terlalu kontras */
        [data-coreui-theme="dark"] .kpi-icon.bg-primary-subtle,
        [data-coreui-theme="dark"] .bg-light-subtle {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        /* Border untuk table agenda di dark mode */
        [data-coreui-theme="dark"] .card-header-custom {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>

    {{-- HEADER DASHBOARD --}}
    <div class="mb-4">
        <h4 class="fw-bold text-adaptive-purple mb-1">Assalamu'alaikum, Musyrif</h4>
        <p class="text-muted small">Berikut adalah ringkasan perkembangan hafalan santri Anda hari ini.</p>
    </div>

    {{-- ================== ROW KPI CARDS ================== --}}
    <div class="row g-3 mb-4">
        {{-- Santri Bimbingan --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Bimbingan</div>
                            <div class="kpi-value count-up" data-target="{{ $jumlahSantri ?? 0 }}">0</div>
                            <div class="kpi-sub text-muted" style="font-size: 11px;">Total santri</div>
                        </div>
                        <div class="kpi-icon shadow-sm"
                            style="background-color: var(--islamic-purple-100); color: var(--islamic-purple-600);">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar" style="background-color: var(--islamic-purple-500); width:100%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Setoran Hari Ini --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Setor</div>
                            <div class="kpi-value count-up" data-target="{{ $setoranHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-success fw-bold" style="font-size: 11px;">Lulus / Ulang</div>
                        </div>
                        <div class="kpi-icon bg-success-subtle text-success shadow-sm">
                            <i class="bi bi-check-all"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-success"
                            style="width: {{ min(100, ($setoranHariIni ?? 0) * 10) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hadir Tidak Setor --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Hadir (TS)</div>
                            <div class="kpi-value count-up" data-target="{{ $hadirTidakSetorHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-warning" style="font-size: 11px;">Tidak Setor</div>
                        </div>
                        <div class="kpi-icon bg-warning-subtle text-warning shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-warning"
                            style="width: {{ min(100, ($hadirTidakSetorHariIni ?? 0) * 10) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sakit --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Sakit</div>
                            <div class="kpi-value count-up" data-target="{{ $sakitHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-primary" style="font-size: 11px;">Berhalangan</div>
                        </div>
                        <div class="kpi-icon bg-primary-subtle text-primary shadow-sm">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-primary" style="width: {{ min(100, ($sakitHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Izin --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Izin</div>
                            <div class="kpi-value count-up" data-target="{{ $izinHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-secondary" style="font-size: 11px;">Berhalangan</div>
                        </div>
                        <div class="kpi-icon bg-secondary-subtle text-secondary shadow-sm">
                            <i class="bi bi-envelope-paper-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-secondary" style="width: {{ min(100, ($izinHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alpha --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Alpha</div>
                            <div class="kpi-value count-up" data-target="{{ $alphaHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-danger" style="font-size: 11px;">Tanpa Ket.</div>
                        </div>
                        <div class="kpi-icon bg-danger-subtle text-danger shadow-sm">
                            <i class="bi bi-person-x-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-danger" style="width: {{ min(100, ($alphaHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Setoran + Rata Nilai Combined --}}
        <div class="col-lg-12">
            <div class="card kpi-card spotlight-card border-0">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6 border-end">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon bg-success text-white">
                                    <i class="bi bi-journal-check"></i>
                                </div>
                                <div>
                                    <div class="kpi-label mb-0">Total Akumulasi Setoran</div>
                                    <div class="kpi-value count-up" data-target="{{ $totalSetoran ?? 0 }}">0</div>
                                </div>
                            </div>
                            <div class="kpi-progress mt-3">
                                <div class="kpi-progress-bar bg-success"
                                    style="width: {{ min(100, ($totalSetoran ?? 0) / 10) }}%"></div>
                            </div>
                        </div>
                        <div class="col-md-6 ps-md-4 mt-3 mt-md-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon bg-warning text-white">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <div>
                                    <div class="kpi-label mb-0">Rata-rata Nilai (Juz Unik: {{ $totalJuzUnik ?? 0 }})</div>
                                    <div class="kpi-value count-up" data-target="{{ $rataNilai ?? 0 }}">0</div>
                                </div>
                            </div>
                            <div class="kpi-progress mt-3">
                                <div class="kpi-progress-bar bg-warning" style="width: {{ min(100, $rataNilai ?? 0) }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== ROW CHART & AGENDA ================== --}}
    <div class="row g-4">
        {{-- Chart 1: Setoran per Santri --}}
        <div class="col-lg-6">
            <div class="card kpi-card spotlight-card border-0 h-100">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-bar-chart-fill"></i> Setoran Hafalan per Santri (Top 7)
                </div>
                <div class="card-body">
                    <canvas id="chartSetoranSantri" height="280"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 2: Distribusi Status --}}
        <div class="col-lg-6">
            <div class="card kpi-card spotlight-card border-0 h-100">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-pie-chart-fill"></i> Distribusi Status Hafalan
                </div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="chartStatusHafalan" height="280"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 3: Distribusi Juz --}}
        <div class="col-lg-8">
            <div class="card kpi-card spotlight-card border-0">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-graph-up-arrow"></i> Distribusi Setoran per Juz
                </div>
                <div class="card-body">
                    <canvas id="chartJuzHafalan" height="300"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 4: Nilai per Santri --}}
        <div class="col-lg-4">
            <div class="card kpi-card spotlight-card border-0">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-award-fill"></i> Rata-rata Nilai (Top 7)
                </div>
                <div class="card-body">
                    <canvas id="chartNilaiSantri" height="300"></canvas>
                </div>
            </div>
        </div>

        {{-- Agenda Table --}}
        <div class="col-12">
            <div class="card kpi-card spotlight-card border-0 mb-4">
                <div class="card-header-custom text-adaptive-purple justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event-fill"></i> Real-time Setoran Santri Hari Ini
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">{{ count($agendaHarian) }}
                        Data</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase small fw-bold">
                                <tr>
                                    <th class="ps-4">Santri</th>
                                    <th>Kelas</th>
                                    <th>Juz</th>
                                    <th>Surah / Ayat</th>
                                    <th class="pe-4">Jam</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($agendaHarian as $item)
                                    <tr>
                                        <td class="ps-4 fw-bold text-adaptive-purple">{{ $item->santri?->nama ?? '-' }}
                                        </td>
                                        <td><span
                                                class="badge bg-light text-dark border">{{ $item->santri?->kelas?->nama_kelas ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @if ($item->template)
                                                <span class="fw-bold">Juz {{ $item->template->juz ?? '-' }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="small">{{ $item->template?->label ?? '-' }}</td>
                                        <td class="pe-4 text-muted"><i
                                                class="bi bi-clock me-1"></i>{{ $item->created_at?->format('H:i') ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                            Belum ada aktivitas setoran hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // MENGGUNAKAN JSON DATA YANG SAMA PERSIS
            const setoranSantriLabels = @json($chartSetoranPerSantri['labels'] ?? []);
            const setoranSantriData = @json($chartSetoranPerSantri['data'] ?? []);
            const nilaiSantriLabels = @json($chartNilaiPerSantri['labels'] ?? []);
            const nilaiSantriData = @json($chartNilaiPerSantri['data'] ?? []);
            const statusLabels = @json($chartStatus['labels'] ?? []);
            const statusData = @json($chartStatus['data'] ?? []);
            const juzLabels = @json($chartJuz['labels'] ?? []);
            const juzData = @json($chartJuz['data'] ?? []);

            // Letakkan di dalam DOMContentLoaded
            const cards = document.querySelectorAll(".spotlight-card");

            document.addEventListener("mousemove", (e) => {
                cards.forEach((card) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    card.style.setProperty("--mouse-x", `${x}px`);
                    card.style.setProperty("--mouse-y", `${y}px`);
                });
            });

            // Helper function untuk chart styling agar senada
            const purpleGradient = (ctx) => {
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(111, 66, 193, 0.8)');
                gradient.addColorStop(1, 'rgba(111, 66, 193, 0.1)');
                return gradient;
            };

            // CHART 1: BAR – SETORAN PER SANTRI
            new Chart(document.getElementById('chartSetoranSantri').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: setoranSantriLabels,
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: setoranSantriData,
                        backgroundColor: 'rgba(111, 66, 193, 0.7)',
                        borderRadius: 8,
                    }]
                },
                options: {
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
                                display: false
                            }
                        }
                    }
                }
            });

            // CHART 2: DOUGHNUT – STATUS HAFALAN
            new Chart(document.getElementById('chartStatusHafalan').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545'],
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });

            // CHART 3: LINE – DISTRIBUSI PER JUZ
            new Chart(document.getElementById('chartJuzHafalan').getContext('2d'), {
                type: 'line',
                data: {
                    labels: juzLabels,
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: juzData,
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#6f42c1',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // CHART 4: BAR – NILAI
            if (document.getElementById('chartNilaiSantri')) {
                new Chart(document.getElementById('chartNilaiSantri').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: nilaiSantriLabels,
                        datasets: [{
                            label: 'Rata-rata Nilai',
                            data: nilaiSantriData,
                            backgroundColor: 'rgba(255, 193, 7, 0.8)',
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }

            // Simple Count Up Animation
            document.querySelectorAll('.count-up').forEach(el => {
                const target = +el.getAttribute('data-target');
                const duration = 1000;
                const increment = target / (duration / 16);
                let current = 0;
                const update = () => {
                    current += increment;
                    if (current < target) {
                        el.innerText = Math.floor(current);
                        requestAnimationFrame(update);
                    } else {
                        el.innerText = target;
                    }
                };
                update();
            });
        });
    </script>
@endpush
