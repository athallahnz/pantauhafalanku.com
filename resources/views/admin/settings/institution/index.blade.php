@extends('layouts.app')

@section('title', 'Pengaturan Lembaga')

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
    </style>

    {{-- =======================
CONTENT
======================= --}}
    <div class="row">
        <div class="col-12">

            <div class="card">
                <div class="card-header">
                    <strong>Pengaturan Lembaga / Sekolah / Ponpes</strong>
                </div>

                <div class="card-body">

                    {{-- ALERT SUCCESS --}}
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('admin.settings.institution.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        {{-- =======================
                    LOGO UPLOAD
                    ======================= --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-flex align-items-center gap-2">
                                <i class="cil-image"></i> Logo Sekolah
                            </label>

                            <div class="logo-upload-wrapper">
                                <input type="file" name="logo" id="logoInput" accept="image/*"
                                    class="logo-input @error('logo') is-invalid @enderror">

                                <div class="logo-upload-box" id="logoPreviewBox">
                                    @if (!empty($setting?->logo))
                                        <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo Sekolah"
                                            class="logo-preview-img">
                                    @else
                                        <div class="logo-placeholder">
                                            <i class="cil-cloud-upload"></i>
                                            <div class="mt-2 fw-semibold">Klik untuk upload logo</div>
                                            <small class="text-muted">
                                                PNG, JPG, WebP, GIF (Maks. 2MB)
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @error('logo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- =======================
                    FORM DATA LEMBAGA
                    ======================= --}}
                        <div class="mb-3">
                            <label class="form-label">Nama Lembaga</label>
                            <input type="text" name="name" value="{{ old('name', $setting->name ?? '') }}"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Masukkan nama lembaga...">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Masukkan alamat...">{{ old('address', $setting->address ?? '') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">No. Telp</label>
                                <input type="text" name="phone" value="{{ old('phone', $setting->phone ?? '') }}"
                                    class="form-control" placeholder="Masukkan nomor telepon...">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="{{ old('email', $setting->email ?? '') }}"
                                    class="form-control" placeholder="Masukkan email...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Website</label>
                            <input type="text" name="website" value="{{ old('website', $setting->website ?? '') }}"
                                class="form-control" placeholder="Masukkan website...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Pimpinan</label>
                            <input type="text" name="head_name"
                                value="{{ old('head_name', $setting->head_name ?? '') }}" class="form-control"
                                placeholder="Masukkan nama pimpinan...">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tahun Berdiri</label>
                            <input type="number" name="established_year"
                                value="{{ old('established_year', $setting->established_year ?? '') }}"
                                class="form-control" placeholder="Masukkan tahun berdiri...">
                        </div>

                        {{-- =======================
                    ACTION BUTTON
                    ======================= --}}
                        <div class="text-end">
                            <button type="reset" class="btn btn-secondary me-2" onclick="history.back()">
                                <i class="bi bi-x-circle"></i> Batal
                            </button>
                            <button class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

{{-- =======================
SCRIPT PREVIEW LOGO
======================= --}}
@push('scripts')
    <script>
        const logoInput = document.getElementById('logoInput');
        const logoBox = document.getElementById('logoPreviewBox');

        if (logoInput) {
            logoInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    alert('File harus berupa gambar');
                    logoInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    logoBox.innerHTML = `
                    <img
                        src="${e.target.result}"
                        class="logo-preview-img"
                        alt="Preview Logo Sekolah"
                    >
                `;
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
@endpush
