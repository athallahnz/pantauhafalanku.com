@php
    $academicPeriod = ['semester_id' => $d['semester']['id'], 'date_from' => $d['period']['start_date'], 'date_to' => $d['period']['end_date']];
    $academicSummaryUrl = route('pimpinan.academic-summary', $academicPeriod);
    $academicModules = [
        'tahsin' => ['Tahsin Harian', 'pimpinan.daily-reports.tahsin.index', 'bi-book'],
        'exams' => ['Ujian Tahsin', 'pimpinan.monitoring.exams.index', 'bi-clipboard-check'],
        'tilawah' => ['Seluruh Tilawah', 'pimpinan.daily-reports.tilawah.index', 'bi-journal-text'],
        'mandiri' => ['Tilawah Mandiri', 'pimpinan.monitoring.tilawah.index', 'bi-journal-bookmark-fill'],
    ];
@endphp
<section class="mb-4" aria-labelledby="academic-summary-heading">
    <div class="section-head">
        <div><div class="section-kicker">Aktivitas Akademik</div><h2 class="section-title" id="academic-summary-heading">Tahsin, Ujian & Tilawah</h2>
        <p class="section-copy">Mengikuti semester dan periode analisis {{ $d['period']['label'] }}. Populasi tiap modul mengikuti penempatan semester, santri aktif, dan pemilik transaksi. Ringkasan hafalan menghitung ID penempatan semester; rekap akademik memuat profil santri yang tersedia, termasuk fallback aktif dan pemilik transaksi. Seluruh Tilawah mencakup Tilawah Mandiri; jumlah keduanya tidak dijumlahkan.</p></div>
    </div>
    <div id="pa-loading" class="text-muted mb-3" role="status"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat ringkasan akademik…</div>
    <div id="pa-error" class="alert alert-danger d-none" role="alert"><span id="pa-error-text"></span><button id="pa-retry" type="button" class="btn btn-outline-secondary btn-sm ms-2">Coba lagi</button></div>
    <div class="row g-3" aria-live="polite">
        @foreach($academicModules as $moduleKey => $module)
        <div class="col-md-6 col-xl-3"><div class="exec-card h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3"><strong>{{ $module[0] }}</strong><i class="bi {{ $module[2] }} text-muted" aria-hidden="true"></i></div>
            <div class="fs-2 fw-bold" data-academic="{{ $moduleKey }}.records">—</div><div class="text-muted small mb-3">{{ $moduleKey === 'exams' ? 'Record ujian' : 'Record aktivitas' }}</div>
            <dl class="small mb-3"><div class="d-flex justify-content-between"><dt>Populasi santri</dt><dd data-academic="{{ $moduleKey }}.population">—</dd></div><div class="d-flex justify-content-between"><dt>{{ $moduleKey === 'exams' ? 'Sudah ujian' : 'Ada aktivitas' }}</dt><dd data-academic="{{ $moduleKey }}.active">—</dd></div><div class="d-flex justify-content-between"><dt>{{ $moduleKey === 'exams' ? 'Belum ujian' : 'Tanpa aktivitas' }}</dt><dd data-academic="{{ $moduleKey }}.inactive">—</dd></div></dl>
            <a class="btn btn-outline-secondary btn-sm w-100" href="{{ route($module[1], $academicPeriod) }}">Buka rekap <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
            <div class="small text-muted mt-2">Periode rekap: {{ $academicPeriod['date_from'] }} s.d. {{ $academicPeriod['date_to'] }}</div>
        </div></div>
        @endforeach
    </div>
</section>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){
    const url=@json($academicSummaryUrl),loading=document.getElementById('pa-loading'),error=document.getElementById('pa-error'),retry=document.getElementById('pa-retry');
    async function load(){loading.classList.remove('d-none');error.classList.add('d-none');retry.disabled=true;
        try{const response=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin'});const body=await response.json().catch(()=>({}));if(!response.ok||response.redirected||!body.data)throw new Error(Object.values(body.errors||{}).flat().join(' ')||body.message||'Ringkasan belum berhasil dimuat.');
            const cells=[...document.querySelectorAll('[data-academic]')];const values=cells.map(el=>{const [module,key]=el.dataset.academic.split('.');const v=body.data[module]?.[key];if(typeof v!=='number')throw new Error('Respons ringkasan tidak valid.');return v;});cells.forEach((el,i)=>el.textContent=values[i].toLocaleString('id-ID'));
        }catch(e){document.getElementById('pa-error-text').textContent=e.message;error.classList.remove('d-none');}finally{loading.classList.add('d-none');retry.disabled=false;}}
    retry.addEventListener('click',load);load();
});
</script>
@endpush
