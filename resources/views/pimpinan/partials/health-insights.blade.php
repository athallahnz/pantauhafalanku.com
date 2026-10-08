@php
    $limits = $d['thresholds'];
    $k = $d['kpi'];
    $a = $d['attendance'];
    $comparison = $d['comparison'];
    $delta = $comparison['setoran_pct'] ?? null;
    $grade = static function ($value, $good, $critical, $higherIsBetter = true) {
        if ($value === null) {
            return ['secondary', 'Data belum cukup'];
        }

        if ($higherIsBetter) {
            if ($value < $critical) {
                return ['danger', 'Kritis'];
            }
            if ($value < $good) {
                return ['warning', 'Perlu Perhatian'];
            }
            return ['success', 'Baik'];
        }

        if (!($value < $critical)) {
            return ['danger', 'Kritis'];
        }
        if (!($value < $good)) {
            return ['warning', 'Perlu Perhatian'];
        }
        return ['success', 'Baik'];
    };
    $coverageGrade = $grade($k['coverage_pct'], $limits['coverage_good'] ?? 85, $limits['coverage_attention'] ?? 70);
    $attendanceGrade = $grade($a['total_records'] > 0 ? $a['valid_pct'] : null, $limits['attendance_good'] ?? 90, $limits['attendance_attention'] ?? 75);
    $alphaGrade = $grade($k['alpha_risk_rate_pct'], $limits['alpha_rate_attention'] ?? 5, $limits['alpha_rate_critical'] ?? 10, false);
    // Trend boundaries use <=, including exactly -5% and -15%.
    $trendGrade = $delta === null ? ['secondary', 'Data belum cukup'] : ($delta <= ($limits['setoran_delta_critical'] ?? -15) ? ['danger', 'Kritis'] : ($delta <= ($limits['setoran_delta_attention'] ?? -5) ? ['warning', 'Perlu Perhatian'] : ['success', 'Baik']));
    $cards = [
        ['id'=>'coverage','title'=>'Cakupan santri setor','value'=>$k['coverage_pct'],'grade'=>$coverageGrade,'definition'=>'Santri dengan minimal satu setoran hafalan lulus atau ulang, dibagi populasi pada periode terpilih.','threshold'=>'Baik ≥'.($limits['coverage_good'] ?? 85).'%; Kritis <'.($limits['coverage_attention'] ?? 70).'%.','direction'=>'Minta Kepala Dept Quran memeriksa santri belum setor per kelas: apakah kegiatan belum berjalan, santri berhalangan, atau pencatatan belum lengkap. Tetapkan prioritas pendampingan berdasarkan penyebab.'],
        ['id'=>'attendance','title'=>'Validitas absensi Musyrif','value'=>$a['total_records'] > 0 ? $a['valid_pct'] : null,'grade'=>$attendanceGrade,'definition'=>'Catatan absensi valid dibagi seluruh catatan yang masuk. Absensi yang tidak diinput belum termasuk dalam penyebut.','threshold'=>'Baik ≥'.($limits['attendance_good'] ?? 90).'%; Kritis <'.($limits['attendance_attention'] ?? 75).'%.','direction'=>'Minta Kepala Dept Quran memeriksa catatan suspect/rejected dan kelengkapan input. Bedakan kendala perangkat/lokasi dengan ketidakhadiran sebelum menentukan tindakan.'],
        ['id'=>'alpha','title'=>'Santri berisiko alpha','value'=>$k['alpha_risk_rate_pct'],'grade'=>$alphaGrade,'definition'=>'Santri dengan minimal '.config('quran-executive.risk.alpha_minimum',3).' catatan alpha dalam periode ini, dibagi populasi santri.','threshold'=>'Perlu Perhatian ≥'.($limits['alpha_rate_attention'] ?? 5).'%; Kritis ≥'.($limits['alpha_rate_critical'] ?? 10).'%.','direction'=>'Minta Kepala Dept Quran menelusuri santri dengan alpha berulang bersama Musyrif dan wali. Pastikan penyebab serta validitas pencatatan sebelum menyusun pendampingan.'],
        ['id'=>'trend','title'=>'Tren jumlah setoran','value'=>$delta,'grade'=>$trendGrade,'definition'=>'Perubahan jumlah transaksi setoran hafalan lulus atau ulang pada dua periode pembanding. Bukan perubahan nilai atau kualitas hafalan.','threshold'=>'Perlu Perhatian saat turun ≥'.abs($limits['setoran_delta_attention'] ?? -5).'%; Kritis saat turun ≥'.abs($limits['setoran_delta_critical'] ?? -15).'%.','direction'=>'Minta Kepala Dept Quran menelaah penurunan bersama kalender kegiatan, hari libur dan kelengkapan input. Bandingkan kelas serta Musyrif sebelum menyimpulkan penurunan kinerja.'],
    ];
    $severity = ['secondary'=>0, 'success'=>1, 'warning'=>2, 'danger'=>3];
    $selectedIndicator = $cards[0]['id'];
    $selectedSeverity = -1;
    foreach ($cards as $indicator) {
        if ($severity[$indicator['grade'][0]] > $selectedSeverity) {
            $selectedIndicator = $indicator['id'];
            $selectedSeverity = $severity[$indicator['grade'][0]];
        }
    }
