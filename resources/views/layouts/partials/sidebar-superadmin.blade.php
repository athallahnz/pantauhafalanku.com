<li class="nav-title">Menu</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
        href="{{ route('superadmin.dashboard') }}">
        <i class="nav-icon bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
    </a>
</li>

<li class="nav-title">Manajemen</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}"
        href="{{ route('superadmin.users.index') }}">
        <i class="nav-icon bi bi-shield-lock-fill"></i> <span>Manajemen User</span>
    </a>
</li>

<li class="nav-title">Master Data</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('kelas.*') ? 'active' : '' }}" href="{{ route('kelas.index') }}">
        <i class="nav-icon bi bi-houses-fill"></i> <span>Kelas</span>
    </a>
</li>
