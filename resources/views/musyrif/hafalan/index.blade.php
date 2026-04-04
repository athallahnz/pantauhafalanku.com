@extends('layouts.app')

@section('title', 'Riwayat Hafalan Santri Binaan')

@section('content')
    <style>
        /* ================= TEMA ISLAMIC PURPLE & MODERN TABLE ================= */
        .text-adaptive-purple {
            color: var(--islamic-purple-700);
        }

        [data-coreui-theme="dark"] .text-adaptive-purple {
            color: #fff !important;
        }

        .main-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        .card-header-purple {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
        }

        /* Styling Filter Nav-Pills (Kapsul) */
        #filterTanggalGroup .nav-link {
            color: var(--cui-secondary-color);
            background-color: var(--cui-tertiary-bg);
            border: 1px solid var(--cui-border-color);
            border-radius: 50px;
            padding: 0.4rem 1rem;
            margin-right: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        #filterTanggalGroup .nav-link.active {
            background-color: var(--islamic-purple-600) !important;
            color: #ffffff !important;
            border-color: var(--islamic-purple-600) !important;
            box-shadow: 0 4px 10px rgba(111, 66, 193, 0.2);
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 24px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: var(--cui-tertiary-bg);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .badge-nilai {
            font-family: 'Amiri', serif;
            font-size: 1.1rem;
        }

        /* ==========================================================
                   MODAL GUIDE STYLES
                   ========================================================== */
        .guide-step {
            position: relative;
            border-left: 3px solid var(--cui-info);
            padding-left: 15px;
            margin-bottom: 20px;
        }

        .guide-number {
            position: absolute;
            left: -12px;
            top: 0;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cui-info);
            color: #fff;
            border-radius: 50%;
            font-size: 11px;
            font-weight: bold;
        }


        /* Container Utama di Pojok Kanan Bawah */
        .fab-group-wrapper {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1050;
            display: flex;
            align-items: center;
            gap: 12px;
            /* Jarak otomatis antar tombol */
        }

        /* Tombol Utama */
        .btn-fab-main {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50px;
            padding: 12px 24px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(111, 66, 193, 0.4);
            transition: all 0.3s ease;
        }

        /* Tombol Info (Secondary) */
        .btn-fab-info {
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--cui-secondary-bg, #e4e6e9);
            color: var(--cui-secondary-color, #4f5d73);
            border: 1px solid transparent;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Bubble Help - Diposisikan Relatif terhadap Container */
        .help-bubble {
            position: absolute;
            bottom: 65px;
            /* Jarak di atas tombol */
            left: -20px;
            /* Sesuaikan agar pas di tengah tombol info */
            background: #6f42c1;
            color: white;
            padding: 10px 18px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
            box-shadow: 0 10px 25px rgba(111, 66, 193, 0.3);
            animation: floatBubble 2s infinite ease-in-out;
            display: none;
            /* Muncul via JS */
        }

        .bubble-arrow {
            position: absolute;
            bottom: -8px;
            left: 35px;
            /* Menunjuk pas ke icon tanda tanya */
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
            border-top: 8px solid #6f42c1;
        }

        @keyframes floatBubble {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        /* Mode Gelap */
        [data-coreui-theme="dark"] .btn-fab-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: #fff;
        }

        /* ==========================================================
               MOBILE RESPONSIVE (Pojok Kiri & Pojok Kanan)
               ========================================================== */
        @media (max-width: 768px) {

            /* Container dilepas flex-nya agar tombol bisa mencar */
            .fab-group-wrapper {
                position: static;
                display: block;
            }

            /* TOMBOL INFO (KIRI BAWAH) */
            .btn-fab-info {
                position: fixed;
                left: 25px;
                /* Pojok kiri */
                bottom: 25px;
                /* Jarak dari bawah */
                width: 55px;
                height: 55px;
                border-radius: 50%;
                z-index: 1050;
            }

            /* TOMBOL UTAMA (KANAN BAWAH) */
            .btn-fab-main {
                position: fixed;
                right: 25px;
                /* Pojok kanan */
                bottom: 25px;
                /* Jarak dari bawah */
                width: 60px;
                height: 60px;
                padding: 0;
                /* Hapus padding teks */
                border-radius: 50%;
                z-index: 1050;
            }

            /* Sembunyikan teks "Input Hafalan" */
            .btn-fab-main .fab-text {
                display: none;
            }

            /* Icon plus dibuat lebih besar untuk jempol */
            .btn-fab-main i {
                font-size: 1.8rem;
                margin: 0;
            }

            /* BUBBLE HELP (Pindah mengikuti tombol info di kiri) */
            .help-bubble {
                position: fixed;
                bottom: 95px;
                left: 15px;
                /* Sejajar tombol info */
                right: auto;
                font-size: 11px;
                padding: 8px 12px;
            }

            .bubble-arrow {
                left: 25px;
                /* Panah menunjuk ke tombol kiri */
                right: auto;
            }
        }
    </style>

    <div class="row mb-4 align-items-center px-3 px-md-0 g-3">
        <div class="col-12 col-md-auto text-start">
            <h4 class="fw-bold text-adaptive-purple mb-1">Manajemen Hafalan</h4>
            <p class="text-muted small mb-0">Pantau dan kelola progress setoran hafalan santri binaan Anda.</p>
        </div>
    </div>

    <div class="card main-card spotlight-card">
        <div class="card-header card-header-purple bg-light bg-opacity-10 py-3 px-3 px-md-4">
            <div
                class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">

                <div class="w-100 w-md-auto overflow-auto">
                    <ul class="nav nav-pills nav-pills-sm flex-nowrap" id="filterTanggalGroup" role="tablist">
                        <li class="nav-item"><button class="nav-link active text-nowrap" type="button"
                                data-filter="today">Hari Ini</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                data-filter="yesterday">Kemarin</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" type="button" data-filter="last_7_days">7
                                Hari</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                data-filter="this_month">Bulan Ini</button></li>
                        <li class="nav-item"><button class="nav-link text-nowrap" type="button"
                                data-filter="all">Semuanya</button></li>
                    </ul>
                </div>

                <div class="w-100 w-md-auto text-md-end">
                    <span
                        class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 d-block d-md-inline-block shadow-sm"
                        id="filterBadge" style="min-width: 150px; border: 1px solid rgba(111, 66, 193, 0.1);">
                        <i class="bi bi-info-circle me-1"></i> Menampilkan: Hari Ini
                    </span>
                </div>

            </div>
        </div>

        <div class="card-body card-body-table table-responsive">
            <table id="hafalan-table" class="table table-hover align-middle w-100 text-nowrap">
                <thead class="bg-light bg-opacity-50">
                    <tr class="text-muted small fw-bold text-uppercase" style="letter-spacing: 1px;">
                        <th>No.</th>
                        <th>Santri</th>
                        <th>Kelas</th>
                        <th>Juz</th>
                        <th>Surah / Ayat</th>
                        <th>Tanggal</th>
                        <th>Nilai</th>
                        <th>Tahap</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Diisi otomatis oleh DataTables via AJAX --}}
                </tbody>
            </table>
        </div>
    </div>

