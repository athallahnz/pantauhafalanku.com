@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="card">
            <div class="card-header">
                <strong>Profil Saya</strong>
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('profile.settings.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="text-center mb-3">
                        <img src="{{ $profile?->photo ? asset('storage/' . $profile->photo) : asset('images/default-avatar.png') }}"
                            class="rounded-circle mb-2" width="120" height="120">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Profil</label>
                        <input type="file" name="photo" class="form-control" placeholder="Pilih foto profil...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name"
                            value="{{ old('full_name', $profile->full_name ?? auth()->user()->name) }}"
                            class="form-control" placeholder="Masukkan nama lengkap...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" value="{{ auth()->user()->email }}" class="form-control" disabled placeholder="Masukkan email...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor HP</label>
                        <input type="text" name="phone"
                            value="{{ old('phone', $profile->phone ?? auth()->user()->nomor) }}" class="form-control" placeholder="Masukkan nomor HP...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="3" placeholder="Masukkan alamat...">{{ old('address', $profile->address ?? '') }}</textarea>
                    </div>

                    <div class="text-end">
                        <button type="reset" class="btn btn-secondary me-2" onclick="history.back()">
                            <i class="bi bi-x-circle"></i> Batal
                        </button>
                        <button class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Profil
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection
