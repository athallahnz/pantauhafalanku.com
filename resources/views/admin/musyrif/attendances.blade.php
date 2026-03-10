@extends('layouts.app')

@section('title', 'Riwayat Absensi Musyrif')

@section('content')
    <style>
        /* ================= TEMA ISLAMIC PURPLE & MODERN UI ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

        /* FIX: Header Page & Teks Ungu di Dark Mode */
        [data-coreui-theme="dark"] .text-adaptive-purple,
        [data-coreui-theme="dark"] h4,
        [data-coreui-theme="dark"] .fw-bold {
            color: #ececec !important;
        }

        .main-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            background-color: var(--cui-card-bg);
        }

        /* ================= FIX HEADER TABEL DARKMODE ================= */
        .table thead th {
            background-color: var(--cui-tertiary-bg);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: var(--cui-secondary-color);
            padding: 15px;
            border-bottom: 1px solid var(--cui-border-color);
        }

        [data-coreui-theme="dark"] .table thead th {
            background-color: #2a2a35 !important;
            /* Warna gelap yang lebih kontras */
            color: #d1d1d1 !important;
            border-bottom: 1px solid #3c3c4b;
        }

        /* ================= FIX CALENDAR DATE ROW ================= */
        .calendar-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--cui-border-color);
            border-radius: 15px;
            overflow: hidden;
        }

        /* Header Hari (Ahd, Sen, dst) di Dark Mode */
        [data-coreui-theme="dark"] .calendar-table thead th {
            background-color: #2a2a35 !important;
            color: #ffffff !important;
        }

        .calendar-day-box {
            height: 100px;
            transition: all 0.2s ease;
            vertical-align: top !important;
            padding: 10px !important;
            border-color: var(--cui-border-color-translucent) !important;
        }

        /* Angka Tanggal di Dark Mode */
        .day-number {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--cui-body-color);
        }

        [data-coreui-theme="dark"] .day-number {
            color: #ffffff !important;
            /* Pastikan angka putih di mode gelap */
        }

        /* Tanggal di luar bulan aktif agar tidak terlalu terang */
        [data-coreui-theme="dark"] .calendar-day-box.bg-light {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        [data-coreui-theme="dark"] .text-muted {
            color: #8a8a95 !important;
        }

        /* Hover effect di dark mode */
        [data-coreui-theme="dark"] .calendar-day-box:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Badge Mode Responsive */
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .badge-pill-custom {
            font-size: 0.65rem;
            padding: 3px 8px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        @media (max-width: 576px) {
            .badge-text-hide {
                display: none !important;
            }

            .badge-dot {
                display: inline-block !important;
            }

            .calendar-day-box {
                height: 70px;
                padding: 5px !important;
            }
        }

        /* ================= FIX TOMBOL KEMBALI ================= */

        /* Warna Tombol di Dark Mode */
        [data-coreui-theme="dark"] .btn-light {
            background-color: #32323e !important;
            border-color: #464652 !important;
            color: #ffffff !important;
        }

        [data-coreui-theme="dark"] .btn-light:hover {
            background-color: #3d3d4b !important;
        }

        /* Logika Tombol Hanya Ikon di Mobile */
        @media (max-width: 576px) {
            .btn-back-responsive {
                width: 42px;
                height: 42px;
                padding: 0 !important;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                border-radius: 50% !important;
                /* Jadi bulat di HP */
            }

            .btn-back-responsive span {
                display: none !important;
                /* Sembunyikan teks 'Kembali' */
            }

            .btn-back-responsive i {
                margin: 0 !important;
                font-size: 1.25rem;
            }
        }
    </style>

    {{-- HEADER --}}
    <div class="row mb-4 align-items-center px-3 px-md-0 g-2">
        <div class="col">
            <h4 class="fw-bold text-adaptive-purple mb-1">Riwayat Absensi</h4>
            <p class="text-muted small mb-0 d-none d-md-block"> {{-- Detail musyrif sembunyi di mobile agar lega --}}
                <i class="bi bi-person-circle me-1"></i> {{ $musyrif->nama }}
                <span class="mx-2">|</span>
                <i class="bi bi-hash me-1"></i> {{ $musyrif->kode ?? 'No Code' }}
            </p>
            <p class="text-muted small mb-0 d-block d-md-none">
                {{ $musyrif->nama }}
            </p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.musyrif.index') }}"
                class="btn btn-light btn-back-responsive rounded-pill px-4 fw-bold shadow-sm"
                title="Kembali ke Daftar Musyrif">
                <i class="bi bi-arrow-left me-md-1"></i>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">{{ session('success') }}</div>
    @endif

    {{-- FILTER CARD --}}
    <div class="card main-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">PERIODE BULAN</label>
                    <input type="month" name="month" class="form-control rounded-3"
                        value="{{ request('month', now()->format('Y-m')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">SESI</label>
                    <select name="type" class="form-select rounded-3">
                        <option value="">Semua Sesi</option>
                        <option value="morning" @selected(request('type') === 'morning')>Pagi</option>
                        <option value="afternoon" @selected(request('type') === 'afternoon')>Malam</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">STATUS</label>
                    <select name="status" class="form-select rounded-3">
                        <option value="">Semua Status</option>
                        <option value="valid" @selected(request('status') === 'valid')>Valid</option>
                        <option value="suspect" @selected(request('status') === 'suspect')>Suspect</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm" type="submit">
                        <i class="bi bi-filter me-1"></i> Filter
                    </button>
                    <a class="btn btn-outline-secondary rounded-pill w-100 fw-bold"
                        href="{{ route('admin.musyrif.attendances', $musyrif->id) }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- KALENDER CARD --}}
    <div class="card main-card mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom-0">
            <h6 class="fw-bold text-white mb-0">
                <i class="bi bi-calendar3 me-2"></i>Kalender Absensi -
                {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }}
            </h6>
        </div>
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                <table class="table calendar-table align-middle mb-0">
                    <thead>
                        <tr>
                            @foreach (['Ahd', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $d)
                                <th class="text-center">{{ $d }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $cursor = \Carbon\Carbon::parse($start)->startOfWeek(\Carbon\Carbon::SUNDAY);
                            $gridEnd = \Carbon\Carbon::parse($end)->endOfWeek(\Carbon\Carbon::SATURDAY);
                            $badgeClass = fn($s) => match ($s) {
                                'valid' => 'bg-success',
                                'suspect' => 'bg-warning text-dark',
                                'rejected' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp

                        @while ($cursor <= $gridEnd)
                            <tr>
                                @for ($i = 0; $i < 7; $i++)
                                    @php
                                        $dayKey = $cursor->format('Y-m-d');
                                        $inMonth = $cursor->month === \Carbon\Carbon::parse($start)->month;
                                        $mor = $calendar[$dayKey]['morning'] ?? null;
                                        $aft = $calendar[$dayKey]['afternoon'] ?? null;
                                    @endphp
                                    <td class="calendar-day-box {{ !$inMonth ? 'bg-light bg-opacity-50' : '' }}">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span
                                                class="day-number {{ $cursor->isToday() ? 'badge bg-primary text-white rounded-circle' : ($inMonth ? 'text-dark' : 'text-muted') }}"
                                                style="{{ $cursor->isToday() ? 'width:24px; height:24px; display:flex; align-items:center; justify-content:center;' : '' }}">
                                                {{ $cursor->day }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column gap-1">
                                            @if ($mor)
                                                <span class="badge-pill-custom {{ $badgeClass($mor) }}">
                                                    <span class="badge-dot bg-white"></span>
                                                    <span class="badge-text-hide">Pagi: {{ strtoupper($mor) }}</span>
                                                </span>
                                            @endif
                                            @if ($aft)
                                                <span class="badge-pill-custom {{ $badgeClass($aft) }}">
                                                    <span class="badge-dot bg-white"></span>
                                                    <span class="badge-text-hide">Malam: {{ strtoupper($aft) }}</span>
                                                </span>
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
            <div class="mt-3 d-flex flex-wrap gap-3 justify-content-center border-top pt-3">
                <small class="text-muted"><span class="badge-dot bg-success me-1"></span> Valid</small>
                <small class="text-muted"><span class="badge-dot bg-warning me-1"></span> Suspect</small>
                <small class="text-muted"><span class="badge-dot bg-danger me-1"></span> Rejected</small>
                <small class="text-muted"><span class="badge-dot bg-primary me-1"></span> Hari Ini</small>
            </div>
        </div>
    </div>

    {{-- LOG TABLE CARD --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4 border-bottom-0">
            <h6 class="fw-bold text-white mb-0"><i class="bi bi-list-ul me-2"></i>Log Aktivitas Absensi</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead>
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Sesi</th>
                            <th>Status</th>
                            <th>Lokasi / Koordinat</th>
                            <th>Foto</th>
                            <th class="text-end pe-4">Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            @php
                                $badge = match ($row->status) {
                                    'valid' => 'bg-success',
                                    'suspect' => 'bg-warning text-dark',
                                    'rejected' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $row->attendance_at->format('d M Y') }}</div>
                                    <div class="text-muted small">{{ $row->attendance_at->format('H:i') }} WIB</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border-0 px-3 py-2 rounded-pill small">
                                        {{ $row->type === 'morning' ? 'Pagi' : 'Malam' }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $badge }} px-3 py-2 rounded-pill small">{{ strtoupper($row->status) }}</span>
                                </td>
                                <td>
                                    <div class="small fw-semibold">{{ $row->latitude }}, {{ $row->longitude }}</div>
                                    <div class="text-muted truncate-text" style="max-width: 250px;">
                                        {{ $row->address_text ?? 'Alamat tidak terdeteksi' }}</div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3 btnPreview"
                                        data-photo="{{ asset('storage/' . $row->photo_path) }}">
                                        <i class="bi bi-image me-1"></i> Foto
                                    </button>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <button class="btn btn-sm btn-success text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="valid"
                                            data-type="{{ $row->type }}" data-time="{{ $row->attendance_at }}"
                                            data-current="{{ $row->status }}">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="suspect"
                                            data-type="{{ $row->type }}" data-time="{{ $row->attendance_at }}"
                                            data-current="{{ $row->status }}">
                                            <i class="bi bi-exclamation-triangle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="rejected"
                                            data-type="{{ $row->type }}" data-time="{{ $row->attendance_at }}"
                                            data-current="{{ $row->status }}">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada riwayat absensi untuk
                                    periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">
                {{ $data->links() }}
            </div>
        </div>
    </div>
@endsection
