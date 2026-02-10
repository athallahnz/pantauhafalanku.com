@extends('layouts.app')

@section('title', 'Absensi Musyrif')
<style>
    /* Root layout full height */
    html,
    body {
        height: 100%;
    }

    /* Container utama jadi flex column */
    .page-full {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Content tengah flexible */
    .page-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* Card camera juga flex column */
    .cam-card {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* Stage camera isi sisa ruang */
    .cam-stage {
        flex: 1;
        width: 100%;

        /* portrait ratio */
        aspect-ratio: 3 / 4;


        /* jangan melebihi ruang tersedia */
        max-height: 100%;

        position: relative;
        overflow: hidden;

        border-radius: 16px;

        display: flex;
    }


    /* Video fill perfectly */
    .cam-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Mirror kamera depan */
    .mirror {
        transform: scaleX(-1);
    }

    /* Mobile optimization */
    @media (max-width: 768px) {

        .container.py-4 {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding-bottom: 0;
        }

        .cam-card {
            border-radius: 16px 16px 0 0 !important;
        }

        .cam-stage {
            flex: 1;
            width: 100%;
            max-height: 60vh;
            aspect-ratio: 9 / 16;
        }

    }
</style>

@section('content')
    <div class="container">
        <div class="mb-3">
            <h4 class="mb-1">Absensi Musyrif</h4>
            <div class="text-muted">Selfie + lokasi otomatis, lalu submit.</div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-bold mb-1">Gagal menyimpan:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-xl-6 col-lg-7">

                {{-- CAMERA CARD --}}
                <div class="card shadow-sm overflow-hidden">
                    <div class="card-body p-0 position-relative">

                        {{-- LIVE CAMERA --}}
                        <div id="cameraStage" class="position-relative bg-dark cam-stage">
                            <video id="video" class="cam-video mirror" autoplay playsinline muted></video>

                            {{-- OVERLAY TOP --}}
                            <div class="position-absolute top-0 start-0 end-0 p-3"
                                style="background:linear-gradient(to bottom, rgba(0,0,0,.55), rgba(0,0,0,0));">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div class="text-white small">
                                        <div class="fw-semibold">Lokasi</div>
                                        <div id="locText" class="opacity-75">GPS: memuat...</div>
                                        <div id="accText" class="opacity-75"></div>
                                        <div id="geoFenceText" class="opacity-75"></div>
                                    </div>

                                    <div class="text-end text-white small">
                                        <div class="fw-semibold">Kamera</div>
                                        <div id="camText" class="opacity-75">Menyiapkan...</div>
                                    </div>
                                </div>
                            </div>

                            {{-- OVERLAY BOTTOM (SHUTTER) --}}
                            <div class="position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-center"
                                style="background:linear-gradient(to top, rgba(0,0,0,.55), rgba(0,0,0,0));">
                                <button id="btnShutter" type="button"
                                    class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center"
                                    style="width:70px;height:70px;border:6px solid rgba(255,255,255,.6);" disabled
                                    aria-label="Ambil Foto">
                                    <i class="bi bi-camera-fill" style="font-size:22px;"></i>
                                </button>
                            </div>
                        </div>

                        {{-- FULL PREVIEW AFTER CAPTURE --}}
                        <div id="previewStage" class="d-none position-relative bg-dark">
                            <img id="previewFull" alt="Preview foto" style="width:100%; display:block; object-fit:cover;">
                            <div class="position-absolute top-0 start-0 end-0 p-3"
                                style="background:linear-gradient(to bottom, rgba(0,0,0,.55), rgba(0,0,0,0));">
                                <div class="text-white small">
                                    <div class="fw-semibold">Preview</div>
                                    <div class="opacity-75">Jika sudah jelas, klik Gunakan Foto.</div>
                                </div>
                            </div>

                            <div class="position-absolute bottom-0 start-0 end-0 p-3 d-flex justify-content-center gap-2"
                                style="background:linear-gradient(to top, rgba(0,0,0,.55), rgba(0,0,0,0));">
                                <button id="btnRetakeFromPreview" type="button" class="btn btn-outline-light">
                                    Ulangi Foto
                                </button>
                                <button id="btnUse" type="button" class="btn btn-light">
                                    Gunakan Foto
                                </button>
                            </div>
                        </div>

                        <canvas id="canvas" class="d-none"></canvas>
                    </div>
                </div>

                {{-- FORM (MUNCUL SETELAH "GUNAKAN FOTO") --}}
                <div id="formStage" class="card shadow-sm mt-3 d-none">
                    <div class="card-body">
                        <form method="POST" action="{{ route('musyrif.absensi.store') }}" id="attForm">
                            @csrf

                            <input type="hidden" name="photo" id="photoInput" value="{{ old('photo') }}">
                            <input type="hidden" name="latitude" id="latInput" value="{{ old('latitude') }}">
                            <input type="hidden" name="longitude" id="lngInput" value="{{ old('longitude') }}">
                            <input type="hidden" name="accuracy" id="accInput" value="{{ old('accuracy') }}">

                            {{-- THUMBNAIL + QUICK ACTION --}}
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="rounded overflow-hidden border bg-light flex-shrink-0"
                                    style="width:72px;height:72px;">
                                    <img id="previewThumb" alt="Thumbnail"
                                        style="width:100%;height:100%;object-fit:cover;display:block;">
                                </div>

                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Foto sudah diambil</div>
                                    <div class="text-muted small">
                                        <span id="thumbLocText">GPS: -</span>
                                        <span class="mx-2">•</span>
                                        <span id="thumbAccText">Akurasi: -</span>
                                        <span class="mx-2">•</span>
                                        <span id="thumbFenceText">Radius: -</span>
                                    </div>
                                </div>

                                <button id="btnRetakeFromForm" type="button" class="btn btn-outline-secondary">
                                    Ulangi
                                </button>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold">Jenis Absensi</label>
                                <select name="type" class="form-select" required>
                                    <option value="" selected disabled>Pilih jenis absensi...</option>
                                    <option value="morning">
                                        Pagi
                                        @if ($morning)
                                            (Sudah: {{ $morning->attendance_at->format('H:i') }})
                                        @endif
                                    </option>
                                    <option value="afternoon">
                                        Malam
                                        @if ($afternoon)
                                            (Sudah: {{ $afternoon->attendance_at->format('H:i') }})
                                        @endif
                                    </option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Keterangan (opsional)</label>
                                <textarea name="notes" class="form-control" rows="3" maxlength="1000"
                                    placeholder="Contoh: terlambat karena hujan deras">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="btnSubmit">
                                Simpan Absensi
                            </button>

                            <div class="small text-muted mt-2">
                                GPS diambil otomatis. Jika GPS gagal, Anda tetap bisa submit (opsional sesuai kebijakan
                                validasi).
                            </div>
                        </form>
                    </div>
                </div>

                <div class="mt-3">
                    <a class="btn btn-outline-secondary w-100" href="{{ route('musyrif.absensi.history') }}">
                        Lihat Riwayat
                    </a>
                </div>

            </div>
        </div>
    </div>

    <script>
        (function() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');

            const cameraStage = document.getElementById('cameraStage');
            const previewStage = document.getElementById('previewStage');
            const formStage = document.getElementById('formStage');

            const btnShutter = document.getElementById('btnShutter');
            const btnUse = document.getElementById('btnUse');
            const btnRetakeFromPreview = document.getElementById('btnRetakeFromPreview');
            const btnRetakeFromForm = document.getElementById('btnRetakeFromForm');

            const locText = document.getElementById('locText');
            const accText = document.getElementById('accText');
            const camText = document.getElementById('camText');

            const previewFull = document.getElementById('previewFull');
            const previewThumb = document.getElementById('previewThumb');

            const thumbLocText = document.getElementById('thumbLocText');
            const thumbAccText = document.getElementById('thumbAccText');

            const photoInput = document.getElementById('photoInput');
            const latInput = document.getElementById('latInput');
            const lngInput = document.getElementById('lngInput');
            const accInput = document.getElementById('accInput');

            const geoFenceText = document.getElementById('geoFenceText');
            const thumbFenceText = document.getElementById('thumbFenceText');

            // Titik pusat lokasi setoran (Masjid Darut Taqwa putra)
            const GEOFENCE_CENTER = {
                lat: -7.8186683,
                lng: 111.5244092
            };

            // Radius meter (silakan ubah sesuai kebijakan)
            const GEOFENCE_RADIUS_M = 150;

            function haversineMeters(lat1, lon1, lat2, lon2) {
                const R = 6371000; // meter
                const toRad = (d) => d * Math.PI / 180;
                const dLat = toRad(lat2 - lat1);
                const dLon = toRad(lon2 - lon1);

                const a =
                    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);

                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            }

            let stream = null;

            function isMobileDevice() {
                return window.matchMedia("(max-width: 768px)").matches ||
                    /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
            }

            function formatCoord(lat, lng) {
                return `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }

            // ============ GEOLOCATION AUTO ============
            function initGeo() {
                if (!navigator.geolocation) {
                    locText.innerText = 'GPS: tidak didukung';
                    accText.innerText = '';
                    syncThumbOverlay();
                    return;
                }

                locText.innerText = 'GPS: memuat...';
                accText.innerText = '';

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const {
                            latitude,
                            longitude,
                            accuracy
                        } = pos.coords;

                        latInput.value = latitude;
                        lngInput.value = longitude;
                        accInput.value = accuracy;

                        locText.innerText = `GPS: ${formatCoord(latitude, longitude)}`;
                        accText.innerText = `Akurasi: ${Math.round(accuracy)} m`;

                        syncThumbOverlay();
                    },
                    (err) => {
                        console.error(err);
                        locText.innerText = 'GPS: gagal (cek izin lokasi)';
                        accText.innerText = '';
                        syncThumbOverlay();
                    }, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            }

            function syncThumbOverlay() {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                const acc = parseFloat(accInput.value);

                if (!isNaN(lat) && !isNaN(lng)) {
                    thumbLocText.innerText = `GPS: ${formatCoord(lat, lng)}`;
                } else {
                    thumbLocText.innerText = 'GPS: -';
                }

                if (!isNaN(acc)) {
                    thumbAccText.innerText = `Akurasi: ${Math.round(acc)} m`;
                } else {
                    thumbAccText.innerText = 'Akurasi: -';
                }

                // === Geofence UI ===
                if (!isNaN(lat) && !isNaN(lng)) {
                    const dist = Math.round(haversineMeters(lat, lng, GEOFENCE_CENTER.lat, GEOFENCE_CENTER.lng));
                    const inside = dist <= GEOFENCE_RADIUS_M;

                    const text =
                        `Radius: ${GEOFENCE_RADIUS_M} m • Jarak: ${dist} m • ${inside ? 'Dalam area' : 'Di luar area'}`;

                    // overlay camera
                    if (geoFenceText) geoFenceText.innerText = text;

                    // thumbnail
                    if (thumbFenceText) thumbFenceText.innerText = `Radius: ${GEOFENCE_RADIUS_M} m • Jarak: ${dist} m`;
                } else {
                    if (geoFenceText) geoFenceText.innerText = `Radius: ${GEOFENCE_RADIUS_M} m • Jarak: -`;
                    if (thumbFenceText) thumbFenceText.innerText = `Radius: ${GEOFENCE_RADIUS_M} m • Jarak: -`;
                }
            }

            // ============ CAMERA AUTO ============
            async function initCamera() {
                try {
                    camText.innerText = 'Meminta izin...';
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            facingMode: "user",
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

                    await new Promise((resolve) => {
                        if (video.readyState >= 1) return resolve();
                        video.onloadedmetadata = () => resolve();
                    });

                    camText.innerText = 'Siap';
                    btnShutter.disabled = false;
                } catch (err) {
                    console.error(err);
                    camText.innerText = 'Gagal (cek izin kamera)';
                    btnShutter.disabled = true;
                }
            }

            function stopCamera() {
                if (stream) {
                    stream.getTracks().forEach(t => t.stop());
                    stream = null;
                }
                video.srcObject = null;
            }

            // ============ CAPTURE ============
            function capture() {
                if (!video.videoWidth) {
                    alert('Kamera belum siap.');
                    return;
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;

                const ctx = canvas.getContext('2d');
                ctx.save();

                // balik horizontal (un-mirror)
                ctx.translate(canvas.width, 0);
                ctx.scale(-1, 1);

                // gambar video ke canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                ctx.restore();


                const dataUrl = canvas.toDataURL('image/jpeg', 0.85);

                photoInput.value = dataUrl;
                previewFull.src = dataUrl;
                previewThumb.src = dataUrl;

                // tampilkan preview full
                cameraStage.classList.add('d-none');
                previewStage.classList.remove('d-none');

                // form belum tampil sebelum "Gunakan"
                formStage.classList.add('d-none');

                syncThumbOverlay();
            }

            function retake() {
                photoInput.value = '';
                previewFull.src = '';
                previewThumb.src = '';

                formStage.classList.add('d-none');
                previewStage.classList.add('d-none');

                cameraStage.classList.remove('d-none');
                syncThumbOverlay();
            }

            function usePhoto() {
                // form tampil dengan thumbnail
                previewStage.classList.add('d-none');
                formStage.classList.remove('d-none');

                // Kamera tetap jalan sesuai requirement Anda (stream dibiarkan hidup).
                // Jika nanti ingin hemat resource, tinggal panggil stopCamera() di sini.
                syncThumbOverlay();
            }

            function lockScroll(lock) {
                document.body.classList.toggle('no-scroll', !!lock);
            }

            cameraStage.addEventListener('click', () => {
                requestFullscreenSafe(document.documentElement);
            }, {
                passive: true
            });

            btnRetakeFromPreview.addEventListener('click', retake);
            btnRetakeFromForm.addEventListener('click', retake);
            btnUse.addEventListener('click', usePhoto);

            async function requestFullscreenIfMobile(el) {
                if (!isMobileDevice()) return;

                try {
                    if (!document.fullscreenElement) {
                        if (el.requestFullscreen) await el.requestFullscreen();
                        else if (el.webkitRequestFullscreen) await el.webkitRequestFullscreen();
                    }
                } catch (_) {}
            }

            async function requestFullscreenSafe(el) {
                try {
                    // pilih container, bukan video (lebih konsisten)
                    if (document.fullscreenElement) return;
                    if (el.requestFullscreen) await el.requestFullscreen();
                    else if (el.webkitRequestFullscreen) await el.webkitRequestFullscreen(); // Safari
                } catch (e) {
                    // silently ignore (iOS kadang restriktif)
                }
            }

            btnShutter.addEventListener('click', async () => {
                await requestFullscreenSafe(document.documentElement); // atau cameraStage
                capture();
            });

            btnShutter.addEventListener('click', async () => {
                if (isMobileDevice()) {
                    await requestFullscreenIfMobile(document.documentElement);
                    document.body.classList.add('no-scroll');
                    haptic();
                }
                capture();
            });

            function haptic(ms = 30) {
                if (!isMobileDevice()) return;
                if (navigator.vibrate) navigator.vibrate(ms);
            }

            // INIT
            initGeo();
            initCamera();

            window.addEventListener('beforeunload', stopCamera);
        })();
    </script>
@endsection
