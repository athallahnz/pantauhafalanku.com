<li class="nav-title">Menu</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('musyrif.dashboard') ? 'active' : '' }}"
        href="{{ route('musyrif.dashboard') }}">
        <i class="nav-icon bi bi-grid-1x2-fill"></i> <span>Dashboard</span>
    </a>
</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('musyrif.absensi.index') ? 'active' : '' }}"
        href="{{ route('musyrif.absensi.index') }}">
        <i class="nav-icon bi bi-camera-fill"></i> <span>Absensi</span>
    </a>
</li>

<li class="nav-title">Data Santri</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('musyrif.santri.*') ? 'active' : '' }}"
        href="{{ route('musyrif.santri.index') }}">
        <i class="nav-icon bi bi-person-lines-fill"></i> <span>Santri Binaan</span>
    </a>
</li>

<li class="nav-title">Data Hafalan</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('musyrif.hafalan.*') ? 'active' : '' }}"
        href="{{ route('musyrif.hafalan.index') }}">
        <i class="nav-icon bi bi-journal-check"></i> <span>Hafalan Santri</span>
    </a>
</li>

<li class="nav-title">Data Tahsin & Tilawah</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('musyrif.tahsin.*') ? 'active' : '' }}"
        href="{{ route('musyrif.tahsin.index') }}">
        <i class="nav-icon bi bi-book-half"></i> <span>Tahsin & Tilawah Santri</span>
    </a>
</li>
