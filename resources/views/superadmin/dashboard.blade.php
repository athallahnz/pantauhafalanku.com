@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

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

        /* Warna Aksen Super Admin */
        .accent-pending {
            border-bottom: 4px solid #dc3545 !important;
        }

        .accent-user {
            border-bottom: 4px solid #6b4eff !important;
        }

        .accent-dept {
            border-bottom: 4px solid #13a3b3 !important;
        }

        .accent-kelas {
            border-bottom: 4px solid #ffc107 !important;
        }

        .accent-santri {
            border-bottom: 4px solid #0dcaf0 !important;
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
            <h4 class="mb-0 fw-bold text-adaptive-purple">Dashboard Super Admin</h4>
            <span class="text-muted small">Ringkasan sistem dan statistik pengguna terdaftar</span>
        </div>
    </div>

    <div class="row g-3 mb-4 row-cols-1 row-cols-md-3 row-cols-lg-5">
        {{-- 1. PENDING (With Button) --}}
        <div class="col">
            <div class="card kpi-card accent-pending">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="kpi-icon" style="background: #ffebeb; color: #dc3545;">
                                <i class="bi bi-person-exclamation"></i>
                            </div>
                            <span class="badge bg-danger">Pending</span>
                        </div>
                        <div class="kpi-label">Butuh Validasi</div>
                        <div class="kpi-value count-up text-danger" data-target="{{ $totalPending }}">0</div>
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('superadmin.users.index') }}"
                            class="btn btn-sm btn-danger w-100 rounded-pill py-1 fw-bold" style="font-size: 0.75rem;">
                            Check User
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. TOTAL USER --}}
        <div class="col">
            <div class="card kpi-card accent-user">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="kpi-icon" style="background: #f3f0ff; color: #6b4eff;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Total User</div>
                    <div class="kpi-value count-up" style="color: #6b4eff;" data-target="{{ $totalUser }}">0</div>
                    <div class="kpi-sub text-muted mt-2" style="font-size: 0.65rem;">Seluruh akun sistem</div>
                </div>
            </div>
        </div>

        {{-- 3. DEPARTEMEN --}}
        <div class="col">
            <div class="card kpi-card accent-dept">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="kpi-icon" style="background: #e6f7f8; color: #13a3b3;">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Admin Dept</div>
                    <div class="kpi-value count-up" style="color: #13a3b3;" data-target="{{ $totalDepartemen }}">0</div>
                    <div class="kpi-sub text-muted mt-2" style="font-size: 0.65rem;">Pengelola sistem</div>
                </div>
            </div>
        </div>

        {{-- 4. KELAS --}}
        <div class="col">
            <div class="card kpi-card accent-kelas">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="kpi-icon" style="background: #fff8e6; color: #ffc107;">
                            <i class="bi bi-easel-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Jumlah Kelas</div>
                    <div class="kpi-value count-up text-warning" data-target="{{ $totalKelas }}">0</div>
                    <div class="kpi-sub text-muted mt-2" style="font-size: 0.65rem;">Kelompok belajar</div>
                </div>
            </div>
        </div>

        {{-- 5. SANTRI --}}
        <div class="col">
            <div class="card kpi-card accent-santri">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="kpi-icon" style="background: #e7faff; color: #0dcaf0;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-label">Total Santri</div>
                    <div class="kpi-value count-up text-info" data-target="{{ $totalSantri }}">0</div>
                    <div class="kpi-sub text-muted mt-2" style="font-size: 0.65rem;">Siswa aktif</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- CHART STATISTIK USER PER ROLE --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 mb-4 h-100">
                <div class="card-header bg-transparent py-3 fw-semibold">
                    <i class="bi bi-pie-chart-fill me-2" style="color: var(--islamic-tosca-500);"></i> Statistik User per
                    Role
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div style="position: relative; height: 250px; width: 100%;">
                        <canvas id="userRoleChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL USER RINGKAS --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 mb-4 h-100">
                <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center fw-semibold">
                    <div><i class="bi bi-table me-2" style="color: var(--islamic-tosca-500);"></i> User Terdaftar</div>
                    <a href="{{ route('superadmin.users.index') }}"
                        class="btn btn-sm text-white px-3 rounded-pill shadow-sm"
                        style="background: var(--islamic-purple-600);">
                        Kelola User <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table id="dashboard-users-table" class="table table-striped table-hover align-middle w-100 mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">No.</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th class="pe-4">Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Data diisi DataTables via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- RINGKASAN SISTEM --}}
    <div class="card border-0 shadow-sm rounded-4 mt-2">
        <div class="card-body p-4 d-flex align-items-center gap-3">
            <div class="flex-shrink-0 text-white rounded-circle d-flex align-items-center justify-content-center"
                style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--islamic-purple-500), var(--islamic-tosca-500));">
                <i class="bi bi-info-circle fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1">Ringkasan Sistem</h6>
                <p class="mb-0 text-muted small">
                    Halaman ini menampilkan ringkasan global pengguna sistem hafalan santri. Ke depan bisa ditambah
                    ringkasan hafalan santri, jumlah musyrif aktif, dan distribusi kelas.
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // ==========================
            // 1) CHART USER PER ROLE
            // ==========================
            const roleLabels = @json($roleCounts->keys());
            const roleData = @json($roleCounts->values());

            const ctx = document.getElementById('userRoleChart').getContext('2d');

            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        label: 'Jumlah User',
                        data: roleData,
                        backgroundColor: [
                            '#6b4eff', // Islamic Purple
                            '#13a3b3', // Islamic Tosca
                            '#ffc107', // Warning/Gold
                            '#0dcaf0', // Info/Blue
                            '#e83e8c' // Pink (cadangan)
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });

            // ==========================
            // 2) DATATABLE USER RINGKAS
            // ==========================
            $('#dashboard-users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('superadmin.users.datatable') }}",
                pageLength: 5,
                lengthChange: false,
                searching: false,
                info: false, // Menghilangkan tulisan "Showing 1 to 5..." agar lebih bersih di dashboard
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                        className: 'ps-4' // Padding rata kiri
                    },
                    {
                        data: 'name',
                        name: 'name',
                        className: 'fw-medium' // Nama di-bold sedikit
                    },
                    {
                        data: 'email',
                        name: 'email',
                        className: 'text-muted small'
                    },
                    {
                        data: 'role',
                        name: 'role',
                        orderable: false,
                        searchable: false,
                        className: 'pe-4',
                        render: function(data, type, row) {
                            if (!data) return '-';

                            let roleName = data.toString().trim().toLowerCase();
                            let badgeClass = 'bg-secondary';

                            if (roleName.includes('superadmin')) {
                                badgeClass = 'bg-danger';
                            } else if (roleName.includes('admin')) {
                                badgeClass = 'bg-success';
                            } else if (roleName.includes('musyrif')) {
                                badgeClass = 'bg-warning text-dark';
                            } else if (roleName.includes('santri')) {
                                badgeClass = 'bg-primary';
                            } else if (roleName.includes('pimpinan')) {
                                badgeClass = 'bg-info text-dark';
                            }

                            return `<span class="badge ${badgeClass} rounded-pill px-3 py-2" style="font-weight: 600; letter-spacing: 0.5px;">${data.toString().trim().toUpperCase()}</span>`;
                        }
                    }
                ],
                order: [
                    [1, 'asc']
                ]
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

            // 1. AKTIFKAN LOG PUSHER KE BROWSER CONSOLE
            Pusher.logToConsole = true;

            // 2. Gunakan petik ganda (") di dalam config agar tidak bentrok dengan petik tunggal (') blade
            const pusher = new Pusher('{{ config('broadcasting.connections.pusher.key') }}', {
                cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
                forceTLS: true
            });

            const channel = pusher.subscribe('admin-channel');

            channel.bind('user-registered', function(data) {
                console.log("DATA DARI PUSHER DITERIMA:", data);

                const pendingEl = document.querySelector('.count-up.text-danger');
                const totalUserEl = document.querySelector('.accent-user .count-up');

                if (pendingEl) {
                    pendingEl.setAttribute('data-target', data.totalPending);
                    pendingEl.innerText = data.totalPending;
                }

                if (totalUserEl && data.totalUser) {
                    totalUserEl.setAttribute('data-target', data.totalUser);
                    totalUserEl.innerText = data.totalUser;
                }

                if ($.fn.DataTable.isDataTable('#dashboard-users-table')) {
                    $('#dashboard-users-table').DataTable().ajax.reload(null, false);
                }

                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Ada Pendaftar Baru!',
                    text: 'Total Pending: ' + data.totalPending,
                    showConfirmButton: false,
                    timer: 4000,
                    timerProgressBar: true
                });
            });
        });
    </script>
@endpush
