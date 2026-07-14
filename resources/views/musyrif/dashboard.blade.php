@extends('layouts.app')

@section('title', 'Dashboard Musyrif')

@section('content')

    <style>
        /* ================= KONSISTENSI TEMA PURPLE ================= */
        :root {
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .kpi-card {
            border-radius: 20px;
            background: #ffffff;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
            border: 1px solid rgba(0, 0, 0, 0.02) !important;
            overflow: hidden;
            backdrop-filter: blur(8px);
            /* Efek blur di belakang kartu */
            -webkit-backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.7) !important;
            /* Semi transparan putih */
        }


        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(111, 66, 193, 0.1);
        }

        .kpi-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--islamic-purple-700);
            line-height: 1;
        }

        .kpi-sub {
            font-size: 0.8rem;
            font-weight: 500;
            margin-top: 5px;
        }

        .kpi-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: var(--transition-smooth);
        }

        .kpi-card:hover .kpi-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        /* Progress Bar Refinement */
        .kpi-progress {
            height: 8px;
            background: #f0f2f5;
            border-radius: 10px;
            margin-top: 15px;
        }

        .kpi-progress-bar {
            border-radius: 10px;
            transition: width 1.5s cubic-bezier(0.1, 0.5, 0.5, 1);
        }

        /* Card Header Styling */
        .card-header-custom {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            color: var(--islamic-purple-700);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ================= FIX DARK THEME UNTUK KPI CARDS ================= */

        /* Gunakan selector data-coreui-theme untuk mendeteksi mode gelap */
        [data-coreui-theme="dark"] .kpi-card {
            background: var(--cui-card-bg, #2a2a35) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }


        [data-coreui-theme="dark"] .kpi-card {
            background: rgba(42, 42, 53, 0.6) !important;
            /* Semi transparan gelap */
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* Penyesuaian warna teks di mode gelap */
        [data-coreui-theme="dark"] .kpi-value {
            color: #ffffff !important;
        }

        [data-coreui-theme="dark"] .kpi-label {
            color: #a0a0a0;
        }

        [data-coreui-theme="dark"] .kpi-sub.text-muted {
            color: #8a8a8a !important;
        }

        /* Background icon box biar nggak terlalu kontras */
        [data-coreui-theme="dark"] .kpi-icon.bg-primary-subtle,
        [data-coreui-theme="dark"] .bg-light-subtle {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        /* Border untuk table agenda di dark mode */
        [data-coreui-theme="dark"] .card-header-custom {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    

        /* ================= SYSTEM REVIEW PROMPT ================= */
        .system-review-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(111, 66, 193, .14);
            border-radius: 20px;
            background:
                radial-gradient(circle at 94% 8%, rgba(111, 66, 193, .12), transparent 32%),
                var(--cui-card-bg, #fff);
            box-shadow: var(--card-shadow);
        }

        .system-review-card::after {
            content: '';
            position: absolute;
            right: -38px;
            bottom: -58px;
            width: 145px;
            height: 145px;
            border: 24px solid rgba(111, 66, 193, .055);
            border-radius: 50%;
            pointer-events: none;
        }

        .system-review-icon {
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            color: var(--islamic-purple-600);
            background: rgba(111, 66, 193, .11);
            font-size: 1.2rem;
        }

        .review-modal-content {
            overflow: hidden;
            border: 0;
            border-radius: 22px;
        }

        .review-modal-hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background:
                radial-gradient(circle at 92% 8%, rgba(255, 255, 255, .20), transparent 26%),
                linear-gradient(135deg, var(--islamic-purple-700, #59359d), var(--islamic-purple-600, #6f42c1));
        }

        .review-modal-hero::after {
            content: '';
            position: absolute;
            right: -45px;
            bottom: -78px;
            width: 160px;
            height: 160px;
            border: 25px solid rgba(255, 255, 255, .07);
            border-radius: 50%;
        }

        .review-star-group {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: .28rem;
        }

        .review-star-group input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .review-star-group label {
            cursor: pointer;
            color: #d9dde5;
            font-size: 2rem;
            line-height: 1;
            transition: transform .15s ease, color .15s ease;
        }

        .review-star-group label:hover,
        .review-star-group label:hover ~ label,
        .review-star-group input:checked ~ label {
            color: #ffc107;
            transform: translateY(-2px);
        }

        .review-character-count {
            color: var(--cui-secondary-color);
            font-size: .72rem;
        }

        [data-coreui-theme="dark"] .system-review-card {
            border-color: rgba(169, 155, 255, .18);
            background:
                radial-gradient(circle at 94% 8%, rgba(169, 155, 255, .13), transparent 32%),
                var(--cui-card-bg, #2a2a35);
        }

    </style>

    {{-- HEADER DASHBOARD --}}
    <div class="mb-4">
        <h4 class="fw-bold text-adaptive-purple mb-1">Assalamu'alaikum, Musyrif</h4>
        <p class="text-muted small">Berikut adalah ringkasan perkembangan hafalan santri Anda hari ini.</p>
    </div>

    {{-- ================== ROW KPI CARDS ================== --}}
    <div class="row g-3 mb-4">
        {{-- Santri Bimbingan --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="kpi-label">Bimbingan</div>
                            <div class="kpi-value count-up" data-target="{{ $jumlahSantri ?? 0 }}">0</div>
                            <div class="kpi-sub text-muted" style="font-size: 11px;">Total santri</div>
                        </div>
                        <div class="kpi-icon shadow-sm"
                            style="background-color: var(--islamic-purple-100); color: var(--islamic-purple-600);">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar" style="background-color: var(--islamic-purple-500); width:100%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Setoran Hari Ini --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Setor</div>
                            <div class="kpi-value count-up" data-target="{{ $setoranHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-success fw-bold" style="font-size: 11px;">Lulus / Ulang</div>
                        </div>
                        <div class="kpi-icon bg-success-subtle text-success shadow-sm">
                            <i class="bi bi-check-all"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-success"
                            style="width: {{ min(100, ($setoranHariIni ?? 0) * 10) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Hadir Tidak Setor --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Hadir (TS)</div>
                            <div class="kpi-value count-up" data-target="{{ $hadirTidakSetorHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-warning" style="font-size: 11px;">Tidak Setor</div>
                        </div>
                        <div class="kpi-icon bg-warning-subtle text-warning shadow-sm">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-warning"
                            style="width: {{ min(100, ($hadirTidakSetorHariIni ?? 0) * 10) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sakit --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Sakit</div>
                            <div class="kpi-value count-up" data-target="{{ $sakitHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-primary" style="font-size: 11px;">Berhalangan</div>
                        </div>
                        <div class="kpi-icon bg-primary-subtle text-primary shadow-sm">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-primary" style="width: {{ min(100, ($sakitHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Izin --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Izin</div>
                            <div class="kpi-value count-up" data-target="{{ $izinHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-secondary" style="font-size: 11px;">Berhalangan</div>
                        </div>
                        <div class="kpi-icon bg-secondary-subtle text-secondary shadow-sm">
                            <i class="bi bi-envelope-paper-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-secondary" style="width: {{ min(100, ($izinHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alpha --}}
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6">
            <div class="card kpi-card spotlight-card h-100 border-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="kpi-label">Alpha</div>
                            <div class="kpi-value count-up" data-target="{{ $alphaHariIni ?? 0 }}">0</div>
                            <div class="kpi-sub text-danger" style="font-size: 11px;">Tanpa Ket.</div>
                        </div>
                        <div class="kpi-icon bg-danger-subtle text-danger shadow-sm">
                            <i class="bi bi-person-x-fill"></i>
                        </div>
                    </div>
                    <div class="kpi-progress mt-2">
                        <div class="kpi-progress-bar bg-danger" style="width: {{ min(100, ($alphaHariIni ?? 0) * 10) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Setoran + Rata Nilai Combined --}}
        <div class="col-lg-12">
            <div class="card kpi-card spotlight-card border-0">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6 border-end">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon bg-success text-white">
                                    <i class="bi bi-journal-check"></i>
                                </div>
                                <div>
                                    <div class="kpi-label mb-0">Total Akumulasi Setoran</div>
                                    <div class="kpi-value count-up" data-target="{{ $totalSetoran ?? 0 }}">0</div>
                                </div>
                            </div>
                            <div class="kpi-progress mt-3">
                                <div class="kpi-progress-bar bg-success"
                                    style="width: {{ min(100, ($totalSetoran ?? 0) / 10) }}%"></div>
                            </div>
                        </div>
                        <div class="col-md-6 ps-md-4 mt-3 mt-md-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="kpi-icon bg-warning text-white">
                                    <i class="bi bi-star-fill"></i>
                                </div>
                                <div>
                                    <div class="kpi-label mb-0">Rata-rata Nilai (Juz Unik: {{ $totalJuzUnik ?? 0 }})</div>
                                    <div class="kpi-value count-up" data-target="{{ $rataNilai ?? 0 }}">0</div>
                                </div>
                            </div>
                            <div class="kpi-progress mt-3">
                                <div class="kpi-progress-bar bg-warning" style="width: {{ min(100, $rataNilai ?? 0) }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== ROW CHART & AGENDA ================== --}}
    <div class="row g-4">
        {{-- Chart 1: Setoran per Santri --}}
        <div class="col-lg-6">
            <div class="card kpi-card spotlight-card border-0 h-100">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-bar-chart-fill"></i> Setoran Hafalan per Santri (Top 7)
                </div>
                <div class="card-body">
                    <canvas id="chartSetoranSantri" height="280"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 2: Distribusi Status --}}
        <div class="col-lg-6">
            <div class="card kpi-card spotlight-card border-0 h-100">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-pie-chart-fill"></i> Distribusi Status Hafalan
                </div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="chartStatusHafalan" height="280"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 3: Distribusi Juz --}}
        <div class="col-lg-8">
            <div class="card kpi-card spotlight-card border-0">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-graph-up-arrow"></i> Distribusi Setoran per Juz
                </div>
                <div class="card-body">
                    <canvas id="chartJuzHafalan" height="300"></canvas>
                </div>
            </div>
        </div>

        {{-- Chart 4: Nilai per Santri --}}
        <div class="col-lg-4">
            <div class="card kpi-card spotlight-card border-0">
                <div class="card-header-custom text-adaptive-purple">
                    <i class="bi bi-award-fill"></i> Rata-rata Nilai (Top 7)
                </div>
                <div class="card-body">
                    <canvas id="chartNilaiSantri" height="300"></canvas>
                </div>
            </div>
        </div>

        {{-- Agenda Table --}}
        <div class="col-12">
            <div class="card kpi-card spotlight-card border-0 mb-4">
                <div class="card-header-custom text-adaptive-purple justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event-fill"></i> Real-time Setoran Santri Hari Ini
                    </div>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3">{{ count($agendaHarian) }}
                        Data</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light text-uppercase small fw-bold">
                                <tr>
                                    <th class="ps-4">Santri</th>
                                    <th>Kelas</th>
                                    <th>Juz</th>
                                    <th>Surah / Ayat</th>
                                    <th class="pe-4">Jam</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($agendaHarian as $item)
                                    <tr>
                                        <td class="ps-4 fw-bold text-adaptive-purple">{{ $item->santri?->nama ?? '-' }}
                                        </td>
                                        <td><span
                                                class="badge bg-light text-dark border">{{ $item->santri?->kelas?->nama_kelas ?? '-' }}</span>
                                        </td>
                                        <td>
                                            @if ($item->template)
                                                <span class="fw-bold">Juz {{ $item->template->juz ?? '-' }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="small">{{ $item->template?->label ?? '-' }}</td>
                                        <td class="pe-4 text-muted"><i
                                                class="bi bi-clock me-1"></i>{{ $item->created_at?->format('H:i') ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                            Belum ada aktivitas setoran hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================== SYSTEM REVIEW CTA ================== --}}
    <div class="system-review-card p-3 p-md-4 mt-4 mb-4">
        <div class="position-relative d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3"
            style="z-index: 1;">
            <div class="d-flex align-items-start gap-3">
                <span class="system-review-icon">
                    <i class="bi bi-chat-square-heart-fill"></i>
                </span>

                <div>
                    @if ($existingSystemReview)
                        <div class="fw-bold mb-1">Terima kasih atas review Anda</div>
                        <div class="small text-muted">
                            Review berbintang {{ $existingSystemReview->rating }} sudah tersimpan dan saat ini berstatus
                            <span class="fw-bold">{{ ucfirst($existingSystemReview->status) }}</span>.
                        </div>
                    @else
                        <div class="fw-bold mb-1">Bagaimana pengalaman Anda menggunakan sistem?</div>
                        <div class="small text-muted">
                            Masukan dari Musyrif membantu pengembangan fitur dan kemudahan penggunaan Pantau Hafalanku.
                        </div>
                    @endif
                </div>
            </div>

            @unless ($existingSystemReview)
                <button type="button" class="btn text-white rounded-pill px-4 fw-bold flex-shrink-0"
                    id="btnOpenSystemReview"
                    style="background: var(--islamic-purple-600);">
                    <i class="bi bi-star-fill me-1"></i>
                    Berikan Review
                </button>
            @else
                <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 flex-shrink-0">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Review Terkirim
                </span>
            @endunless
        </div>
    </div>

@endsection


@push('modals')
    @unless ($existingSystemReview)
        <div class="modal fade" id="systemReviewModal" tabindex="-1" aria-hidden="true"
            data-coreui-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <form class="modal-content review-modal-content shadow-lg" id="systemReviewForm" novalidate>
                    @csrf

                    <div class="modal-header review-modal-hero border-0 px-4 py-4">
                        <div class="position-relative" style="z-index: 1;">
                            <div class="small text-white-50 fw-bold text-uppercase mb-1">
                                Review Sistem
                            </div>
                            <h5 class="modal-title fw-bold text-white mb-1">
                                Bagikan Pengalaman Anda
                            </h5>
                            <p class="small text-white-50 mb-0">
                                Review tidak langsung tampil publik sebelum disetujui Super Admin.
                            </p>
                        </div>

                        <button type="button" class="btn-close btn-close-white position-relative"
                            style="z-index: 1;" data-coreui-dismiss="modal"
                            aria-label="Tutup"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="alert alert-info border-0 rounded-4 small">
                            Satu akun Musyrif hanya dapat mengirim satu review. Pastikan isi review sudah sesuai
                            sebelum dikirim.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Rating Sistem <span class="text-danger">*</span>
                            </label>

                            <div class="review-star-group" aria-label="Pilih rating">
                                @for ($rating = 5; $rating >= 1; $rating--)
                                    <input type="radio" name="rating" value="{{ $rating }}"
                                        id="systemReviewRating{{ $rating }}" required>
                                    <label for="systemReviewRating{{ $rating }}"
                                        title="{{ $rating }} bintang">
                                        <i class="bi bi-star-fill"></i>
                                    </label>
                                @endfor
                            </div>

                            <div class="invalid-feedback d-block d-none" id="systemReviewRatingError"></div>
                        </div>

                        <div class="mb-3">
                            <label for="systemReviewTitle" class="form-label fw-bold">
                                Judul Singkat
                                <span class="text-muted small">(opsional)</span>
                            </label>
                            <input type="text" class="form-control" name="title"
                                id="systemReviewTitle" maxlength="120"
                                placeholder="Contoh: Sangat membantu input setoran">
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between gap-3">
                                <label for="systemReviewText" class="form-label fw-bold">
                                    Review <span class="text-danger">*</span>
                                </label>
                                <span class="review-character-count">
                                    <span id="systemReviewCharacterCount">0</span>/1200
                                </span>
                            </div>

                            <textarea class="form-control" name="review" id="systemReviewText"
                                rows="5" minlength="20" maxlength="1200" required
                                placeholder="Ceritakan fitur yang membantu, kemudahan penggunaan, atau saran pengembangan..."></textarea>
                            <div class="invalid-feedback" id="systemReviewTextError"></div>
                        </div>

                        <div class="rounded-4 border p-3 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox"
                                    name="show_name" id="systemReviewShowName"
                                    value="1" checked>
                                <label class="form-check-label fw-semibold"
                                    for="systemReviewShowName">
                                    Tampilkan nama saya apabila review dipublikasikan
                                </label>
                            </div>
                            <div class="small text-muted mt-1">
                                Jika dinonaktifkan, nama publik ditampilkan sebagai “Musyrif Pengguna Sistem”.
                            </div>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                name="consent_publication"
                                id="systemReviewConsent" value="1" required>
                            <label class="form-check-label small"
                                for="systemReviewConsent">
                                Saya menyetujui review ini dapat ditampilkan pada landing page setelah melalui
                                moderasi Super Admin.
                            </label>
                            <div class="invalid-feedback" id="systemReviewConsentError"></div>
                        </div>

                        <div class="alert alert-danger border-0 rounded-4 small mt-3 d-none"
                            id="systemReviewGeneralError"></div>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-coreui-dismiss="modal">
                            Nanti
                        </button>
                        <button type="submit" class="btn text-white rounded-pill px-4 fw-bold"
                            id="btnSubmitSystemReview"
                            style="background: var(--islamic-purple-600);">
                            <i class="bi bi-send-fill me-1"></i>
                            Kirim Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endunless
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // MENGGUNAKAN JSON DATA YANG SAMA PERSIS
            const setoranSantriLabels = @json($chartSetoranPerSantri['labels'] ?? []);
            const setoranSantriData = @json($chartSetoranPerSantri['data'] ?? []);
            const nilaiSantriLabels = @json($chartNilaiPerSantri['labels'] ?? []);
            const nilaiSantriData = @json($chartNilaiPerSantri['data'] ?? []);
            const statusLabels = @json($chartStatus['labels'] ?? []);
            const statusData = @json($chartStatus['data'] ?? []);
            const juzLabels = @json($chartJuz['labels'] ?? []);
            const juzData = @json($chartJuz['data'] ?? []);

            // Letakkan di dalam DOMContentLoaded
            const cards = document.querySelectorAll(".spotlight-card");

            document.addEventListener("mousemove", (e) => {
                cards.forEach((card) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    card.style.setProperty("--mouse-x", `${x}px`);
                    card.style.setProperty("--mouse-y", `${y}px`);
                });
            });

            // Helper function untuk chart styling agar senada
            const purpleGradient = (ctx) => {
                const gradient = ctx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(111, 66, 193, 0.8)');
                gradient.addColorStop(1, 'rgba(111, 66, 193, 0.1)');
                return gradient;
            };

            // CHART 1: BAR – SETORAN PER SANTRI
            new Chart(document.getElementById('chartSetoranSantri').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: setoranSantriLabels,
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: setoranSantriData,
                        backgroundColor: 'rgba(111, 66, 193, 0.7)',
                        borderRadius: 8,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // CHART 2: DOUGHNUT – STATUS HAFALAN
            new Chart(document.getElementById('chartStatusHafalan').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545'],
                        borderWidth: 0,
                        hoverOffset: 15
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });

            // CHART 3: LINE – DISTRIBUSI PER JUZ
            new Chart(document.getElementById('chartJuzHafalan').getContext('2d'), {
                type: 'line',
                data: {
                    labels: juzLabels,
                    datasets: [{
                        label: 'Jumlah Setoran',
                        data: juzData,
                        borderColor: '#6f42c1',
                        backgroundColor: 'rgba(111, 66, 193, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#6f42c1',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // CHART 4: BAR – NILAI
            if (document.getElementById('chartNilaiSantri')) {
                new Chart(document.getElementById('chartNilaiSantri').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: nilaiSantriLabels,
                        datasets: [{
                            label: 'Rata-rata Nilai',
                            data: nilaiSantriData,
                            backgroundColor: 'rgba(255, 193, 7, 0.8)',
                            borderRadius: 8,
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            }

            // Simple Count Up Animation
            document.querySelectorAll('.count-up').forEach(el => {
                const target = +el.getAttribute('data-target');
                const duration = 1000;
                const increment = target / (duration / 16);
                let current = 0;
                const update = () => {
                    current += increment;
                    if (current < target) {
                        el.innerText = Math.floor(current);
                        requestAnimationFrame(update);
                    } else {
                        el.innerText = target;
                    }
                };
                update();
            });
        });
    </script>
@endpush


@push('scripts')
    @unless ($existingSystemReview)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modalElement = document.getElementById('systemReviewModal');
                const form = document.getElementById('systemReviewForm');
                const openButton = document.getElementById('btnOpenSystemReview');
                const reviewText = document.getElementById('systemReviewText');
                const characterCount = document.getElementById('systemReviewCharacterCount');
                const submitButton = document.getElementById('btnSubmitSystemReview');
                const generalError = document.getElementById('systemReviewGeneralError');

                if (!modalElement || !form) {
                    return;
                }

                const modal = coreui.Modal.getOrCreateInstance(modalElement);

                function clearReviewErrors() {
                    form.querySelectorAll('.is-invalid').forEach(function(element) {
                        element.classList.remove('is-invalid');
                    });

                    form.querySelectorAll('.invalid-feedback').forEach(function(element) {
                        element.textContent = '';
                    });

                    document.getElementById('systemReviewRatingError')?.classList.add('d-none');
                    generalError?.classList.add('d-none');
                    if (generalError) {
                        generalError.textContent = '';
                    }
                }

                function showValidationErrors(errors) {
                    Object.entries(errors || {}).forEach(function([field, messages]) {
                        const message = Array.isArray(messages)
                            ? messages[0]
                            : messages;

                        if (field === 'rating') {
                            const ratingError = document.getElementById('systemReviewRatingError');
                            if (ratingError) {
                                ratingError.textContent = message;
                                ratingError.classList.remove('d-none');
                            }
                            return;
                        }

                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                        }

                        const errorTarget = document.getElementById(
                            field === 'review'
                                ? 'systemReviewTextError'
                                : field === 'consent_publication'
                                    ? 'systemReviewConsentError'
                                    : ''
                        );

                        if (errorTarget) {
                            errorTarget.textContent = message;
                        }
                    });
                }

                openButton?.addEventListener('click', function() {
                    clearReviewErrors();
                    modal.show();
                });

                reviewText?.addEventListener('input', function() {
                    characterCount.textContent = String(this.value.length);
                });

                form.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    clearReviewErrors();

                    const originalHtml = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim...';

                    try {
                        const response = await fetch(
                            @json(route('musyrif.system-reviews.store')),
                            {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector(
                                        'meta[name="csrf-token"]'
                                    )?.getAttribute('content') ?? ''
                                },
                                body: new FormData(form)
                            }
                        );

                        const payload = await response.json().catch(function() {
                            return {};
                        });

                        if (response.status === 422) {
                            showValidationErrors(payload.errors);
                            return;
                        }

                        if (!response.ok) {
                            throw new Error(
                                payload.message || 'Review gagal dikirim.'
                            );
                        }

                        modal.hide();

                        if (window.AppAlert?.success) {
                            await AppAlert.success(payload.message);
                        } else if (window.Swal) {
                            await Swal.fire({
                                icon: 'success',
                                title: 'Review Terkirim',
                                text: payload.message
                            });
                        } else {
                            window.alert(payload.message);
                        }

                        window.location.reload();
                    } catch (error) {
                        generalError.textContent =
                            error.message || 'Review gagal dikirim.';
                        generalError.classList.remove('d-none');
                    } finally {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalHtml;
                    }
                });

                @if ($shouldPromptSystemReview)
                    window.setTimeout(function() {
                        modal.show();
                    }, 850);
                @endif
            });
        </script>
    @endunless
@endpush

