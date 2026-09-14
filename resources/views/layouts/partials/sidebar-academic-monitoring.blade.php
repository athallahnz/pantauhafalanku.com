<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs($monitoringRole . '.monitoring.exams.*') ? 'active' : '' }}"
        href="{{ route($monitoringRole . '.monitoring.exams.index') }}">
        <i class="nav-icon bi bi-clipboard-check"></i> <span>Rekap Ujian Tahsin</span>
    </a>
</li>
<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs($monitoringRole . '.monitoring.tilawah.*') ? 'active' : '' }}"
        href="{{ route($monitoringRole . '.monitoring.tilawah.index') }}">
        <i class="nav-icon bi bi-journal-bookmark-fill"></i> <span>Rekap Tilawah Mandiri</span>
    </a>
</li>
