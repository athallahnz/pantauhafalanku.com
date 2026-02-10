@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
    <style>
        body {
            background: url('{{ asset('assets/background.webp') }}') no-repeat center center fixed;
            background-size: cover;
        }

        .forgot-card {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(4px);
            border-radius: 12px;
        }

        .auth-logo {
            width: 90px;
            height: auto;
        }
    </style>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm forgot-card">
                <div class="card-body p-4">

                    <!-- LOGO -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('assets/logos-primary.png') }}" class="auth-logo" alt="Logo">
                    </div>

                    <h1 class="h3 mb-2 text-center fw-bold">Lupa Password</h1>
                    <h6 class="text-medium-emphasis text-center mb-4">
                        Masukkan email Anda untuk menerima link reset password.
                    </h6>

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" placeholder="Masukkan Email..." required autofocus>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary" type="submit">
                                Kirim Link Reset
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <a class="small" href="{{ route('login') }}" style="text-decoration: none">Kembali</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
