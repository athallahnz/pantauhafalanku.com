<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Kamera Presensi Musyrif</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap & CoreUI Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        /* ================= FULLSCREEN CAMERA UI ================= */
        :root {
            --islamic-purple-600: #6f42c1;
        }

        body,
        html {
            margin: 0;
            padding: 0;
            width: 100vw;
            height: 100vh;
            background-color: #000;
            overflow: hidden;
            font-family: 'Inter', sans-serif;
        }

        /* Container Kamera & Video */
        .camera-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1;
            background-color: #000;
            /* Pastikan background hitam */
        }

        /* 1. Kamera Live tetap pakai cover biar full layar saat mau jepret */
        .cam-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* 2. Hasil Preview pakai contain biar rasio asli (landscape/portrait) tampil UTUH! */
        #previewFull {
            width: 100%;
            height: 100%;
            object-fit: contain !important;
            object-position: center;
        }

        /* Preview foto menyesuaikan rasio asli (Real Result) */
        #previewStage {
            background-color: #000;
            /* Pastikan background hitam */
        }

        .mirror {
            transform: scaleX(-1);
        }

        /* OVERLAY TOP (Judul & Info Lokasi) */
        .overlay-top {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 30px 20px 40px;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 100%);
            z-index: 10;
            color: #fff;
        }

        /* OVERLAY BOTTOM (Tombol Shutter dll) */
        .overlay-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 40px 30px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0) 100%);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Shutter Button */
        .shutter-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #btnShutter {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 4px solid #fff;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        #btnShutter .inner-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #fff;
            transition: all 0.2s ease;
        }

        #btnShutter:active .inner-circle {
            transform: scale(0.9);
            background: #f0f0f0;
        }

        #btnShutter:disabled {
            opacity: 0.5;
        }

        /* Tombol Samping (Back & History) */
        .btn-side {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.2s ease;
        }

        .btn-side:hover,
        .btn-side:active {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        /* Tombol Switch Camera */
        .btn-switch-cam {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 50px;
            padding: 8px 16px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Form Bottom Sheet Overlay */
        #formStage {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 50;
            display: flex;
            align-items: flex-end;
            /* Nempel di bawah */
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        #formStage.show {
            opacity: 1;
            pointer-events: auto;
        }

        .bottom-sheet {
            background: #fff;
            width: 100%;
            border-radius: 28px 28px 0 0;
            padding: 30px 20px 40px;
            transform: translateY(100%);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
        }

        #formStage.show .bottom-sheet {
            transform: translateY(0);
        }

        /* Alert styling */
        .alert-floating {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            width: 90%;
        }

        /* ================= NATIVE LANDSCAPE CAMERA FEEL ================= */
        @media (max-width: 992px) and (orientation: landscape) {

            /* Sulap panel bawah menjadi panel samping kanan */
            .overlay-bottom {
                top: 0;
                bottom: 0;
                right: 0;
                left: auto;
                width: 130px;
                /* Lebar area jempol */
                height: 100vh;
                flex-direction: column;
                justify-content: space-evenly;
                /* Tombol berjajar dari atas ke bawah */
                background: linear-gradient(to left, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0) 100%);
                padding: 20px 10px;
            }

            /* Batasi lebar overlay atas agar teks tidak menabrak tombol shutter di kanan */
            .overlay-top {
                width: calc(100vw - 130px);
                padding: 15px 20px;
            }

            /* Perkecil sedikit margin/padding di info atas agar tidak sempit */
            .overlay-top .mb-3 {
                margin-bottom: 0.5rem !important;
            }

            /* Sesuaikan juga tombol Preview (Ulangi/Gunakan) agar muat di kolom kanan */
            #previewStage .overlay-bottom {
                justify-content: center;
                gap: 15px !important;
            }

            #previewStage .overlay-bottom button {
                width: 100%;
                padding: 12px 5px !important;
                font-size: 0.85rem;
            }

            /* Putar icon shutter biar estetik saat landscape */
            #btnSwitchCam {
                margin-top: 5px;
            }
        }

        /* Handle Garis Abu-abu di Atas Form */
        .drag-handle {
            width: 40px;
            height: 5px;
            background-color: #dee2e6;
            border-radius: 10px;
            margin: 0 auto;
        }

        /* Mode Gelap untuk Drag Handle */
        [data-coreui-theme="dark"] .drag-handle {
            background-color: #4a4a55;
        }

        /* Saat sedang digeser jari, matikan animasi bawaan agar responsif 1:1 */
        .bottom-sheet.dragging {
            transition: none !important;
        }
    </style>
