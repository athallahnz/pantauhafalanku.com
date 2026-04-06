<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="author" content="AnzArt Studio">
    <meta name="description" content="Sistem Informasi Hafalan Santri">

    <title>@yield('title', 'Sistem Informasi Hafalan Santri')</title>

    {{-- PWA & Mobile Optimization --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes"> {{-- Versi standar terbaru --}}
    <meta name="apple-mobile-web-app-title" content="SI Hafalan">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">

    {{-- Third Party CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    {{-- Vite Assets (CSS & JS Bundler) --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    {{-- Essential Vendor JS (JQuery Harus di Atas jika script halaman lain membutuhkannya segera) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    {{-- Plugin JS (Bisa ditaruh di sini atau sebelum </body> untuk speed) --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js" defer></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js" defer></script>
</head>

<body>
    <style>
        /* Global Preloader Styling */
        #global-loader {
            position: fixed;
            z-index: 99999;
            /* Sangat tinggi agar di atas sidebar/header */
            background: rgba(255, 255, 255, 0.85);
            /* Putih transparan */
            backdrop-filter: blur(8px);
            /* Efek blur kaca */
            -webkit-backdrop-filter: blur(8px);
            height: 100%;
            width: 100%;
            top: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition:
                opacity 0.4s ease,
                visibility 0.4s ease;
            visibility: visible;
            opacity: 1;
        }

        /* Transition saat loader disembunyikan */
        #global-loader.loader-hidden {
            opacity: 0;
            visibility: hidden;
        }

        /* Dark Mode Support untuk Loader */
        [data-coreui-theme="dark"] #global-loader {
            background: rgba(30, 30, 30, 0.85);
        }

        .loader-content {
            text-align: center;
        }
    </style>
    <div id="global-loader">
        <div class="loader-content">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 fw-bold text-adaptive-purple">Mohon Tunggu...</p>
        </div>
    </div>
    @php
        /** @var \App\Models\User|null $user */
        /** @var \App\Models\InstitutionSetting|null $institution */
        $user = auth()->user();
        $role = $user->role ?? null;
        $profile = $user?->profileSetting ?? null;
        $institution = \App\Models\InstitutionSetting::first();
    @endphp

    {{-- SIDEBAR --}}
    <div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
        <div class="sidebar-header border-bottom border-white d-flex justify-content-center py-4 sidebar-brand-logo">
            <img src="{{ !empty($institution?->logo) ? asset('storage/' . $institution->logo) : asset('assets/logos.png') }}"
                alt="Logo Institusi" class="img-fluid">
        </div>

        <ul class="sidebar-nav" data-coreui="navigation">
            @switch($role)
                @case('superadmin')
                    @include('layouts.partials.sidebar-superadmin')
                @break

                @case('admin')
                    @include('layouts.partials.sidebar-admin')
                @break

                @case('musyrif')
                    @include('layouts.partials.sidebar-musyrif')
                @break

                @case('santri')
                    @include('layouts.partials.sidebar-santri')
                @break
            @endswitch
        </ul>
    </div>

    {{-- WRAPPER --}}
    <div class="wrapper d-flex flex-column min-vh-100">

        {{-- HEADER --}}
        <header class="header header-sticky px-3 py-3 border-bottom">
            <button class="header-toggler me-3" type="button" id="custom-sidebar-toggler">
                <i class="icon icon-lg bi bi-list fs-3"></i>
            </button>

            <div class="ms-auto d-flex align-items-center gap-3">
                {{-- Theme Toggle iOS Style --}}
                <button id="themeToggle" class="theme-switch-btn" type="button" aria-label="Toggle Theme">
                    <div class="theme-switch-track rounded-pill">
                        <i class="bi bi-sun icon-light text-warning"></i>
                        <i class="bi bi-moon-stars icon-dark"></i>
                        <div class="theme-switch-thumb"></div>
                    </div>
                </button>
                {{-- User Dropdown --}}
                <div class="dropdown">
                    <a class="nav-link d-flex align-items-center py-0" data-coreui-toggle="dropdown" href="#"
                        role="button" aria-haspopup="true" aria-expanded="false">

                        @if (!empty($profile?->photo))
                            <img src="{{ asset('storage/' . $profile->photo) }}"
                                class="rounded-circle me-2 object-fit-cover shadow-sm" width="36" height="36"
                                alt="User Photo">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2 fw-bold shadow-sm"
                                style="width:36px; height:36px;">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        <span class="d-none d-md-inline fw-medium">{{ $user->name ?? 'Guest' }}</span>
                    </a>

                    {{-- Class mt-2 boleh dihapus karena sudah diatur oleh offset di atas --}}
                    <div class="dropdown-menu dropdown-menu-end pt-0 shadow-lg dropdown-animated">
                        <div class="dropdown-header bg-light py-2 rounded-top">
                            <div class="fw-semibold">Akun Saya</div>
                        </div>
                        <a class="dropdown-item py-2" href="{{ route('profile.settings') }}">
                            <i class="bi bi-person me-2"></i> Setting Profil
                        </a>
                        <div class="dropdown-divider my-0"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger py-2">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- MAIN CONTENT --}}
        <div class="body flex-grow-1 px-3 main-content-bg">
            <div class="container-fluid">
                <div class="content-card p-4 mt-4 mb-4">
                    @yield('content')
                </div>
            </div>
        </div>

        {{-- FOOTER --}}
        <footer
            class="footer border-top d-flex flex-column flex-sm-row align-items-center justify-content-center justify-content-sm-between gap-2">
            <div class="text-center text-sm-start small order-2 order-sm-1">
                <span class="text-muted">© {{ date('Y') }}</span>
                <span class="d-none d-md-inline fw-semibold text-muted ms-1">Sistem Informasi Hafalan Santri</span>
                <span class="d-inline d-md-none fw-semibold text-muted ms-1">SI Hafalan</span>
            </div>

            <div class="small order-1 order-sm-2 mb-0">
                <span class="text-muted">Created by</span>
                <span class="fw-bold text-adaptive-purple ms-1" style="letter-spacing: 0.5px;">AnzArt Studio</span>
            </div>
        </footer>
    </div>

    {{-- SCRIPTS --}}
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const loader = document.getElementById('global-loader');

            function hideLoader() {
                if (loader) {
                    loader.classList.add('loader-hidden');
                }
            }

            function showLoader() {
                if (loader) {
                    loader.classList.remove('loader-hidden');
                }
            }

            // 1. Sembunyikan loader saat load awal
            if (document.readyState === 'complete') {
                hideLoader();
            } else {
                window.addEventListener('load', hideLoader);
            }

            // 2. Tampilkan loader saat user navigasi pergi
            // Gunakan pengecekan agar tidak muncul pada link internal (#) atau download
            window.addEventListener('beforeunload', function() {
                showLoader();
            });

            // 3. FIX: Sembunyikan loader jika user kembali via tombol BACK browser
            window.addEventListener('pageshow', function(event) {
                // event.persisted bernilai true jika halaman dimuat dari cache (tombol back)
                if (event.persisted) {
                    hideLoader();
                }
            });

            // 3. Integrasi dengan AJAX (DataTables/Proses Simpan)
            $(document).ajaxStart(function() {
                // Jika Mas ingin loader muncul setiap kali ada AJAX request berat
                // Tapi disarankan hanya untuk proses yang memakan waktu lama
            }).ajaxStop(function() {
                // Sembunyikan kembali
            });

            /* =========================================================
            THEME TOGGLE LOGIC (DARK/LIGHT MODE)
            ========================================================= */
            const html = document.documentElement;
            const themeToggle = document.getElementById("themeToggle");

            function applyTheme(theme) {
                // Ini yang bertugas memberi tahu browser dan CoreUI untuk ganti warna
                html.setAttribute("data-coreui-theme", theme);
            }

            // Cek memori browser, apakah sebelumnya user pakai mode gelap?
            let theme = localStorage.getItem("theme");
            if (!theme) {
                theme = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            applyTheme(theme);

            // Jika tombol diklik, ganti tema
            if (themeToggle) {
                themeToggle.addEventListener("click", function(e) {
                    e.preventDefault(); // Mencegah halaman refresh
                    theme = html.getAttribute("data-coreui-theme") === "dark" ? "light" : "dark";
                    applyTheme(theme);
                    localStorage.setItem("theme", theme); // Simpan pilihan user
                });
            }

            /* =========================================================
            SIDEBAR COLLAPSE SYSTEM
            ========================================================= */
            const sidebar = document.getElementById("sidebar");
            const body = document.body;
            const sidebarToggle = document.getElementById("custom-sidebar-toggler");

            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener("click", function(e) {
                    e.stopPropagation(); // Mencegah event klik langsung menjalar

                    if (window.innerWidth < 992) {
                        body.classList.toggle("sidebar-open");
                    } else {
                        sidebar.classList.toggle("sidebar-narrow");
                        localStorage.setItem("sidebar-collapse", sidebar.classList.contains(
                            "sidebar-narrow"));
                    }
                });

                // Fitur tutup otomatis jika layar disentuh di luar sidebar (khusus mobile)
                document.addEventListener("click", function(e) {
                    if (window.innerWidth < 992 && body.classList.contains("sidebar-open")) {
                        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                            body.classList.remove("sidebar-open");
                        }
                    }
                });

                /* Restore state untuk desktop */
                if (localStorage.getItem("sidebar-collapse") === "true" && window.innerWidth >= 992) {
                    sidebar.classList.add("sidebar-narrow");
                }
            }

            /* Alert Handler (Laravel Session) */
            if (typeof AppAlert !== "undefined") {
                @if (session('success'))
                    AppAlert.success(@json(session('success')));
                @endif
                @if (session('error'))
                    AppAlert.error(@json(session('error')));
                @endif
                @if ($errors->any())
                    AppAlert.error(
                        {!! json_encode(implode("\n", $errors->all())) !!},
                        "Validasi Gagal"
                    );
                @endif
            }

            /* Count Up Animation */
            function easeOut(t) {
                return 1 - Math.pow(1 - t, 3);
            }

            function runCounter(counter, target) {
                const start = parseInt(counter.textContent.replace(/\D/g, "")) || 0;
                if (start === target) return;

                const duration = 1200;
                const frameRate = 30;
                const totalFrames = Math.round(duration / (1000 / frameRate));
                let frame = 0;

                const interval = setInterval(() => {
                    frame++;
                    const progress = frame / totalFrames;
                    const current = Math.round(start + (target - start) * easeOut(progress));
                    counter.textContent = current.toLocaleString("id-ID");

                    if (frame >= totalFrames) {
                        counter.textContent = target.toLocaleString("id-ID");
                        clearInterval(interval);
                    }
                }, 1000 / frameRate);
            }

            document.querySelectorAll(".count-up").forEach(el => {
                runCounter(el, parseInt(el.dataset.target) || 0);
            });
        });
    </script>

    @stack('scripts')
    @stack('modals')
</body>

</html>
