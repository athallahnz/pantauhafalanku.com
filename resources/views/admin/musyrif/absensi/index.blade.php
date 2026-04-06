@extends('layouts.app')

@section('title', 'Log Aktivitas Absensi Keseluruhan')

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

        /* ================= PHOTO PREVIEW UI ================= */
        #rotationWrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50vh;
            transition: transform 0.3s ease-out;
        }

        #photoPreview {
            image-orientation: from-image;
            display: block;
            margin: 0 auto;
            max-width: 100%;
            max-height: 75vh;
            object-fit: contain;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        @media (min-width: 576px) {
            #photoModal .modal-dialog {
                max-width: 800px;
            }
        }
    </style>

    {{-- HEADER --}}
    <div class="row mb-4 align-items-center px-3 px-md-0 g-2">
        <div class="col">
            <h4 class="fw-bold text-adaptive-purple mb-1">Manajemen Absensi</h4>
            <p class="text-muted small mb-0">
                <i class="bi bi-list-check me-1"></i> Log aktivitas absensi seluruh musyrif
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">{{ session('error') }}</div>
    @endif

    {{-- FILTER CARD --}}
    <div class="card main-card mb-4">
        <div class="card-body p-4">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">TANGGAL ABSEN</label>
                    <input type="date" name="date" class="form-control rounded-3" id="filterDate">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">MUSYRIF</label>
                    <select name="musyrif_id" class="form-select rounded-3" id="filterMusyrif">
                        <option value="">Semua Musyrif</option>
                        @foreach ($musyrifs as $m)
                            <option value="{{ $m->id }}">{{ $m->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">SESI</label>
                    <select name="type" class="form-select rounded-3" id="filterType">
                        <option value="">Semua Sesi</option>
                        <option value="morning">Pagi</option>
                        <option value="afternoon">Malam</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">STATUS</label>
                    <select name="status" class="form-select rounded-3" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="valid">Valid</option>
                        <option value="suspect">Suspect</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm" type="button" id="btnFilter">Filter</button>
                    <button class="btn btn-outline-secondary rounded-pill w-100 fw-bold" type="button" id="btnReset">Reset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- LOG TABLE CARD --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Daftar Keseluruhan Absensi</h6>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap w-100" id="allAttendanceTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Waktu</th>
                            <th>Musyrif</th>
                            <th>Sesi & Status</th>
                            <th>Lokasi</th>
                            <th>Foto</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Data diisi oleh Yajra DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- FORM HIDDEN (Untuk Update Status & Delete via Laravel Form Submit) --}}
    <form id="statusForm" action="" method="POST" style="display: none;">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" id="statusValue">
        <input type="hidden" name="reason" id="reasonValue">
    </form>

    <form id="deleteForm" action="" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
@endsection

@push('modals')
    {{-- MODAL PREVIEW FOTO --}}
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 bg-light d-flex justify-content-between align-items-center">
                    <h6 class="modal-title fw-bold">
                        <i class="bi bi-camera text-primary me-2"></i>Preview Foto Absensi
                    </h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-danger text-white rounded-pill px-3" id="btnRotate">
                            <i class="bi bi-arrow-clockwise me-1"></i> Putar Foto
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4 text-center bg-light overflow-hidden">
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

    {{-- MODAL PREVIEW LOKASI --}}
    <div class="modal fade" id="modalPreviewMap" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-0">
                    <h6 class="modal-title fw-bold text-primary">
                        <i class="bi bi-geo-alt text-danger me-2"></i> Preview Lokasi Absensi
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="previewMapIframe" width="100%" height="450" style="border:0; display:block;"
                        allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {

            // ================= INISIALISASI DATATABLES =================
            let table = $('#allAttendanceTable').DataTable({
                processing: true,
                serverSide: true,
                stateSave: true, // Menyimpan state halaman agar tidak reset ke page 1 setelah update/delete
                ajax: {
                    url: "{{ route('admin.musyrif.absensi.index') }}",
                    data: function (d) {
                        // Kirim parameter filter ke server
                        d.date = $('#filterDate').val();
                        d.musyrif_id = $('#filterMusyrif').val();
                        d.type = $('#filterType').val();
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [
                    { data: 'waktu', name: 'attendance_at' },
                    { data: 'musyrif_info', name: 'musyrif.nama' },
                    { data: 'sesi_status', name: 'status' },
                    { data: 'lokasi', name: 'address_text' },
                    { data: 'foto', orderable: false, searchable: false },
                    { data: 'aksi', orderable: false, searchable: false, className: 'text-end pe-4' }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json'
                }
            });

            // ================= FILTER TRIGGER =================
            $('#btnFilter').click(function() {
                table.draw();
            });

            $('#btnReset').click(function() {
                $('#filterForm')[0].reset();
                table.draw();
            });

            // ================= PREVIEW FOTO =================
            let currentRotation = 0;

            // Gunakan event delegation agar fungsi tetap bekerja pada elemen hasil AJAX DataTables
            $('#allAttendanceTable').on('click', '.btnPreview', function() {
                const photoUrl = $(this).data('photo');
                currentRotation = 0;
                $('#rotationWrapper').css('transform', 'rotate(0deg)');
                $('#photoPreview').attr('src', photoUrl);

                const myModal = new bootstrap.Modal(document.getElementById('photoModal'));
                myModal.show();
            });

            $(document).on('click', '#btnRotate', function() {
                currentRotation -= 90;
                const isVertical = (currentRotation / 90) % 2 !== 0;

                if (isVertical) {
                    $('#rotationWrapper').css('transform', `rotate(${currentRotation}deg) scale(0.7)`);
                } else {
                    $('#rotationWrapper').css('transform', `rotate(${currentRotation}deg) scale(1)`);
                }
            });

            // ================= PREVIEW MAPS =================
            $('#allAttendanceTable').on('click', '.btn-preview-map', function() {
                let lat = $(this).data('lat');
                let lng = $(this).data('lng');

                let embedUrl = `https://maps.google.com/maps?q=${lat},${lng}&t=&z=16&ie=UTF8&iwloc=&output=embed`;

                $('#previewMapIframe').attr('src', embedUrl);
                const mapModal = new bootstrap.Modal(document.getElementById('modalPreviewMap'));
                mapModal.show();
            });

            document.getElementById('modalPreviewMap').addEventListener('hidden.bs.modal', function() {
                document.getElementById('previewMapIframe').src = '';
            });

            // ================= UPDATE STATUS =================
            $('#allAttendanceTable').on('click', '.btnUpdateStatus', function() {
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
                            return 'Alasan wajib diisi (min. 5 karakter)!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('#statusForm');
                        let url = "{{ route('admin.musyrif.attendances.update_status', ':attendance') }}";
                        url = url.replace(':attendance', attendanceId);

                        form.attr('action', url);
                        $('#statusValue').val(newStatus);
                        $('#reasonValue').val(result.value);
                        form.submit();
                    }
                });
            });

            // ================= HAPUS DATA =================
            $('#allAttendanceTable').on('click', '.btnDelete', function() {
                const attendanceId = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data absensi yang dihapus tidak dapat dikembalikan!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '<i class="bi bi-trash"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = $('#deleteForm');
                        let url = "{{ route('admin.musyrif.absensi.destroy', ':attendance') }}";
                        url = url.replace(':attendance', attendanceId);

                        form.attr('action', url);
                        form.submit();
                    }
                });
            });

        });
    </script>
@endpush
