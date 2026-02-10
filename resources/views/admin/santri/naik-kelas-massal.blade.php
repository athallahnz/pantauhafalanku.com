@extends('layouts.app')

@section('title', 'Naik Kelas Massal (Per Kelas)')

@section('content')
    <div class="container py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-1">Naik Kelas / Pindah Kelas (Massal)</h4>
                <div class="text-muted small">
                    Alur 1 | Naik per Kelas : Pilih Kelas Asal dan Tujuan → Sistem ambil semua santri → Preview → Execute.
                </div>
                <div class="text-muted small">
                    Alur 2 | Auto Naik Semua Kelas : Klik Auto-Mapping Preview → Preview Berhasil (OK) → Klik Auto-Mapping
                    Execute.
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('santri.master.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Santri
                </a>
            </div>
        </div>


        <div class="alert alert-light border d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">Semester Aktif</div>
                <div class="small text-muted">
                    @if ($semesterAktif)
                        {{ strtoupper($semesterAktif->nama) }}
                    @else
                        Belum ada semester aktif (set is_active=true di semesters).
                    @endif
                </div>
            </div>
            <div class="text-end">
                <span class="badge bg-{{ $semesterAktif ? 'success' : 'danger' }}">
                    {{ $semesterAktif ? 'ACTIVE' : 'NOT SET' }}
                </span>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Kelas Asal</label>
                        <select class="form-select" id="fromKelasId" {{ !$semesterAktif ? 'disabled' : '' }}>
                            <option value="">Pilih kelas asal...</option>
                            @foreach ($kelasList as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                        <div class="small text-muted mt-1">Semua santri pada kelas ini akan diproses.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Kelas Tujuan</label>
                        <select class="form-select" id="toKelasId" {{ !$semesterAktif ? 'disabled' : '' }}>
                            <option value="">Pilih kelas tujuan...</option>
                            @foreach ($kelasList as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                        <div class="small text-muted mt-1">Kelas aktif santri akan diubah ke kelas tujuan.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Musyrif Tujuan (opsional)</label>
                        <select class="form-select" id="toMusyrifId" {{ !$semesterAktif ? 'disabled' : '' }}>
                            <option value="">(Tidak diubah)</option>
                            @foreach ($musyrifList as $m)
                                <option value="{{ $m->id }}">{{ $m->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipe</label>
                        <select class="form-select" id="tipe" {{ !$semesterAktif ? 'disabled' : '' }}>
                            <option value="naik_kelas">Naik Kelas</option>
                            <option value="mutasi">Mutasi</option>
                            <option value="tinggal_kelas">Tinggal Kelas</option>
                            <option value="penempatan">Penempatan</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <input type="text" class="form-control" id="catatan" maxlength="1000"
                            placeholder="Misal: Naik Ganjil 2026/2027" {{ !$semesterAktif ? 'disabled' : '' }}>
                    </div>
                </div>

                <hr class="my-3">

                <div
                    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">

                    {{-- KIRI: Info santri --}}
                    <div class="text-start">
                        <div class="fw-semibold">Santri terdeteksi</div>
                        <div class="small text-muted" id="countInfo">0 santri</div>
                    </div>

                    {{-- KANAN: Action buttons --}}
                    <div class="d-grid d-md-flex gap-2 w-100 w-md-auto align-items-md-center justify-content-md-end">

                        {{-- ===== MANUAL FLOW ===== --}}
                        <span class="small text-uppercase text-muted d-none d-md-inline me-1">Manual</span>

                        <button class="btn btn-outline-primary" id="btnLoad" {{ !$semesterAktif ? 'disabled' : '' }}>
                            Ambil Santri
                        </button>

                        <button class="btn btn-primary" id="btnPreview" disabled>
                            Preview
                        </button>

                        <button class="btn btn-success text-white" id="btnExecute" disabled>
                            Execute
                        </button>

                        {{-- Divider desktop --}}
                        <div class="vr d-none d-md-block mx-2"></div>

                        {{-- ===== AUTO FLOW ===== --}}
                        <span class="small text-uppercase text-muted d-none d-md-inline me-1">Auto</span>

                        <button class="btn btn-primary" id="btnAutoPreview">
                            Auto-Mapping Preview
                        </button>

                        <button class="btn btn-success text-white" id="btnAutoExecute" disabled>
                            Auto-Mapping Execute
                        </button>

                    </div>
                </div>

                <div class="mt-3 d-none" id="previewBox">
                    <div class="alert alert-info mb-2">
                        Preview OK. Klik <b>Eksekusi</b> untuk memproses perubahan kelas.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th style="width:60px;">#</th>
                                    <th>Santri</th>
                                    <th style="width:140px;">NIS</th>
                                </tr>
                            </thead>
                            <tbody id="previewRows"></tbody>
                        </table>
                    </div>

                    <div class="small text-muted">
                        Ditampilkan maksimal 30 santri pertama untuk ringkasan.
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pastikan layout sudah ada meta CSRF --}}
@endsection

@push('scripts')
    <script>
        (function() {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const semesterId = {{ $semesterAktif?->id ?? 'null' }};

            const fromKelasId = document.getElementById('fromKelasId');
            const toKelasId = document.getElementById('toKelasId');
            const toMusyrifId = document.getElementById('toMusyrifId');
            const tipe = document.getElementById('tipe');
            const catatan = document.getElementById('catatan');

            const btnLoad = document.getElementById('btnLoad'); // opsional
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
                countInfo.textContent = '0 santri';
                btnExecute.disabled = true;
                previewBox.classList.add('d-none');
                previewRows.innerHTML = '';
                togglePreviewEnable();
            }

            function must(val, msg) {
                if (!val) throw new Error(msg);
            }

            async function getJson(url) {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(json?.message || 'Gagal mengambil data.');
                return json;
            }

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
                if (!res.ok) {
                    // Jika backend mengembalikan mismatch, tetap tampilkan message utama.
                    throw new Error(json?.message || 'Terjadi kesalahan.');
                }
                return json;
            }

            function buildMassalPayload() {
                must(semesterId, 'Semester aktif tidak tersedia.');
                must(fromKelasId.value, 'Pilih kelas asal.');
                must(toKelasId.value, 'Pilih kelas tujuan.');
                if (fromKelasId.value === toKelasId.value) {
                    throw new Error('Kelas tujuan tidak boleh sama dengan kelas asal.');
                }

                return {
                    semester_id: parseInt(semesterId, 10),
                    from_kelas_id: parseInt(fromKelasId.value, 10),
                    to_kelas_id: parseInt(toKelasId.value, 10),
                    to_musyrif_id: toMusyrifId.value ? parseInt(toMusyrifId.value, 10) : null,
                    tipe: tipe.value || 'naik_kelas',
                    catatan: catatan.value || null
                };
            }

            function togglePreviewEnable() {
                const ok = !!(semesterId && fromKelasId.value && toKelasId.value) && (fromKelasId.value !== toKelasId
                    .value);
                btnPreview.disabled = !ok;
            }

            // Reset jika dropdown berubah
            [fromKelasId, toKelasId, toMusyrifId, tipe, catatan].forEach(el => {
                el.addEventListener('change', resetFlow);
                el.addEventListener('input', resetFlow);
            });
            [fromKelasId, toKelasId].forEach(el => el.addEventListener('change', togglePreviewEnable));

            /**
             * OPSIONAL: "Ambil Santri" hanya untuk cek jumlah cepat via endpoint by_kelas.
             * Tidak wajib untuk flow; Preview Massal sudah mengembalikan count.
             */
            if (btnLoad) {
                btnLoad.addEventListener('click', async function() {
                    try {
                        must(fromKelasId.value, 'Pilih kelas asal.');

                        if (window.Swal) {
                            Swal.fire({
                                title: 'Mengambil santri...',
                                allowOutsideClick: false,
                                didOpen: () => Swal.showLoading()
                            });
                        }

                        const url = new URL(`{{ route('admin.santri.migrasi.by_kelas') }}`, window.location
                            .origin);
                        url.searchParams.set('kelas_id', fromKelasId.value);

                        const json = await getJson(url.toString());
                        const santris = json.santris || [];

                        if (window.Swal) Swal.close();

                        lastCount = santris.length;
                        countInfo.textContent = `${lastCount} santri`;
                        btnExecute.disabled = true;
                        previewBox.classList.add('d-none');
                        previewRows.innerHTML = '';

                        if (window.Swal) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Data diambil',
                                text: `${lastCount} santri terdeteksi pada kelas asal.`
                            });
                        }
                    } catch (e) {
                        if (window.Swal) Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: e.message
                        });
                        else alert(e.message);
                    }
                });
            }

            /**
             * Preview Massal
             * Backend: previewMassal() -> return count + santris (max 30)
             */
            btnPreview.addEventListener('click', async function() {
                try {
                    const payload = buildMassalPayload();

                    if (window.Swal) {
                        Swal.fire({
                            title: 'Memproses preview...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    }

                    const json = await postJson(`{{ route('admin.santri.migrasi.massal.preview') }}`,
                        payload);

                    if (window.Swal) Swal.close();

                    lastCount = json.count || 0;
                    countInfo.textContent = `${lastCount} santri`;

                    const list = (json.santris || []).slice(0, 30);
                    previewRows.innerHTML = list.map((s, i) => `
                <tr>
                    <td>${i + 1}</td>
                    <td>${s.nama ?? '-'}</td>
                    <td>${s.nis ?? '-'}</td>
                </tr>
            `).join('');

                    previewBox.classList.remove('d-none');
                    btnExecute.disabled = lastCount === 0;

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Preview OK',
                            text: `Akan memproses ${lastCount} santri. Klik Eksekusi untuk lanjut.`
                        });
                    }
                } catch (e) {
                    if (window.Swal) Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.message
                    });
                    else alert(e.message);
                }
            });

            /**
             * Execute Massal
             * Backend: executeMassal() -> guard kelas asal harus match
             */
            btnExecute.addEventListener('click', async function() {
                try {
                    const payload = buildMassalPayload();

                    if (window.Swal) {
                        const confirm = await Swal.fire({
                            icon: 'warning',
                            title: 'Konfirmasi Eksekusi',
                            html: `Anda akan memproses <b>${lastCount || 'semua'}</b> santri dari kelas asal.<br>
                            Sistem akan mengubah kelas aktif dan menulis riwayat semester.`,
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Eksekusi',
                            cancelButtonText: 'Batal'
                        });
                        if (!confirm.isConfirmed) return;

                        Swal.fire({
                            title: 'Mengeksekusi...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    }

                    const json = await postJson(`{{ route('admin.santri.migrasi.massal.execute') }}`,
                        payload);

                    if (window.Swal) Swal.close();

                    if (window.Swal) {
                        Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: json.message || 'Migrasi kelas berhasil.'
                            })
                            .then(() => window.location.reload());
                    } else {
                        alert(json.message || 'Berhasil');
                        window.location.reload();
                    }
                } catch (e) {
                    if (window.Swal) Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.message
                    });
                    else alert(e.message);
                }
            });

            btnAutoPreview.addEventListener('click', async () => {
                try {
                    if (!semesterId) throw new Error('Semester aktif tidak tersedia.');

                    const payload = {
                        semester_id: parseInt(semesterId, 10),
                        include_graduation: true,
                        catatan: (catatan?.value || null)
                    };

                    if (window.Swal) {
                        Swal.fire({
                            title: 'Preview auto-mapping...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    }

                    const json = await postJson(`{{ route('admin.santri.migrasi.auto.preview') }}`,
                        payload);

                    if (window.Swal) Swal.close();

                    autoLast = json;
                    btnAutoExecute.disabled = !json.ok;

                    const rows = (json.rows || []);
                    const total = json.total_santri_affected ?? rows.reduce((a, r) => a + (r.count_santri ||
                        0), 0);

                    // build table
                    const tableRows = rows.map(r => {
                        const cnt = Number(r.count_santri || 0);
                        const badgeStyle = cnt > 0 ?
                            'background:#198754;color:#fff;' :
                            'background:#6c757d;color:#fff;';

                        return `
                        <tr>
                        <td style="padding:6px 10px; text-align:left; white-space:nowrap;">
                            ${r.from_nama} &rarr; ${r.to_nama}
                        </td>
                        <td style="padding:6px 10px; text-align:right;">
                            <span style="display:inline-block; min-width:42px; padding:2px 10px; border-radius:999px; font-weight:700; ${badgeStyle}">
                            ${cnt}
                            </span>
                        </td>
                        </tr>
                    `;
                    }).join('');

                    const html = `
                    <div style="text-align:left;">
                        <div style="margin-bottom:10px;">
                        <div style="font-weight:800;">Ringkasan Auto-Mapping</div>
                        <div style="color:#6c757d; font-size:13px;">
                            Total santri diproses: <b>${total}</b>
                        </div>
                        </div>

                        <div style="max-height:260px; overflow:auto; border:1px solid #e9ecef; border-radius:10px;">
                        <table style="width:100%; border-collapse:collapse;">
                            <thead>
                            <tr style="background:#f8f9fa;">
                                <th style="padding:8px 10px; text-align:left; position:sticky; top:0; background:#f8f9fa;">Mapping</th>
                                <th style="padding:8px 10px; text-align:right; position:sticky; top:0; background:#f8f9fa;">Jumlah</th>
                            </tr>
                            </thead>
                            <tbody>
                            ${tableRows}
                            </tbody>
                        </table>
                        </div>

                        ${json.ok ? '' : `
                                            <div style="margin-top:10px; color:#dc3545; font-size:13px;">
                                                Ada mapping yang tidak valid (kelas tidak ditemukan). Periksa nama_kelas.
                                            </div>
                                            `}
                    </div>
                    `;

                    if (window.Swal) {
                        Swal.fire({
                            icon: json.ok ? 'success' : 'error',
                            title: 'Preview',
                            html,
                            width: 720,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // fallback plain text
                        const lines = rows.map(r => `${r.from_nama} → ${r.to_nama}: ${r.count_santri || 0}`)
                            .join('\n');
                        alert(`Total: ${total}\n\n${lines}`);
                    }

                } catch (e) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: e.message
                        });
                    } else {
                        alert(e.message);
                    }
                }
            });


            btnAutoExecute.addEventListener('click', async () => {
                try {
                    if (!autoLast?.ok) throw new Error(
                        'Jalankan preview auto-mapping dulu (dan pastikan OK).');

                    const payload = {
                        semester_id: parseInt(semesterId, 10),
                        include_graduation: true,
                        catatan: (catatan?.value || null)
                    };

                    if (window.Swal) {
                        const c = await Swal.fire({
                            icon: 'warning',
                            title: 'Konfirmasi Auto-Mapping',
                            text: `Total diproses: ${autoLast.total_santri_affected} santri. Lanjutkan?`,
                            showCancelButton: true
                        });
                        if (!c.isConfirmed) return;
                        Swal.fire({
                            title: 'Mengeksekusi...',
                            allowOutsideClick: false,
                            didOpen: () => Swal.showLoading()
                        });
                    }

                    const json = await postJson(`{{ route('admin.santri.migrasi.auto.execute') }}`,
                        payload);
                    if (window.Swal) Swal.close();

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: json.message
                        }).then(() => location.reload());
                    } else {
                        alert(json.message);
                        location.reload();
                    }
                } catch (e) {
                    if (window.Swal) Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: e.message
                    });
                    else alert(e.message);
                }
            });

            resetFlow();
            togglePreviewEnable();
        })();
    </script>
@endpush
