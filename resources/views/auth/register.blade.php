@extends('layouts.auth')

@section('title', 'Register')

@section('content')
    <style>
        /* Background full screen */
        body {
            background: url('{{ asset('assets/background.webp') }}') no-repeat center center fixed;
            background-size: cover;
        }

        /* Card transparency */
        .register-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(4px);
            border-radius: 12px;
        }

        .register-logo {
            width: 90px;
            height: auto;
        }
    </style>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm register-card border">
                <div class="card-body p-4">

                    <!-- LOGO -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('assets/logos-primary.png') }}" alt="Logo" class="register-logo">
                    </div>

                    <h1 class="h3 mb-2 text-center fw-bold">Silahkan Daftar</h1>
                    <h5 class="text-medium-emphasis text-center mb-4">
                        untuk membuat akun baru.
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

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name') }}" placeholder="Masukkan Nama..." required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" placeholder="Masukkan E-Mail..." required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror" name="password" required
                                autocomplete="new-password" placeholder="Masukkan Password Baru...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input id="password_confirmation" type="password" class="form-control"
                                name="password_confirmation" placeholder="Masukkan Ulang Password Baru..." required>
                        </div>

                        {{-- Default role santri --}}
                        <input type="hidden" name="role" value="santri">

                        <div class="d-grid">
                            <button class="btn btn-primary" type="submit">
                                Daftar
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <span class="small">
                                Sudah punya akun? <a href="{{ route('login') }}" style="text-decoration: none">Masuk di sini!</a>
                            </span>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
