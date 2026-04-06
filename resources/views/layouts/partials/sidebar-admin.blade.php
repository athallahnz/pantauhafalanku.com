<li class="nav-title">Menu</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <i class="nav-icon bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
    </a>
</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}"
        href="{{ route('admin.laporan.index') }}">
        <i class="nav-icon bi bi-bar-chart-fill"></i> <span>Laporan</span>
    </a>
</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('admin.activity_logs.*') ? 'active' : '' }}"
        href="{{ route('admin.activity_logs.index') }}">
        <i class="nav-icon bi bi-clock-history"></i> <span>Log Aktivitas</span>
    </a>
</li>

<li class="nav-title">Master Data</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('kelas.*') ? 'active' : '' }}" href="{{ route('kelas.index') }}">
        <i class="nav-icon bi bi-easel2-fill"></i> <span>Kelas</span>
    </a>
</li>

{{-- MENU MUSYRIF DROPDOWN (COLLAPSE) --}}
<li class="nav-group {{ request()->routeIs('admin.musyrif.*', 'admin.attendances.*') ? 'show' : '' }} mb-1">
    <a class="nav-link nav-group-toggle" href="#">
        <i class="nav-icon bi bi-person-plus-fill"></i> <span>Musyrif</span>
    </a>
    <ul class="nav-group-items">
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.musyrif.index') ? 'active' : '' }}"
                href="{{ route('admin.musyrif.index') }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.4rem;"></i> Data Musyrif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('admin.musyrif.absensi.index') ? 'active' : '' }}"
                href="{{ route('admin.musyrif.absensi.index') }}">
                <i class="nav-icon bi bi-circle-fill" style="font-size: 0.4rem;"></i> Log Absensi
            </a>
        </li>
    </ul>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('santri.master.*') ? 'active' : '' }}"
        href="{{ route('santri.master.index') }}">
        <i class="nav-icon bi bi-people-fill"></i> <span>Santri</span>
    </a>
</li>
