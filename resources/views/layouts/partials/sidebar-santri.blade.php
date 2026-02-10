{{-- Sidebar untuk SANTRI --}}
<li class="nav-title">Menu</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('santri.dashboard') ? 'active' : '' }}"
        href="{{ route('santri.dashboard') }}">
        <i class="nav-icon cil-speedometer"></i> Dashboard
    </a>
</li>

{{-- <li class="nav-title">Hafalan Saya</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('santri.hafalan.*') ? 'active' : '' }}"
        href="{{ route('santri.hafalan.index') }}">
        <i class="nav-icon cil-notes"></i> Riwayat Hafalan
    </a>
</li> --}}
