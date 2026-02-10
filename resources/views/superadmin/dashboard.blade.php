@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
    <style>
        .kpi-card {
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfcfd 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: all .25s ease;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06), 0 3px 6px rgba(0, 0, 0, 0.08);
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

    {{-- KPI CARDS --}}
    <div class="row mb-4 g-3">

        {{-- Total User --}}
        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">
                                Total User
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $totalUser }}">
                                0
                            </div>
                        </div>
                        <div class="kpi-icon bg-danger-subtle text-danger">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-danger" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Departemen (Admin) --}}
        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">
                                Departemen (Admin)
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $totalDepartemen }}">
                                0
                            </div>
                        </div>
                        <div class="kpi-icon bg-success-subtle text-success">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-success" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">
                                Jumlah Kelas
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $totalKelas }}">
                                0
                            </div>
                        </div>
                        <div class="kpi-icon bg-info-subtle text-info">
                            <i class="bi bi-easel"></i>
                        </div>
                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-info" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card kpi-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">
                                Jumlah Santri
                            </div>
                            <div class="kpi-value count-up" data-target="{{ $totalSantri }}">
                                0
                            </div>
                        </div>
                        <div class="kpi-icon bg-primary-subtle text-primary">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>

                    <div class="kpi-progress mt-3">
                        <div class="kpi-progress-bar bg-primary" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- CHART STATISTIK USER PER ROLE --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    Statistik User per Role
                </div>
                <div class="card-body">
                    <canvas id="userRoleChart" height="200"></canvas>
                </div>
            </div>
        </div>

        {{-- TABEL USER RINGKAS --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>User Terdaftar</span>
                    <a href="{{ route('superadmin.users.index') }}" class="btn btn-sm btn-light">
                        Kelola User
                    </a>
                </div>
                <div class="card-body table-responsive">
                    <table id="dashboard-users-table" class="table table-striped align-middle w-100">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
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

    {{-- RINGKASAN SISTEM --}}
    <div class="card mb-4">
        <div class="card-header">
            Ringkasan Sistem
        </div>
        <div class="card-body">
            <p>
                Halaman ini menampilkan ringkasan global pengguna sistem hafalan santri.
                Ke depan bisa ditambah ringkasan hafalan santri, jumlah musyrif aktif, dan distribusi kelas.
            </p>
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
                type: 'bar',
                data: {
                    labels: roleLabels,
                    datasets: [{
                        label: 'Jumlah User',
                        data: roleData,
                        borderWidth: 1
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

            // ==========================
            // 2) DATATABLE USER RINGKAS
            //    (reuse endpoint superadmin.users.datatable)
            // ==========================
            $('#dashboard-users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('superadmin.users.datatable') }}",
                pageLength: 5,
                lengthChange: false,
                searching: false,
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'role',
                        name: 'role',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [
                    [1, 'asc']
                ]
            });

            // ================= COUNT UP (NO AUDIO) =================
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
                    const eased = easeOut(progress);
                    const current = Math.round(start + (target - start) * eased);

                    counter.textContent = current.toLocaleString('id-ID');

                    if (frame >= totalFrames) {
                        counter.textContent = target.toLocaleString('id-ID');
                        clearInterval(interval);
                    }
                }, 1000 / frameRate);
            });

            // ================= EASING =================
            function easeOut(t) {
                return 1 - Math.pow(1 - t, 3);
            }

        });
    </script>
@endpush
