@extends('layouts.app')

@section('title', 'Pengaturan Profil')

@section('content')
    <style>
        /* ================= AREA UPLOAD FOTO ADAPTIF ================= */
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
            width: 100%;
            height: 100%;
        }

        .logo-upload-box {
            border: 2px dashed var(--cui-border-color);
            border-radius: 16px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background-color: var(--cui-body-bg);
            /* Adaptif dark/light */
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logo-upload-box:hover {
            border-color: var(--islamic-purple-500);
            background-color: rgba(107, 78, 255, 0.05);
        }

        .logo-upload-box:active {
            transform: scale(0.99);
        }

        .logo-placeholder i {
            font-size: 48px;
            color: var(--cui-secondary-color);
            transition: all 0.3s ease;
        }

        .logo-upload-box:hover .logo-placeholder i {
            color: var(--islamic-purple-600);
            transform: translateY(-4px);
        }

        .logo-upload-box:hover .logo-placeholder .text-muted {
            color: var(--islamic-purple-500) !important;
        }

        .profile-preview-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--islamic-purple-100);
            padding: 4px;
            background: var(--cui-card-bg);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        /* Penyesuaian khusus Dark Mode untuk foto */
        [data-coreui-theme="dark"] .profile-preview-img {
            border-color: var(--islamic-purple-700);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
    </style>

    {{-- HEADER TITLE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Pengaturan Profil</h4>
            <span class="text-muted small">Kelola informasi pribadi dan foto profil Anda</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col mx-auto">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent py-3 fw-semibold d-flex align-items-center">
                    <i class="bi bi-person-lines-fill fs-5 me-2" style="color: var(--islamic-purple-500);"></i> Detail Profil
                </div>

                <div class="card-body p-4">

                    @if (session('success'))
                        <div class="alert alert-success d-flex align-items-center rounded-3" role="alert">
                            <i class="bi bi-check-circle-fill flex-shrink-0 me-2 fs-5"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                    @endif

                    <form action="{{ route('profile.settings.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        {{-- FOTO PROFIL --}}
                        <div class="mb-4 text-center">
                            <label
                                class="form-label fw-semibold d-flex align-items-center justify-content-center gap-2 mb-3">
                                Foto Profil
                            </label>

                            <div class="logo-upload-wrapper mx-auto" style="max-width: 400px;">
                                <input type="file" name="photo" id="photoInput" accept="image/*"
                                    class="logo-input @error('photo') is-invalid @enderror">

                                <div class="logo-upload-box" id="photoPreviewBox">
                                    @if (!empty($profile?->photo))
                                        <img src="{{ asset('storage/' . $profile->photo) }}" alt="Foto Profil"
                                            class="profile-preview-img">
                                    @else
                                        <div class="logo-placeholder">
                                            <i class="bi bi-cloud-arrow-up-fill"></i>
                                            <div class="mt-2 fw-semibold">Klik atau seret foto ke sini</div>
                                            <small class="text-muted">PNG, JPG, WebP (Maks. 2MB)</small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @error('photo')
                                <div class="text-danger mt-2 small">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr class="my-4" style="opacity: 0.1;">

                        {{-- FORM DATA --}}
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-muted small text-uppercase">Nama Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted"><i
                                            class="bi bi-person"></i></span>
                                    <input type="text" name="full_name"
                                        value="{{ old('full_name', $profile->full_name ?? auth()->user()->name) }}"
                                        class="form-control" placeholder="Masukkan nama lengkap...">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-muted small text-uppercase">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted"><i
                                            class="bi bi-envelope"></i></span>
                                    <input type="email" value="{{ auth()->user()->email }}"
                                        class="form-control text-muted" disabled placeholder="Masukkan email..."
                                        style="background-color: var(--cui-secondary-bg);">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-medium text-muted small text-uppercase">Nomor HP</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted"><i
                                            class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone"
                                        value="{{ old('phone', $profile->phone ?? auth()->user()->nomor) }}"
                                        class="form-control" placeholder="Contoh: 08123456789">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-medium text-muted small text-uppercase">Alamat Lengkap</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent text-muted align-items-start pt-2"><i
                                            class="bi bi-geo-alt"></i></span>
                                    <textarea name="address" class="form-control" rows="3" placeholder="Masukkan alamat domisili...">{{ old('address', $profile->address ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        {{-- BUTTONS --}}
                        <div class="text-end mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light px-4" onclick="history.back()">
                                Batal
                            </button>
                            <button type="submit" class="btn text-white px-4"
                                style="background: var(--islamic-purple-600);">
                                <i class="bi bi-save me-1"></i> Simpan
                            </button>
                        </div>

                    </form>
                </div>
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
                    // Beri sedikit animasi fade-in saat gambar berubah
                    photoBox.style.opacity = '0';
                    setTimeout(() => {
                        photoBox.innerHTML = `
                            <img src="${e.target.result}" class="profile-preview-img" alt="Preview Foto Profil">
                        `;
                        photoBox.style.opacity = '1';
                    }, 150);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
@endpush
