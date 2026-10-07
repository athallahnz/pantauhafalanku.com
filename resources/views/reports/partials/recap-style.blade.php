<style>
.recap-page {--rp-surface:#fff;--rp-muted:#f5f6fa;--rp-text:#20242c;--rp-secondary:#6f7580;--rp-border:rgba(31,41,55,.10);--rp-purple:#6b4eff;--rp-tosca:#13a3b3;--rp-shadow:0 4px 14px rgba(27,31,59,.055);color:var(--rp-text);padding-bottom:1.75rem}
[data-coreui-theme="dark"] .recap-page {--rp-surface:#20242d;--rp-muted:#292e38;--rp-text:#f1f3f5;--rp-secondary:#aeb4bf;--rp-border:rgba(255,255,255,.09);--rp-purple:#9a86ff;--rp-tosca:#58c8d3;--rp-shadow:0 8px 22px rgba(0,0,0,.24)}
.recap-page .card {background:var(--rp-surface);border:1px solid var(--rp-border);border-radius:20px;box-shadow:var(--rp-shadow);overflow:hidden}
.recap-page .card-body {padding:1.4rem}
.recap-page .recap-hero {position:relative;border-radius:24px;background:linear-gradient(135deg,rgba(107,78,255,.075),rgba(19,163,179,.035)),var(--rp-surface);color:var(--rp-text)}
.recap-page .recap-hero h4 {color:var(--rp-text)!important;font-size:clamp(1.4rem,2vw,1.9rem);font-weight:800;letter-spacing:-.025em}
.recap-page .recap-hero p {color:var(--rp-secondary);line-height:1.65}
.recap-eyebrow {display:block;font-size:.69rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--rp-purple);margin-bottom:.65rem}
.recap-page .recap-export {background:var(--rp-purple);border-color:var(--rp-purple);color:#fff;border-radius:12px;padding:.65rem 1rem;font-weight:700}
[data-coreui-theme="dark"] .recap-page .recap-export {color:#171a21}
.recap-section-title {font-size:1rem;font-weight:800;margin-bottom:1rem;display:flex;align-items:center;gap:.6rem}
.recap-section-title i {color:var(--rp-purple)}
.recap-page .form-label {font-size:.78rem;font-weight:700;color:var(--rp-secondary)}
.recap-page .form-control,.recap-page .form-select {border:1px solid var(--rp-border);border-radius:12px;min-height:44px;background-color:var(--rp-surface);color:var(--rp-text)}
.recap-page .form-control:focus,.recap-page .form-select:focus {border-color:var(--rp-purple);box-shadow:0 0 0 3px rgba(107,78,255,.12)}
.recap-page .btn {border-radius:12px}.recap-page .btn-primary {background:var(--rp-purple);border-color:var(--rp-purple)}
.recap-page .recap-stats .card {position:relative;min-height:114px}.recap-page .recap-stats .card::before {content:'';position:absolute;inset:0 auto 0 0;width:4px;background:var(--rp-purple)}
.recap-page .recap-stats>div:nth-child(even) .card::before {background:var(--rp-tosca)}
.recap-page .recap-stats .small,.recap-page .am-label {color:var(--rp-secondary);font-size:.75rem;font-weight:700}
.recap-page .recap-stats strong,.recap-page .am-value {display:block;font-size:1.8rem;font-weight:800;color:var(--rp-text);margin-top:.35rem;font-variant-numeric:tabular-nums}
.recap-page .table {--cui-table-color:var(--rp-text);--cui-table-bg:transparent;--cui-table-border-color:var(--rp-border);--cui-table-hover-bg:rgba(107,78,255,.045);--cui-table-hover-color:var(--rp-text);font-size:.82rem}
.recap-page .table th {background:var(--rp-muted);color:var(--rp-secondary);font-size:.69rem;letter-spacing:.04em;text-transform:uppercase;padding:.9rem .8rem;white-space:nowrap;border-bottom:1px solid var(--rp-border)}
.recap-page .table td {padding:1rem .8rem;vertical-align:middle;line-height:1.6;border-color:var(--rp-border)}
.recap-page .table tbody tr:nth-child(even):not(.detail) {background:rgba(107,78,255,.025)}
.recap-page .text-muted,.recap-page .text-body-secondary {color:var(--rp-secondary)!important}
.recap-page .recap-help {background:var(--rp-muted);border-radius:12px;padding:.85rem 1rem;color:var(--rp-secondary);line-height:1.65}
.recap-page .recap-person {min-width:180px}.recap-page .recap-person strong {display:block}.recap-page .recap-person small {color:var(--rp-secondary)}
.recap-page .recap-placement {min-width:175px}.recap-page .recap-attendance {min-width:190px}.recap-page .recap-progress {min-width:230px;max-width:300px}
.recap-page .recap-progress .progress {height:6px;min-width:0;margin:.5rem 0;background:var(--rp-muted)}
.recap-page .progress-bar {background:linear-gradient(90deg,var(--rp-tosca),var(--rp-purple))}
.recap-page .detail,.recap-page .am-history {background:var(--rp-muted)}
.recap-page .card-header {background:var(--rp-muted)!important;border-color:var(--rp-border)}
@media(max-width:767px){.recap-page .card-body{padding:1rem}.recap-page .recap-hero .card-body{align-items:flex-start!important}.recap-page .recap-export{width:100%;text-align:center}.recap-page .recap-stats .card{min-height:100px}}
</style>
