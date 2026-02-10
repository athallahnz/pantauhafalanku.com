{{-- Sidebar untuk MUSYRIF --}}
<li class="nav-title">Menu</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('musyrif.dashboard') ? 'active' : '' }}"
        href="{{ route('musyrif.dashboard') }}">
        <i class="nav-icon cil-speedometer"></i> Dashboard
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('musyrif.absensi.index') ? 'active' : '' }}"
        href="{{ route('musyrif.absensi.index') }}">
        <i class="nav-icon cil-camera"></i> Absensi
    </a>
</li>

<li class="nav-title">Data Santri</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('musyrif.santri.*') ? 'active' : '' }}"
        href="{{ route('musyrif.santri.index') }}">
        <i class="nav-icon cil-people"></i> Santri Binaan
    </a>
</li>

<li class="nav-title">Data Hafalan</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('musyrif.hafalan.*') ? 'active' : '' }}"
        href="{{ route('musyrif.hafalan.index') }}">
        <i class="nav-icon cil-notes"></i> Hafalan Santri
    </a>
</li>
