@extends('layouts.app')

@section('title', 'Alumni & Santri Nonaktif')

@section('content')
    <style>
        .archive-header {
            border-radius: 1.25rem;
            background: radial-gradient(circle at top right, rgba(255, 255, 255, .22), transparent 38%),
                linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
        }

        .archive-stat,
        .archive-panel {
            border: 1px solid var(--cui-border-color);
            border-radius: 1rem;
            background: var(--cui-body-bg);
        }

        .archive-stat {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .archive-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, .08);
        }

        .archive-icon {
            width: 44px;
            height: 44px;
            border-radius: .9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .archive-reason {
            max-width: 250px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .archive-timeline {
            position: relative;
            padding-left: 1.4rem;
        }

        .archive-timeline::before {
            content: '';
            position: absolute;
            left: .35rem;
            top: .45rem;
            bottom: .45rem;
            width: 2px;
            background: var(--cui-border-color);
        }

        .archive-timeline-item {
            position: relative;
            padding-bottom: 1rem;
        }

        .archive-timeline-item::before {
            content: '';
            position: absolute;
            left: -1.35rem;
            top: .35rem;
            width: .75rem;
            height: .75rem;
            border-radius: 50%;
            background: var(--cui-primary);
            box-shadow: 0 0 0 4px var(--cui-body-bg);
        }

        .archive-detail-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--cui-secondary-color);
        }

        .archive-guide {
            position: fixed;
            right: 1.5rem;
            bottom: 1.5rem;
            z-index: 1040;
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 .65rem 1.4rem rgba(0, 0, 0, .2);
        }

        [data-coreui-theme="dark"] .table-light {
            --cui-table-bg: var(--cui-tertiary-bg);
            --cui-table-color: var(--cui-body-color);
        }
    </style>

    <section class="archive-header p-4 p-lg-5 mb-4 shadow-sm">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="small text-white-50 mb-2">Data Santri / Arsip</div>
                <h2 class="fw-bold mb-2">Alumni & Santri Nonaktif</h2>
                <p class="mb-0">
                    Kelola alumni, santri keluar, santri nonaktif, histori status, dan reaktivasi tanpa menghapus progress lama.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-light fw-semibold" data-bs-toggle="modal"
                    data-bs-target="#deactivateModal">
                    <i class="bi bi-person-dash-fill me-1"></i> Arsipkan Santri Aktif
                </button>

                <button type="button" class="btn btn-outline-light fw-semibold no-loader" id="btnExportArchive">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                </button>
            </div>
        </div>
    </section>

    @php
        $stats = [
            ['id' => 'statTotal', 'label' => 'Total Arsip', 'icon' => 'bi-archive-fill', 'class' => 'bg-primary-subtle text-primary'],
            ['id' => 'statLulus', 'label' => 'Alumni / Lulus', 'icon' => 'bi-mortarboard-fill', 'class' => 'bg-success-subtle text-success'],
            ['id' => 'statKeluar', 'label' => 'Santri Keluar', 'icon' => 'bi-box-arrow-right', 'class' => 'bg-danger-subtle text-danger'],
            ['id' => 'statNonaktif', 'label' => 'Santri Nonaktif', 'icon' => 'bi-pause-circle-fill', 'class' => 'bg-secondary-subtle text-secondary'],
            ['id' => 'statRecent', 'label' => 'Perubahan 30 Hari', 'icon' => 'bi-clock-history', 'class' => 'bg-warning-subtle text-warning'],
        ];
    @endphp

    <div class="row g-3 mb-4">
        @foreach ($stats as $stat)
            <div class="col-6 col-lg">
                <div class="archive-stat p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="archive-icon {{ $stat['class'] }}">
                            <i class="bi {{ $stat['icon'] }}"></i>
                        </div>
                        <div>
                            <div class="small text-body-secondary">{{ $stat['label'] }}</div>
                            <div class="fs-4 fw-bold" id="{{ $stat['id'] }}">0</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <section class="archive-panel p-3 p-lg-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold mb-1">Filter Arsip</h5>
                <div class="small text-body-secondary">Statistik mengikuti kelas, semester, dan rentang tanggal.</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilter">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="lulus">Lulus</option>
                    <option value="keluar">Keluar</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>

            <div class="col-md-6 col-xl-3">
                <label class="form-label">Kelas Terakhir</label>
                <select class="form-select" id="filterKelas">
                    <option value="">Semua Kelas</option>
                    @foreach ($kelasList as $kelas)
                        <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 col-xl-3">
                <label class="form-label">Semester Kelulusan</label>
                <select class="form-select" id="filterSemester">
                    <option value="">Semua Semester</option>
                    @foreach ($semesterList as $semester)
                        <option value="{{ $semester->id }}">
                            {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $semester->nama)) }} —
                            {{ $semester->tahunAjaran?->nama ?? '-' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6 col-xl-2">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" class="form-control" id="filterDateStart">
            </div>

            <div class="col-md-6 col-xl-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" id="filterDateEnd">
            </div>
        </div>
    </section>

    <section class="archive-panel p-3 p-lg-4">
        <div class="mb-3">
            <h5 class="fw-bold mb-1">Daftar Alumni & Santri Nonaktif</h5>
            <div class="small text-body-secondary">Progress Hafalan, Tahsin, dan Tilawah tetap dipertahankan.</div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="archiveTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:55px">#</th>
                        <th>Santri</th>
                        <th>Status</th>
                        <th>Kelas Terakhir</th>
                        <th>Semester Kelulusan</th>
                        <th>Tanggal Status</th>
                        <th>Alasan / Catatan</th>
                        <th>Progress</th>
                        <th style="width:165px">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </section>

    <button type="button" class="btn btn-primary archive-guide" data-bs-toggle="modal" data-bs-target="#guideModal"
        title="Petunjuk halaman">
        <i class="bi bi-info-lg fs-5"></i>
    </button>
@endsection

@push('modals')
    <div class="modal fade" id="detailArchiveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Detail Arsip Santri</h5>
                        <div class="small text-body-secondary" id="detailArchiveSubtitle">-</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4" id="detailArchiveProfile"></div>
                    <div class="row g-3 mb-4" id="detailArchiveProgress"></div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Histori Perubahan Status</h6>
                        <a href="#" class="btn btn-sm btn-outline-success" id="detailProgressLink">
                            <i class="bi bi-graph-up-arrow me-1"></i> Progress Lengkap
                        </a>
                    </div>
                    <div class="archive-timeline" id="detailArchiveTimeline"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="deactivateForm">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Arsipkan Santri Aktif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        Untuk santri keluar atau sementara nonaktif. Kelulusan normal tetap melalui Migrasi Semester.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Santri Aktif</label>
                        <select class="form-select" id="deactivateSantriId" required>
                            <option value="">Pilih santri...</option>
                            @foreach ($activeSantriList as $activeSantri)
                                <option value="{{ $activeSantri->id }}">
                                    {{ $activeSantri->nama }} — {{ $activeSantri->nis ?: 'Tanpa NIS' }} —
                                    {{ $activeSantri->kelas?->nama_kelas ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status Arsip</label>
                        <select class="form-select" id="deactivateStatus" required>
                            <option value="keluar">Keluar</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Perubahan</label>
                        <input type="datetime-local" class="form-control" id="deactivateChangedAt" required>
                    </div>

                    <div>
                        <label class="form-label">Alasan</label>
                        <textarea class="form-control" id="deactivateReason" rows="4" maxlength="2000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-archive-fill me-1"></i> Simpan ke Arsip
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="editArchiveStatusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="editArchiveStatusForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Koreksi Status Arsip</h5>
                        <div class="small text-body-secondary" id="editArchiveName">-</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editArchiveSantriId">

                    <div class="mb-3">
                        <label class="form-label">Status Baru</label>
                        <select class="form-select" id="editArchiveStatus" required>
                            <option value="">Pilih status...</option>
                            <option value="lulus">Lulus</option>
                            <option value="keluar">Keluar</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="editArchiveSemesterBox">
                        <label class="form-label">Semester Kelulusan</label>
                        <select class="form-select" id="editArchiveSemester">
                            <option value="">Pilih semester...</option>
                            @foreach ($semesterList as $semester)
                                <option value="{{ $semester->id }}">
                                    {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $semester->nama)) }} —
                                    {{ $semester->tahunAjaran?->nama ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Perubahan</label>
                        <input type="datetime-local" class="form-control" id="editArchiveChangedAt" required>
                    </div>

                    <div>
                        <label class="form-label">Alasan Koreksi</label>
                        <textarea class="form-control" id="editArchiveReason" rows="4" maxlength="2000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Simpan Koreksi</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="reactivateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="reactivateForm">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Aktifkan Kembali Santri</h5>
                        <div class="small text-body-secondary" id="reactivateName">-</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reactivateSantriId">
                    <div class="alert alert-info small">
                        Santri akan kembali ke Data Master aktif dan wajib memperoleh kelas serta musyrif.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kelas</label>
                        <select class="form-select" id="reactivateKelas" required>
                            <option value="">Pilih kelas...</option>
                            @foreach ($kelasList as $kelas)
                                <option value="{{ $kelas->id }}">{{ $kelas->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Musyrif</label>
                        <select class="form-select" id="reactivateMusyrif" disabled required>
                            <option value="">Pilih kelas terlebih dahulu...</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Reaktivasi</label>
                        <input type="datetime-local" class="form-control" id="reactivateChangedAt" required>
                    </div>

                    <div>
                        <label class="form-label">Alasan Reaktivasi</label>
                        <textarea class="form-control" id="reactivateReason" rows="4" maxlength="2000" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Aktifkan Kembali
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="guideModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Petunjuk Alumni & Santri Nonaktif</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-3">
                        <div><strong>Alumni:</strong> kelulusan normal dibuat melalui Migrasi Semester.</div>
                        <div><strong>Keluar/Nonaktif:</strong> gunakan tombol Arsipkan Santri Aktif dan tulis alasan.</div>
                        <div><strong>Koreksi:</strong> setiap perubahan membuat histori baru, bukan menimpa histori lama.</div>
                        <div><strong>Reaktivasi:</strong> wajib memilih kelas dan musyrif yang sesuai.</div>
                        <div><strong>Progress:</strong> seluruh Hafalan, Tahsin, dan Tilawah tetap tersedia.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(function() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const endpoints = {
                data: @json(route('admin.santri.archive.data')),
                statistics: @json(route('admin.santri.archive.statistics')),
                export: @json(route('admin.santri.archive.export')),
                show: @json(route('admin.santri.archive.show', ['santri' => '__ID__'])),
                deactivate: @json(route('admin.santri.archive.deactivate', ['santri' => '__ID__'])),
                updateStatus: @json(route('admin.santri.archive.update-status', ['santri' => '__ID__'])),
                reactivate: @json(route('admin.santri.archive.reactivate', ['santri' => '__ID__'])),
                progress: @json(route('admin.santri.master.progress.show', ['santri' => '__ID__'])),
                musyrifByKelas: @json(route('admin.musyrif.by_kelas', ['kelas_id' => '__ID__'])),
            };

            const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('detailArchiveModal'));
            const editModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('editArchiveStatusModal'));
            const reactivateModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('reactivateModal'));
            const deactivateModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deactivateModal'));

            const endpoint = (template, id) => template.replace('__ID__', encodeURIComponent(id));

            function nowLocalValue() {
                const date = new Date();
                return new Date(date.getTime() - (date.getTimezoneOffset() * 60000)).toISOString().slice(0, 16);
            }

            function filterPayload() {
                return {
                    status: $('#filterStatus').val(),
                    kelas_id: $('#filterKelas').val(),
                    graduated_semester_id: $('#filterSemester').val(),
                    date_start: $('#filterDateStart').val(),
                    date_end: $('#filterDateEnd').val(),
                };
            }

            async function requestJson(url, options = {}) {
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(options.headers || {}),
                    },
                    ...options,
                });

                const json = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validationMessage = json?.errors
                        ? Object.values(json.errors).flat()[0]
                        : null;
                    throw new Error(validationMessage || json?.message || 'Terjadi kesalahan sistem.');
                }

                return json;
            }

            const escapeHtml = value => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            function formatDateTime(value) {
                if (!value) return '-';
                const date = new Date(value);
                return Number.isNaN(date.getTime())
                    ? value
                    : new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
            }

            const showError = error => Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: error?.message || 'Terjadi kesalahan sistem.',
            });

            const table = $('#archiveTable').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[5, 'desc']],
                ajax: {
                    url: endpoints.data,
                    data: data => Object.assign(data, filterPayload()),
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'identity', name: 'identity' },
                    { data: 'status_badge', name: 'status' },
                    { data: 'last_class', name: 'last_class', orderable: false },
                    { data: 'graduation_period', name: 'graduation_period', orderable: false },
                    { data: 'status_date', name: 'status_date' },
                    { data: 'reason', name: 'reason', orderable: false },
                    { data: 'progress_summary', orderable: false, searchable: false },
                    { data: 'action', orderable: false, searchable: false },
                ],
                language: {
                    search: 'Cari:',
                    lengthMenu: 'Tampilkan _MENU_ data',
                    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
                    infoEmpty: 'Belum ada data arsip',
                    zeroRecords: 'Data arsip tidak ditemukan',
                    processing: 'Memuat data...',
                    paginate: { next: 'Berikutnya', previous: 'Sebelumnya' },
                },
            });

            async function loadStatistics() {
                try {
                    const query = new URLSearchParams(filterPayload());
                    const stats = await requestJson(`${endpoints.statistics}?${query}`);
                    $('#statTotal').text(Number(stats.total ?? 0).toLocaleString('id-ID'));
                    $('#statLulus').text(Number(stats.lulus ?? 0).toLocaleString('id-ID'));
                    $('#statKeluar').text(Number(stats.keluar ?? 0).toLocaleString('id-ID'));
                    $('#statNonaktif').text(Number(stats.nonaktif ?? 0).toLocaleString('id-ID'));
                    $('#statRecent').text(Number(stats.recent ?? 0).toLocaleString('id-ID'));
                } catch (error) {
                    showError(error);
                }
            }

            function reloadData() {
                table.ajax.reload(null, false);
                loadStatistics();
            }

            $('#filterStatus, #filterKelas, #filterSemester, #filterDateStart, #filterDateEnd')
                .on('change', reloadData);

            $('#btnResetFilter').on('click', function() {
                $('#filterStatus, #filterKelas, #filterSemester, #filterDateStart, #filterDateEnd').val('');
                reloadData();
            });

            $('#btnExportArchive').on('click', function() {
                window.location.href = `${endpoints.export}?${new URLSearchParams(filterPayload())}`;
            });

            async function loadDetail(id) {
                const json = await requestJson(endpoint(endpoints.show, id));
                const santri = json.santri;
                const histories = Array.isArray(json.histories) ? json.histories : [];

                $('#detailArchiveSubtitle').text(`${santri.nama} • ${santri.nis || 'Tanpa NIS'}`);

                const profile = [
                    ['Status', santri.status_label],
                    ['Kelas Terakhir', santri.kelas_nama || '-'],
                    ['Semester Kelulusan', santri.graduated_semester_label || '-'],
                    ['Tanggal Status', formatDateTime(santri.graduated_at || santri.status_changed_at)],
                    ['Alasan', santri.status_reason || '-'],
                    ['Diubah Oleh', santri.status_changed_by || '-'],
                    ['Akun', santri.user ? `${santri.user.name} • ${santri.user.nomor || santri.user.email || '-'}` : 'Tidak memiliki akun'],
                    ['Tanggal Lahir', santri.tanggal_lahir || '-'],
                ];

                $('#detailArchiveProfile').html(profile.map(([label, value]) => `
                    <div class="col-md-6 col-xl-3">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="archive-detail-label">${escapeHtml(label)}</div>
                            <div class="fw-semibold">${escapeHtml(value)}</div>
                        </div>
                    </div>
                `).join(''));

                const progress = [
                    ['Hafalan', santri.progress?.hafalan ?? 0, 'primary'],
                    ['Tahsin', santri.progress?.tahsin ?? 0, 'success'],
                    ['Tilawah', santri.progress?.tilawah ?? 0, 'info'],
                ];

                $('#detailArchiveProgress').html(progress.map(([label, value, color]) => `
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3">
                            <div class="small text-body-secondary">Total Record ${escapeHtml(label)}</div>
                            <div class="fs-4 fw-bold text-${color}">${Number(value).toLocaleString('id-ID')}</div>
                        </div>
                    </div>
                `).join(''));

                $('#detailProgressLink').attr('href', endpoint(endpoints.progress, santri.id));
                $('#detailArchiveTimeline').html(histories.length ? histories.map(history => `
                    <div class="archive-timeline-item">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="fw-bold">
                                ${escapeHtml(history.from_status_label)}
                                <i class="bi bi-arrow-right mx-1"></i>
                                ${escapeHtml(history.to_status_label)}
                            </div>
                            <div class="small text-body-secondary">${escapeHtml(formatDateTime(history.changed_at))}</div>
                        </div>
                        <div class="small text-body-secondary mt-1">
                            Kelas: ${escapeHtml(history.kelas || '-')} • Musyrif: ${escapeHtml(history.musyrif || '-')} • Semester: ${escapeHtml(history.semester || '-')}
                        </div>
                        <div class="mt-2">${escapeHtml(history.reason || 'Tidak ada catatan')}</div>
                        <div class="small text-body-secondary mt-1">Oleh: ${escapeHtml(history.changed_by || 'Sistem / data lama')}</div>
                    </div>
                `).join('') : '<div class="text-body-secondary">Belum ada histori status.</div>');

                return json;
            }

            $('#archiveTable').on('click', '.btn-detail-archive', async function() {
                try {
                    await loadDetail(this.dataset.id);
                    detailModal.show();
                } catch (error) {
                    showError(error);
                }
            });

            $('#archiveTable').on('click', '.btn-edit-archive-status', async function() {
                try {
                    const { santri } = await loadDetail(this.dataset.id);
                    $('#editArchiveSantriId').val(santri.id);
                    $('#editArchiveName').text(`${santri.nama} • Status saat ini: ${santri.status_label}`);
                    $('#editArchiveStatus, #editArchiveSemester').val('');
                    $('#editArchiveReason').val('');
                    $('#editArchiveChangedAt').val(nowLocalValue());
                    $('#editArchiveSemesterBox').addClass('d-none');
                    editModal.show();
                } catch (error) {
                    showError(error);
                }
            });

            $('#editArchiveStatus').on('change', function() {
                const isGraduated = this.value === 'lulus';
                $('#editArchiveSemesterBox').toggleClass('d-none', !isGraduated);
                $('#editArchiveSemester').prop('required', isGraduated);
            });

            $('#editArchiveStatusForm').on('submit', async function(event) {
                event.preventDefault();
                const id = $('#editArchiveSantriId').val();

                try {
                    const confirm = await Swal.fire({
                        icon: 'warning',
                        title: 'Simpan koreksi status?',
                        text: 'Perubahan akan tercatat dalam histori status.',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal',
                    });
                    if (!confirm.isConfirmed) return;

                    const json = await requestJson(endpoint(endpoints.updateStatus, id), {
                        method: 'PATCH',
                        body: JSON.stringify({
                            status: $('#editArchiveStatus').val(),
                            graduated_semester_id: $('#editArchiveSemester').val() || null,
                            reason: $('#editArchiveReason').val(),
                            changed_at: $('#editArchiveChangedAt').val(),
                        }),
                    });

                    editModal.hide();
                    await Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message });
                    reloadData();
                } catch (error) {
                    showError(error);
                }
            });

            async function loadMusyrifs(kelasId) {
                const select = $('#reactivateMusyrif');
                select.prop('disabled', true).html('<option value="">Memuat musyrif...</option>');

                if (!kelasId) {
                    select.html('<option value="">Pilih kelas terlebih dahulu...</option>');
                    return;
                }

                try {
                    const json = await requestJson(endpoint(endpoints.musyrifByKelas, kelasId));
                    const musyrifs = Array.isArray(json.data) ? json.data : [];
                    select.html('<option value="">Pilih musyrif...</option>');
                    musyrifs.forEach(musyrif => select.append(new Option(
                        musyrif.kode ? `${musyrif.nama} — ${musyrif.kode}` : musyrif.nama,
                        musyrif.id
                    )));
                    select.prop('disabled', false);
                } catch (error) {
                    select.html('<option value="">Gagal memuat musyrif</option>');
                    showError(error);
                }
            }

            $('#archiveTable').on('click', '.btn-reactivate-santri', async function() {
                try {
                    const { santri } = await loadDetail(this.dataset.id);
                    $('#reactivateSantriId').val(santri.id);
                    $('#reactivateName').text(`${santri.nama} • ${santri.status_label}`);
                    $('#reactivateKelas').val(santri.kelas_id || '');
                    $('#reactivateChangedAt').val(nowLocalValue());
                    $('#reactivateReason').val('');
                    reactivateModal.show();
                    await loadMusyrifs(santri.kelas_id || '');
                } catch (error) {
                    showError(error);
                }
            });

            $('#reactivateKelas').on('change', function() {
                loadMusyrifs(this.value);
            });

            $('#reactivateForm').on('submit', async function(event) {
                event.preventDefault();
                const id = $('#reactivateSantriId').val();

                try {
                    const confirm = await Swal.fire({
                        icon: 'question',
                        title: 'Aktifkan kembali santri?',
                        text: 'Santri akan kembali ke Data Master aktif.',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Aktifkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#198754',
                    });
                    if (!confirm.isConfirmed) return;

                    const json = await requestJson(endpoint(endpoints.reactivate, id), {
                        method: 'PATCH',
                        body: JSON.stringify({
                            kelas_id: $('#reactivateKelas').val(),
                            musyrif_id: $('#reactivateMusyrif').val(),
                            reason: $('#reactivateReason').val(),
                            changed_at: $('#reactivateChangedAt').val(),
                        }),
                    });

                    reactivateModal.hide();
                    await Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message });
                    reloadData();
                } catch (error) {
                    showError(error);
                }
            });

            $('#deactivateModal').on('shown.bs.modal', function() {
                $('#deactivateChangedAt').val(nowLocalValue());
            });

            $('#deactivateForm').on('submit', async function(event) {
                event.preventDefault();
                const id = $('#deactivateSantriId').val();

                if (!id) {
                    showError(new Error('Pilih santri aktif.'));
                    return;
                }

                try {
                    const confirm = await Swal.fire({
                        icon: 'warning',
                        title: 'Arsipkan santri aktif?',
                        text: 'Santri tidak lagi muncul sebagai santri aktif Musyrif.',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Arsipkan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#dc3545',
                    });
                    if (!confirm.isConfirmed) return;

                    const json = await requestJson(endpoint(endpoints.deactivate, id), {
                        method: 'PATCH',
                        body: JSON.stringify({
                            status: $('#deactivateStatus').val(),
                            reason: $('#deactivateReason').val(),
                            changed_at: $('#deactivateChangedAt').val(),
                        }),
                    });

                    deactivateModal.hide();
                    this.reset();
                    $(`#deactivateSantriId option[value="${id}"]`).remove();
                    await Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message });
                    reloadData();
                } catch (error) {
                    showError(error);
                }
            });

            loadStatistics();
        });
    </script>
@endpush
