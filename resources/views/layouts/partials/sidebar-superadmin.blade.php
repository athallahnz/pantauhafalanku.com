{{-- Sidebar untuk SUPERADMIN --}}
<li class="nav-title">Menu</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
        href="{{ route('superadmin.dashboard') }}">
        <i class="nav-icon cil-speedometer"></i> Dashboard
    </a>
</li>

<li class="nav-title">Manajemen</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}"
        href="{{ route('superadmin.users.index') }}">
        <i class="nav-icon cil-people"></i> Manajemen User
    </a>
</li>

<li class="nav-title">Master Data</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('kelas.*') ? 'active' : '' }}" href="{{ route('kelas.index') }}">
        <i class="nav-icon cil-school"></i> Kelas
    </a>
</li>
