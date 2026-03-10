@extends('layouts.app')

@section('title', 'Riwayat Absensi')

@section('content')
    <style>
        /* ================= KONSISTENSI & DARK THEME ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
            transition: color 0.3s ease;
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ffffff !important;
        }

        /* Filter Section Mobile Optimization */
        @media (max-width: 768px) {

            /* Hapus flex-direction: column agar grid Bootstrap bekerja normal */
            .filter-card .btn {
                font-size: 0.9rem;
                /* Sedikit dikecilkan agar teks tidak terpotong di HP kecil */
                padding-top: 10px !important;
                padding-bottom: 10px !important;
            }
        }

        /* ================= CALENDAR STYLING ================= */
        .calendar td {
            height: 90px;
            vertical-align: top;
            padding: 8px;
            position: relative;
            background: var(--cui-card-bg, #fff);
            border-color: var(--cui-border-color) !important;
            transition: all 0.2s ease;
        }

        .calendar .today {
            background: rgba(111, 66, 193, 0.05) !important;
            box-shadow: inset 0 0 0 2px var(--islamic-purple-500);
        }

        .calendar .day-number {
            font-size: 14px;
            font-weight: 700;
            color: var(--cui-body-color);
        }

        .calendar .outside {
            color: var(--cui-secondary-color);
            opacity: 0.4;
        }

        /* Dot Indicators di Kalender */
        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 3px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Mobile Calendar Optimization */
        @media (max-width: 576px) {
            .calendar td {
                height: 70px;
                padding: 4px;
            }

            .calendar .day-number {
                font-size: 12px;
            }

            .badge-dot {
                width: 6px;
                height: 6px;
                margin-right: 2px;
            }
        }

        /* ================= MOBILE LIST CARD STYLING ================= */
        .history-card {
            background: var(--cui-card-bg, #fff);
            border: 1px solid var(--cui-border-color);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .history-card:active {
            transform: scale(0.98);
        }

        .thumb-history {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid rgba(0, 0, 0, 0.05);
        }

        /* Fix Badge Shadow for Dark Mode */
        [data-coreui-theme="dark"] .badge {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        /* ================= DARK MODE TEXT FIXES ================= */
        /* Memastikan teks Rincian Kehadiran dan teks dalam card list menyesuaikan diri */
        [data-coreui-theme="dark"] h5.text-adaptive-purple {
            color: #ffffff !important;
        }

        [data-coreui-theme="dark"] .history-card h6 {
            color: #e0e0e0 !important;
            /* Warna teks tanggal (putih sedikit keabuan) */
        }

        [data-coreui-theme="dark"] .history-card .text-muted {
            color: #a0a0a0 !important;
            /* Warna ikon jam dan akurasi (abu terang) */
        }

        [data-coreui-theme="dark"] .history-card {
            background: var(--cui-card-bg, #2a2a35) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Fix Tombol Reset di Dark Mode */
        [data-coreui-theme="dark"] .filter-card .btn-light {
            background-color: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: #e0e0e0 !important;
            /* Menimpa text-muted menjadi putih terang */
        }

        [data-coreui-theme="dark"] .filter-card .btn-light:hover {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
        }
    </style>

    <div class="container py-4 pb-5">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold text-adaptive-purple">Riwayat Absen</h4>
            <a href="{{ route('musyrif.absensi.index') }}"
                class="btn btn-outline-primary rounded-pill px-3 shadow-sm d-flex align-items-center">
                <i class="bi bi-arrow-left me-md-2"></i> <span class="d-none d-md-inline">Kembali</span>
            </a>
        </div>

        {{-- FILTER BULAN --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 filter-card">
            <div class="card-body p-3 p-md-4">
                <form method="GET" class="row g-2 g-md-3 align-items-end">
                    {{-- Input memakan full width (12 kolom) di Mobile, 7 kolom di Desktop --}}
                    <div class="col-12 col-md-7 mb-2 mb-md-0">
                        <label class="form-label small fw-bold text-uppercase text-muted">Periode Bulan</label>
                        <input type="month" name="month" value="{{ request('month', $month) }}"
                            class="form-control form-control-lg rounded-pill border-2 fs-6">
                    </div>

                    {{-- Tombol Tampilkan memakan 6 kolom (setengah layar) di Mobile --}}
                    <div class="col-6 col-md-3">
                        <button class="btn btn-primary rounded-pill w-100 fw-bold py-2 shadow-sm">Tampilkan</button>
                    </div>

                    {{-- Tombol Reset memakan 6 kolom (setengah layar) di Mobile --}}
                    <div class="col-6 col-md-2">
                        <a href="{{ route('musyrif.absensi.history') }}"
                            class="btn btn-outline-secondary border-2 rounded-pill w-100 py-2 fw-bold text-center d-block">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $todayStr = now()->format('Y-m-d');
            $daysOfWeek = ['Ahd', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

            // Helper warna untuk dot & badge status
            $statusColor = fn($status) => match ($status) {
                'valid' => 'bg-success',
                'suspect' => 'bg-warning text-dark',
                'rejected' => 'bg-danger',
                default => 'bg-secondary',
            };

            $gridStart = \Carbon\Carbon::parse($start)->startOfWeek(\Carbon\Carbon::SUNDAY);
            $gridEnd = \Carbon\Carbon::parse($end)->endOfWeek(\Carbon\Carbon::SATURDAY);
        @endphp

        {{-- 1. BAGIAN KALENDER --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div
                class="card-header bg-transparent border-bottom py-3 px-4 fw-bold text-white d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-calendar-month me-2"></i>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered calendar mb-0 border-light w-100">
                    <thead>
                        <tr class="text-center small text-muted text-uppercase fw-bold bg-light">
                            @foreach ($daysOfWeek as $d)
                                <th class="py-2" style="width: 14.28%;">{{ $d }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php $cursor = $gridStart->copy(); @endphp
                        @while ($cursor <= $gridEnd)
                            <tr>
                                @for ($i = 0; $i < 7; $i++)
                                    @php
                                        $dateString = $cursor->format('Y-m-d');
                                        $isToday = $dateString === $todayStr;
                                        $inMonth = $cursor->month == \Carbon\Carbon::parse($start)->month;
                                        $mor = $calendar[$dateString]['morning'] ?? null;
                                        $aft = $calendar[$dateString]['afternoon'] ?? null;
                                    @endphp
                                    <td class="{{ $isToday ? 'today' : '' }}">
                                        <div class="day-number {{ $inMonth ? '' : 'outside' }}">{{ $cursor->day }}</div>

                                        @if ($isToday)
                                            <span class="badge bg-primary position-absolute p-1 rounded-circle"
                                                style="top: 5px; right: 5px; width: 6px; height: 6px;"
                                                title="Hari Ini"></span>
                                        @endif

                                        <div class="mt-1 d-flex gap-1 flex-wrap">
                                            @if ($mor)
                                                <span class="badge-dot {{ $statusColor($mor) }}"
                                                    title="Pagi: {{ $mor }}"></span>
                                            @endif
                                            @if ($aft)
                                                <span class="badge-dot {{ $statusColor($aft) }}"
                                                    title="Malam: {{ $aft }}"></span>
                                            @endif
                                        </div>
                                    </td>
                                    @php $cursor->addDay(); @endphp
                                @endfor
                            </tr>
                        @endwhile
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 2. BAGIAN DATA RIWAYAT --}}

        <h5 class="fw-bold text-adaptive-purple mb-3 mt-2 px-2"><i class="bi bi-list-check me-2"></i>Rincian Kehadiran</h5>

        {{-- A. TAMPILAN MOBILE (LIST CARDS) --}}
        <div class="d-block d-md-none">
            @forelse($data as $row)
                <div class="history-card position-relative">
                    <div class="d-flex align-items-start gap-3">
                        {{-- Thumbnail Foto --}}
                        <a href="{{ asset('storage/' . $row->photo_path) }}" target="_blank" class="flex-shrink-0">
                            <img src="{{ asset('storage/' . $row->photo_path) }}" class="thumb-history shadow-sm">
                        </a>

                        {{-- Info Text --}}
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 fw-bold text-truncate" style="font-size: 0.95rem;">
                                    {{ $row->attendance_at->format('d M Y') }}
                                </h6>
                                <span
                                    class="badge {{ $row->type == 'morning' ? 'bg-success' : 'bg-primary' }} rounded-pill"
                                    style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                    {{ $row->type == 'morning' ? 'PAGI' : 'MALAM' }}
                                </span>
                            </div>

                            <div class="text-muted mb-2" style="font-size: 0.8rem;">
                                <i class="bi bi-clock me-1"></i> {{ $row->attendance_at->format('H:i') }} WIB
                            </div>

                            <div class="d-flex justify-content-between align-items-end">
                                <div class="text-muted text-truncate pe-2" style="font-size: 0.7rem; max-width: 60%;">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>Akurasi:
                                    {{ $row->accuracy ?? '-' }}m
                                </div>
                                <span class="badge {{ $statusColor($row->status) }} rounded-pill px-2 py-1 shadow-sm"
                                    style="font-size: 0.65rem;">
                                    {{ strtoupper($row->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                    Belum ada riwayat absensi.
                </div>
            @endforelse

            {{-- Mobile Pagination --}}
            <div class="mt-3">
                {{ $data->onEachSide(1)->links() }}
            </div>
        </div>

        {{-- B. TAMPILAN DESKTOP (TABLE) --}}
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden d-none d-md-block">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="small fw-bold text-muted text-uppercase bg-light">
                            <tr>
                                <th class="ps-4 py-3">WAKTU</th>
                                <th>JENIS</th>
                                <th>LOKASI</th>
                                <th>FOTO</th>
                                <th class="pe-4 text-end">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold small">{{ $row->attendance_at->format('d/m/Y') }}</div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            {{ $row->attendance_at->format('H:i') }} WIB</div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $row->type == 'morning' ? 'bg-success' : 'bg-primary' }} rounded-pill px-3 py-2"
                                            style="font-size: 10px; letter-spacing: 0.5px;">
                                            {{ $row->type == 'morning' ? 'PAGI' : 'MALAM' }}
                                        </span>
                                    </td>
                                    <td class="small">
                                        <div class="text-muted"><i
                                                class="bi bi-geo-alt-fill text-danger me-1"></i>{{ $row->latitude }},
                                            {{ $row->longitude }}</div>
                                        <div class="fw-bold mt-1" style="font-size: 10px;">Akurasi:
                                            {{ $row->accuracy ?? '-' }}m</div>
                                    </td>
                                    <td>
                                        <a href="{{ asset('storage/' . $row->photo_path) }}" target="_blank">
                                            <img src="{{ asset('storage/' . $row->photo_path) }}"
                                                class="rounded-3 border shadow-sm"
                                                style="height:48px; width:48px; object-fit:cover;">
                                        </a>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <span
                                            class="badge {{ $statusColor($row->status) }} px-3 py-2 rounded-pill shadow-sm"
                                            style="font-size: 10px; letter-spacing: 0.5px;">
                                            {{ strtoupper($row->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted small">Belum ada riwayat
                                        absensi.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    {{ $data->links() }}
                </div>
            </div>
        </div>

    </div>
@endsection
