@extends('layouts.app')

@section('title', 'Kalender Akademik')

@section('content')
    <style>
        .academic-calendar-shell {
            --calendar-accent: var(--islamic-purple-600, #6f42c1);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }

        .calendar-weekday {
            padding: 8px 4px;
            text-align: center;
            font-size: .75rem;
            font-weight: 700;
            color: var(--cui-secondary-color);
            text-transform: uppercase;
        }

        .calendar-day {
            min-height: 108px;
            border: 1px solid var(--cui-border-color);
            border-radius: 16px;
            padding: 10px;
            background: var(--cui-card-bg);
            color: var(--cui-body-color);
            text-align: left;
            transition: .18s ease;
        }

        button.calendar-day:hover:not(:disabled) {
            border-color: var(--calendar-accent);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(57, 38, 100, .12);
        }

        .calendar-day.is-empty {
            border-color: transparent;
            background: transparent;
        }

        .calendar-day.is-holiday {
            background: rgba(13, 202, 240, .10);
            border-color: rgba(13, 202, 240, .45);
        }

        .calendar-day.is-today {
            box-shadow: inset 0 0 0 2px var(--calendar-accent);
        }

        .calendar-day-number {
            font-weight: 800;
        }

        .calendar-event {
            display: block;
            margin-top: 10px;
            font-size: .74rem;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        @media (max-width: 767.98px) {
            .calendar-grid {
                gap: 4px;
            }

            .calendar-day {
                min-height: 78px;
                padding: 6px;
                border-radius: 10px;
            }

            .calendar-event {
                font-size: .62rem;
                margin-top: 5px;
            }
        }
    </style>

    <div class="container-fluid academic-calendar-shell py-3 py-md-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Kalender Akademik</h3>
                <p class="text-body-secondary mb-0">
                    Atur hari masuk dan libur untuk pencatatan Musyrif.
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary rounded-pill" id="btnSyncCalendar">
                    <i class="bi bi-arrow-repeat me-1"></i> Lengkapi Tanggal
                </button>
                <button class="btn btn-primary rounded-pill" id="btnBulkEdit" data-coreui-toggle="modal"
                    data-coreui-target="#bulkCalendarModal">
                    <i class="bi bi-calendar-range me-1"></i> Atur Rentang
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-bold small text-uppercase">Semester</label>
                        <select class="form-select rounded-3" id="semesterSelect">
                            @foreach ($semesters as $semester)
                                <option value="{{ $semester->id }}" @selected($selectedSemester?->id === $semester->id)>
                                    {{ ucfirst($semester->nama) }} ·
                                    {{ $semester->tahunAjaran?->nama ?? '-' }} ·
                                    {{ $semester->lifecycle_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-8 col-lg-4">
                        <label class="form-label fw-bold small text-uppercase">Bulan</label>
                        <input type="month" class="form-control rounded-3" id="calendarMonth"
                            value="{{ now('Asia/Jakarta')->format('Y-m') }}">
                    </div>
                    <div class="col-4 col-lg-2">
                        <button class="btn btn-outline-secondary w-100 rounded-3" id="btnToday">
                            Hari Ini
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="small text-body-secondary">Hari Masuk</div>
                        <div class="fs-3 fw-bold text-success" id="statMasuk">0</div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="small text-body-secondary">Hari Libur</div>
                        <div class="fs-3 fw-bold text-info" id="statLibur">0</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning border-0 rounded-4 d-none" id="readOnlyAlert">
            Semester ini sudah ditutup. Kalender hanya dapat dilihat.
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-2 p-md-4">
                <div class="calendar-grid mb-2">
                    @foreach (['Ahd', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $weekday)
                        <div class="calendar-weekday">{{ $weekday }}</div>
                    @endforeach
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
                <div class="text-center text-body-secondary py-5 d-none" id="calendarEmpty">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    Belum ada tanggal pada bulan ini. Klik “Lengkapi Tanggal”.
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="dayCalendarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4" id="dayCalendarForm">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Atur Tanggal</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="fw-semibold mb-3" id="dayDateLabel"></div>
                    <input type="hidden" id="dayId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-select" id="dayStatus" required>
                            <option value="masuk">Masuk</option>
                            <option value="libur">Libur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama kegiatan/libur</label>
                        <input class="form-control" id="dayEvent" maxlength="150" placeholder="Contoh: Libur nasional">
                    </div>
                    <div>
                        <label class="form-label fw-bold">Keterangan</label>
                        <textarea class="form-control" id="dayNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill" data-coreui-dismiss="modal">Batal</button>
                    <button class="btn btn-primary rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="bulkCalendarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 rounded-4" id="bulkCalendarForm">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Atur Rentang Tanggal</h5>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">Mulai</label>
                            <input type="date" class="form-control" id="bulkStart" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">Selesai</label>
                            <input type="date" class="form-control" id="bulkEnd" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Status</label>
                        <select class="form-select" id="bulkStatus" required>
                            <option value="libur">Libur</option>
                            <option value="masuk">Masuk</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama kegiatan/libur</label>
                        <input class="form-control" id="bulkEvent" maxlength="150">
                    </div>
                    <div>
                        <label class="form-label fw-bold">Keterangan</label>
                        <textarea class="form-control" id="bulkNotes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill" data-coreui-dismiss="modal">Batal</button>
                    <button class="btn btn-primary rounded-pill px-4">Terapkan</button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const semesterSelect = document.getElementById('semesterSelect');
            const monthInput = document.getElementById('calendarMonth');
            const grid = document.getElementById('calendarGrid');
            const emptyState = document.getElementById('calendarEmpty');
            const dayModal = coreui.Modal.getOrCreateInstance(
                document.getElementById('dayCalendarModal')
            );
            const bulkModal = coreui.Modal.getOrCreateInstance(
                document.getElementById('bulkCalendarModal')
            );
            let semesterMeta = null;
            let dayMap = new Map();

            const notify = (type, message) => {
                if (window.AppAlert?.[type]) return AppAlert[type](message);
                alert(message);
            };

            const errorMessage = data => data?.message ||
                Object.values(data?.errors || {}).flat()[0] ||
                'Permintaan tidak dapat diproses.';
            const escapeHtml = value => String(value ?? '').replace(
                /[&<>"']/g,
                character => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                })[character]
            );

            async function request(url, options = {}) {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        ...(options.headers || {})
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(errorMessage(data));
                return data;
            }

            function endpoint(kind, id = null) {
                const semesterId = semesterSelect.value;
                if (kind === 'days') return `{{ url('admin/kalender-akademik') }}/${semesterId}/days`;
                if (kind === 'sync') return `{{ url('admin/kalender-akademik') }}/${semesterId}/sync`;
                if (kind === 'bulk') return `{{ url('admin/kalender-akademik') }}/${semesterId}/bulk`;
                return `{{ url('admin/kalender-akademik/days') }}/${id}`;
            }

            function renderCalendar() {
                grid.innerHTML = '';
                const [year, month] = monthInput.value.split('-').map(Number);
                const first = new Date(year, month - 1, 1);
                const totalDays = new Date(year, month, 0).getDate();
                const today = new Date().toLocaleDateString('en-CA');

                for (let i = 0; i < first.getDay(); i++) {
                    const spacer = document.createElement('div');
                    spacer.className = 'calendar-day is-empty';
                    grid.appendChild(spacer);
                }

                for (let number = 1; number <= totalDays; number++) {
                    const date = `${year}-${String(month).padStart(2, '0')}-${String(number).padStart(2, '0')}`;
                    const day = dayMap.get(date);
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className =
                        `calendar-day ${day?.status === 'libur' ? 'is-holiday' : ''} ${date === today ? 'is-today' : ''}`;
                    button.disabled = !day || !semesterMeta?.editable;
                    button.innerHTML = `
                        <span class="calendar-day-number">${number}</span>
                        <span class="badge ${day?.status === 'libur' ? 'bg-info' : 'bg-success'} rounded-pill float-end">
                            ${day?.status === 'libur' ? 'Libur' : day ? 'Masuk' : '—'}
                        </span>
                        <span class="calendar-event">${escapeHtml(day?.nama_kegiatan || (day ? 'Kegiatan normal' : 'Belum dibuat'))}</span>
                    `;
                    if (day) button.addEventListener('click', () => openDay(day));
                    grid.appendChild(button);
                }

                emptyState.classList.toggle('d-none', dayMap.size > 0);
            }

            function applyEditability() {
                const editable = Boolean(semesterMeta?.editable);
                document.getElementById('btnSyncCalendar').disabled = !editable;
                document.getElementById('btnBulkEdit').disabled = !editable;
                document.getElementById('readOnlyAlert').classList.toggle('d-none', editable);
            }

            async function loadDays() {
                if (!semesterSelect.value || !monthInput.value) return;
                grid.innerHTML =
                    '<div class="text-center py-5" style="grid-column: 1 / -1;">Memuat kalender...</div>';
                try {
                    const data = await request(`${endpoint('days')}?month=${monthInput.value}`);
                    semesterMeta = data.semester;
                    dayMap = new Map(data.days.map(day => [day.tanggal, day]));
                    document.getElementById('statMasuk').textContent = data.stats.masuk;
                    document.getElementById('statLibur').textContent = data.stats.libur;
                    applyEditability();
                    renderCalendar();
                } catch (error) {
                    grid.innerHTML = '';
                    notify('error', error.message);
                }
            }

            function openDay(day) {
                document.getElementById('dayId').value = day.id;
                document.getElementById('dayDateLabel').textContent =
                    new Date(`${day.tanggal}T00:00:00`).toLocaleDateString('id-ID', {
                        weekday: 'long',
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
                document.getElementById('dayStatus').value = day.status;
                document.getElementById('dayEvent').value = day.nama_kegiatan || '';
                document.getElementById('dayNotes').value = day.keterangan || '';
                dayModal.show();
            }

            semesterSelect.addEventListener('change', () => {
                const url = new URL(window.location.href);
                url.searchParams.set('semester_id', semesterSelect.value);
                history.replaceState({}, '', url);
                loadDays();
            });
            monthInput.addEventListener('change', loadDays);
            document.getElementById('btnToday').addEventListener('click', () => {
                monthInput.value = new Date().toLocaleDateString('en-CA').slice(0, 7);
                loadDays();
            });

            document.getElementById('btnSyncCalendar').addEventListener('click', async () => {
                try {
                    const data = await request(endpoint('sync'), {
                        method: 'POST',
                        body: JSON.stringify({})
                    });
                    notify('success', data.message);
                    await loadDays();
                } catch (error) {
                    notify('error', error.message);
                }
            });

            document.getElementById('dayCalendarForm').addEventListener('submit', async event => {
                event.preventDefault();
                try {
                    const data = await request(endpoint('day', document.getElementById('dayId')
                        .value), {
                        method: 'PATCH',
                        body: JSON.stringify({
                            status: document.getElementById('dayStatus').value,
                            nama_kegiatan: document.getElementById('dayEvent').value ||
                                null,
                            keterangan: document.getElementById('dayNotes').value ||
                                null
                        })
                    });
                    dayModal.hide();
                    notify('success', data.message);
                    await loadDays();
                } catch (error) {
                    notify('error', error.message);
                }
            });

            document.getElementById('bulkCalendarForm').addEventListener('submit', async event => {
                event.preventDefault();
                try {
                    const data = await request(endpoint('bulk'), {
                        method: 'POST',
                        body: JSON.stringify({
                            tanggal_mulai: document.getElementById('bulkStart').value,
                            tanggal_selesai: document.getElementById('bulkEnd').value,
                            status: document.getElementById('bulkStatus').value,
                            nama_kegiatan: document.getElementById('bulkEvent').value ||
                                null,
                            keterangan: document.getElementById('bulkNotes').value ||
                                null
                        })
                    });
                    bulkModal.hide();
                    notify('success', data.message);
                    await loadDays();
                } catch (error) {
                    notify('error', error.message);
                }
            });

            loadDays();
        });
    </script>
@endpush
