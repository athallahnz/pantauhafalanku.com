@extends('layouts.app')

@section('title', 'Analisis Kehadiran Tahfidz')

@section('content')
    <style>
        /* Global Variables */
        :root {
            --report-bg: #ffffff;
            --report-border: rgba(0, 0, 0, 0.08);
            --item-hover: rgba(107, 78, 255, 0.04);
        }

        [data-coreui-theme="dark"] {
            --report-bg: #1a1a21;
            --report-border: rgba(255, 255, 255, 0.08);
            --item-hover: rgba(255, 255, 255, 0.03);
        }

        /* Card & Modal Container */
        .report-card {
            border-radius: 1rem;
            border: 1px solid var(--report-border) !important;
            background-color: var(--report-bg) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        }

        /* Typography & Colors */
        .text-purple {
            color: #6b4eff !important;
        }

        .fw-800 {
            font-weight: 800;
        }

        /* Clean Modal UI */
        .modal-content {
            border-radius: 1rem;
            border: none;
            background-color: var(--report-bg);
        }

        .modal-header {
            border-bottom: 1px solid var(--report-border);
            padding: 1.5rem;
        }

        /* Tombol Close (X) agar terlihat di Dark Mode */
        [data-coreui-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Table Minimalist */
        .table thead th {
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #8a8d93;
            border-bottom: 1px solid var(--report-border) !important;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--report-border);
        }

        #detailMusyrifBody tr:hover {
            background-color: var(--item-hover);
            transition: background 0.2s ease;
        }

        /* Stats bar in Modal */
        .stats-segment {
            border-right: 1px solid var(--report-border);
        }

        .stats-segment:last-child {
            border-right: none;
        }

        /* DataTables Customization */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #6b4eff !important;
            border: none !important;
            color: white !important;
            border-radius: 0.5rem;
        }

        /* Tombol Detail Profesional */
        .btn-detail-action {
            background-color: rgba(107, 78, 255, 0.08);
            color: #6b4eff;
            border: 1px solid rgba(107, 78, 255, 0.15);
            transition: all 0.2s ease;
        }

        .btn-detail-action:hover {
            background-color: #6b4eff;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(107, 78, 255, 0.25);
            transform: translateY(-1px);
        }

        /* Dark Mode Adjustment */
        [data-coreui-theme="dark"] .btn-detail-action {
            background-color: rgba(107, 78, 255, 0.15);
            color: #9d8aff;
            border-color: rgba(107, 78, 255, 0.3);
        }

        [data-coreui-theme="dark"] .btn-detail-action:hover {
            background-color: #6b4eff;
            color: #ffffff;
        }

        /* Memastikan tombol di dalam DataTable tidak terpotong */
        .table td {
            vertical-align: middle;
        }
    </style>

    <div class="container-fluid">
        {{-- Header Section --}}
        <div class="row align-items-center mb-4 g-3">
            <div class="col-md-6">
                <h4 class="mb-1 fw-bold text-adaptive-purple">Analisis Poin Pelanggaran Santri</h4>

                <p class="text-muted small mb-0">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ $startDate->translatedFormat('d M Y') }} — {{ $endDate->translatedFormat('d M Y') }}
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <button onclick="window.print()" class="btn btn-white border rounded-pill px-4 shadow-sm">
                    <i class="bi bi-printer me-2"></i>Cetak
                </button>
            </div>
        </div>

        {{-- Filter Section --}}
        <div class="card report-card mb-4 border-0">
            <div class="card-body p-3">
                <form id="filterForm" class="row g-3 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted">Dari</label>
                        <input type="date" name="start_date" id="start_date"
                            class="form-control rounded-pill border-0 bg-body-tertiary px-3"
                            value="{{ $startDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted">Sampai</label>
                        <input type="date" name="end_date" id="end_date"
                            class="form-control rounded-pill border-0 bg-body-tertiary px-3"
                            value="{{ $endDate->format('Y-m-d') }}">
                    </div>
                    <div class="col-12 col-md-6 d-flex gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-4 flex-grow-1">Terapkan
                            Filter</button>
                        <a href="{{ route('santri.master.violation.report') }}"
                            class="btn btn-secondary rounded-pill px-4">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-4">
            {{-- Chart --}}
            <div class="col-lg-8">
                <div class="card report-card h-100">
                    <div class="card-header bg-transparent border-0 py-4 px-4">
                        <h6 class="fw-bold mb-0 mt-0"><i class="bi bi-bar-chart"></i> Tren Ketidakhadiran</h6>
                        <small class="text-white">Frekuensi Alpha berdasarkan hari kerja</small>
                    </div>
                    <div class="card-body px-4">
                        <canvas id="dayAnalysisChart" style="height: 280px;"></canvas>
                    </div>
                </div>
            </div>

            {{-- Top Violators List --}}
            <div class="col-lg-4">
                <div class="card report-card h-100 border-start border-4 border-danger">
                    <div class="card-header bg-transparent border-0 py-4 px-4">
                        <h6 class="fw-bold mb-0 text-white"><i class="bi bi-exclamation-triangle"></i> Santri Paling Kritis
                        </h6>
                        <small class="text-white">Top 10 akumulasi poin tertinggi</small>
                    </div>
                    <div class="card-body px-4">
                        <div class="vstack gap-3 mt-2">
                            @forelse($topViolators as $item)
                                <div
                                    class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-body-tertiary">
                                    <div class="overflow-hidden">
                                        <span class="fw-bold d-block text-truncate small"
                                            style="max-width: 150px;">{{ $item->santri->nama }}</span>
                                        <small class="text-muted fw-bold"
                                            style="font-size: 10px;">{{ $item->santri->kelas->nama_kelas ?? '-' }}</small>
                                    </div>
                                    <span class="badge bg-danger rounded-pill">{{ $item->total_poin }} pts</span>
                                </div>
                            @empty
                                <div class="text-center py-5 text-muted small">Data tidak tersedia</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Yajra DataTable Section --}}
        <div class="card report-card mb-5">
            <div class="card-header bg-transparent border-0 py-4 px-4">
                <h6 class="fw-bold mb-0"><i class="bi bi-people"></i> Evaluasi Musyrif Pendamping</h6>
                <small class="text-white">Analisis efektivitas kontrol kehadiran per halaqah</small>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="musyrifTable" class="table align-middle w-100">
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th class="rounded-start">#</th>
                                <th>Musyrif</th>
                                <th class="text-center">Total Alpha</th>
                                <th class="text-center">Status</th>
                                <th class="rounded-end text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('modals')
    {{-- Modal Detail Musyrif - Clean & Functional --}}
    <div class="modal fade" id="modalDetailMusyrif" tabindex="-1" aria-labelledby="modalDetailMusyrifLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg">

                <div class="modal-header d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h5 class="fw-bold mb-0 text-white" id="detailMusyrifName">Detail Halaqah</h5>
                        <p class="text-white small mb-0">Rekapitulasi ketidakhadiran santri periode ini</p>
                    </div>
                    {{-- Tombol X: Pastikan data-coreui-dismiss benar --}}
                    <button type="button" class="btn-close shadow-none" data-coreui-dismiss="modal" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    {{-- Quick Stats Bar --}}
                    <div class="d-flex bg-body-tertiary border-bottom border-light">
                        <div class="flex-fill p-3 text-center stats-segment">
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.6rem;">Total Santri</span>
                            <h5 class="fw-800 mb-0 mt-1" id="statTotalSantri">0</h5>
                        </div>
                        <div class="flex-fill p-3 text-center stats-segment">
                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.6rem;">Total Poin</span>
                            <h5 class="fw-800 mb-0 mt-1 text-purple" id="statTotalPoin">0</h5>
                        </div>
                    </div>

                    {{-- Table Detail --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Santri</th>
                                    <th class="text-center">Alpha</th>
                                    <th class="text-center">Poin</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody id="detailMusyrifBody" class="border-0">
                                {{-- Data dimuat via AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer border-0 p-3">
                    {{-- Tombol Tutup - Menambahkan ganda dismiss attribute --}}
                    <button type="button"
                        class="btn btn-light rounded-pill px-4 fw-bold text-muted small border shadow-sm"
                        data-coreui-dismiss="modal" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush
@push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            // === 1. Yajra DataTable Initialization ===
            const table = $('#musyrifTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('santri.master.violation.report') }}",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'musyrif_nama',
                        name: 'musyrif_nama'
                    },
                    {
                        data: 'total_alpha',
                        name: 'total_alpha',
                        className: 'text-center'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    },
                ],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Cari musyrif...",
                    lengthMenu: "_MENU_",
                },
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            });

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                table.draw();
            });

            // === 2. Chart.js with Theme Colors ===
            const isDark = document.documentElement.getAttribute('data-coreui-theme') === 'dark';
            const ctx = document.getElementById('dayAnalysisChart').getContext('2d');
            const chartData = @json($chartDays);

            // Di dalam AJAX DataTable (Controller atau Script)
            // Contoh mapping status yang lebih profesional
            const statusLabels = {
                'danger': '<span class="badge rounded-pill" style="background: rgba(229, 62, 62, 0.1); color: #e53e3e; border: 1px solid rgba(229, 62, 62, 0.1);">Kontrol Lemah</span>',
                'warning': '<span class="badge rounded-pill" style="background: rgba(214, 158, 0, 0.1); color: #b78600; border: 1px solid rgba(214, 158, 0, 0.1);">Waspada</span>',
                'success': '<span class="badge rounded-pill" style="background: rgba(56, 161, 105, 0.1); color: #38a169; border: 1px solid rgba(56, 161, 105, 0.1);">Sangat Baik</span>'
            };

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.map(d => d.hari),
                    datasets: [{
                        data: chartData.map(d => d.total),
                        backgroundColor: '#6b4eff',
                        borderRadius: 8,
                        barThickness: 24,
                    }]
                },
                options: {
                    responsive: true,
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
                                color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)'
                            },
                            ticks: {
                                color: isDark ? '#adb5bd' : '#6c757d'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: isDark ? '#adb5bd' : '#6c757d'
                            }
                        }
                    }
                }
            });

            // Handle klik tombol Detail
            $('#musyrifTable').on('click', '.btn-detail-musyrif', function() {
                const id = $(this).data('id');
                const start = $('#start_date').val();
                const end = $('#end_date').val();
                const btn = $(this);

                btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);

                $.ajax({
                    url: `{{ url('santri-master/violation-report/musyrif') }}/${id}`,
                    data: {
                        start_date: start,
                        end_date: end
                    },
                    success: function(res) {
                        $('#detailMusyrifName').text(res.musyrif);

                        let html = '';
                        let totalPoinHalaqah = 0;

                        res.data.forEach(item => {
                            totalPoinHalaqah += parseInt(item.total_poin);

                            // Professional Condition Label
                            let level = item.total_alpha > 3 ?
                                '<span class="text-danger fw-bold"><i class="bi bi-dot"></i> Kritis</span>' :
                                '<span class="text-success fw-bold"><i class="bi bi-dot"></i> Stabil</span>';

                            html += `
                <tr>
                    <td class="ps-4 py-3">
                        <span class="d-block fw-semibold text-dark">${item.santri.nama}</span>
                        <span class="text-muted" style="font-size: 0.75rem;">${item.santri.kelas ? item.santri.kelas.nama_kelas : '-'}</span>
                    </td>
                    <td class="text-center fw-semibold">${item.total_alpha}</td>
                    <td class="text-center text-primary fw-bold">${item.total_poin}</td>
                    <td class="text-end pe-4 small text-uppercase" style="font-size: 0.7rem;">
                        ${level}
                    </td>
                </tr>`;
                        });

                        $('#statTotalSantri').text(res.data.length);
                        $('#statTotalPoin').text(totalPoinHalaqah);
                        $('#detailMusyrifBody').html(html ||
                            '<tr><td colspan="4" class="text-center py-5 text-muted">No records found.</td></tr>'
                        );

                        $('#modalDetailMusyrif').modal('show');
                    },
                    complete: function() {
                        btn.html('Detail Kelas').prop('disabled', false);
                    }
                });
            });

            /** * FAILSAFE: Manual Modal Dismiss
             * Jika tombol X atau Tutup tetap tidak jalan, script ini akan memaksanya.
             */
            $(document).on('click', '[data-coreui-dismiss="modal"], [data-bs-dismiss="modal"]', function() {
                const targetModal = $(this).closest('.modal');

                // Coba tutup menggunakan CoreUI/Bootstrap API
                if (typeof coreui !== 'undefined') {
                    const modalInstance = coreui.Modal.getInstance(targetModal[0]);
                    if (modalInstance) modalInstance.hide();
                } else if (typeof bootstrap !== 'undefined') {
                    const modalInstance = bootstrap.Modal.getInstance(targetModal[0]);
                    if (modalInstance) modalInstance.hide();
                }

                // Jika API gagal, gunakan cara manual (hide & remove backdrop)
                targetModal.modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open').css('overflow', '');
            });
        });
    </script>
@endpush
