<li class="nav-title">Menu</li>

{{-- Tempelkan pada sidebar yang digunakan role pimpinan. --}}
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('pimpinan.dashboard') ? 'active' : '' }}"
        href="{{ route('pimpinan.dashboard') }}">
        <i class="nav-icon bi bi-speedometer2"></i>
        Executive Dashboard
    </a>
</li>
