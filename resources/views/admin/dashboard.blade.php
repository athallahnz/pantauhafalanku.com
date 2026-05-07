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
            background: rgba(255, 255, 255, 0.7) !important;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
            border: 1px solid rgba(0, 0, 0, 0.02) !important;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
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

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .kpi-progress {
            height: 6px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            margin-top: 15px;
            overflow: hidden;
        }

        .kpi-progress-bar {
            border-radius: 10px;
            transition: width 1s ease-in-out;
        }

        /* Dark Mode Fixes */
        [data-coreui-theme="dark"] .kpi-card {
            background: rgba(42, 42, 53, 0.6) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        [data-coreui-theme="dark"] .kpi-value,
        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ffffff !important;
        }

        input[type="date"].form-control {
            font-weight: 600;
            color: var(--islamic-purple-700);
            transition: var(--transition-smooth);
        }

        input[type="date"].form-control:focus {
            background: #fff !important;
            box-shadow: 0 0 0 4px rgba(111, 66, 193, 0.1);
            border: 1px solid rgba(111, 66, 193, 0.3) !important;
        }

        [data-coreui-theme="dark"] input[type="date"].form-control {
            background: rgba(255, 255, 255, 0.05) !important;
            color: #fff;
        }

        /* Menyamakan lebar tombol di filter */
        .flex-fill-custom {
            min-width: 120px;
            /* Atur lebar minimal agar sama besar */
            flex: 0 1 auto;
        }

        /* Memastikan dropdown button mengikuti lebar container */
        .dropdown.flex-fill-custom .btn {
            height: 100%;
        }

        /* Memastikan dropdown mengikuti tema glassmorphism jika dihover */
        .dropdown-item:hover {
            background-color: var(--islamic-purple-50) !important;
            color: var(--islamic-purple-700) !important;
        }

        .min-btn-width {
            min-width: 110px;
        }

        /* Responsif: Di mobile tombol akan memenuhi layar */
        @media (max-width: 768px) {
            .flex-fill-custom {
                flex: 1 1 0;
                min-width: 0;
                font-size: 0.85rem;
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }

        /* Responsivitas khusus mobile */
        @media (max-width: 575.98px) {
            .kpi-label {
                font-size: 0.65rem;
            }

            input[type="date"].form-control {
                font-size: 0.8rem;
                padding: 0.5rem 0.75rem !important;
            }

            .w-100-mobile {
                width: 100% !important;
            }

            /* Agar header tidak terlalu memakan tempat di mobile */
            h4 {
                font-size: 1.5rem;
            }
        }
    </style>

    {{-- Elemen Audio Tersembunyi --}}
    <audio id="notifSound" src="{{ asset('sounds/notif.mp3') }}" preload="auto"></audio>

    {{-- HEADER TITLE --}}
    <div class="row align-items-center mb-4 g-3">
        <div class="col-12 col-md-6 text-center text-md-start">
            <h4 class="mb-1 fw-bold text-adaptive-purple">Dashboard Departemen</h4>
            <span class="text-muted small">
                Periode: <b class="text-dark">{{ $startDate->translatedFormat('d M Y') }}</b> s/d
                <b class="text-dark">{{ $endDate->translatedFormat('d M Y') }}</b>
            </span>
        </div>
        <div class="col-12 col-md-6">
            <div class="d-flex justify-content-center justify-content-md-end gap-2">
                <button id="audioStatusBtn"
                    class="btn btn-sm btn-light border rounded-pill px-3 shadow-sm transition-smooth">
                    <i class="bi bi-volume-mute text-danger me-1"></i>
                    <span class="small fw-bold text-muted">Suara Off</span>
                </button>
                <a href="{{ route('admin.musyrif.index') }}" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                    <i class="bi bi-person-plus me-1"></i> Musyrif Baru
                </a>
            </div>
        </div>
    </div>

    {{-- FILTER SECTION --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('admin.dashboard') }}" method="GET">
                <div class="row g-3 align-items-end">
                    {{-- Input Tanggal --}}
                    <div class="col-6 col-md-3">
                        <label class="kpi-label mb-1">Mulai Tanggal</label>
                        <input type="date" name="start_date"
                            class="form-control form-control-sm rounded-pill border-0 bg-light px-3"
                            value="{{ $startDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="kpi-label mb-1">Sampai Tanggal</label>
                        <input type="date" name="end_date"
                            class="form-control form-control-sm rounded-pill border-0 bg-light px-3"
                            value="{{ $endDate->format('Y-m-d') }}">
                    </div>

                    {{-- Grouping Tombol --}}
                    <div class="col-12 col-md-6">
                        <div class="d-grid d-md-flex gap-2 justify-content-md-start">
                            {{-- Terapkan & Reset (Bersebelahan di mobile) --}}
                            <div class="d-flex gap-2 w-100">
                                <button type="submit"
                                    class="btn btn-primary rounded-pill px-3 flex-grow-1 flex-md-grow-0 min-btn-width">
                                    <i class="bi bi-filter-left me-1"></i> Terapkan
                                </button>
                                <a href="{{ route('admin.dashboard') }}"
                                    class="btn btn-light border rounded-pill px-3 flex-grow-1 flex-md-grow-0 min-btn-width text-muted text-center">
                                    Reset
                                </a>
                            </div>
                            {{-- Dropdown Cepat (Full width di mobile) --}}
                            <div class="dropdown w-100-mobile">
                                <button class="btn btn-outline-secondary dropdown-toggle rounded-pill px-4 w-100"
                                    type="button" data-coreui-toggle="dropdown">
                                    Cepat
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                                    <li><a class="dropdown-item small py-2"
                                            href="?start_date={{ now()->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->endOfMonth()->format('Y-m-d') }}">Bulan
                                            Ini</a></li>
                                    <li><a class="dropdown-item small py-2"
                                            href="?start_date={{ now()->subMonths(3)->startOfMonth()->format('Y-m-d') }}&end_date={{ now()->endOfMonth()->format('Y-m-d') }}">Triwulan</a>
                                    </li>
                                    <li><a class="dropdown-item small py-2"
                                            href="?start_date={{ now()->startOfYear()->format('Y-m-d') }}&end_date={{ now()->endOfYear()->format('Y-m-d') }}">Tahun
                                            Ini</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Jumlah Kelas</div>
                        <div class="kpi-value count-up" data-target="{{ $jumlahKelas }}">0</div>
                    </div>
                    <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-houses"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Jumlah Musyrif</div>
                        <div class="kpi-value count-up" data-target="{{ $jumlahMusyrif }}">0</div>
                    </div>
                    <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-people"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Setoran Bulan Ini</div>
                        <div class="kpi-value count-up" data-target="{{ $setoranBulanIni }}">0</div>
                    </div>
                    <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-journal-check"></i></div>
                </div>
            </div>
        </div>
        {{-- Card Absensi dengan Progress Bar --}}
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card p-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-label">Kehadiran Musyrif</div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="kpi-value count-up" id="absensi-counter"
                                data-target="{{ $absensiMusyrifHariIni }}">0</span>
                            <span class="text-muted fw-bold">/ {{ $jumlahMusyrif }}</span>
                        </div>
                    </div>
                    <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-person-check"></i></div>
                </div>
                <div class="kpi-progress">
                    <div id="absensi-progress" class="kpi-progress-bar bg-warning"
                        style="width: {{ $jumlahMusyrif > 0 ? ($absensiMusyrifHariIni / $jumlahMusyrif) * 100 : 0 }}%; height: 100%;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- QUICK ACCESS SECTION --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card kpi-card border-0">
                <div class="card-body p-3">
                    <div class="kpi-label mb-3"><i class="bi bi-lightning-fill text-warning"></i> Akses Cepat Sistem</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.musyrif.index') }}"
                            class="btn btn-light rounded-pill border shadow-sm px-4 py-2">
                            <i class="bi bi-person-badge text-primary me-2"></i>Kelola Musyrif
                        </a>
                        <a href="{{ route('admin.laporan.index') }}"
                            class="btn btn-light rounded-pill border shadow-sm px-4 py-2">
                            <i class="bi bi-journal-text text-success me-2"></i>Laporan Hafalan
                        </a>
                        <a href="{{ route('admin.santri.migrasi.page') }}"
                            class="btn btn-light rounded-pill border shadow-sm px-4 py-2">
                            <i class="bi bi-arrow-up-circle text-info me-2"></i>Naik Kelas
                        </a>
                        <a href="{{ route('admin.settings.institution') }}"
                            class="btn btn-light rounded-pill border shadow-sm px-4 py-2">
                            <i class="bi bi-gear text-secondary me-2"></i>Profil Lembaga
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CHARTS SECTION --}}
    <div class="row g-4 mb-4">
        {{-- Chart Hafalan --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-white"><i class="bi bi-graph-up me-2"></i> Capaian Hafalan per Kelas
                    </h5>
                    <span class="text-white small d-block mt-1"><i class="bi bi-info-circle me-1"></i> Rata-rata jumlah
                        setoran
                        hafalan per santri bulan ini</span>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="hafalanChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chart Tahsin & Tilawah --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-semibold text-white">
                        <i class="bi bi-bar-chart-line me-2"></i> Capaian Tahsin & Tilawah
                    </h5>
                    <span class="text-white small d-block mt-1"><i class="bi bi-info-circle me-1"></i> Rata-rata frekuensi
                        tahsin/tilawah per santri bulan ini</span>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="tahsinTilawahChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // === 1. KONFIGURASI & STATE ===
            const state = {
                totalMusyrif: {{ $jumlahMusyrif }},
                chartData: @json($chartData),
                tahsinTilawahData: @json($chartTahsinTilawahData), // Tambahkan data ini
                isInteracted: false,
                baseUrl: "{{ route('admin.musyrif.attendances', ':id') }}",
                elements: {
                    sound: document.getElementById('notifSound'),
                    counter: document.getElementById('absensi-counter'),
                    progressBar: document.getElementById('absensi-progress'),
                    audioBtn: document.getElementById('audioStatusBtn')
                }
            };

            // === 2. INTERACTION BRIDGE (Silent Activation) ===
            const unlockInteractions = () => {
                if (state.isInteracted) return;
                state.isInteracted = true;

                // Unlock audio secara "diam-diam" melalui interaksi user
                if (state.elements.sound) {
                    state.elements.sound.play().then(() => {
                        state.elements.sound.pause();
                        state.elements.sound.currentTime = 0;

                        // Update UI Indikator Suara jika ada
                        if (state.elements.audioBtn) {
                            state.elements.audioBtn.classList.replace('btn-light',
                                'btn-primary-subtle');
                            state.elements.audioBtn.innerHTML = `
                                <i class="bi bi-volume-up-fill text-primary me-1"></i>
                                <span class="small fw-bold text-primary">Suara On</span>`;
                        }
                    }).catch(() => {
                        state.isInteracted = false; // Reset jika gagal
                    });
                }

                // Hapus listener setelah aktif
                ['click', 'touchstart', 'keydown'].forEach(evt =>
                    document.removeEventListener(evt, unlockInteractions)
                );
            };

            ['click', 'touchstart', 'keydown'].forEach(evt =>
                document.addEventListener(evt, unlockInteractions)
            );

            // === 3. CORE UI MODULE ===
            const UI = {
                initCountUp: () => {
                    document.querySelectorAll('.count-up').forEach(counter => {
                        const target = parseInt(counter.dataset.target) || 0;
                        let frame = 0,
                            totalFrames = 40;
                        const interval = setInterval(() => {
                            frame++;
                            const progress = frame / totalFrames;
                            const eased = 1 - Math.pow(1 - progress, 3);
                            counter.textContent = Math.round(target * eased).toLocaleString(
                                'id-ID');
                            if (frame === totalFrames) clearInterval(interval);
                        }, 30);
                    });
                },

                updateStats: () => {
                    const {
                        counter,
                        progressBar
                    } = state.elements;
                    if (counter) {
                        const current = parseInt(counter.textContent.replace(/\D/g, '')) || 0;
                        const newVal = current + 1;
                        counter.textContent = newVal.toLocaleString('id-ID');

                        if (progressBar && state.totalMusyrif > 0) {
                            progressBar.style.width = `${(newVal / state.totalMusyrif) * 100}%`;
                        }
                    }
                },

                initChart: () => {
                    // --- Konfigurasi Chart Hafalan ---
                    const canvasHafalan = document.getElementById('hafalanChart');
                    if (canvasHafalan) {
                        const ctxHafalan = canvasHafalan.getContext('2d');
                        const gradient = ctxHafalan.createLinearGradient(0, 0, 0, 300);
                        gradient.addColorStop(0, 'rgba(107, 78, 255, 0.4)');
                        gradient.addColorStop(1, 'rgba(107, 78, 255, 0.0)');

                        new Chart(ctxHafalan, {
                            type: 'line',
                            data: {
                                labels: state.chartData.map(d => d.nama_kelas),
                                datasets: [{
                                    label: 'Hafalan',
                                    data: state.chartData.map(d => d.rata_rata),
                                    borderColor: '#6b4eff',
                                    backgroundColor: gradient,
                                    borderWidth: 3,
                                    fill: true,
                                    tension: 0.4,
                                    pointRadius: 4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return ` ${context.parsed.y} Setoran / Santri`;
                                            }
                                        }
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
                    }

                    // --- Konfigurasi Chart Tahsin & Tilawah ---
                    const canvasTahsinTilawah = document.getElementById('tahsinTilawahChart');
                    if (canvasTahsinTilawah) {
                        new Chart(canvasTahsinTilawah.getContext('2d'), {
                            type: 'bar',
                            data: {
                                labels: state.tahsinTilawahData.map(d => d.nama_kelas),
                                datasets: [{
                                        label: 'Tahsin',
                                        data: state.tahsinTilawahData.map(d => d.rata_tahsin),
                                        backgroundColor: 'rgba(20, 184, 166, 0.85)',
                                        borderRadius: 6
                                    },
                                    {
                                        label: 'Tilawah',
                                        data: state.tahsinTilawahData.map(d => d.rata_tilawah),
                                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                                        borderRadius: 6
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            boxWidth: 8
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                if (context.parsed.y !== null) {
                                                    label +=
                                                        `${context.parsed.y} Kali / Santri`;
                                                }
                                                return label;
                                            }
                                        }
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
                    }
                }
            };

            // === 4. NOTIFICATION MODULE ===
            const Notif = {
                requestPermission: () => {
                    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                        Notification.requestPermission();
                    }
                },

                fireEffects: () => {
                    if (!state.isInteracted) return;

                    // Suara iPhone
                    if (state.elements.sound?.readyState >= 2) {
                        state.elements.sound.pause();
                        state.elements.sound.currentTime = 0;
                        state.elements.sound.play().catch(() => {});
                    }

                    // Getar
                    if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
                },

                handleIncoming: (data) => {
                    const logUrl = state.baseUrl.replace(':id', data.musyrifId);

                    // 1. Browser Native Notif
                    if (Notification.permission === "granted") {
                        const n = new Notification("Absensi Masuk!", {
                            body: `${data.nama} baru saja absen jam ${data.waktu}`,
                            icon: "/vendor/pwa/icons/icon-192x192.png"
                        });
                        n.onclick = () => {
                            window.focus();
                            window.location.href = logUrl;
                        };
                    }

                    // 2. SweetAlert Toast
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Absensi Masuk!',
                        html: `<b>${data.nama}</b> baru saja absen.<br><small>Waktu: ${data.waktu}</small>`,
                        showConfirmButton: true,
                        confirmButtonText: 'Cek Log',
                        confirmButtonColor: '#6b4eff',
                        timer: 10000,
                        timerProgressBar: true,
                    }).then((res) => {
                        if (res.isConfirmed) window.location.href = logUrl;
                    });
                }
            };

            // === 5. INITIALIZATION ===
            UI.initChart();
            UI.initCountUp();
            Notif.requestPermission();

            const pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
                cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
                forceTLS: true
            });

            pusher.subscribe('dashboard-channel').bind('musyrif-absen-event', (data) => {
                Notif.fireEffects();
                Notif.handleIncoming(data);
                UI.updateStats();
            });
        });
    </script>
@endpush
