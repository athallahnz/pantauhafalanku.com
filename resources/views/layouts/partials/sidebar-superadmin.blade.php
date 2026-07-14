{{-- =========================================================
    SIDEBAR SUPER ADMIN
    ========================================================= --}}

<li class="nav-title">Command Center</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('superadmin.dashboard') ? 'active' : '' }}"
        href="{{ route('superadmin.dashboard') }}">
        <i class="nav-icon cil-speedometer"></i>

        <div class="min-w-0">
            <div>Dashboard Sistem</div>
        </div>
    </a>
</li>

<li class="nav-title">User & Access</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('superadmin.users.*') ? 'active' : '' }}"
        href="{{ route('superadmin.users.index') }}">
        <i class="nav-icon cil-people"></i>

        <div class="min-w-0">
            <div>Manajemen User</div>
        </div>
    </a>
</li>

<li class="nav-title">Data Governance</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('superadmin.system-integrity.*') ? 'active' : '' }}"
        href="{{ route('superadmin.system-integrity.index') }}">
        <i class="nav-icon cil-shield-alt"></i>

        <div class="min-w-0">
            <div>Integritas Sistem</div>
        </div>
    </a>
</li>

<li class="nav-title">Master Akademik</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('kelas.*') ? 'active' : '' }}" href="{{ route('kelas.index') }}">
        <i class="nav-icon cil-school"></i>

        <div class="min-w-0">
            <div>Kelas & Akademik</div>
        </div>
    </a>
</li>

<li class="nav-title">Pengaturan</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('profile.settings*') ? 'active' : '' }}"
        href="{{ route('profile.settings') }}">
        <i class="nav-icon cil-settings"></i>

        <div class="min-w-0">
            <div>Profil & Keamanan</div>
        </div>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('superadmin.system-reviews.*') ? 'active' : '' }}"
        href="{{ route('superadmin.system-reviews.index') }}">
        <i class="nav-icon cil-comment-square"></i>

        <div class="min-w-0">
            <div>Review Sistem</div>
        </div>
    </a>
</li>
