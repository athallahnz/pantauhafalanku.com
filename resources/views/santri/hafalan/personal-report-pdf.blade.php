<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Laporan Pribadi Santri</title>
<style>
body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #222; }
h1 { font-size: 17px; } h2 { font-size: 13px; color: #56348b; }
table { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
td, th { border: 1px solid #ddd; padding: 5px; vertical-align: top; overflow-wrap: break-word; }
th { background: #eee; text-align: left; } thead { display: table-header-group; }
.module { page-break-before: always; } tr { page-break-inside: avoid; }
</style></head><body>
<h1>Laporan Pribadi Santri</h1>
<p>{{ $santri->nama }} • NIS: {{ $santri->nis ?: '-' }}<br>Scope: {{ $scopeLabel }}</p>
<p>Laporan progres pribadi. Progres halaman Tahsin dan hasil Ujian Tahsin ditampilkan terpisah.
Tilawah kelompok dan murojaah tidak menambah juz selesai Mandiri Lanjut.
Cakupan ayat dihitung dari rentang terstruktur yang valid; data lama tetap muncul di riwayat.</p>
<table><thead><tr><th>Keterangan</th><th>Nilai</th></tr></thead><tbody>
@foreach ($parameters as $row)<tr><td>{{ $row[0] }}</td><td>{{ $row[1] }}</td></tr>@endforeach
</tbody></table>
@foreach ($histories as $name => $rows)
<div class="module"><h2>{{ $name }} — {{ count($rows) }} record</h2>
<table><colgroup><col style="width:8%"><col style="width:10%"><col style="width:11%"><col style="width:14%"><col style="width:8%"><col style="width:8%"><col style="width:5%"><col style="width:11%"><col style="width:25%"></colgroup><thead><tr>@foreach (\App\Services\SantriPersonalReportService::HEADERS as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
<tbody>@forelse ($rows as $row)<tr>@foreach ($row as $value)<td>{{ $value }}</td>@endforeach</tr>
@empty<tr><td colspan="9">Belum ada riwayat pada scope ini.</td></tr>@endforelse</tbody></table>
</div>@endforeach
</body></html>
