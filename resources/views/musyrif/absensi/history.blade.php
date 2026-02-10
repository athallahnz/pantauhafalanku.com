@extends('layouts.app')

@section('title', 'Riwayat Absensi')

@section('content')
    <style>
        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .calendar td {
            height: 80px;
            vertical-align: top;
            padding: 6px;
            position: relative;
        }

        .calendar .day-number {
            font-size: 14px;
            font-weight: 600;
        }

        .calendar .outside {
            color: #bbb;
        }

        /* TODAY highlight */
        .calendar .today {
            background: rgba(13, 110, 253, 0.08);
            border-radius: 8px;
        }

        /* Desktop badge */
        .today-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            font-size: 10px;
        }

        /* Dot version (hidden by default) */
        .today-dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: none;
        }

        /* MOBILE MODE */
        @media (max-width: 576px) {

            /* sembunyikan badge text */
            .today-badge {
                display: none;
            }

            /* tampilkan dot */
            .today-dot {
                display: block;
            }

            /* perkecil cell */
            .calendar td {
                height: 65px;
                padding: 4px;
            }

            .calendar .day-number {
                font-size: 12px;
            }
        }
    </style>

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h4 class="mb-0">Riwayat Absensi</h4>

            <a href="{{ route('musyrif.absensi.index') }}" class="btn btn-outline-secondary">
                Kembali
            </a>

        </div>

        {{-- FILTER BULAN --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">

                <form method="GET" class="row g-2 align-items-end">

                    <div class="col-md-4">

                        <label class="form-label">Bulan</label>

                        <input type="month" name="month" value="{{ request('month', $month) }}" class="form-control">

                    </div>

                    <div class="col-md-4">

                        <button class="btn btn-primary">
                            Tampilkan
                        </button>

                        <a href="{{ route('musyrif.absensi.history') }}" class="btn btn-outline-secondary">
                            Reset
                        </a>

                    </div>

                </form>

            </div>
        </div>

        {{-- ===================== CALENDAR ====================== --}}

        @php
            $today = now()->format('Y-m-d');

            $daysOfWeek = ['Ahd', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

            $badgeClass = fn($status) => match ($status) {
                'valid' => 'bg-success',
                'suspect' => 'bg-warning',
                'rejected' => 'bg-danger',
                default => 'bg-secondary',
            };

            $gridStart = \Carbon\Carbon::parse($start)->startOfWeek(\Carbon\Carbon::SUNDAY);
            $gridEnd = \Carbon\Carbon::parse($end)->endOfWeek(\Carbon\Carbon::SATURDAY);
        @endphp

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="fw-semibold mb-2">
                    Kalender Absensi ({{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }})
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered calendar">
                        <thead class="table-light">
                            <tr>
                                @foreach ($daysOfWeek as $d)
                                    <th class="text-center small">
                                        {{ $d }}
                                    </th>
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
                                            $isToday = $dateString === $today;
                                            $inMonth = $cursor->month == \Carbon\Carbon::parse($start)->month;
                                            $mor = $calendar[$dateString]['morning'] ?? null;
                                            $aft = $calendar[$dateString]['afternoon'] ?? null;
                                        @endphp
                                        <td class="{{ $isToday ? 'today' : '' }}">

                                            <div class="day-number {{ $inMonth ? '' : 'outside' }}">
                                                {{ $cursor->day }}
                                            </div>

                                            @if ($isToday)
                                                <span class="badge bg-primary today-badge">Hari ini</span>
                                                <span class="today-dot bg-primary"></span>
                                            @endif

                                            <div class="mt-1 small">

                                                @if ($mor)
                                                    <span class="badge-dot {{ $badgeClass($mor) }}"></span>
                                                @endif

                                                @if ($aft)
                                                    <span class="badge-dot {{ $badgeClass($aft) }}"></span>
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
                <div class="small text-muted mt-2">
                    Legend:
                    <span class="badge bg-primary">TODAY</span>
                    <span class="badge bg-success">VALID</span>
                    <span class="badge bg-warning text-dark">SUSPECT</span>
                    <span class="badge bg-danger">REJECT</span>
                </div>
                <div class="small text-muted mt-2">
                    Catatan: Kalender menampilkan status terakhir per hari untuk sesi Pagi/Malam sesuai filter.
                </div>
            </div>
        </div>


        {{-- ======================    TABLE HISTORY    ====================== --}}

        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Jenis</th>
                            <th>Lokasi</th>
                            <th>Foto</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            @php
                                $badgeClass = match ($row->status) {
                                    'valid' => 'bg-success',
                                    'suspect' => 'bg-warning text-dark',
                                    'rejected' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td>
                                    {{ $row->attendance_at->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span
                                        class="badge
                                    {{ $row->type == 'morning' ? 'bg-success' : 'bg-primary' }}">

                                        {{ $row->type == 'morning' ? 'Pagi' : 'Malam' }}

                                    </span>
                                </td>
                                <td class="small">
                                    {{ $row->latitude }},
                                    {{ $row->longitude }}

                                    <div class="text-muted">
                                        Acc: {{ $row->accuracy }} m
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ asset('storage/' . $row->photo_path) }}" target="_blank">

                                        <img src="{{ asset('storage/' . $row->photo_path) }}" class="rounded border"
                                            style="height:52px;">

                                    </a>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }}">
                                        {{ strtoupper($row->status) }}
                                    </span>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">

                                    Belum ada data.

                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $data->links() }}
            </div>
        </div>
    </div>
@endsection