</head>

<body>

    {{-- ALERTS (Success/Error) --}}
    @if (session('success') || $errors->any())
        <div class="alert-floating">
            @if (session('success'))
                <div class="alert alert-success border-0 shadow-lg rounded-4">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-lg rounded-4">
                    <ul class="mb-0 small ps-3">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- ================= 1. STAGE KAMERA LIVE ================= --}}
    <div id="cameraStage" class="camera-container">
        <video id="video" class="cam-video mirror" autoplay playsinline muted></video>

        {{-- Top Overlay: Info & Judul --}}
        <div class="overlay-top">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h3 class="fw-bold mb-0 text-white" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Presensi Musyrif
                    </h3>
                    <div class="small text-light opacity-75">Pastikan wajah & pencahayaan jelas</div>
                    <span id="offlineBadge" class="badge bg-danger rounded-pill mt-1 d-none">
                        <i class="bi bi-wifi-off"></i> Mode Offline Aktif
                    </span>
                </div>
                {{-- Tombol Balik Kamera --}}
                <button id="btnSwitchCam" class="btn btn-switch-cam">
                    <i class="bi bi-camera-reels-fill"></i> Putar
                </button>
            </div>

            <div class="d-flex justify-content-between align-items-end text-white small"
                style="text-shadow: 0 1px 3px rgba(0,0,0,0.8);">
                <div>
                    <div class="fw-bold text-warning"><i class="bi bi-geo-alt-fill me-1"></i>LOKASI GPS</div>
                    <div id="locText" class="opacity-75" style="font-size: 11px;">Mencari sinyal...</div>
                    <div id="accText" class="opacity-75" style="font-size: 11px;"></div>
                    <div id="geoFenceText" class="badge bg-danger mt-1 text-wrap text-start" style="font-size: 10px;">
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold text-info"><i class="bi bi-camera-fill me-1"></i>STATUS</div>
                    <div id="camText" class="opacity-75" style="font-size: 11px;">Menyiapkan...</div>
                </div>
            </div>
        </div>

        {{-- Bottom Overlay: Controls --}}
        <div class="overlay-bottom">
            {{-- Kiri: Tombol Back (Kembali ke Dashboard) --}}
            <a href="{{ route('musyrif.dashboard') }}" class="btn-side shadow" title="Kembali">
                <i class="bi bi-chevron-left"></i>
            </a>

            {{-- Tengah: Tombol Shutter --}}
            <div class="shutter-wrapper">
                <button id="btnShutter" type="button" disabled>
                    <div class="inner-circle"></div>
                </button>
            </div>

            {{-- Kanan: Tombol History --}}
            <a href="{{ route('musyrif.absensi.history') }}" class="btn-side shadow" title="Riwayat Absen">
                <i class="bi bi-clock-history"></i>
            </a>
        </div>
    </div>

    {{-- ================= 2. STAGE PREVIEW FOTO ================= --}}
    <div id="previewStage" class="camera-container d-none">
        <img id="previewFull" alt="Preview foto">

        <div class="overlay-top">
            <h4 class="fw-bold text-white text-center" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">Hasil Foto</h4>
        </div>

        <div class="overlay-bottom justify-content-center gap-3 gap-md-5 px-3">
            <button id="btnRetakeFromPreview"
                class="btn btn-dark bg-opacity-50 rounded-pill px-4 px-md-5 py-2 py-md-3 fw-bold border-light shadow-lg text-white"
                style="backdrop-filter: blur(10px);">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Ulangi
            </button>
            <button id="btnUse" class="btn btn-light rounded-pill px-4 px-md-5 py-2 py-md-3 fw-bold shadow-lg"
                style="color: var(--islamic-purple-600);">
                Gunakan <i class="bi bi-check2-circle ms-1"></i>
            </button>
        </div>  
    </div>

    <canvas id="canvas" class="d-none"></canvas>

    {{-- ================= 3. STAGE FORM (BOTTOM SHEET) ================= --}}
    <div id="formStage">
        <div class="bottom-sheet shadow-lg">
            {{-- Tambahan Garis Drag --}}
            <div class="d-flex justify-content-center mb-3 pb-1">
                <div class="drag-handle"></div>
            </div>

            <div class="mb-4 text-center">
                <h5 class="fw-bold mb-0 text-dark">Simpan Kehadiran</h5>
            </div>

            <form method="POST" action="{{ route('musyrif.absensi.store') }}" id="attForm">
                @csrf
                <input type="hidden" name="photo" id="photoInput" value="{{ old('photo') }}">
                <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude') }}">
                <input type="hidden" name="accuracy" id="accInput" value="{{ old('accuracy') }}">

                {{-- Info Lokasi di Form --}}
                <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-4 border">
                    <div class="rounded-3 overflow-hidden shadow-sm flex-shrink-0" style="width:60px; height:60px;">
                        <img id="previewThumb" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-bold small text-dark">Lokasi Terkunci</div>
                        <div class="text-muted" style="font-size: 11px; line-height: 1.3;">
                            <span id="thumbLocText">-</span><br>
                            <span id="thumbFenceText" class="text-primary fw-bold">-</span>
                        </div>
                    </div>
                </div>

                {{-- Pilihan Sesi --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Sesi Kehadiran</label>
                    <select name="type" class="form-select form-select-lg rounded-4 fs-6" required>
                        <option value="" selected disabled>Pilih sesi absen...</option>
                        <option value="morning">Pagi @if ($morning)
                                (Sudah: {{ $morning->attendance_at->format('H:i') }})
                            @endif
                        </option>
                        <option value="afternoon">Malam @if ($afternoon)
                                (Sudah: {{ $afternoon->attendance_at->format('H:i') }})
                            @endif
                        </option>
                    </select>
                </div>

                {{-- Catatan --}}
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Catatan (Opsional)</label>
                    <textarea name="notes" class="form-control rounded-4" rows="2" placeholder="Tulis keterangan jika ada...">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="btn w-100 py-3 rounded-pill fw-bold text-white shadow"
                    style="background: var(--islamic-purple-600);" id="btnSubmit">
                    <i class="bi bi-send-fill me-1"></i> KIRIM ABSENSI
                </button>
            </form>
        </div>
    </div>

    {{-- SCRIPTS --}}
    <script>
        (function() {
            // 1. INISIALISASI ELEMEN
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const cameraStage = document.getElementById('cameraStage');
            const previewStage = document.getElementById('previewStage');
            const formStage = document.getElementById('formStage');
            const bottomSheet = document.querySelector('.bottom-sheet'); // Elemen Swipe

            const btnShutter = document.getElementById('btnShutter');
            const btnUse = document.getElementById('btnUse');
            const btnRetakeFromPreview = document.getElementById('btnRetakeFromPreview');
            const btnSwitchCam = document.getElementById('btnSwitchCam');

            const locText = document.getElementById('locText');
            const accText = document.getElementById('accText');
            const camText = document.getElementById('camText');
            const previewFull = document.getElementById('previewFull');
            const previewThumb = document.getElementById('previewThumb');
            const thumbLocText = document.getElementById('thumbLocText');

            const photoInput = document.getElementById('photoInput');
            const latInput = document.getElementById('latInput');
            const lngInput = document.getElementById('lngInput');
            const accInput = document.getElementById('accInput');
            const geoFenceText = document.getElementById('geoFenceText');
            const thumbFenceText = document.getElementById('thumbFenceText');

            const attForm = document.getElementById('attForm');
            const offlineBadge = document.getElementById('offlineBadge');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            let currentFacingMode = 'user';
            let stream = null;

            const GEOFENCE_CENTER = {
                lat: -7.8186683,
                lng: 111.5244092
            };
            const GEOFENCE_RADIUS_M = 150;

            // ==========================================
            // SENSOR SWIPE TO DISMISS (BOTTOM SHEET)
            // ==========================================
            if (bottomSheet) {
                let startY = 0;
                let currentY = 0;

                bottomSheet.addEventListener('touchstart', (e) => {
                    startY = e.touches[0].clientY;
                    bottomSheet.classList.add('dragging');
                }, {
                    passive: true
                });

                bottomSheet.addEventListener('touchmove', (e) => {
                    currentY = e.touches[0].clientY;
                    let deltaY = currentY - startY;

                    if (deltaY > 0) { // Hanya bisa ditarik ke bawah
                        bottomSheet.style.transform = `translateY(${deltaY}px)`;
                    }
                }, {
                    passive: true
                });

                bottomSheet.addEventListener('touchend', () => {
                    bottomSheet.classList.remove('dragging');
                    let deltaY = currentY - startY;

                    if (deltaY > 80) { // Tarikan cukup jauh -> Tutup
                        formStage.classList.remove('show');
                    }

                    // Kembalikan posisi style
                    setTimeout(() => {
                        bottomSheet.style.transform = '';
                    }, 300);

                    startY = 0;
                    currentY = 0;
                });
            }

            // ==========================================
            // FITUR PWA & OFFLINE SYNC
            // ==========================================
            function updateNetworkStatus() {
                if (navigator.onLine) {
                    if (offlineBadge) offlineBadge.classList.add('d-none');
                    syncOfflineData();
                } else {
                    if (offlineBadge) offlineBadge.classList.remove('d-none');
                }
            }

            window.addEventListener('online', updateNetworkStatus);
            window.addEventListener('offline', updateNetworkStatus);
            updateNetworkStatus();

            attForm.addEventListener('submit', function(e) {
                if (!navigator.onLine) {
                    e.preventDefault();
                    saveToOfflineQueue();
                }
            });

            function saveToOfflineQueue() {
                const formData = new FormData(attForm);
                const dataObj = Object.fromEntries(formData.entries());
                dataObj.timestamp = new Date().toISOString();

                let queue = JSON.parse(localStorage.getItem('absensi_queue') || '[]');
                queue.push(dataObj);
                localStorage.setItem('absensi_queue', JSON.stringify(queue));

                formStage.classList.remove('show');
                cameraStage.classList.remove('d-none');
                previewStage.classList.add('d-none');

                alert(
                    'Anda sedang Offline! Data absen berhasil disimpan di HP dan akan dikirim otomatis saat sinyal internet kembali.'
                );
            }

            async function syncOfflineData() {
                let queue = JSON.parse(localStorage.getItem('absensi_queue') || '[]');
                if (queue.length === 0) return;

                for (let i = 0; i < queue.length; i++) {
                    const item = queue[i];
                    try {
                        const response = await fetch("{{ route('musyrif.absensi.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(item)
                        });

                        if (response.ok) {
                            queue.splice(i, 1);
                            i--;
                            localStorage.setItem('absensi_queue', JSON.stringify(queue));
                        }
                    } catch (err) {
                        break;
                    }
                }

                if (queue.length === 0) {
                    const alertEl = document.createElement('div');
                    alertEl.className = 'alert-floating';
                    alertEl.innerHTML =
                        `<div class="alert alert-success border-0 shadow-lg rounded-4">Sinkronisasi Offline Berhasil! Absensi Anda sudah masuk ke server.</div>`;
                    document.body.prepend(alertEl);
                    setTimeout(() => alertEl.remove(), 4000);
                }
            }

            // ==========================================
            // LOGIKA KAMERA & KOMPRESI
            // ==========================================
            function capture() {
                const MAX_WIDTH = 800;
                let width = video.videoWidth;
                let height = video.videoHeight;

                if (width > MAX_WIDTH) {
                    height = Math.floor(height * (MAX_WIDTH / width));
                    width = MAX_WIDTH;
                }

                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.save();

                if (currentFacingMode === 'user') {
                    ctx.translate(canvas.width, 0);
                    ctx.scale(-1, 1);
                }

                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                ctx.restore();

                const dataUrl = canvas.toDataURL('image/jpeg', 0.7);

                photoInput.value = dataUrl;
                previewFull.src = dataUrl;
                previewThumb.src = dataUrl;

                cameraStage.classList.add('d-none');
                previewStage.classList.remove('d-none');
                syncThumbOverlay();
            }

            function haversineMeters(lat1, lon1, lat2, lon2) {
                const R = 6371000;
                const toRad = (d) => d * Math.PI / 180;
                const dLat = toRad(lat2 - lat1);
                const dLon = toRad(lon2 - lon1);
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math
                    .sin(dLon / 2) * Math.sin(dLon / 2);
                return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            }

            function isMobileDevice() {
                return window.matchMedia("(max-width: 768px)").matches || /Android|iPhone|iPad|iPod/i.test(navigator
                    .userAgent);
            }

            function haptic(ms = 30) {
                if (isMobileDevice() && navigator.vibrate) navigator.vibrate(ms);
            }

            function formatCoord(lat, lng) {
                return `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }

            function initGeo() {
                if (!navigator.geolocation) {
                    if (locText) locText.innerText = 'GPS: tidak didukung';
                    return;
                }
                navigator.geolocation.getCurrentPosition((pos) => {
                    const {
                        latitude,
                        longitude,
                        accuracy
                    } = pos.coords;
                    latInput.value = latitude;
                    lngInput.value = longitude;
                    accInput.value = accuracy;
                    if (locText) locText.innerText = `GPS: ${formatCoord(latitude, longitude)}`;
                    if (accText) accText.innerText = `Akurasi: ${Math.round(accuracy)} m`;
                    syncThumbOverlay();
                }, (err) => {
                    if (locText) locText.innerText = 'GPS: Ditolak / Gagal';
                }, {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                });
            }

            function syncThumbOverlay() {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    if (thumbLocText) thumbLocText.innerText = `GPS: ${formatCoord(lat, lng)}`;
                    const dist = Math.round(haversineMeters(lat, lng, GEOFENCE_CENTER.lat, GEOFENCE_CENTER.lng));
                    const inside = dist <= GEOFENCE_RADIUS_M;
                    const text =
                        `Radius: ${GEOFENCE_RADIUS_M}m • Jarak: ${dist}m • ${inside ? 'Dalam area' : 'Luar area'}`;

                    if (geoFenceText) {
                        geoFenceText.innerText = text;
                        geoFenceText.className = inside ? "badge bg-success mt-1 text-wrap text-start" :
                            "badge bg-danger mt-1 text-wrap text-start";
                    }
                    if (thumbFenceText) {
                        thumbFenceText.innerText = `Jarak: ${dist} m (${inside ? 'Aman' : 'Luar Area'})`;
                    }
                }
            }

            async function initCamera() {
                if (stream) stream.getTracks().forEach(track => track.stop());

                try {
                    if (camText) camText.innerText = 'Meminta izin...';
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: currentFacingMode,
                            width: {
                                ideal: 1280
                            },
                            height: {
                                ideal: 720
                            }
                        },
                        audio: false
                    });
                    video.srcObject = stream;

                    if (currentFacingMode === 'user') {
                        video.classList.add('mirror');
                    } else {
                        video.classList.remove('mirror');
                    }

                    video.onloadedmetadata = () => {
                        if (camText) camText.innerText = 'Kamera Aktif';
                        btnShutter.disabled = false;
                    };
                } catch (err) {
                    if (camText) camText.innerText = 'Gagal akses kamera';
                }
            }

            btnSwitchCam.addEventListener('click', () => {
                haptic();
                currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
                btnShutter.disabled = true;
                initCamera();
            });

            btnShutter.addEventListener('click', () => {
                haptic();
                capture();
            });

            btnRetakeFromPreview.addEventListener('click', () => {
                cameraStage.classList.remove('d-none');
                previewStage.classList.add('d-none');
            });

            btnUse.addEventListener('click', () => {
                formStage.classList.add('show');
            });

            setTimeout(() => {
                const alertEl = document.querySelector('.alert-floating');
                if (alertEl) alertEl.style.display = 'none';
            }, 4000);

            initGeo();
            initCamera();
        })();
    </script>
</body>

</html>