@endsection

@push('modals')
    <div class="fab-group-wrapper">
        <div id="bubbleHelp" class="help-bubble">
            <div class="bubble-content">
                <i class="bi bi-magic me-1"></i> Bingung cara input? Klik di sini!
            </div>
            <div class="bubble-arrow"></div>
        </div>

        <button class="btn btn-fab-info" data-coreui-toggle="modal" data-coreui-target="#modalPanduanMusyrif">
            <i class="bi bi-question-circle-fill"></i>
        </button>

        <button class="btn btn-primary btn-fab-main" id="btnAddHafalan">
            <i class="bi bi-plus-lg"></i>
            <span class="fab-text">Input Hafalan</span>
        </button>
    </div>

    <div class="modal fade" id="modalPanduanMusyrif" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 25px;">
                <div class="modal-header px-4">
                    <h5 class="modal-title fw-bold text-adaptive-purple"><i class="bi bi-lightbulb-fill me-2"></i>Panduan
                        Input Hafalan</h5>
                    <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="guide-step">
                        <span class="guide-number">1</span>
                        <h6 class="fw-bold mb-1">Buka Form Input</h6>
                        <p class="text-muted small mb-0">Klik tombol ungu (+) di pojok kanan bawah. Tanggal otomatis diset
                            hari ini.</p>
                    </div>

                    <div class="guide-step">
                        <span class="guide-number">2</span>
                        <h6 class="fw-bold mb-1">Pilih Santri Binaan</h6>
                        <p class="text-muted small mb-0">Pilih nama santri. Pastikan santri tersebut memang berada di bawah
                            bimbingan Anda.</p>
                    </div>

                    <div class="guide-step">
                        <span class="guide-number">3</span>
                        <h6 class="fw-bold mb-1">Tentukan Status Kehadiran</h6>
                        <p class="text-muted small mb-0">
                            <b>Lulus/Ulang:</b> Membuka pilihan materi.<br>
                            <b>Alpha:</b> Otomatis memberikan 1 poin pelanggaran ke santri.
                        </p>
                    </div>

                    <div class="guide-step">
                        <span class="guide-number">4</span>
                        <h6 class="fw-bold mb-1">Filter Materi (Juz & Tahap)</h6>
                        <p class="text-muted small mb-0">Pilih Juz dan Tahapan (Harian/Ujian) terlebih dahulu agar daftar
                            Surah/Ayat muncul di kolom Template.</p>
                    </div>

                    <div class="guide-step" style="border-left-color: #ffc107;">
                        <span class="guide-number" style="background: #ffc107;">5</span>
                        <h6 class="fw-bold mb-1">Input Nilai & Catatan</h6>
                        <p class="text-muted small mb-0">Pilih Taqdir (Mumtaz/Jayyid). Tambahkan catatan jika ada koreksi
                            tajwid atau kelancaran.</p>
                    </div>

                    <div class="guide-step" style="border-left-color: #198754; margin-bottom: 0;">
                        <span class="guide-number" style="background: #198754;">6</span>
                        <h6 class="fw-bold mb-1">Validasi & Simpan</h6>
                        <p class="text-muted small mb-0">Sistem akan menolak jika materi yang sama sudah diinput hari ini
                            (mencegah data ganda).</p>
                    </div>

                    <div class="alert alert-secondary mt-4 mb-0 rounded-4 border-0 small">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Gunakan fitur <b>Filter Tanggal</b> di halaman utama untuk melihat kembali riwayat setoran
                        sebelumnya.
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-primary w-100 py-2 rounded-pill fw-bold"
                        data-coreui-dismiss="modal">Saya Mengerti</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== MODAL CREATE ===================== --}}
    <div class="modal fade" id="modalCreateHafalan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formCreateHafalan">
                @csrf
                <div class="modal-content shadow-lg">
                    <div class="modal-header px-4">
                        <h5 class="modal-title fw-bold text-adaptive-purple"><i class="bi bi-journal-plus me-2"></i>Input
                            Setoran Hafalan</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">TANGGAL SETORAN</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-body-tertiary border-end-0">
                                        <i class="bi bi-calendar-check"></i>
                                    </span>
                                    <input type="text" name="tanggal_setoran" id="tanggal_create"
                                        class="form-control bg-body-tertiary border-start-0"
                                        placeholder="Pilih Tanggal..." readonly>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">SANTRI BINAAN</label>
                                <select name="santri_id" id="create_santri_id" class="form-select select2-basic"
                                    required>
                                    <option value="">-- Pilih Santri --</option>
                                    @foreach ($santriBinaan as $santri)
                                        <option value="{{ $santri->id }}">{{ $santri->nama }}
                                            {{ $santri->kelas ? '(' . $santri->kelas->nama_kelas . ')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">JUZ</label>
                                <select id="create_juz_ui" class="form-select" required>
                                    <option value="">-- Pilih Juz --</option>
                                    @for ($i = 1; $i <= 30; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">TAHAPAN</label>
                                <select id="create_tahap_ui" class="form-select" required>
                                    <option value="harian">Harian</option>
                                    <option value="tahap_1">Tahap 1</option>
                                    <option value="tahap_2">Tahap 2</option>
                                    <option value="tahap_3">Tahap 3</option>
                                    <option value="ujian_akhir">Ujian Akhir</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-primary">SURAH : AYAT (TEMPLATES)</label>
                                <select name="hafalan_template_id" id="create_template_id"
                                    class="form-select border-primary border-opacity-25 adaptive-select">
                                    <option value="">-- Pilih Juz & Tahapan dulu --</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">STATUS</label>
                                <select name="status" id="create_status" class="form-select fw-bold" required>
                                    <option value="lulus" class="text-success">Lulus</option>
                                    <option value="ulang" class="text-warning">Ulang</option>
                                    <option value="hadir_tidak_setor" class="text-info">Hadir Tidak Setor</option>
                                    <option value="alpha" class="text-danger">Alpha</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NILAI (TAQDIR)</label>
                                <select name="nilai_label" id="create_nilai_label" class="form-select fw-bold">
                                    <option value="">-- Pilih Nilai --</option>
                                    <option value="mumtaz">ممتاز (Mumtaz)</option>
                                    <option value="jayyid_jiddan">جيد جدًا (Jayyid Jiddan)</option>
                                    <option value="jayyid">جيد (Jayyid)</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">CATATAN MUSYRIF (OPTIONAL)</label>
                                <textarea name="catatan" id="create_catatan" class="form-control" rows="2"
                                    placeholder="Catatan tajwid, kelancaran, adab, dll."></textarea>
                            </div>
                        </div>

                        <div class="alert alert-warning py-2 mt-3 d-none rounded-3 border-0" id="create_hint">
                            <i class="bi bi-info-circle-fill me-2"></i><small id="create_hint_text"></small>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow">Simpan Setoran</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== MODAL EDIT (REFACTORED STYLE) ===================== --}}
    <div class="modal fade" id="modalEditHafalan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formEditHafalan">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_id">
                <div class="modal-content shadow-lg">
                    <div class="modal-header px-4">
                        <h5 class="modal-title fw-bold text-adaptive-purple"><i class="bi bi-pencil-square me-2"></i>Edit
                            Setoran Hafalan</h5>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">TANGGAL SETORAN</label>
                                <input type="text" name="tanggal_setoran" id="tanggal_edit"
                                    class="form-control bg-light" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">SANTRI</label>
                                <select name="santri_id" id="edit_santri_id" class="form-select" required>
                                    @foreach ($santriBinaan as $santri)
                                        <option value="{{ $santri->id }}">{{ $santri->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">JUZ</label>
                                <input type="number" id="edit_juz_ui" class="form-control" min="1"
                                    max="30" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">TAHAPAN</label>
                                <select id="edit_tahap_ui" class="form-select" required>
                                    <option value="harian">Harian</option>
                                    <option value="tahap_1">Tahap 1</option>
                                    <option value="tahap_2">Tahap 2</option>
                                    <option value="tahap_3">Tahap 3</option>
                                    <option value="ujian_akhir">Ujian Akhir</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-primary">SURAH : AYAT</label>
                                <select name="hafalan_template_id" id="edit_template_id"
                                    class="form-select border-primary border-opacity-25"></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">STATUS</label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="lulus">Lulus</option>
                                    <option value="ulang">Ulang</option>
                                    <option value="hadir_tidak_setor">Hadir Tidak Setor</option>
                                    <option value="alpha">Alpha</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NILAI</label>
                                <select name="nilai_label" id="edit_nilai_label" class="form-select">
                                    <option value="">-- Pilih Nilai --</option>
                                    <option value="mumtaz">ممتاز</option>
                                    <option value="jayyid_jiddan">جيد جدًا</option>
                                    <option value="jayyid">جيد</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">CATATAN MUSYRIF</label>
                                <textarea name="catatan" id="edit_catatan" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="alert alert-warning py-2 mt-3 d-none rounded-3 border-0" id="edit_hint">
                            <i class="bi bi-info-circle-fill me-2"></i><small id="edit_hint_text"></small>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-coreui-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow">Update Setoran</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===================== OPTION 1: ACHIEVEMENT CARD ===================== --}}
    <div class="modal fade" id="modalDetailHafalan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 28px;">
                <div class="p-4 text-center"
                    style="background: linear-gradient(135deg, var(--islamic-purple-50) 0%, #ffffff 100%); border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge rounded-pill bg-dark px-3 py-2" id="detail_tahap"
                            style="font-size: 10px; letter-spacing: 1px;"></span>
                        <button type="button" class="btn-close" data-coreui-dismiss="modal"></button>
                    </div>
                    <h3 class="fw-bold text-primary mb-1" id="detail_rentang" style="letter-spacing: -0.5px;"></h3>
                    <p class="text-muted small fw-bold mb-0 text-uppercase" style="letter-spacing: 2px;">Juz <span
                            id="detail_juz"></span></p>
                </div>

                <div class="modal-body p-4">
                    <div class="d-flex align-items-center mb-4 p-3 rounded-4 bg-light border border-white shadow-sm">
                        <div class="flex-shrink-0 bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                            style="width: 50px; height: 50px;">
                            <i class="bi bi-person-fill text-primary fs-4"></i>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold mb-0" id="detail_santri"></h6>
                            <small class="text-muted" id="detail_kelas"></small>
                        </div>
                        <div class="ms-auto text-end">
                            <div id="detail_status"></div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="p-3 rounded-4 border border-light text-center bg-white h-100">
                                <small class="text-muted d-block mb-1 fw-bold" style="font-size: 10px;">TANGGAL</small>
                                <span class="fw-semibold small" id="detail_tanggal"></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-4 border border-light text-center bg-white h-100">
                                <small class="text-muted d-block mb-1 fw-bold" style="font-size: 10px;">NILAI</small>
                                <h4 class="badge-nilai text-primary mb-0" id="detail_nilai"
                                    style="font-family: 'Amiri', serif;"></h4>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-4 border-start border-primary border-4 bg-primary-subtle bg-opacity-10">
                        <small class="fw-bold text-primary d-block mb-1" style="font-size: 10px;"><i
                                class="bi bi-chat-left-text-fill me-1"></i> EVALUASI MUSYRIF</small>
                        <p class="small mb-0 fst-italic text-dark text-opacity-75" id="detail_catatan"></p>
                    </div>
                </div>

                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm"
                        data-coreui-dismiss="modal">Tutup & Kembali</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    {{-- LOGIKA UTUH 100% - SEARCHING FIXED --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ================== Filter Tanggal (Button Group) ==================
            let filterTanggal = 'today';

            const filterLabels = {
                all: 'Semua Riwayat',
                today: 'Hari Ini',
                yesterday: 'Kemarin',
                last_7_days: '7 Hari Terakhir',
                this_month: 'Bulan Ini'
            };

            const modalCreate = new coreui.Modal(document.getElementById('modalCreateHafalan'));
            const modalEdit = new coreui.Modal(document.getElementById('modalEditHafalan'));
            const modalDetail = new coreui.Modal(document.getElementById('modalDetailHafalan'));

            const ROUTE_TEMPLATES = @json(route('musyrif.hafalan.templates'));

            function todayISO() {
                const d = new Date();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                return `${d.getFullYear()}-${mm}-${dd}`;
            }

            function formatTanggalIndonesia(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                return d.toLocaleDateString('id-ID', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }

            function tahapLabel(v) {
                return ({
                    'harian': 'Harian',
                    'tahap_1': 'Tahap 1',
                    'tahap_2': 'Tahap 2',
                    'tahap_3': 'Tahap 3',
                    'ujian_akhir': 'Ujian Akhir',
                })[v] || '-';
            }

            function statusLabel(v) {
                return ({
                    'lulus': 'Lulus',
                    'ulang': 'Ulang',
                    'hadir_tidak_setor': 'Hadir Tidak Setor',
                    'alpha': 'Alpha',
                })[v] || '-';
            }

            function nilaiArab(v) {
                return ({
                    'mumtaz': 'ممتاز',
                    'jayyid_jiddan': 'جيد جدًا',
                    'jayyid': 'جيد',
                })[v] || '-';
            }

            async function fetchTemplates(juz, tahap) {
                const url =
                    `${ROUTE_TEMPLATES}?juz=${encodeURIComponent(juz)}&tahap=${encodeURIComponent(tahap)}`;
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) return {
                    ok: false,
                    templates: []
                };
                return await res.json();
            }

            async function loadTemplateOptions(mode) {
                const juzEl = document.getElementById(`${mode}_juz_ui`);
                const tahapEl = document.getElementById(`${mode}_tahap_ui`);
                const tplEl = document.getElementById(`${mode}_template_id`);
                if (!tplEl) return;

                const juz = juzEl?.value;
                const tahap = tahapEl?.value;
                tplEl.innerHTML = `<option value="">-- Memuat... --</option>`;

                if (!juz || !tahap) {
                    tplEl.innerHTML = `<option value="">-- Pilih Juz & Tahapan dulu --</option>`;
                    return;
                }

                const json = await fetchTemplates(juz, tahap);
                if (!json.ok) {
                    tplEl.innerHTML = `<option value="">-- Gagal memuat template --</option>`;
                    return;
                }
                if (!json.templates || json.templates.length === 0) {
                    tplEl.innerHTML = `<option value="">-- Template tidak ditemukan --</option>`;
                    return;
                }

                tplEl.innerHTML = `<option value="">-- Pilih Surah:Ayat --</option>`;
                json.templates.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = `${t.urutan}. ${t.label}`;
                    tplEl.appendChild(opt);
                });
            }

            function syncRules(mode) {
                const statusEl = document.getElementById(`${mode}_status`);
                const tplEl = document.getElementById(`${mode}_template_id`);
                const nilaiEl = document.getElementById(`${mode}_nilai_label`);
                const hintBox = document.getElementById(`${mode}_hint`);
                const hintText = document.getElementById(`${mode}_hint_text`);

                if (!statusEl || !tplEl || !nilaiEl) return;
                const status = statusEl.value;
                const isSetor = (status === 'lulus' || status === 'ulang');

                tplEl.disabled = !isSetor;
                nilaiEl.disabled = !isSetor;

                if (!isSetor) {
                    tplEl.value = '';
                    nilaiEl.value = '';
                    if (hintBox && hintText) {
                        hintBox.classList.remove('d-none');
                        hintText.textContent = (status === 'alpha') ?
                            'Status Alpha: tidak ada setoran (template & nilai dinonaktifkan).' :
                            'Status Hadir Tidak Setor: tidak ada setoran (template & nilai dinonaktifkan).';
                    }
                } else {
                    if (hintBox) hintBox.classList.add('d-none');
                }
            }

            // ================== DataTables (SEARCH FIXED) ==================
            const table = $('#hafalan-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('musyrif.hafalan.datatable') }}",
                    data: function(d) {
                        d.filter_tanggal = filterTanggal;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'santri',
                        name: 'santri',
                        orderable: false,
                        searchable: true
                    }, // FIXED: true
                    {
                        data: 'kelas',
                        name: 'kelas',
                        orderable: false,
                        searchable: true
                    }, // FIXED: true
                    {
                        data: 'template_juz',
                        name: 'template_juz',
                        searchable: true
                    }, // FIXED: true
                    {
                        data: 'template_label',
                        name: 'template_label',
                        orderable: false,
                        searchable: true
                    }, // FIXED: true
                    {
                        data: 'tanggal',
                        name: 'tanggal_setoran',
                        searchable: false
                    },
                    {
                        data: 'nilai_label',
                        name: 'nilai_label',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'template_tahap',
                        name: 'template_tahap',
                        orderable: false,
                        searchable: true
                    }, // FIXED: true
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: true
                    }, // FIXED: true
                    {
                        data: 'aksi',
                        name: 'aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-end text-nowrap'
                    }
                ],
                order: [
                    [5, 'desc']
                ]
            });

            setTimeout(() => {
                try {
                    const bubble = document.getElementById('bubbleHelp');
                    // Gunakan Instance CoreUI jika ada Toast (opsional)
                    if (typeof coreui !== 'undefined') {
                        const toastEl = document.getElementById('toastAutoHelp');
                        if (toastEl) {
                            coreui.Toast.getOrCreateInstance(toastEl).show();
                        }
                    }

                    if (bubble) {
                        bubble.style.display = 'block'; // Munculkan

                        setTimeout(() => {
                            bubble.classList.add('hide'); // Animasi fade out
                            setTimeout(() => {
                                if (bubble) bubble.remove();
                            }, 500);
                        }, 6000); // 6 detik tampil
                    }
                } catch (e) {
                    console.warn("UI Helper failed to load safely:", e);
                }
            }, 1200);

            // ================== Filter Tanggal ==================
            $('#filterTanggalGroup button').on('click', function() {
                if ($(this).hasClass('active')) return;
                $('#filterTanggalGroup button').removeClass('active');
                $(this).addClass('active');
                filterTanggal = $(this).data('filter');
                $('#filterBadge').html('<i class="bi bi-info-circle me-1"></i> Menampilkan: ' +
                    filterLabels[filterTanggal]);
                table.ajax.reload(null, true);
            });

            // ================== Open Create ==================
            $('#btnAddHafalan').on('click', function() {
                $('#formCreateHafalan')[0].reset();
                const tglCreate = document.getElementById('tanggal_create');
                if (tglCreate) {
                    const iso = todayISO();
                    tglCreate.value = formatTanggalIndonesia(iso);
                    tglCreate.dataset.iso = iso;
                }
                const tplEl = document.getElementById('create_template_id');
                if (tplEl) tplEl.innerHTML = `<option value="">-- Pilih Juz & Tahapan dulu --</option>`;
                syncRules('create');
                modalCreate.show();
            });

            $('#create_juz_ui, #create_tahap_ui').on('change', function() {
                loadTemplateOptions('create');
            });
            $('#create_status').on('change', function() {
                syncRules('create');
            });

            // ================== Store (Create) ==================
            $('#formCreateHafalan').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('musyrif.hafalan.store') }}",
                    type: 'POST',
                    data: $('#formCreateHafalan').serialize(),
                    success: function(res) {
                        modalCreate.hide();
                        table.ajax.reload(null, true);
                        if (window.AppAlert) {
                            AppAlert.success(res.message ??
                                'Setoran hafalan berhasil disimpan.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan.';

                        // 1. Cek jika ada error validasi Laravel (errors: { field: [msg] })
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        }
                        // 2. Cek jika ada pesan kustom (message: "...")
                        else if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        if (window.AppAlert) {
                            AppAlert.error(msg);
                        }
                    }
                });
            });

            // ================== Open Edit ==================
            $(document).on('click', '.btn-edit', async function() {
                const d = $(this).data();
                $('#edit_id').val(d.id);
                $('#edit_santri_id').val(d.santri_id);
                if (d.tanggal_ymd) {
                    $('#tanggal_edit').val(formatTanggalIndonesia(d.tanggal_ymd)).attr('data-iso', d
                        .tanggal_ymd);
                }
                $('#edit_status').val(d.status || 'hadir_tidak_setor');
                $('#edit_nilai_label').val(d.nilai_label || '');
                $('#edit_catatan').val(d.catatan || '');
                $('#edit_juz_ui').val(d.template_juz || '');
                $('#edit_tahap_ui').val(d.template_tahap || 'harian');
                await loadTemplateOptions('edit');
                $('#edit_template_id').val(d.hafalan_template_id || '');
                syncRules('edit');
                const iso = d.tanggal_ymd || todayISO();
                $('#tanggal_edit').val(formatTanggalIndonesia(iso)).attr('data-iso', iso);
                modalEdit.show();
            });

            $('#edit_juz_ui, #edit_tahap_ui').on('change', function() {
                loadTemplateOptions('edit');
            });
            $('#edit_status').on('change', function() {
                syncRules('edit');
            });

            // ================== Update (Edit) ==================
            $('#formEditHafalan').on('submit', function(e) {
                e.preventDefault();
                const id = $('#edit_id').val();
                const url = "{{ url('musyrif/hafalan') }}/" + id;
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: $('#formEditHafalan').serialize(),
                    success: function(res) {
                        modalEdit.hide();
                        table.ajax.reload(null, true);
                        if (window.AppAlert) {
                            AppAlert.success(res.message ??
                                'Setoran hafalan berhasil diupdate.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan.';

                        // 1. Cek jika ada error validasi Laravel (errors: { field: [msg] })
                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            msg = Object.values(xhr.responseJSON.errors).map(e => e[0]).join(
                                '\n');
                        }
                        // 2. Cek jika ada pesan kustom (message: "...")
                        else if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        if (window.AppAlert) {
                            AppAlert.error(msg);
                        }
                    }
                });
            });

            // ================== Detail ==================
            $(document).on('click', '.btn-detail', function() {
                const d = $(this).data();
                $('#detail_santri').text(d.santri || '-');
                $('#detail_kelas').text(d.kelas || '-');
                $('#detail_juz').text(d.template_juz || '-');
                $('#detail_rentang').text(d.template_label || '-');
                $('#detail_tanggal').text(d.tanggal_label || '-');
                $('#detail_nilai').text(nilaiArab(d.nilai_label) || '-');
                $('#detail_tahap').text(tahapLabel(d.template_tahap));
                $('#detail_status').html(
                    `<span class="badge bg-primary-subtle text-primary rounded-pill px-3">${statusLabel(d.status)}</span>`
                );
                $('#detail_catatan').text(d.catatan || '-');
                modalDetail.show();
            });

            // ================== Delete ==================
            $(document).on('click', '.btn-delete', function() {
                const id = $(this).data('id');
                if (!window.AppAlert) return;
                AppAlert.warning('Data hafalan tidak dapat dikembalikan!', 'Hapus Setoran?')
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({
                            url: "{{ url('musyrif/hafalan') }}/" + id,
                            type: 'POST',
                            data: {
                                _method: 'DELETE',
                                _token: "{{ csrf_token() }}"
                            },
                            success: function(res) {
                                table.ajax.reload(null, true);
                                AppAlert.success(res.message ??
                                    'Setoran hafalan berhasil dihapus.');
                            },
                            error: function() {
                                AppAlert.error('Tidak dapat menghapus setoran hafalan.');
                            }
                        });
                    });
            });
        });
    </script>
@endpush
