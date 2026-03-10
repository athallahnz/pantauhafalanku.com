@extends('layouts.app')

@section('title', 'Dashboard Kepala Departemen')

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
            -webkit-backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.7) !important;
            /* Semi transparan putih */
            height: 100%;
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

        .text-adaptive-purple {
            color: var(--islamic-purple-700);
            transition: color 0.3s ease;
        }

        /* ================= FIX DARK THEME UNTUK KPI CARDS ================= */
        [data-coreui-theme="dark"] .kpi-card {
            background: rgba(42, 42, 53, 0.6) !important;
            /* Semi transparan gelap */
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        [data-coreui-theme="dark"] .kpi-value,
        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ffffff !important;
        }

        [data-coreui-theme="dark"] .kpi-label {
            color: #a0a0a0;
        }

        [data-coreui-theme="dark"] .kpi-sub.text-muted {
            color: #8a8a8a !important;
        }

        [data-coreui-theme="dark"] .kpi-icon.bg-primary-subtle,
        [data-coreui-theme="dark"] .bg-light-subtle {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        [data-coreui-theme="dark"] .card-header-custom {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>

    {{-- HEADER TITLE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Dashboard Departemen</h4>
            <span class="text-muted small">Ringkasan aktivitas dan data hafalan bulan ini</span>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="row g-4 mb-4">

        {{-- Card 1: Kelas --}}
        <div class="col-lg-4 col-md-6">
            <div class="kpi-card position-relative p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Kelas di Departemen</div>
                        <div class="kpi-value count-up" style="color: var(--islamic-purple-600);"
                            data-target="{{ $jumlahKelas ?? 0 }}">0</div>
                    </div>
                    <div class="kpi-icon" style="background: var(--islamic-purple-100); color: var(--islamic-purple-600);">
                        <i class="bi bi-houses"></i>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100"
                    style="height: 4px; background: var(--islamic-purple-400);"></div>
            </div>
        </div>

        {{-- Card 2: Musyrif --}}
        <div class="col-lg-4 col-md-6">
            <div class="kpi-card position-relative p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Jumlah Musyrif</div>
                        <div class="kpi-value count-up" style="color: var(--islamic-tosca-600);"
                            data-target="{{ $jumlahMusyrif ?? 0 }}">0</div>
                    </div>
                    <div class="kpi-icon" style="background: var(--islamic-tosca-100); color: var(--islamic-tosca-600);">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100"
                    style="height: 4px; background: var(--islamic-tosca-400);"></div>
            </div>
        </div>

        {{-- Card 3: Setoran --}}
        <div class="col-lg-4 col-md-6">
            <div class="kpi-card position-relative p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Setoran Bulan Ini</div>
                        <div class="kpi-value count-up" style="color: var(--islamic-purple-700);"
                            data-target="{{ $setoranBulanIni ?? 0 }}">0</div>
                    </div>
                    <div class="kpi-icon" style="background: var(--islamic-purple-50); color: var(--islamic-purple-600);">
                        <i class="bi bi-journal-check"></i>
                    </div>
                </div>
                <div class="position-absolute bottom-0 start-0 w-100"
                    style="height: 4px; background: linear-gradient(90deg, var(--islamic-purple-600), var(--islamic-tosca-500));">
                </div>
            </div>
        </div>

    </div>

    {{-- CHART SECTION --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header border-0 py-3 rounded-top-4">
            <h5 class="mb-0 fw-semibold text-white"><i class="bi bi-graph-up me-2"></i> Ringkasan Hafalan per Kelas</h5>
        </div>
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                Grafik di bawah ini menampilkan rata-rata capaian hafalan santri (dalam juz) di masing-masing kelas untuk
                bulan ini.
            </p>

            {{-- Wrapper agar chart responsif --}}
            <div style="position: relative; height: 300px; width: 100%;">
                <canvas id="hafalanChart"></canvas>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Ambil elemen canvas
            const ctx = document.getElementById('hafalanChart').getContext('2d');

            // Setup Gradient untuk background chart
            const gradientPurple = ctx.createLinearGradient(0, 0, 0, 300);
            gradientPurple.addColorStop(0, 'rgba(107, 78, 255, 0.4)'); // --islamic-purple-500
            gradientPurple.addColorStop(1, 'rgba(107, 78, 255, 0.0)');

            // Inisialisasi Chart (Contoh Data Dummy)
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Kelas 7A', 'Kelas 7B', 'Kelas 8A', 'Kelas 8B', 'Kelas 9A', 'Kelas 9B'],
                    datasets: [{
                        label: 'Rata-rata Hafalan (Juz)',
                        data: [1.2, 1.5, 2.8, 2.4, 4.1,
                            4.5
                        ], // Ganti dengan data real dari controller
                        borderColor: '#6b4eff', // var(--islamic-purple-500)
                        backgroundColor: gradientPurple,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#6b4eff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4 // Membuat garis melengkung (smooth)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // Sembunyikan legend jika hanya 1 dataset
                        },
                        tooltip: {
                            backgroundColor: '#40307a', // var(--islamic-purple-700)
                            titleFont: {
                                size: 13
                            },
                            bodyFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' Juz';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)',
                                drawBorder: false,
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            }
                        }
                    }
                }
            });

            // ================= COUNT UP =================
            const counters = document.querySelectorAll('.count-up');
            counters.forEach(counter => {
                const target = parseInt(counter.dataset.target) || 0;
                const start = parseInt(counter.textContent.replace(/\D/g, '')) || 0;
                if (start === target) return;

                const duration = 1200;
                const frameRate = 30;
                const totalFrames = Math.round(duration / (1000 / frameRate));
                let frame = 0;

                const interval = setInterval(() => {
                    frame++;
                    const progress = frame / totalFrames;
                    const eased = 1 - Math.pow(1 - progress, 3); // easeOut
                    const current = Math.round(start + (target - start) * eased);
                    counter.textContent = current.toLocaleString('id-ID');

                    if (frame >= totalFrames) {
                        counter.textContent = target.toLocaleString('id-ID');
                        clearInterval(interval);
                    }
                }, 1000 / frameRate);
            });
        });
    </script>
@endpush
