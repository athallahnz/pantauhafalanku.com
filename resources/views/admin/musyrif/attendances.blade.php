@extends('layouts.app')

@section('title', 'Riwayat Absensi Musyrif')

@section('content')
    <style>
        /* ================= TEMA ISLAMIC PURPLE & MODERN UI ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

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

        /* ================= CALENDAR UI ================= */
        .calendar-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid var(--cui-border-color);
            border-radius: 15px;
            overflow: hidden;
        }

        .calendar-day-box {
            height: 100px;
            transition: all 0.2s ease;
            vertical-align: top !important;
            padding: 10px !important;
            border-color: var(--cui-border-color-translucent) !important;
        }

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

        /* Tambahkan di bagian <style> */
        #rotationWrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50vh;
            /* Jaga agar modal tidak menciut saat rotasi */
            transition: transform 0.3s ease-out;
        }

        #photoPreview {
            /* Magic line: Auto-rotate berdasarkan EXIF data gambar */
            image-orientation: from-image;

            display: block;
            margin: 0 auto;
            max-width: 100%;
            /* Gunakan vh (viewport height) agar tidak terlalu panjang di HP */
            max-height: 75vh;
            object-fit: contain;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        /* Penyesuaian lebar Modal agar landscape tidak terlihat sempit */
        @media (min-width: 576px) {
            #photoModal .modal-dialog {
                max-width: 800px;
                /* Lebarkan modal untuk menampung foto landscape */
            }
        }

        @media (max-width: 576px) {
            .badge-text-hide {
                display: none !important;
            }

            .calendar-day-box {
                height: 70px;
                padding: 5px !important;
            }

            .btn-back-responsive {
                width: 42px;
                height: 42px;
                padding: 0 !important;
                border-radius: 50% !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .btn-back-responsive span {
                display: none !important;
            }
        }
    </style>

    {{-- HEADER --}}
    <div class="row mb-4 align-items-center px-3 px-md-0 g-2">
        <div class="col">
            <h4 class="fw-bold text-adaptive-purple mb-1">Riwayat Absensi</h4>
            <p class="text-muted small mb-0">
                <i class="bi bi-person-circle me-1"></i> {{ $musyrif->nama }}
                <span class="mx-2">|</span>
                <i class="bi bi-hash me-1"></i> {{ $musyrif->kode ?? 'No Code' }}
            </p>
        </div>
        <div class="col-auto">
            <a href="{{ route('admin.musyrif.index') }}"
                class="btn btn-light btn-back-responsive rounded-pill px-4 fw-bold shadow-sm">
                <i class="bi bi-arrow-left me-md-1"></i> <span>Kembali</span>
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
                    <button class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm" type="submit">Filter</button>
                    <a class="btn btn-outline-secondary rounded-pill w-100 fw-bold"
                        href="{{ route('admin.musyrif.attendances', $musyrif->id) }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- KALENDER CARD --}}
    <div class="card main-card mb-4">
        <div class="card-header bg-primary py-3 px-4">
            <h6 class="fw-bold text-white mb-0">
                <i class="bi bi-calendar3 me-2"></i>Kalender -
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
                                        <span
                                            class="day-number {{ $cursor->isToday() ? 'badge bg-primary text-white rounded-circle' : '' }}">
                                            {{ $cursor->day }}
                                        </span>
                                        <div class="d-flex flex-column gap-1 mt-1">
                                            @if ($mor)
                                                <span class="badge-pill-custom {{ $badgeClass($mor) }} text-white"><span
                                                        class="badge-dot bg-white"></span><span
                                                        class="badge-text-hide">Pagi</span></span>
                                            @endif
                                            @if ($aft)
                                                <span class="badge-pill-custom {{ $badgeClass($aft) }} text-white"><span
                                                        class="badge-dot bg-white"></span><span
                                                        class="badge-text-hide">Malam</span></span>
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
    </div>

    {{-- LOG TABLE CARD --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4">
            <h6 class="fw-bold mb-0">Log Aktivitas Absensi</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="attendanceTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Sesi</th>
                            <th>Status</th>
                            <th>Lokasi</th>
                            <th>Foto</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $row->attendance_at->format('d M Y') }}</div>
                                    <div class="text-muted small">{{ $row->attendance_at->format('H:i') }} WIB</div>
                                </td>
                                <td>{{ $row->type === 'morning' ? 'Pagi' : 'Malam' }}</td>
                                <td><span
                                        class="badge {{ match ($row->status) {'valid' => 'bg-success','suspect' => 'bg-warning text-dark','rejected' => 'bg-danger',default => 'bg-secondary'} }} px-3 py-2 rounded-pill">{{ strtoupper($row->status) }}</span>
                                </td>
                                <td>
                                    <div class="small fw-semibold">{{ $row->latitude }}, {{ $row->longitude }}</div>
                                    <div class="text-muted small truncate-text" style="max-width: 200px;">
                                        {{ $row->address_text }}</div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill btnPreview"
                                        data-photo="{{ asset('storage/' . $row->photo_path) }}">
                                        <i class="bi bi-image"></i> Foto
                                    </button>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                        <button class="btn btn-sm btn-success text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="valid"
                                            data-current="{{ $row->status }}"><i class="bi bi-check-lg"></i></button>
                                        <button class="btn btn-sm btn-warning text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="suspect"
                                            data-current="{{ $row->status }}"><i
                                                class="bi bi-exclamation-triangle"></i></button>
                                        <button class="btn btn-sm btn-danger text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="rejected"
                                            data-current="{{ $row->status }}"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">Tidak ada riwayat absensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">{{ $data->links() }}</div>
        </div>
    </div>

    {{-- FORM HIDDEN (Untuk Submit Status & Reason) --}}
    <form id="statusForm" action="" method="POST" style="display: none;">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" id="statusValue">
        <input type="hidden" name="reason" id="reasonValue">
    </form>
@endsection

@push('modals')
    {{-- MODAL PREVIEW FOTO --}}
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 bg-light d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold">Preview Foto Absensi</h6>
                    <div class="d-flex gap-2">
                        {{-- Tombol Rotate --}}
                        <button type="button" class="btn btn-sm btn-outline-light rounded-pill px-3" id="btnRotate">
                            <i class="bi bi-arrow-clockwise me-1"></i> Putar Foto
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4 text-center bg-light overflow-hidden">
                    {{-- Container tambahan untuk menangani overflow saat rotasi landscape ke portrait --}}
                    <div id="rotationWrapper" style="transition: transform 0.3s ease;">
                        <img src="" id="photoPreview" class="img-fluid rounded-3 shadow-sm"
                            style="max-height: 70vh; object-fit: contain;">
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4"
                        data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // 1. Preview Foto
            let currentRotation = 0;

            // 1. Saat Modal Dibuka
            $(document).on('click', '.btnPreview', function() {
                const photoUrl = $(this).data('photo');

                // Reset rotasi ke 0 setiap kali ganti foto
                currentRotation = 0;
                $('#rotationWrapper').css('transform', 'rotate(0deg)');

                $('#photoPreview').attr('src', photoUrl);
                const myModal = new bootstrap.Modal(document.getElementById('photoModal'));
                myModal.show();
            });

            // 2. Logika Tombol Rotate
            $(document).on('click', '#btnRotate', function() {
                currentRotation -= 90; // Putar berlawanan arah jarum jam

                // Hitung orientasi: jika ganjil (90, 270), foto dalam posisi portrait/landscape terbalik
                const isVertical = (currentRotation / 90) % 2 !== 0;

                if (isVertical) {
                    // Cek jika tinggi foto setelah rotasi melebihi lebar layar
                    // Kita gunakan scale agar foto tidak 'overlap' keluar modal
                    $('#rotationWrapper').css('transform', `rotate(${currentRotation}deg) scale(0.7)`);
                } else {
                    $('#rotationWrapper').css('transform', `rotate(${currentRotation}deg) scale(1)`);
                }
            });


            $('#formAbsensi').on('submit', function() {
                const $btn = $(this).find('button[type="submit"]');

                // Cegah klik ganda
                $btn.prop('disabled', true);

                // Beri feedback visual (Spinner)
                $btn.html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Menyimpan...
                `);
            });

            // 2. Update Status (Trigger SweetAlert & Submit Form)
            $(document).on('click', '.btnUpdateStatus', function() {
                const attendanceId = $(this).data('id');
                const newStatus = $(this).data('status');
                const currentStatus = $(this).data('current');

                if (newStatus === currentStatus) {
                    Swal.fire('Info', `Status sudah ${newStatus.toUpperCase()}`, 'info');
                    return;
                }

                Swal.fire({
                    title: `Ubah ke ${newStatus.toUpperCase()}?`,
                    text: "Berikan alasan perubahan status ini:",
                    input: 'textarea',
                    inputPlaceholder: 'Contoh: Foto tidak sesuai / Lokasi diluar radius...',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#6a4ebc',
                    inputAttributes: {
                        'minlength': 5
                    },
                    inputValidator: (value) => {
                        if (!value || value.length < 5) {
                            return 'Alasan wajib diisi (min. 5 karakter)!'
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('#statusForm');

                        // replace ':attendance' sesuai parameter route di web.php
                        let url =
                            "{{ route('admin.musyrif.attendances.update_status', ':attendance') }}";
                        url = url.replace(':attendance', attendanceId);

                        form.attr('action', url);
                        $('#statusValue').val(newStatus);
                        $('#reasonValue').val(result.value);

                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
