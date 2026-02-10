@extends('layouts.app')

@section('title', 'Riwayat Absensi Musyrif')

@section('content')
    <style>
        /* ===== Badge mode ===== */
        .badge-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        /* Desktop: tampil teks */
        .badge-text {
            display: inline-block;
        }

        /* Mobile: sembunyikan teks, tampil dot */
        @media (max-width: 576px) {
            .badge-text {
                display: none !important;
            }

            .badge-dot {
                display: inline-block !important;
            }
        }
    </style>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h4 class="mb-1">Riwayat Absensi</h4>
                <div class="text-muted">
                    Musyrif: <span class="fw-semibold">{{ $musyrif->nama }}</span>
                    @if ($musyrif->user)
                        <span class="mx-2">•</span>
                        <span class="small">{{ $musyrif->user->email }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.musyrif.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-bold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FILTERS --}}
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form class="row g-2 align-items-end" method="GET">
                    <div class="col-md-3">
                        <label class="form-label">Bulan</label>
                        <input type="month" name="month" class="form-control"
                            value="{{ request('month', now()->format('Y-m')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sesi</label>
                        <select name="type" class="form-select">
                            <option value="">Semua</option>
                            <option value="morning" @selected(request('type') === 'morning')>Pagi</option>
                            <option value="afternoon" @selected(request('type') === 'afternoon')>Malam</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            <option value="valid" @selected(request('status') === 'valid')>Valid</option>
                            <option value="suspect" @selected(request('status') === 'suspect')>Suspect</option>
                            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">Filter</button>
                        <a class="btn btn-outline-secondary w-100"
                            href="{{ route('admin.musyrif.attendances', $musyrif->id) }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        @php
            $daysOfWeek = ['Ahd', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

            $badgeClass = function ($status) {
                return match ($status) {
                    'valid' => 'bg-success',
                    'suspect' => 'bg-warning text-dark',
                    'rejected' => 'bg-danger',
                    default => 'bg-secondary',
                };
            };

            $gridStart = \Carbon\Carbon::parse($start)->startOfWeek(\Carbon\Carbon::SUNDAY);
            $gridEnd = \Carbon\Carbon::parse($end)->endOfWeek(\Carbon\Carbon::SATURDAY);
        @endphp
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Kalender Absensi
                        ({{ \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y') }})</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0"">
                        <thead class="table-light">
                            <tr>
                                @foreach ($daysOfWeek as $d)
                                    <th class="text-center small">{{ $d }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $cursor = $gridStart->copy(); @endphp

                            @while ($cursor <= $gridEnd)
                                <tr>
                                    @for ($i = 0; $i < 7; $i++)
                                        @php
                                            $dayKey = $cursor->format('Y-m-d');
                                            $inMonth = $cursor->month === \Carbon\Carbon::parse($start)->month;

                                            $mor = $calendar[$dayKey]['morning'] ?? null;
                                            $aft = $calendar[$dayKey]['afternoon'] ?? null;
                                        @endphp

                                        <td class="p-2" style="height: 92px;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="{{ $inMonth ? 'fw-semibold' : 'text-muted' }}">
                                                    {{ $cursor->day }}
                                                </div>

                                                {{-- highlight today --}}
                                                @if ($cursor->isToday())
                                                    <span class="badge bg-primary badge-text">Hari ini</span>
                                                    <span class="badge-dot bg-primary d-none"></span>
                                                @endif
                                            </div>

                                            <div class="mt-2 d-flex flex-column gap-1">
                                                {{-- Pagi --}}
                                                @if ($mor)
                                                    <div class="d-flex align-items-center gap-1">
                                                        {{-- mobile: dot --}}
                                                        <span class="badge-dot {{ $badgeClass($mor) }} d-none"></span>

                                                        {{-- desktop: text --}}
                                                        <span class="badge {{ $badgeClass($mor) }} badge-text">
                                                            Pagi: {{ strtoupper($mor) }}
                                                        </span>
                                                    </div>
                                                @endif

                                                {{-- Malam --}}
                                                @if ($aft)
                                                    <div class="d-flex align-items-center gap-1">
                                                        {{-- mobile: dot --}}
                                                        <span class="badge-dot {{ $badgeClass($aft) }} d-none"></span>

                                                        {{-- desktop: text --}}
                                                        <span class="badge {{ $badgeClass($aft) }} badge-text">
                                                            Malam: {{ strtoupper($aft) }}
                                                        </span>
                                                    </div>
                                                @endif

                                                {{-- Kosong --}}
                                                @if (!$mor && !$aft)
                                                    <span class="text-muted small badge-text">—</span>
                                                    <span class="badge-dot bg-light border d-none"></span>
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

        {{-- TABLE --}}
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:140px;">Waktu</th>
                            <th style="width:110px;">Sesi</th>
                            <th style="width:160px;">Status</th>
                            <th>Lokasi</th>
                            <th style="width:90px;">Foto</th>
                            <th style="width:220px;">Aksi</th>
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
                                $typeLabel = $row->type === 'morning' ? 'Morning' : 'Afternoon';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row->attendance_at->format('d/m/Y') }}</div>
                                    <div class="text-muted small">{{ $row->attendance_at->format('H:i') }} WIB</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $typeLabel }}</span>
                                </td>
                                <td>
                                    <span class="badge {{ $badge }}">{{ strtoupper($row->status) }}</span>
                                    @if ($row->notes)
                                        <div class="text-muted small mt-1"
                                            style="max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $row->notes }}
                                        </div>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($row->latitude && $row->longitude)
                                        <div>{{ $row->latitude }}, {{ $row->longitude }}</div>
                                        <div class="text-muted">Acc: {{ $row->accuracy ?? '-' }} m</div>
                                        @if ($row->address_text)
                                            <div class="text-muted"
                                                style="max-width:360px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                                {{ $row->address_text }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php $url = asset('storage/'.$row->photo_path); @endphp
                                    <button type="button" class="btn btn-sm btn-outline-secondary btnPreview"
                                        data-photo="{{ $url }}">
                                        Lihat
                                    </button>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button type="button" class="btn btn-sm btn-success text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="valid"
                                            data-type="{{ $typeLabel }}"
                                            data-time="{{ $row->attendance_at->format('d/m/Y H:i') }}"
                                            data-current="{{ $row->status }}">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="suspect"
                                            data-type="{{ $typeLabel }}"
                                            data-time="{{ $row->attendance_at->format('d/m/Y H:i') }}"
                                            data-current="{{ $row->status }}">
                                            Suspect
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger text-white btnUpdateStatus"
                                            data-id="{{ $row->id }}" data-status="rejected"
                                            data-type="{{ $typeLabel }}"
                                            data-time="{{ $row->attendance_at->format('d/m/Y H:i') }}"
                                            data-current="{{ $row->status }}">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $data->links() }}
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- MODAL: Preview Photo --}}
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Foto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <img id="photoModalImg" src="" alt="Foto absensi" class="img-fluid rounded border">
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL: Update Status --}}
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" id="statusForm">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header">
                        <h5 class="modal-title">Update Status Absensi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body">
                        <div class="border rounded p-3 bg-light mb-3">
                            <div class="small text-muted">Sesi</div>
                            <div class="fw-semibold" id="mType">-</div>
                            <div class="small text-muted mt-2">Waktu</div>
                            <div class="fw-semibold" id="mTime">-</div>
                            <div class="small text-muted mt-2">Status saat ini</div>
                            <div class="fw-semibold" id="mCurrent">-</div>
                        </div>

                        <input type="hidden" name="status" id="statusInput">

                        <div class="mb-2">
                            <label class="form-label fw-semibold">Alasan (wajib)</label>
                            <textarea name="reason" id="reasonInput" class="form-control" rows="4" minlength="5" maxlength="500"
                                required placeholder="Contoh: GPS melenceng karena akurasi buruk, selfie sesuai, di-approve."></textarea>
                            <div class="small text-muted mt-1">Minimal 5 karakter.</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            /* ========================================
               PREVIEW PHOTO MODAL
            ======================================== */

            const photoModalEl = document.getElementById('photoModal');
            const photoModalImg = document.getElementById('photoModalImg');

            let photoModal = null;

            if (photoModalEl) {
                photoModal = new bootstrap.Modal(photoModalEl);
            }

            document.querySelectorAll('.btnPreview').forEach(btn => {

                btn.addEventListener('click', function() {

                    if (!photoModal) return;

                    const photoUrl = this.dataset.photo || '';

                    photoModalImg.src = photoUrl;

                    photoModal.show();

                });

            });


            /* ========================================
               UPDATE STATUS MODAL
            ======================================== */

            const statusModalEl = document.getElementById('statusModal');

            let statusModal = null;

            if (statusModalEl) {
                statusModal = new bootstrap.Modal(statusModalEl);
            }

            const statusForm = document.getElementById('statusForm');
            const statusInput = document.getElementById('statusInput');
            const reasonInput = document.getElementById('reasonInput');

            const mType = document.getElementById('mType');
            const mTime = document.getElementById('mTime');
            const mCurrent = document.getElementById('mCurrent');


            document.querySelectorAll('.btnUpdateStatus').forEach(btn => {

                btn.addEventListener('click', function() {

                    if (!statusModal) return;

                    const id = this.dataset.id;
                    const nextStatus = this.dataset.status;
                    const type = this.dataset.type || '-';
                    const time = this.dataset.time || '-';
                    const current = this.dataset.current || '-';


                    /* set form action */
                    statusForm.action =
                        "{{ route('admin.musyrif.attendances.update_status', 'ATT_ID') }}"
                        .replace('ATT_ID', id);


                    /* set hidden status */
                    statusInput.value = nextStatus;


                    /* set modal content */
                    mType.textContent = type;
                    mTime.textContent = time + ' WIB';
                    mCurrent.textContent = current.toUpperCase();


                    /* reset reason */
                    reasonInput.value = '';


                    /* show modal */
                    statusModal.show();

                });

            });


        });
    </script>
@endpush
