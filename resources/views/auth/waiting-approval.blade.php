@extends('layouts.auth')

@section('title', 'Menunggu Persetujuan')

@section('content')
    <style>
        #aurora-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: #000;
        }

        .glass-pane {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(25px) saturate(200%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>

    <div id="aurora-bg"></div>

    <div class="row justify-content-center align-items-center min-vh-100 w-100 m-0" style="position: relative; z-index: 10;">
        <div class="col-md-6 col-lg-4 px-4 text-center">
            <div class="card glass-pane border-0 p-4">
                <div class="card-body">
                    <div class="mb-4">
                        <i class="bi bi-clock-history text-white display-1 opacity-75"></i>
                    </div>

                    <h2 class="h4 fw-bold text-white mb-3">Registrasi Berhasil!</h2>
                    <p class="text-white-50 small mb-4">
                        Akun Anda telah terdaftar di sistem. Mohon tunggu proses validasi oleh <strong>Admin
                            Department</strong> sebelum Anda dapat mengakses dashboard.
                    </p>

                    <div class="d-grid">
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg rounded-4 fw-bold py-3"
                            style="background: linear-gradient(135deg, #6f42c1, #4b2291); border: none;">
                            Kembali ke Login
                        </a>
                    </div>
                </div>
            </div>
            <p class="text-white-50 small mt-4">&copy; 2026 - AnzArt Studio</p>
        </div>
    </div>
@endsection
