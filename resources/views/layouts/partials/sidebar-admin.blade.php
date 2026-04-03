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

<li class="nav-title">Master Data</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('kelas.*') ? 'active' : '' }}" href="{{ route('kelas.index') }}">
        <i class="nav-icon bi bi-easel2-fill"></i> <span>Kelas</span>
    </a>
</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('admin.musyrif.*') ? 'active' : '' }}"
        href="{{ route('admin.musyrif.index') }}">
        <i class="nav-icon bi bi-person-plus-fill"></i> <span>Musyrif</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('santri.master.*') ? 'active' : '' }}"
        href="{{ route('santri.master.index') }}">
        <i class="nav-icon bi bi-people-fill"></i> <span>Santri</span>
    </a>
</li>
