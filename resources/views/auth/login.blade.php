@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <style>
        /* Background full screen */
        body {
            background: url('{{ asset('assets/background.webp') }}') no-repeat center center fixed;
            background-size: cover;
        }

        /* Card transparency effect */
        .login-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(4px);
            border-radius: 12px;
        }

        .login-logo {
            width: 90px;
            height: auto;
        }
    </style>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm login-card border">
                <div class="card-body p-4">

                    <!-- LOGO -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('assets/logos-primary.png') }}" alt="Logo" class="login-logo">
                    </div>

                    <!-- TITLE -->
                    <h1 class="h3 mb-2 text-center fw-bold">Selamat Datang di</h1>
                    <h5 class="text-medium-emphasis text-center mb-4">
                        Sistem Informasi Hafalan Santri
                    </h5>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- EMAIL -->
                        <div class="mb-3">
                            <label class="form-label">Email atau Nomor</label>
                            <input id="login" type="text" class="form-control @error('login') is-invalid @enderror"
                                name="login" value="{{ old('login') }}" placeholder="Masukkan Email atau Nomor..."
                                required autofocus>
                        </div>

                        <!-- PASSWORD -->
                        <div class="mb-3">
                            <label class="form-label">Password</label>

                            <div class="position-relative">
                                <input id="password" type="password"
                                    class="form-control pe-5 @error('password') is-invalid @enderror" name="password"
                                    placeholder="Masukkan Password..." required autocomplete="current-password">

                                <button type="button" id="togglePassword"
                                    class="btn btn-link p-0 position-absolute top-50 translate-middle-y"
                                    style="right: 1rem;" aria-label="Toggle password">
                                    <i class="bi bi-eye fs-5"></i>
                                </button>
                            </div>
                        </div>

                        <!-- REMEMBER -->
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">
                                Ingat saya
                            </label>
                        </div>

                        <!-- SUBMIT -->
                        <div class="d-grid">
                            <button class="btn btn-primary" type="submit">
                                Masuk
                            </button>
                        </div>

                        <!-- LINKS -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            @if (Route::has('password.request'))
                                <a class="small" href="{{ route('password.request') }}" style="text-decoration: none">
                                    Lupa password?
                                </a>
                            @endif

                            @if (Route::has('register'))
                                <a class="small" href="{{ route('register') }}" style="text-decoration: none">
                                    Daftar
                                </a>
                            @endif
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Toggle Password
            const toggleBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const icon = toggleBtn.querySelector('i');

            toggleBtn.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });

            // SweetAlert on submit
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang login',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                });
            }

        });
    </script>
@endpush
