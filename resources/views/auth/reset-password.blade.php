@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <style>
        body {
            background: url('{{ asset('assets/background.webp') }}') no-repeat center center fixed;
            background-size: cover;
        }

        .reset-card {
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
            <div class="card shadow-sm reset-card">
                <div class="card-body p-4">

                    <!-- Logo -->
                    <div class="text-center mb-3">
                        <img src="{{ asset('assets/logos-primary.png') }}" class="auth-logo" alt="Logo">
                    </div>

                    <h1 class="h3 mb-3 text-center">Reset Password</h1>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">
                        <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror" name="password" required
                                autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input id="password_confirmation" type="password" class="form-control"
                                name="password_confirmation" required>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary" type="submit">
                                Simpan Password
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