@endphp
<style>
/* Reset Bootstrap/CoreUI legend float so the grid cannot sit beside it. */
#pimpinanHealthInsights fieldset{display:block!important;min-width:0!important;min-inline-size:0!important;width:100%!important;max-width:100%!important}
#pimpinanHealthInsights legend{float:none!important;display:block!important;width:100%!important;max-width:100%!important;margin:0 0 8px!important;padding:0!important;font-size:.875rem!important;line-height:1.5!important}
#pimpinanHealthInsights .health-options{clear:both!important;width:100%!important;max-width:100%!important;min-width:0!important}
#pimpinanHealthInsights .health-panels{clear:both;min-width:0;max-width:100%}
#pimpinanHealthInsights .health-indicator-link{box-sizing:border-box!important;width:100%!important;max-width:100%!important;min-width:0!important;overflow-wrap:anywhere}

#pimpinanHealthInsights .modal-dialog{width:calc(100% - 32px)!important;max-width:1040px!important;margin:16px auto!important;height:calc(100dvh - 32px)!important}
#pimpinanHealthInsights .modal-content{max-height:100%!important;overflow:hidden}
#pimpinanHealthInsights .modal-header{background:var(--cui-modal-bg,var(--bs-body-bg,#202936))!important;color:inherit!important;padding:16px!important;gap:12px;border-bottom:1px solid var(--cui-border-color,#465061)}
#pimpinanHealthInsights .modal-header>div{flex:1;min-width:0}
#pimpinanHealthInsights .modal-title{font-size:1.1rem;line-height:1.4}
#pimpinanHealthInsights .btn-close{flex:0 0 auto;margin:0!important;padding:12px!important}
#pimpinanHealthInsights .modal-body{padding:16px!important;overflow-x:hidden;overflow-wrap:anywhere;overscroll-behavior:contain}
#pimpinanHealthInsights .health-summary{display:flex;align-items:center;gap:10px;margin-bottom:12px;font-size:.85rem}
#pimpinanHealthInsights .health-options{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:16px}
#pimpinanHealthInsights .health-choice{position:absolute;width:1px;height:1px;opacity:0}
#pimpinanHealthInsights .health-indicator-link{display:flex;flex-direction:column;gap:4px;cursor:pointer;margin:0;padding:12px;border:1px solid var(--cui-border-color,#465061);border-radius:12px;background:transparent;color:inherit;min-width:0;line-height:1.35}
#pimpinanHealthInsights .health-indicator-link strong{font-size:1.5rem}
#pimpinanHealthInsights .health-indicator-link .badge{align-self:flex-start;white-space:normal;font-size:.7rem}
#pimpinanHealthInsights .health-panels>section{display:none}
#pimpinanHealthInsights section{padding:16px!important;margin-bottom:12px!important;border-radius:12px!important}
#pimpinanHealthInsights section p{font-size:.875rem;line-height:1.6}
#pimpinanHealthInsights details>summary{cursor:pointer;min-height:44px;display:flex;align-items:center;font-size:.875rem}
#pimpinanHealthInsights .health-method{font-size:.78rem;color:var(--cui-secondary-color,#adb5bd)}
#pimpinanHealthInsights .table{font-size:.8rem}
#pimpinanHealthInsights .table th,#pimpinanHealthInsights .table td{padding:9px 6px;vertical-align:middle;white-space:normal}
#health-choice-coverage:checked ~ .health-panels #health-coverage{display:block}
#health-choice-coverage:checked ~ .health-options label[for="health-choice-coverage"]{border-color:var(--cui-primary,#8b80ff);background:rgba(128,110,255,.12);box-shadow:inset 0 0 0 1px var(--cui-primary,#8b80ff)}
#health-choice-coverage:focus-visible ~ .health-options label[for="health-choice-coverage"]{outline:3px solid #998cff;outline-offset:2px}
#health-choice-attendance:checked ~ .health-panels #health-attendance{display:block}
#health-choice-attendance:checked ~ .health-options label[for="health-choice-attendance"]{border-color:var(--cui-primary,#8b80ff);background:rgba(128,110,255,.12);box-shadow:inset 0 0 0 1px var(--cui-primary,#8b80ff)}
#health-choice-attendance:focus-visible ~ .health-options label[for="health-choice-attendance"]{outline:3px solid #998cff;outline-offset:2px}
#health-choice-alpha:checked ~ .health-panels #health-alpha{display:block}
#health-choice-alpha:checked ~ .health-options label[for="health-choice-alpha"]{border-color:var(--cui-primary,#8b80ff);background:rgba(128,110,255,.12);box-shadow:inset 0 0 0 1px var(--cui-primary,#8b80ff)}
#health-choice-alpha:focus-visible ~ .health-options label[for="health-choice-alpha"]{outline:3px solid #998cff;outline-offset:2px}
#health-choice-trend:checked ~ .health-panels #health-trend{display:block}
#health-choice-trend:checked ~ .health-options label[for="health-choice-trend"]{border-color:var(--cui-primary,#8b80ff);background:rgba(128,110,255,.12);box-shadow:inset 0 0 0 1px var(--cui-primary,#8b80ff)}
#health-choice-trend:focus-visible ~ .health-options label[for="health-choice-trend"]{outline:3px solid #998cff;outline-offset:2px}
@media(max-width:767.98px){
 #pimpinanHealthInsights{padding:0!important}
 #pimpinanHealthInsights .modal-dialog{width:100%!important;max-width:100%!important;margin:0!important;height:100dvh!important}
 #pimpinanHealthInsights .modal-content{height:100%!important;border:0!important;border-radius:0!important}
 #pimpinanHealthInsights .modal-header{padding:12px 16px!important;padding-top:max(12px,env(safe-area-inset-top))!important}
 #pimpinanHealthInsights .modal-title{font-size:1rem}
 #pimpinanHealthInsights .modal-header .small{font-size:.72rem;margin-top:3px;line-height:1.4}
 #pimpinanHealthInsights .modal-body{padding:12px 16px max(24px,env(safe-area-inset-bottom))!important}
 #pimpinanHealthInsights .health-options{grid-template-columns:repeat(2,minmax(0,1fr))}
 #pimpinanHealthInsights .health-indicator-link{padding:10px;gap:3px}
 #pimpinanHealthInsights .health-indicator-link .small{font-size:.75rem}
 #pimpinanHealthInsights .health-indicator-link strong{font-size:1.35rem}
 #pimpinanHealthInsights section{padding:12px!important}
 #pimpinanHealthInsights .health-evidence-table thead{display:none}
 #pimpinanHealthInsights .health-evidence-table tr{display:flex;flex-wrap:wrap;gap:6px 12px;padding:10px 0;border-bottom:1px solid var(--cui-border-color,#465061)}
 #pimpinanHealthInsights .health-evidence-table td{border:0;padding:0!important;font-size:.78rem}
 #pimpinanHealthInsights .health-evidence-table td:first-child{width:100%;font-weight:600}
 #pimpinanHealthInsights .health-evidence-table td[data-label]::before{content:attr(data-label) ': ';color:var(--cui-secondary-color,#adb5bd)}
}
</style>
<div class="modal fade" id="pimpinanHealthInsights" tabindex="-1" aria-labelledby="pimpinanHealthInsightsTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div><h5 class="modal-title" id="pimpinanHealthInsightsTitle">Ringkasan Kondisi Quran</h5><div class="small text-body-secondary">{{ $d['semester']['label'] }} · {{ $d['period']['label'] }}</div></div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="health-summary"><span class="badge text-bg-{{ $healthTone }}">{{ $d['health']['label'] }}</span><span>Mengikuti indikator terburuk.</span></div>
                <fieldset class="border-0 p-0 m-0">
                <legend class="small fw-semibold mb-2">Pilih indikator untuk melihat pemicu dan bukti</legend>
                @foreach($cards as $card)
                <input class="health-choice" type="radio" name="health-indicator" id="health-choice-{{ $card['id'] }}" aria-controls="health-{{ $card['id'] }}" {{ $selectedIndicator === $card['id'] ? 'checked' : '' }}>
                @endforeach
                <div class="health-options">
                    @foreach($cards as $card)
                    <label class="health-indicator-link" for="health-choice-{{ $card['id'] }}"><span class="small">{{ $card['title'] }}</span><strong>{{ $card['value'] === null ? '—' : number_format($card['value'],1,',','.').'%' }}</strong><span class="badge text-bg-{{ $card['grade'][0] }}">{{ $card['grade'][1] }}</span></label>
                    @endforeach
                </div>
                <div class="health-panels">
                @foreach($cards as $card)
                <section id="health-{{ $card['id'] }}" class="border rounded-3 p-3 mb-3" style="scroll-margin-top:1rem">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2"><h6 class="mb-0">{{ $card['title'] }}</h6><span class="badge text-bg-{{ $card['grade'][0] }}">{{ $card['grade'][1] }}</span></div>
                    <div class="row g-3">
                        <div class="col-lg-6"><div class="small fw-semibold text-body-secondary mb-1">PEMICU & DEFINISI</div><p class="mb-1">{{ $card['definition'] }}</p><p class="small mb-0">{{ $card['threshold'] }}</p></div>
                        <div class="col-lg-6"><div class="small fw-semibold text-body-secondary mb-1">ARAHAN YANG DISARANKAN</div><p class="mb-0">{{ $card['direction'] }}</p></div>
                    </div>
                    <details class="mt-3" open><summary class="fw-semibold mb-2">Lihat bukti pendukung</summary>
                    @if($card['id'] === 'coverage')
                        <p>{{ number_format($k['santri_aktif']) }} dari {{ number_format($k['total_santri']) }} santri pernah setor; {{ number_format($k['santri_belum_setor']) }} belum memiliki setoran lulus/ulang.</p>
                        <div class="progress mb-2" role="progressbar" aria-label="Cakupan santri setor" aria-valuenow="{{ $k['coverage_pct'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-{{ $card['grade'][0] }}" style="width:{{ max(0,min(100,$k['coverage_pct'])) }}%"></div></div>
                        <p class="small text-body-secondary">Cuplikan maksimal 10 kelas dari daftar prioritas dashboard; bukan daftar seluruh santri belum setor.</p>
                        <div class="table-responsive"><table class="table table-sm health-evidence-table"><thead><tr><th>Kelas</th><th>Populasi</th><th>Pernah setor</th><th>Cakupan</th></tr></thead><tbody>@forelse($d['class_performance']['rows'] as $row)<tr><td>{{ $row['nama'] }}</td><td data-label="Populasi">{{ $row['total_santri'] }}</td><td data-label="Setor">{{ $row['santri_aktif'] }}</td><td data-label="Cakupan">{{ number_format($row['coverage_pct'],1,',','.') }}%</td></tr>@empty<tr><td colspan="4">Data kelas belum tersedia.</td></tr>@endforelse</tbody></table></div>
                    @elseif($card['id'] === 'attendance')
                        <p>{{ $a['valid_records'] }} valid / {{ $a['total_records'] }} catatan masuk; {{ $a['suspect_records'] }} suspect dan {{ $a['rejected_records'] }} rejected.</p>
                        @if($a['total_records'] > 0)<div class="progress mb-2" role="progressbar" aria-label="Validitas absensi" aria-valuenow="{{ $a['valid_pct'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-{{ $card['grade'][0] }}" style="width:{{ max(0,min(100,$a['valid_pct'])) }}%"></div></div>@else<p class="text-body-secondary">Tanpa catatan, indikator ini tidak digunakan untuk menentukan status keseluruhan.</p>@endif
                        <p class="small text-body-secondary">Cuplikan Musyrif dari daftar prioritas dashboard. Persentase nol perlu diperiksa bersama jumlah catatan, bukan langsung dianggap tidak hadir.</p>
                        <div class="table-responsive"><table class="table table-sm health-evidence-table"><thead><tr><th>Musyrif</th><th>Validitas absensi</th><th>Santri binaan</th></tr></thead><tbody>@forelse($d['musyrif_performance']['rows'] as $row)<tr><td>{{ $row['nama'] }}</td><td data-label="Validitas">{{ number_format($row['attendance_pct'],1,',','.') }}%</td><td data-label="Santri binaan">{{ $row['total_santri'] }}</td></tr>@empty<tr><td colspan="3">Data Musyrif belum tersedia.</td></tr>@endforelse</tbody></table></div>
                    @elseif($card['id'] === 'alpha')
                        <p><strong>{{ $k['santri_risiko_alpha'] }}</strong> dari {{ $k['total_santri'] }} santri memenuhi ambang alpha berulang. Jumlah transaksi alpha: <strong>{{ $k['alpha'] }}</strong>.</p>
                        <div class="progress mb-2" role="progressbar" aria-label="Persentase santri berisiko alpha" aria-valuenow="{{ $k['alpha_risk_rate_pct'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-{{ $card['grade'][0] }}" style="width:{{ max(0,min(100,$k['alpha_risk_rate_pct'])) }}%"></div></div>
                        <p class="small text-body-secondary mb-0">Jumlah santri berisiko berbeda dari jumlah kejadian alpha. Rincian nama santri belum disediakan oleh payload dashboard ini; identitas tidak disimpulkan dari persentase kelas.</p>
                    @else
                        @if($delta !== null)
                        <p>{{ $comparison['label'] ?? '' }}</p>
                        <div class="row g-2">@foreach(['previous'=>'Periode sebelumnya','current'=>'Periode berjalan'] as $periodKey=>$periodLabel)<div class="col-md-6"><div class="bg-body-tertiary border rounded p-3"><span class="small">{{ $periodLabel }} · {{ $comparison[$periodKey]['start_date'] }} — {{ $comparison[$periodKey]['end_date'] }}</span><strong class="d-block fs-3">{{ number_format($comparison[$periodKey]['total_setor']) }} setoran</strong></div></div>@endforeach</div>
                        @else<p class="mb-0">Perbandingan belum dapat dihitung: periode pembanding belum tersedia atau jumlah setoran sebelumnya nol. Ini bukan penurunan 0%.</p>@endif
                    @endif
                    </details>
                </section>
                @endforeach
                </div>
                </fieldset>
                <details class="health-method"><summary>Cara membaca indikator</summary><p>Ambang adalah benchmark awal, belum target resmi semester. Penilaian belum mengukur seluruh Tahsin/Tilawah. Bukti menunjukkan kondisi tercatat, bukan penyelesaian tindak lanjut.</p><p>{{ $d['health']['summary'] }}</p></details>
            </div>
        </div>
    </div>
</div>
