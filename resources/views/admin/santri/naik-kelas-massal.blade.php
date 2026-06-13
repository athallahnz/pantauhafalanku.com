@extends('layouts.app')

@section('title', 'Naik Kelas Massal')

@section('content')
    <style>
        /* ================= KONSISTENSI STYLING ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
            transition: color 0.3s ease;
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #ffffff !important;
        }

        /* Form Controls */
        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.6rem 1rem;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--cui-secondary-color);
        }

        /* Status Badge Semester */
        .semester-status-card {
            background: linear-gradient(45deg, var(--islamic-purple-600), #8e44ad);
            color: white;
            border: none;
        }

        /* Section Separator */
        .section-title {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--cui-secondary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--cui-border-color);
        }

        /* Execute Button Styles */
        .btn-execute {
            background: #198754;
            color: white;
            border: none;
        }

        .btn-execute:disabled {
            background: #a5d6a7;
        }

        .semester-lifecycle-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .migration-lock-alert {
            border-radius: 1rem;
        }

        .auto-mapping-locked {
            border: 1px dashed var(--cui-border-color);
            border-radius: 1rem;
            background: var(--cui-tertiary-bg);
        }

        [data-coreui-theme="dark"] .bg-light {
            background-color: var(--cui-tertiary-bg) !important;
        }

        [data-coreui-theme="dark"] .text-dark {
            color: var(--cui-body-color) !important;
        }

        .assignment-toolbar {
            border: 1px solid var(--cui-border-color);
            border-radius: 1rem;
            background: var(--cui-tertiary-bg);
        }

        .assignment-select {
            min-width: 220px;
        }

        .assignment-incomplete {
            border-color: var(--cui-danger) !important;
        }

        .old-musyrif-map-row {
            border: 1px solid var(--cui-border-color);
            border-radius: .85rem;
            background: var(--cui-body-bg);
        }

        .auto-mapping-safe {
            border: 1px solid rgba(25, 135, 84, .25);
            border-radius: 1rem;
            background: rgba(25, 135, 84, .08);
        }

        .graduation-option-card {
            border: 1px solid rgba(220, 53, 69, .22);
            border-radius: 1rem;
            background: rgba(220, 53, 69, .045);
            transition:
                border-color .2s ease,
                background-color .2s ease,
                box-shadow .2s ease;
        }

        .graduation-option-card.is-enabled {
            border-color: rgba(220, 53, 69, .45);
            background: rgba(220, 53, 69, .09);
            box-shadow: 0 .4rem 1rem rgba(220, 53, 69, .08);
        }

        .graduation-option-icon {
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .9rem;
            color: #dc3545;
            background: rgba(220, 53, 69, .12);
            font-size: 1.15rem;
        }

        .auto-mapping-group {
            border: 1px solid var(--cui-border-color);
            border-radius: 1rem;
            overflow: hidden;
            background: var(--cui-body-bg);
        }

        .auto-mapping-group-header {
            background: var(--cui-tertiary-bg);
            border-bottom: 1px solid var(--cui-border-color);
        }

        .auto-assignment-incomplete {
            border-color: var(--cui-danger) !important;
        }

        .auto-group-map-card {
            border: 1px solid var(--cui-border-color);
            border-radius: .75rem;
            background: var(--cui-body-bg);
        }

        /* ================= FLOATING PAGE GUIDE ================= */
        .page-guide-fab {
            position: fixed;
            right: max(30px, env(safe-area-inset-right));
            bottom: max(60px, env(safe-area-inset-bottom));
            z-index: 1035;

            width: 56px;
            height: 56px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: 0;
            border-radius: 50%;

            color: #ffffff;

            background:
                linear-gradient(135deg,
                    var(--islamic-purple-600, #6f42c1),
                    var(--islamic-purple-700, #59359d));

            box-shadow:
                0 12px 30px rgba(89, 53, 157, 0.34);

            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                filter 0.2s ease;
        }

        .page-guide-fab:hover,
        .page-guide-fab:focus-visible {
            color: #ffffff;

            transform:
                translateY(-3px) scale(1.03);

            filter: brightness(1.06);

            box-shadow:
                0 16px 36px rgba(89, 53, 157, 0.42);
        }

        .page-guide-fab:focus-visible {
            outline:
                3px solid rgba(111, 66, 193, 0.24);

            outline-offset: 4px;
        }

        .page-guide-fab i {
            font-size: 1.45rem;
        }

        /* Animasi lingkaran pulse */
        .page-guide-fab::after {
            content: '';

            position: absolute;
            inset: -5px;

            border:
                2px solid rgba(111, 66, 193, 0.22);

            border-radius: inherit;

            animation:
                pageGuidePulse 2.4s ease-out infinite;

            pointer-events: none;
        }

        @keyframes pageGuidePulse {
            0% {
                transform: scale(0.88);
                opacity: 0;
            }

            30% {
                opacity: 1;
            }

            100% {
                transform: scale(1.28);
                opacity: 0;
            }
        }

        @media (max-width: 575.98px) {
            .page-guide-fab {
                right:
                    max(14px,
                        env(safe-area-inset-right));

                bottom:
                    max(14px,
                        env(safe-area-inset-bottom));

                width: 52px;
                height: 52px;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            .page-guide-fab,
            .page-guide-fab::after {
                animation: none;
                transition: none;
            }
        }

        .migration-guide-modal .modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 1.25rem;
            box-shadow:
                0 1.25rem 3rem rgba(0, 0, 0, .2);
        }

        .migration-guide-modal .modal-header {
            color: #fff;
            border: 0;
            background:
                linear-gradient(135deg,
                    var(--islamic-purple-700),
                    #8e44ad);
        }

        .migration-guide-modal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .guide-intro-card {
            border: 1px solid rgba(111, 66, 193, .2);
            border-radius: 1rem;
            background:
                rgba(111, 66, 193, .06);
        }

        .guide-step {
            position: relative;
            padding-left: 3.35rem;
        }

        .guide-step+.guide-step {
            margin-top: 1.15rem;
        }

        .guide-step-number {
            position: absolute;
            top: 0;
            left: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.35rem;
            height: 2.35rem;
            border-radius: .8rem;
            color: #fff;
            background:
                linear-gradient(135deg,
                    var(--islamic-purple-600),
                    #8e44ad);
            font-size: .85rem;
            font-weight: 800;
            box-shadow:
                0 .35rem .75rem rgba(111, 66, 193, .2);
        }

        .guide-checklist {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .guide-checklist li {
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            padding: .45rem 0;
        }

        .guide-checklist li i {
            margin-top: .15rem;
            color: var(--cui-success);
        }

        .guide-warning {
            border: 1px solid rgba(255, 193, 7, .32);
            border-radius: 1rem;
            background:
                rgba(255, 193, 7, .08);
        }

        .guide-danger {
            border: 1px solid rgba(220, 53, 69, .28);
            border-radius: 1rem;
            background:
                rgba(220, 53, 69, .06);
        }

        [data-coreui-theme="dark"] .guide-intro-card,
        [data-coreui-theme="dark"] .guide-warning,
        [data-coreui-theme="dark"] .guide-danger {
            background: var(--cui-tertiary-bg);
        }
    </style>

    {{-- HEADER PAGE --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-0 fw-bold text-adaptive-purple">Manajemen Naik Kelas</h4>
            <span class="text-muted small">Proses migrasi data santri antar kelas dan semester</span>
        </div>
        <a href="{{ route('santri.master.index') }}" class="btn btn-outline-secondary px-3 rounded-pill shadow-sm">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    {{-- SEMESTER TRANSITION CARD --}}
    <div class="card semester-status-card shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4">
            <div class="row align-items-end g-3">
                <div class="col-lg-5">
                    <div class="opacity-75 small text-uppercase fw-bold mb-1">
                        Semester Asal
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-calendar-check fs-3 opacity-50"></i>

                        <div>
                            <h4 class="mb-0 fw-bold">
                                @if ($semesterAktif)
                                    @php
                                        $namaSemesterAktif = \Illuminate\Support\Str::title(
                                            str_replace('_', ' ', $semesterAktif->nama ?? ''),
                                        );

                                        $namaTahunAjaranAktif = \Illuminate\Support\Str::title(
                                            str_replace('_', ' ', $semesterAktif->tahunAjaran?->nama ?? '-'),
                                        );
                                    @endphp

                                    {{ $namaSemesterAktif }} — {{ $namaTahunAjaranAktif }}
                                @else
                                    BELUM ADA SEMESTER AKTIF
                                @endif
                            </h4>

                            @if ($semesterAktif)
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="semester-lifecycle-badge bg-success text-white">
                                        <i class="bi bi-check-circle-fill"></i>
                                        Active
                                    </span>

                                    @if ($semesterAktif->isInputLocked())
                                        <span class="semester-lifecycle-badge bg-warning text-dark">
                                            <i class="bi bi-lock-fill"></i>
                                            Input Dikunci
                                        </span>
                                    @else
                                        <span class="semester-lifecycle-badge bg-white bg-opacity-25 text-white">
                                            <i class="bi bi-unlock-fill"></i>
                                            Input Dibuka
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="small opacity-75 mt-2">
                                Semester yang sedang berjalan dan akan ditutup.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 text-center d-none d-lg-block">
                    <i class="bi bi-arrow-right-circle-fill fs-2 opacity-75"></i>
                </div>

                <div class="col-lg-5">
                    <label for="toSemesterId" class="small text-uppercase fw-bold text-white opacity-75 mb-1">
                        Semester Tujuan
                    </label>

                    <select class="form-select border-0 shadow-sm" id="toSemesterId"
                        {{ !$semesterAktif || $semesterTujuanList->isEmpty() ? 'disabled' : '' }}>

                        <option value="">Pilih semester tujuan...</option>

                        @forelse ($semesterTujuanList as $semester)
                            @php
                                $namaSemester = \Illuminate\Support\Str::title(
                                    str_replace('_', ' ', $semester->nama ?? ''),
                                );

                                $namaTahunAjaran = \Illuminate\Support\Str::title(
                                    str_replace('_', ' ', $semester->tahunAjaran?->nama ?? '-'),
                                );
                            @endphp

                            <option value="{{ $semester->id }}">
                                {{ $namaSemester }} — {{ $namaTahunAjaran }}
                                {{ $semester->is_active ? '(Aktif)' : '' }}
                            </option>
                        @empty
                            <option value="">
                                Belum Ada Semester Tujuan
                            </option>
                        @endforelse
                    </select>

                    <div class="small opacity-75 mt-2">
                        Penempatan kelas baru akan disimpan pada semester ini.
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!$semesterAktif)
        <div class="alert alert-danger border-0 migration-lock-alert shadow-sm d-flex align-items-start gap-3">
            <i class="bi bi-x-octagon-fill fs-4"></i>

            <div>
                <div class="fw-bold mb-1">
                    Semester Aktif Tidak Ditemukan
                </div>

                <div class="small">
                    Proses preview dan migrasi kelas tidak dapat dilakukan sebelum
                    terdapat satu semester berstatus active.
                </div>
            </div>
        </div>
    @elseif ($semesterAktif->isInputLocked())
        <div class="alert alert-success border-0 migration-lock-alert shadow-sm d-flex align-items-start gap-3">
            <i class="bi bi-shield-lock-fill fs-4"></i>

            <div>
                <div class="fw-bold mb-1">
                    Input Semester Sudah Dikunci
                </div>

                <div class="small">
                    Preview dan eksekusi migrasi manual dapat dilakukan.
                    Musyrif sementara tidak dapat menambah, mengubah, atau menghapus
                    data Hafalan, Tahsin, dan Tilawah.
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning border-0 migration-lock-alert shadow-sm d-flex align-items-start gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>

            <div>
                <div class="fw-bold mb-1">
                    Input Semester Belum Dikunci
                </div>

                <div class="small">
                    Preview masih dapat dilakukan, tetapi tombol eksekusi manual
                    akan tetap dinonaktifkan. Kunci input semester aktif melalui
                    halaman Pengaturan Akademik sebelum menjalankan migrasi.
                </div>
            </div>
        </div>
    @endif

    @if ($semesterAktif && $semesterTujuanList->isEmpty())
        <div class="alert alert-warning border-0 rounded-4 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Belum tersedia semester tujuan. Buat semester baru terlebih dahulu
            dan pastikan semester tersebut belum berstatus aktif.
        </div>
    @endif

    {{-- CONFIGURATION CARD --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            {{-- ALUR 1: MANUAL PER KELAS --}}
            <div class="section-title mb-4">ALUR 1: KONFIGURASI MANUAL PER KELAS</div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <label class="form-label">Kelas Asal</label>
                    <select class="form-select shadow-xs" id="fromKelasId" {{ !$semesterAktif ? 'disabled' : '' }}>
                        <option value="">Pilih kelas asal...</option>
                        @foreach ($kelasList as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kelas Tujuan</label>
                    <select class="form-select shadow-xs" id="toKelasId" {{ !$semesterAktif ? 'disabled' : '' }}>
                        <option value="">Pilih kelas tujuan...</option>

                        @foreach ($kelasList as $k)
                            <option value="{{ $k->id }}">
                                {{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    </select>

                    <div class="form-text" id="toKelasHelp">
                        Pilih kelas tujuan sesuai tipe perubahan.
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">
                        Terapkan Musyrif ke Semua
                        <span class="text-muted">(Opsional)</span>
                    </label>

                    <select class="form-select shadow-xs" id="toMusyrifId" disabled>
                        <option value="">
                            Pilih kelas tujuan terlebih dahulu...
                        </option>
                    </select>

                    <div class="form-text" id="toMusyrifHelp">
                        Preview dapat dilakukan lebih dahulu. Setelah hasil tampil, pilihan ini dapat diterapkan ke seluruh
                        santri dan tetap bisa diubah per baris.
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tipe Perubahan</label>
                    <select class="form-select shadow-xs" id="tipe" {{ !$semesterAktif ? 'disabled' : '' }}>
                        <option value="naik_kelas">Naik Kelas</option>
                        <option value="mutasi">Mutasi</option>
                        <option value="tinggal_kelas">Tinggal Kelas</option>
                        <option value="penempatan">Penempatan</option>
                    </select>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Catatan Riwayat</label>
                    <input type="text" class="form-control shadow-xs" id="catatan"
                        placeholder="Contoh: Kenaikan Semester Genap 2025/2026" {{ !$semesterAktif ? 'disabled' : '' }}>
                </div>
            </div>

            {{-- AUTO GRADUATION OPTION --}}
            <div class="graduation-option-card p-3 mb-3" id="graduationOptionCard">

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="graduation-option-icon">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>

                        <div>
                            <div class="fw-bold">
                                Kelulusan Kelas Akhir
                            </div>

                            <div class="small text-body-secondary" id="graduationOptionHelp">
                                Tidak disertakan. Santri kelas akhir tidak akan diproses oleh Auto-Mapping.
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-0">
                        <input type="checkbox" class="form-check-input" role="switch" id="includeGraduation"
                            value="1" {{ !$semesterAktif ? 'disabled' : '' }}>

                        <label class="form-check-label fw-semibold" for="includeGraduation">
                            Sertakan kelulusan
                        </label>
                    </div>
                </div>

                <div class="small text-danger mt-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Aktifkan hanya setelah kelas akhir, semester tujuan, dan data calon alumni diverifikasi.
                </div>
            </div>

            {{-- ACTION BOX --}}
            <div class="bg-light rounded-4 p-4 border border-dashed">
                <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between gap-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white rounded-circle p-3 shadow-sm">
                            <i class="bi bi-people text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Total Santri Terpilih</div>
                            <div class="h5 mb-0 text-primary fw-bold" id="countInfo">0 Santri</div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        {{-- Manual Buttons --}}
                        <button type="button" class="btn btn-outline-primary px-3 rounded-pill fw-bold" id="btnPreview"
                            disabled>
                            <i class="bi bi-eye"></i> Preview Manual
                        </button>
                        <button type="button" class="btn btn-execute px-4 rounded-pill fw-bold shadow-sm"
                            id="btnExecute" disabled>
                            <i class="bi bi-lightning-fill"></i> Eksekusi Manual
                        </button>

                        <div class="vr mx-2 d-none d-lg-block"></div>

                        {{-- Auto Buttons --}}
                        <button type="button" class="btn btn-primary px-3 rounded-pill fw-bold shadow-sm"
                            id="btnAutoPreview">
                            <i class="bi bi-magic"></i> Auto-Mapping Preview
                        </button>
                        <button type="button" class="btn btn-execute px-4 rounded-pill fw-bold shadow-sm"
                            id="btnAutoExecute" disabled aria-disabled="true"
                            title="Lakukan Auto Preview dan lengkapi assignment musyrif">
                            <i class="bi bi-rocket-takeoff-fill"></i>
                            Eksekusi Auto
                        </button>
                    </div>
                </div>
            </div>

            <div class="auto-mapping-safe p-3 mt-3 d-flex align-items-start gap-3">
                <i class="bi bi-shield-check text-success fs-4"></i>

                <div>
                    <div class="fw-bold mb-1">
                        Auto-Mapping Menggunakan Snapshot Aman
                    </div>

                    <div class="small text-body-secondary">
                        Seluruh posisi kelas asal dikunci dan disnapshot dalam satu
                        transaksi sebelum perubahan dilakukan. Assignment musyrif
                        tetap ditentukan per santri untuk setiap kelas tujuan.
                    </div>
                </div>
            </div>

            {{-- PREVIEW AREA --}}
            <div class="mt-4 d-none" id="previewBox">
                <div class="alert alert-info border-0 rounded-3 shadow-sm d-flex align-items-start gap-3">
                    <i class="bi bi-info-circle-fill fs-4"></i>

                    <div>
                        <strong>Preview Berhasil.</strong>
                        Tentukan musyrif tujuan setiap santri. Gunakan
                        <strong>Terapkan ke Semua</strong> atau mapping berdasarkan
                        musyrif lama untuk mempercepat, lalu lakukan override pada
                        baris tertentu bila diperlukan.
                    </div>
                </div>

                <div class="assignment-toolbar p-3 mb-3" id="assignmentTools">
                    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                        <div>
                            <div class="fw-bold mb-1">
                                Mapping Berdasarkan Musyrif Lama
                            </div>

                            <div class="small text-body-secondary">
                                Pilih musyrif tujuan untuk setiap kelompok musyrif lama.
                            </div>

                            <div class="small text-primary fw-semibold mt-1" id="manualBatchInfo">
                                Batch belum dibuat.
                            </div>
                        </div>

                        <div class="text-lg-end">
                            <span class="badge text-bg-warning rounded-pill px-3 py-2" id="assignmentStatusBadge">
                                0 / 0 siap
                            </span>
                        </div>
                    </div>

                    <div class="row g-2 mt-2" id="oldMusyrifMappingBox"></div>
                </div>

                <div class="table-responsive border rounded-4 overflow-hidden mt-3">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold">
                            <tr>
                                <th class="ps-4" style="width:60px;">#</th>
                                <th>Santri</th>
                                <th>Musyrif Lama</th>
                                <th class="pe-4" style="min-width:270px;">
                                    Musyrif Tujuan
                                </th>
                            </tr>
                        </thead>

                        <tbody id="previewRows"></tbody>
                    </table>
                </div>

                <div class="small text-body-secondary mt-3">
                    <i class="bi bi-shield-check me-1"></i>
                    Backend memvalidasi setiap assignment dan memastikan musyrif
                    bertugas pada kelas tujuan.
                </div>
            </div>

            {{-- AUTO PREVIEW AREA --}}
            <div class="mt-4 d-none" id="autoPreviewBox">
                <div class="alert alert-success border-0 rounded-3 shadow-sm d-flex align-items-start gap-3">
                    <i class="bi bi-diagram-3-fill fs-4"></i>

                    <div>
                        <strong>Snapshot Auto-Mapping Siap.</strong>
                        Seluruh santri telah dikelompokkan berdasarkan kelas asal
                        sebelum perubahan. Lengkapi assignment musyrif pada setiap
                        kelompok kelas tujuan, kemudian jalankan Eksekusi Auto.
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                    <div>
                        <div class="fw-bold">
                            Rencana Auto-Mapping
                        </div>

                        <div class="small text-body-secondary" id="autoSnapshotInfo">
                            Belum ada snapshot.
                        </div>
                    </div>

                    <span class="badge text-bg-warning rounded-pill px-3 py-2" id="autoAssignmentStatusBadge">
                        0 / 0 siap
                    </span>
                </div>

                <div class="d-grid gap-3" id="autoMappingGroups"></div>
            </div>
        </div>
    </div>


@endsection

@push('modals')
    {{-- FLOATING BUTTON: PANDUAN HALAMAN --}}
    <button type="button" class="page-guide-fab" data-coreui-toggle="modal"
        data-coreui-target="#migrationTechnicalGuideModal" aria-controls="migrationTechnicalGuideModal"
        aria-label="Buka panduan teknis halaman migrasi santri" title="Panduan halaman">

        <i class="bi bi-info-lg" aria-hidden="true">
        </i>
    </button>

    {{-- MODAL TECHNICAL GUIDE --}}
    <div class="modal fade migration-guide-modal" id="migrationTechnicalGuideModal" tabindex="-1"
        aria-labelledby="migrationTechnicalGuideModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header px-4 py-3">
                    <div>
                        <div class="small text-white-50 text-uppercase fw-bold mb-1">
                            Petunjuk Teknis Admin
                        </div>

                        <h5 class="modal-title fw-bold" id="migrationTechnicalGuideModalLabel">
                            Cara Menggunakan Halaman Migrasi Santri
                        </h5>
                    </div>

                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Tutup panduan">
                    </button>
                </div>

                <div class="modal-body p-4">
                    <div class="guide-intro-card p-3 mb-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="graduation-option-icon flex-shrink-0">
                                <i class="bi bi-diagram-3-fill"></i>
                            </div>

                            <div>
                                <div class="fw-bold mb-1">
                                    Fungsi Halaman
                                </div>

                                <div class="small text-body-secondary">
                                    Halaman ini digunakan untuk memindahkan penempatan
                                    santri dari semester aktif ke semester tujuan melalui
                                    alur Manual per kelas atau Auto-Mapping seluruh kelas.
                                    Selalu lakukan Preview dan periksa assignment sebelum
                                    menekan tombol Eksekusi.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="section-title mb-3">
                                URUTAN PENGGUNAAN
                            </div>

                            <div class="guide-step">
                                <span class="guide-step-number">
                                    1
                                </span>

                                <div class="fw-bold mb-1">
                                    Periksa kesiapan semester
                                </div>

                                <div class="small text-body-secondary">
                                    Pastikan Semester Asal berstatus
                                    <strong>Active</strong>, input semester sudah
                                    <strong>dikunci</strong>, dan Semester Tujuan
                                    tersedia serta belum aktif.
                                </div>
                            </div>

                            <div class="guide-step">
                                <span class="guide-step-number">
                                    2
                                </span>

                                <div class="fw-bold mb-1">
                                    Pilih jenis proses
                                </div>

                                <div class="small text-body-secondary">
                                    Gunakan <strong>Preview Manual</strong> untuk satu
                                    kelas tertentu. Gunakan
                                    <strong>Auto-Mapping Preview</strong> untuk
                                    memproses seluruh mapping kelas secara bersamaan.
                                </div>
                            </div>

                            <div class="guide-step">
                                <span class="guide-step-number">
                                    3
                                </span>

                                <div class="fw-bold mb-1">
                                    Lengkapi konfigurasi
                                </div>

                                <div class="small text-body-secondary">
                                    Tentukan kelas asal, kelas tujuan, tipe perubahan,
                                    catatan riwayat, serta Semester Tujuan. Untuk
                                    tinggal kelas, kelas asal dan tujuan harus sama.
                                    Untuk naik kelas atau mutasi, keduanya harus berbeda.
                                </div>
                            </div>

                            <div class="guide-step">
                                <span class="guide-step-number">
                                    4
                                </span>

                                <div class="fw-bold mb-1">
                                    Jalankan Preview
                                </div>

                                <div class="small text-body-secondary">
                                    Preview tidak langsung mengubah data. Sistem membuat
                                    batch dan snapshot posisi awal santri. Periksa jumlah
                                    santri, kelas tujuan, tipe perubahan, warning, dan
                                    masa berlaku batch.
                                </div>
                            </div>

                            <div class="guide-step">
                                <span class="guide-step-number">
                                    5
                                </span>

                                <div class="fw-bold mb-1">
                                    Tentukan musyrif tujuan
                                </div>

                                <div class="small text-body-secondary">
                                    Musyrif harus berasal dari kelas tujuan. Assignment
                                    dapat diterapkan ke seluruh santri, berdasarkan
                                    kelompok musyrif lama, atau diubah per baris.
                                    Tombol Eksekusi tetap disabled selama assignment
                                    belum lengkap.
                                </div>
                            </div>

                            <div class="guide-step">
                                <span class="guide-step-number">
                                    6
                                </span>

                                <div class="fw-bold mb-1">
                                    Verifikasi lalu eksekusi
                                </div>

                                <div class="small text-body-secondary">
                                    Baca kembali ringkasan konfirmasi. Saat Eksekusi
                                    berjalan, jangan menutup atau me-refresh halaman.
                                    Setelah berhasil, periksa halaman Riwayat Migrasi,
                                    placement semester, dan laporan semester.
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="section-title mb-3">
                                CHECKLIST ADMIN
                            </div>

                            <ul class="guide-checklist mb-4">
                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        Semester asal benar dan inputnya sudah dikunci.
                                    </span>
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        Semester tujuan benar dan masih berstatus draft.
                                    </span>
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        Kelas tujuan serta tipe perubahan sudah sesuai.
                                    </span>
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        Seluruh musyrif tujuan bertugas pada kelas tujuan.
                                    </span>
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        Jumlah santri pada Preview sudah diverifikasi.
                                    </span>
                                </li>

                                <li>
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>
                                        Catatan riwayat menjelaskan tujuan migrasi.
                                    </span>
                                </li>
                            </ul>

                            <div class="guide-warning p-3 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-mortarboard-fill text-warning fs-5"></i>

                                    <div>
                                        <div class="fw-bold mb-1">
                                            Opsi Kelulusan
                                        </div>

                                        <div class="small text-body-secondary">
                                            Checkbox <strong>Sertakan Kelulusan</strong>
                                            hanya berlaku pada Auto-Mapping. Jika aktif,
                                            santri kelas akhir masuk Preview sebagai calon
                                            lulus, kelas terakhir dipertahankan untuk audit,
                                            dan musyrif tujuan dikosongkan.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="guide-danger p-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-shield-exclamation text-danger fs-5"></i>

                                    <div>
                                        <div class="fw-bold mb-1">
                                            Jangan Lanjutkan Jika
                                        </div>

                                        <div class="small text-body-secondary">
                                            Preview menampilkan kelas yang salah,
                                            jumlah santri tidak sesuai, assignment
                                            musyrif belum lengkap, atau terdapat warning
                                            snapshot/data berubah. Jalankan Preview ulang
                                            setelah memperbaiki konfigurasi.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="fw-bold mb-2">
                                    <i class="bi bi-person-gear text-primary me-1"></i>
                                    Manual per Kelas
                                </div>

                                <div class="small text-body-secondary">
                                    Cocok untuk kenaikan, mutasi, tinggal kelas, atau
                                    penempatan satu kelas dengan kontrol assignment
                                    musyrif per santri.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="fw-bold mb-2">
                                    <i class="bi bi-magic text-primary me-1"></i>
                                    Auto-Mapping
                                </div>

                                <div class="small text-body-secondary">
                                    Cocok untuk pergantian semester massal. Sistem
                                    menggunakan snapshot awal agar santri tidak berpindah
                                    berulang akibat urutan pemrosesan kelas.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="fw-bold mb-2">
                                    <i class="bi bi-clock-history text-primary me-1"></i>
                                    Setelah Eksekusi
                                </div>

                                <div class="small text-body-secondary">
                                    Periksa Riwayat Migrasi. Rollback hanya tersedia
                                    selama syarat keamanan terpenuhi dan semester tujuan
                                    belum menerima transaksi akademik.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer px-4 py-3">
                    <div class="small text-body-secondary me-auto">
                        <i class="bi bi-shield-check me-1"></i>
                        Preview aman tidak mengubah data sampai Eksekusi dikonfirmasi.
                    </div>

                    <button type="button" class="btn btn-primary px-4" data-coreui-dismiss="modal">
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        (function() {
            const swalHelper = (
                icon,
                title,
                text
            ) => {
                if (window.Swal) {
                    Swal.fire({
                        icon,
                        title,
                        text
                    });

                    return;
                }

                window.alert(text);
            };

            const csrf = document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                .getAttribute('content');

            const fromSemesterId =
                {{ $semesterAktif?->id ?? 'null' }};

            const semesterInputLocked = @json((bool) $semesterAktif?->input_locked_at);

            const musyrifByKelasUrlTemplate = @json(route('admin.musyrif.by_kelas', ['kelas_id' => '__KELAS_ID__']));

            const endpoints = {
                previewMassal: @json(route('admin.santri.migrasi.massal.preview')),
                executeMassal: @json(route('admin.santri.migrasi.massal.execute')),
                autoPreview: @json(route('admin.santri.migrasi.auto.preview')),
                autoExecute: @json(route('admin.santri.migrasi.auto.execute'))
            };

            const toSemesterId =
                document.getElementById('toSemesterId');

            const fromKelasId =
                document.getElementById('fromKelasId');

            const toKelasId =
                document.getElementById('toKelasId');

            const toKelasHelp =
                document.getElementById('toKelasHelp');

            const toMusyrifId =
                document.getElementById('toMusyrifId');

            const toMusyrifHelp =
                document.getElementById('toMusyrifHelp');

            const tipe =
                document.getElementById('tipe');

            const catatan =
                document.getElementById('catatan');

            const includeGraduation =
                document.getElementById(
                    'includeGraduation'
                );

            const graduationOptionCard =
                document.getElementById(
                    'graduationOptionCard'
                );

            const graduationOptionHelp =
                document.getElementById(
                    'graduationOptionHelp'
                );

            const btnPreview =
                document.getElementById('btnPreview');

            const btnExecute =
                document.getElementById('btnExecute');

            const countInfo =
                document.getElementById('countInfo');

            const previewBox =
                document.getElementById('previewBox');

            const previewRows =
                document.getElementById('previewRows');

            const oldMusyrifMappingBox =
                document.getElementById(
                    'oldMusyrifMappingBox'
                );

            const assignmentStatusBadge =
                document.getElementById(
                    'assignmentStatusBadge'
                );

            const manualBatchInfo =
                document.getElementById(
                    'manualBatchInfo'
                );

            const btnAutoPreview =
                document.getElementById('btnAutoPreview');

            const btnAutoExecute =
                document.getElementById('btnAutoExecute');

            const autoPreviewBox =
                document.getElementById('autoPreviewBox');

            const autoSnapshotInfo =
                document.getElementById('autoSnapshotInfo');

            const autoAssignmentStatusBadge =
                document.getElementById(
                    'autoAssignmentStatusBadge'
                );

            const autoMappingGroups =
                document.getElementById('autoMappingGroups');

            let lastCount = 0;
            let targetMusyrifs = [];
            let previewSantris = [];
            let musyrifOptionsLoading = false;
            let manualBatchId = null;
            let manualBatchCode = null;

            let autoLast = null;
            let autoBatchId = null;
            let autoBatchCode = null;
            let autoRows = [];

            function resetManualFlow() {
                lastCount = 0;
                previewSantris = [];
                manualBatchId = null;
                manualBatchCode = null;

                countInfo.textContent =
                    '0 Santri';

                countInfo.className =
                    'h5 mb-0 text-primary fw-bold';

                btnExecute.disabled = true;

                previewBox.classList.add(
                    'd-none'
                );

                previewRows.innerHTML = '';
                oldMusyrifMappingBox.innerHTML = '';

                manualBatchInfo.textContent =
                    'Batch belum dibuat.';

                updateAssignmentStatus();
                togglePreviewEnable();
            }

            function resetAutoFlow() {
                autoLast = null;
                autoBatchId = null;
                autoBatchCode = null;
                autoRows = [];

                autoPreviewBox.classList.add(
                    'd-none'
                );

                autoMappingGroups.innerHTML = '';

                autoSnapshotInfo.textContent =
                    'Belum ada snapshot.';

                btnAutoExecute.disabled = true;
                btnAutoExecute.setAttribute(
                    'aria-disabled',
                    'true'
                );

                btnAutoExecute.title =
                    'Lakukan Auto Preview dan lengkapi assignment musyrif';

                updateAutoAssignmentStatus();
            }

            function resetAllFlows() {
                resetManualFlow();
                resetAutoFlow();
            }

            function syncGraduationOptionUi() {
                const enabled =
                    Boolean(
                        includeGraduation?.checked
                    );

                graduationOptionCard?.classList
                    .toggle(
                        'is-enabled',
                        enabled
                    );

                if (graduationOptionHelp) {
                    graduationOptionHelp.textContent =
                        enabled ?
                        'Disertakan. Kelas akhir akan masuk Preview sebagai calon lulus dan musyrif tujuan dikosongkan.' :
                        'Tidak disertakan. Santri kelas akhir tidak akan diproses oleh Auto-Mapping.';
                }
            }

            function getPreviewDisabledReason() {
                if (!fromSemesterId) {
                    return 'Semester aktif tidak ditemukan.';
                }

                if (!toSemesterId?.value) {
                    return 'Pilih semester tujuan.';
                }

                if (!fromKelasId.value) {
                    return 'Pilih kelas asal.';
                }

                if (!toKelasId.value) {
                    return 'Pilih kelas tujuan.';
                }

                const sameClass =
                    fromKelasId.value ===
                    toKelasId.value;

                if (
                    tipe.value === 'tinggal_kelas' &&
                    !sameClass
                ) {
                    return 'Tinggal kelas harus menggunakan kelas yang sama.';
                }

                if (
                    ['naik_kelas', 'mutasi']
                    .includes(tipe.value) &&
                    sameClass
                ) {
                    return 'Naik kelas atau mutasi harus menuju kelas yang berbeda.';
                }

                return '';
            }

            function togglePreviewEnable() {
                const disabledReason =
                    getPreviewDisabledReason();

                btnPreview.disabled =
                    disabledReason !== '';

                btnPreview.title =
                    disabledReason;

                btnAutoPreview.disabled = !(
                    fromSemesterId &&
                    toSemesterId?.value
                );
            }

            function syncTransitionControls() {
                const isTinggalKelas =
                    tipe.value === 'tinggal_kelas';

                if (isTinggalKelas) {
                    if (fromKelasId.value) {
                        toKelasId.value =
                            fromKelasId.value;
                    }

                    toKelasId.disabled = true;

                    toKelasHelp.textContent =
                        'Tinggal kelas menggunakan kelas asal yang sama pada semester tujuan.';
                } else {
                    toKelasId.disabled = !fromSemesterId;

                    toKelasHelp.textContent =
                        tipe.value === 'penempatan' ?
                        'Penempatan boleh menggunakan kelas yang sama atau berbeda.' :
                        'Kelas tujuan harus berbeda dari kelas asal.';
                }

                togglePreviewEnable();
            }

            function validateManualSelection() {
                const reason =
                    getPreviewDisabledReason();

                if (reason) {
                    throw new Error(reason);
                }
            }

            function targetMusyrifOptions(
                selectedId = ''
            ) {
                const options = [
                    '<option value="">Pilih musyrif tujuan...</option>'
                ];

                targetMusyrifs.forEach(
                    function(musyrif) {
                        const label = musyrif.kode ?
                            `${musyrif.nama} — ${musyrif.kode}` :
                            musyrif.nama;

                        const selected =
                            String(musyrif.id) ===
                            String(selectedId) ?
                            ' selected' :
                            '';

                        options.push(
                            `<option value="${Number(musyrif.id)}"${selected}>` +
                            `${escapeHtml(label)}` +
                            `</option>`
                        );
                    }
                );

                return options.join('');
            }

            async function loadMusyrifByTargetClass() {
                const kelasId =
                    toKelasId.value;

                targetMusyrifs = [];
                toMusyrifId.value = '';

                if (!kelasId) {
                    toMusyrifId.innerHTML =
                        '<option value="">Pilih kelas tujuan terlebih dahulu...</option>';

                    toMusyrifId.disabled = true;

                    toMusyrifHelp.textContent =
                        'Pilih kelas tujuan untuk memuat daftar musyrif.';

                    togglePreviewEnable();
                    return;
                }

                musyrifOptionsLoading = true;
                toMusyrifId.disabled = true;

                toMusyrifId.innerHTML =
                    '<option value="">Memuat musyrif...</option>';

                toMusyrifHelp.textContent =
                    'Memuat daftar musyrif kelas tujuan...';

                try {
                    const endpoint =
                        musyrifByKelasUrlTemplate.replace(
                            '__KELAS_ID__',
                            encodeURIComponent(kelasId)
                        );

                    const response = await fetch(
                        endpoint, {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                    const json = await response
                        .json()
                        .catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(
                            json?.message ||
                            'Gagal memuat musyrif.'
                        );
                    }

                    targetMusyrifs =
                        Array.isArray(json?.data) ?
                        json.data : [];

                    toMusyrifId.innerHTML =
                        '<option value="">(Tidak diterapkan ke semua)</option>';

                    targetMusyrifs.forEach(
                        function(musyrif) {
                            const label = musyrif.kode ?
                                `${musyrif.nama} — ${musyrif.kode}` :
                                musyrif.nama;

                            toMusyrifId.add(
                                new Option(
                                    label,
                                    musyrif.id
                                )
                            );
                        }
                    );

                    toMusyrifHelp.textContent =
                        targetMusyrifs.length > 0 ?
                        'Pilih untuk menerapkan musyrif yang sama ke semua baris setelah Preview.' :
                        (
                            json?.message ||
                            'Belum ada musyrif pada kelas tujuan.'
                        );
                } catch (error) {
                    toMusyrifId.innerHTML =
                        '<option value="">Gagal memuat musyrif</option>';

                    toMusyrifHelp.textContent =
                        error.message;
                } finally {
                    musyrifOptionsLoading = false;
                    toMusyrifId.disabled = false;
                    togglePreviewEnable();
                }
            }

            function getRowSelects() {
                return Array.from(
                    document.querySelectorAll(
                        '.santri-musyrif-select'
                    )
                );
            }

            function assignmentIsRequired(
                santri
            ) {
                if (tipe.value === 'lulus') {
                    return false;
                }

                const classChanged =
                    String(fromKelasId.value) !==
                    String(toKelasId.value);

                return classChanged ||
                    !santri.musyrif_id;
            }

            function updateAssignmentStatus() {
                const selects =
                    getRowSelects();

                let ready = 0;
                const missingNames = [];

                selects.forEach(
                    function(select) {
                        const santriId =
                            Number(
                                select.dataset.santriId
                            );

                        const santri =
                            previewSantris.find(
                                row =>
                                Number(row.id) ===
                                santriId
                            );

                        const required =
                            assignmentIsRequired(
                                santri ?? {}
                            );

                        const hasEffectiveValue =
                            Boolean(select.value) ||
                            (
                                !required &&
                                Boolean(
                                    santri?.musyrif_id
                                )
                            );

                        select.classList.toggle(
                            'assignment-incomplete',
                            !hasEffectiveValue
                        );

                        if (hasEffectiveValue) {
                            ready++;
                        } else {
                            missingNames.push(
                                santri?.nama ??
                                `Santri #${santriId}`
                            );
                        }
                    }
                );

                assignmentStatusBadge.textContent =
                    `${ready} / ${selects.length} siap`;

                assignmentStatusBadge.className =
                    ready === selects.length ?
                    'badge text-bg-success rounded-pill px-3 py-2' :
                    'badge text-bg-warning rounded-pill px-3 py-2';

                btnExecute.disabled = !semesterInputLocked ||
                    lastCount === 0 ||
                    missingNames.length > 0;

                return missingNames;
            }

            function renderOldMusyrifMappings() {
                const groups = new Map();

                previewSantris.forEach(
                    function(santri) {
                        const key =
                            santri.musyrif_id ?
                            String(
                                santri.musyrif_id
                            ) :
                            'none';

                        if (!groups.has(key)) {
                            groups.set(
                                key, {
                                    id: santri.musyrif_id,
                                    name: santri.musyrif_nama ??
                                        'Belum ada musyrif',
                                    count: 0
                                }
                            );
                        }

                        groups.get(key).count++;
                    }
                );

                oldMusyrifMappingBox.innerHTML =
                    Array.from(groups.entries())
                    .map(
                        ([key, group]) => `
                                <div class="col-lg-6">
                                    <div class="old-musyrif-map-row p-3 h-100">
                                        <div class="small text-body-secondary">
                                            Musyrif Lama
                                        </div>

                                        <div class="fw-bold mb-2">
                                            ${escapeHtml(group.name)}
                                            <span class="badge text-bg-light ms-1">
                                                ${group.count} santri
                                            </span>
                                        </div>

                                        <select
                                            class="form-select form-select-sm old-musyrif-map-select"
                                            data-from-musyrif-id="${escapeHtml(key)}">
                                            ${targetMusyrifOptions()}
                                        </select>
                                    </div>
                                </div>
                            `
                    )
                    .join('');

                document
                    .querySelectorAll(
                        '.old-musyrif-map-select'
                    )
                    .forEach(
                        function(select) {
                            select.addEventListener(
                                'change',
                                function() {
                                    const fromId =
                                        this.dataset
                                        .fromMusyrifId;

                                    getRowSelects()
                                        .filter(
                                            rowSelect =>
                                            rowSelect.dataset
                                            .fromMusyrifId ===
                                            fromId
                                        )
                                        .forEach(
                                            rowSelect => {
                                                rowSelect.value =
                                                    this.value;
                                            }
                                        );

                                    updateAssignmentStatus();
                                }
                            );
                        }
                    );
            }

            function renderPreview(
                json
            ) {
                manualBatchId =
                    json?.batch?.id ?? null;

                manualBatchCode =
                    json?.batch?.code ?? null;

                manualBatchInfo.textContent =
                    manualBatchCode ?
                    `Batch ${manualBatchCode} • berlaku sampai ${formatDateTime(json?.batch?.expires_at)}` :
                    'Batch gagal dibuat.';

                previewSantris =
                    Array.isArray(json.santris) ?
                    json.santris : [];

                if (
                    Array.isArray(
                        json.target_musyrifs
                    )
                ) {
                    targetMusyrifs =
                        json.target_musyrifs;
                }

                const currentGlobalValue =
                    toMusyrifId.value;

                toMusyrifId.innerHTML =
                    '<option value="">(Tidak diterapkan ke semua)</option>';

                targetMusyrifs.forEach(
                    function(musyrif) {
                        const label = musyrif.kode ?
                            `${musyrif.nama} — ${musyrif.kode}` :
                            musyrif.nama;

                        toMusyrifId.add(
                            new Option(
                                label,
                                musyrif.id
                            )
                        );
                    }
                );

                if (
                    targetMusyrifs.some(
                        musyrif =>
                        String(musyrif.id) ===
                        String(currentGlobalValue)
                    )
                ) {
                    toMusyrifId.value =
                        currentGlobalValue;
                }

                const sameClass =
                    String(fromKelasId.value) ===
                    String(toKelasId.value);

                previewRows.innerHTML =
                    previewSantris
                    .map(
                        function(
                            santri,
                            index
                        ) {
                            const selectedId =
                                sameClass &&
                                santri.musyrif_id &&
                                targetMusyrifs.some(
                                    musyrif =>
                                    Number(
                                        musyrif.id
                                    ) ===
                                    Number(
                                        santri.musyrif_id
                                    )
                                ) ?
                                santri.musyrif_id :
                                '';

                            const oldMusyrifKey =
                                santri.musyrif_id ?
                                String(
                                    santri.musyrif_id
                                ) :
                                'none';

                            return `
                                    <tr>
                                        <td class="ps-4">
                                            ${index + 1}
                                        </td>

                                        <td>
                                            <div class="fw-bold">
                                                ${escapeHtml(santri.nama ?? '-')}
                                            </div>

                                            <div class="small text-body-secondary">
                                                NIS: ${escapeHtml(santri.nis ?? '-')}
                                            </div>
                                        </td>

                                        <td>
                                            <div class="fw-semibold">
                                                ${escapeHtml(
                                                    santri.musyrif_nama
                                                    ?? 'Belum ada musyrif'
                                                )}
                                            </div>

                                            <div class="small text-body-secondary">
                                                ${escapeHtml(
                                                    santri.musyrif_kode
                                                    ?? '-'
                                                )}
                                            </div>
                                        </td>

                                        <td class="pe-4">
                                            <select
                                                class="form-select form-select-sm assignment-select santri-musyrif-select"
                                                data-santri-id="${Number(santri.id)}"
                                                data-batch-item-id="${Number(santri.batch_item_id)}"
                                                data-from-musyrif-id="${escapeHtml(oldMusyrifKey)}">
                                                ${targetMusyrifOptions(selectedId)}
                                            </select>
                                        </td>
                                    </tr>
                                `;
                        }
                    )
                    .join('');

                getRowSelects().forEach(
                    function(select) {
                        select.addEventListener(
                            'change',
                            updateAssignmentStatus
                        );
                    }
                );

                renderOldMusyrifMappings();
                previewBox.classList.remove('d-none');

                updateAssignmentStatus();
            }

            function collectAssignmentItems() {
                return getRowSelects()
                    .map(
                        function(select) {
                            return {
                                batch_item_id: Number(
                                    select.dataset
                                    .batchItemId
                                ),
                                to_musyrif_id: select.value ?
                                    Number(
                                        select.value
                                    ) : null
                            };
                        }
                    );
            }

            function validateAssignments() {
                if (!manualBatchId) {
                    throw new Error(
                        'Batch Manual tidak tersedia. Jalankan Preview ulang.'
                    );
                }

                const missingNames =
                    updateAssignmentStatus();

                if (missingNames.length > 0) {
                    throw new Error(
                        `Masih ada ${missingNames.length} santri yang belum memiliki musyrif tujuan.`
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Auto-Mapping assignment helpers
            |--------------------------------------------------------------------------
            */
            function autoOptions(
                group,
                selectedId = ''
            ) {
                const options = [
                    '<option value="">Pilih musyrif tujuan...</option>'
                ];

                (group.target_musyrifs ?? [])
                .forEach(
                    function(musyrif) {
                        const label = musyrif.kode ?
                            `${musyrif.nama} — ${musyrif.kode}` :
                            musyrif.nama;

                        const selected =
                            String(musyrif.id) ===
                            String(selectedId) ?
                            ' selected' :
                            '';

                        options.push(
                            `<option value="${Number(musyrif.id)}"${selected}>` +
                            `${escapeHtml(label)}` +
                            `</option>`
                        );
                    }
                );

                return options.join('');
            }

            function getAutoRowSelects() {
                return Array.from(
                    document.querySelectorAll(
                        '.auto-santri-musyrif-select'
                    )
                );
            }

            function renderAutoOldMusyrifMappings(
                group,
                groupIndex
            ) {
                if (group.tipe === 'lulus') {
                    return '';
                }

                const oldGroups = new Map();

                (group.santris ?? []).forEach(
                    function(santri) {
                        const key =
                            santri.from_musyrif_id ?
                            String(
                                santri.from_musyrif_id
                            ) :
                            'none';

                        if (!oldGroups.has(key)) {
                            oldGroups.set(
                                key, {
                                    name: santri.from_musyrif_nama ??
                                        'Belum ada musyrif',
                                    count: 0
                                }
                            );
                        }

                        oldGroups.get(key).count++;
                    }
                );

                return `
                    <div class="row g-2 mt-2">
                        ${Array.from(oldGroups.entries())
                            .map(
                                ([key, oldGroup]) => `
                                                                <div class="col-lg-6">
                                                                    <div class="auto-group-map-card p-2 h-100">
                                                                        <div class="small text-body-secondary">
                                                                            Dari ${escapeHtml(oldGroup.name)}
                                                                            <span class="badge text-bg-light ms-1">
                                                                                ${oldGroup.count}
                                                                            </span>
                                                                        </div>

                                                                        <select
                                                                            class="form-select form-select-sm mt-1 auto-old-map-select"
                                                                            data-group-index="${groupIndex}"
                                                                            data-from-musyrif-id="${escapeHtml(key)}">
                                                                            ${autoOptions(group)}
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            `
                            )
                            .join('')}
                    </div>
                `;
            }

            function renderAutoPreview(
                json
            ) {
                autoLast = json;
                autoBatchId =
                    json?.batch?.id ?? null;

                autoBatchCode =
                    json?.batch?.code ?? null;

                autoRows =
                    Array.isArray(json.rows) ?
                    json.rows : [];

                const graduationIncluded =
                    Boolean(
                        json.include_graduation
                    );

                const graduationSummary =
                    graduationIncluded ?
                    `${Number(json.total_graduation ?? 0)} calon lulus` :
                    'kelulusan tidak disertakan';

                autoSnapshotInfo.textContent =
                    `${autoBatchCode ?? 'Batch tidak tersedia'} • ` +
                    `${Number(json.snapshot_count ?? 0)} santri • ` +
                    `${graduationSummary} • ` +
                    `berlaku sampai ${formatDateTime(json?.batch?.expires_at)}`;

                autoMappingGroups.innerHTML =
                    autoRows
                    .map(
                        function(
                            group,
                            groupIndex
                        ) {
                            const isGraduation =
                                group.tipe === 'lulus';

                            const groupApply = isGraduation ?
                                '' :
                                `
                                        <div style="min-width:260px;">
                                            <label class="form-label mb-1">
                                                Terapkan ke Grup
                                            </label>

                                            <select
                                                class="form-select form-select-sm auto-group-apply-select"
                                                data-group-index="${groupIndex}">
                                                <option value="">
                                                    (Tidak diterapkan)
                                                </option>
                                                ${(group.target_musyrifs ?? [])
                                                    .map(
                                                        musyrif => {
                                                            const label = musyrif.kode
                                                                ? `${musyrif.nama} — ${musyrif.kode}`
                                                                : musyrif.nama;

                                                            return `
                                                                                            <option value="${Number(musyrif.id)}">
                                                                                                ${escapeHtml(label)}
                                                                                            </option>
                                                                                        `;
                                                        }
                                                    )
                                                    .join('')}
                                            </select>
                                        </div>
                                    `;

                            const mappingTools =
                                renderAutoOldMusyrifMappings(
                                    group,
                                    groupIndex
                                );

                            const rows = (
                                    group.santris ?? []
                                )
                                .map(
                                    function(
                                        santri,
                                        santriIndex
                                    ) {
                                        const assignmentCell =
                                            isGraduation ?
                                            `
                                                        <span class="badge text-bg-dark">
                                                            Musyrif dikosongkan
                                                        </span>
                                                    ` :
                                            `
                                                        <select
                                                            class="form-select form-select-sm auto-santri-musyrif-select"
                                                            data-group-index="${groupIndex}"
                                                            data-santri-id="${Number(santri.santri_id)}"
                                                            data-batch-item-id="${Number(santri.batch_item_id)}"
                                                            data-from-kelas-id="${Number(santri.from_kelas_id)}"
                                                            data-to-kelas-id="${Number(santri.to_kelas_id)}"
                                                            data-tipe="${escapeHtml(santri.tipe)}"
                                                            data-from-musyrif-id="${
                                                                santri.from_musyrif_id
                                                                    ? Number(santri.from_musyrif_id)
                                                                    : 'none'
                                                            }">
                                                            ${autoOptions(group)}
                                                        </select>
                                                    `;

                                        return `
                                                <tr>
                                                    <td class="ps-3">
                                                        ${santriIndex + 1}
                                                    </td>

                                                    <td>
                                                        <div class="fw-bold">
                                                            ${escapeHtml(santri.nama ?? '-')}
                                                        </div>

                                                        <div class="small text-body-secondary">
                                                            NIS: ${escapeHtml(santri.nis ?? '-')}
                                                        </div>
                                                    </td>

                                                    <td>
                                                        ${escapeHtml(
                                                            santri.from_musyrif_nama
                                                            ?? 'Belum ada musyrif'
                                                        )}
                                                    </td>

                                                    <td class="pe-3">
                                                        ${assignmentCell}
                                                    </td>
                                                </tr>
                                            `;
                                    }
                                )
                                .join('');

                            return `
                                    <section class="auto-mapping-group"
                                        data-group-index="${groupIndex}">
                                        <div class="auto-mapping-group-header p-3">
                                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                                                <div>
                                                    <div class="fw-bold fs-6">
                                                        ${escapeHtml(group.from_nama)}
                                                        <i class="bi bi-arrow-right mx-1"></i>
                                                        ${escapeHtml(group.to_nama)}
                                                    </div>

                                                    <div class="small text-body-secondary">
                                                        ${Number(group.count_santri ?? 0)} santri •
                                                        ${isGraduation ? 'Kelulusan' : 'Kenaikan kelas'}
                                                    </div>
                                                </div>

                                                ${groupApply}
                                            </div>

                                            ${mappingTools}
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="ps-3" style="width:55px;">#</th>
                                                        <th>Santri</th>
                                                        <th>Musyrif Lama</th>
                                                        <th class="pe-3" style="min-width:260px;">
                                                            Musyrif Tujuan
                                                        </th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    ${rows || `
                                                                                    <tr>
                                                                                        <td colspan="4"
                                                                                            class="text-center text-body-secondary py-3">
                                                                                            Tidak ada santri aktif.
                                                                                        </td>
                                                                                    </tr>
                                                                                `}
                                                </tbody>
                                            </table>
                                        </div>
                                    </section>
                                `;
                        }
                    )
                    .join('');

                document
                    .querySelectorAll(
                        '.auto-group-apply-select'
                    )
                    .forEach(
                        function(select) {
                            select.addEventListener(
                                'change',
                                function() {
                                    const groupIndex =
                                        this.dataset.groupIndex;

                                    getAutoRowSelects()
                                        .filter(
                                            row =>
                                            row.dataset.groupIndex ===
                                            groupIndex
                                        )
                                        .forEach(
                                            row => {
                                                row.value =
                                                    this.value;
                                            }
                                        );

                                    updateAutoAssignmentStatus();
                                }
                            );
                        }
                    );

                document
                    .querySelectorAll(
                        '.auto-old-map-select'
                    )
                    .forEach(
                        function(select) {
                            select.addEventListener(
                                'change',
                                function() {
                                    const groupIndex =
                                        this.dataset.groupIndex;

                                    const fromMusyrifId =
                                        this.dataset.fromMusyrifId;

                                    getAutoRowSelects()
                                        .filter(
                                            row =>
                                            row.dataset.groupIndex ===
                                            groupIndex &&
                                            row.dataset.fromMusyrifId ===
                                            fromMusyrifId
                                        )
                                        .forEach(
                                            row => {
                                                row.value =
                                                    this.value;
                                            }
                                        );

                                    updateAutoAssignmentStatus();
                                }
                            );
                        }
                    );

                getAutoRowSelects().forEach(
                    function(select) {
                        select.addEventListener(
                            'change',
                            updateAutoAssignmentStatus
                        );
                    }
                );

                autoPreviewBox.classList.remove(
                    'd-none'
                );

                updateAutoAssignmentStatus();

                autoPreviewBox.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }

            function collectAutoItems() {
                const items = [];

                autoRows.forEach(
                    function(group) {
                        (group.santris ?? [])
                        .forEach(
                            function(santri) {
                                if (
                                    group.tipe ===
                                    'lulus'
                                ) {
                                    items.push({
                                        batch_item_id: Number(
                                            santri.batch_item_id
                                        ),
                                        to_musyrif_id: null
                                    });

                                    return;
                                }

                                const select =
                                    document.querySelector(
                                        `.auto-santri-musyrif-select[data-santri-id="${Number(santri.santri_id)}"]`
                                    );

                                items.push({
                                    batch_item_id: Number(
                                        santri.batch_item_id
                                    ),
                                    to_musyrif_id: select?.value ?
                                        Number(
                                            select.value
                                        ) : null
                                });
                            }
                        );
                    }
                );

                return items;
            }

            function updateAutoAssignmentStatus() {
                const total = autoRows
                    .reduce(
                        (
                            sum,
                            group
                        ) =>
                        sum +
                        Number(
                            group.count_santri ??
                            0
                        ),
                        0
                    );

                let ready = 0;
                let missing = 0;

                autoRows.forEach(
                    function(group) {
                        if (group.tipe === 'lulus') {
                            ready += Number(
                                group.count_santri ??
                                0
                            );

                            return;
                        }

                        const groupSelects =
                            getAutoRowSelects()
                            .filter(
                                select =>
                                Number(
                                    select.dataset
                                    .groupIndex
                                ) ===
                                autoRows.indexOf(
                                    group
                                )
                            );

                        groupSelects.forEach(
                            function(select) {
                                const valid =
                                    Boolean(
                                        select.value
                                    );

                                select.classList.toggle(
                                    'auto-assignment-incomplete',
                                    !valid
                                );

                                if (valid) {
                                    ready++;
                                } else {
                                    missing++;
                                }
                            }
                        );
                    }
                );

                autoAssignmentStatusBadge.textContent =
                    `${ready} / ${total} siap`;

                autoAssignmentStatusBadge.className =
                    ready === total &&
                    total > 0 ?
                    'badge text-bg-success rounded-pill px-3 py-2' :
                    'badge text-bg-warning rounded-pill px-3 py-2';

                const canExecute =
                    Boolean(autoLast?.ok) &&
                    Boolean(autoBatchId) &&
                    total > 0 &&
                    missing === 0 &&
                    semesterInputLocked;

                btnAutoExecute.disabled = !canExecute;

                btnAutoExecute.setAttribute(
                    'aria-disabled',
                    canExecute ?
                    'false' :
                    'true'
                );

                btnAutoExecute.title = canExecute ?
                    'Eksekusi snapshot Auto-Mapping' :
                    (
                        !semesterInputLocked ?
                        'Kunci input semester aktif terlebih dahulu' :
                        'Lengkapi seluruh assignment musyrif'
                    );

                return {
                    total,
                    ready,
                    missing,
                    canExecute
                };
            }

            function validateAutoAssignments() {
                const status =
                    updateAutoAssignmentStatus();

                if (!autoLast?.ok) {
                    throw new Error(
                        'Lakukan Auto Preview terlebih dahulu.'
                    );
                }

                if (!autoBatchId) {
                    throw new Error(
                        'Batch Auto-Mapping tidak tersedia. Jalankan Auto Preview ulang.'
                    );
                }

                if (status.missing > 0) {
                    throw new Error(
                        `Masih ada ${status.missing} assignment musyrif yang belum lengkap.`
                    );
                }
            }

            async function postJson(
                url,
                payload
            ) {
                const response = await fetch(
                    url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(payload)
                    }
                );

                const json = await response
                    .json()
                    .catch(() => ({}));

                if (!response.ok) {
                    const validationMessage =
                        json?.errors ?
                        Object.values(
                            json.errors
                        ).flat()[0] :
                        null;

                    throw new Error(
                        validationMessage ||
                        json?.message ||
                        'Terjadi kesalahan sistem.'
                    );
                }

                return json;
            }

            /*
            |--------------------------------------------------------------------------
            | Manual events
            |--------------------------------------------------------------------------
            */
            toMusyrifId.addEventListener(
                'change',
                function() {
                    if (!this.value) {
                        return;
                    }

                    getRowSelects().forEach(
                        select => {
                            select.value =
                                this.value;
                        }
                    );

                    updateAssignmentStatus();
                }
            );

            toSemesterId?.addEventListener(
                'change',
                function() {
                    resetAllFlows();
                    togglePreviewEnable();
                }
            );

            catatan.addEventListener(
                'input',
                function() {
                    /*
                     * Catatan disimpan pada batch Preview. Perubahan catatan
                     * membutuhkan Preview ulang agar audit tetap konsisten.
                     */
                    resetAllFlows();
                }
            );

            includeGraduation?.addEventListener(
                'change',
                function() {
                    /*
                     * Konfigurasi kelulusan adalah bagian dari batch.
                     * Preview lama tidak boleh dieksekusi setelah opsi berubah.
                     */
                    resetAutoFlow();
                    syncGraduationOptionUi();
                }
            );

            syncGraduationOptionUi();

            fromKelasId.addEventListener(
                'change',
                async function() {
                    resetManualFlow();

                    if (
                        tipe.value ===
                        'tinggal_kelas'
                    ) {
                        toKelasId.value =
                            fromKelasId.value;
                    }

                    syncTransitionControls();

                    if (
                        tipe.value ===
                        'tinggal_kelas'
                    ) {
                        await loadMusyrifByTargetClass();
                    }
                }
            );

            toKelasId.addEventListener(
                'change',
                async function() {
                    resetManualFlow();
                    syncTransitionControls();
                    await loadMusyrifByTargetClass();
                }
            );

            tipe.addEventListener(
                'change',
                async function() {
                    resetManualFlow();

                    if (
                        tipe.value ===
                        'tinggal_kelas' &&
                        fromKelasId.value
                    ) {
                        toKelasId.value =
                            fromKelasId.value;
                    }

                    syncTransitionControls();
                    await loadMusyrifByTargetClass();
                }
            );

            btnPreview.addEventListener(
                'click',
                async function() {
                    try {
                        validateManualSelection();

                        if (window.Swal) {
                            Swal.fire({
                                title: 'Memproses Preview...',
                                allowOutsideClick: false,
                                didOpen: () =>
                                    Swal.showLoading()
                            });
                        }

                        const json =
                            await postJson(
                                endpoints.previewMassal, {
                                    from_semester_id: Number(
                                        fromSemesterId
                                    ),
                                    to_semester_id: Number(
                                        toSemesterId.value
                                    ),
                                    from_kelas_id: Number(
                                        fromKelasId.value
                                    ),
                                    to_kelas_id: Number(
                                        toKelasId.value
                                    ),
                                    tipe: tipe.value,
                                    catatan: catatan.value
                                }
                            );

                        if (window.Swal) {
                            Swal.close();
                        }

                        lastCount =
                            Number(json.count ?? 0);

                        countInfo.textContent =
                            `${lastCount} Santri`;

                        countInfo.className =
                            'h5 mb-0 text-success fw-bold';

                        renderPreview(json);

                        if (lastCount === 0) {
                            swalHelper(
                                'warning',
                                'Tidak Ada Santri',
                                'Tidak ada santri aktif pada kelas asal.'
                            );

                            return;
                        }

                        if (!semesterInputLocked) {
                            swalHelper(
                                'warning',
                                'Preview Berhasil',
                                'Assignment dapat disusun, tetapi eksekusi belum tersedia karena input semester aktif belum dikunci.'
                            );

                            return;
                        }

                        swalHelper(
                            'success',
                            'Preview Berhasil',
                            'Silakan periksa assignment musyrif setiap santri.'
                        );
                    } catch (error) {
                        if (window.Swal) {
                            Swal.close();
                        }

                        swalHelper(
                            'error',
                            'Gagal',
                            error.message
                        );
                    }
                }
            );

            btnExecute.addEventListener(
                'click',
                async function() {
                    try {
                        if (!semesterInputLocked) {
                            throw new Error(
                                'Input semester aktif belum dikunci.'
                            );
                        }

                        if (lastCount <= 0) {
                            throw new Error(
                                'Lakukan Preview terlebih dahulu.'
                            );
                        }

                        validateManualSelection();
                        validateAssignments();

                        const items =
                            collectAssignmentItems();

                        const confirm = window.Swal ?
                            await Swal.fire({
                                icon: 'warning',
                                title: 'Konfirmasi Migrasi',
                                html: `Proses <b>${items.length} santri</b> dengan assignment musyrif per santri?<br>` +
                                    `<small class="text-danger">Aksi ini tidak dapat dibatalkan.</small>`,
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Proses',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#198754'
                            }) : {
                                isConfirmed: window.confirm(
                                    'Proses migrasi santri?'
                                )
                            };

                        if (!confirm.isConfirmed) {
                            return;
                        }

                        if (window.Swal) {
                            Swal.fire({
                                title: 'Mengeksekusi...',
                                allowOutsideClick: false,
                                didOpen: () =>
                                    Swal.showLoading()
                            });
                        }

                        const json =
                            await postJson(
                                endpoints.executeMassal, {
                                    batch_id: manualBatchId,
                                    items
                                }
                            );

                        if (window.Swal) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: json.message
                            });
                        }

                        window.location.reload();
                    } catch (error) {
                        if (window.Swal) {
                            Swal.close();
                        }

                        swalHelper(
                            'error',
                            'Gagal',
                            error.message
                        );
                    }
                }
            );

            /*
            |--------------------------------------------------------------------------
            | Auto-Mapping events
            |--------------------------------------------------------------------------
            */
            btnAutoPreview.addEventListener(
                'click',
                async function() {
                    try {
                        if (!fromSemesterId) {
                            throw new Error(
                                'Semester asal aktif tidak ditemukan.'
                            );
                        }

                        if (!toSemesterId.value) {
                            throw new Error(
                                'Pilih semester tujuan terlebih dahulu.'
                            );
                        }

                        const graduationEnabled =
                            Boolean(
                                includeGraduation?.checked
                            );

                        if (
                            graduationEnabled &&
                            window.Swal
                        ) {
                            const graduationConfirm =
                                await Swal.fire({
                                    icon: 'warning',
                                    title: 'Sertakan Kelulusan Kelas Akhir?',
                                    html: 'Santri pada kelas akhir akan dibuat sebagai <b>calon lulus</b> dalam batch Auto-Mapping.<br>' +
                                        '<small class="text-danger">Pastikan semester tujuan dan data calon alumni sudah benar.</small>',
                                    showCancelButton: true,
                                    confirmButtonText: 'Ya, Sertakan',
                                    cancelButtonText: 'Batal',
                                    confirmButtonColor: '#dc3545'
                                });

                            if (
                                !graduationConfirm
                                .isConfirmed
                            ) {
                                return;
                            }
                        }

                        resetAutoFlow();

                        if (window.Swal) {
                            Swal.fire({
                                title: 'Membuat Snapshot Auto-Mapping...',
                                text: 'Membaca seluruh kelas asal tanpa mengubah data.',
                                allowOutsideClick: false,
                                didOpen: () =>
                                    Swal.showLoading()
                            });
                        }

                        const json =
                            await postJson(
                                endpoints.autoPreview, {
                                    from_semester_id: Number(
                                        fromSemesterId
                                    ),
                                    to_semester_id: Number(
                                        toSemesterId.value
                                    ),
                                    include_graduation: graduationEnabled,
                                    catatan: catatan.value
                                }
                            );

                        if (window.Swal) {
                            Swal.close();
                        }

                        renderAutoPreview(json);

                        if (
                            Number(
                                json.total_santri_affected ??
                                0
                            ) === 0
                        ) {
                            swalHelper(
                                'warning',
                                'Snapshot Kosong',
                                'Tidak ada santri aktif yang masuk mapping otomatis.'
                            );

                            return;
                        }

                        if (!semesterInputLocked) {
                            swalHelper(
                                'warning',
                                'Snapshot Berhasil',
                                'Lengkapi assignment musyrif. Eksekusi baru tersedia setelah input semester aktif dikunci.'
                            );

                            return;
                        }

                        const graduationMessage =
                            Boolean(
                                json.include_graduation
                            ) ?
                            ` Termasuk ${Number(json.total_graduation ?? 0)} calon lulus.` :
                            ' Kelulusan kelas akhir tidak disertakan.';

                        swalHelper(
                            'success',
                            'Snapshot Berhasil',
                            'Lengkapi assignment musyrif pada setiap kelompok kelas.' +
                            graduationMessage
                        );
                    } catch (error) {
                        if (window.Swal) {
                            Swal.close();
                        }

                        swalHelper(
                            'error',
                            'Auto Preview Gagal',
                            error.message
                        );
                    }
                }
            );

            btnAutoExecute.addEventListener(
                'click',
                async function() {
                    try {
                        if (!semesterInputLocked) {
                            throw new Error(
                                'Input semester aktif belum dikunci.'
                            );
                        }

                        validateAutoAssignments();

                        const items =
                            collectAutoItems();

                        const confirm = window.Swal ?
                            await Swal.fire({
                                icon: 'warning',
                                title: 'Eksekusi Auto-Mapping?',
                                html: `Proses snapshot <b>${items.length} santri</b> dari seluruh kelas?<br>` +
                                    (
                                        Boolean(
                                            autoLast?.include_graduation
                                        ) ?
                                        `<small class="text-danger fw-bold">Termasuk ${Number(autoLast?.total_graduation ?? 0)} santri yang akan diluluskan.</small><br>` :
                                        `<small>Kelulusan kelas akhir tidak disertakan.</small><br>`
                                    ) +
                                    `<small>Seluruh snapshot asal disimpan sebelum perubahan kelas.</small><br>` +
                                    `<small class="text-danger">Lanjutkan hanya jika seluruh assignment sudah diverifikasi.</small>`,
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Eksekusi Auto',
                                cancelButtonText: 'Batal',
                                confirmButtonColor: '#198754'
                            }) : {
                                isConfirmed: window.confirm(
                                    'Eksekusi Auto-Mapping?'
                                )
                            };

                        if (!confirm.isConfirmed) {
                            return;
                        }

                        if (window.Swal) {
                            Swal.fire({
                                title: 'Mengeksekusi Snapshot...',
                                text: 'Jangan menutup halaman sampai proses selesai.',
                                allowOutsideClick: false,
                                didOpen: () =>
                                    Swal.showLoading()
                            });
                        }

                        const json =
                            await postJson(
                                endpoints.autoExecute, {
                                    batch_id: autoBatchId,
                                    items
                                }
                            );

                        if (window.Swal) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Auto-Mapping Berhasil',
                                text: json.message
                            });
                        }

                        window.location.reload();
                    } catch (error) {
                        if (window.Swal) {
                            Swal.close();
                        }

                        swalHelper(
                            'error',
                            'Eksekusi Auto Gagal',
                            error.message
                        );
                    }
                }
            );

            function formatDateTime(value) {
                if (!value) {
                    return '-';
                }

                const date = new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return value;
                }

                return new Intl.DateTimeFormat(
                    'id-ID', {
                        dateStyle: 'medium',
                        timeStyle: 'short'
                    }
                ).format(date);
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            syncTransitionControls();
            loadMusyrifByTargetClass();
            updateAssignmentStatus();
            updateAutoAssignmentStatus();
        })();
    </script>
@endpush
