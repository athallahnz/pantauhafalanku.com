@extends('layouts.app')
@section('title', $kind === 'tahsin' ? 'Rekap Tahsin Harian' : 'Rekap Seluruh Tilawah')
@section('content')
    @php($isTahsin = $kind === 'tahsin')
    <style>
        .daily-report .card {
            border: 0;
            border-radius: 18px
        }

        .daily-report .hero {
            background: linear-gradient(120deg, #55409c, #886bda);
            color: #fff
        }

        .daily-report th,
        .daily-report td {
            vertical-align: top
        }

        .daily-report td {
            white-space: normal;
            overflow-wrap: anywhere
        }

        .daily-report .detail {
            background: var(--cui-tertiary-bg, #f3f4f8)
        }
    </style>
    @include('reports.partials.recap-style')
    <div class="daily-report recap-page">
        <div class="card hero recap-hero mb-4">
            <div class="card-body p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div><span class="recap-eyebrow">Laporan Akademik</span>
                    <h4 class="mb-1">{{ $isTahsin ? 'Rekap Tahsin Harian' : 'Rekap Seluruh Tilawah' }}</h4>
                    <p class="mb-0 small">Pantau aktivitas, kehadiran, dan progres santri berdasarkan semester.</p>
                </div>
                <a id="dr-export" class="btn recap-export disabled no-loader" data-no-loader="true" aria-disabled="true"><i
                        class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel</a>
            </div>
        </div>
        @if (!$selected)
            <div class="alert alert-info">Belum ada semester untuk ditampilkan.</div>
        @else
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="recap-section-title"><i class="bi bi-sliders"></i> Filter Laporan</h2>
                    <form id="dr-form" class="row g-3">
                        <div class="col-md-4"><label class="form-label" for="dr-semester">Semester</label><select
                                name="semester_id" id="dr-semester" class="form-select" required>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}"
                                        data-start="{{ $semester->tanggal_mulai->toDateString() }}"
                                        data-end="{{ $semester->tanggal_selesai->toDateString() }}"
                                        @selected($semester->id === $selected->id)>{{ $semester->nama }}
                                        {{ $semester->tahunAjaran?->nama }}</option>
                                @endforeach
                            </select></div>
                        <div class="col-md-4"><label class="form-label" for="dr-class">Kelas</label><select id="dr-class"
                                name="kelas_id" class="form-select">
                                <option value="">Semua kelas</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class['id'] }}">{{ $class['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label" for="dr-musyrif">Musyrif pembina</label><select
                                id="dr-musyrif" name="musyrif_id" class="form-select">
                                <option value="">Semua Musyrif</option>
                                @foreach ($musyrifs as $musyrif)
                                    <option value="{{ $musyrif->id }}">{{ $musyrif->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label for="dr-from" class="form-label">Tanggal mulai</label><input
                                id="dr-from" name="date_from" type="date" class="form-control" required></div>
                        <div class="col-md-3"><label for="dr-to" class="form-label">Tanggal selesai</label><input
                                id="dr-to" name="date_to" type="date" class="form-control" required></div>
                        <div class="col-md-3"><label for="dr-kind"
                                class="form-label">{{ $isTahsin ? 'Buku/Jilid' : 'Jenis aktivitas' }}</label><select
                                id="dr-kind" name="{{ $isTahsin ? 'buku' : 'entry_type' }}" class="form-select">
                                <option value="">Semua</option>
                                @foreach ($isTahsin ? \App\Models\TahsinExam::bookLabels() : ['individual' => 'Mandiri', 'group' => 'Kelompok', 'catchup' => 'Susulan', 'legacy' => 'Data lama / jenis belum diketahui'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select></div>
                        <div class="col-md-3"><label for="dr-status" class="form-label">Status pertemuan</label><select
                                id="dr-status" name="status" class="form-select">
                                <option value="">Semua</option>
                                @foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8"><label for="dr-search" class="form-label">Cari Santri, NIS, kelas, atau
                                Musyrif</label><input id="dr-search" name="q" maxlength="150" class="form-control">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2"><button class="btn btn-primary"
                                type="submit">Terapkan Filter</button><button class="btn btn-outline-secondary"
                                type="reset">Reset</button></div>
                    </form>
                    <p class="small text-body-secondary mt-3 mb-0">Kelas dan Musyrif mengikuti penempatan semester. Fallback
                        data aktif ditandai. Seluruh filter transaksi berlaku pada progres, jumlah, riwayat, dan ekspor.
                        Santri tanpa transaksi tetap ditampilkan dalam populasi yang sesuai.</p>
                </div>
            </div>
            <div id="dr-error" class="alert alert-danger d-none" role="alert"></div>
            <div class="row g-3 mb-4 recap-stats" aria-live="polite">
                @foreach (['total_santri' => 'Total Santri', 'with_activity' => 'Ada Aktivitas', 'without_activity' => 'Tanpa Aktivitas', 'total_records' => 'Total Record', 'hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpha' => 'Alpha'] as $key => $label)
                    <div class="col-6 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="small">{{ $label }}</div><strong class="fs-3"
                                    id="dr-{{ $key }}">0</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="card">
                <div class="card-body">
                    <h2 class="recap-section-title"><i class="bi bi-people"></i> Rekap Per Santri</h2>
                    <p class="small recap-help">
                        {{ $isTahsin ? 'Progres adalah rata-rata halaman tertinggi hadir pada enam buku. Ujian Tahsin tersedia pada rekap terpisah.' : 'Persentase hanya untuk juz unik Mandiri Lanjut yang hadir. Kelompok, susulan, murojaah, dan data lama dicatat terpisah. Ayat unik hanya dihitung dari rentang terstruktur valid.' }}
                    </p>
                    <div id="dr-loading" class="alert alert-info d-none" role="status"><span
                            class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat rekap…</div>
                    <div class="table-responsive">
                        <table id="dr-table" class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Santri / NIS</th>
                                    <th>Kelas / Pembina</th>
                                    <th>Record / Kehadiran</th>
                                    <th>Progres</th>
                                    @if (!$isTahsin)
                                        <th>Jenis Aktivitas</th>
                                        <th>Cakupan Ayat Unik</th>
                                    @endif
                                    <th>Riwayat</th>
                                </tr>
                            </thead>
                            <tbody id="dr-body"></tbody>
                        </table>
                    </div>

                </div>
            </div>
        @endif
    </div>
@endsection
@push('scripts')
    @if ($selected)
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('dr-form'),
                    semester = document.getElementById('dr-semester');
                const download = document.getElementById('dr-export'),
                    errorBox = document.getElementById('dr-error'),
                    loading = document.getElementById('dr-loading');
                const isTahsin = @json($isTahsin),
                    dataUrl = @json(route($routeBase . '.data')),
                    exportUrl = @json(route($routeBase . '.export')),
                    historyUrl = @json(route($routeBase . '.history', ['santri' => 0]));
                const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c]));
                const stats = ['total_santri', 'with_activity', 'without_activity', 'total_records', 'hadir', 'izin',
                    'sakit', 'alpha'
                ];
                let generation = 0,
                    controller = null,
                    applied = '',
                    ready = false;

                function params() {
                    const q = new URLSearchParams(new FormData(form));
                    for (const [k, v] of [...q])
                        if (!v) q.delete(k);
                    return q.toString();
                }

                function invalidate() {
                    download.classList.add('disabled');
                    download.setAttribute('aria-disabled', 'true');
                    download.removeAttribute('href');
                }

                function setDates() {
                    const opt = semester.selectedOptions[0];
                    for (const [id, key] of [
                            ['dr-from', 'start'],
                            ['dr-to', 'end']
                        ]) {
                        const input = document.getElementById(id);
                        input.value = opt.dataset[key];
                        input.min = opt.dataset.start;
                        input.max = opt.dataset.end;
                    }
                }
                async function json(url, signal) {
                    const r = await fetch(url, {
                        headers: {
                            Accept: 'application/json'
                        },
                        credentials: 'same-origin',
                        signal
                    });
                    const body = await r.json().catch(() => ({}));
                    if (!r.ok || r.redirected) throw new Error(Object.values(body.errors || {}).flat().join(' ') ||
                        body.message || 'Laporan gagal dimuat. Periksa koneksi atau login kembali.');
                    if (!Array.isArray(body.data)) throw new Error('Respons laporan tidak valid.');
                    return body;
                }
                const columns = [{
                        data: 'nama',
                        className: 'recap-person',
                        render: (v, t, r) => t === 'display' ?
                            `<strong>${escape(v)}</strong><small>NIS ${escape(r.nis||'-')}</small>` : v
                    },
                    {
                        data: 'kelas',
                        className: 'recap-placement',
                        render: (v, t, r) => t === 'display' ?
                            `<strong>${escape(v)}</strong><div>${escape(r.musyrif)}</div><small class="text-muted">${escape(r.placement_source)}</small>` :
                            v
                    },
                    {
                        data: 'total',
                        className: 'recap-attendance',
                        render: (v, t, r) => t === 'display' ?
                            `<strong>${Number(v)} record</strong><div class="small">Hadir ${Number(r.hadir)} · Izin ${Number(r.izin)}<br>Sakit ${Number(r.sakit)} · Alpha ${Number(r.alpha)}</div>` :
                            v
                    },
                    {
                        data: 'percentage',
                        className: 'recap-progress',
                        render: (v, t, r) => {
                            if (t !== 'display') return v;
                            const pct = Math.min(100, Math.max(0, Number(v) || 0));
                            return `<strong>${pct}%</strong><div class="progress"><div class="progress-bar" role="progressbar" aria-label="Progres" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100" style="width:${pct}%"></div></div><small>${escape(r.progress_text)}</small>`;
                        }
                    }
                ];
                if (!isTahsin) {
                    columns.push({
                        data: 'tilawah.activity_counts',
                        orderable: false,
                        render: (a, t) => t === 'display' ?
                            `<div class="small">Lanjut ${Number(a.continuation)} · Murojaah ${Number(a.review)}<br>Kelompok ${Number(a.group)} · Susulan ${Number(a.catchup)}<br>Data lama ${Number(a.legacy)}</div>` :
                            ''
                    });
                    columns.push({
                        data: 'tilawah.unique_ayat',
                        orderable: false,
                        render: (a, t) => t === 'display' ?
                            `<div class="small">Kelompok ${Number(a.group)}<br>Susulan ${Number(a.catchup)}<br>Mandiri lanjut ${Number(a.individual)}</div>` :
                            ''
                    });
                }
                columns.push({
                    data: null,
                    orderable: false,
                    className: 'text-end',
                    render: () =>
                        '<button type="button" class="btn btn-outline-primary btn-sm dr-history" aria-expanded="false"><i class="bi bi-chevron-down me-1" aria-hidden="true"></i>Riwayat</button>'
                });
                const table = $('#dr-table').DataTable({
                    data: [],
                    deferRender: true,
                    pageLength: 25,
                    lengthMenu: [10, 25, 50, 100],
                    searching: false,
                    order: [
                        [0, 'asc']
                    ],
                    autoWidth: false,
                    columns,
                    language: {
                        emptyTable: 'Tidak ada santri sesuai filter.',
                        lengthMenu: 'Tampilkan _MENU_ santri',
                        info: '_START_–_END_ dari _TOTAL_ santri',
                        infoEmpty: '0 santri',
                        paginate: {
                            previous: 'Sebelumnya',
                            next: 'Berikutnya'
                        }
                    }
                });
                async function load() {
                    const current = ++generation;
                    controller?.abort();
                    controller = new AbortController();
                    const query = params();
                    ready = false;
                    invalidate();
                    errorBox.classList.add('d-none');
                    loading.classList.remove('d-none');
                    table.clear().draw();
                    document.getElementById('dr-table').setAttribute('aria-busy', 'true');
                    stats.forEach(k => document.getElementById('dr-' + k).textContent = '—');
                    try {
                        const body = await json(dataUrl + '?' + query, controller.signal);
                        if (current !== generation) return;
                        applied = query;
                        table.rows.add(body.data).draw();
                        stats.forEach(k => document.getElementById('dr-' + k).textContent = Number(body.statistics[
                            k]).toLocaleString('id-ID'));
                        ready = true;
                        if (params() === query) {
                            download.href = exportUrl + '?' + query;
                            download.classList.remove('disabled');
                            download.setAttribute('aria-disabled', 'false');
                        }
                    } catch (e) {
                        if (e.name === 'AbortError' || current !== generation) return;
                        errorBox.textContent = e.message;
                        errorBox.classList.remove('d-none');
                    } finally {
                        if (current === generation) {
                            loading.classList.add('d-none');
                            document.getElementById('dr-table').setAttribute('aria-busy', 'false');
                        }
                    }
                }
                $('#dr-table tbody').on('click', '.dr-history', async function() {
                    if (!ready) return;
                    const button = this,
                        row = table.row($(button).closest('tr'));
                    if (row.child.isShown()) {
                        row.child.hide();
                        button.setAttribute('aria-expanded', 'false');
                        return;
                    }
                    const current = generation,
                        query = applied;
                    button.setAttribute('aria-expanded', 'true');
                    row.child('<div class="p-3" role="status">Memuat riwayat…</div>').show();
                    try {
                        const body = await json(historyUrl.replace(/\/0$/, '/' + row.data().id) + '?' +
                            query, controller.signal);
                        if (current !== generation || button.getAttribute('aria-expanded') !== 'true')
                            return;
                        const lines = body.data.map(r => '<tr>' + ['tanggal', 'jenis', 'materi', 'status',
                            'nilai', 'pencatat', 'catatan'
                        ].map(k => `<td>${escape(r[k])}</td>`).join('') + '</tr>').join('');
                        row.child(
                            `<div class="am-history p-3"><strong>Riwayat ${escape(body.santri)}</strong><div class="table-responsive mt-3"><table class="table table-sm"><thead><tr>${['Tanggal','Jenis','Materi','Status','Nilai','Musyrif Pencatat','Catatan'].map(v=>`<th>${v}</th>`).join('')}</tr></thead><tbody>${lines||'<tr><td colspan="7">Belum ada transaksi sesuai filter.</td></tr>'}</tbody></table></div></div>`
                        ).show();
                    } catch (e) {
                        if (e.name !== 'AbortError' && current === generation && button.getAttribute(
                                'aria-expanded') === 'true') row.child(
                            `<div class="alert alert-danger m-3">${escape(e.message)}</div>`).show();
                    }
                });

                function dirty() {
                    generation++;
                    controller?.abort();
                    ready = false;
                    invalidate();
                    loading.classList.add('d-none');
                    document.getElementById('dr-table').setAttribute('aria-busy', 'false');
                    table.clear().draw();
                    stats.forEach(k => document.getElementById('dr-' + k).textContent = '—');
                }
                form.addEventListener('submit', e => {
                    e.preventDefault();
                    load();
                });
                form.addEventListener('change', dirty);
                form.addEventListener('input', dirty);
                semester.addEventListener('change', setDates);
                document.getElementById('dr-from').addEventListener('change', () => {
                    document.getElementById('dr-to').min = document.getElementById('dr-from').value || semester
                        .selectedOptions[0].dataset.start;
                });
                form.addEventListener('reset', () => {
                    setTimeout(() => {
                        setDates();
                        load();
                    }, 0);
                });
                download.addEventListener('click', e => {
                    if (download.getAttribute('aria-disabled') === 'true') e.preventDefault();
                });
                const initial = new URLSearchParams(window.location.search);
                const requestedSemester = initial.get('semester_id');
                if (requestedSemester && [...semester.options].some(option => option.value === requestedSemester)) {
                    semester.value = requestedSemester;
                }
                setDates();
                for (const key of ['date_from', 'date_to']) {
                    if (initial.has(key)) form.elements.namedItem(key).value = initial.get(key);
                }
                load();
            });
        </script>
    @endif
@endpush
