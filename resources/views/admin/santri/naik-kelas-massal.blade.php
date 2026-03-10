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

    {{-- SEMESTER INFO CARD --}}
    <div class="card semester-status-card shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
            <div>
                <div class="opacity-75 small text-uppercase fw-bold mb-1">Semester Aktif Saat Ini</div>
                <h3 class="mb-0 fw-bold">
                    @if ($semesterAktif)
                        {{ strtoupper($semesterAktif->nama) }}
                    @else
                        BELUM ADA SEMESTER AKTIF
                    @endif
                </h3>
            </div>
            <div class="text-end">
                <i class="bi bi-calendar-check" style="font-size: 2.5rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>

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
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Musyrif Baru (Opsional)</label>
                    <select class="form-select shadow-xs" id="toMusyrifId" {{ !$semesterAktif ? 'disabled' : '' }}>
                        <option value="">(Tetap/Tidak Diubah)</option>
                        @foreach ($musyrifList as $m)
                            <option value="{{ $m->id }}">{{ $m->nama }}</option>
                        @endforeach
                    </select>
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
                        <button class="btn btn-outline-primary px-3 rounded-pill fw-bold" id="btnPreview" disabled>
                            <i class="bi bi-eye"></i> Preview Manual
                        </button>
                        <button class="btn btn-execute px-4 rounded-pill fw-bold shadow-sm" id="btnExecute" disabled>
                            <i class="bi bi-lightning-fill"></i> Eksekusi Manual
                        </button>

                        <div class="vr mx-2 d-none d-lg-block"></div>

                        {{-- Auto Buttons --}}
                        <button class="btn btn-primary px-3 rounded-pill fw-bold shadow-sm" id="btnAutoPreview">
                            <i class="bi bi-magic"></i> Auto-Mapping Preview
                        </button>
                        <button class="btn btn-execute px-4 rounded-pill fw-bold shadow-sm" id="btnAutoExecute" disabled>
                            <i class="bi bi-rocket-takeoff-fill"></i> Eksekusi Auto
                        </button>
                    </div>
                </div>
            </div>

            {{-- PREVIEW AREA --}}
            <div class="mt-4 d-none" id="previewBox">
                <div class="alert alert-info border-0 rounded-3 shadow-sm d-flex align-items-center gap-3">
                    <i class="bi bi-info-circle-fill fs-4"></i>
                    <div>
                        <strong>Preview Berhasil!</strong> Silakan periksa daftar santri di bawah ini sebelum menekan tombol
                        Eksekusi.
                    </div>
                </div>

                <div class="table-responsive border rounded-4 overflow-hidden mt-3">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold">
                            <tr>
                                <th class="ps-4" style="width:60px;">#</th>
                                <th>Nama Santri</th>
                                <th class="pe-4" style="width:140px;">NIS</th>
                            </tr>
                        </thead>
                        <tbody id="previewRows"></tbody>
                    </table>
                </div>
                <div class="text-center mt-3 text-muted small italic">
                    * Menampilkan maksimal 30 santri pertama untuk keperluan ringkasan preview.
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            // Gunakan SweetAlert secara konsisten jika tersedia
            const swalHelper = (icon, title, text) => {
                if (window.Swal) {
                    Swal.fire({
                        icon,
                        title,
                        text
                    });
                } else {
                    alert(text);
                }
            };

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const semesterId = {{ $semesterAktif?->id ?? 'null' }};

            const fromKelasId = document.getElementById('fromKelasId');
            const toKelasId = document.getElementById('toKelasId');
            const toMusyrifId = document.getElementById('toMusyrifId');
            const tipe = document.getElementById('tipe');
            const catatan = document.getElementById('catatan');
            const btnPreview = document.getElementById('btnPreview');
            const btnExecute = document.getElementById('btnExecute');
            const countInfo = document.getElementById('countInfo');
            const previewBox = document.getElementById('previewBox');
            const previewRows = document.getElementById('previewRows');
            const btnAutoPreview = document.getElementById('btnAutoPreview');
            const btnAutoExecute = document.getElementById('btnAutoExecute');

            let lastCount = 0;
            let autoLast = null;

            function resetFlow() {
                lastCount = 0;
                countInfo.textContent = '0 Santri';
                countInfo.className = 'h5 mb-0 text-primary fw-bold';
                btnExecute.disabled = true;
                previewBox.classList.add('d-none');
                previewRows.innerHTML = '';
                togglePreviewEnable();
            }

            function togglePreviewEnable() {
                const ok = !!(semesterId && fromKelasId.value && toKelasId.value) && (fromKelasId.value !== toKelasId
                    .value);
                btnPreview.disabled = !ok;
            }

            // Event Listeners for reset
            [fromKelasId, toKelasId, toMusyrifId, tipe, catatan].forEach(el => {
                el.addEventListener('change', resetFlow);
                el.addEventListener('input', resetFlow);
            });
            [fromKelasId, toKelasId].forEach(el => el.addEventListener('change', togglePreviewEnable));

            // Helper AJAX
            async function postJson(url, payload) {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(json?.message || 'Terjadi kesalahan sistem.');
                return json;
            }

            // Flow Manual: Preview
            btnPreview.addEventListener('click', async function() {
                try {
                    if (!fromKelasId.value || !toKelasId.value) throw new Error(
                        'Pilih kelas asal dan tujuan.');

                    if (window.Swal) Swal.fire({
                        title: 'Memproses preview...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const payload = {
                        semester_id: parseInt(semesterId, 10),
                        from_kelas_id: parseInt(fromKelasId.value, 10),
                        to_kelas_id: parseInt(toKelasId.value, 10),
                        to_musyrif_id: toMusyrifId.value ? parseInt(toMusyrifId.value, 10) : null,
                        tipe: tipe.value,
                        catatan: catatan.value
                    };

                    const json = await postJson(`{{ route('admin.santri.migrasi.massal.preview') }}`,
                        payload);
                    if (window.Swal) Swal.close();

                    lastCount = json.count || 0;
                    countInfo.textContent = `${lastCount} Santri`;
                    countInfo.className = 'h5 mb-0 text-success fw-bold';

                    previewRows.innerHTML = (json.santris || []).map((s, i) => `
                        <tr>
                            <td class="ps-4">${i + 1}</td>
                            <td class="fw-bold">${s.nama ?? '-'}</td>
                            <td class="pe-4 text-muted">${s.nis ?? '-'}</td>
                        </tr>
                    `).join('');

                    previewBox.classList.remove('d-none');
                    btnExecute.disabled = lastCount === 0;

                    swalHelper('success', 'Preview OK',
                        `Ditemukan ${lastCount} santri yang siap dipindahkan.`);
                } catch (e) {
                    swalHelper('error', 'Gagal', e.message);
                }
            });

            // Flow Manual: Execute
            btnExecute.addEventListener('click', async function() {
                try {
                    if (window.Swal) {
                        const confirm = await Swal.fire({
                            icon: 'warning',
                            title: 'Konfirmasi Eksekusi',
                            html: `Pindahkan <b>${lastCount} santri</b> ke kelas tujuan?<br><small class="text-danger">Aksi ini tidak dapat dibatalkan.</small>`,
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Pindahkan!',
                            confirmButtonColor: '#198754'
                        });
                        if (!confirm.isConfirmed) return;
                        Swal.fire({
                            title: 'Mengeksekusi...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    }

                    const payload = {
                        semester_id: parseInt(semesterId, 10),
                        from_kelas_id: parseInt(fromKelasId.value, 10),
                        to_kelas_id: parseInt(toKelasId.value, 10),
                        to_musyrif_id: toMusyrifId.value ? parseInt(toMusyrifId.value, 10) : null,
                        tipe: tipe.value,
                        catatan: catatan.value
                    };

                    const json = await postJson(`{{ route('admin.santri.migrasi.massal.execute') }}`,
                        payload);

                    if (window.Swal) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: json.message
                        });
                        location.reload();
                    }
                } catch (e) {
                    swalHelper('error', 'Gagal', e.message);
                }
            });

            // Flow Auto: Preview
            btnAutoPreview.addEventListener('click', async () => {
                try {
                    if (!semesterId) throw new Error('Semester aktif tidak ditemukan.');
                    if (window.Swal) Swal.fire({
                        title: 'Menganalisis Mapping...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const json = await postJson(`{{ route('admin.santri.migrasi.auto.preview') }}`, {
                        semester_id: parseInt(semesterId, 10),
                        include_graduation: true,
                        catatan: catatan.value
                    });

                    if (window.Swal) Swal.close();
                    autoLast = json;
                    btnAutoExecute.disabled = !json.ok;

                    // Build table HTML for SweetAlert
                    let tableMapping = `<div class="table-responsive mt-3"><table class="table table-sm table-bordered small">
                        <thead class="table-light"><tr><th>Mapping</th><th>Jumlah</th></tr></thead><tbody>`;
                    json.rows.forEach(r => {
                        tableMapping +=
                            `<tr><td>${r.from_nama} → ${r.to_nama}</td><td class="fw-bold">${r.count_santri}</td></tr>`;
                    });
                    tableMapping += `</tbody></table></div>`;

                    if (window.Swal) {
                        Swal.fire({
                            title: 'Preview Auto-Mapping',
                            html: `Total santri terdampak: <b>${json.total_santri_affected}</b> ${tableMapping}`,
                            width: '600px'
                        });
                    }
                } catch (e) {
                    swalHelper('error', 'Gagal', e.message);
                }
            });

            // Flow Auto: Execute
            btnAutoExecute.addEventListener('click', async () => {
                try {
                    if (!autoLast?.ok) return;
                    if (window.Swal) {
                        const confirm = await Swal.fire({
                            icon: 'warning',
                            title: 'Eksekusi Auto-Mapping',
                            text: `Sistem akan memproses semua kelas secara otomatis. Lanjutkan?`,
                            showCancelButton: true
                        });
                        if (!confirm.isConfirmed) return;
                        Swal.fire({
                            title: 'Memproses...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    }

                    const json = await postJson(`{{ route('admin.santri.migrasi.auto.execute') }}`, {
                        semester_id: parseInt(semesterId, 10),
                        include_graduation: true,
                        catatan: catatan.value
                    });

                    if (window.Swal) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Sukses',
                            text: json.message
                        });
                        location.reload();
                    }
                } catch (e) {
                    swalHelper('error', 'Gagal', e.message);
                }
            });

        })();
    </script>
@endpush
