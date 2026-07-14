<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22px 22px 28px 22px; }
        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.2px;
            color: #172033;
            background: #ffffff;
        }
        h1, h2, h3, p { margin: 0; }
        .hero {
            padding: 16px 18px;
            border-radius: 16px;
            color: #ffffff;
            background: #5b48d9;
        }
        .hero-table { width: 100%; border-collapse: collapse; }
        .hero-title { font-size: 19px; line-height: 1.2; font-weight: bold; }
        .hero-subtitle { margin-top: 5px; color: #e9e6ff; font-size: 9.5px; line-height: 1.55; }
        .hero-period {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 999px;
            color: #ffffff;
            background: #13a3b3;
            font-size: 9px;
            font-weight: bold;
            white-space: nowrap;
        }
        .section { margin-top: 12px; }
        .section-title {
            margin-bottom: 6px;
            font-size: 12px;
            font-weight: bold;
            color: #172033;
        }
        .section-copy {
            margin-bottom: 8px;
            color: #64748b;
            font-size: 8.6px;
            line-height: 1.45;
        }
        .muted { color: #64748b; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 6px; margin-left: -6px; margin-right: -6px; }
        .summary td {
            width: 25%;
            padding: 9px 10px;
            border: 1px solid #dbe2f0;
            border-radius: 12px;
            background: #f8fafc;
            vertical-align: top;
        }
        .summary .card-purple { border-color: #c4b5fd; background: #f3f0ff; }
        .summary .card-blue { border-color: #93c5fd; background: #eff6ff; }
        .summary .card-green { border-color: #86efac; background: #ecfdf5; }
        .summary .card-orange { border-color: #fcd34d; background: #fffbeb; }
        .summary .label {
            font-size: 7.5px;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
        }
        .summary .value {
            margin-top: 4px;
            font-size: 16px;
            line-height: 1;
            color: #172033;
            font-weight: bold;
        }
        .summary .note { margin-top: 4px; color: #64748b; font-size: 7.7px; line-height: 1.35; }
        .insight-table { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 -6px; }
        .insight-table td {
            width: 33.33%;
            padding: 9px 10px;
            border-radius: 12px;
            border: 1px solid #dbe2f0;
            background: #f8fafc;
            vertical-align: top;
        }
        .insight-label { color: #64748b; font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: .04em; }
        .insight-value { margin-top: 4px; color: #172033; font-size: 13px; font-weight: bold; }
        .legend { margin: 7px 0 8px; font-size: 8px; color: #64748b; }
        .legend span { display: inline-block; margin-right: 9px; }
        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 8px; margin-right: 3px; vertical-align: -1px; }
        .dot-setoran { background: #8b5cf6; }
        .dot-ujian { background: #10b981; }
        .dot-empty { background: #cbd5e1; }
        .juz-grid { width: 100%; border-collapse: separate; border-spacing: 4px; margin-left: -4px; margin-right: -4px; }
        .juz-grid td { width: 20%; padding: 0; vertical-align: top; }
        .juz-card {
            min-height: 72px;
            padding: 7px;
            border: 1px solid #dbe2f0;
            border-radius: 10px;
            background: #f8fafc;
        }
        .juz-card.level-excellent { border-color: #34d399; background: #ecfdf5; }
        .juz-card.level-good { border-color: #60a5fa; background: #eff6ff; }
        .juz-card.level-progress { border-color: #f59e0b; background: #fffbeb; }
        .juz-card.level-started { border-color: #a78bfa; background: #f5f3ff; }
        .juz-card.level-setoran { border-color: #22d3ee; background: #ecfeff; }
        .juz-card.level-empty { border-color: #e2e8f0; background: #f8fafc; color: #94a3b8; }
        .juz-head { width: 100%; border-collapse: collapse; }
        .juz-title { font-size: 8.3px; font-weight: bold; color: #172033; }
        .juz-pct { text-align: right; font-size: 8px; font-weight: bold; color: #10b981; }
        .juz-main { margin-top: 4px; color: #172033; font-size: 14px; line-height: 1; font-weight: bold; }
        .juz-caption { margin-top: 2px; color: #64748b; font-size: 7.2px; }
        .bar { height: 5px; margin-top: 5px; border-radius: 99px; overflow: hidden; background: #e2e8f0; }
        .bar-fill-setoran { height: 5px; background: #8b5cf6; }
        .bar-fill-ujian { height: 5px; background: #10b981; }
        .juz-small { margin-top: 4px; color: #475569; font-size: 7.1px; line-height: 1.3; }
        .juz-status { margin-top: 3px; font-size: 7px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th, table.data td { padding: 5px; border: 1px solid #dbe2f0; }
        table.data th { background: #eef2ff; color: #334155; font-size: 7.5px; text-transform: uppercase; letter-spacing: .025em; }
        table.data tbody tr:nth-child(even) td { background: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 99px;
            background: #eef2ff;
            color: #5b48d9;
            font-size: 7.2px;
            font-weight: bold;
        }
        .page-break { page-break-before: always; }
    </style>
</head>

<body>
    <section class="hero">
        <table class="hero-table">
            <tr>
                <td>
                    <h1 class="hero-title">Rekap Hafalan per Kelas</h1>
                    <p class="hero-subtitle">Export PDF analitik kelas dengan visual 30 Juz untuk melihat kelas mana yang paling kuat pada setoran dan ujian.</p>
                </td>
                <td style="width: 190px; text-align: right; vertical-align: top;">
                    <span class="hero-period">Periode: {{ $periode }}</span>
                </td>
            </tr>
        </table>
    </section>

    <section class="section">
        <h2 class="section-title">Ringkasan Utama</h2>
        <table class="summary">
            <tr>
                <td class="card-purple"><div class="label">Total Kelas</div><div class="value">{{ $summary['total_kelas'] ?? 0 }}</div><div class="note">Kelas sesuai filter.</div></td>
                <td class="card-blue"><div class="label">Total Santri</div><div class="value">{{ $summary['total_santri'] ?? 0 }}</div><div class="note">Santri dalam placement semester.</div></td>
                <td class="card-green"><div class="label">Setoran Harian</div><div class="value">{{ $summary['total_setoran_harian'] ?? 0 }}</div><div class="note">Harian sampai tahap 3.</div></td>
                <td class="card-orange"><div class="label">Ujian / Juz</div><div class="value">{{ $summary['total_ujian_juz'] ?? 0 }}</div><div class="note">Juz lulus ujian akhir.</div></td>
            </tr>
            <tr>
                <td><div class="label">Rata Nilai Ujian</div><div class="value">{{ number_format((float) ($summary['avg_nilai_ujian'] ?? 0), 2) }}</div><div class="note">Rata-rata kelas dari ujian akhir.</div></td>
                <td><div class="label">HTS</div><div class="value">{{ $summary['total_hts'] ?? 0 }}</div><div class="note">Hadir tidak setor.</div></td>
                <td><div class="label">Izin/Sakit</div><div class="value">{{ (int) ($summary['total_izin'] ?? 0) + (int) ($summary['total_sakit'] ?? 0) }}</div><div class="note">Ketidakhadiran berizin.</div></td>
                <td><div class="label">Alpha</div><div class="value">{{ $summary['total_alpha'] ?? 0 }}</div><div class="note">Tanpa keterangan.</div></td>
            </tr>
        </table>
    </section>

    @php
        $juzProgress = $juzProgress ?? [];
        $juzItems = collect($juzProgress['items'] ?? []);
        $topJuz = $juzProgress['top_juz'] ?? null;
        $needsAttention = collect($juzProgress['needs_attention'] ?? []);
    @endphp

    <section class="section">
        <h2 class="section-title">Visual Progress 30 Juz - {{ $juzProgress['group_label'] ?? 'Laporan' }}</h2>
        <p class="section-copy">
            Setiap kartu menggambarkan seberapa banyak santri dalam filter export ini yang sudah masuk setoran pada Juz tersebut dan berapa yang sudah lulus ujian akhir.
        </p>

        <table class="insight-table">
            <tr>
                <td>
                    <div class="insight-label">Juz aktif</div>
                    <div class="insight-value">{{ $juzProgress['active_juz_count'] ?? 0 }}/30 Juz</div>
                    <div class="muted">Minimal ada setoran atau ujian.</div>
                </td>
                <td>
                    <div class="insight-label">Rata cakupan setoran</div>
                    <div class="insight-value">{{ number_format((float) ($juzProgress['avg_setoran_pct'] ?? 0), 1) }}%</div>
                    <div class="muted">Santri yang sudah masuk proses harian/tahap.</div>
                </td>
                <td>
                    <div class="insight-label">Rata cakupan ujian</div>
                    <div class="insight-value">{{ number_format((float) ($juzProgress['avg_ujian_pct'] ?? 0), 1) }}%</div>
                    <div class="muted">Santri yang sudah lulus ujian akhir.</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="insight-label">Juz paling kuat</div>
                    <div class="insight-value">
                        @if ($topJuz)
                            Juz {{ $topJuz['juz'] ?? '-' }}
                        @else
                            -
                        @endif
                    </div>
                    <div class="muted">
                        @if ($topJuz)
                            {{ (int) ($topJuz['ujian_santri'] ?? 0) }} santri lulus ujian, {{ (int) ($topJuz['setoran_santri'] ?? 0) }} santri proses setoran.
                        @else
                            Belum ada data.
                        @endif
                    </div>
                </td>
                <td>
                    <div class="insight-label">Total aktivitas setoran</div>
                    <div class="insight-value">{{ (int) ($juzProgress['total_setoran_records'] ?? 0) }}</div>
                    <div class="muted">Akumulasi setoran harian/tahap 1-3.</div>
                </td>
                <td>
                    <div class="insight-label">Total lulus ujian</div>
                    <div class="insight-value">{{ (int) ($juzProgress['total_ujian_records'] ?? 0) }}</div>
                    <div class="muted">Akumulasi santri-juz lulus ujian akhir.</div>
                </td>
            </tr>
        </table>

        <div class="legend">
            <span><i class="dot dot-setoran"></i>Bar ungu = santri sudah setoran harian/tahap</span>
            <span><i class="dot dot-ujian"></i>Bar hijau = santri lulus ujian akhir</span>
            <span><i class="dot dot-empty"></i>Abu-abu = belum ada progress</span>
        </div>

        <table class="juz-grid">
            @foreach ($juzItems->chunk(5) as $chunk)
                <tr>
                    @foreach ($chunk as $item)
                        <td>
                            <div class="juz-card level-{{ $item['level'] ?? 'empty' }}">
                                <table class="juz-head">
                                    <tr>
                                        <td class="juz-title">Juz {{ $item['juz'] ?? '-' }}</td>
                                        <td class="juz-pct">{{ number_format((float) ($item['ujian_pct'] ?? 0), 1) }}%</td>
                                    </tr>
                                </table>

                                <div class="juz-main">{{ (int) ($item['ujian_santri'] ?? 0) }}</div>
                                <div class="juz-caption">santri lulus ujian</div>

                                <div class="bar">
                                    <div class="bar-fill-setoran" style="width: {{ max(0, min(100, (float) ($item['setoran_pct'] ?? 0))) }}%;"></div>
                                </div>
                                <div class="bar">
                                    <div class="bar-fill-ujian" style="width: {{ max(0, min(100, (float) ($item['ujian_pct'] ?? 0))) }}%;"></div>
                                </div>

                                <div class="juz-small">
                                    Setoran: {{ (int) ($item['setoran_santri'] ?? 0) }} santri<br>
                                    Aktivitas: {{ (int) ($item['setoran_records'] ?? 0) }} setoran
                                </div>
                                <div class="juz-status">{{ $item['level_label'] ?? '-' }}</div>
                            </div>
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    </section>

    <section class="section page-break">
        <h2 class="section-title">Data Detail Kelas</h2>
        <table class="data">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kelas</th>
                    <th>Jumlah Santri</th>
                    <th>Setoran Harian</th>
                    <th>Ujian / Juz</th>
                    <th>HTS</th>
                    <th>Sakit</th>
                    <th>Izin</th>
                    <th>Alpha</th>
                    <th>Rata Nilai Ujian</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $row)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $row->nama_kelas ?? '-' }}</td>
                        <td class="text-right">{{ (int) ($row->jumlah_santri ?? 0) }}</td>
                        <td class="text-right">{{ (int) ($row->jumlah_setoran_harian ?? 0) }}</td>
                        <td class="text-right">{{ (int) ($row->jumlah_ujian ?? 0) }}</td>
                        <td class="text-right">{{ (int) ($row->hadir_tidak_setor ?? 0) }}</td>
                        <td class="text-right">{{ (int) ($row->sakit ?? 0) }}</td>
                        <td class="text-right">{{ (int) ($row->izin ?? 0) }}</td>
                        <td class="text-right">{{ (int) ($row->alpha ?? 0) }}</td>
                        <td class="text-right">{{ is_null($row->rata_nilai_ujian) ? '-' : number_format((float) $row->rata_nilai_ujian, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</body>
</html>
