<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Login Sistem Informasi Hafalan Santri')</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Laravel App">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
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

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body class="bg-light d-flex flex-row align-items-center min-vh-100">
    <div class="container">
        @yield('content')
    </div>
    @stack('script')
</body>

</html>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        @if (session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                timer: 2000,
                showConfirmButton: false
            });
        @endif

        @if ($errors->has('login'))
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: '{{ $errors->first('login') }}'
            });
        @endif

        @if ($errors->has('password'))
            Swal.fire({
                icon: 'error',
                title: 'Login Gagal',
                text: '{{ $errors->first('password') }}'
            });
        @endif

    });
</script>
