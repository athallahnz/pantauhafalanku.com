@extends('layouts.app')
@section('title', $kind === 'exams' ? 'Rekap Ujian Tahsin' : 'Rekap Tilawah Mandiri')

@section('content')
@php($isExam = $kind === 'exams')
<style>
    .am-page .card { border: 0; border-radius: 20px; }
    .am-hero { color: #fff; background: linear-gradient(120deg, #55409c, #886bda); }
    .am-hero h4 { color: #fff; }
    .am-page .am-value { font-size: 1.75rem; font-weight: 800; }
    .am-page .am-label { font-size: .75rem; color: var(--cui-secondary-color, #6b7280); }
    .am-page .progress { height: 6px; min-width: 130px; }
    .am-page .progress-bar { background: linear-gradient(90deg, #19bc99, #6f42c1); }
    .am-page th { font-size: .72rem; letter-spacing: .04em; text-transform: uppercase; }
    .am-page td { vertical-align: middle; }
    .am-page .am-history { background: var(--cui-tertiary-bg, #f3f4f8); border: 1px solid var(--cui-border-color, #ddd); border-radius: 16px; padding: 1rem; }
    .am-page .am-history td:last-child { min-width: 180px; white-space: pre-wrap; overflow-wrap: anywhere; }
    .am-page .am-name { min-width: 160px; white-space: normal; }
    .am-page .am-progress { min-width: 170px; white-space: normal; }
    .am-page .am-details { max-width: 240px; white-space: normal; }
    .am-page .am-history-toggle { min-width: 36px; min-height: 36px; }
    .am-page .am-note { max-width: 850px; }
</style>
<div class="am-page">
    <div class="card am-hero shadow-sm mb-4">
        <div class="card-body p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi {{ $isExam ? 'bi-clipboard-check' : 'bi-journal-bookmark-fill' }} me-2"></i>{{ $isExam ? 'Rekap Ujian Tahsin' : 'Rekap Tilawah Mandiri' }}</h4>
                <p class="mb-0 small">Pantau progres dan riwayat santri berdasarkan semester.</p>
            </div>
            <a id="am-export" class="btn btn-light disabled" aria-disabled="true" href="#"><i class="bi bi-file-earmark-excel me-1"></i>Ekspor Excel</a>
        </div>
    </div>
    @if(!$selected)
        <div class="alert alert-info">Belum ada semester untuk ditampilkan.</div>
    @else
    <div class="card shadow-sm mb-4">
        <div class="card-body p-3 p-md-4">
            <form id="am-filters" class="row g-3">
                <div class="col-md-4">
                    <label for="am-semester" class="form-label">Semester</label>
                    <select id="am-semester" name="semester_id" class="form-select" required>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" data-start="{{ $semester->tanggal_mulai->toDateString() }}" data-end="{{ $semester->tanggal_selesai->toDateString() }}" @selected($semester->id === $selected->id)>{{ $semester->nama }} {{ $semester->tahunAjaran?->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="am-kelas" class="form-label">Kelas</label>
                    <select id="am-kelas" name="kelas_id" class="form-select">
                        <option value="">Semua kelas</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ trim($class->nama_kelas . ' ' . $class->kelompok) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="am-musyrif" class="form-label">Musyrif pembina</label>
                    <select id="am-musyrif" name="musyrif_id" class="form-select">
                        <option value="">Semua Musyrif</option>
                        @foreach($musyrifs as $musyrif)
                            <option value="{{ $musyrif->id }}">{{ $musyrif->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label for="am-from" class="form-label">Tanggal mulai</label>
                    <input id="am-from" name="date_from" type="date" class="form-control" required>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label for="am-to" class="form-label">Tanggal selesai</label>
                    <input id="am-to" name="date_to" type="date" class="form-control" required>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label for="am-type" class="form-label">{{ $isExam ? 'Jenis ujian' : 'Tujuan bacaan' }}</label>
                    <select id="am-type" name="{{ $isExam ? 'exam_type' : 'purpose' }}" class="form-select">
                        <option value="">Semua</option>
                        @if($isExam)
                            <option value="promotion">Kenaikan Buku/Jilid</option><option value="semester">Ujian Semester</option>
                        @else
                            <option value="continuation">Lanjut</option><option value="review">Murojaah</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label for="am-state" class="form-label">{{ $isExam ? 'Hasil ujian terakhir' : 'Status rekap' }}</label>
                    <select id="am-state" name="state" class="form-select">
                        <option value="">Semua</option>
                        @if($isExam)
                            <option value="not_examined">Belum ujian</option><option value="passed">Lulus</option><option value="repeat">Mengulang</option>
                        @else
                            <option value="no_activity">Belum ada catatan</option><option value="not_started">Aktif, belum progres</option>
                            <option value="in_progress">Berprogres</option><option value="completed">Khatam</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-8">
                    <label for="am-search" class="form-label">Cari santri, NIS, kelas, atau Musyrif</label>
                    <input id="am-search" name="q" type="search" maxlength="150" class="form-control" placeholder="Ketik kata pencarian, lalu terapkan filter">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button id="am-apply" type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Terapkan Filter</button>
                    <button type="button" id="am-reset" class="btn btn-outline-secondary">Reset</button>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-3 am-note">Kelas dan Musyrif pembina mengikuti penempatan semester. Pada semester aktif, santri tanpa penempatan memakai data aktif saat ini dan diberi penanda. Santri dengan riwayat tetap ditampilkan meskipun sudah nonaktif.</p>
        </div>
    </div>
    <div id="am-error" class="alert alert-danger d-none" role="alert"></div>
    <div class="row g-3 mb-4" aria-live="polite">
        @foreach(['total_santri' => 'Total Santri', 'with_activity' => ($isExam ? 'Sudah Ujian' : 'Memiliki Catatan'), 'without_activity' => ($isExam ? 'Belum Ujian' : 'Belum Ada Catatan'), 'total_records' => ($isExam ? 'Total Ujian' : 'Total Sesi')] as $key => $label)
        <div class="col-6 col-xl-3"><div class="card shadow-sm h-100"><div class="card-body p-3 p-md-4"><div class="am-label fw-bold text-uppercase">{{ $label }}</div><div class="am-value" data-stat="{{ $key }}">—</div></div></div></div>
        @endforeach
    </div>
    <div class="card main-card spotlight-card shadow-sm border-0">
        <div class="card-header card-header-purple bg-body-tertiary py-3 px-3 px-md-4 border-bottom-0">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <strong>Rekap Per Santri</strong><span id="am-period" class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">Memuat laporan…</span>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">
            <p class="small text-muted mb-3 am-note">
                @if($isExam)
                    Progres dan rekomendasi dihitung dari buku unik yang lulus ujian kenaikan dalam periode terpilih. Filter jenis ujian mengatur jumlah, riwayat, dan hasil terakhir; progres kenaikan tetap dihitung dari semua ujian kenaikan pada periode tersebut.
                @else
                    Progres dihitung dari Juz unik berjenis Lanjut dalam periode terpilih. Murojaah dicatat sebagai aktivitas. Filter tujuan mengatur jumlah dan riwayat; progres tetap memakai seluruh bacaan Lanjut dalam periode tersebut.
                @endif
                Klik panah untuk membuka riwayat. Ringkasan dan ekspor mengikuti filter yang diterapkan.
            </p>
            <div class="table-responsive">
                <table id="am-table" class="table table-hover align-middle w-100 mb-0">
                    <thead class="bg-body-tertiary"><tr class="text-muted small fw-bold text-uppercase">
                        <th>No.</th><th>Santri</th><th>Kelas</th><th>Musyrif Pembina</th><th>Progres Periode</th>
                        <th>{{ $isExam ? 'Ujian Terakhir' : 'Bacaan Terakhir' }}</th><th>Total</th><th>Status</th><th class="text-end">Riwayat</th>
                    </tr></thead><tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
@if($selected)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isExam = @json($kind === 'exams');
    const dataUrl = @json(route($routeBase . '.data'));
    const exportUrl = @json(route($routeBase . '.export'));
    const historyUrl = @json(route($routeBase . '.history', ['santri' => 0]));
    const form = document.getElementById('am-filters');
    const semester = document.getElementById('am-semester');
    const from = document.getElementById('am-from');
    const to = document.getElementById('am-to');
    const exportButton = document.getElementById('am-export');
    const errorBox = document.getElementById('am-error');
    const labels = {passed: 'Lulus', repeat: 'Mengulang', not_examined: 'Belum ujian', no_activity: 'Belum ada catatan', not_started: 'Aktif, belum progres', in_progress: 'Berprogres', completed: 'Khatam'};
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
    let generation = 0;
    let applied = '';
    let requestController = null;
    function bounds() {
        const option = semester.selectedOptions[0];
        from.min = to.min = option.dataset.start;
        from.max = to.max = option.dataset.end;
        from.value = option.dataset.start;
        to.value = option.dataset.end;
    }
    function disableExport() {
        exportButton.classList.add('disabled');
        exportButton.setAttribute('aria-disabled', 'true');
    }
    async function json(url, signal) {
        const response = await fetch(url, {headers: {'Accept': 'application/json'}, signal});
        const body = await response.json().catch(() => ({}));
        if (!response.ok || response.redirected) {
            const errors = Object.values(body.errors || {}).flat();
            throw new Error(errors[0] || body.message || 'Laporan gagal dimuat. Periksa koneksi atau login kembali.');
        }
        return body;
    }
    const table = $('#am-table').DataTable({
        data: [], pageLength: 25, searching: false, order: [[1, 'asc']],
        language: {emptyTable: 'Tidak ada santri yang sesuai filter.', lengthMenu: 'Tampilkan _MENU_ santri', info: '_START_–_END_ dari _TOTAL_ santri', infoEmpty: '0 santri', paginate: {previous: 'Sebelumnya', next: 'Berikutnya'}},
        columns: [
            {data: null, orderable: false, render: (data, type, row, meta) => meta.row + 1},
            {data: 'nama', className: 'am-name', render: (value, type, row) => type !== 'display' ? value : `<strong>${escape(value)}</strong><div class="small text-muted">${escape(row.nis || 'NIS belum tersedia')}</div>`},
            {data: 'kelas', render: (value, type, row) => type !== 'display' ? value : `${escape(value)}${row.placement_source !== 'Penempatan semester' ? `<div class="small text-muted">${escape(row.placement_source)}</div>` : ''}`},
            {data: 'musyrif', render: value => escape(value)},
            {data: 'percentage', className: 'am-progress', render: (value, type, row) => {
                if (type !== 'display') return value;
                const list = row.completed.length ? row.completed.map(escape).join(', ') : 'Belum ada progres dalam periode ini.';
                return `<strong class="text-success">${row.completed_count}/${row.target} ${isExam ? 'Buku' : 'Juz'}</strong> <small class="text-muted">${value}%</small><div class="progress my-2"><div class="progress-bar" role="progressbar" aria-valuenow="${value}" aria-valuemin="0" aria-valuemax="100" style="width:${value}%"></div></div><details class="small am-details"><summary>Lihat ${isExam ? 'buku lulus' : 'Juz tercatat'}</summary>${list}</details>${isExam ? `<small class="text-muted">Rekomendasi: ${escape(row.recommendation)}</small>` : ''}`;
            }},
            {data: 'latest_date', render: (value, type, row) => type !== 'display' ? (value || '') : `<div>${escape(row.latest || (isExam ? 'Belum ujian' : 'Belum ada catatan'))}</div><small class="text-muted">${escape(value)}</small>`},
            {data: 'total', render: (value, type, row) => type !== 'display' ? value : `<strong>${value} ${isExam ? 'ujian' : 'sesi'}</strong><div class="small text-muted">${row.first_count} ${isExam ? 'kenaikan' : 'lanjut'} · ${row.second_count} ${isExam ? 'semester' : 'murojaah'}</div>`},
            {data: 'state', render: value => `<span class="badge ${['passed', 'completed'].includes(value) ? 'bg-success-subtle text-success' : value === 'repeat' ? 'bg-danger-subtle text-danger' : 'bg-secondary-subtle text-secondary'}">${escape(labels[value])}</span>`},
            {data: null, orderable: false, className: 'text-end', render: () => '<button type="button" class="btn btn-outline-secondary btn-sm am-history-toggle" aria-expanded="false" aria-label="Buka riwayat"><i class="bi bi-chevron-down"></i></button>'}
        ]
    });
    table.on('order.dt draw.dt', function () {
        const start = table.page.info().start;
        table.column(0, {page: 'current'}).nodes().each((cell, i) => {cell.textContent = start + i + 1;});
    });
    async function load() {
        const current = ++generation;
        requestController?.abort();
        requestController = new AbortController();
        applied = new URLSearchParams(new FormData(form)).toString();
        const params = applied;
        disableExport();
        errorBox.classList.add('d-none');
        table.clear().draw();
        document.querySelectorAll('[data-stat]').forEach(el => {el.textContent = '—';});
        document.getElementById('am-period').textContent = 'Memuat laporan…';
        try {
            const body = await json(dataUrl + '?' + params, requestController.signal);
            if (current !== generation) return;
            if (!Array.isArray(body.data)) throw new Error('Respons laporan tidak valid. Silakan login kembali.');
            table.rows.add(body.data).draw();
            document.querySelectorAll('[data-stat]').forEach(el => {el.textContent = body.statistics[el.dataset.stat];});
            const appliedParams = new URLSearchParams(params);
            document.getElementById('am-period').textContent = `${appliedParams.get('date_from')} s.d. ${appliedParams.get('date_to')}`;
            exportButton.href = exportUrl + '?' + params;
            if (new URLSearchParams(new FormData(form)).toString() === params) {
                exportButton.classList.remove('disabled');
                exportButton.setAttribute('aria-disabled', 'false');
            }
        } catch (error) {
            if (error.name === 'AbortError' || current !== generation) return;
            errorBox.textContent = error.message;
            errorBox.classList.remove('d-none');
            document.getElementById('am-period').textContent = 'Laporan belum berhasil dimuat';
        }
    }
    $('#am-table tbody').on('click', '.am-history-toggle', async function () {
        const row = table.row($(this).closest('tr'));
        const button = this;
        if (row.child.isShown()) {
            row.child.hide(); button.setAttribute('aria-expanded', 'false');
            button.innerHTML = '<i class="bi bi-chevron-down"></i>'; return;
        }
        const current = generation;
        button.setAttribute('aria-expanded', 'true');
        button.innerHTML = '<i class="bi bi-chevron-up"></i>';
        row.child('<div class="p-3" role="status">Memuat riwayat…</div>').show();
        try {
            const body = await json(historyUrl.replace(/\/0$/, '/' + row.data().id) + '?' + applied);
            if (current !== generation || button.getAttribute('aria-expanded') !== 'true') return;
            if (!Array.isArray(body.data)) throw new Error('Riwayat tidak tersedia. Silakan login kembali.');
            const rows = body.data.map(record => `<tr><td>${escape(record.tanggal)}</td><td>${escape(record.jenis)}</td><td>${escape(record.materi)}</td>${isExam ? `<td>#${record.percobaan}</td><td>${escape(record.nilai)}</td>` : ''}<td>${escape(record.hasil)}</td><td>${escape(record.pencatat)}</td><td>${escape(record.catatan || '-')}</td></tr>`).join('');
            row.child(`<div class="am-history my-2"><strong>Riwayat ${escape(body.santri)}</strong>${rows ? `<div class="table-responsive mt-3"><table class="table table-sm align-middle mb-0"><thead><tr><th>Tanggal</th><th>Jenis / Tujuan</th><th>Materi</th>${isExam ? '<th>Percobaan</th><th>Nilai</th>' : ''}<th>Hasil</th><th>Musyrif Pencatat</th><th>Catatan</th></tr></thead><tbody>${rows}</tbody></table></div>` : '<p class="text-muted mb-0 mt-2">Belum ada catatan sesuai filter.</p>'}</div>`).show();
        } catch (error) {
            if (current === generation && button.getAttribute('aria-expanded') === 'true') row.child(`<div class="alert alert-danger my-2">${escape(error.message)}</div>`).show();
        }
    });
    form.addEventListener('submit', event => {event.preventDefault(); load();});
    form.addEventListener('input', disableExport);
    form.addEventListener('change', disableExport);
    semester.addEventListener('change', bounds);
    from.addEventListener('change', () => {to.min = from.value || semester.selectedOptions[0].dataset.start;});
    document.getElementById('am-reset').addEventListener('click', () => {form.reset(); bounds(); load();});
    exportButton.addEventListener('click', event => {if (exportButton.classList.contains('disabled')) event.preventDefault();});
    bounds(); load();
});
</script>
@endif
@endpush
