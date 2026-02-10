@extends('layouts.app')

@section('content')
    <style>
        .logo-upload-wrapper {
            position: relative;
            isolation: isolate;
        }

        .logo-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .logo-upload-box {
            border: 2px dashed #cfd8dc;
            border-radius: 12px;
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: #fff;
            padding: 20px;
            cursor: pointer;
            transition:
                border-color 0.25s ease,
                background-color 0.25s ease,
                box-shadow 0.25s ease,
                transform 0.15s ease;
        }

        .logo-upload-box:hover {
            border-color: #321fdb;
            background-color: #f8f9ff;
            box-shadow: 0 0 0 4px rgba(50, 31, 219, 0.08);
        }

        .logo-upload-box:active {
            transform: scale(0.995);
        }

        .logo-placeholder i {
            font-size: 40px;
            color: #6c757d;
            transition: all 0.25s ease;
        }

        .logo-upload-box:hover .logo-placeholder i {
            color: #321fdb;
            transform: translateY(-2px);
        }

        .logo-upload-box:hover .logo-placeholder {
            color: #321fdb;
        }

        .logo-preview-img {
            max-height: 120px;
            max-width: 100%;
            object-fit: contain;
        }

        .profile-preview-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #e9ecef;
        }
    </style>

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

                    <div class="mb-4">
                        <label class="form-label fw-semibold d-flex align-items-center gap-2">
                            <i class="cil-user"></i> Foto Profil
                        </label>

                        <div class="logo-upload-wrapper">
                            <input type="file" name="photo" id="photoInput" accept="image/*"
                                class="logo-input @error('photo') is-invalid @enderror">

                            <div class="logo-upload-box" id="photoPreviewBox">

                                @if (!empty($profile?->photo))
                                    <img src="{{ asset('storage/' . $profile->photo) }}" alt="Foto Profil"
                                        class="profile-preview-img">
                                @else
                                    <div class="logo-placeholder">
                                        <i class="cil-cloud-upload"></i>
                                        <div class="mt-2 fw-semibold">Klik untuk upload foto profil</div>
                                        <small class="text-muted">
                                            PNG, JPG, WebP (Maks. 2MB)
                                        </small>
                                    </div>
                                @endif

                            </div>
                        </div>

                        @error('photo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name"
                            value="{{ old('full_name', $profile->full_name ?? auth()->user()->name) }}" class="form-control"
                            placeholder="Masukkan nama lengkap...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" value="{{ auth()->user()->email }}" class="form-control" disabled
                            placeholder="Masukkan email...">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nomor HP</label>
                        <input type="text" name="phone"
                            value="{{ old('phone', $profile->phone ?? auth()->user()->nomor) }}" class="form-control"
                            placeholder="Masukkan nomor HP...">
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

@push('scripts')
    <script>
        const photoInput = document.getElementById('photoInput');
        const photoBox = document.getElementById('photoPreviewBox');

        if (photoInput) {
            photoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    alert('File harus berupa gambar');
                    photoInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    photoBox.innerHTML = `
                    <img
                        src="${e.target.result}"
                        class="profile-preview-img"
                        alt="Preview Foto Profil"
                    >
                `;
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
@endpush
