{{-- Sidebar untuk ADMIN / DEPARTEMEN --}}
<li class="nav-title">Menu</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
        <i class="nav-icon cil-speedometer"></i> Dashboard
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('admin.laporan.*') ? 'active' : '' }}"
        href="{{ route('admin.laporan.index') }}">
        <i class="nav-icon cil-chart"></i> Laporan
    </a>
</li>

<li class="nav-title">Master Data</li>

<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('kelas.*') ? 'active' : '' }}" href="{{ route('kelas.index') }}">
        <i class="nav-icon cil-school"></i> Kelas
    </a>
</li>
<li class="nav-item mb-1">
    <a class="nav-link {{ request()->routeIs('admin.musyrif.*') ? 'active' : '' }}" href="{{ route('admin.musyrif.index') }}">
        <i class="nav-icon cil-people"></i> Musyrif
    </a>
</li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('santri.master.*') ? 'active' : '' }}"
        href="{{ route('santri.master.index') }}">
        <i class="nav-icon cil-people"></i> Santri
    </a>
</li>
