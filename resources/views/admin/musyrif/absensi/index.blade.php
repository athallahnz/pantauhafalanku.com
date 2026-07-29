@extends('layouts.app')

@section('title', 'Log Aktivitas Absensi Keseluruhan')

@section('content')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

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

        /* ================= LOCATION MANAGEMENT ================= */
        #globalLocationsMap {
            height: 380px;
            width: 100%;
            background: var(--cui-tertiary-bg);
        }

        #locationEditorMap {
            height: 300px;
            width: 100%;
            border-radius: 16px;
            background: var(--cui-tertiary-bg);
        }

        .location-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .location-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 11px;
            border: 1px solid var(--cui-border-color);
            border-radius: 999px;
            background: var(--cui-card-bg);
            font-size: 0.75rem;
        }

        .location-color-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            flex: 0 0 auto;
        }

        .location-table-scroll {
            max-height: 460px;
            overflow-y: auto;
        }

        .location-empty-state {
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .leaflet-popup-content {
            min-width: 190px;
        }

        [data-coreui-theme="dark"] .leaflet-container {
            filter: brightness(0.86) contrast(1.05);
        }

        @media (max-width: 768px) {
            #globalLocationsMap {
                height: 300px;
            }

            #locationEditorMap {
                height: 250px;
            }
        }
    </style>

    {{-- HEADER --}}
    <div class="row mb-4 align-items-center px-3 px-md-0 g-2">
        <div class="col-12 col-md">
            <h4 class="fw-bold text-adaptive-purple mb-1">Manajemen Absensi</h4>
            <p class="text-muted small mb-0">
                <i class="bi bi-list-check me-1"></i> Log aktivitas absensi seluruh musyrif
            </p>
        </div>
        <div class="col-12 col-md-auto">
            <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm w-100"
                id="btnOpenLocationManager" data-bs-toggle="modal" data-bs-target="#locationManagerModal">
                <i class="bi bi-geo-alt-fill me-1"></i> Tambah Lokasi
            </button>
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
                    <button class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm" type="button"
                        id="btnFilter">Filter</button>
                    <button class="btn btn-outline-secondary rounded-pill w-100 fw-bold" type="button"
                        id="btnReset">Reset</button>
                </div>
            </form>
        </div>
    </div>

    {{-- LOG TABLE CARD --}}
    <div class="card main-card mb-4">
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

    {{-- GLOBAL GEOFENCE MAP --}}
    <div class="card main-card">
        <div class="card-header bg-white py-3 px-4 border-bottom">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <h6 class="fw-bold mb-1">
                        <i class="bi bi-map-fill text-primary me-2"></i>Peta Global Lokasi & Radius
                    </h6>
                    <div class="text-white small">Lingkaran berwarna menunjukkan area absensi yang dinyatakan valid.</div>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2"
                        id="activeLocationCount">
                        0 lokasi aktif
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btnFitAllLocations">
                        <i class="bi bi-arrows-fullscreen me-1"></i>Lihat Semua
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="globalLocationsMap" aria-label="Peta global titik dan radius absensi"></div>
        </div>
        <div class="card-footer bg-transparent border-top p-3">
            <div id="globalLocationLegend" class="location-legend">
                <span class="text-muted small">Memuat lokasi...</span>
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

    {{-- MODAL MANAJEMEN TITIK & RADIUS --}}
    <div class="modal fade" id="locationManagerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-0 px-4 py-3">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">
                            <i class="bi bi-geo-fill text-primary me-2"></i>Manajemen Lokasi Absensi
                        </h5>
                        <p class="text-white small mb-0">Klik peta untuk menentukan titik pusat, lalu atur radius
                            validasinya.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    <div class="row g-0">
                        {{-- LIST LOKASI --}}
                        <div class="col-lg-7 border-end">
                            <div class="p-3 p-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">Daftar Titik Lokasi</h6>
                                        <div class="text-muted small" id="locationListSummary">Memuat data...</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3"
                                        id="btnNewLocation">
                                        <i class="bi bi-plus-lg me-1"></i>Lokasi Baru
                                    </button>
                                </div>

                                <div class="table-responsive location-table-scroll border rounded-4">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="sticky-top">
                                            <tr>
                                                <th>Lokasi</th>
                                                <th>Radius</th>
                                                <th>Status</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="locationTableBody">
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <span class="spinner-border spinner-border-sm me-2"></span>Memuat
                                                    lokasi...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- FORM EDITOR --}}
                        <div class="col-lg-5">
                            <div class="p-3 p-md-4 bg-light h-100">
                                <form id="locationForm">
                                    <input type="hidden" id="locationId">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1" id="locationFormTitle">Tambah Lokasi Baru</h6>
                                            <div class="text-muted small">Semua kolom bertanda * wajib diisi.</div>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill"
                                            id="locationFormMode">
                                            BARU
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <label for="locationName" class="form-label small fw-bold">NAMA LOKASI *</label>
                                        <input type="text" class="form-control rounded-3" id="locationName"
                                            maxlength="120" placeholder="Contoh: Kampus Putri" required>
                                        <div class="invalid-feedback" data-field-error="name"></div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="locationAddress" class="form-label small fw-bold">ALAMAT /
                                            KETERANGAN</label>
                                        <input type="text" class="form-control rounded-3" id="locationAddress"
                                            maxlength="255" placeholder="Alamat singkat lokasi">
                                        <div class="invalid-feedback" data-field-error="address"></div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label for="locationLatitude" class="form-label small fw-bold">LATITUDE
                                                *</label>
                                            <input type="number" class="form-control rounded-3" id="locationLatitude"
                                                step="0.0000001" min="-90" max="90" required>
                                            <div class="invalid-feedback" data-field-error="latitude"></div>
                                        </div>
                                        <div class="col-6">
                                            <label for="locationLongitude" class="form-label small fw-bold">LONGITUDE
                                                *</label>
                                            <input type="number" class="form-control rounded-3" id="locationLongitude"
                                                step="0.0000001" min="-180" max="180" required>
                                            <div class="invalid-feedback" data-field-error="longitude"></div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label small fw-bold mb-0">PILIH TITIK PADA PETA *</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                            id="btnUseCurrentLocation">
                                            <i class="bi bi-crosshair me-1"></i>GPS Saya
                                        </button>
                                    </div>
                                    <div id="locationEditorMap" class="border shadow-sm mb-3"></div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-7">
                                            <label for="locationRadius" class="form-label small fw-bold">RADIUS VALID
                                                *</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="locationRadius"
                                                    min="20" max="5000" step="10" value="150"
                                                    required>
                                                <span class="input-group-text">meter</span>
                                            </div>
                                            <div class="invalid-feedback d-block" data-field-error="radius_m"></div>
                                        </div>
                                        <div class="col-5">
                                            <label for="locationColor" class="form-label small fw-bold">WARNA AREA
                                                *</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                id="locationColor" value="#6F42C1" title="Pilih warna area">
                                            <div class="invalid-feedback d-block" data-field-error="color"></div>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-4">
                                        <div class="col-5">
                                            <label for="locationSortOrder" class="form-label small fw-bold">URUTAN</label>
                                            <input type="number" class="form-control rounded-3" id="locationSortOrder"
                                                min="0" max="9999" value="0">
                                        </div>
                                        <div class="col-7 d-flex align-items-end">
                                            <div class="form-check form-switch border rounded-3 px-3 py-2 w-100">
                                                <input class="form-check-input ms-0 me-2" type="checkbox"
                                                    id="locationIsActive" checked>
                                                <label class="form-check-label fw-semibold" for="locationIsActive">
                                                    Lokasi Aktif
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
                                            id="btnResetLocationForm">Reset</button>
                                        <button type="submit"
                                            class="btn btn-primary rounded-pill px-4 flex-grow-1 fw-bold"
                                            id="btnSaveLocation">
                                            <span class="save-label"><i class="bi bi-check2-circle me-1"></i>Simpan
                                                Lokasi</span>
                                            <span class="saving-label d-none">
                                                <span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // ================= MANAJEMEN LOKASI & RADIUS =================
            const locationRoutes = {
                index: @json(route('admin.musyrif.attendance-locations.index')),
                store: @json(route('admin.musyrif.attendance-locations.store')),
                update: @json(route('admin.musyrif.attendance-locations.update', ':id')),
                destroy: @json(route('admin.musyrif.attendance-locations.destroy', ':id'))
            };
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const defaultMapCenter = [-7.91846, 111.48354];
            const colorPalette = ['#6F42C1', '#D63384', '#0D6EFD', '#198754', '#FD7E14', '#DC3545'];

            let attendanceLocations = [];
            let globalLocationsMap = null;
            let globalLocationLayer = null;
            let editorMap = null;
            let editorMarker = null;
            let editorCircle = null;

            function escapeHtml(value) {
                const element = document.createElement('div');
                element.textContent = value === null || value === undefined ? '' : String(value);
                return element.innerHTML;
            }

            function locationUrl(template, id) {
                return template.replace(':id', id);
            }

            function makeTileLayer() {
                return L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                });
            }

            function initGlobalLocationsMap() {
                if (globalLocationsMap) return;

                globalLocationsMap = L.map('globalLocationsMap', {
                    zoomControl: true
                }).setView(defaultMapCenter, 11);
                makeTileLayer().addTo(globalLocationsMap);
                globalLocationLayer = L.featureGroup().addTo(globalLocationsMap);
            }

            function initEditorMap() {
                if (editorMap) {
                    editorMap.invalidateSize();
                    return;
                }

                editorMap = L.map('locationEditorMap').setView(defaultMapCenter, 11);
                makeTileLayer().addTo(editorMap);
                editorMap.on('click', function(event) {
                    $('#locationLatitude').val(event.latlng.lat.toFixed(7));
                    $('#locationLongitude').val(event.latlng.lng.toFixed(7));
                    updateEditorShape(true);
                });
            }

            function renderGlobalLocations() {
                initGlobalLocationsMap();
                globalLocationLayer.clearLayers();

                const activeCount = attendanceLocations.filter(function(location) {
                    return location.is_active;
                }).length;
                $('#activeLocationCount').text(activeCount + ' lokasi aktif');

                if (attendanceLocations.length === 0) {
                    $('#globalLocationLegend').html(
                        '<span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Belum ada lokasi. Klik tombol Tambah Lokasi.</span>'
                    );
                    globalLocationsMap.setView(defaultMapCenter, 11);
                    return;
                }

                let legendHtml = '';
                attendanceLocations.forEach(function(location) {
                    const lat = Number(location.latitude);
                    const lng = Number(location.longitude);
                    const radius = Number(location.radius_m);
                    const color = location.is_active ? location.color : '#6C757D';
                    const statusText = location.is_active ? 'Aktif' : 'Nonaktif';

                    const circle = L.circle([lat, lng], {
                        radius: radius,
                        color: color,
                        fillColor: color,
                        fillOpacity: location.is_active ? 0.18 : 0.06,
                        opacity: location.is_active ? 0.9 : 0.55,
                        weight: 2,
                        dashArray: location.is_active ? null : '7 6'
                    }).addTo(globalLocationLayer);

                    L.circleMarker([lat, lng], {
                        radius: 7,
                        color: '#FFFFFF',
                        weight: 2,
                        fillColor: color,
                        fillOpacity: 1
                    }).addTo(globalLocationLayer);

                    circle.bindPopup(
                        '<div class="fw-bold mb-1">' + escapeHtml(location.name) + '</div>' +
                        '<div class="small text-muted mb-2">' + escapeHtml(location.address ||
                            'Tanpa keterangan alamat') + '</div>' +
                        '<div class="small"><b>Radius:</b> ' + radius.toLocaleString('id-ID') +
                        ' m</div>' +
                        '<div class="small"><b>Status:</b> ' + statusText + '</div>' +
                        '<div class="small mt-1">' + lat.toFixed(7) + ', ' + lng.toFixed(7) + '</div>'
                    );

                    legendHtml +=
                        '<span class="location-legend-item">' +
                        '<span class="location-color-dot" style="background:' + escapeHtml(color) +
                        '"></span>' +
                        '<span class="fw-semibold">' + escapeHtml(location.name) + '</span>' +
                        '<span class="text-muted">' + radius.toLocaleString('id-ID') + ' m</span>' +
                        (location.is_active ? '' : '<span class="badge bg-secondary">Nonaktif</span>') +
                        '</span>';
                });

                $('#globalLocationLegend').html(legendHtml);
                fitAllLocations();
            }

            function fitAllLocations() {
                if (!globalLocationLayer || globalLocationLayer.getLayers().length === 0) {
                    if (globalLocationsMap) globalLocationsMap.setView(defaultMapCenter, 11);
                    return;
                }

                const bounds = globalLocationLayer.getBounds();
                if (bounds.isValid()) {
                    globalLocationsMap.fitBounds(bounds.pad(0.18), {
                        maxZoom: 17
                    });
                }
            }

            function renderLocationTable() {
                const tbody = $('#locationTableBody');
                $('#locationListSummary').text(
                    attendanceLocations.length + ' titik tersimpan • ' +
                    attendanceLocations.filter(function(location) {
                        return location.is_active;
                    }).length + ' aktif'
                );

                if (attendanceLocations.length === 0) {
                    tbody.html(
                        '<tr><td colspan="4"><div class="location-empty-state text-muted">' +
                        '<div><i class="bi bi-geo-alt fs-2 d-block mb-2"></i>Belum ada titik lokasi.</div>' +
                        '</div></td></tr>'
                    );
                    return;
                }

                const rows = attendanceLocations.map(function(location) {
                    const statusBadge = location.is_active ?
                        '<span class="badge bg-success-subtle text-success-emphasis rounded-pill">Aktif</span>' :
                        '<span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">Nonaktif</span>';

                    return '<tr>' +
                        '<td>' +
                        '<div class="d-flex align-items-start gap-2">' +
                        '<span class="location-color-dot mt-1" style="background:' + escapeHtml(location
                            .color) + '"></span>' +
                        '<div>' +
                        '<div class="fw-semibold">' + escapeHtml(location.name) + '</div>' +
                        '<div class="text-muted" style="font-size:11px;">' +
                        Number(location.latitude).toFixed(7) + ', ' +
                        Number(location.longitude).toFixed(7) +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</td>' +
                        '<td class="fw-semibold">' + Number(location.radius_m).toLocaleString('id-ID') +
                        ' m</td>' +
                        '<td>' + statusBadge + '</td>' +
                        '<td class="text-end">' +
                        '<div class="btn-group btn-group-sm">' +
                        '<button type="button" class="btn btn-outline-primary btn-edit-location" data-id="' +
                        location.id + '" title="Edit titik"><i class="bi bi-pencil-square"></i></button>' +
                        '<button type="button" class="btn btn-outline-danger btn-delete-location" data-id="' +
                        location.id + '" title="Hapus titik"><i class="bi bi-trash3"></i></button>' +
                        '</div>' +
                        '</td>' +
                        '</tr>';
                }).join('');

                tbody.html(rows);
            }

            async function locationRequest(url, options) {
                const response = await fetch(url, Object.assign({
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                }, options || {}));

                let payload = {};
                try {
                    payload = await response.json();
                } catch (error) {
                    payload = {
                        message: 'Respons server tidak dapat dibaca.'
                    };
                }

                if (!response.ok) {
                    const requestError = new Error(payload.message || 'Permintaan gagal diproses.');
                    requestError.payload = payload;
                    throw requestError;
                }

                return payload;
            }

            async function loadAttendanceLocations() {
                try {
                    const payload = await locationRequest(locationRoutes.index);
                    attendanceLocations = payload.data || [];
                    renderGlobalLocations();
                    renderLocationTable();
                } catch (error) {
                    $('#locationTableBody').html(
                        '<tr><td colspan="4" class="text-center text-danger py-5">' +
                        '<i class="bi bi-exclamation-circle me-1"></i>' + escapeHtml(error.message) +
                        '</td></tr>'
                    );
                    $('#globalLocationLegend').html(
                        '<span class="text-danger small">' + escapeHtml(error.message) + '</span>'
                    );
                }
            }

            function clearLocationErrors() {
                $('#locationForm .is-invalid').removeClass('is-invalid');
                $('[data-field-error]').text('');
            }

            function showLocationErrors(errors) {
                Object.keys(errors || {}).forEach(function(field) {
                    const fieldMap = {
                        name: '#locationName',
                        address: '#locationAddress',
                        latitude: '#locationLatitude',
                        longitude: '#locationLongitude',
                        radius_m: '#locationRadius',
                        color: '#locationColor',
                        sort_order: '#locationSortOrder'
                    };
                    if (fieldMap[field]) $(fieldMap[field]).addClass('is-invalid');
                    $('[data-field-error="' + field + '"]').text(errors[field][0] || '');
                });
            }

            function updateEditorShape(moveMap) {
                if (!editorMap) return;

                const lat = Number($('#locationLatitude').val());
                const lng = Number($('#locationLongitude').val());
                const radius = Number($('#locationRadius').val()) || 150;
                const color = $('#locationColor').val() || '#6F42C1';

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                    if (editorMarker) {
                        editorMap.removeLayer(editorMarker);
                        editorMarker = null;
                    }
                    if (editorCircle) {
                        editorMap.removeLayer(editorCircle);
                        editorCircle = null;
                    }
                    return;
                }

                const latLng = [lat, lng];
                if (!editorMarker) {
                    editorMarker = L.marker(latLng, {
                        draggable: true
                    }).addTo(editorMap);
                    editorMarker.on('dragend', function(event) {
                        const point = event.target.getLatLng();
                        $('#locationLatitude').val(point.lat.toFixed(7));
                        $('#locationLongitude').val(point.lng.toFixed(7));
                        updateEditorShape(false);
                    });
                } else {
                    editorMarker.setLatLng(latLng);
                }

                if (!editorCircle) {
                    editorCircle = L.circle(latLng, {
                        radius: radius,
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.2,
                        weight: 2
                    }).addTo(editorMap);
                } else {
                    editorCircle.setLatLng(latLng);
                    editorCircle.setRadius(radius);
                    editorCircle.setStyle({
                        color: color,
                        fillColor: color
                    });
                }

                if (moveMap) editorMap.setView(latLng, 17);
            }

            function resetLocationForm() {
                clearLocationErrors();
                $('#locationForm')[0].reset();
                $('#locationId').val('');
                $('#locationRadius').val(150);
                $('#locationColor').val(colorPalette[attendanceLocations.length % colorPalette.length]);
                $('#locationSortOrder').val((attendanceLocations.length + 1) * 10);
                $('#locationIsActive').prop('checked', true);
                $('#locationFormTitle').text('Tambah Lokasi Baru');
                $('#locationFormMode').text('BARU');

                const centerLocation = attendanceLocations.find(function(location) {
                    return location.is_active;
                }) || attendanceLocations[0];

                if (centerLocation) {
                    $('#locationLatitude').val(Number(centerLocation.latitude).toFixed(7));
                    $('#locationLongitude').val(Number(centerLocation.longitude).toFixed(7));
                } else {
                    $('#locationLatitude').val('');
                    $('#locationLongitude').val('');
                }

                if (editorMap) {
                    updateEditorShape(Boolean(centerLocation));
                    if (!centerLocation) editorMap.setView(defaultMapCenter, 11);
                }
            }

            function editLocation(id) {
                const location = attendanceLocations.find(function(item) {
                    return Number(item.id) === Number(id);
                });
                if (!location) return;

                clearLocationErrors();
                $('#locationId').val(location.id);
                $('#locationName').val(location.name);
                $('#locationAddress').val(location.address || '');
                $('#locationLatitude').val(Number(location.latitude).toFixed(7));
                $('#locationLongitude').val(Number(location.longitude).toFixed(7));
                $('#locationRadius').val(location.radius_m);
                $('#locationColor').val(location.color);
                $('#locationSortOrder').val(location.sort_order || 0);
                $('#locationIsActive').prop('checked', Boolean(location.is_active));
                $('#locationFormTitle').text('Edit Lokasi');
                $('#locationFormMode').text('EDIT');
                updateEditorShape(true);
            }

            function setLocationSaving(isSaving) {
                $('#btnSaveLocation').prop('disabled', isSaving);
                $('#btnSaveLocation .save-label').toggleClass('d-none', isSaving);
                $('#btnSaveLocation .saving-label').toggleClass('d-none', !isSaving);
            }

            $('#locationManagerModal').on('shown.bs.modal', function() {
                initEditorMap();
                setTimeout(function() {
                    editorMap.invalidateSize();
                    updateEditorShape(false);
                }, 100);
            });

            $('#btnNewLocation, #btnResetLocationForm').on('click', resetLocationForm);
            $('#btnFitAllLocations').on('click', fitAllLocations);

            $('#locationLatitude, #locationLongitude, #locationRadius, #locationColor').on('input change',
                function() {
                    updateEditorShape(false);
                });

            $('#btnUseCurrentLocation').on('click', function() {
                if (!navigator.geolocation) {
                    Swal.fire('GPS Tidak Tersedia', 'Browser tidak mendukung pengambilan lokasi.',
                        'warning');
                    return;
                }

                const button = $(this);
                button.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span>Mencari...'
                );

                navigator.geolocation.getCurrentPosition(function(position) {
                    $('#locationLatitude').val(position.coords.latitude.toFixed(7));
                    $('#locationLongitude').val(position.coords.longitude.toFixed(7));
                    updateEditorShape(true);
                    button.prop('disabled', false).html(
                        '<i class="bi bi-crosshair me-1"></i>GPS Saya');
                }, function() {
                    button.prop('disabled', false).html(
                        '<i class="bi bi-crosshair me-1"></i>GPS Saya');
                    Swal.fire('GPS Gagal',
                        'Lokasi perangkat tidak dapat dibaca. Pilih titik langsung pada peta.',
                        'warning');
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
            });

            $('#locationTableBody').on('click', '.btn-edit-location', function() {
                editLocation($(this).data('id'));
            });

            $('#locationTableBody').on('click', '.btn-delete-location', function() {
                const id = $(this).data('id');
                const location = attendanceLocations.find(function(item) {
                    return Number(item.id) === Number(id);
                });

                Swal.fire({
                    title: 'Hapus titik lokasi?',
                    html: 'Lokasi <b>' + escapeHtml(location ? location.name : '') +
                        '</b> tidak lagi digunakan untuk validasi berikutnya.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then(async function(result) {
                    if (!result.isConfirmed) return;

                    try {
                        const payload = await locationRequest(
                            locationUrl(locationRoutes.destroy, id), {
                                method: 'DELETE'
                            }
                        );
                        await loadAttendanceLocations();
                        resetLocationForm();
                        Swal.fire('Berhasil', payload.message, 'success');
                    } catch (error) {
                        Swal.fire('Gagal', error.message, 'error');
                    }
                });
            });

            $('#locationForm').on('submit', async function(event) {
                event.preventDefault();
                clearLocationErrors();

                const id = $('#locationId').val();
                const payload = {
                    name: $('#locationName').val().trim(),
                    address: $('#locationAddress').val().trim() || null,
                    latitude: Number($('#locationLatitude').val()),
                    longitude: Number($('#locationLongitude').val()),
                    radius_m: Number($('#locationRadius').val()),
                    color: $('#locationColor').val(),
                    is_active: $('#locationIsActive').is(':checked'),
                    sort_order: Number($('#locationSortOrder').val()) || 0
                };

                setLocationSaving(true);
                try {
                    const result = await locationRequest(
                        id ? locationUrl(locationRoutes.update, id) : locationRoutes.store, {
                            method: id ? 'PUT' : 'POST',
                            body: JSON.stringify(payload)
                        }
                    );

                    await loadAttendanceLocations();
                    if (id) {
                        editLocation(id);
                    } else {
                        resetLocationForm();
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: result.message,
                        timer: 1800,
                        showConfirmButton: false
                    });
                } catch (error) {
                    showLocationErrors(error.payload ? error.payload.errors : {});
                    Swal.fire('Data Belum Tersimpan', error.message, 'error');
                } finally {
                    setLocationSaving(false);
                }
            });

            initGlobalLocationsMap();
            loadAttendanceLocations().then(resetLocationForm);

            // ================= INISIALISASI DATATABLES =================
            let table = $('#allAttendanceTable').DataTable({
                processing: true,
                serverSide: true,
                stateSave: true, // Menyimpan state halaman agar tidak reset ke page 1 setelah update/delete
                ajax: {
                    url: "{{ route('admin.musyrif.absensi.index') }}",
                    data: function(d) {
                        // Kirim parameter filter ke server
                        d.date = $('#filterDate').val();
                        d.musyrif_id = $('#filterMusyrif').val();
                        d.type = $('#filterType').val();
                        d.status = $('#filterStatus').val();
                    }
                },
                columns: [{
                        data: 'waktu',
                        name: 'attendance_at'
                    },
                    {
                        data: 'musyrif_info',
                        name: 'musyrif.nama'
                    },
                    {
                        data: 'sesi_status',
                        name: 'status'
                    },
                    {
                        data: 'lokasi',
                        name: 'address_text'
                    },
                    {
                        data: 'foto',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end pe-4'
                    }
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

                let embedUrl =
                    `https://maps.google.com/maps?q=${lat},${lng}&t=&z=16&ie=UTF8&iwloc=&output=embed`;

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
