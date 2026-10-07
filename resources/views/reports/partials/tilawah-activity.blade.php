<section class="student-card p-3 p-lg-4 mb-4">
    <h3 class="student-section-title text-success">Aktivitas Tilawah</h3>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Jenis</th><th>Record</th><th>Cakupan ayat unik hadir / lanjut</th></tr></thead>
            <tbody>
                <tr><td>Mandiri Lanjut</td><td>{{ $report['activity_counts']['continuation'] }}</td><td>{{ $report['unique_ayat']['individual'] }}</td></tr>
                <tr><td>Mandiri Murojaah</td><td>{{ $report['activity_counts']['review'] }}</td><td>Aktivitas pengulangan</td></tr>
                <tr><td>Kelompok</td><td>{{ $report['activity_counts']['group'] }}</td><td>{{ $report['unique_ayat']['group'] }}</td></tr>
                <tr><td>Susulan</td><td>{{ $report['activity_counts']['catchup'] }}</td><td>{{ $report['unique_ayat']['catchup'] }}</td></tr>
                <tr><td>Data lama / jenis belum diketahui</td><td>{{ $report['activity_counts']['legacy'] }}</td><td>Belum terukur</td></tr>
            </tbody>
        </table>
    </div>
    <p class="small text-muted mt-2 mb-0">Record mencakup semua status. Cakupan ayat memakai rentang bacaan valid yang hadir; bacaan berulang tidak dihitung ganda. Data tanpa rentang valid tetap masuk riwayat.</p>
</section>
