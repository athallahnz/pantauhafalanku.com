<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Sistem Informasi Hafalan Santri')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistem Informasi Hafalan Santri">
    <meta name="author" content="AnzArt Studio">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- CoreUI CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@coreui/icons/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    {{-- Vite --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    {{-- DataTables CSS & JS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    @php
        /** @var \App\Models\User|null $user */
        /** @var \App\Models\InstitutionSetting|null $institution */
        $user = auth()->user();
        $role = $user->role ?? null;

        // FIX: definisikan profile DI SINI
        $profile = $user?->profileSetting ?? null;
        $institution = \App\Models\InstitutionSetting::first();

    @endphp

    {{-- =============== SIDEBAR (PERSIS POLA COREUI) =============== --}}
    <div class="sidebar sidebar-dark sidebar-narrow-unfoldable border-end" id="sidebar">
        <div
            class="sidebar-header border-bottom border-white d-none d-md-flex align-items-center justify-content-center px-3 py-4">
            <div class="sidebar-brand-logo">
                <img src="{{ !empty($institution?->logo) ? asset('storage/' . $institution->logo) : asset('assets/logos.png') }}"
                    alt="Logo Lembaga" class="sidebar-logo-img">
            </div>
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

                @default
                    <li class="nav-title">Menu</li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ url('/') }}">
                            <i class="nav-icon cil-home"></i> Beranda
                        </a>
                    </li>
            @endswitch
        </ul>
    </div>

    {{-- =============== WRAPPER (KONTEN KANAN) =============== --}}
    <div class="wrapper d-flex flex-column min-vh-100 bg-light">

        {{-- HEADER --}}
        <header class="header header-sticky py-3">
            <div class="container-fluid">
                {{-- tombol buka/tutup sidebar (MOBILE ONLY) --}}
                <button class="header-toggler px-md-0 me-md-3 d-lg-none" type="button" data-coreui-toggle="sidebar"
                    data-coreui-target="#sidebar">
                    <i class="icon icon-lg cil-menu"></i>
                </button>

                @php
                    $dashboardRoute = match ($role) {
                        'superadmin' => 'superadmin.dashboard',
                        'admin' => 'admin.dashboard',
                        'musyrif' => 'musyrif.dashboard',
                        'santri' => 'santri.dashboard',
                        default => null,
                    };
                @endphp

                @if ($dashboardRoute)
                    <a class="header-brand d-md-none" href="{{ route($dashboardRoute) }}">
                        <span class="fw-bold">SI Hafalan Santri</span>
                    </a>
                @else
                    <a class="header-brand d-md-none" href="{{ url('/') }}">
                        <span class="fw-bold">SI Hafalan Santri</span>
                    </a>
                @endif

                <ul class="header-nav ms-auto">

                    @php
                        $isAdmin = in_array($user->role, ['admin', 'superadmin']);
                    @endphp

                    <li class="nav-item dropdown d-none d-md-flex">
                        <a class="nav-link py-0 d-flex align-items-center" data-coreui-toggle="dropdown" href="#"
                            role="button">

                            @php
                                $name = $user->name ?? 'Guest';
                                $initial = strtoupper(mb_substr($name, 0, 1));

                                $colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-dark'];
                                $color = $colors[ord($initial) % count($colors)];
                            @endphp

                            @if (!empty($profile?->photo))
                                <img src="{{ asset('storage/' . $profile->photo) }}" class="rounded-circle me-2"
                                    width="32" height="32" alt="Foto Profil">
                            @else
                                <div class="rounded-circle {{ $color }} text-white d-flex align-items-center justify-content-center me-2"
                                    style="width:32px; height:32px; font-size:0.85rem; font-weight:600;"
                                    title="{{ $name }}">
                                    {{ $initial }}
                                </div>
                            @endif


                            <span class="fw-semibold">{{ $user->name ?? 'Guest' }}</span>
                        </a>

                        <div class="dropdown-menu dropdown-menu-end pt-0">

                            <div class="dropdown-header bg-light py-2">
                                <strong>{{ $user->name }}</strong><br>
                                <small class="text-muted">{{ ucfirst($user->role ?? '-') }}</small>
                            </div>

                            <div class="dropdown-divider"></div>

                            {{-- SETTINGS --}}
                            @if ($isAdmin)
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('admin.settings.institution') }}">
                                    <i class="cil-settings me-2"></i> Setting Lembaga
                                </a>
                            @endif

                            <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.settings') }}">
                                <i class="cil-user me-2"></i> Setting Profil
                            </a>

                            <div class="dropdown-divider"></div>

                            {{-- LOGOUT --}}
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item d-flex align-items-center" type="submit">
                                    <i class="cil-account-logout me-2"></i> Logout
                                </button>
                            </form>

                        </div>
                    </li>

                    <!-- ===========================
                    MOBILE — Avatar Only
                    =========================== -->
                    <li class="nav-item dropdown d-flex d-md-none">
                        <a class="nav-link py-0 d-flex align-items-center" data-coreui-toggle="dropdown" href="#"
                            role="button">

                            <img src="{{ $profile?->photo ? asset('storage/' . $profile->photo) : asset('images/default-avatar.png') }}"
                                class="avatar rounded-circle mb-2" width="120" height="120">
                        </a>

                        <div class="dropdown-menu dropdown-menu-end pt-0">

                            <div class="dropdown-header bg-light py-2">
                                <strong>{{ $user->name }}</strong><br>
                                <small class="text-muted">{{ ucfirst($user->role ?? '-') }}</small>
                            </div>

                            <div class="dropdown-divider"></div>

                            {{-- SETTINGS --}}
                            @if ($isAdmin)
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('admin.settings.institution') }}">
                                    <i class="cil-settings me-2"></i> Setting Lembaga
                                </a>
                            @endif

                            <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.settings') }}">
                                <i class="cil-user me-2"></i> Setting Profil
                            </a>

                            <div class="dropdown-divider"></div>

                            {{-- LOGOUT --}}
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button class="dropdown-item d-flex align-items-center" type="submit">
                                    <i class="cil-account-logout me-2"></i> Logout
                                </button>
                            </form>

                        </div>
                    </li>

                </ul>
            </div>
        </header>

        {{-- CONTENT --}}
        <div class="body flex-grow-1 px-3 main-content-bg">
            <div class="container-lg">

                <div class="content-card p-4 position-relative mt-4">
                    <div class="corner-ornament"></div>
                    @yield('content')
                </div>

            </div>
        </div>

        {{-- FOOTER --}}
        <footer class="footer">

            {{-- DESKTOP --}}
            <div class="d-none d-md-flex w-100 justify-content-between">
                <div>© {{ date('Y') }} Sistem Informasi Hafalan Santri</div>
                <div class="ms-auto">
                    Development by <span class="fw-semibold">AnzArt Studio</span>
                </div>
            </div>

            {{-- MOBILE --}}
            <div class="d-flex d-md-none w-100 justify-content-center text-center">
                © {{ date('Y') }} SI Hafalan Santri by&nbsp;<span class="fw-semibold">AnzArt Studio</span>
            </div>

        </footer>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            @if (session('success'))
                AppAlert && AppAlert.success(@json(session('success')));
            @endif

            @if (session('error'))
                AppAlert && AppAlert.error(@json(session('error')));
            @endif

            @if ($errors->any())
                AppAlert && AppAlert.error(
                    {!! json_encode(implode("\n", $errors->all())) !!},
                    'Validasi Gagal'
                );
            @endif

            // ================= COUNT UP (FINAL) =================
            function easeOut(t) {
                return 1 - Math.pow(1 - t, 3);
            }

            function runCounter(counter, target) {
                const start = parseInt(counter.textContent.replace(/\D/g, '')) || 0;
                if (start === target) return;

                const duration = 1200;
                const frameRate = 30;
                const totalFrames = Math.round(duration / (1000 / frameRate));
                let frame = 0;

                const interval = setInterval(() => {
                    frame++;
                    const progress = frame / totalFrames;
                    const current = Math.round(start + (target - start) * easeOut(progress));

                    counter.textContent = current.toLocaleString('id-ID');

                    if (frame >= totalFrames) {
                        counter.textContent = target.toLocaleString('id-ID');
                        clearInterval(interval);
                    }
                }, 1000 / frameRate);
            }

            // 🚀 INI YANG TADI KURANG
            document.querySelectorAll('.count-up').forEach(el => {
                runCounter(el, parseInt(el.dataset.target) || 0);
            });

        });
    </script>


    {{-- Additional Scripts --}}
    @stack('scripts')

    {{-- Modal --}}
    @stack('modals')
</body>

</html>
